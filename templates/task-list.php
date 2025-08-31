<?php
// Ensure the API Key and Folder ID are available
$api_key = get_option('clickup_api_key');
$user_id = get_current_user_id();
$folder_id = get_field('clickup_folder', 'user_' . $user_id);

if (!$api_key || !$folder_id) {
    echo '<p class="task-list-error">Missing ClickUp API Key or Folder ID</p>';
    return;
}

function get_clickup_tasks_from_folder($api_key, $folder_id) {
    $all_tasks = [];
    $list_ids = [];

    // Step 1: Get Lists in Folder
    $res_lists = wp_remote_get("https://api.clickup.com/api/v2/folder/{$folder_id}/list", [
        'headers' => ['Authorization' => $api_key]
    ]);

    $lists = json_decode(wp_remote_retrieve_body($res_lists), true)['lists'] ?? [];

    foreach ($lists as $list) {
        $list_ids[] = $list['id'];
    }

    // Step 2: Get tasks from each list (with pagination)
    foreach ($list_ids as $list_id) {
        $page = 0;
        do {
            $response = wp_remote_get("https://api.clickup.com/api/v2/list/{$list_id}/task?page={$page}&subtasks=true&include_closed=true", [
                'headers' => ['Authorization' => $api_key]
            ]);

            $tasks = json_decode(wp_remote_retrieve_body($response), true)['tasks'] ?? [];
            $all_tasks = array_merge($all_tasks, $tasks);

            $has_more = count($tasks) === 100;
            $page++;
        } while ($has_more);
    }

    return $all_tasks;
}

$tasks = get_clickup_tasks_from_folder($api_key, $folder_id);

// Extract all unique categories from tasks
$all_categories = [];
foreach ($tasks as $task) {
    foreach ($task['custom_fields'] as $field) {
        if ($field['name'] === 'Category' && isset($field['value'])) {
            $options = $field['type_config']['options'] ?? [];
            $category_name = '';
            $category_color = '#2ecd6f';
            
            if (isset($options[$field['value']])) {
                $category_name = $options[$field['value']]['name'] ?? '';
                $category_color = $options[$field['value']]['color'] ?? '#2ecd6f';
            } elseif (is_numeric($field['value']) && isset($options[(int)$field['value']])) {
                $category_name = $options[(int)$field['value']]['name'] ?? '';
                $category_color = $options[(int)$field['value']]['color'] ?? '#2ecd6f';
            }
            
            if (!empty($category_name) && !isset($all_categories[$category_name])) {
                $all_categories[$category_name] = $category_color;
            }
        }
    }
}

// Categorize tasks by status (make filtering case-insensitive)
$completed_tasks = array_filter($tasks, fn($t) => isset($t['status']['status']) && strtolower($t['status']['status']) === 'complete');
$in_progress_tasks = array_filter($tasks, fn($t) => isset($t['status']['status']) && strtolower($t['status']['status']) === 'in progress');
$upcoming_tasks = array_filter($tasks, fn($t) => 
    isset($t['status']['status']) &&
    strtolower($t['status']['status']) !== 'complete' && 
    strtolower($t['status']['status']) !== 'in progress'
);

$total_tasks = count($tasks);
$completed = count($completed_tasks);
$in_progress = count($in_progress_tasks);
$upcoming = count($upcoming_tasks);
?>

<div class="task-list-container common-padding">
    <div class="tab-head">
        <h2>Task Management</h2>
        <span>Track project progress and deliverables</span>
    </div>

    <div class="task-list-summary">
        <div class="task-summary-item common-padding sec-common-bg sec-common-radius common-border flex" data-aos="fade-right" data-aos-delay="400" data-aos-duration="800">
            <span class="badge badge-upcoming"><?= $upcoming ?></span>Upcoming
        </div>
        <div class="task-summary-item common-padding sec-common-bg sec-common-radius common-border flex" data-aos="fade-right" data-aos-delay="300" data-aos-duration="700">
            <span class="badge badge-in-progress"><?= $in_progress ?></span> In Progress
        </div>
        <div class="task-summary-item common-padding sec-common-bg sec-common-radius common-border flex" data-aos="fade-right" data-aos-delay="200" data-aos-duration="600">
            <span class="badge badge-completed"><?= $completed ?></span>Completed
        </div>
        <div class="task-summary-item common-padding sec-common-bg sec-common-radius common-border flex common-color" data-aos="fade-right" data-aos-delay="100" data-aos-duration="500">
            <span class="badge common-color"><?= $total_tasks ?></span>Total Tasks
        </div>
    </div>
<div style="display: flex;gap: 5px;">
    <div class="task-list-tabs tab-bg" style="width: 100%;">
        <button class="task-list-tab-button active" data-task-list-tab="upcoming">Upcoming</button>
        <button class="task-list-tab-button" data-task-list-tab="in-progress">In Progress</button>
        <button class="task-list-tab-button" data-task-list-tab="completed">Complete</button>
        <button class="task-list-tab-button" data-task-list-tab="all">All Tasks</button>
    </div>
        <!-- Category Filter -->
    <div class="category-filter-wrapper">
        <div class="category-filter-container">
            <button class="category-filter-btn" id="categoryFilterBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span>Category</span>
                <span class="filter-count" id="categoryFilterCount" style="display: none;">0</span>
                <svg class="chevron-icon" xmlns="http://www.w3.org/2000/svg" width="22" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div class="category-filter-dropdown" id="categoryFilterDropdown">
                <div class="category-filter-search">
                    <input type="text" id="categorySearchInput" placeholder="Search categories..." />
                </div>
                <div class="category-filter-options">
                    <?php foreach ($all_categories as $category_name => $category_color): ?>
                        <label class="category-filter-option" data-category-name="<?= esc_attr(strtolower($category_name)) ?>">
                            <input type="checkbox" value="<?= esc_attr($category_name) ?>" class="category-checkbox">
                            <span class="category-tag" style="background-color:; color:; border: ;">
                                <?= esc_html($category_name) ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button class="category-filter-clear" id="clearCategoryFilter">Clear All</button>
            </div>
        </div>
    </div>
</div>



    <div id="task-list-content">
        <!-- Upcoming Tasks Tab -->
        <div class="task-list-tab-content active" id="task-list-tab-upcoming">
            <?php if ($upcoming === 0): ?>
                <p>No upcoming tasks.</p>
            <?php else: ?>
                <?php foreach ($upcoming_tasks as $i => $task): ?>
                    <?php echo render_task_card($task, $i, $upcoming); ?>
                <?php endforeach; ?>
                <?php if ($upcoming > 20): ?>
                    <button class="task-list-load-more-btn" data-tab="upcoming">Load More</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- In Progress Tasks Tab -->
        <div class="task-list-tab-content" id="task-list-tab-in-progress">
            <?php if ($in_progress === 0): ?>
                <p>No tasks in progress.</p>
            <?php else: ?>
                <?php foreach ($in_progress_tasks as $i => $task): ?>
                    <?php echo render_task_card($task, $i, $in_progress); ?>
                <?php endforeach; ?>
                <?php if ($in_progress > 20): ?>
                    <button class="task-list-load-more-btn" data-tab="in-progress">Load More</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Completed Tasks Tab -->
        <div class="task-list-tab-content" id="task-list-tab-completed">
            <?php if ($completed === 0): ?>
                <p>No completed tasks.</p>
            <?php else: ?>
                <?php foreach ($completed_tasks as $i => $task): ?>
                    <?php echo render_task_card($task, $i, $completed); ?>
                <?php endforeach; ?>
                <?php if ($completed > 20): ?>
                    <button class="task-list-load-more-btn" data-tab="completed">Load More</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- All Tasks Tab -->
        <div class="task-list-tab-content" id="task-list-tab-all">
            <?php foreach ($tasks as $i => $task): ?>
                <?php echo render_task_card($task, $i, $total_tasks); ?>
            <?php endforeach; ?>
            <?php if ($total_tasks > 20): ?>
                <button class="task-list-load-more-btn" data-tab="all">Load More</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
function hexToRgba($hex, $alpha = 1.0) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) === 3) {
        $r = hexdec(str_repeat($hex[0], 2));
        $g = hexdec(str_repeat($hex[1], 2));
        $b = hexdec(str_repeat($hex[2], 2));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return "rgba($r, $g, $b, $alpha)";
}

function render_task_card($task, $index, $total_tasks) {
    $status = isset($task['status']['status']) ? strtolower($task['status']['status']) : 'unknown';
    $status_icon = '';
    $status_class = '';

    if ($status === 'complete') {
        $status_icon = '<span class="status-icon completed"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check w-5 h-5 text-green-500"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg></span>';
        $status_class = 'completed';
    } elseif ($status === 'in progress') {
        $status_icon = '<span class="status-icon in-progress"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock w-5 h-5 text-primary"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></span>';
        $status_class = 'in-progress';
    } else {
        $status_icon = '<span class="status-icon upcoming"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-alert w-5 h-5 text-yellow-500"><circle cx="12" cy="12" r="10"></circle><line x1="12" x2="12" y1="8" y2="12"></line><line x1="12" x2="12.01" y1="16" y2="16"></line></svg></span>';
        $status_class = 'upcoming';
    }

    $category = '';
    $category_color = '#2ecd6f';
    $priority = isset($task['priority']['priority']) ? $task['priority']['priority'] : '';
    $priority_color = isset($task['priority']['color']) ? $task['priority']['color'] : '#000000';

    foreach ($task['custom_fields'] as $field) {
        if ($field['name'] === 'Category' && isset($field['value'])) {
            $options = $field['type_config']['options'] ?? [];
            if (isset($options[$field['value']])) {
                $category = $options[$field['value']]['name'] ?? '';
                $category_color = $options[$field['value']]['color'] ?? '#000000';
            } elseif (is_numeric($field['value']) && isset($options[(int)$field['value']])) {
                $category = $options[(int)$field['value']]['name'] ?? '';
                $category_color = $options[(int)$field['value']]['color'] ?? '#000000';
            }
        }
    }

    ob_start();
    ?>
    <div class="common-border task-list-card <?= $total_tasks > 20 && $index >= 20 ? 'task-list-hidden' : '' ?>" 
         data-task-status="<?= esc_attr($status) ?>"
         data-task-category="<?= esc_attr($category) ?>"
         data-aos="fade-right" data-aos-delay="200" data-aos-duration="500">
        <div class="task-list-content">
            <div class="flex" style="align-items: center;margin-bottom:.5rem;">
                <?= $status_icon ?>
                <h3 class="task-title" style="margin-bottom: 0px;font-size: 1.4rem;"><?= esc_html($task['name']) ?></h3>
            </div>
            <div style="margin-left: 2.5rem;">
                <p class="task-description"><?= esc_html($task['description'] ?: 'No description') ?></p>
                <div class="task-meta">
                    <?php if (!empty($task['assignees'])): ?>
                        <span class="task-assignee">
                            <svg xmlns="http://www.w3.org/2000/svg" width="1.2rem" height="1.2rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user w-4 h-4 text-muted-foreground"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span><?= esc_html(implode(', ', array_column($task['assignees'], 'username'))) ?></span>
                        </span>
                    <?php endif; ?>
                    <span class="task-date">
                        <svg xmlns="http://www.w3.org/2000/svg" width="1.2rem" height="1.2rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar w-4 h-4 text-muted-foreground"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path></svg>
                        <span><?= date('Y-m-d', intval($task['date_created'] / 1000)) ?></span>
                    </span>
                </div>
            </div>
        </div>
        <div class="task-tags">
            <?php if ($category): ?>
                <span class="tag" style="background-color: rgb(46 205 111 / 10%); color: #2ecd6f; border: 1px solid #2ecd6f;">
                    <?= esc_html($category) ?>
                </span>
            <?php endif; ?>
            <?php if ($priority): ?>
                <span class="tag" style="background-color: <?= esc_attr(hexToRgba($priority_color, 0.1)) ?>; color: <?= esc_attr($priority_color) ?>; border: 1px solid <?= esc_attr($priority_color) ?>; text-transform: capitalize;">
                    <?= esc_html($priority) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>

<style>
.task-list-container {
    color: #ffffff;
}

.task-list-summary {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: nowrap;
    justify-content: space-around;
    align-items: center;
}

.task-summary-item {
    font-size: 14px;
    width: 100%;
}

.badge {
    font-size: 1.7rem;
    font-weight: 700;
}

.badge-completed {
    color: #2ecc71
}

.badge-in-progress {
    color: #2ecc71
}

.badge-upcoming {
    color: #eab308;
}

.task-list-tabs {
    display: flex;
    gap: 1px;
    margin-bottom: 1.5rem;
    border: 4px solid #1f1f1f;
    border-radius: 4px;
}

.task-list-tab-button {
    padding: 8px 16px;
    background-color: transparent;
    border: none;
    border-radius: 4px;
    color: #999999;
    cursor: pointer;
    transition: background-color 0.3s;
    width: 100%;
    font-size: 1rem;
}

.task-list-tab-button.active {
    background-color: #121212;
    color: #ffffff;
}

/* Category Filter Styles */
/*.category-filter-wrapper {*/
/*    margin-bottom: 2rem;*/
/*}*/

.category-filter-container {
    position: relative;
    display: inline-block;
}

.category-filter-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px;
    background: #161616;
    border: 1px solid #292929;
    border-radius: 8px;
    color: #fff;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.2s;
}

.category-filter-btn:hover {
    border-color: #44da67;
    background: #1a1a1a;
}

.category-filter-btn.active {
    border-color: #44da67;
    background: #1a1a1a;
    color: #fff;
}

.category-filter-btn .chevron-icon {
    margin-left: auto;
    transition: transform 0.2s;
}

.category-filter-btn.active .chevron-icon {
    transform: rotate(180deg);
}

.category-filter-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 50%;
    background: #161616;
    border: 1px solid #292929;
    border-radius: 12px;
    padding: 12px;
    min-width: 350px;
    max-height: 400px;
    /*overflow-y: auto;*/
    z-index: 1000;
    display: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.category-filter-dropdown.show {
    display: block;
}

.category-filter-search {
    margin-bottom: 12px;
}

.category-filter-search input {
    width: 100%;
    padding: 10px;
    background: #0a0a0a;
    border: 1px solid #292929;
    border-radius: 8px;
    color: #fff;
    font-size: 14px;
}

.category-filter-search input:focus {
    outline: none;
    border-color: #44da67;
}

.category-filter-options {
    max-height: 280px;
    overflow-y: auto;
}

.category-filter-option {
    display: flex;
    align-items: center;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
}
.light-theme .category-filter-option{
    color: #000000;
}

.category-filter-option:hover {
    background: #292929;
}

.light-theme .category-filter-option.selected {
    color: #fff;
}
.category-filter-option.selected {
    background: #44da67;
}

.category-filter-option input[type="checkbox"] {
    margin-right: 12px;
    width: 18px;
    height: 18px;
    cursor: pointer;
    display: none;
}

.category-tag {
    padding: 6px 12px;
    border-radius: 1rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.category-filter-clear {
    display: block;
    width: 100%;
    padding: 10px;
    margin-top: 12px;
    background: transparent;
    border: 1px solid #292929;
    border-radius: 8px;
    color: #999;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
}

.category-filter-clear:hover {
    border-color: #44da67;
    color: #44da67;
}

.filter-count {
    background: #44da67;
    color: #000;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

/* Light theme adjustments */
.light-theme .category-filter-btn {
    background: #fff;
    border-color: #e5e5e5;
    color: #000;
}

.light-theme .category-filter-btn:hover {
    background: #f5f5f5;
    border-color: #44da67;
}

.light-theme .category-filter-dropdown {
    background: #fff;
    border-color: #e5e5e5;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.light-theme .category-filter-search input {
    background: #f5f5f5;
    border-color: #e5e5e5;
    color: #000;
}

.light-theme .category-filter-option:hover {
    background: #f5f5f5;
    color: #000;
}

.light-theme .category-filter-clear {
    border-color: #e5e5e5;
    color: #666;
}

.light-theme .category-filter-clear:hover {
    border-color: #44da67;
    color: #44da67;
}

.light-theme .task-list-tabs {
    border: 4px solid #f1f1f1;
}

.light-theme .task-list-tab-button.active {
    background: #fff;
    color: #000;
}

.light-theme .task-assignee, .light-theme .task-date {
    color: #737373;
}

.task-list-tab-content {
    display: none;
}

.task-list-tab-content.active {
    display: block;
}

.task-list-card {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 15px;
    display: grid;
    align-items: start;
    grid-template-columns: 9fr 2fr;
    gap: 50px;
}

.task-list-card.filtered-out {
    display: none !important;
}

.status-icon {
    font-size: 16px;
    margin-right: 10px;
}

.status-icon.completed { color: #2ecc71; }
.status-icon.in-progress { color: #f1c40f; }
.status-icon.upcoming { color: #eab308; }

.task-title {
    font-size: 18px;
    margin: 0 0 5px 0;
}

.task-meta {
    display: flex;
    align-items: center;
    gap: 15px;
}

p.task-description {
    font-size: .9rem;
    color: #999999;
    margin-bottom: 1rem;
}

.task-assignee, .task-date {
    font-size: 1rem;
    color: #999999;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.task-tags .tag {
    padding: 4px 8px;
    border-radius: 1rem;
    font-size: .8rem;
}

.task-tags {
    min-width: 320px;
    display: flex;
    justify-content: flex-end;
    margin-top: 5px;
    gap: 5px;
}

.task-list-hidden {
    display: none;
}

.task-list-load-more-btn {
    background-color: #44da67;
    color: #000;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 20px;
    display: block;
    margin-left: auto;
    margin-right: auto;
}

.task-list-load-more-btn:hover {
    background-color: #fff;
}

@media (max-width: 768px) {
    .category-filter-btn span {
        display: none;
    }
    .category-filter-btn {
        padding: 12px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Track shown tasks for each tab
    const shownTasks = {
        'all': <?= $total_tasks <= 20 ? $total_tasks : 20 ?>,
        'completed': <?= $completed <= 20 ? $completed : 20 ?>,
        'in-progress': <?= $in_progress <= 20 ? $in_progress : 20 ?>,
        'upcoming': <?= $upcoming <= 20 ? $upcoming : 20 ?>
    };

    // Category Filter functionality
    const categoryFilterBtn = document.getElementById('categoryFilterBtn');
    const categoryFilterDropdown = document.getElementById('categoryFilterDropdown');
    const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
    const clearCategoryFilterBtn = document.getElementById('clearCategoryFilter');
    const categoryFilterCount = document.getElementById('categoryFilterCount');
    const categorySearchInput = document.getElementById('categorySearchInput');
    let selectedCategories = new Set();

    // Toggle dropdown
    categoryFilterBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        categoryFilterDropdown.classList.toggle('show');
        categoryFilterBtn.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!categoryFilterBtn.contains(e.target) && !categoryFilterDropdown.contains(e.target)) {
            categoryFilterDropdown.classList.remove('show');
            categoryFilterBtn.classList.remove('active');
        }
    });

    // Search within categories
    categorySearchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const categoryOptions = document.querySelectorAll('.category-filter-option');
        
        categoryOptions.forEach(option => {
            const categoryName = option.dataset.categoryName;
            if (categoryName.includes(searchTerm)) {
                option.style.display = 'flex';
            } else {
                option.style.display = 'none';
            }
        });
    });

    // Handle category checkbox changes
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                selectedCategories.add(this.value);
                this.parentElement.classList.add('selected');
            } else {
                selectedCategories.delete(this.value);
                this.parentElement.classList.remove('selected');
            }
            applyCategoryFilter();
            updateCategoryFilterCount();
        });
    });

    // Clear all category filters
    clearCategoryFilterBtn.addEventListener('click', function() {
        categoryCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
            checkbox.parentElement.classList.remove('selected');
        });
        selectedCategories.clear();
        applyCategoryFilter();
        updateCategoryFilterCount();
    });

    // Apply category filter
    function applyCategoryFilter() {
        const activeTab = document.querySelector('.task-list-tab-content.active');
        const taskCards = activeTab.querySelectorAll('.task-list-card');
        
        taskCards.forEach(card => {
            const taskCategory = card.dataset.taskCategory;
            
            if (selectedCategories.size === 0) {
                // Show all tasks if no category filter
                card.classList.remove('filtered-out');
            } else {
                // Show only tasks with selected categories
                if (selectedCategories.has(taskCategory)) {
                    card.classList.remove('filtered-out');
                } else {
                    card.classList.add('filtered-out');
                }
            }
        });
    }

    // Update filter count badge
    function updateCategoryFilterCount() {
        if (selectedCategories.size > 0) {
            categoryFilterCount.textContent = selectedCategories.size;
            categoryFilterCount.style.display = 'inline-block';
        } else {
            categoryFilterCount.style.display = 'none';
        }
    }

    // Tab functionality
    const tabButtons = document.querySelectorAll('.task-list-tab-button');
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and tabs
            document.querySelectorAll('.task-list-tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.task-list-tab-content').forEach(tab => tab.classList.remove('active'));
            
            // Add active class to clicked button and corresponding tab
            button.classList.add('active');
            const tabId = 'task-list-tab-' + button.getAttribute('data-task-list-tab');
            document.getElementById(tabId).classList.add('active');
            
            // Apply category filter to new tab
            applyCategoryFilter();
        });
    });

    // Load more functionality for each tab
    const loadMoreButtons = document.querySelectorAll('.task-list-load-more-btn');
    loadMoreButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.getAttribute('data-tab');
            const activeTab = document.getElementById(`task-list-tab-${tab}`);
            const cards = activeTab.querySelectorAll('.task-list-card.task-list-hidden');
            
            // Show the next 20 tasks
            for (let i = 0; i < 20 && i < cards.length; i++) {
                cards[i].classList.remove('task-list-hidden');
                shownTasks[tab]++;
            }
            
            // Apply category filter to newly shown tasks
            applyCategoryFilter();
            
            // Re-init AOS animations
            reinitializeAOS();

            // Hide the button if all tasks are shown
            if (shownTasks[tab] >= activeTab.querySelectorAll('.task-list-card').length) {
                btn.style.display = 'none';
            }
        });
    });

    // Initialize AOS on page load
    if (typeof AOS !== 'undefined') {
        AOS.init();
    }
});

function reinitializeAOS() {
    if (typeof AOS !== 'undefined') {
        AOS.refresh();
    }
}
</script>