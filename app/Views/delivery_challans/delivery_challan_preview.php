<div class="page-wrapper clearfix">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="page-title clearfix">
                    <div class="title-button-group">
                        <?php echo anchor("delivery_challans/view/" . $delivery_challan_info->id, "<i data-feather='arrow-left' class='icon-16'></i> Back to Details", array("class" => "btn btn-default round mr10")); ?>
                        <?php echo anchor(get_uri("delivery_challans/download_pdf/" . $delivery_challan_info->id), "<i data-feather='download' class='icon-16'></i> " . app_lang('download_pdf'), array("title" => app_lang('download_pdf'), "class" => "btn btn-default round mr10")); ?>
                        <?php echo anchor(get_uri("delivery_challans/download_pdf/" . $delivery_challan_info->id . "/view"), "<i data-feather='file-text' class='icon-16'></i> " . app_lang('view_pdf'), array("title" => app_lang('view_pdf'), "target" => "_blank", "class" => "btn btn-default round mr10")); ?>
                        <?php echo js_anchor("<i data-feather='printer' class='icon-16'></i> " . app_lang('print_delivery_challan'), array('title' => app_lang('print_delivery_challan'), 'id' => 'print-dc-preview-btn', "class" => "btn btn-default round")); ?>
                    </div>
                </div>
                <div class="card">
                    <?php echo view("delivery_challans/delivery_challan_pdf", get_defined_vars()); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#print-dc-preview-btn").click(function () {
            window.print();
        });
    });
</script>
