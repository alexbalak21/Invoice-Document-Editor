<?php
/**
 * Renders $doc (array) as the A4 invoice HTML body.
 * Expects $doc to be set in scope.
 */
$d   = $doc;
$sym = htmlspecialchars($d['currency_symbol'] ?? '€', ENT_QUOTES);
$type = htmlspecialchars($d['type'] ?? 'INVOICE', ENT_QUOTES);

// Hardcoded logo from public/assets/logo.png
$logoPath = ROOT . '/public/assets/logo.png';
$logoTag  = '';
if (file_exists($logoPath)) {
    $logoB64 = base64_encode(file_get_contents($logoPath));
    $logoTag = '<img class="logo" src="data:image/png;base64,' . $logoB64 . '" alt="Logo">';
}

function fmtMoney(mixed $val, string $sym): string {
    if ($val === '' || $val === null) return '';
    if (!is_numeric($val)) return htmlspecialchars((string)$val, ENT_QUOTES);
    return $sym . ' ' . number_format((float)$val, 2, '.', ' ');
}

$subtotal = 0;
foreach (($d['items'] ?? []) as $item) {
    if (!empty($item['is_free'])) continue;
    $subtotal += (float)($item['unit_price'] ?? 0) * (float)($item['qty'] ?? 0);
}
$vatRate   = (float)($d['vat_rate'] ?? 0);
$vatAmount = $subtotal * $vatRate / 100;
$total     = $subtotal + $vatAmount;
$amtPaid   = (float)($d['amount_paid'] ?? 0);
$balance   = $total - $amtPaid;

$extraRows = [];
if (!empty($d['quote_ref']))    $extraRows[] = ['Quote:',        htmlspecialchars($d['quote_ref'])];
if (!empty($d['due_date']))     $extraRows[] = ['Due Date:',     htmlspecialchars($d['due_date'])];
if (!empty($d['service_date'])) $extraRows[] = ['Service Date:', htmlspecialchars($d['service_date'])];
if (!empty($d['tracking']))     $extraRows[] = ['Tracking:',     htmlspecialchars($d['tracking'])];
?>
<div class="page">
  <header class="doc-header">
    <div class="company">
      <?= $logoTag ?>
      <h2><?= htmlspecialchars($d['issuer']['name'] ?? '') ?></h2>
      <?php if (!empty($d['issuer']['address'])): ?><p><?= htmlspecialchars($d['issuer']['address']) ?></p><?php endif; ?>
      <?php if (!empty($d['issuer']['city'])):    ?><p><?= htmlspecialchars($d['issuer']['city']) ?></p><?php endif; ?>
      <?php if (!empty($d['issuer']['email'])):   ?><p><?= htmlspecialchars($d['issuer']['email']) ?></p><?php endif; ?>
      <?php if (!empty($d['issuer']['legal'])):   ?><div class="company-legal"><?= nl2br(htmlspecialchars($d['issuer']['legal'])) ?></div><?php endif; ?>
    </div>
    <div class="invoice-title">
      <h1><?= $type ?></h1>
      <table class="invoice-info">
        <tbody><tr><th>No.</th><th>Date</th></tr>
        <tr><td><?= htmlspecialchars($d['number'] ?? '') ?></td><td><?= htmlspecialchars($d['date'] ?? '') ?></td></tr></tbody>
      </table>
      <?php if ($extraRows): ?>
        <table class="invoice-info extra-info"><tbody>
          <?php foreach ($extraRows as [$label, $val]): ?><tr><td><?= $label ?></td><td><?= $val ?></td></tr><?php endforeach; ?>
        </tbody></table>
      <?php endif; ?>
    </div>
  </header>

  <div class="content">
    <section class="bill-to">
      <div class="section-title">Bill To</div>
      <div class="customer">
        <strong><?= htmlspecialchars($d['customer']['name'] ?? '') ?></strong><br>
        <?php if (!empty($d['customer']['address'])): ?><?= htmlspecialchars($d['customer']['address']) ?><br><?php endif; ?>
        <?php if (!empty($d['customer']['city'])):    ?><?= htmlspecialchars($d['customer']['city']) ?><?php endif; ?>
        <?php
          $parts = [];
          if (!empty($d['customer']['contact'])) $parts[] = $d['customer']['contact'];
          if (!empty($d['customer']['phone']))   $parts[] = 'TEL: ' . $d['customer']['phone'];
          if (!empty($d['customer']['vat']))     $parts[] = 'VAT: ' . $d['customer']['vat'];
          if ($parts): ?><div class="customer-contact"><?= htmlspecialchars(implode(' — ', $parts)) ?></div><?php endif; ?>
      </div>
    </section>

    <table class="items">
      <thead><tr><th>Name</th><th class="ref">Ref.</th><th class="price">Unit Price (<?= $sym ?>)</th><th class="qty">Qty</th><th class="amount">Amount (<?= $sym ?>)</th></tr></thead>
      <tbody>
        <?php foreach (($d['items'] ?? []) as $item):
          $isFree   = !empty($item['is_free']);
          $unitDisp = $isFree ? 'offert' : fmtMoney($item['unit_price'] ?? 0, $sym);
          $amtDisp  = $isFree ? 'offert' : fmtMoney(((float)($item['unit_price']??0)) * ((float)($item['qty']??0)), $sym);
        ?>
          <tr>
            <td><?= htmlspecialchars($item['name'] ?? '') ?><?php if (!empty($item['description'])): ?><div class="item-desc"><?= nl2br(htmlspecialchars($item['description'])) ?></div><?php endif; ?></td>
            <td class="ref"><?= htmlspecialchars($item['reference'] ?? '') ?></td>
            <td class="price center"><?= $unitDisp ?></td>
            <td class="qty center"><?= htmlspecialchars((string)($item['qty'] ?? '')) ?></td>
            <td class="amount right"><?= $amtDisp ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="totals"><table><tbody>
      <tr><td>Subtotal (excl. VAT)</td><td class="right"><?= fmtMoney($subtotal, $sym) ?></td></tr>
      <tr><td>VAT<?= $vatRate > 0 ? " ({$vatRate}%)" : '' ?></td><td class="right"><?= fmtMoney($vatAmount, $sym) ?></td></tr>
      <?php if (!empty($d['show_amount_paid']) && $amtPaid > 0): ?>
        <tr><td>Amount Paid</td><td class="right"><?= fmtMoney($amtPaid, $sym) ?></td></tr>
      <?php endif; ?>
      <tr class="grand-total"><td><?= htmlspecialchars($d['balance_label'] ?? 'TOTAL DUE') ?> (<?= htmlspecialchars($d['currency'] ?? 'EUR') ?>)</td><td class="right"><?= fmtMoney($balance, $sym) ?></td></tr>
    </tbody></table></div>

    <div class="spacer"></div>
    <?php if (!empty($d['vat_mention'])): ?><div class="vat-mention"><?= nl2br(htmlspecialchars($d['vat_mention'])) ?></div><?php endif; ?>
    <?php if (!empty($d['notes'])):       ?><div class="notes-block"><strong>Notes</strong><?= nl2br(htmlspecialchars($d['notes'])) ?></div><?php endif; ?>

    <?php $bank = $d['bank'] ?? []; if (!empty($bank['iban'])): ?>
      <div class="bank-details">
        <strong><?= htmlspecialchars($bank['label'] ?? 'Bank Details') ?></strong>
        <table class="bank-info"><tbody>
          <?php foreach (['beneficiary'=>'Beneficiary','bank_name'=>'Bank name','bank_address'=>'Bank address','iban'=>'IBAN','bic'=>'BIC / SWIFT'] as $k=>$l): if(empty($bank[$k]))continue; ?>
            <tr><td class="bank-label"><?= $l ?>:</td><td><?= $k==='iban' ? '<strong>'.htmlspecialchars($bank[$k]).'</strong>' : htmlspecialchars($bank[$k]) ?></td></tr>
          <?php endforeach; ?>
        </tbody></table>
      </div>
    <?php endif; ?>

    <?php if (!empty($d['terms'])): ?>
      <div class="terms-block"><strong>Terms &amp; Conditions</strong><div><?= nl2br(htmlspecialchars($d['terms'])) ?></div></div>
    <?php endif; ?>
  </div>

  <footer class="doc-footer">
    <?php if (!empty($d['footer_thanks'])):  ?><div class="thanks"><?=  htmlspecialchars($d['footer_thanks'])  ?></div><?php endif; ?>
    <?php if (!empty($d['footer_contact'])): ?><div class="contact"><?= nl2br(htmlspecialchars($d['footer_contact'])) ?></div><?php endif; ?>
  </footer>
</div>
