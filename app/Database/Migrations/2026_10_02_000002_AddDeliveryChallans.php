<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeliveryChallans extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Create rise_delivery_challans table
        $db->query("CREATE TABLE IF NOT EXISTS `rise_delivery_challans` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `client_id` INT(11) NOT NULL,
            `challan_date` DATE NOT NULL,
            `delivery_date` DATE DEFAULT NULL,
            `reference_number` VARCHAR(100) DEFAULT NULL,
            `reference_date` DATE DEFAULT NULL,
            `destination` VARCHAR(255) DEFAULT NULL,
            `delivery_info` MEDIUMTEXT DEFAULT NULL,
            `note` MEDIUMTEXT DEFAULT NULL,
            `status` ENUM('draft','dispatched','delivered','cancelled') NOT NULL DEFAULT 'draft',
            `public_key` VARCHAR(10) DEFAULT NULL,
            `created_by` INT(11) NOT NULL DEFAULT 0,
            `company_id` INT(11) NOT NULL DEFAULT 0,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `client_id` (`client_id`),
            KEY `status` (`status`),
            KEY `deleted` (`deleted`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 2. Create rise_delivery_challan_items table
        $db->query("CREATE TABLE IF NOT EXISTS `rise_delivery_challan_items` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `delivery_challan_id` INT(11) NOT NULL,
            `title` TEXT NOT NULL,
            `description` TEXT DEFAULT NULL,
            `hsn_sac_code` VARCHAR(50) DEFAULT NULL,
            `quantity` DOUBLE NOT NULL DEFAULT 1,
            `unit_type` VARCHAR(20) DEFAULT NULL,
            `sort` INT(11) NOT NULL DEFAULT 0,
            `item_id` INT(11) NOT NULL DEFAULT 0,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `delivery_challan_id` (`delivery_challan_id`),
            KEY `deleted` (`deleted`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $db->query("DROP TABLE IF EXISTS `rise_delivery_challan_items`");
        $db->query("DROP TABLE IF EXISTS `rise_delivery_challans`");
    }
}
