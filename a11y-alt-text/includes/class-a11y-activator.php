<?php
/**
 * Fired during plugin activation
 *
 * @link       https://alttext.ai
 * @since      1.0.0
 *
 * @package    A11Y
 * @subpackage A11Y/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    A11Y
 * @subpackage A11Y/includes
 * @author     A11Y <info@alttext.ai>
 */
class A11Y_Activator {
  /**
   * Runs when the plugin has been activated.
   *
   * @since 1.0.33
   * @access public
   */
  public static function activate() {

    // Create the database table
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-a11y-database.php';
    $database = new A11Y_Database();
    $database->check_database_schema();

    // Set the a11y_public option if not set already:
    if ( get_option( 'a11y_public' ) === false ) {
      update_option( 'a11y_public', A11Y_Utility::is_publicly_accessible() ? 'yes' : 'no' );
    }

    // Set a transient to trigger the setup instruction notice:
    set_transient( 'a11y_show_setup_notice', true, MINUTE_IN_SECONDS );
  }
}
