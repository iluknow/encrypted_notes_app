<?php
// migrate.php - скрипт для миграции старых заметок CBC → GCM
require_once 'config.php';
require_once 'NotesManager.php';

// Проверяем, есть ли старые заметки без тега
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notes WHERE tag = '' OR tag IS NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $oldNotesCount = $result['count'];
} catch (Exception $e) {
    $oldNotesCount = 0;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Миграция на GCM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1 class="mb-4"> Миграция на AES-256-GCM</h1>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <?php
            $masterPassword = $_POST['master_password'] ?? '';
            $notesManager = new NotesManager($pdo);
            
            if (empty($masterPassword)) {
                echo '<div class="alert alert-danger">Введите мастер-пароль</div>';
            } else {
                try {
                    // Получаем все заметки без тега
                    $stmt = $pdo->query("SELECT id, title FROM notes WHERE tag = '' OR tag IS NULL");
                    $oldNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $migrated = 0;
                    $failed = 0;
                    
                    foreach ($oldNotes as $note) {
                        try {
                            $notesManager->migrateNoteToGCM($note['id'], $masterPassword);
                            $migrated++;
                        } catch (Exception $e) {
                            $failed++;
                            echo '<div class="alert alert-warning">Ошибка при миграции заметки "' . 
                                 htmlspecialchars($note['title']) . '": ' . 
                                 htmlspecialchars($e->getMessage()) . '</div>';
                        }
                    }
                    
                    echo '<div class="alert alert-success">';
                    echo "<h4> Миграция завершена!</h4>";
                    echo "Успешно: $migrated заметок<br>";
                    echo "Не удалось: $failed заметок<br>";
                    echo '</div>';
                    
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">Ошибка: ' . 
                         htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
            ?>
            <a href="index.php" class="btn btn-primary">Вернуться к заметкам</a>
            
        <?php else: ?>
            <div class="alert alert-info">
                <h4>ℹ️ О миграции</h4>
                <p>Этот процесс перешифрует ваши заметки из старого формата (AES-256-CBC) в новый (AES-256-GCM).</p>
                <p><strong>Найдено старых заметок: <?php echo $oldNotesCount; ?></strong></p>
                <p class="mb-0">Для продолжения введите мастер-пароль:</p>
            </div>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="master_password" class="form-label">Мастер-пароль</label>
                    <input type="password" class="form-control password-input" 
                           id="master_password" name="master_password" required>
                    <div class="form-text">
                         Тот же пароль, который использовался при создании заметок
                    </div>
                </div>
                
                <div class="alert alert-warning">
                    <strong> Внимание!</strong><br>
                    • Не закрывайте страницу во время миграции<br>
                    • Убедитесь, что пароль правильный<br>
                    • Сделайте бэкап базы данных перед началом
                </div>
                
                <button type="submit" class="btn btn-warning btn-lg">Начать миграцию</button>
                <a href="index.php" class="btn btn-secondary">Отмена</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>