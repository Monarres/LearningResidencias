<?php
session_start();
require_once("../conexion.php");

// Seguridad: solo accesible por administradores
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Obtener lista de usuarios
$usuarios = $pdo->query("SELECT id_usuario, nombre FROM usuarios ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// Obtener usuario seleccionado desde GET
$id_usuario = $_GET['id_usuario'] ?? null;
$respuestas = [];

if ($id_usuario) {
    $sql = "SELECT 
                v.titulo AS video,
                c.pregunta,
                ru.respuesta,
                c.respuesta_correcta,
                ru.fecha
            FROM respuestas_usuario ru
            JOIN cuestionarios c ON ru.id_cuestionario = c.id_cuestionario
            JOIN videos v ON c.id_video = v.id_video
            WHERE ru.id_usuario = ?
            ORDER BY v.id_video, c.id_cuestionario";

    $stmt = $pdo->prepare($sql);

    if (!$stmt) {
        die("❌ Error al preparar la consulta: " . implode(" - ", $pdo->errorInfo()));
    }

    if (!$stmt->execute([$id_usuario])) {
        die("❌ Error al ejecutar la consulta: " . implode(" - ", $stmt->errorInfo()));
    }

    $respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Respuestas de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h1 class="mb-4">📋 Respuestas de Usuarios</h1>

    <!-- Formulario para seleccionar usuario -->
    <form method="get" class="mb-4">
        <div class="row g-2 align-items-center">
            <div class="col-auto">
                <label for="id_usuario" class="col-form-label">Seleccionar usuario:</label>
            </div>
            <div class="col-auto">
                <select name="id_usuario" id="id_usuario" class="form-select" required>
                    <option value="">-- Selecciona --</option>
                    <?php foreach ($usuarios as $user): ?>
                        <option value="<?= $user['id_usuario'] ?>" <?= $id_usuario == $user['id_usuario'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Ver respuestas</button>
            </div>
        </div>
    </form>

    <!-- Mostrar respuestas -->
    <?php if ($id_usuario && $respuestas): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Video</th>
                        <th>Pregunta</th>
                        <th>Respuesta Dada</th>
                        <th>Correcta</th>
                        <th>¿Correcto?</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($respuestas as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['video']) ?></td>
                            <td><?= htmlspecialchars($r['pregunta']) ?></td>
                            <td><?= strtoupper($r['respuesta']) ?></td>
                            <td><?= strtoupper($r['respuesta_correcta']) ?></td>
                            <td><?= strtoupper($r['respuesta']) === strtoupper($r['respuesta_correcta']) ? '✅' : '❌' ?></td>
                            <td><?= htmlspecialchars($r['fecha']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($id_usuario): ?>
        <p class="text-warning">⚠ Este usuario aún no ha respondido ningún cuestionario.</p>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-secondary">⬅ Volver al Panel de Administración</a>
    </div>
</div>
</body>
</html>