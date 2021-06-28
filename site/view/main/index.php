<?php
/** @noinspection DuplicatedCode */

use App\dto\IndexViewModel;
use App\view\partial\FooterPartial;
use App\view\partial\HeaderPartial;
use App\view\partial\SensorCrudPartialCreateEdit;

/* @var IndexViewModel $htmlInjects */

$dateFromType = $htmlInjects->input->dateFromType;
$dateFrom = $htmlInjects->input->dateFrom;
$dateTo = $htmlInjects->input->dateTo;
$sensorCrud = $htmlInjects->input->sensorCrud;
$selectOptionsRelativeDateFrom = $htmlInjects->input->selectOptionsRelativeDateFrom;

$sensors = $htmlInjects->sensors;
$lastReadingsView = $htmlInjects->lastReadingsView;
$sensorReadingOutOfBounds = $htmlInjects->sensorAlertsMinMax;
$sensorReadingsBySensorId = $htmlInjects->sensorReadingsBySensorId;
$colors = $htmlInjects->colors;
$errors = $htmlInjects->errors;

?>

<!DOCTYPE HTML>
<html lang="en">
<!--suppress HtmlRequiredTitleElement -->
<head>
   <?php echo HeaderPartial::GetHtml('Sensor'); ?>
   <style>
       .dateFromOption {
           display: grid;
           align-items: center;
           grid-template-columns: 20px auto auto;
           height: var(--row_height);
           transition: all 300ms ease-in;
       }

       .graphOptions {
           display: grid;
           grid-template-columns: 4fr 3fr 2fr;
           margin-bottom: 1em;
       }

       .graphOptionsSelect {
           background: var(--color-green-ll-3);
           color: white;
       }

       .zebra-1, .zebra-2, .zebra-3 {
           background: var(--light_grey_02);
       }

       /* Why zebra-1 and zebra-2 need different steps (2n vs 2n-1)??? */
       .zebra-1:nth-of-type(2n), .zebra-2:nth-of-type(2n - 1), .zebra-3:nth-of-type(2n) {
           background: var(--light_grey_01);
       }

       /* #region radio */
       [type="radio"] {
           z-index: -1;
           position: absolute;
           opacity: 0;
       }
       [type="radio"]:checked ~ label {
           background-color: white;
       }
       [type="radio"]:not(:checked) ~ label {
           cursor: pointer;
       }

       label > .radio-dot {
           position: relative;
           display: inline-flex;
           width: 20px;
           height: 20px;
           border-radius: 20px;
           background: var(--light_grey_03);
       }

       [type="radio"]:checked ~ label > .radio-dot {
           background-color: #6FA773;
       }

       /* White ball inside checked radio */
       [type="radio"]:checked ~ label .radio-dot:after {
           content: "";
           position: absolute;
           top: 50%;
           left: 50%;
           transform: translate(-50%, -50%);
           width: 6px;
           height: 6px;
           border-radius: 10px;
           background-color: #fff;
       }
       /* #endregion radio */


   </style>
   <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

   <!-- date parsing -->
   <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/dayjs@1.10.4/dayjs.min.js"></script>
   <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/dayjs@1.10.4/plugin/customParseFormat.js"></script>

</head>
<body style="min-height: 100vh; min-width: 900px; margin:0; display: grid; grid-template-rows: 1fr auto">
   <div id="data" style="display:none;"
        data-dateFrom="<?php echo $dateFrom . ', 00:00' ?>"
        data-dateTo="<?php echo $dateTo . ', 23:59' ?>"
        data-sensors='<?php echo json_encode($sensors, JSON_HEX_APOS|JSON_HEX_QUOT); ?>'
   >

   </div>
   <div>
      <div class="🎯" style="height: auto">
         <form name="formJsSubmit" action="" method="get" style="display: none">
            <input id="submitDateFrom" name="From" value="">
            <input id="submitDateTo" name="To" value="">
         </form>
         <div style="width: min(90%, 50em); display: grid; grid-template-columns: 5fr 2fr 5fr; margin-top: 2em;">
            <div>

               <div>
                  <input type="radio" onclick="handleRadioClick(this);" name="dateFromType" id="typeAbsolute"
                     <?php if ($dateFromType === 'absolute') { echo "checked"; }?>
                      value="absolute"/>
                  <label class="dateFromOption" for="typeAbsolute">
                     <span class="radio-dot"></span>
                     <span style="margin-left: 20px">From Date</span>
                     <span class="child-input 🎯" style="--xpos: end;">
                        <input name="absoluteDateFrom" type="text" id="absoluteDateFrom"
                               value="<?php echo $dateFrom; ?>" <?php echo $dateFromType !== 'absolute' ? 'disabled' : ''; ?> />
                     </span>
                  </label>
               </div>

               <div>
                  <input type="radio" onclick="handleRadioClick(this);" name="dateFromType" id="typeRelative"
                          <?php if ($dateFromType === 'relative') {echo "checked";} ?>
                         value="relative" />
                  <label class="dateFromOption" for="typeRelative">
                     <span class="radio-dot"></span>
                     <span style="margin-left: 20px">From Relative</span>
                     <span class="child-input 🎯" style="--xpos: end;">
                        <select name="relativeDateFrom" id="relativeDateFrom" class="active"
                           <?php echo $dateFromType !== 'relative' ? 'disabled' : ''; ?>
                        >
                           <?php
                           foreach ($selectOptionsRelativeDateFrom as $item) {
                              echo $item;
                           }
                           ?>
                        </select>
                     </span>
                  </label>
               </div>
            </div>

            <div class="🎯" style="--xpos: center; --ypos: center">
               <button id="btnLoad" class="button">Load</button>
            </div>
            <div class="🎯" style="height: var(--row_height);">
               <label for="dateTo" class="🎯" style="--xpos: start;">To Date</label>
               <input type="text" name="dateTo" id="dateTo" value="<?php echo $dateTo; ?>" />
            </div>


         </div>
      </div>
      <div style="margin-top: 3em; margin-left: clamp(2%, 6%, 10%); margin-right: clamp(2%, 6%, 10%);">
         <div style="margin-bottom: 1em">

            <div>
               <span class="h2">
                  <span>Overview</span>
                  <span type="button" data-toggle="collapse" data-target="#collapseHelp" aria-expanded="false">
                     <i style="font-size: 0.55em" class="bi bi-question-circle"></i>
                  </span>
               </span>
               <?php if (sizeof($errors) !== 0) { ?>
                  <div class="alert alert-danger" style="margin-bottom: 2em" role="alert">
                     <h4>Errors:</h4>
                     <ol>
                     <?php foreach ($errors as $error) { ?>
                        <li><?php echo $error; ?></li>
                     <?php } ?>
                     </ol>
                  </div>
               <?php } ?>
            </div>

            <div class="collapse" id="collapseHelp">
               <div style="margin-bottom: 1em;">
                  <div style="margin: 0.6em 0">
                     <p>
                        Click on sensor to:
                     </p>
                     <ul style="margin-bottom: 6px">
                        <li>Show alarm info</li>
                        <li>Export data</li>
                        <li>Upload data (only for portable sensors)</li>
                        <li>Edit sensor fields (requires password, will not affect sensor's own configuration)</li>
                     </ul>
                     <p>
                        Exported and imported data is semicolon delimited CSV.
                        It is worth noting that Excel reads files using the computer regional settings and will likely display semicolon delimited files incorrectly.
                        Excel uses delimiter from user PC <code>Control Panel\Clock and Region -> Region -> Additional settings -> List separator</code>
                        (this should be changed from <code>,</code> to <code>;</code>). The exported data format itself is correct.
                     </p>
                  </div>
                  <p>
                     For portable sensors reading data can be added to database by parsing CSV files.
                     Look for upload option under the specific portable sensor.
                     Order of columns can be changed.
                     Column separator is <code>;</code>.
                     Double separator <code>;;</code> skips in between column.
                     Any empty values and error values are skipped.
                     Allowed column values are: <code>date</code>, <code>temp</code>, <code>relHum</code>.
                  </p>
                  <p>
                     Sensors alarms will combine all continues alarms under one parent alarm.
                     Parent alarm will show starting time, duration, count of sub alarms and sub alarm deviation
                     (largest offset from average allowed value) of temperature and relative humidity.
                     Alarm duration are approximate depending on sensor reading interval.
                  </p>
               </div>


               <div style="margin-bottom: 1em">
                  <div style="display: grid; grid-auto-rows: auto; grid-template-columns: 30px 10em; width: min-content;"
                       class="collapse-and-change-icon" data-toggle="collapse" data-target="#collapseSensorCreate" aria-expanded="false">
                     <div style="padding-left: 4px" type="button">
                        <i class="bi bi-caret-right"></i>
                     </div>
                     <div>Create new sensor</div>
                  </div>
                  <form action="" method="post" style="padding: 0.4em 30px 0.4em 30px; margin-top: 1.2em" class="collapse" id="collapseSensorCreate">
                     <div style="display: grid; grid-template-columns: 1fr 1fr; grid-gap: 0.4em 3em; padding-bottom: 0.8em">
                        <?php echo SensorCrudPartialCreateEdit::GetHtml($sensorCrud->createBadValues->sensor ?? null) ?>
                     </div>
                     <div>
                        <span>Auth</span>
                        <input type="password" name="auth" value="<?php echo htmlspecialchars($sensorCrud->createBadValues->auth ?? ''); ?>">
                        <button class="button" type="submit" name="formType" value="create">Create</button>
                     </div>
                  </form>
               </div>

            </div>
         </div>

         <div class="wrapper zebra">
            <div style="--ypos: center;--xpos: start; height: 2em; display: grid; grid-auto-rows: auto; grid-template-columns: 30px 5fr 6fr 2fr 2fr 2fr 2fr 2fr 2fr 2fr;">
               <div class="🎯 table-head" style="padding-left: 4px">#</div>
               <div class="🎯 table-head">Sensor</div>
               <div class="🎯 table-head">Latest</i></div>
               <div class="🎯 table-head">Alerts</div>
               <div class="🎯 table-head"><i class="bi bi-thermometer-half">℃<br> avg</i></div>
               <div class="🎯 table-head"><i class="bi bi-thermometer-half">℃<br> min</i></div>
               <div class="🎯 table-head"><i class="bi bi-thermometer-half">℃<br> max</i></div>
               <div class="🎯 table-head"><i class="bi bi-droplet-half">%<br> avg</i></div>
               <div class="🎯 table-head"><i class="bi bi-droplet-half">%<br> min</i></div>
               <div class="🎯 table-head"><i class="bi bi-droplet-half">%<br> max</i></div>
            </div>
            <?php
            foreach ($sensors as $i => $sensor):?>

               <div style="display: none" id="SensorReadingOf<?php echo htmlspecialchars($sensor->id); ?>"
                    data-sensorReading="<?php echo htmlspecialchars(json_encode($sensorReadingsBySensorId[$sensor->id])); ?>">
               </div>

               <div class="🎯 collapse-and-change-icon zebra-1" style="display: grid; grid-auto-rows: auto;
                           grid-template-columns: 30px 5fr 6fr 2fr 2fr 2fr 2fr 2fr 2fr 2fr;
                           --ypos: center; --xpos: start;" data-toggle="collapse" data-target="<?php echo "#collapseSensorInfoIdx_" . $i?>" aria-expanded="false">
                     <span style="padding-left: 4px" type="button">
                        <i class="bi bi-caret-right"></i>
                     </span>
                     <?php
                        $temps = array_map(function ($obj) {
                           return $obj->temp;
                        }, $sensorReadingsBySensorId[$sensor->id]);
                        $tempAvg = sizeof($temps) === 0 ? 'NULL' : number_format(array_sum($temps) / sizeof($temps), 1);
                        $tempMax = sizeof($temps) === 0 ? 'NULL' : number_format(max($temps), 1);
                        $tempMix = sizeof($temps) === 0 ? 'NULL' : number_format(min($temps), 1);

                        $hums = array_map(function ($obj) {
                           return $obj->relHum;
                        }, $sensorReadingsBySensorId[$sensor->id]);
                        $t = $sensorReadingsBySensorId[$sensor->id];
                        $humAvg = sizeof($hums) === 0 ? 'NULL' : number_format(array_sum($hums) / sizeof($hums), 1);
                        $humMax = sizeof($hums) === 0 ? 'NULL' : number_format(max($hums), 1);
                        $humMix = sizeof($hums) === 0 ? 'NULL' : number_format(min($hums), 1);
                     ?>
                     <div><?php echo htmlspecialchars($sensor->name) ?></div>
                     <div>
                        <div style="height: 1em; margin-bottom: 6px; overflow: hidden; font-size: 13px; color: <?php echo $lastReadingsView[$sensor->id]->color ?>">
                           <?php echo htmlspecialchars($lastReadingsView[$sensor->id]->dateRecorded); ?>
                        </div>
                        <div style="--xpos: center; --ypos: center; display: grid; grid-auto-rows: auto;
                        grid-template-columns: auto auto;">
                           <div>
                              <i class="bi bi-thermometer-half"></i><?php echo $lastReadingsView[$sensor->id]->temp; ?>
                           </div>
                           <div>
                              <i class="bi bi-droplet-half"></i><?php echo $lastReadingsView[$sensor->id]->relHum; ?>
                           </div>
                        </div>
                     </div>
                     <div><?php echo sizeof($sensorReadingOutOfBounds[$sensor->id]) ?></div>
                     <div><?php echo $tempAvg ?></div>
                     <div><?php echo $tempMix ?></div>
                     <div><?php echo $tempMax ?></div>
                     <div><?php echo $humAvg ?></div>
                     <div><?php echo $humMix ?></div>
                     <div><?php echo $humMax ?></div>
               </div>

               <div class="collapse zebra-2" id="<?php echo "collapseSensorInfoIdx_" . $i?>" style="border-top: 1px dotted black; padding: 0.4em 30px 0.4em 30px">

                  <div class="h3">Alerts</div>
                  <div style="margin-bottom: 1.4em">
                     <p>
                        <?php
                        if (sizeof($sensorReadingOutOfBounds[$sensor->id]) === 0) {
                           echo 'All good';
                        }?>
                     </p>
                     <?php if (sizeof($sensorReadingOutOfBounds[$sensor->id]) !== 0) { ?>
                        <table style="background: var(--light_grey_03)">
                           <thead class="collapse-and-change-icon" data-toggle="collapse" data-target="<?php echo "#collapseSensorAlertsIdx_" . $i?>" aria-expanded="false">
                              <tr>
                                 <th type="button">
                                    <i class="bi bi-caret-right"></i>
                                 </th>
                                 <th>Timestamp</th>
                                 <th>Duration (min)</th>
                                 <th>Readings count</th>
                                 <th>Deviation Temp (℃)</th>
                                 <th>Deviation Hum (%)</th>
                              </tr>
                           </thead>
                           <tbody class="collapse" id="<?php echo "collapseSensorAlertsIdx_" . $i?>">
                        <?php
                           $arr = $sensorReadingOutOfBounds[$sensor->id];
                           foreach ($arr as $j => $item) {
                              ?>
                              <tr>
                                 <td><?php echo $j + 1?></td>
                                 <td><?php echo $item->beforeDate?></td>
                                 <td><?php echo $item->duration?></td>
                                 <td><?php echo $item->count?></td>
                                 <td><?php echo $item->temp?></td>
                                 <td><?php echo $item->hum?></td>
                              </tr>
                           <?php } ?>
                           </tbody>
                        </table>
                     <?php } ?>
                  </div>

                  <h3 class="h3">Actions</h3>
                  <div style="margin-bottom: 0.8em">
                     <div style="margin-bottom: 0.4em">
                        <span>Sensor interface:</span>
                        <a href="<?php echo htmlspecialchars($sensor->ip) ?>" target="_blank"><?php echo htmlspecialchars($sensor->ip) ?></a>
                     </div>
                     <button style="margin-bottom: 0.4em" class="button"
                             data-filename='<?php echo htmlspecialchars($sensor->name . '_' . $dateFrom . '_' . $dateTo . '.csv'); ?>'
                             data-sensorId='<?php echo $sensor->id;?>'
                             onclick="ExportCSV(this)">Export
                     </button>
                     <?php if ($sensor->isPortable) { ?>
                        <div>
                           <div>
                              <span>Upload column order</span>
                              <input type="text" id="csvParseExpressionSensorId_<?php echo $sensor->id;?>" value="date;temp;relHum">
                           </div>
                           <div>
                              <label class="button">
                                 <span>Upload</span>
                                 <input style="display: none" type="file" id="csvFileSensorId_<?php echo $sensor->id;?>" data-sensorId="<?php echo $sensor->id;?>" class="csvFile" accept=".csv">
                              </label>
                           </div>
                        </div>
                     <?php } ?>
                  </div>

                  <h3 class="h3">Details</h3>
                  <form action="" method="post">
                     <div style="display: grid; grid-template-columns: 1fr 1fr; grid-gap: 0.4em 3em; padding-bottom: 0.8em">
                        <?php echo SensorCrudPartialCreateEdit::GetHtml($sensor) ?>
                     </div>
                     <div style="padding-top: 0.4em">
                        <div>
                           <span>Auth</span>
                           <input type="password" name="auth">
                           <button class="button" type="submit" name="formType" value="edit">Save</button>
                           <button class="button button-danger" type="submit" name="formType" value="delete">Delete</button>
                        </div>
                     </div>
                  </form>

               </div>
            <?php endforeach; ?>
         </div>

         <div style="margin-top: 2em;" class="h2">Graph</div>
         <div class="h4">Settings</div>
         <div style="display: grid; grid-template-columns: 6fr 6fr">

            <div>
               <table style="min-width: 320px; width: 75%">
                  <thead>
                     <tr>
                        <th>Name</th>
                        <th>Color</th>
                        <th><i class="bi bi-thermometer-half"></i></th>
                        <th><i class="bi bi-droplet-half"></i></th>
                     </tr>
                  </thead>
                  <tbody id="sensorDrawingSettings">
                     <?php
                     foreach ($sensors as $i => $sensor):
                        ?>
                     <tr class="zebra-3">
                        <td style="display: none" class="sensorId"><?php echo $sensor->id?></td>
                        <td class="sensorName"><?php echo htmlspecialchars($sensor->name)?></td>
                        <td style="display: flex">
                           <input class="colorSelectTemp" type="color" style="width: 100%; padding: 0" value="<?php echo $colors[$i]; ?>">
                           <input class="colorSelectHum" type="color" style="width: 100%; padding: 0" value="<?php echo $colors[$i + sizeof($sensors)]; ?>">
                        </td>
                        <td><input class="tempSelect" type="checkbox"></td>
                        <td><input class="relHumSelect" type="checkbox"></td>
                     </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>

            <div style="display: flex; flex-direction:column; justify-content: space-between;">
               <div>
                  <div class="graphOptions">
                     <p>Interval</p>
                     <select id="graphOptionsIntervalSelect" class="graphOptionsSelect">
                        <option value="15" selected>15 min</option>
                        <option value="30">30 min</option>
                        <option value="120">2 hour</option>
                        <option value="360">6 hour</option>
                        <option value="1440">1 day</option>
                     </select>
                  </div>
                  <div class="graphOptions">
                     <p>Interval Strategy</p>
                     <select id="graphOptionsStrategySelect" class="graphOptionsSelect">
                        <option value="median">Take Median</option>
                        <option value="average">Take Average</option>
                        <option value="deviation">Take Deviation</option>
                     </select>
                  </div>
                  <div class="graphOptions">
                     <p>No Value As 0</p>
                     <div class="🎯" style="--xpos: end">
                        <input id="graphOptionsLoudNoValue" type="checkbox">
                     </div>
                  </div>
               </div>

               <div style="display: flex; justify-content: end;">
                  <button id="ChartDrawButton" style="width: 9em" class="button">Draw</button>
               </div>
            </div>

         </div>
         <div style="margin-top: 2em;" class="h4">Result</div>
         <div style="height: 540px">
            <p id="chartErr"></p>
            <div id="chartDiv">
               <button id="saveImg" type="button" class="button">Save Image</button>
               <div id="chart" style="width: 100%; height: 500px;"></div>
               <div style="display: none" id="chartAsPictureDiv"></div>
            </div>
         </div>
      </div>
   </div>
   <?php echo FooterPartial::GetHtml() ?>

   <!-- Modal -->
   <div class="modal fade" id="JsonModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
         <div class="modal-content modal-dialog" role="document">
            <div class="modal-header" style="display: block">
               <h5 class="modal-title">Confirm data</h5>
               <span id="modalMsg"></span>
               <button style="position: absolute; top: 16px; right: 16px;" type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
               </button>
            </div>
            <div class="modal-body" style="height: 40em; overflow-y: auto">
               <pre id="csvDataDisplay">

               </pre>
            </div>
            <form action="" method="post">
               <div style="padding: .75rem; border-top: 1px solid #dee2e6;">
                  <input id="csvDataFormField" style="display: none" name="jsonData" value="">
                  <input id="csvSensorId" style="display: none" name="csvSensorId" value="">
                  <span>Auth</span>
                  <input type="password" name="csvAuth">
                  <button class="button" type="submit">Submit</button>
               </div>
            </form>
         </div>
      </div>
   </div>
   <!-- end Modal -->

</body>

<script type="text/javascript">

   HandleCollapseSymbolChange();
   FancyDatePicker(['#dateTo', '#absoluteDateFrom']);
   document.getElementById('ChartDrawButton').onclick = () => {
       google.charts.load("current", { 'packages':["corechart", "line"]});
       google.charts.setOnLoadCallback(drawChart);
   };
   dayjs.extend(window.dayjs_plugin_customParseFormat);
   document.getElementById('saveImg').onclick = SaveChartImg;
   $('.csvFile').change( function () { HandlePortableSensorDataCsvUpload(this.getAttribute('data-sensorId'))});


   function HandlePortableSensorDataCsvUpload(id) {
       let reader = new FileReader();
       reader.onload = function (event) {
           let csv = event.target.result;
           let delimiter = ';';
           let headers = document.getElementById('csvParseExpressionSensorId_' + id).value.split(delimiter);
           let arr, skipCount;
           try {
               [arr, skipCount] = csvToJsonArray(csv, delimiter, headers);
           } catch (e) {
               console.error(e);
               return false;
           }
           document.getElementById('csvDataDisplay').innerText = JSON.stringify(arr, null, 2);
           document.getElementById('csvDataDisplay').style.display = 'block';
           document.getElementById('csvDataFormField').value = JSON.stringify(arr);
           document.getElementById('csvSensorId').value = id;
           document.getElementById('modalMsg').innerText = `Total data rows count: ${arr.length + skipCount}, skipped ${skipCount}`;
           $('#JsonModal').modal('show');
       }

       let csvFile = document.getElementById('csvFileSensorId_' + id);
       let input = csvFile.files[0];
       reader.readAsText(input);
   }

   document.getElementById('btnLoad').onclick = function () {
       let resultTo = document.getElementById('submitDateTo');
       resultTo.value = document.getElementById('dateTo').value;

       let type = document.querySelector('input[name="dateFromType"]:checked').value;
       let resultDateFrom = document.getElementById('submitDateFrom');
       switch (type){
           case 'absolute':
               resultDateFrom.value = document.getElementById('absoluteDateFrom').value;
               break;
           case 'relative':
               resultDateFrom.value = '-' + document.getElementById('relativeDateFrom').value;
               break;
           default:
               console.error('Unknown type:', type);
               break;
       }

       document.formJsSubmit.submit();
   }

   function getEOL() {
       let aPlatform = navigator.platform.toLowerCase();
       if (aPlatform.indexOf('win') !== -1) return '\r\n'
       if (aPlatform.indexOf('mac') !== -1) return '\r'
       return '\n'
   }

   function csvToJsonArray(csv, delimiter, headers) {
       let allowedHeaders = ['date', 'temp', 'relHum'];
       if (! allowedHeaders.every(x => headers.includes(x))) {
           throw `Missing field, fields [${allowedHeaders.join(',')}] must be defined in [${headers.join(',')}]!`;
       }
       let eol = getEOL();
       // skip 1. row (the header)
       let rows = csv.slice(csv.indexOf(eol) + eol.length).split(eol);
       if (rows.length === 0) { throw 'No rows!'; }
       rows.pop(); // remove newline

       let result = [];
       let skipCount = 0;
       let nRowNumber = 0;
       for (let row of rows) {
           nRowNumber++;
           let obj = {};
           let values = row.split(delimiter);
           if (values.length !== headers.length) { throw `Row: ${nRowNumber}. Header and data column count mismatch!`; }
           if (values.length < 3) { throw `Row: ${nRowNumber}. Row has less than 3 columns. Row value: ${row} parsed as [${values.join(',')}]!`; }
           for (let i = 0; i < headers.length; i++) {
               let header = headers[i];
               let value = values[i];

               if (header === '') {
                   continue;
               }

               if (value.substr(0, 5) === 'Error' || value === '') {
                   skipCount++;
                   break;
               }
               switch (header) {
                   case 'date':
                       // format from sensor
                       let date = dayjs(value, 'DD/MM/YYYY HH:mm:ss');
                       if (! date.isValid()) {
                           // format from page export
                           date = dayjs(value, 'DD/MM/YYYY HH:mm');
                       }
                       if (! date.isValid()) {
                           throw `Row: ${nRowNumber}, Invalid date format! Must be DD/MM/YYYY HH:mm:ss or DD/MM/YYYY HH:mm!`
                       }
                       obj[header] = date.format('DD-MM-YYYY HH:mm')
                       break;
                   case 'relHum':
                   case 'temp':
                       let fValue = parseFloat(value);
                       if (isNaN(fValue)) throw `Row: ${nRowNumber}. Cannot parse number ${value}`;
                       obj[header] = parseFloat(fValue.toFixed(1));
                       break;
                   default:
                       throw 'Unexpected header ' + header + '!';
               }
               if (i === headers.length - 1) {
                   result.push(obj);
               }
           }
       }
       return [result, skipCount];
   }

   function drawChart() {
       let chartEle = document.getElementById("chart");
       let data = new google.visualization.DataTable();

       // let t0 = performance.now();

       let step = parseInt(document.getElementById('graphOptionsIntervalSelect').value);
       let strategy = document.getElementById('graphOptionsStrategySelect').value;
       // undefined value is ignored in graph
       let graphDefaultValue = document.getElementById('graphOptionsLoudNoValue').checked ? 0 : undefined;

       let dataFromPHP = document.getElementById('data');
       let dateFrom = dayjs(dataFromPHP.getAttribute('data-dateFrom'), 'DD-MM-YYYY, HH:mm');
       let dateTo = dayjs(dataFromPHP.getAttribute('data-dateTo'), 'DD-MM-YYYY, HH:mm');

       let sensors = [];
       for (let sensor of JSON.parse(dataFromPHP.getAttribute('data-sensors'))) {
           sensors.push(new Sensor(
               sensor.id,
               sensor.name,
               GetSensorReading(sensor.id),
               sensor.maxTemp,
               sensor.minTemp,
               sensor.maxRelHum,
               sensor.minRelHum,
               GetDrawingSettings(sensor.id)
           ));
       }

       if (true) {
           // Google charts can handle it built in so this is actually unnecessary, but it does look nicer
          let chartDiv = document.getElementById('chartDiv');
          let chartErr = document.getElementById('chartErr');
          if (sensors.map(x => x.sensorSettings).every(x => !x.isTemp && !x.isRelHum)) {
              chartDiv.style.display = 'none';
              chartErr.innerText = 'No sensor selected';
              return;
          }
          chartDiv.style.display = 'block';
          chartErr.innerText = '';
       }

       let xAxisTimesToSensors = [];
       for (let i = 0; ; i++) {
           let nextDate = dateFrom.add(step * i, 'minutes');
           if (nextDate >= dateTo) break;
           xAxisTimesToSensors.push({
               'date': nextDate,
               'data': []
           });
       }


       for (let sensor of sensors) {
           let tempRangeAvg = (sensor.maxTemp + sensor.minTemp) / 2;
           let humRangeAvg = (sensor.maxRelHum + sensor.minRelHum) / 2;

           let low = 0;
           for (let i = 0; i < xAxisTimesToSensors.length - 1; i++) {
               if (! sensor.sensorSettings.isTemp && ! sensor.sensorSettings.isRelHum) {
                   xAxisTimesToSensors[i].data.push(null);
                   continue;
               }
               let inbetween = {
                   'temps': null,
                   'hums': null
               }
               let sensorValue = {
                   'temp': null,
                   'relHum': null,
               };

               let before = xAxisTimesToSensors[i].date;
               let after = xAxisTimesToSensors[i + 1].date;

               let tmp = FilterSortedArrayValuesBetweenDates(sensor.sensorReadings, before, after, low);
               let currentRows = tmp['result'];
               low = tmp['low'];

               if (sensor.sensorSettings.isTemp) {
                   inbetween.temps = currentRows.map(x => x.temp);
               }
               if (sensor.sensorSettings.isRelHum) {
                   inbetween.hums = currentRows.map(x => x.relHum);
               }


               switch (strategy) {
                   case 'median':
                       if (sensor.sensorSettings.isTemp) {
                           let valOrUndefined = inbetween.temps[Math.floor(inbetween.temps.length / 2)];
                           sensorValue.temp = valOrUndefined === undefined ? graphDefaultValue : valOrUndefined;
                       }
                       if (sensor.sensorSettings.isRelHum) {
                           let valOrUndefined = inbetween.hums[Math.floor(inbetween.hums.length / 2)];
                           sensorValue.relHum = valOrUndefined === undefined ? graphDefaultValue : valOrUndefined;
                       }
                       break;
                   case 'average':
                       if (sensor.sensorSettings.isTemp) {
                           sensorValue.temp = inbetween.temps.length === 0 ? graphDefaultValue : inbetween.temps.reduce((a, b) => a + b) / inbetween.temps.length;
                       }
                       if (sensor.sensorSettings.isRelHum) {
                           sensorValue.relHum = inbetween.hums.length === 0 ? graphDefaultValue : inbetween.hums.reduce((a, b) => a + b) / inbetween.hums.length;
                       }
                       break;
                   case 'deviation':
                       if (sensor.sensorSettings.isTemp) {
                           sensorValue.temp = inbetween.temps.length === 0 ? graphDefaultValue :
                               inbetween.temps.sort(
                                   (a,b) => Math.abs(a-tempRangeAvg) - Math.abs(b-tempRangeAvg)
                               )[inbetween.temps.length - 1];
                       }
                       if (sensor.sensorSettings.isRelHum) {
                           sensorValue.relHum = inbetween.hums.length === 0 ? graphDefaultValue :
                               inbetween.hums.sort(
                                   (a,b) => Math.abs(a-humRangeAvg) - Math.abs(b-humRangeAvg)
                               )[inbetween.hums.length - 1];
                       }
                       break;
                   default:
                       console.error('Unknown value!');
                       return;
               }
               xAxisTimesToSensors[i].data.push(sensorValue);
           }
       }
       xAxisTimesToSensors.pop();

       data.addColumn('string', 'date');
       for (let sensor of sensors) {
           if (sensor.sensorSettings.isTemp) {
               data.addColumn('number', sensor.name + ' temperature (℃)');
               data.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
           }
           if (sensor.sensorSettings.isRelHum) {
               data.addColumn('number', sensor.name + ' relative humidity (%)');
               data.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
           }
       }


       let rows = [];
       for (let xAxisTime of xAxisTimesToSensors) {
           let row = [];
           row.push(xAxisTime.date.add(step / 2, 'minutes').format('DD.MM'))
           for (let i = 0; i < sensors.length; i++) {
               let sensor = sensors[i];
               const addTooltipFunc = function (valueAndSymbol) {
                   return  `<div style="height: 50px; width: 14em">
                              <p><b>${xAxisTime.date.format('HH:mm, DD.MM.YYYY')}</b></p>
                              <p style="margin-top: 6px">${encodeURI(sensor.name)}: <b>${valueAndSymbol}</b></p>
                           </div>`;
               };
               let tmp = xAxisTime.data[i] ?? null;
               if (sensor.sensorSettings.isTemp) {
                   row.push(tmp.temp);
                   row.push(addTooltipFunc(tmp.temp + '℃'));
               }
               if (sensor.sensorSettings.isRelHum) {
                   row.push(tmp.relHum);
                   row.push(addTooltipFunc(tmp.relHum + '%'));
               }
           }
           rows.push(row);
       }
       data.addRows(rows);

       let colors = [];
       for (let sensor of sensors) {
           if (sensor.sensorSettings.isTemp) {
               colors.push(sensor.sensorSettings.colorTemp);
           }
           if (sensor.sensorSettings.isRelHum) {
               colors.push(sensor.sensorSettings.colorRelHum)
           }
       }

       let options = {
           title: dateFrom.format('DD-MM-YYYY HH:mm') + ' - ' + dateTo.format('DD-MM-YYYY HH:mm'),
           fontSize: 13,
           vAxis: {
               // is it possible to add a callback? I only found ticks, but they mess automatic zooming
               // ticks: [{v:16, f:'banana'}, {v:18, f:'apple'}, {v:20, f:'mango'}, {v:22, f:'orange'}, {v:24, f:'apricot'}]
               title: 'Temperature (℃) and Relative Humidity (%)',
           },
           tooltip: {isHtml: true},
           colors: colors,
           legend: { position: 'right' },
       };
       let chart = new google.visualization.LineChart(chartEle);

       google.visualization.events.addListener(chart, 'ready', function() {
           document.getElementById('chartAsPictureDiv').innerHTML = '<img id="chartAsPictureImg" src="' + chart.getImageURI() + '">';
       })

       chart.draw(data, options);
   }

   function SaveChartImg() {
       let base64 = document.getElementById('chartAsPictureImg').src;
       let dateFrom = document.getElementById('data').getAttribute('data-dateFrom');
       let dateTo = document.getElementById('data').getAttribute('data-dateTo');
       let fileName = 'tempsens ' + dateFrom  + '-' + dateTo;
       ExportBase(base64, fileName)
   }

   function ExportBase(encodedUri, filename) {
       let link = document.createElement("a");
       document.body.appendChild(link); // for FF
       link.setAttribute("href", encodedUri);
       link.setAttribute("download", filename)
       link.click();
   }

   function FilterSortedArrayValuesBetweenDates(sensorReadings, before, after, low) {
       if (false) {
           // this is the readable version, but much slower duo to searching the entire array
           return sensorReadings.filter(x => before <= x.date && after > x.date);
       }

       let result = [];
       while (low < sensorReadings.length) {
           let obj = sensorReadings[low];
           if (after <= obj.date) {
               break;
           }
           if (before <= obj.date) {
               result.push(obj);
           }
           low++;
       }
       return {
           'result': result,
           'low': low
       };
   }

   function GetDrawingSettings(_sensorId) {
       let table = document.getElementById('sensorDrawingSettings');
       for (let row of table.children) {
           let sensorId = row.querySelector('.sensorId').innerHTML;
           if (sensorId !== _sensorId) continue;
           let colorTemp = row.querySelector('.colorSelectTemp').value;
           let colorRelHum = row.querySelector('.colorSelectHum').value;
           let isTemp = row.querySelector('.tempSelect').checked;
           let isRelHum = row.querySelector('.relHumSelect').checked;

           return new SensorSettings(colorTemp, colorRelHum, isTemp, isRelHum);
       }
       return null;
   }

   function GetSensorReading(sensorId) {
       let sensorReadings = Object.entries(JSON.parse(
           document.getElementById('SensorReadingOf' + sensorId).getAttribute("data-sensorReading")
       ));
       let result = [];
       for (let i = 0; i < sensorReadings.length; i++) {
           let tmp = sensorReadings[i][1];
           result.push(new SensorReading(
               dayjs(tmp.date, 'DD/MM/YYYY HH:mm'),
               parseFloat(tmp.temp),
               parseFloat(tmp.relHum)));
       }
       return result;
   }

   class SensorSettings {
       constructor(colorTemp, colorRelHum, isTemp, isRelHum) {
           this.colorTemp = colorTemp;
           this.colorRelHum = colorRelHum;
           this.isTemp = isTemp;
           this.isRelHum = isRelHum;
       }
   }

   class Sensor {
       constructor(id, name, sensorReadings, maxTemp, minTemp, maxRelHum, minRelHum, sensorSettings) {
           this.id = id;
           this.name = name;
           this.sensorReadings = sensorReadings;
           this.maxTemp = maxTemp;
           this.minTemp = minTemp;
           this.maxRelHum = maxRelHum;
           this.minRelHum = minRelHum;
           this.sensorSettings = sensorSettings;
       }
   }

   class SensorReading {
       constructor(date, temp, relHum) {
           this.date = date;
           this.temp = temp;
           this.relHum = relHum;
       }
   }

   function FancyDatePicker(arr) {
       for (let item of arr) {
           $('' + item).datepicker({
               dateFormat: "dd-mm-yy"
           });
       }
   }

   function handleRadioClick(dateFromType) {
       const absoluteDateFromInput = document.getElementById("absoluteDateFrom");
       const relativeDateFromInput = document.getElementById("relativeDateFrom");

      const relative = "relative";
      const absolute = "absolute";
      if (! dateFromType.value in [relative, absolute]) {
         console.error("Unexpected value: " + dateFromType.value)
         return;
      }
      if (absoluteDateFromInput.disabled && dateFromType.value !== absolute ||
          relativeDateFromInput.disabled && dateFromType.value !== relative) {
          return;
      }
      absoluteDateFromInput.disabled = dateFromType.value !== absolute;
      relativeDateFromInput.disabled = dateFromType.value !== relative;
   }

   function HandleCollapseSymbolChange() {
      $('.collapse-and-change-icon').click(function() {
          let targetElement = document.querySelector(this.getAttribute("data-target"))
          if (! targetElement.className.includes("collapsing")) {
            $(this).find('i').toggleClass('bi bi-caret-down bi bi-caret-right');
          }
      });
   }


   function ExportCSV(ctx) {
       let sensorId = ctx.getAttribute("data-sensorId");
       let filename = ctx.getAttribute("data-filename");
       let sensorReadings = GetSensorReading(sensorId);
       let data = [];
       data.push(['Timestamp', 'Temperature (*C)', 'Relative Humidity (%)'])
       for (let sensorReading of sensorReadings) {
           let row = [sensorReading.date.format("DD/MM/YYYY HH:mm"), sensorReading.temp, sensorReading.relHum];
           data.push(row)
       }
       let eol = getEOL();
       let csvContent = "data:text/csv;charset=utf-8," + data.map(e => e.join(";")).join(eol);
       let encodedUri = encodeURI(csvContent);

       ExportBase(encodedUri, filename)
   }



</script>
</html>
