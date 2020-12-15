<?php namespace peroks\plugin_customer\plugin_package;
/**
 * The Plugin admin settings page.
 *
 * @since 1.0.0
 * @author Per Egil Roksvaag
 */
class Admin {
	use Singleton;

	/**
	 * @var string The slug name assigned to this menu page.
	 */
	const PAGE = Main::DOMAIN;

	/**
	 * @var string The capability required for this menu page to be displayed to the user.
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * @var string Admin settings
	 */
	const SECTION_DELETE         = Main::PREFIX . '_delete';
	const OPTION_DELETE_SETTINGS = self::SECTION_DELETE . '_settings';

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$name = plugin_basename( Main::FILE );

		//	Adds a top level or a submenu page to the admin menu (or both).
		add_action( 'admin_menu', array( $this, 'admin_top_menu' ) );
		add_action( 'admin_menu', array( $this, 'admin_sub_menu' ) );

		//	Displays a "Settings" and a "Support" link on the Plugins page.
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		add_filter( "plugin_action_links_{$name}", array( $this, 'plugin_action_links' ) );

		//	Admin settings
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( Main::ACTION_ACTIVATE, array( $this, 'activate' ) );
		add_action( Main::ACTION_DELETE, array( $this, 'delete' ), 100 );
	}

	/* -------------------------------------------------------------------------
	 * WordPress admin callbacks.
	 * ---------------------------------------------------------------------- */

	/**
	 * Adds a top level page to the admin menu.
	 *
	 * @since 1.0.0
	 */
	public function admin_top_menu() {
		$title = __( '[This Plugin Name] settings', '[plugin-text-domain]' );
		$page  = add_menu_page(
			$title,									//	Page title
			Main::NAME,								//	Menu title
			self::CAPABILITY,						//	Required capability
			self::PAGE,							//	Menu page slug
			array( $this, 'admin_page_content' ),	//	Output function
			'dashicons-smiley'						//	Icon name or url
		);

		add_action( "load-{$page}", array( $this, 'admin_page_load' ) );
	}

	/**
	 * Adds a submenu page to the admin menu.
	 *
	 * @since 1.0.0
	 */
	public function admin_sub_menu() {
		$title = __( '[This Plugin Name] settings', '[plugin-text-domain]' );
		$page  = add_submenu_page(
			'options-general.php',					//	Parent page slug
			$title,									//	Page title
			Main::NAME,								//	Menu title
			self::CAPABILITY,						//	Required capability
			self::PAGE,							//	Menu page slug
			array( $this, 'admin_page_content' )	//	Output function
		);

		add_action( "load-{$page}", array( $this, 'admin_page_load' ) );
	}

	/**
	 * Displays a "Support" link for this plugin on the Plugins page.
	 *
	 * @since 1.0.0
	 * @param array $links An array of the plugin's metadata.
	 * @param string $file Path to the plugin file relative to the plugins directory.
	 * @return array Modified metadata array.
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( plugin_basename( Main::FILE ) === $file ) {
			$links[] = vsprintf( '<a href="%s" target="_blank">%s</a>', array(
				esc_url( 'https://codeable.io/developers/per-egil-roksvaag/' ),
				esc_html__( 'Support', '[plugin-text-domain]' ),
			) );
		}
		return $links;
	}

	/**
	 * Displays a "Settings" link for this plugin on the Plugins page.
	 *
	 * @since 1.0.0
	 * @param array $actions An array of plugin action links.
	 * @return array Tme modified action links.
	 */
	public function plugin_action_links( $actions ) {
		array_unshift( $actions, vsprintf( '<a href="%s">%s</a>', array(
			esc_url( menu_page_url( self::PAGE, false ) ),
			esc_html__( 'Settings', '[plugin-text-domain]' ),
		) ) );
		return $actions;
	}

	/**
	 * Callback for loading assets for the admin page.
	 *
	 * @since 1.0.0
	 */
	public function admin_page_load() {
	}

	/**
	 * Displays the admin page content.
	 *
	 * @since 1.0.0
	 */
	public function admin_page_content() {
		if ( current_user_can( self::CAPABILITY ) ) {
			printf( '<div class="wrap">' );
			printf( '<h1>%s</h1>', get_admin_page_title() );
			printf( '<form method="post" action="options.php">' );

			settings_fields( Main::PREFIX );		//	Group name
			do_settings_sections( self::PAGE );	//	Menu page slug
			submit_button();

			printf( '</form>' );
			printf( '</div>' );
		}
	}

	/* -------------------------------------------------------------------------
	 * Admin setting
	 * ---------------------------------------------------------------------- */

	/**
	 * Registers settings, sections and fields.
	 *
	 * @since 1.0.0
	 */
	public function admin_init() {

		// Assets section
		$this->add_section( array(
			'section'     => self::SECTION_DELETE,
			'page'        => self::PAGE,
			'label'       => __( 'DANGER ZONE!', '[plugin-text-domain]' ),
			'description' => vsprintf( '<p>%s %s</p>', array(
				esc_html__( 'Check the below checkbox to also delete all plugin data and settings when this plugin is deleted.', '[plugin-text-domain]' ),
				esc_html__( 'Only do this if you do not intend to use this plugin again, all your data and settings will be lost.', '[plugin-text-domain]' ),
			) ),
		) );

		//	Inline stylesheets
		$this->add_checkbox( array(
			'option'      => self::OPTION_DELETE_SETTINGS,
			'section'     => self::SECTION_DELETE,
			'page'        => self::PAGE,
			'label'       => __( 'Also delete plugin data', '[plugin-text-domain]' ),
			'description' => __( 'Check to also delete all plugin data and settings when deleting this plugin.', '[plugin-text-domain]' ),
		) );
	}

	/**
	 * Sets plugin default setting on activation.
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
			if ( is_null( get_option( self::OPTION_DELETE_SETTINGS, null ) ) ) {
				add_option( self::OPTION_DELETE_SETTINGS, 0 );
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
			if ( get_option( self::OPTION_DELETE_SETTINGS ) ) {
				delete_option( self::OPTION_DELETE_SETTINGS );
			}
		}
	}

	/* -------------------------------------------------------------------------
	 * Admin setting utils
	 * ---------------------------------------------------------------------- */

	/**
	 * Adds a new sections to an admin page.
	 *
	 * Wrapper for add_settings_section.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args An array of arguments with the below key/value pairs:
	 * @var string section The section id (slug)
	 * @var string page The menu slug of the page to display the section
	 * @var string label The section heading
	 * @var string description The section description
	 */
	public function add_section( $args ) {
		$param = (object) wp_parse_args( $args, array(
			'section'     => '',
			'page'        => '',
			'label'       => '',
			'description' => '',
		) );

		add_settings_section( $param->section, $param->label, function () use ( $param ) {
			echo wp_kses_post( $param->description );
		}, $param->page );
	}

	/**
	 * Adds a checkbox to a section on an admin page.
	 *
	 * Wrapper for register_setting and add_settings_field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args An array of arguments with the below key/value pairs:
	 * @var string option The field id (slug)
	 * @var string section The section id (slug)
	 * @var string page The menu slug of the page to display the field
	 * @var string group The option group id
	 * @var string label The field label
	 * @var string description The field description
	 */
	public function add_checkbox( $args ) {
		$param = (object) wp_parse_args( $args, array(
			'option'      => '',
			'section'     => '',
			'page'        => '',
			'group'       => Main::PREFIX,
			'label'       => '',
			'description' => '',
		) );

		register_setting( $param->group, $param->option, array(
			'type'              => 'integer',
			'default'           => 0,
			'sanitize_callback' => function ( $value ) {
				return $value ? 1 : 0;
			},
		) );

		add_settings_field( $param->option, $param->label, function () use ( $param ) {
			vprintf( '<input type="checkbox" id="%s" name="%s" value="1" %s>', array(
				esc_attr( $param->option ),
				esc_attr( $param->option ),
				checked( get_option( $param->option ), 1, false ),
			) );
			printf( '<span>%s</span>', wp_kses_post( $param->description ) );
		}, $param->page, $param->section, array( 'label_for' => esc_attr( $param->option ) ) );
	}
}