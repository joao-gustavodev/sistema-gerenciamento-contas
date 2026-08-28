<?php
require_once __DIR__ . '/../config/database.php';

class Ticket {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM tickets ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create($title, $requester, $priority, $description) {
        $sql = "INSERT INTO tickets (title, requester, priority, description) VALUES (:title, :requester, :priority, :description)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':title' => htmlspecialchars(strip_tags($title)),
            ':requester' => htmlspecialchars(strip_tags($requester)),
            ':priority' => $priority,
            ':description' => htmlspecialchars(strip_tags($description))
        ]);
    }

    public function updateStatus($id, $status) {
        $sql = "UPDATE tickets SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':status' => $status,
            ':id' => $id
        ]);
    }
}