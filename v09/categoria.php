<?php
include("conexion.php");

if (!isset($conn) && isset($conexion)) {
    $conn = $conexion;
}

$mensaje = "";
$categoria_editar = null;
$busqueda = "";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 1. Eliminar Categoría
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_cat'])) {
    $id_eliminar = $_GET['id_cat'];

    try {
        $sql_del = "DELETE FROM categoria WHERE id_cat = ?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->bind_param("i", $id_eliminar);
        $stmt_del->execute();

        $mensaje = "<p style='color: green; font-weight: bold;'>Categoría con ID " . htmlspecialchars($id_eliminar) . " eliminada correctamente.</p>";
        $stmt_del->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<p style='color: red; font-weight: bold;'>Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// 2. Cargar datos para editar
if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_cat'])) {
    $id_buscar = $_GET['id_cat'];

    try {
        $sql_buscar = "SELECT * FROM categoria WHERE id_cat = ?";
        $stmt_buscar = $conn->prepare($sql_buscar);
        $stmt_buscar->bind_param("i", $id_buscar);
        $stmt_buscar->execute();
        $res = $stmt_buscar->get_result();
        $categoria_editar = $res->fetch_assoc();
        $stmt_buscar->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<p style='color: red; font-weight: bold;'>Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// 3. Procesar formularios (Guardar y Actualizar)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Guardar nueva categoría
    if (isset($_POST["guardar"])) {
        $id_cat      = $_POST["id_cat"];
        $nombre      = $_POST["nombre"];
        $descripcion = $_POST["descripcion"];
        $estado      = $_POST["estado"];

        try {
            $sql = "INSERT INTO categoria (id_cat, nombre, descripcion, estado) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $id_cat, $nombre, $descripcion, $estado);
            $stmt->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Categoría registrada correctamente.</p>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error: El ID " . htmlspecialchars($id_cat) . " ya está registrado.</p>";
            } else {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error al registrar: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }

    // Actualizar categoría
    if (isset($_POST["actualizar"])) {
        $id_cat      = $_POST["id_cat"];
        $nombre      = $_POST["nombre"];
        $descripcion = $_POST["descripcion"];
        $estado      = $_POST["estado"];

        try {
            $sql_up = "UPDATE categoria SET nombre = ?, descripcion = ?, estado = ? WHERE id_cat = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("sssi", $nombre, $descripcion, $estado, $id_cat);
            $stmt_up->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Categoría actualizada correctamente.</p>";
            $stmt_up->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<p style='color: red; font-weight: bold;'>Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// 4. Buscar y listar categorías
if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $param_busqueda = "%" . $busqueda . "%";

    $sql_listar = "SELECT * FROM categoria WHERE id_cat LIKE ? OR nombre LIKE ? OR descripcion LIKE ?";
    $stmt_list = $conn->prepare($sql_listar);
    $stmt_list->bind_param("sss", $param_busqueda, $param_busqueda, $param_busqueda);
    $stmt_list->execute();
    $categorias = $stmt_list->get_result();
} else {
    $sql_listar = "SELECT * FROM categoria ORDER BY id_cat ASC";
    $categorias = $conn->query($sql_listar);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Categorías de Productos</title>
</head>
<body>
    <h1>Gestión de Categorías de Productos - El Progreso</h1><hr><br>

    <?php echo $mensaje; ?>

    <!-- Formulario Editar -->
    <?php if ($categoria_editar): ?>
        <h2>Modificación de Registro de Categoría</h2>
        <form action="" method="POST">
            <input type="hidden" name="id_cat" value="<?php echo htmlspecialchars($categoria_editar['id_cat']); ?>">

            <label>Código Identificador (ID Categoría):</label><br>
            <input type="number" value="<?php echo htmlspecialchars($categoria_editar['id_cat']); ?>" disabled><br><br>

            <label>Nombre de la Categoría:</label><br>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($categoria_editar['nombre']); ?>" required><br><br>

            <label>Descripción:</label><br>
            <textarea name="descripcion" rows="3" required><?php echo htmlspecialchars($categoria_editar['descripcion']); ?></textarea><br><br>

            <label>Estado:</label><br>
            <select name="estado">
                <option value="Activo" <?php if ($categoria_editar['estado'] == 'Activo') echo 'selected'; ?>>Activo</option>
                <option value="Inactivo" <?php if ($categoria_editar['estado'] == 'Inactivo') echo 'selected'; ?>>Inactivo</option>
            </select><br><br>

            <button type="submit" name="actualizar">Actualizar Información</button>
            <a href="?">Cancelar Operación</a>
        </form>

    <!-- Formulario Insertar -->
    <?php else: ?>
        <h2>Registro de Nueva Categoría</h2>
        <form action="" method="POST">
            <label>Código Identificador (ID Categoría):</label><br>
            <input type="number" name="id_cat" required><br><br>

            <label>Nombre de la Categoría:</label><br>
            <input type="text" name="nombre" required><br><br>

            <label>Descripción Detallada:</label><br>
            <textarea name="descripcion" rows="3" required></textarea><br><br>

            <input type="hidden" name="estado" value="Activo">

            <button type="submit" name="guardar">Guardar Registro</button>
        </form>
    <?php endif; ?>

    <br>
    <form action="menu.php" method="POST" style="display:inline;">
        <button type="submit">Volver al Menú Principal</button>
    </form>

    <hr>

    <h3>Consulta y Filtro de Categorías</h3>
    <form action="" method="GET">
        <input type="text" name="buscar" placeholder="Ingrese ID, Nombre o Descripción..." value="<?php echo htmlspecialchars($busqueda); ?>">
        <button type="submit">Ejecutar Búsqueda</button>
        <?php if (!empty($busqueda)): ?>
            <a href="?">Restablecer Vista</a>
        <?php endif; ?>
    </form>

    <!-- Listado de Categorías -->
    <h3>Listado General de Categorías Registradas</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID Categoría</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($categorias && $categorias->num_rows > 0): ?>
                <?php while ($fila = $categorias->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['id_cat']); ?></td>
                        <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($fila['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($fila['estado']); ?></td>
                        <td>
                            <a href="?accion=editar&id_cat=<?php echo $fila['id_cat']; ?>">Editar</a> |
                            <a href="?accion=eliminar&id_cat=<?php echo $fila['id_cat']; ?>" onclick="return confirm('¿Seguro que deseas eliminar esta categoría?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No se encontraron registros de categorías institucionales.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>

<?php
$conn->close();
?>