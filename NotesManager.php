<?php
// NotesManager.php - обновленная версия с GCM

class NotesManager {
    private $pdo;
    private $cipher = "aes-256-gcm"; // Меняем CBC на GCM
    private $tagLength = 16; // Длина тега аутентификации для GCM

    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Проверяем поддержку GCM
        if (!in_array('aes-256-gcm', openssl_get_cipher_methods())) {
            throw new Exception("Ваш PHP не поддерживает AES-256-GCM. Обновите OpenSSL.");
        }
    }

    /**
     * Шифрует текст с использованием мастер-пароля (AES-256-GCM)
     */
    private function encrypt($plaintext, $masterPassword) {
        // Генерируем случайный IV (12 байт рекомендуется для GCM)
        $iv = openssl_random_pseudo_bytes(12);
        
        // Переменная для хранения тега аутентификации
        $tag = '';
        
        // Шифруем текст с GCM
        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->cipher,
            $masterPassword,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '', // Дополнительные данные (AAD) - не используем
            $this->tagLength
        );
        
        if ($ciphertext === false) {
            throw new Exception("Ошибка при шифровании: " . openssl_error_string());
        }
        
        return [
            'content' => base64_encode($ciphertext),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag)
        ];
    }

    /**
     * Расшифровывает текст (AES-256-GCM)
     */
    private function decrypt($encryptedData, $iv, $tag, $masterPassword) {
        $ciphertext = base64_decode($encryptedData);
        $iv = base64_decode($iv);
        $tag = base64_decode($tag);
        
        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $masterPassword,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        return $plaintext;
    }

    /**
     * Создает новую заметку
     */
    public function createNote($title, $content, $masterPassword) {
        $encrypted = $this->encrypt($content, $masterPassword);
        
        $sql = "INSERT INTO notes (title, encrypted_content, iv, tag) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $title, 
            $encrypted['content'], 
            $encrypted['iv'], 
            $encrypted['tag']
        ]);
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
            // Проверяем, есть ли поле tag (для совместимости со старыми заметками)
            if (!isset($note['tag']) || empty($note['tag'])) {
                throw new Exception("Заметка зашифрована в старом формате. Перешифруйте её.");
            }
            
            $note['content'] = $this->decrypt(
                $note['encrypted_content'],
                $note['iv'],
                $note['tag'],
                $masterPassword
            );
            
            if ($note['content'] === false) {
                throw new Exception("Неверный мастер-пароль или поврежденные данные!");
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

    /**
     * Перешифровывает заметку с CBC в GCM (для миграции)
     */
    public function migrateNoteToGCM($id, $masterPassword) {
        // Получаем старую заметку (CBC)
        $sql = "SELECT * FROM notes WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$note) {
            throw new Exception("Заметка не найдена");
        }
        
        // Проверяем, не зашифрована ли уже в GCM
        if (isset($note['tag']) && !empty($note['tag'])) {
            throw new Exception("Заметка уже зашифрована в GCM");
        }
        
        // Временная функция для дешифрования CBC (для миграции)
        $decryptCBC = function($encryptedData, $iv, $masterPassword) {
            $ciphertext = base64_decode($encryptedData);
            $iv = base64_decode($iv);
            
            $plaintext = openssl_decrypt(
                $ciphertext,
                'aes-256-cbc',
                $masterPassword,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            return $plaintext;
        };
        
        // Дешифруем старую заметку (CBC)
        $plaintext = $decryptCBC(
            $note['encrypted_content'],
            $note['iv'],
            $masterPassword
        );
        
        if ($plaintext === false) {
            throw new Exception("Неверный мастер-пароль для миграции!");
        }
        
        // Шифруем в GCM
        $encrypted = $this->encrypt($plaintext, $masterPassword);
        
        // Обновляем в базе данных
        $sql = "UPDATE notes SET encrypted_content = ?, iv = ?, tag = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $encrypted['content'],
            $encrypted['iv'],
            $encrypted['tag'],
            $id
        ]);
    }
}
?>