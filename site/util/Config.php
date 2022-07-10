<?php


namespace App\util;

require_once(__DIR__."/../../vendor/autoload.php");
use Dotenv\Dotenv;

class Config
{
   public function __construct()
   {
      $environmentFileExists = file_exists(__DIR__.'/../../.env');
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
      // Console::WriteLine("GetWebDbPassword:" . $_ENV['webDbPassword']);
      return $_ENV['webDbPassword'];
   }

   public function GetConnectUrl(): string
   {
      // Console::WriteLine("GetConnectUrl:" . $_ENV['db_local_dev_connectUrl']);
      return $_ENV['db_local_dev_connectUrl'];
   }

   public function GetUsername(): string
   {
      // Console::WriteLine("GetUsername:" . $_ENV['db_local_dev_username']);
      return $_ENV["db_local_dev_username"];
   }

   public function GetPassword(): string
   {
      // Console::WriteLine("GetPassword:" . $_ENV['db_local_dev_password']);
      return $_ENV["db_local_dev_password"];
   }
}
