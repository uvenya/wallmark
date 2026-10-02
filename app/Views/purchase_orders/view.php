<div class="page-content clearfix">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="page-title no-bg clearfix mb5 no-border">
                    <h1 class="pl0">
                        <span><i data-feather="file-text" class='icon'></i></span>
                        <?php echo get_purchase_order_id($purchase_order_info->id); ?>
                    </h1>

                    <div class="title-button-group mr0">
                        <span class="dropdown inline-block mt15">
                            <button class="btn btn-info text-white dropdown-toggle caret mt0 mb0" type="button" data-bs-toggle="dropdown" aria-expanded="true">
                                <i data-feather="tool" class="icon-16"></i> <?php echo app_lang('actions'); ?>
                            </button>
                            <ul class="dropdown-menu" role="menu">
                                <li role="presentation"><?php echo anchor(get_uri("purchase_orders/download_pdf/" . $purchase_order_info->id), "<i data-feather='download' class='icon-16'></i> " . app_lang('download_pdf'), array("title" => app_lang('download_pdf'), "class" => "dropdown-item")); ?> </li>
                                <li role="presentation"><?php echo anchor(get_uri("purchase_orders/download_pdf/" . $purchase_order_info->id . "/view"), "<i data-feather='file-text' class='icon-16'></i> " . app_lang('view_pdf'), array("title" => app_lang('view_pdf'), "target" => "_blank", "class" => "dropdown-item")); ?> </li>
                                <li role="presentation"><?php echo js_anchor("<i data-feather='printer' class='icon-16'></i> " . app_lang('print_purchase_order'), array('title' => app_lang('print_purchase_order'), 'id' => 'print-po-btn', "class" => "dropdown-item")); ?> </li>
                                <li role="presentation" class="dropdown-divider"></li>
                                <li role="presentation"><?php echo modal_anchor(get_uri("purchase_orders/modal_form"), "<i data-feather='edit' class='icon-16'></i> " . app_lang('edit_purchase_order'), array("title" => app_lang('edit_purchase_order'), "data-post-id" => $purchase_order_info->id, "class" => "dropdown-item")); ?> </li>
                                <li role="presentation"><?php echo modal_anchor(get_uri("purchase_orders/modal_form"), "<i data-feather='copy' class='icon-16'></i> Clone Purchase Order", array("data-post-is_clone" => true, "data-post-id" => $purchase_order_info->id, "title" => "Clone Purchase Order", "class" => "dropdown-item")); ?></li>
                                <li role="presentation" class="dropdown-divider"></li>
                                <li role="presentation"><?php echo ajax_anchor(get_uri("purchase_orders/update_status/" . $purchase_order_info->id . "/accepted"), "<i data-feather='check-circle' class='icon-16'></i> " . app_lang('mark_as_accepted'), array("data-reload-on-success" => "1", "class" => "dropdown-item")); ?> </li>
                                <li role="presentation"><?php echo ajax_anchor(get_uri("purchase_orders/update_status/" . $purchase_order_info->id . "/declined"), "<i data-feather='x-circle' class='icon-16'></i> " . app_lang('mark_as_rejected'), array("data-reload-on-success" => "1", "class" => "dropdown-item")); ?> </li>
                                <li role="presentation"><?php echo ajax_anchor(get_uri("purchase_orders/update_status/" . $purchase_order_info->id . "/sent"), "<i data-feather='send' class='icon-16'></i> " . app_lang('mark_as_sent'), array("data-reload-on-success" => "1", "class" => "dropdown-item")); ?> </li>
                            </ul>
                        </span>
                    </div>
                </div>

                <div class="card p15">
                    <?php
                    $Company_model = model('App\Models\Company_model');
                    $comp = $Company_model->get_one_where(array("id" => $purchase_order_info->company_id ? $purchase_order_info->company_id : get_default_company_id(), "deleted" => 0));
                    if (!$comp->id) {
                        $comp = $Company_model->get_one_where(array("is_default" => true, "deleted" => 0));
                    }
                    ?>
                    <div class="row mb20">
                        <div class="col-md-4">
                            <div class="b-b pb10 mb10"><strong>FROM (BUYER)</strong></div>
                            <strong><?php echo htmlspecialchars($comp->name); ?></strong><br />
                            <?php if ($comp->address) echo nl2br(htmlspecialchars($comp->address)) . "<br/>"; ?>
                            <?php if ($comp->phone) echo "<b>Ph:</b> " . htmlspecialchars($comp->phone) . "<br/>"; ?>
                            <?php if ($comp->email) echo "<b>Email:</b> " . htmlspecialchars($comp->email) . "<br/>"; ?>
                            <?php if ($comp->gst_number) echo "<b>GSTIN:</b> " . htmlspecialchars($comp->gst_number) . "<br/>"; ?>
                        </div>
                        <div class="col-md-4">
                            <div class="b-b pb10 mb10"><strong>TO (SUPPLIER / VENDOR / CLIENT)</strong></div>
                            <strong><?php echo htmlspecialchars($client_info->company_name); ?></strong><br />
                            <?php if ($client_info->address) echo nl2br(htmlspecialchars($client_info->address)) . "<br/>"; ?>
                            <?php if ($client_info->city) echo htmlspecialchars($client_info->city) . ", "; ?>
                            <?php if ($client_info->state) echo htmlspecialchars($client_info->state) . " "; ?>
                            <?php if ($client_info->zip) echo htmlspecialchars($client_info->zip) . "<br/>"; ?>
                            <?php if ($client_info->gst_number) echo "<b>GSTIN:</b> " . htmlspecialchars($client_info->gst_number) . "<br/>"; ?>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="b-b pb10 mb10"><strong>PURCHASE ORDER DETAILS</strong></div>
                            <b>Date:</b> <?php echo format_to_date($purchase_order_info->purchase_order_date, false); ?><br />
                            <?php if ($purchase_order_info->valid_until) { ?>
                                <b>Delivery Date / Valid Until:</b> <?php echo format_to_date($purchase_order_info->valid_until, false); ?><br />
                            <?php } ?>
                            <?php if ($purchase_order_info->reference_number) { ?>
                                <b>Reference No:</b> <?php echo htmlspecialchars($purchase_order_info->reference_number); ?><br />
                            <?php } ?>
                            <?php if ($purchase_order_info->reference_date) { ?>
                                <b>Reference Date:</b> <?php echo format_to_date($purchase_order_info->reference_date, false); ?><br />
                            <?php } ?>
                            <b>Status:</b> <span class="badge bg-secondary"><?php echo ucfirst($purchase_order_info->status); ?></span>
                        </div>
                    </div>

                    <?php if ($purchase_order_info->delivery_info) { ?>
                        <div class="alert alert-info mb20">
                            <strong><?php echo app_lang('delivery_info'); ?>:</strong><br />
                            <?php echo nl2br(htmlspecialchars($purchase_order_info->delivery_info)); ?>
                        </div>
                    <?php } ?>

                    <div class="table-responsive mt15">
                        <table id="purchase-order-item-table" class="display" width="100%">            
                        </table>
                    </div>

                    <div class="clearfix">
                        <div class="float-start mt20 mb20">
                            <?php echo modal_anchor(get_uri("purchase_orders/item_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array("class" => "btn btn-primary text-white", "title" => app_lang('add_item'), "data-post-purchase_order_id" => $purchase_order_info->id)); ?>
                        </div>
                        <div class="float-end pr15" id="purchase-order-total-section">
                            <?php echo view("purchase_orders/purchase_order_total_section"); ?>
                        </div>
                    </div>

                    <?php if ($purchase_order_info->note) { ?>
                        <div class="mt20 p15 bg-light rounded">
                            <strong><?php echo app_lang('note'); ?>:</strong><br />
                            <?php echo nl2br(htmlspecialchars($purchase_order_info->note)); ?>
                        </div>
                    <?php } ?>

                    <?php if ($purchase_order_info->terms_conditions) { ?>
                        <div class="mt15 p15 bg-light rounded">
                            <strong><?php echo app_lang('terms_conditions'); ?>:</strong><br />
                            <?php echo nl2br(htmlspecialchars($purchase_order_info->terms_conditions)); ?>
                        </div>
                    <?php } ?>
                </div>

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $("#purchase-order-item-table").appTable({
            source: '<?php echo_uri("purchase_orders/item_list_data/" . $purchase_order_info->id . "/") ?>',
            order: [[0, "asc"]],
            columns: [
                {visible: false, searchable: false},
                {title: "<?php echo app_lang("item") ?> ", "class": "w35p"},
                {title: "HSN / SAC", "class": "text-center w15p"},
                {title: "<?php echo app_lang("quantity") ?>", "class": "text-center w10p"},
                {title: "<?php echo app_lang("unit_type") ?>", "class": "text-center w10p"},
                {title: "<?php echo app_lang("rate") ?>", "class": "text-right w15p"},
                {title: "<?php echo app_lang("total") ?>", "class": "text-right w15p"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            onDeleteSuccess: function(result) {
                $("#purchase-order-total-section").html(result.purchase_order_total_view);
            },
            onUndoSuccess: function(result) {
                $("#purchase-order-total-section").html(result.purchase_order_total_view);
            }
        });

        $("#print-po-btn").click(function () {
            appLoader.show();
            $.ajax({
                url: "<?php echo get_uri('purchase_orders/print_purchase_order/' . $purchase_order_info->id); ?>",
                dataType: 'json',
                success: function (result) {
                    if (result.success) {
                        document.body.innerHTML = result.print_view;
                        $("html, body").addClass("dt-print-view");
                        setTimeout(function () {
                            window.print();
                        }, 200);
                    } else {
                        appAlert.error(result.message);
                    }
                    appLoader.hide();
                }
            });
        });
    });
</script>
