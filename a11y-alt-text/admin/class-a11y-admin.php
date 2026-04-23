<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://a11y.so
 * @since      1.0.0
 *
 * @package    A11Y
 * @subpackage A11Y/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    A11Y
 * @subpackage A11Y/admin
 * @author     A11Y <info@a11y.so>
 */
class A11Y_Admin {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the global stylesheets for all pages.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'a11y-global', plugin_dir_url( __FILE__ ) . 'css/a11y-global.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'wp-i18n', 'jquery' ), $this->version, true );
    wp_localize_script( $this->plugin_name, 'wp_a11y', array(
      'ajax_url'                                => admin_url( 'admin-ajax.php' ),
      'security_insufficient_credits_notice'    => wp_create_nonce( 'a11y_insufficient_credits_notice' ),
      'security_single_generate'                => wp_create_nonce( 'a11y_single_generate' ),
      'security_edit_history'                   => wp_create_nonce( 'a11y_edit_history' ),
      'security_bulk_generate'                  => wp_create_nonce( 'a11y_bulk_generate' ),
      'security_enrich_post_content'            => wp_create_nonce( 'a11y_enrich_post_content' ),
      'security_enrich_post_content_transient'  => wp_create_nonce( 'a11y_enrich_post_content_transient' ),
      'security_update_toggle'                  => wp_create_nonce( 'a11y_update_toggle' ),
      'security_check_attachment_eligibility'   => wp_create_nonce( 'a11y_check_attachment_eligibility' ),
      'security_update_public_setting'          => wp_create_nonce( 'a11y_update_public_setting' ),
      'security_preview_csv'                    => wp_create_nonce( 'a11y_preview_csv' ),
      'security_url_generate'                   => wp_create_nonce( 'a11y_url_generate' ),
      'can_user_upload_files'                   => current_user_can( 'upload_files' ),
      'should_update_title'                     => A11Y_Utility::get_setting( 'a11y_update_title' ),
      'should_update_caption'                   => A11Y_Utility::get_setting( 'a11y_update_caption' ),
      'should_update_description'               => A11Y_Utility::get_setting( 'a11y_update_description' ),
      'icon_button_generate'                    => plugin_dir_url( A11Y_PLUGIN_FILE ) . 'admin/img/icon-button-generate.svg',
      'has_api_key'                             => A11Y_Utility::get_api_key() ? true : false,
      'settings_page_url'                       => admin_url( 'admin.php?page=a11y' ),
    ) );
    wp_set_script_translations( $this->plugin_name, 'a11y-alt-text' );

    // 블록 에디터(구텐베르크)에서만 로드
    if ( function_exists( 'get_current_screen' ) ) {
        wp_enqueue_script(
            'a11y-block-editor',
            plugin_dir_url( __FILE__ ) . 'js/block-editor.js',
            array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-element', 'wp-hooks', 'wp-compose', 'wp-plugins', 'wp-i18n' ),
            $this->version,
            true
        );
        wp_set_script_translations( 'a11y-block-editor', 'a11y-alt-text' );
    }
	}

  /**
   * Filters the array of row meta for each/specific plugin in the Plugins list table.
   * Appends additional links below each/specific plugin on the plugins page.
   *
   * @access  public
   * @param   array       $links_array            An array of the plugin's metadata
   * @param   string      $plugin_file_name       Path to the plugin file
   * @param   array       $plugin_data            An array of plugin data
   * @param   string      $status                 Status of the plugin
   * @return  array       $links_array
   */
  public function modify_plugin_row_meta( $links_array, $plugin_file_name, $plugin_data, $status ) {
    if ( strpos( $plugin_file_name, basename(A11Y_PLUGIN_FILE) ) ) {
      $links_array[] = '<a href="https://help.a11y.so/en" target="_blank" rel="noopener noreferrer">' .
       __('Documentation', 'a11y-alt-text') .
       '</a>';
    }

    return $links_array;
  }

  /**
   * Display a notice to the user after plugin activation.
   *
   * @access public
   * @since 1.7.2
   */
  public function display_setup_notice() {
    if ( ! get_transient( 'a11y_show_setup_notice' ) ) {
      return;
    }

    delete_transient( 'a11y_show_setup_notice' );

    echo '<div class="notice notice--a11y notice-info is-dismissible"><p>';

    printf(
      wp_kses(
        __( '[A11Y] Thanks for installing A11Y! To complete setup, please <a href="%s">go to the settings page.</a>', 'a11y-alt-text' ),
        array( 'a' => array( 'href' => array() ) )
      ),
      esc_url(admin_url( 'admin.php?page=a11y' ))
    );

    echo '</p></div>';
  }

  /**
   * 블록 에디터에서 a11y_description 메타를 읽고 쓸 수 있도록 REST API에 노출.
   *
   * @since 0.1.0
   */
  public function register_attachment_meta() {
      register_post_meta( 'attachment', 'a11y_description', array(
          'show_in_rest'  => true,
          'single'        => true,
          'type'          => 'string',
          'auth_callback' => function () {
              return current_user_can( 'upload_files' );
          },
          'sanitize_callback' => 'sanitize_textarea_field',
      ) );
  }
}
