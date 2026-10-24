-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 03-10-2026 a las 04:05:45
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.5.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `ferreteria`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cargo`
--

CREATE TABLE `cargo` (
  `id_car` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `salario` decimal(10,2) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cargo`
--

INSERT INTO `cargo` (`id_car`, `nombre`, `descripcion`, `salario`, `estado`) VALUES
(1, 'Administrador', 'Encargado general de la ferretería', 2500000.00, 'Activo'),
(2, 'Vendedor de Mostrador', 'Atiende al público en mostrador', 1300000.00, 'Activo'),
(3, 'Bodeguero', 'Organiza y despacha mercancía', 1200000.00, 'Activo'),
(4, 'Cajero', 'Maneja caja y facturación', 1300000.00, 'Activo'),
(5, 'Domiciliario', 'Entrega pedidos a domicilio', 1000000.00, 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `id_cat` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(100) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`id_cat`, `nombre`, `descripcion`, `estado`) VALUES
(1, 'Herramientas Manuales', 'Martillos, destornilladores, llaves, etc.', 'Activo'),
(2, 'Pinturas y Químicos', 'Pinturas, thinner, brochas, rodillos.', 'Activo'),
(3, 'Materiales de Construcción', 'Cemento, varilla, ladrillos, arena.', 'Activo'),
(4, 'Plomería', 'Tuberías, llaves de paso, pegantes.', 'Activo'),
(5, 'Eléctricos', 'Cables, tomacorrientes, bombillos, breakers.', 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `id_cli` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `tipo_cliente` varchar(20) NOT NULL DEFAULT 'Natural',
  `estado` varchar(20) NOT NULL DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`id_cli`, `nombre`, `telefono`, `direccion`, `correo`, `tipo_cliente`, `estado`) VALUES
(1, 'Constructora Los Patios', '3155551234', 'Av. 5 # 12-40, Cúcuta', 'contacto@lospatios.com', 'Empresa', 'Activo'),
(2, 'Carlos Eduardo Pérez', '3175559876', 'Calle 8 # 3-15, Cúcuta', 'carlos.perez@mail.com', 'Natural', 'Activo'),
(3, 'María Fernanda Gómez', '3205554567', 'Barrio Caobos, Cúcuta', 'maria.gomez@mail.com', 'Natural', 'Activo'),
(4, 'Ferretería El Progreso', '3185557890', 'Av. 15 # 20-30, Cúcuta', 'ventas@elprogreso.com', 'Empresa', 'Activo'),
(5, 'Juan David Contreras', '3125552345', 'Barrio La Libertad, Cúcuta', 'juan.contreras@mail.com', 'Natural', 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compra`
--

CREATE TABLE `compra` (
  `id_com` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `fecha` date NOT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` varchar(20) NOT NULL,
  `observaciones` varchar(200) DEFAULT NULL,
  `id_fk_emp` int(11) NOT NULL,
  `id_fk_prov` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `compra`
--

INSERT INTO `compra` (`id_com`, `nombre`, `fecha`, `fecha_entrega`, `cantidad`, `total`, `estado`, `observaciones`, `id_fk_emp`, `id_fk_prov`) VALUES
(1, 'Compra de Varillas', '2026-09-15', '2026-09-20', 160, 2500000.00, 'Recibida', 'Llegó completo', 1, 1),
(2, 'Compra de Pintura', '2026-09-16', '2026-09-25', 50, 9000000.00, 'Pendiente', 'Pendiente pago', 1, 2),
(3, 'Compra de Tuberías', '2026-09-17', '2026-09-22', 200, 3000000.00, 'Cancelada', 'Cancelada por proveedor', 3, 3),
(4, 'Compra de Martillos', '2026-09-18', '2026-09-28', 30, 1050000.00, 'Pendiente', 'Confirmar transporte', 1, 4),
(5, 'Compra de Cables', '2026-09-19', '2026-09-30', 80, 9600000.00, 'Recibida', 'Recibida sin novedad', 3, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallecompra`
--

CREATE TABLE `detallecompra` (
  `id_data` int(11) NOT NULL,
  `id_fk_com` int(11) NOT NULL,
  `id_fk_prod` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detallecompra`
--

INSERT INTO `detallecompra` (`id_data`, `id_fk_com`, `id_fk_prod`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 3, 1, 0.00, 0.00),
(2, 2, 2, 1, 0.00, 0.00),
(3, 3, 4, 1, 0.00, 0.00),
(4, 4, 1, 1, 0.00, 0.00),
(5, 5, 5, 1, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallefactura`
--

CREATE TABLE `detallefactura` (
  `id_data` int(11) NOT NULL,
  `id_fk_fac` int(11) NOT NULL,
  `id_fk_prod` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detallefactura`
--

INSERT INTO `detallefactura` (`id_data`, `id_fk_fac`, `id_fk_prod`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 3, 1, 0.00, 0.00),
(2, 2, 1, 1, 0.00, 0.00),
(3, 3, 5, 1, 0.00, 0.00),
(4, 4, 4, 1, 0.00, 0.00),
(5, 5, 1, 1, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `id_emp` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `direccion` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `fecha_contrato` date NOT NULL,
  `id_fk_car` int(11) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`id_emp`, `nombre`, `telefono`, `direccion`, `email`, `fecha_contrato`, `id_fk_car`, `estado`) VALUES
(1, 'José Alexander Rojas', '3155550001', 'Av. 0 # 10-20, Cúcuta', 'jose.rojas@ferreteria.com', '2023-01-15', 1, 'Activo'),
(2, 'Ana María Duarte', '3155550002', 'Calle 10 # 5-40, Cúcuta', 'ana.duarte@ferreteria.com', '2023-03-20', 2, 'Activo'),
(3, 'Pedro Antonio Silva', '3155550003', 'Barrio Caobos, Cúcuta', 'pedro.silva@ferreteria.com', '2023-05-10', 3, 'Activo'),
(4, 'Luisa Fernanda Ortiz', '3155550004', 'Urbanización Aeropuerto', 'luisa.ortiz@ferreteria.com', '2023-08-01', 4, 'Activo'),
(5, 'Miguel Ángel Vera', '3155550005', 'Barrio La Libertad', 'miguel.vera@ferreteria.com', '2024-01-10', 5, 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura`
--

CREATE TABLE `factura` (
  `id_fac` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `impuesto` decimal(10,2) NOT NULL,
  `estado` varchar(20) DEFAULT NULL,
  `metodo_pago` varchar(30) NOT NULL DEFAULT 'Efectivo',
  `observaciones` varchar(200) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `id_fk_emp` int(11) NOT NULL,
  `id_fk_cli` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `factura`
--

INSERT INTO `factura` (`id_fac`, `fecha`, `impuesto`, `estado`, `metodo_pago`, `observaciones`, `total`, `id_fk_emp`, `id_fk_cli`) VALUES
(1, '2026-09-20', 19000.00, 'Pendiente', 'Efectivo', NULL, 119000.00, 2, 1),
(2, '2026-09-21', 9000.00, 'Pagada', 'Efectivo', NULL, 57000.00, 2, 2),
(3, '2026-09-22', 15000.00, 'Cancelada', 'Efectivo', NULL, 95000.00, 4, 3),
(4, '2026-09-23', 25000.00, 'Cancelada', 'Efectivo', NULL, 155000.00, 2, 4),
(5, '2026-09-24', 5000.00, 'Pagada', 'Efectivo', NULL, 30000.00, 4, 5),
(6, '2026-09-25', 2222222.00, 'Pendiente', 'Efectivo', NULL, 13434234.00, 1, 1),
(7, '2026-10-02', 23423423.00, 'Pendiente', 'Efectivo', '123123', 12312312.00, 4, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `id_prod` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(30) DEFAULT NULL,
  `descripcion` varchar(100) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `estado` varchar(20) NOT NULL,
  `stock` int(11) NOT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `stock_minimo` int(11) NOT NULL DEFAULT 5,
  `id_fk_cat` int(11) NOT NULL,
  `id_fk_prov` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`id_prod`, `nombre`, `codigo`, `descripcion`, `precio`, `estado`, `stock`, `fecha_ingreso`, `stock_minimo`, `id_fk_cat`, `id_fk_prov`) VALUES
(1, 'Martillo de uña 16oz', 'MAR-001', 'Martillo con mango de madera', 35000.00, 'Bajo Stock', 10, '2026-08-15', 10, 1, 4),
(2, 'Pintura Blanca Tipo 1', 'PIN-001', 'Balde de pintura blanca 5 galones', 180000.00, 'Bajo Stock', 5, '2026-08-20', 5, 2, 2),
(3, 'Varilla Corrugada 1/2', 'VAR-001', 'Varilla de acero para construcción', 25000.00, 'Bajo Stock', 10, '2026-08-25', 20, 3, 1),
(4, 'Tubería PVC 1/2 pulgada', 'TUB-001', 'Tubo PVC presión 6 metros', 15000.00, 'Disponible', 100, '2026-09-01', 15, 4, 3),
(5, 'Cable Eléctrico 12 AWG', 'CAB-001', 'Rollo de cable eléctrico 100 metros', 120000.00, 'Disponible', 30, '2026-09-05', 10, 5, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor`
--

CREATE TABLE `proveedor` (
  `id_prov` int(11) NOT NULL,
  `nit` varchar(30) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `nombre_proveedor` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) NOT NULL,
  `telefono_empresa` varchar(20) DEFAULT NULL,
  `correo` varchar(100) NOT NULL,
  `direccion` varchar(100) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedor`
--

INSERT INTO `proveedor` (`id_prov`, `nit`, `nombre`, `nombre_proveedor`, `telefono`, `telefono_empresa`, `correo`, `direccion`, `ciudad`, `estado`) VALUES
(1, '900123456-1', 'Aceros Arequipa Colombia', NULL, '3115551111', NULL, 'ventas@acerosarequipa.com.co', NULL, 'Cúcuta', 'Activo'),
(2, '900234567-2', 'Pintuco', NULL, '3115552222', NULL, 'contacto@pintuco.com', NULL, 'Bogotá', 'Activo'),
(3, '900345678-3', 'Corona', NULL, '3115553333', NULL, 'pedidos@corona.com.co', NULL, 'Bogotá', 'Activo'),
(4, '900456789-4', 'Stanley', NULL, '3115554444', NULL, 'soporte@stanley.com', NULL, 'Medellín', 'Activo'),
(5, '900123457-2', 'Truper', NULL, '199999', NULL, 'ventas@truper.com.co', NULL, 'Cúcuta', 'Activo'),
(6, '900123457-19', 'Yeremis', NULL, '100101010', NULL, 'yeremis@yeremis.com', NULL, 'Cúcuta', 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usu` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(20) NOT NULL DEFAULT 'Vendedor',
  `estado` varchar(20) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `id_fk_emp` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usu`, `username`, `password`, `rol`, `estado`, `fecha_creacion`, `id_fk_emp`) VALUES
(1, 'admin', '123', 'Administrador', 'Activo', '2026-09-01 08:00:00', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cargo`
--
ALTER TABLE `cargo`
  ADD PRIMARY KEY (`id_car`);

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id_cat`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`id_cli`);

--
-- Indices de la tabla `compra`
--
ALTER TABLE `compra`
  ADD PRIMARY KEY (`id_com`),
  ADD KEY `fk_compra_empleado` (`id_fk_emp`),
  ADD KEY `fk_compra_proveedor` (`id_fk_prov`);

--
-- Indices de la tabla `detallecompra`
--
ALTER TABLE `detallecompra`
  ADD PRIMARY KEY (`id_data`),
  ADD KEY `fk_detallecompra_compra` (`id_fk_com`),
  ADD KEY `fk_detallecompra_producto` (`id_fk_prod`);

--
-- Indices de la tabla `detallefactura`
--
ALTER TABLE `detallefactura`
  ADD PRIMARY KEY (`id_data`),
  ADD KEY `fk_detallefactura_factura` (`id_fk_fac`),
  ADD KEY `fk_detallefactura_producto` (`id_fk_prod`);

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`id_emp`),
  ADD KEY `fk_empleado_cargo` (`id_fk_car`);

--
-- Indices de la tabla `factura`
--
ALTER TABLE `factura`
  ADD PRIMARY KEY (`id_fac`),
  ADD KEY `fk_factura_empleado` (`id_fk_emp`),
  ADD KEY `fk_factura_cliente` (`id_fk_cli`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`id_prod`),
  ADD KEY `fk_producto_categoria` (`id_fk_cat`),
  ADD KEY `fk_producto_proveedor` (`id_fk_prov`);

--
-- Indices de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD PRIMARY KEY (`id_prov`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usu`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cargo`
--
ALTER TABLE `cargo`
  MODIFY `id_car` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id_cat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id_cli` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `compra`
--
ALTER TABLE `compra`
  MODIFY `id_com` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `detallecompra`
--
ALTER TABLE `detallecompra`
  MODIFY `id_data` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `detallefactura`
--
ALTER TABLE `detallefactura`
  MODIFY `id_data` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `id_emp` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `factura`
--
ALTER TABLE `factura`
  MODIFY `id_fac` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `id_prod` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  MODIFY `id_prov` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `compra`
--
ALTER TABLE `compra`
  ADD CONSTRAINT `fk_compra_empleado` FOREIGN KEY (`id_fk_emp`) REFERENCES `empleado` (`id_emp`),
  ADD CONSTRAINT `fk_compra_proveedor` FOREIGN KEY (`id_fk_prov`) REFERENCES `proveedor` (`id_prov`);

--
-- Filtros para la tabla `detallecompra`
--
ALTER TABLE `detallecompra`
  ADD CONSTRAINT `fk_detallecompra_compra` FOREIGN KEY (`id_fk_com`) REFERENCES `compra` (`id_com`),
  ADD CONSTRAINT `fk_detallecompra_producto` FOREIGN KEY (`id_fk_prod`) REFERENCES `producto` (`id_prod`);

--
-- Filtros para la tabla `detallefactura`
--
ALTER TABLE `detallefactura`
  ADD CONSTRAINT `fk_detallefactura_factura` FOREIGN KEY (`id_fk_fac`) REFERENCES `factura` (`id_fac`),
  ADD CONSTRAINT `fk_detallefactura_producto` FOREIGN KEY (`id_fk_prod`) REFERENCES `producto` (`id_prod`);

--
-- Filtros para la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD CONSTRAINT `fk_empleado_cargo` FOREIGN KEY (`id_fk_car`) REFERENCES `cargo` (`id_car`);

--
-- Filtros para la tabla `factura`
--
ALTER TABLE `factura`
  ADD CONSTRAINT `fk_factura_cliente` FOREIGN KEY (`id_fk_cli`) REFERENCES `cliente` (`id_cli`),
  ADD CONSTRAINT `fk_factura_empleado` FOREIGN KEY (`id_fk_emp`) REFERENCES `empleado` (`id_emp`);

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`id_fk_cat`) REFERENCES `categoria` (`id_cat`),
  ADD CONSTRAINT `fk_producto_proveedor` FOREIGN KEY (`id_fk_prov`) REFERENCES `proveedor` (`id_prov`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
