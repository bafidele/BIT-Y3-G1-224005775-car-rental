-- Apply once to an existing car rental database before enabling password resets.
-- This table is additive; it does not alter customer accounts or rental history.
CREATE TABLE IF NOT EXISTS customer_password_resets (
  reset_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  PRIMARY KEY (reset_id),
  UNIQUE KEY uq_customer_password_resets_token (token_hash),
  KEY idx_customer_password_resets_customer (customer_id, created_at),
  KEY idx_customer_password_resets_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
