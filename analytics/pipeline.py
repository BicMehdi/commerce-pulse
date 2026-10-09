"""Validate an order-line CSV and produce a reproducible sales report.

Amounts remain integer euro cents. Non-cancelled orders are booked order value,
not collected payment. Customer identifiers are pseudonymous, not anonymous.
"""
from __future__ import annotations
import argparse
import csv
import json
import os
import tempfile
from collections import Counter, defaultdict
from datetime import date, datetime, timedelta, timezone
from pathlib import Path

HEADERS = ["order_id", "customer_id", "status", "created_at", "order_total_cents", "line_id", "product_id", "product_name", "category_name", "quantity", "unit_price_cents"]
STATUSES = {"pending", "processing", "shipped", "cancelled"}

class DataQualityError(ValueError):
    pass

def positive_integer(value: str, field: str, row: int) -> int:
    if not value.isascii() or not value.isdecimal() or int(value) <= 0:
        raise DataQualityError(f"Row {row}: {field} must be a positive integer")
    return int(value)

def build_report(path: Path, as_of: date | None = None) -> dict:
    as_of = as_of or datetime.now(timezone.utc).date()
    orders: dict[int, dict] = {}
    seen_lines: set[int] = set()
    row_count = 0
    with path.open(encoding="utf-8-sig", newline="") as stream:
        reader = csv.DictReader(stream)
        if reader.fieldnames != HEADERS:
            raise DataQualityError("Unexpected CSV columns or order")
        for number, row in enumerate(reader, 2):
            row_count += 1
            if None in row or any(value is None for value in row.values()):
                raise DataQualityError(f"Row {number}: wrong column count")
            numeric = {key: positive_integer(row[key], key, number) for key in ["order_id", "customer_id", "order_total_cents", "line_id", "product_id", "quantity", "unit_price_cents"]}
            if numeric["line_id"] in seen_lines:
                raise DataQualityError(f"Row {number}: duplicate line_id")
            seen_lines.add(numeric["line_id"])
            if row["status"] not in STATUSES:
                raise DataQualityError(f"Row {number}: unknown status")
            try:
                timestamp = datetime.strptime(row["created_at"], "%Y-%m-%d %H:%M:%S")
            except ValueError as error:
                raise DataQualityError(f"Row {number}: invalid UTC timestamp") from error
            if timestamp.date() > as_of:
                raise DataQualityError(f"Row {number}: order date is in the future")
            if not row["product_name"].strip() or not row["category_name"].strip():
                raise DataQualityError(f"Row {number}: missing product/category")
            metadata = (numeric["customer_id"], row["status"], row["created_at"], numeric["order_total_cents"])
            order = orders.setdefault(numeric["order_id"], {"metadata": metadata, "date": timestamp.date(), "lines": []})
            if order["metadata"] != metadata:
                raise DataQualityError(f"Row {number}: inconsistent order metadata")
            order["lines"].append(numeric | {"name": row["product_name"], "category": row["category_name"]})
    for order_id, order in orders.items():
        computed = sum(line["quantity"] * line["unit_price_cents"] for line in order["lines"])
        if computed != order["metadata"][3]:
            raise DataQualityError(f"Order {order_id}: line totals do not match order total")

    status_counts = Counter(order["metadata"][1] for order in orders.values())
    active = [order for order in orders.values() if order["metadata"][1] != "cancelled"]
    start = as_of - timedelta(days=29)
    previous_start = as_of - timedelta(days=59)
    current = [order for order in active if start <= order["date"] <= as_of]
    previous = [order for order in active if previous_start <= order["date"] < start]
    booked = sum(order["metadata"][3] for order in active)
    booked30 = sum(order["metadata"][3] for order in current)
    previous30 = sum(order["metadata"][3] for order in previous)
    daily = defaultdict(lambda: {"value_cents": 0, "orders": 0})
    monthly = defaultdict(lambda: {"value_cents": 0, "orders": 0})
    products = defaultdict(lambda: {"quantity": 0, "value_cents": 0, "name": ""})
    categories = defaultdict(lambda: {"quantity": 0, "value_cents": 0})
    customer_counts = Counter()
    for order in active:
        total = order["metadata"][3]
        customer_counts[order["metadata"][0]] += 1
        monthly[order["date"].strftime("%Y-%m")]["value_cents"] += total
        monthly[order["date"].strftime("%Y-%m")]["orders"] += 1
        if start <= order["date"] <= as_of:
            daily[order["date"].isoformat()]["value_cents"] += total
            daily[order["date"].isoformat()]["orders"] += 1
        for line in order["lines"]:
            value = line["quantity"] * line["unit_price_cents"]
            product = products[line["product_id"]]
            product["name"] = line["name"]
            product["quantity"] += line["quantity"]
            product["value_cents"] += value
            categories[line["category"]]["quantity"] += line["quantity"]
            categories[line["category"]]["value_cents"] += value
    return {
        "schema_version": 1,
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "as_of": as_of.isoformat(),
        "source": "MySQL/SQLite order-line export; all timestamps UTC",
        "definitions": {"value": "Non-cancelled order value, not collected payment", "last_30_days": f"{start} through {as_of}, inclusive", "average": "Rounded to nearest cent (half up)", "history": "All orders in the input export"},
        "quality": {"rows_checked": row_count, "orders_checked": len(orders), "duplicate_lines": 0, "invalid_rows": 0},
        "kpis": {"booked_value_cents": booked, "active_orders": len(active), "last_30_days_value_cents": booked30, "last_30_days_orders": len(current), "previous_30_days_value_cents": previous30, "change_percent": round((booked30 - previous30) / previous30 * 100, 1) if previous30 else None, "average_order_cents": (booked * 2 + len(active)) // (2 * len(active)) if active else 0, "active_customers": len(customer_counts), "repeat_customers": sum(count > 1 for count in customer_counts.values()), "cancelled_orders": status_counts["cancelled"]},
        "daily": [{"date": (start + timedelta(days=i)).isoformat(), **daily[(start + timedelta(days=i)).isoformat()]} for i in range(30)],
        "monthly": [{"month": key, **value} for key, value in sorted(monthly.items())],
        "products": sorted([{"product_id": key, **value} for key, value in products.items()], key=lambda p: (-p["value_cents"], p["product_id"]))[:5],
        "categories": [{"name": key, **value} for key, value in sorted(categories.items())],
        "statuses": {status: status_counts[status] for status in sorted(STATUSES)},
    }

def write_atomic(report: dict, output: Path) -> None:
    output.parent.mkdir(parents=True, exist_ok=True)
    temporary = None
    try:
        with tempfile.NamedTemporaryFile(mode="w", encoding="utf-8", dir=output.parent, delete=False) as stream:
            temporary = Path(stream.name)
            json.dump(report, stream, ensure_ascii=False, indent=2)
            stream.write("\n")
        os.replace(temporary, output)
    finally:
        if temporary and temporary.exists():
            temporary.unlink()

def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--input", type=Path, required=True)
    parser.add_argument("--output", type=Path, required=True)
    parser.add_argument("--as-of", type=date.fromisoformat)
    args = parser.parse_args()
    try:
        report = build_report(args.input, args.as_of)
    except (DataQualityError, OSError) as error:
        parser.exit(1, f"Data quality failure: {error}\n")
    write_atomic(report, args.output)
    print(f"Report published: {report['quality']['orders_checked']} orders validated.")

if __name__ == "__main__":
    main()
