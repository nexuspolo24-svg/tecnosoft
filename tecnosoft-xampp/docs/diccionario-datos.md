# Diccionario de datos · TecnoSoft Solutions

27 tablas. Moneda NIO; importes DECIMAL(12,2); fechas en UTC; claves InnoDB y texto utf8mb4.

## roles

```sql
id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(40) NOT NULL UNIQUE
```

## users

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL,
email VARCHAR(190) NOT NULL UNIQUE,
password_hash VARCHAR(255) NOT NULL,
phone VARCHAR(25) NULL,
active BOOLEAN NOT NULL DEFAULT TRUE,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
```

## user_roles

```sql
user_id BIGINT UNSIGNED NOT NULL,
role_id SMALLINT UNSIGNED NOT NULL,
PRIMARY KEY(user_id,role_id),
FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
FOREIGN KEY(role_id) REFERENCES roles(id)
```

## addresses

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

```sql
id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
parent_id SMALLINT UNSIGNED NULL,
name VARCHAR(80) NOT NULL UNIQUE,
slug VARCHAR(80) NOT NULL UNIQUE,
FOREIGN KEY(parent_id) REFERENCES categories(id)
```

## brands

```sql
id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(80) NOT NULL UNIQUE
```

## products

```sql
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
```

## product_images

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

```sql
product_id BIGINT UNSIGNED NOT NULL,
name VARCHAR(80) NOT NULL,
value VARCHAR(250) NOT NULL,
PRIMARY KEY(product_id,name),
FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
```

## inventory_movements

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

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NOT NULL UNIQUE,
updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
```

## cart_items

```sql
cart_id BIGINT UNSIGNED NOT NULL,
product_id BIGINT UNSIGNED NOT NULL,
quantity SMALLINT UNSIGNED NOT NULL CHECK(quantity BETWEEN 1 AND 99),
PRIMARY KEY(cart_id,product_id),
FOREIGN KEY(cart_id) REFERENCES carts(id) ON DELETE CASCADE,
FOREIGN KEY(product_id) REFERENCES products(id)
```

## wishlists

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NOT NULL UNIQUE,
FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
```

## wishlist_items

```sql
wishlist_id BIGINT UNSIGNED NOT NULL,
product_id BIGINT UNSIGNED NOT NULL,
PRIMARY KEY(wishlist_id,product_id),
FOREIGN KEY(wishlist_id) REFERENCES wishlists(id) ON DELETE CASCADE,
FOREIGN KEY(product_id) REFERENCES products(id)
```

## orders

```sql
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
```

## order_items

```sql
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
```

## order_addresses

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

```sql
id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL UNIQUE,
description TEXT NOT NULL,
active BOOLEAN NOT NULL DEFAULT TRUE
```

## service_requests

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

```sql
name VARCHAR(80) PRIMARY KEY,
value TEXT NOT NULL
```

## login_attempts

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
email VARCHAR(190) NOT NULL,
ip VARCHAR(45) NOT NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
INDEX idx_attempts_email(email,created_at),
INDEX idx_attempts_ip(ip,created_at)
```

## Reglas de negocio

- El catálogo inicial tiene 24 productos con precios e inventario ficticios, no cotizaciones comerciales.
- Cada usuario tiene un carrito y una lista de deseos como máximo. La interfaz de esta versión usa carrito local; las tablas de carrito y favoritos quedan preparadas para la siguiente etapa.
- Los pedidos conservan nombre, SKU y precio como instantáneas; editar el catálogo no altera el historial. No se eliminan productos con ventas: se desactivan.
- La creación de pedido bloquea existencias, calcula en servidor y descuenta stock en una transacción. La clave idempotente evita duplicados al reintentar.
- El pedido inicial es de retiro y pago en efectivo pendiente; no se cobra en línea. Impuesto adicional y envío son cero en la demostración. Confirmar política fiscal, impuestos incluidos y entrega antes de operar.
- Cancelaciones deben reponer stock en la misma transacción que actualiza estado e historial; aún no existe interfaz administrativa para ese flujo.
- El estado en orders/service_requests sirve para consulta rápida; su historial se escribe en la misma transacción.
- La dirección de una orden es una copia histórica, independiente de la libreta del usuario.
- Un envío solo puede incluir partidas de su propio pedido. El servicio que lo implemente debe validar cantidades pendientes antes de registrar envíos parciales.
- Un pago no guarda datos de tarjeta; las referencias del proveedor son únicas. Reembolsos, entregas, favoritos y administración están modelados, pero no implementados en la interfaz inicial.
- Las licencias digitales también tienen existencias; no se distribuyen claves desde esta versión.
- Los formularios guardan solicitudes en MySQL; no envían correos.
- No se crean administradores con contraseñas predefinidas.
