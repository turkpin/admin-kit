/**
 * AdminKit JavaScript
 * Main JavaScript file for AdminKit admin panel
 */

class AdminKit {
    constructor() {
        this.init();
    }

    init() {
        this.initializeComponents();
        this.bindEvents();
        this.initializeModals();
        this.initializeFileUploads();
        this.initializeToggleSwitches();
        this.initializeTooltips();
    }

    initializeComponents() {
        // Initialize mobile sidebar
        this.initializeMobileSidebar();
        
        // Initialize data tables
        this.initializeDataTables();
        
        // Initialize form validation
        this.initializeFormValidation();
        
        // Initialize confirmation dialogs
        this.initializeConfirmationDialogs();
    }

    bindEvents() {
        // Flash message close buttons
        document.querySelectorAll('.flash-message .close-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.flash-message').remove();
            });
        });

        // Auto-hide flash messages after 5 seconds
        document.querySelectorAll('.flash-message').forEach(msg => {
            setTimeout(() => {
                if (msg.parentNode) {
                    msg.style.opacity = '0';
                    setTimeout(() => msg.remove(), 300);
                }
            }, 5000);
        });

        // Search functionality
        this.initializeSearch();
    }

    initializeMobileSidebar() {
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
                if (overlay) overlay.classList.toggle('open');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                overlay.classList.remove('open');
            });
        }
    }

    initializeDataTables() {
        const tables = document.querySelectorAll('.admin-table[data-sortable="true"]');
        
        tables.forEach(table => {
            this.makeSortable(table);
        });
    }

    makeSortable(table) {
        const headers = table.querySelectorAll('th[data-sortable]');
        
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.innerHTML += ' <span class="sort-indicator">↕️</span>';
            
            header.addEventListener('click', () => {
                this.sortTable(table, header);
            });
        });
    }

    sortTable(table, header) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const isAscending = header.getAttribute('data-sort') !== 'asc';
        
        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();
            
            if (isAscending) {
                return aValue.localeCompare(bValue, undefined, { numeric: true });
            } else {
                return bValue.localeCompare(aValue, undefined, { numeric: true });
            }
        });
        
        // Update sort indicators
        table.querySelectorAll('th').forEach(th => {
            th.removeAttribute('data-sort');
            const indicator = th.querySelector('.sort-indicator');
            if (indicator) indicator.textContent = '↕️';
        });
        
        header.setAttribute('data-sort', isAscending ? 'asc' : 'desc');
        const indicator = header.querySelector('.sort-indicator');
        if (indicator) indicator.textContent = isAscending ? '↑' : '↓';
        
        // Reorder rows
        rows.forEach(row => tbody.appendChild(row));
    }

    initializeFormValidation() {
        const forms = document.querySelectorAll('form[data-validate="true"]');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
    }

    validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'Bu alan zorunludur');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
        });
        
        // Email validation
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                this.showFieldError(field, 'Geçerli bir e-posta adresi girin');
                isValid = false;
            }
        });
        
        return isValid;
    }

    showFieldError(field, message) {
        this.clearFieldError(field);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error text-red-600 text-sm mt-1';
        errorDiv.textContent = message;
        
        field.classList.add('border-red-500');
        field.parentNode.appendChild(errorDiv);
    }

    clearFieldError(field) {
        field.classList.remove('border-red-500');
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    initializeConfirmationDialogs() {
        document.querySelectorAll('[data-confirm]').forEach(element => {
            element.addEventListener('click', (e) => {
                const message = element.getAttribute('data-confirm');
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    }

    initializeModals() {
        // Modal open buttons
        document.querySelectorAll('[data-modal]').forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const modalId = trigger.getAttribute('data-modal');
                this.openModal(modalId);
            });
        });

        // Modal close buttons
        document.querySelectorAll('[data-modal-close]').forEach(closeBtn => {
            closeBtn.addEventListener('click', () => {
                this.closeModal(closeBtn.closest('.modal'));
            });
        });

        // Close modal on backdrop click
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) {
                    this.closeModal(backdrop.querySelector('.modal'));
                }
            });
        });
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal(modal) {
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    initializeFileUploads() {
        document.querySelectorAll('.file-upload-area').forEach(area => {
            const input = area.querySelector('input[type="file"]');
            
            if (input) {
                // Drag and drop events
                area.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    area.classList.add('dragover');
                });
                
                area.addEventListener('dragleave', () => {
                    area.classList.remove('dragover');
                });
                
                area.addEventListener('drop', (e) => {
                    e.preventDefault();
                    area.classList.remove('dragover');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        input.files = files;
                        this.handleFileSelect(input, files[0]);
                    }
                });
                
                // Click to select file
                area.addEventListener('click', () => {
                    input.click();
                });
                
                // File input change
                input.addEventListener('change', (e) => {
                    if (e.target.files.length > 0) {
                        this.handleFileSelect(input, e.target.files[0]);
                    }
                });
            }
        });
    }

    handleFileSelect(input, file) {
        const area = input.closest('.file-upload-area');
        const preview = area.querySelector('.file-preview');
        
        if (preview) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.innerHTML = `<img src="${e.target.result}" class="max-w-full h-32 object-cover rounded">`;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = `<div class="text-sm text-gray-600">${file.name}</div>`;
            }
        }
    }

    initializeToggleSwitches() {
        document.querySelectorAll('.toggle-switch').forEach(toggle => {
            const checkbox = toggle.querySelector('input[type="checkbox"]');
            
            if (checkbox) {
                // Set initial state
                if (checkbox.checked) {
                    toggle.classList.add('checked');
                }
                
                toggle.addEventListener('click', () => {
                    checkbox.checked = !checkbox.checked;
                    toggle.classList.toggle('checked', checkbox.checked);
                    
                    // Trigger change event
                    checkbox.dispatchEvent(new Event('change'));
                });
            }
        });
    }

    initializeTooltips() {
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                this.showTooltip(e.target);
            });
            
            element.addEventListener('mouseleave', (e) => {
                this.hideTooltip(e.target);
            });
        });
    }

    showTooltip(element) {
        const text = element.getAttribute('data-tooltip');
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip absolute bg-gray-800 text-white text-xs px-2 py-1 rounded z-50';
        tooltip.textContent = text;
        tooltip.id = 'tooltip-' + Date.now();
        
        document.body.appendChild(tooltip);
        
        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
        
        element.setAttribute('data-tooltip-id', tooltip.id);
    }

    hideTooltip(element) {
        const tooltipId = element.getAttribute('data-tooltip-id');
        if (tooltipId) {
            const tooltip = document.getElementById(tooltipId);
            if (tooltip) {
                tooltip.remove();
            }
            element.removeAttribute('data-tooltip-id');
        }
    }

    initializeSearch() {
        const searchInputs = document.querySelectorAll('[data-search-target]');
        
        searchInputs.forEach(input => {
            const targetSelector = input.getAttribute('data-search-target');
            const targets = document.querySelectorAll(targetSelector);
            
            input.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase();
                
                targets.forEach(target => {
                    const text = target.textContent.toLowerCase();
                    target.style.display = text.includes(query) ? '' : 'none';
                });
            });
        });
    }

    // Utility functions
    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `flash-message flash-${type} fixed top-4 right-4 z-50 max-w-sm`;
        notification.innerHTML = `
            ${message}
            <button class="close-btn ml-2 text-lg">&times;</button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, duration);
        
        // Manual close
        notification.querySelector('.close-btn').addEventListener('click', () => {
            notification.remove();
        });
    }

    ajax(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const config = { ...defaults, ...options };
        
        return fetch(url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error('Ajax error:', error);
                this.showNotification('Bir hata oluştu', 'error');
                throw error;
            });
    }
}

// Initialize AdminKit when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.adminKit = new AdminKit();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminKit;
}
