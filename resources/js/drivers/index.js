/**
 * Driver Management Module
 * Handles driver listing, drawer, document approval/rejection, and suspend functionality
 */
class DriverManager {
    constructor() {
        this.selectedDriver = null;
        this.selectedRows = [];
        this.currentDocId = null;
        this.driverData = {};
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.initDriverData();
    }

    /**
     * Initialize driver data from data attributes
     */
    initDriverData() {
        const driverElements = document.querySelectorAll('[data-driver-info]');
        driverElements.forEach(el => {
            const driverId = el.dataset.driverId;
            if (driverId) {
                this.driverData[driverId] = JSON.parse(el.dataset.driverInfo);
            }
        });
    }

    /**
     * Bind all event listeners
     */
    bindEvents() {
        // Drawer open buttons
        document.querySelectorAll('[data-action="open-drawer"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const driverId = e.currentTarget.dataset.driverId;
                this.openDrawer(driverId);
            });
        });

        // Edit modal buttons
        document.querySelectorAll('[data-action="open-edit"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const driverId = e.currentTarget.dataset.driverId;
                this.openEditModal(driverId);
            });
        });

        // Close drawer button
        const closeDrawerBtn = document.getElementById('closeDrawerBtn');
        if (closeDrawerBtn) {
            closeDrawerBtn.addEventListener('click', () => this.closeDrawer());
        }

        // Close drawer on backdrop click
        const drawerOverlay = document.getElementById('drawerOverlay');
        if (drawerOverlay) {
            drawerOverlay.addEventListener('click', (e) => {
                if (e.target === drawerOverlay || e.target.id === 'drawerBackdrop') {
                    this.closeDrawer();
                }
            });
        }

        // Suspend button
        const suspendBtn = document.getElementById('suspendBtn');
        if (suspendBtn) {
            suspendBtn.addEventListener('click', () => this.toggleSuspendDriver());
        }

        // Tab switching
        document.querySelectorAll('[data-tab]').forEach(tab => {
            tab.addEventListener('click', (e) => {
                const tabName = e.currentTarget.dataset.tab;
                this.switchTab(tabName);
            });
        });

        // Reject modal
        const rejectDocBtn = document.getElementById('rejectDocBtn');
        if (rejectDocBtn) {
            rejectDocBtn.addEventListener('click', () => this.rejectDocument());
        }

        const closeRejectModal = document.getElementById('closeRejectModal');
        if (closeRejectModal) {
            closeRejectModal.addEventListener('click', () => this.closeRejectModal());
        }

        const rejectBackdrop = document.getElementById('rejectModalBackdrop');
        if (rejectBackdrop) {
            rejectBackdrop.addEventListener('click', () => this.closeRejectModal());
        }

        // Edit modal
        const closeEditBtn = document.getElementById('closeEditModalBtn');
        if (closeEditBtn) {
            closeEditBtn.addEventListener('click', () => this.closeEditModal());
        }

        const cancelEditBtn = document.getElementById('cancelEditBtn');
        if (cancelEditBtn) {
            cancelEditBtn.addEventListener('click', () => this.closeEditModal());
        }

        const editBackdrop = document.getElementById('editModalBackdrop');
        if (editBackdrop) {
            editBackdrop.addEventListener('click', () => this.closeEditModal());
        }

        const saveEditBtn = document.getElementById('saveDriverEdit');
        if (saveEditBtn) {
            saveEditBtn.addEventListener('click', () => this.saveDriverEdit());
        }

        // Row selection checkboxes
        document.querySelectorAll('.row-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const driverId = e.currentTarget.dataset.id;
                this.toggleRow(driverId);
            });
        });

        // Select all checkbox
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                this.toggleSelectAll(e.currentTarget.checked);
            });
        }

        // Filters
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', () => this.filterDrivers());
        }

        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => this.filterDrivers());
        }

        const docsFilter = document.getElementById('docsFilter');
        if (docsFilter) {
            docsFilter.addEventListener('change', () => this.filterDrivers());
        }

        // Rejection reason input
        const rejectionReason = document.getElementById('rejectionReason');
        if (rejectionReason) {
            rejectionReason.addEventListener('input', () => this.updateRejectButton());
        }
    }

    /**
     * Open driver profile drawer
     */
    openDrawer(driverId) {
        const driver = this.driverData[driverId];
        if (!driver) {
            console.error('Driver not found:', driverId);
            return;
        }

        console.log('Opening drawer for driver:', driver);
        console.log('Driver total_trips:', driver.total_trips);
        console.log('Driver statistics:', driver.statistics);

        this.selectedDriver = driver;

        document.getElementById('drawerContent').classList.remove('hidden');
        document.getElementById('drawerOverlay').classList.remove('hidden');
        
        setTimeout(() => {
            document.getElementById('drawerBackdrop').classList.remove('opacity-0');
            document.getElementById('drawerPanel').classList.remove('translate-x-full');
        }, 10);

        this.populateDrawerData(driver);
        this.switchTab('overview');
        this.renderDocuments(driver.documents || []);
        this.updateSuspendButtonState(driver);
    }

    /**
     * Populate drawer with driver data
     */
    populateDrawerData(driver) {
        const avatarEl = document.getElementById('driverAvatar');
        if (avatarEl) avatarEl.textContent = driver.full_name ? driver.full_name.substring(0, 2).toUpperCase() : '';

        const nameEl = document.getElementById('driverName');
        if (nameEl) nameEl.textContent = driver.full_name || '';

        const ratingTextEl = document.getElementById('driverRatingText');
        if (ratingTextEl) ratingTextEl.textContent = (driver.statistics?.average_rating || 0) + ' rating';

        const vehicleEl = document.getElementById('driverVehicle');
        if (vehicleEl) vehicleEl.textContent = (driver.vehicle?.make || '') + ' ' + (driver.vehicle?.model || '');

        const phoneEl = document.getElementById('driverPhone');
        if (phoneEl) phoneEl.textContent = driver.mobile_number || '';

        const vehicleDetailEl = document.getElementById('driverVehicleDetail');
        if (vehicleDetailEl) vehicleDetailEl.textContent = (driver.vehicle?.make || '') + ' ' + (driver.vehicle?.model || 'Not Set');

        const tripsEl = document.getElementById('statTrips');
        if (tripsEl) tripsEl.textContent = driver.total_trips || driver.statistics?.total_trips || 0;

        const ratingEl = document.getElementById('statRating');
        if (ratingEl) ratingEl.textContent = driver.average_rating || driver.statistics?.average_rating || 0;

        const earningsEl = document.getElementById('statEarnings');
        if (earningsEl) earningsEl.textContent = 'PKR ' + (driver.total_earnings || driver.statistics?.total_earnings || 0);

        // Status badge
        const statusEl = document.getElementById('driverStatus');
        if (statusEl) {
            if (driver.status === 'suspended') {
                statusEl.className = 'px-2.5 py-1 text-[9px] font-black uppercase tracking-widest rounded-full bg-red-50 text-red-600 border border-red-100';
                statusEl.textContent = 'SUSPENDED';
            } else {
                statusEl.className = 'px-2.5 py-1 text-[9px] font-black uppercase tracking-widest rounded-full bg-green-50 text-green-600 border border-green-100';
                statusEl.textContent = 'ACTIVE';
            }
        }
    }

    /**
     * Update suspend button state based on driver status
     */
    updateSuspendButtonState(driver) {
        const suspendBtn = document.getElementById('suspendBtn');
        if (!suspendBtn) return;

        if (driver.status === 'suspended') {
            suspendBtn.textContent = 'Unsuspend Driver';
            suspendBtn.classList.remove('bg-[#1C69D4]');
            suspendBtn.classList.add('bg-green-600');
        } else {
            suspendBtn.textContent = 'Suspend Driver';
            suspendBtn.classList.remove('bg-green-600');
            suspendBtn.classList.add('bg-[#1C69D4]');
        }
    }

    /**
     * Close drawer
     */
    closeDrawer() {
        document.getElementById('drawerPanel').classList.add('translate-x-full');
        document.getElementById('drawerBackdrop').classList.add('opacity-0');
        setTimeout(() => {
            document.getElementById('drawerOverlay').classList.add('hidden');
            document.getElementById('drawerContent').classList.add('hidden');
        }, 300);
        this.selectedDriver = null;
    }

    /**
     * Switch tabs in drawer
     */
    switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('[data-tab]').forEach(btn => {
            btn.classList.remove('text-[#1C69D4]', 'border-[#1C69D4]');
            btn.classList.add('text-gray-400', 'border-transparent');
        });
        
        const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
        if (activeTab) {
            activeTab.classList.remove('text-gray-400', 'border-transparent');
            activeTab.classList.add('text-[#1C69D4]', 'border-[#1C69D4]');
        }

        // Show/hide panels
        document.querySelectorAll('.tab-panel').forEach(panel => {
            panel.classList.add('hidden');
        });
        
        const activePanel = document.getElementById(`panel-${tabName}`);
        if (activePanel) activePanel.classList.remove('hidden');
    }

    /**
     * Render documents list
     */
    renderDocuments(docs) {
        const list = document.getElementById('documentsList');
        const noMsg = document.getElementById('noDocumentsMsg');

        if (!list || !noMsg) return;

        if (!docs || docs.length === 0) {
            list.innerHTML = '';
            noMsg.classList.remove('hidden');
            return;
        }

        noMsg.classList.add('hidden');
        list.innerHTML = docs.map(doc => this.renderDocumentCard(doc)).join('');

        // Bind approve/reject buttons
        list.querySelectorAll('[data-action="approve-doc"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const docId = e.currentTarget.dataset.docId;
                this.approveDocument(docId);
            });
        });

        list.querySelectorAll('[data-action="reject-doc"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const docId = e.currentTarget.dataset.docId;
                this.showRejectModal(docId);
            });
        });
    }

    /**
     * Render single document card
     */
    renderDocumentCard(doc) {
        const isPdf = doc.file_path.toLowerCase().endsWith('.pdf');
        const statusClass = {
            'verified': 'bg-green-50 text-green-600 border-green-100',
            'pending': 'bg-orange-50 text-orange-500 border-orange-100',
            'rejected': 'bg-red-50 text-red-500 border-red-100'
        }[doc.status] || 'bg-gray-100 text-gray-500 border-gray-200';

        const statusText = doc.status.charAt(0).toUpperCase() + doc.status.slice(1);
        
        const filePreview = isPdf
            ? `<div class="w-full h-full flex flex-col items-center justify-center bg-red-50 text-red-500"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z"/></svg><span class="text-[6px] font-black uppercase mt-0.5">PDF</span></div>`
            : `<img src="/storage/${doc.file_path}" class="w-full h-full object-cover">`;

        const actionButtons = doc.status === 'pending'
            ? `<div class="flex gap-2">
                <button data-action="approve-doc" data-doc-id="${doc.id}" class="flex items-center gap-1 px-3 py-1.5 bg-green-500 text-white text-[9px] font-black uppercase tracking-widest rounded-lg hover:bg-green-600 transition-all"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg> Approve</button>
                <button data-action="reject-doc" data-doc-id="${doc.id}" class="flex items-center gap-1 px-3 py-1.5 bg-red-50 text-red-600 text-[9px] font-black uppercase tracking-widest rounded-lg hover:bg-red-100 transition-all border border-red-100"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg> Reject</button>
            </div>`
            : '';

        const rejectionReason = doc.status === 'rejected' && doc.rejection_reason
            ? `<div class="mt-3 p-3 bg-red-50/50 rounded-xl border border-red-100/50"><p class="text-[10px] font-black text-red-600 uppercase tracking-widest">Reason</p><p class="text-xs font-medium text-red-500 mt-1">${doc.rejection_reason}</p></div>`
            : '';

        return `<div class="p-4 bg-white border border-gray-100 rounded-2xl hover:border-blue-100 transition-all">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-11 h-11 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400 shrink-0 overflow-hidden">${filePreview}</div>
                    <div class="min-w-0">
                        <p class="text-xs font-black text-gray-900 uppercase tracking-tight truncate">${doc.type.replace('_', ' ')}</p>
                        <p class="text-[10px] font-semibold text-gray-400 mt-0.5">Updated: ${new Date(doc.updated_at).toLocaleDateString()}</p>
                    </div>
                </div>
                <div class="shrink-0">
                    <span class="px-2.5 py-1 text-[9px] font-black uppercase tracking-widest rounded-lg border whitespace-nowrap ${statusClass}">${statusText}</span>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="/storage/${doc.file_path}" target="_blank" class="px-3 py-1.5 bg-white border border-gray-100 text-[9px] font-black uppercase tracking-widest rounded-lg hover:bg-gray-50 transition-all text-gray-500 inline-block">View Full</a>
                ${actionButtons}
            </div>
            ${rejectionReason}
        </div>`;
    }

    /**
     * Approve document
     */
    async approveDocument(docId) {
        try {
            const response = await fetch(`/driver-documents/${docId}/approve`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            
            if (data.success) {
                const doc = this.selectedDriver.documents.find(d => d.id === parseInt(docId));
                if (doc) doc.status = 'verified';
                this.renderDocuments(this.selectedDriver.documents);
                this.updateDriverDocsBadge(this.selectedDriver.id);
                this.showToast('Document approved successfully', 'success');
            }
        } catch (err) {
            console.error(err);
            this.showToast('Failed to approve document', 'error');
        }
    }

    /**
     * Show reject modal
     */
    showRejectModal(docId) {
        this.currentDocId = docId;
        document.getElementById('rejectionReason').value = '';
        document.getElementById('rejectModal').classList.remove('hidden');
        
        setTimeout(() => {
            document.getElementById('rejectModalBackdrop').classList.remove('opacity-0');
            document.getElementById('rejectModalPanel').classList.remove('translate-y-full', 'sm:translate-y-4', 'sm:scale-95');
        }, 10);
        
        this.updateRejectButton();
    }

    /**
     * Close reject modal
     */
    closeRejectModal() {
        document.getElementById('rejectModalPanel').classList.add('translate-y-full', 'sm:translate-y-4', 'sm:scale-95');
        document.getElementById('rejectModalBackdrop').classList.add('opacity-0');
        setTimeout(() => {
            document.getElementById('rejectModal').classList.add('hidden');
        }, 300);
        this.currentDocId = null;
    }

    /**
     * Update reject button state
     */
    updateRejectButton() {
        const hasReason = document.getElementById('rejectionReason').value.trim().length > 0;
        document.getElementById('rejectDocBtn').disabled = !hasReason;
    }

    /**
     * Reject document
     */
    async rejectDocument() {
        if (!this.currentDocId) return;
        
        const reason = document.getElementById('rejectionReason').value;
        
        try {
            const response = await fetch(`/driver-documents/${this.currentDocId}/reject`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ reason: reason })
            });

            const data = await response.json();
            
            if (data.success) {
                const doc = this.selectedDriver.documents.find(d => d.id === this.currentDocId);
                if (doc) {
                    doc.status = 'rejected';
                    doc.rejection_reason = reason;
                }
                this.renderDocuments(this.selectedDriver.documents);
                this.updateDriverDocsBadge(this.selectedDriver.id);
                this.closeRejectModal();
                this.showToast('Document rejected', 'success');
            }
        } catch (err) {
            console.error(err);
            this.showToast('Failed to reject document', 'error');
        }
    }

    /**
     * Toggle suspend/unsuspend driver
     */
    async toggleSuspendDriver() {
        if (!this.selectedDriver) return;
        
        const isSuspended = this.selectedDriver.status === 'suspended';
        const newStatus = isSuspended ? 'active' : 'suspended';
        const actionText = isSuspended ? 'Unsuspend' : 'Suspend';
        
        if (!confirm(`Are you sure you want to ${actionText.toLowerCase()} this driver?`)) return;
        
        try {
            const response = await fetch(`/drivers/${this.selectedDriver.id}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            });

            const data = await response.json();
            
            if (data.status === 'success') {
                this.selectedDriver.status = newStatus;
                this.updateSuspendButtonState(this.selectedDriver);
                this.updateDriverStatusInTable(this.selectedDriver.id, newStatus);
                this.populateDrawerData(this.selectedDriver);
                this.showToast(`Driver ${actionText.toLowerCase()}ed successfully`, 'success');
            }
        } catch (err) {
            console.error(err);
            this.showToast(`Failed to ${actionText.toLowerCase()} driver`, 'error');
        }
    }

    /**
     * Update driver status in table
     */
    updateDriverStatusInTable(driverId, status) {
        const row = document.getElementById(`row-${driverId}`);
        if (!row) return;

        const statusCell = row.querySelector('td:nth-child(8)');
        if (!statusCell) return;

        if (status === 'suspended') {
            statusCell.innerHTML = '<span class="inline-flex items-center px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-full bg-red-50 text-red-600 border border-red-100 whitespace-nowrap">Suspended</span>';
        } else {
            statusCell.innerHTML = '<span class="inline-flex items-center px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-full bg-green-50 text-green-600 border border-green-100 whitespace-nowrap">Active</span>';
        }
    }

    /**
     * Update docs badge in table
     */
    updateDriverDocsBadge(driverId) {
        const docs = this.selectedDriver?.documents || [];
        const verifiedCount = docs.filter(d => d.status === 'verified').length;
        const pendingCount = docs.filter(d => d.status === 'pending').length;
        
        const badgeCell = document.getElementById(`docs-badge-${driverId}`);
        if (!badgeCell) return;
        
        let badgeHtml = '';
        if (verifiedCount === 6) {
            badgeHtml = '<span class="inline-flex px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-full bg-green-50 text-green-600 border border-green-100 whitespace-nowrap">Verified</span>';
        } else if (pendingCount > 0) {
            badgeHtml = `<span class="inline-flex px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-full bg-orange-50 text-orange-500 border border-orange-100 whitespace-nowrap">${pendingCount} Pending</span>`;
        } else {
            badgeHtml = '<span class="inline-flex px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-full bg-gray-100 text-gray-500 border border-gray-200 whitespace-nowrap">Incomplete</span>';
        }
        
        badgeCell.innerHTML = badgeHtml;
    }

    /**
     * Open edit modal
     */
    openEditModal(driverId) {
        console.log('Edit clicked for driver ID:', driverId, typeof driverId);
        console.log('Available driver IDs:', Object.keys(this.driverData));
        
        const driver = this.driverData[driverId];
        if (!driver) {
            console.error('Driver not found for edit:', driverId);
            console.error('driverData:', this.driverData);
            return;
        }

        this.selectedDriver = driver;

        document.getElementById('editDriverName').textContent = driver.full_name || '';
        document.getElementById('editFullName').value = driver.full_name || '';
        document.getElementById('editPhone').value = driver.mobile_number || '';
        document.getElementById('editVehicleModel').value = driver.vehicle?.model || '';
        
        document.getElementById('editModal').classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('editModalBackdrop').classList.remove('opacity-0');
            document.getElementById('editModalPanel').classList.remove('translate-y-full', 'sm:translate-y-4', 'sm:scale-95');
        }, 10);
    }

    /**
     * Close edit modal
     */
    closeEditModal() {
        document.getElementById('editModalPanel').classList.add('translate-y-full', 'sm:translate-y-4', 'sm:scale-95');
        document.getElementById('editModalBackdrop').classList.add('opacity-0');
        setTimeout(() => {
            document.getElementById('editModal').classList.add('hidden');
        }, 300);
    }

    /**
     * Save driver edit
     */
    async saveDriverEdit() {
        if (!this.selectedDriver) {
            this.showToast('No driver selected', 'error');
            return;
        }

        const driverId = this.selectedDriver.id;
        const fullName = document.getElementById('editFullName')?.value || '';
        const phone = document.getElementById('editPhone')?.value || '';
        const vehicleModel = document.getElementById('editVehicleModel')?.value || '';
        const status = document.getElementById('editStatus')?.value?.toLowerCase() || 'offline';
        const kycStatus = document.getElementById('editDocStatus')?.value?.toLowerCase() || 'pending';

        // Convert status display names to database values
        const statusMap = {
            'online': 'online',
            'offline': 'offline',
            'suspended': 'suspended'
        };
        const dbStatus = statusMap[status] || 'offline';

        // Convert KYC status - map UI values to database ENUM values
        const kycMap = {
            'verified': 'approved',   // UI shows "Verified" -> DB stores "approved"
            'pending': 'pending',
            'in_review': 'in_review',
            'rejected': 'rejected',
            'incomplete': 'pending'
        };
        const dbKycStatus = kycMap[kycStatus] || 'pending';

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            const response = await fetch(`/drivers/${driverId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    full_name: fullName,
                    mobile_number: phone,
                    status: dbStatus,
                    kyc_status: dbKycStatus,
                    vehicle_model: vehicleModel
                })
            });

            const result = await response.json();

            if (response.ok) {
                // Update local data
                this.driverData[driverId] = { ...this.driverData[driverId], ...result.driver };
                this.selectedDriver = this.driverData[driverId];
                
                // Update the table row display
                this.updateTableRow(driverId);
                
                this.closeEditModal();
                this.showToast('Driver updated successfully', 'success');
            } else {
                this.showToast(result.message || 'Failed to update driver', 'error');
            }
        } catch (error) {
            console.error('Error updating driver:', error);
            this.showToast('Error updating driver', 'error');
        }
    }

    /**
     * Update table row after edit
     */
    updateTableRow(driverId) {
        const row = document.querySelector(`tr[data-driver-id="${driverId}"]`);
        if (!row) return;

        const driver = this.driverData[driverId];
        if (!driver) return;

        // Update the row data attributes
        row.setAttribute('data-driver-info', JSON.stringify(driver));

        // Find and update name cell
        const nameCell = row.querySelector('td:nth-child(3)');
        if (nameCell) {
            const nameDiv = nameCell.querySelector('.font-bold');
            if (nameDiv) nameDiv.textContent = driver.full_name || '';
        }

        // Update status cell
        const statusCell = row.querySelector('td:nth-child(5)');
        if (statusCell) {
            const statusBadge = statusCell.querySelector('span');
            if (statusBadge) {
                statusBadge.className = `inline-flex items-center px-2 py-1 rounded-full text-[9px] font-black uppercase tracking-wider ${
                    driver.status === 'suspended' 
                        ? 'bg-red-100 text-red-700' 
                        : 'bg-green-100 text-green-700'
                }`;
                statusBadge.textContent = driver.status === 'suspended' ? 'Suspended' : 'Active';
            }
        }

        // Update document status cell
        const docCell = row.querySelector('td:nth-child(6)');
        if (docCell) {
            const docBadge = docCell.querySelector('span');
            if (docBadge) {
                docBadge.className = `inline-flex items-center px-2 py-1 rounded-full text-[9px] font-black uppercase tracking-wider ${
                    driver.kyc_status === 'verified' 
                        ? 'bg-green-100 text-green-700' 
                        : driver.kyc_status === 'pending' 
                            ? 'bg-yellow-100 text-yellow-700' 
                            : 'bg-red-100 text-red-700'
                }`;
                docBadge.textContent = driver.kyc_status?.charAt(0).toUpperCase() + driver.kyc_status?.slice(1) || 'Pending';
            }
        }
    }

    /**
     * Toggle row selection
     */
    toggleRow(id) {
        const index = this.selectedRows.indexOf(id);
        const row = document.getElementById(`row-${id}`);
        const checkbox = row?.querySelector('.row-checkbox');

        if (index > -1) {
            this.selectedRows.splice(index, 1);
            row?.classList.remove('bg-blue-50/20');
            if (checkbox) checkbox.checked = false;
        } else {
            this.selectedRows.push(id);
            row?.classList.add('bg-blue-50/20');
            if (checkbox) checkbox.checked = true;
        }

        this.updateSelectAllCheckbox();
    }

    /**
     * Toggle select all
     */
    toggleSelectAll(checked) {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        this.selectedRows = [];

        checkboxes.forEach(checkbox => {
            const id = checkbox.dataset.id;
            checkbox.checked = checked;
            const row = document.getElementById(`row-${id}`);

            if (checked) {
                this.selectedRows.push(id);
                row?.classList.add('bg-blue-50/20');
            } else {
                row?.classList.remove('bg-blue-50/20');
            }
        });
    }

    /**
     * Update select all checkbox state
     */
    updateSelectAllCheckbox() {
        const allCheckboxes = document.querySelectorAll('.row-checkbox');
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
        const selectAllCheckbox = document.getElementById('selectAll');
        
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = checkedBoxes.length === allCheckboxes.length && allCheckboxes.length > 0;
        }
    }

    /**
     * Filter drivers
     */
    filterDrivers() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const status = document.getElementById('statusFilter').value;
        const docs = document.getElementById('docsFilter').value;

        document.querySelectorAll('.driver-row').forEach(row => {
            const driverInfo = JSON.parse(row.dataset.driverInfo || '{}');
            const driverName = row.querySelector('td:nth-child(2)')?.textContent?.toLowerCase() || '';
            
            const matchesSearch = driverName.includes(search);
            
            const docsList = driverInfo.documents || [];
            const pendingCount = docsList.filter(d => d.status === 'pending').length;
            const verifiedCount = docsList.filter(d => d.status === 'verified').length;

            let matchesDocs = true;
            if (docs === 'Verified') matchesDocs = verifiedCount === 6;
            else if (docs === 'Pending') matchesDocs = pendingCount > 0;
            else if (docs === 'Expired') matchesDocs = false;

            let matchesStatus = true;
            if (status === 'Online') matchesStatus = driverInfo.is_available === true;
            else if (status === 'Offline') matchesStatus = driverInfo.is_available === false;
            else if (status === 'Suspended') matchesStatus = driverInfo.status === 'suspended';

            if (matchesSearch && matchesDocs && matchesStatus) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'success') {
        const toast = document.createElement('div');
        const bgClass = type === 'success' ? 'bg-green-500' : 'bg-red-500';
        toast.className = `fixed bottom-6 right-6 ${bgClass} text-white px-6 py-3 rounded-xl shadow-lg z-50 font-bold text-sm animate-fade-in`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.driverManager = new DriverManager();
});
