<?php echo form_open(get_uri("labels/save"), array("id" => "labels-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix label-modal-body">
    <div class="container-fluid">
        <input type="hidden" id="type" name="type" value="<?php echo $type; ?>" />
        <input type="hidden" id="label_id" name="id" value="" />

        <!-- Add / Edit Label Form Area -->
        <div class="add-label clearfix pb10">
            <div class="d-flex justify-content-between align-items-center mb10">
                <span id="label-form-mode-title" class="fw-bold text-default">
                    <i data-feather="plus-circle" class="icon-16"></i> <?php echo app_lang('add'); ?> <?php echo strtolower(app_lang('label')); ?>
                </span>
                <button id="inline-cancel-btn" type="button" class="btn btn-sm btn-default hide">
                    <i data-feather="x" class="icon-14"></i> <?php echo app_lang('cancel'); ?>
                </button>
            </div>

            <div class="form-group text-center mb15">
                <div class="col-md-12">
                    <?php echo view("includes/color_plate"); ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-9 col-sm-9">
                    <div class="form-group mb0">
                        <?php
                        echo form_input(array(
                            "id" => "label-title",
                            "name" => "title",
                            "value" => "",
                            "class" => "form-control",
                            "placeholder" => app_lang('label') . " " . strtolower(app_lang('title')),
                            "autofocus" => true,
                            "autocomplete" => "off",
                            "data-rule-required" => true,
                            "data-msg-required" => app_lang("field_required"),
                        ));
                        ?>
                    </div> 
                </div>

                <div class="col-md-3 col-sm-3">
                    <button id="label-submit-btn" type="submit" class="btn btn-primary w-100">
                        <span data-feather="check-circle" class="icon-16"></span> <span id="submit-btn-text"><?php echo app_lang('save'); ?></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Existing Labels List Area -->
        <div class="p15 b-t mt15">
            <div class="mb10 d-flex justify-content-between align-items-center">
                <strong><?php echo app_lang('labels'); ?></strong>
                <span class="text-off font-12"><i data-feather="info" class="icon-12"></i> Click any label to edit or delete</span>
            </div>

            <div id="label-show-area">
                <?php echo $existing_labels; ?>
                <div id="no-labels-msg" class="text-center text-off p15 <?php echo $existing_labels ? 'hide' : ''; ?>">
                    <i data-feather="tag" class="icon-24 mb5 opacity-50"></i>
                    <div>No labels found for this section yet. Choose a color and enter a title above to create one.</div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal-footer">
    <button id="label-delete-btn" type="button" class="btn btn-danger hide float-start"><span data-feather="trash-2" class="icon-16"></span> <?php echo app_lang('delete'); ?></button>
    <button id="cancel-edit-btn" type="button" class="btn btn-default ml10 hide float-start"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('cancel'); ?></button>
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        var $labelShowArea = $("#label-show-area");
        var $noLabelsMsg = $("#no-labels-msg");

        if (typeof feather !== "undefined") {
            feather.replace();
        }

        $("#labels-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                if (result.success) {
                    $noLabelsMsg.addClass("hide");

                    if ($("#label_id").val()) {
                        var $selector = $labelShowArea.find("[data-id='" + result.id + "']");
                        $selector.fadeOut(100, function () {
                            var $newEl = $(result.data);
                            $newEl.insertAfter($selector);
                            $selector.remove();
                            if (typeof feather !== "undefined") {
                                feather.replace();
                            }
                        });

                        hideEditMode();
                    } else {
                        var $newEl = $(result.data);
                        $labelShowArea.prepend($newEl);
                        if (typeof feather !== "undefined") {
                            feather.replace();
                        }
                    }

                    $("#label-title").val("").focus();
                }
            }
        });

        // Click on a label to enter Edit / Delete mode
        $('body').on('click', "[data-act='label-edit-delete']", function () {
            showEditMode($(this));
        });

        // Select text in input field when clicking color
        $(".color-palet span").click(function () {
            if ($("#label-title").val()) {
                $("#label-title").select();
            } else {
                $("#label-title").focus();
            }
        });

        function showEditMode($selector) {
            var labelTitle = $selector.attr("data-title") ? $selector.attr("data-title") : $selector.text().trim();
            var labelColor = $selector.attr("data-color");

            $("#label-title").val(labelTitle).focus();
            $("#label_id").val($selector.attr("data-id"));

            // Set color active state
            $(".color-palet span").removeClass("active");
            var $matchedSpan = $(".color-palet").find("[data-color='" + labelColor + "']");
            if ($matchedSpan.length) {
                $matchedSpan.addClass("active");
                $("#custom-color").removeClass("active");
            } else {
                $("#custom-color").addClass("active");
            }
            $("#custom-color").val(labelColor);

            // Update UI to edit mode
            $("#label-form-mode-title").html("<i data-feather='edit-2' class='icon-16 text-warning'></i> Editing label: <strong class='text-primary'>" + labelTitle + "</strong>");
            $("#submit-btn-text").text("<?php echo app_lang('save'); ?>");
            $("#inline-cancel-btn").removeClass("hide");
            $("#label-delete-btn").removeClass("hide");
            $("#cancel-edit-btn").removeClass("hide");

            if (typeof feather !== "undefined") {
                feather.replace();
            }
        }

        function hideEditMode() {
            $("#label-title").val('').focus();
            $("#label_id").val('');

            $("#label-form-mode-title").html("<i data-feather='plus-circle' class='icon-16'></i> <?php echo app_lang('add'); ?> <?php echo strtolower(app_lang('label')); ?>");
            $("#submit-btn-text").text("<?php echo app_lang('save'); ?>");
            $("#inline-cancel-btn").addClass("hide");
            $("#label-delete-btn").addClass("hide");
            $("#cancel-edit-btn").addClass("hide");

            if (typeof feather !== "undefined") {
                feather.replace();
            }
        }

        $("#cancel-edit-btn, #inline-cancel-btn").click(function () {
            hideEditMode();
        });

        $("#label-delete-btn").click(function () {
            appLoader.show({container: ".label-modal-body", css: "left:0;"});

            $.ajax({
                url: "<?php echo get_uri('labels/delete') ?>",
                type: 'POST',
                dataType: 'json',
                data: {id: $("#label_id").val(), type: $("#type").val()},
                success: function (result) {
                    appLoader.hide();

                    if (result.label_exists) {
                        appAlert.error(result.message, {container: '.modal-body', animate: false});
                    } else if (result.success) {
                        var $selector = $labelShowArea.find("[data-id='" + result.id + "']");
                        $selector.fadeOut(100, function () {
                            $selector.remove();
                            if ($labelShowArea.find("[data-act='label-edit-delete']").length === 0) {
                                $noLabelsMsg.removeClass("hide");
                            }
                        });

                        hideEditMode();
                    }
                }
            });
        });

        setTimeout(function () {
            $("#label-title").focus();
        }, 200);
    });
</script>