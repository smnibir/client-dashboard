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

// AJAX handler to fetch lead details - ENHANCED VERSION WITH FIXED RECORDING ENDPOINT
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
    
    // Try different URL variations for the WhatConverts API
    $urls_to_try = [

        "https://app.whatconverts.com/api/v1/leads/$lead_id"
    ];
    
    $lead_data = null;
    $successful_url = '';
    
    foreach ($urls_to_try as $url) {
        error_log('=== Trying URL: ' . $url . ' ===');
        
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
                break;
            }
        } else {
            error_log('Error response from ' . $url . ': ' . substr($body, 0, 200));
        }
    }
    
    if (!$lead_data) {
        wp_send_json_error('Failed to fetch lead details from any endpoint', 500);
    }
    
    error_log('=== DETAILED API RESPONSE ANALYSIS ===');
    error_log('Successful URL: ' . $successful_url);
    error_log('Top-level keys: ' . implode(', ', array_keys($lead_data)));
    
    // Find the actual lead data (it might be nested)
    $actual_lead_data = $lead_data;
    
    // Check all possible nesting structures
    $possible_containers = ['lead', 'data', 'result', 'leads', 'contact', 'record'];
    foreach ($possible_containers as $container) {
        if (isset($lead_data[$container]) && is_array($lead_data[$container])) {
            error_log('Found data in container: ' . $container);
            $actual_lead_data = $lead_data[$container];
            break;
        }
    }
    
    // If leads is an array, get the first one
    if (isset($actual_lead_data[0]) && is_array($actual_lead_data[0])) {
        error_log('Data appears to be an array, taking first element');
        $actual_lead_data = $actual_lead_data[0];
    }
    
    error_log('Final lead data keys: ' . implode(', ', array_keys($actual_lead_data)));
    
    // Comprehensive field mapping function
    function findFieldValue($data, $possible_fields) {
        foreach ($possible_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field]) && $data[$field] !== '' && $data[$field] !== null) {
                error_log('Found value for field group in: ' . $field . ' = ' . $data[$field]);
                return $data[$field];
            }
        }
        return null;
    }
    
    // Comprehensive field mappings
    $name_fields = [
        'quotable_name', 'name', 'contact_name', 'full_name', 'customer_name', 
        'lead_name', 'client_name', 'first_name', 'caller_name'
    ];
    
    $email_fields = [
        'email_address', 'email', 'contact_email', 'lead_email', 'customer_email', 'caller_email'
    ];
    
    $phone_fields = [
        'phone_number', 'phone', 'contact_phone', 'lead_phone', 'customer_phone', 
        'phone_no', 'caller_phone', 'phone_num', 'telephone'
    ];
    
    $type_fields = [
        'lead_type', 'type', 'contact_type', 'conversion_type', 'call_type'
    ];
    
    $source_fields = [
        'lead_source', 'source', 'traffic_source', 'utm_source', 'referrer_source'
    ];
    
    $campaign_fields = [
        'lead_campaign', 'campaign', 'campaign_name', 'utm_campaign'
    ];
    
    $landing_fields = [
        'landing_url', 'landing_page_url', 'landing_page', 'page_url', 'url'
    ];
    
    $message_fields = [
        'message', 'notes', 'content', 'description', 'details', 'comment'
    ];
    
    $date_fields = [
        'date_created', 'created_at', 'timestamp', 'date', 'time_created'
    ];
    
    // Extract values using the mapping
    $name = findFieldValue($actual_lead_data, $name_fields);
    $email = findFieldValue($actual_lead_data, $email_fields);
    $phone = findFieldValue($actual_lead_data, $phone_fields);
    $lead_type = findFieldValue($actual_lead_data, $type_fields);
    $source = findFieldValue($actual_lead_data, $source_fields);
    $campaign = findFieldValue($actual_lead_data, $campaign_fields);
    $landing_page = findFieldValue($actual_lead_data, $landing_fields);
    $message = findFieldValue($actual_lead_data, $message_fields);
    $date_created = findFieldValue($actual_lead_data, $date_fields);
    
    // Create contact display
    $contact_parts = array_filter([$name]);
    $contact_display = !empty($contact_parts) ? implode(' | ', $contact_parts) : 'No contact information';
    
    // Build the final lead array
    $lead = [
        'lead_id' => $actual_lead_data['lead_id'] ?? $actual_lead_data['id'] ?? $lead_id,
        'contactDisplay' => $contact_display,
        'name' => $name ?: 'No name available',
        'email' => $email ?: 'No email available',
        'phone_number' => $phone ?: 'No phone available',
        'lead_type' => $lead_type ?: 'Unknown type',
        'source' => $source ?: 'Unknown source',
        'campaign' => $campaign ?: 'No campaign',
        'keyword' => $actual_lead_data['lead_keyword'] ?? $actual_lead_data['keyword'] ?? $actual_lead_data['utm_term'] ?? 'No keyword',
        'landing_page' => $landing_page ?: 'No landing page',
        'referring_url' => $actual_lead_data['referring_url'] ?? $actual_lead_data['referrer'] ?? $actual_lead_data['utm_referrer'] ?? 'No referring URL',
        'ip_address' => $actual_lead_data['ip_address'] ?? $actual_lead_data['ip'] ?? 'No IP address',
        'message' => $message ?: 'No message',
        'city' => $actual_lead_data['city'] ?? 'Unknown city',
        'state' => $actual_lead_data['state'] ?? $actual_lead_data['state_or_province'] ?? $actual_lead_data['region'] ?? 'Unknown state',
        'country' => $actual_lead_data['country'] ?? $actual_lead_data['country_code'] ?? 'Unknown country',
        'lead_summary' => $actual_lead_data['lead_notes'] ?? $actual_lead_data['lead_summary'] ?? $actual_lead_data['summary'] ?? $message ?? 'No summary available',
        'lead_value' => $actual_lead_data['lead_value'] ?? $actual_lead_data['value'] ?? $actual_lead_data['revenue'] ?? 'No value',
        'date_created' => $date_created ?: 'Unknown date',
        'form_data' => $actual_lead_data['additional_fields'] ?? $actual_lead_data['form_data'] ?? $actual_lead_data['custom_fields'] ?? $actual_lead_data['extra_data'] ?? null,
        'recording_url' => null,
        'recording_duration' => 0,
        'transcript' => null
    ];
    
    // FIXED: Enhanced recording detection using the correct endpoint
    $is_phone_lead = ($lead_type && (
        stripos($lead_type, 'phone') !== false || 
        stripos($lead_type, 'call') !== false ||
        stripos($lead_type, 'voice') !== false ||
        stripos($lead_type, 'telephone') !== false
    ));

    if ($is_phone_lead || !empty($actual_lead_data['recording_url']) || !empty($actual_lead_data['call_recording'])) {
        error_log('=== CHECKING FOR RECORDING (Phone lead or recording data found) ===');
        
        // First check if recording URL is already in the lead data
        $recording_url_fields = [
            'play_recording', 'recording', 'recording_url', 'call_recording', 'audio_url', 
            'recording_file', 'call_audio', 'phone_recording', 'audio_file'
        ];
        
        foreach ($recording_url_fields as $field) {
            if (!empty($actual_lead_data[$field])) {
                $lead['recording_url'] = $actual_lead_data[$field];
                // Get duration from multiple possible sources
                $duration = $actual_lead_data['recording_duration'] ?? 
                           $actual_lead_data['duration_in_seconds'] ?? 
                           $actual_lead_data['call_duration'] ?? 
                           $actual_lead_data['duration'] ?? 0;
                
                // Convert duration to seconds if it's in other formats
                if (is_string($duration) && strpos($duration, ':') !== false) {
                    // Convert MM:SS to seconds
                    $parts = explode(':', $duration);
                    $duration = (int)$parts[0] * 60 + (int)$parts[1];
                }
                
                $lead['recording_duration'] = (int)$duration;
                error_log('Recording found in lead data field: ' . $field . ' = ' . $lead['recording_url'] . ' (duration: ' . $duration . ' seconds)');
                break;
            }
        }
        
        // FIXED: If no recording URL found, try the correct recording endpoint
        if (!$lead['recording_url']) {
            error_log('=== TRYING RECORDING ENDPOINT ===');
            
            // The working endpoint format: https://app.whatconverts.com/api/v1/recording?lead_id={lead_id}
            $recording_endpoint = "https://app.whatconverts.com/api/v1/recording?lead_id=" . $lead_id;
            
            error_log('Trying recording endpoint: ' . $recording_endpoint);
            
            $recording_response = wp_remote_get($recording_endpoint, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($api_token . ':' . $api_secret),
                    'Accept' => 'audio/mpeg, application/json'
                ],
                'timeout' => 30
            ]);
            
            if (!is_wp_error($recording_response)) {
                $recording_code = wp_remote_retrieve_response_code($recording_response);
                $content_type = wp_remote_retrieve_header($recording_response, 'content-type');
                
                error_log('Recording endpoint response code: ' . $recording_code);
                error_log('Recording content type: ' . $content_type);
                
                if ($recording_code === 200) {
                    // Check if it's audio content
                    if (strpos($content_type, 'audio') !== false) {
                        // It's an audio file, set the URL directly
                        $lead['recording_url'] = $recording_endpoint;
                        error_log('Recording URL set to: ' . $recording_endpoint);
                        
                        // Try to get duration from headers or make a separate call
                        $duration_header = wp_remote_retrieve_header($recording_response, 'x-duration');
                        if ($duration_header) {
                            $lead['recording_duration'] = (int)$duration_header;
                        } else {
                            // Try to get duration from the lead data if available
                            $duration = $actual_lead_data['call_duration'] ?? 0;
                            if (is_string($duration) && strpos($duration, ':') !== false) {
                                $parts = explode(':', $duration);
                                $duration = (int)$parts[0] * 60 + (int)$parts[1];
                            }
                            $lead['recording_duration'] = (int)$duration;
                        }
                        
                    } else {
                        // It might be JSON with recording info
                        $recording_body = wp_remote_retrieve_body($recording_response);
                        $recording_data = json_decode($recording_body, true);
                        
                        if ($recording_data && is_array($recording_data)) {
                            error_log('Recording response: ' . json_encode($recording_data));
                            
                            // Check various possible response formats
                            $recording_url = null;
                            if (!empty($recording_data['recording_url'])) {
                                $recording_url = $recording_data['recording_url'];
                            } elseif (!empty($recording_data['url'])) {
                                $recording_url = $recording_data['url'];
                            } elseif (!empty($recording_data['audio_url'])) {
                                $recording_url = $recording_data['audio_url'];
                            } elseif (!empty($recording_data['file_url'])) {
                                $recording_url = $recording_data['file_url'];
                            }
                            
                            if ($recording_url) {
                                $lead['recording_url'] = $recording_url;
                                $lead['recording_duration'] = $recording_data['duration_in_seconds'] ?? 
                                                            $recording_data['duration'] ?? 
                                                            $recording_data['call_duration'] ?? 0;
                                error_log('Recording found via JSON response: ' . $recording_url);
                            }
                        }
                    }
                } else {
                    error_log('Recording endpoint returned code: ' . $recording_code);
                }
            } else {
                error_log('Recording endpoint error: ' . $recording_response->get_error_message());
            }
        }
    }

    // Enhanced transcript detection
    if (!$lead['transcript']) {
        $transcript_fields = [
            'transcript', 'call_transcript', 'transcription', 'speech_to_text', 'converted_text'
        ];
        
        foreach ($transcript_fields as $field) {
            if (!empty($actual_lead_data[$field])) {
                $lead['transcript'] = $actual_lead_data[$field];
                error_log('Transcript found in field: ' . $field);
                break;
            }
        }
    }
    
    error_log('=== FINAL PROCESSED LEAD DATA ===');
    error_log(json_encode($lead, JSON_PRETTY_PRINT));
    
    wp_send_json_success($lead);
}

// NEW: AJAX handler to stream/proxy the recording
add_action('wp_ajax_stream_recording', 'stream_recording');
add_action('wp_ajax_nopriv_stream_recording', 'stream_recording');

function stream_recording() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Unauthorized', 401);
    }
    
    $lead_id = isset($_GET['lead_id']) ? intval($_GET['lead_id']) : 0;
    
    if (!$lead_id) {
        wp_send_json_error('Invalid lead ID', 400);
    }
    
    $api_token = get_option('whatconverts_api_token');
    $api_secret = get_option('whatconverts_api_secret');
    
    if (!$api_token || !$api_secret) {
        wp_send_json_error('API credentials not configured', 500);
    }
    
    // The recording endpoint
    $recording_endpoint = "https://app.whatconverts.com/api/v1/recording?lead_id=" . $lead_id;
    
    // Stream the audio
    $response = wp_remote_get($recording_endpoint, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($api_token . ':' . $api_secret),
            'Accept' => 'audio/mpeg'
        ],
        'timeout' => 60
    ]);
    
    if (is_wp_error($response)) {
        wp_die('Error fetching recording: ' . $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        wp_die('Recording not found or access denied');
    }
    
    // Get the audio data
    $audio_data = wp_remote_retrieve_body($response);
    $content_type = wp_remote_retrieve_header($response, 'content-type') ?: 'audio/mpeg';
    
    // Set headers for audio streaming
    header('Content-Type: ' . $content_type);
    header('Content-Length: ' . strlen($audio_data));
    header('Cache-Control: public, max-age=3600');
    header('Accept-Ranges: bytes');
    
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