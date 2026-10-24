<?php
include("conexion.php");

if (!isset($conn) && isset($conexion)) {
    $conn = $conexion;
}

$mensaje = "";
$cargo_editar = null;
$busqueda = "";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 1. Eliminar Cargo
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_car'])) {
    $id_eliminar = $_GET['id_car'];

    try {
        $sql_del = "DELETE FROM cargo WHERE id_car = ?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->bind_param("i", $id_eliminar);
        $stmt_del->execute();

        $mensaje = "<p style='color: green; font-weight: bold;'>Cargo con ID " . htmlspecialchars($id_eliminar) . " eliminado correctamente.</p>";
        $stmt_del->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<p style='color: red; font-weight: bold;'>Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// 2. Cargar datos para editar
if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_car'])) {
    $id_buscar = $_GET['id_car'];

    try {
        $sql_buscar = "SELECT * FROM cargo WHERE id_car = ?";
        $stmt_buscar = $conn->prepare($sql_buscar);
        $stmt_buscar->bind_param("i", $id_buscar);
        $stmt_buscar->execute();
        $res = $stmt_buscar->get_result();
        $cargo_editar = $res->fetch_assoc();
        $stmt_buscar->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<p style='color: red; font-weight: bold;'>Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// 3. Procesar formularios (Guardar y Actualizar)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Guardar nuevo cargo
    if (isset($_POST["guardar"])) {
        $id_car      = $_POST["id_car"];
        $nombre      = $_POST["nombre"];
        $descripcion = $_POST["descripcion"];
        $salario     = $_POST["salario"];
        $estado      = $_POST["estado"];

        try {
            $sql = "INSERT INTO cargo (id_car, nombre, descripcion, salario, estado) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issds", $id_car, $nombre, $descripcion, $salario, $estado);
            $stmt->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Cargo registrado correctamente.</p>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error: El ID " . htmlspecialchars($id_car) . " ya está registrado.</p>";
            } else {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error al registrar: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }

    // Actualizar cargo
    if (isset($_POST["actualizar"])) {
        $id_car      = $_POST["id_car"];
        $nombre      = $_POST["nombre"];
        $descripcion = $_POST["descripcion"];
        $salario     = $_POST["salario"];
        $estado      = $_POST["estado"];

        try {
            $sql_up = "UPDATE cargo SET nombre = ?, descripcion = ?, salario = ?, estado = ? WHERE id_car = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("ssdsi", $nombre, $descripcion, $salario, $estado, $id_car);
            $stmt_up->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Cargo actualizado correctamente.</p>";
            $stmt_up->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<p style='color: red; font-weight: bold;'>Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// 4. Buscar y listar cargos
if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $param_busqueda = "%" . $busqueda . "%";

    $sql_listar = "SELECT * FROM cargo WHERE id_car LIKE ? OR nombre LIKE ? OR descripcion LIKE ? OR salario LIKE ?";
    $stmt_list = $conn->prepare($sql_listar);
    $stmt_list->bind_param("ssss", $param_busqueda, $param_busqueda, $param_busqueda, $param_busqueda);
    $stmt_list->execute();
    $cargos = $stmt_list->get_result();
} else {
    $sql_listar = "SELECT * FROM cargo ORDER BY id_car ASC";
    $cargos = $conn->query($sql_listar);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Cargos - El Progreso</title>
</head>
<body>
    <h1>Gestión de Cargos - El Progreso</h1>
    <hr>
    <br>

    <?php echo $mensaje; ?>

    <!-- Formulario Editar -->
    <?php if ($cargo_editar): ?>
        <h2>Modificación de Registro de Cargo</h2>
        <form action="" method="POST">
            <input type="hidden" name="id_car" value="<?php echo htmlspecialchars($cargo_editar['id_car']); ?>">

            <label>Código Identificador (ID Cargo):</label><br>
            <input type="number" value="<?php echo htmlspecialchars($cargo_editar['id_car']); ?>" disabled><br><br>

            <label>Denominación del Cargo:</label><br>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($cargo_editar['nombre']); ?>" required><br><br>

            <label>Descripción:</label><br>
            <textarea name="descripcion" rows="3"><?php echo htmlspecialchars($cargo_editar['descripcion']); ?></textarea><br><br>

            <label>Asignación Salarial (Monto Base):</label><br>
            <input type="number" step="0.01" name="salario" value="<?php echo htmlspecialchars($cargo_editar['salario']); ?>" required><br><br>

            <label>Estado:</label><br>
            <select name="estado">
                <option value="Activo" <?php if ($cargo_editar['estado'] == 'Activo') echo 'selected'; ?>>Activo</option>
                <option value="Inactivo" <?php if ($cargo_editar['estado'] == 'Inactivo') echo 'selected'; ?>>Inactivo</option>
            </select><br><br>

            <button type="submit" name="actualizar">Actualizar Información</button>
            <a href="?">Cancelar Operación</a>
        </form>

    <!-- Formulario Insertar -->
    <?php else: ?>
        <h2>Registro de Nuevo Cargo</h2>
        <form action="" method="POST">
            <label>Código Identificador (ID Cargo):</label><br>
            <input type="number" name="id_car" required><br><br>

            <label>Denominación del Cargo:</label><br>
            <input type="text" name="nombre" required><br><br>

            <label>Descripción:</label><br>
            <textarea name="descripcion" rows="3"></textarea><br><br>

            <label>Asignación Salarial (Monto Base):</label><br>
            <input type="number" step="0.01" name="salario" required><br><br>

            <input type="hidden" name="estado" value="Activo">

            <button type="submit" name="guardar">Guardar Registro</button>
        </form>
    <?php endif; ?>

    <br>
    <form action="menu.php" method="POST" style="display:inline;">
        <button type="submit">Volver al Menú Principal</button>
    </form>

    <hr>

    <h3>Consulta de Cargos</h3>
    <form action="" method="GET">
        <input type="text" name="buscar" placeholder="Ingrese ID, Nombre, Descripción o Salario..." value="<?php echo htmlspecialchars($busqueda); ?>">
        <button type="submit">Ejecutar Búsqueda</button>
        <?php if (!empty($busqueda)): ?>
            <a href="?">Restablecer Vista</a>
        <?php endif; ?>
    </form>

    <!-- Listado de Cargos -->
    <h3>Listado General de Cargos Registrados</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID Cargo</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Salario</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($cargos && $cargos->num_rows > 0): ?>
                <?php while ($fila = $cargos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['id_car']); ?></td>
                        <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($fila['descripcion']); ?></td>
                        <td>$<?php echo number_format($fila['salario'], 2); ?></td>
                        <td><?php echo htmlspecialchars($fila['estado']); ?></td>
                        <td>
                            <a href="?accion=editar&id_car=<?php echo $fila['id_car']; ?>">Editar</a> |
                            <a href="?accion=eliminar&id_car=<?php echo $fila['id_car']; ?>" onclick="return confirm('¿Seguro que deseas eliminar este cargo?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No se encontraron registros de cargos institucionales.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>

<?php
$conn->close();
?>