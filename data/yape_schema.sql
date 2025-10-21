USE `ferre_style`;

ALTER TABLE orders
  ADD COLUMN payment_method ENUM('yape') NOT NULL DEFAULT 'yape' AFTER total,
  ADD COLUMN payment_status ENUM('pending','paid','rejected') NOT NULL DEFAULT 'pending' AFTER payment_method,
  ADD COLUMN payment_ref VARCHAR(100) NULL AFTER payment_status,
  ADD COLUMN voucher_path VARCHAR(255) NULL AFTER payment_ref;
