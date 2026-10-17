-- TecnoSoft Solutions · MariaDB 10.4 / MySQL 8 · UTF-8
-- Instalación nueva; no borra tablas ni datos.
CREATE DATABASE tecnosoft CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tecnosoft;

CREATE TABLE roles (
id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(40) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE users (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL,
email VARCHAR(190) NOT NULL UNIQUE,
password_hash VARCHAR(255) NOT NULL,
phone VARCHAR(25) NULL,
active BOOLEAN NOT NULL DEFAULT TRUE,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE user_roles (
user_id BIGINT UNSIGNED NOT NULL,
role_id SMALLINT UNSIGNED NOT NULL,
PRIMARY KEY(user_id,role_id),
FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
FOREIGN KEY(role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE addresses (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NOT NULL,
recipient VARCHAR(100) NOT NULL,
phone VARCHAR(25) NOT NULL,
department VARCHAR(80) NOT NULL,
city VARCHAR(80) NOT NULL,
address_line VARCHAR(500) NOT NULL,
FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE categories (
id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
parent_id SMALLINT UNSIGNED NULL,
name VARCHAR(80) NOT NULL UNIQUE,
slug VARCHAR(80) NOT NULL UNIQUE,
FOREIGN KEY(parent_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE brands (
id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE products (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
category_id SMALLINT UNSIGNED NOT NULL,
brand_id SMALLINT UNSIGNED NULL,
sku VARCHAR(60) NOT NULL UNIQUE,
name VARCHAR(160) NOT NULL,
description TEXT NOT NULL,
kind ENUM('physical','digital') NOT NULL DEFAULT 'physical',
price DECIMAL(12,2) NOT NULL CHECK(price>=0),
stock INT UNSIGNED NOT NULL DEFAULT 0,
active BOOLEAN NOT NULL DEFAULT TRUE,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(category_id) REFERENCES categories(id),
FOREIGN KEY(brand_id) REFERENCES brands(id),
INDEX idx_products_catalog(category_id,active,price)
) ENGINE=InnoDB;

CREATE TABLE product_images (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
product_id BIGINT UNSIGNED NOT NULL,
url VARCHAR(700) NOT NULL,
alt VARCHAR(180) NOT NULL,
sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
UNIQUE(product_id,sort_order)
) ENGINE=InnoDB;

CREATE TABLE product_attributes (
product_id BIGINT UNSIGNED NOT NULL,
name VARCHAR(80) NOT NULL,
value VARCHAR(250) NOT NULL,
PRIMARY KEY(product_id,name),
FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE inventory_movements (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
product_id BIGINT UNSIGNED NOT NULL,
actor_id BIGINT UNSIGNED NULL,
quantity INT NOT NULL CHECK(quantity<>0),
reason VARCHAR(160) NOT NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(product_id) REFERENCES products(id),
FOREIGN KEY(actor_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE carts (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NOT NULL UNIQUE,
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_items (
cart_id BIGINT UNSIGNED NOT NULL,
product_id BIGINT UNSIGNED NOT NULL,
quantity SMALLINT UNSIGNED NOT NULL CHECK(quantity BETWEEN 1 AND 99),
PRIMARY KEY(cart_id,product_id),
FOREIGN KEY(cart_id) REFERENCES carts(id) ON DELETE CASCADE,
FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE wishlists (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NOT NULL UNIQUE,
FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE wishlist_items (
wishlist_id BIGINT UNSIGNED NOT NULL,
product_id BIGINT UNSIGNED NOT NULL,
PRIMARY KEY(wishlist_id,product_id),
FOREIGN KEY(wishlist_id) REFERENCES wishlists(id) ON DELETE CASCADE,
FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE orders (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NOT NULL,
request_key CHAR(36) NOT NULL,
status ENUM('pending','confirmed','preparing','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
currency CHAR(3) NOT NULL DEFAULT 'NIO',
subtotal DECIMAL(12,2) NOT NULL CHECK(subtotal>=0),
shipping_total DECIMAL(12,2) NOT NULL DEFAULT 0 CHECK(shipping_total>=0),
tax_total DECIMAL(12,2) NOT NULL DEFAULT 0 CHECK(tax_total>=0),
total DECIMAL(12,2) NOT NULL CHECK(total=subtotal+shipping_total+tax_total),
delivery_method ENUM('pickup','delivery') NOT NULL DEFAULT 'pickup',
notes VARCHAR(1000) NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(user_id) REFERENCES users(id),
UNIQUE(user_id,request_key),
INDEX idx_orders_user_date(user_id,created_at)
) ENGINE=InnoDB;

CREATE TABLE order_items (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
order_id BIGINT UNSIGNED NOT NULL,
product_id BIGINT UNSIGNED NOT NULL,
product_name VARCHAR(160) NOT NULL,
sku VARCHAR(60) NOT NULL,
quantity SMALLINT UNSIGNED NOT NULL CHECK(quantity>0),
unit_price DECIMAL(12,2) NOT NULL CHECK(unit_price>=0),
line_total DECIMAL(12,2) NOT NULL CHECK(line_total=quantity*unit_price),
FOREIGN KEY(order_id) REFERENCES orders(id),
FOREIGN KEY(product_id) REFERENCES products(id),
UNIQUE(order_id,product_id),
UNIQUE(id,order_id)
) ENGINE=InnoDB;

CREATE TABLE order_addresses (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
order_id BIGINT UNSIGNED NOT NULL,
type ENUM('shipping','billing') NOT NULL,
recipient VARCHAR(100) NOT NULL,
phone VARCHAR(25) NOT NULL,
department VARCHAR(80) NOT NULL,
city VARCHAR(80) NOT NULL,
address_line VARCHAR(500) NOT NULL,
FOREIGN KEY(order_id) REFERENCES orders(id),
UNIQUE(order_id,type)
) ENGINE=InnoDB;

CREATE TABLE order_status_history (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
order_id BIGINT UNSIGNED NOT NULL,
actor_id BIGINT UNSIGNED NULL,
status ENUM('pending','confirmed','preparing','shipped','completed','cancelled') NOT NULL,
note VARCHAR(500) NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(order_id) REFERENCES orders(id),
FOREIGN KEY(actor_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE payments (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
order_id BIGINT UNSIGNED NOT NULL,
method ENUM('cash','transfer','gateway') NOT NULL,
status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
amount DECIMAL(12,2) NOT NULL CHECK(amount>=0),
currency CHAR(3) NOT NULL DEFAULT 'NIO',
provider_reference VARCHAR(190) NULL UNIQUE,
paid_at DATETIME NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(order_id) REFERENCES orders(id)
) ENGINE=InnoDB;

CREATE TABLE shipments (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
order_id BIGINT UNSIGNED NOT NULL,
carrier VARCHAR(100) NULL,
tracking_code VARCHAR(190) NULL,
status ENUM('pending','dispatched','delivered','returned') NOT NULL DEFAULT 'pending',
dispatched_at DATETIME NULL,
delivered_at DATETIME NULL,
FOREIGN KEY(order_id) REFERENCES orders(id),
UNIQUE(id,order_id)
) ENGINE=InnoDB;

CREATE TABLE shipment_items (
shipment_id BIGINT UNSIGNED NOT NULL,
order_item_id BIGINT UNSIGNED NOT NULL,
order_id BIGINT UNSIGNED NOT NULL,
quantity SMALLINT UNSIGNED NOT NULL CHECK(quantity>0),
PRIMARY KEY(shipment_id,order_item_id),
FOREIGN KEY(shipment_id,order_id) REFERENCES shipments(id,order_id),
FOREIGN KEY(order_item_id,order_id) REFERENCES order_items(id,order_id)
) ENGINE=InnoDB;

CREATE TABLE services (
id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL UNIQUE,
description TEXT NOT NULL,
active BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE service_requests (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NULL,
service_id SMALLINT UNSIGNED NOT NULL,
name VARCHAR(100) NOT NULL,
email VARCHAR(190) NOT NULL,
phone VARCHAR(25) NOT NULL,
device VARCHAR(180) NULL,
description TEXT NOT NULL,
modality ENUM('shop','remote','home') NOT NULL DEFAULT 'shop',
status ENUM('new','diagnosing','quoted','approved','in_progress','completed','cancelled') NOT NULL DEFAULT 'new',
quoted_total DECIMAL(12,2) NULL CHECK(quoted_total>=0),
preferred_date DATE NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(user_id) REFERENCES users(id),
FOREIGN KEY(service_id) REFERENCES services(id),
INDEX idx_service_user(user_id,created_at)
) ENGINE=InnoDB;

CREATE TABLE service_request_history (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
request_id BIGINT UNSIGNED NOT NULL,
actor_id BIGINT UNSIGNED NULL,
status ENUM('new','diagnosing','quoted','approved','in_progress','completed','cancelled') NOT NULL,
note VARCHAR(500) NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(request_id) REFERENCES service_requests(id),
FOREIGN KEY(actor_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE contact_messages (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NULL,
name VARCHAR(100) NOT NULL,
email VARCHAR(190) NOT NULL,
phone VARCHAR(25) NULL,
subject VARCHAR(150) NOT NULL,
message TEXT NOT NULL,
status ENUM('new','read','resolved') NOT NULL DEFAULT 'new',
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE settings (
name VARCHAR(80) PRIMARY KEY,
value TEXT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
email VARCHAR(190) NOT NULL,
ip VARCHAR(45) NOT NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
INDEX idx_attempts_email(email,created_at),
INDEX idx_attempts_ip(ip,created_at)
) ENGINE=InnoDB;


USE tecnosoft;
START TRANSACTION;
INSERT INTO roles(name) VALUES ('customer'),('admin'),('technician');
INSERT INTO categories(name,slug) VALUES ('Software','software'),('Hardware','hardware'),('Accesorios','accesorios'),('Seguridad','seguridad'),('Juegos para PC','juegos');
INSERT INTO brands(name) VALUES ('Microsoft');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (1,1,'TS-0001','Microsoft 365 Personal','1 usuario · suscripción anual',1790,'digital',15);
INSERT INTO product_images(product_id,url,alt) VALUES (1,'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80','Microsoft 365 Personal');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (1,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (1,1,'TS-0002','Windows 11 Pro','Licencia digital · consulta disponibilidad',2490,'digital',15);
INSERT INTO product_images(product_id,url,alt) VALUES (2,'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=900&q=80','Windows 11 Pro');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (2,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (4,NULL,'TS-0003','Antivirus Premium','Protección para 1 dispositivo',790,'digital',15);
INSERT INTO product_images(product_id,url,alt) VALUES (3,'https://images.unsplash.com/photo-1563013544-824ae1b704d3?auto=format&fit=crop&w=900&q=80','Antivirus Premium');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (3,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (1,1,'TS-0004','Office Hogar y Estudiantes','Word, Excel y PowerPoint',3990,'digital',15);
INSERT INTO product_images(product_id,url,alt) VALUES (4,'https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&w=900&q=80','Office Hogar y Estudiantes');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (4,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (1,NULL,'TS-0005','CCleaner Professional','Optimización y limpieza anual',690,'digital',15);
INSERT INTO product_images(product_id,url,alt) VALUES (5,'https://images.unsplash.com/photo-1516321165247-4aa89a48be28?auto=format&fit=crop&w=900&q=80','CCleaner Professional');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (5,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (2,NULL,'TS-0006','SSD NVMe 1TB','Almacenamiento de alta velocidad',2590,'physical',15);
INSERT INTO product_images(product_id,url,alt) VALUES (6,'https://images.unsplash.com/photo-1597872200969-2b65d56bd16b?auto=format&fit=crop&w=900&q=80','SSD NVMe 1TB');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (6,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (2,NULL,'TS-0007','Memoria RAM 16GB DDR4','3200 MHz · rendimiento fluido',1490,'physical',15);
INSERT INTO product_images(product_id,url,alt) VALUES (7,'https://images.unsplash.com/photo-1562976540-1502c2145186?auto=format&fit=crop&w=900&q=80','Memoria RAM 16GB DDR4');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (7,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (2,NULL,'TS-0008','Disco duro externo 2TB','Respaldo portátil USB 3.0',2890,'physical',15);
INSERT INTO product_images(product_id,url,alt) VALUES (8,'https://images.unsplash.com/photo-1531492746076-161ca9bcad58?auto=format&fit=crop&w=900&q=80','Disco duro externo 2TB');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (8,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (2,NULL,'TS-0009','Teclado mecánico compacto','Switches rojos · retroiluminado',1690,'physical',15);
INSERT INTO product_images(product_id,url,alt) VALUES (9,'https://images.unsplash.com/photo-1511467687858-23d96c32e4ae?auto=format&fit=crop&w=900&q=80','Teclado mecánico compacto');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (9,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (2,NULL,'TS-0010','Mouse inalámbrico','Ergonómico · precisión ajustable',590,'physical',15);
INSERT INTO product_images(product_id,url,alt) VALUES (10,'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?auto=format&fit=crop&w=900&q=80','Mouse inalámbrico');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (10,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (2,NULL,'TS-0011','Mouse básico USB','Diseño cómodo · conexión inmediata',190,'physical',15);
INSERT INTO product_images(product_id,url,alt) VALUES (11,'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=900&q=80','Mouse básico USB');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (11,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (2,NULL,'TS-0012','Mouse silencioso inalámbrico','Clics silenciosos · para oficina',450,'physical',15);
INSERT INTO product_images(product_id,url,alt) VALUES (12,'https://images.unsplash.com/photo-1527814050087-3793815479db?auto=format&fit=crop&w=900&q=80','Mouse silencioso inalámbrico');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (12,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (2,NULL,'TS-0013','Mouse gamer RGB','6 botones · sensor preciso',990,'physical',15);
INSERT INTO product_images(product_id,url,alt) VALUES (13,'https://images.unsplash.com/photo-1551515300-2d3b7bb80920?auto=format&fit=crop&w=900&q=80','Mouse gamer RGB');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (13,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (2,NULL,'TS-0014','Mouse vertical ergonómico','Postura natural para largas jornadas',1290,'physical',15);
INSERT INTO product_images(product_id,url,alt) VALUES (14,'https://images.unsplash.com/photo-1527814050087-3793815479db?auto=format&fit=crop&w=900&q=80','Mouse vertical ergonómico');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (14,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (2,NULL,'TS-0015','Mouse gamer Pro inalámbrico','Respuesta rápida · recargable',2290,'physical',15);
INSERT INTO product_images(product_id,url,alt) VALUES (15,'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?auto=format&fit=crop&w=900&q=80','Mouse gamer Pro inalámbrico');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (15,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (2,NULL,'TS-0016','Mouse Bluetooth Premium','Multidispositivo · acabado metálico',2790,'physical',15);
INSERT INTO product_images(product_id,url,alt) VALUES (16,'https://images.unsplash.com/photo-1527814050087-3793815479db?auto=format&fit=crop&w=900&q=80','Mouse Bluetooth Premium');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (16,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (3,NULL,'TS-0017','Audífonos con micrófono','Audio claro para trabajo y juego',1190,'physical',15);
INSERT INTO product_images(product_id,url,alt) VALUES (17,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=900&q=80','Audífonos con micrófono');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (17,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (3,NULL,'TS-0018','Hub USB-C 6 en 1','Conexiones para tu escritorio',890,'physical',15);
INSERT INTO product_images(product_id,url,alt) VALUES (18,'https://images.unsplash.com/photo-1625842268584-8f3296236761?auto=format&fit=crop&w=900&q=80','Hub USB-C 6 en 1');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (18,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (3,NULL,'TS-0019','Cable HDMI 2.0 · 2m','Imagen 4K de alta calidad',290,'physical',15);
INSERT INTO product_images(product_id,url,alt) VALUES (19,'https://images.unsplash.com/photo-1555617778-02518510b9fa?auto=format&fit=crop&w=900&q=80','Cable HDMI 2.0 · 2m');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (19,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (3,NULL,'TS-0020','Adaptador Wi-Fi USB','Conexión de doble banda',490,'physical',15);
INSERT INTO product_images(product_id,url,alt) VALUES (20,'https://images.unsplash.com/photo-1593640408182-31c70c8268f5?auto=format&fit=crop&w=900&q=80','Adaptador Wi-Fi USB');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (20,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (5,NULL,'TS-0021','Minecraft para PC','Licencia e instalación según disponibilidad',1190,'digital',15);
INSERT INTO product_images(product_id,url,alt) VALUES (21,'assets/producto.svg','Minecraft para PC');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (21,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (5,NULL,'TS-0022','EA SPORTS FC 26','Licencia e instalación según disponibilidad',2490,'digital',15);
INSERT INTO product_images(product_id,url,alt) VALUES (22,'assets/producto.svg','EA SPORTS FC 26');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (22,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (5,NULL,'TS-0023','Grand Theft Auto V','Licencia e instalación según disponibilidad',990,'digital',15);
INSERT INTO product_images(product_id,url,alt) VALUES (23,'assets/producto.svg','Grand Theft Auto V');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (23,15,'Inventario inicial de demostración');
INSERT INTO products(category_id,brand_id,sku,name,description,price,kind,stock) VALUES (5,NULL,'TS-0024','Forza Horizon 5','Licencia e instalación según disponibilidad',1990,'digital',15);
INSERT INTO product_images(product_id,url,alt) VALUES (24,'assets/producto.svg','Forza Horizon 5');
INSERT INTO inventory_movements(product_id,quantity,reason) VALUES (24,15,'Inventario inicial de demostración');
INSERT INTO services(name,description) VALUES ('Instalación','Windows y controladores actualizados; Office, antivirus y programas esenciales; respaldo previo de archivos.');
INSERT INTO services(name,description) VALUES ('Mantenimiento','Limpieza de software y temporales; optimización de inicio y almacenamiento; actualizaciones y seguridad.');
INSERT INTO services(name,description) VALUES ('Diagnóstico','Revisión de lentitud y errores; informe con alternativas; presupuesto antes de reparar.');
INSERT INTO services(name,description) VALUES ('Soporte técnico','Asistencia remota guiada; visitas coordinadas; configuración de correo, redes y periféricos.');
INSERT INTO settings(name,value) VALUES ('currency','NIO'),('store_name','TecnoSoft Solutions'),('demo_prices','true');
COMMIT;
