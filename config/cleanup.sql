-- ============================================================
--  PAWCONNECT DATABASE CLEANUP SCRIPTS
--  Fix invalid data to meet new validation requirements
--  Run these BEFORE the new validation goes live
-- ============================================================

-- ============================================================
-- 1. USERS TABLE CLEANUP
-- ============================================================

-- 1.1 Fix invalid/missing phone numbers - replace with default 09123456789
UPDATE users SET phone = '09123456789' 
WHERE id IS NOT NULL AND (
   phone IS NULL 
   OR phone = '' 
   OR LENGTH(REPLACE(phone, ' ', '')) != 11 
   OR NOT phone REGEXP '^09[0-9]{9}$');

-- 1.2 Fix invalid Facebook URLs - replace with https://facebook.com/{username}
UPDATE users SET facebook = CONCAT('https://facebook.com/', username) 
WHERE id IS NOT NULL AND (
   facebook IS NULL 
   OR facebook = '' 
   OR facebook NOT LIKE 'https://facebook.com/%'
   OR LENGTH(facebook) < 25);

-- 1.3 Fix impossible birthdates (too old or too new) - set to 2000-01-01
UPDATE users SET birthdate = '2000-01-01' 
WHERE id IS NOT NULL AND (
   birthdate IS NULL 
   OR YEAR(birthdate) < 1910 
   OR YEAR(birthdate) > 2008
   OR birthdate > CURDATE());

-- 1.4 Trim and clean addresses, set NULL if too long
UPDATE users SET address = NULL 
WHERE id IS NOT NULL AND (
   address IS NULL 
   OR address = '' 
   OR LENGTH(address) > 100);

UPDATE users SET address = SUBSTRING(TRIM(address), 1, 100) 
WHERE id IS NOT NULL AND address IS NOT NULL AND LENGTH(TRIM(address)) > 100;

-- 1.5 Trim full names, set reasonable default if too long
UPDATE users SET full_name = NULL 
WHERE id IS NOT NULL AND (full_name IS NULL OR full_name = '');

UPDATE users SET full_name = SUBSTRING(TRIM(full_name), 1, 50) 
WHERE id IS NOT NULL AND full_name IS NOT NULL AND LENGTH(TRIM(full_name)) > 50;

-- ============================================================
-- 2. PETS TABLE CLEANUP
-- ============================================================

-- 2.1 Remove special characters from pet names - keep only alphanumeric, space, dot, hyphen
UPDATE pets SET name = REGEXP_REPLACE(name, '[^a-zA-Z0-9 .\-]', '') 
WHERE id IS NOT NULL;

-- Trim and remove extra spaces
UPDATE pets SET name = TRIM(REGEXP_REPLACE(name, '\s+', ' ')) 
WHERE id IS NOT NULL;

-- Trim pet names to 20 chars max
UPDATE pets SET name = SUBSTRING(TRIM(name), 1, 20) 
WHERE id IS NOT NULL AND LENGTH(TRIM(name)) > 20;

-- 2.2 Remove SQL injection from breed field
UPDATE pets SET breed = REGEXP_REPLACE(breed, '<[^>]*>', '') 
WHERE id IS NOT NULL AND (breed LIKE '%<%>' OR breed LIKE '%script%');

-- Trim breed to 30 chars max
UPDATE pets SET breed = SUBSTRING(TRIM(REGEXP_REPLACE(breed, '<[^>]*>', '')), 1, 30) 
WHERE id IS NOT NULL AND breed IS NOT NULL AND LENGTH(TRIM(REGEXP_REPLACE(breed, '<[^>]*>', ''))) > 30;

-- 2.3 Fix impossible pet ages
-- Step 1: Set extreme/invalid ages to default (handles overflow cases like 999999999999999999e19)
UPDATE pets SET age = '1 days' 
WHERE id IS NOT NULL AND (
    age IS NULL 
    OR age = '' 
    OR age LIKE '%e%'
    OR age LIKE '%E%'
    OR LENGTH(age) > 30
);

-- Step 2: Extract numeric part and unit, reconstruct with proper format
UPDATE pets SET age = CONCAT(
    CAST(CAST(REGEXP_SUBSTR(age, '[0-9]+') AS UNSIGNED) AS CHAR), ' ',
    LOWER(REGEXP_SUBSTR(age, '[a-z]+'))
)
WHERE id IS NOT NULL AND age IS NOT NULL AND age != '' AND age NOT LIKE '%e%' AND age NOT LIKE '%E%';

-- Step 3: Cap years to max 10
UPDATE pets SET age = '10 years' 
WHERE id IS NOT NULL AND age LIKE '%years' AND CAST(SUBSTRING_INDEX(age, ' ', 1) AS UNSIGNED) > 10;

-- Step 4: Cap months to max 12
UPDATE pets SET age = '12 months' 
WHERE id IS NOT NULL AND age LIKE '%months' AND CAST(SUBSTRING_INDEX(age, ' ', 1) AS UNSIGNED) > 12;

-- Step 5: Cap weeks to max 4
UPDATE pets SET age = '4 weeks' 
WHERE id IS NOT NULL AND age LIKE '%weeks' AND CAST(SUBSTRING_INDEX(age, ' ', 1) AS UNSIGNED) > 4;

-- Step 6: Cap days to max 31
UPDATE pets SET age = '31 days' 
WHERE id IS NOT NULL AND age LIKE '%days' AND CAST(SUBSTRING_INDEX(age, ' ', 1) AS UNSIGNED) > 31;

-- Step 7: Fix 0 or null ages - set to 1 days
UPDATE pets SET age = '1 days' 
WHERE id IS NOT NULL AND (age IS NULL OR age = '' OR CAST(SUBSTRING_INDEX(age, ' ', 1) AS UNSIGNED) = 0);

-- 2.4 Remove SQL injection from description
UPDATE pets SET description = REGEXP_REPLACE(description, '<[^>]*>', '') 
WHERE id IS NOT NULL AND (description LIKE '%<%>' OR description LIKE '%script%' OR description LIKE '%OR%1%=1%');

-- Trim description to 100 chars max
UPDATE pets SET description = SUBSTRING(TRIM(REGEXP_REPLACE(description, '<[^>]*>', '')), 1, 100) 
WHERE id IS NOT NULL AND description IS NOT NULL AND LENGTH(TRIM(REGEXP_REPLACE(description, '<[^>]*>', ''))) > 100;

-- 2.5 Remove SQL injection from health_info
UPDATE pets SET health_info = REGEXP_REPLACE(health_info, '<[^>]*>', '') 
WHERE id IS NOT NULL AND (health_info LIKE '%<%>' OR health_info LIKE '%script%' OR health_info LIKE '%OR%1%=1%');

-- Trim health_info to 100 chars max
UPDATE pets SET health_info = SUBSTRING(TRIM(REGEXP_REPLACE(health_info, '<[^>]*>', '')), 1, 100) 
WHERE id IS NOT NULL AND health_info IS NOT NULL AND LENGTH(TRIM(REGEXP_REPLACE(health_info, '<[^>]*>', ''))) > 100;

-- ============================================================
-- 3. VERIFICATION QUERIES (Run these to see what was fixed)
-- ============================================================

-- Check users with still-invalid phone numbers
SELECT id, username, phone FROM users 
WHERE phone IS NOT NULL AND phone NOT REGEXP '^09[0-9]{9}$';

-- Check users with still-invalid Facebook URLs
SELECT id, username, facebook FROM users 
WHERE facebook IS NOT NULL AND facebook NOT LIKE 'https://facebook.com/%';

-- Check users with still-invalid birthdates
SELECT id, username, birthdate FROM users 
WHERE YEAR(birthdate) < 1910 OR YEAR(birthdate) > 2008 OR birthdate > CURDATE();

-- Check pets with still-invalid ages
SELECT id, name, age FROM pets 
WHERE age NOT IN ('Unknown', '1 day', '7 days', '1 week', '2 weeks', '1 month', '2 months', '3 months', '6 months', '1 year', '2 years', '3 years', '4 years', '5 years', '6 years', '7 years', '8 years', '9 years', '10 years')
AND age NOT LIKE '% days'
AND age NOT LIKE '% weeks'
AND age NOT LIKE '% months'
AND age NOT LIKE '% years';

-- Check for remaining SQL injection attempts
SELECT id, name FROM pets WHERE name LIKE '%<script%' OR name LIKE '%OR%1%=1%';
SELECT id, username, facebook FROM users WHERE facebook LIKE '%javascript%' OR facebook LIKE '%<script%';
