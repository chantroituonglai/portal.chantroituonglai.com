<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pretty_price_input extends AdminController
{
	public function __construct()
	{
		parent::__construct();
	}

	public function get_discounts()
	{
		if (!is_staff_logged_in()) {
			show_404();
		}
		$ids = $this->input->post('ids');
		if (!is_array($ids)) { $ids = []; }
		$ids = array_map('intval', $ids);
		$result = [];
		if (!empty($ids)) {
			$rows = $this->db->where_in('itemid', $ids)->get(db_prefix().'ppi_line_discounts')->result_array();
			foreach ($rows as $r) {
				$result[(int)$r['itemid']] = [
					'percent'  => isset($r['discount_percent']) ? (float) $r['discount_percent'] : 0.0,
					'type'     => isset($r['discount_type']) ? $r['discount_type'] : 'percent',
					'amount'   => isset($r['discount_amount']) ? (float) $r['discount_amount'] : 0.0,
					'tax_mode' => isset($r['tax_mode']) ? $r['tax_mode'] : 'before_tax',
				];
			}
		}
		echo json_encode(['success' => true, 'data' => $result]);
		die;
	}
}
