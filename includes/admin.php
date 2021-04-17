<?php namespace peroks\plugin_customer\plugin_package;
/**
 * The Plugin admin settings page.
 *
 * @author Per Egil Roksvaag
 */
class Admin
{
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
		add_action( 'admin_menu', array( $this, 'admin_top_menu' ), 5 );
		add_action( 'admin_menu', array( $this, 'admin_sub_menu' ), 5 );

		//	Displays a "Settings" and a "Support" link on the Plugins page.
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		add_filter( "plugin_action_links_{$name}", array( $this, 'plugin_action_links' ) );

		//	Admin settings
		add_action( 'admin_init', array( $this, 'admin_init' ), 25 );
		add_action( Main::ACTION_ACTIVATE, array( $this, 'activate' ) );
		add_action( Main::ACTION_DELETE, array( $this, 'delete' ), 100 );
	}

	/* -------------------------------------------------------------------------
	 * WordPress admin callbacks.
	 * ---------------------------------------------------------------------- */

	/**
	 * Adds a top level page to the admin menu.
	 */
	public function admin_top_menu() {
		$title = __( '[This Plugin Name] settings', '[plugin-text-domain]' );
		$page  = add_menu_page(
			$title,									//	Page title
			Main::NAME,								//	Menu title
			self::CAPABILITY,						//	Required capability
			self::PAGE,								//	Menu page slug
			array( $this, 'admin_page_content' ),	//	Output function
			'dashicons-smiley'						//	Icon name or url
		);

		add_action( "load-{$page}", array( $this, 'admin_page_load' ) );
	}

	/**
	 * Adds a submenu page to the admin menu.
	 */
	public function admin_sub_menu() {
		$title = __( '[This Plugin Name] settings', '[plugin-text-domain]' );
		$page  = add_submenu_page(
			'options-general.php',					//	Parent page slug
			$title,									//	Page title
			Main::NAME,								//	Menu title
			self::CAPABILITY,						//	Required capability
			self::PAGE,								//	Menu page slug
			array( $this, 'admin_page_content' )	//	Output function
		);

		add_action( "load-{$page}", array( $this, 'admin_page_load' ) );
	}

	/**
	 * Displays a "Support" link for this plugin on the Plugins page.
	 *
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
	 */
	public function admin_page_load() {
	}

	/**
	 * Displays the admin page content.
	 */
	public function admin_page_content() {
		if ( current_user_can( self::CAPABILITY ) ) {
			printf( '<div class="wrap">' );
			printf( '<h1>%s</h1>', get_admin_page_title() );
			printf( '<form method="post" action="options.php">' );

			settings_fields( self::PAGE );		//	Group name
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
	 */
	public function admin_init() {

		//	Danger section
		$this->add_section( array(
			'section'     => self::SECTION_DELETE,
			'page'        => self::PAGE,
			'label'       => __( 'DANGER ZONE!', '[plugin-text-domain]' ),
			'description' => vsprintf( '<p>%s %s</p>', array(
				esc_html__( 'Check the below checkbox to also delete all plugin data and settings when this plugin is deleted.', '[plugin-text-domain]' ),
				esc_html__( 'Only do this if you do not intend to use this plugin again, all your data and settings will be lost.', '[plugin-text-domain]' ),
			) ),
		) );

		//	Delete plugin data
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
	 * @param array $args An array of arguments with the below key/value pairs:
	 * @var string section The section id (slug)
	 * @var string page The menu slug of the page to display the section
	 * @var string label The section heading
	 * @var string description The section description
	 */
	public function add_section( $args ) {
		$param = (object) wp_parse_args( $args, array(
			'section'     => '',
			'page'        => self::PAGE,
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
	 * @param array $args An array of arguments with the below key/value pairs:
	 * @var string option The field id (slug)
	 * @var string section The section id (slug)
	 * @var string page The menu slug of the page to display the field
	 * @var string group The option group id
	 * @var string default The option default value
	 * @var string label The field label
	 * @var string description The field description
	 */
	public function add_checkbox( $args ) {
		$param = (object) wp_parse_args( $args, array(
			'option'      => '',
			'section'     => '',
			'page'        => self::PAGE,
			'group'       => $args['page'] ?? self::PAGE,
			'default'     => 0,
			'label'       => '',
			'description' => '',
		) );

		register_setting( $param->group, $param->option, array(
			'type'              => 'integer',
			'default'           => $param->default,
			'sanitize_callback' => $param->sanitize ?? function ( $value ) {
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

	/**
	 * Adds multiple checkboxes to a section on an admin page.
	 *
	 * Wrapper for register_setting and add_settings_field.
	 *
	 * @param array $args An array of arguments with the below key/value pairs:
	 * @var string option The field id (slug)
	 * @var string section The section id (slug)
	 * @var string page The menu slug of the page to display the field
	 * @var string group The option group id
	 * @var string default The option default value
	 * @var string label The field label
	 * @var string description The field description
	 */
	public function add_multibox( $args ) {
		$param = (object) wp_parse_args( $args, array(
			'option'      => '',
			'section'     => '',
			'page'        => self::PAGE,
			'group'       => $args['page'] ?? self::PAGE,
			'default'     => 0,
			'label'       => '',
			'description' => '',
			'terms'       => array(),
		) );

		register_setting( $param->group, $param->option, array(
			'type'              => 'array',
			'default'           => $param->default,
			'default'           => array(),
			'sanitize_callback' => $param->sanitize ?? function ( $value ) {
				return (array) $value;
			},
		) );

		add_settings_field( $param->option, $param->label, function () use ( $param ) {
			$value = get_option( $param->option ) ?: array();

			foreach ( $param->terms as $key => $term ) {
				vprintf( '<input type="checkbox" name="%s[]" value="%s"%s>', array(
					esc_attr( $param->option ),
					esc_attr( is_string( $key ) ? $key : $term ),
					in_array( $term, $value ) ? ' checked' : '',
				) );
				printf( '<span>%s</span>&nbsp;&nbsp; ', esc_attr( $term ) );
			}
			printf( '<p class="description">%s</p>', wp_kses_post( $param->description ) );
		}, $param->page, $param->section, array() );
	}

	/**
	 * Adds a numeric input field to a section on an admin page.
	 *
	 * Wrapper for register_setting and add_settings_field.
	 *
	 * @param array $args An array of arguments with the below key/value pairs:
	 * @var string option The field id (slug)
	 * @var string section The section id (slug)
	 * @var string page The menu slug of the page to display the field
	 * @var string group The option group id
	 * @var string default The option default value
	 * @var string label The field label
	 * @var string description The field description
	 */
	public function add_number( $args ) {
		$param = (object) wp_parse_args( $args, array(
			'option'      => '',
			'section'     => '',
			'page'        => self::PAGE,
			'group'       => $args['page'] ?? self::PAGE,
			'default'     => 0,
			'label'       => '',
			'description' => '',
		) );

		register_setting( $param->group, $param->option, array(
			'type'              => 'integer',
			'default'           => $param->default,
			'sanitize_callback' => $param->sanitize ?? function ( $value ) {
				return (int) $value;
			},
		) );

		add_settings_field( $param->option, $param->label, function () use ( $param ) {
			$whitelist = array_flip( array(	'max', 'min', 'step', 'readonly' ) );
			$attributes = array_intersect_key( (array) $param, $whitelist );

			vprintf( '<input type="number" id="%s" class="small-text" name="%s" value="%d"%s>', array(
				esc_attr( $param->option ),
				esc_attr( $param->option ),
				get_option( $param->option ),
				$this->array_to_attr( $attributes ),
			) );
			printf( ' <span>%s</span>', wp_kses_post( $param->description ) );
		}, $param->page, $param->section, array( 'label_for' => esc_attr( $param->option ) ) );
	}

	/**
	 * Adds a text input field to a section on an admin page.
	 *
	 * Wrapper for register_setting and add_settings_field.
	 *
	 * @param array $args An array of arguments with the below key/value pairs:
	 * @var string option The field id (slug)
	 * @var string section The section id (slug)
	 * @var string page The menu slug of the page to display the field
	 * @var string group The option group id
	 * @var string default The option default value
	 * @var string label The field label
	 * @var string description The field description
	 */
	public function add_text( $args ) {
		$param = (object) wp_parse_args( $args, array(
			'option'      => '',
			'section'     => '',
			'page'        => self::PAGE,
			'group'       => $args['page'] ?? self::PAGE,
			'default'     => '',
			'label'       => '',
			'description' => '',
		) );

		register_setting( $param->group, $param->option, array(
			'type'              => 'string',
			'default'           => $param->default,
			'sanitize_callback' => $param->sanitize ?? function ( $value ) {
				return sanitize_text_field( trim( $value ) );
			},
		) );

		add_settings_field( $param->option, $param->label, function () use ( $param ) {
			$whitelist = array_flip( array(	'maxlength', 'minlength', 'readonly' ) );
			$attributes = array_intersect_key( (array) $param, $whitelist );

			vprintf( '<input type="text" id="%s" class="regular-text" name="%s" value="%s"%s>', array(
				esc_attr( $param->option ),
				esc_attr( $param->option ),
				get_option( $param->option ),
				$this->array_to_attr( $attributes ),
			) );

			printf( '<p class="description">%s</p>', wp_kses_post( $param->description ) );
		}, $param->page, $param->section, array( 'label_for' => esc_attr( $param->option ) ) );
	}

	/**
	 * Adds a file input field to upload a json file.
	 *
	 * Wrapper for register_setting and add_settings_field.
	 *
	 * @param array $args An array of arguments with the below key/value pairs:
	 * @var string option The field id (slug)
	 * @var string section The section id (slug)
	 * @var string page The menu slug of the page to display the field
	 * @var string group The option group id
	 * @var string default The option default value
	 * @var string label The field label
	 * @var string description The field description
	 */
	public function add_json( $args ) {
		$param = (object) wp_parse_args( $args, array(
			'option'      => '',
			'section'     => '',
			'page'        => self::PAGE,
			'group'       => $args['page'] ?? self::PAGE,
			'default'     => array(),
			'label'       => '',
			'download'    => '',
			'description' => '',
		) );

		$this->download( $param->option );

		register_setting( $param->group, $param->option, array(
			'type'              => 'array',
			'default'           => $param->default,
			'sanitize_callback' => $param->sanitize ?? function ( $value ) use ( $param ) {
				if ( $file = $_FILES[ $param->option ] ?? null ) {
					if ( empty( $file['error'] ) && is_readable( $file['tmp_name'] ) ) {
						if ( $data = json_decode( file_get_contents( $file['tmp_name'] ) ) ) {
							return (array) $data;
						}
					}
				}
				return (array) get_option( $param->option ) ?: array();
			},
		) );

		add_settings_field( $param->option, $param->label, function () use ( $param ) {
			$whitelist = array_flip( array(	'maxlength', 'minlength', 'readonly' ) );
			$attributes = array_intersect_key( (array) $param, $whitelist );

			vprintf( '<input type="file" id="%s" name="%s"%s>', array(
				esc_attr( $param->option ),
				esc_attr( $param->option ),
				$this->array_to_attr( $attributes ),
			) );

			vprintf( '<p class="description"><a href="%s">%s</a></p>', array(
				add_query_arg( 'download', $param->option, menu_page_url( self::PAGE, false ) ),
				wp_kses_post( $param->download ),
			) );
		}, $param->page, $param->section, array( 'label_for' => esc_attr( $param->option ) ) );
	}

	/**
	 * Transforms an option to a JSON file and downloads it.
	 *
	 * @var string option The option id (slug)
	 */
	protected function download( $option ) {
		if ( filter_input( INPUT_GET, 'download', FILTER_SANITIZE_STRING ) == $option ) {
			$flags    = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;
			$download = json_encode( get_option( $option, array() ), $flags );
			$name     = str_replace( '_', '-', $option ) . '.json';

			header( 'Content-Disposition: attachment; filename="' . $name . '"' );
			header( 'Content-Type: application/json' );
			header( 'Content-Length: ' . strlen( $download ) );

			echo $download;
			flush();
			exit();
		}
	}

	/**
	 * Transforms an associative array of key/value pairs to html attributes.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html attributes
	 */
	protected function array_to_attr( $attr = array() ) {
		$call = function ( $key, $value ) {
			if ( $value && is_bool( $value ) ) {
				return sanitize_key( $key ) . '="' . esc_attr( $key ) . '"';
			}
			return sanitize_key( $key ) . '="' . esc_attr( $value ) . '"';
		};

		if ( $attr ) {
			return ' ' . join( ' ', array_map( $call, array_keys( $attr ), $attr ) );
		}
	}
}