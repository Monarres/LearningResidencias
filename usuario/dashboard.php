<?php
session_start();
require_once("../conexion.php");

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

$nombre = $_SESSION['nombre'] ?? "Usuario";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Usuario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .card-videos {
      max-width: 400px;
      margin: auto;
    }
  </style>
</head>
<body>
<div class="container mt-5">
  <h1 class="mb-5 text-center">👤 Bienvenido, <?= htmlspecialchars($nombre) ?></h1>

  <div class="card shadow-sm text-center card-videos">
    <div class="card-body">
      <h5 class="card-title">📚 Videos de Capacitación</h5>
      <p class="card-text">Mira los videos disponibles según tu área de formación.</p>
      <a href="videos_usuario.php" class="btn btn-primary">Ver videos</a>
    </div>
  </div>

  <div class="text-center mt-5">
    <a href="../logout.php" class="btn btn-danger btn-lg">🚪 Cerrar Sesión</a>
  </div>
</div>
</body>
</html>