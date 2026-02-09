<?php

require __DIR__ . '/vendor/autoload.php';

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Aws\S3\S3Client;

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$container = new Container();
AppFactory::setContainer($container);

// Database connection
$container->set('db', function () {
    $dsn = "pgsql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}";
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
});

// MinIO (S3 compatible)
$container->set('s3', function () {
    return new S3Client([
        'region'                  => 'us-east-1',
        'version'                 => 'latest',
        'endpoint'                => $_ENV['MINIO_ENDPOINT'],
        'use_path_style_endpoint' => true,
        'credentials'             => [
            'key'    => $_ENV['MINIO_ACCESS_KEY'],
            'secret' => $_ENV['MINIO_SECRET_KEY'],
        ],
    ]);
});

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Routes API
require __DIR__ . '/src/routes.php';

// ====================== SWAGGER UI INTEGRATION ======================

// Route untuk Swagger JSON (OpenAPI spec)
$app->get('/openapi.json', function (Request $request, Response $response) {
    $openapi = \OpenApi\Generator::scan([
        __DIR__ . '/src',               // folder yang punya annotation @OA\
        __DIR__ . '/index.php'          // jika ada annotation di index
    ]);

    $response->getBody()->write($openapi->toJson());
    return $response->withHeader('Content-Type', 'application/json');
});

// Route untuk Swagger UI (menggunakan swagger-ui-dist dari vendor)
$app->get('/docs[/{path:.*}]', function (Request $request, Response $response, array $args) {
    $path = $args['path'] ?? '';

    $swaggerUiDir = __DIR__ . '/vendor/swagger-api/swagger-ui-dist';

    // Serve static files dari swagger-ui-dist
    $filePath = $swaggerUiDir . '/' . $path;
    if ($path === '' || $path === 'index.html') {
        $filePath = $swaggerUiDir . '/index.html';
    }

    if (file_exists($filePath)) {
        $mime = mime_content_type($filePath);
        $response = $response->withHeader('Content-Type', $mime);

        if (pathinfo($filePath, PATHINFO_EXTENSION) === 'html') {
            // Inject spec URL ke Swagger UI
            $content = file_get_contents($filePath);
            $content = str_replace(
                'https://petstore.swagger.io/v2/swagger.json',
                '/openapi.json',
                $content
            );
            $response->getBody()->write($content);
        } else {
            $response->getBody()->write(file_get_contents($filePath));
        }

        return $response;
    }

    // Fallback ke index.html jika path tidak ditemukan
    $index = $swaggerUiDir . '/index.html';
    $content = file_get_contents($index);
    $content = str_replace(
        'https://petstore.swagger.io/v2/swagger.json',
        '/openapi.json',
        $content
    );
    $response->getBody()->write($content);
    return $response->withHeader('Content-Type', 'text/html');
})->setName('swagger-ui');

$app->run();