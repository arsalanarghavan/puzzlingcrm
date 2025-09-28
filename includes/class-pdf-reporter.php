<?php
// File: includes/class-pdf-reporter.php

require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/lib/fpdf/fpdf.php';

class PuzzlingCRM_PDF_Reporter extends FPDF {
    // Page header
    function Header() {
        // Logo
        $logo_path = PUZZLINGCRM_PLUGIN_DIR . 'assets/images/logo.png';
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 10, 8, 33);
        }
        
        // Add Vazirmatn font for Persian support
        // Note: You need to generate the font file using FPDF tutorials if it doesn't work out of the box.
        // For simplicity, we use Arial here. For full Persian support, font conversion is needed.
        $this->SetFont('Arial', 'B', 15);
        
        // Move to the right
        $this->Cell(80);
        
        // Title
        $header_text = iconv('UTF-8', 'windows-1252', 'گزارش کار روزانه');
        $this->Cell(30, 10, $header_text, 1, 0, 'C');
        
        // Line break
        $this->Ln(20);
    }

    // Page footer
    function Footer() {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        
        // Page number
        $page_num_text = 'Page ' . $this->PageNo() . '/{nb}';
        $this->Cell(0, 10, $page_num_text, 0, 0, 'C');
    }

    // A method to create the task table
    function TaskTable($header, $data) {
        // Column widths
        $w = array(30, 95, 30, 35);
        
        // Header
        for($i=0; $i<count($header); $i++) {
            $this->Cell($w[$i], 7, iconv('UTF-8', 'windows-1252', $header[$i]), 1, 0, 'C');
        }
        $this->Ln();
        
        // Data
        foreach($data as $row) {
            $this->Cell($w[0], 6, iconv('UTF-8', 'windows-1252', $row[0]), 'LR');
            $this->Cell($w[1], 6, iconv('UTF-8', 'windows-1252', $row[1]), 'LR');
            $this->Cell($w[2], 6, iconv('UTF-8', 'windows-1252', $row[2]), 'LR', 0, 'L');
            $this->Cell($w[3], 6, iconv('UTF-8', 'windows-1252', $row[3]), 'LR', 0, 'L');
            $this->Ln();
        }
        // Closing line
        $this->Cell(array_sum($w), 0, '', 'T');
    }
}