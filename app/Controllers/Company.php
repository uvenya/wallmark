<?php

namespace App\Controllers;

class Company extends Security_Controller {

    private $Company_model;

    function __construct() {
        parent::__construct();
        $this->access_only_admin_or_settings_admin();
        $this->Company_model = model('App\Models\Company_model');
    }

    function index() {
        return $this->template->rander("company/index");
    }

    function modal_form() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $view_data['model_info'] = $this->Company_model->get_one($this->request->getPost('id'));
        return $this->template->view('company/modal_form', $view_data);
    }

    function save() {
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "name" => "required",
            "email" => "valid_email"
        ));

        $is_default = $this->request->getPost('is_default');
        $data = array(
            "name" => $this->request->getPost('name'),
            "address" => $this->request->getPost('address'),
            "phone" => $this->request->getPost('phone'),
            "email" => $this->request->getPost('email'),
            "website" => $this->request->getPost('website'),
            "vat_number" => $this->request->getPost('vat_number'),
            "is_default" => $is_default ? $is_default : 0,
            "gst_number" => $this->request->getPost('gst_number'),
            "bank_details" => $this->request->getPost('bank_details')
        );

        $id = $this->request->getPost('id');
        $company_info = $this->Company_model->get_one($id);

        $data = clean_data($data);

        $save_id = $this->Company_model->ci_save($data, $id);

        if ($save_id) {
            if ($is_default) {
                //remove if there has any other default company
                $this->Company_model->remove_other_default_company($save_id);
            }

            $target_path = get_setting("system_file_path");
            $update_data = array();

            // 1. Process Company Logo upload
            $logo_file_names = $this->request->getPost("logo_file_names");
            $logo_file_sizes = $this->request->getPost("logo_file_sizes");

            // Fallback if submitted without prefix
            if (!$logo_file_names && !$this->request->getPost("signature_file_names")) {
                $logo_file_names = $this->request->getPost("file_names");
                $logo_file_sizes = $this->request->getPost("file_sizes");
            }

            if ($logo_file_names && get_array_value($logo_file_names, 0)) {
                $logo_files = array();
                foreach ($logo_file_names as $key => $file_name) {
                    $file_size = get_array_value($logo_file_sizes, $key);
                    $file_data = move_temp_file($file_name, $target_path, "company_logo_$save_id", null, "", "", false, $file_size);
                    if ($file_data) {
                        $logo_files[] = array(
                            "file_name" => get_array_value($file_data, "file_name"),
                            "file_size" => $file_size,
                            "file_id" => get_array_value($file_data, "file_id"),
                            "service_type" => get_array_value($file_data, "service_type")
                        );
                    }
                }

                if ($logo_files) {
                    // Delete previous logo file
                    if ($company_info->logo) {
                        $old_files = @unserialize($company_info->logo);
                        if ($old_files && is_array($old_files)) {
                            foreach ($old_files as $f) {
                                delete_app_files($target_path, array($f));
                            }
                        }
                    }
                    $update_data["logo"] = serialize($logo_files);
                }
            } else if ($this->request->getPost("remove_logo") == "1") {
                if ($company_info->logo) {
                    $old_files = @unserialize($company_info->logo);
                    if ($old_files && is_array($old_files)) {
                        foreach ($old_files as $f) {
                            delete_app_files($target_path, array($f));
                        }
                    }
                }
                $update_data["logo"] = "";
            }

            // 2. Process Authorized Signatory (Signature Image) upload
            $sig_file_names = $this->request->getPost("signature_file_names");
            $sig_file_sizes = $this->request->getPost("signature_file_sizes");

            if ($sig_file_names && get_array_value($sig_file_names, 0)) {
                $sig_files = array();
                foreach ($sig_file_names as $key => $file_name) {
                    $file_size = get_array_value($sig_file_sizes, $key);
                    $file_data = move_temp_file($file_name, $target_path, "company_sig_$save_id", null, "", "", false, $file_size);
                    if ($file_data) {
                        $sig_files[] = array(
                            "file_name" => get_array_value($file_data, "file_name"),
                            "file_size" => $file_size,
                            "file_id" => get_array_value($file_data, "file_id"),
                            "service_type" => get_array_value($file_data, "service_type")
                        );
                    }
                }

                if ($sig_files) {
                    // Delete previous signature file
                    if ($company_info->signature && is_serialized_string($company_info->signature)) {
                        $old_sig = @unserialize($company_info->signature);
                        if ($old_sig && is_array($old_sig)) {
                            foreach ($old_sig as $f) {
                                delete_app_files($target_path, array($f));
                            }
                        }
                    }
                    $update_data["signature"] = serialize($sig_files);
                }
            } else if ($this->request->getPost("remove_signature") == "1") {
                if ($company_info->signature && is_serialized_string($company_info->signature)) {
                    $old_sig = @unserialize($company_info->signature);
                    if ($old_sig && is_array($old_sig)) {
                        foreach ($old_sig as $f) {
                            delete_app_files($target_path, array($f));
                        }
                    }
                }
                $update_data["signature"] = "";
            }

            if (!empty($update_data)) {
                $this->Company_model->ci_save($update_data, $save_id);
            }

            echo json_encode(array("success" => true, "data" => $this->_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function delete() {
        $this->validate_submitted_data(array(
            "id" => "numeric|required"
        ));

        $id = $this->request->getPost('id');
        $company_info = $this->Company_model->get_one($id);
        if ($company_info->is_default) {
            //default company can't be deleted
            show_404();
        }

        if ($this->request->getPost('undo')) {
            if ($this->Company_model->delete($id, true)) {
                echo json_encode(array("success" => true, "data" => $this->_row_data($id), "message" => app_lang('record_undone')));
            } else {
                echo json_encode(array("success" => false, app_lang('error_occurred')));
            }
        } else {
            if ($this->Company_model->delete($id)) {
                echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
            } else {
                echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
            }
        }
    }

    function list_data() {
        $list_data = $this->Company_model->get_details()->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    private function _row_data($id) {
        $options = array("id" => $id);
        $data = $this->Company_model->get_details($options)->getRow();
        return $this->_make_row($data);
    }

    private function _make_row($data) {
        $default_company = "";
        $delete = js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_company'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("company/delete"), "data-action" => "delete"));
        if ($data->is_default) {
            $default_company = " <span class='bg-info badge text-white'>" . app_lang('default_company') . "</span>";
            $delete = "";
        }

        $company_logo = get_company_logo($data->id, '', true);

        $company_info = "<div class='mb10 strong'>" . $data->name . $default_company . "</div>" . "<div>" . nl2br($data->address) . "</div>" . "<div>" . $data->phone . "</div>" . "<div>" . $data->email . "</div>" . "<div>" . $data->website . "</div>" . "<div>" . $data->vat_number . "</div>" . "<div>" . $data->gst_number . "</div>";

        return array(
            $company_logo,
            $company_info,
            modal_anchor(get_uri("company/modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_company'), "data-post-id" => $data->id))
            . $delete
        );
    }

}

/* End of file company.php */
/* Location: ./app/controllers/company.php */