USE appointment_db;

-- =====================================================
-- ACCOUNTS
-- =====================================================

INSERT INTO accounts
(first_name, last_name, phone_number, e_mail, password, role, activity_status, verification_status)
VALUES
-- Doctors
('John','Smith','09171234567','john.smith@hospital.com','doctor123','doctor','active','verified'),
('Maria','Santos','09181234568','maria.santos@hospital.com','doctor123','doctor','active','verified'),
('David','Reyes','09191234569','david.reyes@hospital.com','doctor123','doctor','active','verified'),
('Angela','Cruz','09201234560','angela.cruz@hospital.com','doctor123','doctor','active','verified'),
('Michael','Tan','09211234561','michael.tan@hospital.com','doctor123','doctor','active','verified'),
('Sophia','Lim','09221234562','sophia.lim@hospital.com','doctor123','doctor','active','verified'),

-- Patients
('Juan','Dela Cruz','09351234567','juan@email.com','patient123','patient','active','verified'),
('Ana','Garcia','09361234568','ana@email.com','patient123','patient','active','verified'),
('Mark','Lopez','09371234569','mark@email.com','patient123','patient','active','verified'),
('Liza','Fernandez','09381234560','liza@email.com','patient123','patient','active','verified'),
('Kevin','Torres','09391234561','kevin@email.com','patient123','patient','active','verified'),
('Grace','Aquino','09401234562','grace@email.com','patient123','patient','active','verified'),

-- Secretaries
('Patricia','Flores','09411234563','patricia@hospital.com','secret123','secretary','active','verified'),
('Robert','Navarro','09421234564','robert@hospital.com','secret123','secretary','active','verified'),
('Michelle','Co','09431234565','michelle@hospital.com','secret123','secretary','active','verified'),

-- Admin
('System','Administrator','09999999999','admin@hospital.com','admin123','admin','active','verified');



-- =====================================================
-- DOCTORS
-- account_id = 1-6
-- =====================================================

INSERT INTO doctors(account_id,specialty)
VALUES
(1,'Cardiology'),
(2,'Dermatology'),
(3,'Pediatrics'),
(4,'Orthopedics'),
(5,'Neurology'),
(6,'Internal Medicine');



-- =====================================================
-- PATIENTS
-- account_id = 7-12
-- =====================================================

INSERT INTO patients
(account_id,insurance,insurance_status,preferred_specialty)
VALUES
(7,'PhilHealth','accepted','Cardiology'),
(8,'Maxicare','accepted','Dermatology'),
(9,'Medicard','accepted','Pediatrics'),
(10,'PhilHealth','declined','Orthopedics'),
(11,'Intellicare','accepted','Neurology'),
(12,'Maxicare','accepted','Internal Medicine');



-- =====================================================
-- SECRETARIES
-- account_id = 13-15
-- =====================================================

INSERT INTO secretaries
(account_id,department)
VALUES
(13,'Cardiology'),
(14,'General Medicine'),
(15,'Pediatrics');



-- =====================================================
-- ASSIGNMENTS
-- =====================================================

INSERT INTO assignments
(doctor_id,secretary_id)
VALUES
(1,1),
(2,1),
(3,2),
(4,2),
(5,3),
(6,3);



-- =====================================================
-- APPOINTMENTS
-- =====================================================

INSERT INTO appointments
(patient_id,doctor_id,insurance,room_number,date,time,status)
VALUES
(1,1,'PhilHealth','101','2026-08-10','09:00:00','confirmed'),
(2,2,'Maxicare','102','2026-08-10','10:30:00','confirmed'),
(3,3,'Medicard','201','2026-08-11','13:00:00','completed'),
(4,4,'PhilHealth','202','2026-08-12','11:15:00','cancelled'),
(5,5,'Intellicare','301','2026-08-13','15:30:00','rescheduled'),
(6,6,'Maxicare','302','2026-08-14','14:00:00','confirmed'),
(1,2,'PhilHealth','102','2026-08-15','08:30:00','completed'),
(3,5,'Medicard','301','2026-08-16','16:00:00','declined'),
(2,4,'Maxicare','202','2026-08-17','09:45:00','no show'),
(5,1,'Intellicare','101','2026-08-18','10:00:00','confirmed');



-- =====================================================
-- LOGS
-- =====================================================

INSERT INTO logs
(appointment_id,content)
VALUES
(1,'Appointment created by patient.'),
(1,'Secretary confirmed appointment.'),
(2,'Doctor assigned to appointment.'),
(3,'Consultation completed successfully.'),
(4,'Appointment cancelled by patient.'),
(5,'Appointment rescheduled due to doctor availability.'),
(6,'Insurance verified.'),
(7,'Follow-up consultation completed.'),
(8,'Doctor declined appointment because of schedule conflict.'),
(9,'Patient did not arrive for scheduled appointment.'),
(10,'Appointment confirmed via phone call.');
