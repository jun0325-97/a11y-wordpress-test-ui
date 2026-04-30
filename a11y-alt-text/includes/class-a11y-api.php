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
        $img_hash  = 'mock_hash_' . md5( $filename );
        $mock_type = get_option( 'a11y_mock_response_type', 'graphic' );

        // 크레딧 없음 시나리오 — attachment.php가 'insufficient_credits' 문자열을 인식해 에러 처리
        if ( $mock_type === 'no_credits' ) {
            return 'insufficient_credits';
        }

        // 간소화 모드 — 설정 선택 또는 실제 생성 모드가 simple일 때
        $is_simple = ! empty( $options['is_simple_mode'] );
        if ( $mock_type === 'simple' || ( $mock_type === 'graphic' && $is_simple ) ) {
            return array(
                'alt_text'    => sprintf('[목업-간소화] %s 이미지 설명', $filename),
                'description' => null,
                'img_type'    => '간소화',
                'lang_code'   => 'ko',
                'llm_token'   => array(800),
                'mode'        => '간소화',
                'model'       => 'gpt-5.1',
                'creidt'      => 1, // 실제 API 서버도 오타 그대로 — API 수정 시 함께 변경
                'message'     => 'AI 생성이 완료되었습니다.',
                'img_hash'    => $img_hash,
            );
        }

        // 복합 이미지 (alt + aria-describedby)
        if ( $mock_type === 'complex' ) {
            return array(
                'alt_text'    => sprintf('[목업-복합형] %s 행사 안내문', $filename),
                'description' => '<h1>Mock Event Title</h1><p>Date: 2026-01-01</p><h2>Purpose</h2><p>This is a mock long description for a complex image. Screen readers will read this via aria-describedby.</p>',
                'img_type'    => '복합형',
                'lang_code'   => 'ko',
                'llm_token'   => array(4446),
                'mode'        => '웹접근성',
                'model'       => 'gpt-5.1',
                'creidt'      => 2, // 실제 API 서버도 오타 그대로 — API 수정 시 함께 변경
                'message'     => 'AI 생성이 완료되었습니다.',
                'img_hash'    => $img_hash,
            );
        }

        // 장식 이미지 (alt 빈값)
        if ( $mock_type === 'decorative' ) {
            return array(
                'alt_text'    => '',
                'description' => null,
                'img_type'    => '장식형',
                'lang_code'   => 'ko',
                'llm_token'   => array(500),
                'mode'        => '웹접근성',
                'model'       => 'gpt-5.1',
                'creidt'      => 1, // 실제 API 서버도 오타 그대로 — API 수정 시 함께 변경
                'message'     => 'AI 생성이 완료되었습니다.',
                'img_hash'    => $img_hash,
            );
        }

        // 기본값 — 그래픽형 (alt만)
        return array(
            'alt_text'    => sprintf('[목업-일반형] %s 이미지 설명', $filename),
            'description' => null,
            'img_type'    => '그래픽형',
            'lang_code'   => 'ko',
            'llm_token'   => array(3398),
            'mode'        => '웹접근성',
            'model'       => 'gpt-5.1',
            'creidt'      => 1, // 실제 API 서버도 오타 그대로 — API 수정 시 함께 변경
            'message'     => 'AI 생성이 완료되었습니다.',
            'img_hash'    => $img_hash,
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
            $no_credits = ( get_option( 'a11y_mock_response_type' ) === 'no_credits' );
            return array(
                'tw_plan'    => 'customA',               // 실제 API: data.tw_plan
                'credit'     => $no_credits ? 0 : 9999, // 실제 API: data.credit (잔여 크레딧)
                'whitelabel' => false,                   // 실제 API 필드명 미확인 — 개발팀 확인 필요
            );
        }
        return false;
    }
}


// 실제 API 연결 시 사용할 Request body 구조 (메모)
// 아래는 워크스페이스 내부 API 기준 — 외부 공개 API 엔드포인트 및 파라미터 개발팀 확인 필요
// POST https://a11y.so/api/team_workspace/ai/request_one
//
// [웹접근성 모드 — AI 자동 분류]
// {
//   "sir_id":          (string) 워크스페이스 내부 ID — 외부 API에서 필요한지 미확인,
//   "tw_id":           (string) 워크스페이스 내부 ID — 외부 API에서 필요한지 미확인,
//   "si_id":           (string) 워크스페이스 내부 ID — 외부 API에서 필요한지 미확인,
//   "twu_id":          (string) 워크스페이스 내부 ID — 외부 API에서 필요한지 미확인,
//   "is_simple_mode":  false,
//   "is_use_img_type": false   ← 워드프레스는 항상 false 고정 / img_type 키 미포함
// }
//
// [간소화 모드]
// {
//   ...(동일 ID 필드),
//   "is_simple_mode":  true,
//   "is_use_img_type": false,
//   "img_type":        "간소화"   ← 필수 여부 개발팀 확인 필요
// }