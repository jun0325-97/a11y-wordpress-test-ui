<?php
class A11Y_Frontend {

    public function __construct() {
        if (!is_admin()) {
            add_filter('wp_get_attachment_image_attributes', array($this, 'add_aria_describedby'), 10, 3);
            add_filter('the_content', array($this, 'inject_descriptions'), 20);
        }
    }

    public function add_aria_describedby($attr, $attachment, $size) {
        $description = get_post_meta($attachment->ID, 'a11y_description', true);
        if (!empty($description)) {
            $attr['aria-describedby'] = 'a11y-desc-' . $attachment->ID;
        }
        return $attr;
    }

    public function inject_descriptions($content) {
        if (empty($content)) return $content;
        return preg_replace_callback(
            '/<img[^>]+class="[^"]*wp-image-(\d+)[^"]*"[^>]*>/i',
            array($this, 'process_image_tag'),
            $content
        );
    }

    private function process_image_tag($matches) {
        $img_tag       = $matches[0];
        $attachment_id = intval($matches[1]);
        $description   = get_post_meta($attachment_id, 'a11y_description', true);

        if (empty($description)) return $img_tag;

        $desc_id = 'a11y-desc-' . $attachment_id;

        if (strpos($img_tag, 'aria-describedby') === false) {
            $img_tag = str_replace(
                '<img ',
                '<img aria-describedby="' . esc_attr($desc_id) . '" ',
                $img_tag
            );
        }

        // 스크린리더에 의미있는 태그 허용 + 헤딩 구조 보존
        $allowed_tags = array(
            'h1'     => array(),
            'h2'     => array(),
            'h3'     => array(),
            'h4'     => array(),
            'h5'     => array(),
            'h6'     => array(),
            'p'      => array(),
            'br'     => array(),
            'ul'     => array(),
            'ol'     => array(),
            'li'     => array(),
            'strong' => array(),
            'em'     => array(),
            'span'   => array(),
            'table'  => array(),
            'thead'  => array(),
            'tbody'  => array(),
            'tr'     => array(),
            'th'     => array( 'scope' => array() ),
            'td'     => array(),
            'caption'=> array(),
        );

        $sr_only_div = sprintf(
            '<div id="%s" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;">%s</div>',
            esc_attr($desc_id),
            wp_kses($description, $allowed_tags)
        );

        return $img_tag . $sr_only_div;
    }
}

new A11Y_Frontend();