<?php
// Define encryption key (store securely in wp-config.php or environment variable)
// Example 32-character key (replace with your own secure key in wp-config.php)
$encryption_key = defined('CLICKUP_ENCRYPTION_KEY') ? CLICKUP_ENCRYPTION_KEY : 'X7k9pLmQwXyZaBvNxRk4tPq7s8vY2mHo3'; // Exactly 32 chars
if (strlen($encryption_key) > 32) {
    wp_die('Encryption key must be exactly 32 characters long. Please update CLICKUP_ENCRYPTION_KEY in wp-config.php.');
}

// Encryption/Decryption functions
function encrypt_value($value, $key) {
    $iv_length = openssl_cipher_iv_length('aes-256-cbc');
    $iv = openssl_random_pseudo_bytes($iv_length);
    $encrypted = openssl_encrypt($value, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decrypt_value($value, $key) {
    $data = base64_decode($value);
    $iv_length = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $iv_length);
    $encrypted_data = substr($data, $iv_length);
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
}

// Admin Settings Page for ClickUp API Key, Team ID, and Workspace ID
add_action('admin_menu', function() {
    add_menu_page('ClickUp Settings', 'ClickUp Settings', 'manage_options', 'clickup-settings', function() {
        ?>
        <div class="wrap">
            <h1>ClickUp Integration Settings</h1>
            <form method="post" action="options.php" id="clickup-settings-form">
                <?php
                settings_fields('clickup_settings');
                do_settings_sections('clickup_settings');
                wp_nonce_field('clickup_settings_action', 'clickup_settings_nonce');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="clickup_api_key"><strong>API Key:</strong></label></th>
                        <td><input type="password" name="clickup_api_key" id="clickup_api_key" size="50" autocomplete="off" value=""></td>
                    </tr>
                    <tr>
                        <th><label for="clickup_team_id"><strong>Team ID:</strong></label></th>
                        <td><input type="text" name="clickup_team_id" id="clickup_team_id" size="50" value=""></td>
                    </tr>
                    <tr>
                        <th><label for="clickup_workspace_id"><strong>Workspace ID:</strong></label></th>
                        <td><input type="text" name="clickup_workspace_id" id="clickup_workspace_id" size="50" value=""></td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Populate fields with decrypted values via JavaScript
                <?php
                $api_key = get_option('clickup_api_key');
                $team_id = get_option('clickup_team_id');
                $workspace_id = get_option('clickup_workspace_id');
                $decrypted_api_key = $api_key ? decrypt_value($api_key, $encryption_key) : '';
                $decrypted_team_id = $team_id ? decrypt_value($team_id, $encryption_key) : '';
                $decrypted_workspace_id = $workspace_id ? decrypt_value($workspace_id, $encryption_key) : '';
                ?>
                document.getElementById('clickup_api_key').value = '<?php echo esc_js($decrypted_api_key); ?>';
                document.getElementById('clickup_team_id').value = '<?php echo esc_js($decrypted_team_id); ?>';
                document.getElementById('clickup_workspace_id').value = '<?php echo esc_js($decrypted_workspace_id); ?>';
            });
        </script>
        <?php
    });
});

add_action('admin_init', function() {
    // Register settings with encryption on save
    register_setting(
        'clickup_settings',
        'clickup_api_key',
        array(
            'sanitize_callback' => function($value) use ($encryption_key) {
                if (!current_user_can('manage_options')) {
                    wp_die('Unauthorized access.');
                }
                if (!empty($value) && strlen($value) < 10) {
                    add_settings_error('clickup_api_key', 'clickup_api_key_length_error', 'API Key must be at least 10 characters long.', 'error');
                    return get_option('clickup_api_key'); // Return old value
                }
                return empty($value) ? '' : encrypt_value($value, $encryption_key);
            },
            'default' => ''
        )
    );
    register_setting(
        'clickup_settings',
        'clickup_team_id',
        array(
            'sanitize_callback' => function($value) use ($encryption_key) {
                if (!current_user_can('manage_options')) {
                    wp_die('Unauthorized access.');
                }
                if (!empty($value) && strlen($value) < 10) {
                    add_settings_error('clickup_team_id', 'clickup_team_id_length_error', 'Team ID must be at least 10 characters long.', 'error');
                    return get_option('clickup_team_id'); // Return old value
                }
                return empty($value) ? '' : encrypt_value($value, $encryption_key);
            },
            'default' => ''
        )
    );
    register_setting(
        'clickup_settings',
        'clickup_workspace_id',
        array(
            'sanitize_callback' => function($value) use ($encryption_key) {
                if (!current_user_can('manage_options')) {
                    wp_die('Unauthorized access.');
                }
                if (!empty($value) && strlen($value) < 10) {
                    add_settings_error('clickup_workspace_id', 'clickup_workspace_id_length_error', 'Workspace ID must be at least 10 characters long.', 'error');
                    return get_option('clickup_workspace_id'); // Return old value
                }
                return empty($value) ? '' : encrypt_value($value, $encryption_key);
            },
            'default' => ''
        )
    );

    // Validate settings on save
    add_filter('pre_update_option_clickup_api_key', function($new_value, $old_value) use ($encryption_key) {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['clickup_settings_nonce'], 'clickup_settings_action')) {
            wp_die('Unauthorized access or invalid nonce.');
        }
        if (empty($new_value)) {
            add_settings_error('clickup_api_key', 'clickup_api_key_error', 'API Key cannot be empty.', 'error');
            return $old_value;
        }
        if (strlen($new_value) < 10) {
            add_settings_error('clickup_api_key', 'clickup_api_key_length_error', 'API Key must be at least 10 characters long.', 'error');
            return $old_value;
        }
        return encrypt_value($new_value, $encryption_key);
    }, 10, 2);

    add_filter('pre_update_option_clickup_team_id', function($new_value, $old_value) use ($encryption_key) {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['clickup_settings_nonce'], 'clickup_settings_action')) {
            wp_die('Unauthorized access or invalid nonce.');
        }
        if (empty($new_value)) {
            add_settings_error('clickup_team_id', 'clickup_team_id_error', 'Team ID cannot be empty.', 'error');
            return $old_value;
        }
        if (strlen($new_value) < 10) {
            add_settings_error('clickup_team_id', 'clickup_team_id_length_error', 'Team ID must be at least 10 characters long.', 'error');
            return $old_value;
        }
        return encrypt_value($new_value, $encryption_key);
    }, 10, 2);

    add_filter('pre_update_option_clickup_workspace_id', function($new_value, $old_value) use ($encryption_key) {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['clickup_settings_nonce'], 'clickup_settings_action')) {
            wp_die('Unauthorized access or invalid nonce.');
        }
        if (empty($new_value)) {
            add_settings_error('clickup_workspace_id', 'clickup_workspace_id_error', 'Workspace ID cannot be empty.', 'error');
            return $old_value;
        }
        if (strlen($new_value) < 10) {
            add_settings_error('clickup_workspace_id', 'clickup_workspace_id_length_error', 'Workspace ID must be at least 10 characters long.', 'error');
            return $old_value;
        }
        return encrypt_value($new_value, $encryption_key);
    }, 10, 2);
});