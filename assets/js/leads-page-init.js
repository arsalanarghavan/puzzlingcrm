/**
 * Leads Page Initialization Script
 * This script handles view switching, delete actions, and form submissions for the leads page
 */
(function() {
    'use strict';
    
    console.log('PuzzlingCRM Leads Init: Script loaded');
    
    function initLeadsPage() {
        console.log('PuzzlingCRM Leads Init: Starting initialization');
        
        if (typeof jQuery === 'undefined') {
            console.error('PuzzlingCRM Leads Init: jQuery not found!');
            setTimeout(initLeadsPage, 100);
            return;
        }
        
        var $ = jQuery;
        
        // Initialize view mode from URL or localStorage
        var currentViewMode = window.pzlLeadsViewMode || 'list';
        if (typeof Storage !== 'undefined') {
            var savedView = localStorage.getItem('pzl_leads_view_mode');
            if (savedView && !window.location.search.includes('view=')) {
                currentViewMode = savedView;
                // Update radio button
                $('input[name="viewMode"][value="' + savedView + '"]').prop('checked', true);
                // Update view
                if (savedView === 'list') {
                    $('.pzl-leads-list-view').show();
                    $('.pzl-leads-card-view').hide();
                } else {
                    $('.pzl-leads-list-view').hide();
                    $('.pzl-leads-card-view').show();
                }
            }
        }
        
        // View mode toggle - Use event delegation
        $(document).off('change', 'input[name="viewMode"]').on('change', 'input[name="viewMode"]', function() {
            var viewMode = $(this).val();
            console.log('PuzzlingCRM Leads: View mode changed to', viewMode);
            
            // Save to localStorage
            if (typeof Storage !== 'undefined') {
                localStorage.setItem('pzl_leads_view_mode', viewMode);
            }
            
            // Toggle views
            if (viewMode === 'list') {
                $('.pzl-leads-list-view').fadeIn(300);
                $('.pzl-leads-card-view').fadeOut(300);
            } else {
                $('.pzl-leads-list-view').fadeOut(300);
                $('.pzl-leads-card-view').fadeIn(300);
            }
            
            // Update URL without reload
            try {
                var url = new URL(window.location.href);
                url.searchParams.set('view', viewMode);
                window.history.pushState({view: viewMode}, '', url);
            } catch(e) {
                // Fallback for older browsers
                window.location.search = '?view=' + viewMode;
            }
            
            // Update hidden input in filter form
            $('#view-mode-input').val(viewMode);
        });
        
        // Delete lead handler for both views - Use event delegation
        $(document).off('click', '.pzl-delete-lead').on('click', '.pzl-delete-lead', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('PuzzlingCRM Leads: Delete button clicked');
            
            var button = $(this);
            var leadId = button.data('lead-id') || button.attr('data-lead-id');
            var nonce = button.data('nonce') || button.attr('data-nonce');
            
            if (!leadId || !nonce) {
                console.error('PuzzlingCRM Leads: Missing lead ID or nonce', {leadId: leadId, nonce: nonce});
                return false;
            }
            
            var leadElement = button.closest('tr.leads-list, .card[data-lead-id="' + leadId + '"]');
            
            var performDelete = function() {
                if (leadElement.length) {
                    leadElement.css('opacity', '0.5');
                }
                
                var ajaxUrl = (typeof window.puzzlingcrm_ajax_obj !== 'undefined' && window.puzzlingcrm_ajax_obj.ajax_url) 
                    ? window.puzzlingcrm_ajax_obj.ajax_url 
                    : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
                
                console.log('PuzzlingCRM Leads: Sending delete request', {leadId: leadId, ajaxUrl: ajaxUrl});
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'puzzling_delete_lead',
                        security: nonce,
                        lead_id: leadId
                    },
                    success: function(response) {
                        console.log('PuzzlingCRM Leads: Delete response', response);
                        if (response && response.success) {
                            if (leadElement.length) {
                                leadElement.fadeOut(300, function() {
                                    $(this).remove();
                                });
                            }
                            
                            if (typeof showPuzzlingAlert === 'function') {
                                showPuzzlingAlert('موفق', response.data.message, 'success', response.data);
                            } else {
                                alert(response.data.message);
                            }
                            
                            // Reload page after a short delay to refresh counts
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            if (leadElement.length) {
                                leadElement.css('opacity', '1');
                            }
                            var errorMsg = (response && response.data && response.data.message) 
                                ? response.data.message 
                                : 'خطا در حذف سرنخ';
                            
                            if (typeof showPuzzlingAlert === 'function') {
                                showPuzzlingAlert('خطا', errorMsg, 'error');
                            } else {
                                alert(errorMsg);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('PuzzlingCRM Leads: Delete AJAX error', {xhr: xhr, status: status, error: error});
                        if (leadElement.length) {
                            leadElement.css('opacity', '1');
                        }
                        var errorMsg = 'یک خطای ناشناخته در ارتباط با سرور رخ داد.';
                        
                        if (typeof showPuzzlingAlert === 'function') {
                            showPuzzlingAlert('خطا', errorMsg, 'error');
                        } else {
                            alert(errorMsg);
                        }
                    }
                });
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'آیا از حذف این سرنخ مطمئن هستید؟',
                    text: "این عمل غیرقابل بازگشت است!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'بله، حذف کن!',
                    cancelButtonText: 'انصراف'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        performDelete();
                    }
                });
            } else {
                if (confirm('آیا از حذف این سرنخ مطمئن هستید؟')) {
                    performDelete();
                }
            }
            
            return false;
        });
        
        // Check all checkbox handler - Use event delegation
        $(document).off('change', '.check-all').on('change', '.check-all', function() {
            var isChecked = $(this).is(':checked');
            $('.leads-checkbox input[type="checkbox"]').prop('checked', isChecked);
            if (isChecked) {
                $('.leads-list').addClass('selected');
            } else {
                $('.leads-list').removeClass('selected');
            }
        });
        
        // Close modal function for AJAX success - Global
        window.closeLeadModal = function() {
            try {
                var modalElement = document.getElementById('addLeadModal');
                if (modalElement) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        var modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        } else {
                            $(modalElement).modal('hide');
                        }
                    } else {
                        $(modalElement).modal('hide');
                    }
                }
            } catch(e) {
                $('#addLeadModal').modal('hide');
            }
            
            // Reset form
            var form = document.getElementById('pzl-add-lead-form');
            if (form) {
                form.reset();
            }
        };
        
        // Initialize drag & drop for status cards
        initStatusCardsDragDrop();
        
        console.log('PuzzlingCRM Leads Init: Initialization complete');
    }
    
    /**
     * Initialize drag & drop for status cards
     */
    function initStatusCardsDragDrop() {
        if (typeof dragula === 'undefined') {
            console.warn('PuzzlingCRM Leads Init: dragula not found, drag & drop disabled');
            return;
        }
        
        var container = document.getElementById('pzl-status-cards-container');
        if (!container) {
            console.warn('PuzzlingCRM Leads Init: Status cards container not found');
            return;
        }
        
        // Initialize dragula for status cards
        var drake = dragula([container], {
            moves: function(el, source, handle) {
                // Only allow dragging by the card itself or a drag handle
                return el.classList.contains('pzl-status-card-item');
            },
            accepts: function(el, target) {
                // Only allow dropping within the same container (row)
                return target === container;
            },
            revertOnSpill: false,
            copy: false,
            mirrorContainer: container
        });
        
        drake.on('drag', function(el) {
            el.style.opacity = '0.5';
        });
        
        drake.on('dragend', function(el) {
            el.style.opacity = '1';
        });
        
        drake.on('drop', function(el, target, source, sibling) {
            console.log('PuzzlingCRM Leads: Status card dropped', {
                card: el,
                status: el.getAttribute('data-status-slug'),
                newIndex: Array.from(target.children).indexOf(el)
            });
            
            // Save order to localStorage
            var order = Array.from(container.children).map(function(child) {
                return child.getAttribute('data-status-slug');
            }).filter(function(slug) {
                return slug !== null;
            });
            
            if (typeof Storage !== 'undefined') {
                localStorage.setItem('pzl_status_cards_order', JSON.stringify(order));
            }
        });
        
        // Restore order from localStorage if available
        if (typeof Storage !== 'undefined') {
            var savedOrder = localStorage.getItem('pzl_status_cards_order');
            if (savedOrder) {
                try {
                    var order = JSON.parse(savedOrder);
                    var cards = Array.from(container.children);
                    var cardMap = {};
                    cards.forEach(function(card) {
                        var slug = card.getAttribute('data-status-slug');
                        if (slug) {
                            cardMap[slug] = card;
                        }
                    });
                    
                    // Reorder cards based on saved order
                    order.forEach(function(slug) {
                        if (cardMap[slug]) {
                            container.appendChild(cardMap[slug]);
                        }
                    });
                } catch(e) {
                    console.warn('PuzzlingCRM Leads: Failed to restore card order', e);
                }
            }
        }
        
        console.log('PuzzlingCRM Leads Init: Status cards drag & drop initialized');
    }
    
    // Wait for jQuery and DOM
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function() {
            setTimeout(initLeadsPage, 300);
        });
    } else {
        // Wait for jQuery
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                jQuery(document).ready(function() {
                    setTimeout(initLeadsPage, 300);
                });
            }
        }, 50);
        
        setTimeout(function() {
            clearInterval(checkJQuery);
        }, 5000);
    }
    
    // Also try on window load
    if (typeof window.addEventListener !== 'undefined') {
        window.addEventListener('load', function() {
            setTimeout(initLeadsPage, 200);
        });
    }
})();
