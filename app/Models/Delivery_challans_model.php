<?php

namespace App\Models;

class Delivery_challans_model extends Crud_model
{
    protected $table = null;

    function __construct()
    {
        $this->table = 'delivery_challans';
        parent::__construct($this->table);
    }

    function get_details($options = array())
    {
        $dc_table      = $this->db->prefixTable('delivery_challans');
        $clients_table = $this->db->prefixTable('clients');
        $taxes_table   = $this->db->prefixTable('taxes');
        $users_table   = $this->db->prefixTable('users');

        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $dc_table.id=$id";
        }

        $client_id = $this->_get_clean_value($options, "client_id");
        if ($client_id) {
            $where .= " AND $dc_table.client_id=$client_id";
        }

        $start_date = $this->_get_clean_value($options, "start_date");
        $end_date   = $this->_get_clean_value($options, "end_date");
        if ($start_date && $end_date) {
            $where .= " AND ($dc_table.challan_date BETWEEN '$start_date' AND '$end_date') ";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $dc_table.status='$status'";
        }

        $sql = "SELECT $dc_table.*, $clients_table.company_name, $clients_table.is_lead,
                $taxes_table.title AS tax_name, $taxes_table.percentage AS tax_percentage,
                CONCAT($users_table.first_name, ' ', $users_table.last_name) AS created_by_name
            FROM $dc_table
            LEFT JOIN $clients_table ON $clients_table.id = $dc_table.client_id
            LEFT JOIN $taxes_table ON $taxes_table.id = $dc_table.tax_id
            LEFT JOIN $users_table ON $users_table.id = $dc_table.created_by
            WHERE $dc_table.deleted=0 $where
            ORDER BY $dc_table.id DESC";

        return $this->db->query($sql);
    }

    function get_last_id()
    {
        $dc_table = $this->db->prefixTable('delivery_challans');
        $sql = "SELECT MAX($dc_table.id) AS last_id FROM $dc_table";
        return $this->db->query($sql)->getRow()->last_id;
    }
}
