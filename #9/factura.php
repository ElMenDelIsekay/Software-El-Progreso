<?php
include("conexion.php");

// Compatibilidad con la variable de conexión
if (!isset($conn) && isset($conexion)) {
    $conn = $conexion;
}

$mensaje = "";
$fact_editar = null;
$busqueda = "";

// Activar el modo de reporte de excepciones en MySQL
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// accion eliminar factura bd
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_fac'])) {

    $fact_eliminar = $_GET['id_fac'];

    try {
        $sql_del = "DELETE FROM factura WHERE id_fac = ?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->bind_param("i", $fact_eliminar);
        $stmt_del->execute();

        $mensaje = "<p style='color: green; font-weight: bold;'>Factura con id " . htmlspecialchars($fact_eliminar) .
         " eliminada correctamente.</p>";
        $stmt_del->close();

    } catch (mysqli_sql_exception $e) {
        // Código 1451: Restricción de clave foránea (la factura posee detalles)
        if ($e->getCode() == 1451) {
            $mensaje = "<p style='color: red; font-weight: bold;'>No se puede eliminar la factura con id "
             . htmlspecialchars($fact_eliminar) . " porque tiene detalles de factura registrados.</p>";
        } else {
            $mensaje = "<p style='color: red; font-weight: bold;'>Error al eliminar: "
             . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// 2 accion modificar datos
if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_fac'])) {
    $fact_buscar = $_GET['id_fac'];

    try {
        $sql_buscar = "SELECT * FROM factura WHERE id_fac = ?";
        $stmt_buscar = $conn->prepare($sql_buscar);
        $stmt_buscar->bind_param("i", $fact_buscar);
        $stmt_buscar->execute();
        $res = $stmt_buscar->get_result();
        $fact_editar = $res->fetch_assoc();
        $stmt_buscar->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<p style='color: red; font-weight: bold;'>Error al cargar datos: " .
        htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// 3 formularios guardar y modificar post
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // crear o guardar nueva factura
    if (isset($_POST["guardar"])) {
        $id_fac        = $_POST["id_fac"];
        $fecha         = $_POST["fecha"];
        $impuesto      = $_POST["impuesto"];
        $estado        = $_POST["estado"];
        $metodo_pago   = $_POST["metodo_pago"];
        $observaciones = $_POST["observaciones"];
        $total         = $_POST["total"];
        $id_fk_emp     = $_POST["id_fk_emp"];
        $id_fk_cli     = $_POST["id_fk_cli"];

        try {
            $sql = "INSERT INTO factura (id_fac, fecha, impuesto, estado, metodo_pago, observaciones, total, id_fk_emp, id_fk_cli) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isdsssdii", $id_fac, $fecha, $impuesto, $estado, $metodo_pago, $observaciones, $total, $id_fk_emp, $id_fk_cli);
            $stmt->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Factura registrada correctamente.</p>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            // Código 1062: clave primaria duplicada
            if ($e->getCode() == 1062) {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error: El id de factura " . htmlspecialchars($id_fac) . " ya está registrada.</p>";
            } else {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error al registrar: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }

    // modificar y/o actualizar factura
    if (isset($_POST["actualizar"])) {
        $id_fac        = $_POST["id_fac"];
        $fecha         = $_POST["fecha"];
        $impuesto      = $_POST["impuesto"];
        $estado        = $_POST["estado"];
        $metodo_pago   = $_POST["metodo_pago"];
        $observaciones = $_POST["observaciones"];
        $total         = $_POST["total"];
        $id_fk_emp     = $_POST["id_fk_emp"];
        $id_fk_cli     = $_POST["id_fk_cli"];

        try {
            $sql_up = "UPDATE factura SET fecha = ?, impuesto = ?, estado = ?, metodo_pago = ?, observaciones = ?, total = ?, id_fk_emp = ?, id_fk_cli = ? WHERE id_fac = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("sdsssdiii", $fecha, $impuesto, $estado, $metodo_pago, $observaciones, $total, $id_fk_emp, $id_fk_cli, $id_fac);
            $stmt_up->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Factura actualizada correctamente.</p>";
            $stmt_up->close();

        } catch (mysqli_sql_exception $e) {
            $mensaje = "<p style='color: red; font-weight: bold;'>Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// 4 bloque codigo buscar y listar compras (CON JOIN para traer nombres)
if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $busqueda = trim($_GET['buscar']);
    $param_busqueda = "%" . $busqueda . "%";

    $sql_listar = "SELECT f.id_fac, f.fecha, f.impuesto, f.estado, f.metodo_pago, f.observaciones, f.total, f.id_fk_emp, f.id_fk_cli,
                          e.nombre AS nom_empleado,
                          c.nombre AS nom_cliente
                   FROM factura f
                   LEFT JOIN empleado e ON f.id_fk_emp = e.id_emp
                   LEFT JOIN cliente c ON f.id_fk_cli = c.id_cli
                   WHERE f.id_fac LIKE ? OR f.fecha LIKE ? OR f.estado LIKE ? OR f.total LIKE ?
                   ORDER BY f.id_fac ASC";
    $stmt_list = $conn->prepare($sql_listar);
    $stmt_list->bind_param("ssss", $param_busqueda, $param_busqueda, $param_busqueda, $param_busqueda);
    $stmt_list->execute();
    $facturas = $stmt_list->get_result();
} else {
    $sql_listar = "SELECT f.id_fac, f.fecha, f.impuesto, f.estado, f.metodo_pago, f.observaciones, f.total, f.id_fk_emp, f.id_fk_cli,
                          e.nombre AS nom_empleado,
                          c.nombre AS nom_cliente
                   FROM factura f
                   LEFT JOIN empleado e ON f.id_fk_emp = e.id_emp
                   LEFT JOIN cliente c ON f.id_fk_cli = c.id_cli
                   ORDER BY f.id_fac ASC";
    $facturas = $conn->query($sql_listar);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Facturación  - El Progreso</title>
</head>
<body>
    <h1>Gestión de Facturación  - El Progreso</h1>
    <hr>
    <br>

    <?php echo $mensaje ?>

    <?php if ($fact_editar) : ?>
        <h2>Modificación de Registro de Factura</h2>
        <form action="" method="POST">
            <input type="hidden" name="id_fac" value="<?php echo htmlspecialchars($fact_editar['id_fac']); ?>">

            <label>ID Factura:</label><br>
            <input type="number" value="<?php echo htmlspecialchars($fact_editar['id_fac']); ?>" disabled> <br><br>

            <label>Fecha:</label><br>
            <input type="date" name="fecha" value="<?php echo htmlspecialchars($fact_editar['fecha']); ?>" required> <br><br>

            <label>Impuesto:</label><br>
            <input type="number" name="impuesto" step="0.01" value="<?php echo htmlspecialchars($fact_editar['impuesto']); ?>" required> <br><br>

            <label>Estado:</label><br>
            <select name="estado" id="estado" required>
                <option value="Pendiente" <?php echo ($fact_editar['estado'] === 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                <option value="Pagada" <?php echo ($fact_editar['estado'] === 'Pagada') ? 'selected' : ''; ?>>Pagada</option>
                <option value="Cancelada" <?php echo ($fact_editar['estado'] === 'Cancelada') ? 'selected' : ''; ?>>Cancelada</option>
            </select> <br><br>

            <label>Método de Pago:</label><br>
            <select name="metodo_pago" required>
                <option value="Efectivo" <?php echo ($fact_editar['metodo_pago'] === 'Efectivo') ? 'selected' : ''; ?>>Efectivo</option>
                <option value="Transferencia" <?php echo ($fact_editar['metodo_pago'] === 'Transferencia') ? 'selected' : ''; ?>>Transferencia</option>
                <option value="Crédito" <?php echo ($fact_editar['metodo_pago'] === 'Crédito') ? 'selected' : ''; ?>>Crédito</option>
            </select> <br><br>

            <label>Observaciones:</label><br>
            <textarea name="observaciones" rows="3"><?php echo htmlspecialchars($fact_editar['observaciones']); ?></textarea> <br><br>

            <label>Total:</label><br>
            <input type="number" name="total" step="0.01" value="<?php echo htmlspecialchars($fact_editar['total']); ?>" required> <br><br>

            <label>Empleado:</label><br>
            <select name="id_fk_emp" id="id_fk_emp" required>
                <option value="">-- Seleccione un empleado --</option>
                <?php
                $sql_emp2 = "SELECT id_emp, nombre FROM empleado ORDER BY id_emp ASC";
                $res_emp2 = $conn->query($sql_emp2);
                while ($emp2 = $res_emp2->fetch_assoc()) {
                    $selected = ($fact_editar['id_fk_emp'] == $emp2['id_emp']) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($emp2['id_emp']) . '" ' . $selected . '>'
                        . htmlspecialchars($emp2['id_emp'] . ' - ' . $emp2['nombre'])
                        . '</option>';
                }
                ?>
            </select> <br><br>
                
            <label>Cliente:</label><br>
            <select name="id_fk_cli" id="id_fk_cli" required>
                <option value="">-- Seleccione un cliente --</option>
                <?php
                $sql_cli2 = "SELECT id_cli, nombre FROM cliente ORDER BY id_cli ASC";
                $res_cli2 = $conn->query($sql_cli2);
                while ($cli2 = $res_cli2->fetch_assoc()) {
                    $selected = ($fact_editar['id_fk_cli'] == $cli2['id_cli']) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($cli2['id_cli']) . '" ' . $selected . '>'
                        . htmlspecialchars($cli2['id_cli'] . ' - ' . $cli2['nombre'])
                        . '</option>';
                }
                ?>
            </select> <br><br> 
            <button type="submit" name="actualizar">Actualizar Factura</button>
            <a href="?">Cancelar</a>
        </form>
    <?php else: ?>
        <h2>Registro de Nueva Factura</h2>
        <form action="" method="POST">

            <label>ID Factura:</label><br>
            <input type="number" name="id_fac" required> <br><br>

            <label>Fecha:</label><br>
            <input type="date" name="fecha" required> <br><br>

            <label>Impuesto:</label><br>
            <input type="number" name="impuesto" step="0.01" required> <br><br>

            <input type="text" name="estado" id="estado" value="Pendiente" hidden>

            <label>Método de Pago:</label><br>
            <select name="metodo_pago" required>
                <option value="Efectivo">Efectivo</option>
                <option value="Transferencia">Transferencia</option>
                <option value="Crédito">Crédito</option>
            </select> <br><br>

            <label>Observaciones:</label><br>
            <textarea name="observaciones" rows="3"></textarea> <br><br>

            <label>Total:</label><br>
            <input type="number" name="total" step="0.01" required> <br><br>

            <label>Empleado:</label><br>
            <select name="id_fk_emp" id="id_fk_emp" required>
                <option value="">-- Seleccione un empleado --</option>
                <?php
                $sql_emp2 = "SELECT id_emp, nombre FROM empleado ORDER BY id_emp ASC";
                $res_emp2 = $conn->query($sql_emp2);
                while ($emp2 = $res_emp2->fetch_assoc()) {
                    echo '<option value="' . htmlspecialchars($emp2['id_emp']) . '">'
                        . htmlspecialchars($emp2['id_emp'] . ' - ' . $emp2['nombre'])
                        . '</option>';
                }
                ?>
            </select> <br><br>

            <label>Cliente:</label><br>
                        <select name="id_fk_cli" id="id_fk_cli" required>
                            <option value="">-- Seleccione un cliente --</option>
                            <?php
                            $sql_cli2 = "SELECT id_cli, nombre FROM cliente ORDER BY id_cli ASC";
                            $res_cli2 = $conn->query($sql_cli2);
                            while ($cli2 = $res_cli2->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($cli2['id_cli']) . '">'
                                    . htmlspecialchars($cli2['id_cli'] . ' - ' . $cli2['nombre'])
                                    . '</option>';
                            }
                            ?>
                        </select> <br><br> 

            <button type="submit" name="guardar">Guardar Factura</button>
        </form>
    <?php endif; ?>

    <br>
    <form action="menu.php" method="POST" style="display: inline;">
        <button type="submit">Menu Principal</button>
    </form>
    <h3>Consulta de Facturas</h3>
    <form action="" method="GET">
        <input type="text" name="buscar" placeholder="Ingrese ID, estado o fecha..." value="<?php echo htmlspecialchars($busqueda); ?>">
        <button type="submit">Buscar</button>
        <?php if (!empty($busqueda)): ?>
            <a href="?">Mostrar todos</a>
        <?php endif; ?>
    </form>
    <br>
            <hr><hr>
    <h3>Historial de Facturas Registradas</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Impuesto</th>
                <th>Estado</th>
                <th>Método de Pago</th>
                <th>Observaciones</th>
                <th>Total</th>
                <th>Empleado</th>
                <th>Cliente</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($facturas && $facturas->num_rows > 0): ?>
                <?php while ($fila = $facturas->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['id_fac']); ?></td>
                        <td><?php echo htmlspecialchars($fila['fecha']); ?></td>
                        <td><?php echo htmlspecialchars($fila['impuesto']); ?></td>
                        <td><?php echo htmlspecialchars($fila['estado']); ?></td>
                        <td><?php echo htmlspecialchars($fila['metodo_pago']); ?></td>
                        <td><?php echo htmlspecialchars($fila['observaciones']); ?></td>
                        <td><?php echo htmlspecialchars($fila['total']); ?></td>
                        <td><?php echo $fila['id_fk_emp'] . ' - ' . htmlspecialchars($fila['nom_empleado'] ?? 'Sin asignar'); ?></td>
                        <td><?php echo $fila['id_fk_cli'] . ' - ' . htmlspecialchars($fila['nom_cliente'] ?? 'Sin asignar'); ?></td>
                        <td>
                            <a href="?accion=editar&id_fac=<?php echo $fila['id_fac']; ?>">Editar</a>
                            <a href="?accion=eliminar&id_fac=<?php echo $fila['id_fac']; ?>"
                            onclick="return confirm('¿seguro que desea eliminar esta factura?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10">No se encontraron registros de facturas.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
<?php
$conn->close();
?>