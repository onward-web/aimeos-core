<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2011
 * @copyright Aimeos (aimeos.org), 2015
 * @package MShop
 * @subpackage Catalog
 */


namespace Aimeos\MShop\Catalog\Item;


/**
 * Generic interface for catalog items.
 *
 * @package MShop
 * @subpackage Catalog
 */
interface Iface
	extends \Aimeos\MShop\Common\Item\ListRef\Iface, \Aimeos\MShop\Common\Item\Config\Iface, \Aimeos\MShop\Common\Item\Tree\Iface
{
	/**
	 * Returns the name of the item.
	 *
	 * @return string Name of the item
	 */
	public function getLabel();

	/**
	 * Sets the new name of the item.
	 *
	 * @param string $name New name of the item
	 * @return \Aimeos\MShop\Catalog\Item\Iface Catalog item for chaining method calls
	 */
	public function setLabel( $name );

	/**
	 * Returns the code of the item.
	 *
	 * @return string Code of the item
	 */
	public function getCode();

	/**
	 * Sets the code of the item.
	 *
	 * @param string $name New code of the item
	 * @return \Aimeos\MShop\Catalog\Item\Iface Catalog item for chaining method calls
	 */
	public function setCode( $name );

	/**
	 * Returns the status of the item.
	 *
	 * @return boolean True if enabled, false if not
	 */
	public function getStatus();

	/**
	 * Sets the new status of the item.
	 *
	 * @param boolean $status True if enabled, false if not
	 * @return \Aimeos\MShop\Catalog\Item\Iface Catalog item for chaining method calls
	 */
	public function setStatus( $status );
}
