<?php



// Load ClickUp Space Field
add_filter('acf/load_field/name=clickup_space', function ($field) {
    $field['choices'] = [];

    $api_key = get_option('clickup_api_key');
    if (!$api_key) return $field;

    $response = wp_remote_get("https://api.clickup.com/api/v2/team", [
        'headers' => ['Authorization' => $api_key]
    ]);

    if (is_wp_error($response)) return $field;

    $teams = json_decode(wp_remote_retrieve_body($response), true)['teams'] ?? [];

    if (empty($teams[0]['id'])) return $field;

    $team_id = $teams[0]['id'];
    $res = wp_remote_get("https://api.clickup.com/api/v2/team/$team_id/space", [
        'headers' => ['Authorization' => $api_key]
    ]);

    $spaces = json_decode(wp_remote_retrieve_body($res), true)['spaces'] ?? [];

    foreach ($spaces as $space) {
        $field['choices'][$space['id']] = $space['name'];
    }

    return $field;
});

// Load Folder Field via ACF fallback (initial load)
add_filter('acf/load_field/name=clickup_folder', function ($field) {
    $field['choices'] = [];

    $user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : get_current_user_id();
    $space_id = get_field('clickup_space', 'user_' . $user_id);

    if (!$space_id) return $field;

    $api_key = get_option('clickup_api_key');
    $res = wp_remote_get("https://api.clickup.com/api/v2/space/$space_id/folder", [
        'headers' => ['Authorization' => $api_key]
    ]);

    $folders = json_decode(wp_remote_retrieve_body($res), true)['folders'] ?? [];

    foreach ($folders as $folder) {
        $field['choices'][$folder['id']] = $folder['name'];
    }

    return $field;
});

// Load View Field (Client Portal) via fallback
add_filter('acf/load_field/name=client_portal', function ($field) {
    $field['choices'] = [];

    $user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : get_current_user_id();
    $folder_id = get_field('clickup_folder', 'user_' . $user_id);
    if (!$folder_id) return $field;

    $api_key = get_option('clickup_api_key');
    $res = wp_remote_get("https://api.clickup.com/api/v2/folder/$folder_id/view", [
        'headers' => ['Authorization' => $api_key]
    ]);

    $views = json_decode(wp_remote_retrieve_body($res), true)['views'] ?? [];

    foreach ($views as $view) {
        $field['choices'][$view['id']] = $view['name'];
    }

    return $field;
});






// Load Subfolder Field via fallback (nested folder inside folder)
add_filter('acf/load_field/name=clickup_subfolder', function ($field) {
    $field['choices'] = [];

    $user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : get_current_user_id();
    $parent_folder_id = get_field('clickup_folder', 'user_' . $user_id);
    if (!$parent_folder_id) return $field;

    $api_key = get_option('clickup_api_key');
    $res = wp_remote_get("https://api.clickup.com/api/v2/folder/{$parent_folder_id}/folder", [
        'headers' => ['Authorization' => $api_key]
    ]);

    $folders = json_decode(wp_remote_retrieve_body($res), true)['folders'] ?? [];

    foreach ($folders as $folder) {
        $field['choices'][$folder['id']] = $folder['name'];
    }

    return $field;
});
