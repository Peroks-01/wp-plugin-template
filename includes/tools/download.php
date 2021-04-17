<?php namespace peroks\plugin_customer\plugin_package;
use PclZip;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * The Plugin admin settings page.
 *
 * @author Per Egil Roksvaag
 */
class Download
{
	use Singleton;

	/**
	 * @var string Admin settings
	 */
	const SECTION_DOWNLOAD        = Main::PREFIX . '_download';
	const OPTION_DOWNLOAD_THEMES  = self::SECTION_DOWNLOAD . '_themes';
	const OPTION_DOWNLOAD_PLUGINS = self::SECTION_DOWNLOAD . '_plugins';

	/**
	 * Constructor.
	 */
	protected function __construct() {

		//  Check system requirements
		if ( class_exists( 'RecursiveIteratorIterator' ) ) {

			//	Enables theme downloads.
			if ( get_option( self::OPTION_DOWNLOAD_THEMES ) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
				add_action( 'admin_init', array( $this, 'download_theme' ) );
			}

			//	Enables plugin downloads
			if ( get_option( self::OPTION_DOWNLOAD_PLUGINS ) ) {
				add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
				add_action( 'admin_init', array( $this, 'download_plugin' ) );
			}

			//	Admin settings
			add_action( 'admin_init', array( $this, 'add_settings' ) );
			add_action( Main::ACTION_ACTIVATE, array( $this, 'activate' ) );
			add_action( Main::ACTION_DELETE, array( $this, 'delete' ), 100 );
		}
	}

	/* -------------------------------------------------------------------------
	 * WordPress admin callbacks.
	 * ---------------------------------------------------------------------- */

	/**
	 * Enqueues a JavaScript for displaying Download links on the Themes page.
	 *
	 * @param string $page The current admin page.
	 */
	public function admin_enqueue_scripts( $page ) {
		if ( 'themes.php' == $page ) {
			$deps = array( 'jquery' );
			$args = array( 'defer' => true );
			Asset::instance()->enqueue_script( 'assets/js/tools/download.min.js', $deps, $args );
		}
	}

	/**
	 * Adds download links to all plugins on the Plugins page.
	 *
	 * @param array $actions An array of plugin action links.
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 * @return array The modified action links.
	 */
	public function plugin_action_links( $actions, $plugin_file ) {
		if ( $parts = explode( '/', $plugin_file ) ) {
			array_push( $actions, vsprintf( '<a href="%s" download>%s</a>', array(
				esc_url( admin_url( sprintf( 'plugins.php?download-plugin=%s', $parts[0] ) ) ),
				esc_html__( 'Download', '[plugin-text-domain]' ),
			) ) );
		}
		return $actions;
	}

	/**
	 * Downloads a theme.
	 */
	public function download_theme() {
		if ( current_user_can( 'switch_themes' ) ) {
			if ( $theme = filter_input( INPUT_GET, 'download-theme', FILTER_SANITIZE_STRING ) ) {
				$zip_path  = $this->get_zip_path( $theme );
				$theme_dir = $this->get_theme_dir( $theme );

				if ( $zip_path && $theme_dir && $this->create_zip( $zip_path, $theme_dir ) ) {
					$this->send_file( $zip_path );
				}
			}
		}
	}

	/**
	 * Downloads a plugin.
	 */
	public function download_plugin() {
		if ( current_user_can( 'activate_plugins' ) ) {
			if ( $plugin = filter_input( INPUT_GET, 'download-plugin', FILTER_SANITIZE_STRING ) ) {
				$zip_path   = $this->get_zip_path( $plugin );
				$plugin_dir = $this->get_plugin_dir( $plugin );

				if ( $zip_path && $plugin_dir && $this->create_zip( $zip_path, $plugin_dir ) ) {
					$this->send_file( $zip_path );
				}
			}
		}
	}

	/* -------------------------------------------------------------------------
	 * Utils
	 * ---------------------------------------------------------------------- */

	/**
	 * Gets the full path to a theme dirctory.
	 *
	 * @param string $theme The theme name
	 * @return string The full path to a theme dirctory
	 */
	protected function get_theme_dir( $theme ) {
		$theme_dir = realpath( get_theme_root() . '/' . $theme );
		return is_readable( $theme_dir ) ? $theme_dir : '';
	}

	/**
	 * Gets the full path to a plugin dirctory.
	 *
	 * @return string The full path to a plugin dirctory
	 */
	protected function get_plugin_dir( $plugin ) {
		$plugin_dir = realpath( WP_PLUGIN_DIR . '/' . $plugin );
		return is_readable( $plugin_dir ) ? $plugin_dir : '';
	}

	/**
	 * Gets the full path to a temp zip file.
	 *
	 * @param string $name The theme of plugin name
	 * @return string The full path to a temp zip file
	 */
	protected function get_zip_path( $name ) {
		if ( trim( $dir = sys_get_temp_dir() ) && is_writable( $dir ) ) {
			return $dir . '/' . $name . '.zip';
		}
		if ( trim( $dir = ini_get( 'upload_tmp_dir' ) ) && is_writable( $dir ) ) {
			return $dir . '/' . $name . '.zip';
		}
		if ( is_array( $dir = wp_upload_dir() ) && ! empty( $dir['path'] ) && is_writable( $dir['path'] ) ) {
			return $dir['path'] . '/' . $name . '.zip';
		}
	}

	protected function get_files( $dir ) {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( realpath( $dir ) ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $iterator as $name => $file ) {
			if ( ! $file->isDir() ) {
				$files[] = str_replace( '\\', '/', $file->getRealPath() );
			}
		}

		return $files ?? array();
	}

	protected function create_zip( $zip_path, $dir ) {
		if ( class_exists( 'ZipArchive' ) ) {
			return $this->create_php_zip( $zip_path, $dir );
		}
		return $this->create_pcl_zip( $zip_path, $dir );
	}

	/**
	 * Creates a zip file from a theme or plugin directory.
	 *
	 * @param string $zip_path Full path to a temp zip file
	 * @param string $dir Full path to a theme or plugin directory
	 * @return bool True if a temporary zip file was sucessfully created, false otherwise.
	 */
	protected function create_php_zip( $zip_path, $dir ) {
		$dir  = realpath( $dir );
		$base = strlen( dirname( $dir ) . '/' );
		$zip  = new ZipArchive();

		if ( true === $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			foreach ( $this->get_files( $dir ) as $file ) {
				$zip->addFile( $file, substr( $file, $base ) );
			}
			return $zip->close();
		}
	}

	protected function create_pcl_zip( $zip_path, $dir ) {
		include_once ABSPATH . 'wp-admin/includes/class-pclzip.php';

		$dir   = realpath( $dir );
		$base  = dirname( $dir ) . '/';
		$files = $this->get_files( $dir );
		$zip   = new PclZip( $zip_path );

		return $zip->create( $files, PCLZIP_OPT_REMOVE_PATH, $base );
	}

	/**
	 * Sends a temp zip file to the browser for download.
	 *
	 * @param string $zip_path Full path to a temp zip file.
	 */
	protected function send_file( $zip_path ) {
		if ( is_resource( $fp = fopen( $zip_path, 'r' ) ) ) {
			$zip_name = urlencode( basename( $zip_path ) );

			header( 'Content-Type: application/zip' );
			header( 'Content-Type: application/download', false );
			header( 'Content-Type: application/octet-stream', false );
			header( 'Content-Disposition: attachment; filename="' . $zip_name . '"' );
			header( 'Content-Description: File Transfer' );
			header( 'Content-Length: ' . filesize( $zip_path ) );
			flush();

			while ( empty( feof( $fp ) ) ) {
				echo fread( $fp, 65536 );
				flush();
			}

			fclose( $fp );
			unlink( $zip_path );
			exit();
		}
	}

	/* -------------------------------------------------------------------------
	 * Admin setting
	 * ---------------------------------------------------------------------- */

	/**
	 * Registers settings, sections and fields.
	 */
	public function add_settings() {

		// Download section
		Admin::instance()->add_section( array(
			'section'     => self::SECTION_DOWNLOAD,
			'page'        => Admin::PAGE,
			'label'       => __( 'Theme and plugin downloads', '[plugin-text-domain]' ),
			'description' => vsprintf( '<p>%s</p>', array(
				esc_html__( 'Check the below checkboxes to allow downloads of themes and/or plugins.', '[plugin-text-domain]' ),
			) ),
		) );

		//	Download themes
		Admin::instance()->add_checkbox( array(
			'option'      => self::OPTION_DOWNLOAD_THEMES,
			'section'     => self::SECTION_DOWNLOAD,
			'page'        => Admin::PAGE,
			'label'       => __( 'Enable theme downloads', '[plugin-text-domain]' ),
			'description' => __( 'Check to enable download of themes from the dashboard.', '[plugin-text-domain]' ),
		) );

		//	Download plugins
		Admin::instance()->add_checkbox( array(
			'option'      => self::OPTION_DOWNLOAD_PLUGINS,
			'section'     => self::SECTION_DOWNLOAD,
			'page'        => Admin::PAGE,
			'label'       => __( 'Enable plugin downloads', '[plugin-text-domain]' ),
			'description' => __( 'Check to enable download of plugins from the dashboard.', '[plugin-text-domain]' ),
		) );
	}

	/**
	 * Sets plugin default setting on activation.
	 */
	public function activate() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
			if ( is_null( get_option( self::OPTION_DOWNLOAD_THEMES, null ) ) ) {
				add_option( self::OPTION_DOWNLOAD_THEMES, 0 );
			}
			if ( is_null( get_option( self::OPTION_DOWNLOAD_PLUGINS, null ) ) ) {
				add_option( self::OPTION_DOWNLOAD_PLUGINS, 0 );
			}
		}
	}

	/**
	 * Removes settings on plugin deletion.
	 */
	public function delete() {
		if ( is_admin() && current_user_can( 'delete_plugins' ) ) {
			if ( get_option( self::OPTION_DOWNLOAD_PLUGINS ) ) {
				delete_option( self::OPTION_DOWNLOAD_THEMES );
				delete_option( self::OPTION_DOWNLOAD_PLUGINS );
			}
		}
	}
}