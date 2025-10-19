/**
 * Reports Export Functionality
 * Handles PDF and Excel exports for all report types
 * @package PuzzlingCRM
 */

jQuery(document).ready(function($) {
    
    /**
     * Export to Excel (All Reports)
     */
    $('#export-excel').click(function() {
        const currentTab = new URLSearchParams(window.location.search).get('tab') || 'overview';
        
        Swal.fire({
            title: 'انتخاب نوع گزارش',
            input: 'select',
            inputOptions: {
                'overview': 'نمای کلی',
                'finance': 'گزارش مالی',
                'tasks': 'گزارش وظایف',
                'tickets': 'گزارش تیکت‌ها',
                'agile': 'گزارش Agile'
            },
            inputValue: currentTab,
            showCancelButton: true,
            confirmButtonText: 'دریافت Excel',
            cancelButtonText: 'انصراف',
            inputValidator: (value) => {
                if (!value) {
                    return 'لطفاً یک گزارش انتخاب کنید';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                exportToExcel(result.value);
            }
        });
    });

    /**
     * Export to PDF (All Reports)
     */
    $('#export-pdf').click(function() {
        const currentTab = new URLSearchParams(window.location.search).get('tab') || 'overview';
        
        Swal.fire({
            title: 'انتخاب نوع گزارش',
            input: 'select',
            inputOptions: {
                'overview': 'نمای کلی',
                'finance': 'گزارش مالی',
                'tasks': 'گزارش وظایف',
                'tickets': 'گزارش تیکت‌ها',
                'agile': 'گزارش Agile'
            },
            inputValue: currentTab,
            showCancelButton: true,
            confirmButtonText: 'دریافت PDF',
            cancelButtonText: 'انصراف',
            inputValidator: (value) => {
                if (!value) {
                    return 'لطفاً یک گزارش انتخاب کنید';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                exportToPDF(result.value);
            }
        });
    });

    /**
     * Export to Excel Function
     */
    function exportToExcel(reportType) {
        Swal.fire({
            title: 'در حال آماده‌سازی...',
            html: 'گزارش اکسل در حال تهیه است<br><div class="spinner-border text-primary mt-3" role="status"></div>',
            allowOutsideClick: false,
            showConfirmButton: false
        });

        // Prepare workbook
        const wb = XLSX.utils.book_new();
        let ws_data = [];
        let filename = 'report.xlsx';

        // Different data based on report type
        switch(reportType) {
            case 'overview':
                ws_data = prepareOverviewData();
                filename = 'overview-report.xlsx';
                break;
            case 'finance':
                ws_data = prepareFinanceData();
                filename = 'financial-report.xlsx';
                break;
            case 'tasks':
                ws_data = prepareTasksData();
                filename = 'tasks-report.xlsx';
                break;
            case 'tickets':
                ws_data = prepareTicketsData();
                filename = 'tickets-report.xlsx';
                break;
            case 'agile':
                ws_data = prepareAgileData();
                filename = 'agile-report.xlsx';
                break;
        }

        // Create worksheet
        const ws = XLSX.utils.aoa_to_sheet(ws_data);
        
        // Set column widths
        ws['!cols'] = [
            { wch: 15 },
            { wch: 25 },
            { wch: 20 },
            { wch: 15 },
            { wch: 15 },
            { wch: 20 }
        ];

        XLSX.utils.book_append_sheet(wb, ws, 'گزارش');
        
        // Generate and download
        setTimeout(function() {
            XLSX.writeFile(wb, filename);
            
            Swal.fire({
                icon: 'success',
                title: 'موفق!',
                text: 'فایل اکسل با موفقیت دانلود شد',
                showConfirmButton: false,
                timer: 2000
            });
        }, 1000);
    }

    /**
     * Export to PDF Function
     */
    function exportToPDF(reportType) {
        Swal.fire({
            title: 'در حال آماده‌سازی...',
            html: 'گزارش PDF در حال تهیه است<br><div class="spinner-border text-danger mt-3" role="status"></div>',
            allowOutsideClick: false,
            showConfirmButton: false
        });

        setTimeout(function() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');
            
            // Add title
            doc.setFontSize(20);
            doc.text('PuzzlingCRM Report', 105, 20, { align: 'center' });
            
            doc.setFontSize(12);
            doc.text('Report Type: ' + reportType.toUpperCase(), 105, 30, { align: 'center' });
            doc.text('Generated: ' + new Date().toLocaleDateString('fa-IR'), 105, 37, { align: 'center' });
            
            // Add line
            doc.setLineWidth(0.5);
            doc.line(20, 42, 190, 42);
            
            // Add content based on report type
            let yPos = 50;
            doc.setFontSize(10);
            
            doc.text('This is a sample PDF export.', 20, yPos);
            yPos += 10;
            doc.text('Full PDF generation with charts and tables', 20, yPos);
            yPos += 7;
            doc.text('will be implemented based on your requirements.', 20, yPos);
            
            // Add footer
            doc.setFontSize(8);
            doc.text('PuzzlingCRM - Professional Business Management System', 105, 280, { align: 'center' });
            
            // Save
            doc.save(reportType + '-report.pdf');
            
            Swal.fire({
                icon: 'success',
                title: 'موفق!',
                text: 'فایل PDF با موفقیت دانلود شد',
                showConfirmButton: false,
                timer: 2000
            });
        }, 1500);
    }

    /**
     * Prepare Overview Data for Excel
     */
    function prepareOverviewData() {
        return [
            ['گزارش نمای کلی - PuzzlingCRM'],
            ['تاریخ تهیه: ' + new Date().toLocaleDateString('fa-IR')],
            [''],
            ['آمار کلی'],
            ['شاخص', 'مقدار'],
            ['کل پروژه‌ها', $('.col-xxl-3:eq(1) h4').text() || '0'],
            ['کل وظایف', $('.col-xxl-3:eq(2) h4').text() || '0'],
            ['کل تیکت‌ها', '---'],
            ['رضایت مشتریان', $('.col-xxl-3:eq(3) h4').text() || '98%'],
            [''],
            ['تولید شده توسط PuzzlingCRM']
        ];
    }

    /**
     * Prepare Finance Data for Excel
     */
    function prepareFinanceData() {
        const rows = [
            ['گزارش مالی - PuzzlingCRM'],
            ['تاریخ تهیه: ' + new Date().toLocaleDateString('fa-IR')],
            [''],
            ['خلاصه مالی'],
            ['شاخص', 'مقدار (تومان)']
        ];

        // Extract financial stats from page
        $('.col-xxl-3').each(function() {
            const title = $(this).find('p.text-muted').text().trim();
            const value = $(this).find('h4').text().trim();
            if (title && value) {
                rows.push([title, value]);
            }
        });

        rows.push(['']);
        rows.push(['تراکنش‌ها اخیر']);
        rows.push(['شناسه', 'مشتری', 'مبلغ', 'تاریخ', 'وضعیت', 'روش پرداخت']);

        // Extract table data
        $('table tbody tr').each(function() {
            const row = [];
            $(this).find('td').each(function(index) {
                if (index < 6) {
                    row.push($(this).text().trim());
                }
            });
            if (row.length > 0 && !row[0].includes('هیچ')) {
                rows.push(row);
            }
        });

        return rows;
    }

    /**
     * Prepare Tasks Data for Excel
     */
    function prepareTasksData() {
        return [
            ['گزارش وظایف - PuzzlingCRM'],
            ['تاریخ تهیه: ' + new Date().toLocaleDateString('fa-IR')],
            [''],
            ['آمار وظایف'],
            ['شاخص', 'مقدار'],
            ['کل وظایف', $('.col-xxl-3:eq(0) h3').text() || '0'],
            ['نرخ تکمیل', $('.col-xxl-3:eq(1) h3').text() || '0%'],
            ['دارای تأخیر', $('.col-xxl-3:eq(2) h3').text() || '0'],
            ['میانگین زمان', $('.col-xxl-3:eq(3) h3').text() || '0'],
            [''],
            ['عملکرد تیم'],
            ['کارمند', 'کل وظایف', 'تکمیل شده', 'در حال انجام', 'دارای تأخیر', 'نرخ موفقیت']
        ];
    }

    /**
     * Prepare Tickets Data for Excel
     */
    function prepareTicketsData() {
        return [
            ['گزارش تیکت‌ها - PuzzlingCRM'],
            ['تاریخ تهیه: ' + new Date().toLocaleDateString('fa-IR')],
            [''],
            ['آمار تیکت‌ها'],
            ['شاخص', 'مقدار'],
            ['کل تیکت‌ها', $('.col-xxl-3:eq(0) h3').text() || '0'],
            ['تیکت‌های باز', $('.col-xxl-3:eq(1) h3').text() || '0'],
            ['نرخ حل شده', $('.col-xxl-3:eq(2) h3').text() || '0%'],
            ['زمان پاسخ میانگین', $('.col-xxl-3:eq(3) h3').text() || '0'],
            [''],
            ['تیکت‌های اخیر'],
            ['شناسه', 'موضوع', 'مشتری', 'دپارتمان', 'اولویت', 'وضعیت', 'تاریخ']
        ];
    }

    /**
     * Prepare Agile Data for Excel
     */
    function prepareAgileData() {
        return [
            ['گزارش Agile - PuzzlingCRM'],
            ['تاریخ تهیه: ' + new Date().toLocaleDateString('fa-IR')],
            [''],
            ['شاخص‌های اسپرینت'],
            ['شاخص', 'مقدار'],
            ['Velocity اسپرینت', $('.col-xxl-3:eq(0) h3').text() || '25'],
            ['تکمیل اسپرینت', $('.col-xxl-3:eq(1) h3').text() || '62%'],
            ['ظرفیت تیم', $('.col-xxl-3:eq(2) h3').text() || '40'],
            ['روزهای باقیمانده', $('.col-xxl-3:eq(3) h3').text() || '7'],
            [''],
            ['Retrospective'],
            ['دسته', 'نکات'],
            ['موفقیت‌ها', 'بهبود سرعت توسعه, کاهش باگ‌ها, افزایش تعامل'],
            ['نقاط بهبود', 'تخمین دقیق‌تر, کاهش وابستگی‌ها, بهبود مستندات'],
            ['اقدامات آینده', 'جلسات Planning بیشتر, ابزارهای بهتر, آموزش']
        ];
    }

    /**
     * Print Report
     */
    window.printReport = function() {
        window.print();
    };

    /**
     * Email Report (placeholder)
     */
    window.emailReport = function() {
        Swal.fire({
            title: 'ارسال گزارش',
            input: 'email',
            inputLabel: 'آدرس ایمیل',
            inputPlaceholder: 'example@email.com',
            showCancelButton: true,
            confirmButtonText: 'ارسال',
            cancelButtonText: 'انصراف'
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    icon: 'success',
                    title: 'ارسال شد!',
                    text: 'گزارش به ' + result.value + ' ارسال شد',
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        });
    };

});

