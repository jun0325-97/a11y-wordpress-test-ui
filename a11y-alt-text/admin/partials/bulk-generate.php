<?php
/**
 * Bulk Generate page for the A11Y plugin.
 *
 * @link       https://a11y.so
 * @since      1.0.0
 *
 * @package    A11Y
 * @subpackage A11Y/admin/partials
 */
?>

<?php if ( ! defined( 'WPINC' ) ) die; ?>

<?php
  $account_check_failed  = ( $this->account === false );
  $account_error_is_auth = ( $account_check_failed && $this->account_error_type === 'auth' );
  $no_credits            = ( ! $account_check_failed && is_array( $this->account ) && empty( $this->account['available'] ) );
  $cannot_bulk_update    = ( $account_check_failed || $no_credits );
  $subscriptions_url     = esc_url( A11Y_Utility::get_credits_url() );
  $action                = sanitize_text_field( $_REQUEST['a11y_action'] ?? 'normal' );

  // 벌크 선택(미디어 라이브러리에서 복수 선택 후 일괄 처리) 모드 변수
  $batch_id        = sanitize_text_field( $_REQUEST['a11y_batch_id'] ?? null );
  $selected_images = ( $action === 'bulk-select-generate' ) ? get_transient( 'alttext_bulk_select_generate_' . $batch_id ) : null;

  if ( $action === 'bulk-select-generate' && $selected_images === false ) {
    $action = 'normal';
  }

  if ( $action === 'normal' ) {
    global $wpdb;
    $a11y_asset_table = $wpdb->prefix . A11Y_DB_ASSET_TABLE;
    $mode             = isset( $_GET['a11y_mode'] ) && $_GET['a11y_mode'] === 'all' ? 'all' : 'missing';
    $mode_url         = admin_url( sprintf( 'admin.php?%s', http_build_query( $_GET ) ) );
    $wc_products_url  = $wc_only_featured_url = $only_attached_url = $only_new_url = $mode_url;

    if ( $mode !== 'all' ) {
      $mode_url = add_query_arg( 'a11y_mode', 'all', $mode_url );
    } else {
      $mode_url = remove_query_arg( 'a11y_mode', $mode_url );
    }

    $only_attached = isset( $_GET['a11y_attached'] ) && $_GET['a11y_attached'] === '1' ? '1' : '0';
    $only_attached_url = $only_attached !== '1'
      ? add_query_arg( 'a11y_attached', '1', $only_attached_url )
      : remove_query_arg( 'a11y_attached', $only_attached_url );

    $only_new = isset( $_GET['a11y_only_new'] ) && $_GET['a11y_only_new'] === '1' ? '1' : '0';
    $only_new_url = $only_new !== '1'
      ? add_query_arg( 'a11y_only_new', '1', $only_new_url )
      : remove_query_arg( 'a11y_only_new', $only_new_url );

    $wc_products = isset( $_GET['a11y_wc_products'] ) && $_GET['a11y_wc_products'] === '1' ? '1' : '0';
    $wc_products_url = $wc_products !== '1'
      ? add_query_arg( 'a11y_wc_products', '1', $wc_products_url )
      : remove_query_arg( array( 'a11y_wc_products', 'a11y_wc_only_featured' ), $wc_products_url );

    $wc_only_featured = isset( $_GET['a11y_wc_only_featured'] ) && $_GET['a11y_wc_only_featured'] === '1' ? '1' : '0';
    $wc_only_featured_url = $wc_only_featured !== '1'
      ? add_query_arg( array( 'a11y_wc_products' => 1, 'a11y_wc_only_featured' => 1 ), $wc_only_featured_url )
      : remove_query_arg( 'a11y_wc_only_featured', $wc_only_featured_url );

    // 전체 이미지 수 쿼리
    $all_images_query = <<<SQL
SELECT COUNT(*) as total_images
FROM {$wpdb->posts} p
WHERE (p.post_mime_type LIKE %s)
  AND p.post_type = %s
  AND p.post_status = %s
SQL;
    if ( $only_attached === '1' ) $all_images_query .= " AND (p.post_parent > 0)";
    if ( $only_new === '1' )      $all_images_query .= " AND (NOT EXISTS(SELECT 1 FROM {$a11y_asset_table} WHERE wp_post_id = p.ID))"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    $like_image   = $wpdb->esc_like( 'image/' ) . '%';
    $prepare_args = array( $like_image, 'attachment', 'inherit' );

    if ( $wc_products === '1' ) {
      $all_images_query .= " AND (EXISTS(SELECT 1 FROM {$wpdb->posts} p2 WHERE p2.ID = p.post_parent and p2.post_type = %s))";
      $prepare_args[] = 'product';
    }
    if ( $wc_only_featured === '1' ) {
      $all_images_query .= " AND (EXISTS(SELECT 1 FROM {$wpdb->postmeta} pm2 WHERE pm2.post_id = p.post_parent and pm2.meta_key = %s and CAST(pm2.meta_value as UNSIGNED) = p.ID))";
      $prepare_args[] = '_thumbnail_id';
    }

    $excluded_post_types = A11Y_Utility::get_setting( 'a11y_excluded_post_types' );
    if ( ! empty( $excluded_post_types ) ) {
      $post_types              = array_map( 'trim', explode( ',', $excluded_post_types ) );
      $post_types_placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
      $all_images_query       .= " AND (p.post_parent = 0 OR NOT EXISTS(SELECT 1 FROM {$wpdb->posts} p3 WHERE p3.ID = p.post_parent AND p3.post_type IN ($post_types_placeholders)))";
      $prepare_args            = array_merge( $prepare_args, $post_types );
    }

    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    $all_images_count = $images_count = (int) $wpdb->get_results( $wpdb->prepare( $all_images_query, $prepare_args ) )[0]->total_images;
    $images_missing_alt_text_count = 0;

    // alt 없는 이미지 수 쿼리
    $images_without_alt_text_sql = <<<SQL
SELECT COUNT(DISTINCT p.ID) as total_images
FROM {$wpdb->posts} p
  LEFT JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key = %s)
WHERE (p.post_mime_type LIKE %s)
  AND p.post_type = %s
  AND p.post_status = %s
  AND (pm.post_id IS NULL OR TRIM(COALESCE(pm.meta_value, '')) = '')
SQL;
    if ( $only_attached === '1' ) $images_without_alt_text_sql .= " AND (p.post_parent > 0)";
    if ( $only_new === '1' )      $images_without_alt_text_sql .= " AND (NOT EXISTS(SELECT 1 FROM {$a11y_asset_table} WHERE wp_post_id = p.ID))"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    $alt_prepare_args = array( '_wp_attachment_image_alt', $like_image, 'attachment', 'inherit' );

    if ( $wc_products === '1' ) {
      $images_without_alt_text_sql .= " AND (EXISTS(SELECT 1 FROM {$wpdb->posts} p2 WHERE p2.ID = p.post_parent and p2.post_type = %s))";
      $alt_prepare_args[] = 'product';
    }
    if ( $wc_only_featured === '1' ) {
      $images_without_alt_text_sql .= " AND (EXISTS(SELECT 1 FROM {$wpdb->postmeta} pm2 WHERE pm2.post_id = p.post_parent and pm2.meta_key = %s and CAST(pm2.meta_value as UNSIGNED) = p.ID))";
      $alt_prepare_args[] = '_thumbnail_id';
    }
    if ( ! empty( $excluded_post_types ) ) {
      $post_types              = array_map( 'trim', explode( ',', $excluded_post_types ) );
      $post_types_placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
      $images_without_alt_text_sql .= " AND (p.post_parent = 0 OR NOT EXISTS(SELECT 1 FROM {$wpdb->posts} p3 WHERE p3.ID = p.post_parent AND p3.post_type IN ($post_types_placeholders)))";
      $alt_prepare_args = array_merge( $alt_prepare_args, $post_types );
    }

    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    $images_missing_alt_text_count = (int) $wpdb->get_results( $wpdb->prepare( $images_without_alt_text_sql, $alt_prepare_args ) )[0]->total_images;

    if ( $mode === 'missing' ) $images_count = $images_missing_alt_text_count;

  } elseif ( $action === 'bulk-select-generate' ) {
    $current_selected_images = get_transient( 'alttext_bulk_select_generate_' . $batch_id );
    $images_count     = ( is_array( $current_selected_images ) && ! empty( $current_selected_images ) )
                        ? count( $current_selected_images )
                        : count( $selected_images );
    $all_images_count = count( $selected_images );
  }
?>

<div class="wrap a11y-wrap">

  <!-- 목업 모드 배너 -->
  <div class="a11y-mock-banner" role="status">
    <span class="a11y-mock-badge">Mock Mode</span>
    <p>The A11Y API is not yet connected. Bulk generation will return dummy data for testing.</p>
  </div>

  <!-- 페이지 헤더 -->
  <h1><?php esc_html_e( 'Bulk Generate Alt Text', 'a11y-alt-text' ); ?></h1>
  <p><?php esc_html_e( 'Automatically generate alt text for multiple images at once. Improve accessibility across your entire media library.', 'a11y-alt-text' ); ?></p>

  <!-- 통계 카드 그리드 -->
  <div class="a11y-stats-grid">
    <?php if ( $action === 'bulk-select-generate' ) : ?>
      <div class="a11y-stat-card">
        <p class="a11y-stat-label"><?php esc_html_e( 'Selected Images', 'a11y-alt-text' ); ?></p>
        <p class="a11y-stat-sub is-muted">Ready</p>
        <p class="a11y-stat-value"><?php echo esc_html( number_format( $all_images_count ) ); ?></p>
      </div>
    <?php else : ?>
      <div class="a11y-stat-card">
        <p class="a11y-stat-label"><?php esc_html_e( 'Total Images', 'a11y-alt-text' ); ?></p>
        <p class="a11y-stat-sub is-muted">Library</p>
        <p class="a11y-stat-value"><?php echo esc_html( number_format( $all_images_count ) ); ?></p>
      </div>
      <div class="a11y-stat-card">
        <p class="a11y-stat-label"><?php esc_html_e( 'Missing Alt Text', 'a11y-alt-text' ); ?></p>
        <p class="a11y-stat-sub is-warn">
          <?php echo esc_html( number_format( ( $images_missing_alt_text_count / max( $all_images_count, 1 ) ) * 100, 1 ) ); ?>%
        </p>
        <p class="a11y-stat-value"><?php echo esc_html( number_format( $images_missing_alt_text_count ) ); ?></p>
      </div>
      <div class="a11y-stat-card">
        <p class="a11y-stat-label"><?php esc_html_e( 'With Alt Text', 'a11y-alt-text' ); ?></p>
        <p class="a11y-stat-sub is-ok">
          <?php echo esc_html( number_format( ( ( $all_images_count - $images_missing_alt_text_count ) / max( $all_images_count, 1 ) ) * 100, 1 ) ); ?>%
        </p>
        <p class="a11y-stat-value"><?php echo esc_html( number_format( $all_images_count - $images_missing_alt_text_count ) ); ?></p>
      </div>
      <div class="a11y-stat-card">
        <p class="a11y-stat-label"><?php esc_html_e( 'Available Credits', 'a11y-alt-text' ); ?></p>
        <p class="a11y-stat-sub <?php echo $account_check_failed ? 'is-muted' : ( ( $this->account && $this->account['available'] > 100 ) ? 'is-ok' : 'is-warn' ); ?>">
          <?php if ( $account_check_failed ) {
            esc_html_e( 'Unavailable', 'a11y-alt-text' );
          } elseif ( $this->account && $this->account['available'] <= 100 ) {
            echo 'Low';
          } ?>
        </p>
        <p class="a11y-stat-value">
          <?php echo $account_check_failed
            ? esc_html__( 'N/A', 'a11y-alt-text' )
            : esc_html( number_format( (int) $this->account['available'] ) ); ?>
        </p>
      </div>
    <?php endif; ?>
  </div>

  <!-- 계정 오류 / 크레딧 부족 경고 -->
  <?php if ( $cannot_bulk_update ) : ?>
    <div class="a11y-alert is-warning">
      <div class="a11y-alert-icon" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
        </svg>
      </div>
      <div class="a11y-alert-msg">
        <?php if ( $account_check_failed ) :
          if ( $account_error_is_auth ) :
            esc_html_e( 'Your API key appears to be invalid. Please update it in Settings before running Bulk Generate.', 'a11y-alt-text' );
          else :
            esc_html_e( 'Unable to verify your account. Please check your API key in Settings, or try reloading this page.', 'a11y-alt-text' );
          endif;
        else :
          esc_html_e( 'You have no more credits left. To bulk update your library, you need to purchase more credits.', 'a11y-alt-text' );
        endif; ?>
        <?php if ( $no_credits && $this->account && ! $this->account['whitelabel'] ) : ?>
          &nbsp;<a href="<?php echo esc_url( $subscriptions_url ); ?>" target="_blank"
                   class="button button-primary" style="vertical-align:middle;">
            <?php esc_html_e( 'Purchase Credits', 'a11y-alt-text' ); ?>
          </a>
        <?php endif; ?>
      </div>
    </div>
    <?php return; ?>
  <?php endif; ?>

  <div id="bulk-generate-form">

    <!-- 키워드 입력 박스 -->
    <div class="a11y-option-box">
      <div class="a11y-option-box-header">
        <h3><?php esc_html_e( 'Keywords', 'a11y-alt-text' ); ?></h3>
      </div>
      <div class="a11y-option-box-body">
        <div class="a11y-option-grid">
          <div>
            <label for="bulk-generate-keywords" class="a11y-input-label">
              <?php esc_html_e( 'Keywords', 'a11y-alt-text' ); ?>
              <span class="a11y-input-sub">(optional)</span>
            </label>
            <input
              data-bulk-generate-keywords
              type="text" size="60" maxlength="512"
              name="keywords" id="bulk-generate-keywords"
              class="large-text"
              placeholder="<?php esc_attr_e( 'Enter keywords separated by commas', 'a11y-alt-text' ); ?>"
              aria-describedby="keywords-description"
            >
            <p id="keywords-description" class="a11y-input-desc">
              <?php esc_html_e( 'Try to include these in the generated alt text. Maximum of 6 keywords or phrases.', 'a11y-alt-text' ); ?>
            </p>
          </div>
          <div>
            <label for="bulk-generate-negative-keywords" class="a11y-input-label">
              <?php esc_html_e( 'Negative Keywords', 'a11y-alt-text' ); ?>
              <span class="a11y-input-sub">(optional)</span>
            </label>
            <input
              data-bulk-generate-negative-keywords
              type="text" size="60" maxlength="512"
              name="negative-keywords" id="bulk-generate-negative-keywords"
              class="large-text"
              placeholder="<?php esc_attr_e( 'Enter negative keywords separated by commas', 'a11y-alt-text' ); ?>"
              aria-describedby="negative-keywords-description"
            >
            <p id="negative-keywords-description" class="a11y-input-desc">
              <?php esc_html_e( 'Do not include these in the generated alt text. Maximum of 6 keywords or phrases.', 'a11y-alt-text' ); ?>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- 처리 옵션 박스 (normal 모드일 때만 표시) -->
    <?php if ( $action === 'normal' ) : ?>
    <div class="a11y-option-box">
      <div class="a11y-option-box-header">
        <h3><?php esc_html_e( 'Processing Options', 'a11y-alt-text' ); ?></h3>
      </div>
      <div class="a11y-option-box-body">

        <div class="a11y-checkbox-row">
          <input type="checkbox" id="a11y_bulk_generate_all"
                 data-bulk-generate-mode-all
                 data-url="<?php echo esc_url( $mode_url ); ?>"
                 <?php if ( isset( $_GET['a11y_mode'] ) && $_GET['a11y_mode'] === 'all' ) echo 'checked'; ?>>
          <div>
            <label for="a11y_bulk_generate_all" class="a11y-checkbox-label">
              <?php esc_html_e( 'Overwrite existing alt text', 'a11y-alt-text' ); ?>
            </label>
            <p class="a11y-checkbox-desc">
              <?php esc_html_e( 'Include images that already have alt text and replace their existing alt text.', 'a11y-alt-text' ); ?>
            </p>
          </div>
        </div>

        <div class="a11y-checkbox-row">
          <input type="checkbox" id="a11y_bulk_generate_only_attached"
                 data-bulk-generate-only-attached
                 data-url="<?php echo esc_url( $only_attached_url ); ?>"
                 <?php if ( $only_attached === '1' ) echo 'checked'; ?>>
          <div>
            <label for="a11y_bulk_generate_only_attached" class="a11y-checkbox-label">
              <?php esc_html_e( 'Only attached images', 'a11y-alt-text' ); ?>
            </label>
            <p class="a11y-checkbox-desc">
              <?php esc_html_e( 'Only process images that are attached to posts or pages.', 'a11y-alt-text' ); ?>
            </p>
          </div>
        </div>

        <div class="a11y-checkbox-row">
          <input type="checkbox" id="a11y_bulk_generate_only_new"
                 data-bulk-generate-only-new
                 data-url="<?php echo esc_url( $only_new_url ); ?>"
                 <?php if ( $only_new === '1' ) echo 'checked'; ?>>
          <div>
            <label for="a11y_bulk_generate_only_new" class="a11y-checkbox-label">
              <?php esc_html_e( 'Skip previously processed', 'a11y-alt-text' ); ?>
            </label>
            <p class="a11y-checkbox-desc">
              <?php esc_html_e( 'Skip images that have already been processed by A11Y.', 'a11y-alt-text' ); ?>
            </p>
          </div>
        </div>

        <!-- WooCommerce 옵션 (WC 활성 시에만 표시) -->
        <?php if ( A11Y_Utility::has_woocommerce() ) : ?>
        <hr style="margin:14px 0; border:none; border-top:1px solid #e6e7e8;">
        <p style="margin:0 0 10px; font-size:13px; font-weight:600; color:#1d2327;">
          <?php esc_html_e( 'WooCommerce Options', 'a11y-alt-text' ); ?>
        </p>
        <div class="a11y-checkbox-row">
          <input type="checkbox" id="a11y_bulk_generate_wc_products"
                 data-bulk-generate-wc-products
                 data-url="<?php echo esc_url( $wc_products_url ); ?>"
                 <?php if ( $wc_products === '1' ) echo 'checked'; ?>>
          <label for="a11y_bulk_generate_wc_products" class="a11y-checkbox-label">
            <?php esc_html_e( 'Only process WooCommerce product images.', 'a11y-alt-text' ); ?>
          </label>
        </div>
        <div class="a11y-checkbox-row">
          <input type="checkbox" id="a11y_bulk_generate_wc_only_featured"
                 data-bulk-generate-wc-only-featured
                 data-url="<?php echo esc_url( $wc_only_featured_url ); ?>"
                 <?php if ( $wc_only_featured === '1' ) echo 'checked'; ?>>
          <label for="a11y_bulk_generate_wc_only_featured" class="a11y-checkbox-label">
            <?php esc_html_e( 'For each product, only process the main image and skip gallery images.', 'a11y-alt-text' ); ?>
          </label>
        </div>
        <?php endif; ?>

      </div>
    </div>
    <?php endif; ?>

    <!-- 생성 시작 / 처음부터 버튼 -->
    <div class="a11y-btn-row">
      <button
        data-bulk-generate-start
        type="button"
        class="button <?php echo $images_count > 0 ? 'button-primary' : 'button-secondary'; ?>"
        <?php echo $images_count === 0 ? 'disabled' : ''; ?>
      >
        <?php echo $images_count === 0
          ? esc_html__( 'Generate Alt Text', 'a11y-alt-text' )
          : esc_html( sprintf(
              _n( 'Generate Alt Text for %d Image', 'Generate Alt Text for %d Images', $images_count, 'a11y-alt-text' ),
              $images_count
            ) ); ?>
      </button>
      <button id="a11y-static-start-over-button" type="button"
              class="button button-secondary" style="display:none;">
        <?php esc_html_e( 'Start Over', 'a11y-alt-text' ); ?>
      </button>
    </div>

  </div><!-- /bulk-generate-form -->

  <!-- 진행 표시 영역 (JS로 show/hide) -->
  <div data-bulk-generate-progress-wrapper class="a11y-progress-box">
    <div class="a11y-progress-header">
      <h3 data-bulk-generate-progress-heading aria-live="polite" role="status">
        <?php esc_html_e( 'Processing Images', 'a11y-alt-text' ); ?>
      </h3>
      <p data-bulk-generate-progress-subtitle style="margin:4px 0 0; font-size:12px; color:#646970;">
        <?php esc_html_e( 'Please keep this page open until the update completes.', 'a11y-alt-text' ); ?>
      </p>
    </div>
    <div class="a11y-progress-body">

      <div class="a11y-progress-label">
        <span>Progress</span>
        <span data-bulk-generate-progress-percent>0%</span>
      </div>
      <div class="a11y-progress-track">
        <div
          data-bulk-generate-progress-bar
          data-max="<?php echo esc_attr( $images_count ); ?>"
          data-current="0"
          data-successful="0"
          class="a11y-progress-bar"
          style="width:1%;"
        ></div>
      </div>

      <div class="a11y-progress-stats">
        <p>
          <span data-bulk-generate-progress-current>0</span> /
          <span data-bulk-generate-progress-max><?php echo esc_html( $images_count ); ?></span>
          images processed &nbsp;—&nbsp;
          <span data-bulk-generate-progress-successful class="a11y-count-success">0</span> successful,
          <span data-bulk-generate-progress-skipped class="a11y-count-skip">0</span> skipped
        </p>
        <p style="margin:4px 0 0; font-size:12px; color:#646970;">
          Last image ID:
          <span data-bulk-generate-last-post-id class="a11y-last-id"></span>
        </p>
      </div>

      <div class="a11y-btn-row">
        <button
          data-bulk-generate-cancel
          class="button button-secondary"
          onclick="window.location = '<?php echo esc_url( admin_url( 'admin.php?page=a11y-bulk-generate' ) ); ?>';"
        ><?php esc_html_e( 'Cancel', 'a11y-alt-text' ); ?></button>
        <button
          data-bulk-generate-finished
          style="display:none;"
          class="button button-primary"
          onclick="window.location = '<?php echo esc_url( admin_url( 'admin.php?page=a11y-bulk-generate' ) ); ?>';"
        ><?php esc_html_e( 'View Summary', 'a11y-alt-text' ); ?></button>
      </div>

    </div>
  </div>

  <div class="clear"></div>
</div>