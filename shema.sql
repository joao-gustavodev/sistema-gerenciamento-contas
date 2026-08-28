CREATE DATABASE IF NOT EXISTS helpdesk_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE helpdesk_db;

CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    requester VARCHAR(100) NOT NULL,
    priority ENUM('Baixa', 'Média', 'Alta') DEFAULT 'Média',
    status ENUM('Aberto', 'Em Andamento', 'Fechado') DEFAULT 'Aberto',
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);