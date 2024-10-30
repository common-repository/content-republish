<?php

namespace Yipresser\ContentRepublish\Admin;

use Yipresser\ContentRepublish\Utils;

/**
 * Class to handle Republish post status
 */
class PostStatus {

	/**
	 * Start the engine running
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'init', [ $this, 'register_post_status' ] );
		add_filter( 'wp_insert_post_data', [ $this, 'prevent_publish' ], 100, 2 );
		add_filter( 'gettext', [ $this, 'change_publish_button_text'], 100, 3 );
		add_filter( 'gettext_with_context', [ $this, 'change_schedule_button_text' ], 10, 4 );

	}

	/**
	 * Set up new Republish and future-republish post status
     *
     * @return void
	 */
	public function register_post_status() {
		register_post_status( 'republish', array(
			'label'     => _x( 'Republish', 'post', 'content-republish' ),
			'internal'  => true,
		) );

		register_post_status( 'future-republish', array(
			'label'     => _x( 'Schedule Republish', 'post', 'content-republish' ),
			'internal'  => false,
            'protected' => true,
			'private' => false,
            'public'    => false,
            'publicly_queryable' => false,
			'exclude_from_search' =>  false,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list' => true,
			/* translators: %s: Number of articles. */
			'label_count' => _n_noop( 'Schedule Republish <span class="count">(%s)</span>', 'Schedule Republish <span class="count">(%s)</span>', 'content-republish' ),
		) );

		register_post_status( 'republish-trash', array(
			'label'     => _x( 'Pending Delete', 'post', 'content-republish' ),
			'internal'  => true,
		) );
	}

	/**
	 * Function to intercept the publishing hooks and prevent Republish posts from going live
     *
     * @param $data
	 * @param $postarr
	 *
	 * @return mixed
	 */
	public function prevent_publish( $data, $postarr ) {
		if ( ! empty( $postarr['ID'] ) ) {
			$is_republish = Utils::check_republish( absint($postarr['ID'] ) );

			if ( $is_republish ) {
				if ( $data['post_status'] === 'publish' ) {
					$data['post_status'] = 'republish';
				} else if ( $data['post_status'] === 'future' ) {
					$data['post_status'] = 'future-republish';
				}
			}
		}

		return $data;
	}

	/**
	 * Change the Publish button to Republish
	 *
	 * @param $translation
	 * @param $text
	 * @param $domain
	 *
	 * @return mixed|string|null
	 */
	public function change_publish_button_text( $translation, $text, $domain ) {
		global $pagenow;

		if ( $domain !== 'default' ) {
			return $translation;
		}
		if ( $text !== 'Publish') {
			return $translation;
		}
		if ( isset($pagenow) && in_array( $pagenow, ['post.php', 'post-new.php'], true ) ) {
			$post = get_post();

			if ( $post instanceof \WP_Post && Utils::check_republish($post->ID) ) {
				return __( 'Republish', 'content-republish' );
			}
		}

		return $translation;
	}

	/**
	 * Change the Schedule button to Schedule Republish in Classic editor
	 *
	 * @param $translation
	 * @param $text
	 * @param $context
	 * @param $domain
	 *
	 * @return mixed|string|null
	 */
	public function change_schedule_button_text( $translation, $text, $context, $domain ) {
		global $pagenow;

		if ( $domain !== 'default' || $context !== 'post action/button label' ) {
			return $translation;
		}

		if ( $text !== 'Schedule' ) {
			return $translation;
		}
		if ( isset($pagenow) && in_array( $pagenow, ['post.php', 'post-new.php'], true) ) {
			$post = get_post();

			if ( $post instanceof \WP_Post && Utils::check_republish($post->ID) ) {
				return __( 'Schedule Republish', 'content-republish' );
			}
		}
		return $translation;
	}
}