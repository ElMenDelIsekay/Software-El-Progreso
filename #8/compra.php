<?php
include("conexion.php");

// Compatibilidad con la variable de conexión
if (!isset($conn) && isset($conexion)) {
    $conn = $conexion;
}

$mensaje = "";
$comp_editar = null;
$busqueda = "";

// Activar el modo de reporte de excepciones en MySQL
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// accion eliminar compra bd
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_com'])) {

    $comp_eliminar = $_GET['id_com'];

    try {
        $sql_del = "DELETE FROM compra WHERE id_com = ?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->bind_param("i", $comp_eliminar);
        $stmt_del->execute();

        $mensaje = "<p style='color: green; font-weight: bold;'>Compra con id " . htmlspecialchars($comp_eliminar) .
         " eliminada correctamente.</p>";
        $stmt_del->close();

    } catch (mysqli_sql_exception $e) {
        // Código 1451: Restricción de clave foránea (la compra posee detalles)
        if ($e->getCode() == 1451) {
            $mensaje = "<p style='color: red; font-weight: bold;'>No se puede eliminar la compra con id "
             . htmlspecialchars($comp_eliminar) . " porque tiene detalles de compra registrados.</p>";
        } else {
            $mensaje = "<p style='color: red; font-weight: bold;'>Error al eliminar: "
             . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// 2 accion modificar datos
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
        $mensaje = "<p style='color: red; font-weight: bold;'>Error al cargar datos: " .
        htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// 3 formularios guardar y modificar post
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // crear o guardar nueva compra
    if (isset($_POST["guardar"])) {
        $id_com = $_POST["id_com"];
        $nombre = $_POST["nombre"];
        $fecha = $_POST["fecha"];
        $cantidad = $_POST["cantidad"];
        $total = $_POST["total"];
        $estado = $_POST["estado"];
        $id_fk_emp = $_POST["id_fk_emp"];
        $id_fk_prov = $_POST["id_fk_prov"];

        try {
            $sql = "INSERT INTO compra (id_com, nombre, fecha, cantidad, total, estado, id_fk_emp, id_fk_prov) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issidsii", $id_com, $nombre, $fecha, $cantidad, $total, $estado, $id_fk_emp, $id_fk_prov);
            $stmt->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Compra registrada correctamente.</p>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            // Código 1062: clave primaria duplicada
            if ($e->getCode() == 1062) {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error: El id de compra " . htmlspecialchars($id_com) . " ya está registrada.</p>";
            } else {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error al registrar: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }

    // modificar y/o actualizar compra
    if (isset($_POST["actualizar"])) {
        $id_com = $_POST["id_com"];
        $nombre = $_POST["nombre"];
        $fecha = $_POST["fecha"];
        $cantidad = $_POST["cantidad"];
        $total = $_POST["total"];
        $estado = $_POST["estado"];
        $id_fk_emp = $_POST["id_fk_emp"];
        $id_fk_prov = $_POST["id_fk_prov"];

        try {
            $sql_up = "UPDATE compra SET nombre = ?, fecha = ?, cantidad = ?, total = ?, estado = ?, id_fk_emp = ?, id_fk_prov = ? WHERE id_com = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("ssidsiii", $nombre, $fecha, $cantidad, $total, $estado, $id_fk_emp, $id_fk_prov, $id_com);
            $stmt_up->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Compra actualizada correctamente.</p>";
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

    $sql_listar = "SELECT c.id_com, c.nombre, c.fecha, c.cantidad, c.total, c.estado,
                          c.id_fk_emp, c.id_fk_prov,
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
    $sql_listar = "SELECT c.id_com, c.nombre, c.fecha, c.cantidad, c.total, c.estado,
                          c.id_fk_emp, c.id_fk_prov,
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
    <title>Ferretería - Compras</title>
</head>
<body>
    <?php echo $mensaje ?>

    <?php if ($comp_editar) : ?>
        <h2>Editar Compra</h2>
        <form action="" method="POST">
            <input type="hidden" name="id_com" value="<?php echo htmlspecialchars($comp_editar['id_com']); ?>">

            <label>ID Compra:</label><br>
            <input type="number" value="<?php echo htmlspecialchars($comp_editar['id_com']); ?>" disabled> <br><br>

            <label>Nombre:</label><br>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($comp_editar['nombre']); ?>" required> <br><br>

            <label>Fecha:</label><br>
            <input type="date" name="fecha" value="<?php echo htmlspecialchars($comp_editar['fecha']); ?>" required> <br><br>

            <label>Cantidad:</label><br>
            <input type="number" name="cantidad" value="<?php echo htmlspecialchars($comp_editar['cantidad']); ?>" required> <br><br>

            <label>Total:</label><br>
            <input type="number" name="total" step="0.01" value="<?php echo htmlspecialchars($comp_editar['total']); ?>" required> <br><br>

<!---opciones de estado de la compra editar subir informacion -->

            <label>Estado:</label><br>
            <select name="estado" id="estado" required>
                <option value="Pendiente" <?php echo ($comp_editar['estado'] === 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                <option value="Recibida" <?php echo ($comp_editar['estado'] === 'Recibida') ? 'selected' : ''; ?>>Recibida</option>
                <option value="Cancelada" <?php echo ($comp_editar['estado'] === 'Cancelada') ? 'selected' : ''; ?>>Cancelada</option>
            <?php echo htmlspecialchars($comp_editar['estado']); ?>" </select> <br><br>


            <label>Empleado:</label><br>
            <select name="id_fk_emp" id="id_fk_emp" required>
                <option value="">-- Seleccione un empleado --</option>
                <?php
                $sql_emp2 = "SELECT id_emp, nombre FROM empleado ORDER BY id_emp ASC";
                $res_emp2 = $conn->query($sql_emp2);
                while ($emp2 = $res_emp2->fetch_assoc()) {
                    $selected = ($comp_editar['id_fk_emp'] == $emp2['id_emp']) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($emp2['id_emp']) . '" ' . $selected . '>'
                        . htmlspecialchars($emp2['id_emp'] . ' - ' . $emp2['nombre'])
                        . '</option>';
                }
                ?>
            </select> <br><br>
                
                    <label>Proveedor:</label><br>
            <select name="id_fk_prov" id="id_fk_prov" required>
                <option value="">-- Seleccione un proveedor --</option>
                <?php
                $sql_prov2 = "SELECT id_prov, nombre FROM proveedor ORDER BY id_prov ASC";
                $res_prov2 = $conn->query($sql_prov2);
                while ($prov2 = $res_prov2->fetch_assoc()) {
                    $selected = ($comp_editar['id_fk_prov'] == $prov2['id_prov']) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($prov2['id_prov']) . '" ' . $selected . '>'
                        . htmlspecialchars($prov2['id_prov'] . ' - ' . $prov2['nombre'])
                        . '</option>';
                }
                ?>
            </select> <br><br>        

            <button type="submit" name="actualizar">Actualizar Compra</button>
            <a href="?">Cancelar</a>
        </form>
    <?php else: ?>
        <h2>Ingresar Compra</h2>
        <form action="" method="POST">

            <label>ID Compra:</label><br>
            <input type="number" name="id_com" required> <br><br>

            <label>Nombre:</label><br>
            <input type="text" name="nombre" required> <br><br>

            <label>Fecha:</label><br>
            <input type="date" name="fecha" required> <br><br>

            <label>Cantidad:</label><br>
            <input type="number" name="cantidad" required> <br><br>

            <label>Total:</label><br>
            <input type="number" name="total" step="0.01" required> <br><br>

        <!--opciones de estado de la compra -->

            <label>Estado:</label><br>
            <select name="estado" id="estado" required>
                <option value="">Seleccione un estado</option>
                <option value="Pendiente">Pendiente</option>
                <option value="Recibida">Recibida</option>
                <option value="Cancelada">Cancelada</option>
            </select>
            <br><br>

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

            <label>Provedor:</label><br>
                        <select name="id_fk_prov" id="id_fk_prov" required>
                            <option value="">-- Seleccione un provedor --</option>
                            <?php
                            $sql_prov2 = "SELECT id_prov, nombre FROM proveedor ORDER BY id_prov ASC";
                            $res_prov2 = $conn->query($sql_prov2);
                            while ($prov2 = $res_prov2->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($prov2['id_prov']) . '">'
                                    . htmlspecialchars($prov2['id_prov'] . ' - ' . $prov2['nombre'])
                                    . '</option>';
                            }
                            ?>
                        </select> <br><br>

            <button type="submit" name="guardar">Guardar Compra</button>
        </form>
    <?php endif; ?>

    <br>
    <form action="menu.php" method="POST" style="display: inline;">
        <button type="submit">Menu Principal</button>
    </form>
    <h3>Buscar Compra</h3>
    <form action="" method="GET">
        <input type="text" name="buscar" placeholder="Ingrese ID, estado o fecha..." value="<?php echo htmlspecialchars($busqueda); ?>">
        <button type="submit">Buscar</button>
        <?php if (!empty($busqueda)): ?>
            <a href="?">Mostrar todos</a>
        <?php endif; ?>
    </form>
    <br>
            <hr><hr>
    <h3>Listado de compras</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Fecha</th>
                <th>Cantidad</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Empleado</th>
                <th>Proveedor</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        
            <?php if ($compras && $compras->num_rows > 0): ?>
                <?php while ($fila = $compras->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['id_com']); ?></td>
                        <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($fila['fecha']); ?></td>
                        <td><?php echo htmlspecialchars($fila['cantidad']); ?></td>
                        <td><?php echo htmlspecialchars($fila['total']); ?></td>
                        <td><?php echo htmlspecialchars($fila['estado']); ?></td>
                        <td><?php echo $fila['id_fk_emp'] . ' - ' . htmlspecialchars($fila['nom_empleado'] ?? 'Sin asignar'); ?></td>
                        <td><?php echo $fila['id_fk_prov'] . ' - ' . htmlspecialchars($fila['nom_proveedor'] ?? 'Sin asignar'); ?></td>
                        <td>
                            <a href="?accion=editar&id_com=<?php echo $fila['id_com']; ?>">Editar</a>
                            <a href="?accion=eliminar&id_com=<?php echo $fila['id_com']; ?>"
                            onclick="return confirm('¿seguro que desea eliminar esta compra?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9">No se encontraron compras.....</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
<?php
$conn->close();
?>