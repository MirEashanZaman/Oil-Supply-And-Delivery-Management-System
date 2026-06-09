<?php
session_start();
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/controllers/AuthController.php';

$controller = new AuthController(getDB());
$controller->forgotPassword();
