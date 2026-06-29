<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:callable, middleware:array<int, Middleware|string>}> */
    private array $routes = [];

    /** @var array<int, Middleware|string> */
    private array $groupMiddleware = [];

    /** @param array<int, Middleware|string> $middleware */
    public function get(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    /** @param array<int, Middleware|string> $middleware */
    public function post(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    /** @param array<int, Middleware|string> $middleware */
    public function put(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('PUT', $pattern, $handler, $middleware);
    }

    /** @param array<int, Middleware|string> $middleware */
    public function delete(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('DELETE', $pattern, $handler, $middleware);
    }

    /**
     * Register routes that share a set of middleware.
     *
     * @param array<int, Middleware|string> $middleware
     */
    public function group(array $middleware, callable $register): void
    {
        $previous = $this->groupMiddleware;
        $this->groupMiddleware = array_merge($previous, $middleware);
        $register($this);
        $this->groupMiddleware = $previous;
    }

    /** @param array<int, Middleware|string> $middleware */
    private function add(string $method, string $pattern, callable $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $method = strtoupper($method);

        // Allow method override for HTML forms (PUT/DELETE via hidden _method).
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'DELETE'], true)) {
                $method = $override;
            }
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['pattern']) . '$#';
            if (!preg_match($regex, $path, $matches)) {
                continue;
            }

            $params = array_filter($matches, is_string(...), ARRAY_FILTER_USE_KEY);
            $request = new Request($params);

            foreach ($route['middleware'] as $middleware) {
                $instance = is_string($middleware) ? new $middleware() : $middleware;
                $instance->handle($request);
            }

            return ($route['handler'])(...array_values($params));
        }

        http_response_code(404);
        return view('errors/404', ['title' => 'Stranka nenalezena']);
    }
}
