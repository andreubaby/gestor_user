<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$users = \Illuminate\Support\Facades\DB::connection('mysql_fichajes')
    ->select("SELECT name, email FROM users WHERE email IN ('anam4575@gmail.com','angelantonmartinez82@gmail.com')");

foreach ($users as $u) {
    echo $u->name . PHP_EOL;
    echo 'HEX: ' . bin2hex($u->name) . PHP_EOL;
    echo '---' . PHP_EOL;
}
