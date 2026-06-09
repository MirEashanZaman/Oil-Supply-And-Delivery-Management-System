<?php
session_start();
require_once __DIR__.'/../includes/config.php';
requireLogin(); requireRole('supplier');
require_once __DIR__.'/../controllers/SupplierController.php';

$controller = new SupplierController(getDB());
$controller->dashboard();
