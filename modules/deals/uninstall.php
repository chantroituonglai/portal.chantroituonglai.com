<?php defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();
$CI->db->query("ALTER TABLE " . db_prefix() . "`files` DROP `deal_id`;");
$CI->db->query("DROP TABLE IF EXISTS `tbl_deals`;");
$CI->db->query("DROP TABLE IF EXISTS `tbl_deals_comments`;");
$CI->db->query("DROP TABLE IF EXISTS `tbl_deals_email`;");
$CI->db->query("DROP TABLE IF EXISTS `tbl_deals_items`;");
$CI->db->query("DROP TABLE IF EXISTS `tbl_deals_mettings`;");
$CI->db->query("DROP TABLE IF EXISTS `tbl_deals_pipelines`;");
$CI->db->query("DROP TABLE IF EXISTS `tbl_deals_source`;");
$CI->db->query("DROP TABLE IF EXISTS `tbl_deals_stages`;");
$CI->db->query("DROP TABLE IF EXISTS `tbl_deal_activity_log`;");
$CI->db->query("DROP TABLE IF EXISTS `tbl_deal_calls`;");


