<?php
session_start();
header('Content-Type: application/json');
require_once("conexion.php");

if($_SERVER['REQUEST_METHOD']!=='POST'){
  echo json_encode(['success'=>false,'message'=>'Método no permitido']); exit;
}
$email = trim($_POST['email'] ?? '');
$pass = $_POST['password'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();
if($user && password_verify($pass, $user['contrasena'])){
  $_SESSION['id_usuario'] = $user['id_usuario'];
  $_SESSION['rol'] = $user['rol'];
  $_SESSION['nombre'] = $user['nombre'];
  echo json_encode(['success'=>true,'message'=>"Bienvenido {$user['nombre']}",'redirect'=> $user['rol']==='admin' ? 'admin/dashboard.php' : 'usuario/dashboard.php']);
} else {
  echo json_encode(['success'=>false,'message'=>'Correo o contraseña incorrectos']);
}
