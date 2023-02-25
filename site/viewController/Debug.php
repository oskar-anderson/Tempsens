<?php

namespace App\viewController;

require_once(__DIR__."/../../vendor/autoload.php");

use App\db\dal\DalCache;
use App\db\dal\DalSensorReading;
use App\db\dal\DalSensors;
use App\db\DbHelper;
use App\model\SensorReading;
use App\util\Console;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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
}
