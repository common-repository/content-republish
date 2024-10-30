<?php

namespace Yipresser\ContentRepublish\Admin;

use Yipresser\ContentRepublish\Helpers\Cloner;
use Yipresser\ContentRepublish\Utils;

/**
 * Class to insert and manage Republish row action to posts screen
 */
class RowAction {

	/**
	 * @var Cloner
	 */
	protected $cloner;

	public function __construct( Cloner $cloner ) {
		$this->cloner = $cloner;
	}

	/**
	 * Start the engine running
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'init', [ $this, 'init' ] );

		add_action( 'admin_action_contentrepublish-clone-post', [ $this, 'create_clone_post' ] );

		add_filter( 'display_post_states', [ $this, 'show_clone_post_states' ], 10, 2 );
	}

	/**
	 * Hooks to the row_actions filters
	 *
	 * @return void
	 */
	public function init() {
		if ( is_admin() ) {
			add_filter( 'post_row_actions', [ $this, 'row_actions'], 10, 2 );
			add_filter( 'page_row_actions', [ $this, 'row_actions'], 10, 2 );
		}
	}

	/**
	 * Row actions in actions
	 *
	 * @param $actions
	 * @param $post
	 *
	 * @return mixed
	 */
	public function row_actions( $actions, $post ) {
		global $current_user;

		if ( ! ($current_user instanceof \WP_User) ) {
			$current_user = wp_get_current_user();
		}
		$approved = Utils::get_approved_post_types();

		$has_user_permission = Utils::check_user_clone_permission( $current_user );

		if ( $has_user_permission ) {
			if ( in_array($post->post_type, $approved)) {
				if ( 'publish' === $post->post_status ) {
					$actions['clone_post'] = '<a href="' . wp_nonce_url( admin_url( 'admin.php?action=contentrepublish-clone-post&post=' . $post->ID ), 'contentrepublish-clone-' . $post->ID ) . '" title="'
						. esc_attr( __( "Clone this post for update and republication", 'content-republish' ) )
						. '">' . __( 'Clone for Republish', 'content-republish' ) . '</a>';
				}
			}
		}

		return $actions;
	}

	/**
	 * Function to handle the cloning link in row action
	 * @return void
	 */
	public function create_clone_post() {
		global $current_user;

		$parent_id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : null;

		if ( ! ($current_user instanceof \WP_User) ) {
			$current_user = wp_get_current_user();
		}

		$has_permission = Utils::check_user_clone_permission( $current_user );

		if ( check_admin_referer( 'contentrepublish-clone-' . $parent_id ) && $has_permission ) {
			if ( $parent_id && 'publish' === get_post_field( 'post_status', $parent_id ) ) {
				$republish_id = $this->cloner->clone_post( $parent_id );

				if ( 0 < $republish_id && !is_wp_error( $republish_id ) ) {
					$this->cloner->add_clone_child($parent_id, $republish_id);

					wp_safe_redirect( get_edit_post_link( $republish_id, '&' ) );
					exit;
				}
			} else {
				wp_die( esc_html__( 'Post cloning failed', 'content-republish' ) );
			}
		}

		// if we didn't redirect out, then we fail.
		wp_die( esc_html__( 'Invalid Post ID', 'content-republish' ) );
	}

	/**
	 * Function to show the cloned post states in Posts screen.
	 * @param $post_states
	 * @param $post
	 *
	 * @return array|mixed
	 */
	public function show_clone_post_states( $post_states, $post) {

		if ( ! $post instanceof \WP_Post || ! is_array( $post_states ) ) {
			return $post_states;
		}

		$is_republish = Utils::check_republish( $post->ID );

		if ( $is_republish ) {
			$post_states['content_republish_cloned_item'] = \sprintf(
				/* translators: %s is replaced by the post title. */
				esc_html__( 'Clone of %s', 'content-republish' ),
				Utils::get_parent_title($post)
			);
			return $post_states;
		}

		return $post_states;
	}
}