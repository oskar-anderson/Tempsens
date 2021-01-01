<?php
// T3510 - PHP + NGINX
// XSD schema: http://cometsystem.cz/schemas/soapTx5xx_v2.xsd
 
/* ----------------------------------------------------------------------------
CREATE TABLE `webtemp` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `passKey` varchar(8) NOT NULL DEFAULT '',
  `device` varchar(4) NOT NULL DEFAULT '',
  `temp` varchar(5) NOT NULL DEFAULT '',
  `relHum` varchar(5) NOT NULL DEFAULT '',
  `compQuant` varchar(5) NOT NULL DEFAULT '',
  `pressure` varchar(5) NOT NULL DEFAULT '',
  `alarms` varchar(11) NOT NULL DEFAULT '',
  `compType` varchar(18) NOT NULL DEFAULT '',
  `tempU` varchar(1) NOT NULL DEFAULT '',
  `pressureU` varchar(7) NOT NULL DEFAULT '',
  `timer` varchar(5) NOT NULL DEFAULT '',
  `dactdate` varchar(12) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
 
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
   xmlns:xsd="http://www.w3.org/2001/XMLSchema">
   <soap:Body>
      <InsertTx5xxSample xmlns="http://cometsystem.cz/schemas/soapTx5xx_v2.xsd">
         <passKey>13960932</passKey>
         <device>4145</device>
         <temp>1.4</temp>
         <relHum>91.9</relHum>
         <compQuant>0.3</compQuant>
         <pressure>-9999</pressure>
         <alarms>hi,no,no,no</alarms>
         <compType>Dew point</compType>
         <tempU>C</tempU>
         <pressureU>n/a</pressureU>
         <timer>60</timer>
      </InsertTx5xxSample>
   </soap:Body>
</soap:Envelope>
-------------------------------------------------------------------------------  */

   function Config()
   {
      global $configData;
      if (! isset($configData)) {
         $configData = require ("./config/config.php");
      }
      return $configData;
   }
 
function save_temp($db1,$w00,$w01,$w02,$w03,$w04,$w05,$w06,$w07,$w08,$w09,$w10,$w11,$w12) {
   $sql1="INSERT INTO `webtemp` (`id`,`passKey`,`device`,`temp`,`relHum`,`compQuant`,`pressure`,`alarms`,`compType`,`tempU`,`pressureU`,`timer`,`dactdate`)";
   $sql1=$sql1." VALUES ('$w00','$w01','$w02','$w03','$w04','$w05','$w06','$w07','$w08','$w09','$w10','$w11','$w12');";
  
   if (!$res1 = $db1->query($sql1)) {
      echo "MySQL insert error $sql1 \n";
   }
  return $sql1;
}
 
function InsertTx5xxSample($passKey,$device,$temp,$relHum,$compQuant,$pressure,$alarms,$compType,$tempU,$pressureU,$timer) {
global $db1;
global $now;
 
init();
$sql=save_temp($db1,'',$passKey,$device,$temp,$relHum,$compQuant,$pressure,$alarms,$compType,$tempU,$pressureU,$timer,$now);
unset($db1);
 
$data = "Time: ". StrFTime("%y/%m/%d %H:%M:%S", Time()).", Temp: ".$temp. ", RH: ".$relHum.", CQ: ".$compQuant. "\n";
$fp = fopen('data.txt', 'a');
// fwrite($fp, $sql);
fwrite($fp, $data);
fclose($fp);
}
 
function init() {
   global $db1;
   global $now;
 
   date_default_timezone_set('Europe/Tallinn');
   $now=date("YmdHis");

   // Temperature DB
   try {
     $db1 = new PDO(Config()['db']['connectUrl'], Config()['db']['username'], Config()['db']['password']);
     $db1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   } catch (PDOException $e) {
     echo "FAILED (" . $e->getMessage() . ")\n";
     exit;
   } echo "OK\n";

/* 
   // DB
   try {
      $db1 = new PDO('mysql:host=localhost;dbname=tempsens','tempsens', 'zU83rW338.5');
      $db1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   } catch (PDOException $e) {
      echo "FAILED (" . $e->getMessage() . ")\n";
      exit;
   } echo "OK\n";
*/
}
 
$server = new SoapServer(null, array('uri' => "http://localhost/temp.php"));
$server->addFunction('InsertTx5xxSample');
$server->handle();
 
?>
