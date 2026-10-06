<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Documentation — DocEditor</title>
  <link rel="stylesheet" href="assets/editor.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <style>
    html, body { height: auto; overflow: auto; background: #f0f2f5; }

    /* Layout */
    .doc-wrap {
      display: flex;
      max-width: 1060px;
      margin: 0 auto;
      padding: 36px 24px 100px;
      gap: 36px;
      align-items: flex-start;
    }

    /* Sidebar */
    .doc-nav {
      width: 200px;
      flex-shrink: 0;
      position: sticky;
      top: 24px;
    }
    .doc-nav-label {
      font-size: 10.5px;
      font-weight: 700;
      letter-spacing: .9px;
      text-transform: uppercase;
      color: var(--ui-text-muted);
      padding: 0 8px;
      margin-bottom: 10px;
    }
    .doc-nav a {
      display: block;
      padding: 5px 10px;
      font-size: 13px;
      color: var(--ui-text-soft);
      text-decoration: none;
      border-left: 2px solid transparent;
      border-radius: 0 4px 4px 0;
      line-height: 1.4;
      transition: all .13s;
      margin-bottom: 1px;
    }
    .doc-nav a:hover { color: var(--ui-primary); background: rgba(55,113,200,.07); border-left-color: var(--ui-primary); }
    .doc-nav a.sub { padding-left: 22px; font-size: 12px; color: var(--ui-text-muted); }
    .doc-nav a.sub:hover { color: var(--ui-primary); }
    .doc-nav hr { border: none; border-top: 1px solid var(--ui-border); margin: 8px 0; }

    /* Main content */
    .doc-body { flex: 1; min-width: 0; }

    /* Hero */
    .doc-hero {
      background: linear-gradient(135deg, var(--ui-primary) 0%, #2254a8 100%);
      border-radius: 10px;
      padding: 32px 36px 30px;
      color: white;
      margin-bottom: 28px;
    }
    .doc-hero h1 { font-size: 24px; font-weight: 800; margin: 0 0 8px; letter-spacing: -.3px; }
    .doc-hero p  { font-size: 14px; opacity: .85; line-height: 1.65; margin: 0; max-width: 520px; }

    /* Quick links */
    .quick-links {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
      margin-bottom: 28px;
    }
    .ql-card {
      background: white;
      border-radius: 8px;
      padding: 16px;
      text-decoration: none;
      border: 1px solid var(--ui-border);
      transition: box-shadow .15s, border-color .15s;
    }
    .ql-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.09); border-color: var(--ui-primary); }
    .ql-card .ql-icon { font-size: 22px; margin-bottom: 6px; }
    .ql-card .ql-title { font-size: 13px; font-weight: 700; color: var(--ui-text); margin-bottom: 3px; }
    .ql-card .ql-desc { font-size: 11.5px; color: var(--ui-text-muted); line-height: 1.4; }

    /* Sections */
    .doc-section {
      background: white;
      border-radius: 8px;
      border: 1px solid var(--ui-border);
      padding: 26px 30px;
      margin-bottom: 18px;
      scroll-margin-top: 20px;
    }
    .doc-section h2 {
      font-size: 16px;
      font-weight: 700;
      color: var(--ui-text);
      margin: 0 0 18px;
      padding-bottom: 12px;
      border-bottom: 1px solid var(--ui-border);
      display: flex;
      align-items: center;
      gap: 9px;
    }
    .doc-section h2 .sec-icon { font-size: 18px; }
    .doc-section h3 {
      font-size: 13.5px;
      font-weight: 700;
      color: var(--ui-text);
      margin: 22px 0 8px;
    }
    .doc-section h3:first-of-type { margin-top: 0; }
    .doc-section p {
      font-size: 13.5px;
      line-height: 1.7;
      color: var(--ui-text-soft);
      margin: 0 0 10px;
    }
    .doc-section p:last-child { margin-bottom: 0; }
    .doc-section ul {
      margin: 8px 0 12px 18px;
      padding: 0;
    }
    .doc-section ul li {
      font-size: 13.5px;
      color: var(--ui-text-soft);
      line-height: 1.7;
      margin-bottom: 3px;
    }

    /* Numbered steps */
    .steps { list-style: none; margin: 0; padding: 0; counter-reset: step; }
    .steps li {
      counter-increment: step;
      display: flex;
      gap: 13px;
      align-items: flex-start;
      padding: 11px 0;
      border-bottom: 1px solid var(--ui-border);
      font-size: 13.5px;
      color: var(--ui-text-soft);
      line-height: 1.65;
    }
    .steps li:last-child { border-bottom: none; padding-bottom: 0; }
    .steps li::before {
      content: counter(step);
      display: flex; align-items: center; justify-content: center;
      width: 22px; height: 22px; min-width: 22px;
      background: var(--ui-primary); color: white;
      border-radius: 50%; font-size: 11px; font-weight: 700;
      margin-top: 2px;
    }
    .steps li strong { color: var(--ui-text); }

    /* Reference table */
    .ref-table { width: 100%; border-collapse: collapse; font-size: 13px; margin: 10px 0; }
    .ref-table th {
      background: var(--ui-input-bg);
      text-align: left;
      padding: 7px 12px;
      font-weight: 600;
      font-size: 11.5px;
      color: var(--ui-text-soft);
      border: 1px solid var(--ui-border);
    }
    .ref-table td {
      padding: 8px 12px;
      border: 1px solid var(--ui-border);
      vertical-align: top;
      color: var(--ui-text-soft);
      line-height: 1.5;
    }
    .ref-table td:first-child { font-weight: 600; color: var(--ui-text); white-space: nowrap; }

    /* Tip / warning callouts */
    .tip, .warn, .note {
      border-radius: 0 6px 6px 0;
      padding: 11px 15px;
      font-size: 13px;
      line-height: 1.65;
      color: var(--ui-text-soft);
      margin: 14px 0;
    }
    .tip  { background: #eef4ff; border-left: 3px solid var(--ui-primary); }
    .warn { background: #fff8ec; border-left: 3px solid var(--ui-warning); }
    .note { background: #f3f4f6; border-left: 3px solid var(--ui-border); }
    .tip strong  { color: var(--ui-primary); }
    .warn strong { color: var(--ui-warning); }
    .note strong { color: var(--ui-text); }

    /* Code / JSON */
    pre {
      background: #1e2230;
      color: #c9d1e0;
      border-radius: 7px;
      padding: 16px 18px;
      font-size: 12px;
      line-height: 1.7;
      overflow-x: auto;
      margin: 12px 0;
      font-family: var(--font-mono);
    }
    code {
      background: var(--ui-input-bg);
      border: 1px solid var(--ui-border);
      border-radius: 3px;
      padding: 1px 5px;
      font-size: 12px;
      font-family: var(--font-mono);
      color: var(--ui-text);
    }
    pre code { background: none; border: none; padding: 0; color: inherit; font-size: inherit; }
    kbd {
      background: #edf0f5;
      border: 1px solid #c8cdd8;
      border-bottom-width: 2px;
      border-radius: 4px;
      padding: 1px 6px;
      font-size: 12px;
      font-family: var(--font-mono);
      color: var(--ui-text);
    }

    /* File tree */
    .file-tree { font-size: 13px; font-family: var(--font-mono); line-height: 2; }
    .file-tree .dir  { color: var(--ui-text); font-weight: 600; }
    .file-tree .file { color: var(--ui-text-soft); }
    .file-tree .note { color: var(--ui-text-muted); font-style: italic; font-family: var(--font); font-size: 12px; }

    /* Action badge inline */
    .act {
      display: inline-flex; align-items: center;
      background: #eee; border-radius: 4px;
      padding: 1px 8px; font-size: 12px; font-weight: 600;
      color: var(--ui-text); white-space: nowrap;
    }
    .act.primary { background: var(--ui-primary); color: white; }
    .act.danger  { background: var(--ui-danger);  color: white; }
  </style>
</head>
<body>

<header class="topbar" style="position:relative">
  <div class="topbar-brand">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect x="3" y="2" width="14" height="16" rx="2" fill="rgba(255,255,255,0.25)" stroke="white" stroke-width="1.5"/>
      <line x1="6" y1="7" x2="14" y2="7" stroke="white" stroke-width="1.2"/>
      <line x1="6" y1="10" x2="14" y2="10" stroke="white" stroke-width="1.2"/>
      <line x1="6" y1="13" x2="11" y2="13" stroke="white" stroke-width="1.2"/>
    </svg>
    DocEditor
  </div>
  <div class="topbar-center">
    <span class="topbar-doc-type">Documentation</span>
  </div>
  <div style="display:flex;gap:8px;margin-left:auto">
    <a href="index.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i> Documents</a>
  </div>
</header>

<div class="doc-wrap">

  <!-- ── Sidebar nav ── -->
  <nav class="doc-nav">
    <div class="doc-nav-label">Contents</div>
    <a href="#overview">Overview</a>
    <a href="#getting-started">Getting started</a>
    <hr>
    <a href="#documents">Documents page</a>
    <a href="#doc-actions" class="sub">Actions</a>
    <a href="#doc-search" class="sub">Search & filter</a>
    <hr>
    <a href="#editor">The editor</a>
    <a href="#doc-header" class="sub">Document header</a>
    <a href="#doc-type" class="sub">Document type</a>
    <a href="#bill-to" class="sub">Bill To</a>
    <a href="#line-items" class="sub">Line items</a>
    <a href="#totals" class="sub">Totals & VAT</a>
    <a href="#notes-terms" class="sub">Notes & terms</a>
    <a href="#bank" class="sub">Bank details</a>
    <a href="#issuer" class="sub">Issuer</a>
    <a href="#saving" class="sub">Saving & printing</a>
    <hr>
    <a href="#customers">Customers</a>
    <a href="#cust-picker" class="sub">Picking a customer</a>
    <a href="#cust-auto" class="sub">Auto-save</a>
    <a href="#cust-manage" class="sub">Managing</a>
    <hr>
    <a href="#json">JSON import / export</a>
    <a href="#json-doc" class="sub">Document JSON</a>
    <a href="#json-customers" class="sub">Customer JSON</a>
    <a href="#json-ai" class="sub">AI workflow</a>
    <hr>
    <a href="#files">File structure</a>
  </nav>

  <!-- ── Main content ── -->
  <div class="doc-body">

    <!-- Hero -->
    <div class="doc-hero">
      <h1><i class="fa-regular fa-file-lines"></i> DocEditor — User Guide</h1>
      <p>A self-hosted PHP application for creating professional invoices, quotes, and custom documents with a live preview, a customer address book, and JSON import/export for AI-assisted filling.</p>
    </div>

    <!-- Quick links -->
    <div class="quick-links">
      <a class="ql-card" href="#editor">
        <div class="ql-icon"><i class="fa-solid fa-pen-to-square"></i></div>
        <div class="ql-title">Creating a document</div>
        <div class="ql-desc">Fill the form, watch the live preview, save and print</div>
      </a>
      <a class="ql-card" href="#customers">
        <div class="ql-icon"><i class="fa-solid fa-users"></i></div>
        <div class="ql-title">Customers</div>
        <div class="ql-desc">Pick from your address book or let it save automatically</div>
      </a>
      <a class="ql-card" href="#json">
        <div class="ql-icon"><i class="fa-solid fa-code"></i></div>
        <div class="ql-title">JSON / AI workflow</div>
        <div class="ql-desc">Export a template, let AI fill it, import back</div>
      </a>
      <a class="ql-card" href="#files">
        <div class="ql-icon"><i class="fa-solid fa-folder-open"></i></div>
        <div class="ql-title">File structure</div>
        <div class="ql-desc">What every file does and where the database lives</div>
      </a>
    </div>

    <!-- Overview -->
    <div class="doc-section" id="overview">
      <h2><span class="sec-icon"><i class="fa-solid fa-map"></i></span> Overview</h2>
      <p>DocEditor runs entirely in PHP with a SQLite database — no external services, no internet connection needed. All data lives in a single file: <code>db/documents.sqlite</code>.</p>
      <p>The app has three pages:</p>
      <table class="ref-table">
        <thead><tr><th>Page</th><th>File</th><th>What it does</th></tr></thead>
        <tbody>
          <tr><td>Documents</td><td><code>index.php</code></td><td>Your document history — list, search, filter, duplicate, delete.</td></tr>
          <tr><td>Editor</td><td><code>editor.php</code></td><td>Split-panel editor: form on the left, live A4 preview on the right. Auto-saves every 8 seconds.</td></tr>
          <tr><td>Customers</td><td><code>customers.php</code></td><td>Address book. Add, edit, delete, import and export customer records.</td></tr>
        </tbody>
      </table>
      <div class="note"><strong>Database:</strong> Everything is stored in <code>db/documents.sqlite</code>. Back up this file regularly. Deleting it wipes all documents and customers.</div>
    </div>

    <!-- Getting started -->
    <div class="doc-section" id="getting-started">
      <h2><span class="sec-icon"><i class="fa-solid fa-rocket"></i></span> Getting started</h2>
      <ol class="steps">
        <li>Drop the project folder on any PHP server (Apache, Nginx, or <code>php -S localhost:8000</code>). PHP 8.0+ and the <code>pdo_sqlite</code> extension are required.</li>
        <li>Open <code>index.php</code> in your browser. The <code>db/</code> folder and <code>documents.sqlite</code> are created automatically on the first request.</li>
        <li>Click <span class="act primary">+ Invoice</span> to create your first document.</li>
      </ol>
      <div class="tip"><strong>Tip:</strong> The <code>db/</code> directory must be writable by the web server. If you see a database error, run <code>mkdir db && chmod 775 db</code> from the project root.</div>
    </div>

    <!-- Documents page -->
    <div class="doc-section" id="documents">
      <h2><span class="sec-icon"><i class="fa-solid fa-list"></i></span> Documents page</h2>
      <p><code>index.php</code> is the home screen. It shows all your documents in a table, newest first.</p>

      <h3 id="doc-actions">Row actions</h3>
      <table class="ref-table">
        <thead><tr><th>Button</th><th>What it does</th></tr></thead>
        <tbody>
          <tr><td><span class="act">Edit</span></td><td>Opens the document in the split-panel editor.</td></tr>
          <tr><td><span class="act">Print</span></td><td>Opens the clean A4 print view in a new tab. Use <kbd>Ctrl+P</kbd> → <em>Save as PDF</em>.</td></tr>
          <tr><td><span class="act">Copy</span></td><td>Duplicates the document. The copy gets the same type and customer; its number gets <em>-COPY</em> appended.</td></tr>
          <tr><td><span class="act danger">Del</span></td><td>Shows a confirmation prompt, then permanently deletes the document. This cannot be undone.</td></tr>
        </tbody>
      </table>

      <h3 id="doc-search">Search & filter</h3>
      <p>The search box filters by document number or client name as you type. The type dropdown restricts the list to Invoice, Quote, or Credit Note.</p>
    </div>

    <!-- Editor -->
    <div class="doc-section" id="editor">
      <h2><span class="sec-icon"><i class="fa-solid fa-pen-to-square"></i></span> The Editor</h2>
      <p>The editor is a two-panel layout. The <strong>form panel</strong> (left) contains collapsible sections for every field. The <strong>preview panel</strong> (right) renders a pixel-accurate A4 document that updates as you type — no submit button needed.</p>
      <p>The document type and number are displayed in large text in the centre of the topbar so you always know which document you're editing.</p>

      <h3 id="doc-header">Document header</h3>
      <table class="ref-table">
        <thead><tr><th>Field</th><th>Notes</th></tr></thead>
        <tbody>
          <tr><td>Number</td><td>Free text. Suggested format: <code>INV-YYMMDD-NN</code>.</td></tr>
          <tr><td>Date</td><td>Document date (shown on the printed document).</td></tr>
          <tr><td>Due Date</td><td>Optional. Adds a "Due Date" row to the document info block.</td></tr>
          <tr><td>Service Date</td><td>Optional. Useful for lab services billed after completion.</td></tr>
          <tr><td>Quote Ref.</td><td>Optional reference to a related quote. Adds a "Quote" row on the document.</td></tr>
          <tr><td>Tracking #</td><td>Optional shipment or parcel tracking number.</td></tr>
          <tr><td>Currency</td><td>Selecting EUR, USD, GBP, or CHF auto-fills the symbol field.</td></tr>
          <tr><td>Symbol</td><td>Editable override (e.g. type <code>CHF </code> with a trailing space for spacing).</td></tr>
        </tbody>
      </table>

      <h3 id="doc-type">Document type</h3>
      <p>The <strong>Document Type</strong> dropdown sets the large heading printed on the document.</p>
      <table class="ref-table">
        <thead><tr><th>Option</th><th>Heading on document</th></tr></thead>
        <tbody>
          <tr><td>Invoice</td><td><code>INVOICE</code></td></tr>
          <tr><td>Quote</td><td><code>QUOTE</code></td></tr>
          <tr><td>Credit Note</td><td><code>CREDIT NOTE</code></td></tr>
          <tr><td>Other</td><td><code>OTHER</code></td></tr>
          <tr><td>Custom…</td><td>Whatever you type in the <em>Custom Title</em> field that appears — e.g. <code>DELIVERY NOTE</code>, <code>PROFORMA</code>, <code>PURCHASE ORDER</code>.</td></tr>
        </tbody>
      </table>

      <h3 id="bill-to">Bill To</h3>
      <p>Fill in the customer's company name, address, city/country, contact person, phone and VAT number. These appear in the "Bill To" block on the printed document.</p>
      <p>Use the <span class="act"><i class="fa-solid fa-address-book"></i> Pick customer</span> button to search your address book instead of typing — see the <a href="#customers">Customers</a> section for details.</p>

      <h3 id="line-items">Line items</h3>
      <p>Click <span class="act primary">+ Add line</span> to add a product or service row. Each row has:</p>
      <table class="ref-table">
        <thead><tr><th>Field</th><th>Notes</th></tr></thead>
        <tbody>
          <tr><td>Name</td><td>Main label (bold in the preview).</td></tr>
          <tr><td>Description</td><td>Optional sub-line in smaller text below the name.</td></tr>
          <tr><td>Ref.</td><td>Internal catalogue reference, e.g. <code>S1200-03-NA</code>.</td></tr>
          <tr><td>Unit Price</td><td>Price per unit excluding VAT.</td></tr>
          <tr><td>Qty</td><td>Quantity. Amount = Unit Price × Qty, calculated automatically.</td></tr>
          <tr><td>Free / "offert"</td><td>Tick to mark the line as complimentary. Unit price and amount show as <em>offert</em> and are excluded from the subtotal.</td></tr>
        </tbody>
      </table>
      <p>Click the <strong><i class="fa-solid fa-xmark"></i></strong> button on any row to remove it. Subtotal, VAT and total recalculate instantly.</p>

      <h3 id="totals">Totals & VAT</h3>
      <table class="ref-table">
        <thead><tr><th>Field</th><th>Notes</th></tr></thead>
        <tbody>
          <tr><td>VAT Rate (%)</td><td>Applied to the subtotal. Set to 0 for tax-exempt / export invoices.</td></tr>
          <tr><td>Balance Label</td><td>Label for the grand total row, e.g. <code>TOTAL DUE</code>.</td></tr>
          <tr><td>Show "Amount Paid"</td><td>Tick to show a paid amount row. The balance = total − amount paid.</td></tr>
          <tr><td>Amount Paid</td><td>Only shown/used when the checkbox above is ticked.</td></tr>
        </tbody>
      </table>

      <h3 id="notes-terms">Notes & Terms</h3>
      <table class="ref-table">
        <thead><tr><th>Field</th><th>Where it appears on the document</th></tr></thead>
        <tbody>
          <tr><td>VAT Mention</td><td>Italic line below the totals block — e.g. <em>VAT not applicable — export outside the EU.</em></td></tr>
          <tr><td>Notes</td><td>Free-text block below the VAT mention.</td></tr>
          <tr><td>Terms & Conditions</td><td>Block below the bank details.</td></tr>
          <tr><td>Footer — Thanks</td><td>Centred line in the document footer, e.g. <em>Thank you for your business!</em></td></tr>
          <tr><td>Footer — Contact</td><td>Small contact line(s) in the footer.</td></tr>
        </tbody>
      </table>

      <h3 id="bank">Bank details</h3>
      <p>The <strong>Preset</strong> dropdown selects a pre-configured bank account. Two presets are built in:</p>
      <table class="ref-table">
        <thead><tr><th>Preset</th><th>Account</th><th>IBAN</th></tr></thead>
        <tbody>
          <tr>
            <td>EUR — Banque Populaire</td>
            <td>SAS NOVOCIB</td>
            <td><code>FR76 1680 7004 0081 0876 0421 151</code></td>
          </tr>
          <tr>
            <td>USD — International Wire</td>
            <td>SAS NOVOCIB-CAV USD</td>
            <td><code>FR76 1680 7004 0081 3911 3449 109</code></td>
          </tr>
          <tr>
            <td>Custom…</td>
            <td colspan="2">All six fields become freely editable.</td>
          </tr>
        </tbody>
      </table>
      <p>The EUR preset is the default on every new document. Switching presets fills all six bank fields instantly.</p>

      <h3 id="issuer">Issuer (your company)</h3>
      <p>Fields for your company name, address, city, email and legal information. These are pre-filled with the NOVOCIB defaults from <code>api.php</code>. Changes are stored per-document — there is currently no global issuer settings page. To change the default, edit the <code>defaultDocument()</code> function in <code>api.php</code>.</p>
      <p>The logo is loaded from <code>assets/logo.png</code> and embedded as a base64 data URI in the preview. To update the logo, replace that file.</p>

      <h3 id="saving">Saving & printing</h3>
      <ol class="steps">
        <li><strong>Auto-save</strong> triggers 8 seconds after your last change. The status dot in the bottom-left of the form panel shows the state: grey = unsaved, green = saved, red = error.</li>
        <li>Click <span class="act primary">Save</span> in the topbar to save immediately without waiting.</li>
        <li>Click <span class="act"><i class="fa-solid fa-print"></i> Print</span> to save (if needed) and open the clean A4 view in a new tab. In the browser print dialog, set margins to <em>None</em> and enable <em>Background graphics</em>, then save as PDF.</li>
      </ol>
      <div class="tip"><strong>Tip:</strong> The URL updates to <code>editor.php?id=X</code> the first time a document is saved. Bookmark it to return directly to the document.</div>
    </div>

    <!-- Customers -->
    <div class="doc-section" id="customers">
      <h2><span class="sec-icon"><i class="fa-solid fa-users"></i></span> Customers</h2>
      <p>The customer address book stores: company name, address, city/country, contact person, phone, and VAT number. Records are shared across all documents.</p>

      <h3 id="cust-picker">Picking a customer in the editor</h3>
      <ol class="steps">
        <li>In the <strong>Bill To</strong> section header, click <span class="act"><i class="fa-solid fa-address-book"></i> Pick customer</span>.</li>
        <li>A modal opens with a live search box. Start typing a name, city, or contact name — results filter as you type.</li>
        <li>Click any row to fill all Bill To fields instantly. The modal closes and the preview updates.</li>
      </ol>
      <div class="tip"><strong>Keyboard:</strong> Press <kbd>Esc</kbd> to close the picker without selecting. Click the grey overlay to also dismiss it.</div>
      <p>The footer of the picker has a link to open the <strong>Customers</strong> management page in a new tab.</p>

      <h3 id="cust-auto">Auto-saving customers from documents</h3>
      <p>Every time you save a document that has a customer name filled in, the customer is automatically created or updated in the address book. You don't need to manually add customers you have already invoiced.</p>
      <div class="warn"><strong>Note:</strong> If you edit a customer's address on one document and save it, the address book record for that name is updated to the new values. The change does <em>not</em> retroactively alter the stored data in older documents.</div>

      <h3 id="cust-manage">Managing customers</h3>
      <p>Go to <strong><i class="fa-solid fa-users"></i> Customers</strong> from the Documents topbar. You can:</p>
      <ul>
        <li>Search across all records by name, city, or contact</li>
        <li>Add a new customer with <span class="act primary">+ New Customer</span></li>
        <li>Edit any record — click <span class="act">Edit</span> to open an inline form at the top of the page</li>
        <li>Delete a record with <span class="act danger">Delete</span> and a confirmation prompt (does not affect existing documents)</li>
        <li>Export all customers to a JSON file with <span class="act"><i class="fa-solid fa-download"></i> Export JSON</span></li>
        <li>Import customers from a JSON file with <span class="act"><i class="fa-solid fa-upload"></i> Import JSON</span></li>
      </ul>
    </div>

    <!-- JSON -->
    <div class="doc-section" id="json">
      <h2><span class="sec-icon"><i class="fa-solid fa-code"></i></span> JSON Import / Export</h2>
      <p>Both the editor and the customers page can read and write JSON. This makes it easy to back up data, migrate records, or use an AI assistant to fill in documents.</p>

      <h3 id="json-doc">Document JSON</h3>
      <p>In the editor topbar:</p>
      <ul>
        <li><span class="act"><i class="fa-solid fa-download"></i> Export JSON</span> — downloads the current form state as a <code>.json</code> file. The file is named from the document type and number, e.g. <code>INVOICE_INV-260901-01.json</code>. The logo is stripped to keep the file small and AI-friendly.</li>
        <li><span class="act"><i class="fa-solid fa-upload"></i> Import JSON</span> — opens a file picker. Selecting a valid <code>.json</code> file fills every form field, updates the live preview, and schedules an auto-save. Review the result, then click <span class="act primary">Save</span>.</li>
      </ul>
      <p>The full JSON structure for a document looks like this (the same format exported by the <span class="act">⬇ Export JSON</span> button):</p>
      <pre><code>{
  "type": "INVOICE",
  "number": "INV-260901-01",
  "date": "2026-09-01",
  "due_date": "",
  "service_date": "",
  "quote_ref": "",
  "tracking": "",
  "currency": "EUR",
  "currency_symbol": "€",
  "issuer": {
    "name": "NOVOCIB SAS",
    "address": "BD de Chatillon, Quai Jean Voisin",
    "city": "62200 Boulogne-sur-Mer — France",
    "email": "contact@novocib.com",
    "legal": "SAS, société par actions simplifiée..."
  },
  "customer": {
    "name": "Pureture",
    "address": "4F, 2121-3 Nambusunhwan-ro",
    "city": "06725 Seocho-gu, Seoul — Republic of Korea",
    "contact": "Sohee Yoon",
    "phone": "+82-10-6681-1162",
    "vat": ""
  },
  "items": [
    {
      "name": "HPLC-UV Analysis",
      "description": "Kinetics study, 3 concentrations",
      "reference": "S1200-03-NA",
      "unit_price": 300,
      "qty": 2,
      "is_free": false
    }
  ],
  "vat_rate": 0,
  "amount_paid": 0,
  "show_amount_paid": false,
  "balance_label": "TOTAL DUE",
  "vat_mention": "VAT not applicable - export outside the EU",
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
  "footer_contact": "If you have any questions, please contact us.\n• contact@novocib.com"
}</code></pre>

      <h3 id="json-customers">Customer JSON</h3>
      <p>On the Customers page, <span class="act"><i class="fa-solid fa-download"></i> Export JSON</span> downloads all customer records as a clean array (internal IDs and timestamps are stripped). <span class="act"><i class="fa-solid fa-upload"></i> Import JSON</span> reads the array and saves each entry — entries missing a <code>name</code> are skipped. The toast message tells you how many were imported and how many were skipped.</p>
      <pre><code>[
  {
    "name": "Pureture",
    "address": "4F, 2121-3 Nambusunhwan-ro",
    "city": "06725 Seocho-gu, Seoul — Republic of Korea",
    "contact": "Sohee Yoon",
    "phone": "+82-10-6681-1162",
    "vat": ""
  }
]</code></pre>

      <h3 id="json-ai">AI workflow — step by step</h3>
      <p>The fastest way to create a filled invoice with an AI assistant:</p>
      <ol class="steps">
        <li>Open a <strong>new Invoice</strong> from the Documents page. The form loads with your default issuer details.</li>
        <li>Click <span class="act"><i class="fa-solid fa-download"></i> Export JSON</span>. This downloads the current form as a JSON template with all the field names and default values already set.</li>
        <li>Open your AI assistant (Claude, ChatGPT, etc.). Attach or paste the JSON file and give it a prompt like: <em>"Fill this invoice for customer Pureture — 2 units of HPLC-UV Analysis at €300 each, VAT not applicable, use the USD bank account, due immediately."</em></li>
        <li>The AI returns a completed JSON. Save it as a <code>.json</code> file.</li>
        <li>Back in the editor, click <span class="act"><i class="fa-solid fa-upload"></i> Import JSON</span> and select the file. The form fills instantly and the preview updates.</li>
        <li>Review the document in the preview panel. Make any corrections, then click <span class="act primary">Save</span> and <span class="act"><i class="fa-solid fa-print"></i> Print</span>.</li>
      </ol>
      <div class="tip"><strong>Tip:</strong> Exporting on a blank new document gives the cleanest template — the logo is stripped automatically, so the file stays small and the AI doesn't have to deal with a base64 blob.</div>
    </div>

    <!-- File structure -->
    <div class="doc-section" id="files">
      <h2><span class="sec-icon"><i class="fa-solid fa-folder-open"></i></span> File structure</h2>
      <div class="file-tree">
        <div><span class="dir">project/</span></div>
        <div>&nbsp;&nbsp;├── <span class="file">index.php</span> &nbsp;<span class="note">— Document history & management</span></div>
        <div>&nbsp;&nbsp;├── <span class="file">editor.php</span> &nbsp;<span class="note">— Split-panel document editor</span></div>
        <div>&nbsp;&nbsp;├── <span class="file">preview.php</span> &nbsp;<span class="note">— Clean A4 print view (opened by the Print button)</span></div>
        <div>&nbsp;&nbsp;├── <span class="file">customers.php</span> &nbsp;<span class="note">— Customer address book</span></div>
        <div>&nbsp;&nbsp;├── <span class="file">documentation.php</span> &nbsp;<span class="note">— This page</span></div>
        <div>&nbsp;&nbsp;├── <span class="file">api.php</span> &nbsp;<span class="note">— JSON API for all reads and writes</span></div>
        <div>&nbsp;&nbsp;├── <span class="dir">assets/</span></div>
        <div>&nbsp;&nbsp;│&nbsp;&nbsp;&nbsp;├── <span class="file">editor.css</span> &nbsp;<span class="note">— All UI styles</span></div>
        <div>&nbsp;&nbsp;│&nbsp;&nbsp;&nbsp;├── <span class="file">document.css</span> &nbsp;<span class="note">— A4 print styles</span></div>
        <div>&nbsp;&nbsp;│&nbsp;&nbsp;&nbsp;└── <span class="file">logo.png</span> &nbsp;<span class="note">— Company logo (replace to update on all documents)</span></div>
        <div>&nbsp;&nbsp;├── <span class="dir">templates/</span></div>
        <div>&nbsp;&nbsp;│&nbsp;&nbsp;&nbsp;└── <span class="file">document.php</span> &nbsp;<span class="note">— PHP template used by preview.php</span></div>
        <div>&nbsp;&nbsp;└── <span class="dir">db/</span></div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;└── <span class="file">documents.sqlite</span> &nbsp;<span class="note">— Auto-created. Back this up!</span></div>
      </div>
      <div class="warn" style="margin-top:18px"><strong>Backup:</strong> The entire database is the single file <code>db/documents.sqlite</code>. Copy it to a safe location regularly. There is no recycle bin — deleted documents and customers are gone immediately.</div>
    </div>

  </div><!-- /doc-body -->
</div><!-- /doc-wrap -->

</body>
</html>