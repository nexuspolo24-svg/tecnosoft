# Diccionario de datos · TecnoSoft Solutions

Versión administrativa · 26 de octubre de 2026. **31 tablas y 34 claves externas**, incluidas dos relaciones compuestas de envíos. Moneda NIO. Fechas guardadas en UTC; reportes en America/Managua.

Carrito, favoritos, atributos, marcas y envíos parciales permanecen para ampliaciones; no todos tienen pantalla propia. El carrito operativo se conserva en el navegador y se valida al comprar.


## roles

| Columna | Definición SQL |
|---|---|
| id | SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| name | VARCHAR(40) NOT NULL UNIQUE |

Restricciones e índices:

```sql
id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(40) NOT NULL UNIQUE
```


## users

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| name | VARCHAR(100) NOT NULL |
| email | VARCHAR(190) NOT NULL UNIQUE |
| password_hash | VARCHAR(255) NOT NULL |
| password_version | INT UNSIGNED NOT NULL DEFAULT 1 |
| email_verified_at | DATETIME NULL |
| phone | VARCHAR(25) NULL |
| active | BOOLEAN NOT NULL DEFAULT TRUE |
| created_at | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP |

Restricciones e índices:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL,
email VARCHAR(190) NOT NULL UNIQUE,
password_hash VARCHAR(255) NOT NULL,
password_version INT UNSIGNED NOT NULL DEFAULT 1,
email_verified_at DATETIME NULL,
phone VARCHAR(25) NULL,
active BOOLEAN NOT NULL DEFAULT TRUE,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
```


## user_roles

| Columna | Definición SQL |
|---|---|
| user_id | BIGINT UNSIGNED NOT NULL |
| role_id | SMALLINT UNSIGNED NOT NULL |

Restricciones e índices:

```sql
user_id BIGINT UNSIGNED NOT NULL,
role_id SMALLINT UNSIGNED NOT NULL,
PRIMARY KEY(user_id,role_id),
FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
FOREIGN KEY(role_id) REFERENCES roles(id)
```


## addresses

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| user_id | BIGINT UNSIGNED NOT NULL |
| recipient | VARCHAR(100) NOT NULL |
| phone | VARCHAR(25) NOT NULL |
| department | VARCHAR(80) NOT NULL |
| city | VARCHAR(80) NOT NULL |
| address_line | VARCHAR(500) NOT NULL |

Restricciones e índices:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NOT NULL,
recipient VARCHAR(100) NOT NULL,
phone VARCHAR(25) NOT NULL,
department VARCHAR(80) NOT NULL,
city VARCHAR(80) NOT NULL,
address_line VARCHAR(500) NOT NULL,
FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
```


## categories

| Columna | Definición SQL |
|---|---|
| id | SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| parent_id | SMALLINT UNSIGNED NULL |
| name | VARCHAR(80) NOT NULL UNIQUE |
| slug | VARCHAR(80) NOT NULL UNIQUE |

Restricciones e índices:

```sql
id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
parent_id SMALLINT UNSIGNED NULL,
name VARCHAR(80) NOT NULL UNIQUE,
slug VARCHAR(80) NOT NULL UNIQUE,
FOREIGN KEY(parent_id) REFERENCES categories(id)
```


## brands

| Columna | Definición SQL |
|---|---|
| id | SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| name | VARCHAR(80) NOT NULL UNIQUE |

Restricciones e índices:

```sql
id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(80) NOT NULL UNIQUE
```


## products

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| category_id | SMALLINT UNSIGNED NOT NULL |
| brand_id | SMALLINT UNSIGNED NULL |
| sku | VARCHAR(60) NOT NULL UNIQUE |
| name | VARCHAR(160) NOT NULL |
| description | TEXT NOT NULL |
| kind | ENUM('physical','digital') NOT NULL DEFAULT 'physical' |
| price | DECIMAL(12,2) NOT NULL CHECK(price>=0) |
| cost_price | DECIMAL(12,2) NULL CHECK(cost_price>=0) |
| revision | INT UNSIGNED NOT NULL DEFAULT 1 |
| stock | INT UNSIGNED NOT NULL DEFAULT 0 |
| active | BOOLEAN NOT NULL DEFAULT TRUE |
| created_at | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP |

Restricciones e índices:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
category_id SMALLINT UNSIGNED NOT NULL,
brand_id SMALLINT UNSIGNED NULL,
sku VARCHAR(60) NOT NULL UNIQUE,
name VARCHAR(160) NOT NULL,
description TEXT NOT NULL,
kind ENUM('physical','digital') NOT NULL DEFAULT 'physical',
price DECIMAL(12,2) NOT NULL CHECK(price>=0),
cost_price DECIMAL(12,2) NULL CHECK(cost_price>=0),
revision INT UNSIGNED NOT NULL DEFAULT 1,
stock INT UNSIGNED NOT NULL DEFAULT 0,
active BOOLEAN NOT NULL DEFAULT TRUE,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(category_id) REFERENCES categories(id),
FOREIGN KEY(brand_id) REFERENCES brands(id),
INDEX idx_products_catalog(category_id,active,price)
```


## product_images

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| product_id | BIGINT UNSIGNED NOT NULL |
| url | VARCHAR(700) NOT NULL |
| alt | VARCHAR(180) NOT NULL |
| sort_order | SMALLINT UNSIGNED NOT NULL DEFAULT 0 |

Restricciones e índices:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
product_id BIGINT UNSIGNED NOT NULL,
url VARCHAR(700) NOT NULL,
alt VARCHAR(180) NOT NULL,
sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
UNIQUE(product_id,sort_order)
```


## product_attributes

| Columna | Definición SQL |
|---|---|
| product_id | BIGINT UNSIGNED NOT NULL |
| name | VARCHAR(80) NOT NULL |
| value | VARCHAR(250) NOT NULL |

Restricciones e índices:

```sql
product_id BIGINT UNSIGNED NOT NULL,
name VARCHAR(80) NOT NULL,
value VARCHAR(250) NOT NULL,
PRIMARY KEY(product_id,name),
FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
```


## inventory_movements

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| product_id | BIGINT UNSIGNED NOT NULL |
| actor_id | BIGINT UNSIGNED NULL |
| quantity | INT NOT NULL CHECK(quantity<>0) |
| reason | VARCHAR(160) NOT NULL |
| created_at | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP |

Restricciones e índices:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
product_id BIGINT UNSIGNED NOT NULL,
actor_id BIGINT UNSIGNED NULL,
quantity INT NOT NULL CHECK(quantity<>0),
reason VARCHAR(160) NOT NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(product_id) REFERENCES products(id),
FOREIGN KEY(actor_id) REFERENCES users(id)
```


## carts

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| user_id | BIGINT UNSIGNED NOT NULL UNIQUE |
| updated_at | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

Restricciones e índices:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NOT NULL UNIQUE,
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
```


## cart_items

| Columna | Definición SQL |
|---|---|
| cart_id | BIGINT UNSIGNED NOT NULL |
| product_id | BIGINT UNSIGNED NOT NULL |
| quantity | SMALLINT UNSIGNED NOT NULL CHECK(quantity BETWEEN 1 AND 99) |

Restricciones e índices:

```sql
cart_id BIGINT UNSIGNED NOT NULL,
product_id BIGINT UNSIGNED NOT NULL,
quantity SMALLINT UNSIGNED NOT NULL CHECK(quantity BETWEEN 1 AND 99),
PRIMARY KEY(cart_id,product_id),
FOREIGN KEY(cart_id) REFERENCES carts(id) ON DELETE CASCADE,
FOREIGN KEY(product_id) REFERENCES products(id)
```


## wishlists

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| user_id | BIGINT UNSIGNED NOT NULL UNIQUE |

Restricciones e índices:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NOT NULL UNIQUE,
FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
```


## wishlist_items

| Columna | Definición SQL |
|---|---|
| wishlist_id | BIGINT UNSIGNED NOT NULL |
| product_id | BIGINT UNSIGNED NOT NULL |

Restricciones e índices:

```sql
wishlist_id BIGINT UNSIGNED NOT NULL,
product_id BIGINT UNSIGNED NOT NULL,
PRIMARY KEY(wishlist_id,product_id),
FOREIGN KEY(wishlist_id) REFERENCES wishlists(id) ON DELETE CASCADE,
FOREIGN KEY(product_id) REFERENCES products(id)
```


## orders

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| user_id | BIGINT UNSIGNED NOT NULL |
| request_key | CHAR(36) NOT NULL |
| sales_channel | ENUM('web','store') NOT NULL DEFAULT 'web' |
| customer_name | VARCHAR(100) NULL |
| customer_phone | VARCHAR(25) NULL |
| status | ENUM('pending','confirmed','preparing','shipped','completed','cancelled') NOT NULL DEFAULT 'pending' |
| currency | CHAR(3) NOT NULL DEFAULT 'NIO' |
| subtotal | DECIMAL(12,2) NOT NULL CHECK(subtotal>=0) |
| shipping_total | DECIMAL(12,2) NOT NULL DEFAULT 0 CHECK(shipping_total>=0) |
| tax_total | DECIMAL(12,2) NOT NULL DEFAULT 0 CHECK(tax_total>=0) |
| total | DECIMAL(12,2) NOT NULL CHECK(total=subtotal+shipping_total+tax_total) |
| delivery_method | ENUM('pickup','delivery','digital') NOT NULL DEFAULT 'pickup' |
| notes | VARCHAR(1000) NULL |
| created_at | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP |

Restricciones e índices:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NOT NULL,
request_key CHAR(36) NOT NULL,
sales_channel ENUM('web','store') NOT NULL DEFAULT 'web',
customer_name VARCHAR(100) NULL,
customer_phone VARCHAR(25) NULL,
status ENUM('pending','confirmed','preparing','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
currency CHAR(3) NOT NULL DEFAULT 'NIO',
subtotal DECIMAL(12,2) NOT NULL CHECK(subtotal>=0),
shipping_total DECIMAL(12,2) NOT NULL DEFAULT 0 CHECK(shipping_total>=0),
tax_total DECIMAL(12,2) NOT NULL DEFAULT 0 CHECK(tax_total>=0),
total DECIMAL(12,2) NOT NULL CHECK(total=subtotal+shipping_total+tax_total),
delivery_method ENUM('pickup','delivery','digital') NOT NULL DEFAULT 'pickup',
notes VARCHAR(1000) NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(user_id) REFERENCES users(id),
UNIQUE(user_id,request_key),
INDEX idx_orders_user_date(user_id,created_at)
```


## order_items

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| order_id | BIGINT UNSIGNED NOT NULL |
| product_id | BIGINT UNSIGNED NOT NULL |
| product_name | VARCHAR(160) NOT NULL |
| sku | VARCHAR(60) NOT NULL |
| quantity | SMALLINT UNSIGNED NOT NULL CHECK(quantity>0) |
| unit_price | DECIMAL(12,2) NOT NULL CHECK(unit_price>=0) |
| unit_cost | DECIMAL(12,2) NULL CHECK(unit_cost>=0) |
| line_total | DECIMAL(12,2) NOT NULL CHECK(line_total=quantity*unit_price) |

Restricciones e índices:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
order_id BIGINT UNSIGNED NOT NULL,
product_id BIGINT UNSIGNED NOT NULL,
product_name VARCHAR(160) NOT NULL,
sku VARCHAR(60) NOT NULL,
quantity SMALLINT UNSIGNED NOT NULL CHECK(quantity>0),
unit_price DECIMAL(12,2) NOT NULL CHECK(unit_price>=0),
unit_cost DECIMAL(12,2) NULL CHECK(unit_cost>=0),
line_total DECIMAL(12,2) NOT NULL CHECK(line_total=quantity*unit_price),
FOREIGN KEY(order_id) REFERENCES orders(id),
FOREIGN KEY(product_id) REFERENCES products(id),
UNIQUE(order_id,product_id),
UNIQUE(id,order_id)
```


## order_addresses

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| order_id | BIGINT UNSIGNED NOT NULL |
| type | ENUM('shipping','billing') NOT NULL |
| recipient | VARCHAR(100) NOT NULL |
| phone | VARCHAR(25) NOT NULL |
| department | VARCHAR(80) NOT NULL |
| city | VARCHAR(80) NOT NULL |
| address_line | VARCHAR(500) NOT NULL |

Restricciones e índices:

```sql
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
```


## order_status_history

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| order_id | BIGINT UNSIGNED NOT NULL |
| actor_id | BIGINT UNSIGNED NULL |
| status | ENUM('pending','confirmed','preparing','shipped','completed','cancelled') NOT NULL |
| note | VARCHAR(500) NULL |
| created_at | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP |

Restricciones e índices:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
order_id BIGINT UNSIGNED NOT NULL,
actor_id BIGINT UNSIGNED NULL,
status ENUM('pending','confirmed','preparing','shipped','completed','cancelled') NOT NULL,
note VARCHAR(500) NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(order_id) REFERENCES orders(id),
FOREIGN KEY(actor_id) REFERENCES users(id)
```


## payments

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| order_id | BIGINT UNSIGNED NOT NULL |
| method | ENUM('cash','transfer','gateway') NOT NULL |
| status | ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending' |
| amount | DECIMAL(12,2) NOT NULL CHECK(amount>=0) |
| currency | CHAR(3) NOT NULL DEFAULT 'NIO' |
| provider_reference | VARCHAR(190) NULL UNIQUE |
| paid_at | DATETIME NULL |
| created_at | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP |

Restricciones e índices:

```sql
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
```


## shipments

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| order_id | BIGINT UNSIGNED NOT NULL |
| carrier | VARCHAR(100) NULL |
| tracking_code | VARCHAR(190) NULL |
| status | ENUM('pending','dispatched','delivered','returned') NOT NULL DEFAULT 'pending' |
| dispatched_at | DATETIME NULL |
| delivered_at | DATETIME NULL |

Restricciones e índices:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
order_id BIGINT UNSIGNED NOT NULL,
carrier VARCHAR(100) NULL,
tracking_code VARCHAR(190) NULL,
status ENUM('pending','dispatched','delivered','returned') NOT NULL DEFAULT 'pending',
dispatched_at DATETIME NULL,
delivered_at DATETIME NULL,
FOREIGN KEY(order_id) REFERENCES orders(id),
UNIQUE(id,order_id)
```


## shipment_items

| Columna | Definición SQL |
|---|---|
| shipment_id | BIGINT UNSIGNED NOT NULL |
| order_item_id | BIGINT UNSIGNED NOT NULL |
| order_id | BIGINT UNSIGNED NOT NULL |
| quantity | SMALLINT UNSIGNED NOT NULL CHECK(quantity>0) |

Restricciones e índices:

```sql
shipment_id BIGINT UNSIGNED NOT NULL,
order_item_id BIGINT UNSIGNED NOT NULL,
order_id BIGINT UNSIGNED NOT NULL,
quantity SMALLINT UNSIGNED NOT NULL CHECK(quantity>0),
PRIMARY KEY(shipment_id,order_item_id),
FOREIGN KEY(shipment_id,order_id) REFERENCES shipments(id,order_id),
FOREIGN KEY(order_item_id,order_id) REFERENCES order_items(id,order_id)
```


## services

| Columna | Definición SQL |
|---|---|
| id | SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| name | VARCHAR(100) NOT NULL UNIQUE |
| description | TEXT NOT NULL |
| active | BOOLEAN NOT NULL DEFAULT TRUE |

Restricciones e índices:

```sql
id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL UNIQUE,
description TEXT NOT NULL,
active BOOLEAN NOT NULL DEFAULT TRUE
```


## service_requests

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| user_id | BIGINT UNSIGNED NULL |
| service_id | SMALLINT UNSIGNED NOT NULL |
| name | VARCHAR(100) NOT NULL |
| email | VARCHAR(190) NOT NULL |
| phone | VARCHAR(25) NOT NULL |
| device | VARCHAR(180) NULL |
| description | TEXT NOT NULL |
| modality | ENUM('shop','remote','home') NOT NULL DEFAULT 'shop' |
| status | ENUM('new','diagnosing','quoted','approved','in_progress','completed','cancelled') NOT NULL DEFAULT 'new' |
| quoted_total | DECIMAL(12,2) NULL CHECK(quoted_total>=0) |
| preferred_date | DATE NULL |
| created_at | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP |

Restricciones e índices:

```sql
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
```


## service_request_history

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| request_id | BIGINT UNSIGNED NOT NULL |
| actor_id | BIGINT UNSIGNED NULL |
| status | ENUM('new','diagnosing','quoted','approved','in_progress','completed','cancelled') NOT NULL |
| note | VARCHAR(500) NULL |
| created_at | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP |

Restricciones e índices:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
request_id BIGINT UNSIGNED NOT NULL,
actor_id BIGINT UNSIGNED NULL,
status ENUM('new','diagnosing','quoted','approved','in_progress','completed','cancelled') NOT NULL,
note VARCHAR(500) NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(request_id) REFERENCES service_requests(id),
FOREIGN KEY(actor_id) REFERENCES users(id)
```


## contact_messages

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| user_id | BIGINT UNSIGNED NULL |
| name | VARCHAR(100) NOT NULL |
| email | VARCHAR(190) NOT NULL |
| phone | VARCHAR(25) NULL |
| subject | VARCHAR(150) NOT NULL |
| message | TEXT NOT NULL |
| status | ENUM('new','read','resolved') NOT NULL DEFAULT 'new' |
| created_at | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP |

Restricciones e índices:

```sql
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
```


## settings

| Columna | Definición SQL |
|---|---|
| name | VARCHAR(80) PRIMARY KEY |
| value | TEXT NOT NULL |

Restricciones e índices:

```sql
name VARCHAR(80) PRIMARY KEY,
value TEXT NOT NULL
```


## login_attempts

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| email | VARCHAR(190) NOT NULL |
| ip | VARCHAR(45) NOT NULL |
| created_at | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP |

Restricciones e índices:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
email VARCHAR(190) NOT NULL,
ip VARCHAR(45) NOT NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
INDEX idx_attempts_email(email,created_at),
INDEX idx_attempts_ip(ip,created_at)
```


## schema_migrations

| Columna | Definición SQL |
|---|---|
| version | VARCHAR(80) PRIMARY KEY |
| applied_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP |

Restricciones e índices:

```sql
version VARCHAR(80) PRIMARY KEY,
applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```


## email_verifications

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| user_id | BIGINT UNSIGNED NOT NULL |
| token_hash | CHAR(64) NOT NULL UNIQUE |
| expires_at | DATETIME NOT NULL |
| used_at | DATETIME NULL |
| created_at | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP |

Restricciones e índices:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NOT NULL,
token_hash CHAR(64) NOT NULL UNIQUE,
expires_at DATETIME NOT NULL,
used_at DATETIME NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
INDEX idx_verification_user(user_id,created_at)
```


## audit_log

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| actor_id | BIGINT UNSIGNED NOT NULL |
| action | VARCHAR(80) NOT NULL |
| entity | VARCHAR(80) NOT NULL |
| entity_id | BIGINT UNSIGNED NOT NULL |
| details | TEXT NOT NULL |
| created_at | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP |

Restricciones e índices:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
actor_id BIGINT UNSIGNED NOT NULL,
action VARCHAR(80) NOT NULL,
entity VARCHAR(80) NOT NULL,
entity_id BIGINT UNSIGNED NOT NULL,
details TEXT NOT NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY(actor_id) REFERENCES users(id)
```


## password_resets

| Columna | Definición SQL |
|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| user_id | BIGINT UNSIGNED NULL |
| email_hash | CHAR(64) NOT NULL |
| ip_hash | CHAR(64) NOT NULL |
| token_hash | CHAR(64) NULL UNIQUE |
| expires_at | DATETIME NULL |
| used_at | DATETIME NULL |
| created_at | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP |

Restricciones e índices:

```sql
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
```
