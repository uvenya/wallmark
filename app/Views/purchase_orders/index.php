<div id="page-content" class="page-wrapper clearfix grid-button">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('purchase_orders'); ?></h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri("purchase_orders/modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_purchase_order'), array("class" => "btn btn-default", "title" => app_lang('add_purchase_order'))); ?>
            </div>
        </div>
        <div class="table-responsive">
            <table id="purchase-order-table" class="display" cellspacing="0" width="100%">   
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#purchase-order-table").appTable({
            source: '<?php echo_uri("purchase_orders/list_data") ?>',
            order: [[0, "desc"]],
            smartFilterIdentity: "purchase_orders_list",
            rangeRadioButtons: [{name: "range_radio_button", selectedOption: 'monthly', options: ['monthly', 'yearly', 'custom', 'dynamic'], dynamicRanges:['this_month', 'last_month', 'next_month', 'this_year', 'last_year']}],
            filterDropdown: [
                {name: "status", class: "w150", options: [
                    {id: "", text: "- <?php echo app_lang('status'); ?> -"},
                    {id: "draft", text: "<?php echo app_lang('draft'); ?>"},
                    {id: "sent", text: "<?php echo app_lang('sent'); ?>"},
                    {id: "accepted", text: "<?php echo app_lang('accepted'); ?>"},
                    {id: "declined", text: "<?php echo app_lang('declined'); ?>"}
                ]}
            ],
            columns: [
                {visible: false, searchable: false},
                {title: "<?php echo app_lang("purchase_order_number"); ?>", "class": "w15p all"},
                {title: "<?php echo app_lang("client"); ?> / <?php echo app_lang("lead"); ?>", "class": "w20p all"},
                {visible: false, searchable: false},
                {title: "<?php echo app_lang("purchase_order_date"); ?>", "iDataSort": 3, "class": "w15p"},
                {visible: false, searchable: false},
                {title: "<?php echo app_lang("valid_until"); ?>", "iDataSort": 5, "class": "w15p"},
                {title: "<?php echo app_lang("reference_number"); ?>", "class": "w15p"},
                {title: "<?php echo app_lang("amount"); ?>", "class": "text-right w150"},
                {title: "<?php echo app_lang("status"); ?>", "class": "w100 text-center"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            printColumns: [1, 2, 4, 6, 7, 8, 9],
            xlsColumns: [1, 2, 4, 6, 7, 8, 9],
            summation: [{column: 8, dataType: 'currency', currencySymbol: AppHelper.settings.currencySymbol}]
        });
    });
</script>
