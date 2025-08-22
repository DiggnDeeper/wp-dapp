<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WP_Dapp_Publish_Handler {

	protected $hive_api;

	public function __construct() {
		// Initialize the Hive API wrapper
		$this->hive_api = new WP_Dapp_Hive_API();

		// Register hooks for post meta box - DISABLED to prevent duplicate meta boxes
		// add_action('add_meta_boxes', [$this, 'add_hive_publish_meta_box']);

		// Auto-publish workflow removed: publishing requires explicit Keychain confirmation.
	}

	// Auto-publish handler removed.

	/**
	 * Add Hive publish meta box to post editor
	 * DISABLED: This functionality is now handled by the WP_Dapp_Post_Meta class
	 */
	public function add_hive_publish_meta_box() {
		// Disabled to prevent duplicate meta boxes
		/*
		add_meta_box(
			'wpdapp_hive_publish_box',
			'Publish to Hive',
			[$this, 'render_hive_publish_meta_box'],
			'post',
			'side',
			'default'
		);
		*/
	}

	/**
	 * Render the Hive publish meta box
	 *
	 * @param WP_Post $post The post object
	 */
	public function render_hive_publish_meta_box( $post ) {
		// Get Hive account
		$options      = get_option( 'wpdapp_options', array() );
		$hive_account = ! empty( $options['hive_account'] ) ? $options['hive_account'] : '';

		// Check if post is published to Hive
		$hive_published = get_post_meta( $post->ID, '_wpdapp_hive_published', true );
		$hive_permlink  = get_post_meta( $post->ID, '_wpdapp_hive_permlink', true );

		if ( $hive_published && $hive_permlink ) {
			// Show published status
			echo '<div class="wpdapp-published-info">';
			echo '<p><strong style="color: green;">✓ Published to Hive</strong></p>';
			echo '<p><a href="https://hive.blog/@' . esc_attr( $hive_account ) . '/' . esc_attr( $hive_permlink ) . '" target="_blank">View on Hive</a></p>';
			echo '</div>';
			return;
		}

		// If Hive account is not set, show warning
		if ( empty( $hive_account ) ) {
			echo '<p style="color: red;">Hive account not configured. Please configure it in the <a href="' . admin_url( 'options-general.php?page=wpdapp-settings' ) . '">WP-Dapp Settings</a>.</p>';
			return;
		}

		// Show publish button only for published posts
		if ( $post->post_status !== 'publish' ) {
			echo '<p>Publish this post in WordPress first before publishing to Hive.</p>';
			return;
		}

		// Show Keychain publish button
		echo '<div id="wpdapp-keychain-status"></div>';
		echo '<button type="button" id="wpdapp-publish-button" class="button button-primary">Publish to Hive with Keychain</button>';
		echo '<div id="wpdapp-publish-status"></div>';

		// Add nonce and meta data for JavaScript
		wp_nonce_field( 'wpdapp_publish', 'wpdapp_publish_nonce' );

		// Add inline script with data for the Keychain publish script
		echo '<script>';
		echo 'var wpdapp_publish = {';
		echo 'ajax_url: "' . admin_url( 'admin-ajax.php' ) . '",';
		echo 'nonce: "' . wp_create_nonce( 'wpdapp_publish' ) . '",';
		echo 'post_id: ' . $post->ID . ',';
		echo 'hive_account: "' . esc_js( $hive_account ) . '"';
		echo '};';
		echo '</script>';
	}

	/**
	 * Format post content for Hive.
	 *
	 * @param string $content The post content.
	 * @param int    $post_id The post ID.
	 * @return string Formatted content.
	 */
	public function format_content_for_hive( $content, $post_id ) {
		// Strip shortcodes
		$content = strip_shortcodes( $content );

		// Strip Gutenberg block comments
		// Original regex was limited - this improved version handles all variations:
		// - Works with or without whitespace
		// - Handles multiline comments
		// - Catches both opening and closing tags in one pass
		// - Uses the s (PCRE_DOTALL) modifier to make dot match newlines
		$content = preg_replace( '/<!--\s*wp:.*?(?:-->|\/-->)/s', '', $content ); // Opening tags
		$content = preg_replace( '/<!--\s*\/wp:.*?(?:-->|\/-->)/s', '', $content ); // Closing tags

		// Clean WordPress-specific HTML elements and classes

		// 1. First pass - Remove WordPress-specific classes, IDs, and styles
		$content = preg_replace( '/\s+class=(["\'])[^"\']*\1/i', '', $content );
		$content = preg_replace( '/\s+id=(["\'])[^"\']*\1/i', '', $content );
		$content = preg_replace( '/\s+style=(["\'])[^"\']*\1/i', '', $content );

		// 2. Remove data attributes (WordPress blocks use these extensively)
		$content = preg_replace( '/\s+data-[a-z0-9_\-]+=(["\']).+?\1/i', '', $content );

		// 3. Remove empty div containers (often left after removing classes)
		$content = preg_replace( '/<div[^>]*>\s*<\/div>/is', '', $content );

		// 4. Replace WordPress wrapper divs with simpler structure
		// Look for div patterns that are likely WordPress wrappers
		$content = preg_replace( '/<div[^>]*wp-block[^>]*>/i', '', $content );

		// 5. Simplify HTML structure - replace unnecessary div nesting with basic elements
		$content = preg_replace( '/<div[^>]*>\s*<p/is', '<p', $content );
		$content = preg_replace( '/<\/p>\s*<\/div>/is', '</p>', $content );

		// 6. Clean up remaining unnecessary divs
		// First, count all opening and closing divs
		$open_divs  = substr_count( strtolower( $content ), '<div' );
		$close_divs = substr_count( strtolower( $content ), '</div>' );

		// If they're balanced and we still have divs, let's convert outermost to semantic content
		if ( $open_divs == $close_divs && $open_divs > 0 ) {
			// Replace first div and last /div
			$content = preg_replace( '/<div[^>]*>/i', '', $content, 1 );
			$content = preg_replace( '/<\/div>/i', '', $content, 1 );
		}

		// 7. Remove any empty paragraphs
		$content = preg_replace( '/<p>\s*<\/p>/i', '', $content );

		// 8. Final cleanup - remove any remaining WordPress-specific attributes
		$content = preg_replace( '/\s+wp-[a-z0-9_\-]+=["\'][^"\']*["\']/i', '', $content );

		// 9. Use WordPress's wp_kses function to filter HTML to only allowed elements
		// This helps create clean, minimal HTML suitable for Hive
		$allowed_html = array(
			'a'          => array(
				'href'   => array(),
				'title'  => array(),
				'rel'    => array(),
				'target' => array(),
			),
			'p'          => array(),
			'br'         => array(),
			'em'         => array(),
			'strong'     => array(),
			'ul'         => array(),
			'ol'         => array(),
			'li'         => array(),
			'h1'         => array(),
			'h2'         => array(),
			'h3'         => array(),
			'h4'         => array(),
			'h5'         => array(),
			'h6'         => array(),
			'blockquote' => array(),
			'img'        => array(
				'src'   => array(),
				'alt'   => array(),
				'title' => array(),
			),
			'hr'         => array(),
		);

		$content = wp_kses( $content, $allowed_html );

		// Process images if needed
		$content = $this->process_images( $content );

		// Add a footer with attribution link
		$permalink = get_permalink( $post_id );
		$site_name = get_bloginfo( 'name' );

		$footer  = "\n\n---\n\n";
		$footer .= "Originally published on [$site_name]($permalink)";

		return $content . $footer;
	}

	/**
	 * Process images in content to ensure they work on Hive.
	 *
	 * @param string $content The post content.
	 * @return string Content with processed images.
	 */
	private function process_images( $content ) {
		// For now, just return the content as is
		// In the future, could process images to ensure they work well on Hive
		return $content;
	}
}
