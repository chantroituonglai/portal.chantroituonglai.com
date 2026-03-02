<?php
/**
 * @package   <?= $packageName ?? 'Topics' ?>
 * @author    <?= $author ?? 'Developer' ?>
 * @version   <?= $version ?? '1.0.0' ?>
 * @copyright Copyright (c) <?= date('Y') ?>
 */

defined('BASEPATH') or exit('No direct script access allowed');
?>

<!-- BEGIN TEMPLATE - <?= $templateName ?? 'Main View Template' ?> -->
<!-- SECTION: header -->
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $this->load->view('admin/includes/head'); ?>
    <title><?php echo isset($title) ? $title : get_option('companyname'); ?></title>
    <!-- Add page specific styles here -->
</head>
<body class="<?php echo 'admin'; ?> <?php echo $bodyclass ?? ''; ?>">
    <?php $this->load->view('admin/includes/header'); ?>
    <div id="wrapper">
        <?php $this->load->view('admin/includes/sidebar'); ?>
        <div id="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <h1 class="tw-font-medium tw-text-xl tw-mt-0 tw-mb-4"><?php echo $title ?? 'Page Title'; ?></h1>
                    </div>
                </div>
                <!-- END SECTION: header -->
                
                <!-- SECTION: main_content -->
                <div class="row">
                    <div class="col-md-12">
                        <!-- Main content here -->
                        <div class="panel_s">
                            <div class="panel-body">
                                <p>Main content goes here.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END SECTION: main_content -->
                
                <!-- SECTION: footer -->
            </div>
        </div>
    </div>
    <?php $this->load->view('admin/includes/scripts'); ?>
    <!-- Add page specific scripts here -->
    <!-- END SECTION: footer -->
</body>
</html>
<!-- END TEMPLATE --> 