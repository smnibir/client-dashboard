<?php
/**
 * AJAX Handlers for Billing & Payment functionality
 * Add this to your plugin's includes folder as billing-ajax-handlers.php
 */

// Get payment methods form
add_action('wp_ajax_get_payment_methods_form', 'handle_get_payment_methods_form');
function handle_get_payment_methods_form() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'payment_methods_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_die('Please log in to manage payment methods');
    }
    
    // Get saved payment methods
    $saved_methods = wc_get_customer_saved_methods_list(get_current_user_id());
    
    ob_start();
    ?>
    <div class="payment-methods-wrapper">
        <?php if (!empty($saved_methods['card'])): ?>
            <h4>Saved Cards</h4>
            <div class="saved-cards-list">
                <?php foreach ($saved_methods['card'] as $method): 
                    $card = $method['method'];
                    $is_default = $card->get_id() === get_user_meta(get_current_user_id(), 'wc_default_payment_method', true);
                ?>
                    <div class="saved-card-item">
                        <div class="card-details">
                            <span class="card-brand"><?php echo esc_html($card->get_brand()); ?></span>
                            <span class="card-number">**** <?php echo esc_html($card->get_last4()); ?></span>
                            <span class="card-expiry"><?php echo esc_html($card->get_expiry_month() . '/' . $card->get_expiry_year()); ?></span>
                        </div>
                        <div class="card-actions">
                            <?php if ($is_default): ?>
                                <span class="default-badge">Default</span>
                            <?php else: ?>
                                <button class="set-default-card" data-token-id="<?php echo esc_attr($card->get_id()); ?>">Set as Default</button>
                            <?php endif; ?>
                            <button class="delete-card" data-token-id="<?php echo esc_attr($card->get_id()); ?>">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <h4>Add New Card</h4>
        <form id="add-payment-method-form" method="post">
            <div class="form-group">
                <label for="card-element">Card Details</label>
                <div id="card-element">
                    <!-- Stripe card element will be mounted here -->
                </div>
                <div id="card-errors" role="alert"></div>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="save_card" value="1" checked>
                    Save this card for future payments
                </label>
            </div>
            
            <button type="submit" class="btn-primary">Add Card</button>
        </form>
    </div>
    
    <script>
    // Initialize Stripe if available
    if (typeof Stripe !== 'undefined') {
        var stripe = Stripe('<?php echo get_option('woocommerce_stripe_settings')['publishable_key'] ?? ''; ?>');
        var elements = stripe.elements();
        var cardElement = elements.create('card', {
            style: {
                base: {
                    color: '#ffffff',
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                    fontSmoothing: 'antialiased',
                    fontSize: '16px',
                    '::placeholder': {
                        color: '#999999'
                    }
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a'
                }
            }
        });
        cardElement.mount('#card-element');
        
        // Handle form submission
        $('#add-payment-method-form').on('submit', function(e) {
            e.preventDefault();
            
            stripe.createToken(cardElement).then(function(result) {
                if (result.error) {
                    $('#card-errors').text(result.error.message);
                } else {
                    // Send token to server
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'add_payment_method',
                            token: result.token.id,
                            nonce: '<?php echo wp_create_nonce('add_payment_method_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Card added successfully!');
                                $('#payment-method-modal').fadeOut();
                                location.reload();
                            } else {
                                alert('Error: ' + response.data);
                            }
                        }
                    });
                }
            });
        });
    }
    
    // Set default card
    $('.set-default-card').on('click', function() {
        var tokenId = $(this).data('token-id');
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'set_default_payment_method',
                token_id: tokenId,
                nonce: '<?php echo wp_create_nonce('set_default_payment_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });
    
    // Delete card
    $('.delete-card').on('click', function() {
        if (!confirm('Are you sure you want to delete this card?')) return;
        
        var tokenId = $(this).data('token-id');
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'delete_payment_method',
                token_id: tokenId,
                nonce: '<?php echo wp_create_nonce('delete_payment_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });
    </script>
    
    <style>
    .saved-cards-list {
        margin-bottom: 2rem;
    }
    
    .saved-card-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: #2e2e2e;
        border-radius: 6px;
        margin-bottom: 0.5rem;
    }
    
    .card-details {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    
    .card-brand {
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .card-actions {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    
    .default-badge {
        background: #44da67;
        color: #000;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .set-default-card,
    .delete-card {
        background: transparent;
        border: 1px solid #666;
        color: #999;
        padding: 4px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.875rem;
    }
    
    .set-default-card:hover {
        border-color: #44da67;
        color: #44da67;
    }
    
    .delete-card:hover {
        border-color: #ef4444;
        color: #ef4444;
    }
    
    #card-element {
        background: #2e2e2e;
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
    }
    
    #card-errors {
        color: #ef4444;
        margin-top: 0.5rem;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #999;
    }
    </style>
    <?php
    echo ob_get_clean();
    wp_die();
}

// Add payment method
add_action('wp_ajax_add_payment_method', 'handle_add_payment_method');
function handle_add_payment_method() {
    if (!wp_verify_nonce($_POST['nonce'], 'add_payment_method_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    $token = sanitize_text_field($_POST['token']);
    $user_id = get_current_user_id();
    
    // This would integrate with your payment gateway
    // For Stripe example:
    if (class_exists('WC_Gateway_Stripe')) {
        $stripe = new WC_Gateway_Stripe();
        $result = $stripe->add_payment_method($token);
        
        if ($result) {
            wp_send_json_success('Payment method added successfully');
        } else {
            wp_send_json_error('Failed to add payment method');
        }
    } else {
        wp_send_json_error('Payment gateway not available');
    }
}

// Set default payment method
add_action('wp_ajax_set_default_payment_method', 'handle_set_default_payment_method');
function handle_set_default_payment_method() {
    if (!wp_verify_nonce($_POST['nonce'], 'set_default_payment_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    $token_id = sanitize_text_field($_POST['token_id']);
    $user_id = get_current_user_id();
    
    update_user_meta($user_id, 'wc_default_payment_method', $token_id);
    wp_send_json_success();
}

// Delete payment method
add_action('wp_ajax_delete_payment_method', 'handle_delete_payment_method');
function handle_delete_payment_method() {
    if (!wp_verify_nonce($_POST['nonce'], 'delete_payment_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    $token_id = sanitize_text_field($_POST['token_id']);
    $token = WC_Payment_Tokens::get($token_id);
    
    if ($token && $token->get_user_id() === get_current_user_id()) {
        $token->delete();
        wp_send_json_success();
    } else {
        wp_send_json_error('Invalid token');
    }
}

// Process early renewal
add_action('wp_ajax_process_early_renewal', 'handle_process_early_renewal');
function handle_process_early_renewal() {
    if (!wp_verify_nonce($_POST['nonce'], 'early_renewal_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    $subscription_id = intval($_POST['subscription_id']);
    $subscription = wcs_get_subscription($subscription_id);
    
    if (!$subscription || $subscription->get_user_id() !== get_current_user_id()) {
        wp_send_json_error('Invalid subscription');
    }
    
    // Check if early renewal is allowed
    if (!$subscription->can_be_updated_to('active')) {
        wp_send_json_error('Early renewal not allowed for this subscription');
    }
    
    // Process early renewal
    $renewal_order = wcs_create_renewal_order($subscription);
    
    if (is_wp_error($renewal_order)) {
        wp_send_json_error($renewal_order->get_error_message());
    }
    
    // Process payment
    $payment_result = $renewal_order->payment_complete();
    
    if ($payment_result) {
        $subscription->update_dates(['next_payment' => gmdate('Y-m-d H:i:s', strtotime('+1 month'))]);
        wp_send_json_success('Early renewal processed successfully');
    } else {
        wp_send_json_error('Payment processing failed');
    }
}

// Download invoice
add_action('wp_ajax_download_invoice', 'handle_download_invoice');
add_action('wp_ajax_nopriv_download_invoice', 'handle_download_invoice');
function handle_download_invoice() {
    if (!wp_verify_nonce($_GET['nonce'], 'download_invoice_nonce')) {
        wp_die('Security check failed');
    }
    
    $order_id = intval($_GET['order_id']);
    $order = wc_get_order($order_id);
    
    if (!$order || $order->get_user_id() !== get_current_user_id()) {
        wp_die('Invalid order');
    }
    
    // Check if PDF Invoice plugin is active
    if (class_exists('WPO_WCPDF')) {
        // Use PDF Invoice plugin to generate and download
        $pdf = WPO_WCPDF()->documents->get_document('invoice', $order);
        if ($pdf) {
            $pdf->output_pdf('download');
        }
    } else {
        // Fallback to basic HTML invoice
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="invoice-' . $order_id . '.html"');
        
        echo generate_basic_invoice_html($order);
    }
    
    exit;
}

// Export all invoices
add_action('wp_ajax_export_all_invoices', 'handle_export_all_invoices');
function handle_export_all_invoices() {
    if (!wp_verify_nonce($_GET['nonce'], 'export_invoices_nonce')) {
        wp_die('Security check failed');
    }
    
    $user_id = get_current_user_id();
    $orders = wc_get_orders([
        'customer' => $user_id,
        'limit' => -1,
        'return' => 'objects',
    ]);
    
    // Create CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="invoices-export-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Headers
    fputcsv($output, ['Invoice Number', 'Date', 'Status', 'Total', 'Payment Method', 'Items']);
    
    // Data
    foreach ($orders as $order) {
        $items = [];
        foreach ($order->get_items() as $item) {
            $items[] = $item->get_name() . ' x' . $item->get_quantity();
        }
        
        fputcsv($output, [
            'INV-' . $order->get_order_number(),
            $order->get_date_created()->date('Y-m-d'),
            wc_get_order_status_name($order->get_status()),
            $order->get_total(),
            $order->get_payment_method_title(),
            implode(', ', $items)
        ]);
    }
    
    fclose($output);
    exit;
}

// Helper function to generate basic invoice HTML
function generate_basic_invoice_html($order) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Invoice #<?php echo $order->get_order_number(); ?></title>
        <style>
            body { font-family: Arial, sans-serif; }
            .invoice-header { margin-bottom: 30px; }
            .invoice-details { margin-bottom: 30px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
            .total { font-weight: bold; font-size: 1.2em; }
        </style>
    </head>
    <body>
        <div class="invoice-header">
            <h1>Invoice #<?php echo $order->get_order_number(); ?></h1>
            <p>Date: <?php echo $order->get_date_created()->date('F j, Y'); ?></p>
        </div>
        
        <div class="invoice-details">
            <h3>Bill To:</h3>
            <p>
                <?php echo $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(); ?><br>
                <?php echo $order->get_billing_email(); ?><br>
                <?php echo $order->get_billing_phone(); ?>
            </p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order->get_items() as $item): ?>
                <tr>
                    <td><?php echo $item->get_name(); ?></td>
                    <td><?php echo $item->get_quantity(); ?></td>
                    <td><?php echo wc_price($item->get_subtotal() / $item->get_quantity()); ?></td>
                    <td><?php echo wc_price($item->get_total()); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total">
                    <td colspan="3">Total</td>
                    <td><?php echo wc_price($order->get_total()); ?></td>
                </tr>
            </tfoot>
        </table>
    </body>
    </html>
    <?php
    return ob_get_clean();
}