<?php
// view.php - с поддержкой CBC и GCM
require_once 'config.php';
require_once 'NotesManager.php';

$notesManager = new NotesManager($pdo);
$note = null;
$error = '';
$isLegacyNote = false;

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $note = $notesManager->getNote($_GET['id'], $_POST['master_password']);
    } catch (Exception $e) {
        $error = $e->getMessage();
        
        // Пробуем прочитать как старую заметку (CBC)
        if (strpos($error, 'старом формате') !== false) {
            require_once 'legacy_support.php';
            $legacyManager = new LegacyNotesManager($pdo);
            $note = $legacyManager->getLegacyNote($_GET['id'], $_POST['master_password']);
            
            if ($note) {
                $isLegacyNote = true;
                $error = ''; // Очищаем ошибку, если удалось прочитать
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Просмотр заметки</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn btn-secondary mb-3">← Назад к списку</a>
        
        <?php if ($error && !$isLegacyNote): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!$note && $_SERVER['REQUEST_METHOD'] === 'POST' && !$isLegacyNote): ?>
            <div class="alert alert-warning">Не удалось расшифровать заметку. Проверьте пароль.</div>
        <?php endif; ?>
        
        <?php if (!$note && !$isLegacyNote): ?>
            <h3>Введите мастер-пароль для расшифровки</h3>
            <form method="POST">
                <div class="mb-3">
                    <label for="master_password" class="form-label">Мастер-пароль</label>
                    <input type="password" class="form-control password-input" name="master_password" required>
                    <div class="form-text">
                        Для заметок в новом формате (AES-256-GCM) или старом (AES-256-CBC)
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-encrypted">Расшифровать</button>
            </form>
        <?php else: ?>
            <?php if ($isLegacyNote): ?>
                <div class="alert alert-warning">
                    <strong>Старая заметка (AES-256-CBC)</strong><br>
                    Рекомендуется <a href="migrate.php">мигрировать</a> на новый формат AES-256-GCM
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <strong>Заметка в новом формате (AES-256-GCM)</strong><br>
                    Используется аутентифицированное шифрование
                </div>
            <?php endif; ?>
            
            <h2><?php echo htmlspecialchars($note['title']); ?></h2>
            <div class="card mt-3">
                <div class="card-body">
                    <div class="note-content"><?php echo nl2br(htmlspecialchars($note['content'])); ?></div>
                </div>
                <div class="card-footer text-muted">
                    Создано: <?php echo $note['created_at']; ?>
                    <?php if ($isLegacyNote): ?>
                        | <span class="badge bg-warning">AES-256-CBC</span>
                    <?php else: ?>
                        | <span class="badge bg-success">AES-256-GCM</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($isLegacyNote): ?>
                <div class="mt-3">
                    <a href="migrate.php" class="btn btn-warning">Мигрировать на GCM</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>