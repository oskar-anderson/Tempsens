import dayjs from "dayjs";
import dayjs_plugin_customParseFormat from "dayjs";
import utc from "dayjs";
import timezone from "dayjs";
import SensorSettings from "./SensorSettings";

dayjs.extend(dayjs_plugin_customParseFormat);
dayjs.extend(utc)
dayjs.extend(timezone) // dependent on utc plugin


export default class Overview {
   constructor(baseApi) {
      this.baseApi = baseApi;
      console.log("JOHN")
   }
   handleSensorCreate() {
      let submitBtn = document.querySelector('#collapseSensorCreateSubmitBtn');
      submitBtn.addEventListener('click', async (e) => {
         e.preventDefault();
         let form = submitBtn.closest('form');
         if (! form.reportValidity()) {
            return;
         }
         let formData = this.getFormSensor(form);
         console.log("Sending a post request: ", this.baseApi + "/v1/sensor/create")
         let response = await fetch(this.baseApi + "/v1/sensor/create", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json'},
            body: JSON.stringify(formData)
         })
         let msg = await response.text();
         console.log(`${msg}`);
         if (response.ok) {
            location.reload();
         } else {
            this.displayError(msg);
         }
      })
   }

   handleSensorUpdate() {
      let submitBtns = document.querySelectorAll('.sensorCrudActionSaveBtn');
      for (let submitBtn of submitBtns) {
         submitBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            let form = submitBtn.closest('form');
            if (! form.reportValidity()) {
               return;
            }
            let formData = this.getFormSensor(form);
            console.log("Sending a post request: ", this.baseApi + "/v1/sensor/update")
            let response = await fetch(this.baseApi + "/v1/sensor/update", {
               method: 'POST',
               headers: { 'Content-Type': 'application/json'},
               body: JSON.stringify(formData)
            })
            let msg = await response.text();
            console.log(`${msg}`);
            if (response.ok) {
               location.reload();
            } else {
               this.displayError(msg);
            }
         })
      }
   }

   getFormSensor(form) {
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

   handleSensorDelete() {
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
               id: id,
               auth: auth
            };
            console.log("Sending a post request: ", this.baseApi + "/v1/sensor/delete")
            let response = await fetch(this.baseApi + "/v1/sensor/delete", {
               method: 'POST',
               headers: { 'Content-Type': 'application/json'},
               body: JSON.stringify(formData)
            })
            let msg = await response.text();
            console.log(`${msg}`);
            if (response.ok) {
               location.reload();
            } else {
               this.displayError(msg);
            }
         })
      }
   }


   triggerPortableSensorDataCsvUpload(csvFileUploadBtn) {
      let id = csvFileUploadBtn.getAttribute('data-sensorId');
      let reader = new FileReader();
      reader.onload = (event) => {
         let csv = event.target.result;
         let delimiter = ';';
         let headers = document.getElementById('csvParseExpressionSensorId_' + id).value.split(delimiter);
         let arr, skipCount;
         try {
            [arr, skipCount] = this.csvToJsonArray(csv, delimiter, headers);
         } catch (e) {
            this.displayError(e);
            return false;
         }
         document.getElementById('modalMsg').innerText = `CSV file contained ${arr.length + skipCount} rows, skipped ${skipCount} rows.`;
         document.getElementById('csvDataDisplay').innerText = JSON.stringify(arr, null, 2);
         document.getElementById('csvDataDisplay').style.display = 'block';
         document.querySelector('#import-form-submit-btn').onclick = async () => {
            let pass = document.getElementById('import-form-password-input').value;
            console.log("Sending a post request: ", this.baseApi + "/v1/sensor-reading/upload")
            let response = await fetch(this.baseApi + "/v1/sensor-reading/upload", {
               method: 'POST',
               headers: { 'Content-Type': 'application/json'},
               body: JSON.stringify({
                  sensorReadings: arr,
                  sensorId: id,
                  auth: pass
               })
            })
            let msg = await response.text();
            console.log(`${msg}`);
            if (response.ok) {
               location.reload();
            } else {
               this.displayError(msg);
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


   loadButtonEvent() {
      let dateTo = document.getElementById('dateTo').value;
      let type = document.querySelector('input[name="dateFromType"]:checked').value;
      if (! ['absolute', 'relative'].includes(type)) throw `Unknown type: ${type}`;
      let dateFrom = type === 'absolute' ? document.getElementById('absoluteDateFrom').value : '-' + document.getElementById('relativeDateFrom').value;
      window.location.href = window.location.protocol + '//' + window.location.host + `/overview?From=${dateFrom}&To=${dateTo}`;
   }


   csvToJsonArray(csv, delimiter, headers) {
      let allowedHeaders = ['date', 'temp', 'relHum'];
      if (!allowedHeaders.every(x => headers.includes(x))) {
         throw `Missing field, fields [${allowedHeaders.join(',')}] must be defined in [${headers.join(',')}]!`;
      }
      let rows = csv.split('\n');
      if (rows.length <= 1) {
         throw 'No rows!';
      }
      rows.shift(); // remove title row
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
                  let date = dayjs.tz(value, 'DD/MM/YYYY HH:mm:ss', "Europe/Tallinn").tz("Etc/UTC");
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

   drawChart(indexModel, dateFrom, dateTo) {
      let chartEle = document.getElementById("chart");
      let data = new google.visualization.DataTable();

      // let t0 = performance.now();

      let step = parseInt(document.getElementById('graphOptionsIntervalSelect').value);
      let strategy = document.getElementById('graphOptionsStrategySelect').value;
      // undefined value is ignored in graph
      let graphDefaultValue = document.getElementById('graphOptionsLoudNoValue').checked ? 0 : undefined;

      let sensors = indexModel.sensors.filter(x => {
         let settings = this.getDrawingSettings(x.id);
         return settings.isTemp || settings.isRelHum
      })

      let chartDiv = document.getElementById('chartDiv');
      let chartErr = document.getElementById('chartErr');
      if (sensors.length === 0) {
         chartDiv.style.display = 'none';
         chartErr.innerText = 'No sensor selected';
         return;
      }
      chartDiv.style.display = 'block';
      chartErr.innerText = '';

      const getBuckets = (dateFrom, dateTo) => {
         let buckets = [];
         let bucketStartDate = dateFrom;  // doing this saves 50ms on large graphs opposed to doing dateFrom.add(step * (i - 1), 'minutes');
         for (let i = 1; ; i++) {
            let bucketNextDate = dateFrom.add(step * i, 'minutes');
            if (bucketNextDate >= dateTo) break;
            buckets.push({
               startDateTime: bucketStartDate,
               endDateTime: bucketNextDate,
               row: []  // will match graphLines length
            });
            bucketStartDate = bucketNextDate;
         }
         return buckets;
      }
      let buckets = getBuckets(dateFrom, dateTo);

      for (let sensor of sensors) {
         let sensorReadings = indexModel.sensorReadingsMap.find(x => x.key === sensor.id).value;
         let tempRangeAvg = (sensor.maxTemp + sensor.minTemp) / 2;
         let humRangeAvg = (sensor.maxRelHum + sensor.minRelHum) / 2;

         let drawingSettings = this.getDrawingSettings(sensor.id);
         if (!drawingSettings.isRelHum && !drawingSettings.isTemp) continue;
         let low = 0;
         for (let bucket of buckets) {
            let doStrategy = function (strategy, arr, graphDefaultValue, sensorRangeAvg) {
               let getMedianValue = function (arr, graphDefaultValue) {
                  let valOrUndefined = arr[Math.floor(arr.length / 2)];
                  return valOrUndefined === undefined ? graphDefaultValue : valOrUndefined;
               }
               let getAverageValue = function (arr, graphDefaultValue) {
                  if (arr.length === 0) return graphDefaultValue;
                  let average = arr.reduce((a, b) => a + b) / arr.length;
                  // rounded to 1 decimal point
                  return Math.round(average * 10) / 10;
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
                     throw "Unknown value!";
               }
            }

            let tmp = this.filterSortedArrayValuesBetweenDates(sensorReadings, bucket.startDateTime, bucket.endDateTime, low);
            let currentRows = tmp.result;
            low = tmp.low;


            if (drawingSettings.isTemp) {
               let tempValue = doStrategy(strategy, currentRows.map(x => x.temp), graphDefaultValue, tempRangeAvg);
               bucket.row.push(tempValue);
            }
            if (drawingSettings.isRelHum) {
               let relHumValue = doStrategy(strategy, currentRows.map(x => x.relHum), graphDefaultValue, humRangeAvg);
               bucket.row.push(relHumValue);
            }
         }
      }
      console.log(buckets);

      let graphLines = [];
      for (let sensor of sensors) {
         let drawingSettings = this.getDrawingSettings(sensor.id);
         if (buckets.length === 0) break;
         if (drawingSettings.isTemp) { // order of temp and humidity data insertion must be same as bucket insertion order
            graphLines.push({
               sensorName: encodeURI(sensor.name),
               unit: "℃",
               graphLineColor: drawingSettings.colorTemp,
               label: `${sensor.name} temperature (℃)`
            });
         }
         if (drawingSettings.isRelHum) {
            graphLines.push({
               sensorName: encodeURI(sensor.name),
               unit: "%",
               graphLineColor: drawingSettings.colorRelHum,
               label: `${sensor.name} relative humidity (%)`
            });
         }
      }

      // add header columns
      data.addColumn('datetime');  // This column is for the x-axis
      for (let lineData of graphLines) {
         data.addColumn('number', lineData.label);  // This column is the data, label is used in legend for each line
         data.addColumn({ type: 'string', role: 'tooltip', p: { html: true } });  // This column is for overriding HTML tooltips for hovering cursor over line
      }

      // add data columns
      let rows = [];
      for (let bucket of buckets) {
         let row = [];
         row.push(bucket.startDateTime.add(step / 2, 'minutes').toDate())
         for (let i = 0; i < graphLines.length; i++) {
            row.push(bucket.row[i]);
            row.push(
               `<div class="p-2">
                     <p>
                        <b>${bucket.startDateTime.format('HH:mm, DD-MM-YYYY')}</b>
                     </p>
                     <p style="white-space: nowrap;" class="mt-3">
                        <i style="color: ${graphLines[i].graphLineColor}" class="bi bi-circle-fill"></i>
                        ${graphLines[i].sensorName}:
                        <b>${bucket.row[i]} ${graphLines[i].unit}</b>
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
         document.getElementById('chartAsPictureDiv').innerHTML = `<img id="chartAsPictureImg" src="${chart.getImageURI()}" alt="chart">`;
      })

      chart.draw(data, options);
   }

   saveChartImg(dateFrom, dateTo) {
      let base64 = document.getElementById('chartAsPictureImg').src;
      let fileName = 'tempsens ' + dateFrom + '-' + dateTo;
      this.exportBase(base64, fileName)
   }

   exportBase(encodedUri, filename) {
      let link = document.createElement("a");
      // document.body.appendChild(link); // This might be needed in some browsers, currently not needed.
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", filename)
      link.click();
   }

   filterSortedArrayValuesBetweenDates(sensorReadings, before, after, low) {
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
         result: result,
         low: low
      };
   }

   getDrawingSettings(_sensorId) {
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


   handleRadioClick(e) {
      let dateFromType = e.target;
      const absoluteDateFromInput = document.getElementById("absoluteDateFrom");
      const relativeDateFromInput = document.getElementById("relativeDateFrom");

      const relative = "relative";
      const absolute = "absolute";
      if (! [relative, absolute].includes(dateFromType.value)) {
         this.displayError("Unexpected value: " + dateFromType.value);
         return;
      }
      if (absoluteDateFromInput.disabled && dateFromType.value !== absolute ||
         relativeDateFromInput.disabled && dateFromType.value !== relative) {
         return;
      }
      absoluteDateFromInput.disabled = dateFromType.value !== absolute;
      relativeDateFromInput.disabled = dateFromType.value !== relative;
   }

   handleCollapseSymbolChange() {
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


   ExportCSV(filename, sensorReadings) {
      let data = [];
      data.push(['Timestamp', 'Temperature (*C)', 'Relative Humidity (%)'])
      for (let sensorReading of sensorReadings) {
         let row = [sensorReading.date.format("DD/MM/YYYY HH:mm:ss"), sensorReading.temp, sensorReading.relHum];
         data.push(row)
      }
      let csvContent = "data:text/csv;charset=utf-8," + data.map(e => e.join(";")).join('\n');
      let encodedUri = encodeURI(csvContent);

      this.exportBase(encodedUri, filename)
   }

   seed = function(s) {
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

   randomHexColor(getRandom) {
      // https://stackoverflow.com/questions/1484506/random-color-generator
      return '#' + (getRandom().toString(16) + "000000").substring(2,8);
   }

   displayError(error) {
      document.querySelector('.error-alert').innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show m-2" role="alert">
               <h4>Error: </h4>
               <p>${error}</p>
               <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span>
               </button>
            </div>
            `;
      window.scrollTo(0, 0);
   }

   async main() {
      let indexModel = new IndexModel();
      let footerResponse = await fetch("../webApp/view/partial/FooterPartial.html");
      document.querySelector(".footer").innerHTML = await footerResponse.text()
      document.querySelector('#typeAbsolute').onclick = (e) => this.handleRadioClick(e);
      document.querySelector('#typeRelative').onclick = (e) => this.handleRadioClick(e);
      document.querySelector('#saveImgBtn').onclick = () => this.saveChartImg(
         indexModel.dateFrom.format("DD-MM-YYYY"),
         indexModel.dateTo.format("DD-MM-YYYY")
      );
      let getRandom = this.seed(9);
      document.querySelectorAll('.colorSelectTemp').forEach(x => x.value = this.randomHexColor(getRandom));
      document.querySelectorAll('.colorSelectHum').forEach(x => x.value = this.randomHexColor(getRandom));
      document.querySelectorAll('.csvFile').forEach(x => x.onclick = () => this.triggerPortableSensorDataCsvUpload(x));
      document.querySelectorAll('.js-export-btn').forEach(x => x.onclick = () =>
         this.ExportCSV(
            x.getAttribute("data-filename"),
            indexModel.sensorReadingsMap.find(y => y.key === x.getAttribute("data-sensorId")).value
         )
      );
      this.handleSensorCreate();
      this.handleSensorUpdate();
      this.handleSensorDelete();
      document.querySelector('#btnLoad').onclick = this.loadButtonEvent;
      // we need dates to be in this format for GET request
      ['#dateTo', '#absoluteDateFrom'].forEach(item => $(item).datepicker({dateFormat: "dd-mm-yy"}));
      document.querySelector('#ChartDrawButton').onclick = () => {
         google.charts.load("current", {'packages': ["corechart", "line"]});
         google.charts.setOnLoadCallback(() => this.drawChart(
            indexModel,
            indexModel.dateFrom,
            indexModel.dateTo
         ));
      };
      this.handleCollapseSymbolChange()
   }
}