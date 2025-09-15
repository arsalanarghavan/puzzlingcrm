<?php
/**
 * Forms Settings Page Template for System Manager
 * @package PuzzlingCRM
 */

if (!defined('ABSPATH')) exit;
if ( ! current_user_can('manage_options') ) return;

$forms = get_posts(['post_type' => 'pzl_form', 'posts_per_page' => -1]);
?>
<div class="pzl-form-container">
    <h4><i class="fas fa-clipboard-list"></i> <?php _e( 'مدیریت فرم‌ها', 'puzzlingcrm' ); ?></h4>
    <p class="description"><?php _e( 'فرم‌هایی ایجاد کنید تا پس از خرید محصول به مشتری نمایش داده شوند.', 'puzzlingcrm' ); ?></p>
    
    <div style="margin-top: 20px;">
        <table class="pzl-table">
            <thead>
                <tr>
                    <th><?php _e( 'عنوان فرم', 'puzzlingcrm' ); ?></th>
                    <th><?php _e( 'Shortcode', 'puzzlingcrm' ); ?></th>
                    <th><?php _e( 'عملیات', 'puzzlingcrm' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $forms ) ): ?>
                    <?php foreach ( $forms as $form ): ?>
                        <tr>
                            <td><?php echo esc_html( $form->post_title ); ?></td>
                            <td><code>[puzzling_form id="<?php echo esc_attr( $form->ID ); ?>"]</code></td>
                            <td>
                                <a href="<?php echo get_edit_post_link( $form->ID ); ?>" class="pzl-button pzl-button-sm"><?php _e( 'ویرایش', 'puzzlingcrm' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3"><?php _e( 'هیچ فرمی یافت نشد.', 'puzzlingcrm' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="<?php echo admin_url('post-new.php?post_type=pzl_form'); ?>" class="pzl-button" style="margin-top: 20px;"><?php _e( 'ایجاد فرم جدید', 'puzzlingcrm' ); ?></a>
    </div>
</div>