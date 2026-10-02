<div id="page-content" class="page-wrapper clearfix">
    <div class="card p15 no-border grid-button mb20">
        <div class="clearfix">
            <div class="float-start">
                <?php echo anchor("purchase_orders/view/" . $purchase_order_info->id, "<i data-feather='arrow-left' class='icon-16'></i> Back to Details", array("class" => "btn btn-default round mr10")); ?>
            </div>
            <div class="float-end">
                <?php echo anchor(get_uri("purchase_orders/download_pdf/" . $purchase_order_info->id), "<i data-feather='download' class='icon-16'></i> " . app_lang('download_pdf'), array("title" => app_lang('download_pdf'), "class" => "btn btn-default round mr10")); ?>
                <?php echo anchor(get_uri("purchase_orders/download_pdf/" . $purchase_order_info->id . "/view"), "<i data-feather='file-text' class='icon-16'></i> " . app_lang('view_pdf'), array("title" => app_lang('view_pdf'), "target" => "_blank", "class" => "btn btn-default round mr10")); ?>
                <?php echo js_anchor("<i data-feather='printer' class='icon-16'></i> " . app_lang('print_purchase_order'), array('title' => app_lang('print_purchase_order'), 'id' => 'print-po-preview-btn', "class" => "btn btn-default round")); ?>
            </div>
        </div>
    </div>

    <div class="invoice-preview-container bg-white p20 border rounded" style="max-width: 900px; margin: 0 auto; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
        <?php echo view("purchase_orders/purchase_order_pdf", get_defined_vars()); ?>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#print-po-preview-btn").click(function () {
            window.print();
        });
    });
</script>
