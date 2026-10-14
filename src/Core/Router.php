<?php
namespace App\Core;

/**
 * Router Class
 * Handles URL routing and dispatching to controllers
 */
class Router
{
    /**
     * @var array
     */
    private $routes = [];

    /**
     * @var array Path prefix => controller/action for requests below it that match no route
     */
    private $fallbacks = [];
    
    /**
     * Add a route
     * 
     * @param string $route The URL route
     * @param string $controller The controller name
     * @param string $action The controller method to call
     * @param string $method The HTTP method (GET, POST, etc.) - defaults to ANY
     * @return void
     */
    public function addRoute($route, $controller, $action, $method = null)
    {
        $this->routes[$route][] = [
            'controller' => $controller,
            'action' => $action,
            'method' => $method
        ];
    }
    
    /**
     * Send every request below a path prefix that matches no route to one action (e.g. the
     * 404/405 answers of a JSON API). Requests below the prefix are only matched against
     * routes below it, so the '/{org_slug}/...' routes never catch them.
     *
     * @param string $prefix Path prefix, e.g. '/api/v1'
     * @param string $controller The controller name
     * @param string $action Called with ['allow' => methods of the routes that match the
     *                       path with another method; empty for an unknown path]
     * @return void
     */
    public function addFallback($prefix, $controller, $action)
    {
        $this->fallbacks[rtrim($prefix, '/')] = [
            'controller' => $controller,
            'action' => $action
        ];
    }
    
    /**
     * Dispatch the route
     * 
     * @param string $uri The requested URI
     * @return void
     */
    public function dispatch($uri)
    {
        // Get the HTTP method
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        
        // Remove trailing slash if not root
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }
        $scope = $this->scopeOf($uri);
        
        // Check if route exists
        if (array_key_exists($uri, $this->routes)) {
            // Look for a matching route with the correct HTTP method
            foreach ($this->routes[$uri] as $routeData) {
                // If method is null (any) or matches the request method
                if ($routeData['method'] === null || $routeData['method'] === $requestMethod) {
                    $controller = '\\App\\Controllers\\' . $routeData['controller'];
                    $action = $routeData['action'];
                    
                    // Check if controller exists
                    if (class_exists($controller)) {
                        CSRF::enforce($uri);
                        $controllerInstance = new $controller();
                        
                        // Check if action exists
                        if (method_exists($controllerInstance, $action)) {
                            $controllerInstance->$action();
                            return;
                        }
                    }
                }
            }
        } else {
            // Check for routes with parameters
            foreach ($this->routes as $route => $routesData) {
                if ($this->scopeOf($route) !== $scope) {
                    continue;
                }
                foreach ($routesData as $routeData) {
                    // If method is null (any) or matches the request method
                    if ($routeData['method'] === null || $routeData['method'] === $requestMethod) {
                        $pattern = $this->convertRouteToRegex($route);
                        
                        if (preg_match($pattern, $uri, $matches)) {
                            $controller = '\\App\\Controllers\\' . $routeData['controller'];
                            $action = $routeData['action'];
                            
                            // Check if controller exists
                            if (class_exists($controller)) {
                                CSRF::enforce($route);
                                $controllerInstance = new $controller();
                                
                                // Check if action exists
                                if (method_exists($controllerInstance, $action)) {
                                    // Extract route parameters
                                    $params = $this->extractRouteParameters($route, $uri);
                                    
                                    // Call the controller method with parameters
                                    $controllerInstance->$action($params);
                                    return;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        if ($scope !== null) {
            $controller = '\\App\\Controllers\\' . $this->fallbacks[$scope]['controller'];
            $action = $this->fallbacks[$scope]['action'];
            (new $controller())->$action(['allow' => $this->allowedMethods($uri, $scope)]);
            return;
        }
        
        // Route not found, show 404 page
        header("HTTP/1.0 404 Not Found");
        include APP_ROOT . '/Views/errors/404.php';
    }
    
    /**
     * Fallback prefix the path lies below, or null
     * 
     * @param string $path Request URI or route pattern
     * @return string|null
     */
    private function scopeOf($path)
    {
        foreach (array_keys($this->fallbacks) as $prefix) {
            if ($path === $prefix || strncmp($path, $prefix . '/', strlen($prefix) + 1) === 0) {
                return $prefix;
            }
        }
        return null;
    }
    
    /**
     * Methods of the routes below the prefix that match the URI
     * 
     * @param string $uri The requested URI
     * @param string $scope Fallback prefix
     * @return string[]
     */
    private function allowedMethods($uri, $scope)
    {
        $methods = [];
        foreach ($this->routes as $route => $routesData) {
            if ($this->scopeOf($route) !== $scope || !preg_match($this->convertRouteToRegex($route), $uri)) {
                continue;
            }
            foreach ($routesData as $routeData) {
                if ($routeData['method'] !== null) {
                    $methods[] = $routeData['method'];
                }
            }
        }
        return array_values(array_unique($methods));
    }
    
    /**
     * Convert route with parameters to regex pattern
     * 
     * @param string $route The route pattern
     * @return string The regex pattern
     */
    private function convertRouteToRegex($route)
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);
        return '~^' . $pattern . '$~';
    }
    
    /**
     * Extract parameters from a route
     * 
     * @param string $route The route pattern
     * @param string $uri The actual URI
     * @return array The extracted parameters
     */
    private function extractRouteParameters($route, $uri)
    {
        $params = [];
        
        // If there are no parameters, return empty array
        if (strpos($route, '{') === false) {
            return $params;
        }
        
        // Get parameter names from route
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $route, $paramNames);
        
        // Get parameter values from URI
        $pattern = $this->convertRouteToRegex($route);
        preg_match($pattern, $uri, $paramValues);
        
        // Skip the first value (full match)
        array_shift($paramValues);
        
        // Combine names and values into an associative array
        foreach ($paramNames[1] as $index => $name) {
            $params[$name] = isset($paramValues[$index]) ? $paramValues[$index] : null;
        }
        
        return $params;
    }
} 