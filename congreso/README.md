# Congreso Academia & Tecnología 2026 – Sistema Web

## Estructura del proyecto

```
congreso/
├── index.php               ← Sitio web público del congreso
├── procesar_registro.php   ← Procesa el formulario de registro
├── pago.php                ← Página de pago con PayPal
├── confirmar_pago.php      ← Endpoint AJAX que confirma el pago y guarda en BD
├── gracias.php             ← Página de éxito + descarga de recibo
├── database.sql            ← Script SQL para crear las tablas (ejecutar 1 vez)
├── composer.json           ← Dependencias PHP (mPDF para PDF)
├── .htaccess               ← Seguridad Apache
├── assets/
│   └── style.css           ← Estilos del sitio público
├── includes/
│   ├── db.php              ← Conexión PDO a MySQL (Clever Cloud)
│   └── session.php         ← Variables de sesión, helpers, precios
├── admin/
│   ├── login.php           ← Login del administrador
│   ├── dashboard.php       ← Panel con tablas de ponentes y participantes
│   └── logout.php
└── uploads/
    ├── recibos/            ← PDFs generados automáticamente
    └── (archivos ponentes) ← Archivos subidos por ponentes
```

---

## 1. Configurar la base de datos en Clever Cloud

1. Entra a [console.clever-cloud.com](https://console.clever-cloud.com)
2. Crea un **Add-on MySQL** → anota Host, Puerto, DB, Usuario y Contraseña
3. Abre el **phpMyAdmin** de Clever Cloud y ejecuta `database.sql`

---

## 2. Editar includes/db.php

Reemplaza los valores de conexión con los de tu add-on:

```php
define('DB_HOST',     'xxxxx-mysql.services.clever-cloud.com');
define('DB_PORT',     '3306');
define('DB_NAME',     'congreso_db');
define('DB_USER',     'tu_usuario');
define('DB_PASSWORD', 'tu_password');
```

> **Recomendado:** Usa variables de entorno de Clever Cloud (`MYSQL_ADDON_HOST`, etc.)
> y no edites el archivo — el código ya las lee automáticamente.

---

## 3. Configurar PayPal

En `includes/session.php`, reemplaza:

```php
const PAYPAL_CLIENT_ID = 'AV6yb2xxxxxx...';
```

**Pasos para obtener tu Client ID:**
1. Ve a [developer.paypal.com](https://developer.paypal.com)
2. My Apps & Credentials → Create App → copia el **Client ID** de Sandbox
3. Para producción cambia también `PAYPAL_MODE = 'live'`

---

## 4. Instalar mPDF (generación de PDFs bonitos)

Desde la carpeta raíz del proyecto ejecuta:

```bash
composer install
```

Luego añade al inicio de `confirmar_pago.php`:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

> Si no tienes Composer, el sistema genera el PDF de recibo de forma básica
> sin librería externa (funciona igual, solo cambia el diseño del PDF).

---

## 5. Subir a Clever Cloud

### Opción A – Git (recomendado)
```bash
git init
git remote add clever git+ssh://git@push.clever-cloud.com/app_xxxxxxxx.git
git add .
git commit -m "Congreso 2026 - deploy inicial"
git push clever master
```

### Opción B – FTP / SFTP
Sube todos los archivos a `public_html/` o la raíz de tu aplicación PHP.

---

## 6. Credenciales del administrador

- **URL:** `tudominio.com/admin/login.php`
- **Usuario:** `admin`
- **Contraseña:** `Admin2026!`

> ⚠️ **Cámbiala de inmediato** ejecutando en MySQL:
> ```sql
> UPDATE admins SET password_hash = '$2y$12$NUEVO_HASH' WHERE usuario = 'admin';
> ```
> Genera el hash con: `php -r "echo password_hash('TuNuevaContraseña', PASSWORD_BCRYPT);"`

---

## Flujo del sistema

```
Usuario → index.php
         ↓
         [Elige: Participante / Ponente]
         ↓
         procesar_registro.php
         → Valida datos
         → Ponente: sube archivo a /uploads/
         → Guarda datos en $_SESSION['registro']
         ↓
         pago.php  ← muestra resumen + botón PayPal
         ↓ (PayPal aprueba)
         confirmar_pago.php (AJAX)
         → Inserta en BD (participantes o ponentes)
         → Genera recibo PDF en /uploads/recibos/
         → Guarda ruta del PDF en BD
         ↓
         gracias.php
         → Muestra resumen + enlace para descargar PDF
```

---

## Precios configurados

| Tipo           | Presencial | Virtual |
|---------------|-----------|---------|
| Participante  | $1,500 MXN | $800 MXN |
| Ponente       | $2,000 MXN | $1,200 MXN |

Edítalos en `includes/session.php` (constantes `PRECIO_*`).

---

## Subir a GitHub

```bash
git init
git add .
git commit -m "Sistema Congreso 2026 - completo"
git branch -M main
git remote add origin https://github.com/TU_USUARIO/congreso-2026.git
git push -u origin main
```
