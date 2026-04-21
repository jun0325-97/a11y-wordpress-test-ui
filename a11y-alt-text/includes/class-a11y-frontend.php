<?php
class A11Y_Frontend {

    public function __construct() {
        if (!is_admin()) {
            add_filter('wp_get_attachment_image_attributes', array($this, 'add_aria_describedby'), 10, 3);
            add_filter('the_content', array($this, 'inject_descriptions'), 20);
        }
    }

    public function add_aria_describedby($attr, $attachment, $size) {
        $description = get_post_meta($attachment->ID, 'a11y_long_desc', true);
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
        $img_tag = $matches[0];
        $attachment_id = intval($matches[1]);
        $description = get_post_meta($attachment_id, 'a11y_long_desc', true);

        if (empty($description)) return $img_tag;

        $desc_id = 'a11y-desc-' . $attachment_id;

        if (strpos($img_tag, 'aria-describedby') === false) {
            $img_tag = str_replace('<img ', '<img aria-describedby="' . esc_attr($desc_id) . '" ', $img_tag);
        }

        $sr_only_div = sprintf(
            '<div id="%s" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;">%s</div>',
            esc_attr($desc_id),
            esc_html($description)
        );

        return $img_tag . $sr_only_div;
    }
}

new A11Y_Frontend();