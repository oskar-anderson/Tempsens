<?php

declare(strict_types=1);

namespace App\viewController;

require_once(__DIR__."/../../vendor/autoload.php");

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensors;
use App\db\DbHelper;
use App\dtoWeb\IndexViewModel;
use App\dtoWeb\IndexViewModelChildren\AlertMinMax;
use App\dtoWeb\IndexViewModelChildren\HandleInputModel;
use App\dtoWeb\IndexViewModelChildren\Period;
use App\dtoApi\Sensor\SensorWithAuth_v1;
use App\dtoApi\SensorReadingUpload\SensorReadingUpload;
use App\dtoApi\SensorReadingUpload\SensorReadingUploadReadings;
use App\mapper\SensorMapper;
use App\domain\SensorReading;
use App\util\Base64;
use App\util\Config;
use App\util\Helper;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


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
         ! (preg_match('/-\d*/', $inputDateFrom) || $dateFromIsValidDate)
      ) {
         // default value
         $now = Helper::GetDateNowAsDateTime();
         return new HandleInputModel($now->modify("-91 day"), $now, 'relative');
      }
      $dateTo = DateTimeImmutable::createFromFormat('d-m-Y', $inputDateTo);
      [$dateFrom, $dateFromType] = $dateFromIsValidDate ?
         [DateTimeImmutable::createFromFormat('d-m-Y', $inputDateFrom), 'absolute'] :
         [$dateTo->modify($inputDateFrom . "day"), 'relative'];

      return new HandleInputModel($dateFrom, $dateTo, $dateFromType);
   }

   function CreateSensor(Request $request, Response $response, $args): Response {
      $body = $request->getBody()->getContents();  // $request->getParsedBody() does not work for some reason
      $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
      /** @var SensorWithAuth_v1 $model */
      $model = $serializer->deserialize($body, SensorWithAuth_v1::class, 'json');

      if (sizeof($model->sensor->validate()) !== 0) {
         $response->getBody()->write("Bad request or invalid data!");
         return $response->withStatus(400);
      }
      if ($model->auth !== (new Config())->GetWebDbPassword()) {
         $response->getBody()->write("Not authorized!");
         return $response->withStatus(401);
      }
      $domainSensor = (new SensorMapper())->MapFrontToDomain($model->sensor);
      $pdo = DbHelper::GetPDO();
      (new DalSensors())->InsertByChunk([$domainSensor], $pdo);
      $response->getBody()->write("All good!");
      return $response->withStatus(200);
   }

   function UpdateSensor(Request $request, Response $response, $args): Response {
      $body = $request->getBody()->getContents();  // $request->getParsedBody() does not work for some reason
      $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
      /** @var SensorWithAuth_v1 $model */
      $model = $serializer->deserialize($body, SensorWithAuth_v1::class, 'json');

      if (sizeof($model->sensor->validate()) !== 0) {
         $response->getBody()->write("Bad request or invalid data!");
         return $response->withStatus(400);
      }
      if ($model->auth !== (new Config())->GetWebDbPassword()) {
         $response->getBody()->write("Not authorized!");
         return $response->withStatus(401);
      }
      $domainSensor = (new SensorMapper())->MapFrontToDomain($model->sensor);
      (new DalSensors())->Update($domainSensor);
      $response->getBody()->write("All good!");
      return $response->withStatus(200);
   }

   function DeleteSensor(Request $request, Response $response, $args): Response {
      $model = json_decode($request->getBody()->getContents());
      $id = $model->id ?? null;
      $auth = $model->auth ?? null;

      if ($id === null || $auth === null) {
         $response->getBody()->write("Bad request");
         return $response->withStatus(400);
      };
      if ($auth !== (new Config())->GetWebDbPassword()) {
         $response->getBody()->write("Not authorized!");
         return $response->withStatus(401);
      }

      (new DalSensors())->Delete($id);
      $response->getBody()->write("All good!");
      return $response->withStatus(200);
   }

   function UploadReadings(Request $request, Response $response, $args): Response {
      $body = $request->getBody()->getContents();  // $request->getParsedBody() does not work for some reason
      $serializer = new Serializer([new ObjectNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);
      /** @var SensorReadingUpload $model */
      $model = $serializer->deserialize($body, SensorReadingUpload::class, 'json');
      // Deserializer is a piece of shit and will not hydrate array objects

      /** @var SensorReadingUploadReadings[] $sensorReadingDeserialized */
      $sensorReadingDeserialized = [];
      foreach ($model->sensorReadings as $sensorReading) {
         $sensorReadingDeserialized[] = new SensorReadingUploadReadings(DateTimeImmutable::createFromFormat("d-m-Y H:i:s", $sensorReading['date']), $sensorReading['temp'], $sensorReading['relHum']);
      }
      $model->sensorReadings = $sensorReadingDeserialized;

      if ($model === null || sizeof($model->sensorReadings) === 0) {
         $response->getBody()->write("Bad request or invalid data!");
         return $response->withStatus(400);
      }
      $sensor = (new DalSensors())->GetFirstOrDefault($model->sensorId);
      if ($sensor === null) {
         $response->getBody()->write('Error! Sensor with id not found! Id: ' . $model->sensorId);
         return $response->withStatus(400);
      }

      if ($model->auth !== (new Config())->GetWebDbPassword()) {
         $response->getBody()->write('Not authorized!');
         return $response->withStatus(401);
      }

      $newSensorReadings = array_map(function ($row) use ($sensor) {
         return new SensorReading(
            id: Base64::GenerateId(),
            sensorId: $sensor->id,
            temp: $row->temp,
            relHum: $row->relHum,
            dateRecorded: $row->date,
            dateAdded: Helper::GetDateNowAsDateTime()
         );
      }, $model->sensorReadings);

      $existingSensorReadingsDateRecordedArr = array_map(
         fn($x) => $x->dateRecorded->format('YmdHis'),
         (new DalSensorReading())->GetAllWhereSensorId($model->sensorId)
      );

      /** @var SensorReading[] $duplicateDateTimes */
      $duplicateDateTimes = array_filter($newSensorReadings, function($sensorReadingNew) use ($existingSensorReadingsDateRecordedArr) {
         return in_array($sensorReadingNew->dateRecorded, $existingSensorReadingsDateRecordedArr, true);
      });

      if (sizeof($duplicateDateTimes) !== 0) {
         $duplicateDateStrings = array_map(
            fn($duplicateDateTime) => $duplicateDateTime->getDateRecordedAsDateTime()->format('d/m/Y H:i:s'),
            $duplicateDateTimes);
         $response->getBody()->write('Duplicate entry dates: ' . join(', ', $duplicateDateStrings));
         return $response->withStatus(400);
      }

      $pdo = DbHelper::GetPDO();
      $pdo->beginTransaction();
      (new DalSensorReading())->InsertByChunk($newSensorReadings, $pdo);
      $pdo->commit();

      $response->getBody()->write('Success!');
      return $response->withStatus(200);
   }
}
