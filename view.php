<?php
// view.php
require_once 'config.php';
require_once 'NotesManager.php';

$notesManager = new NotesManager($pdo);
$note = null;
$error = '';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $note = $notesManager->getNote($_GET['id'], $_POST['master_password']);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Просмотр заметки</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <a href="index.php" class="btn btn-secondary mb-3">← Назад к списку</a>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!$note && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="alert alert-warning">Не удалось расшифровать заметку. Проверьте пароль.</div>
        <?php endif; ?>
        
        <?php if (!$note): ?>
            <h3>Введите мастер-пароль для расшифровки</h3>
            <form method="POST">
                <div class="mb-3">
                    <label for="master_password" class="form-label">Мастер-пароль</label>
                    <input type="password" class="form-control" name="master_password" required>
                </div>
                <button type="submit" class="btn btn-primary">Расшифровать</button>
            </form>
        <?php else: ?>
            <h2><?php echo htmlspecialchars($note['title']); ?></h2>
            <div class="card mt-3">
                <div class="card-body">
                    <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($note['content']); ?></p>
                </div>
                <div class="card-footer text-muted">
                    Создано: <?php echo $note['created_at']; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>