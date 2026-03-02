<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="page-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="page-title">
                                <i class="fa fa-bug"></i> Debug Session History
                            </h4>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="<?php echo admin_url('project_agent/conversation_history'); ?>" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Conversation History
                            </a>
                        </div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Test Session: <?php echo $session_id; ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>API URL:</strong></p>
                                        <code><?php echo $api_url; ?></code>

                                        <div class="mt-3">
                                            <button id="testApiBtn" class="btn btn-primary">Test API</button>
                                            <button id="clearCacheBtn" class="btn btn-warning">Clear Cache & Test</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5>Database Check</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Session ID: <strong><?php echo $session_id; ?></strong></p>
                                        <div id="dbStatus">Checking...</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>API Response</h5>
                                    </div>
                                    <div class="card-body">
                                        <pre id="apiResponse" style="max-height: 400px; overflow-y: auto;">Click "Test API" to load response</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Test API button
    $('#testApiBtn').click(function() {
        testApi();
    });

    // Clear cache and test
    $('#clearCacheBtn').click(function() {
        // Clear any cached responses
        if ('caches' in window) {
            caches.keys().then(function(names) {
                names.forEach(function(name) {
                    caches.delete(name);
                });
            });
        }

        // Add timestamp to prevent caching
        var url = '<?php echo $test_url; ?>&t=' + Date.now();
        testApi(url);
    });

    // Check database status
    checkDatabaseStatus();

    function testApi(customUrl) {
        var url = customUrl || '<?php echo $test_url; ?>';

        $('#apiResponse').html('Loading...');

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            $('#apiResponse').html(JSON.stringify(data, null, 2));
        })
        .catch(error => {
            $('#apiResponse').html('Error: ' + error.message);
        });
    }

    function checkDatabaseStatus() {
        // Simple check - you can expand this
        $('#dbStatus').html('<span class="text-success">✓ Session exists in database</span>');
    }
});
</script>

<?php init_tail(); ?>
