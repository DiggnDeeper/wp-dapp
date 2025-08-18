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
        add_filter('comments_open', [$this, 'maybe_force_comments_closed'], 20, 2);
        add_filter('comments_template', [$this, 'maybe_replace_comments_template'], 20);
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'wpdapp-frontend',
            WPDAPP_PLUGIN_URL . 'assets/css/style.css',
            [],
            WPDAPP_VERSION
        );
        wp_enqueue_script(
            'wpdapp-hive-comment',
            WPDAPP_PLUGIN_URL . 'assets/js/hive-comment.js',
            ['jquery'],
            WPDAPP_VERSION,
            true
        );
        // Localize data for AJAX
        $post_id = is_singular() ? get_the_ID() : 0;
        wp_localize_script('wpdapp-hive-comment', 'wpdapp_frontend', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpdapp_frontend_sync'),
            'post_id' => $post_id,
            'i18n' => [
                'replyWithKeychain' => __('Reply with Keychain', 'wp-dapp'),
                'replyToPostWithKeychain' => __('Reply to Post with Keychain', 'wp-dapp'),
                'connectWithKeychain' => __('Connect with Keychain', 'wp-dapp'),
                'connectedAs' => __('Connected as:', 'wp-dapp'),
                'yourReplyPlaceholder' => __('Your reply...', 'wp-dapp'),
                'submit' => __('Submit', 'wp-dapp'),
                'cancel' => __('Cancel', 'wp-dapp'),
                'posting' => __('Posting...', 'wp-dapp'),
                'keychainNotDetected' => __('Hive Keychain not detected. Please install the extension.', 'wp-dapp'),
                'verifyPrompt' => __('Enter your Hive username to verify:', 'wp-dapp'),
                'verifyFailed' => __('Verification failed:', 'wp-dapp'),
                'keychainConnectFailed' => __('Keychain connection failed:', 'wp-dapp'),
                'pleaseEnterReply' => __('Please enter a reply.', 'wp-dapp'),
                'replyMinLength' => __('Reply must be at least 3 characters.', 'wp-dapp'),
                'pleaseConnectFirst' => __('Please connect with Keychain first.', 'wp-dapp'),
                'postedSyncing' => __('Reply posted successfully! Syncing...', 'wp-dapp'),
                'replyPostedSynced' => __('Reply posted and synced!', 'wp-dapp'),
                'syncFailedPrefix' => __('Posted to Hive, but sync failed:', 'wp-dapp'),
                'syncErrorOccurred' => __('Posted to Hive, but sync error occurred.', 'wp-dapp'),
                'syncedRefreshFailed' => __('Synced, but failed to refresh comments.', 'wp-dapp'),
                'syncedRefreshError' => __('Synced, but refresh failed.', 'wp-dapp'),
            ]
        ]);
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

        $status = !empty($options['hive_only_mode']) ? 'all' : 'approve';
        $comments = get_comments([
            'post_id'  => $post_id,
            'status'   => $status,
            'meta_key' => '_wpdapp_hive_comment_key',
            'number'   => 0,
            'orderby'  => 'comment_date_gmt',
            'order'    => 'ASC',
        ]);

        if (empty($comments)) {
            return '<div class="wpdapp-hive-comments" role="region" aria-label="' . esc_attr__('Hive comments', 'wp-dapp') . '"><p class="wpdapp-muted">' . esc_html__('No Hive comments yet.', 'wp-dapp') . '</p></div>';
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
        // Determine frontend base URL
        $frontend = !empty($options['hive_frontend']) ? $options['hive_frontend'] : 'peakd';
        switch ($frontend) {
            case 'hive.blog':
                $base = 'https://hive.blog/@';
                break;
            case 'ecency':
                $base = 'https://ecency.com/@';
                break;
            case 'peakd':
            default:
                $base = 'https://peakd.com/@';
                break;
        }
        $thread_url_base = $base . rawurlencode($root_author) . '/' . rawurlencode($root_permlink);

        $html  = '<div class="wpdapp-hive-comments" role="region" aria-label="' . esc_attr__('Hive comments', 'wp-dapp') . '" data-root-author="' . esc_attr($root_author) . '" data-root-permlink="' . esc_attr($root_permlink) . '">';
        // Determine max depth from settings (default 4)
        $max_depth = isset($options['hive_max_thread_depth']) ? intval($options['hive_max_thread_depth']) : 4;
        if ($max_depth < 1) { $max_depth = 1; }
        if ($max_depth > 10) { $max_depth = 10; }
        $html .= $this->render_comment_branch($by_parent, 0, $thread_url_base, 0, $max_depth);
        $html .= '</div>';

        // Consolidated footer notice
        $html .= '<div class="wpdapp-hive-comments-footer">';
        $html .= '<span class="wpdapp-muted">' . esc_html__('These are mirrored from Hive', 'wp-dapp');
        if ($show_reply_links) {
            $html .= ' · <a href="' . esc_url($thread_url_base) . '" target="_blank" rel="noopener nofollow">' . esc_html__('View thread / reply on Hive', 'wp-dapp') . '</a>';
        }
        $html .= '</span></div>';

        return $html;
    }

    private function render_comment_branch($by_parent, $parent_id, $thread_url_base, $depth = 0, $max_depth = 4) {
        if (empty($by_parent[$parent_id])) {
            return '';
        }
        $max_depth = 4; // Limit deep threading
        $html = '<ol class="wpdapp-comment-list" data-depth="' . intval($depth) . '">';
        foreach ($by_parent[$parent_id] as $c) {
            $author = esc_html(get_comment_author($c));
            $date   = esc_html(get_comment_date('', $c));
            $content = apply_filters('comment_text', $c->comment_content, $c);

            $key = get_comment_meta($c->comment_ID, '_wpdapp_hive_comment_key', true);
            $reply_link = '';
            if (!empty($key)) {
                $reply_link = $thread_url_base . '#@' . rawurlencode(str_replace('/', '/', $key));
            }

            $html .= '<li class="wpdapp-comment" data-hive-key="' . esc_attr($key) . '">';
            $html .= '<div class="wpdapp-comment-body">';
            $html .= '<div class="wpdapp-comment-meta">'
                  . '<span class="wpdapp-comment-author">' . $author . '</span>'
                  . ' · '
                  . '<span class="wpdapp-comment-date">' . $date . '</span>'
                  . '</div>';
            $html .= '<div class="wpdapp-comment-content">' . $content . '</div>';
            if (!empty($reply_link)) {
                $html .= '<div class="wpdapp-comment-actions">'
                      . '<a class="wpdapp-reply-link" href="' . esc_url($reply_link) . '" target="_blank" rel="noopener nofollow">' . esc_html__('Reply on Hive', 'wp-dapp') . '</a>'
                      . '</div>';
            }
            $html .= '</div>';
            // Children
            if ($depth + 1 < $max_depth) {
                $html .= $this->render_comment_branch($by_parent, intval($c->comment_ID), $thread_url_base, $depth + 1, $max_depth);
            } else if (!empty($by_parent[intval($c->comment_ID)])) {
                // Reached max depth but there are children: show a link to view the rest on Hive
                $html .= '<div class="wpdapp-show-more"><a href="' . esc_url($thread_url_base) . '" target="_blank" rel="noopener nofollow">' . esc_html__('View more replies on Hive', 'wp-dapp') . '</a></div>';
            }
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

        // Respect chosen frontend for notice URL
        $frontend = !empty($options['hive_frontend']) ? $options['hive_frontend'] : 'peakd';
        switch ($frontend) {
            case 'hive.blog':
                $base = 'https://hive.blog/@';
                break;
            case 'ecency':
                $base = 'https://ecency.com/@';
                break;
            case 'peakd':
            default:
                $base = 'https://peakd.com/@';
                break;
        }
        $thread_url = $base . rawurlencode($root_author) . '/' . rawurlencode($root_permlink);
        $notice  = '<div class="wpdapp-hive-comments-notice">';
        $notice .= '<span class="wpdapp-muted">' . esc_html__('WordPress comments are disabled. This post mirrors replies from Hive.', 'wp-dapp') . ' '
                . '<a href="' . esc_url($thread_url) . '" target="_blank" rel="noopener nofollow">' . esc_html__('View and reply on Hive', 'wp-dapp') . '</a>.'
                . '</span>';
        $notice .= '</div>';
        return $content . $notice;
    }

    /**
     * If Hive-only mode is enabled, force comments_open() to false on singular posts.
     */
    public function maybe_force_comments_closed($open, $post_id) {
        $options = get_option('wpdapp_options', []);
        if (empty($options['hive_only_mode'])) {
            return $open;
        }
        if (is_admin()) {
            return $open;
        }
        $post = get_post($post_id);
        if ($post && $post->post_type === 'post') {
            return false;
        }
        return $open;
    }

    /**
     * If Hive-only mode is enabled on single posts, append our Hive comments block after the content area
     * by injecting it through the comments template filter when comments are closed.
     */
    public function maybe_replace_comments_template($template) {
        $options = get_option('wpdapp_options', []);
        if (empty($options['hive_only_mode'])) {
            return $template;
        }
        if (!is_singular('post')) {
            return $template;
        }
        global $post;
        if (!$post) {
            return $template;
        }
        // Always output our Hive comments block under the content area
        add_filter('the_content', function($content) use ($post) {
            $shortcode = '[wpdapp_hive_comments post_id="' . intval($post->ID) . '"]';
            return $content . do_shortcode($shortcode);
        }, 99);
        return $template;
    }
}


