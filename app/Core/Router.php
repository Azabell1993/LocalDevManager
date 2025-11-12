<?php
/**
 * 간단한 라우터
 */
class Router
{
    private static $routes = [];
    
    public static function get($path, $action)
    {
        self::$routes['GET'][$path] = $action;
    }
    
    public static function post($path, $action)
    {
        self::$routes['POST'][$path] = $action;
    }
    
    public static function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // 기본 경로 처리
        if ($path === '/') {
            $path = '/dashboard';
        }
        

        
        // 정확한 경로 매치 먼저 확인
        if (isset(self::$routes[$method][$path])) {
            $action = self::$routes[$method][$path];
            
            if (is_callable($action)) {
                call_user_func($action);
            } elseif (is_string($action)) {
                [$controller, $methodName] = explode('@', $action);
                $controllerClass = $controller;
                $instance = new $controllerClass();
                call_user_func([$instance, $methodName]);
            }
            return;
        }
        
        // 동적 라우팅 처리
        $matched = false;
        
        if (isset(self::$routes[$method])) {
            foreach (self::$routes[$method] as $route => $action) {
                // 동적 파라미터 처리 {id} -> (\d+)
                $pattern = preg_replace('/\{(\w+)\}/', '(\d+)', $route);
                $pattern = '#^' . $pattern . '$#';
                
                if (preg_match($pattern, $path, $matches)) {
                    array_shift($matches); // 전체 매치 제거
                    
                    if (is_callable($action)) {
                        call_user_func_array($action, $matches);
                    } elseif (is_string($action)) {
                        [$controller, $methodName] = explode('@', $action);
                        $controllerClass = $controller;
                        $instance = new $controllerClass();
                        call_user_func_array([$instance, $methodName], $matches);
                    }
                    
                    $matched = true;
                    break;
                }
            }
        }
        
        if (!$matched) {
            http_response_code(404);
            echo "404 - 페이지를 찾을 수 없습니다.";
        }
    }
}