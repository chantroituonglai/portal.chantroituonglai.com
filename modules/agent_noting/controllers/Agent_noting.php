<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Agent Noting Module Controller
 * @property Tickets_model $tickets_model
 * @property Invoices_model $invoices_model
 * @property Projects_model $projects_model
 * @property Estimates_model $estimates_model
 * @property Proposals_model $proposals_model
 * @property Contracts_model $contracts_model
 * @property Clients_model $clients_model
 */
class Agent_noting extends AdminController
{
    private $projectAgentReady = false;
    public function __construct()
    {
        parent::__construct();

        // Load models following Perfex CRM standard pattern
        $this->load->model('tickets_model');
        $this->load->model('invoices_model');
        $this->load->model('projects_model');
        $this->load->model('estimates_model');
        $this->load->model('proposals_model');
        $this->load->model('contracts_model');
        $this->load->model('clients_model');
        $this->load->model('leads_model');
        $this->load->model('credit_notes_model');
        $this->load->model('staff_model');

        // Try load Project Agent AI helper if present
        $helper = module_dir_path('project_agent') . 'helpers/project_agent_ai_integration_helper.php';
        if (file_exists($helper)) {
            require_once $helper;
            $this->projectAgentReady = $this->isModuleActive('project_agent')
                && file_exists(module_dir_path('project_agent') . 'models/Project_agent_model.php');
        }

        // Load necessary helpers for formatting functions
        $this->load->helper(['date', 'string', 'url', 'files', 'download', 'security']);

        log_message("error", 'Agent Noting - Controller initialized with models loaded');
    }

    public function generate_note()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $entityType = trim((string)$this->input->post('entity_type'));
        $entityId   = (int)$this->input->post('entity_id');
        $draftText  = (string)$this->input->post('draft_text');
        $language   = trim((string)$this->input->post('language'));

        // Log initial request parameters
        log_message("error", 'Agent Noting - Request Started: ' . json_encode([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'language' => $language,
            'draft_text_length' => strlen($draftText),
            'draft_text_preview' => strlen($draftText) > 0 ? substr($draftText, 0, 100) . (strlen($draftText) > 100 ? '...' : '') : '',
            'timestamp' => date('Y-m-d H:i:s'),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown'
        ]));

        // Build rich context based on entity type
        $context = [
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
        ];

        // Fetch entity-specific data
        $entityData = [];
        try {
            $entityData = $this->getEntityData($entityType, $entityId);
            // Log entity data for debugging
            log_message("error", 'Agent Noting - Entity Data Fetched: ' . json_encode([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'data_keys' => array_keys($entityData),
                'data_count' => count($entityData)
            ]));
        } catch (\Throwable $e) {
            // Log error but continue with basic prompt
            log_message("error", 'Agent Noting - Error fetching entity data: ' . $e->getMessage());
            log_message("error", 'Agent Noting - Error details: ' . json_encode([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]));
        }

        // Enhanced prompt with rich entity context
        $this->lang->load(AGENT_NOTING_MODULE_NAME . '/agent_noting');
        $lines   = [];
        $lines[] = _l('agent_noting_prompt_role');
        $lines[] = _l('agent_noting_prompt_style');
        $lines[] = _l('agent_noting_prompt_privacy');
        $langCode = $language ?: 'auto';
        if ($langCode && strtolower($langCode) !== 'auto') {
            $lines[] = sprintf(_l('agent_noting_prompt_lang_prefix'), $langCode);
        }
        $lines[] = '';
        $lines[] = sprintf(_l('agent_noting_prompt_entity_prefix'), $entityType ?: '');
        if ($entityId > 0) {
            $lines[] = 'Entity ID: ' . $entityId;
        }

        // Add entity-specific context
        if (!empty($entityData)) {
            $lines[] = '';
            $lines[] = '=== ENTITY DETAILS ===';
            foreach ($entityData as $key => $value) {
                if (!empty($value) && !is_array($value)) {
                    $lines[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
                }
            }
        }

        if ($draftText) {
            $lines[] = '';
            $lines[] = _l('agent_noting_prompt_draft_header');
            $lines[] = $draftText;
        }
        $prompt = implode("\n", $lines);

        // Sanitize prompt for UTF-8 and remove control chars, cap length
        $prompt = $this->sanitizeUtf8($prompt);
        if (function_exists('mb_strlen') && mb_strlen($prompt, 'UTF-8') > 8000) {
            $prompt = mb_substr($prompt, 0, 8000, 'UTF-8');
        } elseif (strlen($prompt) > 24000) { // fallback if mb not available
            $prompt = substr($prompt, 0, 24000);
        }

        // Log the complete prompt being sent to AI
        log_message("error", 'Agent Noting - AI Prompt Generated: ' . json_encode([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'language' => $language,
            'draft_text_length' => strlen($draftText),
            'entity_data_available' => !empty($entityData),
            'prompt_length' => strlen($prompt),
            'prompt_preview' => substr($prompt, 0, 500) . (strlen($prompt) > 500 ? '...' : '')
        ]));

        $note = '';
        $error = '';

        try {
            // Prefer ProjectAgent integration if available & ready
            if ($this->projectAgentReady && class_exists('ProjectAgentAiIntegration')) {
                log_message("error", 'Agent Noting - Attempting ProjectAgent AI: ' . json_encode([
                    'class_exists' => true,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId
                ]));

                try {
                    $ai = new ProjectAgentAiIntegration();
                    if ($ai->isAiAvailable()) {
                        $context['language'] = $language ?: 'auto';
                        log_message("error", 'Agent Noting - ProjectAgent AI Available, sending request: ' . json_encode([
                            'context' => $context,
                            'prompt_length' => strlen($prompt)
                        ]));

                        $resp = $ai->generateResponse($prompt, $context);

                        log_message("error", 'Agent Noting - ProjectAgent AI Response: ' . json_encode([
                            'response_type' => gettype($resp),
                            'is_array' => is_array($resp),
                            'response_keys' => is_array($resp) ? array_keys($resp) : null,
                            'has_content' => is_array($resp) && !empty($resp['content']),
                            'has_message' => is_array($resp) && !empty($resp['message']),
                            'content_length' => is_array($resp) && isset($resp['content']) ? strlen($resp['content']) : 0,
                            'message_length' => is_array($resp) && isset($resp['message']) ? strlen($resp['message']) : 0
                        ]));

                        if (is_array($resp) && !empty($resp['content'])) {
                            $note = trim((string)$resp['content']);
                            log_message("error", 'Agent Noting - Using content from ProjectAgent response: ' . substr($note, 0, 200));
                        } elseif (is_array($resp) && !empty($resp['message'])) {
                            $note = trim((string)$resp['message']);
                            log_message("error", 'Agent Noting - Using message from ProjectAgent response: ' . substr($note, 0, 200));
                        }
                    } else {
                        $error = 'ProjectAgent AI not available - provider may not be configured';
                        log_message("error", 'Agent Noting: ProjectAgent AI not available');
                    }
                } catch (Exception $e) {
                    log_message("error", 'Agent Noting - ProjectAgent AI Error: ' . $e->getMessage());
                    $error = 'ProjectAgent AI Error: ' . $e->getMessage();
                }
            }

            // Prefer GeminiAI provider if available via registry
            if ($note === '') {
                try {
                    log_message("error", 'Agent Noting - Attempting GeminiAI provider');
                    $provider = \app\services\ai\AiProviderRegistry::getProvider('geminiai');
                    log_message("error", 'Agent Noting - GeminiAI provider obtained: ' . get_class($provider));

                    if (method_exists($provider, 'completeText')) {
                        log_message("error", 'Agent Noting - Calling GeminiAI completeText method');
                        $note = (string)$provider->completeText($prompt, ['language' => $langCode]);
                        log_message("error", 'Agent Noting - GeminiAI completeText response: ' . substr($note, 0, 200));
                    } elseif (method_exists($provider, 'chat')) {
                        log_message("error", 'Agent Noting - Calling GeminiAI chat method');
                        $note = (string)$provider->chat($prompt, ['language' => $langCode]);
                        log_message("error", 'Agent Noting - GeminiAI chat response: ' . substr($note, 0, 200));
                    } else {
                        log_message("error", 'Agent Noting - GeminiAI provider has no suitable methods');
                    }
                } catch (\Throwable $e) {
                    log_message("error", 'Agent Noting: GeminiAI provider failed: ' . $e->getMessage());
                    log_message("error", 'Agent Noting: GeminiAI error details: ' . json_encode([
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]));
                    if (!$error) { $error = 'GeminiAI provider failed: ' . $e->getMessage(); }
                }
            }

            // Fallback to core AI provider registry (OpenAI) if needed
            if ($note === '') {
                // Use OpenAI if registered
                try {
                    log_message("error", 'Agent Noting - Attempting OpenAI provider');
                    $provider = \app\services\ai\AiProviderRegistry::getProvider('openai');
                    log_message("error", 'Agent Noting - OpenAI provider obtained: ' . get_class($provider));

                    // Basic text completion interface (module-specific); adapt if your provider uses a different method
                    if (method_exists($provider, 'completeText')) {
                        log_message("error", 'Agent Noting - Calling OpenAI completeText method');
                        // If provider supports language parameters, prefer them; otherwise rely on prompt instruction
                        $note = (string)$provider->completeText($prompt, ['language' => $langCode]);
                        log_message("error", 'Agent Noting - OpenAI completeText response: ' . substr($note, 0, 200));
                    } elseif (method_exists($provider, 'chat')) {
                        log_message("error", 'Agent Noting - Calling OpenAI chat method');
                        $note = (string)$provider->chat($prompt, ['language' => $langCode]);
                        log_message("error", 'Agent Noting - OpenAI chat response: ' . substr($note, 0, 200));
                    } else {
                        log_message("error", 'Agent Noting - OpenAI provider has no suitable methods');
                    }
                } catch (\Throwable $e) {
                    log_message("error", 'Agent Noting: OpenAI provider failed: ' . $e->getMessage());
                    log_message("error", 'Agent Noting: OpenAI error details: ' . json_encode([
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]));
                    if (!$error) { $error = 'OpenAI provider failed: ' . $e->getMessage(); }
                }
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        if ($note === '' && $error !== '') {
            echo json_encode(['success' => false, 'error' => $error]);
            return;
        }

        if ($note === '') {
            // Generate fallback note based on entity data if available
            if (!empty($entityData)) {
                log_message("error", 'Agent Noting - Generating fallback note from entity data');
                $note = $this->generateFallbackNote($entityType, $entityData);
                log_message("error", 'Agent Noting - Fallback note generated: ' . substr($note, 0, 200));
            } else {
                // Safe fallback message to avoid empty UI
                log_message("error", 'Agent Noting - No entity data available, using default fallback');
                $note = _l('agent_noting_fallback_note');
            }
        }

        // Enforce target language if specified (e.g., vi). Some providers may ignore hint.
        $langCode = $language ?: 'auto';
        if (!empty($note) && $langCode && strtolower($langCode) !== 'auto') {
            $translated = $this->translateToLanguage($note, $langCode);
            if (is_string($translated) && trim($translated) !== '') {
                $note = $translated;
            }
        }

        // Log final result
        log_message("error", 'Agent Noting - Final Result: ' . json_encode([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'has_note' => !empty($note),
            'note_length' => strlen($note),
            'note_preview' => substr($note, 0, 100) . (strlen($note) > 100 ? '...' : ''),
            'has_error' => !empty($error),
            'error_message' => $error
        ]));

        echo json_encode(['success' => true, 'note' => $note]);
    }

    private function isModuleActive($moduleName)
    {
        try {
            $this->db->select('active');
            $this->db->from(db_prefix() . 'modules');
            $this->db->where('module_name', $moduleName);
            $row = $this->db->get()->row();
            return $row && (int)$row->active === 1;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Fetch entity-specific data based on entity type and ID
     */
    private function getEntityData($entityType, $entityId)
    {
        if (!$entityId || !$entityType) {
            log_message("error", 'Agent Noting - Invalid entity parameters: ' . json_encode([
                'entity_type' => $entityType,
                'entity_id' => $entityId
            ]));
            return [];
        }

        log_message("error", 'Agent Noting - Processing entity type: ' . $entityType . ' with ID: ' . $entityId);

        $result = [];
        try {
            switch (strtolower($entityType)) {
                case 'tickets':
                    $result = $this->getTicketData($entityId);
                    break;
                case 'invoices':
                    $result = $this->getInvoiceData($entityId);
                    break;
                case 'projects':
                    $result = $this->getProjectData($entityId);
                    break;
                case 'estimates':
                    $result = $this->getEstimateData($entityId);
                    break;
                case 'proposals':
                    $result = $this->getProposalData($entityId);
                    break;
                case 'contracts':
                    $result = $this->getContractData($entityId);
                    break;
                default:
                    log_message("error", 'Agent Noting - Unsupported entity type: ' . $entityType);
                    $result = [];
            }
        } catch (Exception $e) {
            log_message("error", 'Agent Noting - Error in getEntityData switch: ' . $e->getMessage());
            $result = [];
        }

        log_message("error", 'Agent Noting - Entity data result: ' . json_encode([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'data_retrieved' => !empty($result),
            'field_count' => count($result)
        ]));

        return $result;
    }

    /**
     * Ensure text is valid UTF-8, strip control chars that may break JSON/HTTP
     */
    private function sanitizeUtf8($text)
    {
        if ($text === null) { return ''; }
        // Normalize to string
        if (!is_string($text)) { $text = (string) $text; }
        // Convert to UTF-8 if needed
        if (function_exists('mb_detect_encoding')) {
            if (!mb_detect_encoding($text, 'UTF-8', true)) {
                $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, ASCII');
            }
        }
        // Remove invalid UTF-8 bytes via iconv fallback (ignore)
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
            if ($converted !== false) { $text = $converted; }
        }
        // Strip control characters except common whitespace (tab, newline, carriage return)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        return $text;
    }

    /**
     * Translate given text to target language using GeminiAI or OpenAI.
     */
    private function translateToLanguage($text, $langCode)
    {
        $text = $this->sanitizeUtf8($text);
        $langCode = trim((string)$langCode);
        if ($text === '' || $langCode === '') { return $text; }

        $instruction = "Translate the following note into language: {$langCode}. Keep tone, formatting, and meaning. Return only the translated text.";
        $payload = $instruction . "\n\n=== NOTE ===\n" . $text;

        // Try Gemini first
        try {
            $provider = \app\services\ai\AiProviderRegistry::getProvider('geminiai');
            if (method_exists($provider, 'completeText')) {
                return (string)$provider->completeText($payload, ['language' => $langCode]);
            } elseif (method_exists($provider, 'chat')) {
                return (string)$provider->chat($payload, ['language' => $langCode]);
            }
        } catch (\Throwable $e) {
            // ignore and try OpenAI
        }

        // Fallback OpenAI
        try {
            $provider = \app\services\ai\AiProviderRegistry::getProvider('openai');
            if (method_exists($provider, 'completeText')) {
                return (string)$provider->completeText($payload, ['language' => $langCode]);
            } elseif (method_exists($provider, 'chat')) {
                return (string)$provider->chat($payload, ['language' => $langCode]);
            }
        } catch (\Throwable $e) {
            // ignore and return original
        }

        return $text;
    }

    private function getTicketData($ticketId)
    {
        try {
            // Use Perfex CRM's built-in method to get ticket
            $ticket = $this->tickets_model->get($ticketId);
            if (!$ticket) {
                log_message("error", 'Agent Noting - Ticket not found: ' . $ticketId);
                return [];
            }

            // Use fields already joined in Tickets_model->get(): department_name, priority_name, status_name
            $departmentName = isset($ticket->department_name) ? $ticket->department_name : 'Unknown';
            $priorityName   = isset($ticket->priority_name) ? $ticket->priority_name : 'Unknown';
            $statusName     = isset($ticket->status_name) ? $ticket->status_name : 'Unknown';

            // Get client/contact info
            $clientCompany = 'Unknown';
            $clientPhone = '';
            $clientEmail = $ticket->email ?: '';

            if ($ticket->userid > 0) {
                $client = $this->clients_model->get($ticket->userid);
                if ($client) {
                    $clientCompany = $client->company ?: 'Unknown';
                    $clientPhone = $client->phonenumber ?: '';
                }
            }

            // Get replies count using Perfex method
            $replies = $this->tickets_model->get_ticket_replies($ticketId);
            $repliesCount = is_array($replies) ? count($replies) : 0;

            // Clean HTML from message for better AI processing
            $cleanMessage = strip_tags($ticket->message);
            $cleanMessage = substr($cleanMessage, 0, 1000);

            $result = [
                'subject' => $ticket->subject,
                'message_preview' => $cleanMessage,
                'department' => $departmentName,
                'priority' => $priorityName,
                'status' => $statusName,
                'client_company' => $clientCompany,
                'client_phone' => $clientPhone,
                'client_email' => $clientEmail,
                'date_created' => $ticket->date,
                'last_reply' => $ticket->lastreply,
                'replies_count' => $repliesCount,
                'assigned_to' => $ticket->assigned ? $this->getStaffName($ticket->assigned) : 'Unassigned',
                'ticket_key' => $ticket->ticketkey,
                'service' => $ticket->service
            ];

            log_message("error", 'Agent Noting - Ticket data retrieved: ' . json_encode([
                'ticket_id' => $ticketId,
                'subject' => substr($ticket->subject, 0, 50),
                'department' => $departmentName,
                'status' => $statusName
            ]));

            return $result;

        } catch (Exception $e) {
            log_message("error", 'Agent Noting - Error in getTicketData: ' . $e->getMessage());
            return [];
        }
    }

    private function getInvoiceData($invoiceId)
    {
        $invoice = $this->invoices_model->get($invoiceId);
        if (!$invoice) {
            return [];
        }

        // Client info via model
        $client = $invoice->client ?: $this->clients_model->get($invoice->clientid);
        $primaryEmail = '';
        if ($invoice->clientid) {
            $pcid = get_primary_contact_user_id($invoice->clientid);
            if ($pcid) {
                $contact = $this->clients_model->get_contact($pcid);
                $primaryEmail = $contact ? $contact->email : '';
            }
        }

        // Items from model result
        $items = isset($invoice->items) ? $invoice->items : [];
        $itemsSummary = '';
        if (!empty($items)) {
            $itemsSummary = 'Items: ';
            foreach ($items as $item) {
                $desc = isset($item['description']) ? $item['description'] : (isset($item['long_description']) ? $item['long_description'] : 'Item');
                $qty  = isset($item['qty']) ? $item['qty'] : (isset($item['quantity']) ? $item['quantity'] : '');
                $amt  = isset($item['amount']) ? $item['amount'] : (isset($item['rate']) && isset($item['qty']) ? ($item['rate'] * $item['qty']) : '');
                $itemsSummary .= $desc . ' (Qty: ' . $qty . ', Amount: ' . $amt . '), ';
            }
            $itemsSummary = rtrim($itemsSummary, ', ');
        }

        return [
            'invoice_number' => $this->formatInvoiceNumber($invoice->id),
            'client_company' => $client ? $client->company : 'Unknown',
            'client_phone'   => $client ? $client->phonenumber : '',
            'client_email'   => $primaryEmail,
            'total_amount'   => $this->formatCurrency($invoice->total, $invoice->currency_name),
            'status'         => $this->getInvoiceStatus($invoice->status),
            'date_created'   => $invoice->datecreated,
            'due_date'       => $invoice->duedate,
            'items_summary'  => $itemsSummary,
            'subtotal'       => $this->formatCurrency($invoice->subtotal, $invoice->currency_name),
            'tax'            => $this->formatCurrency($invoice->total_tax, $invoice->currency_name)
        ];
    }

    private function getProjectData($projectId)
    {
        $project = $this->projects_model->get($projectId);
        if (!$project) {
            return [];
        }

        // Client info via model
        $client = $this->clients_model->get($project->clientid);

        // Members and tasks via model
        $membersCount = 0;
        try { $membersCount = count($this->projects_model->get_project_members($projectId)); } catch (\Throwable $e) { $membersCount = 0; }
        $tasksCount = 0;
        try { $tasksCount = (int) $this->projects_model->get_tasks($projectId, [], false, true); } catch (\Throwable $e) { $tasksCount = 0; }

        return [
            'project_name'   => $project->name,
            'description'    => $project->description ? substr(strip_tags($project->description), 0, 500) : '',
            'client_company' => $client ? $client->company : 'Unknown',
            'status'         => $this->getProjectStatus($project->status),
            'start_date'     => $project->start_date,
            'deadline'       => $project->deadline,
            'progress'       => $project->progress . '%',
            'project_cost'   => $project->project_cost ? $this->formatCurrency($project->project_cost) : 'Not set',
            'members_count'  => $membersCount,
            'tasks_count'    => $tasksCount,
            'created_by'     => $this->getStaffName($project->addedfrom)
        ];
    }

    private function getEstimateData($estimateId)
    {
        $estimate = $this->estimates_model->get($estimateId);
        if (!$estimate) {
            return [];
        }

        $client = $estimate->client ?: $this->clients_model->get($estimate->clientid);
        $primaryEmail = '';
        if ($estimate->clientid) {
            $pcid = get_primary_contact_user_id($estimate->clientid);
            if ($pcid) {
                $contact = $this->clients_model->get_contact($pcid);
                $primaryEmail = $contact ? $contact->email : '';
            }
        }

        return [
            'estimate_number' => $this->formatEstimateNumber($estimate->id),
            'client_company'  => $client ? $client->company : 'Unknown',
            'client_phone'    => $client ? $client->phonenumber : '',
            'client_email'    => $primaryEmail,
            'total_amount'    => $this->formatCurrency($estimate->total, $estimate->currency_name),
            'status'          => $this->getEstimateStatus($estimate->status),
            'date_created'    => $estimate->datecreated,
            'expiry_date'     => $estimate->expirydate,
            'admin_note'      => $estimate->adminnote ? substr(strip_tags($estimate->adminnote), 0, 300) : ''
        ];
    }

    private function getProposalData($proposalId)
    {
        $proposal = $this->proposals_model->get($proposalId);
        if (!$proposal) {
            return [];
        }

        $clientName = 'Unknown';
        $clientPhone = '';
        $clientEmail = '';
        if ($proposal->rel_type === 'lead') {
            $lead = $this->leads_model->get($proposal->rel_id);
            if ($lead) {
                $clientName  = $lead->name ?: 'Lead';
                $clientPhone = $lead->phonenumber ?: '';
                $clientEmail = $lead->email ?: '';
            }
        } else {
            $client = $this->clients_model->get($proposal->rel_id);
            if ($client) {
                $clientName  = $client->company ?: $clientName;
                $clientPhone = $client->phonenumber ?: '';
                $pcid = get_primary_contact_user_id($client->userid);
                if ($pcid) {
                    $contact = $this->clients_model->get_contact($pcid);
                    $clientEmail = $contact ? $contact->email : '';
                }
            }
        }

        return [
            'proposal_subject'  => $proposal->subject,
            'client_name'       => $clientName,
            'client_phone'      => $clientPhone,
            'client_email'      => $clientEmail,
            'total_amount'      => $this->formatCurrency($proposal->total, $proposal->currency_name),
            'status'            => $this->getProposalStatus($proposal->status),
            'date_created'      => $proposal->datecreated,
            'open_till'         => $proposal->open_till,
            'proposal_content'  => $proposal->content ? substr(strip_tags($proposal->content), 0, 500) : ''
        ];
    }

    private function getContractData($contractId)
    {
        $contract = $this->contracts_model->get($contractId);
        if (!$contract) {
            return [];
        }

        // Get client info (email stored in contacts table)
        $client = $this->db->select('company, phonenumber')
            ->from(db_prefix() . 'clients')
            ->where('userid', $contract->client)
            ->get()
            ->row();
        $primaryContact = $this->db->select('email')
            ->from(db_prefix() . 'contacts')
            ->where('userid', $contract->client)
            ->where('is_primary', 1)
            ->limit(1)
            ->get()
            ->row();

        // Get contract type
        // Contract type via model
        $this->load->model('contract_types_model');
        $contractType = $this->contract_types_model->get($contract->contract_type);

        return [
            'contract_subject' => $contract->subject,
            'client_company' => $client ? $client->company : 'Unknown',
            'client_phone' => $client ? $client->phonenumber : '',
            'client_email' => $primaryContact ? $primaryContact->email : '',
            'contract_type' => $contractType ? $contractType->name : 'Unknown',
            'contract_value' => $contract->contract_value ? $this->formatCurrency($contract->contract_value) : 'Not set',
            'start_date' => $contract->datestart,
            'end_date' => $contract->dateend,
            'description' => $contract->description ? substr(strip_tags($contract->description), 0, 500) : '',
            'status' => $contract->signed == 1 ? 'Signed' : 'Not Signed'
        ];
    }

    /**
     * Generate fallback note when AI is not available
     */
    private function generateFallbackNote($entityType, $entityData)
    {
        $note = '';

        switch (strtolower($entityType)) {
            case 'tickets':
                $note = $this->generateTicketFallbackNote($entityData);
                break;
            case 'invoices':
                $note = $this->generateInvoiceFallbackNote($entityData);
                break;
            case 'projects':
                $note = $this->generateProjectFallbackNote($entityData);
                break;
            case 'estimates':
                $note = $this->generateEstimateFallbackNote($entityData);
                break;
            case 'proposals':
                $note = $this->generateProposalFallbackNote($entityData);
                break;
            case 'contracts':
                $note = $this->generateContractFallbackNote($entityData);
                break;
            default:
                $note = 'Entity processed but AI service unavailable for detailed summary.';
        }

        return $note;
    }

    private function generateTicketFallbackNote($data)
    {
        $note = 'Ticket Summary:' . "\n";
        $note .= '- Subject: ' . ($data['subject'] ?? 'N/A') . "\n";
        $note .= '- Department: ' . ($data['department'] ?? 'N/A') . "\n";
        $note .= '- Priority: ' . ($data['priority'] ?? 'N/A') . "\n";
        $note .= '- Status: ' . ($data['status'] ?? 'N/A') . "\n";
        $note .= '- Client: ' . ($data['client_company'] ?? 'N/A') . "\n";
        if (!empty($data['replies_count'])) {
            $note .= '- Replies: ' . $data['replies_count'] . "\n";
        }
        if (!empty($data['assigned_to'])) {
            $note .= '- Assigned to: ' . $data['assigned_to'] . "\n";
        }
        $note .= '- Requires follow-up and resolution.';

        return $note;
    }

    private function generateInvoiceFallbackNote($data)
    {
        $note = 'Invoice Summary:' . "\n";
        $note .= '- Invoice #: ' . ($data['invoice_number'] ?? 'N/A') . "\n";
        $note .= '- Client: ' . ($data['client_company'] ?? 'N/A') . "\n";
        $note .= '- Amount: ' . ($data['total_amount'] ?? 'N/A') . "\n";
        $note .= '- Status: ' . ($data['status'] ?? 'N/A') . "\n";
        $note .= '- Due Date: ' . ($data['due_date'] ?? 'N/A') . "\n";
        $note .= '- Requires payment processing and follow-up.';

        return $note;
    }

    private function generateProjectFallbackNote($data)
    {
        $note = 'Project Summary:' . "\n";
        $note .= '- Project: ' . ($data['project_name'] ?? 'N/A') . "\n";
        $note .= '- Client: ' . ($data['client_company'] ?? 'N/A') . "\n";
        $note .= '- Status: ' . ($data['status'] ?? 'N/A') . "\n";
        $note .= '- Progress: ' . ($data['progress'] ?? 'N/A') . "\n";
        $note .= '- Deadline: ' . ($data['deadline'] ?? 'N/A') . "\n";
        if (!empty($data['members_count'])) {
            $note .= '- Team Members: ' . $data['members_count'] . "\n";
        }
        if (!empty($data['tasks_count'])) {
            $note .= '- Tasks: ' . $data['tasks_count'] . "\n";
        }
        $note .= '- Requires ongoing monitoring and updates.';

        return $note;
    }

    private function generateEstimateFallbackNote($data)
    {
        $note = 'Estimate Summary:' . "\n";
        $note .= '- Estimate #: ' . ($data['estimate_number'] ?? 'N/A') . "\n";
        $note .= '- Client: ' . ($data['client_company'] ?? 'N/A') . "\n";
        $note .= '- Amount: ' . ($data['total_amount'] ?? 'N/A') . "\n";
        $note .= '- Status: ' . ($data['status'] ?? 'N/A') . "\n";
        $note .= '- Expiry Date: ' . ($data['expiry_date'] ?? 'N/A') . "\n";
        $note .= '- Requires client follow-up and confirmation.';

        return $note;
    }

    private function generateProposalFallbackNote($data)
    {
        $note = 'Proposal Summary:' . "\n";
        $note .= '- Subject: ' . ($data['proposal_subject'] ?? 'N/A') . "\n";
        $note .= '- Client: ' . ($data['client_name'] ?? 'N/A') . "\n";
        $note .= '- Amount: ' . ($data['total_amount'] ?? 'N/A') . "\n";
        $note .= '- Status: ' . ($data['status'] ?? 'N/A') . "\n";
        $note .= '- Open Till: ' . ($data['open_till'] ?? 'N/A') . "\n";
        $note .= '- Requires client response and follow-up.';

        return $note;
    }

    private function generateContractFallbackNote($data)
    {
        $note = 'Contract Summary:' . "\n";
        $note .= '- Subject: ' . ($data['contract_subject'] ?? 'N/A') . "\n";
        $note .= '- Client: ' . ($data['client_company'] ?? 'N/A') . "\n";
        $note .= '- Contract Type: ' . ($data['contract_type'] ?? 'N/A') . "\n";
        $note .= '- Value: ' . ($data['contract_value'] ?? 'N/A') . "\n";
        $note .= '- Start Date: ' . ($data['start_date'] ?? 'N/A') . "\n";
        $note .= '- End Date: ' . ($data['end_date'] ?? 'N/A') . "\n";
        $note .= '- Status: ' . ($data['status'] ?? 'N/A') . "\n";
        $note .= '- Requires execution and monitoring.';

        return $note;
    }

    /**
     * Helper methods for formatting and data retrieval
     */
    private function getStaffName($staffId)
    {
        if (!$staffId) return 'Unknown';
        try {
            $staff = $this->staff_model->get($staffId);
            return $staff ? trim($staff->firstname . ' ' . $staff->lastname) : 'Unknown';
        } catch (Exception $e) {
            return 'Unknown';
        }
    }

    private function formatCurrency($amount, $currency = null)
    {
        if ($amount === null || $amount === '') return '0';

        try {
            // Try to use Perfex format_money if available
            if (function_exists('format_money')) {
                return format_money($amount, $currency);
            }
            // Fallback formatting
            $currencySymbol = $currency ? $currency : '$';
            return $currencySymbol . number_format((float)$amount, 2);
        } catch (Exception $e) {
            return number_format((float)$amount, 2);
        }
    }

    private function formatInvoiceNumber($id)
    {
        try {
            if (function_exists('format_invoice_number')) {
                return format_invoice_number($id);
            }
            return 'INV-' . str_pad($id, 4, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            return 'INV-' . $id;
        }
    }

    private function formatEstimateNumber($id)
    {
        try {
            if (function_exists('format_estimate_number')) {
                return format_estimate_number($id);
            }
            return 'EST-' . str_pad($id, 4, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            return 'EST-' . $id;
        }
    }

    private function getInvoiceStatus($status)
    {
        $statuses = [
            1 => 'Unpaid',
            2 => 'Paid',
            3 => 'Partially Paid',
            4 => 'Overdue',
            5 => 'Cancelled',
            6 => 'Draft'
        ];
        return isset($statuses[$status]) ? $statuses[$status] : 'Unknown';
    }

    private function getEstimateStatus($status)
    {
        $statuses = [
            1 => 'Draft',
            2 => 'Sent',
            3 => 'Declined',
            4 => 'Accepted',
            5 => 'Expired'
        ];
        return isset($statuses[$status]) ? $statuses[$status] : 'Unknown';
    }

    private function getProposalStatus($status)
    {
        $statuses = [
            0 => 'Draft',
            1 => 'Sent',
            2 => 'Open',
            3 => 'Revised',
            4 => 'Declined',
            5 => 'Accepted',
            6 => 'Expired'
        ];
        return isset($statuses[$status]) ? $statuses[$status] : 'Unknown';
    }

    private function getProjectStatus($status)
    {
        $statuses = [
            1 => 'Not Started',
            2 => 'In Progress',
            3 => 'On Hold',
            4 => 'Finished',
            5 => 'Cancelled'
        ];
        return isset($statuses[$status]) ? $statuses[$status] : 'Unknown';
    }
}
