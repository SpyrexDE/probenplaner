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
                foreach ($routesData as $routeData) {
                    // If method is null (any) or matches the request method
                    if ($routeData['method'] === null || $routeData['method'] === $requestMethod) {
                        $pattern = $this->convertRouteToRegex($route);
                        
                        if (preg_match($pattern, $uri, $matches)) {
                            $controller = '\\App\\Controllers\\' . $routeData['controller'];
                            $action = $routeData['action'];
                            
                            // Check if controller exists
                            if (class_exists($controller)) {
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
        
        // Route not found, show 404 page
        header("HTTP/1.0 404 Not Found");
        include APP_ROOT . '/Views/errors/404.php';
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