CREATE DATABASE 
-- users
    -- user_id {auto-increment} PK
    -- first_name
    -- role = [patient, secretary, doctors, administrator]
    -- account_status = [verified, pending, suspended]
    -- activity_status = [active, inactive]
    -- insurance = [maxicare, intellicare, medicard, ...]
    -- insurance_status = [accepted, declined]
    -- preferred_specialty

-- doctors
    -- doctor_id {auto-increement} PK
    -- first_name
    -- last_name
    -- specialty

-- appointment
    -- appointment_id {auto-increment} PK
    -- user_id [dapat patients lang pwede] FK
    -- doctor_id FK
    -- insurance_id FK
    -- room_number
    -- date (scheduled date)
    -- time (scheduled time)
    -- status = [confirmed, cancelled, no show, reschedules, declined, completed, ...]

-- logs
    -- log_id {auto-increment} PK
    -- appointment_id FK
    -- message
    -- date (current date of log creation)
    -- time (current time of log creation)