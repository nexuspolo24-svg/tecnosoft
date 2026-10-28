-- Recuperación por correo. Conserva usuarios, roles y pedidos.
-- La columna users.password_version se agrega mediante tools/migrate.php.
CREATE TABLE IF NOT EXISTS password_resets (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NULL,
email_hash CHAR(64) NOT NULL,
ip_hash CHAR(64) NOT NULL,
token_hash CHAR(64) NULL UNIQUE,
expires_at DATETIME NULL,
used_at DATETIME NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
INDEX idx_reset_user(user_id,created_at),
INDEX idx_reset_email(email_hash,created_at),
INDEX idx_reset_ip(ip_hash,created_at)
) ENGINE=InnoDB;
