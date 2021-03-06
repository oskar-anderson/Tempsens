<?php

namespace App\db;

require_once(__DIR__."/../../vendor/autoload.php");

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensorReadingTmp;
use App\db\dal\DalSensors;
use App\db\dal\IDalBase;
use App\util\Config as Config;
use PDO;
use PDOException;
use App\Util\Console;

class DbHelper {

   /**
    *  @return IDalBase[]
    */
   private static function GetTrackedTables(): array
   {
      return [
         new DalSensors(),
         new DalSensorReadingTmp(),
         new DalSensorReading()
      ];
   }

    public static function CreateTables() {
       $createTableStatements = array_map(fn(IDalBase $x) => $x->SqlCreateTableStmt(), DbHelper::GetTrackedTables());
       $pdo = DbHelper::GetPDO();
       foreach ($createTableStatements as $i=>$table) {
          Console::WriteLine($i + 1 . "/" . count($createTableStatements) . ": " . $table, true);
          $pdo->query($table);
       }
    }

   public static function DropTables() {
      $tableNames = array_map(fn(IDalBase $x) => $x->GetName(), DbHelper::GetTrackedTables());
      $pdo = DbHelper::GetPDO();
      $pdo->query("SET FOREIGN_KEY_CHECKS = 0;");
      foreach ($tableNames as $i=>$table) {
         $stmt = "DROP TABLE IF EXISTS " . $table . ";";
         Console::WriteLine($i + 1 . "/" . count($tableNames) . ": " . $stmt, true);
         $pdo->query($stmt);
      }
      $pdo->query("SET FOREIGN_KEY_CHECKS = 1;");
   }


   public static function GetPDO(): PDO
   {
      $config = new Config();
      return DbHelper::GetPdoByKey($config->GetConnectUrl(), $config->GetUsername(), $config->GetPassword());
   }

   public static function GetPdoByKey(string $url, string $username, string $password): PDO
   {
      // Console::WriteLine("New PDO(connectUrl= {$dbconf['connectUrl']}, username=***, password=***)");
      try {
         $pdo = new PDO($url, $username, $password);
         $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
         return $pdo;
      } catch (PDOException $e) {
         Console::WriteLine("GetPdoByKey FAILED ({$e->getMessage()})", true);
         throw $e;
      }
   }
}
