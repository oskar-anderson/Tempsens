<?php

namespace App\db;

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensors;
use App\db\dal\AbstractDalBase;
use App\util\Config as Config;
use PDO;
use PDOException;
use App\Util\Console;

class DbHelper {

   /**
    *  @return AbstractDalBase[]
    */
   private static function GetTrackedTables(): array
   {
      return [
         new DalSensors(),
         new DalSensorReading()
      ];
   }

    public static function CreateTables(): void
    {
       $createTableStatements = array_map(fn(AbstractDalBase $x) => $x->SqlCreateTableStmt(), DbHelper::GetTrackedTables());
       $pdo = DbHelper::GetPDO();
       foreach ($createTableStatements as $i=>$table) {
          (new Console(Console::$Linefeed, true))->WriteLine($i + 1 . "/" . count($createTableStatements) . ": " . $table);
          $pdo->query($table);
       }
    }

   public static function GetPDO(): PDO
   {
      $config = new Config();
      $pdo = new PDO("mysql:host={$config->GetHost()};port={$config->GetPort()};dbname={$config->GetDatabaseName()}", $config->GetUsername(), $config->GetPassword());
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      // var_dump($pdo->setAttribute(PDO::ATTR_TIMEOUT, 28800)); // this fails
      return $pdo;
   }
}
