-- ============================================================
--  Oil Supply & Delivery Management System — Full Database
--  Run this ONCE in phpMyAdmin before using the project
-- ============================================================

CREATE DATABASE IF NOT EXISTS oil_supply_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE oil_supply_db;

-- ── Users (all roles) ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  user_id      INT AUTO_INCREMENT PRIMARY KEY,
  email        VARCHAR(191) NOT NULL UNIQUE,
  phone_number VARCHAR(30)  NOT NULL,
  username     VARCHAR(100) NOT NULL,
  password     VARCHAR(255) NOT NULL,
  address      TEXT         NOT NULL,
  role         ENUM('customer','dealer','supplier','admin') NOT NULL DEFAULT 'customer',
  company      VARCHAR(200) DEFAULT NULL,
  billing_addr TEXT         DEFAULT NULL,
  status       ENUM('active','warning','banned') NOT NULL DEFAULT 'active',
  warn_reason  TEXT         DEFAULT NULL,
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ── Products (listed by supplier OR dealer) ───────────────
CREATE TABLE IF NOT EXISTS products (
  product_id      INT AUTO_INCREMENT PRIMARY KEY,
  seller_id       INT NOT NULL,          -- supplier_id OR dealer user_id
  name            VARCHAR(200) NOT NULL,
  details         TEXT,
  price           DECIMAL(10,2) NOT NULL,
  quantity        INT NOT NULL DEFAULT 0,
  sold            INT NOT NULL DEFAULT 0,
  photo           VARCHAR(255) DEFAULT NULL,
  bulk_threshold  INT NOT NULL DEFAULT 50,
  status          ENUM('available','unavailable') DEFAULT 'available',
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (seller_id) REFERENCES users(user_id)
);

-- ── Cart ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cart (
  cart_id    INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  product_id INT NOT NULL,
  quantity   INT NOT NULL DEFAULT 1,
  UNIQUE KEY uniq_cart (user_id, product_id),
  FOREIGN KEY (user_id)    REFERENCES users(user_id),
  FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- ── Orders ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
  order_id       INT AUTO_INCREMENT PRIMARY KEY,
  customer_id    INT NOT NULL,
  address        TEXT NOT NULL,
  total_price    DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount       DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_method ENUM('cash','card') DEFAULT 'cash',
  status         ENUM('pending','confirmed','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
  delivery_date  DATE DEFAULT NULL,
  delivery_slot  VARCHAR(30) DEFAULT NULL,
  contact_name   VARCHAR(100) DEFAULT NULL,
  contact_phone  VARCHAR(30)  DEFAULT NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES users(user_id)
);

-- ── Order Items ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
  item_id    INT AUTO_INCREMENT PRIMARY KEY,
  order_id   INT NOT NULL,
  product_id INT NOT NULL,
  seller_id  INT NOT NULL,
  quantity   INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id)   REFERENCES orders(order_id),
  FOREIGN KEY (product_id) REFERENCES products(product_id),
  FOREIGN KEY (seller_id)  REFERENCES users(user_id)
);

-- ── Messages (per-order chat) ─────────────────────────────
CREATE TABLE IF NOT EXISTS messages (
  msg_id      INT AUTO_INCREMENT PRIMARY KEY,
  order_id    INT NOT NULL,
  sender_id   INT NOT NULL,
  receiver_id INT NOT NULL,
  message     TEXT NOT NULL,
  is_read     TINYINT(1) DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id)    REFERENCES orders(order_id),
  FOREIGN KEY (sender_id)   REFERENCES users(user_id),
  FOREIGN KEY (receiver_id) REFERENCES users(user_id)
);

-- ── Feedback / Ratings ────────────────────────────────────
CREATE TABLE IF NOT EXISTS feedback (
  feedback_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id    INT NOT NULL UNIQUE,
  customer_id INT NOT NULL,
  seller_id   INT NOT NULL,
  rating      TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment     TEXT,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id)    REFERENCES orders(order_id),
  FOREIGN KEY (customer_id) REFERENCES users(user_id),
  FOREIGN KEY (seller_id)   REFERENCES users(user_id)
);

-- ── Disputes ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS disputes (
  dispute_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id   INT NOT NULL UNIQUE,
  user_id    INT NOT NULL,
  issue_type ENUM('Quantity Error','Quality Issue','Delivery Damage') NOT NULL,
  description TEXT NOT NULL,
  evidence   VARCHAR(255) DEFAULT NULL,
  status     ENUM('open','resolved') DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(order_id),
  FOREIGN KEY (user_id)  REFERENCES users(user_id)
);

-- ── Notifications ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
  notif_id   INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  order_id   INT DEFAULT NULL,
  message    TEXT NOT NULL,
  is_read    TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- ── Bulk Negotiations ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS negotiations (
  neg_id        INT AUTO_INCREMENT PRIMARY KEY,
  product_id    INT NOT NULL,
  customer_id   INT NOT NULL,
  seller_id     INT NOT NULL,
  quantity      INT NOT NULL,
  offered_price DECIMAL(10,2) DEFAULT NULL,
  message       TEXT,
  status        ENUM('pending','accepted','rejected','countered') DEFAULT 'pending',
  counter_price DECIMAL(10,2) DEFAULT NULL,
  counter_msg   TEXT,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id)  REFERENCES products(product_id),
  FOREIGN KEY (customer_id) REFERENCES users(user_id),
  FOREIGN KEY (seller_id)   REFERENCES users(user_id)
);

-- ── Market Prices ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS market_prices (
  price_id     INT AUTO_INCREMENT PRIMARY KEY,
  product_name VARCHAR(200) NOT NULL,
  region       VARCHAR(100) NOT NULL,
  price        DECIMAL(10,2) NOT NULL,
  recorded_at  DATE NOT NULL DEFAULT (CURRENT_DATE)
);

-- ── Sample market data ────────────────────────────────────
INSERT INTO market_prices (product_name, region, price, recorded_at) VALUES
('Industrial Lubricant Oil','North Region', 88.00,'2025-07-01'),
('Industrial Lubricant Oil','North Region', 89.50,'2025-08-01'),
('Industrial Lubricant Oil','North Region', 91.00,'2025-09-01'),
('Industrial Lubricant Oil','North Region', 93.00,'2025-10-01'),
('Industrial Lubricant Oil','North Region', 92.00,'2025-11-01'),
('Industrial Lubricant Oil','North Region', 95.00,'2025-12-01'),
('Transformer Oil',         'South Region', 96.00,'2025-12-01'),
('Crude Oil (Light Sweet)', 'East Region',  90.00,'2025-12-01'),
('Transformer Oil',         'West Region',  98.00,'2025-12-01');
