<?php namespace peroks\plugin_customer\plugin_package;

/**
 * Misc utility and helper functions.
 *
 * @author Per Egil Roksvaag
 * @method static Utils instance() Gets the singleton class instance
 */
class Utils {
	use Singleton;

	/**
	 * @var string The class filter hooks.
	 */
	const FILTER_GET_WIDGET = Main::PREFIX . '_get_widget';

	/* -------------------------------------------------------------------------
	 * Public methods
	 * ---------------------------------------------------------------------- */

	/**
	 * Filters out all entries with a null value.
	 *
	 * @param array $array The array to alter.
	 * @return array The filtered array.
	 */
	public function filter_null( $array ) {
		return array_filter( $array, function ( $value ) {
			return isset( $value );
		} );
	}

	/**
	 * Transforms an associative array of key/value pairs to html attributes.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html attributes
	 */
	public function array_to_attr( $attr = array() ) {
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

	/**
	 * Captures and returns the widget output.
	 *
	 * @param string $widget The widget's PHP class name.
	 * @param array $instance The widget's instance settings.
	 * @param array $args Array of arguments to configure the display of the widget.
	 * @return string The widget's HTML output.
	 */
	public function get_the_widget( $widget, $instance = array(), $args = array() ) {
		ob_start();
		the_widget( $widget, $instance, $args );
		return apply_filters( self::FILTER_GET_WIDGET, ob_get_clean(), $instance, $args );
	}
}