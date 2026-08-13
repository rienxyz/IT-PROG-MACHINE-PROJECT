-- -----------------------------------------------------
-- Schema appointment_db
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `appointment_db` DEFAULT CHARACTER
SET
    utf8mb3;

USE `appointment_db`;

-- -----------------------------------------------------
-- Table `appointment_db`.`accounts`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointment_db`.`accounts` (
    `account_id` INT NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(255) NULL DEFAULT NULL,
    `last_name` VARCHAR(255) NULL DEFAULT NULL,
    `phone_number` VARCHAR(45) NULL,
    `e_mail` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM ('patient', 'secretary', 'doctor', 'admin') NULL DEFAULT NULL,
    `activity_status` ENUM ('active', 'inactive') NULL DEFAULT NULL,
    `verification_status` ENUM ('verified', 'unverified', 'verifying') NULL,
    PRIMARY KEY (`account_id`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `appointment_db`.`patients`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointment_db`.`patients` (
    `patient_id` INT NOT NULL AUTO_INCREMENT,
    `account_id` INT NOT NULL UNIQUE,
    `insurance` VARCHAR(45) NULL,
    `insurance_status` ENUM ('accepted', 'declined') NULL,
    `preferred_specialty` VARCHAR(45) NULL,
    PRIMARY KEY (`patient_id`),
    INDEX `account_id_idx` (`account_id` ASC),
    CONSTRAINT `patient_account_id` FOREIGN KEY (`account_id`) REFERENCES `appointment_db`.`accounts` (`account_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `appointment_db`.`secretaries`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointment_db`.`secretaries` (
    `secretary_id` INT NOT NULL AUTO_INCREMENT,
    `account_id` INT NOT NULL UNIQUE,
    `department` VARCHAR(45) NULL,
    PRIMARY KEY (`secretary_id`),
    INDEX `account_id_idx` (`account_id` ASC),
    CONSTRAINT `secretary_account_id` FOREIGN KEY (`account_id`) REFERENCES `appointment_db`.`accounts` (`account_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `appointment_db`.`doctors`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointment_db`.`doctors` (
    `doctor_id` INT NOT NULL AUTO_INCREMENT,
    `account_id` INT NOT NULL UNIQUE,
    `specialty` VARCHAR(45) NULL DEFAULT NULL,
    PRIMARY KEY (`doctor_id`),
    INDEX `account_id_idx` (`account_id` ASC),
    CONSTRAINT `doctor_account_id` FOREIGN KEY (`account_id`) REFERENCES `appointment_db`.`accounts` (`account_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `appointment_db`.`assignments`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointment_db`.`assignments` (
    `doctor_id` INT NOT NULL,
    `secretary_id` INT NOT NULL,
    PRIMARY KEY (`doctor_id`, `secretary_id`),
    INDEX `secretary_id_idx` (`secretary_id` ASC),
    CONSTRAINT `assignment_doctor_id` FOREIGN KEY (`doctor_id`) REFERENCES `appointment_db`.`doctors` (`doctor_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
    CONSTRAINT `assignment_secretary_id` FOREIGN KEY (`secretary_id`) REFERENCES `appointment_db`.`secretaries` (`secretary_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `appointment_db`.`appointments`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointment_db`.`appointments` (
    `appointment_id` INT NOT NULL AUTO_INCREMENT,
    `patient_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `insurance` VARCHAR(45) NULL DEFAULT NULL,
    `room_number` VARCHAR(45) NULL DEFAULT NULL,
    `date` DATE NULL DEFAULT NULL,
    `time` TIME NULL DEFAULT NULL,
    `status` ENUM ('confirmed', 'cancelled', 'no show', 'rescheduled', 'declined', 'completed') NULL DEFAULT NULL,
    PRIMARY KEY (`appointment_id`),
    INDEX `doctor_id_idx` (`doctor_id` ASC),
    INDEX `patient_id_idx` (`patient_id` ASC),
    CONSTRAINT `appointment_doctor_id` FOREIGN KEY (`doctor_id`) REFERENCES `appointment_db`.`doctors` (`doctor_id`),
    CONSTRAINT `appointment_patient_id` FOREIGN KEY (`patient_id`) REFERENCES `appointment_db`.`patients` (`patient_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `appointment_db`.`logs`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointment_db`.`logs` (
    `log_id` INT NOT NULL AUTO_INCREMENT,
    `appointment_id` INT NULL DEFAULT NULL,
    `content` TEXT NULL DEFAULT NULL,
    `timestamp` TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`),
    INDEX `appointment_id_idx` (`appointment_id` ASC),
    CONSTRAINT `appointment_id` FOREIGN KEY (`appointment_id`) REFERENCES `appointment_db`.`appointments` (`appointment_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB;