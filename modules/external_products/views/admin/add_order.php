<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <?php $this->load->view('admin/includes/aside'); ?>
    <div class="content-wrapper">
        <div class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="panel_s">
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <h4 class="no-margin"><?php echo _l('add_external_order'); ?></h4>
                                    <hr class="hr-panel-heading" />
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <form id="addOrderForm">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="external_order_id"><?php echo _l('external_order_id'); ?> <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="external_order_id" name="external_order_id" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="external_system"><?php echo _l('external_system'); ?> <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="external_system" name="external_system" required>
                                                        <option value=""><?php echo _l('select_option'); ?></option>
                                                        <?php foreach ($external_systems as $system): ?>
                                                            <option value="<?php echo $system['system_name']; ?>"><?php echo $system['system_name']; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="order_number"><?php echo _l('order_number'); ?></label>
                                                    <input type="text" class="form-control" id="order_number" name="order_number">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="order_date"><?php echo _l('order_date'); ?> <span class="text-danger">*</span></label>
                                                    <input type="datetime-local" class="form-control" id="order_date" name="order_date" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="customer_name"><?php echo _l('customer_name'); ?> <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="customer_email"><?php echo _l('customer_email'); ?></label>
                                                    <input type="email" class="form-control" id="customer_email" name="customer_email">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="customer_phone"><?php echo _l('customer_phone'); ?></label>
                                                    <input type="text" class="form-control" id="customer_phone" name="customer_phone">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="order_status"><?php echo _l('order_status'); ?></label>
                                                    <select class="form-control" id="order_status" name="order_status">
                                                        <option value="pending"><?php echo _l('pending'); ?></option>
                                                        <option value="processing"><?php echo _l('processing_orders'); ?></option>
                                                        <option value="shipped"><?php echo _l('shipped_orders'); ?></option>
                                                        <option value="delivered"><?php echo _l('delivered_orders'); ?></option>
                                                        <option value="completed"><?php echo _l('completed_orders'); ?></option>
                                                        <option value="cancelled"><?php echo _l('cancelled_orders'); ?></option>
                                                        <option value="returned"><?php echo _l('returned_orders'); ?></option>
                                                        <option value="refunded"><?php echo _l('refunded_orders'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="total_amount"><?php echo _l('total_amount'); ?></label>
                                                    <input type="number" step="0.01" class="form-control" id="total_amount" name="total_amount" value="0.00">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="currency"><?php echo _l('currency'); ?></label>
                                                    <select class="form-control" id="currency" name="currency">
                                                        <option value="USD">USD</option>
                                                        <option value="EUR">EUR</option>
                                                        <option value="GBP">GBP</option>
                                                        <option value="VND">VND</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="payment_method"><?php echo _l('payment_method'); ?></label>
                                                    <input type="text" class="form-control" id="payment_method" name="payment_method">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="payment_status"><?php echo _l('payment_status'); ?></label>
                                                    <select class="form-control" id="payment_status" name="payment_status">
                                                        <option value="pending"><?php echo _l('pending'); ?></option>
                                                        <option value="paid">Paid</option>
                                                        <option value="failed"><?php echo _l('failed'); ?></option>
                                                        <option value="refunded"><?php echo _l('refunded_orders'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="shipping_method"><?php echo _l('shipping_method'); ?></label>
                                                    <input type="text" class="form-control" id="shipping_method" name="shipping_method">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="tracking_number"><?php echo _l('tracking_number'); ?></label>
                                                    <input type="text" class="form-control" id="tracking_number" name="tracking_number">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="shipping_address"><?php echo _l('shipping_address'); ?></label>
                                                    <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3"></textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="billing_address"><?php echo _l('billing_address'); ?></label>
                                                    <textarea class="form-control" id="billing_address" name="billing_address" rows="3"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="notes"><?php echo _l('notes'); ?></label>
                                                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-12">
                                                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                                                <a href="<?php echo admin_url('external_products/orders'); ?>" class="btn btn-default"><?php echo _l('cancel'); ?></a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
$(document).ready(function() {
    // Set current date and time
    var now = new Date();
    var datetime = now.getFullYear() + '-' + 
                   String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                   String(now.getDate()).padStart(2, '0') + 'T' + 
                   String(now.getHours()).padStart(2, '0') + ':' + 
                   String(now.getMinutes()).padStart(2, '0');
    $('#order_date').val(datetime);
    
    // Form submission
    $('#addOrderForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '<?php echo admin_url('external_products/add_order'); ?>',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert_float('success', response.message);
                    window.location.href = '<?php echo admin_url('external_products/orders'); ?>';
                } else {
                    alert_float('danger', response.message);
                }
            },
            error: function() {
                alert_float('danger', '<?php echo _l('problem_processing_request'); ?>');
            }
        });
    });
});
</script>
</body>
</html>
