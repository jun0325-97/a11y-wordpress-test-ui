<?php

/**
 * Network Settings page for the A11Y plugin
 *
 * @link       https://www.alttext.ai
 * @since      1.10.16
 *
 * @package    AltText_AI
 * @subpackage AltText_AI/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}
?>

<div class="wrap">
  <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
  
  <?php if ( isset( $_GET['updated'] ) && sanitize_text_field( wp_unslash( $_GET['updated'] ) ) === 'true' ) : ?>
    <div class="notice notice-success is-dismissible">
      <p><?php esc_html_e('Network settings saved successfully.', 'a11y-alt-text'); ?></p>
    </div>
  <?php endif; ?>
  
  <div class="a11y-network-settings-container">
    <form method="post" action="edit.php?action=a11y_update_network_settings">
      <?php wp_nonce_field('a11y_network_settings_nonce', 'a11y_network_settings_nonce'); ?>
      
      <div class="a11y-card mb-8">
        <div class="a11y-card-header">
          <h2 class="a11y-card-title"><?php esc_html_e('Network Settings', 'a11y-alt-text'); ?></h2>
          <p class="a11y-card-description"><?php esc_html_e('Configure network-wide settings for A11Y', 'a11y-alt-text'); ?></p>
        </div>
        
        <div class="a11y-card-body">
          <div class="mb-6">
            <h3 class="text-lg font-medium mb-2"><?php esc_html_e('API Key Management', 'a11y-alt-text'); ?></h3>
            
            <div class="mb-4 flex items-center relative gap-x-2">

                <input
                  id="a11y_network_api_key"
                  name="a11y_network_api_key"
                  type="checkbox"
                  value="yes"
                  class="w-4 h-4 !m-0 rounded border-gray-300 checked:bg-white text-primary-600 focus:ring-primary-600"
                  <?php checked('yes', get_site_option('a11y_network_api_key', 'no')); ?>
                >
                <label for="a11y_network_api_key" class="text-gray-600"><?php esc_html_e('Apply main site API key to all subsites', 'a11y-alt-text'); ?></label>

            </div>
            <div class="-mt-1 text-sm leading-6">
              <p class="text-xs text-gray-500 mt-1"><?php esc_html_e('When enabled, all subsites will use the API key from the main site.', 'a11y-alt-text'); ?></p>
            </div>
          </div>
          
          <div class="mb-6">
            <h3 class="text-lg font-medium mb-2"><?php esc_html_e('Settings Synchronization', 'a11y-alt-text'); ?></h3>
            
            <div class="mb-4 flex items-center relative gap-x-2">

                <input
                  id="a11y_network_all_settings"
                  name="a11y_network_all_settings"
                  type="checkbox"
                  value="yes"
                  class="w-4 h-4 !m-0 rounded border-gray-300 checked:bg-white text-primary-600 focus:ring-primary-600"
                  <?php checked('yes', get_site_option('a11y_network_all_settings', 'no')); ?>
                >
                <label for="a11y_network_all_settings" class="text-gray-600"><?php esc_html_e('Apply all settings from main site to all subsites', 'a11y-alt-text'); ?></label>

              </div>
              <div class="-mt-1 text-sm leading-6">
                <p class="text-xs text-gray-500 mt-1"><?php esc_html_e('When enabled, all plugin settings from the main site will be applied to all subsites. Settings on subsites will be disabled and they will use the main site settings.', 'a11y-alt-text'); ?></p>
              </div>
          </div>
          
          <div class="mb-6">
            <h3 class="text-lg font-medium mb-2"><?php esc_html_e('Credits Display', 'a11y-alt-text'); ?></h3>
            
            <div class="mb-4 flex items-center relative gap-x-2">
                <input
                  id="a11y_network_hide_credits"
                  name="a11y_network_hide_credits"
                  type="checkbox"
                  value="yes"
                  class="w-4 h-4 !m-0 rounded border-gray-300 checked:bg-white text-primary-600 focus:ring-primary-600"
                  <?php checked('yes', get_site_option('a11y_network_hide_credits', 'no')); ?>
                >
                <label for="a11y_network_hide_credits" class="text-gray-600"><?php esc_html_e('Hide credits display on subsites', 'a11y-alt-text'); ?></label>
              </div>
              <div class="-mt-1 text-sm leading-6">
                <p class="text-xs text-gray-500 mt-1"><?php esc_html_e('When enabled, the "You have X credits available out of Y" message will be hidden on all subsites.', 'a11y-alt-text'); ?></p>
              </div>
          </div>
        </div>
      </div>
      
      <div class="a11y-form-actions">
        <button type="submit" class="button button-primary"><?php esc_html_e('Save Network Settings', 'a11y-alt-text'); ?></button>
      </div>
    </form>
  </div>
</div>
