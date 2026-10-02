<?php echo form_open(get_uri("purchase_orders/save"), array("id" => "purchase-order-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />

        <?php if ($is_clone) { ?>
            <input type="hidden" name="is_clone" value="1" />
        <?php } ?>

        <div class="form-group">
            <div class="row">
                <label for="purchase_order_date" class="col-md-3"><?php echo app_lang('purchase_order_date'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "purchase_order_date",
                        "name" => "purchase_order_date",
                        "value" => $model_info->purchase_order_date ? $model_info->purchase_order_date : get_my_local_time("Y-m-d"),
                        "class" => "form-control",
                        "placeholder" => app_lang('purchase_order_date'),
                        "autocomplete" => "off",
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="valid_until" class="col-md-3"><?php echo app_lang('valid_until'); ?> / Delivery Date</label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "valid_until",
                        "name" => "valid_until",
                        "value" => $model_info->valid_until,
                        "class" => "form-control",
                        "placeholder" => "Delivery Date / " . app_lang('valid_until'),
                        "autocomplete" => "off"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <?php if (count($companies_dropdown) > 1) { ?>
            <div class="form-group">
                <div class="row">
                    <label for="company_id" class="col-md-3"><?php echo app_lang('company'); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_input(array(
                            "id" => "company_id",
                            "name" => "company_id",
                            "value" => $model_info->company_id,
                            "class" => "form-control",
                            "placeholder" => app_lang('company')
                        ));
                        ?>
                    </div>
                </div>
            </div>
        <?php } ?>

        <?php if ($client_id) { ?>
            <input type="hidden" name="po_client_id" value="<?php echo $client_id; ?>" />
        <?php } else { ?>
            <div class="form-group">
                <div class="row">
                    <label for="po_client_id" class="col-md-3"><?php echo app_lang("client"); ?> / Vendor</label>
                    <div class="col-md-9">
                        <?php
                        echo form_dropdown("po_client_id", $clients_dropdown, array($model_info->client_id), "class='select2 validate-hidden' id='po_client_id' data-rule-required='true' data-msg-required='" . app_lang('field_required') . "'");
                        ?>
                    </div>
                </div>
            </div>
        <?php } ?>

        <div class="form-group">
            <div class="row">
                <label for="reference_number" class="col-md-3"><?php echo app_lang('reference_number'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "reference_number",
                        "name" => "reference_number",
                        "value" => $model_info->reference_number,
                        "class" => "form-control",
                        "placeholder" => "Ref. No. / Quotation Ref."
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="reference_date" class="col-md-3"><?php echo app_lang('reference_date'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "reference_date",
                        "name" => "reference_date",
                        "value" => $model_info->reference_date,
                        "class" => "form-control",
                        "placeholder" => "YYYY-MM-DD",
                        "autocomplete" => "off"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="delivery_info" class="col-md-3"><?php echo app_lang('delivery_info'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id" => "delivery_info",
                        "name" => "delivery_info",
                        "value" => $model_info->delivery_info,
                        "class" => "form-control",
                        "placeholder" => "Delivery address, dispatch instructions, terms of delivery...",
                        "rows" => "3"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="tax_id" class="col-md-3"><?php echo app_lang('tax'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_dropdown("tax_id", $taxes_dropdown, array(isset($selected_tax_id) ? $selected_tax_id : $model_info->tax_id), "class='select2 tax-select2' id='tax_id'");
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group <?php echo (isset($selected_tax_id) && $selected_tax_id === 'other') ? '' : 'hide'; ?>" id="custom-tax-group">
            <div class="row">
                <label for="custom_tax_percentage" class="col-md-3">CGST Percentage</label>
                <div class="col-md-9">
                    <div class="input-group" style="max-width: 220px;">
                        <?php
                        echo form_input(array(
                            "id" => "custom_tax_percentage",
                            "name" => "custom_tax_percentage",
                            "value" => isset($custom_tax_percentage) ? $custom_tax_percentage : "",
                            "class" => "form-control",
                            "type" => "number",
                            "step" => "0.01",
                            "min" => "0",
                            "max" => "100",
                            "placeholder" => "e.g. 9"
                        ));
                        ?>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="tax_id2" class="col-md-3"><?php echo app_lang('second_tax'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_dropdown("tax_id2", $taxes_dropdown, array(isset($selected_tax_id2) ? $selected_tax_id2 : $model_info->tax_id2), "class='select2 tax-select2' id='tax_id2'");
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group <?php echo (isset($selected_tax_id2) && $selected_tax_id2 === 'other') ? '' : 'hide'; ?>" id="custom-tax2-group">
            <div class="row">
                <label for="custom_tax_percentage2" class="col-md-3">SGST Percentage</label>
                <div class="col-md-9">
                    <div class="input-group" style="max-width: 220px;">
                        <?php
                        echo form_input(array(
                            "id" => "custom_tax_percentage2",
                            "name" => "custom_tax_percentage2",
                            "value" => isset($custom_tax_percentage2) ? $custom_tax_percentage2 : "",
                            "class" => "form-control",
                            "type" => "number",
                            "step" => "0.01",
                            "min" => "0",
                            "max" => "100",
                            "placeholder" => "e.g. 18"
                        ));
                        ?>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="po_note" class="col-md-3"><?php echo app_lang('note'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id" => "po_note",
                        "name" => "po_note",
                        "value" => $model_info->note ? $model_info->note : "",
                        "class" => "form-control",
                        "placeholder" => app_lang('note'),
                        "rows" => "3"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="po_terms_conditions" class="col-md-3"><?php echo app_lang('terms_conditions'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id" => "po_terms_conditions",
                        "name" => "po_terms_conditions",
                        "value" => $model_info->terms_conditions ? $model_info->terms_conditions : "",
                        "class" => "form-control",
                        "placeholder" => app_lang('terms_conditions'),
                        "rows" => "4"
                    ));
                    ?>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#purchase-order-form").appForm({
            onSuccess: function (result) {
                if (typeof RELOAD_VIEW_AFTER_UPDATE !== "undefined" && RELOAD_VIEW_AFTER_UPDATE) {
                    location.reload();
                } else {
                    window.location = "<?php echo site_url('purchase_orders/view'); ?>/" + result.id;
                }
            }
        });

        $("#purchase-order-form .tax-select2").select2();
        $("#po_client_id").select2();

        $("#tax_id").change(function () {
            if ($(this).val() === "other") {
                $("#custom-tax-group").removeClass("hide");
                $("#custom_tax_percentage").focus();
            } else {
                $("#custom-tax-group").addClass("hide");
            }
        });

        $("#tax_id2").change(function () {
            if ($(this).val() === "other") {
                $("#custom-tax2-group").removeClass("hide");
                $("#custom_tax_percentage2").focus();
            } else {
                $("#custom-tax2-group").addClass("hide");
            }
        });

        <?php if (count($companies_dropdown) > 1) { ?>
            $("#company_id").select2({data: <?php echo json_encode($companies_dropdown); ?>});
        <?php } ?>

        setDatePicker("#purchase_order_date, #valid_until, #reference_date");
    });
</script>
