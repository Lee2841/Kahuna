CREATE DATABASE kahuna;

USE kahuna;

CREATE TABLE `User` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','agent') NOT NULL DEFAULT 'customer',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uc_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `AccessToken` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `birth` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `c_accesstoken_user` (`userId`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `Products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `SerialNumber` varchar(255) DEFAULT NULL UNIQUE,
  `ProductName` varchar(255) DEFAULT NULL,
  `WarrantyPeriod` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `WarrantyStartDate` date DEFAULT NULL,
  `PurchaseDate` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_Products_UserID` (`UserID`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `Tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `product_serial_number` varchar(45) NOT NULL,
  `status` VARCHAR(255) NOT NULL,
  `issue_description` text NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_serial_number` (`product_serial_number`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `TicketReplies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT,
  `user_id` INT,
  `reply_message` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `ticket_id` (`ticket_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `Products` (`SerialNumber`, `ProductName`, `WarrantyPeriod`) VALUES
('KHWM8199911', 'CombiSpin Washing Machine', 2),
('KHWM8199912', 'CombiSpin + Dry Washing Machine', 2),
('KHMW789991', 'CombiGrill Microwave', 1),
('KHWP890001', 'K5 Water Pump', 5),
('KHWP890002', 'K5 Heated Water Pump', 5),
('KHSS988881', 'Smart Switch Lite', 2),
('KHSS988882', 'Smart Switch Pro', 2),
('KHSS988883', 'Smart Switch Pro V2', 2),
('KHHM89762', 'Smart Heated Mug', 1),
('KHSB0001', 'Smart Bulb 001', 1);

ALTER TABLE `AccessToken`
ADD CONSTRAINT `c_accesstoken_user` FOREIGN KEY (`userId`) REFERENCES `User` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `Products`
ADD CONSTRAINT `FK_Products_UserID` FOREIGN KEY (`UserID`) REFERENCES `User` (`id`) ON DELETE CASCADE;

ALTER TABLE `Tickets`
ADD CONSTRAINT `Tickets_ibfk_1` FOREIGN KEY (`product_serial_number`) REFERENCES `Products` (`SerialNumber`),
ADD CONSTRAINT `Tickets_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `User` (`id`);

ALTER TABLE `TicketReplies`
ADD CONSTRAINT `TicketReplies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `Tickets` (`id`),
ADD CONSTRAINT `TicketReplies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `User` (`id`);

DELIMITER $$

CREATE TRIGGER update_purchase_date_after_update
BEFORE UPDATE ON Products
FOR EACH ROW
BEGIN
    IF NEW.PurchaseDate IS NULL THEN
        SET NEW.PurchaseDate = NEW.WarrantyStartDate;
    END IF;
END$$

DELIMITER ;;