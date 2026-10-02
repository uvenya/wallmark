<div class="page-content clearfix">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="page-title no-bg clearfix mb5 no-border">
                    <h1 class="pl0">
                        <span><i data-feather="truck" class="icon"></i></span>
                        <?php echo get_delivery_challan_id($delivery_challan_info->id); ?>
                    </h1>

                    <div class="title-button-group mr0">
                        <span class="dropdown inline-block mt15">
                            <button class="btn btn-info text-white dropdown-toggle caret mt0 mb0" type="button" data-bs-toggle="dropdown" aria-expanded="true">
                                <i data-feather="tool" class="icon-16"></i> <?php echo app_lang('actions'); ?>
                            </button>
                            <ul class="dropdown-menu" role="menu">
                                <li role="presentation"><?php echo anchor(get_uri("delivery_challans/download_pdf/" . $delivery_challan_info->id), "<i data-feather='download' class='icon-16'></i> " . app_lang('download_pdf'), array("title" => app_lang('download_pdf'), "class" => "dropdown-item")); ?></li>
                                <li role="presentation"><?php echo anchor(get_uri("delivery_challans/download_pdf/" . $delivery_challan_info->id . "/view"), "<i data-feather='file-text' class='icon-16'></i> " . app_lang('view_pdf'), array("title" => app_lang('view_pdf'), "target" => "_blank", "class" => "dropdown-item")); ?></li>
                                <li role="presentation"><?php echo js_anchor("<i data-feather='printer' class='icon-16'></i> " . app_lang('print_delivery_challan'), array('title' => app_lang('print_delivery_challan'), 'id' => 'print-dc-btn', "class" => "dropdown-item")); ?></li>
                                <li role="presentation" class="dropdown-divider"></li>
                                <li role="presentation"><?php echo modal_anchor(get_uri("delivery_challans/modal_form"), "<i data-feather='edit' class='icon-16'></i> " . app_lang('edit_delivery_challan'), array("title" => app_lang('edit_delivery_challan'), "data-post-id" => $delivery_challan_info->id, "class" => "dropdown-item")); ?></li>
                                <li role="presentation"><?php echo modal_anchor(get_uri("delivery_challans/modal_form"), "<i data-feather='copy' class='icon-16'></i> Clone Delivery Challan", array("data-post-is_clone" => true, "data-post-id" => $delivery_challan_info->id, "title" => "Clone Delivery Challan", "class" => "dropdown-item")); ?></li>
                                <li role="presentation" class="dropdown-divider"></li>
                                <?php if ($delivery_challan_info->status !== "dispatched") { ?>
                                    <li role="presentation"><?php echo ajax_anchor(get_uri("delivery_challans/update_status/" . $delivery_challan_info->id . "/dispatched"), "<i data-feather='send' class='icon-16'></i> " . app_lang('mark_as_dispatched'), array("data-reload-on-success" => "1", "class" => "dropdown-item")); ?></li>
                                <?php } ?>
                                <?php if ($delivery_challan_info->status !== "delivered") { ?>
                                    <li role="presentation"><?php echo ajax_anchor(get_uri("delivery_challans/update_status/" . $delivery_challan_info->id . "/delivered"), "<i data-feather='check-circle' class='icon-16'></i> " . app_lang('mark_as_delivered'), array("data-reload-on-success" => "1", "class" => "dropdown-item")); ?></li>
                                <?php } ?>
                                <?php if ($delivery_challan_info->status !== "cancelled") { ?>
                                    <li role="presentation"><?php echo ajax_anchor(get_uri("delivery_challans/update_status/" . $delivery_challan_info->id . "/cancelled"), "<i data-feather='x-circle' class='icon-16'></i> " . app_lang('mark_as_cancelled'), array("data-reload-on-success" => "1", "class" => "dropdown-item")); ?></li>
                                <?php } ?>
                                <?php if ($delivery_challan_info->status !== "draft") { ?>
                                    <li role="presentation"><?php echo ajax_anchor(get_uri("delivery_challans/update_status/" . $delivery_challan_info->id . "/draft"), "<i data-feather='file' class='icon-16'></i> " . app_lang('mark_as_draft'), array("data-reload-on-success" => "1", "class" => "dropdown-item")); ?></li>
                                <?php } ?>
                            </ul>
                        </span>
                    </div>
                </div>

                <div class="card p15">
                    <?php
                    $Company_model = model('App\Models\Company_model');
                    $comp = $Company_model->get_one_where(array("id" => $delivery_challan_info->company_id ? $delivery_challan_info->company_id : get_default_company_id(), "deleted" => 0));
                    if (!$comp->id) {
                        $comp = $Company_model->get_one_where(array("is_default" => true, "deleted" => 0));
                    }
                    ?>

                    <!-- Header: Company / Client / Details -->
                    <div class="row mb20">
                        <div class="col-md-4">
                            <div class="b-b pb10 mb10"><strong>FROM (COMPANY)</strong></div>
                            <strong><?php echo htmlspecialchars($comp->name); ?></strong><br />
                            <?php if ($comp->address) echo nl2br(htmlspecialchars($comp->address)) . "<br/>"; ?>
                            <?php if ($comp->phone) echo "<b>Ph:</b> " . htmlspecialchars($comp->phone) . "<br/>"; ?>
                            <?php if ($comp->email) echo "<b>Email:</b> " . htmlspecialchars($comp->email) . "<br/>"; ?>
                            <?php if ($comp->gst_number) echo "<b>GSTIN:</b> " . htmlspecialchars($comp->gst_number) . "<br/>"; ?>
                        </div>
                        <div class="col-md-4">
                            <div class="b-b pb10 mb10"><strong>TO (CUSTOMER)</strong></div>
                            <strong><?php echo htmlspecialchars($client_info->company_name); ?></strong><br />
                            <?php if ($client_info->address) echo nl2br(htmlspecialchars($client_info->address)) . "<br/>"; ?>
                            <?php if ($client_info->city) echo htmlspecialchars($client_info->city) . ", "; ?>
                            <?php if ($client_info->state) echo htmlspecialchars($client_info->state) . " "; ?>
                            <?php if ($client_info->zip) echo htmlspecialchars($client_info->zip) . "<br/>"; ?>
                            <?php if ($client_info->gst_number) echo "<b>GSTIN:</b> " . htmlspecialchars($client_info->gst_number) . "<br/>"; ?>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="b-b pb10 mb10"><strong>DELIVERY CHALLAN DETAILS</strong></div>
                            <b>Challan No:</b> <?php echo get_delivery_challan_id($delivery_challan_info->id); ?><br/>
                            <b>Date:</b> <?php echo format_to_date($delivery_challan_info->challan_date, false); ?><br />
                            <?php if ($delivery_challan_info->delivery_date) { ?>
                                <b>Delivery Date:</b> <?php echo format_to_date($delivery_challan_info->delivery_date, false); ?><br />
                            <?php } ?>
                            <?php if ($delivery_challan_info->reference_number) { ?>
                                <b>Ref No:</b> <?php echo htmlspecialchars($delivery_challan_info->reference_number); ?><br />
                            <?php } ?>
                            <?php if ($delivery_challan_info->reference_date) { ?>
                                <b>Ref Date:</b> <?php echo format_to_date($delivery_challan_info->reference_date, false); ?><br />
                            <?php } ?>
                            <?php if ($delivery_challan_info->destination) { ?>
                                <b>Destination:</b> <?php echo htmlspecialchars($delivery_challan_info->destination); ?><br />
                            <?php } ?>
                            <b>Status:</b>
                            <?php
                            $sc = "bg-secondary";
                            if ($delivery_challan_info->status == "dispatched") $sc = "bg-primary";
                            else if ($delivery_challan_info->status == "delivered") $sc = "bg-success";
                            else if ($delivery_challan_info->status == "cancelled") $sc = "bg-danger";
                            ?>
                            <span class="badge <?php echo $sc; ?>"><?php echo ucfirst($delivery_challan_info->status); ?></span>
                        </div>
                    </div>

                    <!-- Delivery / Shipping Information -->
                    <?php if ($delivery_challan_info->delivery_info) { ?>
                        <div class="alert alert-info mb20">
                            <strong><?php echo app_lang('delivery_info'); ?>:</strong><br />
                            <?php echo nl2br(htmlspecialchars($delivery_challan_info->delivery_info)); ?>
                        </div>
                    <?php } ?>

                    <!-- Items Table -->
                    <div class="table-responsive mt15">
                        <table id="delivery-challan-item-table" class="display" width="100%">
                        </table>
                    </div>

                    <div class="clearfix">
                        <div class="float-start mt20 mb20">
                            <?php echo modal_anchor(get_uri("delivery_challans/item_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array("class" => "btn btn-primary text-white", "title" => app_lang('add_item'), "data-post-delivery_challan_id" => $delivery_challan_info->id)); ?>
                        </div>
                    </div>

                    <!-- Note -->
                    <?php if ($delivery_challan_info->note) { ?>
                        <div class="mt20 p15 bg-light rounded">
                            <strong><?php echo app_lang('note'); ?>:</strong><br />
                            <?php echo nl2br(htmlspecialchars($delivery_challan_info->note)); ?>
                        </div>
                    <?php } ?>

                    <!-- Authorized Signature -->
                    <?php
                    $sig_html = '';
                    if ($comp->signature) {
                        $sig_files = @unserialize($comp->signature);
                        if ($sig_files && is_array($sig_files)) {
                            $sig_item = reset($sig_files);
                            $sig_url  = get_source_url_of_file($sig_item, get_setting("system_file_path"));
                            if ($sig_url) {
                                $sig_html = '<img src="' . $sig_url . '" style="max-height:60px;max-width:200px;" alt="Signature"/>';
                            }
                        } else {
                            $sig_html = '<strong>' . htmlspecialchars($comp->signature) . '</strong>';
                        }
                    }
                    ?>
                    <div class="row mt20">
                        <div class="col-md-4 offset-md-8">
                            <div class="text-center p15" style="border:1px solid #ddd;border-radius:4px;">
                                <div style="min-height:60px;display:flex;align-items:center;justify-content:center;">
                                    <?php echo $sig_html ? $sig_html : '<span class="text-muted small">Authorized Signature</span>'; ?>
                                </div>
                                <div class="mt10" style="border-top:1px solid #999;padding-top:6px;font-size:12px;">
                                    For <?php echo htmlspecialchars($comp->name); ?> &mdash; Authorized Signatory
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#delivery-challan-item-table").appTable({
            source: '<?php echo_uri("delivery_challans/item_list_data/" . $delivery_challan_info->id . "/") ?>',
            order: [[0, "asc"]],
            columns: [
                {visible: false, searchable: false},
                {title: "<?php echo app_lang("item") ?> ", "class": "w40p"},
                {title: "HSN / SAC", "class": "text-center w15p"},
                {title: "<?php echo app_lang("quantity") ?>", "class": "text-center w10p"},
                {title: "<?php echo app_lang("unit_type") ?>", "class": "text-center w10p"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ]
        });

        $("#print-dc-btn").click(function () {
            appLoader.show();
            $.ajax({
                url: "<?php echo get_uri('delivery_challans/print_delivery_challan/' . $delivery_challan_info->id); ?>",
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
