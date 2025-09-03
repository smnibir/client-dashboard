<?php
// ===== Register meta (typed, future-proof) =====
add_action('init', function () {
    register_meta('user', 'last_account_update', [
        'type'              => 'integer',
        'single'            => true,
        'show_in_rest'      => false,
        'sanitize_callback' => 'absint',
        'auth_callback'     => '__return_true',
    ]);
    register_meta('user', 'last_account_update_by', [
        'type'              => 'integer',
        'single'            => true,
        'show_in_rest'      => false,
        'sanitize_callback' => 'absint',
        'auth_callback'     => '__return_true',
    ]);
});

// Small helper so we don’t repeat ourselves
function bh_mark_user_last_updated($user_id, $by_user_id = 0) {
    if (!$user_id) return;

    $ts = current_time('timestamp');
    update_user_meta($user_id, 'last_account_update', $ts);
    if ($by_user_id) {
        update_user_meta($user_id, 'last_account_update_by', (int) $by_user_id);
    }
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[AccountStatus] Set last_account_update={$ts} for user {$user_id} by {$by_user_id}");
    }
}

/**
 * Fires when a user is updated from the profile screen by *any* user.
 * - profile_update: runs for admin editing someone else OR user editing self (in many cases)
 * - personal_options_update: specifically when a user updates their own profile
 *
 * Use current_user_can( 'edit_user', $user_id ) instead of checking edit_users.
 */
add_action('profile_update', function ($user_id, $old_user_data) {
    if (!current_user_can('edit_user', $user_id)) {
        if (defined('WP_DEBUG') && WP_DEBUG) error_log("[AccountStatus] profile_update denied for {$user_id}");
        return;
    }
    bh_mark_user_last_updated($user_id, get_current_user_id());
}, 10, 2);

add_action('personal_options_update', function ($user_id) {
    // User saving their own profile
    bh_mark_user_last_updated($user_id, get_current_user_id());
}, 10, 1);

/**
 * Catch ACF user field saves.
 * ACF uses post_id like "user_123" for user forms (both admin + front end).
 * Run after ACF writes values (priority 20+).
 */
add_action('acf/save_post', function ($post_id) {
    if (strpos($post_id, 'user_') !== 0) return;
    $user_id = (int) substr($post_id, 5);
    if (!$user_id) return;

    // Use edit_user check (works for both admins and self-edit with proper caps)
    if (!current_user_can('edit_user', $user_id)) {
        if (defined('WP_DEBUG') && WP_DEBUG) error_log("[AccountStatus] acf/save_post denied for {$user_id}");
        return;
    }
    bh_mark_user_last_updated($user_id, get_current_user_id());
}, 25);

/**
 * Initialize on new user creation so UI never shows N/A forever.
 */
add_action('user_register', function ($user_id) {
    // Note: get_current_user_id() may be 0 here (CLI/imports). It's fine.
    bh_mark_user_last_updated($user_id, get_current_user_id());
});

/**
 * (Optional) Backfill if no value exists yet.
 * Useful when you deploy this to existing sites.
 */
add_action('init', function () {
    if (!is_user_logged_in()) return;
    $uid = get_current_user_id();
    if (!$uid) return;

    $has = get_user_meta($uid, 'last_account_update', true);
    if (!$has) {
        // Use user_registered as a reasonable default
        $u = get_userdata($uid);
        if ($u && !empty($u->user_registered)) {
            $ts = strtotime(get_date_from_gmt($u->user_registered));
            if ($ts) {
                update_user_meta($uid, 'last_account_update', $ts);
                if (defined('WP_DEBUG') && WP_DEBUG) error_log("[AccountStatus] Backfilled last_account_update={$ts} for user {$uid}");
            }
        }
    }
});

/**
 * Render the status card HTML.
 */
function bh_render_account_status($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $ts = (int) get_user_meta($user_id, 'last_account_update', true);

    // If still empty, show N/A but log it so you know why
    if (!$ts && defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[AccountStatus] No last_account_update for user {$user_id}");
    }

    // Use site date format; fallback to n/j/Y
    $fmt      = get_option('date_format') ?: 'n/j/Y';
    $date_str = $ts ? wp_date($fmt, $ts) : 'N/A';

    ob_start(); ?>
    <div class="account-status-card common-bg common-border common-padding common-radius" style="display: flex;justify-content: center;flex-direction: column;align-items: center; margin-top: 2rem;">
        <h2  style="margin-bottom: 5px;">Your Account Status</h2>
        <div class="account-status-sub" style="color: #737373;">All systems are running smoothly. Last updated: <?php echo esc_html($date_str); ?></div>
    </div>
    <?php
    return ob_get_clean();
}

// Shortcode: [account_status]
add_shortcode('account_status', function ($atts) {
    $atts = shortcode_atts(['user_id' => 0], $atts);
    return bh_render_account_status((int) $atts['user_id']);
});
