<?php

namespace App\Models;

class Purchase_order_items_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'purchase_order_items';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $po_items_table = $this->db->prefixTable('purchase_order_items');
        $purchase_orders_table = $this->db->prefixTable('purchase_orders');
        $clients_table = $this->db->prefixTable('clients');
        $where = "";
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $po_items_table.id=$id";
        }
        $purchase_order_id = $this->_get_clean_value($options, "purchase_order_id");
        if ($purchase_order_id) {
            $where .= " AND $po_items_table.purchase_order_id=$purchase_order_id";
        }

        $sql = "SELECT $po_items_table.*, (SELECT $clients_table.currency_symbol FROM $clients_table WHERE $clients_table.id=$purchase_orders_table.client_id LIMIT 1) AS currency_symbol
        FROM $po_items_table
        LEFT JOIN $purchase_orders_table ON $purchase_orders_table.id=$po_items_table.purchase_order_id
        WHERE $po_items_table.deleted=0 $where
        ORDER BY $po_items_table.sort ASC";
        return $this->db->query($sql);
    }

}
