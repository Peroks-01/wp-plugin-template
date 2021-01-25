<?php namespace peroks\plugin_customer\plugin_package;
/**
 * Plugin setup.
 *
 * @author Per Egil Roksvaag
 */
class Modal {
	use Singleton;

	/**
	 * @var string The class filter hooks.
	 */
	const FILTER_MODAL_ID        = Main::PREFIX . '_modal_id';
	const FILTER_MODAL_TRIGGER   = Main::PREFIX . '_modal_trigger';
	const FILTER_MODAL_CONTAINER = Main::PREFIX . '_modal_container';
	const FILTER_MODAL_TEMPLATES = Main::PREFIX . '_modal_templates';
	const FILTER_MODAL_CONTENT   = Main::PREFIX . '_modal_content';
	const FILTER_MODAL_HEADER    = Main::PREFIX . '_modal_header';
	const FILTER_MODAL_BODY      = Main::PREFIX . '_modal_body';
	const FILTER_MODAL_FOOTER    = Main::PREFIX . '_modal_footer';

	/**
	 * @var int Modal container counter
	 */
	protected $index = 0;

	/**
	 * @var array Array of registred modal templates
	 */
	protected $templates = array();

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/* -------------------------------------------------------------------------
	 * WordPress callbacks
	 * ---------------------------------------------------------------------- */

	/**
	 * Loads the translated strings (if any).
	 */
	public function init() {

		//	Adds shortcode [curafun_game_modal]
		add_shortcode( Main::PREFIX . '_modal', array( $this, 'shortcode' ) );
		add_shortcode( Main::PREFIX . '_modal_trigger', array( $this, 'modal_trigger' ) );
		add_shortcode( Main::PREFIX . '_modal_container', array( $this, 'modal_container' ) );

		if ( empty( is_admin() ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_styles' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		}
	}

	/**
	 * Enqueues styles.
	 */
	public function wp_enqueue_styles() {
		$args = array( 'inline' => true );
		Asset::instance()->enqueue_style( 'assets/css/pure-modal.min.css', array(), $args );
	}

	/**
	 * Enqueues scripts.
	 */
	public function wp_enqueue_scripts() {
		$args = array( 'defer' => true );
		Asset::instance()->enqueue_script( 'assets/js/pure-modal.min.js', array(), $args );
	}

	public function shortcode( $args = array(), $content = '', $shortcode = '' ) {
		foreach ( array_change_key_case( $args ) as $key => $value ) {
			if ( strpos( $key, '_' ) ) {
				list( $prefix, $name )   = explode( '_', $key );
				$var[ $prefix ][ $name ] = $value;
			}
		}

		$trigger   = $var['trigger']   ?? array();
		$container = $var['container'] ?? array();

		$trigger['container'] = $this->modal_container( $container, $content, $shortcode );
		return $this->modal_trigger( $trigger, $content, $shortcode );
	}

	public function modal_trigger( $args = array(), $content = '', $shortcode = '' ) {
		$args = array_change_key_case( $args );
		$args = shortcode_atts( array(
			'container' => '',
			'type'      => 'button',
			'class'     => array(),
			'attrib'    => array(),
			'icon'      => '',
			'text'      => __( 'Open', '[plugin-text-domain]' ),
		), $args, $shortcode );

		$class   = Utils::instance()->parse_class( $args['class'] );
		$class[] = 'pure-modal-trigger';

		$attrib = (array) $args['attrib'];
		$attrib = array_diff_key( $attrib, array_flip( array( 'class', 'href', 'data-modal-container' ) ) );

		$type  = $args['type'];
		$types = array(
			'link'   => '<a class="%s" data-modal-container="%s" href="javascript:void(0);"%s>%s%s</a>',
			'button' => '<button class="%s" data-modal-container="%s"%s>%s%s</button>',
		);

		$trigger = vsprintf( $types[ $type ] ?? 'button', array(
			esc_attr( join( ' ', $class ) ),
			esc_attr( $args['container'] ),
			esc_attr( Utils::instance()->array_to_attr( $attrib ) ),
			wp_kses_post( $args['icon'] ),
			wp_kses_post( $args['text'] ),
		) );

		return apply_filters( self::FILTER_MODAL_TRIGGER, $trigger, $args );
	}

	public function modal_container( $args = array(), $content = '', $shortcode = '' ) {
		$id = sprintf( Main::instance()->plugin_prefix( 'modal-container-%d', '-' ), ++$this->index );
		$id = apply_filters( self::FILTER_MODAL_ID, $id, $this->index );

		$args = array_change_key_case( $args );
		$args = wp_parse_args( $args, array(
			'id'       => $id,
			'class'    => array(),
			'attrib'   => array(),
			'template' => 'form',
			'load'     => '',
			'defer'    => false,
			'header'   => true,
			'body'     => true,
			'footer'   => true,
			'title'    => __( 'Title', '[plugin-text-domain]' ),
		) );

		$class   = Utils::instance()->parse_class( $args['class'] );
		$class[] = 'pure-modal-container';

		$attrib = (array) $args['attrib'];
		$attrib = array_diff_key( $attrib, array_flip( array( 'id', 'class' ) ) );

		$args['class']  = $class;
		$args['attrib'] = $attrib;

		$content = $this->get_content( $args, $content );

		add_action( 'wp_footer', function () use ( $args, $content ) {
			$template = $args['template'];
			$args['class'][] = $template;
			$callback = $this->get_template( $template );
			$container = call_user_func( $callback, $args, $content, $template );

			echo apply_filters( self::FILTER_MODAL_CONTAINER, $container, $args, $content, $template );
		}, 50 );

		return $args['id'];
	}

	public function get_content( $args, $content ) {
		$content = apply_shortcodes( $content );

		if ( $args['defer'] ?? true ) {
			$base64  = base64_encode( $content );
			$content = sprintf( '<data class="pure-modal-defer" value="%s"></data>', $base64 );
		} elseif ( $load = $args['load'] ?? '' ) {
			$content = sprintf( '<data class="pure-modal-load" value="%s"></data>', $load );
		}

		return apply_filters( self::FILTER_MODAL_CONTENT, $content, $args );
	}

	public function get_template( $template ) {
		if ( empty( $this->templates ) ) {
			$this->templates = apply_filters( self::FILTER_MODAL_TEMPLATES, array(
				'simple'  => array( $this, 'template_simple' ),
				'section' => array( $this, 'template_section' ),
				'form'    => array( $this, 'template_form' ),
			) );
		}
		return $this->templates[ $template ] ?? '__return_empty_string';
	}

	/* -------------------------------------------------------------------------
	 * Modal templates
	 * ---------------------------------------------------------------------- */

	public function template_simple( $args, $content, $template ) {
		$body = vsprintf( '<div id="%s" class="%s"%s>%s</div>', array(
			esc_attr( $args['id'] ),
			esc_attr( join( ' ', $args['class'] ) ),
			esc_attr( Utils::instance()->array_to_attr( $args['attrib'] ) ),
			$content,
		) );

		return apply_filters( self::FILTER_MODAL_BODY, $body, $args, $content, $template );
	}

	public function template_section( $args, $content, $template ) {
		if ( $args['header'] ?? true ) {
			$title    = wp_kses_post( $args['title'] ?? '' );
			$header   = sprintf( '<div class="pure-modal-header"><h3>%s<h3></div>', $title );
			$output[] = apply_filters( self::FILTER_MODAL_HEADER, $header, $args, $content, $template );
		}

		if ( $args['body'] ?? true ) {
			$body     = sprintf( '<div class="pure-modal-body">%s</div>', $content );
			$output[] = apply_filters( self::FILTER_MODAL_BODY, $body, $args, $content, $template );
		}

		if ( $args['footer'] ?? true ) {
			$text     = esc_html__( 'OK', '[plugin-text-domain]' );
			$default  = sprintf( '<button type="button" class="pure-modal-button button">%s</button>', $text );
			$buttons  = wp_kses_post( $args['buttons'] ?? $default );
			$footer   = sprintf( '<div class="pure-modal-footer">%s</div>', $buttons );
			$output[] = apply_filters( self::FILTER_MODAL_FOOTER, $footer, $args, $content, $template );
		}

		if ( isset( $output ) ) {
			return vsprintf( '<section id="%s" class="%s"%s>%s</section>', array(
				esc_attr( $args['id'] ),
				esc_attr( join( ' ', $args['class'] ) ),
				esc_attr( Utils::instance()->array_to_attr( $args['attrib'] ) ),
				join( '', array_filter( $output ) ),
			) );
		}
	}

	public function template_form( $args, $content, $template ) {
		global $wp;

		$args = wp_parse_args( $args, array(
			'method'  => 'POST',
			'action'  => home_url( $wp->request ),
			'enctype' => 'multipart/form-data',
		) );

		if ( $args['header'] ?? true ) {
			$title    = wp_kses_post( $args['title'] ?? '' );
			$header   = sprintf( '<div class="pure-modal-header"><h3>%s<h3></div>', $title );
			$output[] = apply_filters( self::FILTER_MODAL_HEADER, $header, $args, $content, $template );
		}

		if ( $args['body'] ?? true ) {
			$body     = sprintf( '<div class="pure-modal-body">%s</div>', $content );
			$output[] = apply_filters( self::FILTER_MODAL_BODY, $body, $args, $content, $template );
		}

		if ( $args['footer'] ?? true ) {
			$text     = esc_html__( 'Submit', '[plugin-text-domain]' );
			$default  = sprintf( '<button class="pure-modal-button submit">%s</button>', $text );
			$buttons  = wp_kses_post( $args['buttons'] ?? $default );
			$footer   = sprintf( '<div class="pure-modal-footer">%s</div>', $buttons );
			$output[] = apply_filters( self::FILTER_MODAL_FOOTER, $footer, $args, $content, $template );
		}

		if ( isset( $output ) ) {
			return vsprintf( '<form id="%s" class="%s" method="%s" action="%s" enctype="%s"%s>%s</form>', array(
				esc_attr( $args['id'] ),
				esc_attr( join( ' ', $args['class'] ) ),
				esc_attr( $args['method'] ),
				esc_attr( $args['action'] ),
				esc_attr( $args['enctype'] ),
				esc_attr( Utils::instance()->array_to_attr( $args['attrib'] ) ),
				join( '', array_filter( $output ) ),
			) );
		}
	}
}