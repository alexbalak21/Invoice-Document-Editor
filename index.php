<?php
/**
 * index.php — Document history & management
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Documents — Invoice Editor</title>
  <link rel="stylesheet" href="assets/editor.css">
  <style>
    html, body { height: auto; overflow: auto; background: #f0f2f5; }
  </style>
</head>
<body>

<!-- Top bar -->
<header class="topbar">
  <div class="topbar-brand">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect x="3" y="2" width="14" height="16" rx="2" fill="rgba(255,255,255,0.25)" stroke="white" stroke-width="1.5"/>
      <line x1="6" y1="7" x2="14" y2="7" stroke="white" stroke-width="1.2"/>
      <line x1="6" y1="10" x2="14" y2="10" stroke="white" stroke-width="1.2"/>
      <line x1="6" y1="13" x2="11" y2="13" stroke="white" stroke-width="1.2"/>
    </svg>
    DocEditor
  </div>
  <div style="display:flex;gap:8px;margin-left:auto">
    <a href="customers.php" class="btn btn-ghost btn-sm">👥 Customers</a>
    <a href="documentation.php" class="btn btn-ghost btn-sm">📖 Docs</a>
    <button class="btn btn-ghost btn-sm" onclick="openNew('QUOTE')">+ Quote</button>
    <button class="btn btn-primary btn-sm" onclick="openNew('INVOICE')">+ Invoice</button>
  </div>
</header>

<!-- History layout -->
<div class="history-layout">
  <div class="history-topbar">
    <h1>Documents</h1>
    <div class="search-bar">
      <input type="text" id="search-input" placeholder="Search number or client…" oninput="loadDocs()">
      <select id="filter-type" onchange="loadDocs()">
        <option value="">All types</option>
        <option value="INVOICE">Invoice</option>
        <option value="QUOTE">Quote</option>
        <option value="CREDIT NOTE">Credit Note</option>
        <option value="OTHER">Other</option>
      </select>
    </div>
  </div>

  <div id="doc-list">
    <div class="empty-state">
      <div class="empty-icon">📄</div>
      <h2>Loading…</h2>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast-container" id="toast-container"></div>

<!-- Confirm modal -->
<div id="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center">
  <div style="background:white;border-radius:10px;padding:28px 32px;max-width:380px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.18)">
    <h3 style="margin-bottom:10px;font-size:17px;color:#1a1d23" id="modal-title">Confirm</h3>
    <p style="color:#5a6070;font-size:14px;margin-bottom:22px" id="modal-body">Are you sure?</p>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button class="btn btn-ghost btn-sm" onclick="closeModal()" style="color:#1a1d23;background:#eee;border:none">Cancel</button>
      <button class="btn btn-danger btn-sm" id="modal-confirm">Delete</button>
    </div>
  </div>
</div>

<script>
const TYPE_CLASSES = {
  'INVOICE':     'type-invoice',
  'QUOTE':       'type-quote',
  'CREDIT NOTE': 'type-credit',
  'OTHER':       'type-other',
};
const STATUS_CLASSES = {
  'draft': 'badge-draft',
  'final': 'badge-final',
  'paid':  'badge-paid',
};

function openNew(type) {
  window.location.href = `editor.php?type=${encodeURIComponent(type)}`;
}

async function loadDocs() {
  const q    = document.getElementById('search-input').value.trim();
  const type = document.getElementById('filter-type').value;
  const url  = `api.php?action=list${q ? '&q=' + encodeURIComponent(q) : ''}${type ? '&type=' + encodeURIComponent(type) : ''}`;
  try {
    const res  = await fetch(url);
    const json = await res.json();
    if (!json.ok) throw new Error(json.error);
    renderList(json.documents);
  } catch (e) {
    document.getElementById('doc-list').innerHTML = `
      <div class="empty-state"><div class="empty-icon">⚠️</div><h2>Error</h2><p>${e.message}</p></div>`;
  }
}

function renderList(docs) {
  const el = document.getElementById('doc-list');
  if (!docs.length) {
    el.innerHTML = `
      <div class="empty-state">
        <div class="empty-icon">📄</div>
        <h2>No documents yet</h2>
        <p>Create your first invoice or quote above.</p>
      </div>`;
    return;
  }

  let rows = '';
  docs.forEach(doc => {
    const typeClass   = TYPE_CLASSES[doc.type] || 'type-other';
    const statusClass = STATUS_CLASSES[doc.status] || 'badge-draft';
    const updated     = doc.updated_at ? doc.updated_at.slice(0, 16).replace('T', ' ') : '—';
    rows += `
      <tr>
        <td><span class="type-badge ${typeClass}">${esc(doc.type)}</span></td>
        <td style="font-weight:600">${esc(doc.number || '—')}</td>
        <td>${esc(doc.customer || '—')}</td>
        <td>${esc(doc.date || '—')}</td>
        <td><span class="badge ${statusClass}">${esc(doc.status)}</span></td>
        <td style="color:#9098a8;font-size:12px">${esc(updated)}</td>
        <td>
          <div class="action-btns">
            <a href="editor.php?id=${doc.id}" class="btn btn-ghost btn-sm" style="color:#1a1d23;background:#eee;border:none">Edit</a>
            <a href="preview.php?id=${doc.id}" target="_blank" class="btn btn-ghost btn-sm" style="color:#1a1d23;background:#eee;border:none">Print</a>
            <button class="btn btn-ghost btn-sm" style="color:#1a1d23;background:#eee;border:none" onclick="duplicateDoc(${doc.id})">Copy</button>
            <button class="btn btn-danger btn-sm" onclick="confirmDelete(${doc.id}, '${esc(doc.number || 'this document')}')">Del</button>
          </div>
        </td>
      </tr>`;
  });

  el.innerHTML = `
    <table class="doc-table">
      <thead>
        <tr>
          <th>Type</th>
          <th>Number</th>
          <th>Client</th>
          <th>Date</th>
          <th>Status</th>
          <th>Last updated</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>`;
}

async function duplicateDoc(id) {
  try {
    const res  = await fetch(`api.php?action=duplicate&id=${id}`, { method: 'POST' });
    const json = await res.json();
    if (!json.ok) throw new Error(json.error);
    toast('Document duplicated', 'success');
    loadDocs();
  } catch (e) {
    toast('Error: ' + e.message, 'error');
  }
}

// ── Delete with confirm ──
let pendingDeleteId = null;

function confirmDelete(id, label) {
  pendingDeleteId = id;
  document.getElementById('modal-body').textContent = `Delete "${label}"? This cannot be undone.`;
  const overlay = document.getElementById('modal-overlay');
  overlay.style.display = 'flex';
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
    const res  = await fetch(`api.php?action=delete&id=${idToDelete}`, { method: 'POST' });
    const json = await res.json();
    if (!json.ok) throw new Error(json.error);
    toast('Document deleted', 'success');
    loadDocs();
  } catch (e) {
    toast('Error: ' + e.message, 'error');
  }
}

// ── Toast ──
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
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Close modal on overlay click
document.getElementById('modal-overlay').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

// ── Init ──
loadDocs();
</script>
</body>
</html>