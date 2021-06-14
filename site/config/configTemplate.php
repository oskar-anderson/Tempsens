<?php
//
//  Configuration
//

return [

   // Database
   'db_dev' => [
      'connectUrl' => 'mysql:dbname=NAME_HERE;host=IP_HERE;charset=utf8',
      'username' => '',
      'password' => ''
   ],
   'db_local_dev' => [
      'connectUrl' => 'mysql:dbname=NAME_HERE;host=localhost;charset=utf8',
      'username' => '',
      'password' => ''
   ],
   'webDbPassword' => 'PASSWORD FOR CRUD OPERATIONS',
   'db_active' => 'db_local_dev OR db_dev',
];

