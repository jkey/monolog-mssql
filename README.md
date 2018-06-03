monolog-mssql
=============

Microsoft SQL Server Handler for Monolog, which allows to store log messages in a MSSQL Table.
It can log text messages to a specific table.
The class further allows to use extra attributes as database field, which are can be used for later analyzing and sorting.

This is a fork from [waza-ari/monolog-mysql](https://github.com/waza-ari/monolog-mysql), with some changes.
The table and columns are not created or removed automatically.

# Installation
monolog-mssql is available via composer. Just add the following line to your required section in composer.json and 
do a `php composer.phar update`.

```
"jkey/monolog-mysql": ">1.0.0"
```

After that you have to create your database table. We need the following structure, which you are free to extend.

```tsql
CREATE TABLE myLog
  (
    id         BIGINT        NOT NULL
                             IDENTITY(1,1)
                             PRIMARY KEY,
    channel    NVARCHAR(255) NOT NULL,
    level      INT           NOT NULL,
    message    NTEXT         NOT NULL,
    time       INT           NOT NULL
  );
```

# Usage
Just use it as any other Monolog Handler, push it to the stack of your Monolog Logger instance. 
The Handler however needs some parameters:

- **$pdo** PDO Instance of your database. Pass along the PDO instantiation of your database connection with 
your database selected.
- **$table** The table name where the logs should be stored
- **$additionalFields** simple array of additional database fields, which should be stored in the database. 
The fields can later be used in the extra context section of a record. See examples below. _Defaults to an empty array()
- **$level** can be any of the standard Monolog logging levels. Use Monologs statically defined contexts. _
Defaults to Logger::DEBUG_
- **$bubble** _Defaults to true

# Examples
Given that $pdo is your database instance, you could use the class as follows:

```php
<?php
require __DIR__ . '/../vendor/autoload.php';
//Import class
use Monolog\Logger;
use Jkey\Monolog\Handler\MSSQLHandler;

$pdo = new PDO('sqlsrv:Server=localhost;Database=log', 'myuser', 'mypass');

//Create logger
$log = new Logger('name');
$log->pushHandler(new MSSQLHandler($pdo, "log", array('username', 'userid'), \Monolog\Logger::DEBUG));

//Now you can use the logger, and further attach additional information
$log->addWarning("This is a great message, woohoo!", array('username'  => 'John Doe', 'userid'  => 245));
```

# License
This tool is free software and is distributed under the MIT license. Please have a look at the LICENSE file for further information.
