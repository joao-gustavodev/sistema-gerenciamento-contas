<?php
require_once __DIR__ . '/../models/Ticket.php';

class TicketController {
    private $ticketModel;

    public function __construct() {
        $this->ticketModel = new Ticket();
    }

    public function index() {
        $tickets = $this->ticketModel->getAll();
        require_once __DIR__ . '/../views/index.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'] ?? '';
            $requester = $_POST['requester'] ?? '';
            $priority = $_POST['priority'] ?? 'Média';
            $description = $_POST['description'] ?? '';

            if (!empty($title) && !empty($requester) && !empty($description)) {
                $this->ticketModel->create($title, $requester, $priority, $description);
                header('Location: index.php');
                exit;
            }
        }
        require_once __DIR__ . '/../views/create.php';
    }

    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $status = $_POST['status'] ?? null;

            if ($id && $status) {
                $this->ticketModel->updateStatus($id, $status);
            }
            header('Location: index.php');
            exit;
        }
    }
}