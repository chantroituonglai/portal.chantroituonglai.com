<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Better_menubar extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if ($this->input->post()) {
            $enabled = $this->input->post('better_menubar_enabled') ? '1' : '0';
            update_option('better_menubar_enabled', $enabled);
            $mode = $this->input->post('better_menubar_sidebar_mode');
            $mode = in_array($mode, ['fixed','sticky']) ? $mode : 'fixed';
            update_option('better_menubar_sidebar_mode', $mode);
            $header_offset = $this->input->post('better_menubar_header_offset') ? '1' : '0';
            update_option('better_menubar_header_offset', $header_offset);
            $pinned = $this->input->post('better_menubar_pinned_enabled') ? '1' : '0';
            update_option('better_menubar_pinned_enabled', $pinned);
            $header_fixed = $this->input->post('better_menubar_header_fixed') ? '1' : '0';
            update_option('better_menubar_header_fixed', $header_fixed);
            set_alert('success', _l('settings_updated')); // use existing lang
            redirect(admin_url('better_menubar'));
        }

        $data['title'] = 'Better Menubar';
        $data['enabled'] = get_option('better_menubar_enabled') === '1';
        $data['sidebar_mode'] = get_option('better_menubar_sidebar_mode') ?: 'fixed';
        $data['header_offset'] = get_option('better_menubar_header_offset') === '1';
        $data['pinned_enabled'] = get_option('better_menubar_pinned_enabled') === '1';
        $data['header_fixed'] = get_option('better_menubar_header_fixed') === '1';
        $this->load->view('settings', $data);
    }
}
