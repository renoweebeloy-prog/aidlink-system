DROP DATABASE IF EXISTS aidlink;
CREATE DATABASE aidlink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aidlink;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(120) NOT NULL,
    email VARCHAR(140) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    role ENUM('admin', 'staff', 'citizen') NOT NULL DEFAULT 'citizen',
    avatar VARCHAR(255) NULL,
    theme ENUM('dark', 'light') NOT NULL DEFAULT 'dark',
    security_question VARCHAR(255) NULL,
    security_answer VARCHAR(255) NULL,
    security_answer_plain TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    quantity VARCHAR(80) NOT NULL DEFAULT 'Not specified',
    urgency ENUM('Low', 'Medium', 'High', 'Emergency') NOT NULL DEFAULT 'Medium',
    location VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Preparing', 'Delivering', 'Completed', 'Rejected') NOT NULL DEFAULT 'Pending',
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    recipient_deleted_at DATETIME NULL,
    admin_deleted_at DATETIME NULL,
    staff_deleted_at DATETIME NULL,
    CONSTRAINT fk_service_requests_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE message_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('queued', 'acknowledged') NOT NULL DEFAULT 'queued',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at TIMESTAMP NULL,
    recipient_deleted_at DATETIME NULL,
    admin_deleted_at DATETIME NULL,
    staff_deleted_at DATETIME NULL,
    CONSTRAINT fk_message_queue_request
        FOREIGN KEY (request_id) REFERENCES service_requests(id)
        ON DELETE CASCADE
);

CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(140) NOT NULL,
    body TEXT NOT NULL,
    link VARCHAR(255) NOT NULL DEFAULT 'dashboard.php',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    is_group TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_conversations_creator
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE conversation_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    last_read_message_id INT NOT NULL DEFAULT 0,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_conversation_member (conversation_id, user_id),
    CONSTRAINT fk_conversation_members_conversation
        FOREIGN KEY (conversation_id) REFERENCES conversations(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_conversation_members_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_chat_messages_conversation
        FOREIGN KEY (conversation_id) REFERENCES conversations(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_chat_messages_sender
        FOREIGN KEY (sender_id) REFERENCES users(id)
        ON DELETE CASCADE
);

INSERT INTO users (fullname, email, password, phone, role, theme, security_question, security_answer) VALUES
('AidLink Administrator', 'admin@aidlink.local', '$2y$12$iv2ZOaR7x64430F1xN36mOtcz6s8R30pocCnPK2wNZyGise2QYcGq', '09123456789', 'admin', 'dark', 'What is your AidLink recovery word?', '$2y$12$iv2ZOaR7x64430F1xN36mOtcz6s8R30pocCnPK2wNZyGise2QYcGq'),
('Volunteer Coordinator', 'staff@aidlink.local', '$2y$12$iv2ZOaR7x64430F1xN36mOtcz6s8R30pocCnPK2wNZyGise2QYcGq', '09987654321', 'staff', 'dark', 'What is your AidLink recovery word?', '$2y$12$iv2ZOaR7x64430F1xN36mOtcz6s8R30pocCnPK2wNZyGise2QYcGq'),
('Sample Recipient', 'recipient@aidlink.local', '$2y$12$iv2ZOaR7x64430F1xN36mOtcz6s8R30pocCnPK2wNZyGise2QYcGq', '09111222333', 'citizen', 'light', 'What is your AidLink recovery word?', '$2y$12$iv2ZOaR7x64430F1xN36mOtcz6s8R30pocCnPK2wNZyGise2QYcGq');

INSERT INTO service_requests (user_id, category, quantity, urgency, location, description, status, remarks) VALUES
(3, 'Food Assistance', '5 food packs', 'High', 'Community Pantry Point', 'Family needs food packs and basic groceries for this week.', 'Pending', 'Queued for aid assessment.'),
(3, 'Medicine Assistance', 'Maintenance medicine support', 'Medium', 'Aid Distribution Center', 'Request for medicine assistance and coordination guidance.', 'Approved', 'Coordinator is reviewing the request.');

INSERT INTO message_queue (request_id, message, status) VALUES
(1, 'New aid request received for review.', 'queued'),
(2, 'Aid request status changed to Approved.', 'acknowledged');

INSERT INTO system_logs (activity) VALUES
('Default accounts and sample records have been prepared.'),
('Queue records are ready for review.');


INSERT INTO notifications (user_id, title, body, link) VALUES
(1, 'Aid queue ready', 'There are aid requests waiting for review.', 'requests.php'),
(2, 'Aid queue ready', 'There are aid requests waiting for review.', 'requests.php'),
(3, 'Request update', 'Your sample aid request is now being reviewed.', 'requests.php');

INSERT INTO conversations (title, is_group, created_by) VALUES
('Aid Coordination Team', 1, 1),
('Recipient Assistance', 0, 1);

INSERT INTO conversation_members (conversation_id, user_id) VALUES
(1, 1), (1, 2), (2, 1), (2, 3);

INSERT INTO chat_messages (conversation_id, sender_id, body) VALUES
(1, 1, 'Good day. Please monitor new aid requests and keep updates concise.'),
(1, 2, 'Noted. I will acknowledge queued records after review.'),
(2, 1, 'Hello, your request has been received by the aid coordination desk.');
