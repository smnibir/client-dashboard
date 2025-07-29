<?php
// Admin Settings Page for ClickUp API Key, Team ID, and Workspace ID
add_action('admin_menu', function() {
    add_menu_page('ClickUp Settings', 'ClickUp Settings', 'manage_options', 'clickup-settings', function() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('clickup_settings');
            do_settings_sections('clickup_settings');
            ?>
            <h2>ClickUp Integration Settings</h2>

            <label for="clickup_api_key"><strong>API Key:</strong></label><br>
            <input type="text" name="clickup_api_key" value="<?php echo esc_attr(get_option('clickup_api_key')); ?>" size="50"><br><br>

            <label for="clickup_team_id"><strong>Team ID:</strong></label><br>
            <input type="text" name="clickup_team_id" value="<?php echo esc_attr(get_option('clickup_team_id')); ?>" size="50"><br><br>

            <label for="clickup_workspace_id"><strong>Workspace ID:</strong></label><br>
            <input type="text" name="clickup_workspace_id" value="<?php echo esc_attr(get_option('clickup_workspace_id')); ?>" size="50"><br><br>

            <?php submit_button(); ?>
        </form>
        <?php
    });
});

add_action('admin_init', function() {
    register_setting('clickup_settings', 'clickup_api_key');
    register_setting('clickup_settings', 'clickup_team_id');
    register_setting('clickup_settings', 'clickup_workspace_id');
});
