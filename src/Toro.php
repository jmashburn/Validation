<?php

class Toro
{
    public static $routes;

    public static $request_method;

    public static $discovered_handler;

    public static $regex_matches = array();

    public static $handler_instance;

    public static function serve($routes)
    {
        ToroHook::fire('before_request', compact('routes'));
        self::$routes = $routes;

        self::$request_method = strtolower($_SERVER['REQUEST_METHOD']);

        $path_info = '/';

        if (! empty($_SERVER['PATH_INFO'])) {
            $path_info = $_SERVER['PATH_INFO'];
        } elseif (! empty($_SERVER['ORIG_PATH_INFO']) && $_SERVER['ORIG_PATH_INFO'] !== '/index.php') {
            $path_info = $_SERVER['ORIG_PATH_INFO'];
        } else {
            if (! empty($_SERVER['REQUEST_URI'])) {
                $path_info = (strpos($_SERVER['REQUEST_URI'], '?') > 0) ? strstr($_SERVER['REQUEST_URI'], '?', true) : $_SERVER['REQUEST_URI'];
            }
        }
        
        self::$discovered_handler = null;
        self::$regex_matches = array();

        if (isset(self::$routes[$path_info])) {
            self::$discovered_handler = self::$routes[$path_info];
        }
        else if (self::$routes) {
            $tokens = array(
                ':string' => '([a-zA-Z]+)',
                ':number' => '([0-9]+)',
                ':alpha'  => '([a-zA-Z0-9-_]+)'
            );
            foreach (self::$routes as $pattern => $handler_name) {
                $pattern = strtr($pattern, $tokens);
                if (preg_match('#^/?' . $pattern . '/?$#', $path_info, $matches)) {
                    self::$discovered_handler = $handler_name;
                    self::$regex_matches = $matches;
                    break;
                }
            }
        }

        $result = null;

        self::$handler_instance = null;
        if (self::$discovered_handler) {
            if (is_string(self::$discovered_handler)) {
                self::$handler_instance = new self::$discovered_handler();
            }
            elseif (is_callable($discovered_handler)) {
                self::$handler_instance = self::$discovered_handler();
            }
        }

        if (self::$handler_instance) {
            unset(self::$regex_matches[0]);

            if (self::is_xhr_request() && method_exists(self::$handler_instance, self::$request_method . '_xhr')) {
                header('Content-type: application/json');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                header('Cache-Control: no-store, no-cache, must-revalidate');
                header('Cache-Control: post-check=0, pre-check=0', false);
                header('Pragma: no-cache');
                self::$request_method .= '_xhr';
            }

            if (method_exists(self::$handler_instance, self::$request_method)) {
                ToroHook::fire('before_handler', array('routes' => self::$routes, 
                    'discovered_handler' => self::$discovered_handler, 'request_method' => self::$request_method, 
                    'regex_matches' => self::$regex_matches));

                $result = call_user_func_array(array(self::$handler_instance, self::$request_method), self::$regex_matches);
                
                ToroHook::fire('after_handler', array('routes' => self::$routes, 
                    'discovered_handler' => self::$discovered_handler, 'request_method' => self::$request_method, 
                    'regex_matches' => self::$regex_matches, 'result' => $result));

            }
            else {
                ToroHook::fire('404', array('routes' => self::$routes, 
                    'discovered_handler' => self::$discovered_handler, 'request_method' => self::$request_method, 
                    'regex_matches' => self::$regex_matches));
            }
        }
        else {
            ToroHook::fire('404', array('routes' => self::$routes, 
                'discovered_handler' => self::$discovered_handler, 'request_method' => self::$request_method, 
                'regex_matches' => self::$regex_matches));
        }

        ToroHook::fire('after_request', array('routes' => self::$routes, 
            'discovered_handler' => self::$discovered_handler, 'request_method' => self::$request_method, 
            'regex_matches' => self::$regex_matches, 'result' => $result));
    }

    private static function is_xhr_request()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}

class ToroHook
{
    private static $instance;

    private $hooks = array();

    private function __construct() {}
    private function __clone() {}

    public static function add($hook_name, $fn, $priority = 1)
    {
        $instance = self::get_instance();
        $instance->hooks[$hook_name][] = array('data' => $fn, 'priority' => (int) $priority);
    }

    public static function fire($hook_name, $params = null)
    {
        $instance = self::get_instance();
        if (isset($instance->hooks[$hook_name])) {
            uksort($instance->hooks[$hook_name], function ($a, $b) use($instance, $hook_name) { 
                if ($instance->hooks[$hook_name][$a]['priority'] == $instance->hooks[$hook_name][$b]['priority']) {
                    return ($a>$b)?1:-1;
                }
                return $instance->hooks[$hook_name][$a]['priority'] < $instance->hooks[$hook_name][$b]['priority']?1:-1;
            });
            foreach ($instance->hooks[$hook_name] as $hook) {
                if (call_user_func_array($hook['data'], array($params)) === false) {
                    break;
                }
            }
        }
    }

    public static function get_instance()
    {
        if (empty(self::$instance)) {
            self::$instance = new ToroHook();
        }
        return self::$instance;
    }
}
