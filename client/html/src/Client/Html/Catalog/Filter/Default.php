<?php

/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2012
 * @license LGPLv3, http://www.arcavias.com/en/license
 * @package Client
 * @subpackage Html
 */


/**
 * Default implementation of catalog filter section HTML clients.
 *
 * @package Client
 * @subpackage Html
 */
class Client_Html_Catalog_Filter_Default
	extends Client_Html_Abstract
{
	private static $_headerSingleton;

	/** client/html/catalog/filter/default/subparts
	 * List of HTML sub-clients rendered within the catalog filter section
	 *
	 * The output of the frontend is composed of the code generated by the HTML
	 * clients. Each HTML client can consist of serveral (or none) sub-clients
	 * that are responsible for rendering certain sub-parts of the output. The
	 * sub-clients can contain HTML clients themselves and therefore a
	 * hierarchical tree of HTML clients is composed. Each HTML client creates
	 * the output that is placed inside the container of its parent.
	 *
	 * At first, always the HTML code generated by the parent is printed, then
	 * the HTML code of its sub-clients. The order of the HTML sub-clients
	 * determines the order of the output of these sub-clients inside the parent
	 * container. If the configured list of clients is
	 *
	 *  array( "subclient1", "subclient2" )
	 *
	 * you can easily change the order of the output by reordering the subparts:
	 *
	 *  client/html/<clients>/subparts = array( "subclient1", "subclient2" )
	 *
	 * You can also remove one or more parts if they shouldn't be rendered:
	 *
	 *  client/html/<clients>/subparts = array( "subclient1" )
	 *
	 * As the clients only generates structural HTML, the layout defined via CSS
	 * should support adding, removing or reordering content by a fluid like
	 * design.
	 *
	 * @param array List of sub-client names
	 * @since 2014.03
	 * @category Developer
	 */
	private $_subPartPath = 'client/html/catalog/filter/default/subparts';

	/** client/html/catalog/filter/search/name
	 * Name of the search part used by the catalog filter client implementation
	 *
	 * Use "Myname" if your class is named "Client_Html_Catalog_Filter_Search_Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the client class name
	 * @since 2014.03
	 * @category Developer
	 */

	/** client/html/catalog/filter/tree/name
	 * Name of the tree part used by the catalog filter client implementation
	 *
	 * Use "Myname" if your class is named "Client_Html_Catalog_Filter_Tree_Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the client class name
	 * @since 2014.03
	 * @category Developer
	 */

	/** client/html/catalog/filter/attribute/name
	 * Name of the attribute part used by the catalog filter client implementation
	 *
	 * Use "Myname" if your class is named "Client_Html_Catalog_Filter_Attribute_Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the client class name
	 * @since 2014.03
	 * @category Developer
	 */
	private $_subPartNames = array( 'search', 'tree', 'attribute' );

	private $_tags = array();
	private $_expire;
	private $_cache;
	private $_view;


	/**
	 * Returns the HTML code for insertion into the body.
	 *
	 * @param string $uid Unique identifier for the output if the content is placed more than once on the same page
	 * @param array &$tags Result array for the list of tags that are associated to the output
	 * @param string|null &$expire Result variable for the expiration date of the output (null for no expiry)
	 * @return string HTML code
	 */
	public function getBody( $uid = '', array &$tags = array(), &$expire = null )
	{
		if( ( $html = $this->_getCached( 'body', $uid ) ) === null )
		{
			$context = $this->_getContext();
			$view = $this->getView();

			try
			{
				$view = $this->_setViewParams( $view, $tags, $expire );

				$html = '';
				foreach( $this->_getSubClients() as $subclient ) {
					$html .= $subclient->setView( $view )->getBody( $uid, $tags, $expire );
				}
				$view->filterBody = $html;
			}
			catch( Client_Html_Exception $e )
			{
				$error = array( $this->_getContext()->getI18n()->dt( 'client/html', $e->getMessage() ) );
				$view->filterErrorList = $view->get( 'filterErrorList', array() ) + $error;
			}
			catch( Controller_Frontend_Exception $e )
			{
				$error = array( $this->_getContext()->getI18n()->dt( 'controller/frontend', $e->getMessage() ) );
				$view->filterErrorList = $view->get( 'filterErrorList', array() ) + $error;
			}
			catch( MShop_Exception $e )
			{
				$error = array( $this->_getContext()->getI18n()->dt( 'mshop', $e->getMessage() ) );
				$view->filterErrorList = $view->get( 'filterErrorList', array() ) + $error;
			}
			catch( Exception $e )
			{
				$context->getLogger()->log( $e->getMessage() . PHP_EOL . $e->getTraceAsString() );

				$error = array( $context->getI18n()->dt( 'client/html', 'A non-recoverable error occured' ) );
				$view->filterErrorList = $view->get( 'filterErrorList', array() ) + $error;
			}

			/** client/html/catalog/filter/default/template-body
			 * Relative path to the HTML body template of the catalog filter client.
			 *
			 * The template file contains the HTML code and processing instructions
			 * to generate the result shown in the body of the frontend. The
			 * configuration string is the path to the template file relative
			 * to the layouts directory (usually in client/html/layouts).
			 *
			 * You can overwrite the template file configuration in extensions and
			 * provide alternative templates. These alternative templates should be
			 * named like the default one but with the string "default" replaced by
			 * an unique name. You may use the name of your project for this. If
			 * you've implemented an alternative client class as well, "default"
			 * should be replaced by the name of the new class.
			 *
			 * @param string Relative path to the template creating code for the HTML page body
			 * @since 2014.03
			 * @category Developer
			 * @see client/html/catalog/filter/default/template-header
			 */
			$tplconf = 'client/html/catalog/filter/default/template-body';
			$default = 'catalog/filter/body-default.html';

			$html = $view->render( $this->_getTemplate( $tplconf, $default ) );

			$this->_setCached( 'body', $uid, $html, $tags, $expire );
		}
		else
		{
			$html = $this->modifyBody( $html, $uid );
		}

		return $html;
	}


	/**
	 * Returns the HTML string for insertion into the header.
	 *
	 * @param string $uid Unique identifier for the output if the content is placed more than once on the same page
	 * @param array &$tags Result array for the list of tags that are associated to the output
	 * @param string|null &$expire Result variable for the expiration date of the output (null for no expiry)
	 * @return string String including HTML tags for the header
	 */
	public function getHeader( $uid = '', array &$tags = array(), &$expire = null )
	{
		if( self::$_headerSingleton !== null ) {
			return '';
		}

		self::$_headerSingleton = true;

		if( ( $html = $this->_getCached( 'header', $uid ) ) === null )
		{
			$context = $this->_getContext();
			$view = $this->getView();

			try
			{
				$view = $this->_setViewParams( $view, $tags, $expire );

				$html = '';
				foreach( $this->_getSubClients() as $subclient ) {
					$html .= $subclient->setView( $view )->getHeader( $uid, $tags, $expire );
				}
				$view->filterHeader = $html;

				/** client/html/catalog/filter/default/template-header
				 * Relative path to the HTML header template of the catalog filter client.
				 *
				 * The template file contains the HTML code and processing instructions
				 * to generate the HTML code that is inserted into the HTML page header
				 * of the rendered page in the frontend. The configuration string is the
				 * path to the template file relative to the layouts directory (usually
				 * in client/html/layouts).
				 *
				 * You can overwrite the template file configuration in extensions and
				 * provide alternative templates. These alternative templates should be
				 * named like the default one but with the string "default" replaced by
				 * an unique name. You may use the name of your project for this. If
				 * you've implemented an alternative client class as well, "default"
				 * should be replaced by the name of the new class.
				 *
				 * @param string Relative path to the template creating code for the HTML page head
				 * @since 2014.03
				 * @category Developer
				 * @see client/html/catalog/filter/default/template-body
				 */
				$tplconf = 'client/html/catalog/filter/default/template-header';
				$default = 'catalog/filter/header-default.html';

				$html = $view->render( $this->_getTemplate( $tplconf, $default ) );

				$this->_setCached( 'header', $uid, $html, $tags, $expire );
			}
			catch( Exception $e )
			{
				$this->_getContext()->getLogger()->log( $e->getMessage() . PHP_EOL . $e->getTraceAsString() );
			}
		}
		else
		{
			$html = $this->modifyHeader( $html, $uid );
		}

		return $html;
	}


	/**
	 * Returns the sub-client given by its name.
	 *
	 * @param string $type Name of the client type
	 * @param string|null $name Name of the sub-client (Default if null)
	 * @return Client_Html_Interface Sub-client object
	 */
	public function getSubClient( $type, $name = null )
	{
		return $this->_createSubClient( 'catalog/filter/' . $type, $name );
	}


	/**
	 * Processes the input, e.g. store given values.
	 * A view must be available and this method doesn't generate any output
	 * besides setting view variables.
	 */
	public function process()
	{
		$context = $this->_getContext();
		$view = $this->getView();

		try
		{
			parent::process();
		}
		catch( MShop_Exception $e )
		{
			$error = array( $this->_getContext()->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->filterErrorList = $view->get( 'filterErrorList', array() ) + $error;
		}
		catch( Controller_Frontend_Exception $e )
		{
			$error = array( $this->_getContext()->getI18n()->dt( 'controller/frontend', $e->getMessage() ) );
			$view->filterErrorList = $view->get( 'filterErrorList', array() ) + $error;
		}
		catch( Client_Html_Exception $e )
		{
			$error = array( $this->_getContext()->getI18n()->dt( 'client/html', $e->getMessage() ) );
			$view->filterErrorList = $view->get( 'filterErrorList', array() ) + $error;
		}
		catch( Exception $e )
		{
			$context->getLogger()->log( $e->getMessage() . PHP_EOL . $e->getTraceAsString() );

			$error = array( $context->getI18n()->dt( 'client/html', 'A non-recoverable error occured' ) );
			$view->filterErrorList = $view->get( 'filterErrorList', array() ) + $error;
		}
	}


	/**
	 * Returns the cache entry for the given unique ID and type.
	 *
	 * @param string $type Type of the cache entry, i.e. "body" or "header"
	 * @param string $uid Unique identifier for the output if the content is placed more than once on the same page
	 * @return string Cached entry or empty string if not available
	 */
	protected function _getCached( $type, $uid )
	{
		if( !isset( $this->_cache ) )
		{
			$context = $this->_getContext();
			$config = $context->getConfig()->get( 'client/html/catalog/filter', array() );

			$keys = array(
				'body' => $this->_getParamHash( array( 'f' ), $uid . ':catalog:filter-body', $config ),
				'header' => $this->_getParamHash( array( 'f' ), $uid . ':catalog:filter-header', $config ),
			);

			$entries = $context->getCache()->getList( $keys );
			$this->_cache = array();

			foreach( $keys as $key => $hash ) {
				$this->_cache[$key] = ( array_key_exists( $hash, $entries ) ? $entries[$hash] : null );
			}
		}

		return ( array_key_exists( $type, $this->_cache ) ? $this->_cache[$type] : null );
	}


	/**
	 * Returns the cache entry for the given type and unique ID.
	 *
	 * @param string $type Type of the cache entry, i.e. "body" or "header"
	 * @param string $uid Unique identifier for the output if the content is placed more than once on the same page
	 * @param string $value Value string that should be stored for the given key
	 * @param array $tags List of tag strings that should be assoicated to the
	 * 	given value in the cache
	 * @param string|null $expire Date/time string in "YYYY-MM-DD HH:mm:ss"
	 * 	format when the cache entry expires
	 */
	protected function _setCached( $type, $uid, $value, array $tags, $expire )
	{
		$context = $this->_getContext();

		try
		{
			$config = $context->getConfig()->get( 'client/html/catalog/filter', array() );
			$key = $this->_getParamHash( array( 'f' ), $uid . ':catalog:filter-' . $type, $config );

			$context->getCache()->set( $key, $value, array_unique( $tags ), $expire );
		}
		catch( Exception $e )
		{
			$msg = sprintf( 'Unable to set cache entry: %1$s', $e->getMessage() );
			$context->getLogger()->log( $msg, MW_Logger_Abstract::NOTICE );
		}
	}


	protected function _getSubClientNames()
	{
		return $this->_getContext()->getConfig()->get( $this->_subPartPath, $this->_subPartNames );
	}


	/**
	 * Sets the necessary parameter values in the view.
	 *
	 * @param MW_View_Interface $view The view object which generates the HTML output
	 * @param array &$tags Result array for the list of tags that are associated to the output
	 * @param string|null &$expire Result variable for the expiration date of the output (null for no expiry)
	 * @return MW_View_Interface Modified view object
	 */
	protected function _setViewParams( MW_View_Interface $view, array &$tags = array(), &$expire = null )
	{
		if( !isset( $this->_view ) )
		{
			$config = $this->_getContext()->getConfig();

			/** client/html/catalog/count/enable
			 * Enables or disables displaying product counts in the catalog filter
			 *
			 * This configuration option allows shop owners to display product
			 * counts in the catalog filter or to disable fetching product count
			 * information.
			 *
			 * The product count information is fetched via AJAX and inserted via
			 * Javascript. This allows to cache parts of the catalog filter by
			 * leaving out such highly dynamic content like product count which
			 * changes with used filter parameter.
			 *
			 * @param boolean Value of "1" to display product counts, "0" to disable them
			 * @since 2014.03
			 * @category User
			 * @category Developer
			 * @see client/html/catalog/count/url/target
			 * @see client/html/catalog/count/url/controller
			 * @see client/html/catalog/count/url/action
			 * @see client/html/catalog/count/url/config
			 */
			if( $config->get( 'client/html/catalog/count/enable', true ) == true )
			{
				/** client/html/catalog/count/url/target
				 * Destination of the URL where the controller specified in the URL is known
				 *
				 * The destination can be a page ID like in a content management system or the
				 * module of a software development framework. This "target" must contain or know
				 * the controller that should be called by the generated URL.
				 *
				 * @param string Destination of the URL
				 * @since 2014.03
				 * @category Developer
				 * @see client/html/catalog/count/url/controller
				 * @see client/html/catalog/count/url/action
				 * @see client/html/catalog/count/url/config
				 */
				$target = $config->get( 'client/html/catalog/count/url/target' );

				/** client/html/catalog/count/url/controller
				 * Name of the controller whose action should be called
				 *
				 * In Model-View-Controller (MVC) applications, the controller contains the methods
				 * that create parts of the output displayed in the generated HTML page. Controller
				 * names are usually alpha-numeric.
				 *
				 * @param string Name of the controller
				 * @since 2014.03
				 * @category Developer
				 * @see client/html/catalog/count/url/target
				 * @see client/html/catalog/count/url/action
				 * @see client/html/catalog/count/url/config
				 */
				$controller = $config->get( 'client/html/catalog/count/url/controller', 'catalog' );

				/** client/html/catalog/count/url/action
				 * Name of the action that should create the output
				 *
				 * In Model-View-Controller (MVC) applications, actions are the methods of a
				 * controller that create parts of the output displayed in the generated HTML page.
				 * Action names are usually alpha-numeric.
				 *
				 * @param string Name of the action
				 * @since 2014.03
				 * @category Developer
				 * @see client/html/catalog/count/url/target
				 * @see client/html/catalog/count/url/controller
				 * @see client/html/catalog/count/url/config
				 */
				$action = $config->get( 'client/html/catalog/count/url/action', 'count' );

				/** client/html/catalog/count/url/config
				 * Associative list of configuration options used for generating the URL
				 *
				 * You can specify additional options as key/value pairs used when generating
				 * the URLs, like
				 *
				 *  client/html/<clientname>/url/config = array( 'absoluteUri' => true )
				 *
				 * The available key/value pairs depend on the application that embeds the e-commerce
				 * framework. This is because the infrastructure of the application is used for
				 * generating the URLs. The full list of available config options is referenced
				 * in the "see also" section of this page.
				 *
				 * @param string Associative list of configuration options
				 * @since 2014.03
				 * @category Developer
				 * @see client/html/catalog/count/url/target
				 * @see client/html/catalog/count/url/controller
				 * @see client/html/catalog/count/url/action
				 * @see client/html/url/config
				 */
				$config = $config->get( 'client/html/catalog/count/url/config', array() );

				$params = $this->_getClientParams( $view->param(), array( 'f' ) );

				$view->filterCountUrl = $view->url( $target, $controller, $action, $params, array(), $config );
			}

			$this->_view = $view;
		}

		return $this->_view;
	}
}
