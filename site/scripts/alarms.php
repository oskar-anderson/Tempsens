<?php
// Alarms Notification Script; ver 0.03; 7th January 2021; Indrek Hiie
//
// Revision history:
// 0.01, 04.01.2021 - initial test script
// 0.02, 05.01.2021 - basic queries [2h]
// 0.03, 07.01.2021 - watchdog [3h+3h]
// #######################################################################################
//  * START *
// #######################################################################################

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';  // Load PHPMailer

global $missed;
require("functions.inc.php");

// cmdline option processing
cmdline();

date_default_timezone_set('Europe/Tallinn');
$datenow0=date("YmdHis");
echo "* timestamp: $datenow0";
if(!$silent) echo "\n";

// TempSense
echo " * MySQL connection: ";
try {
        $db = new PDO(config()['db']['connectUrl'], config()['db']['username'], config()['db']['password']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
        echo "FAILED (" . $e->getMessage() . ")\n";
        exit;
} echo "OK"; if(!$silent) echo "\n";

if($u1) process($db);

$timer_0=$timer_start=time();

// ------------------- CLEANUP

unset($db); unset($res);

$timer_end=time();
echo "* timestamp: " . date("YmdHis") . " ";
echo "* total time used: " . ($timer_end-$timer_0) . " sec\n";

// #######################################################################################
//  * END *
// #######################################################################################


function process($db) {
   global $timer_start;
   global $limit;
   global $silent;
   global $silentall;
   global $datenow0;

   $parms=getParms($db);
   $sen=getSensors($db);
   //print_r($sen);
   $sensors = count($sen);

   //$sen[$i][0]=$values["id"];
   //$sen[$i][1]=$values["name"];
   //$sen[$i][2]=$values["serial"];
   //$sen[$i][3]=$values["ip"];
   //$sen[$i][4]=$values["desc"];
   //$sen[$i][5]=$values["portable"];
   //$sen[$i][6]=$i;

   $topic= 'WatchDog';

   if(!$silent) echo "* $topic started\n";

   $mailBody="Status report:<br /><br />\n\n";
   $aCnt=0;
   $lCnt=0;

   foreach ($sen as $nam) {
      $serial=$nam[2];

      $sql="
            SELECT a1.`id`, a1.`passKey`, a1.`temp`, a1.`alarms`, a1.`dactdate`,
               a3.`alarmStatus`, a3.`lostStatus`, a3.`id` AS sensID,
               (SELECT ROUND(AVG(`temp`),2) FROM (SELECT `temp` FROM `webtemp` WHERE `passKey`='$serial' ORDER BY `dactdate` DESC LIMIT 4) AS t1) AS avg4temp,
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

      $i=0;

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

            $str="Row: $res01 Serial:$res02 Temp:$res03 ";
            $str.="Status:$res04 Time:$res05 AlarmSt:$res06 ";
            $str.="lostSt:$res07 sensID:$res08 AVG: $res09 DiffHr:$res10 ";
            echo "$str\n";
            $mailBody.=$str . "<br />\n";

	    echo "Sensor '$nam[1]' ser:'$nam[2]' status: \n";
	    $mailBody.="Sensor '$nam[1]' ser:'$nam[2]' status: <br />\n";

            if ( ($res09 < $parms['alert_mintemp']) OR ($res09 > $parms['alert_maxtemp']) ) {
               echo "Average temp " . $res09 . "ºC (4 last rec's) out of range (<" . $parms['alert_mintemp'] . "ºC or >" . $parms['alert_maxtemp'] . "ºC) !!!\n";
               $mailBody.="Average temp " . $res09 . "ºC (4 last rec's) out of range (<" . $parms['alert_mintemp'] . "ºC or >" . $parms['alert_maxtemp'] . "ºC) !!!<br />\n";

               if(!$res06) {
                  echo "--Range ALARM set--\n";
                  $mailBody.="--Range ALARM set--<br />\n";
                  upd_alarm($db,$res02,'1',$datenow0);
                  $aCnt++;
               }

            } else {
               if($res06) {
                  echo "--Range OK set--\n";
                  $mailBody.="--Range OK set--<br />\n";
                  upd_alarm($db,$res02,'0',$datenow0);
               }
               echo "Range OK\n";
               $mailBody.="Range OK<br />\n";
            }

            if($res10 > $parms['watchdog_hrs']) {
               echo "Sensor not seen $res10 hrs (>" . $parms['watchdog_hrs'] . " hrs) !!!\n";
               $mailBody.="Sensor not seen $res10 hrs (>" . $parms['watchdog_hrs'] . " hrs) !!!<br />\n";

               if(!$res07) {
                  echo "--WatchDog ALARM set--\n";
                  $mailBody.="--WatchDog ALARM set--<br />\n";
                  upd_lost($db,$res02,'1',$datenow0);
                  $lCnt++;
               }

            } else {
               if($res07) {
                  echo "--WatchDog OK set--\n";
                  $mailBody.="--WatchDog OK set--<br />\n";
                  upd_lost($db,$res02,'0',$datenow0);
               }
               echo "WatchDog OK\n";
               $mailBody.="WatchDog OK<br />\n";
            }

         } // else_while...

   } //foreach sensor

//      $email=$row['user_email'];
//      $name =$row['user_name'];

      $mailAddr=config()['dev']['email'];
      $mailName=config()['dev']['name'];
      $mailSubject="ALARM: tempsens";
      $mailBody.= "<br/>\nWbr, WatchDog<br />\n";
      //echo $mailBody;

      if($aCnt OR $lCnt) {
         $mailErr = mailer($mailAddr,$mailName,$mailSubject,$mailBody);
         if($mailErr) {
            echo "ERROR: Mail '" . $mailErr . "' for user $mailName, email '$mailAddr'!\n";
         }
         else echo "OK: User $mailName email to '$mailAddr' sent!\n";
      }
      else echo "Email not needed to send!\n";

      $timer_end=time();
      $datenow=date("YmdHis");
      if(!$silentall) echo "* timestamp: $datenow **\n";
      return $timer_end;
}

function entertoDB_alarms($db,$w00,$w01,$w02,$w03,$w04,$w05) {
   $sql="UPDATE `alarms` (`id`,`serial`,`alarmStatus`,`alarmDate`,`lostStatus`,`lostDate`)";
   $sql.=" VALUES ('EE','$w00','$w01','$w02','$w03','$w04','$w05');";
   //echo $sql."\n";
   if (!$res = $db->query($sql)) {
      echo "MySQL update error: $sql \n";
   }
}

function upd_alarm($db,$w00,$w01,$w02) {
   $sql = "UPDATE `alarms` SET
             `alarmStatus`    = '$w01',
             `alarmDate`   = '$w02'
           WHERE `serial` = '$w00';";
   //echo $sql."\n";
   if (!$res = $db->query($sql)) {
      echo "MySQL update error: $sql \n";
   }
}

function upd_lost($db,$w00,$w01,$w02) {
   $sql = "UPDATE `alarms` SET
             `lostStatus`    = '$w01',
             `lostDate`   = '$w02'
           WHERE `serial` = '$w00';";
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
      "1",
      "2",
      "all",
      "limit",
      "silent",
      "silentall"
   );

   $opts = getopt("",$longopts);

   // Handle command line arguments
   foreach (array_keys($opts) as $opt) switch ($opt) {
      case '1':
         $u1=true;
         //$something = $opts['product'];
         break;
      case '2':
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
         else echo "Use: php alarm.php [--1|--2|--all] [--limit|--silent|--silentall]\n";

   if($u_limit)  { if(!$silent) echo "limit mode: 2 rows limit\n"; $limit = 'LIMIT 1 '; } else $limit ='';

}

/*
function time_passed($secs)
   {
   $bit = array(
      'y' => $secs / 31556926 % 12,
      'w' => $secs / 604800 % 52,
      'd' => $secs / 86400 % 7,
      'h' => $secs / 3600 % 24,
      'm' => $secs / 60 % 60,
      's' => $secs % 60
      );

   foreach($bit as $k => $v)
      if($v > 0)$ret[] = $v . $k;

   return join(' ', $ret);
   }

function escape($value)
   {
       $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
       $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");

       return str_replace($search, $replace, $value);
   }
*/

function mailer($mailAddr, $mailName, $mailSubject, $mailBody)
   {
      $aeg = date('d-m-Y H:i',time());

      $mail = new PHPMailer(true);
      try {
      //Server settings
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

//$mailAddr = "indrek.hiie@mail.ee";
//$mailName = "Indrek Hiie";

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

/*
function message($lang, $part)
   {

   $msg = array();

   switch ($lang) {
      case 'et':
      case 'ET':
         $msg[1]="Armas,<br />\n";
         break;
      default:
         $msg[1]="Dear,<br />\n";
         break;
   }
   return $msg[$part];
}
*/

?>
