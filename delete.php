<?php
// delete.php
require_once 'config.php';
require_once 'NotesManager.php';

if (isset($_GET['id'])) {
    $notesManager = new NotesManager($pdo);
    $notesManager->deleteNote($_GET['id']);
}

header('Location: index.php');
exit;
?>