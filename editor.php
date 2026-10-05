<?php
/**
 * editor.php — Split-panel document editor
 * ?id=X to edit existing | ?type=INVOICE to create new
 */

define('DB_PATH', __DIR__ . '/db/documents.sqlite');

$editId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$newType = strtoupper(trim($_GET['type'] ?? 'INVOICE'));

// ── Logo: embed as base64 for JS preview ──
$logoPath = __DIR__ . '/assets/logo.png';
$logoDataUri = '';
if (file_exists($logoPath)) {
    $logoDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}

// Load existing document for pre-fill (server-side, for page title only)
$pageTitle = $editId ? "Edit Document #$editId" : "New $newType";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> — Invoice Editor</title>
  <link rel="stylesheet" href="assets/editor.css">
  <link rel="stylesheet" href="assets/document.css">
</head>
<body>

<!-- ── Top bar ── -->
<header class="topbar">
  <div class="topbar-brand">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect x="3" y="2" width="14" height="16" rx="2" fill="rgba(255,255,255,0.25)" stroke="white" stroke-width="1.5"/>
      <line x1="6" y1="7" x2="14" y2="7" stroke="white" stroke-width="1.2"/>
      <line x1="6" y1="10" x2="14" y2="10" stroke="white" stroke-width="1.2"/>
      <line x1="6" y1="13" x2="11" y2="13" stroke="white" stroke-width="1.2"/>
    </svg>
    DocEditor
    <span id="topbar-docnum"></span>
  </div>

  <span id="topbar-type-badge" class="topbar-doc-type">INVOICE</span>

  <a href="index.php" class="btn btn-ghost btn-sm">← History</a>
  <button class="btn btn-ghost btn-sm" id="btn-export" title="Download document as JSON">⬇ Export JSON</button>
  <label class="btn btn-ghost btn-sm" id="btn-import-label" title="Load a JSON file into the editor" style="cursor:pointer">⬆ Import JSON<input type="file" id="btn-import" accept=".json,application/json" style="display:none"></label>
  <button class="btn btn-ghost btn-sm" id="btn-print" title="Print / Save as PDF">🖨 Print</button>
  <button class="btn btn-primary btn-sm" id="btn-save">Save</button>
</header>

<!-- ── Body ── -->
<div class="app-body">

  <!-- LEFT: form panel -->
  <aside class="form-panel">
    <div class="form-panel-scroll" id="form-scroll">

      <!-- Section: Document Header -->
      <div class="form-section">
        <div class="form-section-header open" data-section="header">
          Document Header <span class="chevron">▼</span>
        </div>
        <div class="form-section-body open" id="sec-header">
          <div class="field">
            <label>Document Type</label>
            <select id="f-type">
              <option value="INVOICE">Invoice</option>
              <option value="QUOTE">Quote</option>
              <option value="CREDIT NOTE">Credit Note</option>
              <option value="OTHER">Other</option>
              <option value="CUSTOM">Custom…</option>
            </select>
          </div>
          <div class="field" id="f-type-custom-wrap" style="display:none">
            <label>Custom Title</label>
            <input id="f-type-custom" type="text" placeholder="e.g. DELIVERY NOTE, PROFORMA…">
          </div>
          <div class="field-row col2">
            <div class="field"><label>Number</label><input id="f-number" type="text" placeholder="INV-260805-01"></div>
            <div class="field"><label>Date</label><input id="f-date" type="date"></div>
          </div>
          <div class="field-row col2">
            <div class="field"><label>Due Date</label><input id="f-due-date" type="date"></div>
            <div class="field"><label>Service Date</label><input id="f-service-date" type="date"></div>
          </div>
          <div class="field-row col2">
            <div class="field"><label>Quote Ref.</label><input id="f-quote-ref" type="text" placeholder="260812"></div>
            <div class="field"><label>Tracking #</label><input id="f-tracking" type="text" placeholder="1Z W3V ..."></div>
          </div>
          <div class="field-row col2">
            <div class="field"><label>Currency</label>
              <select id="f-currency">
                <option value="EUR">EUR</option>
                <option value="USD">USD</option>
                <option value="GBP">GBP</option>
                <option value="CHF">CHF</option>
              </select>
            </div>
            <div class="field"><label>Symbol</label><input id="f-currency-symbol" type="text" placeholder="€" maxlength="4"></div>
          </div>
        </div>
      </div>

      <!-- Section: Bill To -->
      <div class="form-section">
        <div class="form-section-header open" data-section="customer">
          Bill To <span class="chevron">▼</span>
        </div>
        <div class="form-section-body open" id="sec-customer">
          <div class="field"><label>Company / Customer Name</label><input id="f-cust-name" type="text" placeholder="Pureture"></div>
          <div class="field"><label>Address line</label><input id="f-cust-addr" type="text" placeholder="4F, 2121-3 Nambusunhwan-ro"></div>
          <div class="field"><label>City / Country</label><input id="f-cust-city" type="text" placeholder="06725 Seocho-gu, Seoul — Republic of Korea"></div>
          <div class="field-row col2">
            <div class="field"><label>Contact Person</label><input id="f-cust-contact" type="text" placeholder="Sohee Yoon"></div>
            <div class="field"><label>Phone</label><input id="f-cust-phone" type="text" placeholder="+82-10-6681-1162"></div>
          </div>
          <div class="field"><label>VAT Number</label><input id="f-cust-vat" type="text" placeholder="Optional"></div>
        </div>
      </div>

      <!-- Section: Line Items -->
      <div class="form-section">
        <div class="form-section-header open" data-section="items">
          Line Items <span class="chevron">▼</span>
        </div>
        <div class="form-section-body open" id="sec-items">
          <table class="items-form-table">
            <thead>
              <tr>
                <th>Name / Description</th>
                <th class="th-ref">Ref.</th>
                <th class="th-price">Unit Price</th>
                <th class="th-qty">Qty</th>
                <th class="th-del"></th>
              </tr>
            </thead>
            <tbody id="items-tbody"></tbody>
          </table>
          <button class="btn btn-ghost btn-sm btn-add-row" id="btn-add-item">+ Add line</button>
        </div>
      </div>

      <!-- Section: Totals -->
      <div class="form-section">
        <div class="form-section-header" data-section="totals">
          Totals &amp; VAT <span class="chevron">▼</span>
        </div>
        <div class="form-section-body" id="sec-totals">
          <div class="field-row col2">
            <div class="field"><label>VAT Rate (%)</label><input id="f-vat-rate" type="number" min="0" max="100" step="0.1" value="0"></div>
            <div class="field"><label>Balance Label</label><input id="f-balance-label" type="text" placeholder="TOTAL DUE"></div>
          </div>
          <label class="field-check">
            <input type="checkbox" id="f-show-paid"> Show "Amount Paid" row
          </label>
          <div class="field"><label>Amount Paid</label><input id="f-amount-paid" type="number" min="0" step="0.01" value="0"></div>
        </div>
      </div>

      <!-- Section: Text Blocks -->
      <div class="form-section">
        <div class="form-section-header" data-section="text">
          Notes &amp; Terms <span class="chevron">▼</span>
        </div>
        <div class="form-section-body" id="sec-text">
          <div class="field"><label>VAT Mention (italic line)</label><textarea id="f-vat-mention" rows="2" placeholder="VAT not applicable - export outside the EU..."></textarea></div>
          <div class="field"><label>Notes</label><textarea id="f-notes" rows="3" placeholder="Free text notes block..."></textarea></div>
          <div class="field"><label>Terms &amp; Conditions</label><textarea id="f-terms" rows="4" placeholder="Payment terms..."></textarea></div>
          <div class="field"><label>Footer — Thanks</label><input id="f-footer-thanks" type="text" placeholder="Thank you for your business!"></div>
          <div class="field"><label>Footer — Contact</label><textarea id="f-footer-contact" rows="2" placeholder="Contact line..."></textarea></div>
        </div>
      </div>

      <!-- Section: Bank Details -->
      <div class="form-section">
        <div class="form-section-header" data-section="bank">
          Bank Details <span class="chevron">▼</span>
        </div>
        <div class="form-section-body" id="sec-bank">
          <div class="field">
            <label>Preset</label>
            <select id="f-bank-preset">
              <option value="eur">EUR — Banque Populaire, France</option>
              <option value="usd">USD — International Wire</option>
              <option value="custom">Custom…</option>
            </select>
          </div>
          <div class="field"><label>Section Label</label><input id="f-bank-label" type="text" placeholder="Bank Details EUR - Banque Populaire, France"></div>
          <div class="field"><label>Beneficiary</label><input id="f-bank-bene" type="text" placeholder="SAS NOVOCIB"></div>
          <div class="field"><label>Bank Name</label><input id="f-bank-name" type="text" placeholder="BANQUE POPULAIRE..."></div>
          <div class="field"><label>Bank Address</label><input id="f-bank-addr" type="text" placeholder="215 Avenue Jean Jaurès, 69007 Lyon, France"></div>
          <div class="field"><label>IBAN</label><input id="f-bank-iban" type="text" placeholder="FR76 1680 7004 ..."></div>
          <div class="field"><label>BIC / SWIFT</label><input id="f-bank-bic" type="text" placeholder="CCBPFRPPGRE"></div>
        </div>
      </div>

      <!-- Section: Issuer -->
      <div class="form-section">
        <div class="form-section-header" data-section="issuer">
          Issuer (Your Company) <span class="chevron">▼</span>
        </div>
        <div class="form-section-body" id="sec-issuer">
          <div class="field"><label>Company Name</label><input id="f-issuer-name" type="text" placeholder="NOVOCIB SAS"></div>
          <div class="field"><label>Address</label><input id="f-issuer-addr" type="text" placeholder="BD de Chatillon, Quai Jean Voisin"></div>
          <div class="field"><label>City / Country</label><input id="f-issuer-city" type="text" placeholder="62200 Boulogne-sur-Mer — France"></div>
          <div class="field"><label>Email</label><input id="f-issuer-email" type="email" placeholder="contact@company.com"></div>
          <div class="field"><label>Legal Info</label><textarea id="f-issuer-legal" rows="3" placeholder="SAS, société par actions simplifiée..."></textarea></div>
        </div>
      </div>

    </div><!-- /form-panel-scroll -->

    <!-- Auto-save status bar -->
    <div class="autosave-status">
      <div class="autosave-dot" id="autosave-dot"></div>
      <span id="autosave-label">Not saved yet</span>
    </div>
  </aside>

  <!-- RIGHT: live preview -->
  <main class="preview-panel" id="preview-panel">
    <div id="preview-doc">
      <!-- Rendered by JS via templates/document.php logic mirrored in renderPreview() -->
    </div>
  </main>

</div><!-- /app-body -->

<!-- Toast container -->
<div class="toast-container" id="toast-container"></div>

<script>
// ═══════════════════════════════════════════════════════════
// EDITOR.JS — inline for simplicity
// ═══════════════════════════════════════════════════════════

const EDIT_ID   = <?= $editId ? (int)$editId : 'null' ?>;
const NEW_TYPE  = <?= json_encode($newType) ?>;
const LOGO_DATA_URI = <?= json_encode($logoDataUri) ?>;

let currentId   = EDIT_ID;
let docData     = null;
let saveTimer   = null;
let isDirty     = false;

// ── Currency symbols map ──
const CURRENCY_SYMBOLS = { EUR: '€', USD: '$', GBP: '£', CHF: 'CHF ' };

// ── Bank presets ──────────────────────────────────────────
const BANK_PRESETS = {
  eur: {
    label:        'Bank Details EUR - Banque Populaire, France',
    beneficiary:  'SAS NOVOCIB',
    bank_name:    'BANQUE POPULAIRE AUVERGNE RHÔNE ALPES (BPAURA)',
    bank_address: '215 Avenue Jean Jaurès, 69007 Lyon, France',
    iban:         'FR76 1680 7004 0081 0876 0421 151',
    bic:          'CCBPFRPPGRE',
  },
  usd: {
    label:        'Bank Details International Wire (USD)',
    beneficiary:  'SAS NOVOCIB-CAV USD',
    bank_name:    'BANQUE POPULAIRE AUVERGNE RHÔNE ALPES (BPAURA)',
    bank_address: '215 Avenue Jean Jaurès, 69007 Lyon, France',
    iban:         'FR76 1680 7004 0081 3911 3449 109',
    bic:          'CCBPFRPPGRE',
  },
};

function detectBankPreset(bank) {
  if (!bank) return 'eur';
  for (const [key, p] of Object.entries(BANK_PRESETS)) {
    if (p.iban === (bank.iban || '').trim()) return key;
  }
  return 'custom';
}

document.getElementById('f-bank-preset').addEventListener('change', function () {
  const preset = BANK_PRESETS[this.value];
  if (!preset) return; // custom — leave fields as-is
  document.getElementById('f-bank-label').value = preset.label;
  document.getElementById('f-bank-bene').value  = preset.beneficiary;
  document.getElementById('f-bank-name').value  = preset.bank_name;
  document.getElementById('f-bank-addr').value  = preset.bank_address;
  document.getElementById('f-bank-iban').value  = preset.iban;
  document.getElementById('f-bank-bic').value   = preset.bic;
  onFormChange();
});

// ── Collapse sections ──────────────────────────────────────
document.querySelectorAll('.form-section-header').forEach(hdr => {
  hdr.addEventListener('click', () => {
    const sec = hdr.dataset.section;
    const body = document.getElementById('sec-' + sec);
    const isOpen = body.classList.contains('open');
    body.classList.toggle('open', !isOpen);
    body.style.display = isOpen ? 'none' : 'block';
    hdr.classList.toggle('open', !isOpen);
  });
  // Init display
  const sec = hdr.dataset.section;
  const body = document.getElementById('sec-' + sec);
  if (body && !body.classList.contains('open')) body.style.display = 'none';
});

// ── Read all form fields → JSON ────────────────────────────
function collectDoc() {
  const rawType = v('f-type');
  const resolvedType = rawType === 'CUSTOM'
    ? (v('f-type-custom').trim().toUpperCase() || 'CUSTOM')
    : rawType;
  return {
    type:          resolvedType,
    number:        v('f-number'),
    date:          v('f-date'),
    due_date:      v('f-due-date'),
    service_date:  v('f-service-date'),
    quote_ref:     v('f-quote-ref'),
    tracking:      v('f-tracking'),
    currency:      v('f-currency'),
    currency_symbol: v('f-currency-symbol'),
    issuer: {
      name:        v('f-issuer-name'),
      address:     v('f-issuer-addr'),
      city:        v('f-issuer-city'),
      email:       v('f-issuer-email'),
      legal:       v('f-issuer-legal'),
      logo_base64: LOGO_DATA_URI,
    },
    customer: {
      name:    v('f-cust-name'),
      address: v('f-cust-addr'),
      city:    v('f-cust-city'),
      contact: v('f-cust-contact'),
      phone:   v('f-cust-phone'),
      vat:     v('f-cust-vat'),
    },
    items: collectItems(),
    vat_rate:         parseFloat(v('f-vat-rate')) || 0,
    amount_paid:      parseFloat(v('f-amount-paid')) || 0,
    show_amount_paid: document.getElementById('f-show-paid').checked,
    balance_label:    v('f-balance-label') || 'TOTAL DUE',
    vat_mention:      v('f-vat-mention'),
    notes:            v('f-notes'),
    terms:            v('f-terms'),
    bank: {
      label:        v('f-bank-label'),
      beneficiary:  v('f-bank-bene'),
      bank_name:    v('f-bank-name'),
      bank_address: v('f-bank-addr'),
      iban:         v('f-bank-iban'),
      bic:          v('f-bank-bic'),
    },
    footer_thanks:  v('f-footer-thanks'),
    footer_contact: v('f-footer-contact'),
  };
}

function v(id) {
  const el = document.getElementById(id);
  return el ? el.value.trim() : '';
}

// ── Items ──────────────────────────────────────────────────
function collectItems() {
  const rows = document.querySelectorAll('#items-tbody tr.item-row');
  return Array.from(rows).map(row => ({
    name:        row.querySelector('.item-name').value,
    description: row.querySelector('.item-desc').value,
    reference:   row.querySelector('.item-ref').value,
    unit_price:  parseFloat(row.querySelector('.item-price').value) || 0,
    qty:         parseFloat(row.querySelector('.item-qty').value) || 0,
    is_free:     row.querySelector('.item-free').checked,
  }));
}

function addItemRow(item = {}) {
  const tbody = document.getElementById('items-tbody');
  const tr = document.createElement('tr');
  tr.className = 'item-row';
  const price = item.unit_price ?? '';
  const qty   = item.qty ?? 1;
  const amt   = (!item.is_free && price !== '' && qty !== '') ? (parseFloat(price) * parseFloat(qty)).toFixed(2) : '—';
  tr.innerHTML = `
    <td style="min-width:130px">
      <input class="item-name" type="text" placeholder="Product / service name" value="${esc(item.name ?? '')}">
      <textarea class="item-desc-input item-desc" placeholder="Sub-description (optional)">${esc(item.description ?? '')}</textarea>
    </td>
    <td><input class="item-ref" type="text" placeholder="REF" value="${esc(item.reference ?? '')}"></td>
    <td><input class="item-price" type="number" min="0" step="0.01" placeholder="0.00" value="${esc(String(item.unit_price ?? ''))}"></td>
    <td><input class="item-qty" type="number" min="0" step="1" value="${esc(String(item.qty ?? 1))}"></td>
    <td class="td-del"><button class="btn-del-row" title="Remove">✕</button></td>
  `;
  // Free toggle row
  const trFree = document.createElement('tr');
  trFree.className = 'item-row-opts';
  trFree.innerHTML = `
    <td colspan="4" style="border-top:none;padding:2px 6px 6px">
      <label style="display:flex;align-items:center;gap:6px;font-size:11px;color:#5a6070;cursor:pointer">
        <input class="item-free" type="checkbox" ${item.is_free ? 'checked' : ''}> Free / "offert"
      </label>
    </td>
    <td style="border-top:none"></td>
  `;
  tbody.appendChild(tr);
  tbody.appendChild(trFree);

  // Delete
  tr.querySelector('.btn-del-row').addEventListener('click', () => {
    tr.remove(); trFree.remove(); onFormChange();
  });
  // Live recalc
  tr.querySelectorAll('input, textarea').forEach(el => el.addEventListener('input', onFormChange));
  trFree.querySelector('input').addEventListener('change', onFormChange);
}

document.getElementById('btn-add-item').addEventListener('click', () => {
  addItemRow();
  onFormChange();
});

// ── Populate form from JSON ────────────────────────────────
function populateForm(d) {
  const knownTypes = ['INVOICE', 'QUOTE', 'CREDIT NOTE', 'OTHER'];
  const docType = (d.type ?? 'INVOICE').toUpperCase();
  if (knownTypes.includes(docType)) {
    set('f-type', docType);
    document.getElementById('f-type-custom-wrap').style.display = 'none';
    document.getElementById('f-type-custom').value = '';
  } else {
    set('f-type', 'CUSTOM');
    document.getElementById('f-type-custom').value = docType;
    document.getElementById('f-type-custom-wrap').style.display = 'block';
  }
  set('f-number', d.number ?? '');
  set('f-date', d.date ?? '');
  set('f-due-date', d.due_date ?? '');
  set('f-service-date', d.service_date ?? '');
  set('f-quote-ref', d.quote_ref ?? '');
  set('f-tracking', d.tracking ?? '');
  set('f-currency', d.currency ?? 'EUR');
  set('f-currency-symbol', d.currency_symbol ?? '€');

  const iss = d.issuer ?? {};
  set('f-issuer-name', iss.name ?? '');
  set('f-issuer-addr', iss.address ?? '');
  set('f-issuer-city', iss.city ?? '');
  set('f-issuer-email', iss.email ?? '');
  set('f-issuer-legal', iss.legal ?? '');

  const cust = d.customer ?? {};
  set('f-cust-name', cust.name ?? '');
  set('f-cust-addr', cust.address ?? '');
  set('f-cust-city', cust.city ?? '');
  set('f-cust-contact', cust.contact ?? '');
  set('f-cust-phone', cust.phone ?? '');
  set('f-cust-vat', cust.vat ?? '');

  set('f-vat-rate', d.vat_rate ?? 0);
  set('f-amount-paid', d.amount_paid ?? 0);
  set('f-balance-label', d.balance_label ?? 'TOTAL DUE');
  document.getElementById('f-show-paid').checked = !!d.show_amount_paid;

  set('f-vat-mention', d.vat_mention ?? '');
  set('f-notes', d.notes ?? '');
  set('f-terms', d.terms ?? '');
  set('f-footer-thanks', d.footer_thanks ?? '');
  set('f-footer-contact', d.footer_contact ?? '');

  const b = d.bank ?? {};
  set('f-bank-label', b.label ?? '');
  set('f-bank-bene', b.beneficiary ?? '');
  set('f-bank-name', b.bank_name ?? '');
  set('f-bank-addr', b.bank_address ?? '');
  set('f-bank-iban', b.iban ?? '');
  set('f-bank-bic', b.bic ?? '');
  document.getElementById('f-bank-preset').value = detectBankPreset(b);

  // Items
  document.getElementById('items-tbody').innerHTML = '';
  (d.items ?? []).forEach(addItemRow);

  updateTopBar(d);
}

function set(id, val) {
  const el = document.getElementById(id);
  if (el) el.value = val;
}

// ── Currency auto-fill ──────────────────────────────────────
document.getElementById('f-type').addEventListener('change', function () {
  const isCustom = this.value === 'CUSTOM';
  document.getElementById('f-type-custom-wrap').style.display = isCustom ? 'block' : 'none';
  if (isCustom) {
    document.getElementById('f-type-custom').focus();
  }
  onFormChange();
});

document.getElementById('f-type-custom').addEventListener('input', onFormChange);

document.getElementById('f-currency').addEventListener('change', function() {
  const sym = CURRENCY_SYMBOLS[this.value] || this.value;
  document.getElementById('f-currency-symbol').value = sym;
  onFormChange();
});

// ── Live preview ───────────────────────────────────────────
function renderPreview(d) {
  const sym = esc(d.currency_symbol || '€');
  const type = esc(d.type || 'INVOICE');

  // ── Extra info rows ──
  let extraRows = '';
  if (d.quote_ref)    extraRows += `<tr><td>Quote:</td><td>${esc(d.quote_ref)}</td></tr>`;
  if (d.due_date)     extraRows += `<tr><td>Due Date:</td><td>${esc(d.due_date)}</td></tr>`;
  if (d.service_date) extraRows += `<tr><td>Service Date:</td><td>${esc(d.service_date)}</td></tr>`;
  if (d.tracking)     extraRows += `<tr><td>Tracking:</td><td>${esc(d.tracking)}</td></tr>`;

  // ── Totals calc ──
  let subtotal = 0;
  (d.items || []).forEach(it => {
    if (!it.is_free) subtotal += (parseFloat(it.unit_price) || 0) * (parseFloat(it.qty) || 0);
  });
  const vatRate   = parseFloat(d.vat_rate) || 0;
  const vatAmt    = subtotal * vatRate / 100;
  const total     = subtotal + vatAmt;
  const amtPaid   = parseFloat(d.amount_paid) || 0;
  const balance   = total - amtPaid;

  // ── Items rows ──
  let itemRows = '';
  (d.items || []).forEach(it => {
    const isFree = !!it.is_free;
    const unitDisp = isFree ? 'offert' : (sym + ' ' + fmt(parseFloat(it.unit_price) || 0));
    const amtDisp  = isFree ? 'offert' : (sym + ' ' + fmt((parseFloat(it.unit_price)||0) * (parseFloat(it.qty)||0)));
    itemRows += `
      <tr>
        <td>${esc(it.name || '')}${it.description ? `<div class="item-desc">${escNl(it.description)}</div>` : ''}</td>
        <td class="ref">${esc(it.reference || '')}</td>
        <td class="price center">${unitDisp}</td>
        <td class="qty center">${esc(String(it.qty ?? ''))}</td>
        <td class="amount right">${amtDisp}</td>
      </tr>`;
  });

  // ── Totals rows ──
  let totalRows = `
    <tr><td>Subtotal (excl. VAT)</td><td class="right">${sym} ${fmt(subtotal)}</td></tr>
    <tr><td>VAT${vatRate > 0 ? ` (${vatRate}%)` : ''}</td><td class="right">${sym} ${fmt(vatAmt)}</td></tr>`;
  if (d.show_amount_paid && amtPaid > 0) {
    totalRows += `<tr><td>Amount Paid</td><td class="right">${sym} ${fmt(amtPaid)}</td></tr>`;
  }
  totalRows += `<tr class="grand-total"><td>${esc(d.balance_label || 'TOTAL DUE')} (${esc(d.currency || 'EUR')})</td><td class="right">${sym} ${fmt(balance)}</td></tr>`;

  // ── Customer contact ──
  const cust = d.customer || {};
  let contactLine = '';
  const parts = [];
  if (cust.contact) parts.push(cust.contact);
  if (cust.phone)   parts.push('TEL: ' + cust.phone);
  if (cust.vat)     parts.push('VAT: ' + cust.vat);
  if (parts.length) contactLine = `<div class="customer-contact">${esc(parts.join(' — '))}</div>`;

  // ── Bank ──
  const bank = d.bank || {};
  let bankRows = '';
  const bankFields = [
    ['beneficiary', 'Beneficiary'],
    ['bank_name', 'Bank name'],
    ['bank_address', 'Bank address'],
    ['iban', 'IBAN'],
    ['bic', 'BIC / SWIFT'],
  ];
  bankFields.forEach(([key, label]) => {
    if (!bank[key]) return;
    const val = key === 'iban' ? `<strong>${esc(bank[key])}</strong>` : esc(bank[key]);
    bankRows += `<tr><td class="bank-label">${label}:</td><td>${val}</td></tr>`;
  });

  // ── Issuer ──
  const iss = d.issuer || {};
  const logoHtml = LOGO_DATA_URI ? `<img class=\"logo\" src=\"${LOGO_DATA_URI}\" alt=\"Logo\">` : '';

  document.getElementById('preview-doc').innerHTML = `
<div class="page">
  <header class="doc-header">
    <div class="company">
      ${logoHtml}
      <h2>${esc(iss.name || '')}</h2>
      ${iss.address ? `<p>${esc(iss.address)}</p>` : ''}
      ${iss.city    ? `<p>${esc(iss.city)}</p>` : ''}
      ${iss.email   ? `<p>${esc(iss.email)}</p>` : ''}
      ${iss.legal   ? `<div class="company-legal">${escNl(iss.legal)}</div>` : ''}
    </div>
    <div class="invoice-title">
      <h1>${type}</h1>
      <table class="invoice-info">
        <tbody>
          <tr><th>No.</th><th>Date</th></tr>
          <tr><td>${esc(d.number || '')}</td><td>${esc(d.date || '')}</td></tr>
        </tbody>
      </table>
      ${extraRows ? `<table class="invoice-info extra-info"><tbody>${extraRows}</tbody></table>` : ''}
    </div>
  </header>

  <div class="content">
    <section class="bill-to">
      <div class="section-title">Bill To</div>
      <div class="customer">
        <strong>${esc(cust.name || '')}</strong><br>
        ${cust.address ? esc(cust.address) + '<br>' : ''}
        ${cust.city    ? esc(cust.city) : ''}
        ${contactLine}
      </div>
    </section>

    <table class="items">
      <thead>
        <tr>
          <th>Name</th>
          <th class="ref">Ref.</th>
          <th class="price">Unit Price (${sym})</th>
          <th class="qty">Qty</th>
          <th class="amount">Amount (${sym})</th>
        </tr>
      </thead>
      <tbody>${itemRows || '<tr><td colspan="5" style="color:#aaa;text-align:center;padding:14px">No items yet</td></tr>'}</tbody>
    </table>

    <div class="totals">
      <table><tbody>${totalRows}</tbody></table>
    </div>

    <div class="spacer"></div>

    ${d.vat_mention ? `<div class="vat-mention">${escNl(d.vat_mention)}</div>` : ''}
    ${d.notes       ? `<div class="notes-block"><strong>Notes</strong>${escNl(d.notes)}</div>` : ''}

    ${bankRows ? `
    <div class="bank-details">
      <strong>${esc(bank.label || 'Bank Details')}</strong>
      <table class="bank-info"><tbody>${bankRows}</tbody></table>
    </div>` : ''}

    ${d.terms ? `
    <div class="terms-block">
      <strong>Terms &amp; Conditions</strong>
      <div>${escNl(d.terms)}</div>
    </div>` : ''}
  </div>

  <footer class="doc-footer">
    ${d.footer_thanks  ? `<div class="thanks">${esc(d.footer_thanks)}</div>` : ''}
    ${d.footer_contact ? `<div class="contact">${escNl(d.footer_contact)}</div>` : ''}
  </footer>
</div>`;
}

function fmt(n) {
  return Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).replace(',', ' ').replace('.', '.');
}
function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escNl(s) {
  return esc(s).replace(/\n/g, '<br>');
}

// ── On any form change ─────────────────────────────────────
function onFormChange() {
  const d = collectDoc();
  renderPreview(d);
  updateTopBar(d);
  isDirty = true;
  scheduleAutoSave();
}

function updateTopBar(d) {
  document.getElementById('topbar-docnum').textContent = d.number ? `— ${d.number}` : '';
  const badge = document.getElementById('topbar-type-badge');
  badge.textContent = d.type || 'INVOICE';
  badge.className = 'topbar-doc-type';
}

// ── Wire all form inputs ────────────────────────────────────
document.querySelectorAll('#form-scroll input, #form-scroll select, #form-scroll textarea').forEach(el => {
  el.addEventListener('input', onFormChange);
  el.addEventListener('change', onFormChange);
});

// ── Auto-save ──────────────────────────────────────────────
function scheduleAutoSave() {
  clearTimeout(saveTimer);
  saveTimer = setTimeout(saveDocument, 8000); // 8s debounce
  setAutosaveStatus('saving', 'Unsaved changes…');
}

async function saveDocument(forceSave = false) {
  if (!isDirty && !forceSave) return;
  const d = collectDoc();
  setAutosaveStatus('saving', 'Saving…');
  try {
    let url, body;
    if (currentId) {
      url  = `api.php?action=update&id=${currentId}`;
      body = JSON.stringify({ data: d, status: 'draft' });
    } else {
      url  = `api.php?action=save`;
      body = JSON.stringify({ data: d });
    }
    const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body });
    const json = await res.json();
    if (!json.ok) throw new Error(json.error || 'Save failed');
    if (!currentId && json.id) {
      currentId = json.id;
      // Update URL without reload
      history.replaceState(null, '', `editor.php?id=${currentId}`);
    }
    isDirty = false;
    setAutosaveStatus('saved', 'Saved ' + new Date().toLocaleTimeString());
  } catch (e) {
    setAutosaveStatus('error', 'Save failed: ' + e.message);
    toast('Save failed: ' + e.message, 'error');
  }
}

function setAutosaveStatus(state, text) {
  const dot   = document.getElementById('autosave-dot');
  const label = document.getElementById('autosave-label');
  dot.className = 'autosave-dot ' + state;
  label.textContent = text;
}

// ── Manual save button ──────────────────────────────────────
document.getElementById('btn-save').addEventListener('click', async () => {
  isDirty = true;
  await saveDocument(true);
  toast('Document saved', 'success');
});

// ── Print ───────────────────────────────────────────────────
document.getElementById('btn-print').addEventListener('click', async () => {
  if (isDirty) {
    isDirty = true;
    await saveDocument(true);
  }
  if (!currentId) { toast('Please save first', 'error'); return; }
  window.open(`preview.php?id=${currentId}`, '_blank');
});

// ── Export JSON ─────────────────────────────────────────────
document.getElementById('btn-export').addEventListener('click', () => {
  const d    = collectDoc();
  const name = [d.type, d.number].filter(Boolean).join('_').replace(/\s+/g, '-') || 'document';
  const blob = new Blob([JSON.stringify(d, null, 2)], { type: 'application/json' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = name + '.json';
  a.click();
  URL.revokeObjectURL(url);
  toast('JSON exported', 'success');
});

// ── Import JSON ─────────────────────────────────────────────
document.getElementById('btn-import').addEventListener('change', function () {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    try {
      const d = JSON.parse(e.target.result);
      if (typeof d !== 'object' || d === null) throw new Error('Invalid JSON structure');
      populateForm(d);
      renderPreview(d);
      isDirty = true;
      scheduleAutoSave();
      toast('JSON imported — review and save', 'success');
    } catch (err) {
      toast('Import failed: ' + err.message, 'error');
    }
    // Reset input so the same file can be re-imported if needed
    this.value = '';
  };
  reader.readAsText(file);
});

// ── Toast ───────────────────────────────────────────────────
function toast(msg, type = '') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = 'toast' + (type ? ' toast-' + type : '');
  t.textContent = msg;
  c.appendChild(t);
  requestAnimationFrame(() => { requestAnimationFrame(() => t.classList.add('show')); });
  setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 3000);
}

// ── Init ────────────────────────────────────────────────────
async function init() {
  if (EDIT_ID) {
    try {
      const res  = await fetch(`api.php?action=get&id=${EDIT_ID}`);
      const json = await res.json();
      if (!json.ok) throw new Error(json.error);
      docData = json.document.data;
      populateForm(docData);
      renderPreview(docData);
      setAutosaveStatus('saved', 'Loaded from database');
    } catch (e) {
      toast('Failed to load document: ' + e.message, 'error');
    }
  } else {
    // New document — load defaults
    try {
      const res  = await fetch(`api.php?action=default&type=${encodeURIComponent(NEW_TYPE)}`);
      const json = await res.json();
      docData = json.data;
    } catch {
      docData = {};
    }
    docData.type = NEW_TYPE;
    populateForm(docData);
    renderPreview(docData);
    setAutosaveStatus('', 'New document — not saved yet');
  }
}

init();
</script>

</body>
</html>