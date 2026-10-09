import csv
import json
import tempfile
import unittest
from datetime import date
from pathlib import Path
from pipeline import HEADERS, DataQualityError, build_report, write_atomic

class SalesPipelineTests(unittest.TestCase):
    def setUp(self):
        root = (Path(__file__).resolve().parents[1] / 'storage' / 'test-tmp').resolve()
        root.mkdir(parents=True, exist_ok=True)
        self.temp = tempfile.TemporaryDirectory(dir=root)
        if not Path(self.temp.name).resolve().is_relative_to(root):
            raise RuntimeError('Test temporary directory escaped its workspace')
        self.path = Path(self.temp.name) / "sales.csv"
        self.rows = [
            [1, 10, "shipped", "2026-01-15 10:00:00", 3500, 1, 2, "Carnet", "Organisation", 2, 1000],
            [1, 10, "shipped", "2026-01-15 10:00:00", 3500, 2, 3, "Lampe", "Équipement", 1, 1500],
            [2, 10, "pending", "2026-01-20 10:00:00", 1000, 3, 2, "Carnet", "Organisation", 1, 1000],
            [3, 20, "cancelled", "2026-01-20 11:00:00", 1500, 4, 3, "Lampe", "Équipement", 1, 1500],
            [4, 30, "shipped", "2025-12-10 10:00:00", 1500, 5, 3, "Lampe", "Équipement", 1, 1500],
        ]
    def tearDown(self): self.temp.cleanup()
    def report(self, rows=None):
        with self.path.open("w", encoding="utf-8", newline="") as stream:
            writer=csv.writer(stream); writer.writerow(HEADERS); writer.writerows(self.rows if rows is None else rows)
        return build_report(self.path, date(2026, 1, 31))
    def test_counts_orders_once_and_excludes_cancelled_value(self):
        report=self.report(); k=report["kpis"]
        self.assertEqual(k["active_orders"],3); self.assertEqual(k["booked_value_cents"],6000)
        self.assertEqual(k["average_order_cents"],2000); self.assertEqual(k["repeat_customers"],1)
        self.assertEqual(k["cancelled_orders"],1); self.assertEqual(report["quality"]["rows_checked"],5)
    def test_exact_windows_and_zero_filled_days(self):
        report=self.report(); self.assertEqual(report["kpis"]["last_30_days_value_cents"],4500)
        self.assertEqual(report["kpis"]["previous_30_days_value_cents"],1500)
        self.assertEqual(report["kpis"]["change_percent"],200.0)
        self.assertEqual(len(report["daily"]),30); self.assertEqual(report["daily"][0]["date"],"2026-01-02")
        self.assertEqual(sum(day["value_cents"] for day in report["daily"]),4500)
    def test_duplicate_line_is_rejected(self):
        with self.assertRaisesRegex(DataQualityError,"duplicate"): self.report(self.rows+[self.rows[0]])
    def test_inconsistent_order_metadata_is_rejected(self):
        self.rows[1][2]="cancelled"
        with self.assertRaisesRegex(DataQualityError,"metadata"): self.report()
    def test_totals_are_reconciled(self):
        self.rows[0][9]=3
        with self.assertRaisesRegex(DataQualityError,"totals"): self.report()
    def test_bad_money_quantity_date_and_status_are_rejected(self):
        for index,value in [(10,"1.5"),(9,0),(3,"2026-02-01 10:00:00"),(2,"refunded")]:
            with self.subTest(index=index):
                row=self.rows[2].copy(); row[index]=value
                with self.assertRaises(DataQualityError): self.report([row])
    def test_no_previous_period_does_not_invent_growth(self):
        self.assertIsNone(self.report(self.rows[:3])["kpis"]["change_percent"])
    def test_empty_export_is_a_valid_zero_report(self):
        report=self.report([]); self.assertEqual(report["kpis"]["average_order_cents"],0)
        self.assertEqual(report["quality"]["orders_checked"],0)
    def test_atomic_report_replaces_previous_file(self):
        target=Path(self.temp.name)/"report.json"; target.write_text("old")
        write_atomic(self.report(),target)
        self.assertEqual(json.loads(target.read_text())["schema_version"],1)
        self.assertEqual(len(list(Path(self.temp.name).glob("tmp*"))),0)
    def test_header_contract_is_enforced(self):
        self.path.write_text("order_id,amount\n1,10\n")
        with self.assertRaisesRegex(DataQualityError,"columns"): build_report(self.path)

if __name__=="__main__": unittest.main()
