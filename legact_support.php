<?php
// legacy_support.php - поддержка старых заметок (опционально)
class LegacyNotesManager {
    // Старая версия с CBC для чтения мигрированных заметок
    private $pdo;
    private $cipher = "aes-256-cbc";

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getLegacyNote($id, $masterPassword) {
        $sql = "SELECT * FROM notes WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($note && (empty($note['tag']) || $note['tag'] === 'LEGACY_CBC')) {
            // Дешифрование CBC для старых заметок
            $ciphertext = base64_decode($note['encrypted_content']);
            $iv = base64_decode($note['iv']);
            
            $plaintext = openssl_decrypt(
                $ciphertext,
                $this->cipher,
                $masterPassword,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($plaintext !== false) {
                $note['content'] = $plaintext;
                $note['legacy'] = true; // Помечаем как старую заметку
                return $note;
            }
        }
        
        return null;
    }
}
?>