<?php

namespace App\db;

require_once(__DIR__."/../../vendor/autoload.php");

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensorReadingTmp;
use App\db\dal\DalSensors;
use App\util\Config as Config;
use PDO;
use PDOException;


class DbHelper {

   /**
    *  @return TableHelper[]
    */
   private static function GetTrackedTables(): array
   {
      return [
         new TableHelper((new DalSensors())->SqlCreateTableStmt(), (new DalSensors())->GetName()),
         new TableHelper((new DalSensorReadingTmp())->SqlCreateTableStmt(), (new DalSensorReadingTmp())->GetName()),
         new TableHelper((new DalSensorReading())->SqlCreateTableStmt(), (new DalSensorReading())->GetName())
      ];
   }

   /**
    *  @return string[]
    */
   private static function GetTableNames(): array
   {
      return array_map(function($x) { return $x->name; }, DbHelper::GetTrackedTables());
   }


    public static function CreateTables() {
       echo "creating tables..."  . "\n";
       $stmts = array_map(function($x) {return $x->sqlStmt; }, DbHelper::GetTrackedTables());
       $pdo = DbHelper::GetDevPDO();
       foreach ($stmts as $table) {
          echo $table . "\n";
          $pdo->query($table);
       }
    }

   public static function DropTables() {
      $tables = array_reverse(DbHelper::GetTableNames());
      $pdo = DbHelper::GetDevPDO();
      foreach ($tables as $table) {
         $stmt = "DROP TABLE IF EXISTS " . $table . ";";
         echo $stmt  . "\n";
         $pdo->query($stmt);
      }
   }


   public static function GetPDO(): PDO
   {
      return DbHelper::GetPdoByKey(Config::GetConfig()['db_active']);
   }

   public static function GetDevPDO(): PDO
   {
      return DbHelper::GetPdoByKey('db_local_dev');
   }

   public static function GetPdoByKey($configKey): PDO
   {
      $dbconf = Config::GetConfig()[$configKey];
      try {
         $pdo = new PDO($dbconf['connectUrl'], $dbconf['username'], $dbconf['password']);
         $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
         return $pdo;
      } catch (PDOException $e) {
         echo "FAILED (" . $e->getMessage() . ")\n";
         die();
      }
   }
}

class TableHelper {

   function __construct(
      public string $sqlStmt,
      public string $name,
   ) {}
}