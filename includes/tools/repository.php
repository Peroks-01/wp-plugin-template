<?php namespace peroks\plugin_customer\plugin_package;
/**
 * Enables automated plugin updates from a GitHub repopistory.
 *
 * @see https://www.smashingmagazine.com/2015/08/deploy-wordpress-plugins-with-github-using-transients/
 * @author Per Egil Roksvaag
 */
class Repository
{
	use Singleton;

	/**
	 * @var string Admin settings
	 */
	const SECTION_REPOSITORY      = Main::PREFIX . '_repository';
	const OPTION_REPOSITORY_URL   = self::SECTION_REPOSITORY . '_url';
	const OPTION_REPOSITORY_TOKEN = self::SECTION_REPOSITORY . '_token';

	/**
	 * @var object The latest release from the GitHub repository.
	 */
	protected $release;

	/**
	 * Constructor.
	 */
	protected function __construct() {

		//	Activates automated plugin update
		add_action( 'init', array( $this, 'init' ) );

		//	Admin settings
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( Main::ACTION_ACTIVATE, array( $this, 'activate' ) );
		add_action( Main::ACTION_DELETE, array( $this, 'delete' ) );
	}

	/**
	 * Activates automated plugin update.
	 */
	public function init() {
		if ( get_option( self::OPTION_REPOSITORY_URL ) ) {
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_plugins' ) );
			add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3);
			add_filter( 'upgrader_pre_download', array( $this, 'upgrader_pre_download' ), 10, 4 );
			add_filter( 'upgrader_post_install', array( $this, 'upgrader_post_install' ), 10, 3 );
		}
	}

	/* -------------------------------------------------------------------------
	 * WordPress callbacks
	 * ---------------------------------------------------------------------- */

	/**
	 * Checks if a newer version of this plugin is avaialble on GitHub.
	 *
	 * @param object $transient Contains plugin update states.
	 * @return object the modified object.
	 */
	public function update_plugins( $transient ) {

		//	Did WordPress check for updates?
		if ( property_exists( $transient, 'checked' ) && $transient->checked ) {
			$release = $this->get_latest_release();

			//	Do we have a valid relase object?
			if ( empty( is_wp_error( $release ) ) && is_object( $release ) ) {
				$basename = plugin_basename( Main::FILE );
				$version  = trim( $release->tag_name, 'v' );

				//	Is a newer version available on GitHub?
				if ( version_compare( $version, Main::VERSION, '>' ) ) {
					$plugin = (object) array(
						'url'         => get_option( self::OPTION_REPOSITORY_URL ),
						'slug'        => current( explode( '/', $basename ) ),
						'package'     => $release->zipball_url,
						'new_version' => $version,
					);

					//	Set plugin update info
					$transient->response[ $basename ] = $plugin;
				}
			}
		}

		return $transient;
	}

	/**
	 * Displays plugin version details.
	 *
	 * @param array|false|object $result The result object or array
	 * @param string $action The type of information being requested from the Plugin Installation API.
	 * @param object $args Plugin API arguments.
	 * @return false|object Plugin information
	 */
	public function plugins_api( $result, $action, $args ) {
		$base = plugin_basename( Main::FILE );
		$slug = current( explode( '/', $base ) );

		if ( property_exists( $args, 'slug' ) && $args->slug == $slug ) {
			$data    = get_plugin_data( Main::FILE );
			$release = $this->get_latest_release();

			return (object) array(
				'name'              => $data['Name'],
				'slug'              => $base,
				'version'           => trim( $release->tag_name, 'v' ),
				'author'            => $data['AuthorName'],
				'author_profile'    => $data['AuthorURI'],
				'last_updated'      => $release->published_at,
				'homepage'          => $data['PluginURI'],
				'short_description' => $data['Description'],
				'sections'          => array(
					'Description' => $data['Description'],
					'Updates'     => $release->body,
				),
				'download_link' => $release->zipball_url,
			);
		}
		return $result;
	}

	/**
	 * Adds authorisation headers.
	 *
	 * @param bool $reply Whether to bail without returning the package. Default false.
	 * @param string $package The package file name.
	 * @param WP_Upgrader $upgrader The WP_Upgrader instance.
	 * @param array $hook_extra Extra arguments passed to hooked filters.
	 */
	public function upgrader_pre_download ( $reply, $package, $upgrader, $hook_extra ) {
		if ( $token = get_option( self::OPTION_REPOSITORY_TOKEN ) ) {
			$release = $this->get_latest_release();
			$base    = plugin_basename( Main::FILE );

			if ( in_array( $base, $hook_extra ) && $package == $release->zipball_url ) {
				add_filter( 'http_request_args', function ( $args, $url ) use ( $release, $token ) {
					if ( isset( $args['filename'] ) && $url == $release->zipball_url ) {
						$args['headers']['Authorization'] = "token {$token}";
					}
					return $args;
				}, 10, 2 );
			}
		}

		return $reply;
	}

	/**
	 * Install and activate the updated plugin.
	 *
	 * @param bool $response Installation response.
	 * @param array $hook_extra Extra arguments passed to hooked filters.
	 * @param array $result Installation result data.
	 * @return
	 */
	public function upgrader_post_install( $response, $hook_extra, $result ) {
		global $wp_filesystem;

		$plugin_dir = Main::instance()->plugin_path(); // Our plugin directory
		$wp_filesystem->move( $result['destination'], $plugin_dir ); // Move files to the plugin dir
		$result['destination'] = $plugin_dir; // Set the destination for the rest of the stack

		//	Reactivate if the plugin
		activate_plugin( plugin_basename( Main::FILE ) );
		return $result;
	}

	/* -------------------------------------------------------------------------
	 * Utils
	 * ---------------------------------------------------------------------- */

	/**
	 * Gets the latest release of this plugin on GitHub.
	 *
	 * @return bool|object|WP_Error The latest release of this plugin on GitHub.
	 */
	public function get_latest_release() {
		if ( is_null( $this->release ) ) {
			$this->release = false;

			$repo = parse_url( get_option( self::OPTION_REPOSITORY_URL ) );
			$host = trim( $repo['host'] ?? null );
			$path = trim( $repo['path'] ?? null, '/' );
			$args = array();

			if ( 'github.com' == $host && strpos( $path, '/' ) ) {
				if ( $token = get_option( self::OPTION_REPOSITORY_TOKEN ) ) {
					$args['headers']['Authorization'] = "token {$token}";
				}

				$request  = "https://api.github.com/repos/{$path}/releases";
				$response = wp_remote_get( $request, $args );

				if ( is_wp_error( $response ) ) {
					return $response;
				}

				$releases = json_decode( wp_remote_retrieve_body( $response ) );
				$releases = array_filter( (array) $releases, function ( $release ) {
					return isset( $release->draft ) && false === $release->draft;
				} );

				$this->release = current( $releases );
			}
		}
		return $this->release;
	}

	/* -------------------------------------------------------------------------
	 * Admin settings
	 * ---------------------------------------------------------------------- */

	/**
	 * Registers settings, sections and fields.
	 */
	public function admin_init() {

		//	Repository section
		Admin::instance()->add_section( array(
			'section' => self::SECTION_REPOSITORY,
			'page'    => Admin::PAGE,
			'label'   => __( 'Automated plugin update from a GitHub repository', '[plugin-text-domain]' ),
		) );

		//	Repository url
		Admin::instance()->add_text( array(
			'option'      => self::OPTION_REPOSITORY_URL,
			'section'     => self::SECTION_REPOSITORY,
			'page'        => Admin::PAGE,
			'label'       => __( 'GitHub Repository URL', '[plugin-text-domain]' ),
			'description' => __( 'Enter the URL to the plugin repository on GitHub.', '[plugin-text-domain]' ),
		) );

		//	Repository token
		Admin::instance()->add_text( array(
			'option'      => self::OPTION_REPOSITORY_TOKEN,
			'section'     => self::SECTION_REPOSITORY,
			'page'        => Admin::PAGE,
			'label'       => __( 'GitHub access Token', '[plugin-text-domain]' ),
			'description' => __( 'Enter an access token for the plugin repository on GitHub.', '[plugin-text-domain]' ),
		) );
	}

	/**
	 * Sets plugin default settings on activation.
	 */
	public function activate() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
			if ( is_null( get_option( self::OPTION_REPOSITORY_URL, null ) ) ) {
				$data  = (object) get_plugin_data( Main::FILE );
				$host  = parse_url( $data->PluginURI, PHP_URL_HOST );
				$value = 'github.com' == $host ? $data->PluginURI : '';
				add_option( self::OPTION_REPOSITORY_URL, $value );
			}
			if ( is_null( get_option( self::OPTION_REPOSITORY_TOKEN, null ) ) ) {
				add_option( self::OPTION_REPOSITORY_TOKEN, '' );
			}
		}
	}

	/**
	 * Removes settings on plugin deletion.
	 */
	public function delete() {
		if ( is_admin() && current_user_can( 'delete_plugins' ) ) {
			if ( get_option( Admin::OPTION_DELETE_SETTINGS ) ) {
				delete_option( self::OPTION_REPOSITORY_URL );
				delete_option( self::OPTION_REPOSITORY_TOKEN );
			}
		}
	}
}