<?php namespace peroks\plugin_customer\plugin_package;
/**
 * Plugin asset handler.
 *
 * Enqueues styles and scripts, enables stylesheet inlining and JavaScript defer / async.
 *
 * @since 1.0.0
 * @author Per Egil Roksvaag
 */
class Asset {
	use Singleton;

	/**
	 * @var string The base directories for css and js assets.
	 */
	const DIR_STYLES  = 'assets/css';
	const DIR_SCRIPTS = 'assets/js';

	/**
	 * @var string The plugin global filter hooks.
	 */
	const FILTER_ENQUEUE_STYLE  = Main::PREFIX . '_enqueue_style';
	const FILTER_ENQUEUE_SCRIPT = Main::PREFIX . '_enqueue_script';

	/**
	 * @var string Admin settings
	 */
	const SECTION_ASSET             = Main::PREFIX . '_asset';
	const OPTION_ASSET_STYLE_INLINE = self::SECTION_ASSET . '_style_inline';
	const OPTION_ASSET_SCRIPT_DEFER = self::SECTION_ASSET . '_script_defer';

	/**
	 * @var array Styles to inline
	 */
	protected $inline = array();

	/**
	 * @var array Scripts to defer
	 */
	protected $defer = array();

	/**
	 * @var array Scripts to async and defer
	 */
	protected $async = array();

	/**
	 * Constructor.
	 */
	protected function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( Main::ACTION_ACTIVATE, array( $this, 'activate' ) );
			add_action( Main::ACTION_DELETE, array( $this, 'delete' ) );
		} else {
			add_action( 'init', array( $this, 'init' ) );
		}
	}

	/**
	 * Inline style and defer scripts.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		//	Inline styles
		if ( get_option( static::OPTION_ASSET_STYLE_INLINE ) ) {
			add_filter( static::FILTER_ENQUEUE_STYLE, array( $this, 'inline_styles' ), 10, 5 );
			add_action( 'wp_print_styles', array( $this, 'wp_print_styles' ), 50 );
		}

		//	Defer or async scripts
		if ( get_option( static::OPTION_ASSET_SCRIPT_DEFER ) ) {
			add_filter( static::FILTER_ENQUEUE_SCRIPT, array( $this, 'defer_scripts' ), 10, 5 );
			add_filter( 'script_loader_tag', array( $this, 'script_loader_tag' ), 5, 3 );
		}
	}

	/* -------------------------------------------------------------------------
	 * Inline styles
	 * ---------------------------------------------------------------------- */

	/**
	 * Registers styles for inlining.
	 *
	 * @since 1.0.0
	 * @see Main::enqueue_style()
	 *
	 * @param string $handle A stylesheet handle.
	 * @param string $path The stylesheet file system path.
	 * @param string $source The stylesheet URL.
	 * @param array $deps An array of registered stylesheet handles this stylesheet depends on.
	 * @param array $args Optional additional arguments: media, inline, etc.
	 * @return string The stylesheet handle
	 */
	public function inline_styles( $handle, $path, $source, $deps, $args ) {
		if ( $args['inline'] ?? false ) {
			$this->inline[ $handle] = $path;
		}
		return $handle;
	}

	/**
	 * Inlines styles in html head.
	 */
	public function wp_print_styles() {
		foreach ( $this->inline as $handle => $path ) {
			if ( wp_style_is( $handle, 'enqueued' ) && file_exists( $path ) ) {
				if ( $css = file_get_contents( $path ) ) {
					wp_styles()->registered[ $handle ]->src = false;
					wp_add_inline_style( $handle, $css );
				}
			}
		}
	}

	/* -------------------------------------------------------------------------
	 * Defer or async scripts
	 * ---------------------------------------------------------------------- */

	/**
	 * Registers styles for defer or async.
	 *
	 * @since 1.0.0
	 * @see Main::enqueue_script()
	 *
	 * @param string $handle A JavaScript handle.
	 * @param string $path The JavaScript file system path.
	 * @param string $source The JavaScript URL.
	 * @param array $deps An array of registered JavaScript handles this JavaScript depends on.
	 * @param array $args Optional additional arguments: footer, defer, async, etc.
	 * @return string The JavaScript handle
	 */
	public function defer_scripts( $handle, $path, $source, $deps, $args ) {
		if ( $args['async'] ?? false ) {
			$this->async[ $handle] = $path;
		} elseif ( $args['defer'] ?? false ) {
			$this->defer[ $handle] = $path;
		}
		return $handle;
	}

	/**
	 * Renders deferred and async scripts.
	 *
	 * @param string $tag The script tag for the enqueued script.
	 * @param string $handle The script's registered handle.
	 * @param string $src The script's source URL.
	 * @return string The modified tag.
	 */
	public function script_loader_tag( $tag, $handle, $src ) {
		if ( array_key_exists( $handle, $this->async ) && is_bool( strpos( ' async', $tag ) ) ) {
			return str_replace( ' src=', ' async defer src=', $tag );
		}
		if ( array_key_exists( $handle, $this->defer ) && is_bool( strpos( ' defer', $tag ) ) ) {
			return str_replace( ' src=', ' defer src=', $tag );
		}
		return $tag;
	}

	/* -------------------------------------------------------------------------
	 * Asset handlers
	 * ---------------------------------------------------------------------- */

	/**
	 * Enqueues a stylesheet.
	 *
	 * @since 1.0.0
	 * @param string $path The local path to the asset relative to this plugin's root directory.
	 * @param array $deps An array of registered stylesheet handles this stylesheet depends on.
	 * @param array $args Optional additional arguments: media, inline, etc.
	 * @return bool|string The generated asset handle if successful, or false otherwise.
	 */
	public function enqueue_style( $path, $deps = array(), $args = array() ) {
		$path   = trim( trim( $path ), '/' );
		$base   = trim( static::DIR_STYLES, '/' );
		$debug  = SCRIPT_DEBUG || in_array( 'administrator', wp_get_current_user()->roles );
		$source = $debug ? preg_replace( '/[.]min[.](js|css)$/', '.$1', $path ) : $path;
		$handle = preg_replace( "!^{$base}/(.+?)([.]min)?[.](js|css)$!", '$1', $source );
		$handle = preg_replace( '![/._]!', '-', Main::PREFIX . '-' . $handle );

		wp_enqueue_style( $handle, Main::plugin_url( $source ), $deps, Main::VERSION, $args['media'] ?? 'all' );
		return apply_filters( static::FILTER_ENQUEUE_STYLE, $handle, Main::plugin_path( $path ), $source, $deps, $args );
	}

	/**
	 * Enqueues a script.
	 *
	 * @since 1.0.0
	 * @param string $path The local path to the asset relative to this plugin's root directory.
	 * @param array $deps An array of registered script handles this script depends on.
	 * @param array $args Optional additional arguments: footer, defer, async, etc.
	 * @return bool|string The generated asset handle if successful, or false otherwise.
	 */
	public function enqueue_script( $path, $deps = array(), $args = array() ) {
		$path   = trim( trim( $path ), '/' );
		$base   = trim( static::DIR_SCRIPTS, '/' );
		$debug  = SCRIPT_DEBUG || in_array( 'administrator', wp_get_current_user()->roles );
		$source = $debug ? preg_replace( '/[.]min[.](js|css)$/', '.$1', $path ) : $path;
		$handle = preg_replace( "!^{$base}/(.+?)([.]min)?[.](js|css)$!", '$1', $source );
		$handle = preg_replace( '![/._]!', '-', Main::PREFIX . '-' . $handle );

		wp_enqueue_script( $handle, Main::plugin_url( $source ), $deps, Main::VERSION, $args['footer'] ?? true );
		return apply_filters( static::FILTER_ENQUEUE_SCRIPT, $handle, Main::plugin_path( $path ), $source, $deps, $args );
	}

	/* -------------------------------------------------------------------------
	 * Admin settings
	 * ---------------------------------------------------------------------- */

	/**
	 * Registers settings, sections and fields.
	 *
	 * @since 1.0.0
	 */
	public function admin_init() {

		// Assets section
		Admin::instance()->add_section( array(
			'section'     => static::SECTION_ASSET,
			'page'        => Admin::PAGE,
			'label'       => __( 'Asset settings', 'plugin-text-domain' ),
			'description' => vsprintf( '<p>%s</p>', array(
				esc_html__( 'Check the below checkboxes to improve asset performance.', 'plugin-text-domain' ),
			) ),
		) );

		//	Inline stylesheets
		Admin::instance()->add_checkbox( array(
			'option'      => static::OPTION_ASSET_STYLE_INLINE,
			'section'     => static::SECTION_ASSET,
			'page'        => Admin::PAGE,
			'label'       => __( 'Inline stylesheets', 'plugin-text-domain' ),
			'description' => __( 'Check to enable stylesheet inlining.', 'plugin-text-domain' ),
		) );

		//	Defer JavaScript
		Admin::instance()->add_checkbox( array(
			'option'      => static::OPTION_ASSET_SCRIPT_DEFER,
			'section'     => static::SECTION_ASSET,
			'page'        => Admin::PAGE,
			'label'       => __( 'Defer JavaScript', 'plugin-text-domain' ),
			'description' => __( 'Check to enable deferred or async JavasScript.', 'plugin-text-domain' ),
		) );
	}

	/**
	 * Sets plugin default setting on activation.
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
			if ( is_null( get_option( static::OPTION_ASSET_STYLE_INLINE, null ) ) ) {
				add_option( static::OPTION_ASSET_STYLE_INLINE, 1 );
			}
			if ( is_null( get_option( static::OPTION_ASSET_SCRIPT_DEFER, null ) ) ) {
				add_option( static::OPTION_ASSET_SCRIPT_DEFER, 1 );
			}
		}
	}

	/**
	 * Removes settings on plugin deletion.
	 *
	 * @since 1.0.0
	 */
	public function delete() {
		if ( is_admin() && current_user_can( 'delete_plugins' ) ) {
			delete_option( static::OPTION_ASSET_STYLE_INLINE );
			delete_option( static::OPTION_ASSET_SCRIPT_DEFER );
		}
	}
}