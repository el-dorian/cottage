<?php

use app\priv\Info;
use yii\db\Connection;

require_once dirname(__DIR__) . '/priv/Info.php';

return [
    'class' => Connection::class,
    'dsn' => 'mysql:host=localhost;dbname=u1025225mf_oblepiha',
    'username' => Info::DB_LOGIN1,
    'password' => Info::DB_PASSWORD1,
    'charset' => 'utf8',
];