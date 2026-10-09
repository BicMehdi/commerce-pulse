CREATE TABLE IF NOT EXISTS users (
 id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE,
 password_hash TEXT NOT NULL, role TEXT NOT NULL DEFAULT 'customer' CHECK(role IN ('customer','admin')), created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE);
CREATE TABLE IF NOT EXISTS products (
 id INTEGER PRIMARY KEY AUTOINCREMENT, category_id INTEGER NOT NULL REFERENCES categories(id),
 name TEXT NOT NULL, description TEXT NOT NULL, price_cents INTEGER NOT NULL CHECK(price_cents>0),
 stock INTEGER NOT NULL CHECK(stock>=0), illustration TEXT NOT NULL, active INTEGER NOT NULL DEFAULT 1,
 created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_product_catalog ON products(active,category_id);
CREATE TABLE IF NOT EXISTS orders (
 id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL REFERENCES users(id),
 status TEXT NOT NULL CHECK(status IN ('pending','processing','shipped','cancelled')),
 total_cents INTEGER NOT NULL CHECK(total_cents>0), idempotency_key TEXT NOT NULL,
 created_at TEXT NOT NULL, updated_at TEXT NOT NULL, UNIQUE(user_id,idempotency_key)
);
CREATE INDEX IF NOT EXISTS idx_order_date_status ON orders(created_at,status);
CREATE TABLE IF NOT EXISTS order_items (
 id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER NOT NULL REFERENCES orders(id),
 product_id INTEGER NOT NULL REFERENCES products(id), product_name TEXT NOT NULL, category_name TEXT NOT NULL,
 quantity INTEGER NOT NULL CHECK(quantity>0), unit_price_cents INTEGER NOT NULL CHECK(unit_price_cents>0)
);
CREATE TABLE IF NOT EXISTS login_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, scope_key TEXT NOT NULL, attempted_at TEXT NOT NULL);
CREATE INDEX IF NOT EXISTS idx_attempt_scope_time ON login_attempts(scope_key,attempted_at);
