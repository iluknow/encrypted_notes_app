<?php
// config.php

// Настройки для SQLite
$databaseFile = __DIR__ . '/database/notes.db';
$dsn = "sqlite:" . $databaseFile;

try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>
<?php
// init_database.php (запустить один раз)

require_once 'config.php';

$sql = "
CREATE TABLE IF NOT EXISTS notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    encrypted_content TEXT NOT NULL,
    iv TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
";

try {
    $pdo->exec($sql);
    echo "База данных успешно инициализирована!";
    
    // Создадим папку для базы данных, если её нет
    if (!is_dir('database')) {
        mkdir('database');
    }
} catch (PDOException $e) {
    die("Ошибка создания таблицы: " . $e->getMessage());
}
?>