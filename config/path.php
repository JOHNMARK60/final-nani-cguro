<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . "/classes/Appointments.php";
// Add these lines at the bottom of path.php
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../helpers/encrypt_helper.php';
