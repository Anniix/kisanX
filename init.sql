CREATE DATABASE IF NOT EXISTS kisandirect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kisandirect;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('farmer','customer') NOT NULL DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  farmer_id INT NOT NULL,
  category_id INT DEFAULT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  qty INT NOT NULL DEFAULT 0,
  sold_qty INT NOT NULL DEFAULT 0,
  image VARCHAR(255) DEFAULT NULL,
  discount_percent INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- ✅ UPDATED ORDERS TABLE (Matches Checkout Page)
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL, -- Renamed to match PHP code
  status ENUM('pending','dispatched','in_transit','delivered','cancelled') DEFAULT 'pending',
  
  -- New Columns for Checkout
  full_name VARCHAR(150) DEFAULT NULL,
  address TEXT DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  zip_code VARCHAR(20) DEFAULT NULL,
  payment_method VARCHAR(50) DEFAULT 'cod',
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE live_activity (
  id int(11) NOT NULL AUTO_INCREMENT,
  farmer_id int(11) NOT NULL,
  product_name varchar(255) NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS deliveries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  lat DOUBLE DEFAULT NULL,
  lng DOUBLE DEFAULT NULL,
  status VARCHAR(100) DEFAULT 'Preparing',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cart_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_cart (user_id, product_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ❌ OLD SHIPPING TABLE (Not needed anymore, merged into orders)
-- CREATE TABLE IF NOT EXISTS shipping (...); 

-- sample categories
INSERT INTO categories (name) VALUES ('Vegetables'), ('Fruits'), ('Grains'), ('Spices');

-- sample users
INSERT INTO users (name,email,password,role) VALUES
('Ravi Farmer','ravi@farmer.com',SHA2('farmer123',256),'farmer'),
('Anita Farmer','anita@farmer.com',SHA2('farmer123',256),'farmer'),
('Sunil Buyer','sunil@buyers.com',SHA2('buyer123',256),'customer');

-- sample products
INSERT INTO products (farmer_id,category_id,name,description,price,qty,sold_qty,image,discount_percent) VALUES
(1,1,'Fresh Tomatoes','Organic red tomatoes — bulk (10kg)',1200.00,50,0,'tomatoes.jpg',10),
(1,1,'Potatoes','High-quality potatoes — bulk (50kg)',800.00,30,0,'potatoes.jpg',5),
(2,1,'Onions','Sweet onions — bulk (25kg)',600.00,40,0,'onions.jpg',0),
(2,2,'Bananas','Ripe bananas — bunch (100pcs)',900.00,20,0,'bananas.jpg',8);

-- sample order (Updated structure)
INSERT INTO orders (user_id, total_amount, status, full_name, address, city, zip_code, payment_method) 
VALUES (3, 1800.00, 'in_transit', 'Sunil Buyer', '123 Market Road', 'Delhi', '110001', 'cod');

INSERT INTO order_items (order_id,product_id,qty,price) VALUES (1,1,1,1200.00),(1,4,1,600.00);
INSERT INTO deliveries (order_id,lat,lng,status) VALUES (1,28.7041,77.1025,'Out for delivery');