<?php
// ============================================================
//  config/validation.php
//  Input validation and sanitization utilities
// ============================================================

/**
 * Remove all emojis and special characters, keep only alphanumeric + basic punctuation
 */
function remove_emojis(string $text): string {
    // Remove emojis (Unicode ranges)
    $text = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $text);
    // Remove other special Unicode characters
    $text = preg_replace('/[^\x{0000}-\x{007F}]/u', '', $text);
    return trim($text);
}

/**
 * Validate and sanitize phone number
 * Must be exactly 11 digits, start with 09, numbers only
 */
function validate_phone(string $phone): array {
    $phone = trim(preg_replace('/[^0-9]/', '', $phone));
    
    if (strlen($phone) !== 11) {
        return ['valid' => false, 'error' => 'Phone number must be exactly 11 digits.', 'value' => ''];
    }
    if (substr($phone, 0, 2) !== '09') {
        return ['valid' => false, 'error' => 'Phone number must start with 09.', 'value' => ''];
    }
    
    return ['valid' => true, 'error' => '', 'value' => $phone];
}

/**
 * Validate and sanitize Facebook URL
 * Must start with https://facebook.com/
 */
function validate_facebook(string $url): array {
    $url = trim(remove_emojis($url));
    
    if (empty($url)) {
        return ['valid' => false, 'error' => 'Facebook URL is required.', 'value' => ''];
    }
    
    if (strpos($url, 'https://facebook.com/') !== 0) {
        return ['valid' => false, 'error' => 'Facebook URL must start with https://facebook.com/', 'value' => ''];
    }
    
    // Very basic URL structure check
    if (strlen($url) < 25) {
        return ['valid' => false, 'error' => 'Facebook URL appears to be incomplete.', 'value' => ''];
    }
    
    return ['valid' => true, 'error' => '', 'value' => $url];
}

/**
 * Validate full name: max 50 chars, no emojis
 */
function validate_full_name(string $name): array {
    $name = trim(remove_emojis($name));
    
    if (empty($name)) {
        return ['valid' => false, 'error' => 'Full name is required.', 'value' => ''];
    }
    
    if (strlen($name) > 50) {
        return ['valid' => false, 'error' => 'Full name must not exceed 50 characters.', 'value' => ''];
    }
    
    return ['valid' => true, 'error' => '', 'value' => $name];
}

/**
 * Validate pet name: max 20 chars, no emojis
 */
function validate_pet_name(string $name): array {
    $name = trim(remove_emojis($name));
    
    if (empty($name)) {
        return ['valid' => false, 'error' => 'Pet name is required.', 'value' => ''];
    }
    
    if (strlen($name) > 20) {
        return ['valid' => false, 'error' => 'Pet name must not exceed 20 characters.', 'value' => ''];
    }
    
    return ['valid' => true, 'error' => '', 'value' => $name];
}

/**
 * Validate breed: max 30 chars, no emojis
 */
function validate_breed(string $breed): array {
    $breed = trim(remove_emojis($breed));
    
    if (empty($breed)) {
        return ['valid' => false, 'error' => 'Breed is required.', 'value' => ''];
    }
    
    if (strlen($breed) > 30) {
        return ['valid' => false, 'error' => 'Breed must not exceed 30 characters.', 'value' => ''];
    }
    
    return ['valid' => true, 'error' => '', 'value' => $breed];
}

/**
 * Validate pet age: not negative, max 10 years
 */
function validate_pet_age(int|string $age_value, string $age_unit): array {
    $age_value = (int)$age_value;
    
    if ($age_value < 0) {
        return ['valid' => false, 'error' => 'Pet age cannot be negative.', 'value' => ''];
    }
    
    // Convert to years for validation
    $age_in_years = $age_value;
    if ($age_unit === 'month') {
        $age_in_years = $age_value / 12;
    } elseif ($age_unit === 'week') {
        $age_in_years = $age_value / 52;
    } elseif ($age_unit === 'day') {
        $age_in_years = $age_value / 365;
    }
    
    if ($age_in_years > 10) {
        return ['valid' => false, 'error' => 'We do not accept pets older than 10 years for adoption.', 'value' => ''];
    }
    
    return ['valid' => true, 'error' => '', 'value' => $age_value];
}

/**
 * Validate pet description: max 100 chars, no emojis
 */
function validate_pet_description(string $text): array {
    $text = trim(remove_emojis($text));
    
    if (empty($text)) {
        return ['valid' => false, 'error' => 'Pet description is required.', 'value' => ''];
    }
    
    if (strlen($text) > 100) {
        return ['valid' => false, 'error' => 'Pet description must not exceed 100 characters.', 'value' => ''];
    }
    
    return ['valid' => true, 'error' => '', 'value' => $text];
}

/**
 * Validate pet health info: max 100 chars, no emojis
 */
function validate_pet_health_info(string $text): array {
    $text = trim(remove_emojis($text));
    
    if (strlen($text) > 100) {
        return ['valid' => false, 'error' => 'Health info must not exceed 100 characters.', 'value' => ''];
    }
    
    return ['valid' => true, 'error' => '', 'value' => $text];
}

/**
 * Validate birthdate: must be 18+ and not older than 116 years
 */
function validate_birthdate(string $birthdate): array {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
        return ['valid' => false, 'error' => 'Please enter a valid birthdate in YYYY-MM-DD format.', 'value' => ''];
    }
    
    try {
        $dob = new DateTime($birthdate);
        $today = new DateTime('today');
    } catch (Exception $e) {
        return ['valid' => false, 'error' => 'Invalid birthdate.', 'value' => ''];
    }
    
    if ($dob > $today) {
        return ['valid' => false, 'error' => 'Birthdate cannot be in the future.', 'value' => ''];
    }
    
    $age = $dob->diff($today)->y;
    
    if ($age < 18) {
        return ['valid' => false, 'error' => 'You must be at least 18 years old to register.', 'value' => ''];
    }
    
    if ($age > 116) {
        return ['valid' => false, 'error' => 'Please enter a valid birthdate.', 'value' => ''];
    }
    
    return ['valid' => true, 'error' => '', 'value' => $birthdate];
}

/**
 * Validate address: max 100 chars, no emojis, remove special characters
 */
function validate_address(string $address): array {
    $address = trim(remove_emojis($address));
    
    if (empty($address)) {
        return ['valid' => false, 'error' => 'Address is required.', 'value' => ''];
    }
    
    if (strlen($address) > 100) {
        return ['valid' => false, 'error' => 'Address must not exceed 100 characters.', 'value' => ''];
    }
    
    return ['valid' => true, 'error' => '', 'value' => $address];
}
