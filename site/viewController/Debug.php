<?php

namespace App\viewController;

require_once(__DIR__."/../../vendor/autoload.php");

use App\db\dal\DalCache;
use App\frontendDto\Sensor\Sensor_v1;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

// (new Debug())->main();

class Debug
{
   public array $debugs = [];

   function main(Request $request, Response $response, $args): Response
   {
      // It does not seem to actually measure performance well - the performance of the same db query can take 9sec or 27sec
      // Also every other page refresh fails:
      // Fatal error: Uncaught PDOException: SQLSTATE[HY000]: General error: 2006 MySQL server has gone away
      DalCache::ReadSensorReadings();

      $from = '20200601000000';
      $to = '20201201000000';
      $iterations = 1;


      $body = join("<br>", $this->debugs);
      $response->getBody()->write($body);
      return $response;
   }

   public static function measurePerformance($callback, $callbackName, &$resultLogArr, $numberOfExecutions): void {
      $times = [];
      for ($i = 0; $i < $numberOfExecutions; $i++) {
         $before = microtime(true);
         $callback();
         array_push($times, microtime(true) - $before);
      }

      array_push($resultLogArr,"$callbackName avg time " . array_sum($times) / $numberOfExecutions);
      array_push($resultLogArr,"$callbackName all times " . var_export($times, true));
   }

   public function Test1_HydrateWithPostData(Request $request, Response $response, $args): Response {
      $body = $request->getBody()->getContents();  // $request->getParsedBody() does not work for some reason
      $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
      $sensorSymphany = $serializer->deserialize($body, Sensor_v1::class, 'json');
      $response->getBody()->write(json_encode($sensorSymphany));
      return $response->withStatus(400);
   }

   public function Test2_HydrateWithSampleData(Request $request, Response $response, $args): Response {
      $data = <<<JSON
            {
                "id": "aSWiZwD0dc3kUdcqECi3uv",
                "ip": "test",
                "isPortable": true,
                "location": "test",
                "maxRelHum": 60,
                "maxTemp": 25,
                "minRelHum": 20,
                "minTemp": 15,
                "model": "test",
                "name": "test",
                "readingIntervalMinutes": 15,
                "serial": "test"
            }
            JSON;
      $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
      $sensorSymphany = $serializer->deserialize($data, Sensor_v1::class, 'json');

      /*
      $sensor = (new MapperBuilder())
         ->supportDateFormats('d-m-Y H:i:s')
         ->mapper()
         ->map(Sensor::class, $data);
      */
      $response->getBody()->write($sensorSymphany->get5());
      return $response->withStatus(400);
   }
}
