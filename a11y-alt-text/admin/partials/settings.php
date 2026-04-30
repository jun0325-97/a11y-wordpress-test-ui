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
        <svg width="181" height="30" viewBox="0 0 181 30" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
        <rect width="181" height="30" fill="url(#pattern0_1418_18569)"/>
        <defs>
        <pattern id="pattern0_1418_18569" patternContentUnits="objectBoundingBox" width="1" height="1">
        <use xlink:href="#image0_1418_18569" transform="scale(0.00277008 0.0166667)"/>
        </pattern>
        <image id="image0_1418_18569" width="361" height="60" preserveAspectRatio="none" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAWkAAAA8CAYAAACkT0u+AAAACXBIWXMAABYlAAAWJQFJUiTwAAAFu2lUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgOS4xLWMwMDIgNzkuYjdjNjRjYywgMjAyNC8wNy8xNi0wNzo1OTo0MCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iIHhtbG5zOnBob3Rvc2hvcD0iaHR0cDovL25zLmFkb2JlLmNvbS9waG90b3Nob3AvMS4wLyIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0RXZ0PSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VFdmVudCMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIDI2LjAgKFdpbmRvd3MpIiB4bXA6Q3JlYXRlRGF0ZT0iMjAyNi0wNC0zMFQxNjoyMzoxMiswOTowMCIgeG1wOk1vZGlmeURhdGU9IjIwMjYtMDQtMzBUMTY6NDM6NTQrMDk6MDAiIHhtcDpNZXRhZGF0YURhdGU9IjIwMjYtMDQtMzBUMTY6NDM6NTQrMDk6MDAiIGRjOmZvcm1hdD0iaW1hZ2UvcG5nIiBwaG90b3Nob3A6Q29sb3JNb2RlPSIzIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOmZlZDc0MmRhLTVhZGUtODU0Yy05MDU3LTc1MDYzM2I4NjZlMyIgeG1wTU06RG9jdW1lbnRJRD0i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"/>
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

  <!-- 설정 저장 성공 알림 — .inline으로 WP JS의 자동 h1 이동 방지 -->
  <?php if ( isset( $_GET['settings-updated'] ) ) : ?>
  <div class="notice notice-success is-dismissible inline">
    <p><strong><?php esc_html_e( 'Settings saved.', 'a11y-alt-text' ); ?></strong></p>
  </div>
  <?php endif; ?>

  <!-- 인라인 알림들 — .inline으로 WP JS의 자동 h1 이동 방지 -->
  <?php if ( isset( $_GET['api_key_missing'] ) && ! $has_api_key ) : ?>
  <div class="notice notice--a11y notice-warning inline">
    <p><?php echo wp_kses( __( '[A11Y.so] Please <strong>add your API key</strong> to generate alt text.', 'a11y-alt-text' ), array( 'strong' => array() ) ); ?></p>
  </div>
  <?php endif; ?>

  <?php if ( get_transient( 'a11y_insufficient_credits' ) && A11Y_Utility::get_setting( 'a11y_no_credit_warning' ) !== 'yes' ) : ?>
  <div class="notice notice--a11y notice-error is-dismissible inline">
    <p><?php printf(
      wp_kses(
        __( '[A11Y.so] You have no more credits available. <a href="%s" target="_blank">Manage your account</a> to get more credits.', 'a11y-alt-text' ),
        array( 'a' => array( 'href' => array(), 'target' => array() ) )
      ),
      esc_url( A11Y_Utility::get_credits_url() )
    ); ?></p>
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
                          esc_html( $this->account['tw_plan'] )
                        ); ?>
                      </p>
                    <?php endif; ?>

                    <p>
                      <?php printf(
                        wp_kses( __( 'You have <strong>%d</strong> credits available.', 'a11y-alt-text' ), array( 'strong' => array() ) ),
                        (int) $this->account['credit']
                      ); ?>
                    </p>

                    <?php
                      $plan = strtolower( $this->account['tw_plan'] );
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

    <?php $mock_type = get_option('a11y_mock_response_type', 'graphic'); ?>
    <div class="a11y-section-card" style="border-left: 3px solid #f59e0b;">
      <div class="a11y-section-header">
        <h2>🧪 Mock Mode — Test Controls</h2>
      </div>
      <div class="a11y-section-body">
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row">Mock Response Type</th>
            <td>
              <select id="a11y_mock_response_type" name="a11y_mock_response_type">
                <!-- 간소화 모드 옵션 -->
                <option value="simple"
                  class="mock-opt-simple"
                  <?php selected($mock_type, 'simple'); ?>>
                  이미지 유형: 간소화 — alt만 반환
                </option>
                <!-- 웹접근성 지침 모드 옵션 -->
                <option value="graphic"
                  class="mock-opt-wcag"
                  <?php selected($mock_type, 'graphic'); ?>>
                  이미지 유형: 일반 이미지 — alt만 반환
                </option>
                <option value="complex"
                  class="mock-opt-wcag"
                  <?php selected($mock_type, 'complex'); ?>>
                  이미지 유형: 복합 이미지 — alt + 상세설명 반환
                </option>
                <option value="decorative"
                  class="mock-opt-wcag"
                  <?php selected($mock_type, 'decorative'); ?>>
                  이미지 유형: 장식 이미지 — alt="" 반환
                </option>
                <!-- 공통 -->
                <option value="no_credits"
                  class="mock-opt-simple mock-opt-wcag"
                  <?php selected($mock_type, 'no_credits'); ?>>
                  크레딧 없음 — insufficient_credits 에러 반환
                </option>
              </select>
              <p class="description">Generation Mode에 따라 테스트 가능한 시나리오가 표시됩니다. 이미지 유형 분류는 내부 처리되며 사용자에게 노출되지 않습니다.</p>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <script>
    (function () {
      var mockSelect = document.getElementById('a11y_mock_response_type');
      if (!mockSelect) return;

      function applyMode(mode) {
        var isSimple  = (mode === 'simple');
        var showClass = isSimple ? 'mock-opt-simple' : 'mock-opt-wcag';

        Array.from(mockSelect.options).forEach(function (opt) {
          var show     = opt.classList.contains(showClass);
          opt.hidden   = !show;
          opt.disabled = !show;
        });

        // 현재 선택값이 숨겨진 경우 첫 번째 visible 옵션으로 초기화
        if (mockSelect.selectedOptions[0] && mockSelect.selectedOptions[0].hidden) {
          var first = Array.from(mockSelect.options).find(function (o) { return !o.hidden; });
          if (first) mockSelect.value = first.value;
        }
      }

      function getSelectedMode() {
        var checked = document.querySelector('input[name="a11y_generation_mode"]:checked');
        return checked ? checked.value : 'wcag';
      }

      // 초기 적용
      applyMode(getSelectedMode());

      // 카드 전체 클릭 이벤트 감지 — 기존 카드 JS가 radio.checked를 코드로 변경하므로
      // change 이벤트가 발생하지 않음. 카드 클릭을 직접 감지해 모드 반영.
      document.querySelectorAll('.a11y-radio-card').forEach(function (card) {
        card.addEventListener('click', function () {
          var radio = this.querySelector('input[name="a11y_generation_mode"]');
          if (radio) applyMode(radio.value);
        });
      });
    })();
    </script>


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