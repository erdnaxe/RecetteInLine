<?php
/**
 * Atomik Framework
 * Copyright (c) 2008-2009 Maxime Bouroumeau-Fuseau
 */
define('ATOMIK_VERSION', '2.2.2');
!defined('ATOMIK_APP_ROOT') && define('ATOMIK_APP_ROOT', './app');
/* -------------------------------------------------------------------------------------------
 *  APPLICATION CONFIGURATION
 * ------------------------------------------------------------------------------------------ */
Atomik::reset(array(
    'app' => array(
        'default_action'        => 'index',
        'layout'                => false,
        'disable_layout'        => false,
        'routes'                => array(),
        'force_uri_extension'   => false,
        'escaping' => array('default' => array('htmlspecialchars', 'nl2br')),
        'filters' => array(
            'rules'             => array(),
            'callbacks'         => array(),
            'default_message'   => 'The %s field failed to validate',
            'required_message'  => 'The %s field must be filled'
        ),
        'views' => array(
            'file_extension'     => '.phtml',
            'engine'             => false,
            'default_context'    => 'html',
            'context_param'      => 'format',
            'contexts' => array(
                'html' => array(
                    'prefix'         => '',
                    'layout'         => true,
                    'content_type'   => 'text/html'
                ),
                'ajax' => array(
                    'prefix'         => '',
                    'layout'         => false,
                    'content_type'   => 'text/html'
                ),
                'xml' => array(
                    'prefix'         => 'xml',
                    'layout'         => false,
                    'content_type'   => 'text/xml'
                ),
                'json' => array(
                    'prefix'         => 'json',
                    'layout'         => false,
                    'content_type'   => 'application/json'
                )
            )
        ),
        'http_method_param'       => '_method',
        'allowed_http_methods'    => array('GET', 'POST', 'PUT', 'DELETE', 'TRACE', 'HEAD', 'OPTIONS', 'CONNECT')
     )       
));

/* -------------------------------------------------------------------------------------------
 *  CORE CONFIGURATION
 * ------------------------------------------------------------------------------------------ */
Atomik::set(array(
    'plugins'                    => array(),
    'atomik' => array(
        'scriptname'			 => __FILE__,
        'base_url'               => null,
        'url_rewriting'          => false,
        'debug'                  => false,
        'trigger'                => 'action',
        'class_autoload'         => true,
        'start_session'          => true,
        'plugin_assets_tpl'      => 'app/plugins/%s/assets/',
        'log' => array(
            'register_default'   => false,
            'level'              => LOG_WARNING,
            'message_template'   => '[%date%] [%level%] %message%'
        ),
        'dirs' => array(
            'app'                => ATOMIK_APP_ROOT,
            'plugins'            => array(ATOMIK_APP_ROOT . '/modules', ATOMIK_APP_ROOT . '/plugins/'),
            'actions'            => ATOMIK_APP_ROOT . '/actions/',
            'views'              => ATOMIK_APP_ROOT . '/views/',
            'layouts'            => array(ATOMIK_APP_ROOT . '/layouts', ATOMIK_APP_ROOT . '/views'),
            'helpers'            => ATOMIK_APP_ROOT . '/helpers/',
            'includes'           => array(ATOMIK_APP_ROOT . '/includes/', ATOMIK_APP_ROOT . '/libraries/'),
            'overrides'          => ATOMIK_APP_ROOT . '/overrides/'
        ),
        'files' => array(
            'index'              => 'index.php',
            'config'             => ATOMIK_APP_ROOT . '/config', // without extension
            'bootstrap'          => ATOMIK_APP_ROOT . '/bootstrap.php',
            'pre_dispatch'       => ATOMIK_APP_ROOT . '/pre_dispatch.php',
            'post_dispatch'      => ATOMIK_APP_ROOT . '/post_dispatch.php',
            '404'                => ATOMIK_APP_ROOT . '/404.php',
            'error'              => ATOMIK_APP_ROOT . '/error.php',
            'log'                => ATOMIK_APP_ROOT . '/log.txt'
        ),
        'catch_errors'           => false,
        'display_errors'         => true,
        'error_report_attrs'     => array(
            'atomik-error'               => 'style="padding: 10px"',
            'atomik-error-title'         => 'style="font-size: 1.3em; font-weight: bold; color: #FF0000"',
            'atomik-error-lines'         => 'style="width: 100%; margin-bottom: 20px; background-color: #fff;'
                                          . 'border: 1px solid #000; font-size: 0.8em"',
            'atomik-error-line'          => '',
            'atomik-error-line-error'    => 'style="background-color: #ffe8e7"',
            'atomik-error-line-number'   => 'style="background-color: #eeeeee"',
            'atomik-error-line-text'     => '',
            'atomik-error-stack'         => ''
        )
    ),
    'start_time' => time() + microtime()
));

/* -------------------------------------------------------------------------------------------
 *  CORE
 * ------------------------------------------------------------------------------------------ */

// creates the A function (shortcut to Atomik::get)
if (!function_exists('A')) {
    function A(){
        $args = func_get_args();
        return call_user_func_array(array('Atomik', 'get'), $args);
    }
}

// starts Atomik unless ATOMIK_AUTORUN is set to false
if (!defined('ATOMIK_AUTORUN') || ATOMIK_AUTORUN === true) Atomik::run();
class Atomik_Exception extends Exception {}
final class Atomik{
	public static $store = array();
	private static $reset = array();
	private static $plugins = array();
	private static $events = array();
	private static $namespaces = array('flash' => array('Atomik', '_getFlashMessages'));
	private static $execContexts = array();
	private static $pluggableApplications = array();
	private static $methods = array();
	private static $loadedHelpers = array();
	public static function run($uri = null, $dispatch = true){
		try {
			@chdir(dirname(A('atomik/scriptname')));
			if (file_exists($filename = self::get('atomik/files/config') . '.php')) {
				if (is_array($config = include($filename))) self::set($config);
			} else if (file_exists($filename = self::get('atomik/files/config') . '.ini')) {
				if (($data = parse_ini_file($filename, true)) === false)
					throw new Atomik_Exception('INI configuration malformed');
				self::set(self::_dimensionizeArray($data, '.'), null, false);
			} else if (file_exists($filename = self::get('atomik/files/config') . '.json')) {
				if (($config = json_decode(file_get_contents($filename), true)) === null) {
					throw new Atomik_Exception('JSON configuration malformed');
				}
				self::set($config);
			}
			$includePaths = self::path(self::get('atomik/dirs/includes', array()), true);
			$includePaths[] = get_include_path();
			set_include_path(implode(PATH_SEPARATOR, $includePaths));
			if (self::get('atomik/catch_errors', true) == true) {
				set_error_handler('Atomik::_errorHandler');
			}
			if (self::get('atomik/debug', false) == true) {
				error_reporting(E_ALL | E_STRICT);
			}
			if (self::get('atomik/log/register_default', false) == true) {
				self::listenEvent('Atomik::Log', 'Atomik::logToFile');
			}
			if (self::get('atomik/start_session', true) == true) {
				session_start();
				self::$store['session'] = &$_SESSION;
			}
			if (self::get('atomik/class_autoload', true) == true) {
				if (!function_exists('spl_autoload_register')) 
					throw new Atomik_Exception('Missing spl_autoload_register function');
				spl_autoload_register('Atomik::autoload');
			}
			$plugins = array();
			foreach (self::get('plugins', array()) as $key => $value) {
				if (!is_string($key)) {
					$key = $value;
					$value = array();
				}
				$plugins[ucfirst($key)] = (array) $value;
			}
			self::set('plugins', $plugins, false);
			$disabledPlugins = array();
			while (count($pluginsToLoad = array_diff(array_keys(self::get('plugins')), self::getLoadedPlugins(), $disabledPlugins)) > 0) {
				foreach ($pluginsToLoad as $plugin) {
					if (self::loadPlugin($plugin) === false) $disabledPlugins[] = $plugin;
			
				}
			}
			if (file_exists($filename = self::get('atomik/files/bootstrap'))) require($filename);
			self::fireEvent('Atomik::Start', array(&$cancel));
			if ($cancel) self::end(true);
			self::log('Starting', LOG_DEBUG);
			if (!self::has('atomik/url_rewriting')) self::set('atomik/url_rewriting', isset($_SERVER['REDIRECT_URL']) || isset($_SERVER['REDIRECT_URI']));
			if ($dispatch) {
				if (!self::dispatch($uri)) self::trigger404();
				self::end(true);
			}
		}catch (Exception $e){
		    self::log('Exception caught: ' . $e->getMessage(), LOG_ERR);
		    if (!self::get('atomik/catch_errors', true)) {
			throw $e;
		    }
		    self::fireEvent('Atomik::Error', array($e));
		    header('Location: ', false, 500);
		    self::renderException($e);
		    self::end(false);
		}
	}
    
	/**
	* Dispatches the request
	*/
	public static function dispatch($uri = null, $allowPluggableApplication = true){
		self::fireEvent('Atomik::Dispatch::Start', array(&$uri, &$allowPluggableApplication, &$cancel));
		if ($cancel) return true;
		if ($uri === null) {
			$trigger = self::get('atomik/trigger', 'action');
			if (isset($_GET[$trigger]) && !empty($_GET[$trigger])) $uri = trim($_GET[$trigger], '/');
			if (self::get('atomik/base_url', null) === null) {
				if (self::get('atomik/url_rewriting') && (isset($_SERVER['REDIRECT_URL']) || isset($_SERVER['REDIRECT_URI']))) {
					$redirectUrl = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['REDIRECT_URI'];
					self::set('atomik/base_url', substr($redirectUrl, 0, -strlen($_GET[$trigger])));
				} else self::set('atomik/base_url', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/');
			}
		} else {
		    if (self::get('atomik/base_url', null) === null) self::set('atomik/base_url', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/');
		}
		if (empty($uri)) $uri = self::get('app/default_action', 'index');
		if (($request = self::route($uri, $_GET)) === false) return false;
		if (strpos($request['action'], '..') !== false || substr($request['action'], 0, 1) == '_'  || strpos($request['action'], '/_') !== false) return false;
		self::set('request_uri', $uri);
		self::set('request', $request);
		if (!self::has('full_request_uri')) self::set('full_request_uri', $uri);
		self::fireEvent('Atomik::Dispatch::Uri', array(&$uri, &$request, &$cancel));
		if ($cancel) return true;
		if ($allowPluggableApplication) {
			foreach (self::$pluggableApplications as $plugin => $pluggAppConfig) {
				if (!self::uriMatch($pluggAppConfig['route'], $uri)) continue;
				$baseAction = trim($pluggAppConfig['route'], '/*');
				$uri = substr(trim($uri, '/'), strlen($baseAction));
				if ($uri == self::get('app/default_action')) $uri = '';
				self::set('atomik/base_action', $baseAction);
				return self::dispatchPluggableApplication($plugin, $uri, $pluggAppConfig['config']);
			}
		}
		$httpMethod = $_SERVER['REQUEST_METHOD'];
		if (($param = self::get('app/http_method_param', false)) !== false) 
			$httpMethod = self::get($param, $httpMethod, $request);
		if (!in_array($httpMethod, self::get('app/allowed_http_methods'))) return false;
		self::set('app/http_method', strtoupper($httpMethod));
		$viewContext = self::get(self::get('app/views/context_param', 'format'), self::get('app/views/default_context', 'html'), $request);
		self::set('app/view_context', $viewContext);
		if (($viewContextParams = self::get('app/views/contexts/' . $viewContext, false)) !== false) {
			if ($viewContextParams['layout'] !== true) self::set('app/layout', $viewContextParams['layout']);
			header('Content-type: ' . self::get('content-type', 'text/html', $viewContextParams));
		}
		self::fireEvent('Atomik::Dispatch::Before', array(&$cancel));
		if ($cancel) return true;
		self::log('Dispatching action ' . $request['action'], LOG_DEBUG);
		if (file_exists($filename = self::get('atomik/files/pre_dispatch'))) require($filename);
		ob_start();
		if (($content = self::execute(self::get('request/action'), $viewContext, false)) === false) return false;
		$content = ob_get_clean() . $content;
		if (($layouts = self::get('app/layout', false)) !== false) {
			if (!empty($layouts) && !self::get('app/disable_layout', false)) {
				foreach (array_reverse((array) $layouts) as $layout) {
					$content = self::renderLayout($layout, $content);
				}
			}
		}
		self::fireEvent('Atomik::Output::Before', array(&$content));
		echo $content;
		self::fireEvent('Atomik::Output::After', array($content));
		self::fireEvent('Atomik::Dispatch::After');
		if (file_exists($filename = self::get('atomik/files/post_dispatch'))) require($filename);
		return true;
	}
    
    /**
     * Checks if an uri matches the pattern. The pattern can contain the * wildcard at the
     * end to specify that it matches the target and all its child segments.
     */
	public static function uriMatch($pattern, $uri = null){
		if ($uri === null) $uri = self::get('request_uri');
		$uri = trim($uri, '/');
		$pattern = trim($pattern, '/');
        
		if (substr($pattern, -1) == '*') {
			$pattern = rtrim($pattern, '/*');
			return strlen($uri) >= strlen($pattern) && substr($uri, 0, strlen($pattern)) == $pattern;
		} else return $uri == $pattern;
	}
    /**
     * Parses an uri to extract parameters
     */
	public static function route($uri, $params = array(), $routes = null){
		if ($routes === null) $routes = self::get('app/routes');
		self::fireEvent('Atomik::Router::Start', array(&$uri, &$routes, &$params));
		$components = parse_url($uri);
		$uri = trim($components['path'], '/');
		$uriSegments = explode('/', $uri);
		$uriExtension = false;
		if (isset($components['query'])) {
			parse_str($components['query'], $query);
			$params = array_merge($query, $params);
		}
		$lastSegment = array_pop($uriSegments);
		if (($dot = strrpos($lastSegment, '.')) !== false) {
			$uriExtension = substr($lastSegment, $dot + 1);
			$lastSegment = substr($lastSegment, 0, $dot);
		}
		$uriSegments[] = $lastSegment;
		if (self::get('app/force_uri_extension', false) && $uriExtension === false) {
			return false;
		}
		$found = false;
		$request = array();
		foreach ($routes as $route => $default) {
			if (!is_string($route)) {
				$route = $default;
				$default = array();
			}
			if (isset($default['@name'])) unset($default['@name']);
			if ($route{0} == '#') {
				if (!preg_match($route, $uri, $matches)) continue;
				unset($matches[0]);
				$found = true;
				$request = array_merge($default, $matches);
				break;
			}
			$valid = true;
			$segments = explode('/', trim($route, '/'));
			$request = $default;
			$extension = false;
			$lastSegment = array_pop($segments);
			if (($dot = strrpos($lastSegment, '.')) !== false) {
				$extension = substr($lastSegment, $dot + 1);
				$lastSegment = substr($lastSegment, 0, $dot);
			}
			$segments[] = $lastSegment;
			if ($extension !== false) {
				if ($extension{0} == ':') {
					if ($uriExtension !== false) 
						$request[substr($extension, 1)] = $uriExtension;
					else if (!isset($request[substr($extension, 1)])) continue;
				} else if ($extension != $uriExtension) continue;
			}
			for ($i = 0, $count = count($segments); $i < $count; $i++) {
				if (substr($segments[$i], 0, 1) == ':') {
					if (isset($uriSegments[$i])) {
						$request[substr($segments[$i], 1)] = $uriSegments[$i];
						$segments[$i] = $uriSegments[$i];
					} else if (!array_key_exists(substr($segments[$i], 1), $default)) {
						$valid = false;
						break;
					}
				} else {
					if (!isset($uriSegments[$i]) || $uriSegments[$i] != $segments[$i]) {
						$valid = false;
						break;
					}
				}
			}
			if ($valid && isset($request['action'])) {
				$found = true;
				if (($count = count($uriSegments)) > ($start = count($segments))) {
					for ($i = $start; $i < $count; $i += 2) {
						if (isset($uriSegments[$i + 1]))
							$request[$uriSegments[$i]] = $uriSegments[$i + 1];
					}
				}
				break;
			}
		}
		if (!$found) $request = array('action' => implode('/', $uriSegments), self::get('app/views/context_param', 'format') => $uriExtension === false ? self::get('app/views/default_context', 'html') : $uriExtension);
		$request = array_merge($params, $request);
		self::fireEvent('Atomik::Router::End', array($uri, &$request));
		return $request;
	}
    
    /**
     * Executes an action
     */
    public static function execute($action, $viewContext = true, $triggerError = true)
    {
        $view = $action;
        $vars = array();
        $render = $viewContext !== false;
        
        if (is_bool($viewContext)) {
            // using the request's context
            $viewContext = self::get('app/view_context');
        }
        // appends the context's prefix to the view name
        $prefix = self::get('app/views/contexts/' . $viewContext . '/prefix', $viewContext);
        if (!empty($prefix)) {
            $view .= '.' . $prefix;
        }
        
        // creates the execution context
        $context = array('action' => &$action, 'view' => &$view, 'render' => &$render);
        self::$execContexts[] =& $context;
    
        self::fireEvent('Atomik::Execute::Start', array(&$action, &$context, &$triggerError));
        if ($action === false) {
            return false;
        }
        
        // checks if the method is specified in $action
        if (($dot = strrpos($action, '.')) !== false) {
            // it is, extract it
            $methodAction = $action;
            $method = substr($action, $dot + 1);
            $action = substr($action, 0, $dot);
        } else {
            // use the current request's http method
            $method = strtolower(self::get('app/http_method'));
            $methodAction = $action . '.' . $method;
        }
        
        // filenames
        $actionFilename = self::actionFilename($action);
        $methodActionFilename = self::actionFilename($methodAction);
        $viewFilename = self::viewFilename($view);
        
        // checks if at least one of the action files or the view file is defined
        if ($actionFilename === false && $methodActionFilename === false && $viewFilename === false) {
            if ($triggerError) {
                throw new Atomik_Exception('Action ' . $action . ' does not exist');
            }
            return false;
        }
        
        if ($viewFilename === false) {
            // no view files, disabling view
            $view = false;
        }
    
        self::fireEvent('Atomik::Execute::Before', array(&$action, &$context, &$actionFilename, &$methodActionFilename, &$triggerError));
    
        // class name if using a class
        $className = str_replace(' ', '_', ucwords(str_replace('/', ' ', $action)));
            
        // executes the global action
        if ($actionFilename !== false) {
            // executes the action in its own scope and fetches defined variables
            $vars = self::executeFile($actionFilename, array(), $className . 'Action');
        }
        
        // executes the method specific action
        if ($methodActionFilename !== false) {
            // executes the action in its own scope and fetches defined variables
            $vars = self::executeFile($methodActionFilename, $vars, $className . ucfirst($method) . 'Action');
        }
    
        self::fireEvent('Atomik::Execute::After', array($action, &$context, &$vars, &$triggerError));
        
        // deletes the execution context
        array_pop(self::$execContexts);
        
        // returns $vars if the view should not be rendered
        if ($render === false) {
            return $vars;
        }
        
        // no view
        if ($view === false) {
            return '';
        }
        
        // renders the view associated to the action
        return self::render($view, $vars, $triggerError);
    }
    
    /**
     * Executes the action file inside a clean scope and returns defined variables
     */
    public static function executeFile($actionFilename, $vars = array(), $className = null)
    {
        self::fireEvent('Atomik::Executefile', array(&$actionFilename, &$vars));
        
        $atomik = new self();
        $vars = $atomik->_execute($actionFilename, $vars, $className);
        $atomik = null;
        
        return $vars;
    }
    
    /**
     * Executes a file inside a clean scope and returns defined variables
     */
    private function _execute($__filename, $__vars = array(), $__className = null)
    {
        extract($__vars);
        require($__filename);
        $vars = array();
        
        // checks if a class is used
        if ($__className !== null && class_exists($__className, false) && method_exists($__className, 'execute')) {
            // call the class execute() static method
            if (($vars = call_user_func(array($__className, 'execute'))) === null) {
                $vars = array();
            }
            $vars = array_merge(get_class_vars($__className), $vars);
        }
        
        // retreives "public" variables (not prefixed with an underscore)
        $definedVars = get_defined_vars();
        foreach ($definedVars as $name => $value) {
            if (substr($name, 0, 1) != '_') {
                $vars[$name] = $value;
            }
        }
        
        return $vars;
    }
    
    /**
     * Prevents the view of the actionfrom which it's called to be rendered
     */
    public static function noRender()
    {
        if (count(self::$execContexts)) {
            self::$execContexts[count(self::$execContexts) - 1]['view'] = false;
        }
    }
    
    /**
     * Modifies the view associted to the action from which it's called
     */
    public static function setView($view)
    {
        if (count(self::$execContexts)) {
            self::$execContexts[count(self::$execContexts) - 1]['view'] = $view;
        }
    }
    
    /**
     * Renders a view
     */
    public static function render($view, $vars = array(), $triggerError = true, $dirs = null)
    {
        if ($dirs === null) {
            $dirs = self::get('atomik/dirs/views');
        }
        
        self::fireEvent('Atomik::Render::Start', array(&$view, &$vars, &$dirs, &$triggerError));
        
        // view filename
        if (($filename = self::viewFilename($view, $dirs)) === false) {
            if ($triggerError) {
                throw new Atomik_Exception('View ' . $view . ' not found');
            }
            return false;
        }
        
        self::fireEvent('Atomik::Render::Before', array(&$view, &$vars, &$filename, $triggerError));
        
        $output = self::renderFile($filename, $vars);
        
        self::fireEvent('Atomik::Render::After', array($view, &$output, $vars, $filename, $triggerError));
        
        return $output;
    }
    
    /**
     * Renders a file using a filename which will not be resolved.
     *
     * @param string $filename   Filename
     * @param array $vars        An array containing key/value pairs that will be transformed to variables accessible inside the file
     * @return string            The output of the rendered file
     */
    public static function renderFile($filename, $vars = array())
    {
        self::fireEvent('Atomik::Renderfile::Before', array(&$filename, &$vars));
        
        if (($callback = self::get('app/views/engine', false)) !== false) {
            if (!is_callable($callback)) {
                throw new Atomik_Exception('The specified rendering engine callback cannot be called');
            }
            $output = $callback($filename, $vars);
            
        } else {
            $atomik = new self();
            $output = $atomik->_render($filename, $vars);
            $atomik = null;
        }
        
        self::fireEvent('Atomik::Renderfile::After', array($filename, &$output, $vars));
        
        return $output;
    }
    
    /**
     * Renders a layout
     * 
     * @param string $layout       Layout name
     * @param string $content      The content that will be available in the layout in the $contentForLayout variable
     * @param array $vars          An array containing key/value pairs that will be transformed to variables accessible inside the layout
     * @param bool $triggerError   Whether to throw an exception if an error occurs
     * @param array $dirs          Directories where to search for layouts
     * @return string
     */
    public static function renderLayout($layout, $content, $vars = array(), $triggerError = true, $dirs = null)
    {
        if ($dirs === null) {
            $dirs = self::get('atomik/dirs/layouts');
        }
        
        self::fireEvent('Atomik::Renderlayout', array(&$layout, &$content, &$vars, &$triggerError, &$dirs));
        $vars['contentForLayout'] = $content;
        
        return self::render($layout, $vars, $triggerError, $dirs);
    }
    
    /**
     * Renders a file (internal/default rendering engine)
     * 
     * @internal
     * @param string $__filename   Filename
     * @param array $__vars        An array containing key/value pairs that will be transformed to variables accessible inside the file
     * @return string              View output
     */
    private function _render($__filename, $__vars = array())
    {
        extract($__vars);
        ob_start();
        include($__filename);
        return ob_get_clean();
    }
    
    /**
     * Loads an helper file
     * 
     * @param string $helperName
     * @param array $dirs          Directories where to search for helpers
     */
    public static function loadHelper($helperName, $dirs = null)
    {
        if (isset(self::$loadedHelpers[$helperName])) {
            return;
        }
        
        if ($dirs === null) {
            $dirs = self::get('atomik/dirs/helpers');
        }
        
        self::fireEvent('Atomik::Loadhelper::Before', array(&$helperName, &$dirs));
        
        if (($filename = self::path($helperName . '.php', $dirs)) === false) {
            throw new Atomik_Exception('Helper ' . $helperName . ' not found');
        }
        
        include $filename;
    
        if (!function_exists($helperName)) {
            // searching for an helper defined as a class
            $camelizedHelperName = str_replace(' ', '', ucwords(str_replace('_', ' ', $helperName)));
            $className = $camelizedHelperName . 'Helper';
            
            if (!class_exists($className, false)) {
                // neither a function nor a class has been found
                throw new Exception('Helper ' . $helperName . ' file found but no function or class matching the helper name');
            }
            // helper defined as a class
            self::$loadedHelpers[$helperName] = array(new $className(), $camelizedHelperName);
            
        } else {
            // helper defined as a function
            self::$loadedHelpers[$helperName] = $helperName;
        }
        
        self::fireEvent('Atomik::Loadhelper::After', array($helperName, $dirs));
    }
    
    /**
     * Executes an helper
     * 
     * @param string $helperName
     * @param array $args          Arguments for the helper
     * @param array $dirs          Directories where to search for helpers
     * @return mixed
     */
    public static function helper($helperName, $args = array(), $dirs = null)
    {
        self::loadHelper($helperName, $dirs);
        return call_user_func_array(self::$loadedHelpers[$helperName], $args);
    }
    
    /**
     * PHP magic method to handle calls to helper in views
     * 
     * @param string $helperName
     * @param array $args
     * @return mixed
     */
    public function __call($helperName, $args)
    {
        if (method_exists('Atomik', $helperName)) {
            return call_user_func_array(array('Atomik', $helperName), $args);
        }
        if (isset(self::$methods[$helperName])) {
            return call_user_func_array(self::$methods[$helperName], $args);
        }
        return self::helper($helperName, $args);
    }
    
    /**
     * Disables the layout
     * 
     * @param bool $disable Whether to disable the layout
     */
    public static function disableLayout($disable = true)
    {
        self::set('app/disable_layout', $disable);
    }

    /**
     * Fires the Atomik::End event and exits the application
     *
     * @param bool $success         Whether the application exit on success or because an error occured
     * @param bool $writeSession    Whether to call session_write_close() before exiting
     */
    public static function end($success = false, $writeSession = true)
    {
        self::fireEvent('Atomik::End', array($success, &$writeSession));
        
        if ($writeSession) {
            session_write_close();
        }
        
        self::log('Ending', LOG_DEBUG);
        exit;
    }
    
    
    /* -------------------------------------------------------------------------------------------
     *  Accessors
     * ------------------------------------------------------------------------------------------ */
    
    /**
     * Sets a key/value pair in the store
     * 
     * If the first argument is an array, values are merged recursively.
     * The array is first dimensionized
     * You can set values from sub arrays by using a path-like key.
     * For example, to set the value inside the array $array[key1][key2]
     * use the key 'key1/key2'
     * Can be used on any array by specifying the third argument
     *
     * @see Atomik::_dimensionizeArray()
     * @param array|string $key     Can be an array to set many key/value
     * @param mixed $value
     * @param bool $dimensionize    Whether to use Atomik::_dimensionizeArray() on $key
     * @param array $array          The array on which the operation is applied
     * @param array $add            Whether to add values or replace them
     */
    public static function set($key, $value = null, $dimensionize = true, &$array = null, $add = false)
    {
        // if $data is null, uses the global store
        if ($array === null) {
            $array = &self::$store;
        }
        
        // setting a key directly
        if (is_string($key)) {
            $parentArrayKey = strpos($key, '/') !== false ? dirname($key) : null;
            $key = basename($key);
            
            $parentArray = &self::getRef($parentArrayKey, $array);
            if ($parentArray === null) {
                $dimensionizedParentArray = self::_dimensionizeArray(array($parentArrayKey => null));
                $array = self::_mergeRecursive($array, $dimensionizedParentArray);
                $parentArray = &self::getRef($parentArrayKey, $array);
            }
            
            if ($add !== false) {
                if (!isset($parentArray[$key]) || $parentArray[$key] === null) {
                    if (!is_array($value)) {
                        $parentArray[$key] = $value;
                        return;
                    }
                    $parentArray[$key] = array();
                } else if (!is_array($parentArray[$key])) {
                    $parentArray[$key] = array($parentArray[$key]);
                }
                
                $value = is_array($value) ? $value : array($value);
                if ($add == 'prepend') {
                    $parentArray[$key] = array_merge_recursive($value, $parentArray[$key]);
                } else {
                    $parentArray[$key] = array_merge_recursive($parentArray[$key], $value);
                }
            } else {
                $parentArray[$key] = $value;
            }
            
            return;
        }
        
        if (!is_array($key)) {
            throw new Atomik_Exception('The first parameter of Atomik::set() must be a string or an array, ' . gettype($key) . ' given');
        }
        
        if ($dimensionize) {
            $key = self::_dimensionizeArray($key);
        }
    
        // merges the store and the array
        if ($add) {
            $array = array_merge_recursive($array, $key);
        } else {
            $array = self::_mergeRecursive($array, $key);
        }
    }
    
    /**
     * Adds a value to the array pointed by the key
     * 
     * If the first argument is an array, values are merged recursively.
     * The array is first dimensionized
     * You can add values to sub arrays by using a path-like key.
     * For example, to add a value to the array $array[key1][key2]
     * use the key 'key1/key2'
     * If the value pointed by the key is not an array, it will be
     * transformed to one.
     * Can be used on any array by specifying the third argument
     *
     * @see Atomik::_dimensionizeArray()
     * @param array|string $key      Can be an array to add many key/value
     * @param mixed $value
     * @param bool $dimensionize     Whether to use Atomik::_dimensionizeArray()
     * @param array $array           The array on which the operation is applied
     */
    public static function add($key, $value = null, $dimensionize = true, &$array = null)
    {
        return self::set($key, $value, $dimensionize, $array, 'append');
    }
    
    /**
     * Prependes a value to the array pointed by the key
     * 
     * Works the same as add()
     *
     * @see Atomik::add()
     * @param array|string $key      Can be an array to add many key/value
     * @param mixed $value
     * @param bool $dimensionize     Whether to use Atomik::_dimensionizeArray()
     * @param array $array           The array on which the operation is applied
     */
    public static function prepend($key, $value = null, $dimensionize = true, &$array = null)
    {
        return self::set($key, $value, $dimensionize, $array, 'prepend');
    }
    
    /**
     * Like array_merge() but recursively
     *
     * @internal
     * @see array_merge()
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public static function _mergeRecursive($array1, $array2)
    {
        $array = $array1;
        foreach ($array2 as $key => $value) {
            if (is_array($value) && array_key_exists($key, $array1) && is_array($array1[$key])) {
                $array[$key] = self::_mergeRecursive($array1[$key], $value);
                continue;
            }
            $array[$key] = $value;
        }
        return $array;
    }
    
    /**
     * Recursively checks array for path-like keys (ie. keys containing slashes)
     * and transform them into multi dimensions array
     *
     * @internal
     * @param array $array
     * @param string $separator
     * @return array
     */
    public static function _dimensionizeArray($array, $separator = '/')
    {
        $dimArray = array();
        
        foreach ($array as $key => $value) {
            // checks if the key is a path
            if (strpos($key, $separator) !== false) {
                $parts = explode($separator, $key);
                $firstPart = array_shift($parts);
                // recursively dimensionize the key
                $value = self::_dimensionizeArray(array(implode($separator, $parts) => $value), $separator);
                
                if (isset($dimArray[$firstPart])) {
                    if (!is_array($dimArray[$firstPart])) {
                        // if $firstPart exists but is not an array, drops the value and use an array
                        $dimArray[$firstPart] = array();
                    }
                    // merge recursively both arrays
                    $dimArray[$firstPart] = self::_mergeRecursive($dimArray[$firstPart], $value);
                } else {
                    $dimArray[$firstPart] = $value;
                }
                
            } else if (is_array($value)) {
                // dimensionize sub arrays
                $value = self::_dimensionizeArray($value, $separator);
                if (isset($dimArray[$key])) {
                    $dimArray[$key] = self::_mergeRecursive($dimArray[$key], $value);
                } else {
                    $dimArray[$key] = $value;
                }
            } else {
                $dimArray[$key] = $value;
            }
        }
        
        return $dimArray;
    }
    
    /**
     * Gets a value using its associatied key from the store
     * 
     * You can fetch value from sub arrays by using a path-like
     * key. Separate each key with a slash. For example if you want 
     * to fetch the value from an $store[key1][key2][key3] you can use
     * key1/key2/key3
     * Can be used on any array by specifying the third argument
     *
     * @param string|array $key   The configuration key which value should be returned. If null, fetches all values
     * @param mixed $default      Default value if the key is not found
     * @param array $array        The array on which the operation is applied
     * @return mixed
     */
    public static function get($key = null, $default = null, $array = null)
    {
        // checks if a namespace is used
        if (is_string($key) && preg_match('/^([a-z]+):(.*)/', $key, $match)) {
            // checks if the namespace exists */
            if (isset(self::$namespaces[$match[1]])) {
                // calls the namespace callback and returns
                $args = func_get_args();
                $args[0] = $match[2];
                return call_user_func_array(self::$namespaces[$match[1]], $args);
            }
        }
        
        if (($value = self::getRef($key, $array)) !== null) {
            return $value;
        }
        
        // key not found, returns default
        return $default;
    }
    
    /**
     * Checks if a key is defined in the store
     * 
     * Can check through sub array using a path-like key
     * Can be used on any array by specifying the second argument
     *
     * @see Atomik::get()
     * @param string $key    The key which should be deleted
     * @param array $array   The array on which the operation is applied
     * @return bool
     */
    public static function has($key, $array = null)
    {
        return self::getRef($key, $array) !== null;
    }
    
    /**
     * Deletes a key from the store
     * 
     * Can delete through sub array using a path-like key
     * Can be used on any array by specifying the second argument
     *
     * @see Atomik::get()
     * @param string $key
     * @param array $array    The array on which the operation is applied
     * @return mixed          The deleted value
     */
    public static function delete($key, &$array = null)
    {
        $parentArrayKey = strpos($key, '/') !== false ? dirname($key) : null;
        $key = basename($key);
        $parentArray = &self::getRef($parentArrayKey, $array);
        
        if ($parentArray === null || !array_key_exists($key, $parentArray)) {
            throw new Atomik_Exception('Key "' . $key . '" does not exists');
        }
        
        $value = $parentArray[$key];
        unset($parentArray[$key]);
        return $value;
    }
    
    /**
     * Gets a reference to a value from the store using its associatied key
     * 
     * You can fetch value from sub arrays by using a path-like
     * key. Separate each key with a slash. For example if you want 
     * to fetch the value from an $store[key1][key2][key3] you can use
     * key1/key2/key3
     * Can be used on any array by specifying the second argument
     *
     * @param string|array $key    The configuration key which value should be returned. If null, fetches all values
     * @param array $array         The array on which the operation is applied
     * @return mixed               Null if the key does not match
     */
    public static function &getRef($key = null, &$array = null)
    {
        $null = null;
        
        // returns the store
        if ($array === null) {
            $array = &self::$store;
        }
        
        // return the whole arrat
        if ($key === null) {
            return $array;
        }
        
        // checks if the $key is an array
        if (!is_array($key)) {
            // checks if it has slashes
            if (!strpos($key, '/')) {
                if (array_key_exists($key, $array)) {
                    $value =& $array[$key];
                    return $value;
                }
                return $null;
            }
            // creates an array by spliting using slashes
            $key = explode('/', $key);
        }
        
        // checks if the key exists
        $firstKey = array_shift($key);
        if (array_key_exists($firstKey, $array)) {
            if (count($key) > 0) {
                // there's still keys so it goes deeper
                return self::getRef($key, $array[$firstKey]);
            } else {
                // the key has been found
                $value =& $array[$firstKey];
                return $value;
            }
        }
        
        return $null;
    }
    
    /**
     * Resets the global store
     * 
     * If no argument are specified the store is resetted, otherwise value are set normally and the
     * state is saved.
     * 
     * @internal 
     * @see Atomik::set()
     * @param array|string $key      Can be an array to set many key/value
     * @param mixed $value
     * @param bool $dimensionize     Whether to use Atomik::_dimensionizeArray() on $key
     */
    public static function reset($key = null, $value = null, $dimensionize = true)
    {
        if ($key !== null) {
            self::set($key, $value, $dimensionize, self::$reset);
            self::set($key, $value, $dimensionize);
            return;
        }
        
        // reset
        self::$store = self::_mergeRecursive(self::$store, self::$reset);
    }
    
    /**
     * Registers a new selector namespace
     * 
     * A namespace preceed a key. When used, $callback will be 
     * called instead of the normal logic. Applies only on get() calls.
     *
     * @param string $namespace
     * @param callback $callback
     */
    public static function registerSelector($namespace, $callback)
    {
        self::$namespaces[$namespace] = $callback;
    }
    
    
    /* -------------------------------------------------------------------------------------------
     *  Plugins
     * ------------------------------------------------------------------------------------------ */
    
    /**
     * Loads a plugin using the configuration specified under plugins
     * 
     * @param string $name
     * @return bool
     */
    public static function loadPlugin($name)
    {
        $name = ucfirst($name);
        if (($config = self::get('plugins/' . $name, array())) === false) {
            return false;
        }
        
        return self::loadCustomPlugin($name, $config);
    }
    
    /**
     * Loads a custom plugin
     *
     * Options:
     *   - dirs:              Directories from where to load the plugin
     *   - classNameTemplate: % will be replaced with the plugin name
     *   - callStart:         Whether to call the start() method on the plugin class
     *
     * @param string $plugin   The plugin name
     * @param array $config    Configuration for this plugin
     * @param array $options   Options for loading this plugin
     * @return bool            Success
     */
    public static function loadCustomPlugin($plugin, $config = array(), $options = array())
    {
        $plugin = ucfirst($plugin);
        $defaultOptions = array(
            'dirs'              => null,
            'classNameTemplate' => '%Plugin',
            'callStart'         => true
        );
        $options = array_merge($defaultOptions, $options);
        
        // checks if the plugin is already loaded
        if (self::isPluginLoaded($plugin)) {
            return true;
        }
        
        // use default directories
        if ($options['dirs'] === null) {
            $options['dirs'] = self::get('atomik/dirs/plugins');
        }
        
        self::fireEvent('Atomik::Plugin::Before', array(&$plugin, &$config, &$options));
        
        // checks if $plugin has been set to false from one of the event callbacks
        if ($plugin === false) {
            return false;
        }
            
        // tries to load the plugin from a file
        if (($filename = self::path($plugin . '.php', $options['dirs'])) === false) {
            // no file, checks for a directory
            if (($dirname = self::path($plugin, $options['dirs'])) === false) {
                // plugin not found
                throw new Atomik_Exception('Missing plugin (no file or directory matching plugin name): ' . $plugin);
            }
            
            // directory found, plugin file should be inside
            $filename = $dirname . '/Plugin.php';
            $appFilename = $dirname . '/Application.php';
            $pluginDir = $dirname;
            
            if (!($isPluggApp = file_exists($appFilename)) && !file_exists($filename)) {
                throw new Atomik_Exception('Missing plugin (no file inside the plugin\'s directory): ' . $plugin);
            }
            
            // registers the plugin as an application if Application.php exists
            if ($isPluggApp && !isset(self::$pluggableApplications[$plugin])) {
                self::registerPluggableApplication($plugin);
            }
            
            // adds the libraries folder from the plugin directory to the include path
            if (@is_dir($dirname . '/libraries')) {
                set_include_path($dirname . '/libraries'. PATH_SEPARATOR . get_include_path());
            }
            
        } else {
            $pluginDir = dirname($filename);
        }
        
        // loads the plugin
        self::log('Loading plugin ' . $plugin, LOG_DEBUG);
        self::executeFile($filename, array('config' => $config));
        
        // checks if the *Plugin class is defined. The use of this class
        // is not mandatory in plugin file
        $pluginClass = str_replace('%', $plugin, $options['classNameTemplate']);
        if (class_exists($pluginClass, false)) {
            $registerEventsCallback = true;
            
            // call the start method on the plugin class if it's defined
            if ($options['callStart'] && method_exists($pluginClass, 'start')) {
                if (call_user_func(array($pluginClass, 'start'), $config) === false) {
                    $registerEventsCallback = false;
                }
            }
            
            // automatically registers events callback for methods starting with "on"
            if ($registerEventsCallback) {
                self::attachClassListeners($pluginClass);
            }
        }
        
        self::fireEvent('Atomik::Plugin::After', array($plugin));
        
        // stores the plugin name so we won't load it twice 
        // also stores the directory from where it was loaded
        self::$plugins[$plugin] = isset($pluginDir) ? rtrim($pluginDir, DIRECTORY_SEPARATOR) : true;
        
        return true;
    }
    
    /**
     * Loads a plugin only if it's available
     * 
     * @see Atomik::loadPlugin()
     */
    public static function loadPluginIfAvailable($plugin)
    {
        if (!self::isPluginLoaded($plugin) && self::isPluginAvailable($plugin)) {
            self::loadPlugin($plugin);
        }
    }
    
    /**
     * Loads a custom plugin only if it's available
     * 
     * @see Atomik::loadPlugin()
     */
    public static function loadCustomPluginIfAvailable($plugin, $config = array(), $options = array())
    {
        if (!self::isPluginLoaded($plugin) && self::isPluginAvailable($plugin)) {
            self::loadCustomPlugin($plugin, $config, $options);
        }
    }
    
    /**
     * Checks if a plugin is already loaded
     *
     * @param string $plugin
     * @return bool
     */
    public static function isPluginLoaded($plugin)
    {
        return isset(self::$plugins[ucfirst($plugin)]);
    }
    
    /**
     * Checks if a plugin is available
     *
     * @param string $plugin
     * @return bool
     */
    public static function isPluginAvailable($plugin)
    {
        if (self::path($plugin . '.php', self::get('atomik/dirs/plugins')) === false) {
            return self::path($plugin, self::get('atomik/dirs/plugins')) !== false;
        }
        return true;
    }
    
    /**
     * Returns all loaded plugins
     * 
     * @param bool $withDir Whether to only returns plugin names or the name (as array key) and the directory
     * @return array
     */
    public static function getLoadedPlugins($withDir = false)
    {
        if ($withDir) {
            return self::$plugins;
        }
        return array_keys(self::$plugins);
    }
    
    /**
     * Registers a pluggable application
     * 
     * Possible configuration keys are:
     *   - rootDir:             directory inside the plugin directory where the application is stored (default empty string)
     *   - pluginDir:           the plugin's directory (default to null, will find the directory automatically)
     *   - overwriteDirs:       whether to keep access to the user actions, views, layouts and helpers folders
     *   - checkPluginIsLoaded: whether to check if the plugin is loaded
     * 
     * @param string $plugin    Plugin's name
     * @param string $route     The route that will trigger the application (default is the plugin name)
     * @param array $config     Configuration
     */
    public static function registerPluggableApplication($plugin, $route = null, $config = array())
    {
        $plugin = ucfirst($plugin);
        
        self::fireEvent('Atomik::Registerpluggableapplication', array(&$plugin, &$route, &$config));
        if (empty($plugin)) {
            return;
        }
        
        // route
        if ($route === null) {
            // default route
            $route = strtolower($plugin) . '/*';
        }
        
        self::$pluggableApplications[$plugin] = array(
            'plugin'   => $plugin,
            'route'    => trim($route, '/'),
            'config'   => $config
        );
    }
    
    /**
     * Dispatches a pluggable application
     * 
     * @see Atomik::registerPluggableApplication()
     * @param string $plugin   Plugin's name
     * @param string $uri      Uri
     * @param array $config    Configuration
     * @return bool            Dispatch success
     */
    public static function dispatchPluggableApplication($plugin, $uri = null, $config = array())
    {
        $plugin = ucfirst($plugin);
        
        // configuration
        $defaultConfig = array(
            'rootDir'             => '', 
            'pluginDir'           => null,
            'resetConfig'         => true, 
            'overwriteDirs'       => true, 
            'checkPluginIsLoaded' => true
        );
        $config = array_merge($defaultConfig, $config);
        
        if ($config['checkPluginIsLoaded'] && !self::isPluginLoaded($plugin)) {
            return false;
        }
        
        // params check
        if (empty($uri)) {
            $uri = '';
        }
        $rootDir = rtrim('/' . trim($config['rootDir'], '/'), '/');
        
        // plugin dir
        if ($config['pluginDir'] === null) {
            $pluginDir = self::$plugins[$plugin];
        } else {
            $pluginDir = rtrim($config['pluginDir'], '/');
        }
        
        // application dir
        $appDir = $pluginDir . $rootDir;
        if (!is_dir($appDir)) {
            throw new Atomik_Exception('To be used as an application, the plugin ' . $plugin . ' must use a directory');
        }
        
        // overrides dir
        $overrideDir = self::path($plugin . $rootDir, self::get('atomik/dirs/overrides'));
        if ($overrideDir === false) {
            $overrideDir = './app/overrides/' . $plugin . $rootDir;
        } else {
            $overrideDir = rtrim($overrideDir, '/');
        }
        
        if (!self::has('app/running_plugin')) {
            // saves user configuration
            self::set('userapp', self::get('app'));
        }
        
        // resets the configuration but keep the layout
        if ($config['resetConfig']) {
            $layout = self::get('app/layout');
            self::reset();
            self::set('app/layout', $layout);
            self::set('app/routes', array());
        }
        self::set('app/running_plugin', $plugin); 
        
        // rewrite dirs
        $dirs = array(
            'actions'   => array($overrideDir . '/actions', $appDir . '/actions'),
            'views'     => array($overrideDir . '/views', $appDir . '/views'),
            'layouts'   => array($overrideDir . '/layouts', $overrideDir . '/views', $appDir . '/layouts', $appDir . '/views'),
            'helpers'   => array($overrideDir . '/helpers', $appDir . '/helpers')
        );
        
        if ($config['overwriteDirs']) {
            $dirs = array_merge(self::get('atomik/dirs'), $dirs);
        } else {
            $dirs = array_merge_recursive($dirs, self::get('atomik/dirs'));
        }
        
        self::set('atomik/dirs', $dirs);
        
        // rewrite files
        $files = self::get('atomik/files');
        $files['pre_dispatch'] = $appDir . '/pre_dispatch.php';
        $files['post_dispatch'] = $appDir . '/post_dispatch.php';
        self::set('atomik/files', $files);
        
        $cancel = false;
        self::fireEvent('Atomik::Dispatchpluginapplication::Ready', array($plugin, &$uri, $config, &$cancel));
        if ($cancel) {
            return true;
        }
        
        // set the uri before including Application.php
        self::set('request_uri', $uri);
        
        // includes the Application.php file, equivalent to bootstrap.php for pluggable applications
        $applicationFile = $appDir . '/Application.php';
        if (file_exists($applicationFile)) {
            $continue = include $applicationFile;
            if ($continue === false) {
                return true;
            }
        }
        
        $cancel = false;
        self::fireEvent('Atomik::Dispatchpluginapplication::Start', array($plugin, &$uri, $config, &$cancel));
        if ($cancel) {
            return true;
        }
        
        self::log('Dispatching pluggable application: ' . $plugin, LOG_DEBUG);
        
        // re-dispatches the application
        return self::dispatch($uri, false);
    }
    
    /**
     * Registers a method that will be available on the Atomik class when using PHP5.3
     * or through Atomik::call() for previous versions.
     * 
     * @param string $method       The method name
     * @param callback $callback   A callback to be called when the method is called
     */
    public static function registerMethod($method, $callback)
    {
        if (!is_callable($callback)) {
            throw new Atomik_Exception('The specified callback for the method ' . $method . ' is not callable');
        }
        self::$methods[$method] = $callback;
    }
    
    /**
     * Calls a registered method
     * 
     * @param string $method    Method name
     * @param args $arg...      Any number of arguments that will be pass to the method
     * @return mixed            The method result
     */
    public static function call($method)
    {
        if (!isset(self::$methods[$method])) {
            throw new Atomik_Exception('Atomik::' . $method . '() not found');
        }
        
        $args = func_get_args();
        array_shift($args);
        
        return call_user_func_array(self::$methods[$method], $args);
    }
    
    /**
     * PHP 5.3 magic method to handle calls to undefined method
     */
    public static function __callStatic($method, $args)
    {
        array_unshift($args, $method);
        return call_user_func_array(array(self, 'callPlugin'), $args);
    }
    
    
    /* -------------------------------------------------------------------------------------------
     *  Events
     * ------------------------------------------------------------------------------------------ */
    
    
    /**
     * Registers a callback to an event
     *
     * @param string $event         Event name
     * @param callback $callback    The callback to call when the event is fired
     * @param int $priority         Listener priority
     * @param bool $important       If true and a listener of the same priority already exists, registers the new listener before the existing one. 
     */
    public static function listenEvent($event, $callback, $priority = 50, $important = false)
    {
        // initialize the current event array */
        if (!isset(self::$events[$event])) {
            self::$events[$event] = array();
        }
        
        // while there is an event with the same priority, checks
        // with an higher or lower priority
        while (isset(self::$events[$event][$priority])) {
            $priority += $important ? -1 : 1;
        }
        
        // stores the callback
        self::$events[$event][$priority] = $callback;
    }
    
    /**
     * Fires an event
     * 
     * @param string $event            The event name
     * @param array $args              Arguments for listeners
     * @param bool $resultAsString     Whether to return all listener results as a string
     * @return array                   An array containing results of each executed listeners
     */
    public static function fireEvent($event, $args = array(), $resultAsString = false)
    {
        $results = array();
        
        // executes all callback
        if (isset(self::$events[$event])) {
            $keys = array_keys(self::$events[$event]); 
            sort($keys);
            foreach ($keys as $key) {
                $callback = self::$events[$event][$key];
                $results[$key] = call_user_func_array($callback, $args);
            }
        }
        
        
        if ($resultAsString) {
            return implode('', $results);
        }
        return $results;
    }
    
    /**
     * Automatically registers event listeners for methods starting with "on"
     *
     * @param string|object $class
     */
    public static function attachClassListeners($class)
    {
        $methods = get_class_methods($class);
        foreach ($methods as $method) {
            if (preg_match('/^on[A-Z].*$/', $method)) {
                $event = preg_replace('/(?<=\\w)([A-Z])/', '::\1', substr($method, 2));
                self::listenEvent($event, array($class, $method));
            }
        }
    }
    
    
    /* -------------------------------------------------------------------------------------------
     *  Utilities
     * ------------------------------------------------------------------------------------------ */
    
    /**
     * Builds a path.
     * 
     * Multiple cases possible:
     * 
     *  1) path($setOfPaths, $asArray = false)
     *     Returns a path from a set of paths (the set can be a string
     *     or an array). If the second argument is true, it returns
     *     all paths from the set as an array
     *
     *  2) path($file, $setOfPaths, $check = true)
     *     Searches for a file in the set of paths and returns the
     *     first one it finds. If $check is set to false, it returns
     *     the filename as if it was in the first path from the set of
     *     paths. Returns false when $check is true and no file where found.
     *
     * @param string|array $file
     * @param string|array|bool $paths
     * @param bool $check
     * @return string|array
     */
    public static function path($file, $paths = null, $check = true)
    {
        // case1, $file is an array
        if (is_array($file)) {
            if ($paths === true) {
                // returns $paths as array
                return $file;
            }
            // returns the first path from the paths
            return $file[0];
        }
        // $file is a string
        
        // case 1
        if ($paths === null || is_bool($paths)) {
            if ($paths === true) {
                // returns $file as array
                return array($file);
            }
            // returns $file as string
            return $file;
        }
        
        // case 2, $paths is a string
        if (is_string($paths)) {
            $filename = rtrim($paths, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
            if (!$check || file_exists($filename)) {
                return $filename;
            }
            return false;
        }
        
        // case 2, $paths is an array
        if (is_array($paths)) {
            foreach ($paths as $path) {
                $filename = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
                if (!$check || file_exists($filename)) {
                    return $filename;
                }
            }
        }
        
        // nothing found
        return false;
    }
    
    /**
     * Returns an action's filename
     * 
     * @see Atomik::path()
     * @param string $action     Action name
     * @param array $dirs        Directories where actions are stored (default is using configuration)
     * @return string
     */
    public static function actionFilename($action, $dirs = null)
    {
        if ($dirs === null) {
            $dirs = self::get('atomik/dirs/actions');
        }
        
        if (($filename = self::path($action . '.php', $dirs)) === false) {
            return self::path($action . '/index.php', $dirs);
        }
        return $filename;
    }
    
    /**
     * Returns a view's filename
     * 
     * @see Atomik::path()
     * @param string $view         View name
     * @param array $dirs          Directories where views are stored (default is using configuration)
     * @param string $extension    View's file extension
     * @return string
     */
    public static function viewFilename($view, $dirs = null, $extension = null)
    {
        if ($dirs === null) {
            $dirs = self::get('atomik/dirs/views');
        }
        if ($extension === null) {
            $extension = ltrim(self::get('app/views/file_extension'), '.');
        }
        
        if (($filename = self::path($view . '.' . $extension, $dirs)) === false) {
            return self::path($view . '/index.' . $extension, $dirs);
        }
        return $filename;
    }
    
    /**
     * Returns an url for the action depending on whether url rewriting
     * is used or not. Will return an url relative to the current application scope.
     * Ie. if a plugin uses this method, it will return an url for an action of itself
     * 
     * Can be used on links starting with a protocol but they will of course
     * not be resolved like action names.
     * Named routes can also be used (only specify the route name - with the @ - as the action)
     *
     * The item "_merge_GET" can be use in $params to merge GET parameters with specified params.
     *
     * @param string $action         The action name or an url. Can contain GET parameters (after ?)
     * @param array $params          GET parameters to be added to the query string, if true will reuse current GET params
     * @param bool $useIndex         Whether to use index.php in the url
     * @param bool $useBaseAction    Whether to prepend the action with atomik/base_action
     * @return string
     */
    public static function url($action = null, $params = array(), $useIndex = true, $useBaseAction = true)
    {
        $trigger = self::get('atomik/trigger', 'action');
        
        if ($params === false) {
            $params = array();
        }
        
        if ($params === true || in_array('__merge_GET', $params)) {
            if (!is_array($params)) {
                $params = array();
            }
            $GET = $_GET;
            if (isset($GET[$trigger])) {
                unset($GET[$trigger]);
            }
            if (($i = array_search('__merge_GET', $params)) !== false) {
                unset($params[$i]);
            }
            $params = self::_mergeRecursive($GET, $params);
        }
        
        if ($action === null) {
            $action = self::get('request_uri');
        }
        
        // removes the query string from the action
        if (($separator = strpos($action, '?')) !== false) {
            $queryString = parse_url($action, PHP_URL_QUERY);
            $action = substr($action, 0, $separator);
            parse_str($queryString, $actionParams);
            $params = self::_mergeRecursive($actionParams, $params);
        }
        
        // checks if it's a named route
        if ($action{0} == '@') {
        	$routeName = substr($action, 1);
        	$action = null;
        	foreach (self::get('app/routes') as $route => $default) {
        		if (!is_array($default) || !isset($default['@name']) || 
        			$default['@name'] != $routeName) {
        			    continue;
        		}
			    if ($route{0} == '#') {
			        throw new Atomik_Exception('Named route (' . $routeName . 
			        	') cannot use regular expressions');
			    }
    			$action = $route;
    			break;
        	}
            if ($action === null) {
                throw new Atomik_Exception('Missing route named ' . $routeName);
            }
        }
        
        // injects parameters into the url
        if (preg_match_all('/(:([a-zA-Z0-9_]+))/', $action, $matches)) {
            for ($i = 0, $c = count($matches[0]); $i < $c; $i++) {
                if (array_key_exists($matches[2][$i], $params)) {
                    $action = str_replace($matches[1][$i], $params[$matches[2][$i]], $action);
                    unset($params[$matches[2][$i]]);
                }
            }
        }
        
        // checks if $action is not a url (checking if there is a protocol)
        if (!preg_match('/^([a-z]+):\/\/.*/', $action)) {
            $action = ltrim($action, '/');
            $url = rtrim(self::get('atomik/base_url', '.'), '/') . '/';
            
            if ($useBaseAction) {
                $action = ltrim(trim(self::get('atomik/base_action', ''), '/') . '/' . $action, '/');
            }
            
            // checks if url rewriting is used
            if (!$useIndex || self::get('atomik/url_rewriting', false) == true) {
                $useIndex = false;
                $url .= $action;
            } else {
                // no url rewriting, using index.php
                $url .= self::get('atomik/files/index', 'index.php');
                $url .= '?' . $trigger . '=' . $action;
            }
        } else {
            $useIndex = false;
            $url = $action;
        }
        
        if (count($params)) {
            $url .= ($useIndex ? '&amp;' : '?') . http_build_query($params, '', '&amp;');
        }
        
        // trigger an event
        $args = func_get_args();
        unset($args[0]);    
        self::fireEvent('Atomik::Url', array($action, &$url, $args));
        
        return $url;
    }
    
    /**
     * Returns an url exactly like Atomik::url() but relative to the root application.
     *
     * @see Atomik::url()
     * @param string $action
     * @param array $params
     * @param bool $useIndex
     * @return string
     */
    public static function appUrl($action, $params = array(), $useIndex = true)
    {
        return self::url($action, $params, $useIndex, false);
    }
    
    /**
     * Returns an url exactly like Atomik::url() but relative to a plugin
     *
     * @see Atomik::url()
     * @param string $plugin   The name of a plugin which is used as a pluggable application
     * @param string $action
     * @param array $params
     * @param bool $useIndex
     * @return string
     */
    public static function pluginUrl($plugin, $action, $params = array(), $useIndex = true)
    {
    	$plugin = ucfirst($plugin);
        if (!isset(self::$pluggableApplications[$plugin])) {
            throw new Atomik_Exception('Plugin ' . $plugin . ' is not registered as a pluggable application');
        }
        
        $route = rtrim(self::$pluggableApplications[$plugin]['route'], '/*');
        return self::url($route . '/' . ltrim($action, '/'), $params, $useIndex, false);
    }
    
    /**
     * Returns the url of an asset file (ie. an url without index.php) relative
     * to the current application scope
     * 
     * @see Atomik::url()
     * @param string $filename
     * @param array $params
     * @return string
     */
    public static function asset($filename, $params = array())
    {
        if (self::has('app/running_plugin')) {
            if (($plugin = self::get('app/running_plugin')) === null) {
                throw new Atomik_Exception('Invalid running plugin name');
            }
            return self::pluginAsset($plugin, $filename, $params);
        }
        return self::appAsset($filename, $params);
    }
    
    /**
     * Returns the url of an asset file relative to the root application
     * 
     * @see Atomik::asset()
     * @param string $filename
     * @param array $params
     * @return string
     */
    public static function appAsset($filename, $params = array())
    {
        return self::url($filename, $params, false, false);
    }
    
    /**
     * Returns the url of a plugin's asset file following the path template
     * defined in the configuration.
     * 
     * @see Atomik::url()
     * @param string $plugin    Plugin's name (default is the currently running pluggable app)
     * @param string $filename
     * @param array $params
     * @return string
     */
    public static function pluginAsset($plugin, $filename, $params = array())
    {
        $template = self::get('atomik/plugin_assets_tpl', 'app/plugins/%s/assets');
        $dirname = rtrim(sprintf($template, ucfirst($plugin)), '/');
        $filename = '/' . ltrim($filename, '/');
        return self::url($dirname . $filename, $params, false, false);
    }

    /*
     * Includes a file
     *
     * @param string $include      Filename or class name following the PEAR convention
     * @param bool $className      If true, $include will be transformed from a PEAR-formed class name to a filename
     * @param string|array $dirs   Include from specific directories rather than include path
     * @return bool
     */
    public static function needed($include, $className = true, $dirs = null)
    {
        self::fireEvent('Atomik::Needed', array(&$include, &$className, &$dirs));
        if ($include === null) {
            return;
        }
        
        if ($dirs === null) {
        	$dirs = explode(PATH_SEPARATOR, get_include_path());
        }
        
        if ($className && strpos($include, '_') !== false) {
            $include = str_replace('_', DIRECTORY_SEPARATOR, $include);
        }
        $include .= '.php';
        
        if (($filename = self::path($include, $dirs)) === false) {
            return false;
        }
        return include_once($filename);
    }
    
    /**
     * Class autoloader
     * 
     * @see spl_autoload_register()
     * @param string $className
     * @return bool
     */
    public static function autoload($className)
    {
        try {
            self::needed($className);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Escapes text so it can be outputted safely
     * 
     * Uses escape profiles defined in the escaping configuration key
     * 
     * @param string $text      The text to escape
     * @param mixed $profile    A profile name, a function name, or an array of function
     * @return string           The escaped string
     */
    public static function escape($text, $profile = 'default')
    {
        if (!is_array($profile)) {
            if (($functions = self::get('app/escaping/' . $profile, false)) === false) {
                if (function_exists($profile)) {
                    $functions = array($profile);
                } else {
                    $functions = array('htmlspecialchars');
                }
            }
        } else {
            $functions = $profile;
        }
        
        foreach ($functions as $function) {
            $text = call_user_func($function, $text);
        }
        
        return $text;
    }
    
    /**
     * Saves a message that can be retrieve only once
     * 
     * @param string|array $message One message as a string or many messages as an array
     * @param string $label
     */
    public static function flash($message, $label = 'default')
    {
        if (!isset($_SESSION)) {
            throw new Atomik_Exception('The session must be started before using Atomik::flash()');
        }
        
        self::fireEvent('Atomik::Flash', array(&$message, &$label));
        
        if (!self::has('session/__FLASH/' . $label)) {
            self::set('session/__FLASH/' . $label, array());
        }
        
        self::add('session/__FLASH/' . $label, $message);
    }
    
    /**
     * Returns the flash messages saved in the session
     * 
     * @internal 
     * @param string $label     Whether to only retreives messages from this label. When null or 'all', returns all messages
     * @param bool $delete      Whether to delete messages once retrieved
     * @return array            An array of messages if the label is specified or an array of array message
     */
    public static function _getFlashMessages($label = 'all', $delete = true) {
        if (!isset($_SESSION['__FLASH'])) {
            return array();
        }
        
        if (empty($label) || $label == 'all') {
        	if ($delete) {
            	return self::delete('session/__FLASH');
        	}
        	return self::get('session/__FLASH');
        }
        
        if (!isset($_SESSION['__FLASH'][$label])) {
            return array();
        }
        
        if ($delete) {
        	return self::delete('session/__FLASH/' . $label);
        }
        return self::get('session/__FLASH/' . $label);
    }
    
    /**
     * Filters data using PHP's filter extension
     * 
     * @see filter_var()
     * @param mixed $data
     * @param mixed $filter
     * @param mixed $options
     * @param bool $falseOnFail
     * @return mixed
     */
    public static function filter($data, $filter = null, $options = null, $falseOnFail = true)
    {
        if (is_array($data)) {
            // the $filter must be a rule or a string to a rule defined under app/filters/rules
            if (is_string($filter)) {
                if (($rule = self::get('app/filters/rules/' . $filter, false)) === false) {
                    throw new Atomik_Exception('When $data is an array, the filter must be an array of definition or a rule name in Atomik::filter()');
                }
            } else {
                $rule = $filter;
            }
            
            $results = array();
            $messages = array();
            $validate = true;
            
            foreach ($rule as $field => $params) {
                if (isset($data[$field]) && is_array($data[$field])) {
                    // data is an array
                    if (($results[$field] = self::filter($data[$field], $params)) === false) {
                        $messages[$field] = A('app/filters/messages', array());
                        $validate = false;
                    }
                    continue;
                }
                
                $filter = FILTER_SANITIZE_STRING;
                $message = self::get('app/filters/default_message', 'The %s field failed to validate');
                $required = false;
                $default = null;
                $label = $field;
                if (is_array($params)) {
                    // extracting information from the array
                    if (isset($params['message'])) {
                        $message = self::delete('message', $params);
                    }
                    if (isset($params['required'])) {
                        $required = self::delete('required', $params);
                    }
                    if (isset($params['default'])) {
                        $default = self::delete('default', $params);
                    }
                    if (isset($params['label'])) {
                        $label = self::delete('label', $params);
                    }
                    if (isset($params['filter'])) {
                        $filter = self::delete('filter', $params);
                    }
                    $options = count($params) == 0 ? null : $params;
                } else {
                    $filter = $params;
                    $options = null;
                }
                
                if (!isset($data[$field]) && !$required) {
                    // field not set and not required, do nothing
                    continue;
                }
                
                if ((!isset($data[$field]) || $data[$field] == '') && $required) {
                    // the field is required and either not set or empty, this is an error
                    $results[$field] = false;
                    $message = self::get('app/filters/required_message', 'The %s field must be filled');
                    
                } else if ($data[$field] === '' && !$required) {
                    // empty but not required, null value
                    $results[$field] = $default;
                    
                } else {
                    // normal, validating
                    $results[$field] = self::filter($data[$field], $filter, $options);
                }
                
                if ($results[$field] === false) {
                    // failed validation, adding the message
                    $messages[$field] = sprintf($message, $label);
                    $validate = false;
                }
            }
            
            self::set('app/filters/messages', $messages);
            return $validate || !$falseOnFail ? $results : false;
        }
        
        if (is_string($filter)) {
            if (in_array($filter, filter_list())) {
                // filter name from the extension filters
                $filter = filter_id($filter);
                
            } else if (preg_match('@/.+/[a-zA-Z]*@', $filter)) {
                // regexp
                $options = array('options' => array('regexp' => $filter));
                $filter = FILTER_VALIDATE_REGEXP;
                
            } else if (($callback = self::get('app/filters/callbacks/' . $filter, false)) !== false) {
                // callback defined under app/filters/callbacks
                $filter = FILTER_CALLBACK;
                $options = $callback;
            } 
        }
        
        return filter_var($data, $filter, $options);
    }
    
    /**
     * Makes a string friendly to urls
     * 
     * @param string $string
     * @return string
     */
    public static function friendlify($string)
    {
        $string = str_replace('-', ' ', $string);
        $string = preg_replace(array('/\s+/', '/[^A-Za-z0-9\-]/'), array('-', ''), $string);
        $string = trim(strtolower($string));
        return $string;
    }

    /**
     * Redirects to another url
     *
     * @see Atomik::url()
     * @param string $url      The url to redirect to
     * @param bool $useUrl     Whether to use Atomik::url() on $url before redirecting
     * @param int $httpCode    The redirection HTTP code
     */
    public static function redirect($url, $useUrl = true, $httpCode = 302)
    {
        self::fireEvent('Atomik::Redirect', array(&$url, &$useUrl, &$httpCode));
        if ($url === false) {
            return;
        }
        
        // uses Atomik::url()
        if ($useUrl) {
            $url = self::url($url);
        }
        
        if (isset($_SESSION)) {
            $session = $_SESSION;
            // seems to prevent a php bug with session before redirections
            session_regenerate_id(true);
            $_SESSION = $session;
            // avoid loosing the session
            session_write_close();
        }
        
        // redirects
        header('Location: ' . $url, true, $httpCode);
        self::end(true, false);
    }
    
    /**
     * Same as Atomik::redirect() but for urls relative to the root application
     * 
     * @see Atomik::redirect()
     * @param string $url      The url to redirect to
     * @param bool $useUrl     Use Atomik::url() on $url before redirecting
     * @param int $httpCode    The redirection HTTP code
     */
    public static function appRedirect($url, $useUrl = true, $httpCode = 302)
    {
        // uses Atomik::appUrl()
        if ($useUrl) {
            $url = self::appUrl($url);
        }
        self::redirect($url, false, $httpCode);
    }
    
    /**
     * Same as Atomik::redirect() but redirects to a pluggable application
     * 
     * @see Atomik::redirect()
     * @param string $url      The url to redirect to
     * @param bool $useUrl     Use Atomik::url() on $url before redirecting
     * @param int $httpCode    The redirection HTTP code
     */
    public static function pluginRedirect($plugin, $url, $useUrl = true, $httpCode = 302)
    {
        // uses Atomik::pluginUrl()
        if ($useUrl) {
            $url = self::pluginUrl($plugin, $url);
        }
        self::redirect($url, false, $httpCode);
    }
    
    /**
     * Triggers a 404 error
     */
    public static function trigger404()
    {
        self::fireEvent('Atomik::404', array(&$cancel));
        if ($cancel) {
            return;
        }
        
        self::log('404 ERROR: ' . self::get('full_request_uri'), LOG_ERR);
        
        // HTTP headers
        header('HTTP/1.0 404 Not Found');
        header('Content-type: text/html');
        
        if (file_exists($filename = self::get('atomik/files/404'))) {
            // includes the 404 error file
            include($filename);
        } else {
            echo '<h1>404 - File not found</h1>';
        }
        
        self::end();
    }
    
    /**
     * Fire an Atomik::Log event to which logger can listen
     * 
     * @param string $message
     * @param int $level
     */
    public static function log($message, $level = 3)
    {
        self::fireEvent('Atomik::Log', array($message, $level));
    }
    
    /**
     * Default logger: log the message to the file defined in atomik/files/log
     * The message template can be define in atomik/log/message_template
     * 
     * @see Atomik::log()
     * @param string $message
     * @param int $level
     */
    public static function logToFile($message, $level)
    {
        if ($level > self::get('atomik/log/level')) {
            return;
        }
        
        $filename = self::get('atomik/files/log');
        $template = self::get('atomik/log/message_template', '[%date%] [%level%] %message%');
        $tags = array(
            '%date%' => @date('Y-m-d H:i:s'), 
            '%level%' => $level,
            '%message%' => $message
        );
        
        $file = fopen($filename, 'a');
        fwrite($file, str_replace(array_keys($tags), array_values($tags), $template) . "\n");
        fclose($file);
        $file = null;
    }
    
    /**
     * Equivalent to var_dump() but can be disabled using the configuration
     *
     * @see var_dump()
     * @param mixed $data    The data which value should be dumped
     * @param bool $force    Always display the dump even if debug from the config is set to false
     * @param bool $echo     Whether to echo or return the result
     * @return string        The result or null if $echo is set to true
     */
    public static function debug($data, $force = false, $echo = true)
    {
        if (!$force && !self::get('atomik/debug', false)) {
            return;
        }
        
        self::fireEvent('Atomik::Debug', array(&$data, &$force, &$echo));
        
        // var_dump() does not support returns
        ob_start();
        var_dump($data);
        $dump = ob_get_clean();
        
        if (!$echo) {
            return $dump;
        }
        
        echo $dump;
    }
    
    /**
     * Catch errors and throw an ErrorException instead
     *
     * @internal
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @param mixed $errcontext
     */
    public static function _errorHandler($errno, $errstr, $errfile = '', $errline = 0, $errcontext = null)
    {
        // handles errors depending on the level defined with error_reporting
        if ($errno <= error_reporting()) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }
    }
    
    /**
     * Renders an exception
     * 
     * @param Exception $exception    The exception which sould ne rendered
     * @param bool $return            Return the output instead of printing it
     * @return string
     */
    public static function renderException($exception, $return = false)
    {    
        // checks if the user defined error file is available
        if (file_exists($filename = self::get('atomik/files/error'))) {
            include($filename);
            self::end(false);
        }
        
        $attributes = self::get('atomik/error_report_attrs');
    
        echo '<div ' . $attributes['atomik-error'] . '>'
           . '<span ' . $attributes['atomik-error-title'] . '>'
           . 'An error has occured!</span>';
        
        // only display error information if atomik/display_errors is true
        if (self::get('atomik/display_errors', false) === false) {
            echo '</div>';
            self::end(false);
        }
        
        // builds the html erro report
        $html = '<br />An error of type <strong>' . get_class($exception) . '</strong> '
              . 'was caught at <strong>line ' . $exception->getLine() . '</strong><br />'
              . 'in file <strong>' . $exception->getFile() . '</strong>'
              . '<p>' . $exception->getMessage() . '</p>'
              . '<table ' . $attributes['atomik-error-lines'] . '>';
        
        // builds the table which display the lines around the error
        $lines = file($exception->getFile());
        $start = $exception->getLine() - 7;
        $start = $start < 0 ? 0 : $start;
        $end = $exception->getLine() + 7;
        $end = $end > count($lines) ? count($lines) : $end; 
        for($i = $start; $i < $end; $i++) {
            // color the line with the error. with standard Exception, lines are
            if($i == $exception->getLine() - (get_class($exception) != 'ErrorException' ? 1 : 0)) {
                $html .= '<tr ' . $attributes['atomik-error-line-error'] . '><td>';
            }
            else {
                $html .= '<tr ' . $attributes['atomik-error-line'] . '>'
                       . '<td ' . $attributes['atomik-error-line-number'] . '>';
            }
            $html .= $i . '</td><td ' . $attributes['atomik-error-line-text'] . '>' 
                   . (isset($lines[$i]) ? htmlspecialchars($lines[$i]) : '') . '</td></tr>';
        }
        
        $html .= '</table>'
               . '<strong>Stack:</strong><p ' . $attributes['atomik-error-stack'] . '>' 
               . nl2br($exception->getTraceAsString())
               . '</p></div>';
        
        echo $html;
    }
}

