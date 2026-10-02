<?php
/**
 * Delivery Challan PDF Template - Indian GST e-Challan / Tally Format
 * Matches exact structure:
 *  - Top: Delivery Challan header, IRN/Ack details, e-Challan title, QR Code
 *  - Box 1: Consignor (with Company Logo), Consignee (Ship to), Buyer (Bill to) [Left] + Metadata 2-col grid [Right]
 *  - Box 2: Items Table (Sl No, Description, HSN/SAC, Qty, Rate, per, Amount) + Output CGST/SGST + Total
 *  - Box 3: Amount Chargeable in words + E. & O.E
 *  - Box 4: GST / HSN Tax breakdown table (Taxable Value, Central Tax, State Tax, Total Tax)
 *  - Box 5: Tax Amount in words
 *  - Box 6: Company PAN & Declaration [Left] + Bank Details & Authorized Signatory [Right]
 *  - Footer: Computer Generated notice
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
if (empty($comp->pan_number)) $comp->pan_number = get_setting('company_pan_number');
if (empty($comp->bank_details)) $comp->bank_details = get_setting('company_bank_details');
if (empty($comp->signature)) $comp->signature = get_setting('company_signature');

$comp_gst = isset($comp->gst_number) ? trim($comp->gst_number) : '';
$comp_pan = isset($comp->pan_number) ? trim($comp->pan_number) : '';
if (!$comp_pan && strlen($comp_gst) >= 12) {
    $comp_pan = substr($comp_gst, 2, 10);
}
$comp_state = isset($comp->state) && $comp->state ? $comp->state : 'Tamil Nadu';
$comp_state_code = isset($comp->state_code) && $comp->state_code ? $comp->state_code : '33';

// Client details
$client_gst = isset($client_info->gst_number) ? trim($client_info->gst_number) : '';
if (!$client_gst && isset($client_info->gstin)) $client_gst = trim($client_info->gstin);
$client_state = isset($client_info->state) && $client_info->state ? $client_info->state : 'Tamil Nadu';
$client_state_code = isset($client_info->state_code) && $client_info->state_code ? $client_info->state_code : '33';
$client_city_pin = array();
if (!empty($client_info->city)) $client_city_pin[] = $client_info->city;
if (!empty($client_info->zip)) $client_city_pin[] = $client_info->zip;
$client_city_pin_str = implode(' - ', $client_city_pin);

// Helper for local image files - uses HTML height attribute for TCPDF
if (!function_exists('_get_dc_pdf_img_tag')) {
    function _get_dc_pdf_img_tag($file_item, $height = 32, $alt = 'Image') {
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
        $our_logo_html = _get_dc_pdf_img_tag($litem, 32, $comp->name);
    }
}
if (!$our_logo_html) {
    $invoice_logo = get_setting("invoice_logo");
    if ($invoice_logo) {
        $logo_path = getcwd() . '/' . get_setting("system_file_path") . $invoice_logo;
        if (file_exists($logo_path)) {
            $our_logo_html = '<img src="' . $logo_path . '" height="32" alt="' . htmlspecialchars($comp->name) . '" />';
        }
    }
}

// Company Signature
$sig_html = '';
if (!empty($comp->signature)) {
    $sig_files = @unserialize($comp->signature);
    if ($sig_files && is_array($sig_files)) {
        $sig_item = reset($sig_files);
        $sig_html = _get_dc_pdf_img_tag($sig_item, 28, 'Signature');
    }
}

// Bank Details Parsing
$comp_bank_name = 'Bank';
$comp_acc_no = '-';
$comp_branch_ifsc = '-';
if ($comp->bank_details) {
    $bd_lines = explode("\n", str_replace("\r", "", $comp->bank_details));
    foreach ($bd_lines as $bl) {
        $bl = trim($bl);
        if (stripos($bl, 'bank:') !== false || stripos($bl, 'bank name:') !== false) {
            $comp_bank_name = trim(preg_replace('/^bank(\s*name)?\s*:\s*/i', '', $bl));
        } else if (stripos($bl, 'a/c') !== false || stripos($bl, 'account') !== false) {
            $comp_acc_no = trim(preg_replace('/^.*(a\/c|account)(\s*no|number)?\s*:\s*/i', '', $bl));
        } else if (stripos($bl, 'ifsc') !== false) {
            $comp_branch_ifsc = trim($bl);
        } else if (stripos($bl, 'branch') !== false && $comp_branch_ifsc == '-') {
            $comp_branch_ifsc = trim($bl);
        }
    }
    if ($comp_bank_name == 'Bank' && isset($bd_lines[0]) && trim($bd_lines[0])) {
        $comp_bank_name = trim($bd_lines[0]);
    }
}

// Amount in Words helper
if (!function_exists('_inr_words_challan')) {
    function _inr_words_challan($num) {
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
        return trim($r) . ' Only';
    }
}

// Calculate items totals
$total_qty = 0;
$total_taxable = 0;
$has_rates = false;
$hsn_summary = array();

foreach ($delivery_challan_items as $item) {
    $qty = (float) $item->quantity;
    $total_qty += $qty;
    $rate = (float) (isset($item->rate) ? $item->rate : 0);
    $item_total = (float) (isset($item->total) && $item->total > 0 ? $item->total : $qty * $rate);
    if ($rate > 0 || $item_total > 0) {
        $has_rates = true;
        $total_taxable += $item_total;
    }
    $hsn = $item->hsn_sac_code ? $item->hsn_sac_code : '76042100';
    if (!isset($hsn_summary[$hsn])) {
        $hsn_summary[$hsn] = 0;
    }
    $hsn_summary[$hsn] += ($item_total > 0 ? $item_total : 0);
}

// Determine Tax Percentage
$tax_percentage = 0;
if (isset($delivery_challan_info->custom_tax_percentage) && $delivery_challan_info->custom_tax_percentage !== null && $delivery_challan_info->custom_tax_percentage !== '') {
    $tax_percentage = (float) $delivery_challan_info->custom_tax_percentage;
} else if (!empty($delivery_challan_info->tax_percentage)) {
    $tax_percentage = (float) $delivery_challan_info->tax_percentage;
} else if (!empty($delivery_challan_info->tax_id)) {
    $tax_row = model('App\Models\Taxes_model')->get_one($delivery_challan_info->tax_id);
    if ($tax_row && $tax_row->id) {
        $tax_percentage = (float) $tax_row->percentage;
    }
} else if (!isset($delivery_challan_info->tax_id)) {
    // Fallback default for existing records created before the tax field was added
    $tax_percentage = 18;
}

// Check intra-state (Buyer State == Supplier State) vs inter-state
$is_intra_state = ($comp_state_code && $client_state_code && $comp_state_code == $client_state_code) || (!$comp_state_code && !$client_state_code);

if ($is_intra_state) {
    $cgst_rate = $tax_percentage / 2;
    $sgst_rate = $tax_percentage / 2;
    $igst_rate = 0;

    $cgst_total = round($total_taxable * ($cgst_rate / 100), 2);
    $sgst_total = round($total_taxable * ($sgst_rate / 100), 2);
    $igst_total = 0;
    $tax_total  = $cgst_total + $sgst_total;
} else {
    $cgst_rate = 0;
    $sgst_rate = 0;
    $igst_rate = $tax_percentage;

    $cgst_total = 0;
    $sgst_total = 0;
    $igst_total = round($total_taxable * ($igst_rate / 100), 2);
    $tax_total  = $igst_total;
}

$grand_total = $total_taxable + $tax_total;

$amount_in_words = _inr_words_challan($grand_total);
$tax_in_words    = _inr_words_challan($tax_total);
$dc_label        = get_delivery_challan_id($delivery_challan_info->id);

// Generate QR code data URI using TCPDF2DBarcode
$qr_img_src = '';
$qr_text = "e-Challan\nIRN: " . ($delivery_challan_info->reference_number ? $delivery_challan_info->reference_number : $dc_label) . "\nAck No: " . $dc_label . "\nDate: " . $delivery_challan_info->challan_date . "\nAmt: " . number_format($grand_total, 2);
if (file_exists(APPPATH . "ThirdParty/tcpdf/tcpdf_barcodes_2d.php")) {
    require_once APPPATH . "ThirdParty/tcpdf/tcpdf_barcodes_2d.php";
    try {
        $barcode_obj = new \TCPDF2DBarcode($qr_text, 'QRCODE,M');
        $qr_png_data = $barcode_obj->getBarcodePngData(3, 3);
        if ($qr_png_data) {
            $qr_img_src = '@' . base64_encode($qr_png_data);
        }
    } catch (\Throwable $e) {}
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
<style>
body {
    font-family: 'dejavusans', 'Helvetica', 'Arial', sans-serif;
    font-size: 8.5px;
    color: #111;
    line-height: 1.3;
}
table {
    border-collapse: collapse;
}
.b-all { border: 1.5px solid #111; }
.b-top-none { border-top: none; }
.b-bottom-none { border-bottom: none; }
.b-r-heavy { border-right: 1.5px solid #111; }
.b-r-light { border-right: 1px solid #111; }
.b-b-light { border-bottom: 1px solid #111; }
.b-t-light { border-top: 1px solid #111; }
.b-l-light { border-left: 1px solid #111; }
.cell-pad { padding: 3px 5px; }
.cell-pad-sm { padding: 2.5px 4px; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.text-bold { font-weight: bold; }
.c-label { font-size: 7.5px; color: #555; }
.c-val { font-size: 8.5px; font-weight: bold; }
</style>
</head>
<body>

<!-- TOP BAR: IRN & ACK INFO (LEFT), TITLE (CENTER), E-CHALLAN + QR CODE (RIGHT) -->
<table style="width: 100%; margin-bottom: 4px;">
  <tr>
    <!-- Left: IRN, Ack No, Ack Date -->
    <td style="width: 40%; font-size: 8px; line-height: 1.35; vertical-align: top;">
      <b>IRN &nbsp;&nbsp;&nbsp;&nbsp;:</b> <?php echo $delivery_challan_info->reference_number ? htmlspecialchars($delivery_challan_info->reference_number) : '-'; ?><br/>
      <b>Ack No. :</b> <?php echo htmlspecialchars($dc_label); ?><br/>
      <b>Ack Date:</b> <?php echo format_to_date($delivery_challan_info->challan_date, false); ?>
    </td>
    <!-- Center: Main Title -->
    <td style="width: 36%; text-align: center; vertical-align: top; padding-top: 4px;">
      <span style="font-size: 14px; font-weight: bold; letter-spacing: 0.5px;">Delivery Challan</span>
    </td>
    <!-- Right: e-Challan and QR Code centered directly underneath it -->
    <td style="width: 24%; text-align: center; vertical-align: top;">
      <span style="font-weight: bold; font-size: 10px;">e-Challan</span><br/>
      <?php if ($qr_img_src): ?>
        <div style="margin-top: 2px; margin-bottom: 2px;">
          <img src="<?php echo $qr_img_src; ?>" width="58" height="58" alt="QR Code" />
        </div>
      <?php endif; ?>
      <span style="font-size: 7px; color: #555;">Original for Consignee</span>
    </td>
  </tr>
</table>

<!-- MAIN 2-COLUMN HEADER BOX (3-column table with 52% | 24% | 24%) -->
<table class="b-all" style="width: 100%;">
  <!-- Row 1: DC No & Dated -->
  <tr>
    <!-- Left Column: Consignor, Consignee, Buyer (spans 6 rows) -->
    <td rowspan="6" class="b-r-heavy cell-pad" style="width: 52%; vertical-align: top; padding: 0;">
      
      <!-- 1. Consignor / Company (with Logo) -->
      <div style="padding: 4px 6px; font-size: 8.5px; line-height: 1.35; border-bottom: 1px solid #111;">
        <?php if ($our_logo_html): ?>
          <div style="margin-bottom: 3px;"><?php echo $our_logo_html; ?></div>
        <?php endif; ?>
        <span style="font-size: 10px; font-weight: bold;"><?php echo htmlspecialchars($comp->name); ?></span><br/>
        <?php if ($comp->address) echo nl2br(htmlspecialchars($comp->address)) . '<br/>'; ?>
        GSTIN/UIN: <b><?php echo htmlspecialchars($comp_gst ? $comp_gst : '-'); ?></b><br/>
        State Name : <?php echo htmlspecialchars($comp_state); ?>, Code : <?php echo htmlspecialchars($comp_state_code); ?><br/>
        E-Mail : <?php echo htmlspecialchars($comp->email ? $comp->email : '-'); ?>
      </div>

      <!-- 2. Consignee (Ship to) -->
      <div style="padding: 4px 6px; font-size: 8.5px; line-height: 1.35; border-bottom: 1px solid #111;">
        <span class="c-label">Consignee (Ship to)</span><br/>
        <span style="font-size: 10px; font-weight: bold;"><?php echo htmlspecialchars($client_info->company_name); ?></span><br/>
        <?php if ($client_info->address) echo nl2br(htmlspecialchars($client_info->address)) . '<br/>'; ?>
        <?php if ($client_city_pin_str) echo htmlspecialchars($client_city_pin_str) . '<br/>'; ?>
        GSTIN/UIN : <b><?php echo htmlspecialchars($client_gst ? $client_gst : '-'); ?></b><br/>
        State Name : <?php echo htmlspecialchars($client_state); ?>, Code : <?php echo htmlspecialchars($client_state_code); ?>
      </div>

      <!-- 3. Buyer (Bill to) -->
      <div style="padding: 4px 6px; font-size: 8.5px; line-height: 1.35;">
        <span class="c-label">Buyer (Bill to)</span><br/>
        <span style="font-size: 10px; font-weight: bold;"><?php echo htmlspecialchars($client_info->company_name); ?></span><br/>
        <?php if ($client_info->address) echo nl2br(htmlspecialchars($client_info->address)) . '<br/>'; ?>
        <?php if ($client_city_pin_str) echo htmlspecialchars($client_city_pin_str) . '<br/>'; ?>
        GSTIN/UIN : <b><?php echo htmlspecialchars($client_gst ? $client_gst : '-'); ?></b><br/>
        State Name : <?php echo htmlspecialchars($client_state); ?>, Code : <?php echo htmlspecialchars($client_state_code); ?>
      </div>

    </td>

    <!-- Right Row 1: Delivery Challan No & Dated -->
    <td class="b-r-light b-b-light cell-pad" style="width: 24%; vertical-align: top;">
      <span class="c-label">Delivery Challan No.</span><br/>
      <span class="c-val"><?php echo htmlspecialchars($dc_label); ?></span>
    </td>
    <td class="b-b-light cell-pad" style="width: 24%; vertical-align: top;">
      <span class="c-label">Dated</span><br/>
      <span class="c-val"><?php echo format_to_date($delivery_challan_info->challan_date, false); ?></span>
    </td>
  </tr>

  <!-- Right Row 2: Delivery Note & Terms of Payment -->
  <tr>
    <td class="b-r-light b-b-light cell-pad" style="width: 24%; vertical-align: top;">
      <span class="c-label">Delivery Note</span><br/>
      <span class="c-val"><?php echo htmlspecialchars($delivery_challan_info->reference_number ? $delivery_challan_info->reference_number : '-'); ?></span>
    </td>
    <td class="b-b-light cell-pad" style="width: 24%; vertical-align: top;">
      <span class="c-label">Mode/Terms of Payment</span><br/>
      <span class="c-val">-</span>
    </td>
  </tr>

  <!-- Right Row 3: Buyer's Order No & Dated -->
  <tr>
    <td class="b-r-light b-b-light cell-pad" style="width: 24%; vertical-align: top;">
      <span class="c-label">Buyer's Order No.</span><br/>
      <span class="c-val"><?php echo htmlspecialchars($delivery_challan_info->reference_number ? $delivery_challan_info->reference_number : '-'); ?></span>
    </td>
    <td class="b-b-light cell-pad" style="width: 24%; vertical-align: top;">
      <span class="c-label">Dated</span><br/>
      <span class="c-val"><?php echo $delivery_challan_info->reference_date ? format_to_date($delivery_challan_info->reference_date, false) : '-'; ?></span>
    </td>
  </tr>

  <!-- Right Row 4: Dispatch Doc No & Delivery Note Date -->
  <tr>
    <td class="b-r-light b-b-light cell-pad" style="width: 24%; vertical-align: top;">
      <span class="c-label">Dispatch Doc No.</span><br/>
      <span class="c-val"><?php echo htmlspecialchars($dc_label); ?></span>
    </td>
    <td class="b-b-light cell-pad" style="width: 24%; vertical-align: top;">
      <span class="c-label">Delivery Note Date</span><br/>
      <span class="c-val"><?php echo $delivery_challan_info->delivery_date ? format_to_date($delivery_challan_info->delivery_date, false) : format_to_date($delivery_challan_info->challan_date, false); ?></span>
    </td>
  </tr>

  <!-- Right Row 5: Dispatched through & Destination -->
  <tr>
    <td class="b-r-light b-b-light cell-pad" style="width: 24%; vertical-align: top;">
      <span class="c-label">Dispatched through</span><br/>
      <span class="c-val"><?php echo htmlspecialchars($delivery_challan_info->destination ? $delivery_challan_info->destination : '-'); ?></span>
    </td>
    <td class="b-b-light cell-pad" style="width: 24%; vertical-align: top;">
      <span class="c-label">Destination</span><br/>
      <span class="c-val"><?php echo htmlspecialchars($delivery_challan_info->destination ? $delivery_challan_info->destination : ($client_info->city ? $client_info->city : '-')); ?></span>
    </td>
  </tr>

  <!-- Right Row 6: Terms of Delivery -->
  <tr>
    <td colspan="2" class="cell-pad" style="width: 48%; vertical-align: top;">
      <span class="c-label">Terms of Delivery</span><br/>
      <span style="font-size: 8px; color: #222;">
        <?php echo $delivery_challan_info->destination ? htmlspecialchars($delivery_challan_info->destination) : ($client_info->address ? htmlspecialchars($client_info->address) : '-'); ?>
      </span>
    </td>
  </tr>
</table>

<!-- ITEMS TABLE (Columns: Sl No 5%, Description 45%, HSN/SAC 12%, Quantity 10%, Rate 10%, per 6%, Amount 12% = 100%) -->
<table class="b-all b-top-none" style="width: 100%;">
  <!-- Header Row -->
  <tr style="background: #f7f7f7; font-weight: bold;">
    <td class="b-r-light b-b-light cell-pad-sm text-center" style="width: 5%;">Sl<br/>No</td>
    <td class="b-r-light b-b-light cell-pad-sm" style="width: 45%;">Description of Goods</td>
    <td class="b-r-light b-b-light cell-pad-sm text-center" style="width: 12%;">HSN/SAC</td>
    <td class="b-r-light b-b-light cell-pad-sm text-center" style="width: 10%;">Quantity</td>
    <td class="b-r-light b-b-light cell-pad-sm text-center" style="width: 10%;">Rate</td>
    <td class="b-r-light b-b-light cell-pad-sm text-center" style="width: 6%;">per</td>
    <td class="b-b-light cell-pad-sm text-center" style="width: 12%;">Amount</td>
  </tr>

  <!-- Item Rows -->
  <?php 
  $sl_no = 1;
  foreach ($delivery_challan_items as $item): 
      $qty = (float) $item->quantity;
      $rate = (float) (isset($item->rate) ? $item->rate : 0);
      $item_total = (float) (isset($item->total) && $item->total > 0 ? $item->total : $qty * $rate);
      $unit = $item->unit_type ? $item->unit_type : 'pcs';
  ?>
  <tr>
    <td class="b-r-light cell-pad-sm text-center" style="width: 5%; vertical-align: top; padding-top: 4px;">
      <?php echo $sl_no++; ?>
    </td>
    <td class="b-r-light cell-pad-sm" style="width: 45%; vertical-align: top; padding-top: 4px;">
      <b style="font-size: 9.5px;"><?php echo htmlspecialchars($item->title); ?></b>
      <?php if ($item->description): ?>
        <br/><span style="font-size: 8px; color: #444;"><?php echo nl2br(htmlspecialchars($item->description)); ?></span>
      <?php endif; ?>
    </td>
    <td class="b-r-light cell-pad-sm text-center" style="width: 12%; vertical-align: top; padding-top: 4px;">
      <?php echo htmlspecialchars($item->hsn_sac_code ? $item->hsn_sac_code : '-'); ?>
    </td>
    <td class="b-r-light cell-pad-sm text-center" style="width: 10%; vertical-align: top; padding-top: 4px; font-weight: bold;">
      <?php echo to_decimal_format($qty) . ' ' . htmlspecialchars($unit); ?>
    </td>
    <td class="b-r-light cell-pad-sm text-right" style="width: 10%; vertical-align: top; padding-top: 4px;">
      <?php echo ($rate > 0) ? to_decimal_format($rate) : '-'; ?>
    </td>
    <td class="b-r-light cell-pad-sm text-center" style="width: 6%; vertical-align: top; padding-top: 4px;">
      <?php echo htmlspecialchars($unit); ?>
    </td>
    <td class="cell-pad-sm text-right" style="width: 12%; vertical-align: top; padding-top: 4px; font-weight: bold;">
      <?php echo ($item_total > 0) ? to_decimal_format($item_total) : '-'; ?>
    </td>
  </tr>
  <?php endforeach; ?>

  <!-- CGST & SGST Lines inside item table like Tally e-invoice -->
  <?php if ($has_rates && $cgst_total > 0): ?>
  <tr>
    <td class="b-r-light" style="width: 5%;"></td>
    <td class="b-r-light cell-pad-sm text-right" style="width: 45%;">
      <i><b>OUTPUT CGST</b></i>
    </td>
    <td class="b-r-light" style="width: 12%;"></td>
    <td class="b-r-light" style="width: 10%;"></td>
    <td class="b-r-light" style="width: 10%;"></td>
    <td class="b-r-light" style="width: 6%;"></td>
    <td class="cell-pad-sm text-right" style="width: 12%; font-weight: bold;">
      <?php echo to_decimal_format($cgst_total); ?>
    </td>
  </tr>
  <tr>
    <td class="b-r-light" style="width: 5%;"></td>
    <td class="b-r-light cell-pad-sm text-right" style="width: 45%;">
      <i><b>OUTPUT SGST</b></i>
    </td>
    <td class="b-r-light" style="width: 12%;"></td>
    <td class="b-r-light" style="width: 10%;"></td>
    <td class="b-r-light" style="width: 10%;"></td>
    <td class="b-r-light" style="width: 6%;"></td>
    <td class="cell-pad-sm text-right" style="width: 12%; font-weight: bold;">
      <?php echo to_decimal_format($sgst_total); ?>
    </td>
  </tr>
  <?php endif; ?>

  <!-- Vertical grid extension spacer to give standard invoice body height -->
  <tr>
    <td class="b-r-light" style="width: 5%; height: 50px;"></td>
    <td class="b-r-light" style="width: 45%;"></td>
    <td class="b-r-light" style="width: 12%;"></td>
    <td class="b-r-light" style="width: 10%;"></td>
    <td class="b-r-light" style="width: 10%;"></td>
    <td class="b-r-light" style="width: 6%;"></td>
    <td style="width: 12%;"></td>
  </tr>

  <!-- Total Row -->
  <tr style="font-weight: bold; background: #fff;">
    <td class="b-t-light b-r-light cell-pad-sm text-right" colspan="3" style="width: 62%;">
      Total
    </td>
    <td class="b-t-light b-r-light cell-pad-sm text-center" style="width: 10%;">
      <?php echo to_decimal_format($total_qty) . ' pcs'; ?>
    </td>
    <td class="b-t-light b-r-light" style="width: 10%;"></td>
    <td class="b-t-light b-r-light" style="width: 6%;"></td>
    <td class="b-t-light cell-pad-sm text-right" style="width: 12%; font-size: 9.5px;">
      <?php echo ($has_rates ? '₹ ' . to_decimal_format($grand_total) : '-'); ?>
    </td>
  </tr>
</table>

<!-- AMOUNT CHARGEABLE IN WORDS BANNER -->
<table class="b-all b-top-none" style="width: 100%;">
  <tr>
    <td class="cell-pad" style="width: 85%; font-size: 8px;">
      <span class="c-label">Amount Chargeable (in words)</span><br/>
      <b style="font-size: 8.5px;">Indian Rupees <?php echo $amount_in_words; ?></b>
    </td>
    <td class="cell-pad text-right" style="width: 15%; font-size: 8px; vertical-align: top;">
      <i>E. &amp; O.E</i>
    </td>
  </tr>
</table>

<?php if ($is_intra_state) { ?>
<!-- GST / HSN TAX BREAKDOWN TABLE - Intra-state (CGST + SGST) -->
<table class="b-all b-top-none" style="width: 100%;">
  <!-- Header Row 1 -->
  <tr style="background: #f7f7f7; font-weight: bold; text-align: center; font-size: 7.5px;">
    <td rowspan="2" class="b-r-light b-b-light cell-pad-sm" style="width: 30%;">HSN/SAC</td>
    <td rowspan="2" class="b-r-light b-b-light cell-pad-sm" style="width: 16%;">Taxable<br/>Value</td>
    <td colspan="2" class="b-r-light b-b-light cell-pad-sm" style="width: 22%;">Central Tax</td>
    <td colspan="2" class="b-r-light b-b-light cell-pad-sm" style="width: 22%;">State Tax</td>
    <td rowspan="2" class="b-b-light cell-pad-sm" style="width: 10%;">Total<br/>Tax Amount</td>
  </tr>
  <!-- Header Row 2 -->
  <tr style="background: #f7f7f7; font-weight: bold; text-align: center; font-size: 7.5px;">
    <td class="b-r-light b-b-light cell-pad-sm" style="width: 8%;">Rate</td>
    <td class="b-r-light b-b-light cell-pad-sm" style="width: 14%;">Amount</td>
    <td class="b-r-light b-b-light cell-pad-sm" style="width: 8%;">Rate</td>
    <td class="b-r-light b-b-light cell-pad-sm" style="width: 14%;">Amount</td>
  </tr>

  <!-- HSN Data Rows -->
  <?php 
  $cgst_rate_str = ($cgst_rate > 0) ? (rtrim(rtrim(number_format($cgst_rate, 2, '.', ''), '0'), '.') . '%') : '0%';
  $sgst_rate_str = ($sgst_rate > 0) ? (rtrim(rtrim(number_format($sgst_rate, 2, '.', ''), '0'), '.') . '%') : '0%';
  foreach ($hsn_summary as $hsn_code => $hsn_val): 
      $c_tax = round($hsn_val * ($cgst_rate / 100), 2);
      $s_tax = round($hsn_val * ($sgst_rate / 100), 2);
      $t_tax = $c_tax + $s_tax;
  ?>
  <tr style="font-size: 8px;">
    <td class="b-r-light cell-pad-sm" style="width: 30%;"><?php echo htmlspecialchars($hsn_code); ?></td>
    <td class="b-r-light cell-pad-sm text-right" style="width: 16%;"><?php echo ($hsn_val > 0) ? to_decimal_format($hsn_val) : '-'; ?></td>
    <td class="b-r-light cell-pad-sm text-center" style="width: 8%;"><?php echo ($hsn_val > 0 && $cgst_rate > 0) ? $cgst_rate_str : '-'; ?></td>
    <td class="b-r-light cell-pad-sm text-right" style="width: 14%;"><?php echo ($c_tax > 0) ? to_decimal_format($c_tax) : '-'; ?></td>
    <td class="b-r-light cell-pad-sm text-center" style="width: 8%;"><?php echo ($hsn_val > 0 && $sgst_rate > 0) ? $sgst_rate_str : '-'; ?></td>
    <td class="b-r-light cell-pad-sm text-right" style="width: 14%;"><?php echo ($s_tax > 0) ? to_decimal_format($s_tax) : '-'; ?></td>
    <td class="cell-pad-sm text-right" style="width: 10%;"><?php echo ($t_tax > 0) ? to_decimal_format($t_tax) : '-'; ?></td>
  </tr>
  <?php endforeach; ?>

  <!-- HSN Total Row -->
  <tr style="font-weight: bold; background: #fff; font-size: 8px;">
    <td class="b-t-light b-r-light cell-pad-sm text-right" style="width: 30%;">Total</td>
    <td class="b-t-light b-r-light cell-pad-sm text-right" style="width: 16%;"><?php echo ($total_taxable > 0) ? to_decimal_format($total_taxable) : '-'; ?></td>
    <td class="b-t-light b-r-light" style="width: 8%;"></td>
    <td class="b-t-light b-r-light cell-pad-sm text-right" style="width: 14%;"><?php echo ($cgst_total > 0) ? to_decimal_format($cgst_total) : '-'; ?></td>
    <td class="b-t-light b-r-light" style="width: 8%;"></td>
    <td class="b-t-light b-r-light cell-pad-sm text-right" style="width: 14%;"><?php echo ($sgst_total > 0) ? to_decimal_format($sgst_total) : '-'; ?></td>
    <td class="b-t-light cell-pad-sm text-right" style="width: 10%;"><?php echo ($tax_total > 0) ? to_decimal_format($tax_total) : '-'; ?></td>
  </tr>
</table>
<?php } else { ?>
<!-- GST / HSN TAX BREAKDOWN TABLE - Inter-state (IGST) -->
<table class="b-all b-top-none" style="width: 100%;">
  <!-- Header Row 1 -->
  <tr style="background: #f7f7f7; font-weight: bold; text-align: center; font-size: 7.5px;">
    <td rowspan="2" class="b-r-light b-b-light cell-pad-sm" style="width: 36%;">HSN/SAC</td>
    <td rowspan="2" class="b-r-light b-b-light cell-pad-sm" style="width: 20%;">Taxable<br/>Value</td>
    <td colspan="2" class="b-r-light b-b-light cell-pad-sm" style="width: 30%;">Integrated Tax (IGST)</td>
    <td rowspan="2" class="b-b-light cell-pad-sm" style="width: 14%;">Total<br/>Tax Amount</td>
  </tr>
  <!-- Header Row 2 -->
  <tr style="background: #f7f7f7; font-weight: bold; text-align: center; font-size: 7.5px;">
    <td class="b-r-light b-b-light cell-pad-sm" style="width: 12%;">Rate</td>
    <td class="b-r-light b-b-light cell-pad-sm" style="width: 18%;">Amount</td>
  </tr>

  <!-- HSN Data Rows -->
  <?php 
  $igst_rate_str = ($igst_rate > 0) ? (rtrim(rtrim(number_format($igst_rate, 2, '.', ''), '0'), '.') . '%') : '0%';
  foreach ($hsn_summary as $hsn_code => $hsn_val): 
      $i_tax = round($hsn_val * ($igst_rate / 100), 2);
  ?>
  <tr style="font-size: 8px;">
    <td class="b-r-light cell-pad-sm" style="width: 36%;"><?php echo htmlspecialchars($hsn_code); ?></td>
    <td class="b-r-light cell-pad-sm text-right" style="width: 20%;"><?php echo ($hsn_val > 0) ? to_decimal_format($hsn_val) : '-'; ?></td>
    <td class="b-r-light cell-pad-sm text-center" style="width: 12%;"><?php echo ($hsn_val > 0 && $igst_rate > 0) ? $igst_rate_str : '-'; ?></td>
    <td class="b-r-light cell-pad-sm text-right" style="width: 18%;"><?php echo ($i_tax > 0) ? to_decimal_format($i_tax) : '-'; ?></td>
    <td class="cell-pad-sm text-right" style="width: 14%;"><?php echo ($i_tax > 0) ? to_decimal_format($i_tax) : '-'; ?></td>
  </tr>
  <?php endforeach; ?>

  <!-- HSN Total Row -->
  <tr style="font-weight: bold; background: #fff; font-size: 8px;">
    <td class="b-t-light b-r-light cell-pad-sm text-right" style="width: 36%;">Total</td>
    <td class="b-t-light b-r-light cell-pad-sm text-right" style="width: 20%;"><?php echo ($total_taxable > 0) ? to_decimal_format($total_taxable) : '-'; ?></td>
    <td class="b-t-light b-r-light" style="width: 12%;"></td>
    <td class="b-t-light b-r-light cell-pad-sm text-right" style="width: 18%;"><?php echo ($igst_total > 0) ? to_decimal_format($igst_total) : '-'; ?></td>
    <td class="b-t-light cell-pad-sm text-right" style="width: 14%;"><?php echo ($tax_total > 0) ? to_decimal_format($tax_total) : '-'; ?></td>
  </tr>
</table>
<?php } ?>

<!-- TAX AMOUNT IN WORDS -->
<table class="b-all b-top-none" style="width: 100%;">
  <tr>
    <td class="cell-pad" style="font-size: 8px;">
      Tax Amount (in words) : <b>Indian Rupees <?php echo $tax_in_words; ?></b>
    </td>
  </tr>
</table>

<!-- BOTTOM BOX: PAN & DECLARATION (LEFT 52%) + BANK DETAILS & SIGNATURE (RIGHT 48%) -->
<table class="b-all b-top-none" style="width: 100%;">
  <tr>
    <!-- Left: PAN & Declaration -->
    <td class="b-r-heavy cell-pad" style="width: 52%; vertical-align: top; line-height: 1.35; padding: 5px 6px;">
      Company's PAN &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <b><?php echo htmlspecialchars($comp_pan ? $comp_pan : '-'); ?></b><br/><br/>
      <u>Declaration</u><br/>
      <span style="font-size: 7.5px; color: #333;">We declare that this delivery challan shows the actual quantity and particulars of the goods described and that all particulars are true and correct.</span>
    </td>

    <!-- Right: Bank Details & Authorised Signatory -->
    <td class="cell-pad" style="width: 48%; vertical-align: top; line-height: 1.35; padding: 5px 6px;">
      <b>Company's Bank Details</b><br/>
      Bank Name &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?php echo htmlspecialchars($comp_bank_name); ?><br/>
      A/c No. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?php echo htmlspecialchars($comp_acc_no); ?><br/>
      Branch &amp; IFS Code : <?php echo htmlspecialchars($comp_branch_ifsc); ?><br/>

      <div style="border-top: 1px solid #111; margin-top: 4px; padding-top: 2px; text-align: right;">
        <span style="font-size: 8px;">for <b><?php echo htmlspecialchars($comp->name); ?></b></span><br/>
        <div style="height: 30px; text-align: right; padding: 1px 0;">
          <?php if ($sig_html): ?>
            <?php echo $sig_html; ?>
          <?php else: ?>
            <div style="height: 28px;"></div>
          <?php endif; ?>
        </div>
        <b>Authorised Signatory</b>
      </div>
    </td>
  </tr>
</table>

<!-- FOOTER NOTE -->
<div style="text-align: center; font-size: 7.5px; color: #555; margin-top: 3px;">
  This is a Computer Generated Delivery Challan
</div>

</body>
</html>
