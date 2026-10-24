<?php
/* ========== MÓDULO DE COMPRAS ========== */
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
$comp_editar = null;
$busqueda = "";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_com'])) {
    $id_eliminar = $_GET['id_com'];
    try {
        $stmt = $conn->prepare("DELETE FROM compra WHERE id_com = ?");
        $stmt->bind_param("i", $id_eliminar);
        $stmt->execute();
        $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Compra con ID " . $id_eliminar . " eliminada correctamente.</div>";
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = ($e->getCode() == 1451)
            ? "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> No se puede eliminar la compra con ID " . $id_eliminar . " porque tiene detalles asociados.</div>"
            : "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al eliminar: " . $e->getMessage() . "</div>";
    }
}

if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_com'])) {
    $id_buscar = $_GET['id_com'];
    try {
        $stmt = $conn->prepare("SELECT * FROM compra WHERE id_com = ?");
        $stmt->bind_param("i", $id_buscar);
        $stmt->execute();
        $comp_editar = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al cargar datos: " . $e->getMessage() . "</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["guardar"])) {
        $id_com = $_POST["id_com"];
        $nombre = $_POST["nombre"];
        $fecha = $_POST["fecha"];
        $fecha_entrega = $_POST["fecha_entrega"];
        $cantidad = $_POST["cantidad"];
        $total = $_POST["total"];
        $estado = $_POST["estado"];
        $observaciones = $_POST["observaciones"];
        $id_fk_emp = $_POST["id_fk_emp"];
        $id_fk_prov = $_POST["id_fk_prov"];
        try {
            $stmt = $conn->prepare("INSERT INTO compra (id_com, nombre, fecha, fecha_entrega, cantidad, total, estado, observaciones, id_fk_emp, id_fk_prov) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssidssii", $id_com, $nombre, $fecha, $fecha_entrega, $cantidad, $total, $estado, $observaciones, $id_fk_emp, $id_fk_prov);
            $stmt->execute();
            $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Compra registrada correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = ($e->getCode() == 1062)
                ? "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error: El ID " . $id_com . " ya está registrado.</div>"
                : "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al registrar: " . $e->getMessage() . "</div>";
        }
    }
    if (isset($_POST["actualizar"])) {
        $id_com = $_POST["id_com"];
        $nombre = $_POST["nombre"];
        $fecha = $_POST["fecha"];
        $fecha_entrega = $_POST["fecha_entrega"];
        $cantidad = $_POST["cantidad"];
        $total = $_POST["total"];
        $estado = $_POST["estado"];
        $observaciones = $_POST["observaciones"];
        $id_fk_emp = $_POST["id_fk_emp"];
        $id_fk_prov = $_POST["id_fk_prov"];
        try {
            $stmt = $conn->prepare("UPDATE compra SET nombre = ?, fecha = ?, fecha_entrega = ?, cantidad = ?, total = ?, estado = ?, observaciones = ?, id_fk_emp = ?, id_fk_prov = ? WHERE id_com = ?");
            $stmt->bind_param("sssidssiii", $nombre, $fecha, $fecha_entrega, $cantidad, $total, $estado, $observaciones, $id_fk_emp, $id_fk_prov, $id_com);
            $stmt->execute();
            $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Compra actualizada correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al actualizar: " . $e->getMessage() . "</div>";
        }
    }
}

$sql_base = "SELECT c.id_com, c.nombre, c.fecha, c.fecha_entrega, c.cantidad, c.total, c.estado, c.observaciones, c.id_fk_emp, c.id_fk_prov,
                    e.nombre AS nom_empleado, p.nombre AS nom_proveedor
             FROM compra c
             LEFT JOIN empleado e ON c.id_fk_emp = e.id_emp
             LEFT JOIN proveedor p ON c.id_fk_prov = p.id_prov";

if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $param = "%" . $busqueda . "%";
    $stmt = $conn->prepare($sql_base . " WHERE c.id_com LIKE ? OR c.nombre LIKE ? OR c.estado LIKE ? OR c.fecha LIKE ? ORDER BY c.id_com ASC");
    $stmt->bind_param("ssss", $param, $param, $param, $param);
    $stmt->execute();
    $compras = $stmt->get_result();
} else {
    $compras = $conn->query($sql_base . " ORDER BY c.id_com ASC");
}

$titulo_pagina = "Gestión de Pedidos";
$ruta_base = "../";
include("../includes/header.php");
?>

<h1 class="titulo-pagina">Gestión de Pedidos</h1>
<p class="subtitulo-pagina">Registre, modifique y consulte las órdenes de pedido a proveedores.</p>

<?= $mensaje ?>

<?php if ($comp_editar): ?>
  <div class="tarjeta-seccion">
    <h2><i class="fa-solid fa-pen"></i> Modificación de Pedido</h2>
    <form action="" method="POST">
      <input type="hidden" name="id_com" value="<?= $comp_editar['id_com'] ?>">
      <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
      <div class="form-grid">
        <div class="grupo-campo">
          <label>Código Identificador (ID Pedido):</label>
          <input type="number" value="<?= $comp_editar['id_com'] ?>" disabled>
        </div>
        <div class="grupo-campo">
          <label>Descripción / Detalle del Pedido:</label>
          <input type="text" name="nombre" value="<?= $comp_editar['nombre'] ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Fecha de Emisión:</label>
          <input type="date" name="fecha" value="<?= $comp_editar['fecha'] ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Fecha Estimada de Entrega:</label>
          <input type="date" name="fecha_entrega" value="<?= $comp_editar['fecha_entrega'] ?>">
        </div>
        <div class="grupo-campo">
          <label>Cantidad de Unidades:</label>
          <input type="number" name="cantidad" value="<?= $comp_editar['cantidad'] ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Monto Total del Pedido:</label>
          <input type="number" name="total" step="0.01" value="<?= $comp_editar['total'] ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Estado del Pedido:</label>
          <select name="estado" required>
            <option value="Pendiente" <?= $comp_editar['estado'] === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
            <option value="Recibida" <?= $comp_editar['estado'] === 'Recibida' ? 'selected' : '' ?>>Recibida</option>
            <option value="Cancelada" <?= $comp_editar['estado'] === 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
          </select>
        </div>
        <div class="grupo-campo">
          <label>Empleado Gestor:</label>
          <select name="id_fk_emp" required>
            <option value="">-- Seleccione --</option>
            <?php
            $res_emp = $conn->query("SELECT id_emp, nombre FROM empleado ORDER BY id_emp ASC");
            while ($emp = $res_emp->fetch_assoc()) {
                $sel = ($comp_editar['id_fk_emp'] == $emp['id_emp']) ? "selected" : "";
                echo "<option value='{$emp['id_emp']}' $sel>{$emp['id_emp']} - {$emp['nombre']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="grupo-campo">
          <label>Proveedor Asignado:</label>
          <select name="id_fk_prov" required>
            <option value="">-- Seleccione --</option>
            <?php
            $res_prov = $conn->query("SELECT id_prov, nombre FROM proveedor ORDER BY id_prov ASC");
            while ($prov = $res_prov->fetch_assoc()) {
                $sel = ($comp_editar['id_fk_prov'] == $prov['id_prov']) ? "selected" : "";
                echo "<option value='{$prov['id_prov']}' $sel>{$prov['id_prov']} - {$prov['nombre']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="grupo-campo ancho-completo">
          <label>Observaciones:</label>
          <textarea name="observaciones" rows="3"><?= $comp_editar['observaciones'] ?></textarea>
        </div>
      </div>
      <div class="acciones-form">
        <button type="submit" name="actualizar" class="boton-primario"><i class="fa-solid fa-floppy-disk"></i> Actualizar Orden</button>
        <a href="?rol=<?= urlencode($rol) ?>" class="boton-secundario">Cancelar</a>
      </div>
    </form>
  </div>
<?php else: ?>
  <div class="tarjeta-seccion">
    <h2><i class="fa-solid fa-plus"></i> Registro de Nueva Orden de Pedido</h2>
    <form action="" method="POST">
      <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
      <div class="form-grid">
        <div class="grupo-campo">
          <label>Código Identificador (ID Pedido):</label>
          <input type="number" name="id_com" required>
        </div>
        <div class="grupo-campo">
          <label>Descripción / Detalle del Pedido:</label>
          <input type="text" name="nombre" required>
        </div>
        <div class="grupo-campo">
          <label>Fecha de Emisión:</label>
          <input type="date" name="fecha" required>
        </div>
        <div class="grupo-campo">
          <label>Fecha Estimada de Entrega:</label>
          <input type="date" name="fecha_entrega">
        </div>
        <div class="grupo-campo">
          <label>Cantidad de Unidades:</label>
          <input type="number" name="cantidad" required>
        </div>
        <div class="grupo-campo">
          <label>Monto Total del Pedido:</label>
          <input type="number" name="total" step="0.01" required>
        </div>
        <div class="grupo-campo">
          <label>Empleado Gestor:</label>
          <select name="id_fk_emp" required>
            <option value="">-- Seleccione --</option>
            <?php
            $res_emp = $conn->query("SELECT id_emp, nombre FROM empleado ORDER BY id_emp ASC");
            while ($emp = $res_emp->fetch_assoc()) {
                echo "<option value='{$emp['id_emp']}'>{$emp['id_emp']} - {$emp['nombre']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="grupo-campo">
          <label>Proveedor Asignado:</label>
          <select name="id_fk_prov" required>
            <option value="">-- Seleccione --</option>
            <?php
            $res_prov = $conn->query("SELECT id_prov, nombre FROM proveedor ORDER BY id_prov ASC");
            while ($prov = $res_prov->fetch_assoc()) {
                echo "<option value='{$prov['id_prov']}'>{$prov['id_prov']} - {$prov['nombre']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="grupo-campo ancho-completo">
          <label>Observaciones:</label>
          <textarea name="observaciones" rows="3"></textarea>
        </div>
      </div>
      <div class="acciones-form">
        <button type="submit" name="guardar" class="boton-primario"><i class="fa-solid fa-paper-plane"></i> Emitir Orden de Pedido</button>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="tarjeta-seccion">
  <h2><i class="fa-solid fa-magnifying-glass"></i> Consultar Pedidos</h2>
  <form action="" method="GET" class="form-busqueda">
    <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
    <input type="text" name="buscar" placeholder="Ingrese ID, Estado o Fecha..." value="<?= htmlspecialchars($busqueda) ?>">
    <button type="submit" class="boton-primario"><i class="fa-solid fa-magnifying-glass"></i> Consultar</button>
    <?php if (!empty($busqueda)): ?>
      <a href="?rol=<?= urlencode($rol) ?>" class="boton-secundario">Cancelar</a>
    <?php endif; ?>
  </form>
</div>

<div class="tarjeta-seccion">
  <h2><i class="fa-solid fa-list"></i> Listado General de Pedidos Registrados</h2>
  <div class="tabla-contenedor">
    <table class="tabla-datos">
      <thead>
        <tr>
          <th>ID</th><th>Descripción</th><th>F. Emisión</th><th>F. Entrega</th><th>Cant.</th><th>Total</th><th>Estado</th><th>Obs.</th><th>Empleado</th><th>Proveedor</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($compras && $compras->num_rows > 0): ?>
          <?php while ($fila = $compras->fetch_assoc()): ?>
            <tr>
              <td><strong><?= $fila['id_com'] ?></strong></td>
              <td><?= htmlspecialchars($fila['nombre']) ?></td>
              <td><?= $fila['fecha'] ?></td>
              <td><?= $fila['fecha_entrega'] ?></td>
              <td><?= $fila['cantidad'] ?></td>
              <td>$<?= number_format($fila['total'], 0, ',', '.') ?></td>
              <td>
                <?php
                $clase = 'pendiente';
                if ($fila['estado'] === 'Recibida') $clase = 'recibida';
                else if ($fila['estado'] === 'Cancelada') $clase = 'cancelada';
                ?>
                <span class="estado <?= $clase ?>"><?= $fila['estado'] ?></span>
              </td>
              <td><?= htmlspecialchars($fila['observaciones']) ?></td>
              <td><?= $fila['id_fk_emp'] . ' - ' . ($fila['nom_empleado'] ?? 'Sin asignar') ?></td>
              <td><?= $fila['id_fk_prov'] . ' - ' . ($fila['nom_proveedor'] ?? 'Sin asignar') ?></td>
              <td class="acciones-tabla">
                <a href="?accion=editar&id_com=<?= $fila['id_com'] ?>&rol=<?= urlencode($rol) ?>" class="modificar"><i class="fa-solid fa-pen"></i> Modificar</a>
                <a href="?accion=eliminar&id_com=<?= $fila['id_com'] ?>&rol=<?= urlencode($rol) ?>" class="eliminar" onclick="return confirm('¿Seguro que deseas eliminar esta compra?');"><i class="fa-solid fa-trash"></i> Eliminar</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="11" class="sin-registros">No se encontraron registros de pedidos.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
include("../includes/footer.php");
$conn->close();
?>