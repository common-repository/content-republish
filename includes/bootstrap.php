<?php
namespace Yipresser\ContentRepublish;

use Yipresser\ContentRepublish\Admin\Admin;
use Yipresser\ContentRepublish\Admin\MetaBoxes;
use Yipresser\ContentRepublish\Admin\PostStatus;
use Yipresser\ContentRepublish\Admin\Republish_Post;
use Yipresser\ContentRepublish\Admin\RowAction;
use Yipresser\ContentRepublish\Helpers\Republisher;
use Yipresser\ContentRepublish\Helpers\Cloner;

/**
 * Bootstrap class to start the plugin running
 */
final class Bootstrap {

	protected static $instance = null;

	/**
	 * @return Bootstrap|null
	 */
	public static function get_instance(): ?Bootstrap {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	protected $admin;

	protected $post_status;

	protected $republisher;

	protected $cloner;

	protected $republish_post;

	protected $metaboxes;

	protected $rowaction;

	private function __construct() {
		$this->autoload();
		$this->load_modules();
		$this->init();
	}

	/**
	 * Start the Autoload engine
	 *
	 * @return void
	 */
	public function autoload() {
		$autoload_file = CONTENTREPUBLISH_PATH . 'vendor/autoload.php';

		if ( is_readable( $autoload_file ) ) {
				require_once $autoload_file;
		}
	}

	/**
	 * Load all the modules and start the engine.
	 *
	 * @return void
	 */
	private function load_modules() {
		$this->admin = new Admin();
		$this->admin->setup();

		$this->post_status = new PostStatus();
		$this->post_status->run();

		$this->cloner = new Cloner();
		$this->republisher = new Republisher( $this->cloner);

		$this->republish_post = new Republish_Post( $this->republisher );
		$this->republish_post->run();

		$this->metaboxes = new MetaBoxes( $this->cloner );
		$this->metaboxes->run();

		$this->rowaction = new RowAction( $this->cloner );
		$this->rowaction->run();
	}

	/**
	 * @return void
	 */
	public function init() {
		add_action( 'init', [$this, 'load_textdomain'] );
	}

	/**
	 * Get the translations ready.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'content-republish', false, basename( CONTENTREPUBLISH_PATH ) . '/languages/' );
	}
}