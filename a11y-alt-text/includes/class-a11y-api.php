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
        $filename = basename(get_attached_file($attachment_id));
        $format = $options['format'] ?? get_option('a11y_format', 'auto');

        $response = array(
            'alt_text'    => sprintf('[A11Y 목업] %s 이미지에 대한 대체 텍스트', $filename),
            'asset_id'    => 'mock-asset-' . $attachment_id,  // ← 이것도 필요
            'type'        => 'simple',
            'tokens_used' => 80,
        );

        if ($format === 'complex' || $format === 'auto') {
            $response['alt_text']     = sprintf('[A11Y 목업] %s - 요약 설명', $filename);
            $response['description']  = sprintf(
                '[A11Y 목업] %s 이미지의 상세 설명입니다. 실제 API 연결 시 AI가 이미지를 분석하여 스크린리더 사용자를 위한 상세한 설명을 생성합니다.',
                $filename
            );
            $response['type']         = 'complex';
            $response['tokens_used']  = 150;
        }

        return $response;
    }

    public function get_token_balance() {
        if ($this->is_mock_mode()) {
            return array('remaining' => 9999, 'used' => 1, 'plan' => 'mock_trial');
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