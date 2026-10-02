<table id="purchase-order-total-table" class="table display dataTable text-right strong table-responsive">     
    <tr>
        <td><?php echo app_lang("sub_total"); ?></td>
        <td style="width: 120px;"><?php echo to_currency($purchase_order_total_summary->purchase_order_subtotal, $purchase_order_total_summary->currency_symbol); ?></td>
        <td style="width: 60px;"></td>
    </tr>

    <?php
    $discount_edit_btn = "<td class='text-center option w100'>" . modal_anchor(get_uri("purchase_orders/discount_modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "data-post-purchase_order_id" => $purchase_order_id, "title" => app_lang('edit_discount'))) . "</td>";

    $discount_row = "<tr>
                        <td style='padding-top:13px;'>" . app_lang("discount") . "</td>
                        <td style='padding-top:13px;'>" . to_currency($purchase_order_total_summary->discount_total, $purchase_order_total_summary->currency_symbol) . "</td>
                        $discount_edit_btn
                    </tr>";

    $total_after_discount_row = "<tr>
                                    <td>" . app_lang("total_after_discount") . "</td>
                                    <td style='width:120px;'>" . to_currency($purchase_order_total_summary->purchase_order_subtotal - $purchase_order_total_summary->discount_total, $purchase_order_total_summary->currency_symbol) . "</td>
                                    <td></td>
                                </tr>";

    if ($purchase_order_total_summary->purchase_order_subtotal && (!$purchase_order_total_summary->discount_total || ($purchase_order_total_summary->discount_total !== 0 && $purchase_order_total_summary->discount_type == "before_tax"))) {
        echo $discount_row;
        if ($purchase_order_total_summary->discount_total !== 0) {
            echo $total_after_discount_row;
        }
    }
    ?>

    <?php if ($purchase_order_total_summary->tax) { ?>
        <tr>
            <td><?php echo $purchase_order_total_summary->tax_name; ?></td>
            <td><?php echo to_currency($purchase_order_total_summary->tax, $purchase_order_total_summary->currency_symbol); ?></td>
            <td></td>
        </tr>
    <?php } ?>
    <?php if ($purchase_order_total_summary->tax2) { ?>
        <tr>
            <td><?php echo $purchase_order_total_summary->tax_name2; ?></td>
            <td><?php echo to_currency($purchase_order_total_summary->tax2, $purchase_order_total_summary->currency_symbol); ?></td>
            <td></td>
        </tr>
    <?php } ?>

    <?php
    if ($purchase_order_total_summary->discount_total && $purchase_order_total_summary->discount_type == "after_tax") {
        echo $discount_row;
    }
    ?>

    <tr>
        <td><?php echo app_lang("total"); ?></td>
        <td><?php echo to_currency($purchase_order_total_summary->purchase_order_total, $purchase_order_total_summary->currency_symbol); ?></td>
        <td></td>
    </tr>
</table>
