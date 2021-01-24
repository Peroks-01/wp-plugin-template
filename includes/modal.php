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
	const FILTER_MODAL_ID      = Main::PREFIX . '_modal_id';
	const FILTER_MODAL_TARGET  = Main::PREFIX . '_modal_target';
	const FILTER_MODAL_TRIGGER = Main::PREFIX . '_modal_trigger';

	/**
	 * @var int Modal target counter
	 */
	protected static $index = 0;

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
		Asset::instance()->enqueue_style( 'assets/css/pure-modal.css', array(), $args );
	}

	/**
	 * Enqueues scripts.
	 */
	public function wp_enqueue_scripts() {
		$args = array( 'defer' => true );
		Asset::instance()->enqueue_script( 'assets/js/pure-modal.js', array(), $args );
	}

	public function shortcode( $args = array(), $content = '', $tag = '' ) {
		$args['target'] = $this->modal_target( $args, $content );
		return $this->render_trigger( $args );
	}

	public function modal_target( $args, $content ) {
		$target = Main::instance()->plugin_prefix( 'modal-target-%d', '-' );
		$target = sprintf( $target, ++static::$index );
		$target = apply_filters( self::FILTER_MODAL_ID, $target, static::$index );

		add_action( 'wp_footer', function () use ( $args, $content, $target ) {
			$flags = JSON_HEX_APOS | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;
			$args = wp_parse_args( $args, array(
				'target' => $target,
				'type'   => 'link',
				'class'  => array(),
				'attrib' => array(),
			) );

			$class = is_string( $args['class'] ) ? explode( ' ', $args['class'] ) : (array) $args['class'];
			$class = array_filter( array_map( 'trim', $class ) );
			$class = array_merge( array( 'pure-modal-target' ), $class );

			$attrib = (array) $args['attrib'];
			$attrib = array_diff_key( $attrib, array_flip( array( 'class', 'href', 'data-target' ) ) );

			$html = vsprintf( '<div id="%s" class="%s"%s>%s</div>', array(
				esc_attr( $args['target'] ),
				esc_attr( join( ' ', $class ) ),
				esc_attr( Utils::instance()->array_to_attr( $attrib ) ),
				$content,
			) );

			echo apply_filters( self::FILTER_MODAL_TARGET, $html, $args, $content );
		}, 50 );

		return $target;
	}

	public function render_trigger( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'target' => '',
			'type'   => 'button',
			'class'  => array(),
			'attrib' => array(),
			'icon'   => '',
			'text'   => 'Submit',
		) );

		$class = is_string( $args['class'] ) ? explode( ' ', $args['class'] ) : (array) $args['class'];
		$class = array_filter( array_map( 'trim', $class ) );
		$class = array_merge( array( 'pure-modal-trigger' ), $class );

		$attrib = (array) $args['attrib'];
		$attrib = array_diff_key( $attrib, array_flip( array( 'class', 'href', 'data-target' ) ) );

		$type  = $args['type'];
		$types = array(
			'link'   => '<a class="%s" data-target="%s" href="javascript:void(0);"%s>%s%s</a>',
			'button' => '<button class="%s" data-target="%s"%s>%s%s</button>',
		);

		$html = vsprintf( $types[ $type ] ?? 'link', array(
			esc_attr( join( ' ', $class ) ),
			esc_attr( $args['target'] ),
			esc_attr( Utils::instance()->array_to_attr( $attrib ) ),
			wp_kses_post( $args['icon'] ),
			wp_kses_post( $args['text'] ),
		) );

		return apply_filters( self::FILTER_MODAL_TRIGGER, $html, $args );
	}
}