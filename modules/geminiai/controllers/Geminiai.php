<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Geminiai extends AdminController
{
	public function __construct()
	{
		parent::__construct();
	}

	public function bulk_classify()
	{
		log_message('error', '[GEMINIAI] bulk_classify start');
		if (!is_staff_logged_in()) {
			log_message('error', '[GEMINIAI] bulk_classify denied: not staff');
			ajax_access_denied();
		}
		if (!is_admin() && !has_permission('tickets', '', 'view')) {
			log_message('error', '[GEMINIAI] bulk_classify denied: no permission');
			ajax_access_denied();
		}
		if (!$this->input->is_ajax_request()) {
			log_message('error', '[GEMINIAI] bulk_classify bad request (non-ajax)');
			show_error('Bad Request', 400);
		}

		// Feature toggle
		if ((int) get_option('geminiai_ticket_classify_enabled') !== 1) {
			log_message('error', '[GEMINIAI] bulk_classify disabled via settings');
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['success' => false, 'message' => 'Gemini classification is disabled.']);
			die;
		}

		$ids = $this->input->post('ids');
		if (!is_array($ids)) {
			$ids = [$ids];
		}

		$this->load->database();
		$this->load->model('geminiai/Geminiai_model');
		$results = [];
		$provider = new \Perfexcrm\Geminiai\GeminiProvider();

		log_message('error', '[GEMINIAI] bulk_classify processing ids: ' . json_encode($ids));
		foreach ($ids as $id) {
			$id = (int) $id;
			try {
				// Fetch ticket
				$this->db->where('ticketid', $id);
				$ticket = $this->db->get(db_prefix() . 'tickets')->row();
				if (!$ticket) {
					log_message('error', '[GEMINIAI] ticket not found id=' . $id);
					$results[] = [
						'id' => $id,
						'error' => 'Ticket not found',
					];
					continue;
				}

				$subject = (string) $ticket->subject;
				$body    = (string) $ticket->message;
				$from    = (string) $ticket->email;

				$prompt = geminiai_build_prompt_for_ticket($id, $subject, $body);

				log_message('error', '[GEMINIAI] calling provider for ticket id=' . $id);
				$raw = $provider->chat($prompt);
				$rawStr = (string) $raw;
				log_message('error', '[GEMINIAI] provider raw response id=' . $id . ' len=' . strlen($rawStr));
				log_message('error', '[GEMINIAI] provider raw body id=' . $id . ' raw=' . substr($rawStr, 0, 2000));
				list($category, $priority, $score, $err) = geminiai_parse_classification_json($raw);
				if ($err) {
					log_message('error', '[GEMINIAI] parse error id=' . $id . ' err=' . $err . ' raw=' . substr($rawStr,0,2000));
				}

				// Log
				if (function_exists('geminiai_log_ticket_classification')) {
					geminiai_log_ticket_classification([
						'source'         => 'bulk',
						'email_from'     => $from,
						'subject'        => $subject,
						'preview'        => mb_substr(strip_tags($body), 0, 500),
						'classification' => ($category && $priority) ? ($category . ' / ' . $priority) : null,
						'score'          => $score,
						'ticket_id'      => $id,
						'raw'            => $raw,
						'error'          => $err,
					]);
				}

				// Apply mapping (department/priority/status) immediately for bulk
				try {
					$this->Geminiai_model->apply_mapping($id, $category, $priority);
					log_message('error', '[GEMINIAI] mapping applied id=' . $id . ' cat=' . $category . ' pri=' . $priority);
				} catch (\Throwable $e) {
					log_message('error', '[GEMINIAI] mapping error id=' . $id . ' err=' . $e->getMessage());
				}

				$one = [
					'id'       => $id,
					'category' => $category,
					'priority' => $priority,
					'score'    => $score,
					'raw'      => $raw,
					'error'    => $err,
				];
				$results[] = $one;
				log_message('error', '[GEMINIAI] result id=' . $id . ' json=' . json_encode($one));
			} catch (\Throwable $e) {
				if (function_exists('geminiai_log_ticket_classification')) {
					geminiai_log_ticket_classification([
						'source'    => 'bulk',
						'email_from'=> null,
						'subject'   => null,
						'preview'   => null,
						'ticket_id' => $id,
						'raw'       => null,
						'error'     => $e->getMessage(),
					]);
				}
				$results[] = [
					'id'    => $id,
					'error' => $e->getMessage(),
				];
			}
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'success' => true,
			'results' => $results,
		]);
		log_message('error', '[GEMINIAI] bulk_classify end');
		die;
	}
}

