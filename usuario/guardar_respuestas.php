<?php
session_start(); require_once("../conexion.php");
if(!isset($_SESSION['id_usuario'])){ header("Location: ../index.php"); exit; }
$id_usuario = $_SESSION['id_usuario'];
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['resp']) && is_array($_POST['resp'])){
  foreach($_POST['resp'] as $id_cuestionario => $respuesta){
    $id_cuestionario = (int)$id_cuestionario;
    $respuesta = strtoupper(trim($respuesta));
    $stmt = $pdo->prepare("SELECT respuesta_correcta FROM cuestionarios WHERE id_cuestionario = ?");
    $stmt->execute([$id_cuestionario]); $correcta = $stmt->fetchColumn();
    $ok = ($correcta === $respuesta)?1:0;
    // evitar duplicados: si ya respondió esa pregunta, saltar o actualizar (aquí insertamos siempre)
    $pdo->prepare("INSERT INTO respuestas_usuario (id_usuario,id_cuestionario,respuesta,correcta) VALUES (?,?,?,?)")
        ->execute([$id_usuario,$id_cuestionario,$respuesta,$ok]);
  }
}
header("Location: videos_usuario.php");
exit;
