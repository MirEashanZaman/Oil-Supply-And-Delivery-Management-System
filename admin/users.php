<?php
session_start();
require_once __DIR__.'/../includes/config.php';
requireLogin(); requireRole('admin');
require_once __DIR__.'/../controllers/AdminController.php';

$controller = new AdminController(getDB());
$controller->users();
