<?php

namespace App\Controllers;

class Delivery_challans extends Security_Controller
{
    function __construct()
    {
        $uri = uri_string();
        $path = service('request')->getUri()->getPath();
        $is_public = (strpos($uri, 'download_pdf') !== false || strpos($path, 'download_pdf') !== false);
        parent::__construct(!$is_public);
        if (!$is_public) {
            $this->init_permission_checker("delivery_challan");
        }
    }

    // -------------------------------------------------------------------------
    // Index - list all Delivery Challans
    // -------------------------------------------------------------------------
    function index()
    {
        $this->access_only_team_members();

        $view_data = array();
        return $this->template->rander("delivery_challans/index", $view_data);
    }

    // -------------------------------------------------------------------------
    // Modal form - create / edit
    // -------------------------------------------------------------------------
    function modal_form()
    {
        $this->access_only_team_members();

        $this->validate_submitted_data(array(
            "id"        => "numeric",
            "client_id" => "numeric"
        ));

        $id        = $this->request->getPost('id');
        $is_clone  = $this->request->getPost('is_clone');
        $client_id = $this->request->getPost('client_id');

        $view_data['model_info'] = $this->Delivery_challans_model->get_one($id);

        // If the challan has a client already, prefer that
        if ($view_data['model_info']->client_id) {
            $client_id = $view_data['model_info']->client_id;
        }

        $view_data['client_id']          = $client_id;
        $view_data['is_clone']           = $is_clone;
        $view_data['clients_dropdown']   = $this->_get_clients_and_leads_dropdown();
        $view_data['companies_dropdown'] = $this->_get_companies_dropdown();

        // Taxes dropdown (None, registered taxes from rise_taxes, or Other custom %)
        $taxes = $this->Taxes_model->get_all_where(array("deleted" => 0), 1000000, 0, "percentage")->getResult();
        $taxes_dropdown = array("" => "- None (0%) -");
        foreach ($taxes as $tax) {
            $taxes_dropdown[$tax->id] = $tax->title;
        }
        $taxes_dropdown["other"] = "Other";
        $view_data['taxes_dropdown'] = $taxes_dropdown;

        $selected_tax_id = "";
        $custom_tax_percentage = "";
        if (!empty($view_data['model_info']->custom_tax_percentage)) {
            $selected_tax_id = "other";
            $custom_tax_percentage = to_decimal_format($view_data['model_info']->custom_tax_percentage);
        } else if (!empty($view_data['model_info']->tax_id)) {
            $selected_tax_id = $view_data['model_info']->tax_id;
        }
        $view_data['selected_tax_id'] = $selected_tax_id;
        $view_data['custom_tax_percentage'] = $custom_tax_percentage;

        if (!$view_data['model_info']->company_id) {
            $view_data['model_info']->company_id = get_default_company_id();
        }

        return $this->template->view('delivery_challans/modal_form', $view_data);
    }

    // -------------------------------------------------------------------------
    // Save - create or edit a Delivery Challan
    // -------------------------------------------------------------------------
    function save()
    {
        $this->access_only_team_members();

        $this->validate_submitted_data(array(
            "id"          => "numeric",
            "dc_client_id" => "required|numeric",
            "challan_date" => "required"
        ));

        $client_id = $this->request->getPost('dc_client_id');
        $id        = $this->request->getPost('id');
        $is_clone  = $this->request->getPost('is_clone');

        $tax_id_input = $this->request->getPost('tax_id');
        $tax_id = 0;
        $custom_tax_percentage = null;

        if ($tax_id_input === "other") {
            $custom_tax_percentage = (float) $this->request->getPost('custom_tax_percentage');
        } else if ($tax_id_input) {
            $tax_id = (int) $tax_id_input;
        }

        $dc_data = array(
            "client_id"             => $client_id,
            "challan_date"          => $this->request->getPost('challan_date'),
            "delivery_date"         => $this->request->getPost('delivery_date'),
            "reference_number"      => $this->request->getPost('reference_number'),
            "reference_date"        => $this->request->getPost('reference_date'),
            "destination"           => $this->request->getPost('destination'),
            "delivery_info"         => $this->request->getPost('delivery_info'),
            "note"                  => $this->request->getPost('dc_note'),
            "tax_id"                => $tax_id,
            "custom_tax_percentage" => $custom_tax_percentage,
            "company_id"            => $this->request->getPost('company_id') ? $this->request->getPost('company_id') : get_default_company_id(),
        );

        if (!$id) {
            $dc_data["public_key"]  = make_random_string();
            $dc_data["created_by"]  = $this->login_user->id;
            $dc_data["status"]      = "draft";
        }

        $main_dc_id = "";
        if ($is_clone && $id) {
            $main_dc_id = $id;
            $id         = "";
            $dc_data["public_key"] = make_random_string();
            $dc_data["status"]     = "draft";
        }

        $dc_id = $this->Delivery_challans_model->ci_save($dc_data, $id);

        if ($dc_id) {
            // Clone items if cloning
            if ($is_clone && $main_dc_id) {
                $items = $this->Delivery_challan_items_model->get_all_where(
                    array("delivery_challan_id" => $main_dc_id, "deleted" => 0)
                )->getResult();

                foreach ($items as $item) {
                    $item_data = (array) $item;
                    unset($item_data["id"]);
                    $item_data['delivery_challan_id'] = $dc_id;
                    $this->Delivery_challan_items_model->ci_save($item_data);
                }
            }

            echo json_encode(array(
                "success" => true,
                "data"    => $this->_row_data($dc_id),
                "id"      => $dc_id,
                "message" => app_lang('record_saved')
            ));
        } else {
            echo json_encode(array("success" => false, "message" => app_lang('error_occurred')));
        }
    }

    // -------------------------------------------------------------------------
    // Update status
    // -------------------------------------------------------------------------
    function update_status($id, $status)
    {
        $this->access_only_team_members();
        validate_numeric_value($id);

        $allowed = array("draft", "dispatched", "delivered", "cancelled");
        if (in_array($status, $allowed)) {
            $save_data = array("status" => $status);
            if ($this->Delivery_challans_model->ci_save($save_data, $id)) {
                echo json_encode(array("success" => true, "message" => app_lang('record_saved')));
            } else {
                echo json_encode(array("success" => false, "message" => app_lang('error_occurred')));
            }
        } else {
            echo json_encode(array("success" => false, "message" => app_lang('error_occurred')));
        }
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------
    function delete()
    {
        $this->access_only_team_members();

        $this->validate_submitted_data(array("id" => "required|numeric"));

        $id = $this->request->getPost('id');
        if ($this->Delivery_challans_model->delete($id)) {
            echo json_encode(array("success" => true, "message" => app_lang('record_deleted')));
        } else {
            echo json_encode(array("success" => false, "message" => app_lang('record_cannot_be_deleted')));
        }
    }

    // -------------------------------------------------------------------------
    // List data (AJAX datatable)
    // -------------------------------------------------------------------------
    function list_data()
    {
        $this->access_only_team_members();

        $options = array(
            "status"     => $this->request->getPost("status"),
            "start_date" => $this->request->getPost("start_date"),
            "end_date"   => $this->request->getPost("end_date"),
        );

        $list_data = $this->Delivery_challans_model->get_details($options)->getResult();
        $result    = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }

        echo json_encode(array("data" => $result));
    }

    private function _row_data($id)
    {
        $options = array("id" => $id);
        $data    = $this->Delivery_challans_model->get_details($options)->getRow();
        return $this->_make_row($data);
    }

    private function _make_row($data)
    {
        $dc_url = anchor(get_uri("delivery_challans/view/" . $data->id), get_delivery_challan_id($data->id));

        if ($data->is_lead) {
            $client_url = anchor(get_uri("leads/view/" . $data->client_id), $data->company_name);
        } else {
            $client_url = anchor(get_uri("clients/view/" . $data->client_id), $data->company_name);
        }

        $status_class = "bg-secondary";
        if ($data->status == "dispatched") {
            $status_class = "bg-primary";
        } else if ($data->status == "delivered") {
            $status_class = "bg-success";
        } else if ($data->status == "cancelled") {
            $status_class = "bg-danger";
        }
        $status = "<span class='badge $status_class'>" . ucfirst($data->status) . "</span>";

        $actions = modal_anchor(get_uri("delivery_challans/modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_delivery_challan'), "data-post-id" => $data->id))
            . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_delivery_challan'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("delivery_challans/delete"), "data-action" => "delete"));

        return array(
            $data->id,
            $dc_url,
            $client_url,
            $data->challan_date,
            format_to_date($data->challan_date, false),
            $data->delivery_date ? $data->delivery_date : "",
            $data->delivery_date ? format_to_date($data->delivery_date, false) : "-",
            $data->reference_number ? htmlspecialchars($data->reference_number) : "-",
            $data->destination ? htmlspecialchars($data->destination) : "-",
            $status,
            $actions
        );
    }

    // -------------------------------------------------------------------------
    // View a Delivery Challan
    // -------------------------------------------------------------------------
    function view($id = 0)
    {
        validate_numeric_value($id);
        $this->access_only_team_members();

        if ($id) {
            $view_data = get_delivery_challan_making_data($id);
            if ($view_data) {
                $view_data["delivery_challan_id"] = $id;
                return $this->template->rander("delivery_challans/view", $view_data);
            } else {
                show_404();
            }
        }
    }

    // -------------------------------------------------------------------------
    // Items - modal form
    // -------------------------------------------------------------------------
    function item_modal_form()
    {
        $this->access_only_team_members();

        $this->validate_submitted_data(array(
            "id"                   => "numeric",
            "delivery_challan_id"  => "numeric"
        ));

        $delivery_challan_id   = $this->request->getPost('delivery_challan_id');
        $view_data['model_info'] = $this->Delivery_challan_items_model->get_one($this->request->getPost('id'));

        if (!$delivery_challan_id) {
            $delivery_challan_id = $view_data['model_info']->delivery_challan_id;
        }
        $view_data['delivery_challan_id'] = $delivery_challan_id;

        return $this->template->view('delivery_challans/item_modal_form', $view_data);
    }

    // -------------------------------------------------------------------------
    // Items - save
    // -------------------------------------------------------------------------
    function save_item()
    {
        $this->access_only_team_members();

        $this->validate_submitted_data(array(
            "id"                  => "numeric",
            "delivery_challan_id" => "required|numeric"
        ));

        $delivery_challan_id = $this->request->getPost('delivery_challan_id');
        $id                  = $this->request->getPost('id');
        $quantity            = unformat_currency($this->request->getPost('dc_item_quantity'));
        $rate                = unformat_currency($this->request->getPost('dc_item_rate'));
        $total               = (float)$quantity * (float)$rate;
        $item_title          = $this->request->getPost('dc_item_title');
        $item_id             = $this->request->getPost('item_id');

        $item_data = array(
            "delivery_challan_id" => $delivery_challan_id,
            "title"               => $item_title,
            "description"         => $this->request->getPost('dc_item_description'),
            "quantity"            => $quantity,
            "unit_type"           => $this->request->getPost('dc_unit_type'),
            "hsn_sac_code"        => $this->request->getPost('dc_item_hsn_sac_code'),
            "rate"                => $rate ? $rate : 0,
            "total"               => $total ? $total : 0,
        );

        if ($item_id) {
            $item_data["item_id"] = $item_id;
        }

        $dc_item_id = $this->Delivery_challan_items_model->ci_save($item_data, $id);

        if ($dc_item_id) {
            $item_info = $this->Delivery_challan_items_model->get_details(array("id" => $dc_item_id))->getRow();
            echo json_encode(array(
                "success"              => true,
                "delivery_challan_id"  => $item_info->delivery_challan_id,
                "data"                 => $this->_make_item_row($item_info),
                "id"                   => $dc_item_id,
                "message"              => app_lang('record_saved')
            ));
        } else {
            echo json_encode(array("success" => false, "message" => app_lang('error_occurred')));
        }
    }

    // -------------------------------------------------------------------------
    // Items - delete
    // -------------------------------------------------------------------------
    function delete_item()
    {
        $this->access_only_team_members();

        $this->validate_submitted_data(array("id" => "required|numeric"));

        $id        = $this->request->getPost('id');
        $item_info = $this->Delivery_challan_items_model->get_one($id);

        if ($this->Delivery_challan_items_model->delete($id)) {
            echo json_encode(array(
                "success"             => true,
                "delivery_challan_id" => $item_info->delivery_challan_id,
                "message"             => app_lang('record_deleted')
            ));
        } else {
            echo json_encode(array("success" => false, "message" => app_lang('record_cannot_be_deleted')));
        }
    }

    // -------------------------------------------------------------------------
    // Items - list data (AJAX)
    // -------------------------------------------------------------------------
    function item_list_data($delivery_challan_id = 0)
    {
        validate_numeric_value($delivery_challan_id);
        $this->access_only_team_members();

        $list_data = $this->Delivery_challan_items_model->get_details(array("delivery_challan_id" => $delivery_challan_id))->getResult();
        $result    = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_item_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    private function _make_item_row($data)
    {
        $item = "<strong>" . htmlspecialchars($data->title) . "</strong>";
        if ($data->description) {
            $item .= "<br /><span class='text-off'>" . nl2br(htmlspecialchars($data->description)) . "</span>";
        }

        $actions = modal_anchor(get_uri("delivery_challans/item_modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_item'), "data-post-id" => $data->id))
            . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("delivery_challans/delete_item"), "data-action" => "delete"));

        return array(
            $data->sort,
            $item,
            $data->hsn_sac_code ? htmlspecialchars($data->hsn_sac_code) : "-",
            to_decimal_format($data->quantity),
            $data->unit_type ? htmlspecialchars($data->unit_type) : "-",
            $actions
        );
    }

    // -------------------------------------------------------------------------
    // Item suggestion (reuse existing items from invoice_items)
    // -------------------------------------------------------------------------
    function get_dc_item_suggestion()
    {
        $key        = $this->request->getPost("q");
        $suggestion = array();

        $items = $this->Invoice_items_model->get_item_suggestion($key);
        foreach ($items as $item) {
            $suggestion[] = array("id" => $item->id, "text" => $item->title);
        }
        $suggestion[] = array("id" => "+", "text" => "+ " . app_lang("create_new_item"));

        echo json_encode($suggestion);
    }

    function get_dc_item_info_suggestion()
    {
        $item_id = $this->request->getPost("item_id");
        validate_numeric_value($item_id);

        $item = $this->Invoice_items_model->get_item_info_suggestion(array("item_id" => $item_id));
        if ($item) {
            echo json_encode(array("success" => true, "item_info" => $item));
        } else {
            echo json_encode(array("success" => false));
        }
    }

    // -------------------------------------------------------------------------
    // Preview
    // -------------------------------------------------------------------------
    function preview($id = 0)
    {
        validate_numeric_value($id);
        $this->access_only_team_members();

        $view_data = get_delivery_challan_making_data($id);
        if ($view_data) {
            return $this->template->rander("delivery_challans/delivery_challan_preview", $view_data);
        } else {
            show_404();
        }
    }

    // -------------------------------------------------------------------------
    // Download / View PDF
    // -------------------------------------------------------------------------
    function download_pdf($delivery_challan_id = 0, $public_key = "", $mode = "download")
    {
        validate_numeric_value($delivery_challan_id);
        if ($public_key === "view" || $public_key === "download") {
            $mode = $public_key;
            $public_key = "";
        }
        $data = get_delivery_challan_making_data($delivery_challan_id);
        if (!$data) {
            show_404();
        }

        $delivery_challan_info = $data['delivery_challan_info'];
        if ($public_key && $delivery_challan_info->public_key === $public_key) {
            // Authorized via public key
        } else {
            $this->access_only_team_members();
        }

        prepare_delivery_challan_pdf($data, $mode);
    }

    // -------------------------------------------------------------------------
    // Print
    // -------------------------------------------------------------------------
    function print_delivery_challan($id = 0)
    {
        validate_numeric_value($id);
        $this->access_only_team_members();

        $view_data = get_delivery_challan_making_data($id);
        if ($view_data) {
            echo json_encode(array(
                "success"     => true,
                "print_view"  => $this->template->view("delivery_challans/print_delivery_challan", $view_data)
            ));
        } else {
            echo json_encode(array("success" => false, "message" => app_lang('error_occurred')));
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------
    private function _get_clients_and_leads_dropdown()
    {
        $clients_dropdown = array("" => "-");
        $clients = $this->Clients_model->get_all_where(array("deleted" => 0), 0, 0, "is_lead")->getResult();
        foreach ($clients as $client) {
            $label = $client->is_lead
                ? (app_lang("lead") . ": " . $client->company_name)
                : (app_lang("client") . ": " . $client->company_name);
            $clients_dropdown[$client->id] = $label;
        }
        return $clients_dropdown;
    }
}
