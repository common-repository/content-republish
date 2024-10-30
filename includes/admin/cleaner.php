<?php

namespace Yipresser\ContentRepublish\Admin;

use Yipresser\ContentRepublish\Utils;

class Cleaner {

	public function __construct() {
		add_action( 'init', [$this, 'schedule_content_republish_cleaner'] );

		add_action( 'contentrepublish_cleanup', [$this, 'cleanup_republished_content'] );
	}

	/**
	 * @return void
	 */
	public function schedule_content_republish_cleaner(): void {
		if ( ! as_has_scheduled_action( 'contentrepublish_cleanup' ) ) {
			as_schedule_recurring_action( time(), DAY_IN_SECONDS, 'contentrepublish_cleanup', [], 'content-republish', true );
		}
	}

	/**
	 * @return void
	 */
	public function cleanup_republished_content() {
		$args = [
			'post_status' => 'republish-trash',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'post_type' => 'any',
		];
		$posts = get_posts( $args );
		if ( ! empty( $posts ) ) {
			$settings = Utils::get_settings_option();
			$force_delete = false;
			if ( 'delete' === $settings['after-republish-action'] ) {
				$force_delete = true;
			}
			foreach ( $posts as $post_id ) {
				wp_delete_post( $post_id, $force_delete );
			}
		}
	}
}