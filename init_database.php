<?php
// init_database.php - обновленная версия с поддержкой GCM
require_once 'config.php';

echo "<h3>Инициализация базы данных (AES-256-GCM)...</h3>";

try {
    // Создаем таблицу notes с полем tag для GCM
    $sql = "
    CREATE TABLE IF NOT EXISTS notes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        encrypted_content TEXT NOT NULL,
        iv TEXT NOT NULL,
        tag TEXT NOT NULL,  -- Новое поле для тега аутентификации GCM
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
    ";
    
    $pdo->exec($sql);
    
    // Проверяем, создана ли таблица
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='notes'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<p style='color: green;'>Таблица 'notes' успешно создана!</p>";
        
        // Проверяем структуру таблицы
        $stmt = $pdo->query("PRAGMA table_info(notes)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Структура таблицы (AES-256-GCM):</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Имя</th><th>Тип</th><th>NULL</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['cid']}</td>";
            echo "<td>{$col['name']}</td>";
            echo "<td>{$col['type']}</td>";
            echo "<td>{$col['notnull']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div class='alert alert-info mt-3'>";
        echo "<strong>Что нового в AES-256-GCM:</strong><br>";
        echo "• Тег аутентификации для проверки целостности данных<br>";
        echo "• Более высокая производительность<br>";
        echo "• Защита от подделки данных<br>";
        echo "• Рекомендуемый режим шифрования";
        echo "</div>";
        
    } else {
        echo "<p style='color: red;'> Ошибка: таблица не создана</p>";
    }
    
} catch (PDOException $e) {
    die("<p style='color: red;'> Ошибка при создании таблицы: " . $e->getMessage() . "</p>");
}

echo "<br><a href='index.php' class='btn btn-primary'>Перейти к списку заметок</a>";
echo " <a href='migrate.php' class='btn btn-warning'>Мигрировать старые заметки</a>";
?>