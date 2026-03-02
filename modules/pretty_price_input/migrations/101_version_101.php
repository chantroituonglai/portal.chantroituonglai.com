<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$table = db_prefix() . 'ppi_line_discounts';
if (!$CI->db->table_exists($table)) {
	$CI->db->query(
		'CREATE TABLE ' . $table . ' (
			id INT NOT NULL AUTO_INCREMENT,
			itemid INT NOT NULL,
			rel_type VARCHAR(20) NOT NULL,
			discount_percent DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			discount_type VARCHAR(20) NOT NULL DEFAULT "percent",
			discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
			tax_mode VARCHAR(20) NOT NULL DEFAULT "before_tax",
			created_at DATETIME NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_itemid (itemid),
			KEY rel_type_idx (rel_type)
		) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set
	);
}
// Defensive: ensure columns exist if table pre-existed
$fields = $CI->db->list_fields($table);
if (!in_array('discount_type', $fields)) {
	$CI->db->query('ALTER TABLE ' . $table . ' ADD `discount_type` VARCHAR(20) NOT NULL DEFAULT "percent" AFTER `discount_percent`');
}
if (!in_array('discount_amount', $fields)) {
	$CI->db->query('ALTER TABLE ' . $table . ' ADD `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `discount_type`');
}
if (!in_array('tax_mode', $fields)) {
	$CI->db->query('ALTER TABLE ' . $table . ' ADD `tax_mode` VARCHAR(20) NOT NULL DEFAULT "before_tax" AFTER `discount_amount`');
}

update_option('ppi_db_version', 101);
