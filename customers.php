<?php
/**
 * customers.php — Customer address book management
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Customers — Invoice Editor</title>
  <link rel="stylesheet" href="assets/editor.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <style>
    html, body { height: auto; overflow: auto; background: #f0f2f5; }
  </style>
</head>
<body>

<header class="topbar">
  <a href="index.php" class="topbar-brand">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect x="3" y="2" width="14" height="16" rx="2" fill="rgba(255,255,255,0.25)" stroke="white" stroke-width="1.5"/>
      <line x1="6" y1="7" x2="14" y2="7" stroke="white" stroke-width="1.2"/>
      <line x1="6" y1="10" x2="14" y2="10" stroke="white" stroke-width="1.2"/>
      <line x1="6" y1="13" x2="11" y2="13" stroke="white" stroke-width="1.2"/>
    </svg>
    DocEditor
  </a>
  <div class="topbar-divider"></div>
  <nav class="topbar-nav">
    <a href="index.php"><i class="fa-solid fa-file-lines"></i> Documents</a>
    <a href="customers.php" class="active"><i class="fa-solid fa-users"></i> Customers</a>
    <a href="documentation.php"><i class="fa-solid fa-book-open"></i> Docs</a>
  </nav>
  <div class="topbar-actions">
    <button class="btn btn-ghost btn-sm" id="btn-export" title="Download all customers as JSON"><i class="fa-solid fa-download"></i> Export</button>
    <label class="btn btn-ghost btn-sm" title="Import customers from a JSON file" style="cursor:pointer"><i class="fa-solid fa-upload"></i> Import<input type="file" id="btn-import" accept=".json,application/json" style="display:none"></label>
    <button class="btn btn-primary btn-sm" onclick="openForm()"><i class="fa-solid fa-plus"></i> New Customer</button>
  </div>
</header>

<div class="history-layout">

  <div class="history-topbar">
    <h1>Customers</h1>
    <div class="search-bar">
      <input type="text" id="search-input" placeholder="Search name, city, contact…" oninput="loadCustomers()">
    </div>
  </div>

  <!-- Add / Edit form -->
  <div class="cust-form-card" id="cust-form-card" style="display:none">
    <h2 id="form-title">New Customer</h2>
    <input type="hidden" id="edit-id" value="">
    <div class="cust-form-grid">
      <div class="field full">
        <label>Company / Customer Name *</label>
        <input id="fc-name" type="text" placeholder="Pureture">
      </div>
      <div class="field">
        <label>Address</label>
        <input id="fc-addr" type="text" placeholder="4F, 2121-3 Nambusunhwan-ro">
      </div>
      <div class="field">
        <label>City / Country</label>
        <input id="fc-city" type="text" placeholder="06725 Seocho-gu, Seoul — Korea">
      </div>
      <div class="field">
        <label>Contact Person</label>
        <input id="fc-contact" type="text" placeholder="Sohee Yoon">
      </div>
      <div class="field">
        <label>Phone</label>
        <input id="fc-phone" type="text" placeholder="+82-10-6681-1162">
      </div>
      <div class="field">
        <label>VAT Number</label>
        <input id="fc-vat" type="text" placeholder="Optional">
      </div>
    </div>
    <div style="display:flex;gap:10px;margin-top:18px">
      <button class="btn btn-primary btn-sm" onclick="saveCustomer()" style="background:var(--ui-primary);color:white">Save Customer</button>
      <button class="btn btn-ghost btn-sm" onclick="closeForm()" style="background:#eee;color:#1a1d23;border:none">Cancel</button>
    </div>
  </div>

  <!-- Customer table -->
  <div id="cust-list">
    <div class="empty-state">
      <div class="empty-icon"><i class="fa-solid fa-users"></i></div>
      <h2>Loading…</h2>
    </div>
  </div>

</div>

<!-- Confirm delete modal -->
<div id="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center">
  <div style="background:white;border-radius:10px;padding:28px 32px;max-width:380px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.18)">
    <h3 style="margin-bottom:10px;font-size:17px;color:#1a1d23">Delete Customer</h3>
    <p style="color:#5a6070;font-size:14px;margin-bottom:22px" id="modal-body">Are you sure?</p>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button class="btn btn-ghost btn-sm" onclick="closeModal()" style="color:#1a1d23;background:#eee;border:none">Cancel</button>
      <button class="btn btn-danger btn-sm" id="modal-confirm">Delete</button>
    </div>
  </div>
</div>

<div class="toast-container" id="toast-container"></div>

<script>

// ── Load & render list ──────────────────────────────────────
async function loadCustomers() {
  const q = document.getElementById('search-input').value.trim();
  try {
    const res  = await fetch(`api.php?action=customers&q=${encodeURIComponent(q)}`);
    const json = await res.json();
    if (!json.ok) throw new Error(json.error);
    renderList(json.customers);
  } catch(e) {
    document.getElementById('cust-list').innerHTML =
      `<div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><h2>Error</h2><p>${esc(e.message)}</p></div>`;
  }
}

function renderList(customers) {
  const el = document.getElementById('cust-list');
  if (!customers.length) {
    el.innerHTML = `
      <div class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-users"></i></div>
        <h2>No customers yet</h2>
        <p>Customers are saved automatically when you create or save a document, or you can add one manually above.</p>
      </div>`;
    return;
  }
  let rows = '';
  customers.forEach(c => {
    const detail = [c.city, c.contact, c.phone].filter(Boolean).map(esc).join(' · ');
    rows += `
      <tr>
        <td style="font-weight:600">${esc(c.name)}</td>
        <td style="color:var(--ui-text-soft)">${esc(c.address || '—')}</td>
        <td style="color:var(--ui-text-soft)">${detail || '—'}</td>
        <td style="color:var(--ui-text-soft)">${esc(c.vat || '—')}</td>
        <td>
          <div class="action-btns">
            <button class="btn btn-ghost btn-sm" style="color:#1a1d23;background:#eee;border:none" onclick="editCustomer(${c.id})">Edit</button>
            <button class="btn btn-danger btn-sm" onclick="confirmDelete(${c.id}, '${esc(c.name)}')">Delete</button>
          </div>
        </td>
      </tr>`;
  });
  el.innerHTML = `
    <table class="doc-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Address</th>
          <th>Contact</th>
          <th>VAT</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>`;
}

// ── Form open/close ─────────────────────────────────────────
function openForm(id = null) {
  document.getElementById('cust-form-card').style.display = 'block';
  document.getElementById('edit-id').value = id ?? '';
  document.getElementById('form-title').textContent = id ? 'Edit Customer' : 'New Customer';
  if (!id) {
    ['name','addr','city','contact','phone','vat'].forEach(f => document.getElementById('fc-'+f).value = '');
  }
  document.getElementById('fc-name').focus();
  document.getElementById('cust-form-card').scrollIntoView({ behavior: 'smooth' });
}

function closeForm() {
  document.getElementById('cust-form-card').style.display = 'none';
  document.getElementById('edit-id').value = '';
}

async function editCustomer(id) {
  try {
    const res  = await fetch(`api.php?action=customer_get&id=${id}`);
    const json = await res.json();
    if (!json.ok) throw new Error(json.error);
    const c = json.customer;
    openForm(id);
    document.getElementById('fc-name').value    = c.name    ?? '';
    document.getElementById('fc-addr').value    = c.address ?? '';
    document.getElementById('fc-city').value    = c.city    ?? '';
    document.getElementById('fc-contact').value = c.contact ?? '';
    document.getElementById('fc-phone').value   = c.phone   ?? '';
    document.getElementById('fc-vat').value     = c.vat     ?? '';
  } catch(e) {
    toast('Error: ' + e.message, 'error');
  }
}

// ── Save ────────────────────────────────────────────────────
async function saveCustomer() {
  const name = document.getElementById('fc-name').value.trim();
  if (!name) { toast('Name is required', 'error'); document.getElementById('fc-name').focus(); return; }
  const id   = document.getElementById('edit-id').value;
  const body = {
    name,
    address: document.getElementById('fc-addr').value.trim(),
    city:    document.getElementById('fc-city').value.trim(),
    contact: document.getElementById('fc-contact').value.trim(),
    phone:   document.getElementById('fc-phone').value.trim(),
    vat:     document.getElementById('fc-vat').value.trim(),
  };
  const url = id
    ? `api.php?action=customer_update&id=${id}`
    : `api.php?action=customer_save`;
  try {
    const res  = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body) });
    const json = await res.json();
    if (!json.ok) throw new Error(json.error);
    toast(id ? 'Customer updated' : 'Customer created', 'success');
    closeForm();
    loadCustomers();
  } catch(e) {
    toast('Error: ' + e.message, 'error');
  }
}

// ── Delete ──────────────────────────────────────────────────
let pendingDeleteId = null;

function confirmDelete(id, name) {
  pendingDeleteId = id;
  document.getElementById('modal-body').textContent = `Delete "${name}"? This cannot be undone.`;
  document.getElementById('modal-overlay').style.display = 'flex';
  document.getElementById('modal-confirm').onclick = doDelete;
}

function closeModal() {
  document.getElementById('modal-overlay').style.display = 'none';
  pendingDeleteId = null;
}

async function doDelete() {
  const idToDelete = pendingDeleteId;
  closeModal();
  if (!idToDelete) return;
  try {
    const res  = await fetch(`api.php?action=customer_delete&id=${idToDelete}`, { method:'POST' });
    const json = await res.json();
    if (!json.ok) throw new Error(json.error);
    toast('Customer deleted', 'success');
    loadCustomers();
  } catch(e) {
    toast('Error: ' + e.message, 'error');
  }
}

document.getElementById('modal-overlay').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

// ── Toast ───────────────────────────────────────────────────
function toast(msg, type = '') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = 'toast' + (type ? ' toast-' + type : '');
  t.textContent = msg;
  c.appendChild(t);
  requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));
  setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 3000);
}

function esc(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Export JSON ─────────────────────────────────────────────
document.getElementById('btn-export').addEventListener('click', async () => {
  try {
    const res  = await fetch('api.php?action=customers');
    const json = await res.json();
    if (!json.ok) throw new Error(json.error);
    // Strip internal db fields before export
    const clean = json.customers.map(({ id, created_at, updated_at, ...c }) => c);
    const blob  = new Blob([JSON.stringify(clean, null, 2)], { type: 'application/json' });
    const url   = URL.createObjectURL(blob);
    const a     = document.createElement('a');
    a.href      = url;
    a.download  = 'customers.json';
    a.click();
    URL.revokeObjectURL(url);
    toast(`Exported ${clean.length} customer(s)`, 'success');
  } catch(e) {
    toast('Export failed: ' + e.message, 'error');
  }
});

// ── Import JSON ─────────────────────────────────────────────
document.getElementById('btn-import').addEventListener('change', function () {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = async (e) => {
    try {
      const data = JSON.parse(e.target.result);
      if (!Array.isArray(data)) throw new Error('Expected a JSON array of customers');

      let saved = 0, failed = 0;
      for (const c of data) {
        if (!c.name || !String(c.name).trim()) { failed++; continue; }
        try {
          const res  = await fetch('api.php?action=customer_save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              name:    String(c.name    ?? '').trim(),
              address: String(c.address ?? '').trim(),
              city:    String(c.city    ?? '').trim(),
              contact: String(c.contact ?? '').trim(),
              phone:   String(c.phone   ?? '').trim(),
              vat:     String(c.vat     ?? '').trim(),
            }),
          });
          const json = await res.json();
          if (json.ok) saved++; else failed++;
        } catch { failed++; }
      }

      const msg = failed
        ? `Imported ${saved} customer(s), ${failed} skipped`
        : `Imported ${saved} customer(s)`;
      toast(msg, failed ? '' : 'success');
      loadCustomers();
    } catch(err) {
      toast('Import failed: ' + err.message, 'error');
    }
    this.value = ''; // reset so same file can be re-imported
  };
  reader.readAsText(file);
});

loadCustomers();
</script>
</body>
</html>