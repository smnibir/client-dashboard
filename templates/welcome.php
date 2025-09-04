<div class="common-padding">
    
    <div style="display: flex;justify-content: center;align-items: center;padding-top: 2rem;padding-bottom: 3rem;">
        <div class="tab-head-button" style="display: flex;flex-direction: column;align-items: center;text-align: center;">
            <h1 style="margin-bottom: 5px;font-weight: 800;">Welcome to Your Marketing Portal</h1>
            <span style="">Access all your marketing tools, reports, and resources in one place.<br>Select any section below to get started.</span>
        </div>
    </div>

    <div class="home grid">
        <!-- Meeting Notes -->
        <div class="common-bg common-border common-padding common-radius home-card">
            <a href="#meeting-notes" class="home-link" data-tab="meeting-notes">
                <div>
                    <div class="data-svg">
                        <?php
                        $svg_path = plugin_dir_path(__FILE__) . '../assets/svg/notes.svg';
                        if (file_exists($svg_path)) {
                            echo file_get_contents($svg_path);
                        }
                        ?>
                    </div>
                    <h3 style="padding-top: 17px;">Meeting Notes</h3>
                    <p>View past meeting notes and upcoming agenda items</p>
                    <span class="link-text" style="display: flex;align-items: center;">
                        Go to Meeting Notes 
                        <?php
                        $svg_path = plugin_dir_path(__FILE__) . '../assets/svg/arrow.svg';
                        if (file_exists($svg_path)) {
                            echo file_get_contents($svg_path);
                        }
                        ?>
                    </span>
                </div>
            </a>
        </div>
        
        <!-- Task List -->
        <div class="common-bg common-border common-padding common-radius home-card">
            <a href="#task-list" class="home-link" data-tab="task-list">
                <div>
                    <div class="data-svg">
                        <?php
                        $svg_path = plugin_dir_path(__FILE__) . '../assets/svg/task.svg';
                        if (file_exists($svg_path)) {
                            echo file_get_contents($svg_path);
                        }
                        ?>
                    </div>
                    <h3 style="padding-top: 17px;">Task List</h3>
                    <p>Track your marketing tasks and project progress</p>
                    <span class="link-text" style="display: flex;align-items: center;">
                        Go to Task List 
                        <?php
                        $svg_path = plugin_dir_path(__FILE__) . '../assets/svg/arrow.svg';
                        if (file_exists($svg_path)) {
                            echo file_get_contents($svg_path);
                        }
                        ?>
                    </span>
                </div>
            </a>
        </div>
        
        <!-- Performance Summary -->
        <div class="common-bg common-border common-padding common-radius home-card">
            <a href="#performance-summary" class="home-link" data-tab="performance-summary">
                <div>
                    <div class="data-svg">
                        <?php
                        $svg_path = plugin_dir_path(__FILE__) . '../assets/svg/chart.svg';
                        if (file_exists($svg_path)) {
                            echo file_get_contents($svg_path);
                        }
                        ?>
                    </div>
                    <h3 style="padding-top: 17px;">Performance Summary</h3>
                    <p>Overview of key marketing metrics and KPIs</p>
                    <span class="link-text" style="display: flex;align-items: center;">
                        Go to Performance Summary 
                        <?php
                        $svg_path = plugin_dir_path(__FILE__) . '../assets/svg/arrow.svg';
                        if (file_exists($svg_path)) {
                            echo file_get_contents($svg_path);
                        }
                        ?>
                    </span>
                </div>
            </a>
        </div>
        
        <!-- Analytics Dashboard -->
        <div class="common-bg common-border common-padding common-radius home-card">
            <a href="#analytics-dashboard" class="home-link" data-tab="analytics-dashboard">
                <div>
                    <div class="data-svg">
                        <?php
                        $svg_path = plugin_dir_path(__FILE__) . '../assets/svg/analytics.svg';
                        if (file_exists($svg_path)) {
                            echo file_get_contents($svg_path);
                        }
                        ?>
                    </div>
                    <h3 style="padding-top: 17px;">Analytics Dashboard</h3>
                    <p>Detailed analytics and performance insights</p>
                    <span class="link-text" style="display: flex;align-items: center;">
                        Go to Analytics Dashboard 
                        <?php
                        $svg_path = plugin_dir_path(__FILE__) . '../assets/svg/arrow.svg';
                        if (file_exists($svg_path)) {
                            echo file_get_contents($svg_path);
                        }
                        ?>
                    </span>
                </div>
            </a>
        </div>
        
        <?php
// Example: if it's an ACF field for the user
$show_lead_tab = get_field('lead_whatsconver', 'user_' . $user_id);

// Or if it's user meta
// $show_lead_tab = get_user_meta($user_id, 'lead_whatsconver', true);

if (!empty($show_lead_tab)) : ?>
    <!-- Leads -->
    <div class="common-bg common-border common-padding common-radius home-card">
        <a href="#lead" class="home-link" data-tab="billing-payments">
            <div>
                <div class="data-svg">
                    <?php
                    $svg_path = plugin_dir_path(__FILE__) . '../assets/svg/lead.svg';
                    if (file_exists($svg_path)) {
                        echo file_get_contents($svg_path);
                    }
                    ?>
                </div>
                <h3 style="padding-top: 17px;">Lead</h3>
                <p>View leads, call recordings, source tracking, and more</p>
                <span class="link-text" style="display: flex;align-items: center;">
                    Go to Billing & Payments
                    <?php
                    $svg_path = plugin_dir_path(__FILE__) . '../assets/svg/arrow.svg';
                    if (file_exists($svg_path)) {
                        echo file_get_contents($svg_path);
                    }
                    ?>
                </span>
            </div>
        </a>
    </div>
<?php endif; ?>
        
        <!-- Campaign Strategy -->
        <div class="common-bg common-border common-padding common-radius home-card">
            <a href="#campaign-strategy" class="home-link" data-tab="campaign-strategy">
                <div>
                    <div class="data-svg">
                        <?php
                        $svg_path = plugin_dir_path(__FILE__) . '../assets/svg/target.svg';
                        if (file_exists($svg_path)) {
                            echo file_get_contents($svg_path);
                        }
                        ?>
                    </div>
                    <h3 style="padding-top: 17px;">Campaign Strategy</h3>
                    <p>Review current strategies and campaign plans</p>
                    <span class="link-text" style="display: flex;align-items: center;">
                        Go to Campaign Strategy 
                        <?php
                        $svg_path = plugin_dir_path(__FILE__) . '../assets/svg/arrow.svg';
                        if (file_exists($svg_path)) {
                            echo file_get_contents($svg_path);
                        }
                        ?>
                    </span>
                </div>
            </a>
        </div>


        
        <!-- Brand Assets & Info -->
        <div class="common-bg common-border common-padding common-radius home-card">
            <a href="#brand-assets-info" class="home-link" data-tab="brand-assets-info">
                <div>
                    <div class="data-svg">
                        <?php
                        $svg_path = plugin_dir_path(__FILE__) . '../assets/svg/brand.svg';
                        if (file_exists($svg_path)) {
                            echo file_get_contents($svg_path);
                        }
                        ?>
                    </div>
                    <h3 style="padding-top: 17px;">Brand Assets & Info</h3>
                    <p>Access your brand guidelines and marketing assets</p>
                    <span class="link-text" style="display: flex;align-items: center;">
                        Go to Brand Assets & Info 
                        <?php
                        $svg_path = plugin_dir_path(__FILE__) . '../assets/svg/arrow.svg';
                        if (file_exists($svg_path)) {
                            echo file_get_contents($svg_path);
                        }
                        ?>
                    </span>
                </div>
            </a>
        </div>
        
        <!-- Support Form -->
        <div class="common-bg common-border common-padding common-radius home-card">
            <a href="#support-form" class="home-link" data-tab="support-form">
                <div>
                    <div class="data-svg">
                        <?php
                        $svg_path = plugin_dir_path(__FILE__) . '../assets/svg/support.svg';
                        if (file_exists($svg_path)) {
                            echo file_get_contents($svg_path);
                        }
                        ?>
                    </div>
                    <h3 style="padding-top: 17px;">Support Form</h3>
                    <p>Get help and submit support requests</p>
                    <span class="link-text" style="display: flex;align-items: center;">
                        Go to Support Form 
                        <?php
                        $svg_path = plugin_dir_path(__FILE__) . '../assets/svg/arrow.svg';
                        if (file_exists($svg_path)) {
                            echo file_get_contents($svg_path);
                        }
                        ?>
                    </span>
                </div>
            </a>
        </div>
    </div>

    <?php 
    // Only show account status if function exists
    if (function_exists('bh_render_account_status')) {
        echo '<div>' . bh_render_account_status() . '</div>';
    }
    ?>
    
</div>

<style>
.home.grid {
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    display: grid;
}

@media (max-width: 1200px) {
    .home.grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 900px) {
    .home.grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .home.grid {
        grid-template-columns: 1fr;
    }
}

.home.grid p {
    color: #737373;
    margin: 10px 0;
}

.home-card {
    transition: all 0.3s ease;
    position: relative;
}

.home-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(68, 218, 103, 0.2);
    border-color: #44da67;
}

.home-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.home-card:hover .link-text {
    color: #44da67;
}

.home-card:hover .link-text svg {
    transform: translateX(5px);
}

.link-text svg {
    transition: transform 0.3s ease;
    margin-left: 8px;
}
.light-theme .link-text{
    color: #737373;
}

.data-svg {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.grid .data-svg svg {
    width: 24px;
    height: 24px;
    color:#44da67;
}

/* Light theme support */
.light-theme .home-card {
    background: #fff;
    border-color: #e5e5e5;
}

.light-theme .home-card:hover {
    border-color: #44da67;
    box-shadow: 0 10px 30px rgba(68, 218, 103, 0.15);
}

.light-theme .home.grid p {
    color: #666;
}
</style>
