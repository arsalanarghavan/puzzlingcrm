<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="pzl-dashboard-section">
    <h3><span class="dashicons dashicons-list-view"></span> مدیریت وظایف کل سیستم</h3>
    <?php
    // This template is already built for team members, we can reuse it with a flag
    // for the admin view.
    // For a true admin view, this would be a more complex template showing ALL tasks.
    include PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/dashboard-team-member.php';
    ?>
</div>