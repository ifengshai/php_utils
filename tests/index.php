<?php
ini_set('display_errors',1); //错误信息
ini_set('display_startup_errors',1); //php启动错误信息
error_reporting(-1); //打印出所有的 错误信息
require_once __DIR__ . '/../vendor/autoload.php';















var_dump(fs_guid());