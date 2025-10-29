<?php
session_start();
require_once("../conexion.php");

if(!isset($_SESSION['id_usuario']) || $_SESSION['rol']!=='admin'){
  header("Location: ../index.php");
  exit;
}

$id_area = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id_area <= 0){
  header("Location: dashboard.php");
  exit;
}

try {
  // Obtener info del área
  $stmt = $pdo->prepare("SELECT * FROM carpetas WHERE id_carpeta = ?");
  $stmt->execute([$id_area]);
  $area = $stmt->fetch(PDO::FETCH_ASSOC);
  if(!$area) {
    header("Location: dashboard.php");
    exit;
  }

  // Obtener módulos
  $stmt = $pdo->prepare("SELECT * FROM carpetas WHERE id_padre = ? ORDER BY nombre ASC");
  $stmt->execute([$id_area]);
  $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  // En producción deberías loguear el error. Aquí mostramos mensaje simple.
  die("Error al cargar datos: " . htmlspecialchars($e->getMessage()));
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Área - <?= htmlspecialchars($area['nombre']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* ---- Copiado / adaptado del estilo del dashboard para mantener consistencia ---- */
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0d5e8; font-family:'Poppins',sans-serif; min-height:100vh; padding-top:80px; }
.top-header { position:fixed; top:0; left:0; right:0; background:linear-gradient(135deg,#b893cc,#f5a3c7); box-shadow:0 2px 10px rgba(0,0,0,0.15); z-index:1000; padding:20px 0; }
.top-header .container-fluid { display:flex; justify-content:space-between; align-items:center; padding:0 30px; }
.top-header h2 { color:white; font-weight:600; margin:0; font-size:1.5rem; }
.user-info { display:flex; align-items:center; gap:15px; }
.user-name { color:white; font-weight:500; }
.btn-logout { background:white; border:none; color:#9b7cb8; font-weight:500; border-radius:25px; padding:8px 20px; transition:0.3s; text-decoration:none; display:inline-block; }
.btn-logout:hover { background:#f8f9fa; transform:translateY(-2px); box-shadow:0 5px 15px rgba(0,0,0,0.2); color:#9b7cb8; }

.container { max-width:1200px; padding:20px 15px; }
.card { border:none; border-radius:15px; box-shadow:0 2px 10px rgba(0,0,0,0.1); background:white; }
.form-control { border-radius:25px; padding:10px 20px; border:1px solid #ddd; }
.btn-primary {
  background: linear-gradient(135deg,#f5a3c7,#9b7cb8);
  border:none; color:white; font-weight:500; border-radius:25px; padding:10px 25px; transition:0.3s; white-space:nowrap;
}
.btn-primary:hover { background: linear-gradient(135deg,#9b7cb8,#f5a3c7); transform:translateY(-2px); box-shadow:0 5px 15px rgba(155,124,184,0.3); }

.folder-icon { font-size:4rem; margin:20px 0 10px 0; }
.area-card .card { cursor:pointer; transition:all 0.3s ease; height:100%; position:relative; }
.area-card .card:hover { transform:translateY(-5px); box-shadow:0 5px 20px rgba(155,124,184,0.3); }
.dropdown { position:absolute; top:10px; right:10px; }
.dropdown-toggle { background:transparent !important; border:none !important; color:#9b7cb8 !important; font-size:1.5rem; font-weight:bold; width:30px; height:30px; padding:0 !important; line-height:1; box-shadow:none !important; }
.dropdown-toggle::after { display:none !important; }
.dropdown-menu { border-radius:10px; border:none; box-shadow:0 2px 10px rgba(0,0,0,0.1); min-width:150px; z-index: 2000; }
.dropdown-item:hover { background:#f5c6d9; }

.navbar-area {
  background: linear-gradient(90deg, #ff69b4, #f52e98ff);
  border-radius: .5rem;
}
#modulosGrid .col-md-3 { display:flex; }
.folder-card { flex:1; }
.modal-content { border-radius:15px; border:none; }
.modal-content h5 { color:#9b7cb8; font-weight:600; margin-bottom:12px; }
.btn-secondary { border-radius:25px; padding:8px 20px; }

@media (max-width:768px){
  body { padding-top:70px; }
  .top-header .container-fluid { padding:0 15px; }
  .top-header h2 { font-size:1.2rem; }
  .user-name { display:none; }
  .btn-logout { padding:6px 15px; font-size:0.9rem; }
}
</style>
</head>
<body>

<!-- CABECERA FIJA (igual que dashboard) -->
<div class="top-header">
  <div class="container-fluid">
    <h2><span class="navbar-brand mb-0 h5 text-white">Área: <?= htmlspecialchars($area['nombre']) ?></span></h2>
    <div class="user-info">
      <a href="dashboard.php" class="btn btn-light btn-sm me-3">⬅ Volver al Dashboard</a>
      <span class="user-name"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Admin') ?></span>
      
    </div>
  </div>
</div>

<div class="container py-4">


  <!-- FORM CREAR MÓDULO (estilo del dashboard para coherencia) -->
  <div class="card p-3 mb-4 shadow-sm">
    <form id="formCrearModulo" class="d-flex align-items-center gap-2">
      <input name="nombre" class="form-control me-2" placeholder="Nuevo módulo" required>
      <button id="btnCrearModulo" class="btn btn-primary" type="submit">Crear módulo</button>
    </form>
    <div id="moduloMsg" class="mt-2"></div>
  </div>

  <!-- GRID DE MÓDULOS (cards iguales al dashboard) -->
  <div class="row" id="modulosGrid">
    <?php if(empty($modulos)): ?>
      <div class="col-12"><div class="card p-3">No hay módulos en esta área.</div></div>
    <?php endif; ?>

    <?php foreach($modulos as $m): ?>
      <div class="col-md-3 mb-3 area-card" data-id="<?= (int)$m['id_carpeta'] ?>">
        <div class="card p-3 text-center position-relative shadow-sm folder-card" onclick="location.href='carpeta.php?id=<?= (int)$m['id_carpeta'] ?>'" style="cursor:pointer;">
          <div class="dropdown" onclick="event.stopPropagation()">
            <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">⋮</button>
            <ul class="dropdown-menu">
              <li><a href="#" class="dropdown-item btn-edit-modulo" data-id="<?= (int)$m['id_carpeta'] ?>">Editar</a></li>
              <li><a href="#" class="dropdown-item text-danger btn-del-modulo" data-id="<?= (int)$m['id_carpeta'] ?>">Eliminar</a></li>
            </ul>
          </div>

          <div class="folder-icon">📁</div>
          <h5 class="mt-2"><?= htmlspecialchars($m['nombre']) ?></h5>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<!-- MODAL EDITAR MÓDULO (mismo look que dashboard) -->
<div class="modal fade" tabindex="-1" id="modalEdit">
  <div class="modal-dialog">
    <div class="modal-content p-3">
      <h5>Editar módulo</h5>
      <input id="editName" class="form-control mb-2" />
      <div class="d-flex justify-content-end">
        <button class="btn btn-secondary me-2" data-bs-dismiss="modal">Cerrar</button>
        <button id="saveEdit" class="btn btn-primary">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const areaId = <?= (int)$id_area ?>;

// Crear módulo
document.getElementById('formCrearModulo').addEventListener('submit', async function(e){
  e.preventDefault();
  const btn = document.getElementById('btnCrearModulo');
  btn.disabled = true;
  const fd = new FormData(e.target);
  fd.append('action','create');
  fd.append('id_padre', areaId);

  try {
    const res = await fetch('api/modulos.php', { method: 'POST', body: fd });
    const j = await res.json();
    document.getElementById('moduloMsg').innerHTML = `<div class="alert alert-${j.success ? 'success' : 'danger'}">${j.message}</div>`;
    if(j.success) setTimeout(()=> location.reload(), 800);
  } catch (err) {
    console.error(err);
    document.getElementById('moduloMsg').innerHTML = `<div class="alert alert-danger">Error de conexión</div>`;
  } finally {
    btn.disabled = false;
  }
});

// Delegation para editar/eliminar (más robusto)
document.getElementById('modulosGrid').addEventListener('click', function(e){
  // Eliminar
  const del = e.target.closest('.btn-del-modulo');
  if(del){
    e.preventDefault();
    e.stopPropagation();
    const id = parseInt(del.dataset.id, 10);
    if(!id) return Swal.fire('Error','ID inválido','error');
    return Swal.fire({
      title: '¿Eliminar módulo?',
      text: 'Esto también eliminará su contenido.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then(async (result) => {
      if(result.isConfirmed){
        try {
          const res = await fetch('api/modulos.php', {
            method: 'POST',
            body: new URLSearchParams({ action: 'delete', id })
          });
          const j = await res.json();
          Swal.fire({ icon: j.success ? 'success' : 'error', title: j.success ? '¡Eliminado!' : 'Error', text: j.message })
            .then(()=> { if(j.success) location.reload(); });
        } catch(err) {
          console.error(err);
          Swal.fire('Error','Error de conexión','error');
        }
      }
    });
  }

  // Editar
  const edit = e.target.closest('.btn-edit-modulo');
  if(edit){
    e.preventDefault();
    e.stopPropagation();
    const id = parseInt(edit.dataset.id, 10);
    if(!id) return Swal.fire('Error','ID inválido','error');

    const modalEl = document.getElementById('modalEdit');
    const modal = new bootstrap.Modal(modalEl);
    const currentName = edit.closest('.card').querySelector('h5')?.textContent.trim() || '';
    document.getElementById('editName').value = currentName;
    modalEl.dataset.editingId = id;
    modal.show();
  }
});

// Guardar edición desde modal
document.getElementById('saveEdit').addEventListener('click', async function(){
  const modalEl = document.getElementById('modalEdit');
  const id = parseInt(modalEl.dataset.editingId || 0, 10);
  const name = document.getElementById('editName').value.trim();
  if(!id || !name) return Swal.fire('Error','Nombre inválido','error');

  try {
    const res = await fetch('api/modulos.php', { method: 'POST', body: new URLSearchParams({ action: 'edit', id, nombre: name }) });
    const j = await res.json();
    Swal.fire({ icon: j.success ? 'success' : 'error', title: j.success ? '¡Módulo actualizado!' : 'Error', text: j.message })
      .then(()=> { if(j.success) location.reload(); });
  } catch (err) {
    console.error(err);
    Swal.fire('Error','Error de conexión','error');
  }
});
</script>
</body>
</html>
