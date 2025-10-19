/**
 * Time Tracking System
 * Comprehensive time tracking for projects and tasks
 */

class PuzzlingCRM_Time_Tracking {
    constructor() {
        this.container = null;
        this.activeTimer = null;
        this.timerInterval = null;
        this.isRunning = false;
        
        this.init();
    }
    
    init() {
        this.createTimeTrackingInterface();
        this.bindEvents();
        this.loadActiveTimer();
        this.startAutoSave();
    }
    
    createTimeTrackingInterface() {
        if (!document.getElementById('puzzling-time-tracking')) {
            const timeTrackingContainer = document.createElement('div');
            timeTrackingContainer.id = 'puzzling-time-tracking';
            timeTrackingContainer.className = 'puzzling-time-tracking';
            timeTrackingContainer.innerHTML = `
                <div class="time-tracking-header">
                    <h3>ردیابی زمان</h3>
                    <div class="timer-controls">
                        <button class="btn-start-timer" id="start-timer">
                            <i class="ri-play-circle-line"></i>
                            شروع تایمر
                        </button>
                        <button class="btn-stop-timer" id="stop-timer" style="display: none;">
                            <i class="ri-stop-circle-line"></i>
                            توقف تایمر
                        </button>
                    </div>
                </div>
                
                <div class="time-tracking-content">
                    <div class="timer-display" id="timer-display" style="display: none;">
                        <div class="timer-time" id="timer-time">00:00:00</div>
                        <div class="timer-info" id="timer-info">
                            <div class="timer-project" id="timer-project"></div>
                            <div class="timer-description" id="timer-description"></div>
                        </div>
                    </div>
                    
                    <div class="timer-form" id="timer-form">
                        <div class="form-group">
                            <label for="timer-project-select">پروژه:</label>
                            <select id="timer-project-select">
                                <option value="0">انتخاب پروژه</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="timer-category">دسته‌بندی:</label>
                            <select id="timer-category">
                                <option value="work">کار</option>
                                <option value="meeting">جلسه</option>
                                <option value="break">استراحت</option>
                                <option value="training">آموزش</option>
                                <option value="other">سایر</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="timer-description">توضیحات:</label>
                            <textarea id="timer-description" rows="3" placeholder="توضیحات کار انجام شده..."></textarea>
                        </div>
                    </div>
                </div>
            `;
            
            // Insert into page
            const targetElement = document.querySelector('.wrap') || document.querySelector('main') || document.body;
            targetElement.appendChild(timeTrackingContainer);
        }
        
        this.container = document.getElementById('puzzling-time-tracking');
    }
    
    bindEvents() {
        // Timer controls
        document.getElementById('start-timer').addEventListener('click', () => {
            this.startTimer();
        });
        
        document.getElementById('stop-timer').addEventListener('click', () => {
            this.stopTimer();
        });
    }
    
    loadActiveTimer() {
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'GET',
            data: {
                action: 'puzzling_get_active_timer',
                nonce: puzzlingcrm_ajax_obj.nonce
            },
            success: (response) => {
                if (response.success && response.data) {
                    this.activeTimer = response.data;
                    this.updateTimerDisplay();
                    this.startTimerInterval();
                } else {
                    this.hideTimerDisplay();
                }
            }
        });
    }
    
    startTimer() {
        const projectId = document.getElementById('timer-project-select').value;
        const category = document.getElementById('timer-category').value;
        const description = document.getElementById('timer-description').value;
        
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_start_timer',
                nonce: puzzlingcrm_ajax_obj.nonce,
                project_id: projectId,
                category: category,
                description: description
            },
            success: (response) => {
                if (response.success) {
                    this.activeTimer = {
                        session_id: response.data.session_id,
                        project_id: projectId,
                        category: category,
                        description: description,
                        start_time: new Date().toISOString(),
                        status: 'running'
                    };
                    
                    this.updateTimerDisplay();
                    this.startTimerInterval();
                    this.showSuccess(response.data.message);
                } else {
                    this.showError(response.data);
                }
            }
        });
    }
    
    stopTimer() {
        if (!this.activeTimer) return;
        
        const description = document.getElementById('timer-description').value;
        
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_stop_timer',
                nonce: puzzlingcrm_ajax_obj.nonce,
                session_id: this.activeTimer.session_id,
                description: description
            },
            success: (response) => {
                if (response.success) {
                    this.activeTimer = null;
                    this.hideTimerDisplay();
                    this.stopTimerInterval();
                    this.showSuccess(response.data.message);
                } else {
                    this.showError(response.data);
                }
            }
        });
    }
    
    startTimerInterval() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
        }
        
        this.timerInterval = setInterval(() => {
            this.updateTimerDisplay();
        }, 1000);
    }
    
    stopTimerInterval() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
    }
    
    updateTimerDisplay() {
        if (!this.activeTimer) {
            this.hideTimerDisplay();
            return;
        }
        
        const timerDisplay = document.getElementById('timer-display');
        const timerTime = document.getElementById('timer-time');
        const timerProject = document.getElementById('timer-project');
        const timerDescription = document.getElementById('timer-description');
        
        timerDisplay.style.display = 'block';
        
        // Calculate current duration
        const startTime = new Date(this.activeTimer.start_time);
        const currentTime = new Date();
        const duration = Math.floor((currentTime - startTime) / 1000);
        
        timerTime.textContent = this.formatDuration(duration);
        timerProject.textContent = this.getProjectName(this.activeTimer.project_id);
        timerDescription.textContent = this.activeTimer.description;
        
        this.updateTimerControls();
    }
    
    hideTimerDisplay() {
        document.getElementById('timer-display').style.display = 'none';
        document.getElementById('start-timer').style.display = 'inline-flex';
        document.getElementById('stop-timer').style.display = 'none';
    }
    
    updateTimerControls() {
        if (!this.activeTimer) {
            this.hideTimerDisplay();
            return;
        }
        
        document.getElementById('start-timer').style.display = 'none';
        document.getElementById('stop-timer').style.display = 'inline-flex';
    }
    
    getProjectName(projectId) {
        const select = document.getElementById('timer-project-select');
        const option = select.querySelector(`option[value="${projectId}"]`);
        return option ? option.textContent : 'پروژه نامشخص';
    }
    
    formatDuration(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hours > 0) {
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        } else {
            return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
    }
    
    startAutoSave() {
        // Auto-save timer every 30 seconds
        setInterval(() => {
            if (this.activeTimer && this.activeTimer.status === 'running') {
                this.autoSaveTime();
            }
        }, 30000);
    }
    
    autoSaveTime() {
        if (!this.activeTimer) return;
        
        jQuery.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_auto_save_time',
                nonce: puzzlingcrm_ajax_obj.nonce,
                session_id: this.activeTimer.session_id
            },
            success: (response) => {
                if (response.success) {
                    this.updateTimerDisplay();
                }
            }
        });
    }
    
    showSuccess(message) {
        console.log('Success:', message);
    }
    
    showError(message) {
        console.error('Error:', message);
    }
}

// Initialize when DOM is ready
jQuery(document).ready(() => {
    if (typeof puzzlingcrm_ajax_obj !== 'undefined') {
        window.puzzlingTimeTracking = new PuzzlingCRM_Time_Tracking();
    }
});
