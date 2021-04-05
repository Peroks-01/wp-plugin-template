<?php namespace peroks\plugin_customer\plugin_package;

/**
 * Misc utility and helper functions.
 *
 * @author Per Egil Roksvaag
 * @method static Utils instance() Gets the singleton class instance
 */
class Utils
{
	use Singleton;

	/**
	 * @var string The class filter hooks.
	 */
	const FILTER_PARSE_CLASS = Main::PREFIX . '_parse_class';
	const FILTER_GET_WIDGET  = Main::PREFIX . '_get_widget';

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
	 * Adds a value to an array.
	 *
	 * @param array $array The array
	 * @param mixed $value The value to add to the array;
	 * @param string $pos Where to add the value: 'begin' or 'end';
	 * @param bool $unique Whether the added value must be unique (true) or not (false).
	 * @param bool $strict Whether a strict type comparison is performed (true) or not (false).
	 */
	public function add_value( $array, $value, $pos = 'end', $unique = true, $strict = false ) {
		if ( empty( $unique ) || is_bool( array_search( $value, $array, $strict ) ) ) {
			switch ( $pos ) {
				case 'begin': array_unshift( $array, $value ); break;
				case 'end': array_push( $array, $value ); break;
			}
		}
		return $array;
	}

	/**
	 * Removes a value from an array.
	 *
	 * @param array $array The array
	 * @param mixed $value The value to remove from the array;
	 * @param bool $strict Whether a strict type comparison is performed (true) or not (false).
	 */
	public function remove_value( $array, $value, $strict = false ) {
		if ( is_int( $index = array_search( $value, $array, $strict ) ) ) {
			unset( $array[ $index ] );
		}
		return $array;
	}

	/**
	 * Transforms a css class string to an array.
	 *
	 * @param string $class A css class string
	 * @return array An array of css classes.
	 */
	public function parse_class( $class ) {
		$class = is_string( $class ) ? preg_split( '/[\s]+/', $class ) : (array) $class;
		return apply_filters( self::FILTER_PARSE_CLASS, array_filter( $class ) );
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
	 * Removes a class method hook without having the class instance.
	 *
	 * @param string $tag The add_action or add_filter hook to remove.
	 * @param string $class The instance class.
	 * @param string $method The hooked class method.
	 * @param int $priority The priority the hook was registred with.
	 * @return int The number of times the hook was removed.
	 */
	public function remove_hook( $tag, $class = '', $method = '', $priority = 10 ) {
		global $wp_filter;
		$hook  = $wp_filter[ $tag ]->callbacks[ $priority ] ?? array();
		$func  = array_column( $hook, 'function' );
		$count = 0;

		foreach ( $func as $callback ) {
			if ( is_array( $callback ) && count( $callback ) == 2 ) {
				if ( empty( $class ) || ( is_object( $callback[0] ) ? get_class( $callback[0] ) : $callback[0] ) == $class ) {
					if ( empty( $method ) || $callback[1] == $method ) {
						remove_filter( $tag, $callback, $priority );
						$count++;
					}
				}
			}
		}
		return $count;
	}

	/**
	 * Gets class method callbacks hooked to the given tag.
	 *
	 * @param string $tag The add_action or add_filter hook.
	 * @param string $class The instance class.
	 * @param string $method The hooked class method.
	 * @param int $priority The priority the hook was registred with.
	 * @return callback[] An array of class::mehtod() callbacks.
	 */
	public function get_hook_callback( $tag, $class = '', $method = '', $priority = 10 ) {
		global $wp_filter;
		$hook   = $wp_filter[ $tag ]->callbacks[ $priority ] ?? array();
		$func   = array_column( $hook, 'function' );
		$result = array();

		foreach ( $func as $callback ) {
			if ( is_array( $callback ) && count( $callback ) == 2 ) {
				if ( empty( $class ) || get_class( $callback[0] ) == $class ) {
					if ( empty( $method ) || $callback[1] == $method ) {
						$result[] = $callback;
					}
				}
			}
		}
		return $result;
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

	/**
	 * Gets all post IDs and meta values of the given meta key.
	 *
	 * @param string $key The meta key
	 * @param string $status The post status to include in the result
	 * @return array An array of objects (post_id, meta_value)
	 */
	public function get_meta_table( $key, $status = 'publish' ) {
		global $wpdb;

		$key    = sanitize_key( $key );
		$status = sanitize_key( $status );

		$query[] = 'SELECT pm.post_id, pm.meta_value';
		$query[] = "FROM   {$wpdb->prefix}postmeta AS pm";
		$query[] = "JOIN   {$wpdb->prefix}posts AS p ON pm.post_id = p.ID";
		$query[] = 'WHERE  pm.meta_key = %s AND p.post_status = %s';
		$query[] = 'ORDER  BY pm.meta_value';

		$sql = $wpdb->prepare( join( "\n", $query ), compact( 'key', 'status') );
		return $wpdb->get_results( $sql, OBJECT );
	}
}