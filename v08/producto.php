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
        $id_prod    = $_POST["id_prod"];
        $nombre     = $_POST["nombre"];
        $descripcion= $_POST["descripcion"];
        $precio     = $_POST["precio"];
        $estado     = $_POST["estado"];
        $stock      = $_POST["stock"];
        $id_fk_cat  = $_POST["id_fk_cat"];
        $id_fk_prov = $_POST["id_fk_prov"];

        $estado = calcularEstado($stock);

        try {
            $sql = "INSERT INTO producto (id_prod, nombre, descripcion, precio, estado, stock, id_fk_cat, id_fk_prov) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issdsiii", $id_prod, $nombre, $descripcion, $precio, $estado, $stock, $id_fk_cat, $id_fk_prov);
            $stmt->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Producto registrado correctamente.</p>";
            $stmt->close();

            // Redirigir para evitar reenvío y mostrar datos frescos
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
        $id_prod    = $_POST["id_prod"];
        $nombre     = $_POST["nombre"];
        $descripcion= $_POST["descripcion"];
        $precio     = $_POST["precio"];
       
        $stock      = $_POST["stock"];
        $id_fk_cat  = $_POST["id_fk_cat"];
        $id_fk_prov = $_POST["id_fk_prov"];
    
        $estado = calcularEstado($stock);

        try {
            $sql_up = "UPDATE producto SET nombre = ?, descripcion = ?, precio = ?, estado = ?, stock = ?, id_fk_cat = ?, id_fk_prov = ? WHERE id_prod = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("ssdsiiii", $nombre, $descripcion, $precio, $estado, $stock, $id_fk_cat, $id_fk_prov, $id_prod);
            $stmt_up->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Producto actualizado correctamente.</p>";
            $stmt_up->close();

            // Redirigir para que se recargue la página y se lean los datos nuevos
            header("Location: " . $_SERVER['PHP_SELF'] . "?accion=editar&id_prod=" . urlencode($id_prod) . "&msg=actualizado");
            exit;
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<p style='color: red; font-weight: bold;'>Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// 4. Buscar y listar productos
if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $param_busqueda = "%" . $busqueda . "%";

    $sql_listar = "SELECT * FROM producto WHERE id_prod LIKE ? OR nombre LIKE ? OR descripcion LIKE ? OR estado LIKE ?";
    $stmt_list = $conn->prepare($sql_listar);
    $stmt_list->bind_param("ssss", $param_busqueda, $param_busqueda, $param_busqueda, $param_busqueda);
    $stmt_list->execute();
    $productos = $stmt_list->get_result();
} else {
    $sql_listar = "SELECT * FROM producto ORDER BY id_prod ASC";
    $productos = $conn->query($sql_listar);
}

function calcularEstado($stock) {
    if ($stock <= 0) {
        return "Agotado";
    } elseif ($stock <= 10) {
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
    <title>Gestión de Productos</title>
</head>
<body>

    <?php echo $mensaje; ?>

    <!-- Formulario Editar -->
    <?php if ($producto_editar): ?>
        <h2>Editar Producto</h2>
        <form action="" method="POST">
            <input type="hidden" name="id_prod" value="<?php echo htmlspecialchars($producto_editar['id_prod']); ?>">

            <label>ID Producto:</label><br>
            <input type="number" value="<?php echo htmlspecialchars($producto_editar['id_prod']); ?>" disabled><br><br>

            <label>Nombre Producto:</label><br>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($producto_editar['nombre']); ?>" required><br><br>

            <label>Descripción:</label><br>
            <textarea name="descripcion" rows="3" required><?php echo htmlspecialchars($producto_editar['descripcion']); ?></textarea><br><br>

            <label>Precio:</label><br>
            <input type="number" step="0.01" name="precio" value="<?php echo htmlspecialchars($producto_editar['precio']); ?>" required><br><br>

            <label>Estado actual:</label><br>
            <input type="text" value="<?php echo htmlspecialchars($producto_editar['estado']); ?>" disabled>
            <br><br>

            <label>Stock:</label><br>
            <input type="number" name="stock" value="<?php echo htmlspecialchars($producto_editar['stock']); ?>" required><br><br>

            <label>ID Categoría (FK):</label><br>
            <input type="number" name="id_fk_cat" value="<?php echo htmlspecialchars($producto_editar['id_fk_cat']); ?>" required><br><br>

            <label>ID Proveedor (FK):</label><br>
            <input type="number" name="id_fk_prov" value="<?php echo htmlspecialchars($producto_editar['id_fk_prov']); ?>" required><br><br>

            <button type="submit" name="actualizar">Actualizar Producto</button>
            <a href="?">Cancelar</a>
        </form>

    <!-- Formulario Insertar -->
    <?php else: ?>
        <h2>Ingresar Producto</h2>
        <form action="" method="POST">
            <label>ID Producto:</label><br>
            <input type="number" name="id_prod" required><br><br>

            <label>Nombre Producto:</label><br>
            <input type="text" name="nombre" required><br><br>

            <label>Descripción:</label><br>
            <textarea name="descripcion" rows="3" required></textarea><br><br>

            <label>Precio:</label><br>
            <input type="number" step="0.01" name="precio" required> <br><br>

            <label>Stock:</label><br>
            <input type="number" name="stock" required><br><br>

            <label>ID Categoría (FK):</label><br>
            <input type="number" name="id_fk_cat" required><br><br>

            <label>ID Proveedor (FK):</label><br>
            <input type="number" name="id_fk_prov" required><br><br>

            <button type="submit" name="guardar">Guardar Producto</button>
        </form>
    <?php endif; ?>

    <br>
    <form action="menu.php" method="POST" style="display:inline;">
        <button type="submit">Menú Principal</button>
    </form>

    <hr>

    <h3>Buscar Producto</h3>
    <form action="" method="GET">
        <input type="text" name="buscar" placeholder="Ingrese ID, Nombre, Descripción o Estado..." value="<?php echo htmlspecialchars($busqueda); ?>">
        <button type="submit">Buscar</button>
        <?php if (!empty($busqueda)): ?>
            <a href="?">Mostrar todos</a>
        <?php endif; ?>
    </form>

    <!-- Listado de Productos -->
    <h3>Listado de Productos</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID Producto</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Estado</th>
                <th>Stock</th>
                <th>ID Cat.</th>
                <th>ID Prov.</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($productos && $productos->num_rows > 0): ?>
                <?php while ($fila = $productos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['id_prod']); ?></td>
                        <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($fila['descripcion']); ?></td>
                        <td>$<?php echo number_format($fila['precio'], 2); ?></td>
                        <td><?php echo htmlspecialchars($fila['estado']); ?></td>
                        <td><?php echo htmlspecialchars($fila['stock']); ?></td>
                        <td><?php echo htmlspecialchars($fila['id_fk_cat']); ?></td>
                        <td><?php echo htmlspecialchars($fila['id_fk_prov']); ?></td>
                        <td>
                            <a href="?accion=editar&id_prod=<?php echo $fila['id_prod']; ?>">Editar</a> |
                            <a href="?accion=eliminar&id_prod=<?php echo $fila['id_prod']; ?>" onclick="return confirm('¿Seguro que deseas eliminar este producto?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9">No se encontraron productos.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>

<?php
$conn->close();
?>