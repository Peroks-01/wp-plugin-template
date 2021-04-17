<?php namespace peroks\plugin_customer\plugin_package;
/**
 * Implements the singleton pattern.
 *
 * @author Per Egil Roksvaag
 */
trait Singleton
{
	/**
	 * @var object The class singleton.
	 */
	protected static $_instance;

	/**
	 * @return object The class singleton.
	 */
	public static function instance() {
		if ( is_null( static::$_instance ) ) {
			static::$_instance = false;
			$class             = apply_filters( Main::FILTER_CLASS_CREATE, static::class );
			static::$_instance = apply_filters( Main::FILTER_CLASS_CREATED, new $class(), $class, static::class );
		}
		return static::$_instance;
	}

	/**
	 * Protect constructor.
	 */
	protected function __construct() {
	}
}