<div id="page-content" class="clearfix">
    <?php
    load_css(array(
        "assets/css/invoice.css",
    ));
    ?>

    <div class="invoice-preview print-invoice">
        <div class="invoice-preview-container bg-white mt15">
            <?php echo view("purchase_orders/purchase_order_pdf", get_defined_vars()); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("html, body").addClass("dt-print-view");
    });
</script>
