CREATE TABLE IF NOT EXISTS email_verifications (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NOT NULL,
token_hash CHAR(64) NOT NULL UNIQUE,
expires_at DATETIME NOT NULL,
used_at DATETIME NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
INDEX idx_verification_user(user_id,created_at)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS audit_log (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
actor_id BIGINT UNSIGNED NOT NULL,
action VARCHAR(80) NOT NULL,
entity VARCHAR(80) NOT NULL,
entity_id BIGINT UNSIGNED NOT NULL,
details TEXT NOT NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(actor_id) REFERENCES users(id)
) ENGINE=InnoDB;
INSERT IGNORE INTO settings(name,value) VALUES
('whatsapp','50587266171'),('pickup_point','Punto de retiro a coordinar por WhatsApp'),
('delivery_fee','0.00'),('transfer_instructions','Demostración académica: coordina los datos de transferencia con el administrador. No transfieras dinero real durante las pruebas.');
