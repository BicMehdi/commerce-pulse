CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role VARCHAR(20) NOT NULL DEFAULT 'customer' CHECK (role IN ('customer','admin')),
 created_at DATETIME NOT NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(120) NOT NULL,
 description TEXT NOT NULL,
 price_cents INT NOT NULL CHECK (price_cents > 0),
 stock INT NOT NULL CHECK (stock >= 0),
 illustration VARCHAR(30) NOT NULL,
 active BOOLEAN NOT NULL DEFAULT TRUE,
 created_at DATETIME NOT NULL,
 CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES categories(id),
 INDEX idx_product_catalog (active,category_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 status VARCHAR(20) NOT NULL CHECK (status IN ('pending','processing','shipped','cancelled')),
 total_cents INT NOT NULL CHECK (total_cents > 0),
 idempotency_key CHAR(64) NOT NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(id),
 UNIQUE KEY unique_checkout_intent (user_id,idempotency_key),
 INDEX idx_order_date_status (created_at,status)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS order_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 product_name VARCHAR(120) NOT NULL,
 category_name VARCHAR(100) NOT NULL,
 quantity INT NOT NULL CHECK (quantity > 0),
 unit_price_cents INT NOT NULL CHECK (unit_price_cents > 0),
 CONSTRAINT fk_item_order FOREIGN KEY (order_id) REFERENCES orders(id),
 CONSTRAINT fk_item_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS login_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 scope_key CHAR(64) NOT NULL,
 attempted_at DATETIME NOT NULL,
 INDEX idx_attempt_scope_time (scope_key,attempted_at)
) ENGINE=InnoDB;
