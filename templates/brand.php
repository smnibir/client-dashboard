<?php
$user_id = get_current_user_id();

// Get ACF fields
$company_logo = get_field('company_logo', 'user_' . $user_id);
$company_name = get_field('company_name', 'user_' . $user_id) ?: 'Company Name';
$company_sub_title = get_field('company_sub_title', 'user_' . $user_id) ;

$color_palette = get_field('color_palate', 'user_' . $user_id) ?: [];
$typography = get_field('typography', 'user_' . $user_id) ?: [];
$download_assets = get_field('download_assets', 'user_' . $user_id) ?: [];
$team_contacts = get_field('team_contacts', 'user_' . $user_id) ?: [];
$drive_link = get_field('core_drive_link', 'user_' . $user_id) ?: [];
$asset_link = get_field('asset_drive_link', 'user_' . $user_id) ?: [];

// Function to get initials from name
function get_initials($name) {
    $words = explode(' ', trim($name));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    } else {
        return strtoupper(substr($words[0], 0, 2));
    }
}

// Function to get file type abbreviation
function get_file_type_abbr($type) {
    $words = explode(' ', trim($type));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 2));
    } else {
        return strtoupper(substr($words[0], 0, 5));
    }
}

$words = explode(' ', trim($company_name));
$initials = '';

foreach ($words as $word) {
    if (!empty($word)) {
        $initials .= strtoupper($word[0]);
    }
}
?>

<div class="brand-container common-padding">
      <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 2rem;">
        <div class="tab-head-button">
            <h2>Brand Assets & Info</h2>
            <span>Your brand guidelines, assets, and team contact information</span>
        </div>
        <div class="billing-actions">
<?php if ($drive_link): ?>
    <a class="btn-primary" href="<?php echo esc_url($drive_link); ?>" target="_blank" rel="noopener noreferrer">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-folder-open w-4 h-4 mr-2">
            <path d="m6 14 1.5-2.9A2 2 0 0 1 9.24 10H20a2 2 0 0 1 1.94 2.5l-1.54 6a2 2 0 0 1-1.95 1.5H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H18a2 2 0 0 1 2 2v2"></path>
        </svg>
        Open Google Drive
    </a>
<?php endif; ?>
        </div>
    </div>

    <div class="grid brandcolor">
            <!-- Brand Identity Section -->
    <div class="brand-section">
        <div class="section-header">
            <div class="section-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image w-5 h-5 text-primary"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect><circle cx="9" cy="9" r="2"></circle><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path></svg>
            </div>
            <h3>Brand Identity</h3>
        </div>

       <div class="flex" style="    flex-direction: column;
    align-content: center;
">
            <div class="brand-identity-card">
            <div class="logo-container">
                <?php if ($company_logo && isset($company_logo['url'])): ?>
                    <div class="logo-display">
                        <img src="<?php echo esc_url($company_logo['url']); ?>" alt="<?php echo esc_attr($company_name); ?> Logo" class="company-logo">
                    </div>
                <?php else: ?>
                    <div class="logo-placeholder">
                        <div class="placeholder-text"><?php echo $initials; ?></div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="brand-info">
                <h4><?php echo esc_html($company_name); ?></h4>
                <p><?php echo esc_html($company_sub_title); ?></p>
            </div>
        </div>
        <?php if ($asset_link): ?>
        <a class="btn btn-secondary" style=" margin: 2rem auto;" target="_blank" href="<?php echo esc_url($asset_link); ?>">

                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" x2="12" y1="15" y2="3"></line>
                    </svg>
                    Download Logo Pack
                </a>
                <?php endif; ?>
       </div>
    </div>

    <!-- Color Palette Section -->
    <?php if (!empty($color_palette)): ?>
    <div class="brand-section">
        <div class="section-header">
            <div class="section-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="13.5" cy="6.5" r=".5" fill="currentColor"></circle>
                    <circle cx="17.5" cy="10.5" r=".5" fill="currentColor"></circle>
                    <circle cx="8.5" cy="7.5" r=".5" fill="currentColor"></circle>
                    <circle cx="6.5" cy="12.5" r=".5" fill="currentColor"></circle>
                    <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"></path>
                </svg>
            </div>
            <h3>Color Palette</h3>
        </div>

        <div class="color-grid">
            <?php foreach ($color_palette as $color): ?>
                <div class="color-item">
                    <div class="color-swatch" style="background-color: <?php echo esc_attr($color['hex_color']); ?>"></div>
                    <div class="color-info">
                        <h5><?php echo esc_html($color['color_name']); ?></h5>
                        <span class="color-code"><?php echo esc_html($color['hex_color']); ?></span>
                        <span class="color-usage"><?php echo esc_html($color['use_state']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    </div>

    <!-- Typography Section -->
    <?php if (!empty($typography)): ?>
    <div class="brand-section">
        <div class="section-header">
            <div class="section-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="4 7 4 4 20 4 20 7"></polyline>
                    <line x1="9" x2="15" y1="20" y2="20"></line>
                    <line x1="12" x2="12" y1="4" y2="20"></line>
                </svg>
            </div>
            <h3>Typography</h3>
        </div>

        <div class="typography-grid">
            <?php foreach ($typography as $font): ?>
                <div class="typography-item">
                    <h5><?php echo esc_html($font['font_state']); ?></h5>
                    <div class="font-details">
                        <span class="font-name"><?php echo esc_html($font['font_name']); ?></span>
                        <span class="font-weight">Weight: <?php echo esc_html($font['font_weight']); ?></span>
                        <span class="font-usage"><?php echo esc_html($font['element_use']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Download Assets Section -->
    <?php if (!empty($download_assets)): ?>
    <div class="brand-section">
        <div class="section-header">
            <div class="section-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" x2="12" y1="15" y2="3"></line>
                </svg>
            </div>
            <h3>Download Assets</h3>
        </div>

        <div class="assets-grid">
            <?php foreach ($download_assets as $asset): ?>
                <div class="asset-item">
                    <div class="asset-icon">
                        <span class="file-type"><?php echo esc_html(get_file_type_abbr($asset['type'])); ?></span>
                       
                    </div>
                    <div class="asset-info">
                        <h5><?php echo esc_html($asset['name']); ?></h5>
                         <small style="color: #999999;"><?php echo esc_html($asset['type']); ?></small>
                    </div>
                    <div class="asset-action">
                        <?php if (!empty($asset['drive_link'])): ?>
                            <a href="<?php echo esc_url($asset['drive_link']); ?>" target="_blank" class="download-link">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" x2="12" y1="15" y2="3"></line>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Team Contacts Section -->
    <?php if (!empty($team_contacts)): ?>
    <div class="brand-section">
        <div class="section-header">
            <div class="section-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="m22 21-3-3m0 0a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"></path>
                </svg>
            </div>
            <h3>Team Contacts</h3>
        </div>

        <div class="contacts-grid">
            <?php foreach ($team_contacts as $contact): ?>
                <div class="contact-item">
                    <div class="contact-avatar">
                        <?php if (!empty($contact['profile_image']) && isset($contact['profile_image']['url'])): ?>
                            <img src="<?php echo esc_url($contact['profile_image']['url']); ?>" alt="<?php echo esc_attr($contact['name']); ?>">
                        <?php else: ?>
                            <div class="avatar-initials">
                                <?php echo esc_html(get_initials($contact['name'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="contact-info">
                        <h5><?php echo esc_html($contact['name']); ?></h5>
                        <p class="designation"><?php echo esc_html($contact['designation']); ?></p>
                        
                        <div class="contact-details">
                            <?php if (!empty($contact['email'])): ?>
                                <a href="mailto:<?php echo esc_attr($contact['email']); ?>" class="contact-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                        <path d="m22 7-10 5L2 7"></path>
                                    </svg>
                                    <?php echo esc_html($contact['email']); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($contact['phone'])): ?>
                                <a href="tel:<?php echo esc_attr($contact['phone']); ?>" class="contact-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                    </svg>
                                    <?php echo esc_html($contact['phone']); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.brand-container {
    color: #ffffff;
}
.brandcolor{
    grid-template-columns: 1fr 1fr;
    display: grid;
    gap: 2rem;
}

.brand-section {
    margin-bottom: 2rem;
    border-radius: 1rem;
    border: 1px solid #2E2E2E;
    padding: 2rem;
    background: #161616;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 1.3rem;
    justify-content: flex-start;
}

.section-icon {
    color: #44da67;
}

.section-header h3 {
    font-size: 1.7rem;
    margin: 0;
    font-weight: 700;
        
}

/* Brand Identity */
.brand-identity-card {
    display: grid;
    align-items: center;
    justify-items: center;
    border-radius: 1rem;
     border: 1px solid #2E2E2E;
     padding: 2rem;
}

.logo-container {
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-display {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    /*background: #44da67;*/
    border-radius: 12px;
}

.company-logo {
    max-width: 80px;
    max-height: 80px;
    object-fit: contain;
}

.logo-placeholder {
    width: 80px;
    height: 80px;
    background: #44da67;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.placeholder-text {
    color: #000;
    font-size: 1.5rem;
    font-weight: 700;
}

.brand-info h4 {
    font-size: 1.2rem;
    margin: .75rem auto .5rem auto;
    font-weight: 600;
    text-align: center;
}

.brand-info p {
    color: #999;
    margin:  0 auto;
    font-size: .9rem;
    text-align: center;
}


/* Color Palette */
.color-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.color-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: #1a1a1a;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #2e2e2e;
}

.color-swatch {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    /*border: 1px solid #2e2e2e;*/
    flex-shrink: 0;
}

.color-info h5 {
    margin: 0 0 0.25rem 0;
    font-size: .9rem;
    font-weight: 600;
}

.color-code {
    display: block;
    font-family: monospace;
    color: #999;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.color-usage {
    display: block;
    color: #999;
    font-size: 0.875rem;
}

/* Typography */
.typography-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.typography-item {
    background: #1a1a1a;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #2e2e2e;
    text-align: center;
}

.typography-item h5 {
    margin: 0 0 1rem 0;
    font-size: 1.2rem;
    font-weight: 600;
}

.font-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.font-name {
font-size: 1rem;
    font-weight: 500;
}

.font-weight {
    color: #999;
    font-size: 0.875rem;
}

.font-usage {
    color: #999;
    font-size: 0.875rem;
}

/* Download Assets */
.assets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.asset-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 1rem;
    align-items: center;
    background: #1a1a1a;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #2e2e2e;
}

.asset-icon {
    text-align: center;
}

.file-type {
    display: block;
    background: #44da6738;
    color: #44da67;
    width: 40px;
    height: 40px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.8rem;
    margin-bottom: 0.25rem;
}

.asset-icon small {
    color: #999;
    font-size: 0.7rem;
}

.asset-info h5 {
    margin: 0;
    font-size: 1rem;
    font-weight: 500;
}

.download-link {
    color: #44da67;
    padding: 8px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.download-link:hover {
    background: rgba(68, 218, 103, 0.1);
}

/* Team Contacts */
.contacts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.contact-item {
    background: #1a1a1a;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #2e2e2e;
        display: flex;
    align-items: flex-start;
    gap: 12px;
}

.contact-avatar {
    width: 60px;
    height: 60px;
    margin-bottom: 1rem;
}

.contact-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.avatar-initials {
    width: 60px;
    height: 60px;
    background: #44da671a;
    color: #44da67;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
}

.contact-info h5 {
    margin: 0 0 0.25rem 0;
    font-size: 1.3rem;
    font-weight: 600;
}

.designation {
    color: #999;
    margin: 0 0 1rem 0;
    font-size: 0.9rem;
}
p.designation{
    margin-bottom: .8em;
}
.contact-details {
    display: flex;
    flex-direction: row;
    gap: 0.6rem;
}

.contact-link {
    color: #999;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
    transition: color 0.3s ease;
}

.contact-link:hover {
    color: #44da67;
}

/* Responsive */
@media (max-width: 768px) {
    .brand-identity-card {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .color-grid,
    .typography-grid,
    .assets-grid,
    .contacts-grid {
        grid-template-columns: 1fr;
    }
}
</style>