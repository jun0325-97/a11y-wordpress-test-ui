<?php
/**
 * Sync Library (CSV Import) page for the A11Y plugin.
 *
 * @link       https://a11y.so
 * @since      1.1.0
 *
 * @package    A11Y
 * @subpackage A11Y/admin/partials
 */
?>

<?php if ( ! defined( 'WPINC' ) ) die; ?>

<?php
  // CSV 업로드 처리 — 폼 제출 시
  $message      = '';
  $message_type = '';

  if ( isset( $_POST['submit'] ) && isset( $_FILES['csv'] ) ) {
    $attachment = new A11Y_Attachment();
    $response   = $attachment->process_csv();

    if ( $response['status'] === 'success' ) {
      $message      = esc_html( $response['message'] );
      $message_type = 'success';
    } elseif ( $response['status'] === 'error' ) {
      $message      = esc_html( $response['message'] );
      $message_type = 'error';
    }
  }
?>

<div class="wrap a11y-wrap">

  <!-- 목업 모드 배너 -->
  <div class="a11y-mock-banner" role="status">
    <span class="a11y-mock-badge">Mock Mode</span>
    <p>The A11Y API is not yet connected. You can test the CSV import flow with dummy data.</p>
  </div>

  <h1><?php esc_html_e( 'Sync Alt Text Library', 'a11y-alt-text' ); ?></h1>
  <p>
    <?php esc_html_e( 'Synchronize any changes or edits from your online A11Y.so image library to WordPress. Any matching images in WordPress will be updated with the corresponding alt text from your library.', 'a11y-alt-text' ); ?>
  </p>

  <!-- 처리 결과 메시지 -->
  <?php if ( $message ) : ?>
    <div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible" style="margin:16px 0;">
      <p><?php echo $message; ?></p>
    </div>
  <?php endif; ?>

  <div style="max-width:640px;">

    <!-- Step 1: 온라인 라이브러리 내보내기 -->
    <div class="a11y-section-card">
      <div class="a11y-section-header">
        <h2><?php esc_html_e( 'Step 1: Export your online library', 'a11y-alt-text' ); ?></h2>
      </div>
      <div class="a11y-section-body" style="padding:16px;">
        <ol style="margin:0; padding-left:20px; font-size:13px; line-height:2.2;">
          <li>
            <?php printf(
              wp_kses(
                __( 'Go to your <a href="%s" target="_blank" rel="noopener noreferrer">A11Y.so Image Library</a>.', 'a11y-alt-text' ),
                array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
              ),
              'https://a11y.so/images'
            ); ?>
          </li>
          <li><?php esc_html_e( 'Click the Export button.', 'a11y-alt-text' ); ?></li>
          <li><?php esc_html_e( 'Start the export, then download the CSV file when it\'s done.', 'a11y-alt-text' ); ?></li>
        </ol>
      </div>
    </div>

    <!-- Step 2: CSV 파일 업로드 폼 -->
    <div class="a11y-section-card">
      <div class="a11y-section-header">
        <h2><?php esc_html_e( 'Step 2: Upload your CSV', 'a11y-alt-text' ); ?></h2>
      </div>
      <div class="a11y-section-body" style="padding:16px;">
        <form
          method="post"
          enctype="multipart/form-data"
          id="alttextai-csv-import"
          data-file-loaded="false"
        >
          <?php wp_nonce_field( 'a11y_csv_import', 'a11y_csv_import_nonce' ); ?>

          <!-- 드롭존 — JS로 파일 선택 상태 토글 -->
          <label
            for="file_input"
            id="a11y-csv-dropzone"
            class="a11y-csv-dropzone"
            tabindex="0"
            role="button"
            aria-label="<?php esc_attr_e( 'Choose or drag and drop a CSV file', 'a11y-alt-text' ); ?>"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                 viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"
                 style="color:#646970; margin-bottom:8px;">
              <path fill-rule="evenodd" d="M1.5 5.625c0-1.036.84-1.875 1.875-1.875h17.25c1.035 0 1.875.84 1.875 1.875v12.75c0 1.035-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 0 1 1.5 18.375V5.625ZM21 9.375A.375.375 0 0 0 20.625 9h-7.5a.375.375 0 0 0-.375.375v1.5c0 .207.168.375.375.375h7.5a.375.375 0 0 0 .375-.375v-1.5Zm0 3.75a.375.375 0 0 0-.375-.375h-7.5a.375.375 0 0 0-.375.375v1.5c0 .207.168.375.375.375h7.5a.375.375 0 0 0 .375-.375v-1.5Zm0 3.75a.375.375 0 0 0-.375-.375h-7.5a.375.375 0 0 0-.375.375v1.5c0 .207.168.375.375.375h7.5a.375.375 0 0 0 .375-.375v-1.5ZM10.875 18.75a.375.375 0 0 0 .375-.375v-1.5a.375.375 0 0 0-.375-.375h-7.5a.375.375 0 0 0-.375.375v1.5c0 .207.168.375.375.375h7.5ZM3.375 15h7.5a.375.375 0 0 0 .375-.375v-1.5a.375.375 0 0 0-.375-.375h-7.5a.375.375 0 0 0-.375.375v1.5c0 .207.168.375.375.375Zm0-3.75h7.5a.375.375 0 0 0 .375-.375v-1.5A.375.375 0 0 0 10.875 9h-7.5A.375.375 0 0 0 3 9.375v1.5c0 .207.168.375.375.375Z" clip-rule="evenodd"/>
            </svg>

            <!-- 파일 미선택 상태 -->
            <span id="a11y-dropzone-idle">
              <span class="button button-secondary" style="pointer-events:none;">Choose File</span>
              <span style="margin-left:8px; font-size:13px; color:#646970;">or drag and drop.</span>
            </span>

            <!-- 파일 선택 완료 상태 -->
            <span id="a11y-dropzone-loaded" style="display:none; font-size:13px; font-weight:600; color:#007a1f;">
              ✓ File added. Click Import to continue.
            </span>

            <input id="file_input" type="file" name="csv" accept=".csv" required
                   style="position:absolute; width:1px; height:1px; opacity:0; overflow:hidden;">
          </label>

          <!-- Step 3: 다국어 CSV일 때 언어 선택 (JS가 표시 제어) -->
          <div id="a11y-csv-language-selector" style="display:none; margin-top:16px;">
            <label for="a11y-csv-language" style="display:block; margin-bottom:6px; font-size:13px; font-weight:600;">
              <?php esc_html_e( 'Step 3: Select Language', 'a11y-alt-text' ); ?>
            </label>
            <p style="font-size:12px; color:#646970; margin:0 0 8px;">
              <?php esc_html_e( 'Your CSV contains alt text in multiple languages. Choose which language to import:', 'a11y-alt-text' ); ?>
            </p>
            <select id="a11y-csv-language" name="csv_language" style="max-width:300px;">
              <option value="">
                <?php esc_html_e( 'Default (alt_text column)', 'a11y-alt-text' ); ?>
              </option>
              <!-- 언어 옵션은 JS가 CSV 헤더를 파싱해 동적으로 추가 -->
            </select>
            <p style="margin-top:6px; font-size:12px; color:#646970;">
              <?php esc_html_e( 'Selecting "Default" uses the main alt_text column. This is backward compatible with older exports.', 'a11y-alt-text' ); ?>
            </p>
          </div>

          <div style="margin-top:16px;">
            <input type="submit" name="submit"
                   value="<?php esc_attr_e( 'Import', 'a11y-alt-text' ); ?>"
                   class="button button-primary">
          </div>

        </form>
      </div>
    </div>

    <!-- 리뷰 요청 배너 -->
    <div class="a11y-section-card" style="overflow:hidden; background:linear-gradient(90deg,#1e3a8a 0%,#534AB7 100%); border-color:#1e3a8a;">
      <a
        href="https://wordpress.org/support/plugin/a11y-alt-text/reviews/?filter=5"
        target="_blank"
        rel="noopener noreferrer"
        style="display:block; padding:20px 24px; text-decoration:none;"
      >
        <p style="margin:0 0 4px; font-size:16px; font-weight:600; color:#fff;">
          <?php esc_html_e( 'Do you like A11Y.so? Leave us a review!', 'a11y-alt-text' ); ?>
        </p>
        <p style="margin:0 0 8px; font-size:13px; color:rgba(255,255,255,0.8);">
          <?php esc_html_e( 'Help spread the word on WordPress.org. We\'d really appreciate it!', 'a11y-alt-text' ); ?>
        </p>
        <span style="font-size:13px; font-weight:600; color:rgba(255,255,255,0.9); text-decoration:underline;">
          <?php esc_html_e( 'Leave your review →', 'a11y-alt-text' ); ?>
        </span>
      </a>
    </div>

  </div><!-- /max-width wrapper -->
</div>

<!-- 드롭존 스타일 + 파일 선택 JS -->
<style>
.a11y-csv-dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  padding: 32px 24px;
  border: 2px dashed #c3c4c7;
  border-radius: 4px;
  background: #f6f7f7;
  cursor: pointer;
  text-align: center;
  transition: border-color 0.15s, background 0.15s;
}
.a11y-csv-dropzone:hover,
.a11y-csv-dropzone:focus {
  border-color: #534AB7;
  background: #f0eeff;
  outline: none;
}
/* 파일 선택 완료 시 초록색 테두리 */
#alttextai-csv-import[data-file-loaded="true"] .a11y-csv-dropzone {
  border-color: #007a1f;
  background: #f0fff4;
}
</style>

<script>
(function () {
  var form     = document.getElementById('alttextai-csv-import');
  var input    = document.getElementById('file_input');
  var idle     = document.getElementById('a11y-dropzone-idle');
  var loaded   = document.getElementById('a11y-dropzone-loaded');
  var dropzone = document.getElementById('a11y-csv-dropzone');

  if (!input || !form) return;

  function onFileSelected() {
    if (!input.files || !input.files.length) return;

    // UI 상태 전환
    form.setAttribute('data-file-loaded', 'true');
    if (idle)   idle.style.display   = 'none';
    if (loaded) loaded.style.display = 'inline';

    // 다국어 CSV 감지 — 헤더에 alt_text_xx 컬럼이 있으면 언어 선택 표시
    var reader = new FileReader();
    reader.onload = function (e) {
      var firstLine = e.target.result.split('\n')[0];
      var headers   = firstLine.split(',').map(function(h){ return h.trim().replace(/^"|"$/g,''); });
      var langCols  = headers.filter(function(h){ return h.startsWith('alt_text_'); });
      var selector  = document.getElementById('a11y-csv-language-selector');
      var select    = document.getElementById('a11y-csv-language');

      if (langCols.length > 0 && selector && select) {
        // 기존 옵션 초기화 후 언어 옵션 추가
        select.innerHTML = '<option value="">Default (alt_text column)</option>';
        langCols.forEach(function(col){
          var lang = col.replace('alt_text_', '');
          var opt  = document.createElement('option');
          opt.value       = lang;
          opt.textContent = lang;
          select.appendChild(opt);
        });
        selector.style.display = 'block';
      }
    };
    reader.readAsText(input.files[0]);
  }

  input.addEventListener('change', onFileSelected);

  // 드래그 앤 드롭 지원
  dropzone.addEventListener('dragover', function(e){
    e.preventDefault();
    dropzone.style.borderColor = '#534AB7';
  });
  dropzone.addEventListener('dragleave', function(){
    dropzone.style.borderColor = '';
  });
  dropzone.addEventListener('drop', function(e){
    e.preventDefault();
    dropzone.style.borderColor = '';
    if (e.dataTransfer.files.length) {
      var dt = new DataTransfer();
      dt.items.add(e.dataTransfer.files[0]);
      input.files = dt.files;
      onFileSelected();
    }
  });
})();
</script>