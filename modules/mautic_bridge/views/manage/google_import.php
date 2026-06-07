<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('mautic_bridge/manage/_header'); ?>

<?php if (empty($defaults_ok)) { ?>
    <div class="alert alert-warning">
        Missing Mautic credentials or default Perfex lead source/status/assigned staff. Configure Settings before live import.
    </div>
<?php } ?>

<div class="row">
    <div class="col-md-4">
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin"><?php echo _l('mautic_bridge_google_import_upload'); ?></h4>
                <hr class="hr-panel-heading" />
                <?php echo form_open_multipart('', ['id' => 'mautic-bridge-google-import-upload']); ?>
                <div class="form-group">
                    <label for="mautic_google_import_file">JSON/CSV file</label>
                    <input type="file" name="import_file" id="mautic_google_import_file" class="form-control" accept=".json,.csv,application/json,text/csv" required>
                </div>
                <p class="text-muted">
                    Records without a valid email are skipped. Mautic is created/updated first, then Perfex lead and bridge mapping are written.
                </p>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-upload"></i> Upload and preview
                </button>
                <?php echo form_close(); ?>
            </div>
        </div>

        <div class="panel_s hide" id="mautic-bridge-import-progress-panel">
            <div class="panel-body">
                <h4 class="no-margin"><?php echo _l('mautic_bridge_import_progress'); ?></h4>
                <hr class="hr-panel-heading" />
                <div class="progress no-margin">
                    <div class="progress-bar progress-bar-info" id="mautic-bridge-import-progress-bar" role="progressbar" style="width:0%">0%</div>
                </div>
                <div class="mtop15" id="mautic-bridge-import-counters"></div>
                <div class="mtop15">
                    <button type="button" class="btn btn-success" id="mautic-bridge-import-start" disabled>
                        <i class="fa fa-play"></i> Start import
                    </button>
                    <button type="button" class="btn btn-default hide" id="mautic-bridge-import-stop">
                        <i class="fa fa-stop"></i> Stop
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="panel_s hide" id="mautic-bridge-import-mapping-panel">
            <div class="panel-body">
                <h4 class="no-margin"><?php echo _l('mautic_bridge_import_mapping'); ?></h4>
                <hr class="hr-panel-heading" />
                <div id="mautic-bridge-import-mapping"></div>
            </div>
        </div>

        <div class="panel_s hide" id="mautic-bridge-import-preview-panel">
            <div class="panel-body">
                <h4 class="no-margin"><?php echo _l('mautic_bridge_import_preview'); ?></h4>
                <hr class="hr-panel-heading" />
                <div class="table-responsive">
                    <table class="table table-striped no-mtop">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Status</th>
                                <th>Email</th>
                                <th>Company</th>
                                <th>Phone</th>
                                <th>City</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody id="mautic-bridge-import-preview"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('mautic_bridge/manage/_footer'); ?>
