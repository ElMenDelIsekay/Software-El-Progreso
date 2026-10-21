<?php
include("conexion.php");

if (!isset($conn) && isset($conexion)) {
    $conn = $conexion;
}

$mensaje = "";
$cliente_editar = null;
$busqueda = "";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 1. Eliminar Cliente
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_cli'])) {
    $id_eliminar = $_GET['id_cli'];

    try {
        $sql_del = "DELETE FROM cliente WHERE id_cli = ?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->bind_param("i", $id_eliminar);
        $stmt_del->execute();

        $mensaje = "<p style='color: green; font-weight: bold;'>Cliente con ID " . htmlspecialchars($id_eliminar) . " eliminado correctamente.</p>";
        $stmt_del->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<p style='color: red; font-weight: bold;'>Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// 2. Cargar datos para editar
if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_cli'])) {
    $id_buscar = $_GET['id_cli'];

    try {
        $sql_buscar = "SELECT * FROM cliente WHERE id_cli = ?";
        $stmt_buscar = $conn->prepare($sql_buscar);
        $stmt_buscar->bind_param("i", $id_buscar);
        $stmt_buscar->execute();
        $res = $stmt_buscar->get_result();
        $cliente_editar = $res->fetch_assoc();
        $stmt_buscar->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<p style='color: red; font-weight: bold;'>Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// 3. Procesar formularios (Guardar y Actualizar)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Guardar nuevo cliente
    if (isset($_POST["guardar"])) {
        $id_cli        = $_POST["id_cli"];
        $nombre        = $_POST["nombre"];
        $telefono      = $_POST["telefono"];
        $direccion     = $_POST["direccion"];
        $correo        = $_POST["correo"];
        $tipo_cliente  = $_POST["tipo_cliente"];
        $estado        = $_POST["estado"];

        try {
            $sql = "INSERT INTO cliente (id_cli, nombre, telefono, direccion, correo, tipo_cliente, estado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssss", $id_cli, $nombre, $telefono, $direccion, $correo, $tipo_cliente, $estado);
            $stmt->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Cliente registrado correctamente.</p>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error: El ID " . htmlspecialchars($id_cli) . " ya está registrado.</p>";
            } else {
                $mensaje = "<p style='color: red; font-weight: bold;'>Error al registrar: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }

    // Actualizar cliente
    if (isset($_POST["actualizar"])) {
        $id_cli        = $_POST["id_cli"];
        $nombre        = $_POST["nombre"];
        $telefono      = $_POST["telefono"];
        $direccion     = $_POST["direccion"];
        $correo        = $_POST["correo"];
        $tipo_cliente  = $_POST["tipo_cliente"];
        $estado        = $_POST["estado"];

        try {
            $sql_up = "UPDATE cliente 
                       SET nombre = ?, telefono = ?, direccion = ?, correo = ?, tipo_cliente = ?, estado = ? 
                       WHERE id_cli = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("ssssssi", $nombre, $telefono, $direccion, $correo, $tipo_cliente, $estado, $id_cli);
            $stmt_up->execute();

            $mensaje = "<p style='color: green; font-weight: bold;'>Cliente actualizado correctamente.</p>";
            $stmt_up->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<p style='color: red; font-weight: bold;'>Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// 4. Buscar y listar clientes
if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $param_busqueda = "%" . $busqueda . "%";

    $sql_listar = "SELECT * FROM cliente 
                   WHERE id_cli LIKE ? OR nombre LIKE ? OR telefono LIKE ? OR correo LIKE ? OR direccion LIKE ?";
    $stmt_list = $conn->prepare($sql_listar);
    $stmt_list->bind_param("sssss", $param_busqueda, $param_busqueda, $param_busqueda, $param_busqueda, $param_busqueda);
    $stmt_list->execute();
    $clientes = $stmt_list->get_result();
} else {
    $sql_listar = "SELECT * FROM cliente ORDER BY id_cli ASC";
    $clientes = $conn->query($sql_listar);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Clientes - El Progreso </title>
</head>
<body>
    <h1>Gestión de Clientes - El Progreso</h1>
    <hr>
    <br>

    <?php echo $mensaje; ?>

    <!-- Formulario Editar -->
    <?php if ($cliente_editar): ?>
        <h2>Modificación de Registro de Cliente</h2>
        <form action="" method="POST">
            <input type="hidden" name="id_cli" value="<?php echo htmlspecialchars($cliente_editar['id_cli']); ?>">

            <label>Código Identificador (ID Cliente):</label><br>
            <input type="number" value="<?php echo htmlspecialchars($cliente_editar['id_cli']); ?>" disabled><br><br>

            <label>Nombre Completo / Razón Social:</label><br>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($cliente_editar['nombre']); ?>" required><br><br>

            <label>Número de Contacto (Teléfono):</label><br>
            <input type="text" name="telefono" value="<?php echo htmlspecialchars($cliente_editar['telefono']); ?>" required><br><br>

            <label>Dirección:</label><br>
            <input type="text" name="direccion" value="<?php echo htmlspecialchars($cliente_editar['direccion']); ?>"><br><br>

            <label>Correo Electrónico:</label><br>
            <input type="email" name="correo" value="<?php echo htmlspecialchars($cliente_editar['correo']); ?>"><br><br>

            <label>Tipo de Cliente:</label><br>
            <select name="tipo_cliente">
                <option value="Natural" <?php if ($cliente_editar['tipo_cliente'] == 'Natural') echo 'selected'; ?>>Natural</option>
                <option value="Empresa" <?php if ($cliente_editar['tipo_cliente'] == 'Empresa') echo 'selected'; ?>>Empresa</option>
            </select><br><br>

            <label>Estado:</label><br>
            <select name="estado">
                <option value="Activo" <?php if ($cliente_editar['estado'] == 'Activo') echo 'selected'; ?>>Activo</option>
                <option value="Inactivo" <?php if ($cliente_editar['estado'] == 'Inactivo') echo 'selected'; ?>>Inactivo</option>
            </select><br><br>

            <button type="submit" name="actualizar">Actualizar Información</button>
            <a href="?">Cancelar Operación</a>
        </form>

    <!-- Formulario Insertar -->
    <?php else: ?>
        <h2>Registro de Nuevo Cliente</h2>
        <form action="" method="POST">
            <label>Código Identificador (ID Cliente):</label><br>
            <input type="number" name="id_cli" required><br><br>

            <label>Nombre Completo / Razón Social:</label><br>
            <input type="text" name="nombre" required><br><br>

            <label>Número de Contacto (Teléfono):</label><br>
            <input type="text" name="telefono" required><br><br>

            <label>Dirección:</label><br>
            <input type="text" name="direccion"><br><br>

            <label>Correo Electrónico:</label><br>
            <input type="email" name="correo"><br><br>

            <label>Tipo de Cliente:</label><br>
            <select name="tipo_cliente">
                <option value="Natural">Natural</option>
                <option value="Empresa">Empresa</option>
            </select><br><br>

            <input type="hidden" name="estado" value="Activo">

            <button type="submit" name="guardar">Guardar Registro</button>
        </form>
    <?php endif; ?>

    <br>
    <form action="menu.php" method="POST" style="display:inline;">
        <button type="submit">Volver al Menú Principal</button>
    </form>

    <hr>

    <h3>Consulta y Filtro de Clientes</h3>
    <form action="" method="GET">
        <input type="text" name="buscar" placeholder="Ingrese ID, Nombre, Teléfono, Correo o Dirección..." value="<?php echo htmlspecialchars($busqueda); ?>">
        <button type="submit">Ejecutar Búsqueda</button>
        <?php if (!empty($busqueda)): ?>
            <a href="?">Restablecer Vista</a>
        <?php endif; ?>
    </form>

    <!-- Listado de Clientes -->
    <h3>Listado General de Clientes Registrados</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID Cliente</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>Correo</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($clientes && $clientes->num_rows > 0): ?>
                <?php while ($fila = $clientes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['id_cli']); ?></td>
                        <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($fila['telefono']); ?></td>
                        <td><?php echo htmlspecialchars($fila['direccion']); ?></td>
                        <td><?php echo htmlspecialchars($fila['correo']); ?></td>
                        <td><?php echo htmlspecialchars($fila['tipo_cliente']); ?></td>
                        <td><?php echo htmlspecialchars($fila['estado']); ?></td>
                        <td>
                            <a href="?accion=editar&id_cli=<?php echo $fila['id_cli']; ?>">Editar</a> |
                            <a href="?accion=eliminar&id_cli=<?php echo $fila['id_cli']; ?>" onclick="return confirm('¿Seguro que deseas eliminar este cliente?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No se encontraron registros de clientes institucionales.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>

<?php
$conn->close();
?>