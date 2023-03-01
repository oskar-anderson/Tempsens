<?php
/** @noinspection DuplicatedCode */

use App\dto\IndexViewModel;
use App\util\Helper;
use App\view\partial\SensorCrudPartialCreateEdit;

/* @var IndexViewModel $model */

$dateFromType = $model->input->dateFromType;
$dateFrom = $model->input->dateFrom;
$dateTo = $model->input->dateTo;
$periods = $model->periods;

$sensors = $model->sensors;
$lastReadingsView = $model->lastReadingsView;
$sensorReadingOutOfBounds = $model->sensorAlertsMinMax;
$sensorReadingsBySensorId = $model->sensorReadingsBySensorId;
$colors = $model->colors;

?>

<!DOCTYPE HTML>
<html lang="en">
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <meta name="robots" content="index,nofollow"/>
   <meta name="keywords" content="Sensors"/>
   <meta name="description" content="Sensors"/>

   <title>Sensor</title>
   <!-- IDE might complain about links not resolving, but they are resolved relative to the viewController dir not the view dir -->
   <link rel="icon" href="../static/gfx/favicon3.png" type="image/png"/>

   <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css"/>
   <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"/>
   <link rel="stylesheet" type="text/css" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css"/>

   <link rel="stylesheet" type="text/css" media="screen" href="../static/css/reset.css"/>
   <link rel="stylesheet" type="text/css" media="screen" href="../static/css/main-layout.css"/>

   <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
   <script type="text/javascript" src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
   <script type="text/javascript" src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
   <script type="text/javascript" src="https://mozilla.github.io/nunjucks/files/nunjucks.js"></script>
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

      .zebra-overview, .zebra-graph-settings {
         background: var(--light_grey_02);
      }

      .zebra-overview:nth-of-type(4n), .zebra-overview:nth-of-type(4n - 3), .zebra-graph-settings:nth-of-type(2n) {
         background: var(--light_grey_01);
      }

   </style>
   <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

   <!-- date parsing -->
   <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/dayjs@1.10.4/dayjs.min.js"></script>
   <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/dayjs@1.10.4/plugin/customParseFormat.js"></script>

</head>
<body style="min-height: 100vh; min-width: 900px;">
<div style="display: grid; grid-template-rows: 1fr auto;">

<div>
   <div>
      <div class="templating-root m-2" style="display: none">
         {% if errors.length !== 0 %}
         <div class="alert alert-danger alert-dismissible fade show" style="margin-bottom: 2em" role="alert">
            <h4>Errors: </h4>
            <ol>
               {% for error in errors %}
                  <li>{{ error }}</li>
               {% endfor %}
            </ol>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span>
            </button>
         </div>
         {% endif %}
      </div>

      <div class="🎯">


         <div style="width: min(90%, 50em); display: grid; grid-template-columns: 5fr 2fr 5fr; margin-top: 2em;">
            <div>

               <div>
                  <label class="dateFromOption" for="typeAbsolute">
                     <input type="radio" onclick="handleRadioClick(this);" name="dateFromType" id="typeAbsolute"
                        <?= $dateFromType === 'absolute' ? 'checked' : '' ?>
                            value="absolute"/>
                     <span style="margin-left: 20px">From Date</span>
                     <span class="child-input 🎯" style="--xpos: end;">
                        <input name="absoluteDateFrom" type="text" id="absoluteDateFrom"
                               value="<?= $dateFrom ?>" <?= $dateFromType !== 'absolute' ? 'disabled' : ''; ?> />
                     </span>
                  </label>
               </div>

               <div>
                  <label class="dateFromOption" for="typeRelative">
                     <input type="radio" onclick="handleRadioClick(this);" name="dateFromType" id="typeRelative"
                        <?= $dateFromType === 'relative' ? "checked" : ''?>
                            value="relative"/>
                     <span style="margin-left: 20px">From Relative</span>
                     <span class="child-input 🎯" style="--xpos: end;">
                        <select name="relativeDateFrom" id="relativeDateFrom" class="active"
                           <?= $dateFromType !== 'relative' ? 'disabled' : ''; ?>
                        >
                           <?php foreach ($periods as $item) { ?>
                              <option value='<?= $item->value ?>' <?= $item->isSelected ? "selected" : '' ?>>-<?= $item->name ?></option>
                           <?php } ?>

                        </select>
                     </span>
                  </label>
               </div>
            </div>

            <div class="🎯" style="--xpos: center; --ypos: center">
               <button id="btnLoad" class="button">Load</button>
            </div>
            <div class="🎯" style="height: var(--row_height);">
               <label for="dateTo" style="width: 100%;">To Date</label>
               <input type="text" name="dateTo" id="dateTo" value="<?= $dateTo; ?>"/>
            </div>


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
         </div>

         <div class="collapse" id="collapseHelp">
            <div style="margin-bottom: 1em;">

               <p>
                  Click on sensor to:
               </p>
               <ul style="margin-bottom: 12px">
                  <li>Show alert info</li>
                  <li>Export data</li>
                  <li>Upload data (only for portable sensors)</li>
                  <li>Edit sensor fields (requires password, will not affect sensor's own configuration)</li>
               </ul>
               <p>
                  Exported and imported data is semicolon delimited CSV.
                  It is worth noting that Excel reads files using the computer regional settings and will
                  likely display semicolon delimited files incorrectly.
                  Excel uses delimiter from user PC
                  <code>Control Panel\Clock and Region -> Region -> Additional settings -> List separator</code>
                  (this should be changed from <code>,</code> to <code>;</code>). The exported data format
                  itself is correct.
               </p>

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
                  Alarm duration is approximate depending on sensor reading interval.
               </p>
            </div>


            <div style="margin-bottom: 1em; background: var(--light_grey_02);">
               <div style="display: grid; grid-auto-rows: auto; grid-template-columns: 30px 10em; width: min-content;" class="collapse-and-change-icon" data-toggle="collapse" data-target="#collapseSensorCreate" aria-expanded="false">
                  <div style="padding-left: 4px" type="button">
                     <i class="bi bi-caret-right"></i>
                  </div>
                  <div>Create new sensor</div>
               </div>
               <form action="" autocomplete="off" style="padding: 0.4em 30px 0.4em 30px; margin-top: 1.2em" class="collapse" id="collapseSensorCreate">
                  <div style="display: grid; grid-template-columns: 1fr 1fr; grid-gap: 0.4em 3em; padding-bottom: 0.8em">
                     <?= SensorCrudPartialCreateEdit::GetHtml(null) ?>
                  </div>
                  <div>
                     <span>Auth</span>
                     <input type="password" name="auth" value="">
                     <button id="collapseSensorCreateSubmitBtn" class="button" type="button">Create</button>
                  </div>
               </form>
            </div>

         </div>
      </div>

      <div class="wrapper zebra">
         <div
            style="--ypos: center;--xpos: start; height: 2em; display: grid; grid-auto-rows: auto; grid-template-columns: 30px 5fr 6fr 2fr 2fr 2fr 2fr 2fr 2fr 2fr;">
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

            <div class="🎯 collapse-and-change-icon zebra-overview" style="display: grid; grid-auto-rows: auto;
                           grid-template-columns: 30px 5fr 6fr 2fr 2fr 2fr 2fr 2fr 2fr 2fr;
                           --ypos: center; --xpos: start;" data-toggle="collapse"
                 data-target="<?= "#collapseSensorInfoIdx_" . $i ?>" aria-expanded="false">
               <span style="padding-left: 4px" type="button">
                  <i class="bi bi-caret-right"></i>
               </span>
               <?php
               $temps = array_map(fn($obj) => $obj->getTemp(), $sensorReadingsBySensorId[$sensor->id]);
               $tempAvg = sizeof($temps) === 0 ? 'NULL' : number_format(array_sum($temps) / sizeof($temps), 1);
               $tempMax = sizeof($temps) === 0 ? 'NULL' : number_format(max($temps), 1);
               $tempMix = sizeof($temps) === 0 ? 'NULL' : number_format(min($temps), 1);

               $hums = array_map(fn($obj) => $obj->getRelHum(), $sensorReadingsBySensorId[$sensor->id]);
               $humAvg = sizeof($hums) === 0 ? 'NULL' : number_format(array_sum($hums) / sizeof($hums), 1);
               $humMax = sizeof($hums) === 0 ? 'NULL' : number_format(max($hums), 1);
               $humMix = sizeof($hums) === 0 ? 'NULL' : number_format(min($hums), 1);
               ?>
               <div><?= htmlspecialchars($sensor->name) ?></div>
               <div>
                  <div
                     style="height: 1em; margin-bottom: 6px; overflow: hidden; font-size: 13px; color: <?php echo $lastReadingsView[$sensor->id]->color ?>">
                     <?= htmlspecialchars($lastReadingsView[$sensor->id]->dateRecorded); ?>
                  </div>
                  <div style="--xpos: center; --ypos: center; display: grid; grid-auto-rows: auto;
                        grid-template-columns: auto auto;">
                     <div>
                        <i class="bi bi-thermometer-half"></i><?= $lastReadingsView[$sensor->id]->temp; ?>
                     </div>
                     <div>
                        <i class="bi bi-droplet-half"></i><?= $lastReadingsView[$sensor->id]->relHum; ?>
                     </div>
                  </div>
               </div>
               <div>
                  <?= sizeof(array_filter(
                     $sensorReadingOutOfBounds[$sensor->id],
                     fn($x) => $x->temp < $sensor->minTemp || $x->temp > $sensor->maxTemp)
                  )
                  . "|" .
                  sizeof(array_filter(
                     $sensorReadingOutOfBounds[$sensor->id],
                     fn($x) => $x->hum < $sensor->minRelHum || $x->hum > $sensor->maxRelHum)
                  )
                  ?>
               </div>
               <div><?= $tempAvg ?></div>
               <div><?= $tempMix ?></div>
               <div><?= $tempMax ?></div>
               <div><?= $humAvg ?></div>
               <div><?= $humMix ?></div>
               <div><?= $humMax ?></div>
            </div>

            <div class="collapse zebra-overview" id="<?= "collapseSensorInfoIdx_" . $i ?>"
                 style="border-top: 1px dotted black; padding: 0.4em 20px">
               <nav>
                  <div class="nav nav-tabs">
                     <button class="nav-link active button" data-toggle="tab"
                             data-target="#nav-alerts-<?php echo $i ?>">Alerts
                     </button>
                     <button class="nav-link button" data-toggle="tab"
                             data-target="#nav-details-<?php echo $i ?>">Details
                     </button>
                     <button class="nav-link button" data-toggle="tab"
                             data-target="#nav-general-<?php echo $i ?>">General
                     </button>
                     <button class="nav-link button" data-toggle="tab"
                             data-target="#nav-export-<?php echo $i ?>">Export
                     </button>
                     <button class="nav-link button" data-toggle="tab"
                             data-target="#nav-import-<?php echo $i ?>">Import
                     </button>
                  </div>
               </nav>
               <div class="tab-content">

                  <!--  ALERTS  -->
                  <div class="tab-pane active" id="nav-alerts-<?php echo $i ?>" role="tabpanel">
                     <h3 class="h3">Alerts</h3>
                     <div style="margin-bottom: 1.4em">
                        <p>Number of alerts: <?php echo sizeof($sensorReadingOutOfBounds[$sensor->id]); ?></p>
                        <?php if (sizeof($sensorReadingOutOfBounds[$sensor->id]) !== 0) { ?>
                           <table>
                              <thead>
                              <tr>
                                 <th>Timestamp</th>
                                 <th>Duration (min)</th>
                                 <th>Deviation Temp (℃)</th>
                                 <th>Deviation Hum (%)</th>
                              </tr>
                              </thead>
                              <tbody>
                              <?php
                              $arr = $sensorReadingOutOfBounds[$sensor->id];
                              foreach ($arr as $j => $item) { ?>
                                 <tr style="border-bottom: 1px solid #394960;">
                                    <td><?php echo $item->beforeDate->format("d/m/Y H:i") ?></td>
                                    <td title="Readings count: <?php echo $item->count ?>"><?php echo $item->duration ?></td>
                                    <td <?php echo $item->temp < $sensor->minTemp || $item->temp > $sensor->maxTemp ?
                                       "style='color: #dc3545;'" :
                                       "" ?>>
                                       <?php echo number_format($item->temp, 1) ?>
                                    </td>
                                    <td <?php echo $item->hum < $sensor->minRelHum || $item->hum > $sensor->maxRelHum ?
                                       "style='color: #dc3545;'" :
                                       "" ?>>
                                       <?php echo number_format($item->hum, 1) ?>
                                    </td>
                                 </tr>
                              <?php } ?>
                              </tbody>
                           </table>
                        <?php } ?>
                     </div>

                  </div>

                  <!--  DETAILS  -->
                  <div class="tab-pane" id="nav-details-<?php echo $i ?>" role="tabpanel">
                     <h3 class="h3">Details</h3>
                     <form action="" method="post">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; grid-gap: 0.4em 3em; padding-bottom: 0.8em">
                           <?php echo SensorCrudPartialCreateEdit::GetHtml($sensor) ?>
                        </div>
                        <div style="padding-top: 0.4em">
                           <div>
                              <span>Auth</span>
                              <input type="password" name="auth">
                              <button class="button sensorCrudActionSaveBtn" type="button">Save</button>
                              <button class="button sensorCrudActionDeleteBtn button-danger" type="button">Delete</button>
                           </div>
                        </div>
                     </form>
                  </div>

                  <!--  GENERAL  -->
                  <div class="tab-pane" id="nav-general-<?php echo $i ?>" role="tabpanel">
                     <h3 class="h3">General</h3>
                     <div style="margin-bottom: 0.4em">
                        <span>Sensor interface:</span>
                        <a href="<?php echo htmlspecialchars($sensor->ip) ?>"
                           target="_blank"><?php echo htmlspecialchars($sensor->ip) ?></a>
                     </div>
                  </div>

                  <!--  EXPORT -->
                  <div class="tab-pane" id="nav-export-<?php echo $i ?>" role="tabpanel">
                     <h3 class="h3">Export</h3>
                     <button style="margin-bottom: 0.4em" class="js-export-btn button"
                             data-filename='<?php echo htmlspecialchars($sensor->name . '_' . $dateFrom . '_' . $dateTo . '.csv'); ?>'
                             data-sensorId='<?php echo $sensor->id; ?>'>Export
                     </button>
                  </div>

                  <!--  IMPORT  -->
                  <div class="tab-pane" id="nav-import-<?php echo $i ?>" role="tabpanel">
                     <h3 class="h3">Import</h3>
                     <div style="margin-bottom: 0.8em">
                        <?php if ($sensor->isPortable) { ?>

                           <div>
                              <div>
                                 <span>Upload column order</span>
                                 <input type="text"
                                        id="csvParseExpressionSensorId_<?php echo $sensor->id; ?>"
                                        value="date;temp;relHum">
                              </div>
                              <div>
                                 <button type="button" class="button csvFile"
                                         data-sensorId="<?php echo $sensor->id; ?>">Upload
                                 </button>
                              </div>
                           </div>
                        <?php } else { ?>
                           <p>Sensor is not portable! Cannot import!</p>
                        <?php } ?>
                     </div>
                  </div>


               </div>
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
                  <tr class="zebra-graph-settings">
                     <td style="display: none" class="sensorId"><?php echo $sensor->id ?></td>
                     <td class="sensorName"><?php echo htmlspecialchars($sensor->name) ?></td>
                     <td style="display: flex">
                        <input class="colorSelectTemp" type="color" style="width: 100%; padding: 0"
                               value="#000000">
                        <input class="colorSelectHum" type="color" style="width: 100%; padding: 0"
                               value="#000000">
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
                  <select id="graphOptionsIntervalSelect">
                     <option value="15" selected>15 min</option>
                     <option value="30">30 min</option>
                     <option value="120">2 hour</option>
                     <option value="360">6 hour</option>
                     <option value="1440">1 day</option>
                  </select>
               </div>
               <div class="graphOptions">
                  <p>Interval Strategy</p>
                  <select id="graphOptionsStrategySelect">
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
         <p id="chartErr">No sensor selected</p>
         <div id="chartDiv" style="display: none">
            <button id="saveImgBtn" type="button" class="button">Save Image</button>
            <div id="chart" style="width: 100%; height: 500px;"></div>
            <div style="display: none" id="chartAsPictureDiv"></div>
         </div>
      </div>
   </div>
</div>
<footer class="footer">
   Javascript failed!
</footer>
<!-- Modal -->
<div class="modal fade" id="JsonModal" tabindex="-1" role="dialog">
   <div class="modal-dialog" role="document">
      <div class="modal-content modal-dialog" role="document">
         <div class="modal-header" style="display: block">
            <h5 class="modal-title">Confirm data?</h5>
            <span id="modalMsg"></span>
            <button style="position: absolute; top: 16px; right: 16px;" type="button" class="close"
                    data-dismiss="modal" aria-label="Close">
               <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body" style="height: 40em; overflow-y: auto">
               <pre id="csvDataDisplay"></pre>
         </div>

         <div style="padding: .75rem; border-top: 1px solid #dee2e6;">
            <label for="import-form-password-input">Auth</label>
            <input id="import-form-password-input" type="password">
            <button id="import-form-submit-btn" class="button">Submit</button>
         </div>
      </div>
   </div>
</div>
<!-- end Modal -->

</div>
</body>

<script type="text/javascript">

   dayjs.extend(window.dayjs_plugin_customParseFormat);
   let stateTemplate = document.querySelector('.templating-root').innerHTML;
   let baseApi = window.location.protocol + '//' + window.location.host + "/v1"

   function HandleSensorCreate() {
      let submitBtn = document.querySelector('#collapseSensorCreateSubmitBtn');
      submitBtn.addEventListener('click', async (e) => {
         e.preventDefault();
         let form = submitBtn.closest('form');
         if (! form.reportValidity()) {
            return;
         }
         let formData = getFormSensor(form);
         console.log("Sending a post request: ", baseApi + "/sensor/create")
         let response = await fetch(baseApi + "/sensor/create", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json'},
            body: JSON.stringify(formData)
         })
         let msg = await response.text();
         console.log(`${msg}`);
         if (response.ok) {
            location.reload();
         } else {
            stateHasChanged([msg]);
         }
      })
   }

   function HandleSensorUpdate() {
      let submitBtns = document.querySelectorAll('.sensorCrudActionSaveBtn');
      for (let submitBtn of submitBtns) {
         submitBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            let form = submitBtn.closest('form');
            if (! form.reportValidity()) {
               return;
            }
            let formData = getFormSensor(form);
            console.log("Sending a post request: ", baseApi + "/sensor/update")
            let response = await fetch(baseApi + "/sensor/update", {
               method: 'POST',
               headers: { 'Content-Type': 'application/json'},
               body: JSON.stringify(formData)
            })
            let msg = await response.text();
            console.log(`${msg}`);
            if (response.ok) {
               location.reload();
            } else {
               stateHasChanged([msg]);
            }
         })
      }
   }

   function getFormSensor(form) {
      let id = form.querySelector('[name="id"]').value;
      let name = form.querySelector('[name="name"]').value;
      let serial = form.querySelector('[name="serial"]').value;
      let model = form.querySelector('[name="model"]').value;
      let ip = form.querySelector('[name="ip"]').value;
      let location = form.querySelector('[name="location"]').value;
      let isPortable = form.querySelector('[name="isPortable"]').value === 'Y';
      let minTemp = form.querySelector('[name="minTemp"]').value;
      let maxTemp = form.querySelector('[name="maxTemp"]').value;
      let minRelHum = form.querySelector('[name="minRelHum"]').value;
      let maxRelHum = form.querySelector('[name="maxRelHum"]').value;
      let readingIntervalMinutes = form.querySelector('[name="readingIntervalMinutes"]').value;
      let auth = form.querySelector('[name="auth"]').value;
      return {
         auth: auth,
         sensor: {
            id: id,
            name: name,
            serial: serial,
            model: model,
            ip: ip,
            location: location,
            isPortable: isPortable,
            minTemp: minTemp,
            maxTemp: maxTemp,
            minRelHum: minRelHum,
            maxRelHum: maxRelHum,
            readingIntervalMinutes: readingIntervalMinutes
         }
      };
   }

   function HandleSensorDelete() {
      let submitBtns = document.querySelectorAll('.sensorCrudActionDeleteBtn');
      for (let submitBtn of submitBtns) {
         submitBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            let form = submitBtn.closest('form');
            if (! form.reportValidity()) {
               return;
            }
            let id = form.querySelector('input[name="id"]').value;
            let auth = form.querySelector('input[name="auth"]').value;
            let formData = {
               'id': id,
               'auth': auth
            };
            console.log("Sending a post request: ", baseApi + "/sensor/delete")
            let response = await fetch(baseApi + "/sensor/delete", {
               method: 'POST',
               headers: { 'Content-Type': 'application/json'},
               body: JSON.stringify(formData)
            })
            let msg = await response.text();
            console.log(`${msg}`);
            if (response.ok) {
               location.reload();
            } else {
               stateHasChanged([msg]);
            }
         })
      }
   }


   function TriggerPortableSensorDataCsvUpload(csvFileUploadBtn) {
      let id = csvFileUploadBtn.getAttribute('data-sensorId');
      let reader = new FileReader();
      reader.onload = function (event) {
         let csv = event.target.result;
         let delimiter = ';';
         let headers = document.getElementById('csvParseExpressionSensorId_' + id).value.split(delimiter);
         let arr, skipCount;
         try {
            [arr, skipCount] = csvToJsonArray(csv, delimiter, headers);
         } catch (e) {
            stateHasChanged([e]);
            return false;
         }
         document.getElementById('modalMsg').innerText = `Total data rows count: ${arr.length + skipCount}, skipped ${skipCount}`;
         document.getElementById('csvDataDisplay').innerText = JSON.stringify(arr, null, 2);
         document.getElementById('csvDataDisplay').style.display = 'block';
         document.querySelector('#import-form-submit-btn').onclick = async () => {
            let pass = document.getElementById('import-form-password-input').value;
            console.log("Sending a post request: ", baseApi + "/sensor-reading/upload")
            let response = await fetch(baseApi + "/sensor-reading/upload", {
               method: 'POST',
               headers: { 'Content-Type': 'application/json'},
               body: JSON.stringify({
                  "sensorReadings": arr,
                  "sensorId": id,
                  "auth": pass
               })
            })
            let msg = await response.text();
            console.log(`${msg}`);
            if (response.ok) {
               location.reload();
            } else {
               stateHasChanged([msg]);
               $('#JsonModal').modal('hide');
            }
         }
         $('#JsonModal').modal('show');
      }
      let input = document.createElement('input');
      input.type = 'file';
      input.accept = '.csv';


      input.addEventListener("change", () => {
         reader.readAsText(input.files[0]);
      })
      input.click();
   }


   function LoadButtonEvent() {
      let dateTo = document.getElementById('dateTo').value;
      let type = document.querySelector('input[name="dateFromType"]:checked').value;
      if (! ['absolute', 'relative'].includes(type)) throw `Unknown type: ${type}`;
      let dateFrom = type === 'absolute' ? document.getElementById('absoluteDateFrom').value : '-' + document.getElementById('relativeDateFrom').value;
      window.location.href = window.location.protocol + '//' + window.location.host + `/overview?From=${dateFrom}&To=${dateTo}`;
   }

   function getEOL() {
      let aPlatform = navigator.platform.toLowerCase();
      if (aPlatform.indexOf('win') !== -1) return '\r\n'
      if (aPlatform.indexOf('mac') !== -1) return '\r'
      return '\n'
   }

   function csvToJsonArray(csv, delimiter, headers) {
      let allowedHeaders = ['date', 'temp', 'relHum'];
      if (!allowedHeaders.every(x => headers.includes(x))) {
         throw `Missing field, fields [${allowedHeaders.join(',')}] must be defined in [${headers.join(',')}]!`;
      }
      let eol = getEOL();
      // skip 1. row (the header)
      let rows = csv.slice(csv.indexOf(eol) + eol.length).split(eol);
      if (rows.length === 0) {
         throw 'No rows!';
      }
      rows.pop(); // remove newline

      let result = [];
      let skipCount = 0;
      let nRowNumber = 0;
      for (let row of rows) {
         nRowNumber++;
         let obj = {};
         let values = row.split(delimiter);
         if (values.length !== headers.length) {
            throw `Row: ${nRowNumber}. Header and data column count mismatch!`;
         }
         if (values.length < 3) {
            throw `Row: ${nRowNumber}. Row has less than 3 columns. Row value: ${row} parsed as [${values.join(',')}]!`;
         }
         for (let i = 0; i < headers.length; i++) {
            let header = headers[i];
            let value = values[i];

            if (header === '') {
               continue;
            }

            if (value.slice(0, 5) === 'Error' || value === '') {
               skipCount++;
               break;
            }
            switch (header) {
               case 'date':
                  let date = dayjs(value, 'DD/MM/YYYY HH:mm:ss');
                  if (!date.isValid()) {
                     throw `Row: ${nRowNumber}, Invalid date format! Must be DD/MM/YYYY HH:mm:ss!`
                  }
                  obj[header] = date.format('DD-MM-YYYY HH:mm:ss')
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

   function drawChart(indexModel, dateFrom, dateTo) {
      let chartEle = document.getElementById("chart");
      let data = new google.visualization.DataTable();

      // let t0 = performance.now();

      let step = parseInt(document.getElementById('graphOptionsIntervalSelect').value);
      let strategy = document.getElementById('graphOptionsStrategySelect').value;
      // undefined value is ignored in graph
      let graphDefaultValue = document.getElementById('graphOptionsLoudNoValue').checked ? 0 : undefined;

      let sensors = indexModel.GetSensorsWithSettings().filter(sensor => sensor.sensorSettings.isTemp || sensor.sensorSettings.isRelHum)

      let chartDiv = document.getElementById('chartDiv');
      let chartErr = document.getElementById('chartErr');
      if (sensors.length === 0) {
         chartDiv.style.display = 'none';
         chartErr.innerText = 'No sensor selected';
         return;
      }
      chartDiv.style.display = 'block';
      chartErr.innerText = '';


      let graphLines = [];
      let xAxisTimesToSensors = [];
      for (let i = 0; ; i++) {
         let nextDate = dateFrom.add(step * i, 'minutes');
         if (nextDate >= dateTo) break;
         xAxisTimesToSensors.push({
            'datetime': nextDate,
            'data': []  // will match graphLines length
         });
      }

      for (let sensor of sensors) {
         let sensorReadings = indexModel.sensorReadingsMap.find(x => x.key === sensor.id).value;
         let tempRangeAvg = (sensor.maxTemp + sensor.minTemp) / 2;
         let humRangeAvg = (sensor.maxRelHum + sensor.minRelHum) / 2;

         let low = 0;
         for (let i = 0; i < xAxisTimesToSensors.length - 1; i++) {
            let doStrategy = function (strategy, arr, graphDefaultValue, sensorRangeAvg) {
               let getMedianValue = function (arr, graphDefaultValue) {
                  let valOrUndefined = arr[Math.floor(arr.length / 2)];
                  return valOrUndefined === undefined ? graphDefaultValue : valOrUndefined;
               }
               let getAverageValue = function (arr, graphDefaultValue) {
                  // rounded to 1 decimal places
                  return arr.length === 0 ? graphDefaultValue : Math.round(arr.reduce((a, b) => a + b) / arr.length * 10 + Number.EPSILON ) / 10;
               }
               let getDeviationValue = function (arr, graphDefaultValue, sensorRangeAvg) {
                  return arr.length === 0 ? graphDefaultValue :
                     arr.sort(
                        (a, b) => Math.abs(a - sensorRangeAvg) - Math.abs(b - sensorRangeAvg)
                     )[arr.length - 1]
               }
               switch (strategy) {
                  case 'median':
                     return getMedianValue(arr, graphDefaultValue);
                  case 'average':
                     return getAverageValue(arr, graphDefaultValue);
                  case 'deviation':
                     return getDeviationValue(arr, graphDefaultValue, sensorRangeAvg);
                  default:
                     throw Error("Unknown value!");
               }
            }
            let before = xAxisTimesToSensors[i].datetime;
            let after = xAxisTimesToSensors[i + 1].datetime;

            let tmp = FilterSortedArrayValuesBetweenDates(sensorReadings, before, after, low);
            let currentRows = tmp.result;
            low = tmp.low;

            if (sensor.sensorSettings.isTemp) {
               let tempValue = doStrategy(strategy, currentRows.map(x => x.temp), graphDefaultValue, tempRangeAvg);
               xAxisTimesToSensors[i].data.push(tempValue);
               if (i === 0) {
                  graphLines.push({
                     sensorName: encodeURI(sensor.name),
                     unit: "℃",
                     graphLineColor: sensor.sensorSettings.colorTemp,
                     label: `${sensor.name} temperature (℃)`
                  });
               }
            }
            if (sensor.sensorSettings.isRelHum) {
               let relHumValue = doStrategy(strategy, currentRows.map(x => x.relHum), graphDefaultValue, humRangeAvg);
               xAxisTimesToSensors[i].data.push(relHumValue);
               if (i === 0) {
                  graphLines.push({
                     sensorName: encodeURI(sensor.name),
                     unit: "%",
                     graphLineColor: sensor.sensorSettings.colorRelHum,
                     label: `${sensor.name} relative humidity (%)`
                  });
               }
            }
         }
      }
      // We needed one extra date for data manipulation. The last data is empty
      xAxisTimesToSensors.pop();

      // add header columns
      data.addColumn('date');  // This column is for the x axis, you can also use datetime.
      for (let lineData of graphLines) {
         data.addColumn('number', lineData.label);  // This column is the data, label is used in legend for each line
         data.addColumn({ type: 'string', role: 'tooltip', p: { html: true } });  // This column is for HTML tooltips for hovering cursor over line
      }

      // add data columns
      let rows = [];
      for (let xAxisTime of xAxisTimesToSensors) {
         let row = [];
         row.push(xAxisTime.datetime.add(step / 2, 'minutes').toDate())
         for (let i = 0; i < graphLines.length; i++) {
            row.push(xAxisTime.data[i]);
            row.push(
               `<div class="p-2">
                  <p>
                     <b>${xAxisTime.datetime.format('HH:mm, DD-MM-YYYY')}</b>
                  </p>
                  <p style="white-space: nowrap;" class="mt-3">
                     <i style="color: ${graphLines[i].graphLineColor}" class="bi bi-circle-fill"></i>
                     ${graphLines[i].sensorName}:
                     <b>${xAxisTime.data[i]} ${graphLines[i].unit}</b>
                  </p>
               </div>`
            );
         }
         rows.push(row);
      }
      data.addRows(rows);

      let colors = graphLines.map(graphLine => graphLine.graphLineColor);

      let options = {
         title: `Sirowa sensor measurements`,
         fontSize: 13,
         vAxis: {
            title: 'Temperature (℃) and Relative Humidity (%)',
         },
         hAxis: {
            title: `Datetime (${dateFrom.format('DD-MM-YYYY, HH:mm')} - ${dateTo.format('DD-MM-YYYY, HH:mm')})`,
            minorGridlines: { // will hide non month vertical gridlines
               color: 'transparent'
            },
            format: "dd. MMM"
         },
         tooltip: {isHtml: true},
         colors: colors,  // hexadecimal color values for every line in order
         legend: {position: 'right'},
      };
      let chart = new google.visualization.LineChart(chartEle);

      google.visualization.events.addListener(chart, 'ready', function () {
         document.getElementById('chartAsPictureDiv').innerHTML = `<img id="chartAsPictureImg" src="${chart.getImageURI()}">`;
      })

      chart.draw(data, options);
   }

   function SaveChartImg(dateFrom, dateTo) {
      let base64 = document.getElementById('chartAsPictureImg').src;
      let fileName = 'tempsens ' + dateFrom + '-' + dateTo;
      ExportBase(base64, fileName)
   }

   function ExportBase(encodedUri, filename) {
      let link = document.createElement("a");
      // document.body.appendChild(link); // This might be needed in some browsers, currently not needed.
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", filename)
      link.click();
   }

   function FilterSortedArrayValuesBetweenDates(sensorReadings, before, after, low) {
      // this would be more readable, but much slower duo to searching the entire array
      // sensorReadings.filter(x => before <= x.date && after > x.date);

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

   class SensorSettings {
      constructor(colorTemp, colorRelHum, isTemp, isRelHum) {
         this.colorTemp = colorTemp;
         this.colorRelHum = colorRelHum;
         this.isTemp = isTemp;
         this.isRelHum = isRelHum;
      }
   }

   class Sensor {
      constructor(id, name, maxTemp, minTemp, maxRelHum, minRelHum, sensorSettings) {
         this.id = id;
         this.name = name;
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


   function handleRadioClick(dateFromType) {
      const absoluteDateFromInput = document.getElementById("absoluteDateFrom");
      const relativeDateFromInput = document.getElementById("relativeDateFrom");

      const relative = "relative";
      const absolute = "absolute";
      if (! [relative, absolute].includes(dateFromType.value)) {
         stateHasChanged("Unexpected value: " + dateFromType.value);
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
      document.querySelectorAll('.collapse-and-change-icon').forEach(x => x.onclick = () => {
         let targetElement = document.querySelector(x.getAttribute("data-target"))
         if (targetElement.classList.contains("collapsing")) {
            // prevents the icon from going out of sync from the collapse state by multiple rapid clicks
            return;
         }
         // $(x.find('i')).toggleClass('bi bi-caret-down bi bi-caret-right');
         x.querySelector('i').classList.toggle('bi-caret-down');
         x.querySelector('i').classList.toggle('bi-caret-right');
      });
   }


   function ExportCSV(filename, sensorReadings) {
      let data = [];
      data.push(['Timestamp', 'Temperature (*C)', 'Relative Humidity (%)'])
      for (let sensorReading of sensorReadings) {
         let row = [sensorReading.date.format("DD/MM/YYYY HH:mm:ss"), sensorReading.temp, sensorReading.relHum];
         data.push(row)
      }
      let eol = getEOL();
      let csvContent = "data:text/csv;charset=utf-8," + data.map(e => e.join(";")).join(eol);
      let encodedUri = encodeURI(csvContent);

      ExportBase(encodedUri, filename)
   }

   Math.seed = function(s) {
      // https://stackoverflow.com/questions/521295/seeding-the-random-number-generator-in-javascript
      let mask = 0xffffffff;
      let m_w  = (123456789 + s) & mask;
      let m_z  = (987654321 - s) & mask;

      return function() {
         m_z = (36969 * (m_z & 65535) + (m_z >>> 16)) & mask;
         m_w = (18000 * (m_w & 65535) + (m_w >>> 16)) & mask;

         let result = ((m_z << 16) + (m_w & 65535)) >>> 0;
         result /= 4294967296;
         return result;
      }
   }

   function randomHexColor(getRandom) {
      // https://stackoverflow.com/questions/1484506/random-color-generator
      return '#' + (getRandom().toString(16) + "000000").substring(2,8);
   }


   class IndexModel {
      dateTo = dayjs('<?php echo $dateTo . ', 23:59:59' ?>', 'DD-MM-YYYY, HH:mm:ss');
      dateFrom = dayjs('<?php echo $dateFrom . ', 00:00:00' ?>', 'DD-MM-YYYY, HH:mm:ss');
      sensorReadingsMap = [];
      sensorsWithoutSettings = [];

      constructor() {
         let sensorReadingsById = JSON.parse('<?php echo Helper::EchoJson($sensorReadingsBySensorId); ?>');

         let sensorReadingsMap = [];
         for (let [id, sensorReadings] of Object.entries(sensorReadingsById)) {
            let newSensorReadings = [];
            for (let sensorReading of sensorReadings) {
               newSensorReadings.push(new SensorReading(
                  dayjs(sensorReading.date, 'YYYYMMDDHHmmss'),
                  parseFloat(sensorReading.temp),
                  parseFloat(sensorReading.relHum)));
            }
            sensorReadingsMap.push(
               {
                  key: id,
                  value: newSensorReadings
               });
         }
         let sensorsOld = JSON.parse('<?php echo Helper::EchoJson($sensors); ?>');
         let sensors = [];
         for (let sensor of sensorsOld) {
            sensors.push(new Sensor(
               sensor.id,
               sensor.name,
               sensor.maxTemp,
               sensor.minTemp,
               sensor.maxRelHum,
               sensor.minRelHum,
               null
            ));
         }
         this.sensorsWithoutSettings = sensors;
         this.sensorReadingsMap = sensorReadingsMap;
      }

      GetSensorsWithSettings() {
         let sensors = [];
         for (let sensor of this.sensorsWithoutSettings) {
            sensors.push(new Sensor(
               sensor.id,
               sensor.name,
               sensor.maxTemp,
               sensor.minTemp,
               sensor.maxRelHum,
               sensor.maxRelHum,
               GetDrawingSettings(sensor.id)
            ));
         }
         return sensors;
      }
   }

   function stateHasChanged(errors) {
      document.querySelector('.templating-root').innerHTML = nunjucks.renderString(stateTemplate, { errors: errors });
      window.scrollTo(0, 0);
   }

   async function main() {
      let indexModel = new IndexModel();
      stateHasChanged([]);
      fetch("../view/partial/FooterPartial.html").then(x => x.text().then(res => document.querySelector(".footer").innerHTML = res));
      document.querySelector('#saveImgBtn').onclick = () => SaveChartImg(
         indexModel.dateFrom.format("DD-MM-YYYY"),
         indexModel.dateTo.format("DD-MM-YYYY")
      );
      let getRandom = Math.seed(9);
      document.querySelectorAll('.colorSelectTemp').forEach(x => x.value = randomHexColor(getRandom));
      document.querySelectorAll('.colorSelectHum').forEach(x => x.value = randomHexColor(getRandom));
      document.querySelectorAll('.csvFile').forEach(x => x.onclick = () => TriggerPortableSensorDataCsvUpload(x));
      document.querySelectorAll('.js-export-btn').forEach(x => x.onclick = () =>
         ExportCSV(
            x.getAttribute("data-filename"),
            indexModel.sensorReadingsMap.find(y => y.key === x.getAttribute("data-sensorId")).value
         )
      );
      HandleSensorCreate();
      HandleSensorUpdate();
      HandleSensorDelete();
      document.querySelector('#btnLoad').onclick = LoadButtonEvent;
      // we need dates to be in this format for GET request
      ['#dateTo', '#absoluteDateFrom'].forEach(item => $(item).datepicker({dateFormat: "dd-mm-yy"}));
      document.querySelector('#ChartDrawButton').onclick = () => {
         google.charts.load("current", {'packages': ["corechart", "line"]});
         google.charts.setOnLoadCallback(() => drawChart(
            indexModel,
            indexModel.dateFrom,
            indexModel.dateTo
         ));
      };
      HandleCollapseSymbolChange()
   }

   main();
   document.querySelector('.templating-root').style.display = 'block';

</script>
</html>
