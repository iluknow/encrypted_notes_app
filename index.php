<?php
// index.php
require_once 'config.php';
require_once 'NotesManager.php';

$notesManager = new NotesManager($pdo);
$notes = $notesManager->getAllNotes();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои зашифрованные заметки</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">🔐 Мои зашифрованные заметки</h1>
        
        <a href="create.php" class="btn btn-primary mb-4">+ Создать новую заметку</a>
        
        <?php if (empty($notes)): ?>
            <div class="alert alert-info">У вас пока нет заметок. Создайте первую!</div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($notes as $note): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5><?php echo htmlspecialchars($note['title']); ?></h5>
                                <small class="text-muted">
                                    Создано: <?php echo $note['created_at']; ?>
                                </small>
                            </div>
                            <div>
                                <a href="view.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    👁️ Просмотреть
                                </a>
                                <a href="delete.php?id=<?php echo $note['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Удалить заметку?')">
                                    🗑️ Удалить
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>