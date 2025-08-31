<?php
$user_id = get_current_user_id();
$account_id = get_field('lead_account', 'user_' . $user_id);

if (!$account_id) {
    echo '<div class="lead-container common-padding">';
    echo '<p>No Lead account configured. Please contact your administrator.</p>';
    echo '</div>';
    return;
}
?>

<div class="lead-container common-padding">
    <div class="tab-head">
        <h2>Lead Management</h2>
        <span>Track and manage your incoming leads</span>
    </div>

    <!-- Filters Section -->
    <div class="lead-filters">
        <div class="filter-row">
            <div class="date-filter">
                <label>Date Range:</label>
                <select id="lead-date-filter">
                    <option value="7">Last 7 Days</option>
                    <option value="30" selected>Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                    <option value="365">Last Year</option>
                </select>
            </div>
            
            <div class="status-filter">
                <label>Status:</label>
                <select id="lead-status-filter">
                    <option value="">All Statuses</option>
                    <!-- Will be populated dynamically -->
                </select>
            </div>
            
            <div class="source-filter">
                <label>Source:</label>
                <select id="lead-source-filter">
                    <option value="">All Sources</option>
                    <!-- Will be populated dynamically -->
                </select>
            </div>
            
            <div class="type-filter">
                <label>Type:</label>
                <select id="lead-type-filter">
                    <option value="">All Types</option>
                    <!-- Will be populated dynamically -->
                </select>
            </div>
            
            <button id="apply-lead-filters" class="btn-primary">Apply Filters</button>
        </div>
    </div>

    <!-- Leads Table -->
    <div class="leads-table-wrapper">
        <table class="leads-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Type</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Source</th>
                    <th>Medium</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="leads-table-body">
                <!-- Leads will be loaded here via AJAX -->
            </tbody>
        </table>
        
        <div class="leads-loading" style="display: none;">
            <div class="spinner"></div>
            <p>Loading leads...</p>
        </div>
        
        <div class="leads-pagination">
            <button id="prev-page" disabled>Previous</button>
            <span id="page-info">Page 1</span>
            <button id="next-page">Next</button>
        </div>
    </div>
</div>

<!-- Lead Details Modal -->
<div id="lead-details-modal" class="modal" style="display: none;">
    <div class="modal-content lead-modal">
        <div class="modal-header">
            <h3>Lead Details</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body" id="lead-details-content">
            <!-- Lead details will be loaded here -->
        </div>
    </div>
</div>

<style>
/* Lead Dashboard Styles */
.lead-container {
    color: #ffffff;
}

.lead-filters {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #161616;
    border: 1px solid #2e2e2e;
    border-radius: 10px;
}

.filter-row {
    display: flex;
    gap: 1rem;
    align-items: end;
    flex-wrap: wrap;
}

.filter-row > div {
    flex: 1;
    min-width: 150px;
}

.filter-row label {
    display: block;
    margin-bottom: 0.5rem;
    color: #999;
    font-size: 0.875rem;
}

.filter-row select {
    width: 100%;
    padding: 10px;
    background: #0a0a0a;
    border: 1px solid #2e2e2e;
    border-radius: 6px;
    color: #fff;
}

.filter-row select:focus {
    outline: none;
    border-color: #44da67;
}

.leads-table-wrapper {
    background: #161616;
    border: 1px solid #2e2e2e;
    border-radius: 10px;
    overflow: hidden;
}

.leads-table {
    width: 100%;
    border-collapse: collapse;
}

.leads-table thead {
    background: #0a0a0a;
}

.leads-table th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #999;
    font-size: 0.875rem;
    border-bottom: 1px solid #2e2e2e;
}

.leads-table tbody tr {
    border-bottom: 1px solid #2e2e2e;
    transition: background 0.2s;
}

.leads-table tbody tr:hover {
    background: rgba(68, 218, 103, 0.05);
}

.leads-table td {
    padding: 12px;
    font-size: 0.9rem;
    color: #e5e5e5;
}

.lead-type {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.lead-type svg {
    width: 16px;
    height: 16px;
}

.lead-type.phone-call,
.lead-type.phone {
    color: #3b82f6;
}

.lead-type.web-form,
.lead-type.form {
    color: #10b981;
}

.lead-type.chat {
    color: #f59e0b;
}

.lead-status {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.lead-status.new {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.lead-status.repeat {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}

.lead-status.unique {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.btn-view-lead {
    padding: 6px 12px;
    background: transparent;
    border: 1px solid #44da67;
    color: #44da67;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.btn-view-lead:hover {
    background: #44da67;
    color: #000;
}

.leads-loading {
    text-align: center;
    padding: 3rem;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #2e2e2e;
    border-top-color: #44da67;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.leads-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
}

.leads-pagination button {
    padding: 8px 16px;
    background: #2e2e2e;
    border: none;
    border-radius: 6px;
    color: #fff;
    cursor: pointer;
    transition: all 0.2s;
}

.leads-pagination button:hover:not(:disabled) {
    background: #44da67;
    color: #000;
}

.leads-pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Lead Details Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-content {
    background: #1a1a1a;
    border: 1px solid #2e2e2e;
    border-radius: 10px;
    width: 90%;
    max-width: 900px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #2e2e2e;
}

.modal-header h3 {
    margin: 0;
    color: #fff;
}

.modal-close {
    background: none;
    border: none;
    color: #999;
    font-size: 1.5rem;
    cursor: pointer;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.modal-body {
    padding: 1.5rem;
}

.lead-modal {
    max-width: 900px;
}

.lead-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.lead-detail-item {
    padding: 1rem;
    background: #0a0a0a;
    border-radius: 8px;
    border: 1px solid #2e2e2e;
}

.lead-detail-item label {
    display: block;
    color: #999;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.lead-detail-item .value {
    color: #fff;
    font-size: 1rem;
}

.lead-detail-item .value a {
    color: #44da67;
    text-decoration: none;
}

.lead-detail-item .value a:hover {
    text-decoration: underline;
}

.lead-section {
    padding: 1.5rem;
    background: #0a0a0a;
    border-radius: 8px;
    border: 1px solid #2e2e2e;
    margin-bottom: 1rem;
}

.lead-section h4 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: #44da67;
}

/* Recording Player Styles */
.recording-player {
    background: #161616;
    border-radius: 8px;
    padding: 1.5rem;
}

.recording-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.recording-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #fff;
    font-weight: 600;
}

.recording-title svg {
    color: #44da67;
}

.recording-duration {
    color: #999;
    font-size: 0.875rem;
}

.audio-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.play-button {
    background: #44da67;
    border: none;
    color: #000;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.play-button:hover {
    background: #3bc55a;
    transform: scale(1.05);
}

.play-button svg {
    width: 24px;
    height: 24px;
    margin-left: 3px;
}

.play-button.playing svg {
    margin-left: 0;
}

.audio-progress {
    flex: 1;
    background: #2e2e2e;
    height: 6px;
    border-radius: 3px;
    position: relative;
    cursor: pointer;
}

.audio-progress-bar {
    background: #44da67;
    height: 100%;
    border-radius: 3px;
    width: 0;
    transition: width 0.1s;
}

.audio-time {
    color: #999;
    font-size: 0.875rem;
    min-width: 90px;
    text-align: right;
}

audio {
    width: 100%;
    margin-top: 1rem;
}

.transcript-box {
    background: #000;
    padding: 1rem;
    border-radius: 6px;
    max-height: 300px;
    overflow-y: auto;
    color: #e5e5e5;
    font-size: 0.9rem;
    line-height: 1.6;
}

.content-text {
    color: #e5e5e5;
    line-height: 1.6;
}

/* Light theme adjustments */
.light-theme .lead-filters {
    background: #fff;
    border-color: #e5e5e5;
}

.light-theme .filter-row select {
    background: #f5f5f5;
    border-color: #e5e5e5;
    color: #000;
}

.light-theme .leads-table-wrapper {
    background: #fff;
    border-color: #e5e5e5;
}

.light-theme .leads-table thead {
    background: #f5f5f5;
}

.light-theme .leads-table th,
.light-theme .leads-table td {
    border-color: #e5e5e5;
    color: #000;
}

.light-theme .modal-content {
    background: #fff;
    border-color: #e5e5e5;
}

.light-theme .modal-header {
    border-color: #e5e5e5;
}

.light-theme .modal-header h3 {
    color: #000;
}

.light-theme .lead-detail-item,
.light-theme .lead-section {
    background: #f5f5f5;
    border-color: #e5e5e5;
}

.light-theme .lead-detail-item .value {
    color: #000;
}

.light-theme .recording-player {
    background: #f5f5f5;
}

@media (max-width: 768px) {
    .leads-table-wrapper {
        overflow-x: auto;
    }
    
    .lead-details-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-row {
        flex-direction: column;
    }
    
    .filter-row > div {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let totalPages = 1;
    let currentFilters = {};
    let audioPlayer = null;

    // Load filters and leads on page load
    loadFilterOptions();
    loadLeads();

    // Load filter options dynamically
    function loadFilterOptions() {
        console.log('Loading filter options...');
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=fetch_lead_filters'
        })
        .then(response => response.json())
        .then(result => {
            console.log('Filter response:', result);
            
            if (result.success && result.data) {
                // Populate source filter
                const sourceFilter = document.getElementById('lead-source-filter');
                if (result.data.sources && result.data.sources.length > 0) {
                    sourceFilter.innerHTML = '<option value="">All Sources</option>';
                    result.data.sources.forEach(source => {
                        const option = document.createElement('option');
                        option.value = source;
                        option.textContent = source;
                        sourceFilter.appendChild(option);
                    });
                }
                
                // Populate status filter  
                const statusFilter = document.getElementById('lead-status-filter');
                if (result.data.statuses && result.data.statuses.length > 0) {
                    statusFilter.innerHTML = '<option value="">All Statuses</option>';
                    result.data.statuses.forEach(status => {
                        const option = document.createElement('option');
                        option.value = status;
                        option.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                        statusFilter.appendChild(option);
                    });
                }
                
                // Populate type filter
                const typeFilter = document.getElementById('lead-type-filter');
                if (result.data.types && result.data.types.length > 0) {
                    typeFilter.innerHTML = '<option value="">All Types</option>';
                    result.data.types.forEach(type => {
                        const option = document.createElement('option');
                        option.value = type;
                        option.textContent = type;
                        typeFilter.appendChild(option);
                    });
                }
            } else {
                console.error('No filter data received or error:', result);
            }
        })
        .catch(error => {
            console.error('Error loading filter options:', error);
        });
    }

    // Apply filters
    document.getElementById('apply-lead-filters').addEventListener('click', function() {
        currentPage = 1;
        currentFilters = {
            dateRange: document.getElementById('lead-date-filter').value,
            status: document.getElementById('lead-status-filter').value,
            source: document.getElementById('lead-source-filter').value,
            lead_type: document.getElementById('lead-type-filter').value
        };
        loadLeads();
    });

    // Pagination
    document.getElementById('prev-page').addEventListener('click', function() {
        if (currentPage > 1) {
            currentPage--;
            loadLeads();
        }
    });

    document.getElementById('next-page').addEventListener('click', function() {
        if (currentPage < totalPages) {
            currentPage++;
            loadLeads();
        }
    });

    // Load leads function
    function loadLeads() {
        const tableBody = document.getElementById('leads-table-body');
        const loadingDiv = document.querySelector('.leads-loading');
        
        loadingDiv.style.display = 'block';
        tableBody.innerHTML = '';

        const data = new FormData();
        data.append('action', 'fetch_whatconverts_leads');
        data.append('page', currentPage);
        
        Object.keys(currentFilters).forEach(key => {
            if (currentFilters[key]) {
                data.append(key, currentFilters[key]);
            }
        });

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(result => {
            loadingDiv.style.display = 'none';
            
            console.log('Leads response:', result);
            
            if (result.success && result.data.leads.length > 0) {
                totalPages = result.data.total_pages;
                
                // Update filters if they came with the response
                if (result.data.filters && currentPage === 1) {
                    updateFiltersFromResponse(result.data.filters);
                }
                
                result.data.leads.forEach(lead => {
                    const row = createLeadRow(lead);
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
                updatePagination();
            } else {
                tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No leads found</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading leads:', error);
            loadingDiv.style.display = 'none';
            tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: #ef4444;">Error loading leads</td></tr>';
        });
    }

    // Update filters from response (if first page)
    function updateFiltersFromResponse(filters) {
        if (!filters) return;
        
        // Update source filter if not already populated
        const sourceFilter = document.getElementById('lead-source-filter');
        if (sourceFilter.options.length <= 1 && filters.sources && filters.sources.length > 0) {
            sourceFilter.innerHTML = '<option value="">All Sources</option>';
            filters.sources.forEach(source => {
                const option = document.createElement('option');
                option.value = source;
                option.textContent = source;
                sourceFilter.appendChild(option);
            });
        }
        
        // Update status filter if not already populated
        const statusFilter = document.getElementById('lead-status-filter');
        if (statusFilter.options.length <= 1 && filters.statuses && filters.statuses.length > 0) {
            statusFilter.innerHTML = '<option value="">All Statuses</option>';
            filters.statuses.forEach(status => {
                const option = document.createElement('option');
                option.value = status;
                option.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                statusFilter.appendChild(option);
            });
        }
        
        // Update type filter if not already populated
        const typeFilter = document.getElementById('lead-type-filter');
        if (typeFilter.options.length <= 1 && filters.types && filters.types.length > 0) {
            typeFilter.innerHTML = '<option value="">All Types</option>';
            filters.types.forEach(type => {
                const option = document.createElement('option');
                option.value = type;
                option.textContent = type;
                typeFilter.appendChild(option);
            });
        }
    }

    // Create lead row HTML
    function createLeadRow(lead) {
        const date = new Date(lead.created_at);
        const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        const typeIcon = getTypeIcon(lead.lead_type);
        const statusClass = lead.lead_status.toLowerCase().replace(/\s+/g, '-');
        
        // Combine email and phone for contact column
        let contact = [];
        if (lead.email && lead.email !== '-') contact.push(lead.email);
        if (lead.phone_number && lead.phone_number !== '-') contact.push(lead.phone_number);
        const contactDisplay = contact.length > 0 ? contact.join('<br>') : '-';
        
        return `
            <tr>
                <td>${formattedDate}</td>
                <td>
                    <span class="lead-type ${lead.lead_type.toLowerCase().replace(/\s+/g, '-')}">
                        ${typeIcon}
                        ${lead.lead_type}
                    </span>
                </td>
                <td>${contactDisplay}</td>
                <td><span class="lead-status ${statusClass}">${lead.lead_status}</span></td>
                <td>${lead.source || '-'}</td>
                <td>${lead.medium || '-'}</td>
                <td>
                    <button class="btn-view-lead" data-lead-id="${lead.lead_id}">
                        View Details
                    </button>
                </td>
            </tr>
        `;
    }

    // Get type icon
    function getTypeIcon(type) {
        const typeLower = type.toLowerCase();
        
        if (typeLower.includes('phone') || typeLower.includes('call')) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>';
        } else if (typeLower.includes('form') || typeLower.includes('web')) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';
        } else if (typeLower.includes('chat')) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>';
        } else {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
        }
    }

    // Update pagination
    function updatePagination() {
        document.getElementById('page-info').textContent = `Page ${currentPage} of ${totalPages}`;
        document.getElementById('prev-page').disabled = currentPage === 1;
        document.getElementById('next-page').disabled = currentPage >= totalPages;
    }

    // View lead details
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-view-lead')) {
            const leadId = e.target.dataset.leadId;
            openLeadModal(leadId);
        }
    });

    // Format duration to readable format
    function formatDuration(seconds) {
        if (!seconds) return '0:00';
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${minutes}:${secs.toString().padStart(2, '0')}`;
    }

    // Open lead modal
    function openLeadModal(leadId) {
        const modal = document.getElementById('lead-details-modal');
        const content = document.getElementById('lead-details-content');
        
        modal.style.display = 'flex';
        content.innerHTML = '<div class="leads-loading"><div class="spinner"></div><p>Loading lead details...</p></div>';
        
        console.log('Fetching details for lead ID:', leadId);
        
        const data = new FormData();
        data.append('action', 'fetch_lead_details');
        data.append('lead_id', leadId);
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(result => {
            console.log('Lead details response:', result);
            
            if (result.success && result.data) {
                content.innerHTML = renderLeadDetails(result.data);
                
                // Initialize audio player if there's a recording
                if (result.data.recording_url) {
                    initializeAudioPlayer();
                }
            } else {
                content.innerHTML = '<p style="color: #ef4444; text-align: center;">Error loading lead details: ' + (result.data || 'Unknown error') + '</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<p style="color: #ef4444; text-align: center;">Error loading lead details</p>';
        });
    }

    // Initialize custom audio player
    function initializeAudioPlayer() {
        const audio = document.getElementById('lead-audio');
        const playBtn = document.getElementById('play-btn');
        const progressBar = document.getElementById('progress-bar');
        const progressContainer = document.getElementById('progress-container');
        const currentTimeEl = document.getElementById('current-time');
        const durationEl = document.getElementById('duration');
        
        if (!audio || !playBtn) return;
        
        // Update duration when metadata loads
        audio.addEventListener('loadedmetadata', function() {
            durationEl.textContent = formatTime(audio.duration);
        });
        
        // Play/pause functionality
        playBtn.addEventListener('click', function() {
            if (audio.paused) {
                audio.play();
                playBtn.classList.add('playing');
                playBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>';
            } else {
                audio.pause();
                playBtn.classList.remove('playing');
                playBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>';
            }
        });
        
        // Update progress bar
        audio.addEventListener('timeupdate', function() {
            const progress = (audio.currentTime / audio.duration) * 100;
            progressBar.style.width = progress + '%';
            currentTimeEl.textContent = formatTime(audio.currentTime);
        });
        
        // Seek functionality
        progressContainer.addEventListener('click', function(e) {
            const rect = progressContainer.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            audio.currentTime = percent * audio.duration;
        });
        
        // Reset when ended
        audio.addEventListener('ended', function() {
            playBtn.classList.remove('playing');
            playBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>';
            progressBar.style.width = '0%';
        });
    }
    
    // Format time helper
    function formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    // Render lead details
    function renderLeadDetails(lead) {
        console.log('Rendering lead details:', lead);
        
        // Format date if available
        let formattedDate = 'N/A';
        if (lead.date_created && lead.date_created !== 'N/A') {
            const date = new Date(lead.date_created);
            formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        }
        
        // Format landing page URL as clickable link
        let landingPageDisplay = lead.landing_page || 'N/A';
        if (lead.landing_page && lead.landing_page !== 'N/A' && lead.landing_page.startsWith('http')) {
            landingPageDisplay = `<a href="${lead.landing_page}" target="_blank" rel="noopener noreferrer">${lead.landing_page}</a>`;
        }
        
        // Format location
        let location = [];
        if (lead.city && lead.city !== 'N/A') location.push(lead.city);
        if (lead.state && lead.state !== 'N/A') location.push(lead.state);
        if (lead.country && lead.country !== 'N/A') location.push(lead.country);
        const locationDisplay = location.length > 0 ? location.join(', ') : 'N/A';
        
        let html = `
            <div class="lead-details-grid">
                <div class="lead-detail-item">
                    <label>Name</label>
                    <div class="value">${lead.contactDisplay || 'N/A'}</div>
                </div>
                <div class="lead-detail-item">
                    <label>Email</label>
                    <div class="value">${lead.email || 'N/A'}</div>
                </div>
                <div class="lead-detail-item">
                    <label>Phone</label>
                    <div class="value">${lead.phone_number || 'N/A'}</div>
                </div>
                <div class="lead-detail-item">
                    <label>Lead Type</label>
                    <div class="value">${lead.lead_type || 'N/A'}</div>
                </div>
                <div class="lead-detail-item">
                    <label>Source / Medium</label>
                    <div class="value">${lead.source || 'N/A'} / ${lead.medium || 'N/A'}</div>
                </div>
                <div class="lead-detail-item">
                    <label>Campaign</label>
                    <div class="value">${lead.campaign || 'N/A'}</div>
                </div>
                <div class="lead-detail-item">
                    <label>Landing Page</label>
                    <div class="value">${landingPageDisplay}</div>
                </div>
                <div class="lead-detail-item">
                    <label>Location</label>
                    <div class="value">${locationDisplay}</div>
                </div>
                <div class="lead-detail-item">
                    <label>IP Address</label>
                    <div class="value">${lead.ip_address || 'N/A'}</div>
                </div>
                <div class="lead-detail-item">
                    <label>Date Created</label>
                    <div class="value">${formattedDate}</div>
                </div>
            </div>
        `;
        
        // Add call recording if available
        if (lead.recording_url) {
            html += `
                <div class="lead-section">
                    <h4>Call Recording</h4>
                    <div class="recording-player">
                        <div class="recording-header">
                            <div class="recording-title">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                                Phone Call Recording
                            </div>
                            <div class="recording-duration">Duration: ${formatDuration(lead.recording_duration)}</div>
                        </div>
                        <div class="audio-controls">
                            <button class="play-button" id="play-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                </svg>
                            </button>
                            <div class="audio-progress" id="progress-container">
                                <div class="audio-progress-bar" id="progress-bar"></div>
                            </div>
                            <div class="audio-time">
                                <span id="current-time">0:00</span> / <span id="duration">${formatDuration(lead.recording_duration)}</span>
                            </div>
                        </div>
                        <audio id="lead-audio" preload="metadata">
                            <source src="${lead.recording_url}" type="audio/mpeg">
                            <source src="${lead.recording_url}" type="audio/wav">
                            <source src="${lead.recording_url}" type="audio/ogg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                </div>
            `;
        }
        
        // Add transcript if available
        if (lead.transcript) {
            html += `
                <div class="lead-section">
                    <h4>Call Transcript</h4>
                    <div class="transcript-box">
                        ${lead.transcript}
                    </div>
                </div>
            `;
        }
        
        // Add lead summary
        html += `
            <div class="lead-section">
                <h4>Lead Summary</h4>
                <div class="content-text">
                    ${lead.lead_summary || 'No summary available'}
                </div>
            </div>
        `;
        
        // Add additional fields if available
        if (lead.form_data) {
            html += `
                <div class="lead-section">
                    <h4>Additional Information</h4>
                    <pre style="color: #e5e5e5; background: #000; padding: 1rem; border-radius: 6px; overflow-x: auto; white-space: pre-wrap;">
${JSON.stringify(lead.form_data, null, 2)}
                    </pre>
                </div>
            `;
        }
        
        return html;
    }

    // Close modal
    document.querySelector('.modal-close').addEventListener('click', function() {
        document.getElementById('lead-details-modal').style.display = 'none';
        // Stop audio if playing
        const audio = document.getElementById('lead-audio');
        if (audio) {
            audio.pause();
        }
    });

    // Close modal on outside click
    document.getElementById('lead-details-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            // Stop audio if playing
            const audio = document.getElementById('lead-audio');
            if (audio) {
                audio.pause();
            }
        }
    });
});
</script>