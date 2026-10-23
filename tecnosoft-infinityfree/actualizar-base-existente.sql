-- Aplicar en if0_43142556_tecnosoft después de respaldar. No borra datos.
CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(80) PRIMARY KEY,applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @ts_sql=(SELECT IF(COUNT(*)=0,'ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL','SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='email_verified_at');
PREPARE ts_stmt FROM @ts_sql; EXECUTE ts_stmt; DEALLOCATE PREPARE ts_stmt;
SET @ts_sql=(SELECT IF(COUNT(*)=0,'ALTER TABLE products ADD COLUMN cost_price DECIMAL(12,2) NULL CHECK(cost_price>=0)','SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='cost_price');
PREPARE ts_stmt FROM @ts_sql; EXECUTE ts_stmt; DEALLOCATE PREPARE ts_stmt;
SET @ts_sql=(SELECT IF(COUNT(*)=0,'ALTER TABLE products ADD COLUMN revision INT UNSIGNED NOT NULL DEFAULT 1','SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='revision');
PREPARE ts_stmt FROM @ts_sql; EXECUTE ts_stmt; DEALLOCATE PREPARE ts_stmt;
SET @ts_sql=(SELECT IF(COUNT(*)=0,'ALTER TABLE order_items ADD COLUMN unit_cost DECIMAL(12,2) NULL CHECK(unit_cost>=0)','SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='order_items' AND COLUMN_NAME='unit_cost');
PREPARE ts_stmt FROM @ts_sql; EXECUTE ts_stmt; DEALLOCATE PREPARE ts_stmt;
SET @ts_sql=(SELECT IF(COUNT(*)=0,'ALTER TABLE orders ADD COLUMN sales_channel ENUM(''web'',''store'') NOT NULL DEFAULT ''web''','SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='sales_channel');
PREPARE ts_stmt FROM @ts_sql; EXECUTE ts_stmt; DEALLOCATE PREPARE ts_stmt;
SET @ts_sql=(SELECT IF(COUNT(*)=0,'ALTER TABLE orders ADD COLUMN customer_name VARCHAR(100) NULL','SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='customer_name');
PREPARE ts_stmt FROM @ts_sql; EXECUTE ts_stmt; DEALLOCATE PREPARE ts_stmt;
SET @ts_sql=(SELECT IF(COUNT(*)=0,'ALTER TABLE orders ADD COLUMN customer_phone VARCHAR(25) NULL','SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='customer_phone');
PREPARE ts_stmt FROM @ts_sql; EXECUTE ts_stmt; DEALLOCATE PREPARE ts_stmt;
ALTER TABLE orders MODIFY delivery_method ENUM('pickup','delivery','digital') NOT NULL DEFAULT 'pickup';
CREATE TABLE IF NOT EXISTS email_verifications (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NOT NULL,
token_hash CHAR(64) NOT NULL UNIQUE,
expires_at DATETIME NOT NULL,
used_at DATETIME NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
INDEX idx_verification_user(user_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS audit_log (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
actor_id BIGINT UNSIGNED NOT NULL,
action VARCHAR(80) NOT NULL,
entity VARCHAR(80) NOT NULL,
entity_id BIGINT UNSIGNED NOT NULL,
details TEXT NOT NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(actor_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO settings(name,value) VALUES
('whatsapp','50587266171'),('pickup_point','Punto de retiro a coordinar por WhatsApp'),
('delivery_fee','0.00'),('transfer_instructions','Demostración académica: coordina los datos de transferencia con el administrador. No transfieras dinero real durante las pruebas.');

INSERT IGNORE INTO schema_migrations(version) VALUES ('2026-10-administracion');
