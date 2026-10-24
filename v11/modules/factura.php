<?php
/* ========== MÓDULO DE FACTURAS ========== */
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
$fact_editar = null;
$busqueda = "";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id_fac'])) {
    $id_eliminar = $_GET['id_fac'];
    try {
        $stmt = $conn->prepare("DELETE FROM factura WHERE id_fac = ?");
        $stmt->bind_param("i", $id_eliminar);
        $stmt->execute();
        $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Factura con ID " . htmlspecialchars($id_eliminar) . " eliminada correctamente.</div>";
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = ($e->getCode() == 1451)
            ? "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> No se puede eliminar la factura con ID " . htmlspecialchars($id_eliminar) . " porque tiene detalles registrados.</div>"
            : "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id_fac'])) {
    $id_buscar = $_GET['id_fac'];
    try {
        $stmt = $conn->prepare("SELECT * FROM factura WHERE id_fac = ?");
        $stmt->bind_param("i", $id_buscar);
        $stmt->execute();
        $fact_editar = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["guardar"])) {
        $id_fac = $_POST["id_fac"];
        $fecha = $_POST["fecha"];
        $impuesto = $_POST["impuesto"];
        $estado = $_POST["estado"];
        $metodo_pago = $_POST["metodo_pago"];
        $observaciones = $_POST["observaciones"];
        $total = $_POST["total"];
        $id_fk_emp = $_POST["id_fk_emp"];
        $id_fk_cli = $_POST["id_fk_cli"];
        try {
            $stmt = $conn->prepare("INSERT INTO factura (id_fac, fecha, impuesto, estado, metodo_pago, observaciones, total, id_fk_emp, id_fk_cli) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isdsssdii", $id_fac, $fecha, $impuesto, $estado, $metodo_pago, $observaciones, $total, $id_fk_emp, $id_fk_cli);
            $stmt->execute();
            $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Factura registrada correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = ($e->getCode() == 1062)
                ? "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error: El ID " . htmlspecialchars($id_fac) . " ya está registrado.</div>"
                : "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al registrar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    if (isset($_POST["actualizar"])) {
        $id_fac = $_POST["id_fac"];
        $fecha = $_POST["fecha"];
        $impuesto = $_POST["impuesto"];
        $estado = $_POST["estado"];
        $metodo_pago = $_POST["metodo_pago"];
        $observaciones = $_POST["observaciones"];
        $total = $_POST["total"];
        $id_fk_emp = $_POST["id_fk_emp"];
        $id_fk_cli = $_POST["id_fk_cli"];
        try {
            $stmt = $conn->prepare("UPDATE factura SET fecha = ?, impuesto = ?, estado = ?, metodo_pago = ?, observaciones = ?, total = ?, id_fk_emp = ?, id_fk_cli = ? WHERE id_fac = ?");
            $stmt->bind_param("sdsssdiii", $fecha, $impuesto, $estado, $metodo_pago, $observaciones, $total, $id_fk_emp, $id_fk_cli, $id_fac);
            $stmt->execute();
            $mensaje = "<div class='alerta exito'><i class='fa-solid fa-circle-check'></i> Factura actualizada correctamente.</div>";
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $mensaje = "<div class='alerta error'><i class='fa-solid fa-circle-xmark'></i> Error al actualizar: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

$sql_base = "SELECT f.id_fac, f.fecha, f.impuesto, f.estado, f.metodo_pago, f.observaciones, f.total, f.id_fk_emp, f.id_fk_cli,
                    e.nombre AS nom_empleado, c.nombre AS nom_cliente
             FROM factura f
             LEFT JOIN empleado e ON f.id_fk_emp = e.id_emp
             LEFT JOIN cliente c ON f.id_fk_cli = c.id_cli";

if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
    $busqueda = trim($_GET['buscar']);
    $param = "%" . $busqueda . "%";
    $stmt = $conn->prepare($sql_base . " WHERE f.id_fac LIKE ? OR f.fecha LIKE ? OR f.estado LIKE ? OR f.total LIKE ? ORDER BY f.id_fac ASC");
    $stmt->bind_param("ssss", $param, $param, $param, $param);
    $stmt->execute();
    $facturas = $stmt->get_result();
} else {
    $facturas = $conn->query($sql_base . " ORDER BY f.id_fac ASC");
}

$titulo_pagina = "Gestión de Facturación";
$ruta_base = "../";
include("../includes/header.php");
?>

<h1 class="titulo-pagina">Gestión de Facturación</h1>
<p class="subtitulo-pagina">Registre, modifique y consulte las facturas emitidas a clientes.</p>

<?= $mensaje ?>

<?php if ($fact_editar): ?>
  <div class="tarjeta-seccion">
    <h2><i class="fa-solid fa-pen"></i> Modificación de Factura</h2>
    <form action="" method="POST">
      <input type="hidden" name="id_fac" value="<?= htmlspecialchars($fact_editar['id_fac']) ?>">
      <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
      <div class="form-grid">
        <div class="grupo-campo">
          <label>ID Factura:</label>
          <input type="number" value="<?= htmlspecialchars($fact_editar['id_fac']) ?>" disabled>
        </div>
        <div class="grupo-campo">
          <label>Fecha:</label>
          <input type="date" name="fecha" value="<?= htmlspecialchars($fact_editar['fecha']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Impuesto:</label>
          <input type="number" name="impuesto" step="0.01" value="<?= htmlspecialchars($fact_editar['impuesto']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Estado:</label>
          <select name="estado" required>
            <option value="Pendiente" <?= $fact_editar['estado'] === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
            <option value="Pagada" <?= $fact_editar['estado'] === 'Pagada' ? 'selected' : '' ?>>Pagada</option>
            <option value="Cancelada" <?= $fact_editar['estado'] === 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
          </select>
        </div>
        <div class="grupo-campo">
          <label>Método de Pago:</label>
          <select name="metodo_pago" required>
            <option value="Efectivo" <?= $fact_editar['metodo_pago'] === 'Efectivo' ? 'selected' : '' ?>>Efectivo</option>
            <option value="Transferencia" <?= $fact_editar['metodo_pago'] === 'Transferencia' ? 'selected' : '' ?>>Transferencia</option>
            <option value="Crédito" <?= $fact_editar['metodo_pago'] === 'Crédito' ? 'selected' : '' ?>>Crédito</option>
          </select>
        </div>
        <div class="grupo-campo">
          <label>Total:</label>
          <input type="number" name="total" step="0.01" value="<?= htmlspecialchars($fact_editar['total']) ?>" required>
        </div>
        <div class="grupo-campo">
          <label>Empleado:</label>
          <select name="id_fk_emp" required>
            <option value="">-- Seleccione --</option>
            <?php
            $res = $conn->query("SELECT id_emp, nombre FROM empleado ORDER BY id_emp ASC");
            while ($e = $res->fetch_assoc()) {
                $sel = ($fact_editar['id_fk_emp'] == $e['id_emp']) ? 'selected' : '';
                echo "<option value='{$e['id_emp']}' $sel>{$e['id_emp']} - {$e['nombre']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="grupo-campo">
          <label>Cliente:</label>
          <select name="id_fk_cli" required>
            <option value="">-- Seleccione --</option>
            <?php
            $res = $conn->query("SELECT id_cli, nombre FROM cliente ORDER BY id_cli ASC");
            while ($c = $res->fetch_assoc()) {
                $sel = ($fact_editar['id_fk_cli'] == $c['id_cli']) ? 'selected' : '';
                echo "<option value='{$c['id_cli']}' $sel>{$c['id_cli']} - {$c['nombre']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="grupo-campo ancho-completo">
          <label>Observaciones:</label>
          <textarea name="observaciones" rows="3"><?= htmlspecialchars($fact_editar['observaciones']) ?></textarea>
        </div>
      </div>
      <div class="acciones-form">
        <button type="submit" name="actualizar" class="boton-primario"><i class="fa-solid fa-floppy-disk"></i> Actualizar Factura</button>
        <a href="?rol=<?= urlencode($rol) ?>" class="boton-secundario">Cancelar</a>
      </div>
    </form>
  </div>
<?php else: ?>
  <div class="tarjeta-seccion">
    <h2><i class="fa-solid fa-plus"></i> Registro de Nueva Factura</h2>
    <form action="" method="POST">
      <div class="form-grid">
        <div class="grupo-campo">
          <label>ID Factura:</label>
          <input type="number" name="id_fac" required>
        </div>
        <div class="grupo-campo">
          <label>Fecha:</label>
          <input type="date" name="fecha" required>
        </div>
        <div class="grupo-campo">
          <label>Impuesto:</label>
          <input type="number" name="impuesto" step="0.01" required>
        </div>
        <div class="grupo-campo">
          <label>Método de Pago:</label>
          <select name="metodo_pago" required>
            <option value="Efectivo">Efectivo</option>
            <option value="Transferencia">Transferencia</option>
            <option value="Crédito">Crédito</option>
          </select>
        </div>
        <div class="grupo-campo">
          <label>Total:</label>
          <input type="number" name="total" step="0.01" required>
        </div>
        <div class="grupo-campo">
          <label>Empleado:</label>
          <select name="id_fk_emp" required>
            <option value="">-- Seleccione --</option>
            <?php
            $res = $conn->query("SELECT id_emp, nombre FROM empleado ORDER BY id_emp ASC");
            while ($e = $res->fetch_assoc()) {
                echo "<option value='{$e['id_emp']}'>{$e['id_emp']} - {$e['nombre']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="grupo-campo">
          <label>Cliente:</label>
          <select name="id_fk_cli" required>
            <option value="">-- Seleccione --</option>
            <?php
            $res = $conn->query("SELECT id_cli, nombre FROM cliente ORDER BY id_cli ASC");
            while ($c = $res->fetch_assoc()) {
                echo "<option value='{$c['id_cli']}'>{$c['id_cli']} - {$c['nombre']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="grupo-campo ancho-completo">
          <label>Observaciones:</label>
          <textarea name="observaciones" rows="3"></textarea>
        </div>
        <input type="hidden" name="estado" value="Pendiente">
        <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
      </div>
      <div class="acciones-form">
        <button type="submit" name="guardar" class="boton-primario"><i class="fa-solid fa-paper-plane"></i> Guardar Factura</button>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="tarjeta-seccion">
  <h2><i class="fa-solid fa-magnifying-glass"></i> Consulta de Facturas</h2>
  <form action="" method="GET" class="form-busqueda">
    <input type="hidden" name="rol" value="<?= htmlspecialchars($rol) ?>">
    <input type="text" name="buscar" placeholder="Ingrese ID, estado o fecha..." value="<?= htmlspecialchars($busqueda) ?>">
    <button type="submit" class="boton-primario"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
    <?php if (!empty($busqueda)): ?>
      <a href="?rol=<?= urlencode($rol) ?>" class="boton-secundario">Mostrar todos</a>
    <?php endif; ?>
  </form>
</div>

<div class="tarjeta-seccion">
  <h2><i class="fa-solid fa-list"></i> Historial de Facturas Registradas</h2>
  <div class="tabla-contenedor">
    <table class="tabla-datos">
      <thead>
        <tr>
          <th>ID</th><th>Fecha</th><th>Impuesto</th><th>Estado</th><th>Método Pago</th><th>Obs.</th><th>Total</th><th>Empleado</th><th>Cliente</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($facturas && $facturas->num_rows > 0): ?>
          <?php while ($fila = $facturas->fetch_assoc()): ?>
            <tr>
              <td><strong><?= htmlspecialchars($fila['id_fac']) ?></strong></td>
              <td><?= htmlspecialchars($fila['fecha']) ?></td>
              <td>$<?= number_format($fila['impuesto'], 0, ',', '.') ?></td>
              <td>
                <?php
                $clase = 'pendiente';
                if ($fila['estado'] === 'Pagada') $clase = 'pagada';
                else if ($fila['estado'] === 'Cancelada') $clase = 'cancelada';
                ?>
                <span class="estado <?= $clase ?>"><?= htmlspecialchars($fila['estado']) ?></span>
              </td>
              <td><?= htmlspecialchars($fila['metodo_pago']) ?></td>
              <td><?= htmlspecialchars($fila['observaciones']) ?></td>
              <td>$<?= number_format($fila['total'], 0, ',', '.') ?></td>
              <td><?= $fila['id_fk_emp'] . ' - ' . htmlspecialchars($fila['nom_empleado'] ?? 'Sin asignar') ?></td>
              <td><?= $fila['id_fk_cli'] . ' - ' . htmlspecialchars($fila['nom_cliente'] ?? 'Sin asignar') ?></td>
              <td class="acciones-tabla">
                <a href="?accion=editar&id_fac=<?= $fila['id_fac'] ?>&rol=<?= urlencode($rol) ?>" class="modificar"><i class="fa-solid fa-pen"></i> Editar</a>
                <a href="?accion=eliminar&id_fac=<?= $fila['id_fac'] ?>&rol=<?= urlencode($rol) ?>" class="eliminar" onclick="return confirm('¿Seguro que desea eliminar esta factura?');"><i class="fa-solid fa-trash"></i> Eliminar</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="10" class="sin-registros">No se encontraron registros de facturas.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
include("../includes/footer.php");
$conn->close();
?>