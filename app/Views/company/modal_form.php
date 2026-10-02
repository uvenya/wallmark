<?php echo form_open(get_uri("company/save"), array("id" => "company-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
        <div class="container-fluid">
            <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />
            <div class="form-group">
                <div class="row">
                    <label for="name" class=" col-md-3"><?php echo app_lang('company_name'); ?></label>
                    <div class=" col-md-9">
                        <?php
                        echo form_input(array(
                            "id" => "name",
                            "name" => "name",
                            "value" => $model_info->name,
                            "class" => "form-control",
                            "placeholder" => app_lang('company_name'),
                            "data-rule-required" => true,
                            "data-msg-required" => app_lang("field_required")
                        ));
                        ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="address" class=" col-md-3"><?php echo app_lang('address'); ?></label>
                    <div class=" col-md-9">
                        <?php
                        echo form_textarea(array(
                            "id" => "address",
                            "name" => "address",
                            "value" => $model_info->address,
                            "class" => "form-control",
                            "placeholder" => app_lang('address'),
                        ));
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="phone" class=" col-md-3"><?php echo app_lang('phone'); ?></label>
                    <div class=" col-md-9">
                        <?php
                        echo form_input(array(
                            "id" => "phone",
                            "name" => "phone",
                            "value" => $model_info->phone,
                            "class" => "form-control",
                            "placeholder" => app_lang('phone')
                        ));
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="email" class=" col-md-3"><?php echo app_lang('email'); ?></label>
                    <div class=" col-md-9">
                        <?php
                        echo form_input(array(
                            "id" => "email",
                            "name" => "email",
                            "value" => $model_info->email,
                            "class" => "form-control",
                            "placeholder" => app_lang('email')
                        ));
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="website" class=" col-md-3"><?php echo app_lang('website'); ?></label>
                    <div class=" col-md-9">
                        <?php
                        echo form_input(array(
                            "id" => "website",
                            "name" => "website",
                            "value" => $model_info->website,
                            "class" => "form-control",
                            "placeholder" => app_lang('website')
                        ));
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="vat_number" class=" col-md-3"><?php echo app_lang('vat_number'); ?></label>
                    <div class=" col-md-9">
                        <?php
                        echo form_input(array(
                            "id" => "vat_number",
                            "name" => "vat_number",
                            "value" => $model_info->vat_number,
                            "class" => "form-control",
                            "placeholder" => app_lang('vat_number')
                        ));
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="gst_number" class="col-md-3"><?php echo app_lang('gst_number'); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_input(array(
                            "id" => "gst_number",
                            "name" => "gst_number",
                            "value" => $model_info->gst_number,
                            "class" => "form-control",
                            "placeholder" => app_lang('gst_number')
                        ));
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group ">
                <div class="row">
                    <label for="is_default" class=" col-md-3"><?php echo app_lang('default_company'); ?></label>

                    <div class=" col-md-9">
                        <?php
                        //is set default company, disable the checkbox
                        $disable = "";
                        if ($model_info->is_default) {
                            $disable = "disabled='disabled'";
                        }
                        echo form_checkbox("is_default", "1", $model_info->is_default, "id='is_default' class='form-check-input mt-2' $disable");
                        ?>

                        <?php if ($model_info->is_default) { ?>
                            <input type="hidden" name="is_default" value="<?php echo $model_info->is_default; ?>" />
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="company_logo" class="col-md-3 mt10"><?php echo app_lang('company_logo'); ?> <br/><small class="text-muted">(300x100 px)</small></label>
                    <div class="col-md-9">
                        <div id="company-logo-dropzone" class="post-dropzone">
                            <input type="hidden" name="remove_logo" id="remove_logo" value="0" />
                            <div class="d-flex align-items-center mb10" style="gap:15px; flex-wrap:wrap;">
                                <?php
                                if ($model_info->logo) {
                                    $c_logo = @unserialize($model_info->logo);
                                    if ($c_logo && is_array($c_logo)) {
                                        $logo_item = get_array_value($c_logo, 0);
                                        $logo_url = get_source_url_of_file($logo_item, get_setting('system_file_path'), 'thumbnail');
                                        if ($logo_url) {
                                            echo '<div class="float-start d-flex align-items-center" id="company-logo-preview-box" style="gap:8px;">';
                                            echo '<img src="' . $logo_url . '" style="max-height:55px; max-width:180px; border:1px solid #ddd; padding:4px; background:#fff; border-radius:4px;" alt="Logo" />';
                                            echo '<button type="button" class="btn btn-default btn-sm text-danger" id="delete-company-logo-btn" title="Remove Logo"><i data-feather="trash-2" class="icon-16"></i></button>';
                                            echo '</div>';
                                        }
                                    }
                                }
                                ?>
                                <div class="float-start">
                                    <div class="upload-file-button btn btn-default btn-sm">
                                        <i data-feather="upload" class="icon-16"></i> <span><?php echo $model_info->logo ? 'Change Logo' : 'Upload Logo'; ?></span>
                                    </div>
                                    <div class="text-muted mt5 small">Upload PNG or JPG (recommended: 300x100 px)</div>
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
                    <label for="company_bank_details" class="col-md-3 mt10"><?php echo app_lang('bank_details'); ?> </label>
                    <div class="col-md-9">
                        <?php
                        echo form_textarea(array(
                            "id" => "company_bank_details",
                            "name" => "bank_details",
                            "value" => $model_info->bank_details ? $model_info->bank_details : "",
                            "class" => "form-control",
                            "placeholder" => "Bank Name, Account Number, IFSC Code, Branch...",
                            "rows" => "4"
                        ));
                        ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label class="col-md-3 mt10"><?php echo app_lang('authorized_signatory'); ?> <br/><small class="text-muted">(Signature Image)</small></label>
                    <div class="col-md-9">
                        <div id="company-signature-dropzone" class="post-dropzone">
                            <input type="hidden" name="remove_signature" id="remove_signature" value="0" />
                            <div class="d-flex align-items-center mb10" style="gap:15px; flex-wrap:wrap;">
                                <?php
                                if ($model_info->signature) {
                                    $sig_file = @unserialize($model_info->signature);
                                    if ($sig_file && is_array($sig_file)) {
                                        $sig_file_item = get_array_value($sig_file, 0);
                                        $sig_url = get_source_url_of_file($sig_file_item, get_setting('system_file_path'), 'thumbnail');
                                        if ($sig_url) {
                                            echo '<div class="float-start d-flex align-items-center" id="company-signature-preview-box" style="gap:8px;">';
                                            echo '<img src="' . $sig_url . '" style="max-height:55px; max-width:180px; border:1px solid #ddd; padding:4px; background:#fff; border-radius:4px;" alt="Signature" />';
                                            echo '<button type="button" class="btn btn-default btn-sm text-danger" id="delete-company-signature-btn" title="Remove Signature"><i data-feather="trash-2" class="icon-16"></i></button>';
                                            echo '</div>';
                                        }
                                    }
                                }
                                ?>
                                <div class="float-start">
                                    <div class="upload-file-button btn btn-default btn-sm signature-upload-btn">
                                        <i data-feather="edit-3" class="icon-16"></i> <span><?php echo $model_info->signature ? 'Change Signature' : 'Upload Signature'; ?></span>
                                    </div>
                                    <div class="text-muted mt5 small">Upload PNG or JPG with transparent/white background</div>
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

        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
        <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
    </div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function() {
        var uploadUrl = "<?php echo get_uri("uploader/upload_file"); ?>";
        var validationUri = "<?php echo get_uri("uploader/validate_image_file"); ?>";

        var logoDropzone = attachDropzoneWithForm("#company-logo-dropzone", uploadUrl, validationUri, {
            maxFiles: 1
        });
        
        var signatureDropzone = attachDropzoneWithForm("#company-signature-dropzone", uploadUrl, validationUri, {
            maxFiles: 1
        });

        function tagDropzoneInputs() {
            $("#company-logo-dropzone input[name='file_names[]']").attr("name", "logo_file_names[]");
            $("#company-logo-dropzone input[name='file_sizes[]']").attr("name", "logo_file_sizes[]");
            $("#company-signature-dropzone input[name='file_names[]']").attr("name", "signature_file_names[]");
            $("#company-signature-dropzone input[name='file_sizes[]']").attr("name", "signature_file_sizes[]");
        }

        setInterval(tagDropzoneInputs, 250);

        $("#delete-company-logo-btn").click(function() {
            $("#company-logo-preview-box").hide();
            $("#remove_logo").val("1");
            $("#company-logo-dropzone .upload-file-button span").text("Upload Logo");
        });

        $("#delete-company-signature-btn").click(function() {
            $("#company-signature-preview-box").hide();
            $("#remove_signature").val("1");
            $("#company-signature-dropzone .upload-file-button span").text("Upload Signature");
        });

        $("#company-form").appForm({
            onSubmit: function() {
                tagDropzoneInputs();
            },
            beforeAjaxSubmit: function(data, self, options) {
                tagDropzoneInputs();
            },
            onSuccess: function(result) {
                $("#company-table").appTable({
                    reload: true
                });
                appAlert.success(result.message, {
                    duration: 10000
                });
            }
        });

        if (typeof feather !== 'undefined') {
            feather.replace();
        }

        setTimeout(function() {
            $("#name").focus();
        }, 200);
    });
</script>