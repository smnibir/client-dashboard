<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Shortcode: [client_dashboard]
 * Renders the ClickUp Client Portal tabs with robust guards for PHP 8.2+.
 */
function render_clickup_client_dashboard() {

    // --- Helpers (ACF-aware) -------------------------------------------------
    $get_user_field = function($key, $user_id) {
        // Prefer ACF if available
        if (function_exists('get_field')) {
            $v = get_field($key, 'user_' . $user_id);
            if ($v !== null && $v !== '') return $v;
        }
        // Fallback to user meta
        $v = get_user_meta($user_id, $key, true);
        return $v ?: '';
    };

    $safe_array = function($maybe) {
        return is_array($maybe) ? $maybe : [];
    };

    // --- Current user & stored config ----------------------------------------
    $user_id      = get_current_user_id();
    $doc_id       = $get_user_field('client_portal', $user_id); // ACF (user_{$id})
    $workspace_id = get_option('clickup_workspace_id');
    $api_key      = get_option('clickup_api_key');

    // Early exit if required config missing
    if (!$doc_id || !$workspace_id || !$api_key) {
        return '<p>Click Here to login</p>';
    }

    // --- ClickUp API: Fetch pages under Client Portal doc --------------------
    $endpoint = "https://api.clickup.com/api/v3/workspaces/{$workspace_id}/docs/{$doc_id}/pages";
    $response = wp_remote_get($endpoint, [
        'headers' => [
            'Authorization' => $api_key,
        ],
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        return '<p>Error fetching ClickUp pages.</p>';
    }

    $body  = wp_remote_retrieve_body($response);
    $pages = json_decode($body, true);

    // Normalize shape: API sometimes returns { "pages": [...] }
    if (is_array($pages) && isset($pages['pages']) && is_array($pages['pages'])) {
        $pages = $pages['pages'];
    }
    if (!is_array($pages)) {
        $pages = [];
    }
    if (empty($pages)) {
        return '<p>No pages found under this Client Portal.</p>';
    }

    // --- ACF setting to show Lead tab ----------------------------------------
    // (Keep your original key; guard it if ACF not present)
    $show_lead_tab = $get_user_field('lead_whatsconver', $user_id); // truthy enables tab

    // --- Allowed tabs & ordering ---------------------------------------------
    $allowed_titles = [
        'Home',
        'Meeting Notes',
        'Task List',
        'Performance Summary',
        'Analytics Dashboard',
        'Campaign Strategy',
        'Billing & Payments',
        'Brand Assets & Info',
        'Support Form',
    ];

    // Insert "Lead" right after "Analytics Dashboard" if enabled
    if (!empty($show_lead_tab)) {
        $analytics_index = array_search('Analytics Dashboard', $allowed_titles, true);
        if ($analytics_index !== false) {
            array_splice($allowed_titles, $analytics_index + 1, 0, 'Lead');
        }
    }

    // Map tab → template file
    $template_map = [
        'Home'               => 'welcome.php',
        'Meeting Notes'         => 'meeting-notes.php',
        'Task List'             => 'task-list.php',
        'Performance Summary'   => 'performance-summary.php',
        'Analytics Dashboard'   => 'iframe.php',
        'Lead'                  => 'lead.php',
        'Campaign Strategy'     => 'campaign-strategy.php',
        'Billing & Payments'    => 'bill.php',
        'Brand Assets & Info'   => 'brand.php',
        'Support Form'          => 'iframe-support.php',
    ];

    // Map tab → SVG icon file name (from assets/svg/)
    $icons = [
        'Home'               => 'home.svg',
        'Meeting Notes'         => 'notes.svg',
        'Task List'             => 'task.svg',
        'Performance Summary'   => 'chart.svg',
        'Analytics Dashboard'   => 'analytics.svg',
        'Lead'                  => 'lead.svg',
        'Campaign Strategy'     => 'target.svg',
        'Billing & Payments'    => 'card.svg',
        'Brand Assets & Info'   => 'brand.svg',
        'Support Form'          => 'support.svg',
    ];

    // --- Filter/Order the API pages against allowed titles -------------------
    $filtered_pages = array_values(array_filter($pages, function ($page) use ($allowed_titles) {
        return is_array($page)
            && isset($page['name'])
            && in_array($page['name'], $allowed_titles, true);
    }));

    usort($filtered_pages, function ($a, $b) use ($allowed_titles) {
        $a_idx = isset($a['name']) ? array_search($a['name'], $allowed_titles, true) : PHP_INT_MAX;
        $b_idx = isset($b['name']) ? array_search($b['name'], $allowed_titles, true) : PHP_INT_MAX;
        $a_idx = ($a_idx === false) ? PHP_INT_MAX : $a_idx;
        $b_idx = ($b_idx === false) ? PHP_INT_MAX : $b_idx;
        return $a_idx <=> $b_idx;
    });

    // Add manual tabs if not in API (except Support Form which has a specific template)
    $api_titles = is_array($filtered_pages) ? wp_list_pluck($filtered_pages, 'name') : [];
    foreach ($allowed_titles as $title) {
        if (!in_array($title, $api_titles, true) && $title !== 'Support Form') {
            $filtered_pages[] = [
                'name'    => $title,
                'content' => '',
            ];
        }
    }

    // --- Extra ACF tabs after Support Form -----------------------------------
    // ACF repeater: add_custom_data (tab_name, details)
    $acf_tabs = $safe_array($get_user_field('add_custom_data', $user_id));

    // --- Parsedown (optional) -------------------------------------------------
    $Parsedown = null;
    $parsedown_path = plugin_dir_path(__DIR__) . 'includes/parsedown.php';
    if (!class_exists('Parsedown') && file_exists($parsedown_path)) {
        require_once $parsedown_path;
    }
    if (class_exists('Parsedown')) {
        $Parsedown = new Parsedown();
    }

    // --- User profile data ----------------------------------------------------
    $user          = get_userdata($user_id);
    $first_name    = $get_user_field('first_name', $user_id) ?: ($user ? $user->first_name : '');
    $last_name     = $get_user_field('last_name',  $user_id) ?: ($user ? $user->last_name  : '');
    $user_name     = trim($first_name . ' ' . $last_name);
    $profile_image = $get_user_field('profile_image', $user_id); // ACF image array expected
    $company_name  = $get_user_field('company_name',  $user_id);
    $initial       = (!$profile_image ? (mb_substr($first_name, 0, 1) . mb_substr($last_name, 0, 1)) : '');

    // --- Slugs (not strictly needed but kept) --------------------------------
    $tab_slugs = [];
    foreach ($allowed_titles as $title) {
        $tab_slugs[] = sanitize_title($title);
    }
    foreach ($acf_tabs as $acf) {
        if (is_array($acf) && !empty($acf['tab_name'])) {
            $tab_slugs[] = sanitize_title($acf['tab_name']);
        }
    }

    ob_start();
    ?>
    <div class="mobile-warning">
        <p>Use a larger screen to view the portal.<br>Stay tuned for our mobile application 🚀</p>
    </div>
    <style>
        .clickup-dashboard { display: block; }
        .mobile-warning { display: none; background: #111; color: #fff; padding: 2rem; text-align: center; font-size: 1.2rem; line-height: 1.6; }
        @media (max-width: 800px) {
            .clickup-dashboard { display: none !important; }
            .mobile-warning { display: block; }
        }
        .dropdown-item span { margin-top: 3px; }
    </style>

    <div class="clickup-dashboard">
        <div class="clickup-sidebar">
            <div class="wg-branding flex" style="justify-content: space-between;">
                <div class="flex" style="gap:4px">
                    <div>
                        <img src="<?php echo esc_url('https://green-salmon-841673.hostingersite.com/wp-content/uploads/2025/07/webgrowth.png'); ?>" alt="Webgrowth">
                    </div>
                    <div class="flex" style="flex-direction: column;">
                        <span class="common-color" style="font-size: 1.25rem;line-height: 1.8rem;font-weight: 700;letter-spacing: -.025em;">Webgrowth</span>
                        <span class="common-color" style="font-weight: 500;font-size: .8rem;line-height: 1rem;letter-spacing: -.025em;">Client Portal</span>
                    </div>
                </div>

                <button type="button" id="theme-toggle" aria-label="Toggle Theme" class="theme-toggle-button" style="max-width: 39px; padding: 0px 8px;">
                    <svg class="sun-icon h-4 w-4 rotate-0 scale-100 transition-all dark-mode-hidden" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="4"></circle>
                        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                    </svg>
                    <svg class="moon-icon h-4 w-4 rotate-90 scale-0 transition-all light-mode-hidden" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
                    </svg>
                </button>
            </div>

            <ul>
                <?php
                $tab_index = 0;
                foreach ($allowed_titles as $title) {
                    $icon      = $icons[$title] ?? '';
                    $svg_path  = plugin_dir_path(__DIR__) . 'assets/svg/' . $icon;
                    $svg_content = (is_string($icon) && $icon && file_exists($svg_path)) ? file_get_contents($svg_path) : '';

                    $tab_slug = sanitize_title($title);
                    echo "<li data-tab='clickup-tab-{$tab_index}' data-slug='{$tab_slug}'><span class='icon'>{$svg_content}</span> <span class='text-icon'>" . esc_html($title) . "</span></li>";
                    $tab_index++;

                    // Inject ACF tabs right after Support Form
                    if ($title === 'Support Form' && !empty($acf_tabs)) {
                        foreach ($acf_tabs as $acf) {
                            if (!is_array($acf) || empty($acf['tab_name'])) continue;
                            $acf_slug = sanitize_title($acf['tab_name']);
                            echo "<li data-tab='clickup-tab-{$tab_index}' data-slug='{$acf_slug}'>
                                    <span class='icon-acf'>
                                        <svg width='24' height='24' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 640'><path d='M259.1 73.5C262.1 58.7 275.2 48 290.4 48L350.2 48C365.4 48 378.5 58.7 381.5 73.5L396 143.5C410.1 149.5 423.3 157.2 435.3 166.3L503.1 143.8C517.5 139 533.3 145 540.9 158.2L570.8 210C578.4 223.2 575.7 239.8 564.3 249.9L511 297.3C511.9 304.7 512.3 312.3 512.3 320C512.3 327.7 511.8 335.3 511 342.7L564.4 390.2C575.8 400.3 578.4 417 570.9 430.1L541 481.9C533.4 495 517.6 501.1 503.2 496.3L435.4 473.8C423.3 482.9 410.1 490.5 396.1 496.6L381.7 566.5C378.6 581.4 365.5 592 350.4 592L290.6 592C275.4 592 262.3 581.3 259.3 566.5L244.9 496.6C230.8 490.6 217.7 482.9 205.6 473.8L137.5 496.3C123.1 501.1 107.3 495.1 99.7 481.9L69.8 430.1C62.2 416.9 64.9 400.3 76.3 390.2L129.7 342.7C128.8 335.3 128.4 327.7 128.4 320C128.4 312.3 128.9 304.7 129.7 297.3L76.3 249.8C64.9 239.7 62.3 223 69.8 209.9L99.7 158.1C107.3 144.9 123.1 138.9 137.5 143.7L205.3 166.2C217.4 157.1 230.6 149.5 244.6 143.4L259.1 73.5zM320.3 400C364.5 399.8 400.2 363.9 400 319.7C399.8 275.5 363.9 239.8 319.7 240C275.5 240.2 239.8 276.1 240 320.3C240.2 364.5 276.1 400.2 320.3 400z'/></svg>
                                    </span>
                                    <span class='acf-title'>" . esc_html($acf['tab_name']) . "</span>
                                  </li>";
                            $tab_index++;
                        }
                    }
                }
                ?>
            </ul>

            <!-- User Profile Section -->
            <div class="flex user-profile-section" style="position: absolute; bottom: 0rem; width: calc(100% - 3rem);">
                <div class="flex user-profile" style="align-items: center; padding: .75rem 1rem; cursor: pointer; width: 100%;">
                    <div class="profile-circle flex" style="color:#000000;width: 40px; height: 40px; border-radius: 50%; align-items: center; justify-content: center; font-weight: bold; margin-right: 10px; <?php echo empty($profile_image) ? 'background: #44da67;' : ''; ?>">
                        <?php
                        if (is_array($profile_image) && !empty($profile_image['url'])) {
                            echo '<img src="' . esc_url($profile_image['url']) . '" alt="' . esc_attr($user_name) . '" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">';
                        } else {
                            echo esc_html($initial);
                        }
                        ?>
                    </div>
                    <div class="flex user-dp" style="flex-direction: column; flex-grow: 1;">
                        <span class="text-icon" style="font-size: 1rem;margin: -5px 0"><?php echo esc_html($user_name); ?></span>
                        <span class="text-icon sec-color" style="font-size: .8rem;"><?php echo esc_html($company_name); ?></span>
                    </div>
                    <div class="dropdown-arrow flex" id="dropdown-arrow" style="font-size: 1.2rem; transition: transform 0.3s;">
                        <svg class="fill" style="width: 2rem; height: 2rem;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M297.4 201.4C309.9 188.9 330.2 188.9 342.7 201.4L502.7 361.4C515.2 373.9 515.2 394.2 502.7 406.7C490.2 419.2 469.9 419.2 457.4 406.7L320 269.3L182.6 406.6C170.1 419.1 149.8 419.1 137.3 406.6C124.8 394.1 124.8 373.8 137.3 361.3L297.3 201.3z"/></svg>
                    </div>
                </div>
                <div class="dropdown-menu" id="dropdown-menu" style="display: none; position: absolute; bottom: 100%; left: 0; background: #2e2e2e; border-radius: .75rem; width: 100%; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); z-index: 1000; margin-top: .75rem;">
                    <a href="/account/orders/" class="dropdown-item flex" data-action="portal" style="padding: .75rem 1rem; color: #ffffff; text-decoration: none; gap: 5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="20" height="20" fill="#ffffff"><path d="M64 192L64 224L576 224L576 192C576 156.7 547.3 128 512 128L128 128C92.7 128 64 156.7 64 192zM64 272L64 448C64 483.3 92.7 512 128 512L512 512C547.3 512 576 483.3 576 448L576 272L64 272zM128 424C128 410.7 138.7 400 152 400L200 400C213.3 400 224 410.7 224 424C224 437.3 213.3 448 200 448L152 448C138.7 448 128 437.3 128 424zM272 424C272 410.7 282.7 400 296 400L360 400C373.3 400 384 410.7 384 424C384 437.3 373.3 448 360 448L296 448C282.7 448 272 437.3 272 424z"/></svg>
                        <span>Payment Portal</span>
                    </a>
                    <a href="/account" class="dropdown-item flex" data-action="edit-profile" style="padding: .75rem 1rem; color: #ffffff; text-decoration: none; gap: 5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="20" height="20" fill="#ffffff"><path d="M136 192C136 125.7 189.7 72 256 72C322.3 72 376 125.7 376 192C376 258.3 322.3 312 256 312C189.7 312 136 258.3 136 192zM48 546.3C48 447.8 127.8 368 226.3 368L285.7 368C384.2 368 464 447.8 464 546.3C464 562.7 450.7 576 434.3 576L77.7 576C61.3 576 48 562.7 48 546.3zM544 160C557.3 160 568 170.7 568 184L568 232L616 232C629.3 232 640 242.7 640 256C640 269.3 629.3 280 616 280L568 280L568 328C568 341.3 557.3 352 544 352C530.7 352 520 341.3 520 328L520 280L472 280C458.7 280 448 269.3 448 256C448 242.7 458.7 232 472 232L520 232L520 184C520 170.7 530.7 160 544 160z"/></svg>
                        <span>Update Profile</span>
                    </a>
                    <a href="#" class="dropdown-item flex" data-action="logout" style="padding: .75rem 1rem; color: #ffffff; text-decoration: none; gap: 5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="20" height="20" fill="#ffffff"><path d="M224 160C241.7 160 256 145.7 256 128C256 110.3 241.7 96 224 96L160 96C107 96 64 139 64 192L64 448C64 501 107 544 160 544L224 544C241.7 544 256 529.7 256 512C256 494.3 241.7 480 224 480L160 480C142.3 480 128 465.7 128 448L128 192C128 174.3 142.3 160 160 160L224 160zM566.6 342.6C579.1 330.1 579.1 309.8 566.6 297.3L438.6 169.3C426.1 156.8 405.8 156.8 393.3 169.3C380.8 181.8 380.8 202.1 393.3 214.6L466.7 288L256 288C238.3 288 224 302.3 224 320C224 337.7 238.3 352 256 352L466.7 352L393.3 425.4C380.8 437.9 380.8 458.2 393.3 470.7C405.8 483.2 426.1 483.2 438.6 470.7L566.6 342.7z"/></svg>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="clickup-content-area">
            <?php
            $tab_index = 0;

            foreach ($allowed_titles as $title) {
                $tab_slug = sanitize_title($title);
                echo "<div id='clickup-tab-{$tab_index}' class='clickup-tab' data-slug='{$tab_slug}' style='display: none;'>";

                // Find the matching page by name
                $page = current(array_filter($filtered_pages, function ($p) use ($title) {
                    return is_array($p) && isset($p['name']) && $p['name'] === $title;
                }));

                $template = $template_map[$title] ?? null;
                $content  = is_array($page) && isset($page['content']) ? $page['content'] : '';

                // Extract first URL for iframe-type templates
                if ($template === 'iframe.php' || $template === 'iframe-support.php') {
                    $iframe_url = '';
                    if (is_string($content) && $content !== '') {
                        if (preg_match('/https?:\/\/[^\s"]+/', $content, $m)) {
                            $iframe_url = $m[0];
                        }
                    }
                    // Make $iframe_url visible to the included template
                    $iframe_url = esc_url($iframe_url);
                }

                $path = plugin_dir_path(__DIR__) . 'templates/' . $template;

                if ($template && file_exists($path)) {
                    // Templates can use: $content, $Parsedown, $iframe_url, etc.
                    include $path;
                } else {
                    echo "<p>Template missing for: " . esc_html($title) . "</p>";
                }

                echo "</div>";
                $tab_index++;

                // Extra ACF tabs after Support Form
                if ($title === 'Support Form' && !empty($acf_tabs)) {
                    foreach ($acf_tabs as $acf) {
                        if (!is_array($acf) || empty($acf['tab_name'])) continue;

                        $acf_slug    = sanitize_title($acf['tab_name']);
                        $acf_name    = $acf['tab_name'];
                        $acf_content = isset($acf['details']) ? $acf['details'] : '';

                        echo "<div id='clickup-tab-{$tab_index}' class='clickup-tab' data-slug='{$acf_slug}' style='display: none;'>";
                        // The acf.php template expects $acf_name, $acf_content (and optionally $Parsedown)
                        $acf_template = plugin_dir_path(__DIR__) . 'templates/acf.php';
                        if (file_exists($acf_template)) {
                            include $acf_template;
                        } else {
                            // Fallback render if template missing
                            echo '<div class="acf-fallback">';
                            echo '<h2>' . esc_html($acf_name) . '</h2>';
                            if ($Parsedown && is_string($acf_content)) {
                                echo $Parsedown->text($acf_content);
                            } else {
                                echo wp_kses_post(wpautop($acf_content));
                            }
                            echo '</div>';
                        }
                        echo "</div>";

                        $tab_index++;
                    }
                }
            }
            ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const body = document.body;
            const toggleButton = document.getElementById('theme-toggle');

            const savedTheme = localStorage.getItem('site-theme');
            if (savedTheme === 'light') {
                body.classList.add('light-theme');
            }

            toggleButton.addEventListener('click', () => {
                if (body.classList.contains('light-theme')) {
                    body.classList.remove('light-theme');
                    localStorage.removeItem('site-theme');
                } else {
                    body.classList.add('light-theme');
                    localStorage.setItem('site-theme', 'light');
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabs = document.querySelectorAll('.clickup-sidebar li');
            const contents = document.querySelectorAll('.clickup-tab');

            function activateTabBySlug(slug) {
                tabs.forEach(el => el.classList.remove('active'));
                contents.forEach(el => el.style.display = 'none');

                let tabFound = false;
                tabs.forEach(tab => {
                    if (tab.dataset.slug === slug) {
                        tab.classList.add('active');
                        const target = document.getElementById(tab.dataset.tab);
                        if (target) {
                            target.style.display = 'block';
                            tabFound = true;
                        }
                    }
                });

                if (!tabFound && tabs.length && contents.length) {
                    tabs[0].classList.add('active');
                    contents[0].style.display = 'block';
                }
            }

            function updateHash(slug) {
                if (history.pushState) {
                    history.pushState(null, null, '#' + slug);
                } else {
                    location.hash = '#' + slug;
                }
            }

            const initialHash = window.location.hash.substring(1);
            if (initialHash) {
                activateTabBySlug(initialHash);
            } else if (tabs.length && contents.length) {
                tabs[0].classList.add('active');
                contents[0].style.display = 'block';
            }

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    const slug = tab.dataset.slug;

                    tabs.forEach(el => el.classList.remove('active'));
                    contents.forEach(el => el.style.display = 'none');

                    tab.classList.add('active');
                    const target = document.getElementById(tab.dataset.tab);
                    if (target) target.style.display = 'block';

                    updateHash(slug);
                });
            });

            window.addEventListener('hashchange', function () {
                const hash = window.location.hash.substring(1);
                if (hash) {
                    activateTabBySlug(hash);
                }
            });

            // User profile dropdown behavior
            const userProfile   = document.querySelector('.user-profile');
            const dropdownMenu  = document.getElementById('dropdown-menu');
            const dropdownArrow = document.getElementById('dropdown-arrow');

            if (userProfile && dropdownMenu && dropdownArrow) {
                userProfile.addEventListener('click', function (e) {
                    e.preventDefault();
                    const isOpen = dropdownMenu.style.display === 'block';
                    dropdownMenu.style.display = isOpen ? 'none' : 'block';
                    dropdownArrow.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
                });

                document.addEventListener('click', function (e) {
                    if (!userProfile.contains(e.target)) {
                        dropdownMenu.style.display = 'none';
                        dropdownArrow.style.transform = 'rotate(0deg)';
                    }
                });

                document.querySelectorAll('.dropdown-item').forEach(item => {
                    item.addEventListener('click', function (e) {
                        e.preventDefault();
                        const action = this.getAttribute('data-action');
                        switch (action) {
                            case 'logout':
                                window.location.href = '<?php echo esc_js(wp_logout_url()); ?>';
                                break;
                            case 'edit-profile':
                                window.location.href = '/account';
                                break;
                            case 'portal':
                                window.location.href = '/account/orders/';
                                break;
                        }
                        dropdownMenu.style.display = 'none';
                        dropdownArrow.style.transform = 'rotate(0deg)';
                    });
                });
            }
        });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('client_dashboard', 'render_clickup_client_dashboard');
