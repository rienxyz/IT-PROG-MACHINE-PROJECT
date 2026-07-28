-- -----------------------------------------------------
-- Schema appoiintment_db
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `appointment_db` DEFAULT CHARACTER
SET
    utf8;

USE `appointment_db`;

-- -----------------------------------------------------
-- Table `appointment_db`.`users`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointment_db`.`users` (
    `user_id` INT NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(45) NULL,
    `last_name` VARCHAR(45) NULL,
    `role` ENUM ('patient', 'secretary', 'doctor', 'admin') NULL,
    `account_status` ENUM ('active', 'inactive') NULL,
    `insurance` VARCHAR(45) NULL,
    `insurance_status` ENUM ('accepted', 'declined') NULL,
    `preferred_specialty` VARCHAR(45) NULL,
    PRIMARY KEY (`user_id`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `appointment_db`.`doctors`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointment_db`.`doctors` (
    `doctor_id` INT NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(45) NULL,
    `last_name` VARCHAR(45) NULL,
    `specialty` VARCHAR(45) NULL,
    PRIMARY KEY (`doctor_id`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `appointment_db`.`appointments`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointment_db`.`appointments` (
    `appointment_id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NULL,
    `doctor_id` INT NULL,
    `insurance` VARCHAR(45) NULL,
    `room_number` VARCHAR(45) NULL,
    `date` DATE NULL,
    `time` TIME NULL,
    `status` ENUM (
        'confirmed',
        'cancelled',
        'no show',
        'rescheduled',
        'declined',
        'completed'
    ) NULL,
    PRIMARY KEY (`appointment_id`),
    INDEX `user_id_idx` (`user_id` ASC) VISIBLE,
    INDEX `doctor_id_idx` (`doctor_id` ASC) VISIBLE,
    CONSTRAINT `user_id` FOREIGN KEY (`user_id`) REFERENCES `appointment_db`.`users` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
    CONSTRAINT `doctor_id` FOREIGN KEY (`doctor_id`) REFERENCES `appointment_db`.`doctors` (`doctor_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `appointment_db`.`logs`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointment_db`.`logs` (
    `log_id` INT NOT NULL AUTO_INCREMENT,
    `appointment_id` INT NULL,
    `content` TEXT (500) NULL,
    `timestamp` TIMESTAMP(3) NULL,
    PRIMARY KEY (`log_id`),
    INDEX `appointment_id_idx` (`appointment_id` ASC) VISIBLE,
    CONSTRAINT `appointment_id` FOREIGN KEY (`appointment_id`) REFERENCES `appointment_db`.`appointments` (`appointment_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB;
