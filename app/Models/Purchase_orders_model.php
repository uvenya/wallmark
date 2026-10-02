<?php

namespace App\Models;

class Purchase_orders_model extends Crud_model
{

    protected $table = null;

    function __construct()
    {
        $this->table = 'purchase_orders';
        parent::__construct($this->table);
    }

    function get_details($options = array())
    {
        $purchase_orders_table = $this->db->prefixTable('purchase_orders');
        $clients_table = $this->db->prefixTable('clients');
        $taxes_table = $this->db->prefixTable('taxes');
        $po_items_table = $this->db->prefixTable('purchase_order_items');
        $users_table = $this->db->prefixTable('users');
        $projects_table = $this->db->prefixTable('projects');

        $where = "";
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $purchase_orders_table.id=$id";
        }
        $client_id = $this->_get_clean_value($options, "client_id");
        if ($client_id) {
            $where .= " AND $purchase_orders_table.client_id=$client_id";
        }

        $start_date = $this->_get_clean_value($options, "start_date");
        $end_date = $this->_get_clean_value($options, "end_date");
        if ($start_date && $end_date) {
            $where .= " AND ($purchase_orders_table.purchase_order_date BETWEEN '$start_date' AND '$end_date') ";
        }

        $effective_tax_pct_1 = "IFNULL($purchase_orders_table.custom_tax_percentage, tax_table.percentage)";
        $effective_tax_pct_2 = "IFNULL($purchase_orders_table.custom_tax_percentage2, tax_table2.percentage)";

        $after_tax_1 = "(IFNULL($effective_tax_pct_1,0)/100*IFNULL(items_table.purchase_order_value,0))";
        $after_tax_2 = "(IFNULL($effective_tax_pct_2,0)/100*IFNULL(items_table.purchase_order_value,0))";

        $discountable_po_value = "IF($purchase_orders_table.discount_type='after_tax', (IFNULL(items_table.purchase_order_value,0) + $after_tax_1 + $after_tax_2), IFNULL(items_table.purchase_order_value,0) )";

        $discount_amount = "IF($purchase_orders_table.discount_amount_type='percentage', IFNULL($purchase_orders_table.discount_amount,0)/100* $discountable_po_value, $purchase_orders_table.discount_amount)";

        $before_tax_1 = "(IFNULL($effective_tax_pct_1,0)/100* (IFNULL(items_table.purchase_order_value,0)- $discount_amount))";
        $before_tax_2 = "(IFNULL($effective_tax_pct_2,0)/100* (IFNULL(items_table.purchase_order_value,0)- $discount_amount))";

        $purchase_order_value_calculation = "(
            IFNULL(items_table.purchase_order_value,0)+
            IF($purchase_orders_table.discount_type='before_tax',  ($before_tax_1+ $before_tax_2), ($after_tax_1 + $after_tax_2))
            - $discount_amount
           )";

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $purchase_orders_table.status='$status'";
        }

        $sql = "SELECT $purchase_orders_table.*, $clients_table.currency, $clients_table.currency_symbol, $clients_table.company_name, $clients_table.is_lead,
           CONCAT($users_table.first_name, ' ',$users_table.last_name) AS signer_name, $users_table.email AS signer_email,
           $purchase_order_value_calculation AS purchase_order_value, $effective_tax_pct_1 AS tax_percentage, $effective_tax_pct_2 AS tax_percentage2,
           $projects_table.title AS project_title
        FROM $purchase_orders_table
        LEFT JOIN $clients_table ON $clients_table.id= $purchase_orders_table.client_id
        LEFT JOIN $users_table ON $users_table.id= $purchase_orders_table.accepted_by
        LEFT JOIN (SELECT $taxes_table.* FROM $taxes_table) AS tax_table ON tax_table.id = $purchase_orders_table.tax_id
        LEFT JOIN (SELECT $taxes_table.* FROM $taxes_table) AS tax_table2 ON tax_table2.id = $purchase_orders_table.tax_id2 
        LEFT JOIN (SELECT purchase_order_id, SUM(total) AS purchase_order_value FROM $po_items_table WHERE deleted=0 GROUP BY purchase_order_id) AS items_table ON items_table.purchase_order_id = $purchase_orders_table.id 
        LEFT JOIN $projects_table ON $projects_table.id= $purchase_orders_table.project_id
        WHERE $purchase_orders_table.deleted=0 $where";

        return $this->db->query($sql);
    }

    function get_purchase_order_total_summary($purchase_order_id = 0)
    {
        $po_items_table = $this->db->prefixTable('purchase_order_items');
        $purchase_orders_table = $this->db->prefixTable('purchase_orders');
        $clients_table = $this->db->prefixTable('clients');
        $taxes_table = $this->db->prefixTable('taxes');

        $purchase_order_id = $this->_get_clean_value($purchase_order_id);

        $item_sql = "SELECT SUM($po_items_table.total) AS purchase_order_subtotal
        FROM $po_items_table
        LEFT JOIN $purchase_orders_table ON $purchase_orders_table.id= $po_items_table.purchase_order_id    
        WHERE $po_items_table.deleted=0 AND $po_items_table.purchase_order_id=$purchase_order_id AND $purchase_orders_table.deleted=0";
        $item = $this->db->query($item_sql)->getRow();

        $po_sql = "SELECT $purchase_orders_table.*, tax_table.percentage AS tax_percentage, tax_table.title AS tax_name,
            tax_table2.percentage AS tax_percentage2, tax_table2.title AS tax_name2
        FROM $purchase_orders_table
        LEFT JOIN (SELECT $taxes_table.* FROM $taxes_table) AS tax_table ON tax_table.id = $purchase_orders_table.tax_id
        LEFT JOIN (SELECT $taxes_table.* FROM $taxes_table) AS tax_table2 ON tax_table2.id = $purchase_orders_table.tax_id2
        WHERE $purchase_orders_table.deleted=0 AND $purchase_orders_table.id=$purchase_order_id";
        $purchase_order = $this->db->query($po_sql)->getRow();

        $client_sql = "SELECT $clients_table.currency_symbol, $clients_table.currency FROM $clients_table WHERE $clients_table.id=$purchase_order->client_id";
        $client = $this->db->query($client_sql)->getRow();

        $result = new \stdClass();
        $result->purchase_order_subtotal = $item->purchase_order_subtotal ? $item->purchase_order_subtotal : 0;

        // Custom tax percentage support
        $tax_percentage = $purchase_order->tax_percentage;
        $tax_name = $purchase_order->tax_name;
        if (isset($purchase_order->custom_tax_percentage) && $purchase_order->custom_tax_percentage !== null && $purchase_order->custom_tax_percentage !== '') {
            $tax_percentage = (float) $purchase_order->custom_tax_percentage;
            if (!$tax_name) {
                $pct_lbl = rtrim(rtrim(number_format($tax_percentage, 2, '.', ''), '0'), '.');
                $tax_name = "CGST (" . $pct_lbl . "%)";
            }
        }
        if ($tax_name && preg_match('/^Tax\b/i', $tax_name)) {
            $tax_name = preg_replace('/^Tax\b/i', 'CGST', $tax_name);
        }

        $tax_percentage2 = $purchase_order->tax_percentage2;
        $tax_name2 = $purchase_order->tax_name2;
        if (isset($purchase_order->custom_tax_percentage2) && $purchase_order->custom_tax_percentage2 !== null && $purchase_order->custom_tax_percentage2 !== '') {
            $tax_percentage2 = (float) $purchase_order->custom_tax_percentage2;
            if (!$tax_name2) {
                $pct_lbl2 = rtrim(rtrim(number_format($tax_percentage2, 2, '.', ''), '0'), '.');
                $tax_name2 = "SGST (" . $pct_lbl2 . "%)";
            }
        }
        if ($tax_name2 && preg_match('/^Tax\b/i', $tax_name2)) {
            $tax_name2 = preg_replace('/^Tax\b/i', 'SGST', $tax_name2);
        }

        $result->tax_percentage = $tax_percentage;
        $result->tax_percentage2 = $tax_percentage2;
        $result->tax_name = $tax_name;
        $result->tax_name2 = $tax_name2;
        $result->tax = 0;
        $result->tax2 = 0;

        $po_subtotal = $result->purchase_order_subtotal;
        $po_subtotal_for_taxes = $po_subtotal;
        if ($purchase_order->discount_type == "before_tax") {
            $po_subtotal_for_taxes = $po_subtotal - ($purchase_order->discount_amount_type == "percentage" ? ($po_subtotal * ($purchase_order->discount_amount / 100)) : $purchase_order->discount_amount);
        }

        if ($result->tax_percentage) {
            $result->tax = $po_subtotal_for_taxes * ($result->tax_percentage / 100);
        }
        if ($result->tax_percentage2) {
            $result->tax2 = $po_subtotal_for_taxes * ($result->tax_percentage2 / 100);
        }
        $purchase_order_total = $po_subtotal + $result->tax + $result->tax2;

        //get discount total
        $result->discount_total = 0;
        if ($purchase_order->discount_type == "after_tax") {
            $po_subtotal = $purchase_order_total;
        }

        $result->discount_total = $purchase_order->discount_amount_type == "percentage" ? ($po_subtotal * ($purchase_order->discount_amount / 100)) : $purchase_order->discount_amount;
        $result->discount_type = $purchase_order->discount_type;
        $result->discount_total = is_null($result->discount_total) ? 0 : $result->discount_total;
        $result->purchase_order_total = $purchase_order_total - number_format($result->discount_total, 2, ".", "");

        $result->currency_symbol = ($client && $client->currency_symbol) ? $client->currency_symbol : get_setting("currency_symbol");
        $result->currency = ($client && $client->currency) ? $client->currency : get_setting("default_currency");
        return $result;
    }

    function get_purchase_order_last_id()
    {
        $purchase_orders_table = $this->db->prefixTable('purchase_orders');
        $sql = "SELECT MAX($purchase_orders_table.id) AS last_id FROM $purchase_orders_table";
        return $this->db->query($sql)->getRow()->last_id;
    }
}
