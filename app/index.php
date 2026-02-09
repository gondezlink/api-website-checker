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

// Database
$container->set('db', function () {
    $dsn = "pgsql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}";
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
});

// MinIO (S3 compatible)
$container->set('s3', function () {
    return new S3Client([
        'region'  => 'us-east-1',
        'version' => 'latest',
        'endpoint' => $_ENV['MINIO_ENDPOINT'],
        'use_path_style_endpoint' => true,
        'credentials' => [
            'key'    => $_ENV['MINIO_ACCESS_KEY'],
            'secret' => $_ENV['MINIO_SECRET_KEY'],
        ],
    ]);
});

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Routes
require __DIR__ . '/src/routes.php';

// Swagger UI
$app->get('/docs', function (Request $request, Response $response) {
    $openapi = \OpenApi\Generator::scan([__DIR__ . '/src']);
    $json = $openapi->toJson();

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>API Website Checker - Swagger UI</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css"/>
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js" crossorigin></script>
  <script>
    window.onload = () => {
      window.ui = SwaggerUIBundle({
        spec: $json,
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIBundle.SwaggerUIStandalonePreset
        ],
      });
    };
  </script>
</body>
</html>
HTML;

    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->run();