<?php
class A11Y_API {

    private $api_key;

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    public function create_image($attachment_id, $attachment_url, $options = array(), &$response_code = null) {
        if (empty($this->api_key)) {
            $response_code = 401;
            return false;
        }
        if ($this->is_mock_mode()) {
            return $this->mock_response($attachment_id, $options);
        }
        return false;
    }

    private function is_mock_mode() {
        return true;
    }

    private function mock_response($attachment_id, $options) {
        $filename  = basename(get_attached_file($attachment_id));
        $mock_type = get_option('a11y_mock_response_type', 'graphic');

        // 간소화 모드 — 유형 분류 없음, alt만
        if ( $mock_type === 'simple' ) {
            return array(
                'alt_text'    => sprintf('[목업-간소화] %s 이미지 설명', $filename),
                'description' => null,
                'img_type'    => null,
                'lang_code'   => 'ko',
                'llm_token'   => array(800),
                'mode'        => '간소화',
                'model'       => 'gpt-5.1',
                'creidt'      => 1,
            );
        }

        // 웹접근성 지침 모드 — 복합 이미지 (alt + aria-describedby)
        if ( $mock_type === 'complex' ) {
            return array(
                'alt_text'    => sprintf('[목업-복합형] %s 행사 안내문', $filename),
                'description' => '<h1>Mock Event Title</h1><p>Date: 2026-01-01</p><h2>Purpose</h2><p>This is a mock long description for a complex image. Screen readers will read this via aria-describedby.</p>',
                'img_type'    => '복합형',
                'lang_code'   => 'ko',
                'llm_token'   => array(4446),
                'mode'        => '웹접근성',
                'model'       => 'gpt-5.1',
                'creidt'      => 2,
            );
        }

        // 웹접근성 지침 모드 — 장식 이미지 (alt 빈값)
        if ( $mock_type === 'decorative' ) {
            return array(
                'alt_text'    => '',
                'description' => null,
                'img_type'    => '장식형',
                'lang_code'   => 'ko',
                'llm_token'   => array(500),
                'mode'        => '웹접근성',
                'model'       => 'gpt-5.1',
                'creidt'      => 1,
            );
        }

        // 웹접근성 지침 모드 — 일반 이미지 (alt만)
        return array(
            'alt_text'    => sprintf('[목업-일반형] %s 이미지 설명', $filename),
            'description' => null,
            'img_type'    => '그래픽형',
            'lang_code'   => 'ko',
            'llm_token'   => array(3398),
            'mode'        => '웹접근성',
            'model'       => 'gpt-5.1',
            'creidt'      => 1,
        );
    }

    public function get_token_balance() {
        if ($this->is_mock_mode()) {
            return array(
                'remaining' => 95,   // 실제 응답의 user count 기준
                'used'      => 5,
                'plan'      => 'mock_trial',
            );
        }
        return false;
    }

    public function get_account() {
        if ($this->is_mock_mode()) {
            return array(
                'plan'      => 'mock_trial',
                'available' => 9999,
                'quota'     => 9999,
                'whitelabel' => false,
            );
        }
        return false;
    }
}


// 실제 API 연결 시 사용할 Request body 구조 (메모)
// POST https://a11y.so/api/team_workspace/ai/request_one
// {
//   "sir_id":         (string) site image record id,
//   "tw_id":          (string) team workspace id,
//   "si_id":          (string) site image id,
//   "twfu_id":        (string) team workspace file upload id,
//   "is_simple_mode": (bool)   true = 간소화, false = 웹접근성 지침,
//   "is_use_img_type":(bool)   img_type 분류 사용 여부,
//   "img_type":       (string) 미분류 / 그래픽형 / 복합형 등
// }