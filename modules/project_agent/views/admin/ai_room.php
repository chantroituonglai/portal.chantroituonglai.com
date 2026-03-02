<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="panel-title"><i class="fa fa-comments"></i> <?php echo isset($title) ? $title : 'AI Room'; ?></h4>
          </div>
          <div class="panel-body">
            <?php $CI = &get_instance(); $CI->load->view('project_agent/ai_room'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
