<?php
if (!isset($Parsedown)) {
    require_once plugin_dir_path(__DIR__) . 'includes/parsedown.php';
    $Parsedown = new Parsedown();
}

$user_id = get_current_user_id();
$first_name = get_user_meta($user_id, 'first_name', true);
$last_name = get_user_meta($user_id, 'last_name', true); // Added last_name
$acf_welcome = get_field('welcome', 'user_' . $user_id);
$profile_image = get_field('profile_image', 'user_' . $user_id);
$company_name = get_field('company_name', 'user_' . $user_id);
$company_logo = get_field('company_logo', 'user_' . $user_id);

$initial = $profile_image && is_array($profile_image) && isset($profile_image['url']) ? '' : strtoupper(substr($first_name, 0, 1) . ($last_name ? substr($last_name, 0, 1) : ''));
?>

<div style="padding: 2rem;">
    <div data-aos="fade-right" data-aos-delay="300" data-aos-duration="400">
    <div class="clickup-welcome-header common-padding common-border common-radius" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
<div style="display:flex;align-items:center;gap:15px;">
    <?php if (!empty($profile_image) && is_array($profile_image) && isset($profile_image['url'])): ?>
        <img src="<?php echo esc_url($profile_image['url']); ?>" 
             alt="<?php echo esc_attr($first_name ?: 'User'); ?>'s Profile Picture" 
             style="width:50px;height:50px;border-radius:50%;object-fit:cover;">
    <?php else: ?>
        <div style="width:50px;height:50px;border-radius:50%;background:#44da67;color:#000;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:bold;">
            <?php echo esc_html($initial); ?>
        </div>
    <?php endif; ?>
    
    <div>
        <h2 style="margin:0;font-size:1.875rem;line-height:2.25rem;font-weight:700;">
            Welcome back, <?php echo esc_html($first_name ?: 'there'); ?>!
        </h2>
        <p style="margin:0;font-size:1.125rem;color:#999999;line-height:1.75rem;font-weight:500;">
            Here's your marketing performance overview
        </p>
    </div>
</div>
    <div style="display:flex;align-items:center;gap:5px;flex-direction: column;">
        <?php if ($company_name): ?>
            <span style="color:#999999;font-size: .875rem;
    line-height: 1.25rem;"><?php echo esc_html($company_name); ?></span>
        <?php endif; ?>
        <?php if ($company_logo): ?>
            <img src="<?php echo esc_url($company_logo['url']); ?>" alt="Logo" style="height:40px;">
        <?php endif; ?>
    </div>
</div>
</div>


<div class="shadow common-radius common-border common-padding" data-aos="fade-right" data-aos-delay="300" data-aos-duration="800">
    <div class="flex" style="gap: 10px;align-items: center;margin-bottom: 2rem;">
        <svg style="color:#44da67;" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-play w-5 h-5 text-primary" data-lov-id="src/components/portal/WelcomeDashboard.tsx:36:12" data-lov-name="Play" data-component-path="src/components/portal/WelcomeDashboard.tsx" data-component-line="36" data-component-file="WelcomeDashboard.tsx" data-component-name="Play" data-component-content="%7B%22className%22%3A%22w-5%20h-5%20text-primary%22%7D"><polygon points="6 3 20 12 6 21 6 3"></polygon></svg>
        <h2 style="font-size: 1.7rem;margin-bottom: 0px">Monthly Strategy Update</h2>
    </div>
    <div style="border: 1px solid #2e2e2e; border-radius: 1rem;">
            <?php
    // Get ACF field (already set in your case)
    // $acf_welcome = get_field('welcome', 'user_' . $user_id); 

    if (!empty($acf_welcome)) {
        // Allow iframes + standard HTML in ACF content
        $allowed_html = wp_kses_allowed_html('post');
        $allowed_html['iframe'] = [
            'src'             => true,
            'class'           => true,
            'style'           => true,
            'loading'         => true,
            'width'          => true,
            'height'         => true,
            'frameborder'    => true,
            'allowfullscreen' => false,
        ];
        echo wp_kses($acf_welcome, $allowed_html);
    } else {
        // Parse markdown content
        $parsed_content = $Parsedown->text($content);

        // Track processed Canva URLs
        $processed_canva = [];

        // Convert Canva links to embeds (if allowed)
        $parsed_content = preg_replace_callback(
            '/<a\s+href="(https:\/\/(?:www\.)?canva\.com\/[^"]+)"[^>]*>.*?<\/a>/is',
            function ($matches) use (&$processed_canva) {
                $url = esc_url($matches[1]);
                if (!in_array($url, $processed_canva)) {
                    $processed_canva[] = $url;
                    // Use Canva's official embed method (more reliable than iframe)
                    $canva_id = basename(parse_url($url, PHP_URL_PATH));
                    return '<div class="canva-embed" data-design-id="' . esc_attr($canva_id) . '" style="height:600px;"></div>
                            <script async src="https://sdk.canva.com/v1/embed.js"></script>';
                }
                return $matches[0];
            },
            $parsed_content
        );

        // Output with iframe support
        $allowed_html = wp_kses_allowed_html('post');
        $allowed_html['iframe'] = [
            'src'             => true,
            'class'           => true,
            'style'           => true,
            'loading'         => true,
            'width'          => true,
            'height'         => true,
            'frameborder'    => true,
            'allowfullscreen' => true,
        ];
        echo wp_kses($parsed_content, $allowed_html);
    }
    ?>
    </div>
</div>
 <div style="display: flex;justify-content: space-between;align-items: center;padding-top: 2rem;">
            <div class="tab-head-button">
                <h2>Ready to dive deeper?</h2>
                <span>Explore your detailed analytics and campaign performance</span>
            </div>
            <button id="welcm" class="flex" style="justify-content: space-between;align-items: center;gap: 10px;"><span style="font-size: 1rem;">View Strategy Overview</span>  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right w-4 h-4 ml-2" data-lov-id="src/components/portal/PerformanceSummary.tsx:81:10" data-lov-name="ArrowRight" data-component-path="src/components/portal/PerformanceSummary.tsx" data-component-line="81" data-component-file="PerformanceSummary.tsx" data-component-name="ArrowRight" data-component-content="%7B%22className%22%3A%22w-4%20h-4%20ml-2%22%7D"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg></button>
        </div>
</div>

<script>
  document.getElementById('welcm').addEventListener('click', function () {
    // Step 1: Remove "active" class from all tab <li> elements
    document.querySelectorAll('[data-tab]').forEach(function (el) {
      el.classList.remove('active');
    });

    // Step 2: Add "active" class to the correct tab <li>
    const targetLi = document.querySelector('[data-tab="clickup-tab-5"]');
    if (targetLi) {
      targetLi.classList.add('active');
    }

    // Step 3: Hide all tab content
    document.querySelectorAll('.clickup-tab').forEach(function (tab) {
      tab.style.display = 'none';
    });

    // Step 4: Show the selected tab content
    const targetTab = document.getElementById('clickup-tab-5');
    if (targetTab) {
      targetTab.style.display = 'block';
    }
  });
</script>