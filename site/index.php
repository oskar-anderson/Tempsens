<?php

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
   $response->getBody()->write("Hello world! You probably want to visit <a href='/overview'>/overview</a>");
   return $response;
});

$app->get('/info', function (Request $request, Response $response, $args) {
   $response->getBody()->write(App\util\Helper::GetPhpInfo());
   return $response;
});

$app->get('/overview', [Overview::class, "Index"]);
$app->post('/v1/sensor-reading/upload', [Overview::class, "UploadReadings"]);
$app->post('/v1/sensor/create', [Overview::class, "CreateSensor"]);
$app->post('/v1/sensor/update', [Overview::class, "UpdateSensor"]);
$app->post('/v1/sensor/delete', [Overview::class, "DeleteSensor"]);
$app->post('/test1', [Debug::class, "Test1_HydrateWithPostData"]);
$app->post('/test2', [Debug::class, "Test2_HydrateWithSampleData"]);

$app->get('/debug', [Debug::class, "main"]);

// API
$app->post('/api/physical-sensor/insert-reading', function (Request $request, Response $response, $args) {
   $xml_string = $request->getBody()->getContents();
   $xmlStringParsable = str_replace('soap:', '', $xml_string);    // https://stackoverflow.com/questions/4194489/how-to-parse-soap-xml
   $requestDataObject = @simplexml_load_string($xmlStringParsable); // add @ before function call to prevent warning message from being added to response
   if (! $requestDataObject) {
      $response->getBody()->write("Failed to parse XML: {
         xml_string: $xml_string,
         xmlStringParsable: $xmlStringParsable
      }");
      return $response;
   }
   $serial = (string) $requestDataObject->Body->InsertTx5xxSample->passKey;
   $temp = (float) $requestDataObject->Body->InsertTx5xxSample->temp;
   $relHum = (float) $requestDataObject->Body->InsertTx5xxSample->relHum;

   $id = SensorApi::Save($serial, $temp, $relHum);
   $response->getBody()->write($id);
   return $response;
});

$app->run();