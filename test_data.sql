-- Script per generare dati di test realistici
-- Eseguire nell'ordine presentato

-- 1. Pulire dati esistenti (ATTENZIONE: cancella tutto!)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE appointments;
TRUNCATE TABLE appointment_patterns;
TRUNCATE TABLE plan_therapies;
TRUNCATE TABLE therapeutic_plans;
TRUNCATE TABLE therapists;
TRUNCATE TABLE patients;
TRUNCATE TABLE users;
TRUNCATE TABLE user_profiles;
TRUNCATE TABLE districts;
TRUNCATE TABLE specializations;
TRUNCATE TABLE treatment_types;
TRUNCATE TABLE specialization_treatments;
TRUNCATE TABLE absence_counters;
TRUNCATE TABLE absences;
SET FOREIGN_KEY_CHECKS = 1;

-- 2. Inserire dati base
-- Distretti
INSERT INTO districts (code, name, asl_reference) VALUES
('DST01', 'Distretto Centro', 'ASL Napoli 1'),
('DST02', 'Distretto Nord', 'ASL Napoli 2'),
('DST03', 'Distretto Sud', 'ASL Napoli 3');

-- Specializzazioni
INSERT INTO specializations (code, name, description) VALUES
('LOG', 'Logopedia', 'Terapia del linguaggio'),
('PSI', 'Psicomotricità', 'Terapia psicomotoria'),
('FIS', 'Fisioterapia', 'Terapia fisica e riabilitazione'),
('PSY', 'Psicologia', 'Supporto psicologico'),
('NPI', 'Neuropsichiatria', 'Valutazione neuropsichiatrica');

-- Tipi di trattamento
INSERT INTO treatment_types (code, name, description) VALUES
('LOG_IND', 'Logopedia Individuale', 'Seduta logopedica individuale'),
('LOG_GRP', 'Logopedia Gruppo', 'Seduta logopedica di gruppo'),
('PSM_IND', 'Psicomotricità Individuale', 'Seduta psicomotoria individuale'),
('PSM_GRP', 'Psicomotricità Gruppo', 'Seduta psicomotoria di gruppo'),
('FIS_IND', 'Fisioterapia Individuale', 'Seduta fisioterapica individuale'),
('PSY_IND', 'Supporto Psicologico', 'Seduta psicologica individuale'),
('NPI_VAL', 'Valutazione NPI', 'Valutazione neuropsichiatrica');

-- Relazioni specializzazione-trattamenti
INSERT INTO specialization_treatments (specialization_id, treatment_type_id) VALUES
(1, 1), (1, 2), -- Logopedia
(2, 3), (2, 4), -- Psicomotricità
(3, 5),         -- Fisioterapia
(4, 6),         -- Psicologia
(5, 7);         -- NPI

-- 3. Creare utenti (password: 'password123' hashata con bcrypt)
DELIMITER $$
CREATE PROCEDURE CreateTestUsers()
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE user_id INT;
    
    -- Admin
    INSERT INTO users (email, password_hash, auth_key, status) 
    VALUES ('admin@therapy.com', '$2y$10$EjO3L.Hq.Xlhq1VH1CV29u3h4JHH0MBhVqEh5gVtMx8p3pN3nO9Aq', 'test_auth_key_admin', 'active');
    
    -- Manager (Sharon)
    INSERT INTO users (email, password_hash, auth_key, status) 
    VALUES ('sharon@therapy.com', '$2y$10$EjO3L.Hq.Xlhq1VH1CV29u3h4JHH0MBhVqEh5gVtMx8p3pN3nO9Aq', 'test_auth_key_manager', 'active');
    
    -- Amministrazione
    INSERT INTO users (email, password_hash, auth_key, status) 
    VALUES ('amministrazione@therapy.com', '$2y$10$EjO3L.Hq.Xlhq1VH1CV29u3h4JHH0MBhVqEh5gVtMx8p3pN3nO9Aq', 'test_auth_key_admin2', 'active');
    
    -- Terapisti (20)
    WHILE i <= 20 DO
        INSERT INTO users (email, password_hash, auth_key, status) 
        VALUES (
            CONCAT('terapista', i, '@therapy.com'),
            '$2y$10$EjO3L.Hq.Xlhq1VH1CV29u3h4JHH0MBhVqEh5gVtMx8p3pN3nO9Aq',
            CONCAT('test_auth_key_therapist_', i),
            'active'
        );
        
        SET user_id = LAST_INSERT_ID();
        
        -- Profilo utente
        INSERT INTO user_profiles (user_id, first_name, last_name, fiscal_code, phone) 
        VALUES (
            user_id,
            CONCAT('Nome', i),
            CONCAT('Terapista', i),
            CONCAT('TRPST', LPAD(i, 2, '0'), 'A00A000A'),
            CONCAT('333', LPAD(i, 7, '0'))
        );
        
        -- Assegna come terapista con specializzazione casuale
        INSERT INTO therapists (user_id, specialization_id, weekly_hours_contract, calendar_color, is_active)
        VALUES (
            user_id,
            1 + (i % 5), -- Distribuisce tra le 5 specializzazioni
            CASE 
                WHEN i % 3 = 0 THEN 20  -- Part-time
                WHEN i % 3 = 1 THEN 30  -- 3/4
                ELSE 38                 -- Full-time
            END,
            CONCAT('#', 
                LPAD(CONV(50 + i * 10, 10, 16), 2, '0'),
                LPAD(CONV(100 + i * 5, 10, 16), 2, '0'),
                LPAD(CONV(150 + i * 3, 10, 16), 2, '0')
            ),
            TRUE
        );
        
        SET i = i + 1;
    END WHILE;
    
    -- Utenti per pazienti/tutori
    SET i = 1;
    WHILE i <= 30 DO
        INSERT INTO users (email, password_hash, auth_key, status) 
        VALUES (
            CONCAT('tutore', i, '@gmail.com'),
            '$2y$10$EjO3L.Hq.Xlhq1VH1CV29u3h4JHH0MBhVqEh5gVtMx8p3pN3nO9Aq',
            CONCAT('test_auth_key_tutor_', i),
            'active'
        );
        
        SET user_id = LAST_INSERT_ID();
        
        INSERT INTO user_profiles (user_id, first_name, last_name, fiscal_code, phone) 
        VALUES (
            user_id,
            CONCAT('Genitore', i),
            CONCAT('Cognome', i),
            CONCAT('GNTTR', LPAD(i, 2, '0'), 'B00B000B'),
            CONCAT('334', LPAD(i, 7, '0'))
        );
        
        SET i = i + 1;
    END WHILE;
END$$
DELIMITER ;

CALL CreateTestUsers();
DROP PROCEDURE CreateTestUsers;

-- 4. Creare pazienti
DELIMITER $$
CREATE PROCEDURE CreateTestPatients()
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE patient_id INT;
    
    WHILE i <= 50 DO
        INSERT INTO patients (first_name, last_name, birth_date, fiscal_code, district_id, notes)
        VALUES (
            CONCAT('Bambino', i),
            CONCAT('Paziente', i),
            DATE_SUB(CURDATE(), INTERVAL (3 + FLOOR(RAND() * 12)) YEAR), -- Età 3-15 anni
            CONCAT('PZNT', LPAD(i, 2, '0'), 'C00C000C'),
            1 + FLOOR(RAND() * 3), -- Distretto casuale
            CASE 
                WHEN RAND() < 0.3 THEN 'Necessita supporto costante'
                WHEN RAND() < 0.6 THEN 'Progressi buoni'
                ELSE NULL
            END
        );
        
        SET patient_id = LAST_INSERT_ID();
        
        -- Collega a tutore (alcuni pazienti hanno 2 tutori)
        INSERT INTO account_patients (user_id, patient_id, has_parental_authority, relationship_type)
        VALUES (
            23 + ((i - 1) % 30), -- Tutori iniziano da user_id 24
            patient_id,
            TRUE,
            'parent'
        );
        
        -- 20% ha un secondo tutore
        IF RAND() < 0.2 THEN
            INSERT INTO account_patients (user_id, patient_id, has_parental_authority, relationship_type)
            VALUES (
                23 + ((i + 14) % 30), -- Altro tutore
                patient_id,
                TRUE,
                'parent'
            );
        END IF;
        
        SET i = i + 1;
    END WHILE;
END$$
DELIMITER ;

CALL CreateTestPatients();
DROP PROCEDURE CreateTestPatients;

-- 5. Creare piani terapeutici con appuntamenti
DELIMITER $$
CREATE PROCEDURE CreateTherapeuticPlansWithAppointments()
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE j INT;
    DECLARE k INT;
    DECLARE plan_id INT;
    DECLARE therapy_id INT;
    DECLARE pattern_id INT;
    DECLARE therapist_id INT;
    DECLARE current_date DATE;
    DECLARE end_date DATE;
    DECLARE slot_time TIME;
    DECLARE duration INT;
    DECLARE appointments_count INT DEFAULT 0;
    
    -- Crea 30 piani attivi (60% dei pazienti ha piano attivo)
    WHILE i <= 30 DO
        -- Piano con date casuali negli ultimi 3 mesi
        SET current_date = DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 90) DAY);
        
        INSERT INTO therapeutic_plans (
            patient_id, 
            start_date, 
            duration_days, 
            health_regime, 
            status, 
            created_by
        ) VALUES (
            i,
            current_date,
            180, -- 6 mesi standard
            ELT(1 + FLOOR(RAND() * 4), 'L11', 'L11DOM', 'ABA', 'Private'),
            'active',
            3 -- Amministrazione user_id
        );
        
        SET plan_id = LAST_INSERT_ID();
        SET end_date = DATE_ADD(current_date, INTERVAL 180 DAY);
        
        -- Aggiungi 2-3 terapie per piano
        SET j = 0;
        WHILE j < (2 + FLOOR(RAND() * 2)) DO
            INSERT INTO plan_therapies (
                therapeutic_plan_id,
                treatment_type_id,
                weekly_hours,
                is_group,
                notes
            ) VALUES (
                plan_id,
                1 + FLOOR(RAND() * 6), -- Trattamento casuale
                1 + FLOOR(RAND() * 3), -- 1-3 ore settimanali
                RAND() < 0.3, -- 30% sono di gruppo
                NULL
            );
            
            SET therapy_id = LAST_INSERT_ID();
            
            -- Crea pattern settimanali per questa terapia
            SET k = 0;
            WHILE k < (1 + FLOOR(RAND() * 3)) DO -- 1-3 sessioni a settimana
                -- Seleziona terapista casuale con la specializzazione giusta
                SELECT t.id INTO therapist_id
                FROM therapists t
                JOIN specializations s ON t.specialization_id = s.id
                JOIN specialization_treatments st ON s.id = st.specialization_id
                JOIN plan_therapies pt ON st.treatment_type_id = pt.treatment_type_id
                WHERE pt.id = therapy_id
                ORDER BY RAND()
                LIMIT 1;
                
                -- Orario casuale tra 8:00 e 18:00
                SET slot_time = MAKETIME(8 + FLOOR(RAND() * 10), IF(RAND() < 0.5, 0, 30), 0);
                SET duration = IF(RAND() < 0.7, 60, 45); -- 70% sessioni da 60 min, 30% da 45 min
                
                INSERT INTO appointment_patterns (
                    plan_therapy_id,
                    therapist_id,
                    day_of_week,
                    start_time,
                    duration_minutes,
                    location_type,
                    valid_from,
                    valid_to,
                    created_by
                ) VALUES (
                    therapy_id,
                    therapist_id,
                    1 + FLOOR(RAND() * 5), -- Lun-Ven
                    slot_time,
                    duration,
                    IF(RAND() < 0.9, 'office', 'home'),
                    current_date,
                    end_date,
                    2 -- Manager user_id
                );
                
                SET pattern_id = LAST_INSERT_ID();
                
                -- Genera appuntamenti basati sul pattern
                CALL GenerateAppointmentsFromPattern(pattern_id, appointments_count);
                
                SET k = k + 1;
            END WHILE;
            
            SET j = j + 1;
        END WHILE;
        
        -- Inizializza contatore assenze
        INSERT INTO absence_counters (patient_id, therapeutic_plan_id)
        VALUES (i, plan_id);
        
        SET i = i + 1;
    END WHILE;
    
    SELECT CONCAT('Generati ', appointments_count, ' appuntamenti totali') as Result;
END$$

-- Procedura helper per generare appuntamenti da pattern
CREATE PROCEDURE GenerateAppointmentsFromPattern(IN p_pattern_id INT, INOUT p_count INT)
BEGIN
    DECLARE v_therapist_id INT;
    DECLARE v_plan_therapy_id INT;
    DECLARE v_patient_id INT;
    DECLARE v_day_of_week INT;
    DECLARE v_start_time TIME;
    DECLARE v_duration INT;
    DECLARE v_location VARCHAR(10);
    DECLARE v_valid_from DATE;
    DECLARE v_valid_to DATE;
    DECLARE v_current_date DATE;
    DECLARE v_appointment_datetime DATETIME;
    DECLARE v_status VARCHAR(20);
    
    -- Recupera dati pattern
    SELECT 
        ap.therapist_id, ap.plan_therapy_id, ap.day_of_week, 
        ap.start_time, ap.duration_minutes, ap.location_type,
        ap.valid_from, ap.valid_to, tp.patient_id
    INTO 
        v_therapist_id, v_plan_therapy_id, v_day_of_week,
        v_start_time, v_duration, v_location,
        v_valid_from, v_valid_to, v_patient_id
    FROM appointment_patterns ap
    JOIN plan_therapies pt ON ap.plan_therapy_id = pt.id
    JOIN therapeutic_plans tp ON pt.therapeutic_plan_id = tp.id
    WHERE ap.id = p_pattern_id;
    
    SET v_current_date = v_valid_from;
    
    -- Genera appuntamenti
    WHILE v_current_date <= v_valid_to AND v_current_date <= CURDATE() + INTERVAL 30 DAY DO
        IF DAYOFWEEK(v_current_date) = v_day_of_week + 1 THEN -- MySQL: 1=Dom, 2=Lun
            SET v_appointment_datetime = TIMESTAMP(v_current_date, v_start_time);
            
            -- Determina status basato sulla data
            IF v_appointment_datetime < NOW() - INTERVAL 2 HOUR THEN
                -- Appuntamento passato
                SET v_status = CASE
                    WHEN RAND() < 0.85 THEN 'completed'
                    WHEN RAND() < 0.7 THEN 'absent_justified'
                    ELSE 'absent_not_justified'
                END;
            ELSE
                SET v_status = 'scheduled';
            END IF;
            
            -- Inserisci appuntamento (ignora conflitti)
            INSERT IGNORE INTO appointments (
                pattern_id,
                plan_therapy_id,
                therapist_id,
                patient_id,
                appointment_datetime,
                duration_minutes,
                location_type,
                status,
                created_by
            ) VALUES (
                p_pattern_id,
                v_plan_therapy_id,
                v_therapist_id,
                v_patient_id,
                v_appointment_datetime,
                v_duration,
                v_location,
                v_status,
                2
            );
            
            SET p_count = p_count + ROW_COUNT();
            
            -- Se assente, crea record assenza
            IF v_status IN ('absent_justified', 'absent_not_justified') AND ROW_COUNT() > 0 THEN
                INSERT INTO absences (
                    appointment_id,
                    patient_id,
                    absence_date,
                    reason,
                    is_justified,
                    is_communicated,
                    communicated_by,
                    communicated_at
                ) VALUES (
                    LAST_INSERT_ID(),
                    v_patient_id,
                    v_appointment_datetime,
                    ELT(1 + FLOOR(RAND() * 3), 'health', 'family', 'other'),
                    v_status = 'absent_justified',
                    TRUE,
                    2,
                    v_appointment_datetime
                );
            END IF;
        END IF;
        
        SET v_current_date = DATE_ADD(v_current_date, INTERVAL 1 DAY);
    END WHILE;
END$$
DELIMITER ;

CALL CreateTherapeuticPlansWithAppointments();
DROP PROCEDURE GenerateAppointmentsFromPattern;
DROP PROCEDURE CreateTherapeuticPlansWithAppointments;

-- 6. Aggiorna contatori assenze
UPDATE absence_counters ac
SET 
    total_appointments = (
        SELECT COUNT(*) 
        FROM appointments a
        JOIN plan_therapies pt ON a.plan_therapy_id = pt.id
        WHERE pt.therapeutic_plan_id = ac.therapeutic_plan_id
        AND a.patient_id = ac.patient_id
    ),
    total_absences = (
        SELECT COUNT(*) 
        FROM appointments a
        JOIN plan_therapies pt ON a.plan_therapy_id = pt.id
        WHERE pt.therapeutic_plan_id = ac.therapeutic_plan_id
        AND a.patient_id = ac.patient_id
        AND a.status IN ('absent_justified', 'absent_not_justified')
    ),
    justified_absences = (
        SELECT COUNT(*) 
        FROM appointments a
        JOIN plan_therapies pt ON a.plan_therapy_id = pt.id
        WHERE pt.therapeutic_plan_id = ac.therapeutic_plan_id
        AND a.patient_id = ac.patient_id
        AND a.status = 'absent_justified'
    ),
    unjustified_absences = (
        SELECT COUNT(*) 
        FROM appointments a
        JOIN plan_therapies pt ON a.plan_therapy_id = pt.id
        WHERE pt.therapeutic_plan_id = ac.therapeutic_plan_id
        AND a.patient_id = ac.patient_id
        AND a.status = 'absent_not_justified'
    );

-- 7. Crea alcuni appuntamenti per oggi e prossimi giorni per test
INSERT INTO appointments (
    pattern_id,
    plan_therapy_id,
    therapist_id,
    patient_id,
    appointment_datetime,
    duration_minutes,
    location_type,
    status,
    created_by
)
SELECT 
    NULL, -- Senza pattern (eccezioni)
    pt.id,
    t.id,
    tp.patient_id,
    TIMESTAMP(CURDATE() + INTERVAL days DAY, MAKETIME(8 + FLOOR(RAND() * 10), IF(RAND() < 0.5, 0, 30), 0)),
    60,
    'office',
    'scheduled',
    2
FROM 
    plan_therapies pt
    JOIN therapeutic_plans tp ON pt.therapeutic_plan_id = tp.id
    JOIN therapists t ON t.specialization_id = (
        SELECT st.specialization_id 
        FROM specialization_treatments st 
        WHERE st.treatment_type_id = pt.treatment_type_id 
        LIMIT 1
    ),
    (SELECT 0 as days UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) d
WHERE 
    tp.status = 'active'
    AND RAND() < 0.3 -- 30% di probabilità
LIMIT 50;

-- 8. Report finale
SELECT 'Dati generati con successo!' as Status;
SELECT COUNT(*) as total_users FROM users;
SELECT COUNT(*) as total_therapists FROM therapists;
SELECT COUNT(*) as total_patients FROM patients;
SELECT COUNT(*) as total_plans FROM therapeutic_plans;
SELECT COUNT(*) as total_appointments FROM appointments;
SELECT 
    status, 
    COUNT(*) as count 
FROM appointments 
GROUP BY status;