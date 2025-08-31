<?php
if (!isset($Parsedown)) {
    require_once plugin_dir_path(__DIR__) . 'includes/parsedown.php';
    $Parsedown = new Parsedown();
}

$parsed_content = $Parsedown->text($content);

// Track processed Canva URLs to prevent duplicates
$processed_canva = [];

// Replace only the FIRST instance of each Canva URL with iframe
$parsed_content = preg_replace_callback(
    '/<a\s+href="(https:\/\/(?:www\.)?canva\.com\/[^"]+)"[^>]*>.*?<\/a>/is',
    function ($matches) use (&$processed_canva) {
        $url = esc_url($matches[1]);

        if (!in_array($url, $processed_canva)) {
            $processed_canva[] = $url;
            return '<iframe src="' . $url . '" class="clickup-iframe" loading="lazy" style="width:100%; height:600px; border:none;"></iframe>';
        }

        // If already processed, return original link
        return $matches[0];
    },
    $parsed_content
);

echo $parsed_content;
?>
