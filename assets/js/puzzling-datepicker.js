jQuery(document).ready(function($) {
    /**
     * A lightweight, dependency-free Jalali Date Picker for PuzzlingCRM.
     */
    function PuzzlingJalaliDatePicker() {
        // Helper function to convert Gregorian to Jalali
        function toJalali(gy, gm, gd) {
            var g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
            var jy = (gy <= 1600) ? 0 : 979;
            gy -= (gy <= 1600) ? 621 : 1600;
            var gy2 = (gm > 2) ? (gy + 1) : gy;
            var days = 365 * gy + parseInt((gy2 + 3) / 4) - parseInt((gy2 + 99) / 100) + parseInt((gy2 + 399) / 400) - 80 + gd + g_d_m[gm - 1];
            jy += 33 * parseInt(days / 12053);
            days %= 12053;
            jy += 4 * parseInt(days / 1461);
            days %= 1461;
            jy += parseInt((days - 1) / 365);
            if (days > 365) days = (days - 1) % 365;
            var jm = (days < 186) ? 1 + parseInt(days / 31) : 7 + parseInt((days - 186) / 30);
            var jd = 1 + ((days < 186) ? (days % 31) : ((days - 186) % 30));
            return [jy, jm, jd];
        }

        // Helper function to convert Jalali to Gregorian
        function toGregorian(jy, jm, jd) {
            var gy = (jy <= 979) ? 621 : 1600;
            jy -= (jy <= 979) ? 0 : 979;
            var days = 365 * jy + parseInt(jy / 33) * 8 + parseInt(((jy % 33) + 3) / 4) + 78 + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
            gy += 400 * parseInt(days / 146097);
            days %= 146097;
            if (days > 36524) {
                gy += 100 * parseInt(--days / 36524);
                days %= 36524;
                if (days >= 365) days++;
            }
            gy += 4 * parseInt(days / 1461);
            days %= 1461;
            gy += parseInt((days - 1) / 365);
            if (days > 365) days = (days - 1) % 365;
            var gd = days + 1;
            var sal_a = [0, 31, ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
            for (var gm = 0; gm < 13 && gd > sal_a[gm]; gm++) gd -= sal_a[gm];
            return [gy, gm, gd];
        }

        const monthNames = ["فروردین", "اردیبهشت", "خرداد", "تیر", "مرداد", "شهریور", "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"];
        const dayNames = ["ش", "ی", "د", "س", "چ", "پ", "ج"];

        let today = new Date();
        let todayJalali = toJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());

        function createPicker(input) {
            if (input.data('pzl-datepicker-active')) return;
            input.data('pzl-datepicker-active', true);
            
            // Set autocomplete to off to prevent browser's datepicker
            input.attr('autocomplete', 'off');

            let currentYear = todayJalali[0];
            let currentMonth = todayJalali[1];

            const pickerContainer = $('<div class="pzl-datepicker-container"></div>');
            const pickerHeader = $('<div class="pzl-datepicker-header"></div>').appendTo(pickerContainer);
            const prevMonthBtn = $('<button type="button" class="pzl-datepicker-prev">&lt;</button>').appendTo(pickerHeader);
            const monthYearDisplay = $('<span class="pzl-datepicker-month-year"></span>').appendTo(pickerHeader);
            const nextMonthBtn = $('<button type="button" class="pzl-datepicker-next">&gt;</button>').appendTo(pickerHeader);
            const calendarGrid = $('<div class="pzl-datepicker-grid"></div>').appendTo(pickerContainer);
            const todayBtn = $('<button type="button" class="pzl-datepicker-today">امروز</button>').appendTo(pickerContainer);

            $('body').append(pickerContainer);

            function renderCalendar(year, month) {
                monthYearDisplay.text(monthNames[month - 1] + ' ' + year);
                calendarGrid.empty();

                dayNames.forEach(day => calendarGrid.append(`<div class="pzl-datepicker-day-name">${day}</div>`));
                
                let gregorianStartDate = toGregorian(year, month, 1);
                let firstDayOfWeek = new Date(gregorianStartDate[0], gregorianStartDate[1] - 1, gregorianStartDate[2]).getDay();
                firstDayOfWeek = (firstDayOfWeek + 1) % 7;

                let daysInMonth = (month <= 6) ? 31 : (month <= 11) ? 30 : (toGregorian(year + 1, 1, 1)[0] - toGregorian(year, 1, 1)[0] > 365) ? 30 : 29;

                for (let i = 0; i < firstDayOfWeek; i++) {
                    calendarGrid.append('<div class="pzl-datepicker-day empty"></div>');
                }

                for (let day = 1; day <= daysInMonth; day++) {
                    let dayCell = $(`<div class="pzl-datepicker-day">${day}</div>`);
                    if (year === todayJalali[0] && month === todayJalali[1] && day === todayJalali[2]) {
                        dayCell.addClass('today');
                    }
                    dayCell.on('click', function() {
                        let selectedDate = `${year}/${String(month).padStart(2, '0')}/${String(day).padStart(2, '0')}`;
                        input.val(selectedDate);
                        pickerContainer.hide();
                    });
                    calendarGrid.append(dayCell);
                }
            }
            
            function showPicker() {
                $('.pzl-datepicker-container').hide(); // Hide any other open pickers
                let offset = input.offset();
                pickerContainer.css({ top: offset.top + input.outerHeight() + 5, left: offset.left, right: 'auto' }).show();
                 // Adjust position if it goes off-screen
                if(offset.left + pickerContainer.width() > $(window).width()) {
                    pickerContainer.css({left: 'auto', right: $(window).width() - (offset.left + input.outerWidth())});
                }
            }

            prevMonthBtn.on('click', function() {
                currentMonth--;
                if (currentMonth < 1) { currentMonth = 12; currentYear--; }
                renderCalendar(currentYear, currentMonth);
            });

            nextMonthBtn.on('click', function() {
                currentMonth++;
                if (currentMonth > 12) { currentMonth = 1; currentYear++; }
                renderCalendar(currentYear, currentMonth);
            });
            
            todayBtn.on('click', function() {
                let todayStr = `${todayJalali[0]}/${String(todayJalali[1]).padStart(2, '0')}/${String(todayJalali[2]).padStart(2, '0')}`;
                input.val(todayStr);
                pickerContainer.hide();
            });

            input.on('focus', function() {
                let dateValue = $(this).val();
                if (dateValue && /^\d{4}\/\d{1,2}\/\d{1,2}$/.test(dateValue)) {
                    let parts = dateValue.split('/');
                    currentYear = parseInt(parts[0]);
                    currentMonth = parseInt(parts[1]);
                } else {
                    currentYear = todayJalali[0];
                    currentMonth = todayJalali[1];
                }
                renderCalendar(currentYear, currentMonth);
                showPicker();
            });
        }
        
        function initializeAllPickers() {
             $('.pzl-jalali-date-picker').each(function() {
                createPicker($(this));
            });
        }
        
        initializeAllPickers();

        // Use a MutationObserver to initialize datepickers on dynamically added content (e.g., in modals or AJAX-loaded forms)
        const observer = new MutationObserver(function(mutationsList) {
            for (const mutation of mutationsList) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                   initializeAllPickers();
                }
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
        
        // Hide picker when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.pzl-datepicker-container').length && !$(e.target).hasClass('pzl-jalali-date-picker')) {
                $('.pzl-datepicker-container').hide();
            }
        });
    }

    // Run the datepicker initializer
    PuzzlingJalaliDatePicker();
});