/**
 * Kanban Board
 * Drag & Drop Kanban board for project management
 */

class PuzzlingCRM_Kanban_Board {
    constructor() {
        this.container = null;
        this.columns = [];
        this.cards = {};
        this.draggedCard = null;
        this.draggedColumn = null;
        this.projectId = 0;
        this.isLoading = false;
        
        this.init();
    }
    
    init() {
        this.createKanbanInterface();
        this.bindEvents();
        this.loadColumns();
        this.loadCards();
    }
    
    createKanbanInterface() {
        if (!document.getElementById('puzzling-kanban-board')) {
            const kanbanContainer = document.createElement('div');
            kanbanContainer.id = 'puzzling-kanban-board';
            kanbanContainer.className = 'puzzling-kanban-board';
            kanbanContainer.innerHTML = `
                <div class="kanban-header">
                    <h3>تخته کانبان پروژه‌ها</h3>
                    <div class="kanban-controls">
                        <button class="btn-add-card" id="add-kanban-card">
                            <i class="ri-add-line"></i>
                            کارت جدید
                        </button>
                        <button class="btn-refresh" id="refresh-kanban">
                            <i class="ri-refresh-line"></i>
                            به‌روزرسانی
                        </button>
                    </div>
                </div>
                
                <div class="kanban-content">
                    <div class="kanban-loading" id="kanban-loading" style="display: none;">
                        <div class="loading-spinner"></div>
                        <span>در حال بارگذاری...</span>
                    </div>
                    
                    <div class="kanban-columns" id="kanban-columns"></div>
                </div>
            `;
            
            // Insert into page
            const targetElement = document.querySelector('.wrap') || document.querySelector('main') || document.body;
            targetElement.appendChild(kanbanContainer);
        }
        
        this.container = document.getElementById('puzzling-kanban-board');
    }
    
    bindEvents() {
        // Add card
        document.getElementById('add-kanban-card').addEventListener('click', () => {
            this.showAddCardModal();
        });
        
        // Refresh
        document.getElementById('refresh-kanban').addEventListener('click', () => {
            this.refreshBoard();
        });
    }
    
    loadColumns() {
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'GET',
            data: {
                action: 'puzzling_get_kanban_columns',
                nonce: puzzlingcrm_ajax_obj.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.columns = response.data;
                    this.renderColumns();
                }
            }
        });
    }
    
    loadCards() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'GET',
            data: {
                action: 'puzzling_get_kanban_cards',
                nonce: puzzlingcrm_ajax_obj.nonce,
                project_id: this.projectId
            },
            success: (response) => {
                this.isLoading = false;
                this.hideLoading();
                
                if (response.success) {
                    this.cards = response.data;
                    this.renderCards();
                } else {
                    this.showError('خطا در بارگذاری کارت‌ها');
                }
            },
            error: () => {
                this.isLoading = false;
                this.hideLoading();
                this.showError('خطا در ارتباط با سرور');
            }
        });
    }
    
    renderColumns() {
        const columnsContainer = document.getElementById('kanban-columns');
        
        columnsContainer.innerHTML = this.columns.map(column => `
            <div class="kanban-column" data-column="${column.id}">
                <div class="column-header" style="border-color: ${column.color}">
                    <div class="column-title">
                        <i class="${column.icon}" style="color: ${column.color}"></i>
                        <span>${column.title}</span>
                        <span class="card-count" id="count-${column.id}">0</span>
                    </div>
                </div>
                <div class="column-content" id="column-${column.id}">
                    <!-- Cards will be rendered here -->
                </div>
            </div>
        `).join('');
        
        // Initialize drag and drop
        this.initializeDragAndDrop();
    }
    
    renderCards() {
        // Clear existing cards
        this.columns.forEach(column => {
            const columnElement = document.getElementById(`column-${column.id}`);
            columnElement.innerHTML = '';
        });
        
        // Render cards
        Object.keys(this.cards).forEach(columnId => {
            const cards = this.cards[columnId];
            const columnElement = document.getElementById(`column-${columnId}`);
            const countElement = document.getElementById(`count-${columnId}`);
            
            countElement.textContent = cards.length;
            
            cards.forEach(card => {
                const cardElement = this.createCardElement(card);
                columnElement.appendChild(cardElement);
            });
        });
        
        // Initialize drag and drop
        this.initializeDragAndDrop();
    }
    
    createCardElement(card) {
        const priorityClass = `priority-${card.priority}`;
        const overdueClass = card.is_overdue ? 'overdue' : '';
        
        return `
            <div class="kanban-card ${priorityClass} ${overdueClass}" 
                 data-card-id="${card.id}" 
                 data-column="${card.column}"
                 draggable="true">
                <div class="card-header">
                    <div class="card-title">${card.title}</div>
                </div>
                
                <div class="card-content">
                    ${card.description ? `<div class="card-description">${card.description}</div>` : ''}
                    
                    <div class="card-meta">
                        ${card.assignee_name ? `
                            <div class="card-assignee">
                                <img src="${card.assignee_avatar}" alt="${card.assignee_name}" class="assignee-avatar">
                                <span>${card.assignee_name}</span>
                            </div>
                        ` : ''}
                        
                        ${card.due_date ? `
                            <div class="card-due-date ${card.is_overdue ? 'overdue' : ''}">
                                <i class="ri-calendar-line"></i>
                                <span>${card.formatted_due_date}</span>
                            </div>
                        ` : ''}
                    </div>
                    
                    <div class="card-tags">
                        ${card.tags.map(tag => `
                            <span class="card-tag">${tag}</span>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    }
    
    initializeDragAndDrop() {
        const cards = document.querySelectorAll('.kanban-card');
        const columns = document.querySelectorAll('.kanban-column');
        
        // Card drag events
        cards.forEach(card => {
            card.addEventListener('dragstart', (e) => {
                this.draggedCard = e.target;
                this.draggedColumn = e.target.dataset.column;
                e.target.classList.add('dragging');
            });
            
            card.addEventListener('dragend', (e) => {
                e.target.classList.remove('dragging');
                this.draggedCard = null;
                this.draggedColumn = null;
            });
        });
        
        // Column drop events
        columns.forEach(column => {
            column.addEventListener('dragover', (e) => {
                e.preventDefault();
                column.classList.add('drag-over');
            });
            
            column.addEventListener('dragleave', (e) => {
                column.classList.remove('drag-over');
            });
            
            column.addEventListener('drop', (e) => {
                e.preventDefault();
                column.classList.remove('drag-over');
                
                if (this.draggedCard) {
                    const newColumn = column.dataset.column;
                    const cardId = this.draggedCard.dataset.cardId;
                    
                    if (newColumn !== this.draggedColumn) {
                        this.moveCard(cardId, newColumn);
                    }
                }
            });
        });
    }
    
    moveCard(cardId, newColumn) {
        const card = this.findCardById(cardId);
        if (!card) return;
        
        const oldColumn = card.column;
        const newPosition = this.cards[newColumn].length;
        
        // Update UI immediately
        this.updateCardColumn(cardId, oldColumn, newColumn, newPosition);
        
        // Send to server
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_move_kanban_card',
                nonce: puzzlingcrm_ajax_obj.nonce,
                card_id: cardId,
                new_column: newColumn,
                new_position: newPosition
            },
            success: (response) => {
                if (response.success) {
                    this.updateColumnCounts();
                } else {
                    // Revert on error
                    this.updateCardColumn(cardId, newColumn, oldColumn, card.position);
                    this.showError(response.data);
                }
            },
            error: () => {
                // Revert on error
                this.updateCardColumn(cardId, newColumn, oldColumn, card.position);
                this.showError('خطا در انتقال کارت');
            }
        });
    }
    
    updateCardColumn(cardId, oldColumn, newColumn, newPosition) {
        // Remove from old column
        this.cards[oldColumn] = this.cards[oldColumn].filter(card => card.id != cardId);
        
        // Add to new column
        const card = this.findCardById(cardId);
        if (card) {
            card.column = newColumn;
            card.position = newPosition;
            this.cards[newColumn].push(card);
        }
        
        // Re-render affected columns
        this.renderColumnCards(oldColumn);
        this.renderColumnCards(newColumn);
    }
    
    renderColumnCards(columnId) {
        const columnElement = document.getElementById(`column-${columnId}`);
        const cards = this.cards[columnId] || [];
        
        columnElement.innerHTML = cards.map(card => this.createCardElement(card)).join('');
        
        // Re-initialize drag and drop
        this.initializeDragAndDrop();
    }
    
    findCardById(cardId) {
        for (const columnId in this.cards) {
            const card = this.cards[columnId].find(c => c.id == cardId);
            if (card) return card;
        }
        return null;
    }
    
    updateColumnCounts() {
        this.columns.forEach(column => {
            const count = this.cards[column.id] ? this.cards[column.id].length : 0;
            document.getElementById(`count-${column.id}`).textContent = count;
        });
    }
    
    showAddCardModal() {
        // Simple implementation for now
        alert('افزودن کارت جدید');
    }
    
    refreshBoard() {
        this.loadCards();
    }
    
    showLoading() {
        document.getElementById('kanban-loading').style.display = 'flex';
    }
    
    hideLoading() {
        document.getElementById('kanban-loading').style.display = 'none';
    }
    
    showError(message) {
        console.error('Kanban error:', message);
    }
}

// Initialize when DOM is ready
jQuery(document).ready(() => {
    if (typeof puzzlingcrm_ajax_obj !== 'undefined') {
        window.puzzlingKanbanBoard = new PuzzlingCRM_Kanban_Board();
    }
});
