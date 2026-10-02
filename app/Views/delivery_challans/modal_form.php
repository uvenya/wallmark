<?php echo form_open(get_uri("delivery_challans/save"), array("id" => "delivery-challan-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />

        <?php if ($is_clone) { ?>
            <input type="hidden" name="is_clone" value="1" />
        <?php } ?>

        <!-- Challan Date -->
        <div class="form-group">
            <div class="row">
                <label for="challan_date" class="col-md-3"><?php echo app_lang('challan_date'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id"                 => "challan_date",
                        "name"               => "challan_date",
                        "value"              => $model_info->challan_date ? $model_info->challan_date : get_my_local_time("Y-m-d"),
                        "class"              => "form-control",
                        "placeholder"        => app_lang('challan_date'),
                        "autocomplete"       => "off",
                        "data-rule-required" => true,
                        "data-msg-required"  => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>

        <!-- Delivery Date -->
        <div class="form-group">
            <div class="row">
                <label for="delivery_date" class="col-md-3"><?php echo app_lang('delivery_date'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id"           => "delivery_date",
                        "name"         => "delivery_date",
                        "value"        => $model_info->delivery_date,
                        "class"        => "form-control",
                        "placeholder"  => app_lang('delivery_date'),
                        "autocomplete" => "off"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <!-- Company (if multiple companies) -->
        <?php if (count($companies_dropdown) > 1) { ?>
            <div class="form-group">
                <div class="row">
                    <label for="company_id" class="col-md-3"><?php echo app_lang('company'); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_input(array(
                            "id"          => "company_id",
                            "name"        => "company_id",
                            "value"       => $model_info->company_id,
                            "class"       => "form-control",
                            "placeholder" => app_lang('company')
                        ));
                        ?>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- Client / Lead -->
        <?php if ($client_id) { ?>
            <input type="hidden" name="dc_client_id" value="<?php echo $client_id; ?>" />
        <?php } else { ?>
            <div class="form-group">
                <div class="row">
                    <label for="dc_client_id" class="col-md-3"><?php echo app_lang("client"); ?> / <?php echo app_lang("lead"); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_dropdown("dc_client_id", $clients_dropdown, array($model_info->client_id), "class='select2 validate-hidden' id='dc_client_id' data-rule-required='true' data-msg-required='" . app_lang('field_required') . "'");
                        ?>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- Reference Number (PO Number / Invoice ref) -->
        <div class="form-group">
            <div class="row">
                <label for="reference_number" class="col-md-3"><?php echo app_lang('reference_number'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id"          => "reference_number",
                        "name"        => "reference_number",
                        "value"       => $model_info->reference_number,
                        "class"       => "form-control",
                        "placeholder" => "PO No. / Invoice No. / Reference"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <!-- Reference Date -->
        <div class="form-group">
            <div class="row">
                <label for="reference_date" class="col-md-3"><?php echo app_lang('reference_date'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id"           => "reference_date",
                        "name"         => "reference_date",
                        "value"        => $model_info->reference_date,
                        "class"        => "form-control",
                        "placeholder"  => "YYYY-MM-DD",
                        "autocomplete" => "off"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <!-- Destination -->
        <div class="form-group">
            <div class="row">
                <label for="destination" class="col-md-3"><?php echo app_lang('destination'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id"          => "destination",
                        "name"        => "destination",
                        "value"       => $model_info->destination,
                        "class"       => "form-control",
                        "placeholder" => app_lang('destination')
                    ));
                    ?>
                </div>
            </div>
        </div>

        <!-- Delivery / Shipping Information -->
        <div class="form-group">
            <div class="row">
                <label for="delivery_info" class="col-md-3"><?php echo app_lang('delivery_info'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id"          => "delivery_info",
                        "name"        => "delivery_info",
                        "value"       => $model_info->delivery_info,
                        "class"       => "form-control",
                        "placeholder" => "Shipping address, dispatch instructions, carrier details...",
                        "rows"        => "3"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <!-- Tax -->
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
                            "id"          => "custom_tax_percentage",
                            "name"        => "custom_tax_percentage",
                            "value"       => isset($custom_tax_percentage) ? $custom_tax_percentage : "",
                            "class"       => "form-control",
                            "type"        => "number",
                            "step"        => "0.01",
                            "min"         => "0",
                            "max"         => "100",
                            "placeholder" => "e.g. 18"
                        ));
                        ?>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Note -->
        <div class="form-group">
            <div class="row">
                <label for="dc_note" class="col-md-3"><?php echo app_lang('note'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id"          => "dc_note",
                        "name"        => "dc_note",
                        "value"       => $model_info->note ? $model_info->note : "",
                        "class"       => "form-control",
                        "placeholder" => app_lang('note'),
                        "rows"        => "3"
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
        $("#delivery-challan-form").appForm({
            onSuccess: function (result) {
                if (typeof RELOAD_VIEW_AFTER_UPDATE !== "undefined" && RELOAD_VIEW_AFTER_UPDATE) {
                    location.reload();
                } else {
                    window.location = "<?php echo site_url('delivery_challans/view'); ?>/" + result.id;
                }
            }
        });

        $("#dc_client_id").select2();

        <?php if (count($companies_dropdown) > 1) { ?>
            $("#company_id").select2({data: <?php echo json_encode($companies_dropdown); ?>});
        <?php } ?>

        $("#tax_id").select2().on("change", function () {
            if ($(this).val() === "other") {
                $("#custom-tax-group").removeClass("hide");
            } else {
                $("#custom-tax-group").addClass("hide");
            }
        });

        setDatePicker("#challan_date, #delivery_date, #reference_date");
    });
</script>
