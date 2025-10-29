<?php
session_start();
require_once("../conexion.php");

// Verificar admin
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$id_carpeta = $_GET['id'] ?? null;
if (!$id_carpeta) { 
    header("Location: dashboard.php"); 
    exit; 
}

// Obtener nombre del módulo
$stmt = $pdo->prepare("SELECT * FROM carpetas WHERE id_carpeta = ?");
$stmt->execute([$id_carpeta]);
$carpeta = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$carpeta) die("Módulo no encontrado");

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';

// Subir video
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo_video'])) {
    $titulo = trim($_POST['titulo_video']);
    $nombreArchivo = null;
    $rutaRelativa = null;

    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        // Validar que es un video
        $tiposPermitidos = ['video/mp4', 'video/avi', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/webm'];
        $tipoArchivo = $_FILES['video']['type'];
        
        if (!in_array($tipoArchivo, $tiposPermitidos)) {
            $mensaje = "Solo se permiten archivos de video (MP4, AVI, MOV, WEBM)";
            $tipo_mensaje = "danger";
        } else {
            // Crear directorio si no existe
            $dirDestino = "../assets/videos/";
            if (!file_exists($dirDestino)) {
                mkdir($dirDestino, 0777, true);
            }
            
            // Generar nombre único
            $extension = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
            $nombreArchivo = time() . "_" . uniqid() . "." . $extension;
            $ruta = $dirDestino . $nombreArchivo;
            $rutaRelativa = "assets/videos/" . $nombreArchivo;
            
            // Mover archivo
            if (move_uploaded_file($_FILES['video']['tmp_name'], $ruta)) {
                try {
                    // Insertar en la base de datos
                    $stmt = $pdo->prepare("INSERT INTO videos (id_carpeta, titulo, archivo, ruta) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$id_carpeta, $titulo, $nombreArchivo, $rutaRelativa]);
                    
                    $mensaje = "Video subido correctamente";
                    $tipo_mensaje = "success";
                    
                    // Redirigir para evitar reenvío del formulario
                    header("Location: carpeta.php?id=$id_carpeta&msg=success");
                    exit;
                } catch (PDOException $e) {
                    // Si hay error en BD, eliminar el archivo subido
                    if (file_exists($ruta)) {
                        unlink($ruta);
                    }
                    $mensaje = "Error al guardar en base de datos: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            } else {
                $mensaje = "Error al mover el archivo al servidor. Verifica permisos de la carpeta.";
                $tipo_mensaje = "danger";
            }
        }
    } else {
        $errores = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido en php.ini',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en disco',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida'
        ];
        
        $codigoError = $_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE;
        $mensaje = $errores[$codigoError] ?? 'Error desconocido al subir el archivo';
        $tipo_mensaje = "danger";
    }
}

// Mensaje de éxito desde redirect
if (isset($_GET['msg']) && $_GET['msg'] === 'success') {
    $mensaje = "✅ Video subido correctamente";
    $tipo_mensaje = "success";
}

// Obtener videos
$stmt = $pdo->prepare("SELECT * FROM videos WHERE id_carpeta = ? ORDER BY id_video DESC");
$stmt->execute([$id_carpeta]);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Módulo <?= htmlspecialchars($carpeta['nombre']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: #f0d5e8;
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      padding-top: 80px;
    }
    
    /* Header fijo */
    .top-header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      background: linear-gradient(135deg, #b893cc, #f5a3c7);
      box-shadow: 0 2px 10px rgba(0,0,0,0.15);
      z-index: 1000;
      padding: 20px 0;
    }

    .top-header .container-fluid {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 30px;
    }

    .top-header h2 {
      color: white;
      font-weight: 600;
      margin: 0;
      font-size: 1.5rem;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .user-name {
      color: white;
      font-weight: 500;
    }

    .btn-volver {
      background: white;
      border: none;
      color: #9b7cb8;
      font-weight: 500;
      border-radius: 25px;
      padding: 8px 20px;
      transition: 0.3s;
      text-decoration: none;
      display: inline-block;
    }

    .btn-volver:hover {
      background: #f8f9fa;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
      color: #9b7cb8;
    }

    .container {
      max-width: 1200px;
      padding: 20px 15px;
    }

    .card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      background: white;
      overflow: visible;
      position: relative;
    }

    .card-upload {
      border: 2px dashed #9b7cb8;
      background: linear-gradient(135deg, rgba(155, 124, 184, 0.05) 0%, rgba(245, 163, 199, 0.05) 100%);
    }

    .form-control {
      border-radius: 25px;
      padding: 10px 20px;
      border: 1px solid #ddd;
    }

    .btn-primary-custom {
      background: linear-gradient(135deg, #f5a3c7, #9b7cb8);
      border: none;
      color: white;
      font-weight: 500;
      border-radius: 25px;
      padding: 10px 25px;
      transition: 0.3s;
      white-space: nowrap;
    }

    .btn-primary-custom:hover {
      background: linear-gradient(135deg, #9b7cb8, #f5a3c7);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(155, 124, 184, 0.3);
    }

    .video-card {
      transition: all 0.3s ease;
    }

    .video-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 20px rgba(155, 124, 184, 0.3);
    }

    .video-card h5 {
      color: #9b7cb8;
      font-weight: 600;
    }

    video {
      border-radius: 10px;
      background: #000;
    }

    /* Menú de 3 puntos */
    .dropdown {
      position: absolute;
      top: 10px;
      right: 10px;
      z-index: 100;
    }

    .dropdown-toggle {
      background: transparent !important;
      border: none !important;
      color: #9b7cb8 !important;
      font-size: 1.5rem;
      font-weight: bold;
      width: 30px;
      height: 30px;
      padding: 0 !important;
      line-height: 1;
      box-shadow: none !important;
    }

    .dropdown-toggle::after {
      display: none !important;
    }

    .dropdown-toggle:hover {
      background: #f8f9fa !important;
      border-radius: 50%;
    }

    .dropdown-toggle:focus {
      box-shadow: none !important;
      outline: none !important;
    }

    .dropdown-menu {
      border-radius: 10px;
      border: none;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      min-width: 180px;
    }

    .dropdown-item {
      padding: 10px 15px;
      transition: 0.3s;
    }

    .dropdown-item:hover {
      background: #f5c6d9;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #9b7cb8;
    }

    .empty-state .icon {
      font-size: 80px;
      opacity: 0.3;
    }

    .alert {
      border-radius: 15px;
      border: none;
    }

    .badge {
      background: linear-gradient(135deg, #f5a3c7, #9b7cb8);
      font-weight: 500;
      padding: 8px 15px;
      border-radius: 20px;
    }

    .progress {
      border-radius: 15px;
      overflow: hidden;
    }

    .progress-bar {
      background: linear-gradient(135deg, #f5a3c7, #9b7cb8);
    }

    .file-info {
      display: none;
      margin-top: 10px;
      padding: 10px;
      background: #f5c6d9;
      border-radius: 10px;
      font-size: 14px;
      color: #9b7cb8;
    }

    .progress-container {
      display: none;
      margin-top: 15px;
    }

    .info-box {
      background: white;
      border-left: 4px solid #9b7cb8;
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 20px;
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .section-header h4 {
      color: #9b7cb8;
      font-weight: 600;
      margin: 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
      body {
        padding-top: 70px;
      }
      
      .top-header .container-fluid {
        padding: 0 15px;
      }
      
      .top-header h2 {
        font-size: 1.2rem;
      }
      
      .user-name {
        display: none;
      }
      
      .btn-volver {
        padding: 6px 15px;
        font-size: 0.9rem;
      }
    }
  </style>
</head>
<body>

<!-- Header fijo superior -->
<div class="top-header">
  <div class="container-fluid">
    <h2>📂 <?= htmlspecialchars($carpeta['nombre']) ?></h2>
    <div class="header-actions">
      <span class="user-name"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Admin') ?></span>
      <a href="area.php?id=<?= $carpeta['id_padre'] ?>" class="btn-volver">
        ⬅ Volver
      </a>
    </div>
  </div>
</div>

<div class="container">
  <!-- Mensajes de alerta -->
  <?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($mensaje) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Subir video -->
  <div class="card card-upload mb-4">
    <div class="card-body p-4">
      <h5 class="mb-3" style="color: #9b7cb8; font-weight: 600;">
        🎬 Subir nuevo video
      </h5>
      <form method="post" enctype="multipart/form-data" id="formSubirVideo">
        <div class="row g-3">
          <div class="col-md-5">
            <label class="form-label fw-bold">Título del video</label>
            <input type="text" name="titulo_video" class="form-control" placeholder="Ej: Introducción al tema" required>
          </div>
          <div class="col-md-5">
            <label class="form-label fw-bold">Archivo de video</label>
            <input type="file" name="video" id="inputVideo" accept="video/*" class="form-control" required>
            <div class="file-info" id="fileInfo"></div>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary-custom w-100">
              Subir
            </button>
          </div>
        </div>
        
        <!-- Barra de progreso -->
        <div class="progress-container" id="progressContainer">
          <div class="progress" style="height: 25px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                 id="progressBar" 
                 role="progressbar" 
                 style="width: 0%;">
              0%
            </div>
          </div>
          <small class="text-muted mt-2 d-block">Subiendo video, por favor espera...</small>
        </div>
      </form>
    </div>
  </div>

  <!-- Información del servidor -->
  <div class="info-box">
    <strong style="color: #9b7cb8;">ℹ️ Información:</strong> 
    Tamaño máximo: <strong><?= ini_get('upload_max_filesize') ?></strong> | 
    Formatos: <strong>MP4, AVI, MOV, WEBM</strong> |
    Carpeta: <code>assets/videos/</code>
  </div>

  <!-- Listado de videos -->
  <div class="section-header">
    <h4>Videos del módulo</h4>
    <span class="badge">
      <?= count($videos) ?> video<?= count($videos) != 1 ? 's' : '' ?>
    </span>
  </div>
  
  <?php if (count($videos) > 0): ?>
    <div class="row">
      <?php foreach ($videos as $video): ?>
        <div class="col-md-6 mb-4">
          <div class="card video-card">
            <!-- Menú de tres puntos -->
            <div class="dropdown" onclick="event.stopPropagation()">
              <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                ⋮
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <a href="#" class="dropdown-item btn-edit" 
                     data-id="<?= $video['id_video'] ?>" 
                     data-title="<?= htmlspecialchars($video['titulo']) ?>">
                    Editar título
                  </a>
                </li>
                <li>
                  <a href="eliminar_video.php?id=<?= $video['id_video'] ?>&carpeta=<?= $id_carpeta ?>" 
                     class="dropdown-item text-danger" 
                     onclick="return confirm('¿Seguro que deseas eliminar este video?\n\n⚠️ Esta acción no se puede deshacer.')">
                    Eliminar
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <a href="cuestionario.php?id=<?= $video['id_video'] ?>" class="dropdown-item">
                    Gestionar cuestionario
                  </a>
                </li>
              </ul>
            </div>

            <div class="card-body">
              <h5 class="mb-3">
                <?= htmlspecialchars($video['titulo']) ?>
              </h5>
              <?php if ($video['ruta'] && file_exists("../" . $video['ruta'])): ?>
                <video src="../<?= htmlspecialchars($video['ruta']) ?>" 
                       width="100%" 
                       controls 
                       controlsList="nodownload"
                       preload="metadata">
                  Tu navegador no soporta el elemento de video.
                </video>
              <?php else: ?>
                <div class="alert alert-warning mb-0">
                  ⚠️ Video no encontrado
                </div>
              <?php endif; ?>
              <div class="d-flex justify-content-between align-items-center mt-3">
                <small class="text-muted">
                  ID: <?= $video['id_video'] ?>
                </small>
                <?php if (!empty($video['archivo'])): ?>
                  <small class="text-muted">
                    📁 <?= htmlspecialchars($video['archivo']) ?>
                  </small>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <div class="icon">🎬</div>
      <h4>No hay videos en este módulo</h4>
      <p class="text-muted">Sube tu primer video usando el formulario de arriba</p>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Mostrar información del archivo seleccionado
document.getElementById('inputVideo').addEventListener('change', function(e) {
  const file = e.target.files[0];
  const fileInfo = document.getElementById('fileInfo');
  
  if (file) {
    const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
    fileInfo.innerHTML = `
      <strong>📹 Archivo seleccionado:</strong><br>
      Nombre: ${file.name}<br>
      Tamaño: ${sizeMB} MB<br>
      Tipo: ${file.type}
    `;
    fileInfo.style.display = 'block';
  } else {
    fileInfo.style.display = 'none';
  }
});

// Mostrar progreso al enviar
document.getElementById('formSubirVideo').addEventListener('submit', function(e) {
  const file = document.getElementById('inputVideo').files[0];
  if (file) {
    const progressContainer = document.getElementById('progressContainer');
    progressContainer.style.display = 'block';
    
    let progress = 0;
    const interval = setInterval(() => {
      progress += 5;
      if (progress > 95) {
        clearInterval(interval);
        document.getElementById('progressBar').style.width = '95%';
        document.getElementById('progressBar').textContent = '95%';
      } else {
        document.getElementById('progressBar').style.width = progress + '%';
        document.getElementById('progressBar').textContent = progress + '%';
      }
    }, 200);
  }
});

// Editar título de video
document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.addEventListener('click', async e => {
    e.preventDefault();
    const id = btn.dataset.id;
    const currentTitle = btn.dataset.title;
    const nuevo = prompt("✏️ Editar título del video:", currentTitle);
    
    if (nuevo && nuevo.trim() !== "" && nuevo !== currentTitle) {
      try {
        const formData = new FormData();
        formData.append("id_video", id);
        formData.append("titulo", nuevo.trim());

        const res = await fetch("editar_video.php", { 
          method: "POST", 
          body: formData 
        });
        
        const json = await res.json();

        if (json.success) {
          alert("✅ " + json.message);
          location.reload();
        } else {
          alert("❌ Error: " + json.message);
        }
      } catch (err) {
        alert("❌ Error de conexión: " + err.message);
        console.error(err);
      }
    }
  });
});
</script>
</body>
</html>