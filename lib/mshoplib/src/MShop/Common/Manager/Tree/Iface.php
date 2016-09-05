<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2016
 * @package MShop
 * @subpackage Common
 */


namespace Aimeos\MShop\Common\Manager\Tree;


/**
 * Interface for all tree manager implementations.
 *
 * @package MShop
 * @subpackage Common
 */
interface Iface extends \Aimeos\MShop\Common\Manager\Iface
{
	/**
	 * Returns a list of item IDs, that are in the path of given item ID.
	 *
	 * @param string $id ID of item to get the path for
	 * @param string[] $ref List of domains to fetch list items and referenced items for
	 * @return array Associative list of items implementing \Aimeos\MShop\Catalog\Item\Iface with IDs as keys
	 */
	public function getPath( $id, array $ref = array() );


	/**
	 * Returns a node and its descendants depending on the given resource.
	 *
	 * @param string|null $id Retrieve nodes starting from the given ID
	 * @param string[] List of domains (e.g. text, media, etc.) whose referenced items should be attached to the objects
	 * @param integer $level One of the level constants from \Aimeos\MW\Tree\Manager\Base
	 * @param \Aimeos\MW\Criteria\Iface|null $criteria Optional criteria object with conditions
	 * @return \Aimeos\MShop\Catalog\Item\Iface Catalog item, maybe with subnodes
	 */
	public function getTree( $id = null, array $ref = array(), $level = \Aimeos\MW\Tree\Manager\Base::LEVEL_TREE, \Aimeos\MW\Criteria\Iface $criteria = null );


	/**
	 * Adds a new item object.
	 *
	 * @param \Aimeos\MShop\Common\Item\Tree\Iface $item Tree item which should be inserted
	 * @param string|null $parentId ID of the parent item where the item should be inserted into
	 * @param string|null $refId ID of the item where the item should be inserted before (null to append)
	 * @return void
	 */
	public function insertItem( \Aimeos\MShop\Common\Item\Tree\Iface $item, $parentId = null, $refId = null );


	/**
	 * Moves an existing item to the new parent in the storage.
	 *
	 * @param string $id ID of the item that should be moved
	 * @param string $oldParentId ID of the old parent item which currently contains the item that should be removed
	 * @param string $newParentId ID of the new parent item where the item should be moved to
	 * @param string|null $refId ID of the item where the item should be inserted before (null to append)
	 * @return void
	 */
	public function moveItem( $id, $oldParentId, $newParentId, $refId = null );
}
