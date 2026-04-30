<?php
/**
 * Plugin Name: A11Y.so
 * Description: Use A11Y.so to automatically generate AI-powered image alt text that enhances web accessibility.
 * Version: 0.1.0
 * Author: A11Y.so
 * Author URI: https://a11y.so/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: a11y-alt-text
 * Domain Path: /languages
 * Requires PHP: 7.4
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'A11Y_VERSION', '0.1.0' );
define( 'A11Y_PLUGIN_FILE', __FILE__ );
define( 'A11Y_DB_ASSET_TABLE', 'a11y_assets' );
define( 'A11Y_HISTORY_ITEMS_PER_PAGE', 10 );
define( 'A11Y_HISTORY_PAGE_SELECTORS', 10 );

if ( ! defined( 'A11Y_CSV_LINE_LENGTH' ) ) {
    define( 'A11Y_CSV_LINE_LENGTH', 2048 );
}

function activate_a11y() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-a11y-activator.php';
    A11Y_Activator::activate();
}

function deactivate_a11y() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-a11y-deactivator.php';
    A11Y_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_a11y' );
register_deactivation_hook( __FILE__, 'deactivate_a11y' );

require plugin_dir_path( __FILE__ ) . 'includes/class-a11y.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-a11y-frontend.php';

function run_a11y() {
    $plugin = new A11Y();
    $plugin->run();
}

run_a11y();