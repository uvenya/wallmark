<?php

namespace App\Models;

class Delivery_challan_items_model extends Crud_model
{
    protected $table = null;

    function __construct()
    {
        $this->table = 'delivery_challan_items';
        parent::__construct($this->table);
    }

    function get_details($options = array())
    {
        $items_table = $this->db->prefixTable('delivery_challan_items');
        $dc_table    = $this->db->prefixTable('delivery_challans');

        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $items_table.id=$id";
        }

        $delivery_challan_id = $this->_get_clean_value($options, "delivery_challan_id");
        if ($delivery_challan_id) {
            $where .= " AND $items_table.delivery_challan_id=$delivery_challan_id";
        }

        $sql = "SELECT $items_table.*
            FROM $items_table
            LEFT JOIN $dc_table ON $dc_table.id = $items_table.delivery_challan_id
            WHERE $items_table.deleted=0 $where
            ORDER BY $items_table.sort ASC";

        return $this->db->query($sql);
    }
}
