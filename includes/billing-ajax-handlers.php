<?php
/**
 * AJAX Handlers for Billing & Payment functionality
 * Properly integrated with WooCommerce Stripe Gateway
 */

// Check if user has payment methods
add_action('wp_ajax_check_payment_methods', 'handle_check_payment_methods');
function handle_check_payment_methods() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'check_payment_methods_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error('Please log in to check payment methods');
    }
    
    $user_id = get_current_user_id();
    $saved_methods = wc_get_customer_saved_methods_list($user_id);
    $has_payment_method = !empty($saved_methods['card']);
    
    wp_send_json_success(['has_payment_method' => $has_payment_method]);
}

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
    $card_count = !empty($saved_methods['card']) ? count($saved_methods['card']) : 0;
    
    // Get Stripe settings
    $stripe_settings = get_option('woocommerce_stripe_settings');
    $publishable_key = isset($stripe_settings['publishable_key']) ? $stripe_settings['publishable_key'] : '';
    
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
                    <div class="saved-card-item" data-card-count="<?php echo esc_attr($card_count); ?>">
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
                            <?php if ($card_count > 1): ?>
                                <button class="delete-card" data-token-id="<?php echo esc_attr($card->get_id()); ?>">Delete</button>
                            <?php else: ?>
                                <span class="min-card-notice" title="At least one card must be kept on file">Protected</span>
                            <?php endif; ?>
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
            
            <input type="hidden" name="stripe_source" id="stripe-source" value="">
            <input type="hidden" name="payment_method_nonce" value="<?php echo wp_create_nonce('add_payment_method'); ?>">
            
            <button type="submit" class="btn-primary" id="submit-payment-method">Add Card</button>
        </form>
    </div>
    
    <script>
    // Initialize Stripe
    if (typeof Stripe !== 'undefined' && '<?php echo esc_js($publishable_key); ?>') {
        var stripe = Stripe('<?php echo esc_js($publishable_key); ?>');
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
        
        // Handle real-time validation errors from the card Element
        cardElement.addEventListener('change', function(event) {
            var displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });
        
        // Handle form submission
        var form = document.getElementById('add-payment-method-form');
        var submitButton = document.getElementById('submit-payment-method');
        
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Disable submit button
            submitButton.disabled = true;
            submitButton.textContent = 'Processing...';
            
            stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
            }).then(function(result) {
                if (result.error) {
                    // Show error to customer
                    var errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                    
                    // Re-enable submit button
                    submitButton.disabled = false;
                    submitButton.textContent = 'Add Card';
                } else {
                    // Send payment method to server
                    stripePaymentMethodHandler(result.paymentMethod);
                }
            });
        });
        
        function stripePaymentMethodHandler(paymentMethod) {
            // Insert the payment method ID into the form
            var hiddenInput = document.getElementById('stripe-source');
            hiddenInput.value = paymentMethod.id;
            
            // Submit via AJAX
            var formData = new FormData(form);
            formData.append('action', 'add_stripe_payment_method');
            formData.append('payment_method_id', paymentMethod.id);
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Success - trigger event and close modal
                    alert('Card added successfully!');
                    jQuery(document).trigger('payment_method_added');
                    location.reload();
                } else {
                    // Show error
                    document.getElementById('card-errors').textContent = data.data || 'An error occurred. Please try again.';
                    submitButton.disabled = false;
                    submitButton.textContent = 'Add Card';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('card-errors').textContent = 'An error occurred. Please try again.';
                submitButton.disabled = false;
                submitButton.textContent = 'Add Card';
            });
        }
    } else {
        document.getElementById('card-errors').textContent = 'Stripe is not properly configured. Please contact support.';
    }
    
    // Set default card
    document.querySelectorAll('.set-default-card').forEach(function(button) {
        button.addEventListener('click', function() {
            var tokenId = this.dataset.tokenId;
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=set_default_payment_method&token_id=' + tokenId + '&nonce=<?php echo wp_create_nonce('set_default_payment_nonce'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        });
    });
    
    // Delete card with minimum card check
    document.querySelectorAll('.delete-card').forEach(function(button) {
        button.addEventListener('click', function() {
            var cardCount = parseInt(this.closest('.saved-card-item').dataset.cardCount);
            
            if (cardCount <= 1) {
                alert('You must keep at least one payment method on file.');
                return;
            }
            
            if (!confirm('Are you sure you want to delete this card?')) return;
            
            var tokenId = this.dataset.tokenId;
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=delete_payment_method&token_id=' + tokenId + '&nonce=<?php echo wp_create_nonce('delete_payment_nonce'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.data || 'Error deleting card.');
                }
            });
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
    
    .min-card-notice {
        color: #666;
        font-size: 0.75rem;
        font-style: italic;
        padding: 4px 12px;
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
    
    .delete-card:disabled {
        opacity: 0.5;
        cursor: not-allowed;
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
        font-size: 0.875rem;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #999;
    }
    
    .btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    </style>
    <?php
    echo ob_get_clean();
    wp_die();
}

// Add Stripe payment method
add_action('wp_ajax_add_stripe_payment_method', 'handle_add_stripe_payment_method');
function handle_add_stripe_payment_method() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['payment_method_nonce'], 'add_payment_method')) {
        wp_send_json_error('Security check failed');
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error('Please log in to add payment methods');
    }
    
    $payment_method_id = sanitize_text_field($_POST['payment_method_id']);
    $user_id = get_current_user_id();
    
    // Check if WooCommerce Stripe Gateway is active
    if (!class_exists('WC_Gateway_Stripe')) {
        wp_send_json_error('Stripe gateway is not available');
    }
    
    try {
        // Get the Stripe gateway instance
        $stripe_gateway = WC()->payment_gateways->payment_gateways()['stripe'];
        
        if (!$stripe_gateway) {
            wp_send_json_error('Stripe gateway not found');
        }
        
        // Get Stripe customer ID for the user
        $stripe_customer_id = get_user_meta($user_id, '_stripe_customer_id', true);
        
        if (!$stripe_customer_id) {
            // Create a new Stripe customer
            $customer_data = array(
                'email' => wp_get_current_user()->user_email,
                'description' => 'Customer for user #' . $user_id,
            );
            
            $response = WC_Stripe_API::request($customer_data, 'customers');
            
            if (is_wp_error($response)) {
                wp_send_json_error($response->get_error_message());
            }
            
            $stripe_customer_id = $response->id;
            update_user_meta($user_id, '_stripe_customer_id', $stripe_customer_id);
        }
        
        // Attach payment method to customer
        $attach_response = WC_Stripe_API::request(
            array('customer' => $stripe_customer_id),
            'payment_methods/' . $payment_method_id . '/attach'
        );
        
        if (is_wp_error($attach_response)) {
            wp_send_json_error($attach_response->get_error_message());
        }
        
        // Save the payment method as a token in WooCommerce
        $token = new WC_Payment_Token_CC();
        $token->set_token($payment_method_id);
        $token->set_gateway_id('stripe');
        $token->set_user_id($user_id);
        
        // Set card details from the payment method
        if (isset($attach_response->card)) {
            $token->set_card_type(strtolower($attach_response->card->brand));
            $token->set_last4($attach_response->card->last4);
            $token->set_expiry_month($attach_response->card->exp_month);
            $token->set_expiry_year($attach_response->card->exp_year);
        }
        
        // Save the token
        $token->save();
        
        // If this is the first card, make it default
        $saved_methods = wc_get_customer_saved_methods_list($user_id);
        if (empty($saved_methods) || count($saved_methods['card']) === 1) {
            $token->set_default(true);
            $token->save();
        }
        
        wp_send_json_success('Payment method added successfully');
        
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}

// Set default payment method
add_action('wp_ajax_set_default_payment_method', 'handle_set_default_payment_method');
function handle_set_default_payment_method() {
    if (!wp_verify_nonce($_POST['nonce'], 'set_default_payment_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    $token_id = intval($_POST['token_id']);
    $token = WC_Payment_Tokens::get($token_id);
    
    if ($token && $token->get_user_id() === get_current_user_id()) {
        WC_Payment_Tokens::set_users_default($token->get_user_id(), $token_id);
        wp_send_json_success();
    } else {
        wp_send_json_error('Invalid token');
    }
}

// Delete payment method with minimum card check
add_action('wp_ajax_delete_payment_method', 'handle_delete_payment_method');
function handle_delete_payment_method() {
    if (!wp_verify_nonce($_POST['nonce'], 'delete_payment_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    $token_id = intval($_POST['token_id']);
    $user_id = get_current_user_id();
    
    // Check if user has more than one payment method
    $saved_methods = wc_get_customer_saved_methods_list($user_id);
    $card_count = !empty($saved_methods['card']) ? count($saved_methods['card']) : 0;
    
    if ($card_count <= 1) {
        wp_send_json_error('You must keep at least one payment method on file.');
    }
    
    $token = WC_Payment_Tokens::get($token_id);
    
    if ($token && $token->get_user_id() === $user_id) {
        // If deleting the default card, set another as default
        if ($token->is_default()) {
            foreach ($saved_methods['card'] as $method) {
                if ($method['method']->get_id() != $token_id) {
                    WC_Payment_Tokens::set_users_default($user_id, $method['method']->get_id());
                    break;
                }
            }
        }
        
        $token->delete();
        wp_send_json_success();
    } else {
        wp_send_json_error('Invalid token');
    }
}

// Process early renewal with payment validation
add_action('wp_ajax_process_early_renewal', 'handle_process_early_renewal');
function handle_process_early_renewal() {
    if (!wp_verify_nonce($_POST['nonce'], 'early_renewal_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    $subscription_id = intval($_POST['subscription_id']);
    $subscription = wcs_get_subscription($subscription_id);
    $user_id = get_current_user_id();
    
    if (!$subscription || $subscription->get_user_id() !== $user_id) {
        wp_send_json_error('Invalid subscription');
    }
    
    // Check if user has payment methods
    $saved_methods = wc_get_customer_saved_methods_list($user_id);
    if (empty($saved_methods['card'])) {
        wp_send_json_error('No payment method on file. Please add a payment method to continue.');
    }
    
    // Get default payment method
    $default_token_id = get_user_meta($user_id, 'wc_default_payment_method', true);
    if (!$default_token_id) {
        // If no default, use the first available
        $first_method = reset($saved_methods['card']);
        $default_token_id = $first_method['method']->get_id();
    }
    
    // Check if subscription can be renewed
    if (!$subscription->can_be_updated_to('active') && $subscription->get_status() !== 'on-hold') {
        wp_send_json_error('This subscription cannot be renewed at this time');
    }
    
    try {
        // Process payment based on subscription status
        if ($subscription->get_status() === 'on-hold') {
            // This is an overdue payment - attempt to reactivate
            $order = $subscription->get_last_order('all', 'renewal');
            
            if ($order && $order->needs_payment()) {
                // Set the payment method on the order
                $token = WC_Payment_Tokens::get($default_token_id);
                if ($token) {
                    $order->set_payment_method($token->get_gateway_id());
                    $order->add_payment_token($token);
                    $order->save();
                    
                    // Process the payment
                    $result = $order->payment_complete();
                    
                    if ($result) {
                        // Reactivate subscription
                        $subscription->update_status('active');
                        wp_send_json_success('Payment processed successfully. Subscription reactivated.');
                    } else {
                        wp_send_json_error('Payment processing failed. Please check your payment method.');
                    }
                } else {
                    wp_send_json_error('Invalid payment method');
                }
            } else {
                wp_send_json_error('No pending payment found for this subscription');
            }
        } else {
            // Regular early renewal
            $renewal_order = wcs_create_renewal_order($subscription);
            
            if (is_wp_error($renewal_order)) {
                wp_send_json_error($renewal_order->get_error_message());
            }
            
            // Set payment method on the renewal order
            $token = WC_Payment_Tokens::get($default_token_id);
            if ($token) {
                $renewal_order->set_payment_method($token->get_gateway_id());
                $renewal_order->add_payment_token($token);
                $renewal_order->save();
                
                // Process payment
                $payment_result = $renewal_order->payment_complete();
                
                if ($payment_result) {
                    $subscription->update_dates(['next_payment' => gmdate('Y-m-d H:i:s', strtotime('+1 ' . $subscription->get_billing_period()))]);
                    wp_send_json_success('Early renewal processed successfully');
                } else {
                    wp_send_json_error('Payment processing failed. Please check your payment method.');
                }
            } else {
                wp_send_json_error('Invalid payment method');
            }
        }
    } catch (Exception $e) {
        wp_send_json_error('Error processing payment: ' . $e->getMessage());
    }
}

// Helper function to check if Stripe API class exists
if (!function_exists('wc_stripe_api_loaded')) {
    function wc_stripe_api_loaded() {
        if (class_exists('WC_Stripe_API')) {
            return true;
        }
        
        // Try to load the Stripe API class
        $stripe_path = WP_PLUGIN_DIR . '/woocommerce-gateway-stripe/includes/class-wc-stripe-api.php';
        if (file_exists($stripe_path)) {
            require_once $stripe_path;
            return true;
        }
        
        return false;
    }
}

// Make sure Stripe API is loaded when needed
add_action('init', function() {
    if (is_admin() && defined('DOING_AJAX') && DOING_AJAX) {
        wc_stripe_api_loaded();
    }
});