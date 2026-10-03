USE `sistemacomercio`;

ALTER TABLE `producto`
    MODIFY COLUMN `Nombre` VARCHAR(100) NOT NULL,
    MODIFY COLUMN `Precio` INT NOT NULL;

CREATE TABLE IF NOT EXISTS `pedido` (
    `Id` INT NOT NULL AUTO_INCREMENT,
    `ClienteId` INT NOT NULL,
    `ProductoId` INT NOT NULL,
    `Cantidad` INT NOT NULL,
    `Total` INT NOT NULL,
    `Estado` VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
    `Fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`Id`),
    CONSTRAINT `fk_pedido_cliente`
        FOREIGN KEY (`ClienteId`) REFERENCES `cliente` (`Id`),
    CONSTRAINT `fk_pedido_producto`
        FOREIGN KEY (`ProductoId`) REFERENCES `producto` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `pago` (
    `Id` INT NOT NULL AUTO_INCREMENT,
    `PedidoId` INT NOT NULL,
    `Monto` INT NOT NULL,
    `Metodo` VARCHAR(50) NOT NULL,
    `Estado` VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
    `Fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`Id`),
    UNIQUE KEY `uq_pago_pedido` (`PedidoId`),
    CONSTRAINT `fk_pago_pedido`
        FOREIGN KEY (`PedidoId`) REFERENCES `pedido` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
