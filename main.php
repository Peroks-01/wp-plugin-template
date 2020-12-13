<?php namespace peroks\plugin_customer\plugin_package;
/*
 * Plugin Name:       This Plugin Name
 * Plugin URI:        https://github.com/Peroks-01/wp-plugin-template
 * Description:       This plugin description
 *
 * Text Domain:       plugin-text-domain
 * Domain Path:       /languages
 *
 * Author:            Per Egil Roksvaag
 * Author URI:        https://codeable.io/developers/per-egil-roksvaag/
 *
 * Version:           1.0.0
 * Stable tag:        1.0.0
 * Requires at least: 5.0
 * Tested up to:      5.6
 * Requires PHP:      7.0
 */

/**
 * The This Plugin Name plugin main class.
 *
 * @since 1.0.0
 * @author Per Egil Roksvaag
 * @version 1.0.0
 */
class Main {
	/**
	 * @var string The plugin version.
	 */
	const VERSION = '1.0.0';

	/**
	 * @var string The plugin file.
	 */
	const FILE = __FILE__;

	/**
	 * @var string The plugin name.
	 */
	const NAME = 'This Plugin Name';

	/**
	 * @var string The plugin text domain (hyphen).
	 */
	const DOMAIN = 'plugin-text-domain';

	/**
	 * @var string The plugin prefix (underscore).
	 */
	const PREFIX = 'plugin_prefix';

	/**
	 * @var string The system environment requirements.
	 */
	const REQUIRE_PHP  = '7.0';	//	Required PHP version
	const REQUIRE_WP   = '5.0';	//	Required WordPress version
	const REQUIRE_WOO  = '0';	//	Required WooCommerce version
	const REQUIRE_LMS  = '0';	//	Required LearnDash LMS version
	const REQUIRE_WPML = '0';	//	Required WordPress Multilingual version

	/**
	 * @var string The plugin global options.
	 */
	const OPTION_VERSION = self::PREFIX . '_version';

	/**
	 * @var string The plugin global action hooks.
	 */
	const ACTION_LOADED     = self::PREFIX . '_loaded';
	const ACTION_UPDATE     = self::PREFIX . '_update';
	const ACTION_ACTIVATE   = self::PREFIX . '_activate';
	const ACTION_DEACTIVATE = self::PREFIX . '_deactivate';
	const ACTION_DELETE     = self::PREFIX . '_delete';

	/**
	 * @var string The plugin global filter hooks.
	 */
	const FILTER_CLASS_CREATE  = self::PREFIX . '_class_create';
	const FILTER_CLASS_CREATED = self::PREFIX . '_class_created';
	const FILTER_CLASS_PATH    = self::PREFIX . '_class_path';
	const FILTER_SYSTEM_CHECK  = self::PREFIX . '_system_check';
	const FILTER_PLUGIN_PATH   = self::PREFIX . '_plugin_path';
	const FILTER_PLUGIN_URL    = self::PREFIX . '_plugin_url';

	/**
	 * @var object The class singleton.
	 */
	protected static $_instance;

	/**
	 * @return object The class singleton.
	 */
	public static function instance() {
		if ( is_null( static::$_instance ) && static::check() ) {
			static::$_instance = false;
			$class             = apply_filters( static::FILTER_CLASS_CREATE, static::class );
			static::$_instance = apply_filters( static::FILTER_CLASS_CREATED, new $class(), $class, static::class );
			do_action( static::ACTION_LOADED, static::$_instance );
		}
		return static::$_instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->autoload();
		$this->run();
		$this->update();
	}

	/**
	 * Registers autoloading.
	 *
	 * @since 1.0.0
	 */
	protected function autoload() {
		$classes = apply_filters( static::FILTER_CLASS_PATH, array(
			__NAMESPACE__ . '\Singleton' => static::plugin_path( 'includes/singleton.php' ),
			__NAMESPACE__ . '\Setup'     => static::plugin_path( 'includes/setup.php' ),
			__NAMESPACE__ . '\Asset'     => static::plugin_path( 'includes/asset.php' ),
			__NAMESPACE__ . '\Admin'     => static::plugin_path( 'includes/admin.php' ),
		) );

		spl_autoload_register( function ( $name ) use ( $classes ) {
			if ( array_key_exists( $name, $classes ) ) {
				include $classes[ $name ];
			}
		} );
	}

	/**
	 * Loads and runs the plugin classes.
	 *
	 * @since 1.0.0
	 */
	protected function run() {
		Setup::instance();
		Asset::instance();

		if ( is_admin() ) {
			Admin::instance();
		}
	}

	/* =========================================================================
	 * Everything below this line is just plugin management and some very
	 * basic path and url handlers. You'll find the real action in the classes
	 * loaded above.
	 * ====================================================================== */

	/* -------------------------------------------------------------------------
	 * Check system requirements
	 * ---------------------------------------------------------------------- */

	/**
	 * Checks if the system environment is supported.
	 *
	 * @since 1.0.0
	 * @return bool True if the system environment is supported, false otherwise.
	 */
	protected static function check() {
		if ( defined( 'static::REQUIRE_PHP' ) && static::REQUIRE_PHP ) {
			if ( version_compare( PHP_VERSION, static::REQUIRE_PHP ) < 0 ) {
				$error = static::error( 'PHP', static::REQUIRE_PHP );
			}
		}

		if ( defined( 'static::REQUIRE_WP' ) && static::REQUIRE_WP ) {
			if ( version_compare( get_bloginfo( 'version' ), static::REQUIRE_WP ) < 0 ) {
				$error = static::error( 'WordPress', static::REQUIRE_WP );
			}
		}

		if ( defined( 'static::REQUIRE_WOO' ) && static::REQUIRE_WOO ) {
			global $woocommerce;

			if ( empty( is_a( $woocommerce, 'WooCommerce' ) ) || version_compare( $woocommerce->version, static::REQUIRE_WOO ) < 0 ) {
				$error = static::error( 'WooCommerce', static::REQUIRE_WOO );
			}
		}

		if ( defined( 'static::REQUIRE_LMS' ) && static::REQUIRE_LMS ) {
			if ( empty( defined( '\LEARNDASH_VERSION' ) ) || version_compare( \LEARNDASH_VERSION, static::REQUIRE_LMS ) < 0 ) {
				$error = static::error( 'LearnDash LMS', static::REQUIRE_LMS );
			}
		}

		if ( defined( 'static::REQUIRE_WPML' ) && static::REQUIRE_WPML ) {
			if ( empty( defined( '\ICL_SITEPRESS_VERSION' ) ) || version_compare( \ICL_SITEPRESS_VERSION, static::REQUIRE_WPML ) < 0 ) {
				$error = static::error( 'WPML (WordPress Multilingual)', static::REQUIRE_WPML );
			}
		}

		return empty( $error );
	}

	/**
	 * Logs and outputs missing system requirements.
	 *
	 * @since 1.0.0
	 * @param string $require The name of the required component.
	 * @param string $version The minimum version required.
	 * @return bool True, except when overridden by filter.
	 */
	protected static function error( $require, $version ) {
		if ( apply_filters( static::FILTER_SYSTEM_CHECK, true, $require, $version ) ) {
			if ( is_admin() ) {

				//	Error message
				$message = __( '%1$s requires %2$s version %3$s or higher, the plugin is NOT RUNNING.', 'plugin-text-domain' );
				$message = sprintf( $message, static::NAME, $require, $version );

				//	Admin notice output
				$notice = function () use ( $message ) {
					vprintf( '<div class="notice notice-error"><p><strong>%s: </strong>%s</p></div>', array(
						esc_html__( 'Error', 'plugin-text-domain' ),
						esc_html( $message ),
					) );
				};

				//	Write error message to log and create admin notice.
				error_log( $message );
				add_action( 'admin_notices', $notice );
			}
			return true;
		}
		return false;
	}

	/* -------------------------------------------------------------------------
	 * Update, activate, deactivate and uninstall plugin.
	 * ---------------------------------------------------------------------- */

	/**
	 * Checks if the plugin was updated.
	 *
	 * Notifies plugin classes to update and flushes rewrite rules.
	 *
	 * @since 1.0.0
	 * @return bool True if the plugin was updated, false otherwise.
	 */
	protected function update() {
		$version = get_option( static::OPTION_VERSION );

		if ( static::VERSION !== $version ) {
			do_action( static::ACTION_UPDATE, $this, static::VERSION, $version );
			update_option( static::OPTION_VERSION, static::VERSION );

			add_action( 'wp_loaded', 'flush_rewrite_rules' );
			add_action( 'admin_notices', function () {
				$notice = __( '%s has been updated to version %s', 'plugin-text-domain' );
				$notice = sprintf( $notice, static::NAME, static::VERSION );
				printf( '<div class="notice notice-success is-dismissible"><p>%s.</p></div>', esc_html( $notice ) );
				error_log( $notice );
			} );
			return true;
		}
		return false;
	}

	/**
	 * Registers plugin activation, deactivation and uninstall hooks.
	 *
	 * @since 1.0.0
	 */
	public static function register() {
		if ( is_admin() ) {
			register_activation_hook( static::FILE, array( static::class, 'activate' ) );
			register_deactivation_hook( static::FILE, array( static::class, 'deactivate' ) );
			register_uninstall_hook( static::FILE, array( static::class, 'uninstall' ) );
		}
	}

	/**
	 * Runs when the plugin is activated.
	 *
	 * Notifies plugin classes to activate and flushes rewrite rules.
	 * This hook is called AFTER all other hooks (except 'shutdown').
	 * WP redirects the request immediately after this hook, so we can't register any hooks to be executed later.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
			do_action( static::ACTION_ACTIVATE, static::instance(), static::VERSION, get_option( static::OPTION_VERSION ) );
			update_option( static::OPTION_VERSION, static::VERSION );
			$message = __( '%s version %s has been activated', 'plugin-text-domain' );
			error_log( sprintf( $message, static::NAME, static::VERSION ) );
			flush_rewrite_rules();
		}
	}

	/**
	 * Runs when the plugin is deactivated.
	 *
	 * Notifies plugin classes to deactivate and flushes rewrite rules.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
			do_action( static::ACTION_DEACTIVATE, static::instance(), static::VERSION, get_option( static::OPTION_VERSION ) );
			$message = __( '%s version %s has been deactivated', 'plugin-text-domain' );
			error_log( sprintf( $message, static::NAME, static::VERSION ) );
			flush_rewrite_rules();
		}
	}

	/**
	 * Runs when the plugin is deleted.
	 *
	 * Notifies plugin classes to delete all plugin settings and flushes rewrite rules.
	 *
	 * @since 1.0.0
	 */
	public static function uninstall() {
		if ( is_admin() && current_user_can( 'delete_plugins' ) ) {
			do_action( static::ACTION_DELETE, static::instance(), static::VERSION, get_option( static::OPTION_VERSION ) );
			delete_option( static::OPTION_VERSION );
			$message = __( '%s version %s has been removed', 'plugin-text-domain' );
			error_log( sprintf( $message, static::NAME, static::VERSION ) );
			flush_rewrite_rules();
		}
	}

	/* -------------------------------------------------------------------------
	 * Basic path and url handlers.
	 * ---------------------------------------------------------------------- */

	/**
	 * Gets a full filesystem path from a local path.
	 *
	 * @since 1.0.0
	 * @param string $path The local path relative to this plugin's root directory.
	 * @return string The full filesystem path.
	 */
	public static function plugin_path( $path = '' ) {
		$path = ltrim( trim( $path ), '/' );
		$full = plugin_dir_path( static::FILE ) . $path;
		return apply_filters( static::FILTER_PLUGIN_PATH, $full, $path );
	}

	/**
	 * Gets the URL to the given local path.
	 *
	 * @since 1.0.0
	 * @param string $path The local path relative to this plugin's root directory.
	 * @return string The URL.
	 */
	public static function plugin_url( $path = '' ) {
		$path = ltrim( trim( $path ), '/' );
		$url  = plugins_url( $path, static::FILE );
		return apply_filters( static::FILTER_PLUGIN_URL, $url, $path );
	}

	/* -------------------------------------------------------------------------
	 * Debugging
	 * ---------------------------------------------------------------------- */

	/**
	 * Writes an entry to the php log and adds context information.
	 *
	 * @since 1.0.0
	 * @param string $log Log entry.
	 */
	public static function log( $log = '' ) {
		$caller = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		$file   = empty( $caller[0]['file'] ) ? '' : ' in ' . $caller[0]['file'];
		$line   = empty( $caller[0]['line'] ) ? '' : ' on line ' . $caller[0]['line'];
		$type   = empty( $caller[1]['function'] ) ? '#Debug: ' : '#' . $caller[1]['function'] . ': ';
		$entry  = str_replace( "\n", ' ', var_export( $log, true ) );
		error_log( $type . gettype( $log ) . ': ' . $entry . $file . $line );
	}

	/**
	 * Writes the backtrace to the php log.
	 *
	 * @since 1.0.0
	 */
	public static function trace() {
		static::log( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) );
	}
}

//	Registers and runs the main plugin class
if ( defined( 'ABSPATH' ) ) {
	Main::register();
	add_action( 'plugins_loaded', array( Main::class, 'instance' ), 5 );
}