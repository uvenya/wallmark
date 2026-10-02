<div id="page-content" class="page-wrapper clearfix grid-button">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('delivery_challans'); ?></h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri("delivery_challans/modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_delivery_challan'), array("class" => "btn btn-default", "title" => app_lang('add_delivery_challan'))); ?>
            </div>
        </div>
        <div class="table-responsive">
            <table id="delivery-challan-table" class="display" cellspacing="0" width="100%">
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#delivery-challan-table").appTable({
            source: '<?php echo_uri("delivery_challans/list_data") ?>',
            order: [[0, "desc"]],
            smartFilterIdentity: "delivery_challans_list",
            rangeRadioButtons: [{name: "range_radio_button", selectedOption: 'monthly', options: ['monthly', 'yearly', 'custom', 'dynamic'], dynamicRanges: ['this_month', 'last_month', 'next_month', 'this_year', 'last_year']}],
            filterDropdown: [
                {name: "status", class: "w150", options: [
                    {id: "", text: "- <?php echo app_lang('status'); ?> -"},
                    {id: "draft",      text: "<?php echo app_lang('draft'); ?>"},
                    {id: "dispatched", text: "<?php echo app_lang('dispatched'); ?>"},
                    {id: "delivered",  text: "<?php echo app_lang('delivered'); ?>"},
                    {id: "cancelled",  text: "<?php echo app_lang('cancelled'); ?>"}
                ]}
            ],
            columns: [
                {visible: false, searchable: false},
                {title: "<?php echo app_lang('delivery_challan_number'); ?>", "class": "w15p all"},
                {title: "<?php echo app_lang('client'); ?> / <?php echo app_lang('lead'); ?>", "class": "w20p all"},
                {visible: false, searchable: false},
                {title: "<?php echo app_lang('challan_date'); ?>", "iDataSort": 3, "class": "w15p"},
                {visible: false, searchable: false},
                {title: "<?php echo app_lang('delivery_date'); ?>", "iDataSort": 5, "class": "w15p"},
                {title: "<?php echo app_lang('reference_number'); ?>", "class": "w15p"},
                {title: "<?php echo app_lang('destination'); ?>", "class": "w15p"},
                {title: "<?php echo app_lang('status'); ?>", "class": "w100 text-center"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            printColumns: [1, 2, 4, 6, 7, 8, 9],
            xlsColumns:   [1, 2, 4, 6, 7, 8, 9]
        });
    });
</script>
