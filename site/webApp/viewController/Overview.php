<?php

declare(strict_types=1);

namespace App\webApp\viewController;

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensors;
use App\dtoWeb\IndexViewModel;
use App\dtoWeb\IndexViewModelChildren\AlertMinMax;
use App\dtoWeb\IndexViewModelChildren\HandleInputModel;
use App\dtoWeb\IndexViewModelChildren\Period;
use App\dtoWeb\SensorReading;
use App\util\Helper;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
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
      $sensorsWithLastReading = (new DalSensors())->GetAllWithLastReading();
      array_push($this->debugs,'Get sensors and last readings: ' . microtime(true) - $before);

      $periods = Period::GetPeriodOptions($input->dateFrom, $input->dateTo);
      $sensorReadingsBySensorId = [];
      foreach ($sensorsWithLastReading as $sensorAndLastReading) {
         $sensorReadingsBySensorId[$sensorAndLastReading->sensor->id] = [];
      }

      $before = microtime(true);
      $sensorReadingsBySensorId = array_merge($sensorReadingsBySensorId, (new DalSensorReading())->GetAllBetween($input->dateFrom->format(DateTimeInterface::ATOM), $input->dateTo->format(DateTimeInterface::ATOM)));
      array_push($this->debugs,"GetAllBetween(" . $input->dateFrom->format(DateTimeInterface::ATOM) . ", " . $input->dateTo->format(DateTimeInterface::ATOM) .  ") query (count: " . (new Collection($sensorReadingsBySensorId))->map(function ($readings) { return count($readings); })->sum() . "): " . microtime(true) - $before);

      $before = microtime(true);
      $sensorAlertsMinMax = [];
      foreach ($sensorsWithLastReading as $sensorAndLastReading) {
         $sensor = $sensorAndLastReading->sensor;
         $tempAlertChains = AlertMinMax::GroupAlerts(
            array_values(array_filter($sensorReadingsBySensorId[$sensor->id], fn($x) => $x->temp < $sensor->minTemp || $x->temp > $sensor->maxTemp)),
               function(SensorReading $start, SensorReading $next) use ($sensor) {
                  return ($next->dateRecorded->getTimestamp() - $start->dateRecorded->getTimestamp()) / 60 <= $sensor->readingIntervalMinutes;
               });
         $relHumAlertChains = AlertMinMax::GroupAlerts(
            array_values(array_filter($sensorReadingsBySensorId[$sensor->id], fn($x) => $x->relHum < $sensor->minRelHum || $x->relHum > $sensor->maxRelHum)),
            function(SensorReading $start, SensorReading $next) use ($sensor) {
               return ($next->dateRecorded->getTimestamp() - $start->dateRecorded->getTimestamp()) / 60 <= $sensor->readingIntervalMinutes;
            });
         $tempAndRelHumAlerts = [];
         foreach ($tempAlertChains as $tempAlertChain) {
            array_push($tempAndRelHumAlerts, AlertMinMax::CreateTempOrRelHumAlert($sensor, $tempAlertChain, "temperature"));
         }
         foreach ($relHumAlertChains as $relHumAlertChain) {
            array_push($tempAndRelHumAlerts, AlertMinMax::CreateTempOrRelHumAlert($sensor, $relHumAlertChain, "relative humidity"));
         }
         $missingValuesAlerts = AlertMinMax::GetMissingValuesAsAlerts($sensor->readingIntervalMinutes, $sensorReadingsBySensorId[$sensor->id], $input->dateFrom, $input->dateTo);
         $sensorAlertsMinMax[$sensor->id] = [];

         $mergedAlerts = array_merge($missingValuesAlerts, $tempAndRelHumAlerts);
         usort($mergedAlerts, fn(AlertMinMax $a, AlertMinMax $b) => $a->beforeDate->getTimestamp() - $b->beforeDate->getTimestamp());  // sorted ASC
         array_push($sensorAlertsMinMax[$sensor->id], ...$mergedAlerts);
      }
      array_push($this->debugs,'Group chaining sensor alerts: ' . microtime(true) - $before);
      $before = microtime(true);

      $htmlInjects = (new IndexViewModel(input: $input, sensors: $sensorsWithLastReading, sensorAlertsMinMax: $sensorAlertsMinMax,
         sensorReadingsBySensorId: $sensorReadingsBySensorId, periods: $periods));

      $content = Helper::Render(__DIR__ . "/../view/main/Overview.php", $htmlInjects);
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
      $dateToIsValid = !! DateTimeImmutable::createFromFormat('siH_d-m-Y', "595923_" . $inputDateTo, new DateTimeZone('UTC'));
      $dateFromIsValidDate = !! DateTimeImmutable::createFromFormat('siH_d-m-Y', "000000_" . $inputDateFrom, new DateTimeZone('UTC'));
      if (! isset($_GET['To']) ||
         ! isset($_GET['From']) ||
         ! $dateToIsValid ||
         ! (preg_match('/^-\d*$/', $inputDateFrom) || $dateFromIsValidDate)
      ) {
         // default value
         $now = Helper::GetUtcNow();
         return new HandleInputModel($now->modify("-91 day"), $now, 'relative');
      }
      $dateTo = DateTimeImmutable::createFromFormat('siH_d-m-Y', "595923_" . $inputDateTo, new DateTimeZone('UTC'));
      [$dateFrom, $dateFromType] = $dateFromIsValidDate ?
         [DateTimeImmutable::createFromFormat('siH_d-m-Y', "000000_" . $inputDateFrom, new DateTimeZone('UTC')), 'absolute'] :
         [$dateTo->modify($inputDateFrom . "day"), 'relative'];

      return new HandleInputModel($dateFrom, $dateTo, $dateFromType);
   }
}
