<?php







// Get Folders for a Space
add_action('wp_ajax_get_clickup_folders', function () {
    $space_id = sanitize_text_field($_POST['space_id']);
    $api_key = get_option('clickup_api_key');

    $response = wp_remote_get("https://api.clickup.com/api/v2/space/$space_id/folder", [
        'headers' => ['Authorization' => $api_key]
    ]);

    if (is_wp_error($response)) wp_send_json_error(['message' => 'ClickUp Folder API Error']);

    $folders = [];
    $data = json_decode(wp_remote_retrieve_body($response), true);
    foreach ($data['folders'] ?? [] as $folder) {
        $folders[$folder['id']] = $folder['name'];
    }

    wp_send_json_success($folders);
});

// Get Views for a Folder
add_action('wp_ajax_get_clickup_views', function () {
    $folder_id = sanitize_text_field($_POST['folder_id']);
    $api_key = get_option('clickup_api_key');

    $response = wp_remote_get("https://api.clickup.com/api/v2/folder/$folder_id/view", [
        'headers' => ['Authorization' => $api_key]
    ]);

    if (is_wp_error($response)) wp_send_json_error(['message' => 'ClickUp View API Error']);

    $views = [];
    $data = json_decode(wp_remote_retrieve_body($response), true);
    foreach ($data['views'] ?? [] as $view) {
        if (!empty($view['id']) && !empty($view['name'])) {
            $views[$view['id']] = $view['name'];
        }
    }

    wp_send_json_success($views);
});

// wc
// Fetch WhatConverts accounts
function fetch_whatconverts_accounts($token, $secret) {
    $url = 'https://app.whatconverts.com/api/v1/accounts';
    
    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($token . ':' . $secret),
            'Content-Type' => 'application/json'
        ]
    ]);
    
    if (is_wp_error($response)) {
        error_log('WhatConverts accounts API error: ' . $response->get_error_message());
        return [];
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['accounts'] ?? [];
}

// AJAX handler to fetch leads with proper error handling
add_action('wp_ajax_fetch_whatconverts_leads', 'fetch_whatconverts_leads');
add_action('wp_ajax_nopriv_fetch_whatconverts_leads', 'fetch_whatconverts_leads');

function fetch_whatconverts_leads() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Unauthorized', 401);
    }
    
    $user_id = get_current_user_id();
    $account_id = get_field('lead_account', 'user_' . $user_id);
    
    if (!$account_id) {
        wp_send_json_error('No account selected', 400);
    }
    
    $api_token = get_option('whatconverts_api_token');
    $api_secret = get_option('whatconverts_api_secret');
    
    if (!$api_token || !$api_secret) {
        wp_send_json_error('API credentials not configured', 500);
    }
    
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $date_range = isset($_POST['dateRange']) ? sanitize_text_field($_POST['dateRange']) : '30';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : '';
    $lead_type = isset($_POST['lead_type']) ? sanitize_text_field($_POST['lead_type']) : '';

    // Calculate date range
    $end_date = date('Y-m-d');
    $start_date = ($date_range === 'custom' && isset($_POST['start_date']) && isset($_POST['end_date'])) 
        ? sanitize_text_field($_POST['start_date']) 
        : date('Y-m-d', strtotime("-$date_range days"));
    
    // Build API URL
    $params = [
        'account_id' => $account_id,
        'page_number' => $page,
        'leads_per_page' => 50,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
    
    if ($status) $params['lead_status'] = $status;
    if ($source) $params['lead_source'] = $source;
    if ($lead_type) $params['lead_type'] = $lead_type;
    
    $url = 'https://app.whatconverts.com/api/v1/leads?' . http_build_query($params);
    
    error_log('WhatConverts API URL: ' . $url); // Debug log
    
    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($api_token . ':' . $api_secret),
            'Content-Type' => 'application/json'
        ],
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        error_log('WhatConverts leads API error: ' . $response->get_error_message());
        wp_send_json_error('Failed to fetch leads: ' . $response->get_error_message(), 500);
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($response_code !== 200) {
        error_log('WhatConverts API error response: ' . print_r($body, true));
        wp_send_json_error('API error: ' . ($body['message'] ?? 'Unknown error'), $response_code);
    }
    
    // Collect unique values for filters
    $all_sources = [];
    $all_statuses = [];
    $all_types = [];
    
    $leads = array_map(function($lead) use (&$all_sources, &$all_statuses, &$all_types) {
        // Collect filter values
        if (!empty($lead['lead_source']) && !in_array($lead['lead_source'], $all_sources)) {
            $all_sources[] = $lead['lead_source'];
        }
        if (!empty($lead['lead_status']) && !in_array($lead['lead_status'], $all_statuses)) {
            $all_statuses[] = $lead['lead_status'];
        }
        if (!empty($lead['lead_type']) && !in_array($lead['lead_type'], $all_types)) {
            $all_types[] = $lead['lead_type'];
        }
        
        return [
            'lead_id' => $lead['lead_id'],
            'lead_type' => $lead['lead_type'] ?? 'Unknown',
            'email' => $lead['email'] ?? '-',
            'phone_number' => $lead['phone_number'] ?? '-',
            'lead_status' => ucwords($lead['lead_status'] ?? 'new'),
            'source' => $lead['lead_source'] ?? '-',
            'medium' => $lead['lead_medium'] ?? '-',
            'campaign' => $lead['lead_campaign'] ?? '-',
            'lead_summary' => $lead['lead_summary'] ?? $lead['lead_notes'] ?? '-',
            'created_at' => $lead['date_created'] ?? date('Y-m-d H:i:s')
        ];
    }, $body['leads'] ?? []);
    
    // Store filter values in transient for filter endpoint
    set_transient('whatconverts_filter_values_' . $user_id, [
        'sources' => $all_sources,
        'statuses' => $all_statuses,
        'types' => $all_types
    ], 3600); // Cache for 1 hour
    
    wp_send_json_success([
        'leads' => $leads,
        'total_pages' => $body['total_pages'] ?? 1,
        'total_leads' => $body['total_leads'] ?? 0,
        'filters' => [
            'sources' => $all_sources,
            'statuses' => $all_statuses,
            'types' => $all_types
        ]
    ]);
}

// AJAX handler to fetch lead details with recording
add_action('wp_ajax_fetch_lead_details', 'fetch_lead_details');
add_action('wp_ajax_nopriv_fetch_lead_details', 'fetch_lead_details');

function fetch_lead_details() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Unauthorized', 401);
    }
    
    $lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
    
    if (!$lead_id) {
        wp_send_json_error('Invalid lead ID', 400);
    }
    
    $api_token = get_option('whatconverts_api_token');
    $api_secret = get_option('whatconverts_api_secret');
    
    if (!$api_token || !$api_secret) {
        wp_send_json_error('API credentials not configured', 500);
    }
    
    // Fetch lead details
    $url = "https://app.whatconverts.com/api/v1/leads/$lead_id";
    
    error_log('Fetching lead details from: ' . $url); // Debug log
    
    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($api_token . ':' . $api_secret),
            'Content-Type' => 'application/json'
        ],
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        error_log('WhatConverts lead details API error: ' . $response->get_error_message());
        wp_send_json_error('Failed to fetch lead details: ' . $response->get_error_message(), 500);
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    error_log('Lead details response code: ' . $response_code); // Debug log
    error_log('Lead details response body: ' . substr($body, 0, 500)); // Debug log first 500 chars
    
    if ($response_code !== 200) {
        wp_send_json_error('Failed to fetch lead details. Response code: ' . $response_code, $response_code);
    }
    
    $lead_data = json_decode($body, true);
    
    if (!$lead_data) {
        wp_send_json_error('Invalid response format', 500);
    }
    
    // Map all possible fields from the API response
    $lead = [
        'lead_id' => $lead_data['lead_id'] ?? $lead_id,
        'name' => $lead_data['quotable_name'] ?? $lead_data['name'] ?? 'N/A',
        'email' => $lead_data['email_address'] ?? $lead_data['email'] ?? 'N/A',
        'phone_number' => $lead_data['phone_number'] ?? 'N/A',
        'lead_type' => $lead_data['lead_type'] ?? 'N/A',
        'source' => $lead_data['lead_source'] ?? $lead_data['source'] ?? 'N/A',
        'medium' => $lead_data['lead_medium'] ?? $lead_data['medium'] ?? 'N/A',
        'campaign' => $lead_data['lead_campaign'] ?? $lead_data['campaign'] ?? 'N/A',
        'keyword' => $lead_data['lead_keyword'] ?? $lead_data['keyword'] ?? 'N/A',
        'landing_page' => $lead_data['landing_url'] ?? $lead_data['landing_page_url'] ?? $lead_data['landing_page'] ?? 'N/A',
        'referring_url' => $lead_data['referring_url'] ?? 'N/A',
        'ip_address' => $lead_data['ip_address'] ?? 'N/A',
        'city' => $lead_data['city'] ?? 'N/A',
        'state' => $lead_data['state'] ?? $lead_data['state_or_province'] ?? 'N/A',
        'country' => $lead_data['country'] ?? 'N/A',
        'lead_summary' => $lead_data['lead_notes'] ?? $lead_data['lead_summary'] ?? $lead_data['details'] ?? 'No summary available',
        'lead_value' => $lead_data['lead_value'] ?? $lead_data['value'] ?? 'N/A',
        'date_created' => $lead_data['date_created'] ?? 'N/A',
        'form_data' => $lead_data['additional_fields'] ?? $lead_data['form_data'] ?? null,
        'recording_url' => null,
        'recording_duration' => 0,
        'transcript' => null
    ];
    
    // Try to get call recording if it's a phone lead
    if (isset($lead_data['lead_type']) && 
        (stripos($lead_data['lead_type'], 'phone') !== false || 
         stripos($lead_data['lead_type'], 'call') !== false)) {
        
        error_log('Fetching recording for phone lead: ' . $lead_id); // Debug log
        
        // Check if recording URL is already in the lead data
        if (!empty($lead_data['recording_url'])) {
            $lead['recording_url'] = $lead_data['recording_url'];
            $lead['recording_duration'] = $lead_data['recording_duration'] ?? $lead_data['duration_in_seconds'] ?? 0;
        } else {
            // Try to fetch recording separately
            $recording_url = "https://app.whatconverts.com/api/v1/leads/$lead_id/recording";
            
            $recording_response = wp_remote_get($recording_url, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($api_token . ':' . $api_secret),
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 30
            ]);
            
            if (!is_wp_error($recording_response) && wp_remote_retrieve_response_code($recording_response) === 200) {
                $recording_data = json_decode(wp_remote_retrieve_body($recording_response), true);
                
                if (isset($recording_data['recording_url'])) {
                    $lead['recording_url'] = $recording_data['recording_url'];
                    $lead['recording_duration'] = $recording_data['duration_in_seconds'] ?? 0;
                    error_log('Recording found: ' . $lead['recording_url']); // Debug log
                }
            } else {
                error_log('No recording available for lead: ' . $lead_id);
            }
        }
        
        // Try to get transcript if available
        if (!empty($lead_data['transcript'])) {
            $lead['transcript'] = $lead_data['transcript'];
        }
    }
    
    error_log('Sending lead details: ' . json_encode($lead)); // Debug log
    
    wp_send_json_success($lead);
}

// Add a function to fetch dynamic filters data
add_action('wp_ajax_fetch_lead_filters', 'fetch_lead_filters');
add_action('wp_ajax_nopriv_fetch_lead_filters', 'fetch_lead_filters');

function fetch_lead_filters() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Unauthorized', 401);
    }
    
    $user_id = get_current_user_id();
    $account_id = get_field('lead_account', 'user_' . $user_id);
    
    if (!$account_id) {
        wp_send_json_error('No account selected', 400);
    }
    
    $api_token = get_option('whatconverts_api_token');
    $api_secret = get_option('whatconverts_api_secret');
    
    if (!$api_token || !$api_secret) {
        wp_send_json_error('API credentials not configured', 500);
    }
    
    // Check if we have cached filter values
    $cached_filters = get_transient('whatconverts_filter_values_' . $user_id);
    if ($cached_filters) {
        wp_send_json_success($cached_filters);
        return;
    }
    
    // Fetch a larger sample of leads to extract unique values
    $url = 'https://app.whatconverts.com/api/v1/leads?' . http_build_query([
        'account_id' => $account_id,
        'page_number' => 1,
        'leads_per_page' => 200, // Get more leads for better filter coverage
        'start_date' => date('Y-m-d', strtotime('-90 days')),
        'end_date' => date('Y-m-d')
    ]);
    
    error_log('Fetching filters from: ' . $url); // Debug log
    
    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($api_token . ':' . $api_secret),
            'Content-Type' => 'application/json'
        ],
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        error_log('WhatConverts filter API error: ' . $response->get_error_message());
        wp_send_json_error('Failed to fetch filter data', 500);
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $leads = $body['leads'] ?? [];
    
    // Extract unique values
    $sources = [];
    $mediums = [];
    $types = [];
    $statuses = [];
    
    foreach ($leads as $lead) {
        if (!empty($lead['lead_source']) && !in_array($lead['lead_source'], $sources)) {
            $sources[] = $lead['lead_source'];
        }
        if (!empty($lead['lead_medium']) && !in_array($lead['lead_medium'], $mediums)) {
            $mediums[] = $lead['lead_medium'];
        }
        if (!empty($lead['lead_type']) && !in_array($lead['lead_type'], $types)) {
            $types[] = $lead['lead_type'];
        }
        if (!empty($lead['lead_status']) && !in_array($lead['lead_status'], $statuses)) {
            $statuses[] = $lead['lead_status'];
        }
    }
    
    sort($sources);
    sort($mediums);
    sort($types);
    sort($statuses);
    
    $filter_data = [
        'sources' => $sources,
        'mediums' => $mediums,
        'types' => $types,
        'statuses' => $statuses
    ];
    
    // Cache for 1 hour
    set_transient('whatconverts_filter_values_' . $user_id, $filter_data, 3600);
    
    error_log('Filter data found: ' . json_encode($filter_data)); // Debug log
    
    wp_send_json_success($filter_data);
}


