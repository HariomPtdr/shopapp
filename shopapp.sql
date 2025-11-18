CREATE DATABASE IF NOT EXISTS shopapp;
USE shopapp;

-- USERS
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(150) UNIQUE,
  password VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PRODUCTS
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CARTS (one per user)
CREATE TABLE carts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CART ITEMS
CREATE TABLE cart_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cart_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ORDERS
CREATE TABLE orders (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  name VARCHAR(150),
  phone VARCHAR(50),
  subtotal DECIMAL(12,2),
  discount DECIMAL(12,2),
  total DECIMAL(12,2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ORDER ITEMS
CREATE TABLE order_items (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  product_id INT NULL,
  product_name VARCHAR(255),
  unit_price DECIMAL(10,2),
  quantity INT,
  line_total DECIMAL(12,2),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SAMPLE PRODUCTS
INSERT INTO products (name, description, price) VALUES
('Fresh Apple', 'Crisp red apples - 1kg', 120.00),
('Organic Milk', '1 L full cream milk', 55.00),
('Multigrain Bread', 'Fresh multigrain loaf', 45.00),
('Farm Eggs (6)', 'Pack of 6 fresh eggs', 75.00),
('Banana (1 dozen)', 'Ripe yellow bananas', 60.00),
('Paneer 200g', 'Cottage cheese 200g pack', 95.00);

-- DEMO USER (password: 123456)
INSERT INTO users (name, email, password) VALUES
('Hariom Patidar', 'test@example.com', '$2y$10$u7rF6wKPVJ6PHHPZtZ1BhuOljmGrnxutgqVZkGkENpfy5so8ic6Pm');
