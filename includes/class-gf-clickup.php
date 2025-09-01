<?php
/**
 * Gravity Forms → ClickUp integration
 * - Creates a ClickUp task after a GF submission.
 * - Keeps encryption (expects CLICKUP_ENCRYPTION_KEY in wp-config.php).
 * - Uses decrypt_value($cipher, $key) if available; otherwise falls back to plaintext.
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
         * Adjust if your plugin stores them under different keys.
         */
        const OPT_API_KEY   = 'clickup_api_key';
        const OPT_LIST_ID   = 'clickup_default_list_id'; // recommended to keep in settings
        const FALLBACK_LIST = '901103489415';            // fallback if option not set

        public function __construct() {
            // Main hook to create task after submission
            add_action('gform_after_submission', array($this, 'handle_submission'), 10, 2);

            $this->log('[DEBUG] GF-ClickUp class constructed');
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

            // Gravity helper rgar should exist, but guard anyway
            if ( ! function_exists('rgar') ) {
                $this->log('[DEBUG] ERROR: rgar() not available; is Gravity Forms loaded?');
                return;
            }

            // 1) Get API key (keeps encryption)
            $api_key = $this->get_api_key();
            if ( $api_key === '' ) {
                $this->log('[DEBUG] ERROR: API key missing (decryption failed or not set). Aborting.');
                return;
            }
            $this->log('[DEBUG] API key obtained: ' . substr($api_key, 0, 10) . '...');

            // 2) Map your field IDs (adjust to your form)
            $FIELD_NAME    = 1; // Name
            $FIELD_EMAIL   = 4; // Email
            $FIELD_TITLE   = 9; // Brief title
            $FIELD_DETAILS = 5; // Detailed description
            $FIELD_URGENCY = 7; // Urgency (priority)
            $FIELD_ATTACH  = 8; // File Upload (Gravity stores URLs in $entry[$FIELD_ATTACH])

            // Log some entry fields for debugging (safe subset)
            $this->log('[DEBUG] All entry fields:');
            foreach ($entry as $k => $v) {
                if (is_string($v)) {
                    $this->log('[DEBUG] Field ' . $k . ': ' . substr($v, 0, 100));
                }
            }

            $name    = trim( rgar($entry, (string) $FIELD_NAME) );
            $email   = trim( rgar($entry, (string) $FIELD_EMAIL) );
            $title   = trim( rgar($entry, (string) $FIELD_TITLE) );
            $details = trim( rgar($entry, (string) $FIELD_DETAILS) );
            $urgency = trim( rgar($entry, (string) $FIELD_URGENCY) );
            $attach  = trim( rgar($entry, (string) $FIELD_ATTACH) );

            $this->log('[DEBUG] Extracted fields:');
            $this->log('[DEBUG] Name: ' . $name);
            $this->log('[DEBUG] Email: ' . $email);
            $this->log('[DEBUG] Title: ' . $title);
            $this->log('[DEBUG] Details: ' . substr($details, 0, 100));
            $this->log('[DEBUG] Urgency: ' . $urgency);
            if ($attach !== '') {
                $this->log('[DEBUG] Attachment URL(s): ' . substr($attach, 0, 200));
            }

            if ($title === '') {
                $title = 'Support Request from ' . ($name !== '' ? $name : 'Website');
                $this->log('[DEBUG] Using fallback title: ' . $title);
            }

            $priority = $this->priority_from_label($urgency);
            $this->log('[DEBUG] Priority set to: ' . $priority);

            $description = $this->build_description($name, $email, $urgency, $title, $details);

            $payload = array(
                'name'        => $title,
                'description' => $description,
                'priority'    => $priority,
            );

            $this->log('[DEBUG] Payload: ' . wp_json_encode($payload));

            // 3) Determine list ID (from option with fallback)
            $list_id = get_option(self::OPT_LIST_ID);
            if ( ! $list_id ) {
                $list_id = self::FALLBACK_LIST;
            }

            $url = 'https://api.clickup.com/api/v2/list/' . rawurlencode($list_id) . '/task';
            $this->log('[DEBUG] API URL: ' . $url);
            $this->log('[DEBUG] Making API request...');

            $resp = wp_remote_post($url, array(
                'headers' => array(
                    'Authorization' => $api_key,         // ClickUp v2 expects token value only
                    'Content-Type'  => 'application/json',
                ),
                'timeout' => 30,
                'body'    => wp_json_encode($payload),
            ));

            if ( is_wp_error($resp) ) {
                $this->log('[DEBUG] ERROR: wp_remote_post failed: ' . $resp->get_error_message());
                return;
            }

            $code     = wp_remote_retrieve_response_code($resp);
            $body_raw = wp_remote_retrieve_body($resp);
            $this->log('[DEBUG] API Response Code: ' . $code);
            $this->log('[DEBUG] API Response Body: ' . $body_raw);

            if ( $code < 200 || $code >= 300 ) {
                $this->log('[DEBUG] ERROR: API request failed. HTTP ' . $code);
                return;
            }

            $json = json_decode($body_raw, true);
            if ( empty($json['id']) ) {
                $this->log('[DEBUG] ERROR: No task ID in response');
                return;
            }

            $task_id  = $json['id'];
            $task_url = isset($json['url']) ? $json['url'] : '';

            $this->log('[DEBUG] SUCCESS: Task created with ID: ' . $task_id);
            if ($task_url) {
                $this->log('[DEBUG] Task URL: ' . $task_url);
            }

            // 4) Store metadata back to the entry
            if ( function_exists('gform_update_meta') ) {
                gform_update_meta($entry['id'], 'clickup_task_id',  $task_id);
                gform_update_meta($entry['id'], 'clickup_task_url', $task_url);
                $this->log('[DEBUG] Metadata stored via gform_update_meta');
            }

            if ( class_exists('GFAPI') ) {
                GFAPI::add_note($entry['id'], 0, 'System', 'ClickUp task created: ' . $task_id . ($task_url ? ' (' . $task_url . ')' : ''));
                $this->log('[DEBUG] Note added via GFAPI');
            }

            // 5) (Optional) Upload attachments if present (Gravity may give multiple URLs separated by commas)
            if ( $attach !== '' ) {
                $urls = array_map('trim', explode(',', $attach));
                foreach ($urls as $file_url) {
                    if ($file_url === '') continue;
                    $this->upload_attachment_to_task($task_id, $file_url, $api_key);
                }
            }

            $this->log('[DEBUG] ===== Process Complete =====');
        }

        /**
         * Get the ClickUp API key.
         * - Keeps encryption.
         * - Uses decrypt_value($cipher, CLICKUP_ENCRYPTION_KEY) if available.
         * - Falls back to plaintext if decryption returns empty.
         *
         * @return string
         */
        private function get_api_key(): string {
            $this->log('[DEBUG] Getting ClickUp API key...');

            $stored = get_option(self::OPT_API_KEY);
            if ($stored === '' || $stored === null) {
                $this->log('[DEBUG] No API key found in options');
                return '';
            }

            $key_const = (defined('CLICKUP_ENCRYPTION_KEY') && CLICKUP_ENCRYPTION_KEY)
                ? CLICKUP_ENCRYPTION_KEY
                : '';

            $decrypted = '';
            if ( $key_const && function_exists('decrypt_value') ) {
                // Your settings code stores base64(iv|cipher) without a tag; try-decrypt and fall back
                $decrypted = decrypt_value($stored, $key_const);
            }

            $ok = is_string($decrypted) && $decrypted !== '';
            $this->log('[DEBUG] Decryption successful: ' . ($ok ? 'YES' : 'NO'));

            return $ok ? $decrypted : $stored; // fallback to plaintext token if not encrypted
        }

        /**
         * Map urgency label to ClickUp numeric priority.
         * 1 = URGENT, 2 = HIGH, 3 = NORMAL, 4 = LOW
         *
         * @param string $label
         * @return int
         */
        private function priority_from_label( string $label ): int {
            $u = strtolower(trim($label));
            $this->log('[DEBUG] Priority mapping: ' . $label . ' -> ' . $u);

            if ($u === 'urgent' || $u === 'critical') return 1;
            if ($u === 'high') return 2;
            if ($u === 'normal' || $u === 'medium') return 3;
            if ($u === 'low') return 4;
            return 3; // default NORMAL
        }

        /**
         * Build Markdown-ish description body.
         */
        private function build_description( string $name, string $email, string $urgency, string $title, string $details ): string {
            return implode("\n", array(
                '**Support Request**',
                '',
                '**From:** '   . ($name   !== '' ? $name   : 'N/A'),
                '**Email:** '  . ($email  !== '' ? $email  : 'N/A'),
                '**Urgency:** ' . ($urgency !== '' ? $urgency : 'N/A'),
                '',
                '**Brief Title:**',
                ($title !== '' ? $title : 'N/A'),
                '',
                '**Details:**',
                ($details !== '' ? $details : 'N/A'),
            ));
        }

        /**
         * Upload a file (by URL) to the created ClickUp task as an attachment.
         * Note: ClickUp supports multipart upload to /task/{task_id}/attachment
         *
         * @param string $task_id
         * @param string $file_url
         * @param string $api_key
         * @return void
         */
        private function upload_attachment_to_task( string $task_id, string $file_url, string $api_key ): void {
            // Fetch file into tmp
            $this->log('[DEBUG] Attempting attachment upload: ' . $file_url);

            // Download the file to temp
            $tmp = download_url($file_url, 30);
            if ( is_wp_error($tmp) ) {
                $this->log('[DEBUG] Attachment download failed: ' . $tmp->get_error_message());
                return;
            }

            $filename = basename(parse_url($file_url, PHP_URL_PATH)) ?: 'attachment';
            $url      = 'https://api.clickup.com/api/v2/task/' . rawurlencode($task_id) . '/attachment';

            $args = array(
                'headers'   => array(
                    'Authorization' => $api_key,
                ),
                'timeout'   => 60,
                'body'      => array(
                    'attachment' => curl_file_create($tmp, mime_content_type($tmp) ?: 'application/octet-stream', $filename),
                ),
            );

            // Force WP Requests to send multipart/form-data with file
            add_filter('http_api_curl', function($handle) {
                // no-op; curl_file_create triggers multipart automatically
            });

            $response = wp_remote_post($url, $args);

            // Clean up temp file
            @unlink($tmp);

            if ( is_wp_error($response) ) {
                $this->log('[DEBUG] Attachment upload error: ' . $response->get_error_message());
                return;
            }

            $code = wp_remote_retrieve_response_code($response);
            $this->log('[DEBUG] Attachment upload HTTP: ' . $code);
            if ($code < 200 || $code >= 300) {
                $this->log('[DEBUG] Attachment upload failed. Body: ' . wp_remote_retrieve_body($response));
            } else {
                $this->log('[DEBUG] Attachment uploaded successfully.');
            }
        }

        /**
         * Lightweight logger wrapper
         */
        private function log( string $msg ): void {
            error_log($msg);
        }
    }
}

// Instantiate once.
if ( class_exists('GF_ClickUp_Integration') ) {
    // Avoid multiple instances in case of multiple includes
    global $gf_clickup_integration_instance;
    if ( ! isset($gf_clickup_integration_instance) ) {
        $gf_clickup_integration_instance = new GF_ClickUp_Integration();
        // Mark loaded for debugging consistency with your logs
        error_log('[DEBUG] GF-ClickUp integration file loaded successfully');
    }
}
