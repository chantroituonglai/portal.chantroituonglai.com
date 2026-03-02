<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_100 extends App_module_migration
{
    public function up()
    {
        require_once __DIR__ . '/../install.php';
        update_option('project_agent_db_version', 100);
    }
}
