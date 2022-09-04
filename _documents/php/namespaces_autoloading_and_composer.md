# Namespaces, autoloading and Composer

## Without using namespaces, requiring individual files
Without namespaces you would use require to load the contents of file B into file A. This would easily lead to unexpected behavior. Lets say we have files `index.php` and `Person.php` that use files `PersonHobby.php` and `PersonUtil.php`.

`index.php`
```php
<?php

require_once 'Person.php';
require_once 'PersonUtil.php';

echo PersonUtil::getGreeting() . "<br>";
(new Person("Kyle"))->greet("Stan");
```


`Person.php`
```php
<?php

require_once 'PersonHobby.php';
// require_once 'PersonUtil.php'; // Oops, we forgot to include this file

class Person {
   public array $hobbies = [];

   public function __construct(
      public string $name
   ) {
      $this->hobbies["cycling"] = new PersonHobby('cycling');
   }

   public function greet($name): void
   {
      echo PersonUtil::getGreeting() . " $name, my name is " . $this->name;
   }
}
```


<details>
<summary>PersonHobby.php</summary>
  
```php
<?php

class PersonHobby {


   function __construct(
      private string $hobbyName,
   ) {}


   public function getHobby() {
      echo $this->hobbyName;
   }
}
```
</details>

<details>
<summary>PersonUtil.php</summary>
  
```php
<?php

class PersonUtil
{
   public static function getGreeting() {
      $rng = rand(0, 100); 
      if ($rng > 50) {
         return "Hello";
      }
      return "Hi";
   }
}
```

</details>


Output of `index.php`
```
Hello
Hello Stan, my name is Kyle
```

Lets say we want to remove usage of `PersonUtil` along with the require statement. We remove the lines:
```php
require_once 'PersonUtil.php';
echo PersonUtil::getGreeting() . "<br>";`
```


Output of `index.php`
```
Fatal error: Uncaught Error: Class "PersonUtil" not found in <path_to_file>\Person.php:18
```
We forgot to require the `PersonUtil.php` file in `Person.php`. Our IDE (PhpStorm) might know about the `PersonUtil` class, but cannot tell us if it is required or not (PhpStorm does however complain about our class names `Namespace name doesn't match the PSR-0/PSR-4 project structure`). This approach is messy, it is easy to break working code.

## Without using namespaces, requiring all files

Lets create a new file called `dependencies.php` and change all our entrypoints to require only `dependencies.php`.

`dependencies.php`
```php
<?php

require_once 'Person.php';
require_once 'PersonHobby.php';
require_once 'PersonUtil.php';
```

`index.php`
```php
<?php

require_once 'dependencies.php';

echo PersonUtil::getGreeting() . "<br>";
(new Person("Kyle"))->greet("Stan");

// OR

(new \Person("Kyle"))->greet("Stan");

/*

\ (backslash) is the namespace separator in PHP 5.3.

A \ before the beginning of a function represents the Global Namespace.

Putting it there will ensure that the function called is from the global namespace, even if there is a function by the same name in the current namespace.

*/
```

`Person.php`
```php
<?php

class Person {
   public array $hobbies = [];

   public function __construct(
      public string $name
   ) {
      $this->hobbies["cycling"] = new PersonHobby('cycling');
   }

   public function greet($name): void
   {
      echo PersonUtil::getGreeting() . " $name, my name is " . $this->name;
   }
}
```

This works, but there are a few problems with this approach:
* It is not efficient
* Using third-party dependencies (libraries) has to be done manually
* All classnames(our own and from libraries we are using) have to be unique


Lets try another approach.

## Using namespaces, requiring all files

Lets add a namespace to Person class. Namespace can be `App`, `asd` or `someRandomString` - it does not a have a restriction (later on we will see this is a bad idea. Namespace should match the file structure). Lets add `namespace App;` to Person class.
```php
<?php

namespace App;

use PersonHobby;

class Person {
   public array $hobbies = [];

   public function __construct(
      public string $name
   ) {
      $this->hobbies["cycling"] = new \PersonHobby('cycling');
   }

   public function greet($name): void
   {
      echo \PersonUtil::getGreeting() . " $name, my name is " . $this->name;
   }
}
```
Notice that we are now using a backslash before using a classname (`\PersonHobby`, `\PersonUtil`). Our other classes are still in a global namespace, so we need to prepend a backslash before calling them or make sure they use the same namespace. If we do not do this we will get errors like this:
``` 
Fatal error: Uncaught Error: Class "App\PersonHobby"
Fatal error: Uncaught Error: Class "App\PersonUtil"
```


Now we create a new Person instance by
```php
(new \App\Person("Kyle"))->greet("Stan");

// OR 

use App\Person;
(new Person("Kyle"))->greet("Stan");
```

This works. But we still need to have a `dependencies.php` file, that we have to keep up to date. It would be great if we could just generate the content of this file based on our file structure.


## With namespaces, no require, doing it ourselves

We can automate keeping track of our dependencies manually by changing `index.php`:
```php
<?php

spl_autoload_register(function ($class) {
   $pathPrepend = __DIR__ . '\\';
   // or you can also do 
   // $pathPrepend = __DIR__ . '/'
   // PHP is messy and will use different slashes interchangeable
   $class = str_replace('App\\', '', $class);
   $classpath = $pathPrepend . $class . '.php';
   require $classpath;
});

use App\Person;
echo PersonUtil::getGreeting() . "<br>";
(new Person("Kyle"))->greet("Stan");
```

When PHP cannot find class PersonUtil it will try to find function `spl_autoload_register` and call it with parameter $class = 'PersonUtil'. That namespace will get converted to file path and be required. The next line `(new \App\Person("Kyle"))->greet("Stan");` will call the autoload function with parameter $class = 'App\Person' and require the `Person.php` file. Although we are still requiring files, it is done so automatically.

This will only work if all the files are in the same directory. But to organize our files we should be able to add them to different directories. Lets create a directory called `person` and move files `Person.php`, `PersonHobby.php` and `PersonUtil.php` into the new directory. Now all these classes have to have `namespace App\person;`. Conventionally the root directory is called `App` or `<your_project_name>` followed by the directory name and seperated by a backslash. We also have to update the dependencies inside `index.php`.

```php
<?php

spl_autoload_register(function ($class) {
   $pathPrepend = __DIR__ . '\\';  
   $class = str_replace('App\\', '', $class);
   $classpath = $pathPrepend . $class . '.php';
   require $classpath;
});

use App\person\Person;
use App\person\PersonUtil;

echo PersonUtil::getGreeting() . "<br>";
(new Person("Kyle"))->greet("Stan");
```

This works well, but we still cannot manage third-party dependencies. There is a tool called Composer that will handle third-party dependencies and also deal with the autoloading for us.

## Composer

Most popular way of managing third-party dependencies is using Composer tool. Instructions for installing Composer can be found on Composer homepage.

You can now create a `composer.json` file. `composer.json` contains configuration for autoloading and manage third-party dependencies in a human readable format.

`composer.json` should look something like this:
```json
{
    "name": "vendor_name/project_name",
    "description": "description",
    "type": "project",
    "minimum-stability": "stable",
    "require": {
        "php": ">=8.0",
        "ext-soap": "*",
        "ext-pdo": "*",
        "ext-ctype": "*",
        "vlucas/phpdotenv": "^5.4"
    },
    "autoload": {
        "psr-4": {
            "App\\": "./site"
        }
    }
}
```

Here we are defining that there is a directory named `site` in the same directory as the `composer.json` file. All PHP files inside `site` directory will use the namespace `App`, all subdirectories will use namespace `App\<subdirectory_name>`. PSR (PHP Standard Recommendation) is a set of good coding standards for PHP.

Run `composer install`. This will create file `composer.lock` and a directory `vendor`:

`composer.lock` - This will lock the version numbers of the dependencies we are using, so `composer install` will always install the same dependency versions.

`vendor` - contains file `autoload.php` and directories of all third-party dependencies. Now we can call `require_once(__DIR__."/../vendor/autoload.php");` instead of having to define `spl_autoload_register`. This will autoload both our own and third-party dependencies.

