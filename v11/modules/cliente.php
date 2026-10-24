<?php
/* ========== MÓDULO DE CLIENTES ========== */
include("../config/conexion.php");

$rol = isset($_GET['rol']) ? $_GET['rol'] : (isset($_POST['rol']) ? $_POST['rol'] : '');

if (empty($rol)) {
    header("Location: ../index.html");
    exit();
}

$permisos = [
    'Administrador' => ['producto', 'categoria', 'compra', 'factura', 'cliente', 'proveedor', 'empleado', 'cargo'],
    'Vendedor'      => ['producto', 'factura', 'cliente'],
    'Bodeguero'     => ['producto', 'categoria', 'compra', 'proveedor']
];

$modulo_actual = basename($_SERVER['PHP_SELF'], ".php");

if (!in_array($modulo_actual, $permisos[$rol] ?? [])) {
    echo "<script>
        alert('Acceso denegado. Su rol (" . $rol . ") no tiene permiso para acceder a este módulo.');
        window.location.href = '../menu.php?rol=" . urlencode($rol) . "';
    </script>";
    exit();
}

$mensaje = "";
$cliente_editar = null;
$busqueda = "";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_cli'])) {
    $id_eliminar = $_GET['id_cli'];
    try {
        $stmt = $conn->prepare("DELETE FROM cliente WHERE id_cli = ?");
        $stmt->bind_param("i", $id_eliminar);
        $stmt->execute();
        $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Cliente con ID " . htmlspecialchars($id_eliminar) . " eliminado correctamente.</div>";
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_cli'])) {
    $id_buscar = $_GET['id_cli'];
    try {
        $stmt = $conn->prepare("SELECT * FROM cliente WHERE id_cli = ?");
        $stmt->bind_param("i", $id_buscar);
        $stmt->execute();
        $cliente_editar = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["guardar"])) {
        $id_cli = $_POST["id_cli"];
        $nombre = $_POST["nombre"];
        $telefono = $_POST["telefono"];
        $direccion = $_POST["direccion"];
        $correo = $_POST["correo"];
        $tipo_cliente = $_POST["tipo_cliente"];
        $estado = $_POST["estado"];
        try {
            $stmt = $conn->prepare("INSERT INTO cliente (id_cli, nombre, telefono, direccion, correo, tipo_cliente, estado) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $id_cli, $nombre, $telefono, $direccion, $correo, $tipo_cliente, $estado);
            $stmt->execute();
            $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Cliente registrado correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = ($e->getCode() == 1062)
                ? "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error: El ID " . htmlspecialchars($id_cli) . " ya está registrado.</div>"
                : "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al registrar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    if (isset($_POST["actualizar"])) {
        $id_cli = $_POST["id_cli"];
        $nombre = $_POST["nombre"];
        $telefono = $_POST["telefono"];
        $direccion = $_POST["direccion"];
        $correo = $_POST["correo"];
        $tipo_cliente = $_POST["tipo_cliente"];
        $estado = $_POST["estado"];
        try {
            $stmt = $conn->prepare("UPDATE cliente SET nombre = ?, telefono = ?, direccion = ?, correo = ?, tipo_cliente = ?, estado = ? WHERE id_cli = ?");
            $stmt->bind_param("ssssssi", $nombre, $telefono, $direccion, $correo, $tipo_cliente, $estado, $id_cli);
            $stmt->execute();
            $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Cliente actualizado correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $param = "%" . $busqueda . "%";
    $stmt = $conn->prepare("SELECT * FROM cliente WHERE id_cli LIKE ? OR nombre LIKE ? OR telefono LIKE ? OR correo LIKE ? OR direccion LIKE ?");
    $stmt->bind_param("sssss", $param, $param, $param, $param, $param);
    $stmt->execute();
    $clientes = $stmt->get_result();
} else {
    $clientes = $conn->query("SELECT * FROM cliente ORDER BY id_cli ASC");
}

$titulo_pagina = "Gestión de Clientes";
$ruta_base = "../";
include("../includes/header.php");
?>

<h1 class="titulo-pagina">Gestión de Clientes</h1>
<p class="subtitulo-pagina">Registre, modifique y consulte la información de los clientes de la ferretería.</p>

<?= $mensaje ?>

<?php if ($cliente_editar): ?>
  <div class="tarjeta-seccion">
    <h2><i class="fa-solid fa-pen"></i> Modificación de Registro de Cliente</h2>
    <form action="" method="POST">
      <input type="hidden" name="id_cli" value="<?= htmlspecialchars($cliente_editar['id_cli']) ?>">
      <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
      <div class="form-grid">
        <div class="grupo-campo">
          <label>Código Identificador (ID Cliente):</label>
          <input type="number" value="<?= htmlspecialchars($cliente_editar['id_cli']) ?>" disabled>
        </div>
        <div class="grupo-campo">
          <label>Nombre Completo / Razón Social:</label>
          <input type="text" name="nombre" value="<?= htmlspecialchars($cliente_editar['nombre']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Número de Contacto (Teléfono):</label>
          <input type="text" name="telefono" value="<?= htmlspecialchars($cliente_editar['telefono']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Correo Electrónico:</label>
          <input type="email" name="correo" value="<?= htmlspecialchars($cliente_editar['correo']) ?>">
        </div>
        <div class="grupo-campo ancho-completo">
          <label>Dirección:</label>
          <input type="text" name="direccion" value="<?= htmlspecialchars($cliente_editar['direccion']) ?>">
        </div>
        <div class="grupo-campo">
          <label>Tipo de Cliente:</label>
          <select name="tipo_cliente">
            <option value="Natural" <?= $cliente_editar['tipo_cliente'] == 'Natural' ? 'selected' : '' ?>>Natural</option>
            <option value="Empresa" <?= $cliente_editar['tipo_cliente'] == 'Empresa' ? 'selected' : '' ?>>Empresa</option>
          </select>
        </div>
        <div class="grupo-campo">
          <label>Estado:</label>
          <select name="estado">
            <option value="Activo" <?= $cliente_editar['estado'] == 'Activo' ? 'selected' : '' ?>>Activo</option>
            <option value="Inactivo" <?= $cliente_editar['estado'] == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
          </select>
        </div>
      </div>
      <div class="acciones-form">
        <button type="submit" name="actualizar" class="boton-primario"><i class="fa-solid fa-floppy-disk"></i> Actualizar Información</button>
        <a href="?rol=<?= urlencode($rol) ?>" class="boton-secundario">Cancelar</a>
      </div>
    </form>
  </div>
<?php else: ?>
  <div class="tarjeta-seccion">
    <h2><i class="fa-solid fa-plus"></i> Registro de Nuevo Cliente</h2>
    <form action="" method="POST">
      <div class="form-grid">
        <div class="grupo-campo">
          <label>Código Identificador (ID Cliente):</label>
          <input type="number" name="id_cli" required>
        </div>
        <div class="grupo-campo">
          <label>Nombre Completo / Razón Social:</label>
          <input type="text" name="nombre" required>
        </div>
        <div class="grupo-campo">
          <label>Número de Contacto (Teléfono):</label>
          <input type="text" name="telefono" required>
        </div>
        <div class="grupo-campo">
          <label>Correo Electrónico:</label>
          <input type="email" name="correo">
        </div>
        <div class="grupo-campo ancho-completo">
          <label>Dirección:</label>
          <input type="text" name="direccion">
        </div>
        <div class="grupo-campo">
          <label>Tipo de Cliente:</label>
          <select name="tipo_cliente">
            <option value="Natural">Natural</option>
            <option value="Empresa">Empresa</option>
          </select>
        </div>
        <input type="hidden" name="estado" value="Activo">
        <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
      </div>
      <div class="acciones-form">
        <button type="submit" name="guardar" class="boton-primario"><i class="fa-solid fa-paper-plane"></i> Guardar Registro</button>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="tarjeta-seccion">
  <h2><i class="fa-solid fa-magnifying-glass"></i> Consulta y Filtro de Clientes</h2>
  <form action="" method="GET" class="form-busqueda">
    <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
    <input type="text" name="buscar" placeholder="Ingrese ID, Nombre, Teléfono, Correo o Dirección..." value="<?= htmlspecialchars($busqueda) ?>">
    <button type="submit" class="boton-primario"><i class="fa-solid fa-magnifying-glass"></i> Ejecutar Búsqueda</button>
    <?php if (!empty($busqueda)): ?>
      <a href="?rol=<?= urlencode($rol) ?>" class="boton-secundario">Restablecer Vista</a>
    <?php endif; ?>
  </form>
</div>

<div class="tarjeta-seccion">
  <h2><i class="fa-solid fa-list"></i> Listado General de Clientes Registrados</h2>
  <div class="tabla-contenedor">
    <table class="tabla-datos">
      <thead>
        <tr>
          <th>ID</th><th>Nombre</th><th>Teléfono</th><th>Dirección</th><th>Correo</th><th>Tipo</th><th>Estado</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($clientes && $clientes->num_rows > 0): ?>
          <?php while ($fila = $clientes->fetch_assoc()): ?>
            <tr>
              <td><strong><?= htmlspecialchars($fila['id_cli']) ?></strong></td>
              <td><?= htmlspecialchars($fila['nombre']) ?></td>
              <td><?= htmlspecialchars($fila['telefono']) ?></td>
              <td><?= htmlspecialchars($fila['direccion']) ?></td>
              <td><?= htmlspecialchars($fila['correo']) ?></td>
              <td>
                <span class="tipo <?= strtolower($fila['tipo_cliente']) === 'empresa' ? 'empresa' : 'natural' ?>">
                  <?= htmlspecialchars($fila['tipo_cliente']) ?>
                </span>
              </td>
              <td>
                <span class="estado <?= $fila['estado'] === 'Activo' ? 'activo' : 'inactivo' ?>">
                  <?= htmlspecialchars($fila['estado']) ?>
                </span>
              </td>
              <td class="acciones-tabla">
                <a href="?accion=editar&id_cli=<?= $fila['id_cli'] ?>&rol=<?= urlencode($rol) ?>" class="modificar"><i class="fa-solid fa-pen"></i> Editar</a>
                <a href="?accion=eliminar&id_cli=<?= $fila['id_cli'] ?>&rol=<?= urlencode($rol) ?>" class="eliminar" onclick="return confirm('¿Seguro que deseas eliminar este cliente?');"><i class="fa-solid fa-trash"></i> Eliminar</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="8" class="sin-registros">No se encontraron registros de clientes.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
include("../includes/footer.php");
$conn->close();
?>