<?php
// -------------------------------------------------------
// Professional Proposal PDF Template - Matching Invoice Grid Style
// -------------------------------------------------------

// Load company info
$company_options = array("is_default" => true);
if ($proposal_info->company_id) {
    $company_options = array("id" => $proposal_info->company_id);
}
$company_options["deleted"] = 0;
$Company_model = model('App\Models\Company_model');
$comp = $Company_model->get_one_where($company_options);
if ($proposal_info->company_id && !$comp->id) {
    $comp = $Company_model->get_one_where(array("is_default" => true, "deleted" => 0));
}

$summary    = $proposal_total_summary;
$currency   = $summary->currency_symbol ? $summary->currency_symbol : get_setting("currency_symbol");

// Helper to render image for TCPDF with fixed height so it scales proportionally
if (!function_exists('_get_proposal_pdf_img_tag')) {
    function _get_proposal_pdf_img_tag($file_item, $height = 42, $alt = '') {
        if (!$file_item || !is_array($file_item)) return '';
        $sys_path = get_setting("system_file_path");
        $url = get_source_url_of_file($file_item, $sys_path);
        if (!$url) return '';
        return '<img src="' . $url . '" height="' . $height . '" border="0" alt="' . htmlspecialchars($alt) . '" />';
    }
}

// Company Logo
$our_logo_html = '<b style="font-size:13px;color:#111;">' . htmlspecialchars($comp->name) . '</b>';
if ($comp->logo) {
    $lf = @unserialize($comp->logo);
    if ($lf && is_array($lf)) {
        $litem = reset($lf);
        $rendered_logo = _get_proposal_pdf_img_tag($litem, 44, $comp->name);
        if ($rendered_logo) {
            $our_logo_html = $rendered_logo;
        }
    }
}

// Client / Recipient Logo
$client_logo_html = '';
$client_logo_item = null;
if (!empty($proposal_info->client_logo)) {
    $cl_files = @unserialize($proposal_info->client_logo);
    if ($cl_files && is_array($cl_files)) {
        $client_logo_item = reset($cl_files);
    }
}
if (!$client_logo_item && isset($client_info) && !empty($client_info->client_logo)) {
    $cl_files = @unserialize($client_info->client_logo);
    if ($cl_files && is_array($cl_files)) {
        $client_logo_item = reset($cl_files);
    }
}
if ($client_logo_item) {
    $client_logo_html = _get_proposal_pdf_img_tag($client_logo_item, 40, 'Recipient Logo');
}

// Company Signature (larger size as requested)
$sig_html = '';
if ($comp->signature) {
    $sig_files = @unserialize($comp->signature);
    if ($sig_files && is_array($sig_files)) {
        $sig_item = reset($sig_files);
        $sig_html = _get_proposal_pdf_img_tag($sig_item, 52, 'Signature');
    }
}

// Amount in Words helper
if (!function_exists('_inr_words')) {
    function _inr_words($num) {
        $num   = (float) $num;
        $paise = (int) round(($num - floor($num)) * 100);
        $n     = (int) floor($num);
        $ones  = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
        $tens  = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        $below1000 = function($x) use ($ones,$tens) {
            $r = '';
            if ($x>=100) { $r .= $ones[(int)($x/100)] . ' Hundred '; $x %= 100; }
            if ($x>=20)  { $r .= $tens[(int)($x/10)] . ' '; $x %= 10; }
            if ($x>0)    { $r .= $ones[$x] . ' '; }
            return $r;
        };
        if ($n == 0) return 'Zero Rupees Only';
        $r = '';
        if ($n>=10000000) { $r .= $below1000((int)($n/10000000)) . 'Crore '; $n %= 10000000; }
        if ($n>=100000)   { $r .= $below1000((int)($n/100000))   . 'Lakh ';  $n %= 100000;   }
        if ($n>=1000)     { $r .= $below1000((int)($n/1000))     . 'Thousand '; $n %= 1000;  }
        if ($n>0)         { $r .= $below1000($n); }
        $r = trim($r) . ' Rupees';
        if ($paise>0)     { $r .= ' and ' . $below1000($paise) . 'Paise'; }
        return trim($r) . ' only';
    }
}

$amount_words   = _inr_words($summary->proposal_total);
$proposal_label = get_proposal_id($proposal_info->id);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"/>
<style>
body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 9.5px;
    color: #111;
    margin: 0;
    padding: 0;
}
table {
    border-collapse: collapse;
    width: 100%;
}
.title-hdr {
    text-align: center;
    font-size: 16px;
    font-weight: bold;
    letter-spacing: 1px;
    padding: 6px;
    border-bottom: 1.5px solid #111;
}
.from-td {
    width: 53%;
    border-right: 1.5px solid #111;
    border-bottom: 1.5px solid #111;
    padding: 6px 8px;
    vertical-align: top;
    line-height: 1.35;
}
.meta-td {
    width: 47%;
    border-bottom: 1.5px solid #111;
    padding: 6px 8px;
    vertical-align: top;
    text-align: right;
    line-height: 1.35;
}
.to-td {
    width: 53%;
    border-right: 1.5px solid #111;
    padding: 6px 8px;
    vertical-align: top;
    line-height: 1.35;
}
.client-logo-td {
    width: 47%;
    padding: 6px 8px;
    vertical-align: middle;
    text-align: right;
    line-height: 1.35;
}
.tbl-hdr th {
    background-color: #f0f0f0;
    font-weight: bold;
    border: 1px solid #111;
    padding: 5px 4px;
    text-align: center;
    font-size: 9px;
}
.item-cell {
    border: 1px solid #111;
    padding: 5px 4px;
    font-size: 9px;
    vertical-align: top;
}
.tot-lbl {
    border: 1px solid #111;
    text-align: right;
    font-weight: bold;
    padding: 4px 6px;
    font-size: 9px;
}
.tot-val {
    border: 1px solid #111;
    text-align: right;
    font-weight: bold;
    padding: 4px 6px;
    font-size: 9px;
}
.gtot-cell {
    background-color: #f0f0f0;
    font-size: 9.5px;
}
.words-row {
    border-bottom: 1.5px solid #111;
    padding: 5px 8px;
    font-weight: bold;
    font-size: 9.5px;
}
.bank-cell {
    width: 53%;
    border-right: 1.5px solid #111;
    padding: 6px 8px;
    vertical-align: top;
    line-height: 1.35;
    font-size: 9px;
}
.sign-cell {
    width: 47%;
    padding: 6px 8px;
    vertical-align: top;
    text-align: center;
    font-size: 9px;
    line-height: 1.3;
}
.note-terms-cell {
    border-top: 1.5px solid #111;
    padding: 6px 8px;
    font-size: 8.5px;
    line-height: 1.3;
}
</style>
</head>
<body>

<!-- 1. HEADER SECTION (TITLE, FROM, TO, LOGOS) -->
<table style="border: 1.5px solid #111; border-bottom: none; border-collapse: collapse; width: 100%;">

  <!-- TITLE HEADER -->
  <tr>
    <td colspan="2" class="title-hdr">
      PROPOSAL
    </td>
  </tr>

  <!-- FROM (LEFT) & OUR LOGO + PROPOSAL INFO (RIGHT) -->
  <tr>
    <td class="from-td">
      <b>From:</b><br/>
      <b style="font-size: 10.5px;"><?php echo htmlspecialchars($comp->name); ?></b><br/>
      <?php if ($comp->address): ?><?php echo nl2br(htmlspecialchars($comp->address)); ?><br/><?php endif; ?>
      <?php if ($comp->phone): ?>Contact : <?php echo htmlspecialchars($comp->phone); ?><br/><?php endif; ?>
      <?php if ($comp->email): ?>Email : <?php echo htmlspecialchars($comp->email); ?><br/><?php endif; ?>
      <?php if ($comp->gst_number): ?>GST No. <?php echo htmlspecialchars($comp->gst_number); ?><br/><?php elseif ($comp->vat_number): ?>VAT No. <?php echo htmlspecialchars($comp->vat_number); ?><br/><?php endif; ?>
    </td>
    <td class="meta-td">
      <div style="text-align: right; margin-bottom: 6px;">
        <?php echo $our_logo_html; ?>
      </div>
      <div style="text-align: right; font-size: 9px; line-height: 1.45;">
        <b>Proposal No:</b> <?php echo htmlspecialchars($proposal_label); ?><br/>
        <b>Proposal Date:</b> <?php echo format_to_date($proposal_info->proposal_date, false); ?>
      </div>
    </td>
  </tr>

  <!-- TO (LEFT) & CLIENT LOGO + VALIDITY (RIGHT) -->
  <tr>
    <td class="to-td">
      <b>To:</b><br/>
      <b style="font-size: 10.5px;">M/s. <?php echo htmlspecialchars($client_info->company_name); ?></b><br/>
      <?php if ($client_info->address): ?><?php echo nl2br(htmlspecialchars($client_info->address)); ?><br/><?php endif; ?>
      <?php 
      $location_parts = array_filter(array(
          $client_info->city,
          $client_info->state . ($client_info->zip ? ' - ' . $client_info->zip : '')
      ));
      if (!empty($location_parts)): ?>
        <?php echo htmlspecialchars(implode(', ', $location_parts)); ?><br/>
      <?php elseif ($client_info->zip): ?>
        <?php echo htmlspecialchars($client_info->zip); ?><br/>
      <?php endif; ?>
      <?php if ($client_info->phone): ?>Contact : <?php echo htmlspecialchars($client_info->phone); ?><br/><?php endif; ?>
      <?php if ($client_info->gst_number): ?>GST No. <?php echo htmlspecialchars($client_info->gst_number); ?><br/><?php elseif ($client_info->vat_number): ?>VAT No. <?php echo htmlspecialchars($client_info->vat_number); ?><br/><?php endif; ?>
    </td>
    <td class="client-logo-td">
      <?php if ($client_logo_html): ?>
        <div style="text-align: right; margin-bottom: 6px;">
          <?php echo $client_logo_html; ?>
        </div>
      <?php endif; ?>
      <div style="text-align: right; font-size: 9px; line-height: 1.45;">
        <b>Valid Until:</b> <?php echo format_to_date($proposal_info->valid_until, false); ?><br/>
        <b>Status:</b> <?php echo ucfirst($proposal_info->status); ?>
      </div>
    </td>
  </tr>

</table>

<!-- 2. ITEMS TABLE (SEPARATE ROOT TABLE - PERFECT BORDER ALIGNMENT) -->
<table style="border-left: 1.5px solid #111; border-right: 1.5px solid #111; border-top: 1.5px solid #111; border-collapse: collapse; width: 100%;">
  <tr class="tbl-hdr">
    <th style="width: 7%; border-left: none;">Sl.No</th>
    <th style="width: 47%; text-align: left;">Description of Goods</th>
    <th style="width: 9%;">Unit</th>
    <th style="width: 10%;">Area / Qty</th>
    <th style="width: 13%; text-align: right;">Rate (<?php echo htmlspecialchars($currency); ?>)</th>
    <th style="width: 14%; text-align: right; border-right: none;">Amount (<?php echo htmlspecialchars($currency); ?>)</th>
  </tr>
  <?php $rn = 1; foreach ($proposal_items as $item): ?>
  <tr>
    <td class="item-cell" style="text-align: center; border-left: none;"><?php echo $rn; ?></td>
    <td class="item-cell">
      <b><?php echo htmlspecialchars($item->title); ?></b>
      <?php if ($item->description): ?>
        <br/><span style="color: #444; font-size: 8px;"><?php echo strip_tags(custom_nl2br($item->description)); ?></span>
      <?php endif; ?>
    </td>
    <td class="item-cell" style="text-align: center;"><?php echo htmlspecialchars($item->unit_type ? $item->unit_type : '-'); ?></td>
    <td class="item-cell" style="text-align: center;"><?php echo to_decimal_format($item->quantity); ?></td>
    <td class="item-cell" style="text-align: right;"><?php echo to_decimal_format($item->rate); ?></td>
    <td class="item-cell" style="text-align: right; border-right: none;"><?php echo to_decimal_format($item->total); ?></td>
  </tr>
  <?php $rn++; endforeach; ?>

  <!-- TOTALS SECTION -->
  <tr>
    <td colspan="4" class="item-cell" style="font-weight: bold; background-color: #fff; border-left: none;">Total</td>
    <td class="tot-lbl">Basic Value</td>
    <td class="tot-val" style="border-right: none;"><?php echo to_currency($summary->proposal_subtotal, $currency); ?></td>
  </tr>
  <?php if ($summary->discount_total && $summary->discount_type == 'before_tax'): ?>
  <tr>
    <td colspan="4" class="item-cell" style="border-left: none;"></td>
    <td class="tot-lbl">Discount</td>
    <td class="tot-val" style="border-right: none;">- <?php echo to_currency($summary->discount_total, $currency); ?></td>
  </tr>
  <tr>
    <td colspan="4" class="item-cell" style="border-left: none;"></td>
    <td class="tot-lbl">After Discount</td>
    <td class="tot-val" style="border-right: none;"><?php echo to_currency($summary->proposal_subtotal - $summary->discount_total, $currency); ?></td>
  </tr>
  <?php endif; ?>
  <?php 
  if ($summary->tax): 
      $tname1 = $summary->tax_name ? $summary->tax_name : 'CGST';
      if (preg_match('/^Tax\b/i', $tname1)) {
          $tname1 = preg_replace('/^Tax\b/i', 'CGST', $tname1);
      }
      if (strpos($tname1, '%') === false && $summary->tax_percentage) {
          $tname1 .= ' @ ' . to_decimal_format($summary->tax_percentage) . '%';
      }
  ?>
  <tr>
    <td colspan="4" class="item-cell" style="border-left: none;"></td>
    <td class="tot-lbl"><?php echo htmlspecialchars($tname1); ?></td>
    <td class="tot-val" style="border-right: none;"><?php echo to_currency($summary->tax, $currency); ?></td>
  </tr>
  <?php endif; ?>
  <?php 
  if ($summary->tax2): 
      $tname2 = $summary->tax_name2 ? $summary->tax_name2 : 'SGST';
      if (preg_match('/^Tax\b/i', $tname2)) {
          $tname2 = preg_replace('/^Tax\b/i', 'SGST', $tname2);
      }
      if (strpos($tname2, '%') === false && $summary->tax_percentage2) {
          $tname2 .= ' @ ' . to_decimal_format($summary->tax_percentage2) . '%';
      }
  ?>
  <tr>
    <td colspan="4" class="item-cell" style="border-left: none;"></td>
    <td class="tot-lbl"><?php echo htmlspecialchars($tname2); ?></td>
    <td class="tot-val" style="border-right: none;"><?php echo to_currency($summary->tax2, $currency); ?></td>
  </tr>
  <?php endif; ?>
  <?php if ($summary->discount_total && $summary->discount_type == 'after_tax'): ?>
  <tr>
    <td colspan="4" class="item-cell" style="border-left: none;"></td>
    <td class="tot-lbl">Discount</td>
    <td class="tot-val" style="border-right: none;">- <?php echo to_currency($summary->discount_total, $currency); ?></td>
  </tr>
  <?php endif; ?>
  <tr>
    <td colspan="4" class="item-cell" style="border-left: none;"></td>
    <td class="tot-lbl gtot-cell">Grand Total</td>
    <td class="tot-val gtot-cell" style="border-right: none;"><?php echo to_currency($summary->proposal_total, $currency); ?></td>
  </tr>
</table>

<!-- 3. FOOTER SECTION (RUPEES IN WORDS, BANK DETAILS, SIGNATURE, TERMS) -->
<table style="border: 1.5px solid #111; border-collapse: collapse; width: 100%;">

  <!-- RUPEES IN WORDS BANNER -->
  <tr>
    <td colspan="2" class="words-row">
      Rupees in words : <?php echo $amount_words; ?>
    </td>
  </tr>

  <!-- COMPACT BANK DETAILS (LEFT) & LARGER SIGNATURE (RIGHT) -->
  <tr>
    <td class="bank-cell">
      <b style="font-size: 9.5px;">Bank Details</b><br/>
      <?php if ($comp->bank_details): ?>
        <?php echo nl2br(htmlspecialchars($comp->bank_details)); ?>
      <?php else: ?>
        <span style="color: #888;">No bank details configured.</span>
      <?php endif; ?>
    </td>
    <td class="sign-cell">
      <b style="font-size: 9.5px;">Authorized Signatory</b><br/>
      <div style="height: 56px; text-align: center; padding: 2px 0;">
        <?php if ($sig_html): ?>
          <?php echo $sig_html; ?>
        <?php else: ?>
          <div style="height: 50px;"></div>
        <?php endif; ?>
      </div>
      <b style="font-size: 9.5px;"><?php echo htmlspecialchars($comp->name); ?></b>
    </td>
  </tr>

  <!-- NOTE & TERMS & CONDITIONS (IF PRESENT) -->
  <?php if ($proposal_info->note || $proposal_info->terms_conditions): ?>
  <tr>
    <td colspan="2" class="note-terms-cell">
      <?php if ($proposal_info->note): ?>
        <b>Note:</b> <?php echo nl2br(strip_tags($proposal_info->note)); ?><br/>
      <?php endif; ?>
      <?php if ($proposal_info->terms_conditions): ?>
        <div style="margin-top: 4px;">
          <b>Terms &amp; Conditions:</b><br/>
          <div style="white-space: pre-wrap;"><?php echo nl2br(strip_tags($proposal_info->terms_conditions)); ?></div>
        </div>
      <?php endif; ?>
    </td>
  </tr>
  <?php endif; ?>

</table>

</body>
</html>
