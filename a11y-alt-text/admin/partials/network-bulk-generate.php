<?php
/**
 * Network Bulk Generate page for the A11Y plugin.
 *
 * Allows super admins to view missing alt text across all
 * network sites and process them from one central location.
 *
 * @link       https://www.a11y.so
 * @since      1.10.20
 *
 * @package    AltText_AI
 * @subpackage AltText_AI/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}
?>

<div class="wrap">
  <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

  <div class="a11y-network-settings-container">
    <div class="a11y-card mb-8">
      <div class="a11y-card-header">
        <h2 class="a11y-card-title"><?php esc_html_e( 'Network Image Statistics', 'a11y-alt-text' ); ?></h2>
        <p class="a11y-card-description"><?php esc_html_e( 'Alt text status across all sites in your network.', 'a11y-alt-text' ); ?></p>
      </div>

      <div class="a11y-card-body">
        <div id="a11y-network-stats-loading">
          <p><?php esc_html_e( 'Loading site statistics...', 'a11y-alt-text' ); ?></p>
        </div>

        <table id="a11y-network-stats-table" class="wp-list-table widefat fixed striped" style="display: none;">
          <caption class="sr-only"><?php esc_html_e( 'Network site image statistics', 'a11y-alt-text' ); ?></caption>
          <thead>
            <tr>
              <th class="check-column"><input type="checkbox" id="a11y-select-all" aria-label="<?php esc_attr_e( 'Select all sites', 'a11y-alt-text' ); ?>" /></th>
              <th><?php esc_html_e( 'Site', 'a11y-alt-text' ); ?></th>
              <th><?php esc_html_e( 'Total Images', 'a11y-alt-text' ); ?></th>
              <th><?php esc_html_e( 'Missing Alt Text', 'a11y-alt-text' ); ?></th>
              <th><?php esc_html_e( 'Status', 'a11y-alt-text' ); ?></th>
            </tr>
          </thead>
          <tbody id="a11y-network-stats-body">
          </tbody>
          <tfoot>
            <tr>
              <td></td>
              <td><strong><?php esc_html_e( 'Total', 'a11y-alt-text' ); ?></strong></td>
              <td><strong id="a11y-total-images">0</strong></td>
              <td><strong id="a11y-total-missing">0</strong></td>
              <td></td>
            </tr>
          </tfoot>
        </table>

        <div id="a11y-network-stats-error" role="alert" style="display: none;">
          <p class="notice notice-error"><?php esc_html_e( 'Failed to load site statistics.', 'a11y-alt-text' ); ?></p>
        </div>
      </div>
    </div>

    <div class="a11y-card mb-8" id="a11y-network-progress-card" style="display: none;">
      <div class="a11y-card-header">
        <h2 class="a11y-card-title"><?php esc_html_e( 'Processing Progress', 'a11y-alt-text' ); ?></h2>
      </div>
      <div class="a11y-card-body">
        <p id="a11y-network-current-site" aria-live="polite"></p>
        <div style="background: #e0e0e0; border-radius: 4px; height: 24px; margin: 10px 0; overflow: hidden;">
          <div id="a11y-network-progress-bar"
               role="progressbar"
               aria-valuemin="0"
               aria-valuemax="100"
               aria-valuenow="0"
               aria-label="<?php esc_attr_e( 'Alt text generation progress', 'a11y-alt-text' ); ?>"
               style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s; border-radius: 4px;"></div>
        </div>
        <p id="a11y-network-progress-text" aria-live="polite"><?php esc_html_e( 'Waiting to start...', 'a11y-alt-text' ); ?></p>
      </div>
    </div>

    <div class="a11y-form-actions">
      <button type="button" id="a11y-network-generate-btn" class="a11y-button blue" disabled>
        <?php esc_html_e( 'Generate Alt Text for Selected Sites', 'a11y-alt-text' ); ?>
      </button>
      <button type="button" id="a11y-network-cancel-btn" class="a11y-button white" style="display: none;">
        <?php esc_html_e( 'Cancel', 'a11y-alt-text' ); ?>
      </button>
      <button type="button" id="a11y-network-refresh-btn" class="a11y-button white">
        <?php esc_html_e( 'Refresh Stats', 'a11y-alt-text' ); ?>
      </button>
    </div>
  </div>
</div>
