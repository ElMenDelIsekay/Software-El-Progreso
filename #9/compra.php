<?php
include("conexion.php");

if (!isset($conn) && isset($conexion)) {
    $conn = $conexion;
}

$mensaje = "";
$comp_editar = null;
$busqueda = "";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 1. Eliminar Compra
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_com'])) {
    $comp_eliminar = $_GET['id_com'];

    try {
        $sql_del = "DELETE FROM compra WHERE id_com = ?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->bind_param("i", $comp_eliminar);
        $stmt_del->execute();

        $mensaje = "<p style='color: green; font-weight: bold;'>El registro de compra con ID " . $comp_eliminar . " ha sido eliminado exitosamente.</p>";
        $stmt_del->close();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            $mensaje = "<p style='color: red; font-weight: bold;'>No se puede eliminar la compra con ID " . $comp_eliminar . " debido a que posee detalles de compra vinculados.</p>";
        } else {
            $mensaje = "<p style='color: red; font-weight: bold;'>Error al intentar eliminar el registro: " . $e->getMessage() . "</p>";
        }
    }
}

// 2. Cargar datos para editar
if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_com'])) {
    $comp_buscar = $_GET['id_com'];

    try {
        $sql_buscar = "SELECT * FROM compra WHERE id_com = ?";
        $stmt_buscar = $conn->prepare($sql_buscar);
        $stmt_buscar->bind_param("i", $comp_buscar);
        $stmt_buscar->execute();
        $res = $stmt_buscar->get_result();
        $comp_editar = $res->fetch_assoc();
        $stmt_buscar->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<p style='color: red; font-weight: bold;'>Error al cargar los datos del registro: " . $e->getMessage() . "</p>";
    }
}

// 3 formularios guardar y modificar post
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // crear o guardar nueva compra
    if (isset($_POST["guardar"])) {
        $id_com        = $_POST["id_com"];
        $nombre        = $_POST["nombre"];
        $fecha         = $_POST["fecha"];
        $fecha_entrega = $_POST["fecha_entrega"];
        $cantidad      = $_POST["cantidad"];
        $total         = $_POST["total"];
        $estado        = $_POST["estado"];
        $observaciones = $_POST["observaciones"];
        $id_fk_emp     = $_POST["id_fk_emp"];
        $id_fk_prov    = $_POST["id_fk_prov"];

        try {
            $sql = "INSERT INTO compra (id_com, nombre, fecha, fecha_entrega, cantidad, total, estado, observaciones, id_fk_emp, id_fk_prov) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssidssii", $id_com, $nombre, $fecha, $fecha_entrega, $cantidad, $total, $estado, $observaciones, $id_fk_emp, $id_fk_prov);
            $stmt->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Registro de compra guardado satisfactoriamente.</p>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error: El código identificador " . $id_com . " ya se encuentra registrado.</p>";
            } else {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error al procesar el registro: " . $e->getMessage() . "</p>";
            }
        }
    }

    // Actualizar compra
    if (isset($_POST["actualizar"])) {
        $id_com        = $_POST["id_com"];
        $nombre        = $_POST["nombre"];
        $fecha         = $_POST["fecha"];
        $fecha_entrega = $_POST["fecha_entrega"];
        $cantidad      = $_POST["cantidad"];
        $total         = $_POST["total"];
        $estado        = $_POST["estado"];
        $observaciones = $_POST["observaciones"];
        $id_fk_emp     = $_POST["id_fk_emp"];
        $id_fk_prov    = $_POST["id_fk_prov"];

        try {
            $sql_up = "UPDATE compra SET nombre = ?, fecha = ?, fecha_entrega = ?, cantidad = ?, total = ?, estado = ?, observaciones = ?, id_fk_emp = ?, id_fk_prov = ? WHERE id_com = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("sssidssiii", $nombre, $fecha, $fecha_entrega, $cantidad, $total, $estado, $observaciones, $id_fk_emp, $id_fk_prov, $id_com);
            $stmt_up->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>La información de la compra ha sido actualizada correctamente.</p>";
            $stmt_up->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<p style='color: red; font-weight: bold;'>Error al actualizar el registro: " . $e->getMessage() . "</p>";
        }
    }
}

// 4. Buscar y listar compras
if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $param_busqueda = "%" . $busqueda . "%";

    $sql_listar = "SELECT c.id_com, c.nombre, c.fecha, c.fecha_entrega, c.cantidad, c.total, c.estado, c.observaciones, c.id_fk_emp, c.id_fk_prov,
                          e.nombre AS nom_empleado,
                          p.nombre AS nom_proveedor
                   FROM compra c
                   LEFT JOIN empleado e ON c.id_fk_emp = e.id_emp
                   LEFT JOIN proveedor p ON c.id_fk_prov = p.id_prov
                   WHERE c.id_com LIKE ? OR c.nombre LIKE ? OR c.estado LIKE ? OR c.fecha LIKE ?
                   ORDER BY c.id_com ASC";
    $stmt_list = $conn->prepare($sql_listar);
    $stmt_list->bind_param("ssss", $param_busqueda, $param_busqueda, $param_busqueda, $param_busqueda);
    $stmt_list->execute();
    $compras = $stmt_list->get_result();
} else {
    $sql_listar = "SELECT c.id_com, c.nombre, c.fecha, c.fecha_entrega, c.cantidad, c.total, c.estado, c.observaciones, c.id_fk_emp, c.id_fk_prov,
                          e.nombre AS nom_empleado,
                          p.nombre AS nom_proveedor
                   FROM compra c
                   LEFT JOIN empleado e ON c.id_fk_emp = e.id_emp
                   LEFT JOIN proveedor p ON c.id_fk_prov = p.id_prov
                   ORDER BY c.id_com ASC";
    $compras = $conn->query($sql_listar);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión y Solicitud de Pedidos - El Progreso</title>
</head>
<body>
    <h1>Gestión de Pedidos - El Progreso</h1><hr><br>
    <?php echo $mensaje; ?>
    <!-- Formulario Editar -->
    <?php if ($comp_editar): ?>
        <h2>Modificación de Pedido</h2>
        <form action="" method="POST">
            <input type="hidden" name="id_com" value="<?php echo $comp_editar['id_com']; ?>">

            <label>Código Identificador (ID Pedido):</label><br>
            <input type="number" value="<?php echo $comp_editar['id_com']; ?>" disabled><br><br>

            <label>Descripción / Detalle del Pedido:</label><br>
            <input type="text" name="nombre" value="<?php echo $comp_editar['nombre']; ?>" required><br><br>

            <label>Fecha de Emisión del Pedido:</label><br>
            <input type="date" name="fecha" value="<?php echo $comp_editar['fecha']; ?>" required><br><br>

            <label>Fecha Estimada de Entrega:</label><br>
            <input type="date" name="fecha_entrega" value="<?php echo $comp_editar['fecha_entrega']; ?>"><br><br>

            <label>Cantidad de Unidades Solicitadas:</label><br>
            <input type="number" name="cantidad" value="<?php echo $comp_editar['cantidad']; ?>" required><br><br>

            <label>Monto Total del Pedido:</label><br>
            <input type="number" name="total" step="0.01" value="<?php echo $comp_editar['total']; ?>" required><br><br>

            <label>Estado del Pedido:</label><br>
            <select name="estado" id="estado" required>
                <option value="Pendiente" <?php if ($comp_editar['estado'] === 'Pendiente') { echo 'selected'; } ?>>Pendiente de Entrega</option>
                <option value="Recibida" <?php if ($comp_editar['estado'] === 'Recibida') { echo 'selected'; } ?>>Pedido Recibido</option>
                <option value="Cancelada" <?php if ($comp_editar['estado'] === 'Cancelada') { echo 'selected'; } ?>>Pedido Cancelado</option>
            </select><br><br>

            <label>Observaciones del Pedido:</label><br>
            <textarea name="observaciones" rows="3"><?php echo $comp_editar['observaciones']; ?></textarea><br><br>

            <label>Empleado Gestor del Pedido:</label><br>
            <select name="id_fk_emp" id="id_fk_emp" required>
                <option value="">-- Seleccione el empleado encargado --</option>
                <?php
                $sql_emp2 = "SELECT id_emp, nombre FROM empleado ORDER BY id_emp ASC";
                $res_emp2 = $conn->query($sql_emp2);
                while ($emp2 = $res_emp2->fetch_assoc()) {
                    $selected = "";
                    if ($comp_editar['id_fk_emp'] == $emp2['id_emp']) {
                        $selected = "selected";
                    }
                    echo '<option value="' . $emp2['id_emp'] . '" ' . $selected . '>' . $emp2['id_emp'] . ' - ' . $emp2['nombre'] . '</option>';
                }
                ?>
            </select><br><br>

            <label>Proveedor Asignado al Pedido:</label><br>
            <select name="id_fk_prov" id="id_fk_prov" required>
                <option value="">-- Seleccione el proveedor correspondiente --</option>
                <?php
                $sql_prov2 = "SELECT id_prov, nombre FROM proveedor ORDER BY id_prov ASC";
                $res_prov2 = $conn->query($sql_prov2);
                while ($prov2 = $res_prov2->fetch_assoc()) {
                    $selected = "";
                    if ($comp_editar['id_fk_prov'] == $prov2['id_prov']) {
                        $selected = "selected";
                    }
                    echo '<option value="' . $prov2['id_prov'] . '" ' . $selected . '>' . $prov2['id_prov'] . ' - ' . $prov2['nombre'] . '</option>';
                }
                ?>
            </select><br><br>

            <button type="submit" name="actualizar">Actualizar Orden de Pedido</button>
            <a href="?">Cancelar</a>
        </form>

    <!-- Formulario Insertar -->
    <?php else: ?>
        <h2>Registro de Nueva Orden de Pedido</h2>
        <form action="" method="POST">
            <label>Código Identificador (ID Pedido):</label><br>
            <input type="number" name="id_com" required><br><br>

            <label>Descripción / Detalle del Pedido:</label><br>
            <input type="text" name="nombre" required><br><br>

            <label>Fecha de Emisión del Pedido:</label><br>
            <input type="date" name="fecha" required><br><br>

            <label>Fecha Estimada de Entrega:</label><br>
            <input type="date" name="fecha_entrega"><br><br>

            <label>Cantidad de Unidades Solicitadas:</label><br>
            <input type="number" name="cantidad" required><br><br>

            <label>Monto Total del Pedido:</label><br>
            <input type="number" name="total" step="0.01" required><br><br>

            <label>Observaciones del Pedido:</label><br>
            <textarea name="observaciones" rows="3"></textarea><br><br>

            <label>Empleado Gestor del Pedido:</label><br>
            <select name="id_fk_emp" id="id_fk_emp" required>
                <option value="">-- Seleccione el empleado encargado --</option>
                <?php
                $sql_emp2 = "SELECT id_emp, nombre FROM empleado ORDER BY id_emp ASC";
                $res_emp2 = $conn->query($sql_emp2);
                while ($emp2 = $res_emp2->fetch_assoc()) {
                    echo '<option value="' . $emp2['id_emp'] . '">' . $emp2['id_emp'] . ' - ' . $emp2['nombre'] . '</option>';
                }
                ?>
            </select><br><br>

            <label>Proveedor Asignado al Pedido:</label><br>
            <select name="id_fk_prov" id="id_fk_prov" required>
                <option value="">-- Seleccione el proveedor correspondiente --</option>
                <?php
                $sql_prov2 = "SELECT id_prov, nombre FROM proveedor ORDER BY id_prov ASC";
                $res_prov2 = $conn->query($sql_prov2);
                while ($prov2 = $res_prov2->fetch_assoc()) {
                    echo '<option value="' . $prov2['id_prov'] . '">' . $prov2['id_prov'] . ' - ' . $prov2['nombre'] . '</option>';
                }
                ?>
            </select><br><br>

            <button type="submit" name="guardar">Emitir Orden de Pedido</button>
        </form>
    <?php endif; ?>

    <br>
    <form action="menu.php" method="POST" style="display:inline;">
        <button type="submit">Volver al Menú Principal</button>
    </form>

    <br><br>
    <h2>Consultar de Pedidos</h2>
    <form action="" method="GET">
        <input type="text" name="buscar" placeholder="Ingrese ID de Pedido, Estado o Fecha..." value="<?php echo $busqueda; ?>">
        <button type="submit">Consultar Pedidos</button>
        <?php if (!empty($busqueda)): ?>
            <a href="?">Cancelar</a>
        <?php endif; ?>
    </form>
    <br>
    <hr>

    <!-- Listado de Compras/Pedidos -->
    <h2>Listado General de Pedidos Registrados</h2>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID Pedido</th>
                <th>Descripción / Detalle</th>
                <th>Fecha Emisión</th>
                <th>Fecha Entrega</th>
                <th>Cantidad</th>
                <th>Total Solicitado</th>
                <th>Estado del Pedido</th>
                <th>Observaciones</th>
                <th>Empleado Gestor</th>
                <th>Proveedor Destinatario</th>
                <th>Acciones del Pedido</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($compras && $compras->num_rows > 0): ?>
                <?php while ($fila = $compras->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $fila['id_com']; ?></td>
                        <td><?php echo $fila['nombre']; ?></td>
                        <td><?php echo $fila['fecha']; ?></td>
                        <td><?php echo $fila['fecha_entrega']; ?></td>
                        <td><?php echo $fila['cantidad']; ?></td>
                        <td><?php echo $fila['total']; ?></td>
                        <td><?php echo $fila['estado']; ?></td>
                        <td><?php echo $fila['observaciones']; ?></td>
                        <td><?php echo $fila['id_fk_emp'] . ' - ' . ($fila['nom_empleado'] ?? 'Sin asignar'); ?></td>
                        <td><?php echo $fila['id_fk_prov'] . ' - ' . ($fila['nom_proveedor'] ?? 'Sin asignar'); ?></td>
                        <td>
                            <a href="?accion=editar&id_com=<?php echo $fila['id_com']; ?>">Modificar</a> |
                            <a href="?accion=eliminar&id_com=<?php echo $fila['id_com']; ?>" onclick="return confirm('¿Está seguro de anular e inactivar esta orden de pedido?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11">No se encontraron registros de pedidos en el sistema.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>

<?php
$conn->close();
?>