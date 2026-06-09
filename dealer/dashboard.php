<?php
session_start();
require_once __DIR__.'/../includes/config.php';
requireLogin(); requireRole('dealer');
require_once __DIR__.'/../controllers/DealerController.php';

$controller = new DealerController(getDB());
$controller->dashboard();
