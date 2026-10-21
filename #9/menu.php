<?php
include("conexion.php");
// Procesar la redirección antes de renderizar cualquier HTML
if (isset($_POST['btn_com'])) {
    header("Location: compra.php"); // es una función nativa e integrada de PHP.
    exit();
} else if (isset($_POST['btn_fac'])) {
    header("Location: factura.php");
    exit();
} else if (isset($_POST['btn_cli'])) {
    header("Location: cliente.php");
    exit();
} else if (isset($_POST['btn_prov'])) {
    header("Location: proveedor.php");
    exit();
} else if (isset($_POST['btn_emp'])) {
    header("Location: empleado.php");
    exit();
} else if (isset($_POST['btn_prod'])) {
    header("Location: producto.php");
    exit();
} else if (isset($_POST['btn_car'])) {
    header("Location: cargo.php");
    exit();
} else if (isset($_POST['btn_cat'])) {
    header("Location: categoria.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Navegación Principal</title>
</head>
<body>
    <h1>Sistema de Gestión Administrativa - El Progreso</h1>
    <h2>Menú Principal de Módulos</h2>
    <hr>
    <br>
    <!-- Dejar action="" envía los datos al mismo archivo PHP -->
    <form action="" method="POST">
        <table border="0" cellpadding="6" cellspacing="0">
            <tr>
                <td><button type="submit" name="btn_com" style="width: 120px; height: 40px;">Módulo de Pedidos</button></td>
                <td><button type="submit" name="btn_fac" style="width: 120px; height: 40px;">Módulo de Facturación</button></td>
            </tr>
            <tr>
                <td><button type="submit" name="btn_cli" style="width: 120px; height: 40px;">Gestión de Clientes</button></td>
                <td><button type="submit" name="btn_prov" style="width: 120px; height: 40px;">Gestión de Proveedores</button></td>
            </tr>
           <tr>
                <td><button type="submit" name="btn_emp" style="width: 120px; height: 40px;">Gestión de Empleados</button></td>
                <td><button type="submit" name="btn_prod" style="width: 120px; height: 40px;">Catálogo de Productos</button></td>
            </tr>
           <tr>
                <td><button type="submit" name="btn_car" style="width: 120px; height: 40px;">Gestión de Cargos</button></td>
                <td><button type="submit" name="btn_cat" style="width: 120px; height: 40px;">Categorías de Productos</button></td>
            </tr>
           </table>
    </form>

</body>
</html>