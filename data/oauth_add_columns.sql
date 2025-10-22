USE `if0_40225463_tienda`;
ALTER TABLE users
  ADD COLUMN google_sub VARCHAR(64) NULL UNIQUE AFTER email,
  ADD COLUMN avatar_url VARCHAR(500) NULL AFTER google_sub,
  ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER avatar_url;
