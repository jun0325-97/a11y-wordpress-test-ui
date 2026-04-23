<?php

if ( ! function_exists( 'is_plugin_active' ) ) {
  require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://a11y.so
 * @since      1.0.0
 *
 * @package    A11Y
 * @subpackage A11Y/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    A11Y
 * @subpackage A11Y/includes
 * @author     A11Y <info@a11y.so>
 */
class A11Y {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      A11Y_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'A11Y_VERSION' ) ) {
			$this->version = A11Y_VERSION;
		} else {
			$this->version = '1.0.1';
		}
		$this->plugin_name = 'a11y';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - A11Y_Loader. Orchestrates the hooks of the plugin.
	 * - A11Y_i18n. Defines internationalization functionality.
	 * - A11Y_Admin. Defines all hooks for the admin area.
	 * - A11Y_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
    /**
		 * Database creation and migration methods.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-a11y-database.php';

    /**
		 * The class housing utility methods used across the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-a11y-utility.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-a11y-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-a11y-i18n.php';

    /**
		 * The class responsible for the API connection.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-a11y-api.php';

    /**
		 * The class responsible for attachment handling.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-a11y-attachment.php';

    /**
		 * The class responsible for post handling.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-a11y-post.php';

    /**
		 * The class responsible for syncing alt text into Elementor's cached data.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-a11y-elementor-sync.php';

		// Page builder handlers
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/builders/class-a11y-builder-yootheme.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-a11y-admin.php';

    /**
		 * The class responsible for managing the settings page.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-a11y-settings.php';

		/**
		 * Load WP-CLI commands if WP-CLI is available.
		 */
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-a11y-cli.php';
		}

		$this->loader = new A11Y_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the A11Y_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new A11Y_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$database        = new A11Y_Database();
		$admin           = new A11Y_Admin( $this->get_plugin_name(), $this->get_version() );
		$settings        = new A11Y_Settings( $this->get_version() );
		$attachment      = new A11Y_Attachment();
		$post            = new A11Y_Post();
		$elementor_sync  = new A11Y_Elementor_Sync();

    // Database
    $this->loader->add_action( 'plugins_loaded', $database, 'check_database_schema' );

    // Admin
	$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
	$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
	$this->loader->add_filter( 'plugin_row_meta', $admin, 'modify_plugin_row_meta', 10, 4 );
    $this->loader->add_action( 'admin_notices', $admin, 'display_setup_notice' );
	$this->loader->add_action( 'init', $admin, 'register_attachment_meta' );

    // Settings
    $this->loader->add_action( 'admin_menu', $settings, 'register_settings_pages' );
    $this->loader->add_action( 'network_admin_menu', $settings, 'register_network_settings_page' );
    $this->loader->add_action( 'admin_init', $settings, 'register_settings' );
    $this->loader->add_action( 'admin_init', $settings, 'clear_error_logs' );
    $this->loader->add_action( 'admin_init', $settings, 'remove_api_key_missing_param' );
    $this->loader->add_action( 'network_admin_edit_a11y_update_network_settings', $settings, 'handle_network_settings_update' );
    $this->loader->add_action( 'admin_notices', $settings, 'display_insufficient_credits_notice' );
    $this->loader->add_action( 'admin_notices', $settings, 'display_api_key_missing_notice' );
    $this->loader->add_action( 'wp_ajax_a11y_expire_insufficient_credits_notice', $settings, 'expire_insufficient_credits_notice' );
    $this->loader->add_action( 'wp_ajax_a11y_update_public_setting', $settings, 'ajax_update_public_setting' );

    $this->loader->add_filter( 'pre_update_option_a11y_api_key', $settings, 'save_api_key', 10, 2 );
    $this->loader->add_filter( 'pre_update_option_a11y_wpml_enabled_languages', $settings, 'preserve_wpml_enabled_languages', 10, 3 );
    $this->loader->add_filter( 'pre_update_option', $settings, 'preserve_network_controlled_setting', 10, 3 );
    $this->loader->add_filter( 'option_page_capability_a11y-settings', $settings, 'filter_settings_capability' );

    // Network Bulk Generate
    $this->loader->add_action( 'network_admin_menu', $settings, 'register_network_bulk_generate_page' );
    if ( is_multisite() ) {
      $this->loader->add_action( 'wp_ajax_a11y_network_get_stats', $attachment, 'ajax_network_get_stats' );
      $this->loader->add_action( 'wp_ajax_a11y_network_bulk_generate', $attachment, 'ajax_network_bulk_generate' );
    }

    // Refresh network settings cache when any setting is updated (multisite only)
    if ( is_multisite() ) {
      $this->loader->add_action( 'update_option', $settings, 'maybe_refresh_network_settings', 10, 1 );
    }

    // Attachment
    $this->loader->add_action( 'admin_init', $attachment, 'action_single_generate', 99 );
    $this->loader->add_action( 'add_attachment', $attachment, 'add_attachment', 10, 1 );
    $this->loader->add_action( 'wp_ajax_a11y_single_generate', $attachment, 'ajax_single_generate' );
    $this->loader->add_action( 'wp_ajax_a11y_bulk_generate', $attachment, 'ajax_bulk_generate' );
    $this->loader->add_action( 'wp_ajax_a11y_edit_history', $attachment, 'ajax_edit_history' );
    $this->loader->add_action( 'wp_ajax_a11y_check_image_eligibility', $attachment, 'ajax_check_attachment_eligibility' );
    $this->loader->add_action( 'wp_ajax_a11y_preview_csv', $attachment, 'ajax_preview_csv' );
    $this->loader->add_action( 'admin_notices', $attachment, 'render_bulk_select_notice' );
    $this->loader->add_action( 'restrict_manage_posts', $attachment, 'add_media_alt_filter', 1 );
    $this->loader->add_action( 'pre_get_posts', $attachment, 'media_alt_filter_handler' );

    $this->loader->add_filter( 'bulk_actions-upload', $attachment, 'add_bulk_select_action', 10, 1 );
    $this->loader->add_filter( 'handle_bulk_actions-upload', $attachment, 'bulk_select_action_handler', 10, 3 );

    // Post
    $this->loader->add_action( 'deleted_post', $post, 'on_post_deleted' );
    $this->loader->add_action( 'add_meta_boxes', $post, 'add_bulk_generate_meta_box' );
    $this->loader->add_action( 'wp_ajax_a11y_enrich_post_content', $post, 'enrich_post_content' );
    $this->loader->add_action( 'wp_ajax_a11y_check_enrich_post_content_transient', $post, 'display_enrich_post_content_success_notice' );
    $this->loader->add_action( 'admin_notices', $post, 'display_enrich_post_content_success_notice' );
    $this->loader->add_action( 'admin_init', $post, 'register_bulk_action' );

    // Sync media library alt text into page builder content on the frontend
    $this->loader->add_filter( 'the_content', $post, 'sync_alt_text_to_content', 999 );

    // Sync media library alt text into Elementor's cached image data.
    // Hook both added_post_meta (first-time alt text) and updated_post_meta (subsequent edits).
    $this->loader->add_action( 'added_post_meta', $elementor_sync, 'sync_alt_to_elementor', 10, 4 );
    $this->loader->add_action( 'updated_post_meta', $elementor_sync, 'sync_alt_to_elementor', 10, 4 );

    // After bulk post enrichment, do one comprehensive Elementor sync for the page
    // (avoids per-image read-modify-write races during the bulk loop).
    $this->loader->add_action( 'a11y_post_enrichment_complete', $elementor_sync, 'sync_post' );

    // Other plugin integrations
    $this->loader->add_action( 'pll_translate_media', $attachment, 'on_translation_created', 99, 3 );
  }

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    A11Y_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
