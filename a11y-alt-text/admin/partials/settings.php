<?php
/**
 * Admin settings page view for the A11Y plugin.
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
  $has_file_based_api_key = defined( 'A11Y_API_KEY' );
  $wp_kses_args = array(
    'a'      => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
    'br'     => array(),
    'strong' => array(),
    'code'   => array(),
  );

  // 멀티사이트 네트워크 제어 여부 확인
  $is_multisite                = is_multisite();
  $is_main_site                = is_main_site();
  $network_controls_api_key    = $is_multisite && get_site_option( 'a11y_network_api_key' ) === 'yes';
  $network_controls_all        = $is_multisite && get_site_option( 'a11y_network_all_settings' ) === 'yes';
  $network_hides_credits       = $is_multisite && ! $is_main_site && get_site_option( 'a11y_network_hide_credits' ) === 'yes';
  $settings_network_controlled = $is_multisite && ! $is_main_site && $network_controls_all;
  $api_key_locked              = $is_multisite && ! $is_main_site && ( $network_controls_api_key || $network_controls_all );

  // 언어 설정 — 영어/한국어만 지원
  $lang = A11Y_Utility::get_setting( 'a11y_lang', A11Y_Utility::get_default_language() );
  $supported_languages = array(
    'en' => 'English',
    'ko' => 'Korean',
  );

  $timeout_secs   = intval( A11Y_Utility::get_setting( 'a11y_timeout', 20 ) );
  $timeout_values = array( 10, 15, 20, 25, 30 );

  // API Key 유무 — 웰컴 패널 표시 및 버튼 텍스트 분기 기준
  $has_api_key = ! empty( A11Y_Utility::get_api_key() );
?>

<div class="wrap a11y-wrap">

  <?php if ( ! $has_api_key ) : ?>
  <!-- ① 웰컴 패널: API Key가 없을 때만 PHP에서 렌더링 -->
  <!-- 로고 SVG는 원본 base64 그대로 유지 — 수정 시 깨짐 -->
  <div id="a11y-welcome-panel" class="a11y-welcome-panel">

    <div class="a11y-welcome-header">
      <div class="a11y-welcome-header-left">
        <svg width="142" height="30" viewBox="0 0 142 30" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
          <rect width="142" height="30" fill="url(#pattern0_1412_18568)"/>
          <defs>
          <pattern id="pattern0_1412_18568" patternContentUnits="objectBoundingBox" width="1" height="1">
          <use xlink:href="#image0_1412_18568" transform="scale(0.00352113 0.0166667)"/>
          </pattern>
          <image id="image0_1412_18568" width="284" height="60" preserveAspectRatio="none" xlink:href="data:image/png;base64,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"/>
          </defs>
        </svg>
      </div>
      <button id="a11y-welcome-close-btn" class="a11y-welcome-dismiss" aria-label="<?php esc_attr_e( 'Dismiss', 'a11y-alt-text' ); ?>">
        Dismiss
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 6L6 18M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div class="a11y-welcome-intro">
      <p><?php esc_html_e( 'A11Y.so generates alt text and long descriptions so every visitor — including screen reader users — can experience your images. Complete the steps below to get started.', 'a11y-alt-text' ); ?></p>
    </div>

    <div class="a11y-welcome-cards">
      <div class="a11y-welcome-card">
        <span class="a11y-welcome-card-num">1</span>
        <p class="a11y-welcome-card-title"><?php esc_html_e( 'Create your account', 'a11y-alt-text' ); ?></p>
        <p class="a11y-welcome-card-body"><?php printf(
          wp_kses(
            __( 'Sign up for a free account at <a href="%s" target="_blank" rel="noopener noreferrer">a11y.so</a>', 'a11y-alt-text' ),
            array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
          ),
          'https://a11y.so?utm_source=wp'
        ); ?></p>
      </div>
      <div class="a11y-welcome-card">
        <span class="a11y-welcome-card-num">2</span>
        <p class="a11y-welcome-card-title"><?php esc_html_e( 'Connect your API key', 'a11y-alt-text' ); ?></p>
        <p class="a11y-welcome-card-body"><?php printf(
          wp_kses(
            __( 'Copy <a href="%s" target="_blank" rel="noopener noreferrer">your API key</a> from your account and enter it in the input field below.', 'a11y-alt-text' ),
            array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
          ),
          'https://a11y.so/account/api_keys'
        ); ?></p>
      </div>
      <div class="a11y-welcome-card">
        <span class="a11y-welcome-card-num">3</span>
        <p class="a11y-welcome-card-title"><?php esc_html_e( 'Generate alt text', 'a11y-alt-text' ); ?></p>
        <p class="a11y-welcome-card-body"><?php printf(
          wp_kses(
            __( 'Once your API key is connected, you can use the image alt text generation feature.', 'a11y-alt-text' ),
            array( 'a' => array( 'href' => array() ) )
          ),
          esc_url( admin_url( 'admin.php?page=a11y-bulk-generate' ) )
        ); ?></p>
      </div>
    </div>

    <div class="a11y-welcome-footer">
      <span class="a11y-welcome-footer-dot" aria-hidden="true"></span>
      <p>
        <?php esc_html_e( 'API not connected — ', 'a11y-alt-text' ); ?>
        <strong><?php esc_html_e( 'Mock Mode active.', 'a11y-alt-text' ); ?></strong>
        <?php esc_html_e( 'Any API key value works for testing. Dummy data will be returned.', 'a11y-alt-text' ); ?>
      </p>
    </div>

  </div>

  <!-- 웰컴 패널 닫기 JS — sessionStorage 미사용, PHP 렌더링이 곧 표시 여부 제어 -->
  <script>
  (function () {
    var panel    = document.getElementById('a11y-welcome-panel');
    var closeBtn = document.getElementById('a11y-welcome-close-btn');
    if (!panel || !closeBtn) return;
    closeBtn.addEventListener('click', function (e) {
      e.preventDefault();
      panel.style.display = 'none';
    });
  })();
  </script>
  <?php endif; ?>

  <!-- 목업 모드 배너 — API Key 있을 때만 표시 -->
  <?php if ( $has_api_key ) : ?>
  <div class="a11y-mock-banner" role="status">
    <span class="a11y-mock-badge">Mock Mode</span>
    <p>The A11Y.so API is not yet connected. Any API Key value will work for testing — dummy data will be returned.</p>
  </div>
  <?php endif; ?>

  <div class="a11y-settings-form-header">
    <h1 class="a11y-settings-heading"><?php esc_html_e( 'A11Y.so WordPress Settings', 'a11y-alt-text' ); ?></h1>

    <?php if ( ! $settings_network_controlled ) : ?>
      <button
        type="submit"
        form="a11y-settings-form"
        name="submit"
        class="button button-primary a11y-header-save-btn"
      >
        <?php esc_html_e( 'Save Changes', 'a11y-alt-text' ); ?>
      </button>
    <?php endif; ?>
  </div>

  <?php if ( $settings_network_controlled || $api_key_locked ) : ?>
    <div class="notice notice-info a11y-network-controlled-notice">
      <p>
        <strong><?php esc_html_e( 'Network Settings Active:', 'a11y-alt-text' ); ?></strong>
        <?php if ( $settings_network_controlled ) :
          esc_html_e( 'All settings are controlled by the network administrator and cannot be changed on this site.', 'a11y-alt-text' );
        elseif ( $api_key_locked ) :
          esc_html_e( 'The API key is shared across the network and cannot be changed on this site. Other settings can be configured locally.', 'a11y-alt-text' );
        endif; ?>
      </p>
    </div>
  <?php endif; ?>

  <form
    id="a11y-settings-form"
    method="post"
    class="a11y-settings-form <?php echo $settings_network_controlled ? 'a11y-network-controlled' : ''; ?>"
    action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>"
  >
    <?php settings_fields( 'a11y-settings' ); ?>
    <?php do_settings_sections( 'a11y-settings' ); ?>


    <!-- ================================================================
         카드 1: API Key & Account
         API Key 입력 + 계정 링크를 한 곳에 — 연결 상태를 한눈에 파악
         ================================================================ -->
    <div class="a11y-section-card">
      <div class="a11y-section-header">
        <h2><?php esc_html_e( 'API Key', 'a11y-alt-text' ); ?></h2>
      </div>
      <div class="a11y-section-body">
        <table class="form-table" role="presentation">
          <tbody>

            <tr>
              <th scope="row">
                <label for="a11y_api_key"><?php esc_html_e( 'API Key', 'a11y-alt-text' ); ?></label>
              </th>
              <td>
                <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                  <input
                    type="text"
                    id="a11y_api_key"
                    name="a11y_api_key"
                    class="regular-text"
                    value="<?php echo $has_api_key ? '*********' : ''; ?>"
                    <?php if ( $has_file_based_api_key || $has_api_key || $api_key_locked ) echo 'readonly'; ?>
                  >
                  <?php if ( ! $api_key_locked ) :
                    // ★ admin.js가 "Clear API Key" 문자열을 감지해 input을 비워줌 — 영어 고정 필수
                    $btn_value = $has_api_key ? 'Clear API Key' : 'Add API Key';
                    $btn_class = $has_api_key ? 'button button-secondary' : 'button button-primary';
                  ?>
                    <input
                      type="submit"
                      name="handle_api_key"
                      class="<?php echo $btn_class; ?>"
                      value="<?php echo esc_attr( $btn_value ); ?>"
                      <?php if ( $has_file_based_api_key ) echo 'disabled'; ?>
                    >
                  <?php endif; ?>
                </div>

                <?php if ( ! $has_api_key ) : ?>
                  <div class="a11y-api-status is-empty">
                    <?php printf(
                      wp_kses(
                        __( 'Get your API Key at <a href="%s" target="_blank">A11Y.so &gt; Account &gt; API Keys</a>.', 'a11y-alt-text' ),
                        array( 'a' => array( 'href' => array(), 'target' => array() ) )
                      ),
                      esc_url( 'https://a11y.so/account/api_keys' )
                    ); ?>
                  </div>
                <?php elseif ( $this->account === false ) : ?>
                  <div class="a11y-api-status is-error">
                    <?php if ( $this->account_error_type === 'auth' ) :
                      printf(
                        wp_kses(
                          __( 'Your API key is invalid. Please check your API key or <a href="%s" target="_blank">create a new one</a>.', 'a11y-alt-text' ),
                          array( 'a' => array( 'href' => array(), 'target' => array() ) )
                        ),
                        esc_url( 'https://a11y.so/account/api_keys' )
                      );
                    else :
                      esc_html_e( 'Unable to verify your API key. Please check your connection and try again.', 'a11y-alt-text' );
                    endif; ?>
                  </div>
                <?php elseif ( ! $network_hides_credits ) : ?>
                  <div class="a11y-api-status is-ok">
                    <?php if ( ! $this->account['whitelabel'] ) : ?>
                      <p>
                        <?php printf(
                          wp_kses( __( 'You\'re on the <strong>%s</strong> plan.', 'a11y-alt-text' ), array( 'strong' => array() ) ),
                          esc_html( $this->account['plan'] )
                        ); ?>
                      </p>
                    <?php endif; ?>

                    <p>
                      <?php printf(
                        wp_kses( __( 'You have <strong>%d</strong> credits available out of <strong>%d</strong>.', 'a11y-alt-text' ), array( 'strong' => array() ) ),
                        (int) $this->account['available'],
                        (int) $this->account['quota']
                      ); ?>
                    </p>

                    <?php
                      $plan = strtolower( $this->account['plan'] );
                      $is_upgrade_target = ( strpos($plan, 'enterprise') === false );
                    ?>

                    <?php if ( $is_upgrade_target ) : ?>
                      <p>
                        <?php esc_html_e( 'You can upgrade your plan to get more credits.', 'a11y-alt-text' ); ?>
                      </p>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </td>
            </tr>

            <!-- 계정 링크 — API Key 카드에 통합 -->
            <?php if ( ! $this->account || ! $this->account['whitelabel'] ) : ?>
            <tr>
              <th scope="row"><?php esc_html_e( 'Account', 'a11y-alt-text' ); ?></th>
              <td>
                <?php printf(
                  wp_kses(
                    __( '<a href="%s" target="_blank">Manage your account</a> and additional settings on a11y.so.', 'a11y-alt-text' ),
                    array( 'a' => array( 'href' => array(), 'target' => array() ) )
                  ),
                  esc_url( 'https://a11y.so/account/edit?utm_source=wp&utm_medium=dl' )
                ); ?>
              </td>
            </tr>
            <?php endif; ?>

          </tbody>
        </table>
      </div>
    </div>


    <!-- ================================================================
         카드 2: Generation Settings
         Description Format + Language + 생성 시 동작 + 새 이미지 자동 생성
         ================================================================ -->
    <div class="a11y-section-card">
      <div class="a11y-section-header">
        <h2><?php esc_html_e( 'Generation Settings', 'a11y-alt-text' ); ?></h2>
      </div>
      <div class="a11y-section-body">
        <table class="form-table" role="presentation">
          <tbody>

      <!-- Generation Mode — 간소화 / 웹접근성 지침 -->
      <tr>
        <th scope="row">
          <label><?php esc_html_e( 'Generation Mode', 'a11y-alt-text' ); ?></label>
        </th>
        <td>
          <?php $current_mode = get_option( 'a11y_generation_mode', 'wcag' ); ?>

          <div class="a11y-radio-card <?php echo $current_mode === 'wcag' ? 'is-selected' : ''; ?>"
              id="a11y-mode-card-wcag">
            <label class="a11y-radio-card-label">
              <input type="radio" name="a11y_generation_mode" value="wcag"
                    <?php checked( $current_mode, 'wcag' ); ?>>
              <span class="a11y-radio-card-title">
                <?php esc_html_e( '웹접근성 지침 모드', 'a11y-alt-text' ); ?>
                <span class="a11y-badge-recommended"><?php esc_html_e( '권장', 'a11y-alt-text' ); ?></span>
              </span>
            </label>
            <p class="a11y-radio-card-desc">
              <?php esc_html_e( 'AI가 이미지 유형을 자동 분류하여 WCAG 2.2 기준에 맞는 결과물을 생성합니다.', 'a11y-alt-text' ); ?>
            </p>
            <ul class="a11y-radio-card-list">
              <li><?php esc_html_e( '일반 이미지 → 간결한 alt 텍스트', 'a11y-alt-text' ); ?></li>
              <li><?php esc_html_e( '복합 이미지(차트·인포그래픽 등) → alt + aria-describedby 상세 설명', 'a11y-alt-text' ); ?></li>
              <li><?php esc_html_e( '장식 이미지 → alt="" (빈 값, 스크린 리더 스킵)', 'a11y-alt-text' ); ?></li>
            </ul>
          </div>

          <div class="a11y-radio-card <?php echo $current_mode === 'simple' ? 'is-selected' : ''; ?>"
              id="a11y-mode-card-simple">
            <label class="a11y-radio-card-label">
              <input type="radio" name="a11y_generation_mode" value="simple"
                    <?php checked( $current_mode, 'simple' ); ?>>
              <span class="a11y-radio-card-title">
                <?php esc_html_e( '간소화 모드', 'a11y-alt-text' ); ?>
              </span>
            </label>
            <p class="a11y-radio-card-desc">
              <?php esc_html_e( '이미지 유형을 분류하지 않고, 모든 이미지에 간결한 alt 텍스트만 생성합니다.', 'a11y-alt-text' ); ?>
            </p>
          </div>



          <script>
          (function () {
            document.querySelectorAll('.a11y-radio-card').forEach(function (card) {
              // 카드 전체 클릭 시 내부 라디오 선택
              card.addEventListener('click', function (e) {
                var radio = this.querySelector('input[type="radio"]');
                if (!radio) return;
                radio.checked = true;
                // is-selected 클래스 토글
                document.querySelectorAll('.a11y-radio-card').forEach(function (c) {
                  c.classList.remove('is-selected');
                });
                this.classList.add('is-selected');
              });
            });
          })();
          </script>
        </td>
      </tr>

            <!-- Alt Text Language -->
            <tr>
              <th scope="row">
                <label for="a11y_lang"><?php esc_html_e( 'Alt Text Language', 'a11y-alt-text' ); ?></label>
              </th>
              <td>
                <select id="a11y_lang" name="a11y_lang">
                  <?php foreach ( $supported_languages as $lc => $ln ) :
                    $sel = ( $lang === $lc ) ? ' selected' : '';
                    echo wp_kses(
                      "<option value=\"$lc\"$sel>$ln</option>\n",
                      array( 'option' => array( 'selected' => array(), 'value' => array() ) )
                    );
                  endforeach; ?>
                </select>
              </td>
            </tr>

            <!-- alt 생성 시 함께 업데이트할 필드들 -->
            <tr>
              <th scope="row"><?php esc_html_e( 'Also update:', 'a11y-alt-text' ); ?></th>
              <td>
                <?php foreach ( array(
                  'a11y_update_title'       => 'Also set the image title with the generated alt text.',
                  'a11y_update_caption'     => 'Also set the image caption with the generated alt text.',
                  'a11y_update_description' => 'Also set the image description with the generated alt text.',
                ) as $opt => $label ) : ?>
                  <div class="a11y-checkbox-row">
                    <input type="checkbox" id="<?php echo esc_attr( $opt ); ?>"
                           name="<?php echo esc_attr( $opt ); ?>" value="yes"
                           <?php checked( 'yes', A11Y_Utility::get_setting( $opt ) ); ?>>
                    <label for="<?php echo esc_attr( $opt ); ?>" class="a11y-checkbox-label">
                      <?php echo esc_html( $label ); ?>
                    </label>
                  </div>
                <?php endforeach; ?>
              </td>
            </tr>

            <!-- 새 이미지 업로드 시 자동 생성 -->
            <tr>
              <th scope="row"><?php esc_html_e( 'New uploads:', 'a11y-alt-text' ); ?></th>
              <td>
                <div class="a11y-checkbox-row">
                  <input type="checkbox" id="a11y_enabled" name="a11y_enabled" value="yes"
                         <?php checked( 'yes', A11Y_Utility::get_setting( 'a11y_enabled', 'yes' ) ); ?>>
                  <div>
                    <label for="a11y_enabled" class="a11y-checkbox-label">
                      <?php esc_html_e( 'Automatically generate alt text with A11Y.so.', 'a11y-alt-text' ); ?>
                    </label>
                    <p class="a11y-checkbox-desc">
                      <?php esc_html_e( 'Note: You can always generate alt text using the Bulk Generate page or Update Alt Text button on an individual image.', 'a11y-alt-text' ); ?>
                    </p>
                  </div>
                </div>
              </td>
            </tr>

          </tbody>
        </table>
      </div>
    </div>


    <!-- ================================================================
         카드 3: Filtering & Bulk Refreshing
         ================================================================ -->
    <div class="a11y-section-card">
      <div class="a11y-section-header">
        <h2><?php esc_html_e( 'Filtering & Bulk Refreshing', 'a11y-alt-text' ); ?></h2>
      </div>
      <div class="a11y-section-body">
        <table class="form-table" role="presentation">
          <tbody>

            <tr>
              <th scope="row">
                <label for="a11y_type_extensions">
                  <?php esc_html_e( 'Only process these file extensions:', 'a11y-alt-text' ); ?>
                </label>
              </th>
              <td>
                <input type="text" id="a11y_type_extensions" name="a11y_type_extensions"
                       class="regular-text" placeholder="jpg,webp"
                       value="<?php echo esc_attr( A11Y_Utility::get_setting( 'a11y_type_extensions' ) ); ?>">
                <p class="description">
                  <?php esc_html_e( 'Separate with commas. Leave blank to process all image types.', 'a11y-alt-text' ); ?>
                </p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="a11y_excluded_post_types">
                  <?php esc_html_e( 'Exclude these post types:', 'a11y-alt-text' ); ?>
                </label>
              </th>
              <td>
                <input type="text" id="a11y_excluded_post_types" name="a11y_excluded_post_types"
                       class="regular-text" placeholder="proof,submission"
                       value="<?php echo esc_attr( A11Y_Utility::get_setting( 'a11y_excluded_post_types' ) ); ?>">
                <p class="description">
                  <?php esc_html_e( 'Separate with commas. Leave blank to process images from all post types.', 'a11y-alt-text' ); ?>
                </p>
              </td>
            </tr>

            <!-- skip_filenotfound — 전역 처리 동작, Bulk 전용 아님 -->
            <tr>
              <th scope="row"><?php esc_html_e( 'Image availability:', 'a11y-alt-text' ); ?></th>
              <td>
                <div class="a11y-checkbox-row">
                  <input type="checkbox" id="a11y_skip_filenotfound"
                        name="a11y_skip_filenotfound" value="yes"
                        <?php checked( 'yes', A11Y_Utility::get_setting( 'a11y_skip_filenotfound' ) ); ?>>
                  <label for="a11y_skip_filenotfound" class="a11y-checkbox-label">
                    <?php esc_html_e( 'Skip image files unable to be found on the server.', 'a11y-alt-text' ); ?>
                  </label>
                </div>
              </td>
            </tr>

            <!-- Bulk action behavior — Bulk Action 메뉴 전용 옵션만 묶음 -->
            <tr>
              <th scope="row"><?php esc_html_e( 'Bulk action behavior:', 'a11y-alt-text' ); ?></th>
              <td>
                <?php foreach ( array(
                  'a11y_bulk_refresh_overwrite' => 'Overwrite existing alt text when refreshing posts and pages using the Bulk Action menu.',
                  'a11y_bulk_refresh_external'  => 'Process external images when refreshing posts and pages using the Bulk Action menu.',
                ) as $opt => $label ) : ?>
                  <div class="a11y-checkbox-row">
                    <input type="checkbox" id="<?php echo esc_attr( $opt ); ?>"
                          name="<?php echo esc_attr( $opt ); ?>" value="yes"
                          <?php checked( 'yes', A11Y_Utility::get_setting( $opt ) ); ?>>
                    <label for="<?php echo esc_attr( $opt ); ?>" class="a11y-checkbox-label">
                      <?php echo esc_html( $label ); ?>
                    </label>
                  </div>
                <?php endforeach; ?>
              </td>
            </tr>

          </tbody>
        </table>
      </div>
    </div>


    <!-- ================================================================
         카드 4: Technical Settings
         Miscellaneous + Timeout + Error Logs — 고급 옵션 일체
         ================================================================ -->
    <div class="a11y-section-card">
      <div class="a11y-section-header">
        <h2><?php esc_html_e( 'Technical Settings', 'a11y-alt-text' ); ?></h2>
        <p><?php esc_html_e( 'Advanced settings — only modify these if needed.', 'a11y-alt-text' ); ?></p>
      </div>
      <div class="a11y-section-body">
        <table class="form-table" role="presentation">
          <tbody>

            <!-- Miscellaneous -->
            <tr>
              <th scope="row"><?php esc_html_e( 'Miscellaneous', 'a11y-alt-text' ); ?></th>
              <td>
                <div class="a11y-checkbox-row">
                  <input type="checkbox" id="a11y_public" name="a11y_public" value="yes"
                         <?php checked( 'yes', A11Y_Utility::get_setting( 'a11y_public' ) ); ?>>
                  <div>
                    <label for="a11y_public" class="a11y-checkbox-label">
                      <?php esc_html_e( 'This site is reachable over the public internet.', 'a11y-alt-text' ); ?>
                    </label>
                    <p class="a11y-checkbox-desc">
                      <?php esc_html_e( 'Check to allow A11Y.so to fetch your images via URLs. If this site is private, uncheck this box and images will be uploaded to A11Y.so instead.', 'a11y-alt-text' ); ?>
                    </p>
                  </div>
                </div>
                <div class="a11y-checkbox-row" style="margin-top:8px;">
                  <input type="checkbox" id="a11y_no_credit_warning" name="a11y_no_credit_warning" value="yes"
                         <?php checked( 'yes', A11Y_Utility::get_setting( 'a11y_no_credit_warning' ) ); ?>>
                  <label for="a11y_no_credit_warning" class="a11y-checkbox-label">
                    <?php esc_html_e( 'Do not show warning when out of credits.', 'a11y-alt-text' ); ?>
                  </label>
                </div>
              </td>
            </tr>

            <!-- Admin Menu Access — API Key 연결 후에만 표시 -->
            <?php if ( $has_api_key && ! $settings_network_controlled ) : ?>
            <tr>
              <th scope="row">
                <label for="a11y_admin_capability">
                  <?php esc_html_e( 'Menu Access', 'a11y-alt-text' ); ?>
                </label>
              </th>
              <td>
                <?php
                  $current_cap = A11Y_Utility::get_setting( 'a11y_admin_capability', 'manage_options' );
                  $capabilities = array(
                    'manage_options'    => __( 'Administrator only', 'a11y-alt-text' ),
                    'edit_others_posts' => __( 'Editor and above', 'a11y-alt-text' ),
                    'publish_posts'     => __( 'Author and above', 'a11y-alt-text' ),
                    'read'              => __( 'All logged-in users', 'a11y-alt-text' ),
                  );
                ?>
                <select id="a11y_admin_capability" name="a11y_admin_capability">
                  <?php foreach ( $capabilities as $cap => $label ) : ?>
                    <option value="<?php echo esc_attr( $cap ); ?>"
                      <?php selected( $current_cap, $cap ); ?>>
                      <?php echo esc_html( $label ); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <p class="description">
                  <?php esc_html_e( 'Control which user roles can access the A11Y.so admin menu.', 'a11y-alt-text' ); ?>
                </p>
              </td>
            </tr>
            <?php endif; ?>

            <!-- Timeout -->
            <tr>
              <th scope="row">
                <label for="a11y_timeout">
                  <?php esc_html_e( 'Request timeout:', 'a11y-alt-text' ); ?>
                </label>
              </th>
              <td>
                <div style="display:flex; align-items:center; gap:8px;">
                  <select id="a11y_timeout" name="a11y_timeout">
                    <?php foreach ( $timeout_values as $tv ) :
                      $sel = ( $timeout_secs === $tv ) ? ' selected' : '';
                      echo wp_kses(
                        "<option value=\"$tv\"$sel>{$tv}</option>\n",
                        array( 'option' => array( 'selected' => array(), 'value' => array() ) )
                      );
                    endforeach; ?>
                  </select>
                  <span class="description"><?php esc_html_e( 'seconds', 'a11y-alt-text' ); ?></span>
                </div>
              </td>
            </tr>

            <!-- Error Logs -->
            <tr id="a11y_error_logs_container">
              <th scope="row"><?php esc_html_e( 'Error Logs', 'a11y-alt-text' ); ?></th>
              <td>
                <div id="a11y_error_logs" class="a11y-error-log">
                  <?php echo wp_kses( A11Y_Utility::get_error_logs(), $wp_kses_args ); ?>
                </div>
                <div style="margin-top:8px;">
                  <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'a11y_action', 'clear-error-logs' ), 'a11y_clear_error_logs' ) ); ?>"
                     class="button button-secondary">
                    <?php esc_html_e( 'Clear Logs', 'a11y-alt-text' ); ?>
                  </a>
                </div>
              </td>
            </tr>

          </tbody>
        </table>
      </div>
    </div>

    <div class="a11y-section-card" style="border-left: 3px solid #f59e0b;">
      <div class="a11y-section-header">
        <h2>🧪 Mock Mode — Test Controls</h2>
      </div>
      <div class="a11y-section-body">
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row">Mock Response Type</th>
            <td>
              <?php $mock_type = get_option('a11y_mock_response_type', 'graphic'); ?>
              <select name="a11y_mock_response_type">
                <option value="simple"  <?php selected($mock_type, 'simple');  ?>>간소화 모드 — alt만 반환</option>
                <option value="graphic" <?php selected($mock_type, 'graphic'); ?>>웹접근성 지침: 일반 이미지 — alt만 반환</option>
                <option value="complex" <?php selected($mock_type, 'complex'); ?>>웹접근성 지침: 복합 이미지 — alt + 상세설명 반환</option>
                <option value="decorative" <?php selected($mock_type, 'decorative'); ?>>웹접근성 지침: 장식 이미지 — alt="" 반환</option>
              </select>
              <p class="description">실제 API 연결 전 각 시나리오별 UI를 테스트합니다. 이미지 유형 분류는 내부 처리되며 사용자에게 노출되지 않습니다.</p>
            </td>
          </tr>
        </table>
      </div>
    </div>


    <!-- 저장 버튼 + 버전 표시 -->
    <div class="a11y-settings-footer">
      <span class="a11y-version">v<?php echo esc_html( A11Y_VERSION ); ?></span>
      <?php if ( ! $settings_network_controlled ) :
        submit_button( __( 'Save Changes', 'a11y-alt-text' ), 'primary a11y-header-save-btn', 'submit', false );
      endif; ?>
    </div>

  </form>
</div>



<?php if ( $settings_network_controlled ) : ?>
<script>
// 네트워크 제어 중일 때 모든 입력 필드 비활성화
document.addEventListener('DOMContentLoaded', function () {
  var form = document.querySelector('.a11y-network-controlled');
  if (form) {
    form.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach(function(el){
      el.disabled = true;
    });
  }
});
</script>
<?php endif; ?>