-- ============================================================
-- SANITIZE DESCRIPTION AND HEALTH_INFO FIELDS
-- Allow only: alphanumeric, space, dot (.), comma (,), dash (-)
-- ============================================================

-- Step 1: Remove all special characters - keep only alphanumeric, space, dot, comma, dash
UPDATE pets SET description = REGEXP_REPLACE(description, '[^a-zA-Z0-9 .,-]', '')
WHERE id IS NOT NULL AND description IS NOT NULL;

UPDATE pets SET health_info = REGEXP_REPLACE(health_info, '[^a-zA-Z0-9 .,-]', '')
WHERE id IS NOT NULL AND health_info IS NOT NULL;

-- Step 2: Replace multiple spaces with single space
UPDATE pets SET description = TRIM(REGEXP_REPLACE(description, ' +', ' '))
WHERE id IS NOT NULL AND description IS NOT NULL;

UPDATE pets SET health_info = TRIM(REGEXP_REPLACE(health_info, ' +', ' '))
WHERE id IS NOT NULL AND health_info IS NOT NULL;

-- Step 3: Set to NULL if empty or whitespace-only
UPDATE pets SET description = NULL
WHERE id IS NOT NULL AND (description = '' OR description REGEXP '^[\\s]+$');

UPDATE pets SET health_info = NULL
WHERE id IS NOT NULL AND (health_info = '' OR health_info REGEXP '^[\\s]+$');

-- Step 4: Enforce max length (100 chars)
UPDATE pets SET description = SUBSTRING(TRIM(description), 1, 100)
WHERE id IS NOT NULL AND description IS NOT NULL AND LENGTH(TRIM(description)) > 100;

UPDATE pets SET health_info = SUBSTRING(TRIM(health_info), 1, 100)
WHERE id IS NOT NULL AND health_info IS NOT NULL AND LENGTH(TRIM(health_info)) > 100;

-- Step 5: Final cleanup - set invalid data to NULL
UPDATE pets SET description = NULL
WHERE id IS NOT NULL AND description NOT REGEXP '^[a-zA-Z0-9 .,-]*$';

UPDATE pets SET health_info = NULL
WHERE id IS NOT NULL AND health_info NOT REGEXP '^[a-zA-Z0-9 .,-]*$';

-- ============================================================
-- ADD CHECK CONSTRAINTS (after data is clean)
-- ============================================================

-- Add constraint for description - allows NULL and valid characters only
ALTER TABLE pets ADD CONSTRAINT chk_pet_description_format
CHECK (description IS NULL OR description REGEXP '^[a-zA-Z0-9 .,-]+$');

-- Add constraint for health_info - allows NULL and valid characters only
ALTER TABLE pets ADD CONSTRAINT chk_pet_health_info_format
CHECK (health_info IS NULL OR health_info REGEXP '^[a-zA-Z0-9 .,-]+$');

-- ============================================================
-- VERIFICATION QUERIES
-- ============================================================

-- Check remaining invalid description values
SELECT COUNT(*) as invalid_descriptions FROM pets
WHERE description IS NOT NULL AND description NOT REGEXP '^[a-zA-Z0-9 .,-]+$';

-- Check remaining invalid health_info values
SELECT COUNT(*) as invalid_health_info FROM pets
WHERE health_info IS NOT NULL AND health_info NOT REGEXP '^[a-zA-Z0-9 .,-]+$';

-- Show sample of cleaned data
SELECT id, name, description, health_info FROM pets 
WHERE (description IS NOT NULL OR health_info IS NOT NULL)
LIMIT 10;
