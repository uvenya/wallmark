<?php echo form_open(get_uri("delivery_challans/save_item"), array("id" => "delivery-challan-item-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />
        <input type="hidden" id="item_id" name="item_id" value="" />
        <input type="hidden" name="delivery_challan_id" value="<?php echo $delivery_challan_id; ?>" />

        <!-- Item Title -->
        <div class="form-group">
            <div class="row">
                <label for="dc_item_title" class="col-md-3"><?php echo app_lang('item'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id"                 => "dc_item_title",
                        "name"               => "dc_item_title",
                        "value"              => $model_info->title,
                        "class"              => "form-control validate-hidden",
                        "placeholder"        => app_lang('select_or_create_new_item'),
                        "data-rule-required" => true,
                        "data-msg-required"  => app_lang("field_required"),
                    ));
                    ?>
                    <a id="dc_item_title_dropdown_icon" tabindex="-1" href="javascript:void(0);" style="color: #B3B3B3;float: right; padding: 5px 7px; margin-top: -35px; font-size: 18px;"><span>×</span></a>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="form-group">
            <div class="row">
                <label for="dc_item_description" class="col-md-3"><?php echo app_lang('description'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id"          => "dc_item_description",
                        "name"        => "dc_item_description",
                        "value"       => $model_info->description ? $model_info->description : "",
                        "class"       => "form-control",
                        "placeholder" => app_lang('description'),
                        "rows"        => "3"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <!-- HSN / SAC Code -->
        <div class="form-group">
            <div class="row">
                <label for="dc_item_hsn_sac_code" class="col-md-3">HSN / SAC Code</label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id"          => "dc_item_hsn_sac_code",
                        "name"        => "dc_item_hsn_sac_code",
                        "value"       => $model_info->hsn_sac_code ? $model_info->hsn_sac_code : "",
                        "class"       => "form-control",
                        "placeholder" => "HSN / SAC Code"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <!-- Quantity -->
        <div class="form-group">
            <div class="row">
                <label for="dc_item_quantity" class="col-md-3"><?php echo app_lang('quantity'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id"                 => "dc_item_quantity",
                        "name"               => "dc_item_quantity",
                        "value"              => $model_info->quantity ? to_decimal_format($model_info->quantity) : "1",
                        "class"              => "form-control",
                        "placeholder"        => app_lang('quantity'),
                        "data-rule-required" => true,
                        "data-msg-required"  => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>

        <!-- Unit -->
        <div class="form-group">
            <div class="row">
                <label for="dc_unit_type" class="col-md-3"><?php echo app_lang('unit_type'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id"          => "dc_unit_type",
                        "name"        => "dc_unit_type",
                        "value"       => $model_info->unit_type,
                        "class"       => "form-control",
                        "placeholder" => app_lang('unit_type') . ' (e.g. Nos, Pcs, Mtr, Kg, Sq.ft)'
                    ));
                    ?>
                </div>
            </div>
        </div>

        <!-- Rate -->
        <div class="form-group">
            <div class="row">
                <label for="dc_item_rate" class="col-md-3"><?php echo app_lang('rate'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id"          => "dc_item_rate",
                        "name"        => "dc_item_rate",
                        "value"       => $model_info->rate ? to_decimal_format($model_info->rate) : "",
                        "class"       => "form-control",
                        "placeholder" => app_lang('rate')
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
        $("#delivery-challan-item-form").appForm({
            onSuccess: function (result) {
                $("#delivery-challan-item-table").appTable({newData: result.data, dataId: result.id});
            }
        });

        var isUpdate = "<?php echo $model_info->id; ?>";
        if (!isUpdate) {
            applySelect2OnDcItemTitle();
        }

        $("#dc_item_title_dropdown_icon").click(function () {
            applySelect2OnDcItemTitle();
        });
    });

    function applySelect2OnDcItemTitle() {
        $("#dc_item_title").select2({
            showSearchBox: true,
            ajax: {
                url: "<?php echo get_uri("delivery_challans/get_dc_item_suggestion"); ?>",
                type: 'POST',
                dataType: 'json',
                quietMillis: 250,
                data: function (term) {
                    return {q: term};
                },
                results: function (data) {
                    return {results: data};
                }
            }
        }).change(function (e) {
            if (e.val === "+") {
                $("#dc_item_title").select2("destroy").val("").focus();
            } else if (e.val) {
                $.ajax({
                    url: "<?php echo get_uri("delivery_challans/get_dc_item_info_suggestion"); ?>",
                    data: {item_id: e.val},
                    cache: false,
                    type: 'POST',
                    dataType: "json",
                    success: function (response) {
                        if (response && response.success) {
                            $("#item_id").val(response.item_info.id);
                            $("#dc_item_title").val(response.item_info.title);
                            $("#dc_item_description").val(response.item_info.description);
                            $("#dc_unit_type").val(response.item_info.unit_type);
                            if (response.item_info.rate) {
                                $("#dc_item_rate").val(response.item_info.rate);
                            }
                            if (response.item_info.hsn_sac_code) {
                                $("#dc_item_hsn_sac_code").val(response.item_info.hsn_sac_code);
                            }
                        }
                    }
                });
            }
        });
    }
</script>
