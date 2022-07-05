<?php

use app\priv\Info;
use yii\db\Connection;

require_once dirname(__DIR__) . '/priv/Info.php';

return [
    'class' => Connection::class,
    'dsn' => 'mysql:host=localhost;dbname=u1025225mf_snt',
    'username' => Info::DB_USER,
    'password' => Info::DB_PASSWORD,
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
