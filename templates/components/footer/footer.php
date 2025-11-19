<?php
/**
 * Footer Template
 * 
 * Main footer template for dashboard
 *
 * @package PuzzlingCRM
 * @since 2.1.0
 * @var array $component_data Data from component
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Start::main-footer -->
<footer class="footer mt-auto py-3 bg-white text-center">
	<div class="container">
		<span class="text-muted">
			<?php $this->load_template( 'parts/copyright.php' ); ?>
		</span>
	</div>
</footer>
<!-- End::main-footer -->

