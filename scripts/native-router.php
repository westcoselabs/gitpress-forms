<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$root = dirname(__DIR__) . '/.runtime/wordpress';
if ($path !== '/' && is_file($root . $path)) { return false; }
if (str_starts_with($path, '/wp-json/')) { $_GET['rest_route'] = substr($path, strlen('/wp-json')); }
require $root . '/index.php';
