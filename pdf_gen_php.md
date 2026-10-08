# PDF Generation in PHP using Browsershot (Recommended)

This guide explains how to replace a Python/Flask + Playwright PDF service with a native PHP solution using **Spatie Browsershot** and **Headless Chrome**.

Browsershot uses Chrome/Puppeteer under the hood and provides rendering quality very similar to Playwright.

---

# Why Browsershot?

Your invoice template uses modern browser features:

- CSS Variables (`:root`)
- Flexbox
- `@media print`
- `@page`
- Base64 images
- Modern typography/layout

Browsershot renders these correctly because it uses a real Chromium engine.

---

# Requirements

## PHP

```bash
php -v
```

Recommended:

```text
PHP 8.1+
```

---

## Composer

Verify:

```bash
composer --version
```

Install if needed:

https://getcomposer.org/

---

## Node.js

Verify:

```bash
node -v
npm -v
```

Recommended:

```text
Node.js 18+
```

---

# Installation

## 1. Install Browsershot

```bash
composer require spatie/browsershot
```

---

## 2. Install Puppeteer

From your project root:

```bash
npm install puppeteer
```

This downloads a compatible Chromium version.

---

# Basic Example

Create:

```php
<?php

require 'vendor/autoload.php';

use Spatie\Browsershot\Browsershot;

$html = file_get_contents('invoice.html');

$pdf = Browsershot::html($html)
    ->format('A4')
    ->showBackground()
    ->pdf();

file_put_contents('invoice.pdf', $pdf);

echo "PDF generated successfully";
```

Run:

```bash
php generate.php
```

---

# Save 