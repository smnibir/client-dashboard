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

