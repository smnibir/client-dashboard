<?php


// Register REST API routes
add_action('rest_api_init', function () {
    // Authentication endpoint
    register_rest_route('webgrowth/v1', '/login', [
        'methods' => 'POST',
        'callback' => 'webgrowth_api_login',
        'permission_callback' => '__return_true'
    ]);

    // Dashboard data endpoint
    register_rest_route('webgrowth/v1', '/dashboard', [
        'methods' => 'GET',
        'callback' => 'webgrowth_api_get_dashboard',
        'permission_callback' => 'webgrowth_api_permissions_check'
    ]);

    // Meeting notes endpoint
    register_rest_route('webgrowth/v1', '/meeting-notes', [
        'methods' => 'GET',
        'callback' => 'webgrowth_api_get_meeting_notes',
        'permission_callback' => 'webgrowth_api_permissions_check'
    ]);

    // Tasks endpoint
    register_rest_route('webgrowth/v1', '/tasks', [
        'methods' => 'GET',
        'callback' => 'webgrowth_api_get_tasks',
        'permission_callback' => 'webgrowth_api_permissions_check'
    ]);

    // Billing endpoint
    register_rest_route('webgrowth/v1', '/billing', [
        'methods' => 'GET',
        'callback' => 'webgrowth_api_get_billing',
        'permission_callback' => 'webgrowth_api_permissions_check'
    ]);

    // Brand assets endpoint
    register_rest_route('webgrowth/v1', '/brand-assets', [
        'methods' => 'GET',
        'callback' => 'webgrowth_api_get_brand_assets',
        'permission_callback' => 'webgrowth_api_permissions_check'
    ]);

    // Performance summary endpoint
    register_rest_route('webgrowth/v1', '/performance', [
        'methods' => 'GET',
        'callback' => 'webgrowth_api_get_performance',
        'permission_callback' => 'webgrowth_api_permissions_check'
    ]);

    // User profile endpoint
    register_rest_route('webgrowth/v1', '/profile', [
        'methods' => 'GET',
        'callback' => 'webgrowth_api_get_profile',
        'permission_callback' => 'webgrowth_api_permissions_check'
    ]);
});

// Authentication function
function webgrowth_api_login($request) {
    $username = $request->get_param('username');
    $password = $request->get_param('password');

    $user = wp_authenticate($username, $password);

    if (is_wp_error($user)) {
        return new WP_Error('invalid_credentials', 'Invalid username or password', ['status' => 401]);
    }

    // Generate JWT token (install JWT Authentication plugin or implement custom)
    $token = webgrowth_generate_jwt_token($user->ID);

    return [
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user->ID,
            'email' => $user->user_email,
            'first_name' => get_user_meta($user->ID, 'first_name', true),
            'last_name' => get_user_meta($user->ID, 'last_name', true),
            'company_name' => get_field('company_name', 'user_' . $user->ID),
            'profile_image' => get_field('profile_image', 'user_' . $user->ID)
        ]
    ];
}

// Permission check
function webgrowth_api_permissions_check($request) {
    $token = $request->get_header('Authorization');
    
    if (!$token) {
        return new WP_Error('no_auth', 'No authorization token provided', ['status' => 401]);
    }

    $user_id = webgrowth_validate_jwt_token($token);
    
    if (!$user_id) {
        return new WP_Error('invalid_auth', 'Invalid authorization token', ['status' => 401]);
    }

    // Set current user for the request
    wp_set_current_user($user_id);
    
    return true;
}

// Get dashboard data
function webgrowth_api_get_dashboard($request) {
    $user_id = get_current_user_id();
    
    return [
        'welcome_message' => get_field('welcome', 'user_' . $user_id),
        'first_name' => get_user_meta($user_id, 'first_name', true),
        'last_name' => get_user_meta($user_id, 'last_name', true),
        'company_name' => get_field('company_name', 'user_' . $user_id),
        'company_logo' => get_field('company_logo', 'user_' . $user_id),
        'profile_image' => get_field('profile_image', 'user_' . $user_id)
    ];
}

// Get meeting notes
function webgrowth_api_get_meeting_notes($request) {
    $user_id = get_current_user_id();
    $doc_id = get_field('client_portal', 'user_' . $user_id);
    $workspace_id = get_option('clickup_workspace_id');
    $api_key = get_option('clickup_api_key');
    
    if (!$doc_id || !$workspace_id || !$api_key) {
        return new WP_Error('missing_config', 'Missing ClickUp configuration', ['status' => 400]);
    }

    $response = wp_remote_get("https://api.clickup.com/api/v3/workspaces/{$workspace_id}/docs/{$doc_id}/pages", [
        'headers' => ['Authorization' => $api_key],
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('api_error', 'Error fetching ClickUp data', ['status' => 500]);
    }

    $pages = json_decode(wp_remote_retrieve_body($response), true);
    $meeting_notes_page = null;

    foreach ($pages as $page) {
        if ($page['name'] === 'Meeting Notes') {
            $meeting_notes_page = $page;
            break;
        }
    }

    return [
        'content' => $meeting_notes_page ? $meeting_notes_page['content'] : '',
        'formatted_content' => $meeting_notes_page ? webgrowth_parse_markdown($meeting_notes_page['content']) : ''
    ];
}

// Get tasks
function webgrowth_api_get_tasks($request) {
    $user_id = get_current_user_id();
    $api_key = get_option('clickup_api_key');
    $folder_id = get_field('clickup_folder', 'user_' . $user_id);

    if (!$api_key || !$folder_id) {
        return new WP_Error('missing_config', 'Missing ClickUp configuration', ['status' => 400]);
    }

    $all_tasks = [];
    $list_ids = [];

    // Get Lists in Folder
    $res_lists = wp_remote_get("https://api.clickup.com/api/v2/folder/{$folder_id}/list", [
        'headers' => ['Authorization' => $api_key]
    ]);

    $lists = json_decode(wp_remote_retrieve_body($res_lists), true)['lists'] ?? [];

    foreach ($lists as $list) {
        $list_ids[] = $list['id'];
    }

    // Get tasks from each list
    foreach ($list_ids as $list_id) {
        $response = wp_remote_get("https://api.clickup.com/api/v2/list/{$list_id}/task?subtasks=true&include_closed=true", [
            'headers' => ['Authorization' => $api_key]
        ]);

        $tasks = json_decode(wp_remote_retrieve_body($response), true)['tasks'] ?? [];
        $all_tasks = array_merge($all_tasks, $tasks);
    }

    // Format tasks for mobile app
    $formatted_tasks = array_map(function($task) {
        $status = strtolower($task['status']['status'] ?? 'unknown');
        $category = '';
        $category_color = '#2ecc71';
        
        foreach ($task['custom_fields'] as $field) {
            if ($field['name'] === 'Category' && isset($field['value'])) {
                $options = $field['type_config']['options'] ?? [];
                if (isset($options[$field['value']])) {
                    $category = $options[$field['value']]['name'] ?? '';
                    $category_color = $options[$field['value']]['color'] ?? '#000000';
                }
            }
        }

        return [
            'id' => $task['id'],
            'name' => $task['name'],
            'description' => $task['description'] ?? '',
            'status' => $status,
            'priority' => $task['priority']['priority'] ?? '',
            'priority_color' => $task['priority']['color'] ?? '#000000',
            'category' => $category,
            'category_color' => $category_color,
            'assignees' => array_map(fn($a) => $a['username'], $task['assignees'] ?? []),
            'date_created' => date('Y-m-d', intval($task['date_created'] / 1000)),
            'due_date' => isset($task['due_date']) ? date('Y-m-d', intval($task['due_date'] / 1000)) : null
        ];
    }, $all_tasks);

    return [
        'total' => count($formatted_tasks),
        'completed' => count(array_filter($formatted_tasks, fn($t) => $t['status'] === 'complete')),
        'in_progress' => count(array_filter($formatted_tasks, fn($t) => $t['status'] === 'in progress')),
        'upcoming' => count(array_filter($formatted_tasks, fn($t) => !in_array($t['status'], ['complete', 'in progress']))),
        'tasks' => $formatted_tasks
    ];
}

// Get billing information
function webgrowth_api_get_billing($request) {
    $user_id = get_current_user_id();
    
    if (!class_exists('WooCommerce')) {
        return new WP_Error('woo_not_found', 'WooCommerce is not installed', ['status' => 400]);
    }

    $customer = new WC_Customer($user_id);
    $subscriptions = [];
    
    if (function_exists('wcs_get_users_subscriptions')) {
        $user_subscriptions = wcs_get_users_subscriptions($user_id);
        
        foreach ($user_subscriptions as $subscription) {
            if (!in_array($subscription->get_status(), ['active', 'pending-cancel'])) continue;
            
            $items = [];
            foreach ($subscription->get_items() as $item) {
                $product = $item->get_product();
                $items[] = [
                    'name' => $item->get_name(),
                    'quantity' => $item->get_quantity(),
                    'total' => $item->get_total(),
                    'short_description' => $product ? $product->get_short_description() : ''
                ];
            }
            
            $subscriptions[] = [
                'id' => $subscription->get_id(),
                'status' => $subscription->get_status(),
                'total' => $subscription->get_total(),
                'billing_period' => $subscription->get_billing_period(),
                'billing_interval' => $subscription->get_billing_interval(),
                'next_payment_date' => $subscription->get_date('next_payment'),
                'start_date' => $subscription->get_date('start'),
                'items' => $items
            ];
        }
    }

    // Get recent orders
    $orders = wc_get_orders([
        'customer' => $user_id,
        'limit' => 10,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects',
    ]);

    $formatted_orders = array_map(function($order) {
        return [
            'id' => $order->get_id(),
            'number' => $order->get_order_number(),
            'date' => $order->get_date_created()->date('Y-m-d'),
            'status' => $order->get_status(),
            'total' => $order->get_total(),
            'payment_method' => $order->get_payment_method_title()
        ];
    }, $orders);

    return [
        'subscriptions' => $subscriptions,
        'orders' => $formatted_orders,
        'current_month_total' => webgrowth_calculate_monthly_total($user_id),
        'payment_methods' => webgrowth_get_payment_methods($user_id)
    ];
}

// Get brand assets
function webgrowth_api_get_brand_assets($request) {
    $user_id = get_current_user_id();
    
    return [
        'company_logo' => get_field('company_logo', 'user_' . $user_id),
        'company_name' => get_field('company_name', 'user_' . $user_id),
        'company_sub_title' => get_field('company_sub_title', 'user_' . $user_id),
        'color_palette' => get_field('color_palate', 'user_' . $user_id) ?: [],
        'typography' => get_field('typography', 'user_' . $user_id) ?: [],
        'download_assets' => get_field('download_assets', 'user_' . $user_id) ?: [],
        'team_contacts' => get_field('team_contacts', 'user_' . $user_id) ?: [],
        'drive_links' => [
            'core' => get_field('core_drive_link', 'user_' . $user_id),
            'assets' => get_field('asset_drive_link', 'user_' . $user_id)
        ]
    ];
}

// Get performance data
function webgrowth_api_get_performance($request) {
    $user_id = get_current_user_id();
    $doc_id = get_field('client_portal', 'user_' . $user_id);
    $workspace_id = get_option('clickup_workspace_id');
    $api_key = get_option('clickup_api_key');
    
    if (!$doc_id || !$workspace_id || !$api_key) {
        return new WP_Error('missing_config', 'Missing ClickUp configuration', ['status' => 400]);
    }

    $response = wp_remote_get("https://api.clickup.com/api/v3/workspaces/{$workspace_id}/docs/{$doc_id}/pages", [
        'headers' => ['Authorization' => $api_key],
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('api_error', 'Error fetching ClickUp data', ['status' => 500]);
    }

    $pages = json_decode(wp_remote_retrieve_body($response), true);
    $performance_page = null;

    foreach ($pages as $page) {
        if ($page['name'] === 'Performance Summary') {
            $performance_page = $page;
            break;
        }
    }

    return [
        'content' => $performance_page ? $performance_page['content'] : '',
        'formatted_content' => $performance_page ? webgrowth_parse_markdown($performance_page['content']) : ''
    ];
}

// Get user profile
function webgrowth_api_get_profile($request) {
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    
    return [
        'id' => $user_id,
        'email' => $user->user_email,
        'first_name' => get_field('first_name', 'user_' . $user_id) ?: $user->first_name,
        'last_name' => get_field('last_name', 'user_' . $user_id) ?: $user->last_name,
        'company_name' => get_field('company_name', 'user_' . $user_id),
        'profile_image' => get_field('profile_image', 'user_' . $user_id),
        'clickup_space' => get_field('clickup_space', 'user_' . $user_id),
        'clickup_folder' => get_field('clickup_folder', 'user_' . $user_id),
        'client_portal' => get_field('client_portal', 'user_' . $user_id)
    ];
}

// Helper functions
function webgrowth_generate_jwt_token($user_id) {
    // Implement JWT token generation
    // You can use Firebase JWT library or WordPress JWT Authentication plugin
    $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : 'your-secret-key';
    
    $payload = [
        'iss' => get_site_url(),
        'iat' => time(),
        'exp' => time() + (7 * 24 * 60 * 60), // 7 days
        'user_id' => $user_id
    ];
    
    // Return base64 encoded token for simplicity (use proper JWT library in production)
    return base64_encode(json_encode($payload));
}

function webgrowth_validate_jwt_token($token) {
    // Remove "Bearer " prefix if present
    $token = str_replace('Bearer ', '', $token);
    
    // Implement JWT token validation
    // This is simplified - use proper JWT library in production
    $payload = json_decode(base64_decode($token), true);
    
    if (!$payload || !isset($payload['user_id']) || !isset($payload['exp'])) {
        return false;
    }
    
    if ($payload['exp'] < time()) {
        return false;
    }
    
    return $payload['user_id'];
}

function webgrowth_parse_markdown($content) {
    if (!class_exists('Parsedown')) {
        require_once plugin_dir_path(__DIR__) . 'includes/parsedown.php';
    }
    
    $parsedown = new Parsedown();
    return $parsedown->text($content);
}

function webgrowth_calculate_monthly_total($user_id) {
    $orders = wc_get_orders([
        'customer' => $user_id,
        'date_created' => '>' . date('Y-m-01'),
        'status' => ['completed', 'processing'],
        'return' => 'objects',
    ]);
    
    $total = 0;
    foreach ($orders as $order) {
        $total += $order->get_total();
    }
    
    return $total;
}

function webgrowth_get_payment_methods($user_id) {
    $methods = [];
    
    if (function_exists('wc_get_customer_saved_methods_list')) {
        $saved_methods = wc_get_customer_saved_methods_list($user_id);
        
        if (!empty($saved_methods['card'])) {
            foreach ($saved_methods['card'] as $method) {
                $card = $method['method'];
                $methods[] = [
                    'id' => $card->get_id(),
                    'brand' => $card->get_brand(),
                    'last4' => $card->get_last4(),
                    'expiry_month' => $card->get_expiry_month(),
                    'expiry_year' => $card->get_expiry_year(),
                    'is_default' => $card->get_id() === get_user_meta($user_id, 'wc_default_payment_method', true)
                ];
            }
        }
    }
    
    return $methods;
}

// Add CORS headers for mobile app
add_action('rest_api_init', function() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($value) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit;
        }
        
        return $value;
    });
}, 15);