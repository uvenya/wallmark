<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPurchaseOrders extends Migration {

    public function up() {
        $db = \Config\Database::connect();

        // 1. Create rise_purchase_orders table
        $db->query("CREATE TABLE IF NOT EXISTS `rise_purchase_orders` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `client_id` INT(11) NOT NULL,
            `purchase_order_date` DATE NOT NULL,
            `valid_until` DATE DEFAULT NULL,
            `reference_number` VARCHAR(100) DEFAULT NULL,
            `reference_date` DATE DEFAULT NULL,
            `delivery_info` MEDIUMTEXT DEFAULT NULL,
            `note` MEDIUMTEXT DEFAULT NULL,
            `terms_conditions` MEDIUMTEXT DEFAULT NULL,
            `status` ENUM('draft','sent','accepted','declined') NOT NULL DEFAULT 'draft',
            `tax_id` INT(11) NOT NULL DEFAULT 0,
            `custom_tax_percentage` DECIMAL(7,2) DEFAULT NULL,
            `tax_id2` INT(11) NOT NULL DEFAULT 0,
            `custom_tax_percentage2` DECIMAL(7,2) DEFAULT NULL,
            `discount_type` ENUM('before_tax','after_tax') NOT NULL DEFAULT 'before_tax',
            `discount_amount` DOUBLE NOT NULL DEFAULT 0,
            `discount_amount_type` ENUM('percentage','fixed_amount') NOT NULL DEFAULT 'percentage',
            `content` MEDIUMTEXT DEFAULT NULL,
            `public_key` VARCHAR(10) DEFAULT NULL,
            `accepted_by` INT(11) NOT NULL DEFAULT 0,
            `created_by` INT(11) NOT NULL DEFAULT 0,
            `company_id` INT(11) NOT NULL DEFAULT 0,
            `project_id` INT(11) DEFAULT 0,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `client_id` (`client_id`),
            KEY `status` (`status`),
            KEY `deleted` (`deleted`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 2. Create rise_purchase_order_items table
        $db->query("CREATE TABLE IF NOT EXISTS `rise_purchase_order_items` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `purchase_order_id` INT(11) NOT NULL,
            `title` TEXT NOT NULL,
            `description` TEXT DEFAULT NULL,
            `hsn_sac_code` VARCHAR(50) DEFAULT NULL,
            `quantity` DOUBLE NOT NULL DEFAULT 1,
            `unit_type` VARCHAR(20) DEFAULT NULL,
            `rate` DOUBLE NOT NULL DEFAULT 0,
            `total` DOUBLE NOT NULL DEFAULT 0,
            `sort` INT(11) NOT NULL DEFAULT 0,
            `item_id` INT(11) NOT NULL DEFAULT 0,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `purchase_order_id` (`purchase_order_id`),
            KEY `deleted` (`deleted`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function down() {
        $db = \Config\Database::connect();
        $db->query("DROP TABLE IF EXISTS `rise_purchase_order_items`");
        $db->query("DROP TABLE IF EXISTS `rise_purchase_orders`");
    }
}
