<?php

namespace Yipresser\ContentRepublish\Admin;

use Yipresser\ContentRepublish\Utils;

/**
 * Class Admin
 */
class Admin {

	/**
	 * @var string
	 */
	protected $menu_slug = 'content-republish';

    /**
     * @var string
     */
    protected $hook_suffix;

	/**
	 * @var Settings
	 */
    protected $settings;

	/**
	 * @var Cleaner
	 */
    protected $cleaner;

    public function __construct() {
	    $this->settings = new Settings();

	    $this->cleaner = new Cleaner();
    }

	/**
	 * Set up the admin section
     *
     * @return void
	 */
    public function setup() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );

		add_filter( 'plugin_action_links_' . CONTENTREPUBLISH_PLUGIN_BASENAME, [ $this, 'add_settings_plugin_action_links' ] );

        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_style' ] );
	}

	/**
	 * @param $links
	 * @return mixed
	 */
	public function add_settings_plugin_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( menu_page_url( $this->menu_slug, false ), null, '&' ) . '">' . esc_html__( 'Settings', 'content-republish' ) . '</a>';

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Add options page
     *
     * @return void
	 */
	public function add_menu_page() {
		$this->hook_suffix = add_options_page( __( 'Content Republish', 'content-republish' ), __( 'Content Republish', 'content-republish' ), 'manage_options', $this->menu_slug, [$this, 'render_menu_page'] );
    }

	/**
	 * Render menu page content
     *
     * @return void
	 */
	public function render_menu_page() {
		?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Content Republish Settings', 'content-republish' ); ?></h1>

            <div class="cr-settings-wrap flex">
	            <div class="cr-settings flex-grow">
                    <?php $this->settings->render_settings_on_page( $this->settings::OPTION_GROUP ); ?>
                </div>
                <div class="cr-promotion">
                    <div class="cr-promotion-container postbox">
                        <div class="container-header postbox-header">
                            <h3>
                                <span><?php esc_html_e( 'Upgrade to Content Republish Pro', 'content-republish'); ?></span>
                            </h3>
                        </div>

                        <div class="container-content inside">
                            <p><?php esc_html_e( 'Enhance the power of Content Republish with the Pro version:', 'content-republish' ); ?></p>
                            <ul>
                                <li><?php esc_html_e( 'Supports custom post types', 'content-republish'); ?></li>
                                <li><?php esc_html_e( 'Send notifications for republished posts', 'content-republish' );?></li>
                                <li><?php esc_html_e( 'Custom settings for individual post', 'content-republish' ); ?></li>
                                <li><?php esc_html_e( 'Remove Content Republish ads and branding', 'content-republish' ); ?></li>
                                <li><?php esc_html_e( 'Fast, professional support', 'content-republish' ); ?></li>
                            </ul>
                            <p><a href="https://www.contentimizer.com/content-republish?utm_source=client&utm_medium=plugin&utm_campaign=content-republish" class="upgrade-btn" target="__blank"><?php esc_html_e('Upgrade to Pro', 'content-republish' ); ?></a>
                            </p>
                        </div>
                    </div>
                    <div class="cr-promotion-container postbox">
                        <div class="container-header postbox-header">
                            <h3>
                                <span><?php esc_html_e( 'Need Content Republish Support?', 'content-republish'); ?></span>
                            </h3>
                        </div>
                        <div class="container-content inside">
                            <p><?php printf(
                                /* translators: %s is replaced by the external link to WordPress.org plugins directory. */
                                    __( 'If you need help or have a new feature request,  <a href="%s">let us know</a>', 'content-republish' ),
                                    esc_url( 'https://wordpress.org/support/plugin/content-republish/') ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	<?php
	}

    public function enqueue_style( $hook_suffix ) {
        if( $hook_suffix === $this->hook_suffix ) {
            wp_enqueue_style( 'content-republish-settings', CONTENTREPUBLISH_URI . 'includes/admin/assets/css/admin-settings.min.css', array(), CONTENTREPUBLISH_VERSION, 'all' );
        }
    }
}