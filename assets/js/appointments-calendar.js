/**
 * Appointments Calendar View
 * @package PuzzlingCRM
 */

jQuery(document).ready(function($) {
    
    const calendarEl = document.getElementById('appointments-calendar');
    
    if (calendarEl) {
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'fa',
            direction: 'rtl',
            firstDay: 6, // Saturday
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            buttonText: {
                today: 'امروز',
                month: 'ماه',
                week: 'هفته',
                day: 'روز',
                list: 'لیست'
            },
            weekends: true,
            editable: true,
            selectable: true,
            selectMirror: true,
            dayMaxEvents: true,
            
            // Load appointments
            events: function(info, successCallback, failureCallback) {
                $.ajax({
                    url: puzzlingcrm_ajax_obj.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'puzzling_get_appointments_calendar',
                        security: puzzlingcrm_ajax_obj.nonce,
                        start: info.startStr,
                        end: info.endStr
                    },
                    success: function(response) {
                        if (response.success) {
                            successCallback(response.data.events);
                        } else {
                            failureCallback();
                        }
                    },
                    error: failureCallback
                });
            },
            
            // Click on date to create appointment
            select: function(info) {
                Swal.fire({
                    title: 'ایجاد قرار ملاقات جدید',
                    html: `
                        <div class="text-start">
                            <div class="mb-3">
                                <label class="form-label">مشتری</label>
                                <select id="appointment-customer" class="form-select">
                                    <option value="">انتخاب مشتری</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">موضوع</label>
                                <input type="text" id="appointment-title" class="form-control" placeholder="موضوع قرار ملاقات">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ساعت</label>
                                <input type="time" id="appointment-time" class="form-control" value="10:00">
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'ایجاد',
                    cancelButtonText: 'انصراف',
                    didOpen: () => {
                        // Load customers
                        $.ajax({
                            url: puzzlingcrm_ajax_obj.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'puzzling_get_customers_list',
                                security: puzzlingcrm_ajax_obj.nonce
                            },
                            success: function(response) {
                                if (response.success) {
                                    const select = $('#appointment-customer');
                                    response.data.customers.forEach(customer => {
                                        select.append(`<option value="${customer.id}">${customer.name}</option>`);
                                    });
                                }
                            }
                        });
                    },
                    preConfirm: () => {
                        const customer = $('#appointment-customer').val();
                        const title = $('#appointment-title').val();
                        const time = $('#appointment-time').val();
                        
                        if (!customer || !title || !time) {
                            Swal.showValidationMessage('لطفاً تمام فیلدها را پر کنید');
                            return false;
                        }
                        
                        return { customer, title, time };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        createAppointment(info.startStr, result.value);
                    }
                    calendar.unselect();
                });
            },
            
            // Click on event to edit
            eventClick: function(info) {
                const event = info.event;
                
                Swal.fire({
                    title: event.title,
                    html: `
                        <div class="text-start">
                            <p><i class="ri-calendar-line me-2"></i><strong>تاریخ:</strong> ${event.start.toLocaleDateString('fa-IR')}</p>
                            <p><i class="ri-time-line me-2"></i><strong>ساعت:</strong> ${event.start.toLocaleTimeString('fa-IR', {hour: '2-digit', minute: '2-digit'})}</p>
                            <p><i class="ri-user-line me-2"></i><strong>مشتری:</strong> ${event.extendedProps.customer || '---'}</p>
                        </div>
                    `,
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'ویرایش',
                    denyButtonText: 'حذف',
                    cancelButtonText: 'بستن'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '?view=appointments&action=edit&appt_id=' + event.id;
                    } else if (result.isDenied) {
                        deleteAppointment(event.id);
                    }
                });
            },
            
            // Drag and drop to reschedule
            eventDrop: function(info) {
                updateAppointmentDate(info.event.id, info.event.startStr);
            }
        });
        
        calendar.render();
    }
    
    /**
     * Create Appointment
     */
    function createAppointment(date, data) {
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_quick_create_appointment',
                security: puzzlingcrm_ajax_obj.nonce,
                customer_id: data.customer,
                title: data.title,
                date: date,
                time: data.time
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'موفق!',
                        text: 'قرار ملاقات ایجاد شد',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    
                    // Reload calendar
                    if (calendar) {
                        calendar.refetchEvents();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: response.data.message
                    });
                }
            }
        });
    }
    
    /**
     * Delete Appointment
     */
    function deleteAppointment(appointmentId) {
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_delete_appointment',
                security: puzzlingcrm_ajax_obj.nonce,
                appointment_id: appointmentId
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'حذف شد',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    
                    if (calendar) {
                        calendar.refetchEvents();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: response.data.message
                    });
                }
            }
        });
    }
    
    /**
     * Update Appointment Date
     */
    function updateAppointmentDate(appointmentId, newDate) {
        $.ajax({
            url: puzzlingcrm_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'puzzling_update_appointment_date',
                security: puzzlingcrm_ajax_obj.nonce,
                appointment_id: appointmentId,
                new_date: newDate
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'به‌روزرسانی شد',
                        showConfirmButton: false,
                        timer: 1000
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: response.data.message
                    });
                    calendar.refetchEvents();
                }
            }
        });
    }
});

