<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://a11y.so
 * @since      1.0.0
 *
 * @package    A11Y.so
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'a11y_api_key' );
delete_option( 'a11y_error_logs' );
delete_option( 'a11y_public' );
delete_option( 'a11y_lang' );
delete_option( 'a11y_model_name' );
delete_option( 'a11y_enabled' );
delete_option( 'a11y_ecomm' );
delete_option( 'a11y_ecomm_title' );
delete_option( 'a11y_force_lang' );
delete_option( 'a11y_wpml_enabled_languages' );
delete_option( 'a11y_update_title' );
delete_option( 'a11y_update_caption' );
delete_option( 'a11y_update_description' );
delete_option( 'a11y_alt_prefix' );
delete_option( 'a11y_alt_suffix' );
delete_option( 'a11y_type_extensions' );
delete_option( 'a11y_gpt_prompt' );
delete_option( 'a11y_no_credit_warning' );
delete_option( 'a11y_timeout' );
delete_option( 'a11y_keywords_title' );
delete_option( 'a11y_bulk_refresh_overwrite' );
delete_option( 'a11y_bulk_refresh_external' );
delete_option( 'a11y_refresh_src_attr' );
delete_option( 'a11y_wp_generate_metadata' );
delete_option( 'a11y_skip_filenotfound' );
delete_option( 'a11y_woo_marketplace' );

// Database cleanup
global $wpdb;
$table_name = $wpdb->prefix . 'a11y_assets'; // Cannot use plugin constant here
$wpdb->query( "DROP TABLE IF EXISTS {$table_name};" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

delete_option( 'a11y_db_version' );
