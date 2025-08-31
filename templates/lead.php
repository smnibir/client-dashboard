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
                    <option value="custom">Custom Range</option>
                </select>
            </div>
            
            <div class="status-filter">
                <label>Status:</label>
                <select id="lead-status-filter">
                    <option value="">All</option>
                    <option value="new">New Lead</option>
                    <option value="repeat">Repeat Lead</option>
                    <option value="unique">Unique Lead</option>
                </select>
            </div>
            
            <div class="source-filter">
                <label>Source:</label>
                <select id="lead-source-filter">
                    <option value="">All Sources</option>
                    <option value="google">Google</option>
                    <option value="facebook">Facebook</option>
                    <option value="direct">Direct</option>
                    <option value="organic">Organic</option>
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
                    <!--<th>Email Address</th>-->
                    <th>Phone Number</th>
                    <th>Status</th>
                    <th>Source</th>
                    <th>Medium</th>
                    <!--<th>Campaign</th>-->
                    <!--<th>Lead Summary</th>-->
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
    min-width: 200px;
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

.lead-type.phone-call {
    color: #3b82f6;
}

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
.lead-modal {
    max-width: 800px;
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

.lead-timeline {
    padding: 1.5rem;
    background: #0a0a0a;
    border-radius: 8px;
    border: 1px solid #2e2e2e;
}

.lead-timeline h4 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: #44da67;
}

.timeline-item {
    padding: 1rem 0;
    border-bottom: 1px solid #2e2e2e;
}

.timeline-item:last-child {
    border-bottom: none;
}

.timeline-time {
    color: #999;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.timeline-content {
    color: #e5e5e5;
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

.light-theme .lead-detail-item {
    background: #f5f5f5;
    border-color: #e5e5e5;
}

@media (max-width: 768px) {
    .leads-table-wrapper {
        overflow-x: auto;
    }
    
    .lead-details-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let totalPages = 1;
    let currentFilters = {};

    // Load leads on page load
    loadLeads();

    // Apply filters
    document.getElementById('apply-lead-filters').addEventListener('click', function() {
        currentPage = 1;
        currentFilters = {
            dateRange: document.getElementById('lead-date-filter').value,
            status: document.getElementById('lead-status-filter').value,
            source: document.getElementById('lead-source-filter').value,
            lead_type: document.getElementById('lead-type-filter')?.value || ''
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
            
            if (result.success && result.data.leads.length > 0) {
                totalPages = result.data.total_pages;
                result.data.leads.forEach(lead => {
                    const row = createLeadRow(lead);
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
                updatePagination();
            } else {
                tableBody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 2rem;">No leads found</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading leads:', error);
            loadingDiv.style.display = 'none';
            tableBody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 2rem; color: #ef4444;">Error loading leads</td></tr>';
        });
    }

    // Create lead row HTML
    function createLeadRow(lead) {
        const date = new Date(lead.created_at);
        const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        const typeIcon = getTypeIcon(lead.lead_type);
        const statusClass = lead.lead_status.toLowerCase().replace(' ', '-');
        
        return `
            <tr>
                <td>${formattedDate}</td>
                <td>
                    <span class="lead-type ${lead.lead_type.toLowerCase().replace(' ', '-')}">
                        ${typeIcon}
                        ${lead.lead_type}
                    </span>
                </td>
              
                <td>${lead.phone_number || '-'}</td>
                <td><span class="lead-status ${statusClass}">${lead.lead_status}</span></td>
                <td>${lead.source || '-'}</td>
                <td>${lead.medium || '-'}</td>
                <td>
                    <button class="btn-view-lead" data-lead-id="${lead.lead_id}">
                        View Lead
                    </button>
                </td>
            </tr>
        `;
    }

    // Get type icon
    function getTypeIcon(type) {
        const icons = {
            'Phone Call': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>',
            'Web Form': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
            'Chat': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>'
        };
        return icons[type] || '';
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

    // Open lead modal
    function openLeadModal(leadId) {
        const modal = document.getElementById('lead-details-modal');
        const content = document.getElementById('lead-details-content');
        
        modal.style.display = 'flex';
        content.innerHTML = '<div class="leads-loading"><div class="spinner"></div><p>Loading lead details...</p></div>';
        
        const data = new FormData();
        data.append('action', 'fetch_lead_details');
        data.append('lead_id', leadId);
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                content.innerHTML = renderLeadDetails(result.data);
            } else {
                content.innerHTML = '<p style="color: #ef4444; text-align: center;">Error loading lead details: ' + result.data + '</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<p style="color: #ef4444; text-align: center;">Error loading lead details</p>';
        });
    }

    // Render lead details
    function renderLeadDetails(lead) {
        return `
            <div class="lead-details-grid">
                <div class="lead-detail-item">
                    <label>Name</label>
                    <div class="value">${lead.name || 'N/A'}</div>
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
                    <div class="value">${lead.landing_page || 'N/A'}</div>
                </div>
                <div class="lead-detail-item">
                    <label>IP Address</label>
                    <div class="value">${lead.ip_address || 'N/A'}</div>
                </div>
            </div>
            
            <div class="lead-timeline">
                <h4>Lead Summary</h4>
                <div class="timeline-content">
                    ${lead.lead_summary || 'No summary available'}
                </div>
            </div>
            
            ${lead.form_data ? `
                <div class="lead-timeline">
                    <h4>Form Data</h4>
                    <pre style="color: #e5e5e5; background: #000; padding: 1rem; border-radius: 6px; overflow-x: auto;">
${JSON.stringify(lead.form_data, null, 2)}
                    </pre>
                </div>
            ` : ''}
        `;
    }

    // Close modal
    document.querySelector('.modal-close').addEventListener('click', function() {
        document.getElementById('lead-details-modal').style.display = 'none';
    });

    // Close modal on outside click
    document.getElementById('lead-details-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});
</script>
?>