<?php 
session_start(); 
if (isset($_SESSION['id_usuario'])) {
  header('Location: ' . ($_SESSION['rol'] === 'admin' ? 'admin/dashboard.php' : 'usuario/dashboard.php'));
  exit;
} 
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Login - eLearning</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
  background: linear-gradient(135deg, #f5c6d9, #e8b4d4);
  font-family: 'Poppins', sans-serif;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.login-container {
  background: #fff;
  border-radius: 15px;
  box-shadow: 0 0 20px rgba(0,0,0,0.1);
  display: flex;
  overflow: hidden;
  width: 850px;
  max-width: 95%;
  margin: auto;
}
.login-left {
  background: linear-gradient(135deg, #9b7cb8, #b893cc);
  color: white;
  width: 45%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  padding: 40px 20px;
  text-align: center;
}
.login-left h2 {
  font-size: 28px;
  font-weight: 600;
}
.login-left img {
  width: 80%;
  max-width: 240px;
  margin-top: 20px;
}
.login-right {
  width: 55%;
  padding: 50px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.login-right h3 {
  color: #9b7cb8;
  font-weight: 600;
  margin-bottom: 25px;
  text-align: center;
}
.form-control {
  border-radius: 25px;
  padding: 12px 20px;
  border: 1px solid #ddd;
}
.btn-login {
  background: linear-gradient(135deg, #f5a3c7, #9b7cb8);
  border: none;
  color: white;
  font-weight: 500;
  border-radius: 25px;
  padding: 12px;
  transition: 0.3s;
}
.btn-login:hover {
  background: linear-gradient(135deg, #9b7cb8, #f5a3c7);
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(155, 124, 184, 0.3);
}
a {
  text-decoration: none;
  color: #9b7cb8;
  font-size: 0.9em;
}
a:hover {
  text-decoration: underline;
}
#msg {
  margin-bottom: 15px;
}
</style>
</head>
<body>
<div class="login-container">
  <div class="login-left">
    <h2>Baby ballet Marbet®</h2>
    <p>International Dancing Corporation</p>
    <img src="assets/images/mascotas_1.png" alt="login illustration">
  </div>
  <div class="login-right">
    <h3>Login</h3>
    <div id="msg"></div>
    <form id="loginForm">
      <div class="mb-3">
        <input name="email" type="email" class="form-control" placeholder="Username" required>
      </div>
      <div class="mb-3">
        <input name="password" type="password" class="form-control" placeholder="Password" required>
      </div>
      <button class="btn-login w-100" type="submit">Login</button>
    </form>
  </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async e=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  const res = await fetch('login.php',{method:'POST',body:fd});
  const data = await res.json();
  const msg = document.getElementById('msg');
  if(data.success){
    msg.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
    setTimeout(()=> location.href = data.redirect, 700);
  } else {
    msg.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
  }
});
</script>
</body>
</html>