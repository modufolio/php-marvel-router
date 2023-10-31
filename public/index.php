<?php

declare(strict_types = 1);

/**
 * PHP Marvel Router - A minimalistic PHP router with superpowers
 *
 * PHP script that handles routing for the application.
 * It defines routes, resolves URLs, and returns JSON responses.
 *
 * @author      Maarten Thiebou
 * @copyright   Appweaver
 * @license     MIT License (https://opensource.org/licenses/MIT)
 */

//ini_set('display_errors', '1');
//ini_set('display_startup_errors', '1');
//error_reporting(E_ALL);

header_remove('X-Powered-By');
define('APP_START', microtime(true));


$app = [
    'wildcards' => [
        '(:num)' => '(-?\d+)',
        '(:alpha)' => '([a-zA-Z]+)',
        '(:alphanum)' => '([a-zA-Z0-9]+)',
        '(:any)' => '([a-zA-Z0-9.\-_%=+@()]+)',
        '(:all)' => '(.*)',
    ],
    'routes' => [
        [
            'pattern' => '/',
            'method' => 'GET',
            'action' => function () {
                return [
                    'message' => 'Hi There!',
                ];
            },
        ],
        [
            'pattern' => '/api',
            'method' => 'POST',
            'action' => function () {
                return [
                    'message' => 'Post data',
                    'data' => $this->data()
                ];
            },
        ],
        [
            'pattern' => '/api/(:any)/(:num)',
            'method' => 'GET',
            'action' => function ($class, $id) {
                return [
                    'message' => 'You are viewing ' . $class . ' API' . ' with id ' . $id,
                ];
            }
        ],
    ],
];


$router = function ($path, $method) use (&$app) {
    $resolveRoute = function ($path, $method, $routes) use (&$resolveRoute, &$app) {
        foreach ($routes as $route) {


            $pattern = strtr($route['pattern'], $app['wildcards']);

            if (preg_match('#^' . $pattern . '$#u', $path, $parameters) === 0) {
                continue;
            }

            if (strtoupper($route['method'] ?? 'GET') !== $method) {
                return [
                    'message' => 'Method not allowed',
                    'status' => 405,
                ];
            }

            $arguments = array_slice($parameters, 1);
            return $route['action']->call(new class () {
                public function data(): array
                {
                    $data = empty($_POST) === false ? $_POST : file_get_contents('php://input');

                    if (is_string($data)) {
                        $parsedData = json_decode($data, true);
                        return json_last_error() === JSON_ERROR_NONE ? $parsedData : [];
                    }

                    return is_array($data) ? $data : [];
                }
            }, ...$arguments);
        }

        return [
            'message' => 'Page not found',
            'status' => 404,
        ];
    };

    return $resolveRoute($path, $method, $app['routes']);
};

// Get the path from the request
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = is_string($path) ? $path : '/';

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Resolve the route
$response = $router($path, $method);

// Calculate the response time
$responseTime = number_format((microtime(true) - APP_START) * 1000, 2);

// Output the response
header('Content-Type: application/json');
header('Response-Time: ' . $responseTime . 'ms');
http_response_code($response['status'] ?? 200);
exit(is_array($response) ? json_encode($response) : $response);
