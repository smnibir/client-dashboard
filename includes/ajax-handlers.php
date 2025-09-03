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

// WhatConverts Functions
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
    
    error_log('WhatConverts API URL: ' . $url);
    
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
            'email' => $lead['email_address'] ?? $lead['email'] ?? '-',
            'phone_number' => $lead['phone_number'] ?? $lead['phone'] ?? '-',
            'lead_status' => ucwords($lead['lead_status'] ?? 'new'),
            'source' => $lead['lead_source'] ?? '-',
            'ip_address' => $lead['ip_address'] ?? '-',
            'medium' => $lead['lead_medium'] ?? '-',
            'campaign' => $lead['lead_campaign'] ?? '-',
            'lead_summary' => $lead['lead_summary'] ?? $lead['lead_notes'] ?? '-',
            'created_at' => $lead['date_created'] ?? date('Y-m-d H:i:s'),
            'message' => $lead['message'] ?? '-',
            'call_duration' => $lead['call_duration'] ?? '-'
        ];
    }, $body['leads'] ?? []);
    
    // Store filter values in transient for filter endpoint
    set_transient('whatconverts_filter_values_' . $user_id, [
        'sources' => $all_sources,
        'statuses' => $all_statuses,
        'types' => $all_types
    ], 3600);
    
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

// AJAX handler to fetch lead details - WITH FIXED RECORDING DETECTION
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
    
    // Get user's account ID
    $user_id = get_current_user_id();
    $account_id = get_field('lead_account', 'user_' . $user_id);
    
    if (!$account_id) {
        error_log('No account ID found for user: ' . $user_id);
        wp_send_json_error('No account selected', 400);
    }
    
    // Try both endpoint formats
    $urls_to_try = [
        "https://app.whatconverts.com/api/v1/leads?lead_id=$lead_id&account_id=$account_id",
        "https://app.whatconverts.com/api/v1/leads/$lead_id"
    ];
    
    $lead_data = null;
    $successful_url = '';
    
    foreach ($urls_to_try as $url) {
        error_log('Trying URL: ' . $url);
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($api_token . ':' . $api_secret),
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            error_log('API Error for URL ' . $url . ': ' . $response->get_error_message());
            continue;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('Response Code for ' . $url . ': ' . $response_code);
        
        if ($response_code === 200) {
            $decoded_data = json_decode($body, true);
            if ($decoded_data && !empty($decoded_data)) {
                $lead_data = $decoded_data;
                $successful_url = $url;
                error_log('SUCCESS with URL: ' . $url);
                error_log('Raw API Response (first 2000 chars): ' . substr($body, 0, 2000));
                break;
            }
        }
    }
    
    if (!$lead_data) {
        wp_send_json_error('Failed to fetch lead details from any endpoint', 500);
    }
    
    // Find the actual lead data - it might be nested or in an array
    $actual_lead_data = null;
    
    // Log the structure to understand it better
    error_log('Response structure type: ' . gettype($lead_data));
    if (is_array($lead_data)) {
        error_log('Top-level keys: ' . implode(', ', array_keys($lead_data)));
    }
    
    // Check various possible structures
    if (isset($lead_data['leads']) && is_array($lead_data['leads']) && !empty($lead_data['leads'])) {
        // Response has 'leads' array - take the first matching lead
        foreach ($lead_data['leads'] as $lead) {
            if (isset($lead['lead_id']) && $lead['lead_id'] == $lead_id) {
                $actual_lead_data = $lead;
                error_log('Lead found in "leads" array by ID match');
                break;
            }
        }
        // If no exact match, take the first one
        if (!$actual_lead_data && !empty($lead_data['leads'])) {
            $actual_lead_data = $lead_data['leads'][0];
            error_log('Taking first lead from "leads" array');
        }
    }
    // Check if it's directly the lead data
    elseif (isset($lead_data['lead_id']) || isset($lead_data['id'])) {
        $actual_lead_data = $lead_data;
        error_log('Lead data is at root level');
    }
    // Check if it's nested in 'lead' key
    elseif (isset($lead_data['lead']) && is_array($lead_data['lead'])) {
        $actual_lead_data = $lead_data['lead'];
        error_log('Lead data found in "lead" key');
    }
    // Check if it's nested in 'data' key
    elseif (isset($lead_data['data']) && is_array($lead_data['data'])) {
        $actual_lead_data = $lead_data['data'];
        error_log('Lead data found in "data" key');
    }
    // Check if the response itself is an array of leads
    elseif (isset($lead_data[0]) && is_array($lead_data[0])) {
        $actual_lead_data = $lead_data[0];
        error_log('Lead data is first element of array');
    }
    
    if (!$actual_lead_data) {
        error_log('ERROR: Could not find lead data in response');
        error_log('Full structure (first 1000 chars): ' . substr(json_encode($lead_data), 0, 1000));
        wp_send_json_error('Invalid response structure', 500);
    }
    
    // Log all available fields for debugging
    error_log('Available lead fields: ' . implode(', ', array_keys($actual_lead_data)));
    
    // Log sample values for debugging
    $sample_fields = ['lead_id', 'quotable_name', 'email_address', 'phone_number', 'lead_type'];
    foreach ($sample_fields as $field) {
        if (isset($actual_lead_data[$field])) {
            $value = is_string($actual_lead_data[$field]) ? $actual_lead_data[$field] : json_encode($actual_lead_data[$field]);
            error_log("Field '$field': " . substr($value, 0, 100));
        }
    }
    
    // Build the lead response with direct field access
    $lead = [
        'lead_id' => $actual_lead_data['lead_id'] ?? $lead_id,
        'contactDisplay' => $actual_lead_data['quotable_name'] ?? 
                           $actual_lead_data['name'] ?? 
                           $actual_lead_data['contact_name'] ?? 
                           'No contact information',
        'name' => $actual_lead_data['quotable_name'] ?? 
                  $actual_lead_data['name'] ?? 
                  'No name available',
        'email' => $actual_lead_data['email_address'] ?? 
                   $actual_lead_data['email'] ?? 
                   'No email available',
        'phone_number' => $actual_lead_data['phone_number'] ?? 
                          $actual_lead_data['phone'] ?? 
                          'No phone available',
        'lead_type' => $actual_lead_data['lead_type'] ?? 
                       $actual_lead_data['type'] ?? 
                       'Unknown type',
        'source' => $actual_lead_data['lead_source'] ?? 
                    $actual_lead_data['source'] ?? 
                    'Unknown source',
        'campaign' => $actual_lead_data['lead_campaign'] ?? 
                      $actual_lead_data['campaign'] ?? 
                      'No campaign',
        'keyword' => $actual_lead_data['lead_keyword'] ?? 
                     $actual_lead_data['keyword'] ?? 
                     'No keyword',
        'landing_page' => $actual_lead_data['landing_url'] ?? 
                          $actual_lead_data['landing_page'] ?? 
                          'No landing page',
        'referring_url' => $actual_lead_data['referring_url'] ?? 
                           $actual_lead_data['referrer'] ?? 
                           'No referring URL',
        'ip_address' => $actual_lead_data['ip_address'] ?? 
                        'No IP address',
        'message' => $actual_lead_data['message'] ?? 
                     $actual_lead_data['notes'] ?? 
                     $actual_lead_data['content'] ?? 
                     'No message',
        'city' => $actual_lead_data['city'] ?? 
                  $actual_lead_data['geo_city'] ?? 
                  'Unknown city',
        'state' => $actual_lead_data['state'] ?? 
                   $actual_lead_data['geo_state'] ?? 
                   $actual_lead_data['state_or_province'] ?? 
                   'Unknown state',
        'country' => $actual_lead_data['country'] ?? 
                     $actual_lead_data['geo_country'] ?? 
                     'Unknown country',
        'lead_value' => $actual_lead_data['lead_value'] ?? 
                        $actual_lead_data['value'] ?? 
                        'No value',
        'date_created' => $actual_lead_data['date_created'] ?? 
                          $actual_lead_data['created_at'] ?? 
                          'Unknown date',
        'form_data' => $actual_lead_data['additional_fields'] ?? 
                       $actual_lead_data['form_data'] ?? 
                       $actual_lead_data['custom_fields'] ?? 
                       null,
        'has_recording' => false,
        'recording_lead_id' => null,
        'recording_duration' => 0,
        'transcript' => $actual_lead_data['transcript'] ?? 
                        $actual_lead_data['call_transcript'] ?? 
                        null
    ];
    
    // ===== FIXED RECORDING DETECTION =====
    // Check if this is a phone lead
    $lead_type = $actual_lead_data['lead_type'] ?? '';
    $is_phone_lead = (
        stripos($lead_type, 'phone') !== false || 
        stripos($lead_type, 'call') !== false ||
        stripos($lead_type, 'voice') !== false ||
        stripos($lead_type, 'telephone') !== false
    );

    // For phone leads, ALWAYS assume recording exists
    // Since the API endpoint directly streams the audio, we let the player handle errors
    if ($is_phone_lead) {
        error_log('Phone lead detected - setting recording as available for lead: ' . $lead_id);
        
        // Always set recording as available for phone calls
        $lead['has_recording'] = true;
        $lead['recording_lead_id'] = $lead_id;
        
        // Get duration from lead data if available
        $duration = $actual_lead_data['call_duration'] ?? 
                   $actual_lead_data['duration'] ?? 
                   $actual_lead_data['recording_duration'] ?? 
                   $actual_lead_data['duration_in_seconds'] ?? 0;
        
        // Convert MM:SS format to seconds if needed
        if (is_string($duration) && strpos($duration, ':') !== false) {
            $parts = explode(':', $duration);
            $duration = (int)$parts[0] * 60 + (int)($parts[1] ?? 0);
        }
        
        $lead['recording_duration'] = (int)$duration;
        error_log('Recording set as available for phone lead ' . $lead_id . ' (duration: ' . $duration . ')');
    }
    
    // Log the final lead data being sent
    error_log('=== FINAL LEAD DATA ===');
    error_log('Name: ' . $lead['name']);
    error_log('Email: ' . $lead['email']);
    error_log('Phone: ' . $lead['phone_number']);
    error_log('Type: ' . $lead['lead_type']);
    error_log('Has Recording: ' . ($lead['has_recording'] ? 'Yes' : 'No'));
    
    wp_send_json_success($lead);
}

// AJAX handler to stream/proxy recordings - WITH BETTER ERROR HANDLING
add_action('wp_ajax_stream_recording', 'stream_recording');
add_action('wp_ajax_nopriv_stream_recording', 'stream_recording');

function stream_recording() {
    if (!is_user_logged_in()) {
        http_response_code(401);
        wp_die('Unauthorized', 401);
    }
    
    $lead_id = isset($_GET['lead_id']) ? intval($_GET['lead_id']) : 0;
    $is_download = isset($_GET['download']) && $_GET['download'] == '1';
    
    if (!$lead_id) {
        http_response_code(400);
        wp_die('Invalid lead ID', 400);
    }
    
    $api_token = get_option('whatconverts_api_token');
    $api_secret = get_option('whatconverts_api_secret');
    
    if (!$api_token || !$api_secret) {
        http_response_code(500);
        wp_die('API credentials not configured', 500);
    }
    
    // The recording endpoint
    $recording_endpoint = "https://app.whatconverts.com/api/v1/recording?lead_id=" . $lead_id;
    
    error_log('Attempting to stream recording from: ' . $recording_endpoint);
    
    // Fetch the audio with proper headers
    $response = wp_remote_get($recording_endpoint, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($api_token . ':' . $api_secret),
            'Accept' => 'audio/mpeg, audio/wav, audio/mp3, audio/*'
        ],
        'timeout' => 60,
        'stream' => false,
        'decompress' => false
    ]);
    
    if (is_wp_error($response)) {
        error_log('Error fetching recording: ' . $response->get_error_message());
        http_response_code(500);
        wp_die('Error fetching recording: ' . $response->get_error_message(), 500);
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    
    if ($response_code !== 200) {
        error_log('Recording fetch failed with code: ' . $response_code);
        $body = wp_remote_retrieve_body($response);
        error_log('Error response body: ' . substr($body, 0, 500));
        
        // Return 404 for missing recordings
        if ($response_code === 404) {
            http_response_code(404);
            wp_die('Recording not found', 404);
        }
        
        http_response_code($response_code);
        wp_die('Recording not available', $response_code);
    }
    
    // Get the audio data
    $audio_data = wp_remote_retrieve_body($response);
    $content_type = wp_remote_retrieve_header($response, 'content-type');
    
    // Default to audio/mpeg if no content type
    if (!$content_type) {
        $content_type = 'audio/mpeg';
    }
    
    error_log('Recording retrieved successfully. Content-Type: ' . $content_type . ', Size: ' . strlen($audio_data) . ' bytes');
    
    // Clean any previous output
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set appropriate headers
    header('Content-Type: ' . $content_type);
    header('Content-Length: ' . strlen($audio_data));
    header('Accept-Ranges: bytes');
    
    if ($is_download) {
        // Force download
        header('Content-Disposition: attachment; filename="recording-' . $lead_id . '.mp3"');
        header('Cache-Control: no-cache');
    } else {
        // Allow caching for streaming
        header('Cache-Control: public, max-age=3600');
        header('Content-Disposition: inline');
    }
    
    // Output the audio data
    echo $audio_data;
    exit;
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
        'leads_per_page' => 200,
        'start_date' => date('Y-m-d', strtotime('-90 days')),
        'end_date' => date('Y-m-d')
    ]);
    
    error_log('Fetching filters from: ' . $url);
    
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
    
    error_log('Filter data found: ' . json_encode($filter_data));
    
    wp_send_json_success($filter_data);
}