<?php
// Alarms Notification Script; ver 1.0; 12th January 2021; Indrek Hiie
//
// Revision history:
// 1.00, 12.01.2020
//
// #######################################################################################
//  * START *
// #######################################################################################

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';  // Load PHPMailer

global $missed;
require("functions.inc.php");
global $xTerm;
if(getenv('TERM')) $xTerm = "\e[0m"; else $xTerm="";

// cmdline option processing
cmdline();

date_default_timezone_set('Europe/Tallinn');
$datenow0=date("YmdHis");
if(!$silentall) echo " * timestamp: $datenow0";
if(!$silent) echo "\n";

// TempSense
if(!$silentall) echo " * MySQL connection: ";
try {
        $db = new PDO(config()['db']['connectUrl'], config()['db']['username'], config()['db']['password']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
        echo "FAILED (" . $e->getMessage() . ")\n";
        exit;
} if(!$silentall) echo "OK\n";

$timer_0=$timer_start=time();

if($u1) $timer_start = process($db);
if($u2) $timer_end   = sendQueue($db);

// ------------------- CLEANUP

unset($db); unset($res);

$timer_end=time();
if(!$silentall) {
   echo " * timestamp: " . date("YmdHis");
   echo " * total time used: " . ($timer_end-$timer_0) . " sec\n";
}

// #######################################################################################
//  * END *
// #######################################################################################

function process($db) {
   global $timer_start;
   global $limit;
   global $silent;
   global $silentall;
   global $datenow0;
   global $xTerm;

   if($xTerm) $xRed=    "\e[0;31m"; else $xRed="";
   if($xTerm) $xGreen=  "\e[0;32m"; else $xGreen="";
   if($xTerm) $xBlue=   "\e[0;34m"; else $xBlue="";
   if($xTerm) $xGrey=   "\e[0;90m"; else $xGrey="";

   $parms=getParms($db);
   $sen=getSensors($db);
   $sensors=count($sen);

   //$sen[$i][0]=$values["id"];
   //$sen[$i][1]=$values["name"];
   //$sen[$i][2]=$values["serial"];
   //$sen[$i][3]=$values["ip"];
   //$sen[$i][4]=$values["desc"];
   //$sen[$i][5]=$values["portable"];
   //$sen[$i][6]=$i;

   $topic= 'WatchDog';
   if(!$silent) echo " * $topic started\n";

   $mailBody="Status report:<br />\n";
   $aCnt_total=0;
   $lCnt_total=0;

   if(isset($parms['avg_cnt'])) {
      $avg_cnt=$parms['avg_cnt'];
      if($avg_cnt < 1) $avg_cnt=1;
      if($avg_cnt > 8) $avg_cnt=8;
   } else $avg_cnt=4;

   foreach ($sen as $nam) {
      $i=0;
      $aCnt_sensor=0;
      $lCnt_sensor=0;

      $serial=$nam[2];
      $sql="
            SELECT a1.`id`, a1.`passKey`, a1.`temp`, a1.`alarms`, a1.`dactdate`,
               a3.`alarmStatus`, a3.`lostStatus`, a3.`id` AS sensID,
               (SELECT ROUND(AVG(`temp`),2) FROM (SELECT `temp` FROM `webtemp` WHERE `passKey`='$serial' ORDER BY `dactdate` DESC LIMIT $avg_cnt ) AS t1) AS avg4temp,
               (DATE_FORMAT(NOW(),'%Y%m%d%H')-DATE_FORMAT(STR_TO_DATE(a1.`dactdate`, '%Y%m%d%H%i'),'%Y%m%d%H')) AS diffHrs
            FROM `webtemp` AS a1
            INNER JOIN `sensors` AS a2 ON a1.`passKey`=a2.`serial`
            INNER JOIN `alarms` AS a3 ON a1.`passKey`=a3.`serial`
            WHERE a2.`active`= '1' AND a2.`portable`= '0'
            AND a1.`passKey`='$serial'
            ORDER BY a1.`dactdate` DESC LIMIT 1;
         ";
      //echo $sql."\n";
      $res = $db->query($sql);

      if(!$res)
      {
         die("Execute query error, because: ". print_r($db->errorInfo(),true) );
      }
      else
         while($row = $res->fetch())
         {
            $i++;
            $res01=$row['id'];
            $res02=$row['passKey'];
            $res03=$row['temp'];
            $res04=$row['alarms'];
            $res05=$row['dactdate'];
            $res06=$row['alarmStatus'];
            $res07=$row['lostStatus'];
            $res08=$row['sensID'];
            $res09=$row['avg4temp'];
            $res10=$row['diffHrs'];

            if(!$silent) $strC="\n"; else $strC="";
            $strC.="Sensor '$nam[1]' ser:'$nam[2]' status: ";
            if(!$silent) $strC.="\n";
            $str="<br />\nSensor '$nam[1]' ser:'$nam[2]' status: <br />\n";

            $strR="Row: $res01 Serial:$res02 Temp:$res03 ";
            $strR.="Status:$res04 Time:$res05 AlarmSt:$res06 ";
            $strR.="lostSt:$res07 sensID:$res08 AVG: $res09 DiffHr:$res10 ";
            if(!$silent) $strC.="$strR\n";
            //$str.= $strR . "<br />\n";

            if ( ($res09 < $parms['alert_mintemp']) OR ($res09 > $parms['alert_maxtemp']) ) {

               if(($res09 < $parms['alert_mintemp'])) { $cold="blue"; $xCo=$xBlue; } else { $cold="red"; $xCo=$xRed; }
               if(!$silent) $strC.=$xGrey . " * Average temp $xCo" . $res09 . "ºC$xGrey ($avg_cnt last rec's) out of range (<" . $parms['alert_mintemp'] . "ºC or >" . $parms['alert_maxtemp'] . "ºC) !!!$xTerm ";
               $str.="<span style='color:grey'>Average temp <span style='color:$cold'>" . $res09 . "ºC</span> ($avg_cnt last rec's) out of range (<" . $parms['alert_mintemp'] . "ºC or >" . $parms['alert_maxtemp'] . "ºC) !!!</span><br />\n";

               if(!$res06) {
                  $strC.=$xRed . "--Out-Of-Range ALARM set--$xTerm ";
                  $str.="<span style='color:red'>--Out-Of-Range ALARM set--</span><br />\n";
                  upd_alarm($db,$res02,'1',$datenow0);
                  $aCnt_sensor++;
               }

            } else {
               if($res06) {
                  $strC.=$xGreen . "--Range OK set--$xTerm ";
                  $str.="<span style='color:green'>--Range OK set--</span><br />\n";
                  upd_alarm($db,$res02,'0',$datenow0);
               }
               if(!$silent) $strC.=$xGreen . " * Range OK $xTerm ";
               $str.="<span style='color:green'>Range OK</span><br />\n";
            }
            if(!$silent) $strC.="\n";

            if($res10 > $parms['watchdog_hrs']) {
               if(!$silent) $strC.=$xGrey . " * Sensor not seen $res10 hrs (>" . $parms['watchdog_hrs'] . " hrs) !!!$xTerm ";
               $str.="<span style='color:grey'>Sensor not seen $res10 hrs (>" . $parms['watchdog_hrs'] . " hrs) !!!</span><br />\n";

               if(!$res07) {
                  $strC.=$xRed . "--Sensor-Lost ALARM set--$xTerm ";
                  $str.="<span style='color:red'>--Sensor-Lost ALARM set--</span><br />\n";
                  upd_lost($db,$res02,'1',$datenow0);
                  $lCnt_sensor++;
               }

            } else {
               if($res07) {
                  $strC.=$xGreen . "--Sensor-Visible OK set--$xTerm ";
                  $str.="<span style='color:green'>--Sensor-Visible OK set--<span><br />\n";
                  upd_lost($db,$res02,'0',$datenow0);
               }
               if(!$silent) $strC.=$xGreen . " * Sensor OK $xTerm ";
               $str.="<span style='color:green'>Sensor OK</span><br />\n";
            }
            if(!$silent) $strC.="\n";

            $contacts=getContacts($res02,$db);
            //print_r($contacts);
            $mailBody.=$str;
            $str.= "<br/>\nWbr, WatchDog<br />\n";
            $aCnt_total+=$aCnt_sensor;
            $lCnt_total+=$lCnt_sensor;
            if(!$silent) echo $strC;

            if($aCnt_sensor OR $lCnt_sensor) {
               if($silent) echo $strC . "\n";
               foreach ($contacts as $con) {
                  $mailAddr=$con[1];
                  $mailName=$con[2];
                  $mailSubject="=?utf-8?Q?ALARM:=E2=80=BC=EF=B8=8F_" . $parms['name']. $parms['name_ext'] . "_Sensor_'" . $nam[1] . "'?=";
                  entertoDB_queue($db,$mailAddr,$mailName,addslashes($mailSubject),addslashes($str),'0',$datenow0);
                  echo "Email for $mailName '$mailAddr' put into queue!\n";
               }
            }
            else if(!$silent) echo "Email not needed to send!\n";

         } // else_while...
   } //foreach sensor

   $contacts=getContacts('all',$db);
   //print_r($contacts);
   $mailBody.= "<br/>\nWbr, WatchDog<br />\n";
   if(!$silent) echo "\n * Total:\n";

   if($aCnt_total OR $lCnt_total) {
      if($silent) echo " * Total:\n";
      foreach ($contacts as $con) {
         $mailAddr=$con[1];
         $mailName=$con[2];
         $mailSubject="=?utf-8?Q?ALARM:=E2=80=BC=EF=B8=8F_" . $parms['name'] . $parms['name_ext'] . "tempsens_global?=";

         entertoDB_queue($db,$mailAddr,$mailName,addslashes($mailSubject),addslashes($mailBody),'0',$datenow0);
         echo "Email for $mailName '$mailAddr' put into queue!\n";
      }
   }
   else if(!$silent) echo "Email not needed to send!\n";

   if(!$silent) echo "\n";
   $timer_end=time();
   //$datenow=date("YmdHis");
   //if(!$silentall) echo "* timestamp: $datenow **\n";
   return $timer_end;
}

function sendQueue($db) {
   global $timer_start;
   global $limit;
   global $silent;
   global $silentall;
   global $datenow0;

   $topic= 'Queue processing';
   if(!$silent) echo " * $topic started\n";

   $qry = "SELECT * FROM `queue` AS a1 WHERE `status`<'2';";
   //echo $qry;

   if (!$res = $db->query($qry)) {
      echo "Queue query error!<br />\n";
   }
   else {
      $i=$j=$k=0;
      while($values = $res->fetch()) {
         $i++;
         $mailID  =$values['id'];
         $mailAddr=$values['addr'];
         $mailName=$values['name'];
         $mailSubj=$values['subj'];
         $mailBody=$values['body'];
         $mailErr = mailer($mailAddr,$mailName,$mailSubj,$mailBody);
         if($mailErr) {
            $k++;
            echo "ERROR: Mail '" . $mailErr . "' for user $mailName, email '$mailAddr'!\n";
            upd_queue($db,$mailID,'1');
         }
         else {
            $j++;
            echo "OK: User $mailName email to '$mailAddr' sent!\n";
            upd_queue($db,$mailID,'2');
         }
      } //while
   } //else

   if(!$silent AND ($i>0)) echo "Processed $i messages in queue, $j OK, $k errors\n";
   if(!$silent AND ($i==0)) echo "Queue empty!\n";
   if(!$silent) echo "\n";

   $timer_end=time();
   //$datenow=date("YmdHis");
   //if(!$silentall) echo "* timestamp: $datenow **\n";
   return $timer_end;
}

function entertoDB_queue($db,$w00,$w01,$w02,$w03,$w04,$w05) {
   $sql="INSERT INTO `queue` (`id`,`addr`,`name`,`subj`,`body`,`status`,`duedate`)";
   $sql.=" VALUES ('','$w00','$w01','$w02','$w03','$w04','$w05');";
   //echo $sql."\n";
   if (!$res = $db->query($sql)) {
      echo "MySQL update error: $sql \n";
   }
}

function entertoDB_alarms($db,$w00,$w01,$w02,$w03,$w04) {
   $sql="INSERT INTO `alarms` (`id`,`serial`,`alarmStatus`,`alarmDate`,`lostStatus`,`lostDate`)";
   $sql.=" VALUES ('','$w00','$w01','$w02','$w03','$w04');";
   //echo $sql."\n";
   if (!$res = $db->query($sql)) {
      echo "MySQL update error: $sql \n";
   }
}

function upd_alarm($db,$w00,$w01,$w02) {
   $sql = "UPDATE `alarms` SET
             `alarmStatus` = '$w01',
             `alarmDate`   = '$w02'
           WHERE  `serial` = '$w00';";
   //echo $sql."\n";
   if (!$res = $db->query($sql)) {
      echo "MySQL update error: $sql \n";
   }
}

function upd_lost($db,$w00,$w01,$w02) {
   $sql = "UPDATE `alarms` SET
             `lostStatus` = '$w01',
             `lostDate`   = '$w02'
           WHERE `serial` = '$w00';";
   //echo $sql."\n";
   if (!$res = $db->query($sql)) {
      echo "MySQL update error: $sql \n";
   }
}

function upd_queue($db,$w00,$w01) {
   $sql = "UPDATE `queue` SET
             `status` = '$w01'
           WHERE `id` = '$w00';";
   //echo $sql."\n";
   if (!$res = $db->query($sql)) {
      echo "MySQL update error: $sql \n";
   }
}

function cmdline() {
   global $u1;
   global $u2;
   global $u_limit;

   global $limit;
   global $silent;
   global $silentall;

   $u1=$u2=0;
   $u_limit=$u_silent=$u_silentall=0;

   $longopts  = array(
      "check",
      "send",
      "all",
      "limit",
      "silent",
      "silentall"
   );

   $opts = getopt("",$longopts);

   // Handle command line arguments
   foreach (array_keys($opts) as $opt) switch ($opt) {
      case 'check':
         $u1=true;
         //$something = $opts['product'];
         break;
      case 'send':
         $u2=true;
         break;
      case 'all':
         $u1=true;
         $u2=true;
         break;
      case 'limit':
         $u_limit=true;
         break;
      case 'silent':
         $u_silent=true;
         break;
      case 'silentall':
         $u_silentall=true;
         break;
   }

   if($u_silent) $silent = true; else $silent = false;
   if($u_silentall) { $silentall = true; $silent = true; } else $silentall = false;

   if($u1 & $u2) {
      if(!$silent) echo "Processing: everything\n";
   }
   else
      if(!$silent)
         if($u1 | $u2 ) {
            echo "Processing: [ ";
            if($u1) echo "1x ";
            if($u2) echo "2x ";
            echo "]\n";
         }
         else echo "Use: php alarm.php [--check|--send|--all] [--limit|--silent|--silentall]\n";

   if($u_limit)  { if(!$silent) echo "limit mode: 2 rows limit\n"; $limit = 'LIMIT 1 '; } else $limit ='';

}

function mailer($mailAddr, $mailName, $mailSubject, $mailBody) {
   $aeg = date('d-m-Y H:i',time());
   $mail = new PHPMailer(true);
   try {  //Server settings
      $mail->CharSet = 'UTF-8';
      $mail->Encoding = 'base64';
      $mail->SMTPDebug = 0;  //set '2' for debugging
      $mail->isSMTP();
      $mail->Host = config()['smtp']['host'];
      $mail->SMTPAuth = config()['smtp']['auth'];
      $mail->Username = config()['smtp']['username'];
      $mail->Password = config()['smtp']['password'];
      $mail->SMTPSecure = 'tls';
      $mail->Port = config()['smtp']['port'];

/*
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->SMTPOptions = array(
         'ssl' => [
         'verify_peer' => false,
         'verify_depth' => 3,
         'allow_self_signed' => false,
         'peer_name' => 'smtp.sirowa.com',
         'cafile' => '~/.ssh/ProxyCA.cer',
         ],
      );
*/

      $mail->setFrom(config()['smtp']['email'], config()['smtp']['name']);

      $mailBody="<style type='text/css'><!--\n
         table { width: 100%; border-collapse: collapse; }\n
         table th { border: 1px #b9bbbe solid; padding: 8px; background: #f0f0f0; font-weight: bold; text-align: left; }\n
         table td { border: 1px #b9bbbe solid; padding: 8px; text-align: left; }\n
         -->\n</style>\n" . $mailBody;

      if(config()['dev']['dev']) {
         $mailAddr=config()['dev']['email'];
         $mailName=config()['dev']['name'];
      }

      $mail->addAddress($mailAddr, $mailName);

//         $mail->addAddress('recipient2@example.com');
//         $mail->addReplyTo('noreply@example.com', 'noreply');
//         $mail->addCC('cc@example.com');
//         $mail->addBCC(config()['dev']['email'], config()['dev']['name']);
//         Attachments
//         $mail->addAttachment('/backup/myfile.tar.gz');

      //Content
      $mail->isHTML(true);
      $mail->Subject = $mailSubject;
      $mail->Body    = $mailBody;

      $mail->send();
      return 0;
   } catch (Exception $e) {
      return $mail->ErrorInfo;
   }
}

function getContacts($ser, $db) {
   $contacts=array();
   if($ser=='all')
      $qry = "SELECT a1.`email` AS email, a1.`name` AS name FROM `emails` AS a1, `emails_to_sensors` AS a2
                 WHERE a1.`id`=a2.`emails_id` AND a2.`sensors_id`='99999';";
   else
      $qry = "SELECT a1.`email` AS email, a1.`name` AS name FROM `emails` AS a1, `emails_to_sensors` AS a2, `sensors` AS a3
                 WHERE a1.`id`=a2.`emails_id` AND a3.`id`=a2.`sensors_id` AND a3.`serial`='$ser';";
   //echo $qry;

   if (!$res = $db->query($qry)) {
      echo "Contacts query error!<br />\n";
   }
   else {
      $i=0;
      while($values = $res->fetch()) {
         $i++;
         $contacts[$i][1]=$values['email'];
         $contacts[$i][2]=$values['name'];
      }
   }
   return $contacts;
}

?>
