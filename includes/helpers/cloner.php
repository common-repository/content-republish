<?php
namespace Yipresser\ContentRepublish\Helpers;

use Yipresser\ContentRepublish\Utils;

/**
 * Helper class for cloning posts
 */
class Cloner {

	/**
	 * Function for cloning post
	 *
	 * @param $parent_id
	 *
	 * @return int|\WP_Error
	 */
	public function clone_post( $parent_id ) {
		$parent = get_post( $parent_id );
		$parent_metas = get_post_meta( $parent_id ); // return array

		$clone_meta = [];
		$clone_meta['_republish_post'] = 1;
		$clone_meta['_republish_post_parent'] = $parent_id;

		if ( is_array( $parent_metas ) ) {
			foreach ( $parent_metas as $key => $value ) {
				$clone_meta[$key] = maybe_unserialize( $value[0] );
			}
		}

		$clone_meta = apply_filters('contentrepublish_clone_post_meta', $clone_meta );

		$clone = [];
		$clone['post_title'] = $parent->post_title;
		$clone['post_content'] = $parent->post_content;
		$clone['post_author'] = $parent->post_author;
		$clone['post_content_filtered'] = $parent->post_content_filtered;
		$clone['post_excerpt'] = $parent->post_excerpt;
		$clone['post_type'] = $parent->post_type;
		$clone['post_parent'] = $parent->post_parent;
		$clone['meta_input'] = $clone_meta;
		$clone_id = wp_insert_post( wp_slash( $clone ) );

		if ( 0 < $clone_id && ! is_wp_error( $clone_id ) ) {
			// copy the taxonomies over
			$this->clone_taxonomies( $clone_id, $parent );

			do_action( 'contentrepublish_after_clone', $clone_id, $parent_id );
		}

		return $clone_id;
	}

	/**
	 * Function for cloning taxonomies
	 *
	 * @param $new_post_id
	 * @param $old_post
	 *
	 * @return void
	 */
	public function clone_taxonomies( $new_post_id, $old_post ): void {
		$taxonomies = get_object_taxonomies( $old_post->post_type );

		foreach ( $taxonomies as $taxonomy ) {
			// remove taxonomy on original post first
			wp_set_object_terms( $new_post_id, NULL, $taxonomy );

			$post_terms = wp_get_object_terms( $old_post->ID, $taxonomy, ['orderby' => 'term_order']);
			$terms = [];

			for ( $i = 0; $i < count( $post_terms ); $i++ ) {
				$terms[] = $post_terms[$i]->slug;
			}

			wp_set_object_terms( $new_post_id, $terms, $taxonomy );
		}
	}

	/**
	 * Function for cloning post metas
	 *
	 * @param $new_id
	 * @param $old_id
	 * @param bool $backup_and_delete
	 *
	 * @return void
	 */
	public function clone_post_metas( $new_id, $old_id ): void {

		$post_metas = get_post_meta( $old_id );
		if ( is_array( $post_metas ) ) {
			foreach ( $post_metas as $key => $value ) {
				if ( str_contains($key, '_republish_' ) ) {
					continue;
				}
				update_post_meta( $new_id, $key, maybe_unserialize( $value[0] ) );
			}
		}
	}

	/**
	 * @param $parent_id
	 * @param $child_id
	 * @return void
	 */
	public function add_clone_child( $parent_id, $child_id ) {
		$cloned_child = get_post_meta( $parent_id, '_republish_cloned_child', true );
		if ( is_array( $cloned_child ) ) {
			$cloned_child[] = $child_id;
		} else {
			$cloned_child = [$child_id];
		}
		update_post_meta( $parent_id, '_republish_cloned_child', $cloned_child );
	}

	/**
	 * @param $parent_id
	 * @param $child_id
	 * @return void
	 */
	public function remove_cloned_child($parent_id, $child_id ) {
		$cloned_child = get_post_meta( $parent_id, '_republish_cloned_child', true );
		if ( !empty($cloned_child) && is_array( $cloned_child ) ) {
			$cloned_child = array_diff($cloned_child, [$child_id]);
			if ( empty( $cloned_child ) ) {
				delete_post_meta( $parent_id, '_republish_cloned_child' );
			} else {
				update_post_meta( $parent_id, '_republish_cloned_child', $cloned_child );
			}
		}
	}
}