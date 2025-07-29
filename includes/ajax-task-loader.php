<?php
add_action('wp_ajax_load_clickup_tasks', function() {
    check_ajax_referer('load_clickup_tasks', 'nonce');

    $offset = intval($_POST['offset']);
    $tasks = get_option('mock_clickup_tasks', []); // Replace with actual API call result
    $limit = 20;
    $chunk = array_slice($tasks, $offset, $limit);

    ob_start();
    foreach ($chunk as $task) {
        echo '<div class="task">' . esc_html($task['name']) . '</div>';
    }
    $html = ob_get_clean();

    wp_send_json([
        'html' => $html,
        'count' => count($chunk),
        'has_more' => ($offset + $limit) < count($tasks)
    ]);
});