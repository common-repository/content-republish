<?php
namespace Yipresser\ContentRepublish\Admin;

use Yipresser\ContentRepublish\Helpers\Cloner;
use Yipresser\ContentRepublish\Utils;

/**
 * Class metaboxes
 */
class MetaBoxes {

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
        add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'wp_ajax_contentrepublish_clone_post', [$this, 'ajax_clone_post'] );
        add_action( 'wp_ajax_contentrepublish_republish_conversion', [$this, 'ajax_convert_post_to_republish'] );
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue_scripts'] );
	}

	/**
	 * Logic to display the metaboxes on various Post Types
     *
     * @return void
	 */
	public function add_meta_box() {
		global $post;

		global $current_user;

		if ( ! ($current_user instanceof \WP_User) ) {
			$current_user = wp_get_current_user();
		}

		$has_user_permission = Utils::check_user_clone_permission( $current_user );

		$has_post_type_permission = Utils::check_post_type_permission( $post->post_type );

		if ( $has_user_permission && $has_post_type_permission ) {
			$is_republish_post = Utils::check_republish($post->ID);
			if ( ! $is_republish_post) {
				add_meta_box(
					'contentrepublish_convert_to_republish',
                    __( 'Content Republish', 'content-republish' ),
					[ $this, 'render_republish_conversion_meta_box'],
					Utils::get_approved_post_types(),
					'normal'
				);
			}
		}
	}

	/**
	 * Render republish conversion metabox content
     *
     * @return void
     *
	 */
	public function render_republish_conversion_meta_box($post) {
		// check if the post is a new post (without post id) or a saved post
		if ( isset( $_GET['post'] ) && absint( $_GET['post']) === $post->ID ) :
			// Use nonce for verification
            wp_nonce_field( 'contentrepublish_republish_conversion', 'contentrepublish_nonce' );

            if ( 'publish' === $post->post_status) : ?>
                <p><button class="button button-secondary" id="contentrepublish-clone-btn"><?php esc_html_e( 'Clone this post', 'content-republish'); ?></button></p>
                <?php $cloned_child = get_post_meta( $post->ID, '_republish_cloned_child', true );
                if ( !empty($cloned_child) && is_array($cloned_child) ) : ?>
                    <p><?php /* translators: %d: Number of articles. */
                        printf( esc_html__( 'You have %d clone for this post.', 'content-republish'), count($cloned_child) ); ?></p>
                    <table style="margin-bottom:1rem;">
                        <tr>
                            <th style="text-align:left;"><?php esc_html_e('Post ID', 'content-republish' ); ?></th>
                            <th style="text-align:left;"><?php esc_html_e('Post Title', 'content-republish' ); ?></th>
                            <th></th>
                        </tr>
                    <?php
                    foreach( $cloned_child as $child_id ) : ?>
                        <tr>
                            <td style="padding-right: 1rem;"><?php echo esc_html($child_id); ?></td>
                            <td style="padding-right: 1rem;"><?php echo esc_html(get_the_title( $child_id )); ?></td>
	                        <?php if( current_user_can( 'edit_post', $child_id ) ) : ?>
                                <td><a href="<?php echo esc_url(get_edit_post_link($child_id, '&'), null, '&'); ?>">Edit</a></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </table>
                <?php endif; ?>
                <div id="contentrepublish-status"></div>
            <?php else : ?>
                    <p><?php esc_html_e( 'Convert this post to a republish post. Once converted, you can use this post to overwrite an existing post.', 'content-republish' ); ?></p>
                    <p><label for="contentrepublish_post_parent"><?php esc_html_e( 'Enter the post_id of the published post to republish:', 'content-republish' ); ?></label> <input type="number" id="contentrepublish_post_parent" name="republish[post_parent]" value=""> <button class="button button-secondary" id="contentrepublish-convert-republish-btn"><?php esc_html_e( 'Convert to Republish post', 'content-republish');?></button></p>
                    <div id="contentrepublish-status"></div>
                <?php
            endif;
	    endif;
    }

	/**
	 * @return void
	 */
    public function ajax_clone_post() {
	    $response = array();

	    if ( ! check_ajax_referer('contentrepublish_republish_conversion') ) {
		    $response['message'] = __( 'Nonce check failed.', 'content-republish');
		    wp_send_json_error( $response);
	    } else {
		    $post_id = isset($_REQUEST['post_id']) ? absint($_REQUEST['post_id']) : 0;
            if ( $post_id > 0 && 'publish' === get_post_status( $post_id ) ) {
	            $republish_id = $this->cloner->clone_post( $post_id );
	            if ( ! is_wp_error( $republish_id ) && 0 < $republish_id ) {
		            $this->cloner->add_clone_child($post_id, $republish_id);
		            /* translators: %s: edit post link. */
                    $response['message'] = sprintf( __( 'Post cloned successfully. <a href="%s">Click here to edit</a>.', 'content-republish' ), esc_url( get_edit_post_link( $republish_id, '&' ), null, '&' ) );
		            wp_send_json_success( $response );
	            } else {
		            $response['message'] = __( 'Error: Cloning failed.', 'content-republish' );
		            wp_send_json_error( $response);
	            }
            } else {
	            $response['message'] = __( 'Error: Invalid post id or post status (Only published post can be cloned).', 'content-republish' );
	            wp_send_json_error( $response);
            }
	    }
    }

	/**
	 * @return void
	 */
    public function ajax_convert_post_to_republish() {
	    $response = array();

	    if ( ! check_ajax_referer('contentrepublish_republish_conversion') ) {
		    $response['message'] = __( 'Nonce check failed.', 'content-republish' );
		    wp_send_json_error( $response);
	    } else {
		    $parent_id = isset($_REQUEST['parent_id']) ? absint($_REQUEST['parent_id']) : 0;
            if ( $parent_id > 0 ) {
	            if ( 'publish' !== get_post_status( $parent_id ) ) {
		            $response['message'] = __( 'Error: Existing post is not a published post. Republish conversion failed.', 'content-republish' );
		            wp_send_json_error( $response);
	            } else {
		            $post_id = isset($_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;
		            if ( $post_id > 0 ) {
			            update_post_meta( $post_id, '_republish_post', 1 );
			            update_post_meta( $post_id, '_republish_post_parent', $parent_id );
			            $this->cloner->add_clone_child($parent_id, $post_id);
			            $response['message'] = __( 'Success: Reload the page to see the changes.', 'content-republish' );
			            wp_send_json_success( $response, 200 );
		            } else {
			            $response['message'] = __( 'Error: Invalid post id.', 'content-republish' );
			            wp_send_json_error( $response);
		            }
	            }
            } else {
	            $response['message'] = __( 'Error: Invalid post id.', 'content-republish' );
	            wp_send_json_error( $response);
            }

	    }
    }

    /**
     * @param $hook
     * @return void
     */
    public function enqueue_scripts( $hook ) {
        global $post;

        global $current_user;

        if ( 'post.php' != $hook ) {
            return;
        }

        if ( ! ($current_user instanceof \WP_User) ) {
            $current_user = wp_get_current_user();
        }

        $has_user_permission = Utils::check_user_clone_permission( $current_user );

        $has_post_type_permission = Utils::check_post_type_permission( $post->post_type );

        if ( $has_user_permission && $has_post_type_permission ) {
            $is_republish_post = Utils::check_republish($post->ID);
            if (!$is_republish_post) {
                wp_enqueue_script('content-republish-metabox', CONTENTREPUBLISH_URI . 'includes/admin/assets/js/metabox.min.js', ['jquery'], CONTENTREPUBLISH_VERSION, true);
                wp_localize_script('content-republish-metabox', 'CONTENT_REPUBLISH_POST_STATUS', ['post_id' => $post->ID, 'post_status' => $post->post_status]);
            }
        }
    }
}