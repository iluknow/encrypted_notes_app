<?php
// NotesManager.php

class NotesManager {
    private $pdo;
    private $cipher = "aes-256-cbc"; // Алгоритм шифрования

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Шифрует текст с использованием мастер-пароля
     */
    private function encrypt($plaintext, $masterPassword) {
        // Генерируем случайный вектор инициализации
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher));
        
        // Шифруем текст
        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->cipher,
            $masterPassword,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        // Кодируем в base64 для хранения в БД
        return [
            'content' => base64_encode($ciphertext),
            'iv' => base64_encode($iv)
        ];
    }

    /**
     * Расшифровывает текст
     */
    private function decrypt($encryptedData, $iv, $masterPassword) {
        $ciphertext = base64_decode($encryptedData);
        $iv = base64_decode($iv);
        
        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $masterPassword,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return $plaintext;
    }

    /**
     * Создает новую заметку
     */
    public function createNote($title, $content, $masterPassword) {
        $encrypted = $this->encrypt($content, $masterPassword);
        
        $sql = "INSERT INTO notes (title, encrypted_content, iv) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$title, $encrypted['content'], $encrypted['iv']]);
    }

    /**
     * Получает список всех заметок (без расшифровки содержимого)
     */
    public function getAllNotes() {
        $sql = "SELECT id, title, created_at FROM notes ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получает и расшифровывает одну заметку
     */
    public function getNote($id, $masterPassword) {
        $sql = "SELECT * FROM notes WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($note) {
            $note['content'] = $this->decrypt(
                $note['encrypted_content'],
                $note['iv'],
                $masterPassword
            );
            
            if ($note['content'] === false) {
                throw new Exception("Неверный мастер-пароль!");
            }
        }
        
        return $note;
    }

    /**
     * Удаляет заметку
     */
    public function deleteNote($id) {
        $sql = "DELETE FROM notes WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
}
?>