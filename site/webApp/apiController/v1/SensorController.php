<?php

namespace App\webApp\apiController\v1;

use App\db\dal\DalSensors;
use App\db\DbHelper;
use App\dtoApi\Sensor\SensorWithAuth_v1;
use App\mapper\SensorMapper;
use App\util\Config;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SensorController
{
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
}