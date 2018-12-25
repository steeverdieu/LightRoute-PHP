<?php
namespace LightRoute;

require "Exception/RouteException.php";
require "Route.php";

use LightRoute\Exception\RouteException;

class Router
{

    /**
     * Keep the single instace of this class
     *
     * @var Router
     */
    private static $instance;

    /**
     * Keep the supported request methods of the router 
     *
     * @var array
     */
    private $supportedRequestMethods = ['GET', 'POST'];

    /**
     * Kepp all routes registered in the router
     *
     * @var array
     */
    private $routes = [];

    private function __construct()
    {

    }


    /**
     * Create an instance of Router
     *
     * @return Router
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Router;
        }
        return self::$instance;
    }

    /**
     * Register routes into the router
     *
     * @param string $requestMethod
     * @param string $routeUrl
     * @param Callable $callback 
     * @return Route | \Exception
     */
    public function addRoute(string $requestMethod, string $routeUrl, Callable $callback)
    {
        $requestMethod = strtoupper($requestMethod);
        if (in_array($requestMethod, $this->supportedRequestMethods, true)) {
            $route = new Route($routeUrl, $callback);
            if (!$this->hasRegistered($requestMethod, $route)) {
                $this->routes[$requestMethod][] = $route;
                return $route;
            }
            throw new RouteException("Route [" . $requestMethod . "] is a doublon");
        }
        throw new RouteException("Method " . $requestMethod . " is not supported");
    }

    /**
     * Redirect to a route by using GET as request method
     *
     * @param string $routeName Name of the route to be redirected to
     * @param array $queryParams Query params that needs the route
     * @return void | \Exception
     */
    public function redirect(string $routeName, array $queryParams): void
    {
        if (isset($this->routes['GET'])) {
            foreach($this->routes['GET'] as $route) {
                if ($route->getName() === $routeName) {
                    $path = preg_replace_callback('#:[\w]+#', function($matches) use($queryParams){
                        $paramName = str_replace(':', '', $matches[0]);
                        if (!isset($queryParams[$paramName])) {
                            throw new RouteException("Redirect method need " . strtoupper($paramName) . " to work correctly.");                           
                        }
                        return $queryParams[$paramName];
                    }, $route->getUrl());
                    header('Location:' . $path);
                    break;
                }
            }        
        }
        throw new RouteException("Redirect route for : " . $routeName . " not found");
    }

    /**
     * Resolve the current user's request
     *
     * @return void
     */
    public function resolve()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUrl = $_SERVER['REQUEST_URI'];

        if (isset($this->routes[$requestMethod])) {
            foreach($this->routes[$requestMethod] as $route) {
                if ($route->matchesToUrl($requestUrl)) {
                    if ($route->isQueryParamsValid()) {
                        return $route->execute();
                    } else {
                        throw new RouteException("Route params not valid");
                    }
                }

            }
        }
        throw new RouteException("Route not found");
    }

    /**
     * Check if a route has been already registered
     *
     * @param string $requestMethod
     * @param Route $checkRoute
     * @return boolean | Route
     */
    private function hasRegistered(string $requestMethod, Route $checkRoute)
    {
        if (isset($this->routes[$requestMethod])) {
            foreach ($this->routes[$requestMethod] as $route) {
                if ($route === $checkRoute) {
                    return $route;
                }
            }
        }
        return false;
    }


}