<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2018
 */


namespace Aimeos\MW\Setup\Task;


/**
 * Adds catalog and product performance records
 */
class CatalogAddPerfData extends \Aimeos\MW\Setup\Task\Base
{
	private $maxBatch;
	private $numCatLevels;
	private $numCategories;
	private $numCatProducts;
	private $numProdVariants;
	private $attributes = [];


	/**
	 * Initializes the task object.
	 *
	 * @param \Aimeos\MW\Setup\DBSchema\Iface $schema Database schema object
	 * @param \Aimeos\MW\DB\Connection\Iface $conn Database connection
	 * @param mixed $additional Additionally provided information for the setup tasks if required
	 * @param array $paths List of paths of the setup tasks ordered by dependencies
	 */
	public function __construct( \Aimeos\MW\Setup\DBSchema\Iface $schema, \Aimeos\MW\DB\Connection\Iface $conn,
		$additional = null, array $paths = [] )
	{
		\Aimeos\MW\Common\Base::checkClass( '\\Aimeos\\MShop\\Context\\Item\\Iface', $additional );

		parent::__construct( $schema, $conn, $additional, $paths );
	}


	/**
	 * Returns the list of task names which this task depends on.
	 *
	 * @return string[] List of task names
	 */
	public function getPreDependencies()
	{
		return ['MShopAddCodeDataUnitperf', 'AttributeAddPerfData', 'LocaleAddPerfData', 'MShopSetLocale'];
	}


	/**
	 * Returns the list of task names which depends on this task.
	 *
	 * @return string[] List of task names
	 */
	public function getPostDependencies()
	{
		return ['CatalogRebuildPerfIndex'];
	}


	/**
	 * Insert catalog nodes and product/catalog relations.
	 */
	public function migrate()
	{
		$this->msg( 'Adding catalog performance data', 0 ); $this->status( '' );


		$fcn = function( array $parents, $catParentId, $numCatPerLevel, $catIdx ) {

			\Aimeos\MShop\Factory::clear();

			$treeFcn = function( array $parents, $catParentId, $catIdx, $level ) use ( &$treeFcn, $numCatPerLevel ) {

				$catItem = $this->addCatalogItem( $catParentId, $catIdx );
				array_unshift( $parents, $catItem );

				if( $level > 0 )
				{
					for( $i = 0; $i < $numCatPerLevel; $i++ ) {
						$treeFcn( $parents, $catItem->getId(), $catIdx . '/' . $i, $level - 1 );
					}
				}
				else
				{
					$this->addProductItems( $parents, $catIdx );
				}

				$this->save( 'catalog', $catItem );
			};

			$treeFcn( $parents, $catParentId, $catIdx, $this->numCatLevels - 1 );

			$this->msg( '- Subtree ' . $catIdx, 1, 'done' );
		};


		$this->init();

		$config = $this->additional->getConfig();
		$this->maxBatch = $config->get( 'setup/unitperf/max-batch', 10000 );
		$this->numCatLevels = $config->get( 'setup/unitperf/num-catlevels', 1 );
		$this->numCategories = $config->get( 'setup/unitperf/num-categories', 10 );
		$this->numCatProducts = $config->get( 'setup/unitperf/num-catproducts', 100 );
		$this->numProdVariants = $config->get( 'setup/unitperf/num-prodvariants', 1000 );

		$process = $this->additional->getProcess();
		$catalogRootItem = $this->addCatalogItem( null, 'home' );

		$numCatPerLevel = round( pow( $this->numCategories, 1 / $this->numCatLevels ) / 5 ) * 5;
		$this->additional->__sleep();

		for( $i = 1; $i <= round( $this->numCategories / pow( $numCatPerLevel, $this->numCatLevels - 1 ) ); $i++ ) {
			$process->start( $fcn, [[$catalogRootItem], $catalogRootItem->getId(), $numCatPerLevel, $i] );
		}

		$process->wait();
	}


	protected function addCatalogItem( $parentId, $catIdx )
	{
		$catalogManager = \Aimeos\MShop\Factory::createManager( $this->additional, 'catalog' );

		$item = $catalogManager->createItem()
			->setLabel( 'category-' . $catIdx )
			->setCode( 'cat-' . $catIdx )
			->setStatus( 1 );

		$item = $this->addCatalogTexts( $item, $catIdx );
		$item = $catalogManager->insertItem( $item, $parentId );

		return $item;
	}


	protected function addCatalogProducts( array $catItems, array $items )
	{
		$catalogListManager = \Aimeos\MShop\Factory::createManager( $this->additional, 'catalog/lists' );

		$promoListItem = $catalogListManager->createItem( 'promotion', 'product' );
		$defListItem = $catalogListManager->createItem( 'default', 'product' );

		$promo = round( $this->numCatProducts / 10 ) ?: 1;

		foreach( $catItems as $idx => $catItem )
		{
			$catItem = clone $catItem; // forget stored product references afterwards

			foreach( $items as $i => $item )
			{
				if( $i % pow( 10, $idx ) === 0 ) {
					$catItem->addListItem( 'product', (clone $defListItem)->setRefId( $item->getId() ) );
				}

				if( ($i + $idx) % $promo === 0 ) {
					$catItem->addListItem( 'product', (clone $promoListItem)->setRefId( $item->getId() ) );
				}
			}

			$this->save( 'catalog', $catItem );
		}
	}


	protected function addCatalogTexts( \Aimeos\MShop\Catalog\Item\Iface $catItem, $catIdx )
	{
		$textManager = \Aimeos\MShop\Factory::createManager( $this->additional, 'text' );
		$catalogListManager = \Aimeos\MShop\Factory::createManager( $this->additional, 'catalog/lists' );

		$textItem = $textManager->createItem( 'name', 'product' )
			->setContent( 'Category ' . $catIdx )
			->setLabel( 'cat-' . $catIdx )
			->setLanguageId( 'en' )
			->setStatus( 1 );

		$listItem = $catalogListManager->createItem( 'default', 'product' );

		return $catItem->addListItem( 'text', $listItem, $textItem );
	}


	protected function addProductAttributes( \Aimeos\MShop\Product\Item\Iface $prodItem, array $attrIds )
	{
		$productListManager = \Aimeos\MShop\Factory::createManager( $this->additional, 'product/lists' );

		$listItem = $productListManager->createItem( 'default', 'attribute' );

		foreach( $attrIds as $attrId ) {
			$prodItem->addListItem( 'attribute', (clone $listItem)->setRefId( $attrId ) );
		}

		$listItem = $productListManager->createItem( 'config', 'attribute' );

		foreach( $this->attributes['sticker'] as $attrId ) {
			$prodItem->addListItem( 'attribute', (clone $listItem)->setRefId( $attrId ) );
		}

		return $prodItem;
	}


	protected function addProductItems( array $catItems = [], $catIdx )
	{
		$articles = $this->shuffle( [
			'shirt', 'skirt', 'jacket', 'pants', 'socks', 'blouse', 'slip', 'sweater', 'dress', 'top',
			'anorak', 'babydoll', 'swimsuit', 'trunks', 'bathrobe', 'beret', 'bra', 'bikini', 'blazer', 'bodysuit',
			'bolero', 'bowler', 'trousers', 'bustier', 'cape', 'catsuit', 'chucks', 'corduroys', 'corsage', 'cutaway',
			'lingerie', 'tricorn', 'bow tie', 'tails', 'leggings', 'galoshes', 'string', 'belt', 'hotpants', 'hat',
			'jumpsuit', 'jumper', 'caftan', 'hood', 'kimono', 'headscarf', 'scarf', 'corset', 'costume', 'tie',
			'cummerbund', 'robe', 'underpants', 'dungarees', 'undershirt', 'camisole', 'mantle', 'bodice', 'topless', 'moonboots',
			'cap', 'nightie', 'negligee', 'overalls', 'parka', 'poncho', 'bloomers', 'pumps', 'pajamas', 'farthingale',
			'sari', 'veil', 'apron', 'swimsuit', 'shorts', 'tuxedo', 'stocking', 'suspender', 'tanga', 'tankini',
			'toga', 'tunic', 'turban', 'jerkin', 'coat', 'suit', 'vest', 'gloves', 'bag', 'briefcase',
			'shoes', 'sandals', 'flip-flops', 'ballerinas', 'slingbacks', 'clogs', 'moccasins', 'sneakers', 'boots', 'slippers',
		] );

		$productManager = \Aimeos\MShop\Factory::createManager( $this->additional, 'product' );
		$productManager->begin();

		$newItem = $productManager->createItem( ( $this->numProdVariants > 0 ? 'select' : 'default' ), 'product' );
		$slice = (int) ceil( $this->maxBatch / ( $this->numProdVariants ?: 1 ) );
		$property = $this->shuffle( $this->attributes['property'] );
		$material = $this->shuffle( $this->attributes['material'] );
		$items = [];

		for( $i = 1; $i <= $this->numCatProducts; $i++ )
		{
			$text = key( $property ) . ' ' . key( $material ) . ' ' . current( $articles );

			$item = (clone $newItem)
				->setLabel( $text . ' (' . $catIdx . ')' )
				->setCode( 'prod-' . $i . ':' . $catIdx )
				->setStatus( 1 );

			$item = $this->addProductAttributes( $item, [current( $property ), current( $material )] );
			$item = $this->addProductTexts( $item, $text, $catIdx );
			$item = $this->addProductMedia( $item, $i );
			$item = $this->addProductPrices( $item, $i );
			$item = $this->addProductVariants( $item, $i );
			$item = $this->addProductSuggestions( $item, $catItems );

			$items[] = $item;

			next( $articles );
			if( current( $articles ) === false )
			{
				reset( $articles ); next( $property );

				if( current( $property ) === false )
				{
					reset( $property ); next( $material );

					if( current( $material ) === false ) {
						reset( $material );
					}
				}
			}

			if( $i % $slice === 0 )
			{
				$productManager->saveItems( $items );
				$this->addCatalogProducts( $catItems, $items );
				$this->addStock( $items );
				$items = [];
			}
		}

		$productManager->saveItems( $items );
		$this->addCatalogProducts( $catItems, $items );
		$this->addStock( $items );

		$productManager->commit();
	}


	protected function addProductMedia( \Aimeos\MShop\Product\Item\Iface $prodItem, $idx )
	{
		$prefix = 'https://demo.aimeos.org/media/';

		$mediaManager = \Aimeos\MShop\Factory::createManager( $this->additional, 'media' );
		$productListManager = \Aimeos\MShop\Factory::createManager( $this->additional, 'product/lists' );

		$litem = $productListManager->createItem( 'default', 'media' );
		$newItem = $mediaManager->createItem( 'default', 'product' );

		foreach( $this->shuffle( range( 0, 3 ) ) as $i )
		{
			$mediaItem = (clone $newItem)
				->setLabel( ($i+1) . '. picture for ' . $prodItem->getLabel() )
				->setPreview( $prefix . 'unitperf/' . ( ( $idx + $i ) % 4 + 1 ) . '.jpg' )
				->setUrl( $prefix . 'unitperf/' . ( ( $idx + $i ) % 4 + 1 ) . '-big.jpg' )
				->setMimeType( 'image/jpeg' )
				->setStatus( 1 );

			$prodItem->addListItem( 'media', (clone $litem)->setPosition( $i ), $mediaItem );
		}

		$mediaItem = (clone $newItem)
			->setPreview( $prefix . 'unitperf/download-preview.jpg' )
			->setUrl( $prefix . 'unitperf/download.pdf' )
			->setMimeType( 'application/pdf' )
			->setLabel( 'PDF download' )
			->setStatus( 1 );

		$litem = $productListManager->createItem( 'download', 'media' );

		return $prodItem->addListItem( 'media', (clone $litem), $mediaItem );
	}


	protected function addProductPrices( \Aimeos\MShop\Product\Item\Iface $prodItem, $idx )
	{
		$priceManager = \Aimeos\MShop\Factory::createManager( $this->additional, 'price' );
		$productListManager = \Aimeos\MShop\Factory::createManager( $this->additional, 'product/lists' );

		$litem = $productListManager->createItem( 'default', 'price' );
		$newItem = $priceManager->createItem( 'default', 'product' );
		$base = rand( 0, 8 ) * 100;

		for( $i = 0; $i < 3; $i++ )
		{
			$priceItem = (clone $newItem)
				->setLabel( $prodItem->getLabel() . ': from ' . ( 1 + $i * 5 ) )
				->setValue( 100 + (( $base + $idx ) % 900) - $i * 10 )
				->setQuantity( 1 + $i * 10 )
				->setCurrencyId( 'EUR' )
				->setRebate( $i * 10 )
				->setStatus( 1 );

			$prodItem->addListItem( 'price', (clone $litem)->setPosition( $i ), $priceItem );
		}

		return $prodItem;
	}


	protected function addProductSuggestions( \Aimeos\MShop\Product\Item\Iface $prodItem, array $catItems )
	{
		if( ( $catItem = reset( $catItems ) ) !== false )
		{
			$productListManager = \Aimeos\MShop\Factory::createManager( $this->additional, 'product/lists' );

			$listItem = $productListManager->createItem( 'suggestion', 'product' );
			$listItems = $catItem->getListItems( 'product' );
			$ids = []; $num = 5;

			while( ( $litem = array_pop( $listItems ) ) !== null && $num > 0 )
			{
				if( !in_array( $litem->getRefId(), $ids ) )
				{
					$prodItem->addListItem( 'product', (clone $listItem)->setRefId( $litem->getRefId() ) );
					$ids[] = $litem->getRefId();
					$num--;
				}
			}
		}

		return $prodItem;
	}


	protected function addProductTexts( \Aimeos\MShop\Product\Item\Iface $prodItem, $label, $catIdx )
	{
		$textManager = \Aimeos\MShop\Factory::createManager( $this->additional, 'text' );
		$productListManager = \Aimeos\MShop\Factory::createManager( $this->additional, 'product/lists' );

		$listItem = $productListManager->createItem( 'default', 'text' );

		$textItem = $textManager->createItem( 'url', 'product' )
			->setContent( str_replace( ' ', '_', $label ) . '_' . $catIdx )
			->setLabel( $label . '(' . $catIdx . ')' )
			->setLanguageId( 'en' )
			->setStatus( 1 );

		$prodItem->addListItem( 'text', (clone $listItem), $textItem );

		$textItem = $textManager->createItem( 'name', 'product' )
			->setLanguageId( 'en' )
			->setContent( $label )
			->setLabel( $label )
			->setStatus( 1 );

		$prodItem->addListItem( 'text', (clone $listItem)->setPosition( 0 ), $textItem );

		$textItem = $textManager->createItem( 'short', 'product' )
			->setContent( 'Short description for ' . $label )
			->setLabel( $label . ' (short)' )
			->setLanguageId( 'en' )
			->setStatus( 1 );

		$prodItem->addListItem( 'text', (clone $listItem)->setPosition( 1 ), $textItem );

		$textItem = $textManager->createItem( 'long', 'product' )
			->setContent( 'Long description for ' . $label . '. This may include some "lorem ipsum" text' )
			->setLabel( $label . ' (long)' )
			->setLanguageId( 'en' )
			->setStatus( 1 );

		$prodItem->addListItem( 'text', (clone $listItem)->setPosition( 2 ), $textItem );

		return $prodItem;
	}


	protected function addProductVariants( \Aimeos\MShop\Product\Item\Iface $prodItem, $idx )
	{
		$productManager = \Aimeos\MShop\Factory::createManager( $this->additional, 'product' );
		$productListManager = \Aimeos\MShop\Factory::createManager( $this->additional, 'product/lists' );

		$defListItem = $productListManager->createItem( 'default', 'product' );
		$varListItem = $productListManager->createItem( 'variant', 'attribute' );
		$newItem = $productManager->createItem( 'default', 'product' );

		$length = $this->shuffle( $this->attributes['length'] );
		$width = $this->shuffle( $this->attributes['width'] );
		$size = $this->shuffle( $this->attributes['size'] );

		for( $i = 0; $i < $this->numProdVariants; $i++ )
		{
			$text = key( $length ) . ', ' . key( $width ) . ', ' . $prodItem->getLabel() . ' (' . key( $size ) . ')';

			$item = (clone $newItem)
				->setCode( 'variant-' . $idx . '/' . $i . ':' . $prodItem->getCode() )
				->setLabel( $text )
				->setStatus( 1 );

			$item->addListItem( 'attribute', (clone $varListItem)->setRefId( current( $length ) ) );
			$item->addListItem( 'attribute', (clone $varListItem)->setRefId( current( $width ) ) );
			$item->addListItem( 'attribute', (clone $varListItem)->setRefId( current( $size ) ) );

			$prodItem->addListItem( 'product', clone $defListItem, $item );

			next( $length );
			if( current( $length ) === false )
			{
				reset( $length ); next( $width );

				if( current( $width ) === false )
				{
					reset( $width ); next( $size );

					if( current( $size ) === false ) {
						reset( $size );
					}
				}
			}
		}

		return $prodItem;
	}


	public function addStock( array $items )
	{
		$stockManager = \Aimeos\MShop\Factory::createManager( $this->additional, 'stock' );

		$stockItem = $stockManager->createItem( 'default', 'product');
		$stocklevels = $this->shuffle( [null, 100, 80, 60, 40, 20, 10, 5, 2, 0] );
		$list = [];

		foreach( $items as $item )
		{
			foreach( $item->getRefItems( 'product', 'default', 'default' ) as $refItem )
			{
				$sitem = clone $stockItem;
				$sitem->setProductCode( $refItem->getCode() );
				$sitem->setStockLevel( current( $stocklevels ) );

				if( next( $stocklevels ) === false ) {
					reset( $stocklevels );
				}

				$list[] = $sitem;
			}

			$sitem = clone $stockItem;
			$list[] = $sitem->setProductCode( $item->getCode() );
		}

		$stockManager->begin();
		$stockManager->saveItems( $list, false );
		$stockManager->commit();
	}


	protected function init()
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->additional, 'attribute' );
		$search = $manager->createSearch()->setSlice( 0, 0x7fffffff );

		foreach( $manager->searchItems( $search ) as $id => $item ) {
			$this->attributes[$item->getType()][$item->getCode()] = $id;
		}
	}


	protected function save( $domain, $item )
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->additional, $domain );

		$manager->begin();
		$item = $manager->saveItem( $item );
		$manager->commit();

		return $item;
	}


	protected function shuffle( array $list )
	{
		$keys = array_keys( $list );
		shuffle( $keys );
		$result = [];

		foreach( $keys as $key ) {
			$result[$key] = $list[$key];
		}

		return $result;
	}
}
