/**
 * Teacher Timetable Management System - Main JavaScript
 */

// ============================================
// SIDEBAR TOGGLE
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
            document.querySelector('.main-header').classList.toggle('expanded');
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const userDropdown = document.getElementById('userDropdown');
        const userMenu = document.querySelector('.user-menu');
        
        if (userDropdown && userMenu && !userMenu.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.style.display = 'none';
        }
    });
});

// ============================================
// USER DROPDOWN
// ============================================
function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
}

// ============================================
// CONFIRMATION DIALOGS
// ============================================
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item? This action cannot be undone.');
}

function confirmAction(message) {
    return confirm(message || 'Are you sure you want to proceed?');
}

// ============================================
// FORM VALIDATION
// ============================================
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.style.borderColor = 'var(--danger-color)';
            
            // Add error message if not exists
            let errorMsg = field.parentElement.querySelector('.error-message');
            if (!errorMsg) {
                errorMsg = document.createElement('div');
                errorMsg.className = 'error-message';
                errorMsg.style.color = 'var(--danger-color)';
                errorMsg.style.fontSize = '12px';
                errorMsg.style.marginTop = '5px';
                field.parentElement.appendChild(errorMsg);
            }
            errorMsg.textContent = 'This field is required';
        } else {
            field.style.borderColor = '';
            const errorMsg = field.parentElement.querySelector('.error-message');
            if (errorMsg) errorMsg.remove();
        }
    });
    
    return isValid;
}

// ============================================
// AJAX HELPERS
// ============================================
function ajaxRequest(url, method, data, callback) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    callback(null, response);
                } catch (e) {
                    callback(null, xhr.responseText);
                }
            } else {
                callback(new Error('Request failed'), null);
            }
        }
    };
    
    xhr.send(data);
}

function fetchData(url, callback) {
    ajaxRequest(url, 'GET', null, callback);
}

function postData(url, data, callback) {
    const formData = Object.keys(data)
        .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(data[key]))
        .join('&');
    ajaxRequest(url, 'POST', formData, callback);
}

// ============================================
// TABLE FUNCTIONS
// ============================================
function filterTable(tableId, searchTerm) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const term = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
}

function sortTable(tableId, columnIndex) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aText = a.cells[columnIndex].textContent.trim();
        const bText = b.cells[columnIndex].textContent.trim();
        return aText.localeCompare(bText);
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// ============================================
// MODAL FUNCTIONS
// ============================================
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// ============================================
// TIMETABLE GRID FUNCTIONS
// ============================================
function initTimetableGrid() {
    const cells = document.querySelectorAll('.timetable-cell[data-slot]');
    
    cells.forEach(cell => {
        cell.addEventListener('click', function() {
            const day = this.dataset.day;
            const slot = this.dataset.slot;
            openSlotEditor(day, slot);
        });
    });
}

function openSlotEditor(day, slot) {
    // This would open a modal to edit the slot
    console.log('Editing slot:', day, slot);
}

function validateTimetableEntry(teacherId, day, slot, callback) {
    postData('/ttc/api/validate_conflict.php', {
        teacher_id: teacherId,
        day: day,
        slot_id: slot
    }, function(error, response) {
        if (error) {
            callback(error);
        } else {
            callback(null, response);
        }
    });
}

// ============================================
// UTILITY FUNCTIONS
// ============================================
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatTime(timeString) {
    const time = new Date('2000-01-01 ' + timeString);
    return time.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ============================================
// NOTIFICATIONS
// ============================================
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);';
    notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'danger' ? 'exclamation-circle' : 'info-circle')}"></i> ${message}`;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ============================================
// DEPENDENT DROPDOWNS
// ============================================
function updateDependentDropdown(sourceId, targetId, url, paramName) {
    const source = document.getElementById(sourceId);
    const target = document.getElementById(targetId);
    
    if (!source || !target) return;
    
    source.addEventListener('change', function() {
        const value = this.value;
        
        if (!value) {
            target.innerHTML = '<option value="">Select...</option>';
            target.disabled = true;
            return;
        }
        
        fetchData(`${url}?${paramName}=${value}`, function(error, response) {
            if (error) {
                console.error('Error fetching data:', error);
                return;
            }
            
            let options = '<option value="">Select...</option>';
            if (Array.isArray(response)) {
                response.forEach(item => {
                    options += `<option value="${item.id}">${item.name}</option>`;
                });
            }
            
            target.innerHTML = options;
            target.disabled = false;
        });
    });
}

// ============================================
// PRINT FUNCTION
// ============================================
function printTimetable() {
    window.print();
}

// ============================================
// EXPORT FUNCTIONS
// ============================================
function exportToPDF(url, params) {
    const queryString = Object.keys(params)
        .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`)
        .join('&');
    
    window.open(`${url}?${queryString}`, '_blank');
}
