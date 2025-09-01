<?php
/**
 * Gravity Forms → ClickUp integration
 * - Creates a ClickUp task after a GF submission.
 * - Uses encryption for API keys (requires CLICKUP_ENCRYPTION_KEY in wp-config.php).
 * - Enhanced error handling and debugging.
 *
 * Place this file in your plugin and require_once it from the main plugin file.
 */

if ( ! defined('ABSPATH') ) {
    exit;
}

if ( ! class_exists('GF_ClickUp_Integration') ) {

    class GF_ClickUp_Integration {

        /**
         * Option names used by your settings screen.
         */
        const OPT_API_KEY   = 'clickup_api_key';
        const OPT_LIST_ID   = 'clickup_default_list_id';
        const OPT_DEFAULT_ASSIGNEE = 'clickup_default_assignee_id'; // New option for Fiona's ID
        const FALLBACK_LIST = '901103489415';

        /**
         * Encryption key - should be defined in wp-config.php
         */
        private $encryption_key;

        public function __construct() {
            // Initialize encryption key
            $this->encryption_key = defined('CLICKUP_ENCRYPTION_KEY') ? CLICKUP_ENCRYPTION_KEY : '';
            
            // Validate encryption key length
            if ($this->encryption_key && strlen($this->encryption_key) !== 32) {
                $this->log('[ERROR] CLICKUP_ENCRYPTION_KEY must be exactly 32 characters long. Current length: ' . strlen($this->encryption_key));
                return;
            }

            // Use wp_loaded hook to avoid blocking form submission
            add_action('wp_loaded', array($this, 'init_hooks'));

            $this->log('[DEBUG] GF-ClickUp class constructed successfully (Fiona ID: 75445443, Portal User Field: 10)');
        }

        /**
         * Initialize hooks after WordPress is fully loaded
         */
        public function init_hooks() {
            // Hook to process after form submission (non-blocking)
            add_action('gform_after_submission', array($this, 'schedule_task_creation'), 5, 2);
            
            // Hook to actually create the task (runs after response is sent)
            add_action('wp_footer', array($this, 'process_scheduled_tasks'));
            add_action('wp_ajax_nopriv_process_clickup_tasks', array($this, 'process_scheduled_tasks'));
            add_action('wp_ajax_process_clickup_tasks', array($this, 'process_scheduled_tasks'));
        }

        /**
         * Schedule task creation instead of blocking form submission
         *
         * @param array $entry Gravity Forms entry array
         * @param array $form  Gravity Forms form meta array
         * @return void
         */
        public function schedule_task_creation( $entry, $form ) {
            $this->log('[DEBUG] Scheduling ClickUp task creation for entry ID: ' . $entry['id']);
            
            // Store the submission data for processing
            $task_data = array(
                'entry_id' => $entry['id'],
                'form_id' => $form['id'],
                'entry_data' => $entry,
                'form_data' => $form,
                'timestamp' => time()
            );
            
            // Store in transient for processing
            $scheduled_tasks = get_transient('gf_clickup_scheduled_tasks') ?: array();
            $scheduled_tasks[] = $task_data;
            set_transient('gf_clickup_scheduled_tasks', $scheduled_tasks, HOUR_IN_SECONDS);
            
            // Also trigger immediate processing via AJAX to avoid delay
            wp_schedule_single_event(time(), 'gf_clickup_process_tasks');
        }

        /**
         * Process scheduled tasks without blocking the form response
         */
        public function process_scheduled_tasks() {
            $scheduled_tasks = get_transient('gf_clickup_scheduled_tasks');
            if (!$scheduled_tasks) {
                return;
            }
            
            foreach ($scheduled_tasks as $key => $task_data) {
                $this->handle_submission($task_data['entry_data'], $task_data['form_data']);
                unset($scheduled_tasks[$key]);
            }
            
            // Update transient with remaining tasks
            if (empty($scheduled_tasks)) {
                delete_transient('gf_clickup_scheduled_tasks');
            } else {
                set_transient('gf_clickup_scheduled_tasks', $scheduled_tasks, HOUR_IN_SECONDS);
            }
        }

        /**
         * Handle a GF submission and create a ClickUp task.
         *
         * @param array $entry Gravity Forms entry array
         * @param array $form  Gravity Forms form meta array
         * @return void
         */
        public function handle_submission( $entry, $form ) {
            $this->log('[DEBUG] ===== GF Submission Hook Triggered =====');
            $this->log('[DEBUG] Form ID: ' . ( isset($form['id']) ? $form['id'] : 'n/a' ));
            $this->log('[DEBUG] Entry ID: ' . ( isset($entry['id']) ? $entry['id'] : 'n/a' ));

            // Check if Gravity Forms functions are available
            if ( ! function_exists('rgar') ) {
                $this->log('[ERROR] rgar() function not available. Is Gravity Forms loaded?');
                return;
            }

            // 1) Get API key with proper error handling
            $api_key = $this->get_api_key();
            if ( empty($api_key) ) {
                $this->log('[ERROR] Failed to obtain API key. Check encryption settings and stored key.');
                return;
            }
            $this->log('[DEBUG] API key obtained successfully: ' . substr($api_key, 0, 10) . '...');

            // 2) Map your field IDs (adjust these to match your form)
            $FIELD_NAME        = 1;  // Name field
            $FIELD_EMAIL       = 4;  // Email field  
            $FIELD_TITLE       = 9;  // Brief title field
            $FIELD_DETAILS     = 5;  // Detailed description field
            $FIELD_URGENCY     = 7;  // Urgency/priority field
            $FIELD_ATTACH      = 8;  // File Upload field
            $FIELD_PORTAL_USER = 10; // Portal User Name field (hidden)

            // Extract field values
            $name        = trim( rgar($entry, (string) $FIELD_NAME) );
            $email       = trim( rgar($entry, (string) $FIELD_EMAIL) );
            $title       = trim( rgar($entry, (string) $FIELD_TITLE) );
            $details     = trim( rgar($entry, (string) $FIELD_DETAILS) );
            $urgency     = trim( rgar($entry, (string) $FIELD_URGENCY) );
            $attach      = trim( rgar($entry, (string) $FIELD_ATTACH) );
            $portal_user = trim( rgar($entry, (string) $FIELD_PORTAL_USER) );

            $this->log('[DEBUG] Extracted field values:');
            $this->log('[DEBUG] Name: ' . $name);
            $this->log('[DEBUG] Email: ' . $email);
            $this->log('[DEBUG] Title: ' . $title);
            $this->log('[DEBUG] Details: ' . substr($details, 0, 100) . '...');
            $this->log('[DEBUG] Urgency: ' . $urgency);
            $this->log('[DEBUG] Portal User: ' . $portal_user);
            $this->log('[DEBUG] Attachments raw: ' . $attach);

            // Log all entry data to help with field mapping
            $this->log('[DEBUG] Full entry data for field mapping reference:');
            foreach ($entry as $field_id => $value) {
                if (is_string($value) && !empty($value)) {
                    $this->log('[DEBUG] Field ' . $field_id . ': ' . substr($value, 0, 100) . (strlen($value) > 100 ? '...' : ''));
                }
            }

            // Create fallback title if empty
            if (empty($title)) {
                if (!empty($portal_user)) {
                    $title = 'Support Request from ' . $portal_user . (!empty($name) ? ' (' . $name . ')' : '');
                } else {
                    $title = 'Support Request from ' . (!empty($name) ? $name : 'Website');
                }
                $this->log('[DEBUG] Using fallback title: ' . $title);
            } else {
                // Add portal user to existing title for easier identification
                if (!empty($portal_user)) {
                    $title = $title;
                    $this->log('[DEBUG] Enhanced title with portal user: ' . $title);
                }
            }

            // Convert urgency to ClickUp priority
            $priority = $this->priority_from_label($urgency);
            $this->log('[DEBUG] Priority mapped to: ' . $priority);

            // Build task description
            $description = $this->build_description($name, $email, $urgency, $title, $details, $portal_user);

            // Get default assignee (Fiona Okeeffe)
            $assignees = $this->get_default_assignees($api_key);

            // Prepare task payload
            $payload = array(
                'name'        => $title,
                'description' => $description,
                'priority'    => $priority,
            );
            
            // Add assignees if available
            if (!empty($assignees)) {
                $payload['assignees'] = $assignees;
            }

            // Add portal user as a tag for easier filtering (optional)
            if (!empty($portal_user)) {
                $payload['tags'] = array($portal_user);
                $this->log('[DEBUG] Added portal user tag: ' . $portal_user);
            }

            $this->log('[DEBUG] Task payload: ' . wp_json_encode($payload));

            // 3) Determine list ID
            $list_id = get_option(self::OPT_LIST_ID, self::FALLBACK_LIST);
            $this->log('[DEBUG] Using list ID: ' . $list_id);

            // 4) Create the task
            $task_result = $this->create_clickup_task($list_id, $payload, $api_key);
            
            if (!$task_result) {
                $this->log('[ERROR] Failed to create ClickUp task');
                return;
            }

            $task_id = $task_result['id'];
            $task_url = $task_result['url'] ?? '';

            $this->log('[SUCCESS] Task created with ID: ' . $task_id);
            if ($task_url) {
                $this->log('[DEBUG] Task URL: ' . $task_url);
            }

            // 5) Store metadata back to the entry
            $this->store_task_metadata($entry['id'], $task_id, $task_url, $portal_user);

            // 6) Handle file attachments if present
            if (!empty($attach)) {
                $this->handle_attachments($task_id, $attach, $api_key);
            }

            $this->log('[DEBUG] ===== Process Complete =====');
        }

        /**
         * Get and decrypt the ClickUp API key.
         *
         * @return string The decrypted API key or empty string on failure
         */
        private function get_api_key(): string {
            $this->log('[DEBUG] Retrieving ClickUp API key...');

            $stored_key = get_option(self::OPT_API_KEY);
            if (empty($stored_key)) {
                $this->log('[ERROR] No API key found in database');
                return '';
            }

            // If no encryption key is set, try to use the stored key as-is
            if (empty($this->encryption_key)) {
                $this->log('[WARNING] No encryption key set. Using stored key as plaintext.');
                return $stored_key;
            }

            // Try to decrypt the stored key
            if (!function_exists('decrypt_value')) {
                $this->log('[ERROR] decrypt_value() function not available');
                return $stored_key; // Fallback to plaintext
            }

            try {
                $decrypted_key = decrypt_value($stored_key, $this->encryption_key);
                
                if (empty($decrypted_key)) {
                    $this->log('[WARNING] Decryption returned empty value. Trying plaintext fallback.');
                    return $stored_key;
                }

                $this->log('[DEBUG] API key decrypted successfully');
                return $decrypted_key;

            } catch (Exception $e) {
                $this->log('[ERROR] Decryption failed: ' . $e->getMessage());
                return $stored_key; // Fallback to plaintext
            }
        }

        /**
         * Create a ClickUp task via API
         *
         * @param string $list_id The ClickUp list ID
         * @param array $payload The task data
         * @param string $api_key The API key
         * @return array|false Task data on success, false on failure
         */
        private function create_clickup_task($list_id, $payload, $api_key) {
            $url = 'https://api.clickup.com/api/v2/list/' . rawurlencode($list_id) . '/task';
            
            $this->log('[DEBUG] Making API request to: ' . $url);
            
            $response = wp_remote_post($url, array(
                'headers' => array(
                    'Authorization' => $api_key, // ClickUp expects just the token
                    'Content-Type'  => 'application/json',
                ),
                'timeout' => 30,
                'body'    => wp_json_encode($payload),
            ));

            // Check for WordPress HTTP errors
            if (is_wp_error($response)) {
                $this->log('[ERROR] HTTP request failed: ' . $response->get_error_message());
                return false;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            $this->log('[DEBUG] API Response Code: ' . $response_code);
            $this->log('[DEBUG] API Response Body: ' . $response_body);

            // Check for HTTP error codes
            if ($response_code < 200 || $response_code >= 300) {
                $this->log('[ERROR] API request failed with HTTP ' . $response_code);
                $this->log('[ERROR] Response body: ' . $response_body);
                
                // Try to parse error details
                $error_data = json_decode($response_body, true);
                if ($error_data && isset($error_data['err'])) {
                    $this->log('[ERROR] ClickUp API Error: ' . $error_data['err']);
                }
                
                return false;
            }

            // Parse successful response
            $task_data = json_decode($response_body, true);
            if (empty($task_data['id'])) {
                $this->log('[ERROR] No task ID in API response');
                return false;
            }

            return $task_data;
        }

        /**
         * Store task metadata in Gravity Forms entry
         *
         * @param int $entry_id The GF entry ID
         * @param string $task_id The ClickUp task ID
         * @param string $task_url The ClickUp task URL
         * @param string $portal_user Portal user name (optional)
         */
        private function store_task_metadata($entry_id, $task_id, $task_url, $portal_user = '') {
            // Store metadata using Gravity Forms functions
            if (function_exists('gform_update_meta')) {
                gform_update_meta($entry_id, 'clickup_task_id', $task_id);
                gform_update_meta($entry_id, 'clickup_task_url', $task_url);
                if (!empty($portal_user)) {
                    gform_update_meta($entry_id, 'portal_user_name', $portal_user);
                }
                $this->log('[DEBUG] Metadata stored via gform_update_meta');
            }

            // Add entry note if GFAPI is available
            if (class_exists('GFAPI')) {
                $note_text = 'ClickUp task created: ' . $task_id;
                if ($task_url) {
                    $note_text .= ' (' . $task_url . ')';
                }
                if (!empty($portal_user)) {
                    $note_text .= ' | Portal User: ' . $portal_user;
                }
                GFAPI::add_note($entry_id, 0, 'System', $note_text);
                $this->log('[DEBUG] Entry note added via GFAPI');
            }
        }

        /**
         * Handle file attachments with better error handling
         *
         * @param string $task_id The ClickUp task ID
         * @param string $attach_urls Comma-separated file URLs from Gravity Forms
         * @param string $api_key The API key
         */
        private function handle_attachments($task_id, $attach_urls, $api_key) {
            if (empty($attach_urls)) {
                $this->log('[DEBUG] No attachments to process');
                return;
            }

            $this->log('[DEBUG] Processing attachments: ' . $attach_urls);
            
            // Gravity Forms can store multiple files as JSON or comma-separated URLs
            $file_urls = [];
            
            // Try to decode as JSON first (for multiple files)
            $decoded = json_decode($attach_urls, true);
            if (is_array($decoded)) {
                // Handle JSON array format
                foreach ($decoded as $file_url) {
                    if (is_string($file_url) && !empty($file_url)) {
                        $file_urls[] = trim($file_url);
                    }
                }
            } else {
                // Handle comma-separated or single URL format
                $urls = explode(',', $attach_urls);
                foreach ($urls as $url) {
                    $url = trim($url);
                    if (!empty($url)) {
                        $file_urls[] = $url;
                    }
                }
            }
            
            $this->log('[DEBUG] Found ' . count($file_urls) . ' file(s) to upload');
            
            foreach ($file_urls as $index => $file_url) {
                $this->log('[DEBUG] Processing file ' . ($index + 1) . '/' . count($file_urls) . ': ' . $file_url);
                $this->upload_attachment_to_task($task_id, $file_url, $api_key);
                
                // Add small delay between uploads to avoid rate limiting
                if (count($file_urls) > 1) {
                    usleep(500000); // 0.5 second delay
                }
            }
        }

        /**
         * Get default assignees (Fiona Okeeffe)
         *
         * @param string $api_key The API key
         * @return array Array of user IDs to assign
         */
        private function get_default_assignees($api_key): array {
            // Fiona Okeeffe's known ClickUp user ID
            $fiona_id = '75445443';
            
            // First check if admin has manually set a different assignee ID
            $stored_assignee_id = get_option(self::OPT_DEFAULT_ASSIGNEE);
            if (!empty($stored_assignee_id) && $stored_assignee_id !== $fiona_id) {
                $this->log('[DEBUG] Using custom stored assignee ID: ' . $stored_assignee_id);
                return array($stored_assignee_id);
            }

            // Use Fiona's hardcoded ID as primary option
            $this->log('[DEBUG] Using Fiona Okeeffe\'s ID: ' . $fiona_id);
            
            // Store it in options if not already set
            if (empty($stored_assignee_id)) {
                update_option(self::OPT_DEFAULT_ASSIGNEE, $fiona_id);
            }
            
            return array($fiona_id);
        }

        /**
         * Find a user ID by name using ClickUp API (kept for reference/debugging)
         * Currently unused since Fiona's ID (75445443) is hardcoded
         *
         * @param string $api_key The API key
         * @param string $name The name to search for
         * @return string|false User ID if found, false otherwise
         */
        private function find_user_id_by_name($api_key, $name) {
            $this->log('[DEBUG] Searching for user: ' . $name);

            // Get team members
            $response = wp_remote_get('https://api.clickup.com/api/v2/team', array(
                'headers' => array(
                    'Authorization' => $api_key,
                    'Content-Type' => 'application/json',
                ),
                'timeout' => 15,
            ));

            if (is_wp_error($response)) {
                $this->log('[ERROR] Failed to get team info: ' . $response->get_error_message());
                return false;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                $this->log('[ERROR] Team API request failed with HTTP ' . $response_code);
                return false;
            }

            $team_data = json_decode(wp_remote_retrieve_body($response), true);
            if (!isset($team_data['teams'][0]['members'])) {
                $this->log('[ERROR] No team members found in API response');
                return false;
            }

            $members = $team_data['teams'][0]['members'];
            
            // Search for the user by name (case-insensitive)
            foreach ($members as $member) {
                $member_name = isset($member['user']['username']) ? $member['user']['username'] : '';
                $member_email = isset($member['user']['email']) ? $member['user']['email'] : '';
                
                if (stripos($member_name, $name) !== false || stripos($member_email, $name) !== false) {
                    $user_id = $member['user']['id'];
                    $this->log('[DEBUG] Found user "' . $name . '" with ID: ' . $user_id);
                    return $user_id;
                }
            }

            return false;
        }

        private function priority_from_label( string $label ): int {
            $label_lower = strtolower(trim($label));
            $this->log('[DEBUG] Mapping priority: "' . $label . '" -> "' . $label_lower . '"');

            switch ($label_lower) {
                case 'urgent':
                case 'critical':
                    return 1;
                case 'high':
                    return 2;
                case 'normal':
                case 'medium':
                    return 3;
                case 'low':
                    return 4;
                default:
                    return 3; // Default to NORMAL
            }
        }

        /**
         * Build task description with formatted content.
         *
         * @param string $name Contact name
         * @param string $email Contact email  
         * @param string $urgency Urgency level
         * @param string $title Request title
         * @param string $details Request details
         * @param string $portal_user Portal user name
         * @return string Formatted description
         */
        private function build_description( string $name, string $email, string $urgency, string $title, string $details, string $portal_user = '' ): string {
            $parts = array(
                '**Support Request**',
                '',
                '**From:** '   . (!empty($name) ? $name : 'N/A'),
                '**Email:** '  . (!empty($email) ? $email : 'N/A'),
            );

            // Add Portal User if available
            if (!empty($portal_user)) {
                $parts[] = '**Portal User:** ' . $portal_user;
            }

            $parts = array_merge($parts, array(
                '**Urgency:** ' . (!empty($urgency) ? $urgency : 'N/A'),
                '',
                '**Brief Title:**',
                !empty($title) ? $title : 'N/A',
                '',
                '**Details:**',
                !empty($details) ? $details : 'N/A',
            ));

            return implode("\n", $parts);
        }

        /**
         * Upload a file attachment to ClickUp task using multiple methods for reliability.
         *
         * @param string $task_id The ClickUp task ID
         * @param string $file_url The file URL to upload
         * @param string $api_key The API key
         * @return void
         */
        private function upload_attachment_to_task( string $task_id, string $file_url, string $api_key ): void {
            $this->log('[DEBUG] Starting attachment upload for task: ' . $task_id);
            $this->log('[DEBUG] File URL: ' . $file_url);

            // Validate file URL
            if (!filter_var($file_url, FILTER_VALIDATE_URL)) {
                $this->log('[ERROR] Invalid file URL: ' . $file_url);
                return;
            }

            // Download the file to a temporary location
            $temp_file = download_url($file_url, 60);
            if (is_wp_error($temp_file)) {
                $this->log('[ERROR] Failed to download file: ' . $temp_file->get_error_message());
                return;
            }

            // Verify file exists and is readable
            if (!file_exists($temp_file) || !is_readable($temp_file)) {
                $this->log('[ERROR] Downloaded file not accessible: ' . $temp_file);
                return;
            }

            $file_size = filesize($temp_file);
            $this->log('[DEBUG] Downloaded file size: ' . $file_size . ' bytes');

            // Get filename and mime type
            $filename = basename(parse_url($file_url, PHP_URL_PATH));
            if (empty($filename)) {
                $filename = 'attachment_' . time();
            }
            
            $mime_type = $this->get_mime_type($temp_file, $filename);
            $this->log('[DEBUG] File details - Name: ' . $filename . ', MIME: ' . $mime_type . ', Size: ' . $file_size);

            // Try cURL method first (most reliable for file uploads)
            if (function_exists('curl_init')) {
                $success = $this->upload_with_curl($task_id, $temp_file, $filename, $mime_type, $api_key);
                if ($success) {
                    @unlink($temp_file);
                    return;
                }
            }

            // Fallback to WordPress HTTP API
            $success = $this->upload_with_wp_http($task_id, $temp_file, $filename, $mime_type, $api_key);
            
            // Clean up temp file
            @unlink($temp_file);
            
            if (!$success) {
                $this->log('[ERROR] All upload methods failed for file: ' . $filename);
            }
        }

        /**
         * Upload using cURL (most reliable method)
         */
        private function upload_with_curl($task_id, $temp_file, $filename, $mime_type, $api_key): bool {
            $this->log('[DEBUG] Attempting upload with cURL method');
            
            $upload_url = 'https://api.clickup.com/api/v2/task/' . rawurlencode($task_id) . '/attachment';
            
            $curl = curl_init();
            
            // Prepare file for upload
            $cfile = new CURLFile($temp_file, $mime_type, $filename);
            
            curl_setopt_array($curl, [
                CURLOPT_URL => $upload_url,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $api_key,
                    // Don't set Content-Type for multipart, let cURL handle it
                ],
                CURLOPT_POSTFIELDS => [
                    'attachment' => $cfile
                ],
                CURLOPT_VERBOSE => false,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            
            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);
            
            $this->log('[DEBUG] cURL Response Code: ' . $http_code);
            
            if ($error) {
                $this->log('[ERROR] cURL Error: ' . $error);
                return false;
            }
            
            if ($http_code >= 200 && $http_code < 300) {
                $this->log('[SUCCESS] File uploaded successfully via cURL: ' . $filename);
                $this->log('[DEBUG] Response: ' . substr($response, 0, 200) . '...');
                return true;
            } else {
                $this->log('[ERROR] cURL upload failed with HTTP ' . $http_code);
                $this->log('[ERROR] Response: ' . $response);
                return false;
            }
        }

        /**
         * Upload using WordPress HTTP API (fallback method)
         */
        private function upload_with_wp_http($task_id, $temp_file, $filename, $mime_type, $api_key): bool {
            $this->log('[DEBUG] Attempting upload with WordPress HTTP API');
            
            $upload_url = 'https://api.clickup.com/api/v2/task/' . rawurlencode($task_id) . '/attachment';
            
            // Read file contents
            $file_contents = file_get_contents($temp_file);
            if ($file_contents === false) {
                $this->log('[ERROR] Could not read file contents');
                return false;
            }
            
            // Create proper multipart boundary
            $boundary = 'WP_CLICKUP_' . wp_generate_password(16, false);
            
            // Build multipart body
            $body = '';
            $body .= '--' . $boundary . "\r\n";
            $body .= 'Content-Disposition: form-data; name="attachment"; filename="' . $filename . '"' . "\r\n";
            $body .= 'Content-Type: ' . $mime_type . "\r\n";
            $body .= "\r\n";
            $body .= $file_contents;
            $body .= "\r\n";
            $body .= '--' . $boundary . '--' . "\r\n";
            
            $response = wp_remote_post($upload_url, [
                'headers' => [
                    'Authorization' => $api_key,
                    'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
                ],
                'body' => $body,
                'timeout' => 120,
            ]);
            
            if (is_wp_error($response)) {
                $this->log('[ERROR] WP HTTP upload failed: ' . $response->get_error_message());
                return false;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            if ($response_code >= 200 && $response_code < 300) {
                $this->log('[SUCCESS] File uploaded successfully via WP HTTP: ' . $filename);
                return true;
            } else {
                $this->log('[ERROR] WP HTTP upload failed with HTTP ' . $response_code);
                $this->log('[ERROR] Response: ' . $response_body);
                return false;
            }
        }

        /**
         * Get MIME type for file
         */
        private function get_mime_type($file_path, $filename): string {
            // Try multiple methods to get MIME type
            $mime_type = 'application/octet-stream'; // Default fallback
            
            // Method 1: Use finfo if available
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $detected = finfo_file($finfo, $file_path);
                    if ($detected) {
                        $mime_type = $detected;
                    }
                    finfo_close($finfo);
                }
            }
            
            // Method 2: Use mime_content_type if available
            if ($mime_type === 'application/octet-stream' && function_exists('mime_content_type')) {
                $detected = mime_content_type($file_path);
                if ($detected) {
                    $mime_type = $detected;
                }
            }
            
            // Method 3: Guess from file extension
            if ($mime_type === 'application/octet-stream') {
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $mime_types = [
                    'pdf' => 'application/pdf',
                    'doc' => 'application/msword',
                    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'xls' => 'application/vnd.ms-excel',
                    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'ppt' => 'application/vnd.ms-powerpoint',
                    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'txt' => 'text/plain',
                    'zip' => 'application/zip',
                    'rar' => 'application/x-rar-compressed',
                ];
                
                if (isset($mime_types[$extension])) {
                    $mime_type = $mime_types[$extension];
                }
            }
            
            return $mime_type;
        }

        /**
         * Enhanced logging function with timestamp and log levels
         *
         * @param string $message The message to log
         * @param string $level Log level (DEBUG, INFO, WARNING, ERROR, SUCCESS)
         */
        private function log( string $message, string $level = 'INFO' ): void {
            $timestamp = current_time('Y-m-d H:i:s');
            $formatted_message = "[{$timestamp}] [{$level}] GF-ClickUp: {$message}";
            
            error_log($formatted_message);
            
            // Also log to a custom log file if WP_DEBUG_LOG is enabled
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                $log_file = WP_CONTENT_DIR . '/debug-gf-clickup.log';
                file_put_contents($log_file, $formatted_message . PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        }

        /**
         * Test attachment upload functionality (for debugging)
         *
         * @param string $test_file_url URL of file to test
         * @return array Test results
         */
        public function test_attachment_upload($test_file_url = null): array {
            $api_key = $this->get_api_key();
            if (empty($api_key)) {
                return array(
                    'success' => false,
                    'message' => 'No API key available'
                );
            }

            // Use test file URL or a default image
            if (empty($test_file_url)) {
                $test_file_url = 'https://via.placeholder.com/100x100.png?text=Test';
            }

            // First, create a test task to attach to
            $list_id = get_option(self::OPT_LIST_ID, self::FALLBACK_LIST);
            
            $test_payload = array(
                'name' => 'Test Attachment Upload - ' . date('Y-m-d H:i:s'),
                'description' => 'This is a test task created to test file attachment uploads. You can safely delete this task.',
                'priority' => 3,
            );

            $task_result = $this->create_clickup_task($list_id, $test_payload, $api_key);
            if (!$task_result) {
                return array(
                    'success' => false,
                    'message' => 'Failed to create test task for attachment testing'
                );
            }

            $test_task_id = $task_result['id'];
            
            // Now try to upload the attachment
            try {
                $this->upload_attachment_to_task($test_task_id, $test_file_url, $api_key);
                
                return array(
                    'success' => true,
                    'message' => 'Attachment test completed. Check the task in ClickUp to verify the file was attached.',
                    'test_task_id' => $test_task_id,
                    'test_task_url' => $task_result['url'] ?? '',
                    'test_file_url' => $test_file_url
                );
                
            } catch (Exception $e) {
                return array(
                    'success' => false,
                    'message' => 'Attachment upload test failed: ' . $e->getMessage(),
                    'test_task_id' => $test_task_id
                );
            }
        }

        public function test_api_connection(): array {
            $api_key = $this->get_api_key();
            if (empty($api_key)) {
                return array(
                    'success' => false,
                    'message' => 'No API key available'
                );
            }

            // Test with a simple API call to get user info
            $response = wp_remote_get('https://api.clickup.com/api/v2/user', array(
                'headers' => array(
                    'Authorization' => $api_key,
                    'Content-Type' => 'application/json',
                ),
                'timeout' => 15,
            ));

            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'message' => 'HTTP Error: ' . $response->get_error_message()
                );
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);

            if ($response_code === 200) {
                $user_data = json_decode($response_body, true);
                $result = array(
                    'success' => true,
                    'message' => 'API connection successful',
                    'user' => $user_data['user']['username'] ?? 'Unknown user',
                    'team_members' => array()
                );

                // Get team members for debugging
                $team_response = wp_remote_get('https://api.clickup.com/api/v2/team', array(
                    'headers' => array(
                        'Authorization' => $api_key,
                        'Content-Type' => 'application/json',
                    ),
                    'timeout' => 15,
                ));

                if (!is_wp_error($team_response) && wp_remote_retrieve_response_code($team_response) === 200) {
                    $team_data = json_decode(wp_remote_retrieve_body($team_response), true);
                    if (isset($team_data['teams'][0]['members'])) {
                        foreach ($team_data['teams'][0]['members'] as $member) {
                            $result['team_members'][] = array(
                                'id' => $member['user']['id'],
                                'username' => $member['user']['username'],
                                'email' => $member['user']['email'] ?? ''
                            );
                        }
                    }
                }

                // Check current assignee setup
                $current_assignee = get_option('clickup_default_assignee_id', '75445443');
                $result['current_assignee_id'] = $current_assignee;
                $result['using_fiona_default'] = ($current_assignee === '75445443');

                return $result;
            } else {
                return array(
                    'success' => false,
                    'message' => 'API Error (HTTP ' . $response_code . '): ' . $response_body
                );
            }
        }
    }
}

// Instantiate the class
if ( class_exists('GF_ClickUp_Integration') ) {
    global $gf_clickup_integration_instance;
    if ( ! isset($gf_clickup_integration_instance) ) {
        $gf_clickup_integration_instance = new GF_ClickUp_Integration();
        error_log('[INFO] GF-ClickUp integration initialized successfully');
    }
}

// Add cron hook for processing tasks
add_action('gf_clickup_process_tasks', function() {
    global $gf_clickup_integration_instance;
    if ($gf_clickup_integration_instance) {
        $gf_clickup_integration_instance->process_scheduled_tasks();
    }
});

// Add AJAX endpoint for immediate processing
add_action('wp_ajax_gf_process_clickup', function() {
    global $gf_clickup_integration_instance;
    if ($gf_clickup_integration_instance) {
        $gf_clickup_integration_instance->process_scheduled_tasks();
        wp_send_json_success('Tasks processed');
    }
    wp_send_json_error('Integration not available');
});

add_action('wp_ajax_nopriv_gf_process_clickup', function() {
    global $gf_clickup_integration_instance;
    if ($gf_clickup_integration_instance) {
        $gf_clickup_integration_instance->process_scheduled_tasks();
        wp_send_json_success('Tasks processed');
    }
    wp_send_json_error('Integration not available');
});

// Add JavaScript to trigger background processing
add_action('wp_footer', function() {
    if (get_transient('gf_clickup_scheduled_tasks')) {
        ?>
        <script>
        // Trigger ClickUp task processing in background
        setTimeout(function() {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=gf_process_clickup'
            }).then(function(response) {
                return response.json();
            }).then(function(data) {
                if (data.success) {
                    console.log('ClickUp tasks processed successfully');
                }
            }).catch(function(error) {
                console.log('ClickUp background processing failed:', error);
            });
        }, 2000); // Wait 2 seconds after page load
        </script>
        <?php
    }
});

// Add AJAX endpoint for attachment testing
add_action('wp_ajax_test_clickup_attachment', function() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    global $gf_clickup_integration_instance;
    if ($gf_clickup_integration_instance) {
        $test_url = isset($_POST['test_url']) ? esc_url($_POST['test_url']) : null;
        $result = $gf_clickup_integration_instance->test_attachment_upload($test_url);
        wp_send_json($result);
    } else {
        wp_send_json(array('success' => false, 'message' => 'Integration not initialized'));
    }
});

// Add admin function to test the API connection (for debugging)
if (is_admin()) {
    add_action('wp_ajax_test_clickup_api', function() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $gf_clickup_integration_instance;
        if ($gf_clickup_integration_instance) {
            $result = $gf_clickup_integration_instance->test_api_connection();
            wp_send_json($result);
        } else {
            wp_send_json(array('success' => false, 'message' => 'Integration not initialized'));
        }
    });

    // Enhanced admin test page
    add_action('admin_menu', function() {
        add_management_page(
            'ClickUp Integration Test', 
            'ClickUp Test', 
            'manage_options', 
            'test-clickup', 
            function() {
                // Handle setting custom assignee ID
                if (isset($_POST['set_assignee_id']) && $_POST['assignee_user_id']) {
                    $assignee_id = sanitize_text_field($_POST['assignee_user_id']);
                    update_option('clickup_default_assignee_id', $assignee_id);
                    echo '<div class="notice notice-success"><p>Default assignee ID updated to: ' . esc_html($assignee_id) . '</p></div>';
                }

                // Handle reset to Fiona's default
                if (isset($_POST['reset_to_fiona'])) {
                    update_option('clickup_default_assignee_id', '75445443');
                    echo '<div class="notice notice-success"><p>Reset to Fiona Okeeffe\'s default ID: 75445443</p></div>';
                }

                // Handle attachment test
                if (isset($_POST['test_attachment'])) {
                    global $gf_clickup_integration_instance;
                    $test_file_url = !empty($_POST['test_file_url']) ? esc_url($_POST['test_file_url']) : null;
                    $attachment_result = $gf_clickup_integration_instance->test_attachment_upload($test_file_url);
                    
                    echo '<div class="notice notice-' . ($attachment_result['success'] ? 'success' : 'error') . '">';
                    echo '<p><strong>Attachment Test Result:</strong></p>';
                    echo '<p>' . esc_html($attachment_result['message']) . '</p>';
                    if (isset($attachment_result['test_task_id'])) {
                        echo '<p>Test Task ID: ' . esc_html($attachment_result['test_task_id']) . '</p>';
                        if (!empty($attachment_result['test_task_url'])) {
                            echo '<p><a href="' . esc_url($attachment_result['test_task_url']) . '" target="_blank">View Test Task in ClickUp</a></p>';
                        }
                    }
                    echo '</div>';
                }

                if (isset($_POST['test_api'])) {
                    global $gf_clickup_integration_instance;
                    $result = $gf_clickup_integration_instance->test_api_connection();
                    echo '<div class="notice notice-' . ($result['success'] ? 'success' : 'error') . '">';
                    echo '<p><strong>' . esc_html($result['message']) . '</strong></p>';
                    if (isset($result['user'])) {
                        echo '<p>Connected as: ' . esc_html($result['user']) . '</p>';
                    }
                    if (isset($result['current_assignee_id'])) {
                        echo '<p>Current default assignee ID: <strong>' . esc_html($result['current_assignee_id']) . '</strong>';
                        if ($result['using_fiona_default']) {
                            echo ' <span style="color:green;">(Fiona Okeeffe - Default)</span>';
                        }
                        echo '</p>';
                    }
                    echo '</div>';

                    if ($result['success'] && !empty($result['team_members'])) {
                        echo '<div class="notice notice-info">';
                        echo '<p><strong>Available Team Members:</strong></p>';
                        echo '<table class="widefat"><thead><tr><th>User ID</th><th>Username</th><th>Email</th><th>Notes</th></tr></thead><tbody>';
                        foreach ($result['team_members'] as $member) {
                            $is_current_assignee = ($member['id'] == get_option('clickup_default_assignee_id', '75445443'));
                            echo '<tr' . ($is_current_assignee ? ' style="background-color:#d4edda;"' : '') . '>';
                            echo '<td><strong>' . esc_html($member['id']) . '</strong></td>';
                            echo '<td>' . esc_html($member['username']) . '</td>';
                            echo '<td>' . esc_html($member['email']) . '</td>';
                            echo '<td>';
                            if ($is_current_assignee) {
                                echo '<strong style="color:green;">← Current Default Assignee</strong>';
                            } elseif ($member['id'] == '75445443') {
                                echo '<strong style="color:blue;">← Fiona Okeeffe (Default)</strong>';
                            } elseif (stripos($member['username'], 'fiona') !== false || stripos($member['email'], 'fiona') !== false) {
                                echo '<span style="color:orange;">← Potential Fiona Match</span>';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                    }
                }
                ?>
                <div class="wrap">
                    <h1>ClickUp Integration Test</h1>
                    
                    <div class="card" style="max-width: 100%;">
                        <h2>📋 Quick Status Check</h2>
                        <form method="post">
                            <input type="submit" name="test_api" class="button button-primary" value="🔍 Test API & Show Team Members">
                        </form>
                    </div>

                    <div class="card" style="max-width: 100%; margin-top: 20px;">
                        <h2>📎 Test File Attachments</h2>
                        <p>Test if file uploads to ClickUp tasks are working properly.</p>
                        <form method="post">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Test File URL</th>
                                    <td>
                                        <input type="url" name="test_file_url" value="https://via.placeholder.com/200x200.png?text=Test+Upload" class="regular-text" />
                                        <p class="description">Enter a file URL to test, or leave default to test with a placeholder image.</p>
                                    </td>
                                </tr>
                            </table>
                            <p class="submit">
                                <input type="submit" name="test_attachment" class="button button-secondary" value="🧪 Test Attachment Upload" />
                            </p>
                            <p class="description">
                                <strong>Note:</strong> This will create a test task in your ClickUp list and attempt to attach the file. 
                                You can safely delete the test task afterward.
                            </p>
                        </form>
                    </div>

                    <div class="card" style="max-width: 100%; margin-top: 20px;">
                        <h2>👤 Default Assignee Settings</h2>
                        <p><strong>Current Default:</strong> Fiona Okeeffe (ID: 75445443)</p>
                        <p>All new ClickUp tasks will be automatically assigned to this user.</p>
                        
                        <h3>Change Default Assignee</h3>
                        <form method="post" style="margin-bottom: 20px;">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Custom Assignee User ID</th>
                                    <td>
                                        <input type="text" name="assignee_user_id" value="<?php echo esc_attr(get_option('clickup_default_assignee_id', '75445443')); ?>" class="regular-text" />
                                        <p class="description">Enter a different ClickUp user ID if you want to change the default assignee.</p>
                                    </td>
                                </tr>
                            </table>
                            <p class="submit">
                                <input type="submit" name="set_assignee_id" class="button button-primary" value="💾 Update Default Assignee" />
                                <input type="submit" name="reset_to_fiona" class="button button-secondary" value="🔄 Reset to Fiona Okeeffe" style="margin-left: 10px;" />
                            </p>
                        </form>
                    </div>
                    
                    <div class="card" style="max-width: 100%; margin-top: 20px;">
                        <h2>🛠️ Debug Information</h2>
                        <table class="form-table">
                            <tr>
                                <th>API Key Status:</th>
                                <td><?php echo get_option('clickup_api_key') ? '<span style="color:green;">✅ Set</span>' : '<span style="color:red;">❌ Not Set</span>'; ?></td>
                            </tr>
                            <tr>
                                <th>Default List ID:</th>
                                <td><?php echo esc_html(get_option('clickup_default_list_id') ?: 'Using fallback (901103489415)'); ?></td>
                            </tr>
                            <tr>
                                <th>Default Assignee ID:</th>
                                <td><?php 
                                    $assignee_id = get_option('clickup_default_assignee_id', '75445443');
                                    echo esc_html($assignee_id);
                                    if ($assignee_id === '75445443') {
                                        echo ' <span style="color:green;">(Fiona Okeeffe)</span>';
                                    }
                                ?></td>
                            </tr>
                            <tr>
                                <th>Pending Tasks:</th>
                                <td><?php 
                                    $scheduled = get_transient('gf_clickup_scheduled_tasks');
                                    echo $scheduled ? count($scheduled) . ' tasks waiting' : '<span style="color:green;">None</span>'; 
                                ?></td>
                            </tr>
                            <tr>
                                <th>Form Integration:</th>
                                <td><?php 
                                    global $gf_clickup_integration_instance;
                                    echo $gf_clickup_integration_instance ? '<span style="color:green;">✅ Active</span>' : '<span style="color:red;">❌ Not Active</span>';
                                ?></td>
                            </tr>
                        </table>
                        
                        <h3>📝 Recent Logs</h3>
                        <p><em>Check these files for detailed debugging information:</em></p>
                        <ul>
                            <li><strong>Main Log:</strong> <code>/wp-content/debug.log</code></li>
                            <li><strong>ClickUp Log:</strong> <code>/wp-content/debug-gf-clickup.log</code></li>
                        </ul>

                        <h3>🔧 Troubleshooting Tips</h3>
                        <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin: 15px 0;">
                            <p><strong>If attachments aren't working:</strong></p>
                            <ol>
                                <li>Check your <strong>file upload field ID</strong> in the form settings</li>
                                <li>Look at the <strong>"Full entry data"</strong> in the debug logs to find the correct field ID</li>
                                <li>Update the <code>$FIELD_ATTACH</code> variable in the code</li>
                                <li>Use the <strong>"Test Attachment Upload"</strong> above to verify ClickUp API is working</li>
                                <li>Make sure your form has <strong>file upload permissions</strong> enabled</li>
                            </ol>

                            <p><strong>Current Form Field Mapping:</strong></p>
                            <ul>
                                <li><strong>Name:</strong> Field ID 1</li>
                                <li><strong>Email:</strong> Field ID 4</li>
                                <li><strong>Details:</strong> Field ID 5</li>
                                <li><strong>Urgency:</strong> Field ID 7</li>
                                <li><strong>Attachments:</strong> Field ID 8</li>
                                <li><strong>Title:</strong> Field ID 9</li>
                                <li><strong>Portal User:</strong> Field ID 10 (Hidden Field)</li>
                            </ul>
                            <p><em>Update these IDs in the code if your form fields have different numbers.</em></p>

                            <p><strong>Common Gravity Forms file field formats:</strong></p>
                            <ul>
                                <li><strong>Single file:</strong> <code>https://example.com/file.pdf</code></li>
                                <li><strong>Multiple files:</strong> <code>["url1.pdf","url2.jpg","url3.doc"]</code></li>
                                <li><strong>Advanced fields:</strong> JSON with file metadata</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <style>
                .card { background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
                .form-table th { width: 200px; }
                .widefat tbody tr:hover { background-color: #f6f7f7; }
                </style>
                <?php
            }
        );
    });
}