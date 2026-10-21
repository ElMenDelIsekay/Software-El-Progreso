<?php
include("conexion.php");

if (!isset($conn) && isset($conexion)) {
    $conn = $conexion;
}

$mensaje = "";
$proveedor_editar = null;
$busqueda = "";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 1. Eliminar Proveedor
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_prov'])) {
    $id_eliminar = $_GET['id_prov'];

    try {
        $sql_del = "DELETE FROM proveedor WHERE id_prov = ?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->bind_param("i", $id_eliminar);
        $stmt_del->execute();

        $mensaje = "<p style='color: green; font-weight: bold;'>Proveedor con ID " . htmlspecialchars($id_eliminar) . " eliminado correctamente.</p>";
        $stmt_del->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<p style='color: red; font-weight: bold;'>Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// 2. Cargar datos para editar
if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_prov'])) {
    $id_buscar = $_GET['id_prov'];

    try {
        $sql_buscar = "SELECT * FROM proveedor WHERE id_prov = ?";
        $stmt_buscar = $conn->prepare($sql_buscar);
        $stmt_buscar->bind_param("i", $id_buscar);
        $stmt_buscar->execute();
        $res = $stmt_buscar->get_result();
        $proveedor_editar = $res->fetch_assoc();
        $stmt_buscar->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<p style='color: red; font-weight: bold;'>Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// 3. Procesar formularios (Guardar y Actualizar)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Guardar nuevo proveedor
    if (isset($_POST["guardar"])) {
        $id_prov  = $_POST["id_prov"];
        $nit      = $_POST["nit"];
        $nombre   = $_POST["nombre"];
        $telefono = $_POST["telefono"];
        $correo   = $_POST["correo"];

        try {
            $sql = "INSERT INTO proveedor (id_prov, nit, nombre, telefono, correo) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issss", $id_prov, $nit, $nombre, $telefono, $correo);
            $stmt->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Proveedor registrado correctamente.</p>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error: El ID " . htmlspecialchars($id_prov) . " ya está registrado.</p>";
            } else {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error al registrar: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }

    // Actualizar proveedor
    if (isset($_POST["actualizar"])) {
        $id_prov  = $_POST["id_prov"];
        $nit      = $_POST["nit"];
        $nombre   = $_POST["nombre"];
        $telefono = $_POST["telefono"];
        $correo   = $_POST["correo"];

        try {
            $sql_up = "UPDATE proveedor SET nit = ?, nombre = ?, telefono = ?, correo = ? WHERE id_prov = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("ssssi", $nit, $nombre, $telefono, $correo, $id_prov);
            $stmt_up->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Proveedor actualizado correctamente.</p>";
            $stmt_up->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<p style='color: red; font-weight: bold;'>Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// 4. Buscar y listar proveedores
if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $param_busqueda = "%" . $busqueda . "%";

    $sql_listar = "SELECT * FROM proveedor WHERE id_prov LIKE ? OR nit LIKE ? OR nombre LIKE ? OR telefono LIKE ? OR correo LIKE ?";
    $stmt_list = $conn->prepare($sql_listar);
    $stmt_list->bind_param("sssss", $param_busqueda, $param_busqueda, $param_busqueda, $param_busqueda, $param_busqueda);
    $stmt_list->execute();
    $proveedores = $stmt_list->get_result();
} else {
    $sql_listar = "SELECT * FROM proveedor ORDER BY id_prov ASC";
    $proveedores = $conn->query($sql_listar);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Proveedores</title>
</head>
<body>

    <?php echo $mensaje; ?>

    <!-- Formulario Editar -->
    <?php if ($proveedor_editar): ?>
        <h2>Editar Proveedor</h2>
        <form action="" method="POST">
            <input type="hidden" name="id_prov" value="<?php echo htmlspecialchars($proveedor_editar['id_prov']); ?>">

            <label>ID Proveedor:</label><br>
            <input type="number" value="<?php echo htmlspecialchars($proveedor_editar['id_prov']); ?>" disabled><br><br>

            <label>NIT:</label><br>
            <input type="text" name="nit" value="<?php echo htmlspecialchars($proveedor_editar['nit']); ?>" required><br><br>

            <label>Nombre Proveedor:</label><br>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($proveedor_editar['nombre']); ?>" required><br><br>

            <label>Teléfono:</label><br>
            <input type="text" name="telefono" value="<?php echo htmlspecialchars($proveedor_editar['telefono']); ?>" required><br><br>

            <label>Correo:</label><br>
            <input type="email" name="correo" value="<?php echo htmlspecialchars($proveedor_editar['correo']); ?>"><br><br>

            <button type="submit" name="actualizar">Actualizar Proveedor</button>
            <a href="?">Cancelar</a>
        </form>

    <!-- Formulario Insertar -->
    <?php else: ?>
        <h2>Ingresar Proveedor</h2>
        <form action="" method="POST">
            <label>ID Proveedor:</label><br>
            <input type="number" name="id_prov" required><br><br>

            <label>NIT:</label><br>
            <input type="text" name="nit" required><br><br>

            <label>Nombre Proveedor:</label><br>
            <input type="text" name="nombre" required><br><br>

            <label>Teléfono:</label><br>
            <input type="text" name="telefono" required><br><br>

            <label>Correo:</label><br>
            <input type="email" name="correo"><br><br>

            <button type="submit" name="guardar">Guardar Proveedor</button>
        </form>
    <?php endif; ?>

    <br>
    <form action="menu.php" method="POST" style="display:inline;">
        <button type="submit">Menú Principal</button>
    </form>

    <hr>

    <h3>Buscar Proveedor</h3>
    <form action="" method="GET">
        <input type="text" name="buscar" placeholder="Ingrese ID, NIT, Nombre, Teléfono o Correo..." value="<?php echo htmlspecialchars($busqueda); ?>">
        <button type="submit">Buscar</button>
        <?php if (!empty($busqueda)): ?>
            <a href="?">Mostrar todos</a>
        <?php endif; ?>
    </form>

    <!-- Listado de Proveedores -->
    <h3>Listado de Proveedores</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID Proveedor</th>
                <th>NIT</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($proveedores && $proveedores->num_rows > 0): ?>
                <?php while ($fila = $proveedores->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['id_prov']); ?></td>
                        <td><?php echo htmlspecialchars($fila['nit']); ?></td>
                        <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($fila['telefono']); ?></td>
                        <td><?php echo htmlspecialchars($fila['correo'] ?? ''); ?></td>
                        <td>
                            <a href="?accion=editar&id_prov=<?php echo $fila['id_prov']; ?>">Editar</a> |
                            <a href="?accion=eliminar&id_prov=<?php echo $fila['id_prov']; ?>" onclick="return confirm('¿Seguro que deseas eliminar este proveedor?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No se encontraron proveedores.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>

<?php
$conn->close();
?>