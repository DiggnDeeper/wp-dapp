<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WP-Dapp Comment Synchronization
 *
 * Imports Hive replies into WordPress comments.
 */
class WP_Dapp_Comment_Sync {

	/** @var WP_Dapp_Hive_API */
	private $hive_api;

	public function __construct() {
		$this->hive_api = new WP_Dapp_Hive_API();

		// Provide a 15-minute schedule and hook our cron task
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );
		add_action( 'wpdapp_sync_hive_comments_event', array( $this, 'cron_sync_all' ) );
	}

	/**
	 * Register a custom 15-minute cron schedule.
	 */
	public function add_cron_schedules( $schedules ) {
		if ( ! isset( $schedules['wpdapp_every_15_minutes'] ) ) {
			$schedules['wpdapp_every_15_minutes'] = array(
				'interval' => 15 * 60,
				'display'  => __( 'Every 15 Minutes (WP-Dapp)', 'wp-dapp' ),
			);
		}
		return $schedules;
	}

	/**
	 * Cron callback to sync comments for recent Hive-published posts.
	 */
	public function cron_sync_all() {
		$options = get_option( 'wpdapp_options', array() );
		if ( empty( $options['enable_comment_sync'] ) ) {
			return; // Disabled via settings
		}

		$auto_approve = ! empty( $options['auto_approve_comments'] );

		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_wpdapp_hive_author',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => '_wpdapp_hive_permlink',
					'compare' => 'EXISTS',
				),
			),
		);

		$query = new WP_Query( $args );
		if ( ! $query->have_posts() ) {
			return;
		}

		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			$this->sync_post_comments( $post_id, $auto_approve );
		}

		wp_reset_postdata();
	}

	/**
	 * Sync Hive replies into WordPress comments for a specific post.
	 *
	 * @param int  $post_id
	 * @param bool $auto_approve
	 * @return array{imported:int,skipped:int,total_hive:int}|WP_Error
	 */
	public function sync_post_comments( $post_id, $auto_approve = false ) {
		$post_id = intval( $post_id );
		if ( ! $post_id ) {
			return new WP_Error( 'invalid_post', 'Invalid post ID' );
		}

		$hive_author   = get_post_meta( $post_id, '_wpdapp_hive_author', true );
		$hive_permlink = get_post_meta( $post_id, '_wpdapp_hive_permlink', true );
		if ( empty( $hive_author ) || empty( $hive_permlink ) ) {
			return new WP_Error( 'not_published_to_hive', 'This post has not been published to Hive' );
		}

		$replies = $this->hive_api->get_all_replies( $hive_author, $hive_permlink, 0, 2000 );
		if ( is_wp_error( $replies ) ) {
			return $replies;
		}

		$imported       = 0;
		$skipped        = 0;
		$map_hive_to_wp = array();

		// Index existing imported comments to avoid duplicates
		$existing_index = array();
		$existing       = get_comments(
			array(
				'post_id'  => $post_id,
				'status'   => 'all',
				'meta_key' => '_wpdapp_hive_comment_key',
				'number'   => 0,
			)
		);
		if ( ! empty( $existing ) ) {
			foreach ( $existing as $c ) {
				$key = get_comment_meta( $c->comment_ID, '_wpdapp_hive_comment_key', true );
				if ( ! empty( $key ) ) {
					$existing_index[ strtolower( $key ) ] = intval( $c->comment_ID );
				}
			}
		}

		foreach ( $replies as $reply ) {
			$author   = isset( $reply['author'] ) ? $reply['author'] : '';
			$permlink = isset( $reply['permlink'] ) ? $reply['permlink'] : '';
			if ( $author === '' || $permlink === '' ) {
				++$skipped;
				continue;
			}

			$key = strtolower( $author . '/' . $permlink );
			if ( isset( $existing_index[ $key ] ) ) {
				++$skipped;
				$map_hive_to_wp[ $key ] = $existing_index[ $key ];
				continue;
			}

			// Determine parent mapping if present
			$parent_id  = 0;
			$parent_key = '';
			if ( ! empty( $reply['parent_author'] ) && ! empty( $reply['parent_permlink'] ) ) {
				$parent_key = strtolower( $reply['parent_author'] . '/' . $reply['parent_permlink'] );
				if ( isset( $existing_index[ $parent_key ] ) ) {
					$parent_id = $existing_index[ $parent_key ];
				} elseif ( isset( $map_hive_to_wp[ $parent_key ] ) ) {
					$parent_id = $map_hive_to_wp[ $parent_key ];
				}
			}

			$comment_content  = isset( $reply['body'] ) ? wp_kses_post( $reply['body'] ) : '';
			$comment_date_gmt = isset( $reply['created'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $reply['created'] ) ) : gmdate( 'Y-m-d H:i:s' );

			$comment_data = array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => $author,
				'comment_author_email' => '',
				'comment_author_url'   => 'https://hive.blog/@' . $author,
				'comment_content'      => $comment_content,
				'comment_type'         => '',
				'comment_parent'       => $parent_id,
				'user_id'              => 0,
				'comment_date_gmt'     => $comment_date_gmt,
				'comment_approved'     => $auto_approve ? 1 : 0,
			);

			$new_id = wp_insert_comment( $comment_data );
			if ( is_wp_error( $new_id ) || ! $new_id ) {
				++$skipped;
				continue;
			}

			add_comment_meta( $new_id, '_wpdapp_hive_comment_key', $key, true );
			if ( ! empty( $parent_key ) ) {
				add_comment_meta( $new_id, '_wpdapp_hive_parent_key', $parent_key, true );
			}

			++$imported;
			$map_hive_to_wp[ $key ] = intval( $new_id );
		}

		return array(
			'imported'   => $imported,
			'skipped'    => $skipped,
			'total_hive' => is_array( $replies ) ? count( $replies ) : 0,
		);
	}
}
