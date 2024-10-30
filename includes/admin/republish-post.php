<?php

namespace Yipresser\ContentRepublish\Admin;

use Yipresser\ContentRepublish\Helpers\Republisher;
use Yipresser\ContentRepublish\Utils;

/**
 * A class to handle the Republish post in Admin
 */
class Republish_Post {
	/**
	 * @var Republisher
	 */
    protected $republisher;

    public function __construct( Republisher $republisher ) {
		$this->republisher = $republisher;
	}

	/**
	 * Start the engine running
     *
     * @return void
	 */
    public function run() {
	    add_action( 'admin_notices', [ $this, 'post_admin_notice' ] );
	    add_action( 'admin_enqueue_scripts', [ $this, 'gutenberg_ui_mod'] );
	    add_action( 'transition_post_status', [ $this, 'schedule_or_republish' ], 8, 3 );
	    add_action( 'content_republish_post', [ $this, 'do_republish' ] );
	    add_action( 'admin_action_contentrepublish-delete-post', [ $this, 'do_delete_republish' ] );
    }

	/**
	 * Add an admin notice at the top of republish posts.
     *
     * @return void
	 */
	public function post_admin_notice(): void {
		global $post;

		$screen = get_current_screen();
		if ( $post instanceof \WP_Post ) {
			$is_republish_post = Utils::check_republish( $post->ID );

			if ( $screen->base === 'post' ) {
                if ($is_republish_post ) :
                    $parent = Utils::get_parent( $post->ID );
                    ?>
                    <div class="notice notice-warning">
                        <p><?php printf(
	                        /* translators: %1$s and %2$s are replaced by the post title, permalink and edit link. */
                                wp_kses( __( 'This is a cloned copy of %1$s ( %2$s | %3$s ). Publishing this post will overwrite the original post with this content.', 'content-republish' ), 'strong' ),
                                '<strong>' . esc_html( html_entity_decode( get_the_title( $parent ) ) ) . '</strong>',
                                '<a href="' . esc_url( get_permalink( $parent ), null, '&' ) . '" target="_blank">'. esc_html__('view original', 'content-republish') . '</a>',
		                        '<a href="' . esc_url( get_edit_post_link( $parent, '&' ), null, '&' ) . '" target="_blank">' . esc_html__( 'edit original', 'content-republish') . '</a>'
                            ); ?></p>
                    </div>
                <?php
                else :
                    $last_update = get_post_meta( $post->ID, '_republish_last_updated', true );
                    if ( !empty( $last_update ) ) : ?>
                        <div class="notice notice-success is-dismissible">
                            <p><?php printf(
	                            /* translators: %s is replaced by the last update date. */
                                    esc_html__( 'This post was last updated on %s.', 'content-republish' ),
                                    esc_html($last_update)
                                ); ?></p>
                        </div>
                    <?php
                    endif;
			    endif;
            }
		}
	}

	/**
	 * Add admin notice to republish post, and change the Publish button to show Republish. For Gutenberg editor only.
     * @return void
	 */
	public function gutenberg_ui_mod(): void {
        global $post;
        $screen = get_current_screen();
        if ( isset( $post->ID) && is_numeric( $post->ID) ) {
            $is_republish_post = Utils::check_republish($post->ID);

            if ( $screen->base == 'post' && $screen->is_block_editor() ) {
                $last_update = get_post_meta( $post->ID, '_republish_last_updated', true );
                $ContentRepublishBlockUI = [
                    'is_republish' => $is_republish_post,
                    'last_update' => $last_update ?? '',
                ];
                wp_enqueue_script('content-republish-block-editor', CONTENTREPUBLISH_URI . 'includes/admin/assets/js/block-editor.min.js', ['jquery'], CONTENTREPUBLISH_VERSION, true);

                $ContentRepublishBlocki18n = [];
                if ( $is_republish_post ) {
                    $parent = Utils::get_parent($post->ID);
                    $link = get_permalink($parent);
                    $redirect_url = add_query_arg([
                        'action' => 'contentrepublish-delete-post',
                        'republish_id' => $post->ID,
                        'parent_id' => $parent,
                        'republish_nonce' => wp_create_nonce('contentrepublish-delete-post'),
                    ],
                        admin_url('admin.php')
                    );
                    $ContentRepublishBlockUI['parent_url'] = esc_url($link, null, '&');
                    $ContentRepublishBlockUI['parent_edit_link'] = esc_url(get_edit_post_link($parent, '&'), null, '&');
                    $ContentRepublishBlockUI['redirect_url'] = esc_url($redirect_url, null, '&');

                    $ContentRepublishBlocki18n = [
                        'message' => sprintf(
                        /* translators: %s is replaced by the post title. */
                            esc_html__('This is a draft copy of %s. Publishing this post will overwrite the original post with this content.', 'content-republish'),
                            '"' . esc_html(html_entity_decode(get_the_title($parent))) . '"')
                    ];
                } else {
                    if ( !empty( $last_update ) ) {
                        $ContentRepublishBlocki18n = [
                            'message' => sprintf(
                            /* translators: %s is replaced by the last update date. */
                                esc_html__( 'This post was last updated on %s', 'content-republish' ),
                                esc_html($last_update) )
                        ];
                    }
                }
                wp_localize_script(
                    'content-republish-block-editor',
                    'ContentRepublishBlockUI',
                    $ContentRepublishBlockUI
                );
                wp_localize_script(
                    'content-republish-block-editor',
                    'ContentRepublishBlocki18n',
                    $ContentRepublishBlocki18n
                );
            }
        }
	}

	/**
	 * Schedule a republish post to go live in the future
     * @param $new_status
	 * @param $old_status
	 * @param $post
	 *
	 * @return void
	 */
	public function schedule_or_republish( $new_status, $old_status, $post ) {
		if ( 'republish' === $new_status && 'republish' !== $old_status ) {
            $parent_id = Utils::get_parent($post->ID);
            $this->republisher->republish( $post );
            if( ! Utils::is_rest_request() && ! wp_doing_ajax() ) {
                wp_safe_redirect(
                    add_query_arg( [
                        'action' => 'contentrepublish-delete-post',
                        'republish_id' => $post->ID,
                        'parent_id' => $parent_id,
                        'republish_nonce' => wp_create_nonce( 'contentrepublish-delete-post' ),
                    ],
                        admin_url( 'admin.php' )
                    )
                );
                exit;
            }
        } else if ( 'future-republish' === $new_status && $post instanceof \WP_Post ) {
			$post_timestamp = get_post_timestamp( $post->ID );
			if ( ! as_has_scheduled_action( 'content_republish_post', [$post->ID], 'content-republish') ) {
				as_schedule_single_action( $post_timestamp, 'content_republish_post', [ $post->ID ], 'content-republish' );
			} else {
				// reschedule action if the post timestamp is not the same.
				$scheduled_timestamp = as_next_scheduled_action( 'content_republish_post', [$post->ID], 'content-republish' );
				if ( $scheduled_timestamp !== $post_timestamp ) {
					as_unschedule_action('content_republish_post', [ $post->ID ], 'content-republish');
					as_schedule_single_action( $post_timestamp, 'content_republish_post', [ $post->ID ], 'content-republish' );
				}
			}
		} else if ( 'future-republish' === $old_status && 'future-republish' !== $new_status ) {
            // need to unschedule the scheduling
			if ( as_has_scheduled_action( 'content_republish_post', [$post->ID], 'content-republish') ) {
				as_unschedule_action('content_republish_post', [ $post->ID ], 'content-republish');
			}
        }
	}

	/**
	 * Run the Republish action.
     *
     * @param $republish_id
	 *
	 * @return void
	 */
	public function do_republish( $republish_id ) {
        $this->republisher->republish( $republish_id );
        $this->republisher->delete_republish($republish_id);
	}

	/**
	 * Check if the republish post need to be deleted.
     * @return void
	 */
    public function do_delete_republish() {
	    $redirect_url = admin_url('edit.php');

        if ( check_admin_referer( 'contentrepublish-delete-post', 'republish_nonce' ) ) {
            if ( isset( $_GET['republish_id'] ) && is_numeric( $_GET['republish_id'] ) ) {
                $republish_id = absint( $_GET['republish_id'] );
                if ( 'pending-delete' === get_post_meta( $republish_id, '_republish_status', true ) ) {
                    $this->republisher->delete_republish( $republish_id );
                }
            }
            if ( isset( $_GET['parent_id'])) {
                $parent_id = absint( $_GET['parent_id']);
                $redirect_url = admin_url( 'post.php?action=edit&post=' . $parent_id );
            }
        }

	    wp_safe_redirect( $redirect_url );
        exit;
    }


}