<?php
/**
 * Router Class
 * Gerenciamento de rotas do sistema
 */

class Router
{
    private $routes = [];
    private $notFoundCallback;

    /**
     * Adiciona uma rota GET
     */
    public function get($path, $callback)
    {
        $this->addRoute('GET', $path, $callback);
    }

    /**
     * Adiciona uma rota POST
     */
    public function post($path, $callback)
    {
        $this->addRoute('POST', $path, $callback);
    }

    /**
     * Adiciona uma rota para qualquer método
     */
    private function addRoute($method, $path, $callback)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'callback' => $callback
        ];
    }

    /**
     * Define o callback para rota não encontrada
     */
    public function notFound($callback)
    {
        $this->notFoundCallback = $callback;
    }

    /**
     * Executa o roteamento
     */
    public function run()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove /FacilitaCred/public do início da URI
        $basePath = '/FacilitaCred/public';
        if (strpos($requestUri, $basePath) === 0) {
            $requestUri = substr($requestUri, strlen($basePath));
        }

        // Se vazio, define como raiz
        if ($requestUri === '' || $requestUri === false) {
            $requestUri = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod) {
                $pattern = $this->convertPathToRegex($route['path']);

                if (preg_match($pattern, $requestUri, $matches)) {
                    array_shift($matches); // Remove o match completo
                    call_user_func_array($route['callback'], $matches);
                    return;
                }
            }
        }

        // Rota não encontrada
        if ($this->notFoundCallback) {
            call_user_func($this->notFoundCallback);
        } else {
            http_response_code(404);
            echo "404 - Página não encontrada";
        }
    }

    /**
     * Converte o caminho para regex
     * Suporta parâmetros dinâmicos: /user/:id -> /user/([^/]+)
     */
    private function convertPathToRegex($path)
    {
        $path = preg_replace('/\//', '\\/', $path);
        $path = preg_replace('/:([a-zA-Z0-9_]+)/', '([^\/]+)', $path);
        return '/^' . $path . '$/';
    }

    /**
     * Redireciona para uma URL
     */
    public static function redirect($url)
    {
        header("Location: $url");
        exit;
    }

    /**
     * Retorna a URL base
     */
    public static function url($path = '')
    {
        return BASE_URL . $path;
    }
}
