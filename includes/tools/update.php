<?php namespace peroks\plugin_customer\plugin_package;
/**
 * Enables automated plugin updated from GitHub.
 *
 * @see https://www.smashingmagazine.com/2015/08/deploy-wordpress-plugins-with-github-using-transients/
 * @author Per Egil Roksvaag
 */
class Update
{
	use Singleton;

	/**
	 * @var string Admin settings
	 */
	const SECTION_UPDATE      = Main::PREFIX . '_update';
	const OPTION_UPDATE_TOKEN = self::SECTION_UPDATE . '_token';

	/**
	 * @var string The class filter hooks.
	 */
	const FILTER_UPDATE_SOMETHING = Main::PREFIX . '_update_something';

	/**
	 * @var string A GitHub Personal access token
	 */
	protected $token;

	/**
	 * @var object The latest release from the GitHub repository.
	 */
	protected $release;

	/**
	 * @var bool True, if If the plugin is active or false otherwise.
	 */
	protected $active;

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
		if ( $this->token = get_option( self::OPTION_UPDATE_TOKEN ) ) {
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_plugins' ) );
			add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3);
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
					$data   = get_plugin_data( Main::FILE );
					$plugin = (object) array(
						'url'         => $data['PluginURI'],
						'slug'        => current( explode( '/', $basename ) ),
						'package'     => add_query_arg( 'access_token', $this->token, $release->zipball_url ),
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
				'download_link' => add_query_arg( 'access_token', $this->token, $release->zipball_url ),
			);
		}
		return $result;
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

		$base       = plugin_basename( Main::FILE );
		$plugin_dir = Main::instance()->plugin_path(); // Our plugin directory
		$wp_filesystem->move( $result['destination'], $plugin_dir ); // Move files to the plugin dir
		$result['destination'] = $plugin_dir; // Set the destination for the rest of the stack

		//	Reactivate if the plugin was active
		if ( $this->active ) {
			activate_plugin( $base );
		}

		return $result;
	}

	/* -------------------------------------------------------------------------
	 * Utils
	 * ---------------------------------------------------------------------- */

	/**
	 * Gets the latest release of this plugin on GitHub.
	 *
	 * @return object The latest release of this plugin on GitHub.
	 */
	public function get_latest_release() {
		if ( is_null( $this->release ) ) {
			$data = get_plugin_data( Main::FILE );
			$base = plugin_basename( Main::FILE );

			$url  = parse_url( $data['PluginURI'] );
			$host = trim( $url['host'] ?? null );
			$path = trim( $url['path'] ?? null, '/' );

			if ( 'github.com' == $host && strpos( $path, '/' ) ) {
				$request  = "https://api.github.com/repos/{$path}/releases";
				$request  = add_query_arg( 'access_token', $this->token, $request );
				$response = wp_remote_get( $request );

				if ( is_wp_error( $response ) ) {
					return $response;
				}

				$releases = json_decode( wp_remote_retrieve_body( $response ) );
				$releases = array_filter( (array) $releases, function ( $release ) {
					return isset( $release->draft ) && false === $release->draft;
				} );

				$this->release = current( $releases );
				$this->active  = is_plugin_active( $base );
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

		// Update section
		Admin::instance()->add_section( array(
			'section' => self::SECTION_UPDATE,
			'page'    => Admin::PAGE,
			'label'   => __( 'Update plugin from GitHub', '[plugin-text-domain]' ),
		) );

		//	New: Dimension group IDs
		Admin::instance()->add_text( array(
			'option'      => self::OPTION_UPDATE_TOKEN,
			'section'     => self::SECTION_UPDATE,
			'page'        => Admin::PAGE,
			'label'       => __( 'Update access Token', '[plugin-text-domain]' ),
			'description' => __( 'Enter an access token to get automated plugin updates.', '[plugin-text-domain]' ),
		) );
	}

	/**
	 * Sets plugin default settings on activation.
	 */
	public function activate() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
			if ( is_null( get_option( self::OPTION_UPDATE_TOKEN, null ) ) ) {
				add_option( self::OPTION_UPDATE_TOKEN, '' );
			}
		}
	}

	/**
	 * Removes settings on plugin deletion.
	 */
	public function delete() {
		if ( is_admin() && current_user_can( 'delete_plugins' ) ) {
			if ( get_option( Admin::OPTION_DELETE_SETTINGS ) ) {
				delete_option( self::OPTION_UPDATE_TOKEN );
			}
		}
	}
}