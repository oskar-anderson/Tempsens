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

   /**
    * @throws Exception Undefined
    */
   private function GetByName(string $name): string {
      if ($_ENV[$name] === null) {
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
   public function GetHost(): string
   {
      return $this->GetByName("dbHost");
   }

   /**
    * @throws Exception Undefined
    */
   public function GetPort(): string
   {
      return $this->GetByName("dbPort");
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
	public function IsDbInitGenerateDbWithSampleData(): bool
	{
      return $this->GetByName("dbInitGenerateDbWithSampleData") === "Y";
	}
}
