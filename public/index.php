<?php
require_once __DIR__ . '/../controllers/TicketController.php';

$action = $_GET['action'] ?? 'index';
$controller = new TicketController();

switch ($action) {
    case 'create':
        $controller->create();
        break;
    case 'update-status':
        $controller->updateStatus();
        break;
    case 'index':
    default:
        $controller->index();
        break;
}