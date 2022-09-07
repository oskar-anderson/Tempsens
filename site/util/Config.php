<?php


namespace App\util;

require_once(__DIR__."/../../vendor/autoload.php");
use Dotenv\Dotenv;
use Exception;

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
}
