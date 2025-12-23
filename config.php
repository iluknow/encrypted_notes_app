<?php
// config.php - с проверкой поддержки GCM
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Проверяем поддержку GCM
if (!in_array('aes-256-gcm', openssl_get_cipher_methods())) {
    die("<div class='alert alert-danger container mt-5'>
        <h3> Требуется обновление OpenSSL</h3>
        <p>Ваша версия PHP не поддерживает AES-256-GCM, который требуется для этого приложения.</p>
        <p><strong>Решение:</strong></p>
        <ul>
            <li>Обновите PHP до версии 7.1+</li>
            <li>Убедитесь, что OpenSSL версии 1.0.1+</li>
            <li>Или используйте старую версию приложения с CBC</li>
        </ul>
        <p>Текущая версия PHP: " . PHP_VERSION . "</p>
        <p>Доступные методы шифрования: " . implode(', ', openssl_get_cipher_methods()) . "</p>
    </div>");
}

// Создаем папку database, если её нет
if (!is_dir('database')) {
    mkdir('database', 0777, true);
}

// Путь к базе данных SQLite
$databaseFile = __DIR__ . '/database/notes.db';

try {
    // Подключаемся к SQLite
    $dsn = "sqlite:" . $databaseFile;
    $pdo = new PDO($dsn);
    
    // Устанавливаем атрибуты PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Включаем поддержку внешних ключей для SQLite
    $pdo->exec("PRAGMA foreign_keys = ON");
    $pdo->exec("PRAGMA encoding = 'UTF-8'");
    
    // Проверяем наличие поля tag (для совместимости)
    try {
        $stmt = $pdo->query("SELECT tag FROM notes LIMIT 1");
    } catch (Exception $e) {
        // Поле tag не существует, нужно обновить БД
        echo "<div class='alert alert-warning container mt-3'>
                <strong>ℹ Требуется обновление базы данных</strong><br>
                Запустите <a href='init_database.php'>скрипт инициализации</a> для обновления структуры.
              </div>";
    }
    
} catch (PDOException $e) {
    die("<div class='alert alert-danger container mt-5'>
            <h3>Ошибка подключения к базе данных</h3>
            <p>" . $e->getMessage() . "</p>
            <p>Проверьте права на запись в папку проекта</p>
        </div>");
}

// Автоматическое создание таблицы при первом запуске
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='notes'");
    $tableExists = $stmt->fetchColumn();
    
    if (!$tableExists) {
        // Показываем сообщение, но не создаем автоматически
        echo "<div class='alert alert-info container mt-3'>
                <strong>База данных не инициализирована</strong><br>
                Запустите <a href='init_database.php'>скрипт инициализации</a> для создания таблиц.
              </div>";
    }
} catch (Exception $e) {
    // Игнорируем ошибки при проверке таблицы
}
?>