<?php

namespace App\db;

require_once(__DIR__."/../../vendor/autoload.php");

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensorReadingTmp;
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
         new DalSensorReadingTmp(),
         new DalSensorReading()
      ];
   }

    public static function CreateTables() {
       $createTableStatements = array_map(fn(AbstractDalBase $x) => $x->SqlCreateTableStmt(), DbHelper::GetTrackedTables());
       $pdo = DbHelper::GetPDO();
       foreach ($createTableStatements as $i=>$table) {
          (new Console(Console::$Linefeed, true))->WriteLine($i + 1 . "/" . count($createTableStatements) . ": " . $table);
          $pdo->query($table);
       }
    }

   public static function DropTables() {
      $tableNames = array_map(fn(AbstractDalBase $x) => $x->GetTableName(), DbHelper::GetTrackedTables());
      $pdo = DbHelper::GetPDO();
      $pdo->query("SET FOREIGN_KEY_CHECKS = 0;");
      foreach ($tableNames as $i=>$table) {
         $stmt = "DROP TABLE IF EXISTS " . $table . ";";
         (new Console(Console::$Linefeed, true))->WriteLine($i + 1 . "/" . count($tableNames) . ": " . $stmt);
         $pdo->query($stmt);
      }
      $pdo->query("SET FOREIGN_KEY_CHECKS = 1;");
   }


   public static function GetPDO(): PDO
   {
      $config = new Config();
      return DbHelper::GetPdoByKey($config->GetConnectDsn(), $config->GetUsername(), $config->GetPassword());
   }

   public static function GetPdoByKey(string $dsn, string $username, string $password): PDO
   {
      try {
         $pdo = new PDO($dsn, $username, $password);
         $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
         // var_dump($pdo->setAttribute(PDO::ATTR_TIMEOUT, 28800)); // this fails
         return $pdo;
      } catch (PDOException $e) {
         (new Console(Console::$Linefeed, true))->WriteLine("GetPdoByKey FAILED ({$e->getMessage()})");
         throw $e;
      }
   }
}
