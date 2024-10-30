<?php

namespace Yipresser\ContentRepublish;

/**
 * A helper class
 */
final class Utils {

	const CR_SETTINGS_OPTION_NAME = 'contentrepublish-settings';

	/**
	 * Get settings option from the option table
	 * @return false|mixed|null
	 */
	public static function get_settings_option() {
		$default = [
			'post-types' => ['post']
		];
		$option = get_option(self::CR_SETTINGS_OPTION_NAME, $default );
		if ( empty( $option['post-types'] ) ) {
			$option['post-types'] = ['post','page'];
		}
		return $option;
	}

	/**
	 * Function to check if a post is a republish post
	 * @param int $post_id
	 *
	 * @return bool
	 */
	public static function check_republish( int $post_id ): bool {
		$is_republish = false;
		if ( ! empty( $post_id ) ) {
			$check_republish = get_post_meta( $post_id, '_republish_post', true );
			if ( $check_republish ) {
				$is_republish = true;
			}
		}
		return $is_republish;
	}

	/**
	 * Function to get the title of the parent post
	 * @param $post
	 *
	 * @return string|null
	 */
	public static function get_parent_title( $post ): ?string {
		$title = null;
		if ( $post instanceof \WP_Post ) {
			$parent_id = (int) get_post_meta( $post->ID, '_republish_post_parent', true); //$post->post_parent;
			if ( $parent_id ) {
				$title = '<a href="'. get_edit_post_link($parent_id, '&' ) .'">'. get_the_title($parent_id). '</a>';
			}
		}

		return $title;
	}

	/**
	 * Function to check if a user has clone permission
	 * @param \WP_User $user
	 *
	 * @return bool
	 */
	public static function check_user_clone_permission( \WP_User $user ): bool {
		$option = self::get_settings_option();

		if ( $user->has_cap( 'clone_posts ' ) )  {
			return true;
		} else {
			$user_roles = $user->roles;

			$clonable_roles = $option['clone-roles'] ?? ['administrator'];

			return (bool) array_intersect( $user_roles, $clonable_roles );
		}
	}

	/**
	 * Function to check if a post type is enabled for republish
	 * @param $post_type
	 *
	 * @return bool
	 */
	public static function check_post_type_permission( $post_type ): bool {
		$option = self::get_settings_option();

		return in_array( $post_type, $option['post-types'] );
	}

	/**
	 *
	 * Function to retrieve all the post types enabled for republish
	 * @return array
	 */
	public static function get_approved_post_types(): array {
		$option = self::get_settings_option();
		return $option['post-types'];
	}

	/**
	 * Function to retrieve the parent of a republish post.
	 * @param $post_id
	 *
	 * @return int
	 */
	public static function get_parent( $post_id ): int {
		$parent = 0;
		if ( ! empty( $post_id) && is_numeric( $post_id ) ) {
			$parent = (int) get_post_meta( (int)$post_id, '_republish_post_parent', true );
		}
		return $parent;
	}

	/**
	 * A quick check to see if the current request is REST request
	 * @return bool
	 */
	public static function is_rest_request(): bool {
		if ( ! defined( 'REST_REQUEST' ) || ( defined( 'REST_REQUEST' ) && ! REST_REQUEST ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * @param $plugin
	 *
	 * @return bool
	 */
	public static function is_plugin_active( $plugin ): bool {
		return in_array( $plugin, (array) get_option( 'active_plugins', array() ), true );
	}
}