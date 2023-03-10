<?php

declare(strict_types=1);

namespace App\viewController;

require_once(__DIR__."/../../vendor/autoload.php");

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensors;
use App\dtoWeb\IndexViewModel;
use App\dtoWeb\IndexViewModelChildren\AlertMinMax;
use App\dtoWeb\IndexViewModelChildren\HandleInputModel;
use App\dtoWeb\IndexViewModelChildren\Period;
use App\util\Helper;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


class Overview {

   public array $debugs = [];

   public function Index(Request $request, Response $response, $args): Response
   {
      $start = microtime(true);

      $before = microtime(true);
      $input = $this->HandleInput();
      array_push($this->debugs,'Handle input: ' . microtime(true) - $before);

      $before = microtime(true);
      $sensors = (new DalSensors())->GetAllWithLastReading();
      array_push($this->debugs,'Get sensors: ' . microtime(true) - $before);


      $from = DateTimeImmutable::createFromFormat('d-m-Y', $input->dateFrom);
      $to = DateTimeImmutable::createFromFormat('d-m-Y', $input->dateTo);
      $periods = Period::GetPeriodOptions($from, $to);


      $sensorReadingsBySensorId = [];
      foreach ($sensors as $sensor) {
         $sensorReadingsBySensorId[$sensor->sensor->id] = [];
      }

      $before = microtime(true);
      $sensorReadingsBySensorId = array_merge($sensorReadingsBySensorId, (new DalSensorReading())->GetAllBetween($from->format('Ymd') . '2359', $to->format('Ymd') . '2359'));
      array_push($this->debugs,"GetAllBetween(" . $from->format('Ymd') . '2359' . ", " . $to->format('Ymd') . '2359' .  ") query (count: " . (new Collection($sensorReadingsBySensorId))->map(function ($readings) { return count($readings); })->sum() . "): " . microtime(true) - $before);

      $before = microtime(true);
      $sensorAlertsMinMax = [];
      foreach ($sensors as $sensorPlus) {
         $sensor = $sensorPlus->sensor;
         $rawOutOfBounds = array_values(array_filter($sensorReadingsBySensorId[$sensor->id], fn(\App\dtoWeb\SensorReading $x) =>
            $x->temp < $sensor->minTemp || $x->temp > $sensor->maxTemp ||
            $x->relHum < $sensor->minRelHum || $x->relHum > $sensor->maxRelHum
         ));
         $sensorAlertsMinMax[$sensor->id] = AlertMinMax::Get($sensor, $rawOutOfBounds);
      }
      array_push($this->debugs,'Group chaining sensor alerts: ' . microtime(true) - $before);
      $before = microtime(true);

      $htmlInjects = (new IndexViewModel(input: $input, sensors: $sensors, sensorAlertsMinMax: $sensorAlertsMinMax,
         sensorReadingsBySensorId: $sensorReadingsBySensorId, periods: $periods));

      $content = Helper::Render(__DIR__ . "/../view/main/overview.php", $htmlInjects);
      array_push($this->debugs,'PHP generating page: ' . microtime(true) - $before);
      array_push($this->debugs,'PHP total: ' . microtime(true) - $start);
      $content .= '<script type=text/javascript>' . join("", array_map(fn($x) => 'console.log(' . json_encode($x) . ');', $this->debugs)) . '</script>';
      $response->getBody()->write($content);
      return $response->withStatus(200);
   }

   function HandleInput(): HandleInputModel
   {
      $inputDateTo = $_GET['To'] ?? '';
      $inputDateFrom = $_GET['From'] ?? '';
      $dateToIsValid = !! DateTimeImmutable::createFromFormat('d-m-Y', $inputDateTo);
      $dateFromIsValidDate = !! DateTimeImmutable::createFromFormat('d-m-Y', $inputDateFrom);
      if (! isset($_GET['To']) ||
         ! isset($_GET['From']) ||
         ! $dateToIsValid ||
         ! (preg_match('/^-\d*$/', $inputDateFrom) || $dateFromIsValidDate)
      ) {
         // default value
         $now = Helper::GetUtcNow();
         return new HandleInputModel($now->modify("-91 day"), $now, 'relative');
      }
      $dateTo = DateTimeImmutable::createFromFormat('d-m-Y', $inputDateTo);
      [$dateFrom, $dateFromType] = $dateFromIsValidDate ?
         [DateTimeImmutable::createFromFormat('d-m-Y', $inputDateFrom), 'absolute'] :
         [$dateTo->modify($inputDateFrom . "day"), 'relative'];

      return new HandleInputModel($dateFrom, $dateTo, $dateFromType);
   }
}
