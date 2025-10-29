<?php
session_start();
require_once("../conexion.php");

// Verificar si es admin
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$mensaje = "";

// 🔹 Insertar nuevo usuario
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['accion']) && $_POST['accion'] === "agregar") {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $contrasena = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];

    if (!empty($nombre) && !empty($email) && !empty($_POST['password'])) {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, contrasena, rol) 
                               VALUES (:nombre, :email, :contrasena, :rol)");
        try {
            $stmt->execute([
                'nombre' => $nombre,
                'email' => $email,
                'contrasena' => $contrasena,
                'rol' => $rol
            ]);
            header("Location: usuarios.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Violación de restricción (ej: email único)
                $mensaje = "El correo ya está registrado. Intenta con otro.";
            } else {
                $mensaje = "Error al insertar usuario: " . $e->getMessage();
            }
        }
    } else {
        $mensaje = "Todos los campos son obligatorios.";
    }
}

//  Editar usuario
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['accion']) && $_POST['accion'] === "editar") {
    $id = (int) $_POST['id_usuario'];
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $rol = $_POST['rol'];

    if (!empty($_POST['password'])) {
        $contrasena = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET nombre = :nombre, email = :email, contrasena = :contrasena, rol = :rol WHERE id_usuario = :id";
        $params = ['nombre' => $nombre, 'email' => $email, 'contrasena' => $contrasena, 'rol' => $rol, 'id' => $id];
    } else {
        $sql = "UPDATE usuarios SET nombre = :nombre, email = :email, rol = :rol WHERE id_usuario = :id";
        $params = ['nombre' => $nombre, 'email' => $email, 'rol' => $rol, 'id' => $id];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        header("Location: usuarios.php");
        exit;
    } catch (PDOException $e) {
        $mensaje = " Error al actualizar usuario: " . $e->getMessage();
    }
}

// 🔹 Eliminar usuario
if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];
    try {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = :id");
        $stmt->execute(['id' => $id]);
        header("Location: usuarios.php");
        exit;
    } catch (PDOException $e) {
        $mensaje = " Error al eliminar usuario: " . $e->getMessage();
    }
}

// 🔹 Listar usuarios
$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY id_usuario DESC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestionar Usuarios</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-4">
  <h1 class="mb-4">Gestión de Usuarios</h1>

  <!-- Mensajes -->
  <?php if (!empty($mensaje)): ?>
    <div class="alert alert-warning"><?= $mensaje ?></div>
  <?php endif; ?>

  <!-- Formulario para agregar -->
  <form method="POST" class="mb-4">
    <input type="hidden" name="accion" value="agregar">
    <div class="row g-2">
      <div class="col-md-3">
        <input type="text" name="nombre" class="form-control" placeholder="Nombre" required>
      </div>
      <div class="col-md-3">
        <input type="email" name="email" class="form-control" placeholder="Correo" required>
      </div>
      <div class="col-md-3">
        <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
      </div>
      <div class="col-md-2">
        <select name="rol" class="form-select" required>
          <option value="admin">Administrador</option>
          <option value="Diseno">Diseño</option>
          <option value="coor_academica">Coordinacion academica</option>
          <option value="coor_administrativa">Coordinacion administrativa</option>
          <option value="profesores">Profesores</option>
        </select>
      </div>
      <div class="col-md-1">
        <button class="btn btn-success w-100" type="submit">➕</button>
      </div>
    </div>
  </form>

  <!-- Tabla de usuarios -->
  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>#</th> <!-- Contador virtual -->
        <th>Nombre</th>
        <th>Email</th>
        <th>Rol</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php $i=1; foreach ($usuarios as $u): ?>
      <tr>
        <td><?= $i++ ?></td> <!-- Aquí va el contador -->
        <td><?= htmlspecialchars($u['nombre']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><?= $u['rol'] ?></td>
        <td>
          <button class="btn btn-sm btn-warning"
                  data-bs-toggle="modal"
                  data-bs-target="#modalEditar"
                  data-id="<?= $u['id_usuario'] ?>"
                  data-nombre="<?= htmlspecialchars($u['nombre']) ?>"
                  data-email="<?= htmlspecialchars($u['email']) ?>"
                  data-rol="<?= $u['rol'] ?>">
            Editar
          </button>
          <a href="usuarios.php?eliminar=<?= $u['id_usuario'] ?>" class="btn btn-danger btn-sm"
             onclick="return confirm('¿Eliminar este usuario?');">Eliminar</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <a href="dashboard.php" class="btn btn-secondary">⬅ Volver al Dashboard</a>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="accion" value="editar">
      <input type="hidden" name="id_usuario" id="edit-id">
      <div class="modal-header">
        <h5 class="modal-title">Editar Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-3">
        <div class="col-md-12">
          <label class="form-label">Nombre</label>
          <input type="text" class="form-control" name="nombre" id="edit-nombre" required>
        </div>
        <div class="col-md-12">
          <label class="form-label">Correo</label>
          <input type="email" class="form-control" name="email" id="edit-email" required>
        </div>
        <div class="col-md-12">
          <label class="form-label">Nueva Contraseña (opcional)</label>
          <input type="password" class="form-control" name="password" placeholder="Dejar vacío para no cambiar">
        </div>
        <div class="col-md-12">
          <label class="form-label">Rol</label>
          <select name="rol" id="edit-rol" class="form-select" required>
            <option value="usuario">Usuario</option>
            <option value="admin">Administrador</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-warning">Actualizar</button>
      </div>
    </form>
  </div>
</div>

<script>
// Pasar datos al modal
const modalEditar = document.getElementById('modalEditar');
modalEditar.addEventListener('show.bs.modal', event => {
  const button = event.relatedTarget;
  document.getElementById('edit-id').value = button.getAttribute('data-id');
  document.getElementById('edit-nombre').value = button.getAttribute('data-nombre');
  document.getElementById('edit-email').value = button.getAttribute('data-email');
  document.getElementById('edit-rol').value = button.getAttribute('data-rol');
});
</script>
</body>
</html>
