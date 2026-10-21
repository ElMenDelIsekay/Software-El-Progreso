<?php
include("conexion.php");

if (!isset($conn) && isset($conexion)) {
    $conn = $conexion;
}

$mensaje = "";
$empleado_editar = null;
$busqueda = "";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 1. Eliminar Empleado
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_emp'])) {
    $id_eliminar = $_GET['id_emp'];

    try {
        $sql_del = "DELETE FROM empleado WHERE id_emp = ?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->bind_param("i", $id_eliminar);
        $stmt_del->execute();

        $mensaje = "<p style='color: green; font-weight: bold;'>Empleado con ID " . htmlspecialchars($id_eliminar) . " eliminado correctamente.</p>";
        $stmt_del->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<p style='color: red; font-weight: bold;'>Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// 2. Cargar datos para editar
if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_emp'])) {
    $id_buscar = $_GET['id_emp'];

    try {
        $sql_buscar = "SELECT * FROM empleado WHERE id_emp = ?";
        $stmt_buscar = $conn->prepare($sql_buscar);
        $stmt_buscar->bind_param("i", $id_buscar);
        $stmt_buscar->execute();
        $res = $stmt_buscar->get_result();
        $empleado_editar = $res->fetch_assoc();
        $stmt_buscar->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<p style='color: red; font-weight: bold;'>Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// 3. Procesar formularios (Guardar y Actualizar)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Guardar nuevo empleado
    if (isset($_POST["guardar"])) {
        $id_emp         = $_POST["id_emp"];
        $nombre         = $_POST["nombre"];
        $telefono       = $_POST["telefono"];
        $direccion      = $_POST["direccion"];
        $email          = $_POST["email"];
        $fecha_contrato = $_POST["fecha_contrato"];
        $id_fk_car      = $_POST["id_fk_car"];
        $estado         = $_POST["estado"];

        try {
            $sql = "INSERT INTO empleado (id_emp, nombre, telefono, direccion, email, fecha_contrato, id_fk_car, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssssis", $id_emp, $nombre, $telefono, $direccion, $email, $fecha_contrato, $id_fk_car, $estado);
            $stmt->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Empleado registrado correctamente.</p>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error: El ID " . htmlspecialchars($id_emp) . " ya está registrado.</p>";
            } else {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error al registrar: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }

    // Actualizar empleado
    if (isset($_POST["actualizar"])) {
        $id_emp         = $_POST["id_emp"];
        $nombre         = $_POST["nombre"];
        $telefono       = $_POST["telefono"];
        $direccion      = $_POST["direccion"];
        $email          = $_POST["email"];
        $fecha_contrato = $_POST["fecha_contrato"];
        $id_fk_car      = $_POST["id_fk_car"];
        $estado         = $_POST["estado"];

        try {
            $sql_up = "UPDATE empleado SET nombre = ?, telefono = ?, direccion = ?, email = ?, fecha_contrato = ?, id_fk_car = ?, estado = ? WHERE id_emp = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("sssssis i", $nombre, $telefono, $direccion, $email, $fecha_contrato, $id_fk_car, $estado, $id_emp);
            $stmt_up->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Empleado actualizado correctamente.</p>";
            $stmt_up->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<p style='color: red; font-weight: bold;'>Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// 4. Buscar y listar empleados
if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $busqueda = trim($_GET['buscar']);
    $param_busqueda = "%" . $busqueda . "%";

    $sql_listar = "SELECT e.id_emp, e.nombre, e.telefono, e.direccion, e.email, e.fecha_contrato, e.id_fk_car, e.estado,
                          c.nombre AS nom_cargo
                   FROM empleado e
                   LEFT JOIN cargo c ON e.id_fk_car = c.id_car
                   WHERE e.id_emp LIKE ? OR e.nombre LIKE ? OR e.telefono LIKE ? OR e.email LIKE ?
                   ORDER BY e.id_emp ASC";
    $stmt_list = $conn->prepare($sql_listar);
    $stmt_list->bind_param("ssss", $param_busqueda, $param_busqueda, $param_busqueda, $param_busqueda);
    $stmt_list->execute();
    $empleados = $stmt_list->get_result();
} else {
    $sql_listar = "SELECT e.id_emp, e.nombre, e.telefono, e.direccion, e.email, e.fecha_contrato, e.id_fk_car, e.estado,
                          c.nombre AS nom_cargo
                   FROM empleado e
                   LEFT JOIN cargo c ON e.id_fk_car = c.id_car
                   ORDER BY e.id_emp ASC";
    $empleados = $conn->query($sql_listar);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Empleados - El Progreso</title>
</head>
<body>
    <h1>Gestión de Empleados - El Progreso</h1>
    <hr>
    <br>

    <?php echo $mensaje; ?>

    <!-- Formulario Editar -->
    <?php if ($empleado_editar): ?>
        <h2>Modificación de Registro de Empleado</h2>
        <form action="" method="POST">
            <input type="hidden" name="id_emp" value="<?php echo htmlspecialchars($empleado_editar['id_emp']); ?>">

            <label>Código Identificador (ID Empleado):</label><br>
            <input type="number" value="<?php echo htmlspecialchars($empleado_editar['id_emp']); ?>" disabled><br><br>

            <label>Nombre Completo / Apellidos:</label><br>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($empleado_editar['nombre']); ?>" required><br><br>

            <label>Número de Contacto (Teléfono):</label><br>
            <input type="text" name="telefono" value="<?php echo htmlspecialchars($empleado_editar['telefono']); ?>" required><br><br>

            <label>Dirección de Residencia:</label><br>
            <input type="text" name="direccion" value="<?php echo htmlspecialchars($empleado_editar['direccion']); ?>" required><br><br>

            <label>Correo Electrónico (Email):</label><br>
            <input type="email" name="email" value="<?php echo htmlspecialchars($empleado_editar['email']); ?>"><br><br>

            <label>Fecha de Vinculación / Contrato:</label><br>
            <input type="date" name="fecha_contrato" value="<?php echo htmlspecialchars($empleado_editar['fecha_contrato']); ?>" required><br><br>

            <label>Cargo / Función Desempeñada:</label><br>
            <select name="id_fk_car" id="id_fk_car" required>
                <option value="">-- Seleccione un cargo --</option>
                <?php
                $sql_car2 = "SELECT id_car, nombre FROM cargo ORDER BY id_car ASC";
                $res_car2 = $conn->query($sql_car2);
                while ($car2 = $res_car2->fetch_assoc()) {
                    $selected = ($empleado_editar['id_fk_car'] == $car2['id_car']) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($car2['id_car']) . '" ' . $selected . '>'
                        . htmlspecialchars($car2['id_car'] . ' - ' . $car2['nombre'])
                        . '</option>';
                }
                ?>
            </select> <br><br>  

            <label>Estado:</label><br>
            <select name="estado">
                <option value="Activo" <?php if ($empleado_editar['estado'] == 'Activo') echo 'selected'; ?>>Activo</option>
                <option value="Inactivo" <?php if ($empleado_editar['estado'] == 'Inactivo') echo 'selected'; ?>>Inactivo</option>
            </select><br><br>

            <button type="submit" name="actualizar">Actualizar Información</button>
            <a href="?">Cancelar Operación</a>
        </form>

    <!-- Formulario Insertar -->
    <?php else: ?>
        <h2>Registro de Nuevo Empleado</h2>
        <form action="" method="POST">
            <label>Código Identificador (ID Empleado):</label><br>
            <input type="number" name="id_emp" required><br><br>

            <label>Nombre Completo / Apellidos:</label><br>
            <input type="text" name="nombre" required><br><br>

            <label>Número de Contacto (Teléfono):</label><br>
            <input type="text" name="telefono" required><br><br>

            <label>Dirección de Residencia:</label><br>
            <input type="text" name="direccion" required><br><br>

            <label>Correo Electrónico (Email):</label><br>
            <input type="email" name="email"><br><br>

            <label>Fecha de Vinculación / Contrato:</label><br>
            <input type="date" name="fecha_contrato" required><br><br>

            <label>Cargo / Función Desempeñada:</label><br>
            <select name="id_fk_car" id="id_fk_car" required>
                <option value="">-- Seleccione un cargo --</option>
                <?php
                $sql_car2 = "SELECT id_car, nombre FROM cargo ORDER BY id_car ASC";
                $res_car2 = $conn->query($sql_car2);
                while ($car2 = $res_car2->fetch_assoc()) {
                    echo '<option value="' . htmlspecialchars($car2['id_car']) . '">'
                        . htmlspecialchars($car2['id_car'] . ' - ' . $car2['nombre'])
                        . '</option>';
                }
                ?>
            </select> <br><br> 

            <input type="hidden" name="estado" value="Activo">

            <button type="submit" name="guardar">Guardar Registro</button>
        </form>
    <?php endif; ?>

    <br>
    <form action="menu.php" method="POST" style="display:inline;">
        <button type="submit">Volver al Menú Principal</button>
    </form>

    <hr>

    <h3>Consulta de Empleados</h3>
    <form action="" method="GET">
        <input type="text" name="buscar" placeholder="Ingrese ID, Nombre, Teléfono o Email..." value="<?php echo htmlspecialchars($busqueda); ?>">
        <button type="submit">Ejecutar Búsqueda</button>
        <?php if (!empty($busqueda)): ?>
            <a href="?">Restablecer Vista</a>
        <?php endif; ?>
    </form>

    <!-- Listado de Empleados -->
    <h3>Listado General de Empleados Registrados</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID Empleado</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>Email</th>
                <th>Fecha Contrato</th>
                <th>Cargo</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($empleados && $empleados->num_rows > 0): ?>
                <?php while ($fila = $empleados->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['id_emp']); ?></td>
                        <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($fila['telefono']); ?></td>
                        <td><?php echo htmlspecialchars($fila['direccion']); ?></td>
                        <td><?php echo htmlspecialchars($fila['email']); ?></td>
                        <td><?php echo htmlspecialchars($fila['fecha_contrato']); ?></td>
                        <td><?php echo $fila['id_fk_car'] . ' - ' . htmlspecialchars($fila['nom_cargo'] ?? 'Sin asignar'); ?></td>
                        <td><?php echo htmlspecialchars($fila['estado']); ?></td>
                        <td>
                            <a href="?accion=editar&id_emp=<?php echo $fila['id_emp']; ?>">Editar</a> |
                            <a href="?accion=eliminar&id_emp=<?php echo $fila['id_emp']; ?>" onclick="return confirm('¿Seguro que deseas eliminar este empleado?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9">No se encontraron registros de empleados institucionales.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>

<?php
$conn->close();
?>