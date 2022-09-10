<?php


namespace App\util;

require_once(__DIR__."/../../vendor/autoload.php");
use Dotenv\Dotenv;
use Exception;
use JetBrains\PhpStorm\ArrayShape;

class Config
{
   public function __construct()
   {
      $environmentFileExists = file_exists(__DIR__.'/../../.env');
      // .env file is used for local development,
      // hosting platforms have other methods for setting up Config variables
      if ($environmentFileExists) {
         $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
         $dotenv->load();
      }
   }

   #[ArrayShape(["connectDsn" => "string", "username" => "string", "password" => "string"])]
   public function GetEnvDbCredentials(): array
   {
      return [
         "connectDsn" => $this->GetConnectDsn(),
         "username" => $this->GetUsername(),
         "password" => $this->GetWebDbPassword()
      ];
   }

   /**
    * @throws Exception Undefined
    */
   private function GetByName(string $name): string {
      if ($_ENV[$name] === null || $_ENV[$name] === "") {
         throw new Exception("Internal error! Environment config variable $name not defined.");
      }
      return $_ENV[$name];
   }

   /**
    * @throws Exception Undefined
    */
   public function GetWebDbPassword(): string
   {
      return $this->GetByName("webDbPassword");
   }

   /**
    * @throws Exception Undefined
    */
   public function GetDatabaseName(): string
   {
      return $this->GetByName("dbName");
   }

   /**
    * @throws Exception Undefined
    */
   public function GetConnectDsn(): string
   {
      return $this->GetByName("dbConnectDsn");
   }

   /**
    * @throws Exception Undefined
    */
   public function GetUsername(): string
   {
      return $this->GetByName("dbUsername");
   }

   /**
    * @throws Exception Undefined
    */
   public function GetPassword(): string
   {
      return $this->GetByName("dbPassword");
   }

   /**
    * @throws Exception Undefined
    */
   public function GetUseDbCache(): bool
   {
      $type = $this->GetByName("cacheType");
      $isDb = $type === "db";
      $isFile = $type === "file";
      if ($isFile || $isDb) {
         return $isDb;
      }
      throw new Exception("Config cacheType={$type} must be in ['db', 'file']!");
   }

   /**
    * @throws Exception Undefined
    */
	public function IsDbInitGenerateDbWithSampleData(): bool
	{
      return $this->GetByName("dbInitGenerateDbWithSampleData") === "Y";
	}
}
