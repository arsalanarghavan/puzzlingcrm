<?php
/**
 * Language Debug Page
 * 
 * This file helps debug language detection issues.
 * Access it via: /wp-content/plugins/puzzlingcrm/debug-language.php
 * 
 * @package PuzzlingCRM
 */

// Load WordPress
require_once('../../../wp-load.php');

// Load plugin functions (includes helper functions)
if (defined('PUZZLINGCRM_PLUGIN_DIR')) {
	require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/puzzling-functions.php';
	
	// Load helper classes
	if (file_exists(PUZZLINGCRM_PLUGIN_DIR . 'includes/helpers/class-number-formatter.php')) {
		require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/helpers/class-number-formatter.php';
	}
	if (file_exists(PUZZLINGCRM_PLUGIN_DIR . 'includes/helpers/class-date-formatter.php')) {
		require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/helpers/class-date-formatter.php';
	}
}

// Check if user is logged in
if (!is_user_logged_in()) {
    die('Please log in first.');
}

$user_id = get_current_user_id();
$user = wp_get_current_user();

// Get all language-related values
$user_meta_lang = get_user_meta($user_id, 'pzl_language', true);
$cookie_lang = isset($_COOKIE['pzl_language']) ? sanitize_text_field($_COOKIE['pzl_language']) : '';
$wp_locale = get_locale();

// Get from helper classes if available
$number_formatter_locale = '';
$number_formatter_is_persian = '';
if (class_exists('PuzzlingCRM_Number_Formatter')) {
    if (method_exists('PuzzlingCRM_Number_Formatter', 'get_locale')) {
        $number_formatter_locale = PuzzlingCRM_Number_Formatter::get_locale();
    }
    if (method_exists('PuzzlingCRM_Number_Formatter', 'is_persian')) {
        $number_formatter_is_persian = PuzzlingCRM_Number_Formatter::is_persian() ? 'true' : 'false';
    }
}

// Get from helper function if available
$helper_lang = '';
if (function_exists('pzl_get_current_language')) {
    $helper_lang = pzl_get_current_language();
}

// Test number formatting
$test_number = 1234567.89;
$formatted_number = '';
if (function_exists('pzl_format_number')) {
    $formatted_number = pzl_format_number($test_number, 2);
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PuzzlingCRM - Language Debug</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .debug-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 10px;
        }
        .debug-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #0073aa;
            border-radius: 4px;
        }
        .debug-section h2 {
            margin-top: 0;
            color: #0073aa;
            font-size: 18px;
        }
        .debug-item {
            margin: 10px 0;
            padding: 8px;
            background: white;
            border-radius: 4px;
        }
        .debug-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            min-width: 200px;
        }
        .debug-value {
            color: #333;
            font-family: monospace;
            padding: 4px 8px;
            background: #e8e8e8;
            border-radius: 3px;
        }
        .debug-value.empty {
            color: #999;
            font-style: italic;
        }
        .debug-value.true {
            color: #46b450;
        }
        .debug-value.false {
            color: #dc3232;
        }
        .test-result {
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
        }
        .test-result h3 {
            margin-top: 0;
            color: #856404;
        }
        .recommendation {
            margin-top: 20px;
            padding: 15px;
            background: #d1ecf1;
            border: 1px solid #0c5460;
            border-radius: 4px;
        }
        .recommendation h3 {
            margin-top: 0;
            color: #0c5460;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <h1>🔍 PuzzlingCRM Language Debug</h1>
        
        <div class="debug-section">
            <h2>User Information</h2>
            <div class="debug-item">
                <span class="debug-label">User ID:</span>
                <span class="debug-value"><?php echo esc_html($user_id); ?></span>
            </div>
            <div class="debug-item">
                <span class="debug-label">Username:</span>
                <span class="debug-value"><?php echo esc_html($user->user_login); ?></span>
            </div>
            <div class="debug-item">
                <span class="debug-label">Display Name:</span>
                <span class="debug-value"><?php echo esc_html($user->display_name); ?></span>
            </div>
        </div>

        <div class="debug-section">
            <h2>Language Detection Sources</h2>
            <div class="debug-item">
                <span class="debug-label">User Meta (pzl_language):</span>
                <span class="debug-value <?php echo empty($user_meta_lang) ? 'empty' : ''; ?>">
                    <?php echo empty($user_meta_lang) ? '(empty/not set)' : esc_html($user_meta_lang); ?>
                </span>
            </div>
            <div class="debug-item">
                <span class="debug-label">Cookie (pzl_language):</span>
                <span class="debug-value <?php echo empty($cookie_lang) ? 'empty' : ''; ?>">
                    <?php echo empty($cookie_lang) ? '(empty/not set)' : esc_html($cookie_lang); ?>
                </span>
            </div>
            <div class="debug-item">
                <span class="debug-label">WordPress Locale:</span>
                <span class="debug-value"><?php echo esc_html($wp_locale); ?></span>
            </div>
        </div>

        <div class="debug-section">
            <h2>Helper Class Results</h2>
            <div class="debug-item">
                <span class="debug-label">Number_Formatter::get_locale():</span>
                <span class="debug-value <?php echo empty($number_formatter_locale) ? 'empty' : ''; ?>">
                    <?php echo empty($number_formatter_locale) ? '(not available)' : esc_html($number_formatter_locale); ?>
                </span>
            </div>
            <div class="debug-item">
                <span class="debug-label">Number_Formatter::is_persian():</span>
                <span class="debug-value <?php echo $number_formatter_is_persian === 'true' ? 'true' : ($number_formatter_is_persian === 'false' ? 'false' : 'empty'); ?>">
                    <?php echo empty($number_formatter_is_persian) ? '(not available)' : esc_html($number_formatter_is_persian); ?>
                </span>
            </div>
        </div>

        <div class="debug-section">
            <h2>Helper Function Results</h2>
            <div class="debug-item">
                <span class="debug-label">pzl_get_current_language():</span>
                <span class="debug-value <?php echo empty($helper_lang) ? 'empty' : ''; ?>">
                    <?php echo empty($helper_lang) ? '(not available)' : esc_html($helper_lang); ?>
                </span>
            </div>
        </div>

        <div class="test-result">
            <h3>🧪 Test: Number Formatting</h3>
            <div class="debug-item">
                <span class="debug-label">Test Number:</span>
                <span class="debug-value"><?php echo number_format($test_number, 2); ?></span>
            </div>
            <div class="debug-item">
                <span class="debug-label">Formatted Result:</span>
                <span class="debug-value"><?php echo esc_html($formatted_number); ?></span>
            </div>
            <div class="debug-item">
                <span class="debug-label">Expected (English):</span>
                <span class="debug-value">1,234,567.89</span>
            </div>
            <div class="debug-item">
                <span class="debug-label">Expected (Persian):</span>
                <span class="debug-value">۱,۲۳۴,۵۶۷.۸۹</span>
            </div>
        </div>

        <div class="recommendation">
            <h3>💡 Recommendations</h3>
            <ul>
                <li><strong>If User Meta is empty:</strong> The language preference hasn't been saved yet. Change the language using the language switcher in the header.</li>
                <li><strong>If Cookie is empty:</strong> The cookie might have expired or been cleared. Try changing the language again.</li>
                <li><strong>If WordPress Locale is Persian:</strong> This is the default WordPress locale. The system should use User Meta or Cookie if set.</li>
                <li><strong>If numbers are still Persian in English mode:</strong> Check if <code>pzl_get_current_language()</code> returns 'en'. If not, the detection logic needs to be fixed.</li>
            </ul>
        </div>

        <div class="debug-section">
            <h2>All Cookies</h2>
            <div class="debug-item">
                <pre style="background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto;"><?php print_r($_COOKIE); ?></pre>
            </div>
        </div>

        <div class="debug-section">
            <h2>All User Meta (pzl_*)</h2>
            <div class="debug-item">
                <?php
                $all_user_meta = get_user_meta($user_id);
                $pzl_meta = array_filter($all_user_meta, function($key) {
                    return strpos($key, 'pzl_') === 0;
                }, ARRAY_FILTER_USE_KEY);
                ?>
                <pre style="background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto;"><?php print_r($pzl_meta); ?></pre>
            </div>
        </div>
    </div>
</body>
</html>

