<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Front-end helpers for displaying Hive comments and notices.
 */
class WP_Dapp_Frontend {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('wpdapp_hive_comments', [$this, 'render_hive_comments_shortcode']);
        add_filter('the_content', [$this, 'append_notice_when_comments_closed']);
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'wpdapp-frontend',
            WPDAPP_PLUGIN_URL . 'assets/css/style.css',
            [],
            WPDAPP_VERSION
        );
    }

    /**
     * Shortcode: [wpdapp_hive_comments post_id=""]
     * Renders imported Hive comments even if WP comments are closed.
     */
    public function render_hive_comments_shortcode($atts = []) {
        $atts = shortcode_atts([
            'post_id' => 0,
            'show_reply_links' => '1',
        ], $atts, 'wpdapp_hive_comments');

        $post_id = intval($atts['post_id']);
        if (!$post_id && is_singular()) {
            $post_id = get_the_ID();
        }
        if (!$post_id) {
            return '';
        }

        $options = get_option('wpdapp_options', []);
        if (empty($options['enable_comment_sync'])) {
            return '';
        }

        $root_author   = get_post_meta($post_id, '_wpdapp_hive_author', true);
        $root_permlink = get_post_meta($post_id, '_wpdapp_hive_permlink', true);
        if (empty($root_author) || empty($root_permlink)) {
            return '';
        }

        $comments = get_comments([
            'post_id'  => $post_id,
            'status'   => 'approve',
            'meta_key' => '_wpdapp_hive_comment_key',
            'number'   => 0,
            'orderby'  => 'comment_date_gmt',
            'order'    => 'ASC',
        ]);

        if (empty($comments)) {
            return '<div class="wpdapp-hive-comments"><p class="wpdapp-muted">No Hive comments yet.</p></div>';
        }

        // Build tree by parent
        $by_parent = [];
        foreach ($comments as $c) {
            $parent = intval($c->comment_parent);
            if (!isset($by_parent[$parent])) {
                $by_parent[$parent] = [];
            }
            $by_parent[$parent][] = $c;
        }

        $show_reply_links = $atts['show_reply_links'] === '1';
        $thread_url_base = 'https://peakd.com/@' . rawurlencode($root_author) . '/' . rawurlencode($root_permlink);

        $html  = '<div class="wpdapp-hive-comments">';
        $html .= $this->render_comment_branch($by_parent, 0, $thread_url_base);
        $html .= '</div>';

        // Small footer to indicate origin
        $html .= '<div class="wpdapp-hive-comments-footer">';
        $html .= '<span class="wpdapp-muted">Replies are mirrored from Hive. ';
        if ($show_reply_links) {
            $html .= '<a href="' . esc_url($thread_url_base) . '" target="_blank" rel="noopener nofollow">Reply on Hive</a>';
        }
        $html .= '</span></div>';

        return $html;
    }

    private function render_comment_branch($by_parent, $parent_id, $thread_url_base) {
        if (empty($by_parent[$parent_id])) {
            return '';
        }
        $html = '<ol class="wpdapp-comment-list">';
        foreach ($by_parent[$parent_id] as $c) {
            $author = esc_html(get_comment_author($c));
            $date   = esc_html(get_comment_date('', $c));
            $content = apply_filters('comment_text', $c->comment_content, $c);

            $key = get_comment_meta($c->comment_ID, '_wpdapp_hive_comment_key', true);
            $reply_link = '';
            if (!empty($key)) {
                $reply_link = $thread_url_base . '#@' . rawurlencode(str_replace('/', '/', $key));
            }

            $html .= '<li class="wpdapp-comment">';
            $html .= '<div class="wpdapp-comment-body">';
            $html .= '<div class="wpdapp-comment-meta">'
                  . '<span class="wpdapp-comment-author">' . $author . '</span>'
                  . ' · '
                  . '<span class="wpdapp-comment-date">' . $date . '</span>'
                  . '</div>';
            $html .= '<div class="wpdapp-comment-content">' . $content . '</div>';
            if (!empty($reply_link)) {
                $html .= '<div class="wpdapp-comment-actions">'
                      . '<a class="wpdapp-reply-link" href="' . esc_url($reply_link) . '" target="_blank" rel="noopener nofollow">Reply on Hive</a>'
                      . '</div>';
            }
            $html .= '</div>';
            // Children
            $html .= $this->render_comment_branch($by_parent, intval($c->comment_ID), $thread_url_base);
            $html .= '</li>';
        }
        $html .= '</ol>';
        return $html;
    }

    /**
     * Append a small notice when WP comments are closed but Hive mirroring is enabled.
     */
    public function append_notice_when_comments_closed($content) {
        if (!is_singular('post')) {
            return $content;
        }
        global $post;
        if (!$post) {
            return $content;
        }
        $options = get_option('wpdapp_options', []);
        if (empty($options['enable_comment_sync'])) {
            return $content;
        }
        $root_author   = get_post_meta($post->ID, '_wpdapp_hive_author', true);
        $root_permlink = get_post_meta($post->ID, '_wpdapp_hive_permlink', true);
        if (empty($root_author) || empty($root_permlink)) {
            return $content;
        }
        if (comments_open($post->ID)) {
            return $content;
        }

        $thread_url = 'https://peakd.com/@' . rawurlencode($root_author) . '/' . rawurlencode($root_permlink);
        $notice  = '<div class="wpdapp-hive-comments-notice">';
        $notice .= '<span class="wpdapp-muted">WordPress comments are disabled. This post mirrors replies from Hive. '
                . '<a href="' . esc_url($thread_url) . '" target="_blank" rel="noopener nofollow">View and reply on Hive</a>.'
                . '</span>';
        $notice .= '</div>';
        return $content . $notice;
    }
}


