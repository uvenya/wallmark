<?php

namespace App\Controllers;

class Purchase_orders extends Security_Controller {

    function __construct() {
        parent::__construct();
        $this->init_permission_checker("purchase_order");
    }

    function index() {
        $this->access_only_team_members();

        $view_data = array();
        return $this->template->rander("purchase_orders/index", $view_data);
    }

    /* load new purchase order modal */
    function modal_form() {
        $this->access_only_team_members();

        $this->validate_submitted_data(array(
            "id" => "numeric",
            "client_id" => "numeric"
        ));

        $id = $this->request->getPost('id');
        $is_clone = $this->request->getPost('is_clone');

        $client_id = $this->request->getPost('client_id');
        $view_data['model_info'] = $this->Purchase_orders_model->get_one($id);

        $project_client_id = $client_id;
        if ($view_data['model_info']->client_id) {
            $project_client_id = $view_data['model_info']->client_id;
        }

        // make the dropdown lists
        $taxes = $this->Taxes_model->get_all_where(array("deleted" => 0), 1000000, 0, "percentage")->getResult();
        $taxes_dropdown = array("" => "-");
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

        $selected_tax_id2 = "";
        $custom_tax_percentage2 = "";
        if (!empty($view_data['model_info']->custom_tax_percentage2)) {
            $selected_tax_id2 = "other";
            $custom_tax_percentage2 = to_decimal_format($view_data['model_info']->custom_tax_percentage2);
        } else if (!empty($view_data['model_info']->tax_id2)) {
            $selected_tax_id2 = $view_data['model_info']->tax_id2;
        }
        $view_data['selected_tax_id2'] = $selected_tax_id2;
        $view_data['custom_tax_percentage2'] = $custom_tax_percentage2;

        $view_data['clients_dropdown'] = $this->_get_clients_and_leads_dropdown();

        $client_info = $this->Clients_model->get_one($view_data['model_info']->client_id);
        if ($client_info && $client_info->is_lead) {
            $client_id = $client_info->id;
        }

        $view_data['client_id'] = $client_id;
        $view_data['is_clone'] = $is_clone;

        $view_data['companies_dropdown'] = $this->_get_companies_dropdown();
        if (!$view_data['model_info']->company_id) {
            $view_data['model_info']->company_id = get_default_company_id();
        }

        return $this->template->view('purchase_orders/modal_form', $view_data);
    }

    private function _get_clients_and_leads_dropdown() {
        $clients_dropdown = array("" => "-");
        $clients = $this->Clients_model->get_all_where(array("deleted" => 0), 0, 0, "is_lead")->getResult();

        foreach ($clients as $client) {
            $company_name = $client->is_lead ? (app_lang("lead") . ": " . $client->company_name) : (app_lang("client") . ": " . $client->company_name);
            $clients_dropdown[$client->id] = $company_name;
        }

        return $clients_dropdown;
    }

    /* add, edit or clone a purchase order */
    function save() {
        $this->access_only_team_members();

        $this->validate_submitted_data(array(
            "id" => "numeric",
            "po_client_id" => "required|numeric",
            "purchase_order_date" => "required"
        ));

        $client_id = $this->request->getPost('po_client_id');
        $id = $this->request->getPost('id');
        $is_clone = $this->request->getPost('is_clone');

        $tax_id_input = $this->request->getPost('tax_id');
        $tax_id2_input = $this->request->getPost('tax_id2');

        $tax_id = 0;
        $custom_tax_percentage = null;
        if ($tax_id_input === "other") {
            $custom_pct = unformat_currency($this->request->getPost('custom_tax_percentage'));
            if ($custom_pct > 0) {
                $custom_tax_percentage = $custom_pct;
                $existing_tax = $this->Taxes_model->get_one_where(array("percentage" => $custom_pct, "deleted" => 0));
                if ($existing_tax && $existing_tax->id) {
                    $tax_id = $existing_tax->id;
                } else {
                    $pct_lbl = rtrim(rtrim(number_format($custom_pct, 2, '.', ''), '0'), '.');
                    $tax_data = array(
                        "title" => "Tax (" . $pct_lbl . "%)",
                        "percentage" => $custom_pct
                    );
                    $tax_id = $this->Taxes_model->ci_save($tax_data);
                }
            }
        } else if ($tax_id_input) {
            $tax_id = (int) $tax_id_input;
        }

        $tax_id2 = 0;
        $custom_tax_percentage2 = null;
        if ($tax_id2_input === "other") {
            $custom_pct2 = unformat_currency($this->request->getPost('custom_tax_percentage2'));
            if ($custom_pct2 > 0) {
                $custom_tax_percentage2 = $custom_pct2;
                $existing_tax2 = $this->Taxes_model->get_one_where(array("percentage" => $custom_pct2, "deleted" => 0));
                if ($existing_tax2 && $existing_tax2->id) {
                    $tax_id2 = $existing_tax2->id;
                } else {
                    $pct_lbl2 = rtrim(rtrim(number_format($custom_pct2, 2, '.', ''), '0'), '.');
                    $tax_data2 = array(
                        "title" => "Tax (" . $pct_lbl2 . "%)",
                        "percentage" => $custom_pct2
                    );
                    $tax_id2 = $this->Taxes_model->ci_save($tax_data2);
                }
            }
        } else if ($tax_id2_input) {
            $tax_id2 = (int) $tax_id2_input;
        }

        $po_data = array(
            "client_id" => $client_id,
            "purchase_order_date" => $this->request->getPost('purchase_order_date'),
            "valid_until" => $this->request->getPost('valid_until'),
            "reference_number" => $this->request->getPost('reference_number'),
            "reference_date" => $this->request->getPost('reference_date'),
            "delivery_info" => $this->request->getPost('delivery_info'),
            "tax_id" => $tax_id,
            "custom_tax_percentage" => $custom_tax_percentage,
            "tax_id2" => $tax_id2,
            "custom_tax_percentage2" => $custom_tax_percentage2,
            "company_id" => $this->request->getPost('company_id') ? $this->request->getPost('company_id') : get_default_company_id(),
            "note" => $this->request->getPost('po_note'),
            "terms_conditions" => $this->request->getPost('po_terms_conditions')
        );

        if (!$id) {
            $po_data["public_key"] = make_random_string();
            $po_data["created_by"] = $this->login_user->id;
        }

        $main_po_id = "";
        if ($is_clone && $id) {
            $main_po_id = $id;
            $id = "";
            $main_po_info = $this->Purchase_orders_model->get_one($main_po_id);
            $po_data["discount_amount"] = $main_po_info->discount_amount;
            $po_data["discount_amount_type"] = $main_po_info->discount_amount_type;
            $po_data["discount_type"] = $main_po_info->discount_type;
            $po_data["terms_conditions"] = $main_po_info->terms_conditions;
            $po_data["custom_tax_percentage"] = $main_po_info->custom_tax_percentage;
            $po_data["custom_tax_percentage2"] = $main_po_info->custom_tax_percentage2;
            $po_data["public_key"] = make_random_string();
        }

        $po_id = $this->Purchase_orders_model->ci_save($po_data, $id);
        if ($po_id) {
            if ($is_clone && $main_po_id) {
                $items = $this->Purchase_order_items_model->get_all_where(array("purchase_order_id" => $main_po_id, "deleted" => 0))->getResult();
                foreach ($items as $item) {
                    $item_data = (array) $item;
                    unset($item_data["id"]);
                    $item_data['purchase_order_id'] = $po_id;
                    $this->Purchase_order_items_model->ci_save($item_data);
                }
            }

            echo json_encode(array("success" => true, "data" => $this->_row_data($po_id), 'id' => $po_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function update_status($id, $status) {
        $this->access_only_team_members();
        validate_numeric_value($id);

        if ($status == "accepted" || $status == "declined" || $status == "sent" || $status == "draft") {
            $data = array("status" => $status);
            if ($status == "accepted") {
                $data["accepted_by"] = $this->login_user->id;
            }
            $this->Purchase_orders_model->ci_save($data, $id);
            echo json_encode(array("success" => true, 'message' => app_lang('record_saved')));
        }
    }

    function delete() {
        $this->access_only_team_members();

        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));

        $id = $this->request->getPost('id');
        if ($this->Purchase_orders_model->delete($id)) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    function list_data() {
        $this->access_only_team_members();

        $options = array(
            "status" => $this->request->getPost("status"),
            "start_date" => $this->request->getPost("start_date"),
            "end_date" => $this->request->getPost("end_date")
        );

        $list_data = $this->Purchase_orders_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }

        echo json_encode(array("data" => $result));
    }

    private function _row_data($id) {
        $options = array("id" => $id);
        $data = $this->Purchase_orders_model->get_details($options)->getRow();
        return $this->_make_row($data);
    }

    private function _make_row($data) {
        $po_url = anchor(get_uri("purchase_orders/view/" . $data->id), get_purchase_order_id($data->id));

        $client_url = "";
        if ($data->is_lead) {
            $client_url = anchor(get_uri("leads/view/" . $data->client_id), $data->company_name);
        } else {
            $client_url = anchor(get_uri("clients/view/" . $data->client_id), $data->company_name);
        }

        $status_class = "bg-secondary";
        if ($data->status == "accepted") {
            $status_class = "bg-success";
        } else if ($data->status == "declined") {
            $status_class = "bg-danger";
        } else if ($data->status == "sent") {
            $status_class = "bg-primary";
        }
        $status = "<span class='badge $status_class'>" . ucfirst($data->status) . "</span>";

        $actions = modal_anchor(get_uri("purchase_orders/modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_purchase_order'), "data-post-id" => $data->id))
            . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_purchase_order'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("purchase_orders/delete"), "data-action" => "delete"));

        return array(
            $data->id,
            $po_url,
            $client_url,
            $data->purchase_order_date,
            format_to_date($data->purchase_order_date, false),
            $data->valid_until ? $data->valid_until : "",
            $data->valid_until ? format_to_date($data->valid_until, false) : "-",
            $data->reference_number ? htmlspecialchars($data->reference_number) : "-",
            to_currency($data->purchase_order_value, $data->currency_symbol),
            $status,
            $actions
        );
    }

    /* view purchase order details */
    function view($id = 0) {
        validate_numeric_value($id);
        $this->access_only_team_members();

        if ($id) {
            $view_data = get_purchase_order_making_data($id);
            if ($view_data) {
                $view_data["purchase_order_id"] = $id;
                return $this->template->rander("purchase_orders/view", $view_data);
            } else {
                show_404();
            }
        }
    }

    private function _get_total_view($purchase_order_id = 0) {
        $view_data["purchase_order_total_summary"] = $this->Purchase_orders_model->get_purchase_order_total_summary($purchase_order_id);
        $view_data["purchase_order_id"] = $purchase_order_id;
        return $this->template->view('purchase_orders/purchase_order_total_section', $view_data);
    }

    /* discount modal */
    function discount_modal_form() {
        $this->access_only_team_members();

        $this->validate_submitted_data(array(
            "purchase_order_id" => "required|numeric"
        ));

        $purchase_order_id = $this->request->getPost('purchase_order_id');
        $view_data['model_info'] = $this->Purchase_orders_model->get_one($purchase_order_id);
        return $this->template->view('purchase_orders/discount_modal_form', $view_data);
    }

    function save_discount() {
        $this->access_only_team_members();

        $this->validate_submitted_data(array(
            "purchase_order_id" => "required|numeric",
            "discount_type" => "required",
            "discount_amount" => "numeric",
            "discount_amount_type" => "required"
        ));

        $purchase_order_id = $this->request->getPost('purchase_order_id');
        $data = array(
            "discount_type" => $this->request->getPost('discount_type'),
            "discount_amount" => $this->request->getPost('discount_amount'),
            "discount_amount_type" => $this->request->getPost('discount_amount_type')
        );

        $data = clean_data($data);
        if ($this->Purchase_orders_model->ci_save($data, $purchase_order_id)) {
            echo json_encode(array("success" => true, "purchase_order_total_view" => $this->_get_total_view($purchase_order_id), 'message' => app_lang('record_saved'), "purchase_order_id" => $purchase_order_id));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    /* items */
    function item_modal_form() {
        $this->access_only_team_members();

        $this->validate_submitted_data(array(
            "id" => "numeric",
            "purchase_order_id" => "numeric"
        ));

        $purchase_order_id = $this->request->getPost('purchase_order_id');
        $view_data['model_info'] = $this->Purchase_order_items_model->get_one($this->request->getPost('id'));
        if (!$purchase_order_id) {
            $purchase_order_id = $view_data['model_info']->purchase_order_id;
        }
        $view_data['purchase_order_id'] = $purchase_order_id;

        return $this->template->view('purchase_orders/item_modal_form', $view_data);
    }

    function save_item() {
        $this->access_only_team_members();

        $this->validate_submitted_data(array(
            "id" => "numeric",
            "purchase_order_id" => "required|numeric"
        ));

        $purchase_order_id = $this->request->getPost('purchase_order_id');
        $id = $this->request->getPost('id');
        $rate = unformat_currency($this->request->getPost('po_item_rate'));
        $quantity = unformat_currency($this->request->getPost('po_item_quantity'));
        $item_title = $this->request->getPost('po_item_title');

        $item_id = $this->request->getPost('item_id');

        $item_data = array(
            "purchase_order_id" => $purchase_order_id,
            "title" => $item_title,
            "description" => $this->request->getPost('po_item_description'),
            "quantity" => $quantity,
            "unit_type" => $this->request->getPost('po_unit_type'),
            "hsn_sac_code" => $this->request->getPost('po_item_hsn_sac_code'),
            "rate" => $rate,
            "total" => $rate * $quantity,
        );

        if ($item_id) {
            $item_data["item_id"] = $item_id;
        }

        $po_item_id = $this->Purchase_order_items_model->ci_save($item_data, $id);
        if ($po_item_id) {
            $item_info = $this->Purchase_order_items_model->get_details(array("id" => $po_item_id))->getRow();
            echo json_encode(array(
                "success" => true,
                "purchase_order_id" => $item_info->purchase_order_id,
                "data" => $this->_make_item_row($item_info),
                "purchase_order_total_view" => $this->_get_total_view($item_info->purchase_order_id),
                'id' => $po_item_id,
                'message' => app_lang('record_saved')
            ));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function delete_item() {
        $this->access_only_team_members();

        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));

        $id = $this->request->getPost('id');
        $item_info = $this->Purchase_order_items_model->get_one($id);

        if ($this->Purchase_order_items_model->delete($id)) {
            echo json_encode(array(
                "success" => true,
                "purchase_order_id" => $item_info->purchase_order_id,
                "purchase_order_total_view" => $this->_get_total_view($item_info->purchase_order_id),
                'message' => app_lang('record_deleted')
            ));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    function item_list_data($purchase_order_id = 0) {
        validate_numeric_value($purchase_order_id);
        $this->access_only_team_members();

        $list_data = $this->Purchase_order_items_model->get_details(array("purchase_order_id" => $purchase_order_id))->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_item_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    private function _make_item_row($data) {
        $item = "<strong>" . htmlspecialchars($data->title) . "</strong>";
        if ($data->description) {
            $item .= "<br /><span class='text-off'>" . nl2br(htmlspecialchars($data->description)) . "</span>";
        }

        $actions = modal_anchor(get_uri("purchase_orders/item_modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_item'), "data-post-id" => $data->id))
            . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("purchase_orders/delete_item"), "data-action" => "delete"));

        return array(
            $data->sort,
            $item,
            $data->hsn_sac_code ? htmlspecialchars($data->hsn_sac_code) : "-",
            to_decimal_format($data->quantity),
            $data->unit_type ? htmlspecialchars($data->unit_type) : "-",
            to_currency($data->rate, $data->currency_symbol),
            to_currency($data->total, $data->currency_symbol),
            $actions
        );
    }

    function get_po_item_suggestion() {
        $key = $this->request->getPost("q");
        $suggestion = array();

        $items = $this->Invoice_items_model->get_item_suggestion($key);

        foreach ($items as $item) {
            $suggestion[] = array("id" => $item->id, "text" => $item->title);
        }

        $suggestion[] = array("id" => "+", "text" => "+ " . app_lang("create_new_item"));

        echo json_encode($suggestion);
    }

    function get_po_item_info_suggestion() {
        $item_id = $this->request->getPost("item_id");
        validate_numeric_value($item_id);

        $item = $this->Invoice_items_model->get_item_info_suggestion(array("item_id" => $item_id));
        if ($item) {
            $item->rate = $item->rate ? to_decimal_format($item->rate) : "";
            echo json_encode(array("success" => true, "item_info" => $item));
        } else {
            echo json_encode(array("success" => false));
        }
    }

    /* preview purchase order */
    function preview($id = 0) {
        validate_numeric_value($id);
        $this->access_only_team_members();

        $view_data = get_purchase_order_making_data($id);
        if ($view_data) {
            return $this->template->rander("purchase_orders/purchase_order_preview", $view_data);
        } else {
            show_404();
        }
    }

    /* download or view PDF */
    function download_pdf($purchase_order_id = 0, $mode = "download") {
        validate_numeric_value($purchase_order_id);
        $this->access_only_team_members();

        $data = get_purchase_order_making_data($purchase_order_id);
        if ($data) {
            prepare_purchase_order_pdf($data, $mode);
        } else {
            show_404();
        }
    }

    /* print purchase order */
    function print_purchase_order($id = 0) {
        validate_numeric_value($id);
        $this->access_only_team_members();

        $view_data = get_purchase_order_making_data($id);
        if ($view_data) {
            echo json_encode(array("success" => true, "print_view" => $this->template->view("purchase_orders/print_purchase_order", $view_data)));
        } else {
            echo json_encode(array("success" => false, "message" => app_lang('error_occurred')));
        }
    }
}
