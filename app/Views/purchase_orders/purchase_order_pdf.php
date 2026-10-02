<?php
/**
 * Purchase Order PDF Template - Rise CRM
 * Boxed Indian Format matching client specifications:
 *  - Outer frame: 1.5px solid #111
 *  - Section 1: PURCHASE ORDER title centered in boxed header
 *  - Section 2: 2-column header: From (Company details) / To (Vendor details) [Left] + Logo, Ref No, Ref Date, PO Date, PO No [Right]
 *  - Section 3: Items table with shaded gray header (SL.NO, Glass Specification, Hsn Code, Qty, Rate, Per, Amount)
 *               Vertical grid lines, Subtotal row, Tax breakdown rows (SGST/CGST or IGST), Grand Total
 *  - Section 4: Rupees in words banner
 *  - Section 5: Bottom box: Notes & terms [Left] + Authorized Signatory, signature & Company Name [Right]
 */

$comp = (object) (isset($company_info) && $company_info ? (array) $company_info : array());
$client_info = (object) (isset($client_info) && $client_info ? (array) $client_info : array());

// Fallback company details
if (empty($comp->name)) $comp->name = get_setting('company_name');
if (empty($comp->address)) $comp->address = get_setting('company_address');
if (empty($comp->phone)) $comp->phone = get_setting('company_phone');
if (empty($comp->email)) $comp->email = get_setting('company_email');
if (empty($comp->gst_number)) $comp->gst_number = get_setting('company_gst_number');
if (empty($comp->state)) $comp->state = get_setting('company_state');
if (empty($comp->state_code)) $comp->state_code = get_setting('company_state_code');
if (empty($comp->signature)) $comp->signature = get_setting('company_signature');

$comp_gst = isset($comp->gst_number) ? trim($comp->gst_number) : '';
$comp_state = isset($comp->state) && $comp->state ? $comp->state : 'Tamil Nadu';
$comp_state_code = isset($comp->state_code) && $comp->state_code ? $comp->state_code : '33';

// Client / Vendor details
$client_gst = isset($client_info->gst_number) ? trim($client_info->gst_number) : '';
if (!$client_gst && isset($client_info->gstin)) $client_gst = trim($client_info->gstin);
$client_state = isset($client_info->state) && $client_info->state ? $client_info->state : 'Tamil Nadu';
$client_state_code = isset($client_info->state_code) && $client_info->state_code ? $client_info->state_code : '';
if (!$client_state_code && strlen($client_gst) >= 2 && is_numeric(substr($client_gst, 0, 2))) {
    $client_state_code = substr($client_gst, 0, 2);
}
if (!$client_state_code) {
    $client_state_code = '33';
}

$client_city_pin = array();
if (!empty($client_info->city)) $client_city_pin[] = $client_info->city;
if (!empty($client_info->zip)) $client_city_pin[] = $client_info->zip;
$client_city_pin_str = implode(' - ', $client_city_pin);

// Contact info of primary contact if available
$primary_contact_name = '';
$primary_contact_email = '';
$primary_contact_phone = '';
if (isset($client_info->id) && $client_info->id) {
    try {
        $u_row = model('App\Models\Users_model')->get_all_where(array("user_type" => "client", "client_id" => $client_info->id, "deleted" => 0, "is_primary_contact" => 1))->getRow();
        if ($u_row) {
            $primary_contact_name = trim($u_row->first_name . ' ' . $u_row->last_name);
            $primary_contact_email = $u_row->email;
            $primary_contact_phone = $u_row->phone;
        }
    } catch (\Throwable $e) {}
}

$client_phone = $primary_contact_phone ? $primary_contact_phone : (isset($client_info->phone) ? $client_info->phone : '');
$client_email = $primary_contact_email ? $primary_contact_email : (isset($client_info->email) ? $client_info->email : '');
$client_msme = isset($client_info->msme_number) ? $client_info->msme_number : '';

// Helper for local image files - uses HTML height attribute for TCPDF
if (!function_exists('_get_po_pdf_img_tag')) {
    function _get_po_pdf_img_tag($file_item, $height = 36, $alt = 'Image') {
        if (!$file_item) return '';
        $fn = is_array($file_item) ? (isset($file_item['file_name']) ? $file_item['file_name'] : '') : (is_object($file_item) ? $file_item->file_name : (string)$file_item);
        if (!$fn) return '';
        $src = '';
        $candidates = [
            getcwd() . '/files/system/' . $fn,
            getcwd() . '/files/timeline_files/' . $fn,
            getcwd() . '/files/' . $fn
        ];
        foreach ($candidates as $cand) {
            if (file_exists($cand)) {
                $src = $cand;
                break;
            }
        }
        if (!$src) {
            $src = get_file_uri('files/system/' . $fn);
        }
        return '<img src="' . $src . '" height="' . (int)$height . '" alt="' . htmlspecialchars($alt) . '" />';
    }
}

// Company Logo
$our_logo_html = '';
if (!empty($comp->logo)) {
    $lf = @unserialize($comp->logo);
    if ($lf && is_array($lf)) {
        $litem = reset($lf);
        $our_logo_html = _get_po_pdf_img_tag($litem, 38, $comp->name);
    }
}
if (!$our_logo_html) {
    $invoice_logo = get_setting("invoice_logo");
    if ($invoice_logo) {
        $logo_path = getcwd() . '/' . get_setting("system_file_path") . $invoice_logo;
        if (file_exists($logo_path)) {
            $our_logo_html = '<img src="' . $logo_path . '" height="38" alt="' . htmlspecialchars($comp->name) . '" />';
        }
    }
}
if (!$our_logo_html) {
    $our_logo_html = '<span style="font-size:15px;font-weight:bold;color:#111;">' . htmlspecialchars($comp->name) . '</span>';
}

// Company Signature
$sig_html = '';
if (!empty($comp->signature)) {
    $sig_files = @unserialize($comp->signature);
    if ($sig_files && is_array($sig_files)) {
        $sig_item = reset($sig_files);
        $sig_html = _get_po_pdf_img_tag($sig_item, 34, 'Signature');
    }
}

// Indian Rupee Amount in Words helper
if (!function_exists('_inr_words_po')) {
    function _inr_words_po($num) {
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
        if ($n == 0) return 'Zero Only';
        $r = '';
        if ($n>=10000000) { $r .= $below1000((int)($n/10000000)) . ' Crore '; $n %= 10000000; }
        if ($n>=100000)   { $r .= $below1000((int)($n/100000))   . ' Lakh ';  $n %= 100000;   }
        if ($n>=1000)     { $r .= $below1000((int)($n/1000))     . ' Thousand '; $n %= 1000;  }
        if ($n>0)         { $r .= $below1000($n); }
        $r = trim(preg_replace('/\s+/', ' ', $r));
        if ($paise>0)     { $r .= ' and ' . $below1000($paise) . ' Paise'; }
        return trim($r) . ' only';
    }
}

$summary = $purchase_order_total_summary;
$subtotal = isset($summary->purchase_order_subtotal) ? (float)$summary->purchase_order_subtotal : 0;
$grand_total = isset($summary->purchase_order_total) ? (float)$summary->purchase_order_total : $subtotal;
$amount_in_words = _inr_words_po($grand_total);
$po_label = get_purchase_order_id($purchase_order_info->id);

// Taxes breakdown calculation
$tax_rows = array();
if (isset($summary->tax) && $summary->tax > 0 && isset($summary->tax2) && $summary->tax2 > 0) {
    $tname1 = $summary->tax_name ? $summary->tax_name : 'CGST';
    if (preg_match('/^Tax\b/i', $tname1)) {
        $tname1 = preg_replace('/^Tax\b/i', 'CGST', $tname1);
    }
    $tname2 = $summary->tax_name2 ? $summary->tax_name2 : 'SGST';
    if (preg_match('/^Tax\b/i', $tname2)) {
        $tname2 = preg_replace('/^Tax\b/i', 'SGST', $tname2);
    }
    $tax_rows[] = array(
        'label' => $tname1,
        'amount' => $summary->tax
    );
    $tax_rows[] = array(
        'label' => $tname2,
        'amount' => $summary->tax2
    );
} else if (isset($summary->tax) && $summary->tax > 0) {
    $tax_pct = (float)$summary->tax_percentage;
    $is_intra = ($comp_state_code && $client_state_code && $comp_state_code == $client_state_code) || (!$comp_state_code && !$client_state_code);
    
    if ($is_intra && $tax_pct > 0) {
        $half_pct = $tax_pct / 2;
        $half_tax = $summary->tax / 2;
        $pct_str = rtrim(rtrim(number_format($half_pct, 2, '.', ''), '0'), '.');
        $tax_rows[] = array(
            'label' => 'SGST @ ' . $pct_str . '%',
            'amount' => $half_tax
        );
        $tax_rows[] = array(
            'label' => 'CGST @ ' . $pct_str . '%',
            'amount' => $half_tax
        );
    } else {
        $tax_rows[] = array(
            'label' => $summary->tax_name ? $summary->tax_name : 'IGST @ ' . to_decimal_format($tax_pct) . '%',
            'amount' => $summary->tax
        );
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
<style>
body {
    font-family: 'dejavusans', 'Helvetica', 'Arial', sans-serif;
    font-size: 8px;
    color: #111;
    line-height: 1.25;
}
table {
    border-collapse: collapse;
}
.b-all { border: 1.5px solid #111; }
.b-top-none { border-top: none; }
.b-bottom-none { border-bottom: none; }
</style>
</head>
<body>

<!-- SECTION 1: PURCHASE ORDER TITLE (Full width boxed header) -->
<table class="b-all" cellpadding="5" style="width: 100%;">
  <tr>
    <td style="text-align: center; vertical-align: middle;">
      <span style="font-size: 14px; font-weight: bold; letter-spacing: 1.5px; line-height: 18px;">PURCHASE ORDER</span>
    </td>
  </tr>
</table>

<!-- SECTION 2: 2-COLUMN HEADER (From / To on Left + Logo / Reference Metadata on Right) -->
<table class="b-all b-top-none" style="width: 100%;">
  <tr>
    <!-- Left Column: From Details -->
    <td style="width: 57%; vertical-align: top; padding: 6px 6px; border-right: 1px solid #111; border-bottom: 1px solid #111;">
      <span style="font-weight: bold; font-size: 8.5px;">From:</span><br/>
      <span style="font-weight: bold; font-size: 9px;"><?php echo htmlspecialchars($comp->name); ?></span><br/>
      <?php if ($comp->address): ?><?php echo nl2br(htmlspecialchars(trim($comp->address))); ?><br/><?php endif; ?>
      <?php if ($comp->phone): ?>Contact : <?php echo htmlspecialchars($comp->phone); ?><br/><?php endif; ?>
      <?php if ($comp_gst): ?>GST No. <?php echo htmlspecialchars($comp_gst); ?><?php endif; ?>
    </td>
    <!-- Right Column: Logo at top, Ref, Ref Date, PO Date, PO No -->
    <td rowspan="2" style="width: 43%; vertical-align: top; padding: 6px 8px;">
      <div style="text-align: right; margin-bottom: 8px;">
        <?php echo $our_logo_html; ?>
      </div>
      <div style="font-size: 8px; line-height: 1.5;">
        <b>Ref: </b><?php echo htmlspecialchars($purchase_order_info->reference_number ? $purchase_order_info->reference_number : '-'); ?><br/>
        <b>Ref Date :</b> <?php echo $purchase_order_info->reference_date ? format_to_date($purchase_order_info->reference_date, false) : '-'; ?><br/>
        <br/>
        <b>Purchase Order Date</b> <?php echo format_to_date($purchase_order_info->purchase_order_date, false); ?><br/>
        <b>Purchase Order No:</b> <?php echo htmlspecialchars($po_label); ?>
      </div>
    </td>
  </tr>
  <tr>
    <!-- Left Column: To Details -->
    <td style="width: 57%; vertical-align: top; padding: 6px 6px; border-right: 1px solid #111;">
      <span style="font-weight: bold; font-size: 8.5px;">To:</span><br/>
      <span style="font-weight: bold; font-size: 9px;"><?php echo htmlspecialchars($client_info->company_name); ?></span><br/>
      <?php if ($client_info->address): ?><?php echo nl2br(htmlspecialchars(trim($client_info->address))); ?><br/><?php endif; ?>
      <?php if (!empty($client_city_pin_str)): ?><?php echo htmlspecialchars($client_city_pin_str); ?><br/><?php endif; ?>
      <?php if ($client_phone): ?>Ph: <?php echo htmlspecialchars($client_phone); ?><br/><?php endif; ?>
      <?php if ($client_msme): ?>MSME: <?php echo htmlspecialchars($client_msme); ?><br/><?php endif; ?>
      <?php if ($client_gst): ?>GSTIN/UIN: <?php echo htmlspecialchars($client_gst); ?><br/><?php endif; ?>
      State Name : <?php echo htmlspecialchars($client_state); ?>, Code : <?php echo htmlspecialchars($client_state_code); ?><br/>
      <?php if ($primary_contact_name): ?>Contact : <?php echo htmlspecialchars($primary_contact_name); ?><?php if ($client_phone): ?>, <?php echo htmlspecialchars($client_phone); ?><?php endif; ?><br/><?php elseif ($client_phone): ?>Contact : <?php echo htmlspecialchars($client_phone); ?><br/><?php endif; ?>
      <?php if ($client_email): ?>E-Mail : <?php echo htmlspecialchars($client_email); ?><br/><?php endif; ?>
    </td>
  </tr>
</table>

<!-- SECTION 3: ITEMS TABLE -->
<table class="b-all b-top-none" style="width: 100%;">
  <thead>
    <tr style="background-color: #d8dbdf;">
      <th style="width: 5%; text-align: center; font-weight: bold; border-right: 1px solid #111; border-bottom: 1px solid #111; padding: 5px 2px;">SL.NO</th>
      <th style="width: 46%; text-align: center; font-weight: bold; border-right: 1px solid #111; border-bottom: 1px solid #111; padding: 5px 4px;">Items</th>
      <th style="width: 8%; text-align: center; font-weight: bold; border-right: 1px solid #111; border-bottom: 1px solid #111; padding: 5px 2px;">Hsn Code</th>
      <th style="width: 8%; text-align: center; font-weight: bold; border-right: 1px solid #111; border-bottom: 1px solid #111; padding: 5px 2px;">Qty</th>
      <th style="width: 12%; text-align: center; font-weight: bold; border-right: 1px solid #111; border-bottom: 1px solid #111; padding: 5px 2px;">Rate</th>
      <th style="width: 6%; text-align: center; font-weight: bold; border-right: 1px solid #111; border-bottom: 1px solid #111; padding: 5px 2px;">Per</th>
      <th style="width: 15%; text-align: center; font-weight: bold; border-bottom: 1px solid #111; padding: 5px 2px;">Amount</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $sl = 1;
    $items_count = count($purchase_order_items);
    foreach ($purchase_order_items as $item):
        $item_qty = (float)$item->quantity;
        $unit_str = $item->unit_type ? $item->unit_type : 'Nos';
        $rate_val = (float)$item->rate;
        $total_val = (float)$item->total;
    ?>
    <tr>
      <td style="width: 5%; text-align: center; vertical-align: top; border-right: 1px solid #111; padding: 3.5px 2px;"><?php echo $sl; ?></td>
      <td style="width: 46%; vertical-align: top; border-right: 1px solid #111; padding: 3.5px 5px;">
        <span style="font-weight: bold;"><?php echo htmlspecialchars($item->title); ?></span>
        <?php if ($item->description): ?>
          <br/><span style="font-size: 7.5px;"><?php echo nl2br(htmlspecialchars(trim($item->description))); ?></span>
        <?php endif; ?>
      </td>
      <td style="width: 8%; text-align: center; vertical-align: top; border-right: 1px solid #111; padding: 3.5px 2px;"><?php echo htmlspecialchars($item->hsn_sac_code ? $item->hsn_sac_code : ''); ?></td>
      <td style="width: 8%; text-align: center; vertical-align: top; border-right: 1px solid #111; padding: 3.5px 2px;"><?php echo to_decimal_format($item_qty); ?> <?php echo htmlspecialchars($unit_str); ?></td>
      <td style="width: 12%; text-align: right; vertical-align: top; border-right: 1px solid #111; padding: 3.5px 4px;"><?php echo $rate_val > 0 ? to_decimal_format($rate_val) : ''; ?></td>
      <td style="width: 6%; text-align: center; vertical-align: top; border-right: 1px solid #111; padding: 3.5px 2px;"><?php echo htmlspecialchars($unit_str); ?></td>
      <td style="width: 15%; text-align: right; vertical-align: top; padding: 3.5px 4px;"><?php echo number_format($total_val, 2); ?></td>
    </tr>
    <?php
        $sl++;
    endforeach;
    
    // Single tall filler row extending vertical lines cleanly to match reference layout
    $filler_height = max(45, 140 - ($items_count * 22));
    ?>
    <tr>
      <td style="width: 5%; height: <?php echo $filler_height; ?>px; border-right: 1px solid #111; padding: 0;">&nbsp;</td>
      <td style="width: 46%; height: <?php echo $filler_height; ?>px; border-right: 1px solid #111; padding: 0;">&nbsp;</td>
      <td style="width: 8%; height: <?php echo $filler_height; ?>px; border-right: 1px solid #111; padding: 0;">&nbsp;</td>
      <td style="width: 8%; height: <?php echo $filler_height; ?>px; border-right: 1px solid #111; padding: 0;">&nbsp;</td>
      <td style="width: 12%; height: <?php echo $filler_height; ?>px; border-right: 1px solid #111; padding: 0;">&nbsp;</td>
      <td style="width: 6%; height: <?php echo $filler_height; ?>px; border-right: 1px solid #111; padding: 0;">&nbsp;</td>
      <td style="width: 15%; height: <?php echo $filler_height; ?>px; padding: 0;">&nbsp;</td>
    </tr>

    <!-- Subtotal Row -->
    <tr style="border-top: 1px solid #111; border-bottom: 1px solid #111;">
      <td style="width: 5%; border-top: 1px solid #111; border-bottom: 1px solid #111; border-right: 1px solid #111; padding: 3.5px 2px;">&nbsp;</td>
      <td style="width: 46%; border-top: 1px solid #111; border-bottom: 1px solid #111; border-right: 1px solid #111; padding: 3.5px 5px;">&nbsp;</td>
      <td style="width: 8%; border-top: 1px solid #111; border-bottom: 1px solid #111; border-right: 1px solid #111; padding: 3.5px 2px;">&nbsp;</td>
      <td style="width: 8%; border-top: 1px solid #111; border-bottom: 1px solid #111; border-right: 1px solid #111; padding: 3.5px 2px;">&nbsp;</td>
      <td style="width: 12%; border-top: 1px solid #111; border-bottom: 1px solid #111; border-right: 1px solid #111; padding: 3.5px 4px;">&nbsp;</td>
      <td style="width: 6%; text-align: center; font-weight: bold; border-top: 1px solid #111; border-bottom: 1px solid #111; border-right: 1px solid #111; padding: 3.5px 2px;">0</td>
      <td style="width: 15%; text-align: right; font-weight: bold; border-top: 1px solid #111; border-bottom: 1px solid #111; padding: 3.5px 4px;"><?php echo number_format($subtotal, 2); ?></td>
    </tr>

    <!-- Tax Breakdown Rows -->
    <?php if (!empty($tax_rows)): ?>
      <?php foreach ($tax_rows as $t_row): ?>
      <tr>
        <td style="width: 5%; border-right: 1px solid #111; padding: 3px 2px;">&nbsp;</td>
        <td style="width: 46%; border-right: 1px solid #111; padding: 3px 5px;">&nbsp;</td>
        <td style="width: 8%; border-right: 1px solid #111; padding: 3px 2px;">&nbsp;</td>
        <td style="width: 8%; border-right: 1px solid #111; padding: 3px 2px;">&nbsp;</td>
        <td style="width: 12%; text-align: left; font-weight: bold; border-top: 1px solid #111; border-bottom: 1px solid #111; border-right: 1px solid #111; padding: 3px 4px;"><?php echo htmlspecialchars($t_row['label']); ?></td>
        <td style="width: 6%; text-align: center; border-top: 1px solid #111; border-bottom: 1px solid #111; border-right: 1px solid #111; padding: 3px 2px;">-</td>
        <td style="width: 15%; text-align: right; border-top: 1px solid #111; border-bottom: 1px solid #111; padding: 3px 4px;"><?php echo number_format($t_row['amount'], 2); ?></td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($summary->discount_total) && $summary->discount_total > 0): ?>
    <tr>
      <td style="width: 5%; border-right: 1px solid #111; padding: 3px 2px;">&nbsp;</td>
      <td style="width: 46%; border-right: 1px solid #111; padding: 3px 5px;">&nbsp;</td>
      <td style="width: 8%; border-right: 1px solid #111; padding: 3px 2px;">&nbsp;</td>
      <td style="width: 8%; border-right: 1px solid #111; padding: 3px 2px;">&nbsp;</td>
      <td style="width: 12%; text-align: left; font-weight: bold; border-top: 1px solid #111; border-bottom: 1px solid #111; border-right: 1px solid #111; padding: 3px 4px;">Discount</td>
      <td style="width: 6%; text-align: center; border-top: 1px solid #111; border-bottom: 1px solid #111; border-right: 1px solid #111; padding: 3px 2px;">-</td>
      <td style="width: 15%; text-align: right; border-top: 1px solid #111; border-bottom: 1px solid #111; padding: 3px 4px;">-<?php echo number_format($summary->discount_total, 2); ?></td>
    </tr>
    <?php endif; ?>

    <!-- Grand Total Row -->
    <tr style="border-top: 1px solid #111;">
      <td style="width: 5%; border-top: 1px solid #111; border-right: 1px solid #111; padding: 3.5px 2px;">&nbsp;</td>
      <td style="width: 46%; border-top: 1px solid #111; border-right: 1px solid #111; padding: 3.5px 5px;">&nbsp;</td>
      <td style="width: 8%; border-top: 1px solid #111; border-right: 1px solid #111; padding: 3.5px 2px;">&nbsp;</td>
      <td style="width: 8%; border-top: 1px solid #111; border-right: 1px solid #111; padding: 3.5px 2px;">&nbsp;</td>
      <td style="width: 12%; text-align: left; font-weight: bold; border-top: 1px solid #111; border-bottom: 1px solid #111; border-right: 1px solid #111; padding: 3.5px 4px;">Grand Total</td>
      <td style="width: 6%; text-align: center; border-top: 1px solid #111; border-bottom: 1px solid #111; border-right: 1px solid #111; padding: 3.5px 2px;">&nbsp;</td>
      <td style="width: 15%; text-align: right; font-weight: bold; border-top: 1px solid #111; border-bottom: 1px solid #111; padding: 3.5px 4px;"><?php echo number_format($grand_total, 2); ?></td>
    </tr>
  </tbody>
</table>

<!-- SECTION 4: RUPEES IN WORDS BANNER -->
<table class="b-all b-top-none" style="width: 100%;">
  <tr>
    <td style="padding: 4.5px 6px; font-size: 8px;">
      <b>Rupees in words : <?php echo htmlspecialchars($amount_in_words); ?></b>
    </td>
  </tr>
</table>

<!-- SECTION 5: BOTTOM BOX (Note on Left + Authorized Signatory on Right) -->
<table class="b-all b-top-none" style="width: 100%;">
  <tr>
    <!-- Left: Notes & Terms -->
    <td style="width: 60%; vertical-align: top; padding: 8px 6px; font-size: 7.5px; line-height: 1.45;">
      <span style="font-weight: bold; font-size: 8px;">Note:</span><br/>
      <?php if (!empty($purchase_order_info->note)): ?>
        <?php
          $note_lines = explode("\n", str_replace("\r", "", trim($purchase_order_info->note)));
          $ni = 1;
          foreach ($note_lines as $nl):
              $nl = trim($nl);
              if (!$nl) continue;
              if (preg_match('/^\d+[\.\)]/', $nl)) {
                  echo htmlspecialchars($nl) . "<br/>";
              } else {
                  echo $ni . "." . htmlspecialchars($nl) . "<br/>";
                  $ni++;
              }
          endforeach;
        ?>
      <?php else: ?>
        1.Colour Should be remain has mentioned in th proforma invoice<br/>
        2.No deviation in the colour change acceptable<br/>
        3. Date of Delivery within 7 working days<br/>
      <?php endif; ?>
      <?php if (!empty($purchase_order_info->terms_conditions)): ?>
        <br/><span style="font-weight: bold; font-size: 7.5px;">Terms:</span><br/>
        <?php echo nl2br(htmlspecialchars(trim($purchase_order_info->terms_conditions))); ?>
      <?php endif; ?>
    </td>
    <!-- Right: Authorized Signatory, Signature, Company Name -->
    <td style="width: 40%; vertical-align: top; padding: 8px 8px; text-align: right;">
      <div style="font-weight: bold; font-size: 8.5px; margin-top: 35px; margin-bottom: 8px;">
        Authorized Signatory
      </div>
      <div style="min-height: 40px; margin-bottom: 6px;">
        <?php if ($sig_html): ?>
          <?php echo $sig_html; ?>
        <?php else: ?>
          &nbsp;<br/>&nbsp;<br/>
        <?php endif; ?>
      </div>
      <div style="font-weight: bold; font-size: 8.5px;">
        <?php echo htmlspecialchars($comp->name); ?>
      </div>
    </td>
  </tr>
</table>

</body>
</html>
