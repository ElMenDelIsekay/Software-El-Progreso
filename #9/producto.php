<?php
include("conexion.php");

if (!isset($conn) && isset($conexion)) {
    $conn = $conexion;
}

$mensaje = "";
$producto_editar = null;
$busqueda = "";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 1. Eliminar Producto
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_prod'])) {
    $id_eliminar = $_GET['id_prod'];

    try {
        $sql_del = "DELETE FROM producto WHERE id_prod = ?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->bind_param("i", $id_eliminar);
        $stmt_del->execute();

        $mensaje = "<p style='color: green; font-weight: bold;'>Producto con ID " . htmlspecialchars($id_eliminar) . " eliminado correctamente.</p>";
        $stmt_del->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<p style='color: red; font-weight: bold;'>Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// 2. Cargar datos para editar
if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_prod'])) {
    $id_buscar = $_GET['id_prod'];

    try {
        $sql_buscar = "SELECT * FROM producto WHERE id_prod = ?";
        $stmt_buscar = $conn->prepare($sql_buscar);
        $stmt_buscar->bind_param("i", $id_buscar);
        $stmt_buscar->execute();
        $res = $stmt_buscar->get_result();
        $producto_editar = $res->fetch_assoc();
        $stmt_buscar->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<p style='color: red; font-weight: bold;'>Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// 3. Procesar formularios (Guardar y Actualizar)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Guardar nuevo producto
    if (isset($_POST["guardar"])) {
        $id_prod       = $_POST["id_prod"];
        $nombre        = $_POST["nombre"];
        $codigo        = $_POST["codigo"];
        $descripcion   = $_POST["descripcion"];
        $precio        = $_POST["precio"];
        $stock         = $_POST["stock"];
        $fecha_ingreso = $_POST["fecha_ingreso"];
        $stock_minimo  = $_POST["stock_minimo"];
        $id_fk_cat     = $_POST["id_fk_cat"];
        $id_fk_prov    = $_POST["id_fk_prov"];

        $estado = calcularEstado($stock, $stock_minimo);

        try {
            $sql = "INSERT INTO producto (id_prod, nombre, codigo, descripcion, precio, estado, stock, fecha_ingreso, stock_minimo, id_fk_cat, id_fk_prov) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssdsisiii", $id_prod, $nombre, $codigo, $descripcion, $precio, $estado, $stock, $fecha_ingreso, $stock_minimo, $id_fk_cat, $id_fk_prov);
            $stmt->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Producto registrado correctamente.</p>";
            $stmt->close();

            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=guardado");
            exit;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error: El ID " . htmlspecialchars($id_prod) . " ya está registrado.</p>";
            } else {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error al registrar: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }

    // Actualizar producto
    if (isset($_POST["actualizar"])) {
        $id_prod       = $_POST["id_prod"];
        $nombre        = $_POST["nombre"];
        $codigo        = $_POST["codigo"];
        $descripcion   = $_POST["descripcion"];
        $precio        = $_POST["precio"];
        $stock         = $_POST["stock"];
        $fecha_ingreso = $_POST["fecha_ingreso"];
        $stock_minimo  = $_POST["stock_minimo"];
        $id_fk_cat     = $_POST["id_fk_cat"];
        $id_fk_prov    = $_POST["id_fk_prov"];

        $estado = calcularEstado($stock, $stock_minimo);

        try {
            $sql_up = "UPDATE producto SET nombre = ?, codigo = ?, descripcion = ?, precio = ?, estado = ?, stock = ?, fecha_ingreso = ?, stock_minimo = ?, id_fk_cat = ?, id_fk_prov = ? WHERE id_prod = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("sssdsisiii", $nombre, $codigo, $descripcion, $precio, $estado, $stock, $fecha_ingreso, $stock_minimo, $id_fk_cat, $id_fk_prov, $id_prod);
            $stmt_up->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Producto actualizado correctamente.</p>";
            $stmt_up->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<p style='color: red; font-weight: bold;'>Error al actualizar el registro: " . $e->getMessage() . "</p>";
        }
    }
}

// 4. Buscar y listar productos
if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $param_busqueda = "%" . $busqueda . "%";

    $sql_listar = "SELECT p.id_prod, p.nombre, p.codigo, p.descripcion, p.precio, p.estado, p.stock, p.fecha_ingreso, p.stock_minimo, p.id_fk_cat, p.id_fk_prov,
                          c.nombre AS nom_categoria,
                          pr.nombre AS nom_proveedor
                   FROM producto p
                   LEFT JOIN categoria c ON p.id_fk_cat = c.id_cat
                   LEFT JOIN proveedor pr ON p.id_fk_prov = pr.id_prov
                   WHERE p.id_prod LIKE ? OR p.nombre LIKE ? OR p.codigo LIKE ? OR p.estado LIKE ? OR p.fecha_ingreso LIKE ?
                   ORDER BY p.id_prod ASC";
    $stmt_list = $conn->prepare($sql_listar);
    $stmt_list->bind_param("sssss", $param_busqueda, $param_busqueda, $param_busqueda, $param_busqueda, $param_busqueda);
    $stmt_list->execute();
    $productos = $stmt_list->get_result();
} else {
    $sql_listar = "SELECT p.id_prod, p.nombre, p.codigo, p.descripcion, p.precio, p.estado, p.stock, p.fecha_ingreso, p.stock_minimo, p.id_fk_cat, p.id_fk_prov,
                          c.nombre AS nom_categoria,
                          pr.nombre AS nom_proveedor
                   FROM producto p
                   LEFT JOIN categoria c ON p.id_fk_cat = c.id_cat
                   LEFT JOIN proveedor pr ON p.id_fk_prov = pr.id_prov
                   ORDER BY p.id_prod ASC";
    $productos = $conn->query($sql_listar);
}

function calcularEstado($stock, $stock_minimo = 5) {
    if ($stock <= 0) {
        return "Agotado";
    } elseif ($stock <= $stock_minimo) {
        return "Bajo Stock";
    } else {
        return "Disponible";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión Administrativa de Productos</title>
</head>
<body>
    <h1>Gestión de Productos - El Progreso</h1><hr><br>
    <?php echo $mensaje; ?>

    <!-- Formulario Editar -->
    <?php if ($producto_editar): ?>
        <h2>Modificación de Registro de Producto</h2>
        <form action="" method="POST">
            <input type="hidden" name="id_prod" value="<?php echo htmlspecialchars($producto_editar['id_prod']); ?>">

            <label>Código Identificador (ID Producto):</label><br>
            <input type="number" value="<?php echo htmlspecialchars($producto_editar['id_prod']); ?>" disabled><br><br>

            <label>Nombre del Producto:</label><br>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($producto_editar['nombre']); ?>" required><br><br>

            <label>Código / Referencia:</label><br>
            <input type="text" name="codigo" value="<?php echo htmlspecialchars($producto_editar['codigo']); ?>"><br><br>

            <label>Descripción General:</label><br>
            <textarea name="descripcion" rows="3" required><?php echo htmlspecialchars($producto_editar['descripcion']); ?></textarea><br><br>

            <label>Precio Unitario:</label><br>
            <input type="number" step="0.01" name="precio" value="<?php echo htmlspecialchars($producto_editar['precio']); ?>" required><br><br>

            <label>Estado Actual:</label><br>
            <input type="text" value="<?php echo htmlspecialchars($producto_editar['estado']); ?>" disabled>
            <br><br>

            <label>Existencias (Stock):</label><br>
            <input type="number" name="stock" value="<?php echo htmlspecialchars($producto_editar['stock']); ?>" required><br><br>

            <label>Fecha de Ingreso:</label><br>
            <input type="date" name="fecha_ingreso" value="<?php echo htmlspecialchars($producto_editar['fecha_ingreso']); ?>"><br><br>

            <label>Stock Mínimo:</label><br>
            <input type="number" name="stock_minimo" value="<?php echo htmlspecialchars($producto_editar['stock_minimo']); ?>" required><br><br>

            <label>Categoría Asignada:</label><br>
            <select name="id_fk_cat" id="id_fk_cat" required>
                <option value="">-- Seleccione una categoría --</option>
                <?php
                $sql_cat2 = "SELECT id_cat, nombre FROM categoria ORDER BY id_cat ASC";
                $res_cat2 = $conn->query($sql_cat2);
                while ($cat2 = $res_cat2->fetch_assoc()) {
                    $selected = "";
                    if ($producto_editar['id_fk_cat'] == $cat2['id_cat']) {
                        $selected = "selected";
                    }
                    echo '<option value="' . $cat2['id_cat'] . '" ' . $selected . '>' . $cat2['id_cat'] . ' - ' . $cat2['nombre'] . '</option>';
                }
                ?>
            </select><br><br>

            <label>Proveedor Asociado:</label><br>
            <select name="id_fk_prov" id="id_fk_prov" required>
                <option value="">-- Seleccione un proveedor --</option>
                <?php
                $sql_prov2 = "SELECT id_prov, nombre FROM proveedor ORDER BY id_prov ASC";
                $res_prov2 = $conn->query($sql_prov2);
                while ($prov2 = $res_prov2->fetch_assoc()) {
                    $selected = "";
                    if ($producto_editar['id_fk_prov'] == $prov2['id_prov']) {
                        $selected = "selected";
                    }
                    echo '<option value="' . $prov2['id_prov'] . '" ' . $selected . '>' . $prov2['id_prov'] . ' - ' . $prov2['nombre'] . '</option>';
                }
                ?>
            </select><br><br>

            <button type="submit" name="actualizar">Actualizar Información</button>
            <a href="?">Cancelar</a>
        </form>

    <!-- Formulario Insertar -->
    <?php else: ?>
        <h2>Registro de Nuevo Producto</h2>
        <form action="" method="POST">
            <label>Código Identificador (ID Producto):</label><br>
            <input type="number" name="id_prod" required><br><br>

            <label>Nombre del Producto:</label><br>
            <input type="text" name="nombre" required><br><br>

            <label>Código / Referencia:</label><br>
            <input type="text" name="codigo"><br><br>

            <label>Descripción General:</label><br>
            <textarea name="descripcion" rows="3" required></textarea><br><br>

            <label>Precio Unitario:</label><br>
            <input type="number" step="0.01" name="precio" required> <br><br>

            <label>Existencias (Stock):</label><br>
            <input type="number" name="stock" required><br><br>

            <label>Fecha de Ingreso:</label><br>
            <input type="date" name="fecha_ingreso"><br><br>

            <label>Stock Mínimo:</label><br>
            <input type="number" name="stock_minimo" value="5" required><br><br>

            <label>Categoría Asignada:</label><br>
            <select name="id_fk_cat" id="id_fk_cat" required>
                <option value="">-- Seleccione una categoría --</option>
                <?php
                $sql_cat2 = "SELECT id_cat, nombre FROM categoria ORDER BY id_cat ASC";
                $res_cat2 = $conn->query($sql_cat2);
                while ($cat2 = $res_cat2->fetch_assoc()) {
                    echo '<option value="' . $cat2['id_cat'] . '">' . $cat2['id_cat'] . ' - ' . $cat2['nombre'] . '</option>';
                }
                ?>
            </select><br><br>

            <label>Proveedor Asociado:</label><br>
            <select name="id_fk_prov" id="id_fk_prov" required>
                <option value="">-- Seleccione un proveedor --</option>
                <?php
                $sql_prov2 = "SELECT id_prov, nombre FROM proveedor ORDER BY id_prov ASC";
                $res_prov2 = $conn->query($sql_prov2);
                while ($prov2 = $res_prov2->fetch_assoc()) {
                    echo '<option value="' . $prov2['id_prov'] . '">' . $prov2['id_prov'] . ' - ' . $prov2['nombre'] . '</option>';
                }
                ?>
            </select><br><br>

            <button type="submit" name="guardar">Guardar Registro</button>
        </form>
    <?php endif; ?>

    <br>
    <form action="menu.php" method="POST" style="display:inline;">
        <button type="submit">Volver al Menú Principal</button>
    </form>

    <hr>

    <h3>Consulta de Productos</h3>
    <form action="" method="GET">
        <input type="text" name="buscar" placeholder="Ingrese ID, Nombre, Código, Estado o Fecha..." value="<?php echo htmlspecialchars($busqueda); ?>">
        <button type="submit">Ejecutar Búsqueda</button>
        <?php if (!empty($busqueda)): ?>
            <a href="?">Restablecer Vista</a>
        <?php endif; ?>
    </form>

    <!-- Listado de Productos -->
    <h3>Listado General de Productos Registrados</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID Producto</th>
                <th>Nombre</th>
                <th>Código</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Estado</th>
                <th>Stock</th>
                <th>Fecha Ingreso</th>
                <th>Stock Mínimo</th>
                <th>Categoría</th>
                <th>Proveedor</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($productos && $productos->num_rows > 0): ?>
                <?php while ($fila = $productos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['id_prod']); ?></td>
                        <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($fila['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($fila['descripcion']); ?></td>
                        <td>$<?php echo number_format($fila['precio'], 2); ?></td>
                        <td><?php echo htmlspecialchars($fila['estado']); ?></td>
                        <td><?php echo htmlspecialchars($fila['stock']); ?></td>
                        <td><?php echo htmlspecialchars($fila['fecha_ingreso']); ?></td>
                        <td><?php echo htmlspecialchars($fila['stock_minimo']); ?></td>
                        <td><?php echo $fila['id_fk_cat'] . ' - ' . htmlspecialchars($fila['nom_categoria'] ?? 'Sin asignar'); ?></td>
                        <td><?php echo $fila['id_fk_prov'] . ' - ' . htmlspecialchars($fila['nom_proveedor'] ?? 'Sin asignar'); ?></td>
                        <td>
                            <a href="?accion=editar&id_prod=<?php echo $fila['id_prod']; ?>">Editar</a> |
                            <a href="?accion=eliminar&id_prod=<?php echo $fila['id_prod']; ?>" onclick="return confirm('¿Seguro que deseas eliminar este producto?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="12">No se encontraron registros de productos institucionales.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>

<?php
$conn->close();
?>