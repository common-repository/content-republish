<?php

namespace Yipresser\ContentRepublish\Admin;

use Yipresser\ContentRepublish\Utils;
use Yipresser\ContentRepublish\Vendor\Yipresser\WpSettingsApiHelper\WP_Settings_API_Helper;

/**
 * Class to handle fields in Settings page
 */
class Settings extends WP_Settings_API_Helper {

	const OPTION_GROUP = 'contentrepublish-settings';

	protected $option_name;

	public function __construct() {

		$this->init();

		parent::__construct();
	}

	/**
	 * Set up fields for the Settings API
     *
     * @return void
	 */
	public function init() {

        $this->option_name = Utils::CR_SETTINGS_OPTION_NAME;

		$this->settings_options = [
			[
				'option_group'  => self::OPTION_GROUP,
				'option_name'   => $this->option_name,
				'args'          => [ 'sanitize_callback' => [ $this, 'sanitize_settings' ]]
			]
		];

		$this->settings_sections = [
			[
				'id'          => 'content-republish-settings',
				'title'       => '',
				'description' => '',
				'menu_slug'   => self::OPTION_GROUP,
				'option_name' => $this->option_name,
				'fields'      => [
                    [
                        'type'  => 'callback',
                        'title' => __('Post Types to enable Republish', 'content-republish'),
                        'id'    => 'post-types',
                        'name'  => 'post-types',
                        'desc'  => '',
                        'callback' => [ $this, 'render_post_types_fields'],
                    ],
                    [
						'type'  => 'callback',
						'title' => __('Who can clone posts?', 'content-republish'),
						'id'    => 'clone-role-permission',
						'name'  => 'clone-role-permission',
						'desc'  => '',
						'callback' => [ $this, 'render_roles_permission_fields'],
						'param' => 'clone',
					],
					[
						'type'  => 'checkbox',
						'title' => __('Overwrite the original author when republish', 'content-republish'),
						'id'    => 'replace-author',
						'name'  => 'replace-author',
						'desc'  => '',
					],
                    [
						'type'  => 'checkbox',
						'title' => __('Overwrite the taxonomies (categories, tags, etc.) when republish', 'content-republish'),
						'id'    => 'clone-taxonomies',
						'name'  => 'clone-tax',
						'desc'  => '',
					],
                    [
						'type'  => 'checkbox',
						'title' => __('Overwrite the custom fields when republish', 'content-republish'),
						'id'    => 'clone-post-metas',
						'name'  => 'clone-post-metas',
						'desc'  => '',
					],
					[
						'type'  => 'checkbox',
						'title' => __('Update the post date when republish', 'content-republish'),
						'id'    => 'clone-post-date',
						'name'  => 'clone-post-date',
						'desc'  => '',
					],
					[
						'type'  => 'select',
						'title' => __('After republish:', 'content-republish'),
						'id'    => 'after-republish-action',
						'name'  => 'after-republish-action',
                        'choices' => [
	                        'trash' => __( 'Move the cloned post to Trash', 'content-republish' ),
                            'delete' => __( 'Delete the cloned post permanently', 'content-republish' ),
                        ],
						'desc'  => '',
					],
				],
			],
		];
	}

    /**
     * Function to render the post types field
     *
     * @return void
     */
    public function render_post_types_fields() {
        $option = Utils::get_settings_option();
        ?>
        <label for="post"><input id="post" name="<?php echo esc_attr( $this->option_name . '[post-types][]'); ?>" type="checkbox" checked disabled value="post">Post</label><br/>
        <?php
        $checked = '';
        if ( isset( $option['post-types']) && in_array('page', $option['post-types'] ) ) {
        $checked = ' checked="checked"';
        } ?>
        <label for="page"><input id="page" name="<?php echo esc_attr( $this->option_name . '[post-types][]'); ?>" type="checkbox"<?php echo esc_attr( $checked ); ?> value="page">Page</label><br/>
    <?php
        // TODO: need to account for block post type
        $custom_post_types = get_post_types( ['show_ui' => true, '_builtin' => false ], 'objects');
        $post_types = [];

        foreach( $custom_post_types as $custom_post_type ) {
            $post_types[$custom_post_type->name] = $custom_post_type->label;
        }

        foreach( $post_types as $post_type_name => $post_type_label ) : ?>
            <label for="<?php echo esc_attr($post_type_name); ?>"><input id="<?php echo esc_attr($post_type_name); ?>" name="<?php echo esc_attr( $this->option_name . '[post-types][]'); ?>" type="checkbox" disabled value="<?php echo esc_attr($post_type_name); ?>"><?php echo esc_html( $post_type_label ); ?></label><br/>
        <?php endforeach; ?>

        <div style="margin-top:10px;font-weight:bold;">
        <?php printf(
                /* translators: %s is replaced by the external link to PRO purchase site. */
                __( '* Upgrade to <a target="_blank" href="%s">the PRO version</a> to enable custom post types.', 'content-republish'),
        esc_url('https://www.contentimizer.com/content-republish/?utm_source=client&utm_medium=plugin&utm_campaign=content-republish')

    ); ?>
        </div>
        <?php
    }

	/**
	 * Function to render the user roles
     *
     * @param $args
	 * @param $clone_republish
	 *
	 * @return void
	 */
    public function render_roles_permission_fields( $args, $clone_republish ) {

	    $option = Utils::get_settings_option();

        $roles = get_editable_roles();

        $input_name = 'clone-roles';

	    foreach ( $roles as $role => $details ) {
		    $name = translate_user_role( $details['name'] );
		    $checked = '';
            $disabled = '';
            if ( 'administrator' === $role ) {
	            $checked = ' checked="checked"';
                $disabled = 'disabled="disabled"';
            } else {
	            switch($clone_republish) {
                    case 'clone' :
	                    if ( isset( $option['clone-roles']) && is_array( $option['clone-roles'] ) && in_array($role, $option['clone-roles'], true ) ) {
		                    $checked = ' checked="checked"';
	                    }
	                    $input_name = 'clone-roles';
                        break;
                    case 'republish':
	                    if ( isset( $option['republish-roles']) && is_array( $option['republish-roles'] ) && in_array($role, $option['republish-roles'], true ) ) {
		                    $checked = ' checked="checked"';
	                    }
	                    $input_name = 'republish-roles';
                        break;
	            }
            }
            ?>
            <label for="<?php echo esc_attr($role); ?>"><input id="<?php echo esc_attr($role); ?>" name="<?php echo esc_attr( $this->option_name . '['.$input_name.'][]'); ?>" type="checkbox" <?php echo esc_attr($checked); ?> <?php echo esc_attr($disabled); ?> value="<?php echo esc_attr($role); ?>"><?php echo esc_html( $name ); ?></label><br/>
            <?php
	    } ?>
        <input type="hidden" name="<?php echo esc_attr( $this->option_name . '['.$input_name.'][]'); ?>" value="administrator">
        <?php
    }

	/**
	 * Sanitize fields before saving to database
     * @param $option
	 *
	 * @return mixed
	 */
	public function sanitize_settings( $option ) {
		if ( is_array( $option ) ) {
            if (isset( $option['post-types'] ) && is_array( $option['post-types'] ) ) {
                if ( in_array( 'page', $option['post-types'], true ) ) {
                    $option['post-types'] = ['post', 'page'];
                } else {
                    $option['post-types'] = ['post'];
                }
            } else {
                $option['post-types'] = ['post'];
            }

            if (isset( $option['clone-tax'] ) ) {
				if ( 1 !== intval( $option['clone-tax'] ) ) {
					$option['post-clone-tax'] = 1;
				}
			}

			if (isset( $option['clone-post-metas'] ) ) {
				if ( 1 !== intval( $option['clone-post-metas'] ) ) {
					$option['clone-post-metas'] = 1;
				}
			}

			if (isset( $option['clone-post-date'] ) ) {
				if ( 1 !== intval( $option['clone-post-date'] ) ) {
					$option['clone-post-date'] = 1;
				}
			}

            if (isset( $option['replace-author'] ) ) {
				if ( 1 !== intval( $option['replace-author'] ) ) {
					$option['replace-author'] = 1;
				}
			}

			if (isset( $option['clone-role-permission'] ) ) {
				$roles = array_keys( get_editable_roles() );
                if ( is_array( $option['clone-role-permission'])) {
                    $subset = array_diff( $option['clone-role-permission'], $roles );
                    if ( $subset ) {
                        $option['clone-role-permission'] = array_diff( $option['clone-role-permission'], $subset );
                    }
                } else {
                    $option['clone-role-permission'] = ['administrator'];
                }
			}

            if ( isset( $option['after-republish-action'])) {
                if ( ! in_array( $option['after-republish-action'], ['delete', 'trash'], true ) ) {
	                $option['after-republish-action'] = 'trash';
                }
            }
        }

        return $option;
	}
}