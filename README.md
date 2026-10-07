# DocEditor — Invoice & Quote Editor

Refactored to a clean MVC architecture with MySQL, PSR-4 autoloading,
and a single front-controller entry point.

---

## Architecture

```
doceditor/
├── app/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── DocumentApiController.php   — JSON API for documents
│   │   │   ├── CustomerApiController.php   — JSON API for customers
│   │   │   └── ItemApiController.php       — JSON API for items
│   │   ├── DocumentController.php          — serves HTML pages
│   │   ├── CustomerController.php
│   │   └── ItemController.php
│   ├── Core/
│   │   ├── Database.php    — PDO MySQL singleton
│   │   ├── Env.php         — .env loader (no-composer fallback)
│   │   ├── Request.php     — GET/POST/JSON body wrapper
│   │   ├── Response.php    — json(), view(), error() helpers
│   │   └── Router.php      — front-controller router
│   ├── Models/
│   │   ├── Document.php    — value object + fromRow() + toArray()
│   │   ├── Customer.php
│   │   └── Item.php
│   ├── Repositories/
│   │   ├── DocumentRepository.php  — all SQL for documents
│   │   ├── CustomerRepository.php
│   │   └── ItemRepository.php
│   └── Services/
│       ├── DocumentService.php  — business logic, auto-upsert customer,
│       │                          document numbering
│       ├── CustomerService.php
│       └── ItemService.php
├── config/
│   └── app.php             — central config (reads .env via Env::get())
├── database/
│   ├── migrate.php         — CLI migration runner
│   └── migrations/
│       ├── 001_create_tables.sql               — MySQL schema
│       └── 002_sqlite_to_mysql_import.php      — one-time SQLite → MySQL
├── public/
│   ├── index.php           — single entry point (all HTTP requests)
│   ├── .htaccess           — mod_rewrite → index.php
│   └── assets/             — CSS, logo.png (copy from old app)
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── app.php     — shared page shell (topbar + toast)
│       │   └── editor.php  — editor shell (full-height, extra toolbar buttons)
│       ├── partials/
│       │   ├── topbar.php           — auto-highlights active nav link
│       │   ├── editor_body.php      — split-panel editor HTML
│       │   ├── editor_logic.php     — all editor JavaScript
│       │   └── document_render.php  — A4 invoice HTML (PHP template)
│       ├── documents/
│       │   ├── index.php        — document history page
│       │   ├── editor.php       — editor page (includes partials)
│       │   ├── preview.php      — print-ready A4 (no layout)
│       │   └── documentation.php
│       ├── customers/
│       │   └── index.php
│       └── items/
│           └── index.php
├── routes/
│   └── web.php             — all routes in one file
├── .env                    — credentials (never commit)
├── .env.example            — safe template to commit
├── .gitignore
├── bootstrap.php           — loads .env, autoloader, error handling
└── composer.json           — only vlucas/phpdotenv
```

---

## Request lifecycle

```
Browser → public/index.php
            └── bootstrap.php       (load .env, autoloader)
            └── routes/web.php      (register routes on Router)
            └── Router::dispatch()
                  └── matches URI → Controller::action()
                        └── Service  (business logic)
                              └── Repository  (SQL / PDO)
                                    └── Model  (value object)
                        └── Response::json() or Response::view()
                              └── resources/views/...php  (rendered)
```

---

## REST API routes

| Method | Path | Action |
|--------|------|--------|
| GET  | `/api/documents`               | List documents (search: `?q=`, `?type=`) |
| GET  | `/api/documents/default`       | Default template (`?type=INVOICE`) |
| GET  | `/api/documents/:id`           | Get one document |
| POST | `/api/documents`               | Create document |
| POST | `/api/documents/:id`           | Update document |
| POST | `/api/documents/:id/delete`    | Delete document |
| POST | `/api/documents/:id/duplicate` | Duplicate document |
| GET  | `/api/numbers/next`            | Consume next number (`?type=INVOICE`) |
| GET  | `/api/numbers/peek`            | Peek next number without consuming |
| GET  | `/api/customers`               | List customers (`?q=`) |
| GET  | `/api/customers/:id`           | Get one customer |
| POST | `/api/customers`               | Create customer |
| POST | `/api/customers/:id`           | Update customer |
| POST | `/api/customers/:id/delete`    | Delete customer |
| GET  | `/api/items`                   | List items (`?q=`) |
| GET  | `/api/items/:id`               | Get one item |
| POST | `/api/items`                   | Create item |
| POST | `/api/items/:id`               | Update item |
| POST | `/api/items/:id/delete`        | Delete item |

---

## Setup

### Requirements
- PHP 8.1+  with `pdo_mysql` extension
- MySQL 8.0+ (or MariaDB 10.5+)
- Apache with `mod_rewrite` enabled, OR Nginx with rewrite config below

### 1. Clone / copy files

```bash
cp -r doceditor/ /var/www/html/doceditor
cd /var/www/html/doceditor
```

### 2. Copy assets from the old app

```bash
cp path/to/old/assets/editor.css    public/assets/
cp path/to/old/assets/document.css  public/assets/
cp path/to/old/assets/logo.png      public/assets/
```

### 3. Configure environment

```bash
cp .env.example .env
nano .env
```

Fill in your MySQL credentials:
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=doceditor
DB_USERNAME=doceditor_user
DB_PASSWORD=your_strong_password
```

### 4. Install dependencies (optional but recommended)

```bash
composer install
```

If Composer is not available, the built-in `Env` class and PSR-4 fallback
loader in `bootstrap.php` handle everything with zero dependencies.

### 5. Create MySQL user and database

```sql
CREATE USER 'doceditor_user'@'localhost' IDENTIFIED BY 'your_strong_password';
CREATE DATABASE doceditor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON doceditor.* TO 'doceditor_user'@'localhost';
FLUSH PRIVILEGES;
```

### 6. Run migrations

```bash
php database/migrate.php
```

### 7. (Optional) Import data from old SQLite app

```bash
php database/migrations/002_sqlite_to_mysql_import.php /path/to/old/db/documents.sqlite
```

### 8. Point your web server at `public/`

**Apache** — set `DocumentRoot` to the `public/` directory.
The `.htaccess` handles rewrites.

**Nginx:**
```nginx
server {
    root /var/www/html/doceditor/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
    }
}
```

**PHP built-in server (local dev):**
```bash
php -S localhost:8000 -t public/
```

### 9. Open the app

```
http://localhost:8000/documents
```

---

## Key design decisions

**No framework.** 4 core classes (`Router`, `Response`, `Request`, `Database`)
replace what Laravel would provide. Total framework code: ~250 lines.

**Repository pattern.** All SQL lives in `Repositories/`. Controllers and
Services never touch PDO directly. Swapping MySQL for another DB means
editing only the Repository files.

**Models as value objects.** Models have no methods that touch the database —
they are plain data containers. `fromRow()` maps a DB row; `toArray()` serialises
for JSON. All mutation goes through Services → Repositories.

**Services own business logic.** Auto-upserting a customer when saving a
document, generating document numbers, stripping logo data from exports — all
of this lives in `DocumentService`, not in the controller.

**Single entry point.** Every HTTP request hits `public/index.php`.
No more `api.php?action=X` — URLs are clean REST paths.

**Views are plain PHP.** No templating engine. Layouts use output buffering
(`Response::view()` captures the view into `$content`, then includes the layout).
