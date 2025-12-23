<?php
// create.php
require_once 'config.php';
require_once 'NotesManager.php';

$notesManager = new NotesManager($pdo);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $masterPassword = $_POST['master_password'] ?? '';
    
    if (empty($title) || empty($content) || empty($masterPassword)) {
        $error = 'Все поля обязательны для заполнения!';
    } else {
        if ($notesManager->createNote($title, $content, $masterPassword)) {
            $success = 'Заметка успешно создана и зашифрована!';
            $_POST = []; // Очищаем форму
        } else {
            $error = 'Ошибка при создании заметки.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создать заметку</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <a href="index.php" class="btn btn-secondary mb-3">← Назад к списку</a>
        <h2>Создать новую заметку</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="title" class="form-label">Заголовок</label>
                <input type="text" class="form-control" id="title" name="title" 
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="content" class="form-label">Текст заметки</label>
                <textarea class="form-control" id="content" name="content" rows="5" required><?php 
                    echo htmlspecialchars($_POST['content'] ?? '');
                ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="master_password" class="form-label">Мастер-пароль для шифрования</label>
                <input type="password" class="form-control" id="master_password" 
                       name="master_password" required>
                <div class="form-text">
                    ⚠️ Запомните этот пароль! Без него невозможно прочитать заметку.
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Зашифровать и сохранить</button>
        </form>
    </div>
</body>
</html>