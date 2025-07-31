<?php
if (!isset($Parsedown)) {
    require_once plugin_dir_path(__DIR__) . 'includes/parsedown.php';
    $Parsedown = new Parsedown();
}

$parsed_content = $Parsedown->text($content);

// Replace first instance of Canva URLs with iframe
$processed_canva = [];
$parsed_content = preg_replace_callback(
    '/<a\s+href="(https:\/\/(?:www\.)?canva\.com\/[^"]+)"[^>]*>.*?<\/a>/is',
    function ($matches) use (&$processed_canva) {
        $url = esc_url($matches[1]);
        if (!in_array($url, $processed_canva)) {
            $processed_canva[] = $url;
            return '<iframe src="' . $url . '" class="campaign-strategy-iframe" loading="lazy" style="width:100%; height:600px; border:none;"></iframe>';
        }
        return $matches[0];
    },
    $parsed_content
);

// Parse <h2> sections
preg_match_all('/<h2>(.*?)<\/h2>(.*?)(?=<h2>|$)/is', $parsed_content, $matches, PREG_SET_ORDER);

// Structure tab data
$tabs = [];
foreach ($matches as $match) {
    $title = trim($match[1]);
    $slug  = sanitize_title($title) . '-' . uniqid();
    $body  = trim($match[2]);
    $tabs[] = [
        'title' => $title,
        'slug'  => $slug,
        'body'  => $body,
    ];
}
?>

<style>
.campaign-strategy-tabbed-buttons {
    display: flex;
    flex-wrap: nowrap;
    margin-bottom: 2rem;
    border: 4px solid #1f1f1f;
}
.campaign-strategy-tabbed-buttons button {
    padding: 10px 16px;
    background: transparent;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
    color: #999;
    width: 100%;
    font-size: 1rem;
}
.campaign-strategy-tabbed-buttons button.active {
    background: #121212;
    color: white;
}
.campaign-strategy-tabbed-content {
    display: none;
}
.campaign-strategy-tabbed-content.active {
    display: block;
}
.campaign-strategy-tab-head {
    /* Add any specific styles for the tab head here */
}
.campaign-strategy-common-padding {
    /* Add any common padding styles here */
}
</style>

<div class="common-padding">

        <div class="tab-head">
            <h2>Campaign Strategy</h2>
            <span>Current campaigns and planned initiatives across all service areas</span>
        </div>
  
    <div class="campaign-strategy-tabbed-container">
        <!-- Tab Buttons -->
        <div class="campaign-strategy-tabbed-buttons tab-bg sec-common-radius">
            <?php foreach ($tabs as $i => $tab): ?>
                <button class="<?php echo $i === 0 ? 'active' : ''; ?>" data-campaign-strategy-tab="<?php echo esc_attr($tab['slug']); ?>">
                    <?php echo $tab['title']; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Tab Content -->
        <?php foreach ($tabs as $i => $tab): ?>
            <div class="common-padding sec-common-radius common-border common-padding common-bg campaign-strategy-tabbed-content <?php echo $i === 0 ? 'active' : ''; ?>" data-campaign-strategy-tab="<?php echo esc_attr($tab['slug']); ?>">
                <?php echo $tab['body']; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.campaign-strategy-tabbed-buttons button');
    const contents = document.querySelectorAll('.campaign-strategy-tabbed-content');

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-campaign-strategy-tab');

            buttons.forEach(b => b.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            btn.classList.add('active');
            document.querySelector(`.campaign-strategy-tabbed-content[data-campaign-strategy-tab="${target}"]`).classList.add('active');
        });
    });
});
</script>

