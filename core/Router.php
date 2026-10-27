<?php

namespace Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][trim($path, '/')] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][trim($path, '/')] = $handler;
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->routes['PUT'][trim($path, '/')] = $handler;
    }

    public function patch(string $path, callable|array $handler): void
    {
        $this->routes['PATCH'][trim($path, '/')] = $handler;
    }

    public function delete(string $path, callable|array $handler): void
    {
        $this->routes['DELETE'][trim($path, '/')] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri = trim($uri, '/');

        if (isset($this->routes[$method][$uri])) {
            $this->callHandler($this->routes[$method][$uri]);
            return;
        }

        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            if ($this->matchPattern($route, $uri)) {
                $this->callHandler($handler, $this->extractParams($route, $uri));
                return;
            }
        }

        Response::error('Route not found', 404);
    }

    private function callHandler(callable|array $handler, array $params = []): void
    {
        if (is_array($handler)) {
            [$controller, $method] = $handler;
            $instance = new $controller();
            $result = $instance->$method(...$params);
        } else {
            $result = $handler(...$params);
        }

        if (is_array($result)) {
            Response::json($result);
            return;
        }

        if ($result === null) {
            Response::json(['success' => true]);
        }
    }

    private function matchPattern(string $route, string $uri): bool
    {
        $route = trim($route, '/');
        $uri = trim($uri, '/');

        if ($route === $uri) {
            return true;
        }

        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);
        return (bool) preg_match('#^' . $pattern . '$#', $uri);
    }

    private function extractParams(string $route, string $uri): array
    {
        $routeParts = explode('/', trim($route, '/'));
        $uriParts = explode('/', trim($uri, '/'));
        $params = [];

        foreach ($routeParts as $index => $part) {
            if (preg_match('/^\{([a-zA-Z0-9_]+)\}$/', $part, $matches)) {
                $params[$matches[1]] = $uriParts[$index] ?? null;
            }
        }

        return array_values($params);
    }
}
