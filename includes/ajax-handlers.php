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

// AJAX handler to fetch leads
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
    
    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($api_token . ':' . $api_secret),
            'Content-Type' => 'application/json'
        ]
    ]);
    
    if (is_wp_error($response)) {
        error_log('WhatConverts leads API error: ' . $response->get_error_message());
        wp_send_json_error('Failed to fetch leads', 500);
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($response_code !== 200) {
        error_log('WhatConverts API error: ' . print_r($body, true));
        wp_send_json_error('API error: ' . ($body['message'] ?? 'Unknown error'), $response_code);
    }
    
    $leads = array_map(function($lead) {
        return [
            'lead_id' => $lead['lead_id'],
            'lead_type' => $lead['lead_type'],
            'email' => $lead['email'] ?? '-',
            'phone_number' => $lead['phone_number'] ?? '-',
            'lead_status' => ucwords($lead['lead_status'] ?? 'new'),
            'source' => $lead['lead_source'] ?? '-',
            'medium' => $lead['lead_medium'] ?? '-',
            'campaign' => $lead['lead_campaign'] ?? '-',
            'lead_summary' => $lead['lead_summary'] ?? '-',
            'created_at' => $lead['date_created']
        ];
    }, $body['leads'] ?? []);
    
    wp_send_json_success([
        'leads' => $leads,
        'total_pages' => $body['total_pages'] ?? 1,
        'total_leads' => $body['total_leads'] ?? 0
    ]);
}

// AJAX handler to fetch single lead details with recording
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
    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($api_token . ':' . $api_secret),
            'Content-Type' => 'application/json'
        ]
    ]);
    
    if (is_wp_error($response)) {
        error_log('WhatConverts lead details API error: ' . $response->get_error_message());
        wp_send_json_error('Failed to fetch lead details', 500);
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($response_code !== 200) {
        error_log('WhatConverts lead details API error: ' . print_r($body, true));
        wp_send_json_error('API error: ' . ($body['message'] ?? 'Unknown error'), $response_code);
    }
    
    $lead = [
        'name' => $body['name'] ?? 'N/A',
        'email' => $body['email'] ?? 'N/A',
        'phone_number' => $body['phone_number'] ?? 'N/A',
        'lead_type' => $body['lead_type'] ?? 'N/A',
        'source' => $body['lead_source'] ?? 'N/A',
        'medium' => $body['lead_medium'] ?? 'N/A',
        'campaign' => $body['lead_campaign'] ?? 'N/A',
        'landing_page' => $body['landing_page'] ?? 'N/A',
        'ip_address' => $body['ip_address'] ?? 'N/A',
        'lead_summary' => $body['lead_summary'] ?? 'No summary available',
        'form_data' => $body['form_data'] ?? null,
        'recording_url' => null,
        'recording_duration' => 0
    ];
    
    // Fetch recording for phone leads
    if (isset($body['lead_type']) && strpos(strtolower($body['lead_type']), 'phone') !== false) {
        $recording_url = "https://app.whatconverts.com/api/v1/recordings/$lead_id";
        $recording_response = wp_remote_get($recording_url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($api_token . ':' . $api_secret),
                'Content-Type' => 'application/json'
            ]
        ]);
        
        if (!is_wp_error($recording_response) && wp_remote_retrieve_response_code($recording_response) === 200) {
            $recording_body = json_decode(wp_remote_retrieve_body($recording_response), true);
            $lead['recording_url'] = $recording_body['recording_url'] ?? null;
            $lead['recording_duration'] = $recording_body['duration'] ?? 0;
        }
    }
    
    wp_send_json_success($lead);
}