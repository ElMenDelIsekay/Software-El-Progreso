<?php
include("conexion.php");
$nombre = $_POST["nombre"];
$pa = $_POST["pa"];
// 1. Preparar la consulta SQL buscando en la tabla usuario
$stmt = $conn->prepare("SELECT * FROM usuario WHERE username = ? AND password = ?");
// 2. Vincular las variables a los parámetros ("ss" significa que ambos son de tipo String)
$stmt->bind_param("ss", $nombre, $pa);
// 3. Ejecutar la consulta
$stmt->execute();
// 4. Obtener el resultado de la búsqueda
$resultado = $stmt->get_result();
// 5. Validar si encontró coincidencia
if ($resultado->num_rows > 0) {
    // Si existe el usuario y la contraseña, abre la página del menu principal
    header("Location: menu.php");
    exit();
} else {
    // cuando no encuentra, redirige de nuevo al inicio muestra mensaje js
    echo "<script>
    alert('Usuario o contraseña incorrectos');
    window.location.href = 'index.html';
    </script>";
}
// 6. Cerrar conexiones
$stmt->close();
$conn->close();
?>