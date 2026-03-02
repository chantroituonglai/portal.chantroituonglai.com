<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Buttons / UI
$lang['agent_noting_add_ai_note']   = 'Add AI Note';
$lang['agent_noting_generating']    = 'Generating...';
$lang['agent_noting_ai_error']      = 'AI error';
$lang['agent_noting_failed_contact']= 'Failed to contact AI service';

// Language selector options
$lang['agent_noting_lang_auto'] = 'Auto';
$lang['agent_noting_lang_vi']   = 'Tiếng Việt';
$lang['agent_noting_lang_en']   = 'English';
$lang['agent_noting_lang_ja']   = '日本語';
$lang['agent_noting_lang_ko']   = '한국어';
$lang['agent_noting_lang_zh']   = '中文';
$lang['agent_noting_lang_fr']   = 'Français';
$lang['agent_noting_lang_de']   = 'Deutsch';
$lang['agent_noting_lang_es']   = 'Español';

// Prompts / messages (server-side)
$lang['agent_noting_prompt_role']           = 'You are an assistant that writes concise internal notes.';
$lang['agent_noting_prompt_style']          = 'Write a helpful, neutral, factual note for staff. Keep it 3-6 sentences, with clear bullets if useful.';
$lang['agent_noting_prompt_privacy']        = 'Avoid personally identifiable information beyond provided content.';
$lang['agent_noting_prompt_lang_prefix']    = 'Write the note in language: %s';
$lang['agent_noting_prompt_entity_prefix']  = 'Entity Type: %s';
$lang['agent_noting_prompt_draft_header']   = 'Draft/context provided by user:';

// Fallbacks
$lang['agent_noting_provider_unavailable']  = 'AI provider unavailable';
$lang['agent_noting_fallback_note']         = "Summary: outlined current status and next steps.\n- Context missing or AI unavailable.\n- Add key details and re-try.";

