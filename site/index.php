<?php require("header.php"); ?>
<!DOCTYPE HTML>
<!--
    Termsens rev. 0.3.5, 24.01.2020

    Revison history:
    ver 0.1.0, 15.01.2020 by Indrek Hiie - Soap Data Collector
    ver 0.2.0, 27.02.2020 by Timm Soodla - initial GUI version
    ver 0.3.0, 30.12.2020 by Indrek Hiie - moved to PDO MySQL driver, sensors now in DB, more modular code and some bugfixes applied
    ver 0.3.5, 24.01.2020 - watchdog added

    Thank you for reading my HTML sources. I can be reached at indrek.hiie¤mail.ee.
-->
<html>
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
   <meta http-equiv="Content-Script-Type" content="text/javascript" />
   <meta http-equiv="Content-Style-Type" content="text/css" />
   <meta name="robots" content="index,nofollow" />
   <meta name="keywords" content="Sensors" />
   <meta name="description" content="Sensors" />
   <title>Sensor</title>

   <link rel="shortcut icon" href="gfx/favicon.ico" type="image/ico" />
   <link rel="icon" href="gfx/favicon.ico" type="image/ico" />

   <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
   <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
   <link rel="stylesheet" type="text/css" media="screen" href="./css/css.php" />

   <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
   <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
   <script type="text/javascript" src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

   <script type="text/javascript">
   $( function() {
      $( "#dateFrom" ).datepicker({
         dateFormat: "dd-mm-yy",
         onSelect: function( selectedDate ) {
            var newDate = $(this).datepicker("getDate");
            if (newDate) { // Not null
               newDate.setDate(newDate.getDate() + <?php echo $per_a[$per][3]; ?>);
            };
            $( "#dateTo" ).datepicker("option", "minDate", newDate);
            $( "#dateTo" ).datepicker("setDate", newDate);
         }
      });
      $( "#dateTo" ).datepicker({
         dateFormat: "dd-mm-yy"
      });
   } );
   </script>

   <script type="text/javascript">
   google.load("visualization", "1", {packages:["corechart"]});
   google.setOnLoadCallback(drawChart);

   function drawChart() {
      var data = google.visualization.arrayToDataTable([
         ['dactdate','temp'],
<?php
   foreach ($graph as $row) {
      echo "['" . date('m/d H:i',$row[0]) . "'," . $row[4] . "],";
   }
?>
         ]);

      var options = {
         title: '<?php echo "$row[3]"; ?> Temperatures',
         fontSize: 13,
         explorer: {},
         legend: 'none'
      };
      var chart = new google.visualization.LineChart(document.getElementById("chart"));
      google.visualization.events.addListener(chart, 'error', function (googleError) {
      google.visualization.errors.removeError(googleError.id);
      document.getElementById("chart").innerHTML = "-- No data for selected period/sensor --";
   });
   chart.draw(data,options);
   }
   </script>
</head>
<body>
   <div id="master">
      <div id="content">
         <span>
         <br />Period &nbsp;
         <select class="active" onchange="window.location='?id=<?php echo $id; ?>&inter=<?php echo $inter; ?>&per='+this.value">
<?php
  foreach ($per_a as $item) {
  echo "            <option value='" . $item[0] . "'";
  if ($per == $item[0]) echo " selected";
  echo "> " . $item[2] . " </option>\n";
  }
?>
         </select> &nbsp; &nbsp; &nbsp;

         Interval &nbsp;
         <select class="active" onchange="window.location='?id=<?php echo $id; ?>&per=<?php echo $per; ?>&dateFrom=<?php echo $dateFrom; ?>&dateTo=<?php echo $dateTo; ?>&inter='+this.value">
            <option value="1" <?php if ($inter == 1) echo "selected";?>> 15 minutes </option>
            <option value="2" <?php if ($inter == 2) echo "selected";?>> 30 minutes </option>
            <option value="4" <?php if ($inter == 4) echo "selected";?>> 1 hour </option>
            <option value="24" <?php if ($inter == 24) echo "selected";?>> 6 hours </option>
            <option value="48" <?php if ($inter == 48) echo "selected";?>> 12 hours </option>
            <option value="96" <?php if ($inter == 96) echo "selected";?>> 24 hours </option>
         </select> &nbsp;
         </span>

         <br />Sensors:
<?php
  foreach ($sen as $nam) {
     echo "            <a href='$baseurl?id=" . $nam[0] . "&per=" . $per. "&inter=" . $inter . "&dateFrom=" . $dateFrom ."&dateTo=" . $dateTo ."' title='" . $nam[1] . "' >";
     echo "<img src='gfx/";
     if($nam[5]) echo "ballb"; //portable
        else if($nam[9]) echo "bally"; // lost
        else if($nam[7]) echo "ballr"; // alarm
        else echo "ballg"; //ok
     echo ".png' alt='' id='";
     if($id == $nam[0]) echo "alert_sel"; else echo "alert";
     echo "' /></a> \n";
  }
?>
         &nbsp;&nbsp;View:&nbsp;

         <select class="active" onchange="window.location='?per=<?php echo $per; ?>&inter=<?php echo $inter; ?>&dateFrom=<?php echo $dateFrom; ?>&dateTo=<?php echo $dateTo; ?>&id='+this.value">
<?php
  foreach ($sen as $nam) {
     echo "            <option value='" . $nam[0] . "'";
     if ($id == $nam[0]) echo " selected";
     echo "> " . $nam[1] . " </option>\n";
  }
?>
         </select>

         <div align="center">
            <form action="<?php echo $baseurl ?>" class="query" id="dateFromForm" method="post">
               From:
               <input type="text" id="dateFrom" name="dateFrom" size="15" value="<?php echo date('d-m-Y', strtotime($dateFrom)); ?>" />
               &nbsp;&nbsp;To:
               <input type="text" id="dateTo" name="dateTo" size="15" value="<?php echo date('d-m-Y', strtotime($dateTo)); ?>" />
               &nbsp;&nbsp;
               <input type="hidden" id="id" name="id" value="<?php echo $id; ?>" />
               <input type="hidden" id="per" name="per" value="<?php echo $per; ?>" />
               <input type="hidden" id="inter" name="inter" value="<?php echo $inter; ?>" />
               <input type="submit" class="groov" value="Load" style="height:21px;" />
            </form>

            <form action="<?php echo $baseurl ?>" class="query" id="exportForm" method="post">
               <br />
               <input type="hidden" id="id" name="id" value="<?php echo $id; ?>" />
               <input type="hidden" id="dateFrom" name="dateFrom" value="<?php echo $dateFrom; ?>" />
               <input type="hidden" id="dateTo" name="dateTo" value="<?php echo $dateTo; ?>" />
               <input type="hidden" id="export" name="export" value="1" />
               <input type="submit" class="groov" value="Export" style="height:21px;" />
               &nbsp; &nbsp;
            </form>
         </div>

         Sensor (serial: <?php echo $sen[$sen_id][2] ?>, location: <?php echo $sen[$sen_id][4]; ?>) interface: <a href="http://<?php echo $sen[$sen_id][3]; ?>" target="_blank">http://<?php echo $sen[$sen_id][3]; ?></a><br />
         Alarm: <?php
         if($sen[$sen_id][7]) echo '--ACTIVE-- @' . date('d-m-Y H:i',strtotime($sen[$sen_id][8])) . '&nbsp;(' . (strtotime($datenow)-strtotime($sen[$sen_id][8]))/60 . ' min ago)';
         else echo "none";
         ?><br />
         Online: <?php
         if($sen[$sen_id][9]) echo '--LOST-- @' . date('d-m-Y H:i',strtotime($sen[$sen_id][10])) . '&nbsp;(' . (strtotime($datenow)-strtotime($sen[$sen_id][10]))/60 . ' min ago)';
         else echo "ok";
         ?><br />

         <div id="chart"></div>
         <div id="footer">
            <div id="footleft"><b>Recent global alerts:</b> <=<?php echo $parms['stat_mintemp']; ?>ºC, >=<?php echo $parms['stat_maxtemp']; ?>ºC<br />
<?php echo getGlobalAlert('0', 'all', $db1); ?>
            </div>
            <div id="footcenter"><b>Recent current sensor alerts:</b> <=<?php echo $parms['stat_mintemp']; ?>ºC, >=<?php echo $parms['stat_maxtemp']; ?>ºC<br />
<?php echo getGlobalAlert($sen[$sen_id][5], $id, $db1); ?>
            </div>
            <div id="footright"><b>Max/min:</b><br />
<?php
  echo "               Total Min: ". getTop('min',0,'all',$db1);
  echo "               Total Max: ". getTop('max',0,'all',$db1);
  echo "               Total Avg: ". getTop('avg',0,'all',$db1);
  echo "               Sensor Min: ". getTop('min',$sen[$sen_id][5],$id,$db1);
  echo "               Sensor Max: ". getTop('max',$sen[$sen_id][5],$id,$db1);
  echo "               Sensor Avg: ". getTop('avg',$sen[$sen_id][5],$id,$db1);
?>
            </div>
         </div>
      </div>
      <div id="version"><?php echo $parms['name'] . "  " . $parms['release'] . ", " . $parms['date']; ?></div>
   </div>
</body>
</html>

<?php
unset($db1);
?>
