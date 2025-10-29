<?php
session_start();
require_once("../conexion.php");
if(!isset($_SESSION['id_usuario']) || $_SESSION['rol']!=='admin'){ 
  header("Location: ../index.php"); 
  exit; 
}

$stmt = $pdo->query("SELECT * FROM carpetas WHERE id_padre IS NULL ORDER BY nombre ASC");
$areas = $stmt->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin - Dashboard</title>
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

.user-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.user-name {
  color: white;
  font-weight: 500;
}

.btn-logout {
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

.btn-logout:hover {
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
}

.form-control {
  border-radius: 25px;
  padding: 10px 20px;
  border: 1px solid #ddd;
}

.btn-primary {
  background: linear-gradient(135deg, #f5a3c7, #9b7cb8);
  border: none;
  color: white;
  font-weight: 500;
  border-radius: 25px;
  padding: 10px 25px;
  transition: 0.3s;
  white-space: nowrap;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #9b7cb8, #f5a3c7);
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(155, 124, 184, 0.3);
}

.area-card .card {
  cursor: pointer;
  transition: all 0.3s ease;
  height: 100%;
  position: relative;
}

.area-card .card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 20px rgba(155, 124, 184, 0.3);
}

.folder-icon {
  font-size: 4rem;
  margin: 20px 0 10px 0;
}

.area-card h5 {
  color: #9b7cb8;
  font-weight: 600;
  margin-bottom: 20px;
}

.dropdown {
  position: absolute;
  top: 10px;
  right: 10px;
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
  min-width: 150px;
}

.dropdown-item {
  padding: 10px 15px;
  transition: 0.3s;
}

.dropdown-item:hover {
  background: #f5c6d9;
}

.modal-content {
  border-radius: 15px;
  border: none;
}

.modal-content h5 {
  color: #9b7cb8;
  font-weight: 600;
  margin-bottom: 20px;
}

.btn-secondary {
  border-radius: 25px;
  padding: 8px 20px;
}

#areaMsg .alert {
  border-radius: 15px;
  margin-top: 15px;
}

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
  
  .btn-logout {
    padding: 6px 15px;
    font-size: 0.9rem;
  }
}
</style>
</head>
<body>

<div class="top-header">
  <div class="container-fluid">
    <h2>Panel - Áreas</h2>
    <div class="user-info">
      <span class="user-name"><?=htmlspecialchars($_SESSION['nombre'])?></span>
      <a href="../logout.php" class="btn-logout">Cerrar sesión</a>
    </div>
  </div>
</div>

<div class="container">

  <div class="card p-4 mb-4">
    <form id="formCrearArea" class="d-flex gap-2">
      <input name="nombre" class="form-control" placeholder="Nueva área (nombre)" required>
      <button class="btn btn-primary">Crear área</button>
    </form>
    <div id="areaMsg"></div>
  </div>

  <div class="row" id="areasGrid">
    <?php foreach($areas as $a): ?>
      <div class="col-lg-3 col-md-4 col-sm-6 mb-4 area-card" data-id="<?= $a['id_carpeta'] ?>">
        <div class="card p-3 text-center" 
             onclick="location.href='area.php?id=<?= $a['id_carpeta'] ?>'">

          <div class="dropdown" onclick="event.stopPropagation()">
            <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              ⋮
            </button>
            <ul class="dropdown-menu">
              <li>
                <a href="#" class="dropdown-item btn-edit-area" data-id="<?= $a['id_carpeta'] ?>">Editar</a>
              </li>
              <li>
                <a href="#" class="dropdown-item text-danger btn-del-area" data-id="<?= $a['id_carpeta'] ?>">Eliminar</a>
              </li>
            </ul>
          </div>

          <div class="folder-icon">📁</div>
          <h5><?=htmlspecialchars($a['nombre'])?></h5>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="modal fade" tabindex="-1" id="modalEdit">
  <div class="modal-dialog">
    <div class="modal-content p-4">
      <h5>Editar Área</h5>
      <input id="editName" class="form-control mb-3">
      <div class="d-flex justify-content-end">
        <button class="btn btn-secondary me-2" data-bs-dismiss="modal">Cerrar</button>
        <button id="saveEdit" class="btn btn-primary">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const base = "..";
document.getElementById('formCrearArea').addEventListener('submit', async e=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action','create');
  const res = await fetch('api/areas.php',{method:'POST',body:fd});
  const j = await res.json();
  document.getElementById('areaMsg').innerHTML = `<div class="alert alert-${j.success?'success':'danger'}">${j.message}</div>`;
  if(j.success) setTimeout(()=>location.reload(), 1000);
});

document.querySelectorAll('.btn-del-area').forEach(btn=>{
  btn.addEventListener('click', async (e)=> {
    e.preventDefault();
    if(!confirm('¿Eliminar área y todo su contenido?')) return;
    const id = btn.dataset.id;
    const res = await fetch('api/areas.php',{method:'POST',body:new URLSearchParams({action:'delete',id})});
    const j = await res.json();
    if(j.success) location.reload(); else alert(j.message);
  });
});

let editingId=null;
document.querySelectorAll('.btn-edit-area').forEach(b=>{
  b.addEventListener('click', (e)=>{
    e.preventDefault();
    e.stopPropagation();
    editingId=b.dataset.id;
    const modal = new bootstrap.Modal(document.getElementById('modalEdit'));
    modal.show();
    document.getElementById('editName').value = b.closest('.area-card').querySelector('h5').textContent.trim();
  });
});
document.getElementById('saveEdit').addEventListener('click', async ()=>{
  const name = document.getElementById('editName').value.trim();
  const res = await fetch('api/areas.php',{method:'POST',body:new URLSearchParams({action:'edit',id:editingId,nombre:name})});
  const j = await res.json();
  alert(j.message);
  if(j.success) location.reload();
});
</script>
</body>
</html>