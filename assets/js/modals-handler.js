/**
 * Modals Handler - Projects, Customers, Contracts
 * @package PuzzlingCRM
 */

jQuery(document).ready(function($) {
    
    /**
     * Open Project Detail Modal
     */
    window.openProjectModal = function(projectId) {
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_get_project_details',
                security: puzzlingcrm_ajax_obj.nonce,
                project_id: projectId
            },
            success: function(response) {
                if (response.success) {
                    showModal('project-modal', response.data.html);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: response.data.message
                    });
                }
            }
        });
    };
    
    /**
     * Open Customer 360 View
     */
    window.openCustomerModal = function(customerId) {
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_get_customer_360',
                security: puzzlingcrm_ajax_obj.nonce,
                customer_id: customerId
            },
            success: function(response) {
                if (response.success) {
                    showModal('customer-modal', response.data.html);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: response.data.message
                    });
                }
            }
        });
    };
    
    /**
     * Open Contract Quick View
     */
    window.openContractModal = function(contractId) {
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_get_contract_details',
                security: puzzlingcrm_ajax_obj.nonce,
                contract_id: contractId
            },
            success: function(response) {
                if (response.success) {
                    showModal('contract-modal', response.data.html);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: response.data.message
                    });
                }
            }
        });
    };
    
    /**
     * Show Modal
     */
    function showModal(modalId, content) {
        // Remove existing modal
        $('#' + modalId).remove();
        
        // Create modal
        const modalHTML = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        ${content}
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHTML);
        
        const modal = new bootstrap.Modal(document.getElementById(modalId));
        modal.show();
        
        // Remove on close
        $('#' + modalId).on('hidden.bs.modal', function() {
            $(this).remove();
        });
    }
    
    // Event delegation for quick view buttons
    $(document).on('click', '.btn-project-quick-view', function(e) {
        e.preventDefault();
        const projectId = $(this).data('project-id');
        openProjectModal(projectId);
    });
    
    $(document).on('click', '.btn-customer-360', function(e) {
        e.preventDefault();
        const customerId = $(this).data('customer-id');
        openCustomerModal(customerId);
    });
    
    $(document).on('click', '.btn-contract-quick-view', function(e) {
        e.preventDefault();
        const contractId = $(this).data('contract-id');
        openContractModal(contractId);
    });
});

