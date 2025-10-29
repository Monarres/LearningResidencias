<?php
session_start();
require_once("../conexion.php");

// Verificar admin
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$id_video = $_GET['id'] ?? null;
if (!$id_video) { header("Location: dashboard.php"); exit; }

// Obtener info del video
$stmt = $pdo->prepare("SELECT * FROM videos WHERE id_video = ?");
$stmt->execute([$id_video]);
$video = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$video) die("Video no encontrado");

// Eliminar pregunta
if (isset($_GET['eliminar'])) {
    $id_q = $_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM cuestionarios WHERE id_cuestionario = ?");
    $stmt->execute([$id_q]);
    header("Location: cuestionario.php?id=$id_video");
    exit;
}

// Insertar nueva pregunta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'nueva') {
    $pregunta = trim($_POST['pregunta']);
    $opA = trim($_POST['opcion_a']);
    $opB = trim($_POST['opcion_b']);
    $opC = trim($_POST['opcion_c']);
    $correcta = $_POST['respuesta_correcta'];

    $stmt = $pdo->prepare("INSERT INTO cuestionarios (id_video, pregunta, opcion_a, opcion_b, opcion_c, respuesta_correcta) 
    VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id_video, $pregunta, $opA, $opB, $opC, $correcta]);

    header("Location: cuestionario.php?id=$id_video");
    exit;
}

// Editar pregunta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    $id_q = $_POST['id_cuestionario'];
    $pregunta = trim($_POST['pregunta']);
    $opA = trim($_POST['opcion_a']);
    $opB = trim($_POST['opcion_b']);
    $opC = trim($_POST['opcion_c']);
    $correcta = $_POST['respuesta_correcta'];

    $stmt = $pdo->prepare("UPDATE cuestionarios SET pregunta=?, opcion_a=?, opcion_b=?, opcion_c=?, respuesta_correcta=? WHERE id_cuestionario=?");
    $stmt->execute([$pregunta, $opA, $opB, $opC, $correcta, $id_q]);

    header("Location: cuestionario.php?id=$id_video");
    exit;
}

// Obtener cuestionarios del video
$stmt = $pdo->prepare("SELECT * FROM cuestionarios WHERE id_video = ? ORDER BY creado DESC");
$stmt->execute([$id_video]);
$cuestionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si se quiere editar
$editando = null;
if (isset($_GET['editar'])) {
    $id_q = $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM cuestionarios WHERE id_cuestionario = ?");
    $stmt->execute([$id_q]);
    $editando = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cuestionario de <?= htmlspecialchars($video['titulo']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body>
<div class="container mt-4">
  <h2>📝 Cuestionario para el video: <b><?= htmlspecialchars($video['titulo']) ?></b></h2>
  <a href="carpeta.php?id=<?= $video['id_carpeta'] ?>" class="btn btn-secondary mb-3">⬅ Volver</a>

  <!-- Formulario -->
  <div class="card mb-4">
    <div class="card-body">
      <?php if ($editando): ?>
        <h5>✏ Editar pregunta</h5>
        <form method="post">
          <input type="hidden" name="accion" value="editar">
          <input type="hidden" name="id_cuestionario" value="<?= $editando['id_cuestionario'] ?>">
          <div class="mb-2">
            <textarea name="pregunta" class="form-control" required><?= htmlspecialchars($editando['pregunta']) ?></textarea>
          </div>
          <div class="mb-2">
            <input type="text" name="opcion_a" class="form-control" value="<?= htmlspecialchars($editando['opcion_a']) ?>" required>
          </div>
          <div class="mb-2">
            <input type="text" name="opcion_b" class="form-control" value="<?= htmlspecialchars($editando['opcion_b']) ?>" required>
          </div>
          <div class="mb-2">
            <input type="text" name="opcion_c" class="form-control" value="<?= htmlspecialchars($editando['opcion_c']) ?>" required>
          </div>
          <div class="mb-2">
            <label>Respuesta correcta:</label>
            <select name="respuesta_correcta" class="form-select" required>
              <option value="A" <?= $editando['respuesta_correcta']=='A'?'selected':'' ?>>Opción A</option>
              <option value="B" <?= $editando['respuesta_correcta']=='B'?'selected':'' ?>>Opción B</option>
              <option value="C" <?= $editando['respuesta_correcta']=='C'?'selected':'' ?>>Opción C</option>
            </select>
          </div>
          <button class="btn btn-warning">Actualizar</button>
          <a href="cuestionario.php?id=<?= $id_video ?>" class="btn btn-secondary">Cancelar</a>
        </form>
      <?php else: ?>
        <h5>➕ Agregar nueva pregunta</h5>
        <form method="post">
          <input type="hidden" name="accion" value="nueva">
          <div class="mb-2">
            <textarea name="pregunta" class="form-control" placeholder="Escribe la pregunta" required></textarea>
          </div>
          <div class="mb-2">
            <input type="text" name="opcion_a" class="form-control" placeholder="Opción A" required>
          </div>
          <div class="mb-2">
            <input type="text" name="opcion_b" class="form-control" placeholder="Opción B" required>
          </div>
          <div class="mb-2">
            <input type="text" name="opcion_c" class="form-control" placeholder="Opción C" required>
          </div>
          <div class="mb-2">
            <label>Respuesta correcta:</label>
            <select name="respuesta_correcta" class="form-select" required>
              <option value="A">Opción A</option>
              <option value="B">Opción B</option>
              <option value="C">Opción C</option>
            </select>
          </div>
          <button class="btn btn-primary">Guardar pregunta</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Lista de preguntas -->
  <h4>Preguntas agregadas</h4>
  <?php foreach ($cuestionarios as $q): ?>
    <div class="card mb-2 p-3 shadow-sm">
      <p><b>Pregunta:</b> <?= htmlspecialchars($q['pregunta']) ?></p>
      <ul>
        <li>A) <?= htmlspecialchars($q['opcion_a']) ?></li>
        <li>B) <?= htmlspecialchars($q['opcion_b']) ?></li>
        <li>C) <?= htmlspecialchars($q['opcion_c']) ?></li>
      </ul>
      <p><b>Respuesta correcta:</b> <?= $q['respuesta_correcta'] ?></p>
      <a href="cuestionario.php?id=<?= $id_video ?>&editar=<?= $q['id_cuestionario'] ?>" class="btn btn-warning btn-sm">✏ Editar</a>
      <a href="cuestionario.php?id=<?= $id_video ?>&eliminar=<?= $q['id_cuestionario'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta pregunta?')">🗑 Eliminar</a>
    </div>
  <?php endforeach; ?>
</div>
</body>
</html>
