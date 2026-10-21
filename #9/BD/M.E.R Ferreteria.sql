/* create tables. */
CREATE TABLE cargo (
    ID_car INT(11) NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Salario DECIMAL(10, 2) NOT NULL,
    estado VARCHAR(20),
    PRIMARY KEY (ID_car)
);

CREATE TABLE categoria (
    ID_cat INT(11) NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Descripcion VARCHAR(100),
    estado VARCHAR(20),
    PRIMARY KEY (ID_cat)
);

CREATE TABLE cliente (
    ID_cli INT(11) NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Telefono VARCHAR(20),
    direccion VARCHAR(150),
    correo VARCHAR(100),
    estado VARCHAR(20),
    PRIMARY KEY (ID_cli)
);

CREATE TABLE proveedor (
    ID_prov INT(11) NOT NULL,
    NIT VARCHAR(50) NOT NULL UNIQUE,
    Nombre VARCHAR(100) NOT NULL,
    nombre_proveedor VARCHAR(150),
    Telefono VARCHAR(20),
    telefono_empresa VARCHAR(20),
    Correo VARCHAR(100),
    direccion VARCHAR(100),
    estado VARCHAR(20),
    PRIMARY KEY (ID_prov)
);

CREATE TABLE empleado (
    ID_emp INT(11) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    direccion VARCHAR(100),
    e_mail VARCHAR(100),
    fecha_contrato DATE,
    estado VARCHAR(20),
    ID_FK_car INT(11) NOT NULL,
    PRIMARY KEY (ID_emp)
);

CREATE TABLE producto (
    ID_prod INT(11) NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Descripcion VARCHAR(100),
    Precio DECIMAL(10, 2) NOT NULL,
    Estado VARCHAR(20) NOT NULL,
    Stock INT(11) NOT NULL DEFAULT 0,
    fecha_ingreso DATE,
    stock_minimo INT(11),
    ID_FK_cat INT(11) NOT NULL,
    ID_FK_prov INT(11) NOT NULL,
    PRIMARY KEY (ID_prod)
);

CREATE TABLE compra (
    ID_com INT(11) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    Fecha DATE NOT NULL,
    cantidad INT(11) NOT NULL,
    Total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    Estado VARCHAR(20) NOT NULL,
    ID_FK_emp INT(11) NOT NULL,
    ID_FK_prov INT(11) NOT NULL,
    PRIMARY KEY (ID_com)
);

CREATE TABLE factura (
    ID_fac INT(11) NOT NULL,
    Fecha DATE NOT NULL,
    Impuesto DECIMAL(10, 2) DEFAULT 0.00,
    estado VARCHAR(20),
    metodo_pago VARCHAR(30),
    observaciones VARCHAR(200),
    Total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    ID_FK_emp INT(11) NOT NULL,
    ID_FK_cli INT(11) NOT NULL,
    PRIMARY KEY (ID_fac)
);

CREATE TABLE detallecompra (
    ID_deta INT(11) NOT NULL,
    Cantidad INT(11) NOT NULL,
    precio_unitario DECIMAL(10, 2),
    sub_total DECIMAL(10, 2),
    ID_FK_prod INT(11) NOT NULL,
    ID_FK_com INT(11) NOT NULL,
    PRIMARY KEY (ID_deta)
);

CREATE TABLE detallefactura (
    ID_deta INT(11) NOT NULL,
    cantidad INT(11),
    precio_unitario DECIMAL(10, 2),
    subtotal DECIMAL(10, 2),
    ID_FK_fac INT(11) NOT NULL,
    ID_FK_prod INT(11) NOT NULL,
    PRIMARY KEY (ID_deta)
);

CREATE TABLE usuario (
    ID_usu INT(11) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    rol VARCHAR(20),
    estado VARCHAR(20) NOT NULL,
    fecha_creacion DATETIME,
    ID_FK_emp INT(11) NOT NULL,
    PRIMARY KEY (ID_usu)
);


/* create foreign keys. */
ALTER TABLE detallecompra
    ADD FOREIGN KEY (ID_FK_prod)
    REFERENCES producto (ID_prod)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

ALTER TABLE detallefactura
    ADD FOREIGN KEY (ID_FK_prod)
    REFERENCES producto (ID_prod)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

ALTER TABLE empleado
    ADD FOREIGN KEY (ID_FK_car)
    REFERENCES cargo (ID_car)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

ALTER TABLE producto
    ADD FOREIGN KEY (ID_FK_prov)
    REFERENCES proveedor (ID_prov)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

ALTER TABLE factura
    ADD FOREIGN KEY (ID_FK_emp)
    REFERENCES empleado (ID_emp)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

ALTER TABLE usuario
    ADD FOREIGN KEY (ID_FK_emp)
    REFERENCES empleado (ID_emp)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

ALTER TABLE compra
    ADD FOREIGN KEY (ID_FK_emp)
    REFERENCES empleado (ID_emp)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

ALTER TABLE detallecompra
    ADD FOREIGN KEY (ID_FK_com)
    REFERENCES compra (ID_com)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

ALTER TABLE factura
    ADD FOREIGN KEY (ID_FK_cli)
    REFERENCES cliente (ID_cli)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

ALTER TABLE compra
    ADD FOREIGN KEY (ID_FK_prov)
    REFERENCES proveedor (ID_prov)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

ALTER TABLE detallefactura
    ADD FOREIGN KEY (ID_FK_fac)
    REFERENCES factura (ID_fac)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

ALTER TABLE producto
    ADD FOREIGN KEY (ID_FK_cat)
    REFERENCES categoria (ID_cat)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT;

