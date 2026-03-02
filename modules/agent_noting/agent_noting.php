<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Agent Noting
Description: Adds "Add AI Note" button to note forms across modules and generates notes via AI providers.
Version: 0.1.0
Author: FHC
*/

define('AGENT_NOTING_MODULE_NAME', 'agent_noting');

register_language_files(AGENT_NOTING_MODULE_NAME, [AGENT_NOTING_MODULE_NAME]);

hooks()->add_action('app_admin_footer', function () {
    // Load a tiny JS to inject the button next to note forms
    $version = '?v=' . uniqid();
    // Inject language strings for JS
    echo '<script>window.AGENT_NOTING_LANG = ' . json_encode([
        'add_ai_note'   => _l('agent_noting_add_ai_note'),
        'generating'    => _l('agent_noting_generating'),
        'ai_error'      => _l('agent_noting_ai_error'),
        'failed_contact'=> _l('agent_noting_failed_contact'),
        'lang' => [
            'auto' => _l('agent_noting_lang_auto'),
            'vi'   => _l('agent_noting_lang_vi'),
            'en'   => _l('agent_noting_lang_en'),
            'ja'   => _l('agent_noting_lang_ja'),
            'ko'   => _l('agent_noting_lang_ko'),
            'zh'   => _l('agent_noting_lang_zh'),
            'fr'   => _l('agent_noting_lang_fr'),
            'de'   => _l('agent_noting_lang_de'),
            'es'   => _l('agent_noting_lang_es'),
        ],
    ]) . ';</script>';
    echo '<script src="' . module_dir_url(AGENT_NOTING_MODULE_NAME, 'assets/js/agent_noting.js') . $version . '"></script>';
});

// Optionally, expose a permission later; for MVP no special permission beyond being logged in.
