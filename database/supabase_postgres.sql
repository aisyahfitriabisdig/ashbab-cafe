-- ============================================================
-- ASHBAB CAFE - Supabase (PostgreSQL) Database Schema
-- File: database/supabase_postgres.sql
-- Jalankan isi file ini di Supabase -> SQL Editor
-- ============================================================

-- ============================================================
-- Table: sessions (untuk PHP Session di Vercel Serverless)
-- ============================================================
CREATE TABLE IF NOT EXISTS sessions (
  id VARCHAR(128) PRIMARY KEY,
  data TEXT NOT NULL DEFAULT '',
  last_activity INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON sessions (last_activity);

-- ============================================================
-- Table: uploaded_files (fallback media storage untuk Vercel)
-- ============================================================
CREATE TABLE IF NOT EXISTS uploaded_files (
  id VARCHAR(191) PRIMARY KEY,
  mime_type VARCHAR(100) NOT NULL,
  data TEXT NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Table: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'cashier' CHECK (role IN ('admin', 'cashier')),
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Table: categories
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  icon VARCHAR(100) DEFAULT 'fa-utensils',
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Table: menus
-- ============================================================
CREATE TABLE IF NOT EXISTS menus (
  id SERIAL PRIMARY KEY,
  category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  stock INTEGER NOT NULL DEFAULT 0,
  image VARCHAR(255) DEFAULT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'available' CHECK (status IN ('available', 'unavailable')),
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_menus_category_id ON menus (category_id);

-- ============================================================
-- Table: orders
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
  id SERIAL PRIMARY KEY,
  order_number VARCHAR(30) NOT NULL UNIQUE,
  customer_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  table_number VARCHAR(10) DEFAULT NULL,
  notes TEXT,
  total_amount DECIMAL(10,2) NOT NULL,
  payment_method VARCHAR(20) NOT NULL CHECK (payment_method IN ('transfer', 'qris')),
  payment_proof VARCHAR(255) DEFAULT NULL,
  payment_status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (payment_status IN ('pending', 'confirmed', 'rejected')),
  order_status VARCHAR(20) NOT NULL DEFAULT 'waiting' CHECK (order_status IN ('waiting', 'accepted', 'processing', 'completed', 'cancelled')),
  is_submitted SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Trigger otomatis untuk updated_at pada tabel orders
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = CURRENT_TIMESTAMP;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_orders_updated_at ON orders;
CREATE TRIGGER trg_orders_updated_at
BEFORE UPDATE ON orders
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();

-- ============================================================
-- Table: order_items
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
  id SERIAL PRIMARY KEY,
  order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  menu_id INTEGER NOT NULL REFERENCES menus(id),
  quantity INTEGER NOT NULL DEFAULT 1,
  price DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items (order_id);
CREATE INDEX IF NOT EXISTS idx_order_items_menu_id ON order_items (menu_id);

-- ============================================================
-- Table: expenses
-- ============================================================
CREATE TABLE IF NOT EXISTS expenses (
  id SERIAL PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(100) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  description TEXT,
  expense_date DATE NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Table: settings
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
  id SERIAL PRIMARY KEY,
  cafe_name VARCHAR(100) NOT NULL DEFAULT 'ASHBAB CAFE',
  logo VARCHAR(255) DEFAULT NULL,
  favicon VARCHAR(255) DEFAULT NULL,
  banner TEXT DEFAULT NULL,
  address TEXT,
  phone VARCHAR(20) DEFAULT NULL,
  email VARCHAR(100) DEFAULT NULL,
  qris_image VARCHAR(255) DEFAULT NULL,
  bank_account TEXT,
  footer_text TEXT,
  tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  about_image VARCHAR(255) DEFAULT NULL,
  menu_bg_config TEXT DEFAULT NULL
);

-- ============================================================
-- Table: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
  id SERIAL PRIMARY KEY,
  order_id INTEGER DEFAULT NULL,
  target VARCHAR(20) NOT NULL DEFAULT 'admin' CHECK (target IN ('admin', 'customer')),
  title VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'unread' CHECK (status IN ('unread', 'read')),
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_notifications_order_id ON notifications (order_id);

-- ============================================================
-- Seed Data
-- ============================================================

-- Default Users (password: 'password' - segera ubah setelah login!)
INSERT INTO users (name, username, password, role) VALUES
('Super Admin', 'admin', '$2b$12$Y8jnXlbNe4.e9Um9aecGeulSea8LQUmGzl6euMuXnqcxRYK3u0Aj2', 'admin'),
('Kasir Utama', 'kasir', '$2b$12$Y8jnXlbNe4.e9Um9aecGeulSea8LQUmGzl6euMuXnqcxRYK3u0Aj2', 'cashier')
ON CONFLICT (username) DO NOTHING;

-- Default Categories
INSERT INTO categories (name, icon)
SELECT * FROM (VALUES
  ('Makanan', 'fa-utensils'),
  ('Minuman', 'fa-glass-water'),
  ('Coffee', 'fa-mug-hot'),
  ('Non Coffee', 'fa-cup-straw'),
  ('Snack', 'fa-cookie-bite'),
  ('Dessert', 'fa-ice-cream')
) AS v(name, icon)
WHERE NOT EXISTS (SELECT 1 FROM categories);

-- Default Settings
INSERT INTO settings (cafe_name, address, phone, email, footer_text, tax_percent)
SELECT 'ASHBAB CAFE', 'Jl. Contoh No. 123, Jakarta', '08123456789', 'ashbabcafe@email.com', '© 2026 ASHBAB CAFE. All Rights Reserved.', 10.00
WHERE NOT EXISTS (SELECT 1 FROM settings);

-- Sample Menus
INSERT INTO menus (category_id, name, description, price, stock, status)
SELECT * FROM (VALUES
  (3, 'Espresso', 'Kopi espresso murni yang kuat dan nikmat', 18000, 50, 'available'),
  (3, 'Americano', 'Espresso dengan tambahan air panas yang menyegarkan', 22000, 50, 'available'),
  (3, 'Cappuccino', 'Espresso dengan susu steam dan foam yang creamy', 28000, 50, 'available'),
  (3, 'Latte', 'Espresso dengan susu steam yang lembut dan creamy', 30000, 50, 'available'),
  (3, 'Caramel Macchiato', 'Latte dengan saus karamel yang manis dan menggoda', 35000, 50, 'available'),
  (4, 'Matcha Latte', 'Teh hijau matcha premium dengan susu yang creamy', 32000, 30, 'available'),
  (4, 'Chocolate', 'Cokelat panas yang kaya dan menghangatkan', 28000, 30, 'available'),
  (4, 'Strawberry Juice', 'Jus stroberi segar dengan rasa yang manis asam', 25000, 20, 'available'),
  (1, 'Nasi Goreng Spesial', 'Nasi goreng dengan bumbu spesial, telur, dan ayam', 35000, 20, 'available'),
  (1, 'Mie Goreng', 'Mie goreng dengan bumbu khas dan topping lengkap', 30000, 20, 'available'),
  (5, 'Kentang Goreng', 'Kentang goreng crispy dengan saus pilihan', 20000, 30, 'available'),
  (5, 'Onion Ring', 'Onion ring crispy yang renyah dan gurih', 22000, 25, 'available'),
  (6, 'Es Krim', 'Es krim premium dengan berbagai pilihan rasa', 25000, 15, 'available'),
  (6, 'Pudding Cokelat', 'Pudding cokelat lembut dengan saus karamel', 18000, 20, 'available')
) AS v(category_id, name, description, price, stock, status)
WHERE NOT EXISTS (SELECT 1 FROM menus);

