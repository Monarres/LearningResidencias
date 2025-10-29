<?php
session_start(); require_once("../conexion.php");
if(!isset($_SESSION['id_usuario'])){ header("Location: ../index.php"); exit; }
$id_usuario = $_SESSION['id_usuario'];
// traer videos ordenados por carpeta y creación; aquí mostraremos secuencial por carpeta -> simplifico mostrando por id_video asc
$stmt = $pdo->query("SELECT v.*, c.nombre AS carpeta_nombre FROM videos v JOIN carpetas c ON v.id_carpeta = c.id_carpeta ORDER BY v.id_video ASC");
$videos = $stmt->fetchAll();
$puede=true;
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Videos</title>
<link href="../assets/css/style.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body>
<div class="container mt-4">
  <h3>Videos</h3>
  <?php foreach($videos as $v): 
    // contar respuestas de usuario para las preguntas de ese video
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM respuestas_usuario ru JOIN cuestionarios q ON ru.id_cuestionario=q.id_cuestionario WHERE ru.id_usuario=? AND q.id_video=?");
    $stmt->execute([$id_usuario,$v['id_video']]); $cnt = $stmt->fetchColumn();
    // cuantas preguntas tiene video
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM cuestionarios WHERE id_video=?"); $stmt2->execute([$v['id_video']]); $totalQ = $stmt2->fetchColumn();
    $completo = ($totalQ>0 && $cnt >= $totalQ);
  ?>
    <div class="card mb-3 p-3">
      <h5><?=htmlspecialchars($v['titulo'])?> <small class="text-muted">/ <?=htmlspecialchars($v['carpeta_nombre'])?></small></h5>
      <?php if($puede): ?>
        <video class="w-100 mb-2" controls><source src="../<?=htmlspecialchars($v['ruta'])?>" type="video/mp4"></video>
        <?php
          // mostrar cuestionario (preguntas)
          $stmtQ = $pdo->prepare("SELECT * FROM cuestionarios WHERE id_video=?");
          $stmtQ->execute([$v['id_video']]); $pregs = $stmtQ->fetchAll();
        ?>
        <?php if($completo): ?>
          <div class="alert alert-success">✅ Cuestionario completado</div>
        <?php elseif(count($pregs)>0): ?>
          <form method="post" action="guardar_respuestas.php">
            <?php foreach($pregs as $p): ?>
              <div class="mb-2"><strong><?=htmlspecialchars($p['pregunta'])?></strong>
                <div class="form-check"><input class="form-check-input" required type="radio" name="resp[<?=$p['id_cuestionario']?>]" value="A"><label class="form-check-label"><?=htmlspecialchars($p['opcion_a'])?></label></div>
                <div class="form-check"><input class="form-check-input" required type="radio" name="resp[<?=$p['id_cuestionario']?>]" value="B"><label class="form-check-label"><?=htmlspecialchars($p['opcion_b'])?></label></div>
                <div class="form-check"><input class="form-check-input" required type="radio" name="resp[<?=$p['id_cuestionario']?>]" value="C"><label class="form-check-label"><?=htmlspecialchars($p['opcion_c'])?></label></div>
              </div>
            <?php endforeach; ?>
            <input type="hidden" name="id_video" value="<?=$v['id_video']?>">
            <button class="btn btn-primary">Enviar respuestas</button>
          </form>
        <?php else: ?>
          <div class="alert alert-warning">Este video no tiene cuestionario.</div>
        <?php endif; ?>
      <?php else: ?>
        <div class="alert alert-secondary">🔒 Debes completar el video/anterior para desbloquear.</div>
      <?php endif; ?>
    </div>
  <?php 
    if(!$completo) $puede=false; // bloquear los siguientes si no completó
  endforeach; ?>
</div>
</body></html>
