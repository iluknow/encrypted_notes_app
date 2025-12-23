<?php
// init_database.php - разместить в корне проекта
require_once 'config.php';

echo "<h3>Инициализация базы данных...</h3>";

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS notes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        encrypted_content TEXT NOT NULL,
        iv TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ Таблица 'notes' успешно создана!</p>";
    
    echo "<br><a href='index.php' class='btn btn-primary'>Перейти к списку заметок</a>";
    
} catch (PDOException $e) {
    die("<p style='color: red;'> Ошибка при создании таблицы: " . $e->getMessage() . "</p>");
}
?>