<?php

namespace Yipresser\ContentRepublish\Helpers;

use Yipresser\ContentRepublish\Utils;

/**
 * Helper class for republishing post
 */
class Republisher {

	/**
	 * @var Cloner
	 */
	protected $cloner;

	public function __construct( Cloner $cloner ) {
		$this->cloner = $cloner;
	}

	/**
	 * Function to republish post
	 *
	 * @param $post_item
	 *
	 * @return void|null
	 */
	public function republish( $post_item ) {

		// check whether the arg is Post object or post_id
		if ( empty( $post_item ) ) {
			return;
		}

		if ( $post_item instanceof \WP_Post ) {
			$republish = $post_item;
		} elseif ( is_object( $post_item ) ) {
			if ( empty( $post_item->filter ) ) {
				$republish = sanitize_post( $post_item, 'raw' );
				$republish = new \WP_Post( $republish );
			} elseif ( 'raw' === $post_item->filter ) {
				$republish = new \WP_Post( $post_item );
			} else {
				$republish = \WP_Post::get_instance( $post_item->ID );
			}
		} else {
			$republish = \WP_Post::get_instance( $post_item );
		}

		if ( ! $republish ) {
			return null;
		}

		$republish_id = $republish->ID;

		$is_republish_post = Utils::check_republish($republish_id);
		if ( $is_republish_post ) {
			$settings = Utils::get_settings_option();
			$parent_id = (int) get_post_meta( $republish->ID, '_republish_post_parent', true);
			$original = get_post( $parent_id);
			// check if original post exists
			if ( ! $original ) {
				// Original post does not exist, delete this republish clone.
				wp_delete_post( $republish_id );
				return null;
			}

			$parent = [];
			$parent['ID'] = $parent_id;
			$parent['post_title'] = $republish->post_title;
			$parent['post_content'] = $republish->post_content;
			$parent['post_content_filtered'] = $republish->post_content_filtered;
			$parent['post_excerpt'] = $republish->post_excerpt;
			$parent['post_parent'] = $republish->post_parent;

			$replace_author = false;
			if ( isset( $settings['replace-author'] ) && intval( $settings['replace-author'] ) === 1 ) {
				$replace_author = true;
				$parent['post_author'] = $republish->post_author;
			}

			if ( isset( $settings['clone-post-date'] ) && intval( $settings['clone-post-date'] ) === 1 ) {
				if ( get_post_timestamp($republish_id) <= time() ) { // republish date is in the past, safe to clone
					$parent['post_date'] = $republish->post_date;
					$parent['post_date_gmt'] = $republish->post_date_gmt;
				} else { // republish date is in the future, set the original date to now.
					$timezone = wp_timezone();
					$date = date_create('now', $timezone );
					if ( $date instanceof \DateTime ) {
						$parent['post_date'] = $date->format( 'Y-m-d H:i:s' );
						$date->setTimezone( new \DateTimeZone( 'UTC' ) );
						$parent['post_date_gmt'] = $date->format( 'Y-m-d H:i:s' );
					}
				}
			}

			// republish in action, do not fire after insert hooks to prevent duplicate post revision
			$updated_post_id = wp_update_post( wp_slash( $parent ), true, false );

			// change attachment for republish post to its parent, since we will be deleting the republish post and we don't want to create orphaned attachments.
			$republish_attachements = get_posts([
				'post_type'      => 'attachment',
				'post_parent' => $republish_id,
				'posts_per_page' => -1,
				'fields'        => 'ids',
			]);
			if ( ! empty( $republish_attachements ) ) {
				foreach( $republish_attachements as $attachment_id ) {
					wp_update_post(['ID' => $attachment_id, 'post_parent' => $parent_id] );
				}
			}

			// save revision again after post updated.
			if ( ! is_wp_error($updated_post_id) && $updated_post_id === $parent_id ) {
				$fields = _wp_post_revision_fields( $parent );

				$revision_data = array();

				foreach ( array_intersect( array_keys( $parent ), array_keys( $fields ) ) as $field ) {
					$revision_data[ $field ] = $parent[ $field ];
				}

				$revision_data['post_parent']   = $parent['ID'];
				$revision_data['post_status']   = 'inherit';
				$revision_data['post_type']     = 'revision';
				$revision_data['post_name']     = "$parent[ID]-revision-v1"; // "1" is the revisioning system version.
				$revision_data['post_date']     = $parent['post_modified'] ?? '';
				$revision_data['post_date_gmt'] = $parent['post_modified_gmt'] ?? '';
				if ( $replace_author ) {
					$revision_data['post_author'] = $republish->post_author;
				} else {
					$revision_data['post_author'] = get_post_field( 'post_author', $parent_id );
				}

				wp_insert_post( wp_slash($revision_data), true );
			}

			if ( isset($settings['clone-tax']) && intval( $settings['clone-tax'] ) === 1 ) {
				$this->cloner->clone_taxonomies( $parent_id, $republish );
			}

			if ( isset($settings['clone-post-metas']) && intval( $settings['clone-post-metas'] ) === 1 ) {
				$this->cloner->clone_post_metas($parent_id, $republish_id);
			}

			update_post_meta( $parent_id, '_republish_last_updated', $republish->post_date );

			do_action( 'contentrepublish_after_republish', $republish_id, $parent_id );

			$this->cloner->remove_cloned_child($parent_id, $republish_id);

			// set it to republish-trash status first. If the redirect cleanup failed, it can be cleaned up later
			wp_update_post( ['ID' => $republish_id, 'post_status' => 'republish-trash'], false, false );
			update_post_meta( $republish_id, '_republish_status', 'pending-delete' );
		}
	}


	/**
	 * Function to delete the republish post.
	 *
	 * @param $republish_id
	 *
	 * @return void
	 */
	public function delete_republish( $republish_id ) {
		$settings = Utils::get_settings_option();
		$force_delete = false;
		if ( 'delete' === $settings['after-republish-action'] ) {
			$force_delete = true;
		}
		wp_delete_post( $republish_id, $force_delete );
	}
}