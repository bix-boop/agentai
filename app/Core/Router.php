<?php
namespace App\Core;

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $path = $this->normalize($path);
        $routes = $this->routes[$method] ?? [];
        foreach ($routes as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($route['params'] as $name) {
                    $params[$name] = $matches[$name] ?? null;
                }
                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $action] = $handler;
                    $controller = new $class();
                    $controller->$action(...array_values($params));
                    return;
                }
                call_user_func_array($handler, array_values($params));
                return;
            }
        }
        http_response_code(404);
        echo '404 Not Found';
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $path = $this->normalize($path);
        [$regex, $params] = $this->compile($path);
        $this->routes[$method][] = [
            'path' => $path,
            'regex' => $regex,
            'params' => $params,
            'handler' => $handler,
        ];
    }

    private function normalize(string $path): string
    {
        if ($path === '') {
            $path = '/';
        }
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }
        return rtrim($path, '/') ?: '/';
    }

    private function compile(string $path): array
    {
        $params = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($m) use (&$params) {
            $params[] = $m[1];
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $path);
        $regex = '#^' . $regex . '$#';
        return [$regex, $params];
    }
}