# Invoice / Quote Editor — Build Plan

## Overview

A PHP-based web application to create, edit, and archive business documents
(Invoices, Quotes, Credit Notes) with a split-panel editor (form + live preview)
and SQLite storage. Documents are stored as JSON, printable as PDF via Ctrl+P.

---

## Tech Stack

| Layer      | Choice                   | Why                                              |
|------------|--------------------------|--------------------------------------------------|
| Language   | PHP 8.2+                 | Requested; zero dependencies                     |
| Storage    | SQLite (via PDO)         | Single file, no server, perfect for single user  |
| Frontend   | Vanilla JS + CSS         | No build step, no framework overhead             |
| Print      | Browser Ctrl+P           | Preserves existing workflow                      |
| Data format| JSON column in SQLite    | Flexible, versionable, re-editable               |

---

## File Structure

```
/invoice-editor/
│
├── index.php              ← Document list / history
├── editor.php             ← Split-panel editor (form + live preview)
├── api.php                ← Save / load / delete (JSON API endpoints)
├── preview.php            ← Printable A4 HTML (your current format, PHP-rendered)
│
├── db/
│   └── documents.sqlite   ← SQLite database (auto-created on first run)
│
├── config/
│   └── issuer.json        ← Default issuer profile (NOVOCIB data, editable)
│
├── assets/
│   ├── editor.css         ← Split-panel UI styles
│   ├── editor.js          ← Live preview logic + form handling
│   └── document.css       ← A4 invoice styles (your existing CSS)
│
└── templates/
    └── invoice.html.php   ← A4 template (PHP version of your HTML models)
```

---

## Database Schema (SQLite)

```sql
CREATE TABLE documents (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    type        TEXT NOT NULL DEFAULT 'INVOICE',   -- INVOICE | QUOTE | CREDIT NOTE
    number      TEXT NOT NULL,
    date        TEXT NOT NULL,
    status      TEXT DEFAULT 'draft',              -- draft | final | paid
    customer    TEXT,                              -- client name (for list display)
    data        TEXT NOT NULL,                     -- full document as JSON
    created_at  TEXT DEFAULT (datetime('now')),
    updated_at  TEXT DEFAULT (datetime('now'))
);

-- Optional: customer address book (Phase 4)
CREATE TABLE customers (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT NOT NULL,
    data        TEXT NOT NULL   -- JSON: address, contact, phone, VAT
);
```

The `data` column holds the entire document. No schema migration needed when
adding new fields — just update the JSON structure and the PHP template.

---

## JSON Document Structure

```json
{
  "type": "INVOICE",
  "number": "INV-260805-01",
  "date": "2026-08-05",
  "due_date": "2026-08-05",
  "service_date": "",
  "quote_ref": "",
  "tracking": "",

  "issuer": {
    "name": "NOVOCIB SAS",
    "address": "BD de Chatillon, Quai Jean Voisin",
    "city": "62200 Boulogne-sur-Mer — France",
    "email": "lbalakireva@novocib.com",
    "legal": "SAS, société par actions simplifiée — Share capital: 260 158,00 €\nEORI# FR48237937700047\nVAT# FR90 482 379 377",
    "logo_base64": ""
  },

  "customer": {
    "name": "Pureture",
    "address": "4F, 2121-3 Nambusunhwan-ro",
    "city": "06725 Seocho-gu, Seoul — Republic of Korea",
    "contact": "Sohee Yoon",
    "phone": "+82-10-6681-1162",
    "vat": ""
  },

  "currency": "EUR",
  "currency_symbol": "€",

  "items": [
    {
      "name": "HPLC-UV analysis...",
      "description": "Samples: 260126 & 260528",
      "reference": "S1200-03-NA",
      "unit_price": 300,
      "unit_price_display": "",
      "qty": 2,
      "is_free": false
    }
  ],

  "vat_rate": 0,
  "amount_paid": 0,
  "show_amount_paid": false,
  "balance_label": "TOTAL DUE",

  "vat_mention": "VAT not applicable - export outside the EU...",
  "notes": "",
  "terms": "PAYMENT IMMEDIATE UPON RECEIPT...",

  "bank": {
    "label": "Bank Details EUR - Banque Populaire, France",
    "beneficiary": "SAS NOVOCIB",
    "bank_name": "BANQUE POPULAIRE AUVERGNE RHÔNE ALPES (BPAURA)",
    "bank_address": "215 Avenue Jean Jaurès, 69007 Lyon, France",
    "iban": "FR76 1680 7004 0081 0876 0421 151",
    "bic": "CCBPFRPPGRE"
  },

  "footer_thanks": "Thank you for your business!",
  "footer_contact": "If you have any questions regarding this invoice, please contact us.\n• lbalakireva@novocib.com"
}
```

---

## Pages & Their Roles

### `index.php` — Document History
- Table: number, type, customer, date, status
- Actions per row: Edit | Duplicate | Print | Delete
- "New Document" button with type selector (Invoice / Quote / Credit Note)
- Search / filter bar

### `editor.php` — Split-Panel Editor
- **Left panel (form)** — collapsible sections:
  - Document Header (type, number, dates, optional fields)
  - Bill To (customer name, address, contact, VAT)
  - Line Items (dynamic rows: add / remove / reorder)
  - Totals (VAT rate, amount paid toggle, balance label)
  - Text Blocks (VAT mention, Notes, Terms)
  - Bank Details
  - Issuer / Footer
- **Right panel** — live A4 preview (DOM-updated, no server round-trip)
- Auto-save every 30 seconds
- "Save as Final" button (locks editing, changes status)
- "Print" button (opens `preview.php?id=X` in a new tab → Ctrl+P)

### `api.php` — JSON API

| Method | Params              | Action                        |
|--------|---------------------|-------------------------------|
| GET    | `action=list`       | Return all documents (summary)|
| GET    | `action=get&id=X`   | Load one document (full JSON) |
| POST   | `action=save`       | Create new document           |
| POST   | `action=update&id=X`| Update existing document      |
| POST   | `action=duplicate&id=X` | Clone a document          |
| POST   | `action=delete&id=X`| Delete a document             |

### `preview.php` — Print-Ready A4
- Loads JSON from DB by `?id=X`
- Renders via `templates/invoice.html.php`
- Clean print CSS (`@media print { body { margin: 0; } }`)
- This is the page you Ctrl+P → Save as PDF

---

## Live Preview Strategy

**Approach: direct DOM manipulation (no server round-trip)**

Every input change triggers a JS function that reads the whole form as a JSON
object and updates the preview `<div>` DOM nodes directly (text content,
`innerHTML` for rich fields). This is instant and works offline.

```
[input change] → buildDocumentJSON() → updatePreviewDOM(json)
```

Auto-save (every 30s or on blur of major fields) posts JSON to
`api.php?action=update&id=X` in the background — no page reload.

For the line items table: JS re-renders the `<tbody>` rows and recalculates
subtotal / VAT / total on every change.

---

## Build Phases

### Phase 1 — Core Editor (est. 2–3 days)
- [ ] SQLite setup + `api.php` (save / load)
- [ ] `editor.php` split-panel layout (CSS grid, resizable)
- [ ] Form covering all fields from both invoice models
- [ ] `buildDocumentJSON()` + `updatePreviewDOM()` live preview
- [ ] `preview.php` with the A4 template (PHP-rendered from JSON)

### Phase 2 — Line Items (est. 1 day)
- [ ] Dynamic add / remove / reorder rows (JS)
- [ ] Auto-calculate subtotal, VAT amount, total
- [ ] "offert" (free item) toggle per line
- [ ] Optional: colspan row for fees/surcharges without REF

### Phase 3 — History & Workflow (est. 1 day)
- [ ] `index.php` document list with status badges
- [ ] Duplicate document
- [ ] Status transitions: draft → final → paid
- [ ] Document number auto-increment by type + year

### Phase 4 — Polish (optional, later)
- [ ] Logo upload (stored as base64 in JSON)
- [ ] Customer address book (`customers` table, autocomplete in Bill-To)
- [ ] Issuer profile editor (UI for `config/issuer.json`)
- [ ] Export raw JSON button (backup / import)
- [ ] Dual-currency support (second amount column)

---

## Open Questions to Decide Before Building

1. **SQLite vs MySQL** — SQLite is the right call for a single user / local server.
   Switch to MySQL only if multiple people need to edit simultaneously.

2. **Issuer profile** — NOVOCIB data is the same on every document. Store as
   `config/issuer.json`, copy into each new document JSON as editable defaults.

3. **Dual currency** — Your invoice HTML has a "ITEMS dual currency" comment
   but it's unused. Include the toggle from the start or add in Phase 4?

4. **Customer address book** — Worth doing in Phase 4. Lets you autocomplete
   Bill-To from past invoices. Uses a separate `customers` table.

5. **Document locking** — Should "final" documents be fully locked, or allow
   edits with a confirmation prompt?

---

## Notes on Your Existing HTML Models

Both invoices share the same CSS. Key differences between them to handle:

| Feature               | `INVOICE_model.html` | `Invoice_260915.html` |
|-----------------------|----------------------|-----------------------|
| Extra info rows       | Due Date, Service Date | Quote ref, Due Date, Tracking |
| Totals rows           | Subtotal, VAT, Total | Subtotal, VAT, Amount Paid, Balance Due |
| Balance label         | "TOTAL DUE (EUR)"    | "BALANCE DUE (EUR)"  |
| VAT mention           | Article 259 export   | HS code / origin text |
| Notes content         | VAT note             | "Paid in full" + tracking |
| Terms tone            | Immediate payment    | 30-day + "STATUS: PAID" |

The JSON structure above handles all of these via optional fields and toggles.