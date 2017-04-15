<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package MW
 * @subpackage View
 */


namespace Aimeos\MW\View;


/**
 * Base view implementation
 *
 * @package MW
 * @subpackage View
 */
class Base
{
	private static $helper = [];


	/**
	 * Calls the view helper with the given name and arguments and returns it's output.
	 *
	 * @param string $name Name of the view helper
	 * @param array $args Arguments passed to the view helper
	 * @return mixed Output depending on the view helper
	 */
	public function __call( $name, array $args )
	{
		if( !isset( self::$helper[$name] ) )
		{
			if( ctype_alnum( $name ) === false )
			{
				$classname = is_string( $name ) ? '\\Aimeos\\MW\\View\\Helper\\' . $name : '<not a string>';
				throw new \Aimeos\MW\View\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
			}

			$iface = '\\Aimeos\\MW\\View\\Helper\\Iface';
			$classname = '\\Aimeos\\MW\\View\\Helper\\' . ucfirst( $name ) . '\\Standard';

			if( class_exists( $classname ) === false ) {
				throw new \Aimeos\MW\View\Exception( sprintf( 'Class "%1$s" not available', $classname ) );
			}

			$helper = new $classname( $this );

			if( !( $helper instanceof $iface ) ) {
				throw new \Aimeos\MW\View\Exception( sprintf( 'Class "%1$s" does not implement interface "%2$s"', $classname, $iface ) );
			}

			self::$helper[$name] = $helper;
		}

		return call_user_func_array( array( self::$helper[$name], 'transform' ), $args );
	}


	/**
	 * Calls the view helper with the given name and arguments and returns it's output.
	 *
	 * @param string $name Name of the view helper
	 * @param array $args Arguments passed to the view helper
	 * @return mixed Output depending on the view helper
	 */
	public static function __callStatic( $name, array $args )
	{
		if( isset( self::$helper[$name] ) ) {
			return call_user_func_array( array( self::$helper[$name], 'transform' ), $args );
		}

		throw new Exception( sprintf( 'View helper "%1$s" not found', $name ) );
	}


	/**
	 * Clones internal objects of the view.
	 */
	public function __clone()
	{
		foreach( self::$helper as $name => $helper )
		{
			if( $helper === null ) {
				continue;
			}

			$helper = clone $helper;
			$helper->setView( $this ); // reset view so view helpers will use the current one (for translation, etc.)

			self::$helper[$name] = $helper;
		}
	}


	/**
	 * Adds a view helper instance to the view.
	 *
	 * @param string $name Name of the view helper as called in the template
	 * @param \Aimeos\MW\View\Helper\Iface|null $helper View helper instance or null to remove it
	 * @return \Aimeos\MW\View\Iface View object for method chaining
	 */
	public function addHelper( $name, \Aimeos\MW\View\Helper\Iface $helper = null )
	{
		self::$helper[$name] = $helper;
		return $this;
	}
}
