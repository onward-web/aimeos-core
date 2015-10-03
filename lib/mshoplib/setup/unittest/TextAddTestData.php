<?php

/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2012
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 */


/**
 * Adds attribute test data and all items from other domains.
 */
class MW_Setup_Task_TextAddTestData extends MW_Setup_Task_Base
{
	/**
	 * Returns the list of task names which this task depends on.
	 *
	 * @return string[] List of task names
	 */
	public function getPreDependencies()
	{
		return array( 'MShopSetLocale' );
	}


	/**
	 * Returns the list of task names which depends on this task.
	 *
	 * @return string[] List of task names
	 */
	public function getPostDependencies()
	{
		return array( 'CatalogRebuildTestIndex' );
	}


	/**
	 * Executes the task for MySQL databases.
	 */
	protected function mysql()
	{
		$this->process();
	}


	/**
	 * Adds attribute test data.
	 */
	protected function process()
	{
		$iface = 'MShop_Context_Item_Interface';
		if( !( $this->additional instanceof $iface ) ) {
			throw new MW_Setup_Exception( sprintf( 'Additionally provided object is not of type "%1$s"', $iface ) );
		}

		$this->msg( 'Adding text test data', 0 );
		$this->additional->setEditor( 'core:unittest' );

		$ds = DIRECTORY_SEPARATOR;
		$path = dirname( __FILE__ ) . $ds . 'data' . $ds . 'text.php';

		if( ( $testdata = include( $path ) ) == false ) {
			throw new MShop_Exception( sprintf( 'No file "%1$s" found for text domain', $path ) );
		}

		$this->addTextData( $testdata );

		$this->status( 'done' );
	}


	/**
	 * Adds the required text test data for text.
	 *
	 * @param array $testdata Associative list of key/list pairs
	 * @throws MW_Setup_Exception If no type ID is found
	 */
	private function addTextData( array $testdata )
	{
		$textManager = MShop_Text_Manager_Factory::createManager( $this->additional, 'Default' );
		$textTypeManager = $textManager->getSubManager( 'type', 'Default' );

		$ttypeIds = array();
		$ttype = $textTypeManager->createItem();

		$this->conn->begin();

		foreach( $testdata['text/type'] as $key => $dataset )
		{
			$ttype->setId( null );
			$ttype->setCode( $dataset['code'] );
			$ttype->setDomain( $dataset['domain'] );
			$ttype->setLabel( $dataset['label'] );
			$ttype->setStatus( $dataset['status'] );

			$textTypeManager->saveItem( $ttype );
			$ttypeIds[$key] = $ttype->getId();
		}

		$text = $textManager->createItem();
		foreach( $testdata['text'] as $key => $dataset )
		{
			if( !isset( $ttypeIds[$dataset['typeid']] ) ) {
				throw new MW_Setup_Exception( sprintf( 'No text type ID found for "%1$s"', $dataset['typeid'] ) );
			}

			$text->setId( null );
			$text->setLanguageId( $dataset['langid'] );
			$text->setTypeId( $ttypeIds[$dataset['typeid']] );
			$text->setDomain( $dataset['domain'] );
			$text->setLabel( $dataset['label'] );
			$text->setContent( $dataset['content'] );
			$text->setStatus( $dataset['status'] );

			$textManager->saveItem( $text, false );
		}

		$this->conn->commit();
	}
}