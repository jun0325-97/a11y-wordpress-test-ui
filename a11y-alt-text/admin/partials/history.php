<?php
/**
 * History page for the A11Y plugin.
 *
 * @link       https://a11y.so
 * @since      1.4.1
 *
 * @package    A11Y
 * @subpackage A11Y/admin/partials
 */
?>

<?php if ( ! defined( 'WPINC' ) ) die; ?>

<?php
  // 이력 데이터 조회 — 페이지네이션 포함
  global $wpdb;
  $a11y_asset_table = $wpdb->prefix . A11Y_DB_ASSET_TABLE;
  $paged            = max( 1, intval( $_GET['paged'] ?? 1 ) );
  $offset           = ( $paged - 1 ) * A11Y_HISTORY_ITEMS_PER_PAGE;

  $pagination_start = floor( ( $paged - 1 ) / A11Y_HISTORY_PAGE_SELECTORS ) * A11Y_HISTORY_PAGE_SELECTORS + 1;
  $pagination_end   = $pagination_start + A11Y_HISTORY_PAGE_SELECTORS - 1;

  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
  $total_assets = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT wp_post_id) FROM {$a11y_asset_table}" );
  $total_pages  = (int) ceil( $total_assets / A11Y_HISTORY_ITEMS_PER_PAGE );
  $pagination_end = min( $pagination_end, $total_pages );

  // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
  $a11y_assets = $wpdb->get_results( $wpdb->prepare( <<<SQL
    SELECT wp_post_id, MAX(updated_at) as updated_at
    FROM {$a11y_asset_table}
    GROUP BY 1
    ORDER BY updated_at DESC
    LIMIT %d OFFSET %d
SQL
    , A11Y_HISTORY_ITEMS_PER_PAGE, $offset ) );
  // phpcs:enable
?>

<div class="wrap a11y-wrap">

  <!-- 목업 모드 배너 -->
  <div class="a11y-mock-banner" role="status">
    <span class="a11y-mock-badge">Mock Mode</span>
    <p>The A11Y.so API is not yet connected. History below reflects mock-generated data.</p>
  </div>

  <h1><?php esc_html_e( 'Alt Text Processing History', 'a11y-alt-text' ); ?></h1>
  <p><?php esc_html_e( 'Below is a list of all images from your Media Library which have been processed by A11Y.so.', 'a11y-alt-text' ); ?></p>

  <?php if ( $total_assets === 0 ) : ?>

    <!-- 빈 상태 — 아직 처리된 이미지 없음 -->
    <div style="margin-top:48px; text-align:center; color:#646970;">
      <svg style="display:block; margin:0 auto 12px; width:48px; height:48px; color:#c3c4c7;"
           xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
           stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
      </svg>
      <p style="margin:0 0 6px; font-size:15px; font-weight:600; color:#1d2327;">
        <?php esc_html_e( 'No media files have been processed yet.', 'a11y-alt-text' ); ?>
      </p>
      <p style="font-size:13px;">
        <?php printf(
          wp_kses(
            __( 'Use the <a href="%s">Bulk Generate</a> tool to get started on your existing images.', 'a11y-alt-text' ),
            array( 'a' => array( 'href' => array() ) )
          ),
          esc_url( admin_url( 'admin.php?page=a11y-bulk-generate' ) )
        ); ?>
      </p>
    </div>

  <?php else : ?>

    <!-- 이력 테이블 — WP 네이티브 widefat 스타일 사용 -->
    <div class="a11y-section-card" style="margin-top:16px; overflow-x:auto;">
      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th scope="col" style="width:80px;"><?php esc_html_e( 'Media ID', 'a11y-alt-text' ); ?></th>
            <th scope="col" style="width:120px;"><?php esc_html_e( 'Image', 'a11y-alt-text' ); ?></th>
            <th scope="col"><?php esc_html_e( 'Alt Text', 'a11y-alt-text' ); ?></th>
            <th scope="col" style="width:160px;"><?php esc_html_e( 'Processed On', 'a11y-alt-text' ); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $a11y_assets as $asset ) :
            $attachment_id  = $asset->wp_post_id;
            $attachment_url = wp_get_attachment_url( $attachment_id );
            $alt_text       = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
            $edit_url       = get_edit_post_link( $attachment_id );
            $updated_at     = $asset->updated_at;
          ?>
          <tr>
            <!-- 미디어 ID + 편집 링크 -->
            <td style="vertical-align:middle;">
              <a href="<?php echo esc_url( $edit_url ); ?>" style="font-weight:600;">
                #<?php echo esc_html( $attachment_id ); ?>
              </a>
            </td>

            <!-- 썸네일 -->
            <td style="vertical-align:middle; padding:8px;">
              <?php if ( $attachment_url ) : ?>
                <a href="<?php echo esc_url( $edit_url ); ?>">
                  <img
                    src="<?php echo esc_url( $attachment_url ); ?>"
                    alt="<?php echo esc_attr( $alt_text ); ?>"
                    style="width:80px; height:60px; object-fit:cover; border-radius:3px; border:1px solid #e6e7e8;"
                  >
                </a>
              <?php else : ?>
                <span style="color:#646970; font-size:12px;">(file not found)</span>
              <?php endif; ?>
            </td>

            <!-- alt 텍스트 인라인 편집 -->
            <td style="vertical-align:middle;">
              <div style="display:flex; align-items:flex-start; gap:8px;">
                <textarea
                  id="edit-history-input-<?php echo esc_attr( $attachment_id ); ?>"
                  rows="3"
                  maxlength="1024"
                  class="large-text"
                  style="resize:vertical; min-width:200px;"
                ><?php echo esc_textarea( $alt_text ); ?></textarea>
                <div style="flex-shrink:0; display:flex; flex-direction:column; gap:4px; padding-top:2px;">
                  <button
                    type="button"
                    class="button button-secondary"
                    data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
                    data-edit-history-trigger
                    style="white-space:nowrap;"
                  >Update</button>
                  <!-- 저장 성공 메시지 — JS가 토글 -->
                  <span
                    id="edit-history-success-<?php echo esc_attr( $attachment_id ); ?>"
                    class="a11y-status-success"
                    style="display:none; font-size:12px; font-weight:600; text-align:center;"
                  >Updated!</span>
                </div>
              </div>
            </td>

            <!-- 처리 일시 — WP 날짜 포맷 사용 -->
            <td style="vertical-align:middle; font-size:12px; color:#646970; white-space:nowrap;">
              <?php echo esc_html( wp_date( get_option( 'date_format' ) . ' H:i', strtotime( $updated_at ) ) ); ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- 페이지네이션 — WP 네이티브 tablenav 스타일 -->
    <?php if ( $total_pages > 1 ) : ?>
      <div class="tablenav bottom" style="margin-top:16px;">
        <div class="tablenav-pages">
          <span class="displaying-num">
            <?php printf(
              esc_html( _n( '%d item', '%d items', $total_assets, 'a11y-alt-text' ) ),
              (int) $total_assets
            ); ?>
          </span>
          <span class="pagination-links">

            <?php if ( $pagination_start > A11Y_HISTORY_PAGE_SELECTORS ) : ?>
              <a class="first-page button"
                 href="<?php echo esc_url( add_query_arg( 'paged', 1 ) ); ?>"
                 title="First page"><span aria-hidden="true">&laquo;</span></a>
              <a class="prev-page button"
                 href="<?php echo esc_url( add_query_arg( 'paged', $pagination_start - 1 ) ); ?>"
                 title="Previous page"><span aria-hidden="true">&lsaquo;</span></a>
            <?php endif; ?>

            <?php for ( $i = $pagination_start; $i <= $pagination_end; $i++ ) :
              if ( $i === $paged ) : ?>
                <span class="paging-input">
                  <span class="tablenav-paging-text"><?php echo esc_html( $i ); ?></span>
                </span>
              <?php else : ?>
                <a class="button" style="text-decoration:none;"
                   href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>">
                  <?php echo esc_html( $i ); ?>
                </a>
              <?php endif;
            endfor; ?>

            <?php if ( $pagination_end < $total_pages ) : ?>
              <a class="next-page button"
                 href="<?php echo esc_url( add_query_arg( 'paged', $pagination_end + 1 ) ); ?>"
                 title="Next page"><span aria-hidden="true">&rsaquo;</span></a>
              <a class="last-page button"
                 href="<?php echo esc_url( add_query_arg( 'paged', $total_pages ) ); ?>"
                 title="Last page"><span aria-hidden="true">&raquo;</span></a>
            <?php endif; ?>

          </span>
        </div>
        <br class="clear">
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>