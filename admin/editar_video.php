<?php
session_start();
require_once("../conexion.php");

header('Content-Type: application/json');

// Verificar admin
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id_video = (int)($_POST['id_video'] ?? 0);
$titulo = trim($_POST['titulo'] ?? '');

if (!$id_video || !$titulo) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE videos SET titulo = ? WHERE id_video = ?");
    $stmt->execute([$titulo, $id_video]);
    
    echo json_encode(['success' => true, 'message' => 'Título actualizado correctamente']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
}
?>