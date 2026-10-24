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
        $id_car  = $_POST["id_car"];
        $nombre  = $_POST["nombre"];
        $salario = $_POST["salario"];

        try {
            $sql = "INSERT INTO cargo (id_car, nombre, salario) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isd", $id_car, $nombre, $salario);
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
        $id_car  = $_POST["id_car"];
        $nombre  = $_POST["nombre"];
        $salario = $_POST["salario"];

        try {
            $sql_up = "UPDATE cargo SET nombre = ?, salario = ? WHERE id_car = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("sdi", $nombre, $salario, $id_car);
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

    $sql_listar = "SELECT * FROM cargo WHERE id_car LIKE ? OR nombre LIKE ? OR salario LIKE ?";
    $stmt_list = $conn->prepare($sql_listar);
    $stmt_list->bind_param("sss", $param_busqueda, $param_busqueda, $param_busqueda);
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
    <title>Gestión Cargo</title>
</head>
<body>

    <?php echo $mensaje; ?>

    <!-- Formulario Editar -->
    <?php if ($cargo_editar): ?>
        <h2>Editar Cargo</h2>
        <form action="" method="POST">
            <input type="hidden" name="id_car" value="<?php echo htmlspecialchars($cargo_editar['id_car']); ?>">

            <label>ID Cargo:</label><br>
            <input type="number" value="<?php echo htmlspecialchars($cargo_editar['id_car']); ?>" disabled><br><br>

            <label>Nombre del Cargo:</label><br>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($cargo_editar['nombre']); ?>" required><br><br>

            <label>Salario:</label><br>
            <input type="number" step="0.01" name="salario" value="<?php echo htmlspecialchars($cargo_editar['salario']); ?>" required><br><br>

            <button type="submit" name="actualizar">Actualizar Cargo</button>
            <a href="?">Cancelar</a>
        </form>

    <!-- Formulario Insertar -->
    <?php else: ?>
        <h2>Ingresar Cargo</h2>
        <form action="" method="POST">
            <label>ID Cargo:</label><br>
            <input type="number" name="id_car" required><br><br>

            <label>Nombre del Cargo:</label><br>
            <input type="text" name="nombre" required><br><br>

            <label>Salario:</label><br>
            <input type="number" step="0.01" name="salario" required><br><br>

            <button type="submit" name="guardar">Guardar Cargo</button>
        </form>
    <?php endif; ?>

    <br>
    <form action="menu.php" method="POST" style="display:inline;">
        <button type="submit">Menú Principal</button>
    </form>

    <hr>

    <h3>Buscar Cargo</h3>
    <form action="" method="GET">
        <input type="text" name="buscar" placeholder="Ingrese ID, Nombre o Salario..." value="<?php echo htmlspecialchars($busqueda); ?>">
        <button type="submit">Buscar</button>
        <?php if (!empty($busqueda)): ?>
            <a href="?">Mostrar todos</a>
        <?php endif; ?>
    </form>

    <!-- Listado de Cargos -->
    <h3>Listado de Cargos</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID Cargo</th>
                <th>Nombre</th>
                <th>Salario</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($cargos && $cargos->num_rows > 0): ?>
                <?php while ($fila = $cargos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['id_car']); ?></td>
                        <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                        <td>$<?php echo number_format($fila['salario'], 2); ?></td>
                        <td>
                            <a href="?accion=editar&id_car=<?php echo $fila['id_car']; ?>">Editar</a> |
                            <a href="?accion=eliminar&id_car=<?php echo $fila['id_car']; ?>" onclick="return confirm('¿Seguro que deseas eliminar este cargo?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No se encontraron cargos.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>

<?php
$conn->close();
?>