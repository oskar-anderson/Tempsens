<?php


namespace App\util;

require_once(__DIR__."/../../vendor/autoload.php");
use Dotenv\Dotenv;

class Config
{
   public function __construct()
   {
      $environmentFileExists = file_exists(__DIR__.'/../../.env');
      // .dev file is used for local development,
      // hosting platforms have other methods for setting up Config variables
      if ($environmentFileExists) {
         $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
         $dotenv->load();
      }
   }

   public static function GetConfig(): array
   {
      return require (__DIR__."/../config/config.php");
   }

   public function GetEnvDbCredentials(): array
   {
      return [
         "connectUrl" => $this->GetConnectUrl(),
         "username" => $this->GetUsername(),
         "password" => $this->GetWebDbPassword()
      ];
   }

   public function GetWebDbPassword(): string
   {
      return $_ENV['webDbPassword'];
   }

   public function GetConnectUrl(): string
   {
      return $_ENV['dbConnectUrl'];
   }

   public function GetUsername(): string
   {
      return $_ENV["dbUsername"];
   }

   public function GetPassword(): string
   {
      return $_ENV["dbPassword"];
   }
}
