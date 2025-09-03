<?php
/**
 * Admin: ClickUp & WhatConverts settings (RAW, no encryption)
 */
if (!defined('ABSPATH')) exit;

/**
 * Settings Page: ClickUp
 */
add_action('admin_menu', function () {
    add_menu_page(
        'ClickUp Settings',
        'ClickUp Settings',
        'manage_options',
        'clickup-settings',
        function () {
            if (!current_user_can('manage_options')) wp_die('Unauthorized');

            $api_key      = get_option('clickup_api_key', '');
            $team_id      = get_option('clickup_team_id', '');
            $workspace_id = get_option('clickup_workspace_id', '');
            ?>
            <div class="wrap">
                <h1>ClickUp Integration Settings</h1>
                <form method="post" action="options.php" id="clickup-settings-form">
                    <?php
                    settings_fields('clickup_settings');
                    do_settings_sections('clickup_settings');
                    ?>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="clickup_api_key"><strong>API Key</strong></label></th>
                            <td>
                                <input type="password" name="clickup_api_key" id="clickup_api_key" size="50" autocomplete="off"
                                       value="<?php echo esc_attr($api_key); ?>">
                                <p class="description">Stored as plain text in the database.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="clickup_team_id"><strong>Team ID</strong></label></th>
                            <td><input type="text" name="clickup_team_id" id="clickup_team_id" size="50"
                                       value="<?php echo esc_attr($team_id); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="clickup_workspace_id"><strong>Workspace ID</strong></label></th>
                            <td><input type="text" name="clickup_workspace_id" id="clickup_workspace_id" size="50"
                                       value="<?php echo esc_attr($workspace_id); ?>"></td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }
    );
});

/**
 * Register ClickUp options (RAW)
 */
add_action('admin_init', function () {
    $trim = function ($v) {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        return is_array($v) ? $v : trim((string)$v);
    };

    register_setting('clickup_settings', 'clickup_api_key', [
        'type'              => 'string',
        'sanitize_callback' => function ($v) use ($trim) {
            $v = $trim($v);
            if ($v === '') add_settings_error('clickup_api_key', 'clickup_api_key_empty', 'API Key cannot be empty.', 'error');
            return $v;
        },
        'default' => '',
    ]);

    register_setting('clickup_settings', 'clickup_team_id', [
        'type'              => 'string',
        'sanitize_callback' => $trim,
        'default' => '',
    ]);

    register_setting('clickup_settings', 'clickup_workspace_id', [
        'type'              => 'string',
        'sanitize_callback' => $trim,
        'default' => '',
    ]);
});

/**
 * Submenu: WhatConverts (RAW)
 */
add_action('admin_menu', function () {
    add_submenu_page(
        'clickup-settings',
        'WhatConverts Settings',
        'WhatConverts',
        'manage_options',
        'whatconverts-settings',
        function () {
            if (!current_user_can('manage_options')) wp_die('Unauthorized');
            $token  = get_option('whatconverts_api_token', '');
            $secret = get_option('whatconverts_api_secret', '');
            ?>
            <div class="wrap">
                <h1>WhatConverts Integration Settings</h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('whatconverts_settings');
                    do_settings_sections('whatconverts_settings');
                    ?>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="whatconverts_api_token">API Token</label></th>
                            <td>
                                <input type="password" name="whatconverts_api_token" id="whatconverts_api_token" size="50" autocomplete="off"
                                       value="<?php echo esc_attr($token); ?>">
                                <p class="description">Stored as plain text in the database.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="whatconverts_api_secret">API Secret</label></th>
                            <td><input type="password" name="whatconverts_api_secret" id="whatconverts_api_secret" size="50" autocomplete="off"
                                       value="<?php echo esc_attr($secret); ?>"></td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }
    );
});

/**
 * Register WhatConverts options (RAW)
 */
add_action('admin_init', function () {
    $trim = function ($v) {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        return is_array($v) ? $v : trim((string)$v);
    };

    register_setting('whatconverts_settings', 'whatconverts_api_token', [
        'type'              => 'string',
        'sanitize_callback' => $trim,
        'default'           => '',
    ]);

    register_setting('whatconverts_settings', 'whatconverts_api_secret', [
        'type'              => 'string',
        'sanitize_callback' => $trim,
        'default'           => '',
    ]);
});
