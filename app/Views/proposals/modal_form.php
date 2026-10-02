<?php echo form_open(get_uri("proposals/save"), array("id" => "proposal-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />

        <?php if ($is_clone) { ?>
            <input type="hidden" name="is_clone" value="1" />
        <?php } ?>

        <div class="form-group">
            <div class="row">
                <label for="proposal_date" class=" col-md-3"><?php echo app_lang('proposal_date'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "proposal_date",
                        "name" => "proposal_date",
                        "value" => $model_info->proposal_date,
                        "class" => "form-control",
                        "placeholder" => app_lang('proposal_date'),
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
                <label for="valid_until" class=" col-md-3"><?php echo app_lang('valid_until'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "valid_until",
                        "name" => "valid_until",
                        "value" => $model_info->valid_until,
                        "class" => "form-control",
                        "placeholder" => app_lang('valid_until'),
                        "autocomplete" => "off",
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                        "data-rule-greaterThanOrEqual" => "#proposal_date",
                        "data-msg-greaterThanOrEqual" => app_lang("end_date_must_be_equal_or_greater_than_start_date")
                    ));
                    ?>
                </div>
            </div>
        </div>
        <?php if (count($companies_dropdown) > 1) { ?>
            <div class="form-group">
                <div class="row">
                    <label for="company_id" class=" col-md-3"><?php echo app_lang('company'); ?></label>
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
            <input type="hidden" name="proposal_client_id" value="<?php echo $client_id; ?>" />
        <?php } else { ?>
            <div class="form-group">
                <div class="row">
                    <label for="proposal_client_id" class=" col-md-3"><?php echo app_lang("client") . "/" . app_lang("lead"); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_dropdown("proposal_client_id", $clients_dropdown, array($model_info->client_id), "class='select2 validate-hidden' id='proposal_client_id' data-rule-required='true', data-msg-required='" . app_lang('field_required') . "'");
                        ?>
                    </div>
                </div>
            </div>
        <?php } ?>

        <div class="form-group">
            <div class="row">
                <label for="client_logo" class="col-md-3">Recipient / Client Logo</label>
                <div class="col-md-9">
                    <div id="proposal-client-logo-dropzone" class="post-dropzone">
                        <input type="hidden" name="remove_client_logo" id="remove_client_logo" value="0" />
                        <div class="d-flex align-items-center mb10" style="gap:15px; flex-wrap:wrap;">
                            <?php
                            $existing_logo_data = null;
                            if (!empty($model_info->client_logo)) {
                                $existing_logo_data = @unserialize($model_info->client_logo);
                            } else if (isset($client_info) && $client_info && !empty($client_info->client_logo)) {
                                $existing_logo_data = @unserialize($client_info->client_logo);
                            }

                            if ($existing_logo_data && is_array($existing_logo_data)) {
                                $logo_item = get_array_value($existing_logo_data, 0);
                                $logo_url = get_source_url_of_file($logo_item, get_setting('system_file_path'), 'thumbnail');
                                if ($logo_url) {
                                    echo '<div class="float-start d-flex align-items-center" id="client-logo-preview-box" style="gap:8px;">';
                                    echo '<img src="' . $logo_url . '" style="max-height:50px; max-width:160px; border:1px solid #ddd; padding:3px; background:#fff; border-radius:4px;" alt="Client Logo" />';
                                    echo '<button type="button" class="btn btn-default btn-sm text-danger" id="delete-client-logo-btn" title="Remove Client Logo"><i data-feather="trash-2" class="icon-16"></i></button>';
                                    echo '</div>';
                                }
                            }
                            ?>
                            <div class="float-start">
                                <div class="upload-file-button btn btn-default btn-sm">
                                    <i data-feather="upload" class="icon-16"></i> <span><?php echo $existing_logo_data ? 'Change Client Logo' : 'Upload Client Logo'; ?></span>
                                </div>
                                <div class="text-muted mt5 small">Upload recipient company's logo (PNG or JPG) to display on proposal</div>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <div class="mr15">
                            <?php echo view("includes/dropzone_preview"); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="tax_id" class=" col-md-3"><?php echo app_lang('tax'); ?></label>
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
                <label for="tax_id2" class=" col-md-3"><?php echo app_lang('second_tax'); ?></label>
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
                <label for="proposal_note" class=" col-md-3"><?php echo app_lang('note'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id" => "proposal_note",
                        "name" => "proposal_note",
                        "value" => $model_info->note ? process_images_from_content($model_info->note, false) : "",
                        "class" => "form-control",
                        "placeholder" => app_lang('note'),
                        "data-rich-text-editor" => true
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="proposal_terms_conditions" class=" col-md-3"><?php echo app_lang('terms_conditions'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id" => "proposal_terms_conditions",
                        "name" => "proposal_terms_conditions",
                        "value" => $model_info->terms_conditions ? $model_info->terms_conditions : "",
                        "class" => "form-control",
                        "placeholder" => app_lang('terms_conditions'),
                        "rows" => "5"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <?php echo view("custom_fields/form/prepare_context_fields", array("custom_fields" => $custom_fields, "label_column" => "col-md-3", "field_column" => " col-md-9")); ?> 

        <?php if ($is_clone) { ?>
            <div class="form-group">
                <div class="row">
                    <label for="copy_items"class=" col-md-12">
                        <?php
                        echo form_checkbox("copy_items", "1", true, "id='copy_items' disabled='disabled' class='float-start mr15 form-check-input'");
                        ?>    
                        <?php echo app_lang('copy_items'); ?>
                    </label>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="copy_discount"class=" col-md-12">
                        <?php
                        echo form_checkbox("copy_discount", "1", true, "id='copy_discount' disabled='disabled' class='float-start mr15 form-check-input'");
                        ?>    
                        <?php echo app_lang('copy_discount'); ?>
                    </label>
                </div>
            </div>
        <?php } ?> 

    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#proposal-form").appForm({
            onSuccess: function (result) {
                if (typeof RELOAD_VIEW_AFTER_UPDATE !== "undefined" && RELOAD_VIEW_AFTER_UPDATE) {
                    location.reload();
                } else {
                    window.location = "<?php echo site_url('proposals/view'); ?>/" + result.id;
                }
            }
        });
        $("#proposal-form .tax-select2").select2();
        $("#proposal_client_id").select2();

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

        $("#company_id").select2({data: <?php echo json_encode($companies_dropdown); ?>});

        setDatePicker("#proposal_date, #valid_until");

        var uploadUrl = "<?php echo get_uri("uploader/upload_file"); ?>";
        var validationUri = "<?php echo get_uri("uploader/validate_image_file"); ?>";

        var clientLogoDropzone = attachDropzoneWithForm("#proposal-client-logo-dropzone", uploadUrl, validationUri, {
            maxFiles: 1
        });

        $("#delete-client-logo-btn").click(function () {
            $("#client-logo-preview-box").remove();
            $("#remove_client_logo").val("1");
        });

        function tagClientLogoInputs() {
            $("#proposal-client-logo-dropzone input[name='file_names[]']").attr("name", "client_logo_file_names[]");
            $("#proposal-client-logo-dropzone input[name='file_sizes[]']").attr("name", "client_logo_file_sizes[]");
        }
        setInterval(tagClientLogoInputs, 250);
    });
</script>