<?php namespace peroks\plugin_customer\plugin_package;
/**
 * Plugin setup.
 *
 * @since 1.0.0
 * @author Per Egil Roksvaag
 */
class Setup {
	use Singleton;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );

		if ( empty( is_admin() ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_styles' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		}
	}

	/* -------------------------------------------------------------------------
	 * Plugin setup
	 * ---------------------------------------------------------------------- */

	/**
	 * Loads the translated strings (if any).
	 *
	 * @since 1.0.0
	 */
	public function init() {
		$base = dirname( plugin_basename( Main::FILE ) ) . '/languages';
		load_plugin_textdomain( Main::DOMAIN, false, $base );
	}

	/**
	 * Register widgets.
	 *
	 * @since 1.0.0
	 */
	public function widgets_init() {
	}

	/**
	 * Enqueues styles.
	 *
	 * @since 1.0.0
	 */
	public function wp_enqueue_styles() {
		$args = array( 'inline' => true );
		Asset::instance()->enqueue_style( 'assets/css/this-plugin-name.min.css', array(), $args );
	}

	/**
	 * Enqueues scripts.
	 *
	 * @since 1.0.0
	 */
	public function wp_enqueue_scripts() {
		$args = array( 'async' => true );
		Asset::instance()->enqueue_script( 'assets/js/this-plugin-name.min.js', array(), $args );
	}
}