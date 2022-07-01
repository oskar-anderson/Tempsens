# Tempsens

## Installation

### Generate dependencies:
In root folder (Tempsens)
```
composer update
```

If the command throws error '`Root composer.json requires PHP extension ext-soap ...`' uncomment `extension=soap` attribute in the `php.ini` global PHP config file.

### Create config.php file
To prevent sensitive data being uploaded to remote (Github) a template file is used.
`configTemplate.php` file is provided with empty keys.
Create a copy of this file named `config.php` and set its key values.

### Generate Database
Go into your `php.ini` file and uncomment `extension=pdo_mysql`.

## Vendor dependencies

Current dependencies:
* autoloader(No dependencies)

Update dependencies from composer.json
```
composer update
```

## Database

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
DateAdded is null for not isPortable sensors as this.

## patch notes


### 0.1.0
15.01.2020 by Indrek Hiie - Soap Data Collector 

### 0.2.0
27.02.2020 by Timm Soodla - initial GUI version
### 0.3.0
30.12.2020 by Indrek Hiie - moved to PDO MySQL driver, sensors now in DB, more modular code and some bugfixes applied

### 0.3.4

![Preview image](_documents/version_0.3.4.png)

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
* Separated views and controllers
* Added partial views
* Created favicon
* Used new PHP 8.0 and below features (function parameter and return types, named arguments, constructor property promotion)

by: Karl Oskar Anderson

## Future

### Todo
Test SensorReading sql performance in non localhost environment. 
I had a weird situation where selecting 1 more field (dateAdded) significantly slows down query even if the field is always empty/null. 

Change initializer.php to load data from real DB.

Remove bash crud job (scripts/alarms.php) that will not work anymore - I don't think it ever did.

Merge current document in _documents folder with the latest production documentation.


## Notes

Good:
* Project structure

Bad:
* PHP
  * Types and type hinting tricks
    * Assoc array vs indexed array
    * Using PHPDoc for array init: @return Sensor[]
  * echo wrapping Console::Writeline()
  * no namespaces for builtin functions
  * too many parenthesis: (new DalSensors())->GetAll();
  * Feels like an 8-year-old language
  * python list comprehension > C# Linq > Java Streams > PHP array_map()

Confusing:
* PHP
  * echo (syntax and manual newline)
  * array_push(myArr, 7) vs myArr[7]
  * array creation: array() vs []
  * PHP manual user notes
* Routing (.htaccess vs index.php)
* PHP and JS data transfer (REST vs echo into JS var)
* PHP vs JS for site rendering
* Cookies - ended making a cache.json file