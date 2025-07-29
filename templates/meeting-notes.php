<?php
if (!isset($Parsedown)) {
    require_once plugin_dir_path(__DIR__) . 'includes/parsedown.php';
    $Parsedown = new Parsedown();
}

// Process iframe links BEFORE Parsedown
$processed_urls = [];

// First, handle markdown style links with bold formatting **[URL](URL)**
$content = preg_replace_callback(
    '/\*\*\[(https:\/\/(?:www\.)?(?:canva\.com|reports\.webgrowth\.io)\/[^\]]+)\]\(([^\)]+)\)\*\*/is',
    function ($matches) use (&$processed_urls) {
        $url = esc_url($matches[2]); // Use the URL from the link part
        if (!in_array($url, $processed_urls)) {
            $processed_urls[] = $url;
            $iframe_class = strpos($url, 'reports.webgrowth.io') !== false ? 'report-iframe' : 'canva-iframe';
            return '<iframe src="' . $url . '" class="clickup-iframe ' . $iframe_class . '" loading="lazy" style="width:100%; height:600px; border:none;"></iframe>';
        }
        return '';
    },
    $content
);

// Handle simple bold URLs **URL**
$content = preg_replace_callback(
    '/\*\*(https:\/\/(?:www\.)?(?:canva\.com|reports\.webgrowth\.io)\/[^\*\s]+)\*\*/is',
    function ($matches) use (&$processed_urls) {
        $url = esc_url($matches[1]);
        if (!in_array($url, $processed_urls)) {
            $processed_urls[] = $url;
            $iframe_class = strpos($url, 'reports.webgrowth.io') !== false ? 'report-iframe' : 'canva-iframe';
            return '<iframe src="' . $url . '" class="clickup-iframe ' . $iframe_class . '" loading="lazy" style="width:100%; height:600px; border:none;"></iframe>';
        }
        return '';
    },
    $content
);

// Now parse with Parsedown
$parsed_content = $Parsedown->text($content);

// After Parsedown, handle any remaining HTML links
$parsed_content = preg_replace_callback(
    '/<a\s+href="(https:\/\/(?:www\.)?(?:canva\.com|reports\.webgrowth\.io)\/[^"]+)"[^>]*>.*?<\/a>/is',
    function ($matches) use (&$processed_urls) {
        $url = esc_url($matches[1]);
        if (!in_array($url, $processed_urls)) {
            $processed_urls[] = $url;
            $iframe_class = strpos($url, 'reports.webgrowth.io') !== false ? 'report-iframe' : 'canva-iframe';
            return '<iframe src="' . $url . '" class="clickup-iframe ' . $iframe_class . '" loading="lazy" style="width:100%; height:600px; border:none;"></iframe>';
        }
        return '';
    },
    $parsed_content
);

// Handle any plain URLs in the HTML
$parsed_content = preg_replace_callback(
    '/(?<!["\'>])(https:\/\/(?:www\.)?(?:canva\.com|reports\.webgrowth\.io)\/[^\s<>"\']+)(?!["\'])/i',
    function ($matches) use (&$processed_urls) {
        $url = esc_url($matches[1]);
        if (!in_array($url, $processed_urls)) {
            $processed_urls[] = $url;
            $iframe_class = strpos($url, 'reports.webgrowth.io') !== false ? 'report-iframe' : 'canva-iframe';
            return '<iframe src="' . $url . '" class="clickup-iframe ' . $iframe_class . '" loading="lazy" style="width:100%; height:600px; border:none;"></iframe>';
        }
        return $url; // Return the URL if already processed
    },
    $parsed_content
);

// Function to format the meeting date
function format_meeting_date($date_string) {
    if (preg_match('/[A-Za-z]+\s\d{1,2},\s\d{4}/', $date_string)) {
        return $date_string;
    }
    if ($timestamp = strtotime($date_string)) {
        return date('F j, Y', $timestamp);
    }
    return $date_string;
}
?>

<!-- CSS -->
<style>
.meeting-notes-container {
    margin: 0 auto;
}
.search-container {
display: flex;
align-items: center;
padding-left: 10px;

}
.search-icon{
    width: 25;
    height: 25;
    color: #999;
}
.search-icon svg{
    
    color: #999;
}
#global-search {

    padding: 12px;
color: #fff;
    border-radius: 6px;
    font-size: 16px;
    background: transparent;
}
input#global-search{
    border: 0px;
    width: 350px;
}
.search-container:focus-within {
  border: 2px solid #44da67;
}

.tabs {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}
.tab-btn {
    padding: 13px 15px;
    border: none;
        font-size: .98rem;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
    background: transparent;
    color: #ffffff;
}
.tab-btn:hover {
 color: #44da67;
    background: #292929;
}
.tab-btn.active {
    background: #44da67;
    color: #000;
}
.date-section {
    margin-bottom: 30px;
        color:#ffffff;
}
.date-header {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    align-items: center;
    gap: 8px;
}
.date-header svg {
    /*margin-right: 10px;*/
}
h2.date-title {
    font-size: 1.3rem;
    font-weight: 600;
          color:#ffffff;
    letter-spacing: -.025em;
    margin-bottom: 0px;
}
.tab-content {
    display: none;
}
.tab-content.active {
    display: block;
}
.highlight {
    background-color: #ffeb3b;
    color: #000;
    padding: 2px 4px;
    border-radius: 2px;
}
.attendees {
    color: #666;
    font-style: italic;
    margin: 5px 0 15px;
}
.clickup-iframe {
    margin: 1rem 0;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
.report-iframe {
    height: 800px !important;
}
@media (max-width: 768px) {
    .clickup-iframe {
        height: 400px !important;
    }
}
</style>
<div class="common-padding">
    <div class="tab-head">
    <h2>Meeting Notes</h2>
    <span>Review past meetings and track action items</span>
    </div>
<!-- HTML -->
<div class="meeting-notes-container">
    <!-- Search -->
 <div class="flex" style="align-items: center;gap: 10px;padding-bottom: 2rem;">
        <div class="search-container common-border common-bg common-radius">
        <span class="search-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
    </span>
    <input type="text" id="global-search" placeholder="Search meetings...">
    </div>

    <!-- Tabs -->
    <div class="tabs" id="meeting-tabs">
        <button class="tab-btn active" data-tab="all">All</button>
        <?php
        // Extract all unique tab names from h2 headings
        preg_match_all('/<h2>(.*?)<\/h2>/', $parsed_content, $matches);
        $tabs = array_unique($matches[1]);
        foreach ($tabs as $tab) {
            $slug = sanitize_title($tab);
            echo '<button class="tab-btn" data-tab="' . esc_attr($slug) . '">' . esc_html($tab) . '</button>';
        }
        ?>
    </div>
 </div>

    <!-- Meeting Content -->
    <div id="meeting-content">
        <?php
        // Split content by date sections (h1 headings)
        $date_sections = preg_split('/(<h1>.*?<\/h1>)/', $parsed_content, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        // Process each date section
        for ($i = 1; $i < count($date_sections); $i += 2) {
            $date_heading = $date_sections[$i];
            $section_content = isset($date_sections[$i + 1]) ? $date_sections[$i + 1] : '';

            if (preg_match('/<h1>(.*?)<\/h1>/', $date_heading, $date_match)) {
                $raw_date = preg_replace('/^Meeting Agenda:\s*/', '', $date_match[1]);
                $formatted_date = format_meeting_date($raw_date);

                echo '<div class="date-section common-radius common-border common-padding common-radius" data-date="' . esc_attr($formatted_date) . '">';
                echo '<div class="date-header">';
                echo '<div class="data-svg"><svg width="23" height="23" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 2V5M16 2V5M3.5 9H20.5M21 8.5V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></div>';
                echo '<h2 class="date-title">' . esc_html($formatted_date) . '</h2>';
                echo '</div>';

                // Show attendees if present (first paragraph after date)
                if (preg_match('/<p>(.*?)<\/p>/', $section_content, $attendees_match)) {
                    echo '<p class="attendees">' . esc_html($attendees_match[1]) . '</p>';
                }

                // Split section content by h2 headings
                $tab_contents = preg_split('/(<h2>.*?<\/h2>)/', $section_content, -1, PREG_SPLIT_DELIM_CAPTURE);
                
                // Process each tab section
                for ($j = 0; $j < count($tab_contents); $j++) {
                    if ($j % 2 === 0 && !empty(trim($tab_contents[$j]))) {
                        // Content before h2 or between h2 sections
                        if ($j === 0) {
                            // First content (before any h2)
                            echo '<div class="tab-content active" data-tab="all">';
                            echo $tab_contents[$j];
                            echo '</div>';
                        }
                    } else if ($j % 2 === 1) {
                        // This is an h2 heading
                        if (preg_match('/<h2>(.*?)<\/h2>/', $tab_contents[$j], $tab_match)) {
                            $tab_name = $tab_match[1];
                            $tab_slug = sanitize_title($tab_name);
                            $tab_body = isset($tab_contents[$j + 1]) ? $tab_contents[$j + 1] : '';
                            
                            echo '<div class="tab-content active" data-tab="' . esc_attr($tab_slug) . '">';
                            echo $tab_contents[$j]; // The h2
                            echo $tab_body; // The content after h2
                            echo '</div>';
                            $j++; // Skip the next iteration since we already processed the content
                        }
                    }
                }

                echo '</div>'; // Close .date-section
            }
        }
        ?>
    </div>
</div>
</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tab functionality
    const tabBtns = document.querySelectorAll('.tab-btn');
    const currentActiveTab = { value: 'all' };
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            // Update active tab button
            tabBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const tabName = this.dataset.tab;
            currentActiveTab.value = tabName;
            
            // Filter date sections
            document.querySelectorAll('.date-section').forEach(section => {
                const tabContents = section.querySelectorAll('.tab-content');
                let hasVisibleContent = false;
                
                tabContents.forEach(content => {
                    if (tabName === 'all') {
                        // Show all content
                        content.classList.add('active');
                        hasVisibleContent = true;
                    } else {
                        // Show only content with matching data-tab
                        if (content.dataset.tab === tabName) {
                            content.classList.add('active');
                            hasVisibleContent = true;
                        } else if (content.dataset.tab !== 'all') {
                            content.classList.remove('active');
                        }
                    }
                });
                
                // Show/hide entire date section based on whether it has visible content
                section.style.display = hasVisibleContent ? 'block' : 'none';
            });

            // Clear search when switching tabs
            document.getElementById('global-search').value = '';
            clearHighlights();
        });
    });

    // Search functionality
    const globalSearch = document.getElementById('global-search');
    globalSearch.addEventListener('input', function () {
        const searchTerm = this.value.trim().toLowerCase();
        
        // Clear previous highlights
        clearHighlights();

        if (searchTerm.length === 0) {
            // Reset to show content based on active tab
            document.querySelector('.tab-btn.active').click();
            return;
        }

        if (searchTerm.length > 2) {
            // Apply search within the context of the active tab
            document.querySelectorAll('.date-section').forEach(section => {
                let sectionHasMatch = false;
                const tabContents = section.querySelectorAll('.tab-content');
                
                tabContents.forEach(content => {
                    // Check if this content should be visible based on active tab
                    const isRelevantToTab = currentActiveTab.value === 'all' || 
                                          content.dataset.tab === 'all' || 
                                          content.dataset.tab === currentActiveTab.value;
                    
                    if (!isRelevantToTab) {
                        content.classList.remove('active');
                        return;
                    }
                    
                    // Store original content
                    if (!content.dataset.original) {
                        content.dataset.original = content.innerHTML;
                    }
                    
                    const originalContent = content.dataset.original;
                    const textContent = content.textContent.toLowerCase();
                    
                    if (textContent.includes(searchTerm)) {
                        // Highlight matches
                        const regex = new RegExp('(' + escapeRegExp(searchTerm) + ')', 'gi');
                        content.innerHTML = originalContent.replace(regex, '<span class="highlight">$1</span>');
                        content.classList.add('active');
                        sectionHasMatch = true;
                    } else {
                        content.classList.remove('active');
                    }
                });
                
                // Show/hide section based on search results
                section.style.display = sectionHasMatch ? 'block' : 'none';
            });
        }
    });

    function clearHighlights() {
        document.querySelectorAll('.tab-content').forEach(content => {
            if (content.dataset.original) {
                content.innerHTML = content.dataset.original;
            }
        });
    }
    
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
});
</script>