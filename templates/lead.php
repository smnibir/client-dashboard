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
             
            <div class="status-filter" style="display: none;">
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
            
            <div class="type-filter" style="display: none;">
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
                    <th>Duration</th>
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
    width: 100%;
    max-width: 700px;
    max-height: 90vh;
    overflow-y: none;
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
    grid-template-columns: 50% 50%;
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

/* Recording Player Styles - Simplified */
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

.simple-audio-controls {
    display: flex;
    justify-content: center;
}

.simple-play-button {
    background: #44da67;
    border: none;
    color: #000;
    padding: 12px 24px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.2s;
}

.simple-play-button:hover {
    background: #3bc55a;
    transform: translateY(-1px);
}

.simple-play-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.simple-play-button.playing {
    background: #ef4444;
}

.simple-play-button.playing:hover {
    background: #dc2626;
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

/* Form Data Styles */
.form-data-container {
    background: #000;
    padding: 1rem;
    border-radius: 6px;
    max-height: 400px;
    overflow-y: auto;
}

.form-field-row {
    display: flex;
    margin-bottom: 0.75rem;
    align-items: flex-start;
    border-bottom: 1px solid #2e2e2e;
    padding-bottom: 0.75rem;
	flex-direction: column;
}

.form-field-row:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.form-field-label {
    font-weight: 600;
    color: #44da67;
    min-width: 150px;
    flex-shrink: 0;
    margin-right: 1rem;
    font-size: 0.9rem;
}

.form-field-value {
    color: #e5e5e5;
    flex: 1;
    word-break: break-word;
    font-size: 0.9rem;
    line-height: 1.4;
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
                tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem;">No leads found</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading leads:', error);
            loadingDiv.style.display = 'none';
            tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem; color: #ef4444;">Error loading leads</td></tr>';
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
                <td>${lead.call_duration || '-'}</td>
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
        } else if (typeLower.includes('chat') || typeLower.includes('text') || typeLower.includes('sms')) {
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
                // No need for audio player initialization - using direct links now
            } else {
                content.innerHTML = '<p style="color: #ef4444; text-align: center;">Error loading lead details: ' + (result.data || 'Unknown error') + '</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<p style="color: #ef4444; text-align: center;">Error loading lead details</p>';
        });
    }

    // Simple audio player initialization
    function initializeAudioPlayer() {
        const audio = document.getElementById('lead-audio');
        const playBtn = document.getElementById('simple-play-btn');
        
        if (!audio || !playBtn) return;
        
        console.log('Initializing simple audio player with URL:', audio.src);
        
        // Play/pause functionality
        playBtn.addEventListener('click', function() {
            if (audio.paused) {
                console.log('Playing audio...');
                playBtn.disabled = true;
                playBtn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="10 8 16 12 10 16"></polyline>
                    </svg>
                    <span>Loading...</span>
                `;
                
                audio.play().then(() => {
                    playBtn.disabled = false;
                    playBtn.classList.add('playing');
                    playBtn.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="6" y="4" width="4" height="16"></rect>
                            <rect x="14" y="4" width="4" height="16"></rect>
                        </svg>
                        <span>Stop</span>
                    `;
                }).catch((error) => {
                    console.error('Error playing audio:', error);
                    playBtn.disabled = false;
                    playBtn.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                        </svg>
                        <span>Play Recording</span>
                    `;
                    alert('Error playing recording. Please try again.');
                });
            } else {
                audio.pause();
                audio.currentTime = 0; // Reset to beginning
                playBtn.classList.remove('playing');
                playBtn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    <span>Play Recording</span>
                `;
            }
        });
        
        // Reset when audio ends
        audio.addEventListener('ended', function() {
            playBtn.classList.remove('playing');
            playBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
                <span>Play Recording</span>
            `;
        });
        
        // Handle audio errors
        audio.addEventListener('error', function() {
            console.error('Audio error occurred');
            playBtn.disabled = false;
            playBtn.classList.remove('playing');
            playBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
                <span>Play Recording</span>
            `;
        });
    }
    
    // Format time helper
    function formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    // Enhanced renderLeadDetails function with conditional sections
    function renderLeadDetails(lead) {
        console.log('Rendering lead details:', lead);
        
        // Format date if available
        let formattedDate = 'N/A';
        if (lead.date_created && lead.date_created !== 'N/A' && lead.date_created !== 'Unknown date') {
            const date = new Date(lead.date_created);
            formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        }
        
        // Format landing page URL as clickable link
        let landingPageDisplay = lead.landing_page || 'N/A';
        if (lead.landing_page && lead.landing_page !== 'N/A' && lead.landing_page !== 'No landing page' && lead.landing_page.startsWith('http')) {
            landingPageDisplay = `<a href="${lead.landing_page}" target="_blank" rel="noopener noreferrer">${lead.landing_page}</a>`;
        }
        
        // Format location
        let location = [];
        if (lead.city && lead.city !== 'N/A' && lead.city !== 'Unknown city') location.push(lead.city);
        if (lead.state && lead.state !== 'N/A' && lead.state !== 'Unknown state') location.push(lead.state);
        if (lead.country && lead.country !== 'N/A' && lead.country !== 'Unknown country') location.push(lead.country);
        const locationDisplay = location.length > 0 ? location.join(', ') : 'N/A';
        
        // Determine lead type for conditional sections
        const leadType = (lead.lead_type || '').toLowerCase();
        const isPhoneCall = leadType.includes('phone') || leadType.includes('call') || leadType.includes('voice') || leadType.includes('telephone');
        const isTextMessage = leadType.includes('text') || leadType.includes('sms') || leadType.includes('message');
        const isWebForm = leadType.includes('form') || leadType.includes('web') || leadType.includes('contact') || leadType.includes('submission');
        
        console.log('Lead type detection:', {
            leadType: lead.lead_type,
            isPhoneCall,
            isTextMessage, 
            isWebForm,
            hasRecording: !!lead.recording_url,
            hasMessage: !!(lead.message && lead.message !== 'No message' && lead.message !== 'N/A'),
            hasFormData: !!lead.form_data
        });
        
        let html = `
            <div class="lead-details-grid">
                <div class="lead-detail-item">
                    <label>Name</label>
                    <div class="value">${lead.contactDisplay && lead.contactDisplay !== 'No contact information' ? lead.contactDisplay : 'N/A'}</div>
                </div>
                <div class="lead-detail-item">
                    <label>Email</label>
                    <div class="value">${lead.email && lead.email !== 'No email available' ? lead.email : 'N/A'}</div>
                </div>
                <div class="lead-detail-item">
                    <label>Lead Type</label>
                    <div class="value">${lead.lead_type && lead.lead_type !== 'Unknown type' ? lead.lead_type : 'N/A'}</div>
                </div>
                <div class="lead-detail-item">
                    <label>Campaign</label>
                    <div class="value">${lead.campaign && lead.campaign !== 'No campaign' ? lead.campaign : 'N/A'}</div>
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
                    <div class="value">${lead.ip_address && lead.ip_address !== 'No IP address' ? lead.ip_address : 'N/A'}</div>
                </div>
                <div class="lead-detail-item">
                    <label>Date Created</label>
                    <div class="value">${formattedDate}</div>
                </div>
            </div>
        `;
        
        // CONDITIONAL SECTIONS BASED ON LEAD TYPE
        
        // 1. Phone Call Recording Section - ONLY for phone calls
        if (isPhoneCall && lead.recording_url) {
            console.log('Adding recording section for phone call');
            console.log('Recording URL being used:', lead.recording_url);
            
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
                        <div class="simple-audio-controls">
                            <a href="${lead.recording_url}" target="_blank" class="simple-play-button" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                </svg>
                                <span>Play Recording</span>
                            </a>
                            <a href="${lead.recording_url.replace('/play', '/download')}" target="_blank" class="simple-play-button" style="text-decoration: none; display: flex; align-items: center; gap: 10px; margin-left: 10px; background: #666;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                <span>Download</span>
                            </a>
                        </div>
                    </div>
                </div>
            `;
        } else if (isPhoneCall && !lead.recording_url) {
            // Show message for phone calls without recording
            html += `
                <div class="lead-section">
                    <h4>Call Recording</h4>
                    <div class="content-text" style="color: #999; font-style: italic;">
                        No recording available for this phone call.
                    </div>
                </div>
            `;
        }
        
        // 2. Call Transcript Section - for phone calls with transcript
        if (isPhoneCall && lead.transcript && lead.transcript !== 'No transcript available') {
            console.log('Adding transcript section for phone call');
            html += `
                <div class="lead-section">
                    <h4>Call Transcript</h4>
                    <div class="transcript-box">
                        ${lead.transcript}
                    </div>
                </div>
            `;
        }
        
        // 3. Text Message Section - ONLY for text messages
        if (isTextMessage && lead.message && lead.message !== 'No message' && lead.message !== 'N/A') {
            console.log('Adding message section for text message');
            html += `
                <div class="lead-section">
                    <h4>Text Message</h4>
                    <div class="transcript-box">
                        ${lead.message}
                    </div>
                </div>
            `;
        }
        
        // 4. Web Form Data Section - ONLY for web forms
        if (isWebForm && lead.form_data) {
            console.log('Adding form data section for web form');
            
            // Format form data as key-value pairs
            let formDataHtml = '';
            try {
                const formData = typeof lead.form_data === 'string' ? JSON.parse(lead.form_data) : lead.form_data;
                
                Object.entries(formData).forEach(([key, value]) => {
                    // Clean up the key by removing "(Required)" and other common suffixes
                    let cleanKey = key.replace(/\(Required\)/g, '').trim();
                    
                    formDataHtml += `
                        <div class="form-field-row">
                            <div class="form-field-label">${cleanKey}:</div>
                            <div class="form-field-value">${value || 'N/A'}</div>
                        </div>
                    `;
                });
            } catch (e) {
                // Fallback to raw display if parsing fails
                formDataHtml = `<pre style="color: #e5e5e5; background: #000; padding: 1rem; border-radius: 6px; overflow-x: auto; white-space: pre-wrap;">${JSON.stringify(lead.form_data, null, 2)}</pre>`;
            }
            
            html += `
                <div class="lead-section">
                    <h4>Additional Information</h4>
                    <div class="form-data-container">
                        ${formDataHtml}
                    </div>
                </div>
            `;
        } else if (isWebForm && !lead.form_data) {
            // Show message for web forms without additional data
            html += `
                <div class="lead-section">
                    <h4>Additional Information</h4>
                    <div class="content-text" style="color: #999; font-style: italic;">
                        No additional form data available.
                    </div>
                </div>
            `;
        }
        
        // 5. General Message Section - for non-text message types that have messages
        if (!isTextMessage && lead.message && lead.message !== 'No message' && lead.message !== 'N/A') {
            console.log('Adding general message section');
            html += `
                <div class="lead-section">
                    <h4>Message</h4>
                    <div class="transcript-box">
                        ${lead.message}
                    </div>
                </div>
            `;
        }
        
        // 6. Lead Summary - Always show if available
        // if (lead.lead_summary && lead.lead_summary !== 'No summary available') {
        //     html += `
        //         <div class="lead-section">
        //             <h4>Lead Summary</h4>
        //             <div class="content-text">
        //                 ${lead.lead_summary}
        //             </div>
        //         </div>
        //     `;
        // }
        
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