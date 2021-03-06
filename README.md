# Tempsens

## Demo
[Live demo on Heroku](https://tempsens-testing.herokuapp.com/site/viewController/index.php?From=-91&To=01-04-2021)

NB! Database size is limited to 5MB, so displayed data is not accurate.

## Local installation
Guide for setting the project up locally using XAMPP, assuming the project is located at `C:\xampp\htdocs\myApps\Tempsens`.

### Dependencies

#### Current dependencies:
* [phpdotenv](https://github.com/vlucas/phpdotenv) - Loads environment variables from `.env` to `getenv()`, `$_ENV` and `$_SERVER` automagically.

#### Generate dependencies
Run `composer update` in root folder.

If the command throws error '`Root composer.json requires PHP extension ext-soap ...`' uncomment `extension=soap` attribute in the `php.ini` global PHP config file.

### Create `.env` file
To prevent sensitive data being uploaded to remote (Github) a template file is used.
`.env_template` file is provided with empty keys.
Create a copy of this file named `.env` and set its key values.

### Allow database access
Go into your `php.ini` file and uncomment `extension=pdo_mysql`.

## Local testing

### Test web upload:
Sample CSV data files are available on request to test portable sensor's sensor readings import functionality. 
Request `backupCSV/portable` folder.

### Sample data for Database
Script `site/db/Initializer.php` generates new database tables and imports data from local CSV files.
Modify the script to fit your needs then run `php Initializer.php` to execute the script.

Sample CSV data files are available on request. 
Create directory `site/db/backupCSV` and place the data there.

### SOAP API
You can test sensor's SOAP insert API requests locally using your preferred API testing tool, I used Postman.

Send a `POST` request to URL `http://localhost:80/myApps/Tempsens/site/SoapMethods.php` with request body:

```
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
   xmlns:xsd="http://www.w3.org/2001/XMLSchema">
   <soap:Body>
      <InsertTx5xxSample xmlns="http://cometsystem.cz/schemas/soapTx5xx_v2.xsd">
         <passKey>20960050</passKey>
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
```
Expected response:
```
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://localhost" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
    <SOAP-ENV:Body>
        <ns1:InsertTx5xxSampleResponse>
            <return xsi:nil="true"/>
        </ns1:InsertTx5xxSampleResponse>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```
## Database Schema

[Note] Disable word wrap to display table correctly (alt + z in VSCode). Viewing markdown files through Github is not an issue. 

```
+------------------------------------+      +---------------------------------------------+                                                                                                              
|               Sensors              |      |               SensorReading                 |                                                                                                              
+--------------+---------------+-----+      +--------------+----------------+-------------+                                                                                                              
| Id           | string!       | PK  |      | Id           | string!        | PK          |                                                                                                              
| Name         | string!       |     |1....*| SensorId     | string!        | FK(sensors) |                                                                                                              
| Serial       | string!       |     |      | Temp         | decimal(18,1)! |             |                                                                                                              
| Model        | string!       |     |      | RelHum       | decimal(18,1)! |             |                                                                           
| Ip           | string!       |     |      | DateRecorded | string!        | Idx         |                                                                           
| Location     | string!       |     |      | DateAdded    | string?        |             |                                                                        
| IsPortable   | bool!         |     |      +--------------+----------------+-------------+                                                                                                        
| MinTemp      | decimal(18,1)!|     |                                                                                                              
| MaxTemp      | decimal(18,1)!|     |                                                                                                                                         
| MinRelHum    | decimal(18,1)!|     |                                                                                                                                                           
| MaxRelHum    | decimal(18,1)!|     |                                                                                                              
| ReadingInterv| int!          |     |                                                                                                               
+--------------+---------------+-----+                                                                        
```                                                                                                           
[Tip] To edit DB schema learn to use VSCode multiline editing shortcuts (alt + mouse_click; alt + ctrl + down/up arrow)

Date format is YYYYMMDDHHmm. This makes ordering SensorReadings by date easy (eg 13:42, 13.03.2021 becomes 202103131342)                
DateAdded is null for not isPortable sensors.

## Patch notes


### 0.1.0
15.01.2020 by Indrek Hiie - Soap Data Collector 

### 0.2.0
27.02.2020 by Timm Soodla - initial GUI version
### 0.3.0
30.12.2020 by Indrek Hiie - moved to PDO MySQL driver, sensors now in DB, more modular code and some bugfixes applied

### 0.3.4 (was used in production)

[Preview image](_documents/version_0.3.4.png)

### 0.3.5 (didn't reach production)
24.01.2021 - watchdog added

### 1.0.0

![Preview image](_documents/version_1.0.0.png)

Notes:
* Remade database (removed: alarms, emails, emails_to_sensor, parms, portable and queue. Redesigned sensor and sensorReading)
* Removed unnecessary values sent by sensors from being saved to sensorReading
* Removed scripts/alarms.php - Email sending didn't work
* Removed confusing alerts(global vs sensor) and Max/min(total vs sensor)
* Removed ambiguous sensor state .png balls in v0.3.5
* Removed unused Composer dependencies(php-jwt, Guzzle, oauth2-client, random_compaq, Phpmailer, http-message, getallHeaders, oauth-keycloak, Symfony)
* Replaced Db generated int Ids with server-side 22 char base64 random generated ones
* Added Db default data initialization script and migrations
* Now all sensors are shown together and can be graphed together
* Added relative humidity to overview and graph
* Fixed bugs with selecting end date changing start date value
* Added CRUD functionality to sensor
* Added cache file for fast last sensor readings
* Added options for chart drawing
* Added option to export graph as screenshot
* Added option to upload CSV data to portable sensors
* Restructured code
* Separated views and controllers, following MVC design
* Added partial views
* Created favicon
* Used new PHP 8.0 and below features (function parameter and return types, named arguments, constructor property promotion)

by: Karl Oskar Anderson

## Production setup notes
Configure sensor's registered SOAP API path to  `site/SoapMethods.php`

Merge current document in _documents folder with the latest production documentation


## Todo

Todo notes:
* Test SensorReading sql performance in non localhost environment. 
I had a weird situation where selecting 1 more field (dateAdded) significantly slows down query even if the field is always empty/null. 
