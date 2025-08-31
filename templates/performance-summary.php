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
            return '<iframe src="' . $url . '" class="clickup-iframe" loading="lazy" style="width:100%; height:600px; border:none;"></iframe>';
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

.tabbed-buttons {
    display: flex;
    flex-wrap: nowrap;
    margin-bottom: 2rem;
    border: 4px solid #1f1f1f;
    border-radius: 5px;
}
.tabbed-buttons button {
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
.tabbed-buttons button.active {
    background: #121212;
    color: white;
}
.tabbed-content {
    display: none;
    padding: 2rem;
    
}
.tabbed-content.active {
    display: block;
}
</style>
    <div class="common-padding">
        <div style="display: flex;justify-content: space-between;align-items: center;padding-bottom: 2rem;">
            <div class="tab-head-button">
                <h2>Performance Summary</h2>
                <span>High-level KPIs and performance metrics</span>
            </div>
            <button id="ps" class="flex" style="justify-content: space-between;align-items: center;gap: 10px;"><span style="font-size: 1rem;">See Full Analytics Report</span>  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right w-4 h-4 ml-2" data-lov-id="src/components/portal/PerformanceSummary.tsx:81:10" data-lov-name="ArrowRight" data-component-path="src/components/portal/PerformanceSummary.tsx" data-component-line="81" data-component-file="PerformanceSummary.tsx" data-component-name="ArrowRight" data-component-content="%7B%22className%22%3A%22w-4%20h-4%20ml-2%22%7D"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg></button>
        </div>
<div class="tabbed-container">
    <!-- Tab Buttons -->
    <div class="tabbed-buttons tab-bg">
        <?php foreach ($tabs as $i => $tab): ?>
            <button class="<?php echo $i === 0 ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($tab['slug']); ?>">
               <?php echo $tab['title']; ?>

            </button>
        <?php endforeach; ?>
    </div>

    <!-- Tab Content -->
    <?php foreach ($tabs as $i => $tab): ?>
        <div class="common-bg common-border common-padding sec-common-radius tabbed-content <?php echo $i === 0 ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($tab['slug']); ?>">
            <?php echo $tab['body']; ?>
        </div>
    <?php endforeach; ?>
</div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.tabbed-buttons button');
    const contents = document.querySelectorAll('.tabbed-content');

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;

            buttons.forEach(b => b.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            btn.classList.add('active');
            document.querySelector(`.tabbed-content[data-tab="${target}"]`).classList.add('active');
        });
    });
});
</script>

<script>
  document.getElementById('ps').addEventListener('click', function () {
    // Step 1: Remove "active" class from all tab <li> elements
    document.querySelectorAll('[data-tab]').forEach(function (el) {
      el.classList.remove('active');
    });

    // Step 2: Add "active" class to the correct tab <li>
    const targetLi = document.querySelector('[data-tab="clickup-tab-4"]');
    if (targetLi) {
      targetLi.classList.add('active');
    }

    // Step 3: Hide all tab content
    document.querySelectorAll('.clickup-tab').forEach(function (tab) {
      tab.style.display = 'none';
    });

    // Step 4: Show the selected tab content
    const targetTab = document.getElementById('clickup-tab-4');
    if (targetTab) {
      targetTab.style.display = 'block';
    }
  });
</script>

