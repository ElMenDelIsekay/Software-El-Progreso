<?php
/* ========== VALIDACIÓN DE LOGIN ========== */
include("config/conexion.php");

$nombre = $_POST["nombre"];
$pa = $_POST["pa"];

$stmt = $conn->prepare("SELECT * FROM usuario WHERE username = ? AND password = ?");
$stmt->bind_param("ss", $nombre, $pa);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();
    $rol = $usuario['rol'];
    header("Location: menu.php?rol=" . urlencode($rol));
    exit();
} else {
    echo "<script>
    alert('Usuario o contraseña incorrectos');
    window.location.href = 'index.html';
    </script>";
}

$stmt->close();
$conn->close();
?>