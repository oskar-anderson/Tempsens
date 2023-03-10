<?php

use App\apiController\v1\SensorController;
use App\apiController\v1\SensorReadingController;
use App\SensorApi;
use App\viewController\Debug;
use App\viewController\Overview;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Define Custom Error Handler
$customErrorHandler = function (
   Request          $request,
   Throwable        $exception,
   bool             $displayErrorDetails,
   bool             $logErrors,
   bool             $logErrorDetails
) use ($app) {
   $payload = [
      'error_message' => $exception->getMessage(),
      'error_code' => $exception->getCode(),
      'error_file' => $exception->getFile(),
      'error_line' => $exception->getLine(),
      'error_trace' => $exception->getTraceAsString()
      ];

   $response = $app->getResponseFactory()->createResponse();
   $response->getBody()->write(
      json_encode($payload, JSON_UNESCAPED_UNICODE)
   );

   return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
};

$errorMiddleware = $app->addErrorMiddleware(displayErrorDetails: true, logErrors: false, logErrorDetails: false);
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

// PAGES
$app->get('/', function (Request $request, Response $response, $args) {
   $response->getBody()->write("Welcome to the app! To view sensor data <a href='/overview'>click here</a>.</div>");
   return $response;
});

$app->get('/info', function (Request $request, Response $response, $args) {
   $response->getBody()->write(App\util\Helper::GetPhpInfo());
   return $response;
});

$app->get('/overview', [Overview::class, "Index"]);
$app->get('/debug', [Debug::class, "main"]);

// API
$app->post('/v1/sensor-reading/insert-reading', [SensorReadingController::class, "Save"]);
$app->post('/v1/sensor-reading/upload', [SensorReadingController::class, "UploadReadings"]);
$app->post('/v1/sensor/create', [SensorController::class, "CreateSensor"]);
$app->post('/v1/sensor/update', [SensorController::class, "UpdateSensor"]);
$app->post('/v1/sensor/delete', [SensorController::class, "DeleteSensor"]);

// testing
$app->post('/test1', [Debug::class, "Test1_HydrateWithPostData"]);
$app->post('/test2', [Debug::class, "Test2_HydrateWithSampleData"]);

$app->run();