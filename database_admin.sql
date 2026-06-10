CREATE DATABASE IF NOT EXISTS jly_projectbali
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE jly_projectbali;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount_price DECIMAL(12,2) NULL,
  size VARCHAR(120) NULL,
  material VARCHAR(150) NULL,
  flowers VARCHAR(150) NULL,
  bundle_note VARCHAR(255) NULL,
  image_url TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_products_category (category_id),
  KEY idx_products_active_featured (is_active, is_featured),
  KEY idx_products_price (price, discount_price),
  CONSTRAINT fk_products_category
    FOREIGN KEY (category_id) REFERENCES categories(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  booked_date DATE NOT NULL,
  customer_name VARCHAR(150) NULL,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_product_booked_date (product_id, booked_date),
  KEY idx_product_bookings_date (booked_date),
  CONSTRAINT fk_product_bookings_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_name VARCHAR(150) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  email VARCHAR(150) NULL,
  event_date DATE NOT NULL,
  event_location VARCHAR(255) NULL,
  note TEXT NULL,
  status ENUM('new', 'confirmed', 'cancelled') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_booking_requests_event_date (event_date),
  KEY idx_booking_requests_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_request_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_request_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  product_name VARCHAR(150) NOT NULL,
  price_label VARCHAR(80) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_booking_request_product (booking_request_id, product_id),
  KEY idx_booking_request_items_product (product_id),
  CONSTRAINT fk_booking_request_items_request
    FOREIGN KEY (booking_request_id) REFERENCES booking_requests(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_booking_request_items_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO admins (username, password_hash)
VALUES ('admin', '$2y$10$FelfB/zeZICFlsZ3kyFuIunQEWZU.xZPfhOsR4cVAnA1QMJ1gNuYa');

INSERT IGNORE INTO categories (id, name) VALUES
(1, 'Signature'),
(2, 'Custom Made'),
(3, 'Modern'),
(4, 'Gallery'),
(5, 'Display'),
(6, 'Reception');

INSERT INTO products
  (category_id, name, description, price, discount_price, size, material, flowers, bundle_note, image_url, is_active, is_featured)
VALUES
(1, 'Mirror Selfie Klasik', 'Mirror selfie dengan frame gold ukiran elegan, cocok untuk area entrance, photo corner, atau foyer venue.', 1500000, NULL, '180 x 80 cm', 'Kaca, frame resin gold', 'Opsional, 1 rangkaian sudut', 'Bisa bundling dengan welcome sign dan table number', 'https://lh3.googleusercontent.com/aida-public/AB6AXuCyYxKcsb4TDK0GcB7T4ZLXJLsWI7dSzGuYM8f9kn8EyVlKTfx0I1wkUrtDV67F13Ai92NG0r6ac9_0YBHBCrdTQQpDVAkrXji107TUUbC7aY14F7sV2G6lhuyHQRoC-UswGsYDIbhpF4L09H_BfEj1BbY5s8n9pCi4Tdh9yHNTbohOoVIsu9QX5I_63fIU2gyivrc18Xfxn4h9zG2bgY1SMX6GE3n0XKFdKqSDSUlt0j5WRP9alPV1cfBcZHJyiIRlZUSrMgyQxM4g', 1, 1),
(2, 'Welcome Sign Akrilik', 'Papan akrilik transparan dengan kaligrafi custom untuk menyambut tamu dengan tampilan modern.', 450000, NULL, '60 x 90 cm', 'Akrilik bening, stand besi', 'Opsional, 2 rangkaian kecil', 'Bisa bundling dengan mirror selfie', 'https://lh3.googleusercontent.com/aida-public/AB6AXuAMTQjNtNNyhcQaVGXOIJJCTHYf_X-wJ60NWHkhgJxcwfRsqZ5fcu9wC6xytsCSQ-mFb0FhNTym7wc7JHy_rSWUzY-RgOueFgQIlJUz2DcaI4SR4S2fkaGRX86DdAGOuDyNIDiWG1YpvWvsvNL2J5U3u0tz81vldLfs0rLYzwzCPhSs3kSlLi3ZxcjfrTdl0od000LYgMXZgTtYZFZ8Do7BiD_tn6Xu398d3On8I9wKMCsr7LtWdLX50RmAk_LRIvbg0RgwzV9-PaDn', 1, 1),
(3, 'Stand Foto Minimalis', 'Stand metal ramping untuk galeri pre-wedding, seating chart, atau display foto keluarga.', 750000, NULL, '160 x 55 cm', 'Metal powder-coated', 'Opsional, 1 rangkaian kecil', '', 'https://lh3.googleusercontent.com/aida-public/AB6AXuB6Qn92blLpsdjPdDIKOfLq4nTPKCTgK5AxKFWzUNEgup8p88Rx72lPettO3L8UCfnBjfFqP2uxrwFu7b26-5urtaO8e5Qc3g6Dv0eME51kDgisUM5ShENliN6y9kRxlygluLn0nGp-DFPlONu3sxDZXHAgcynrfV6ZBeQjIc1vn5foPnwSZ7213wSZsjhdVLzMGWwLTm--TfEwuUZhMUV4RBdQyK2bKIUs0kf9Be4Ab3iDFTLMennOeTjis6XOHXKLmJqpjeS_oInh', 1, 1),
(4, 'Set Gallery Frame', 'Rangkaian frame foto elegan untuk meja penerima tamu, gallery walk, atau area lounge.', 650000, NULL, 'A4, A3, 40 x 60 cm', 'Frame kayu, kaca bening', 'Opsional, 2 rangkaian meja', '', '', 1, 0),
(5, 'Plinth Display Ivory', 'Pedestal berwarna ivory untuk display bunga, mahar, hampers, atau detail dekorasi pilihan.', 850000, NULL, 'Set 3: 40, 60, 80 cm', 'Multipleks finishing ivory', 'Opsional, 3 rangkaian kecil', 'Bundling dengan set gallery frame', '', 1, 0),
(6, 'Table Number Akrilik', 'Nomor meja akrilik minimalis untuk menjaga tampilan meja tamu tetap bersih dan elegan.', 250000, NULL, '12 x 18 cm per nomor', 'Akrilik bening 3 mm', 'Tidak termasuk dekor bunga', '', '', 1, 0);

INSERT IGNORE INTO product_bookings (product_id, booked_date, customer_name, note) VALUES
(1, '2026-06-14', 'Sample booking', 'Contoh tanggal sudah dipesan'),
(1, '2026-06-21', 'Sample booking', 'Contoh tanggal sudah dipesan'),
(2, '2026-06-14', 'Sample booking', 'Contoh tanggal sudah dipesan'),
(3, '2026-07-05', 'Sample booking', 'Contoh tanggal sudah dipesan');
