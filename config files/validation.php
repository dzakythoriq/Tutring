<?php
/**
 * Input validation utility functions
 */

/**
 * Validate email format
 * 
 * @param string $email Email to validate
 * @return boolean True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * 
 * @param string $password Password to validate
 * @param int $minLength Minimum length (default: 6)
 * @return array Result with status and message
 */
function validatePassword($password, $minLength = 6) {
    $result = ['status' => true, 'message' => ''];
    
    if (strlen($password) < $minLength) {
        $result['status'] = false;
        $result['message'] = "Password must be at least {$minLength} characters long";
    }
    
    return $result;
}

/**
 * Validate numeric value
 * 
 * @param mixed $value Value to validate
 * @param float $min Minimum value (optional)
 * @param float $max Maximum value (optional)
 * @return array Result with status and message
 */
function validateNumeric($value, $min = null, $max = null) {
    $result = ['status' => true, 'message' => ''];
    
    if (!is_numeric($value)) {
        $result['status'] = false;
        $result['message'] = "Value must be a number";
        return $result;
    }
    
    if ($min !== null && $value < $min) {
        $result['status'] = false;
        $result['message'] = "Value must be at least {$min}";
    }
    
    if ($max !== null && $value > $max) {
        $result['status'] = false;
        $result['message'] = "Value must not exceed {$max}";
    }
    
    return $result;
}

/**
 * Validate date format
 * 
 * @param string $date Date to validate
 * @param string $format Date format (default: Y-m-d)
 * @return boolean True if valid, false otherwise
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate time format
 * 
 * @param string $time Time to validate
 * @param string $format Time format (default: H:i)
 * @return boolean True if valid, false otherwise
 */
function validateTime($time, $format = 'H:i') {
    $d = DateTime::createFromFormat($format, $time);
    return $d && $d->format($format) === $time;
}

/**
 * Validate booking schedule
 * 
 * @param string $date Date of booking
 * @param string $startTime Start time
 * @param string $endTime End time
 * @return array Result with status and message
 */
function validateSchedule($date, $startTime, $endTime) {
    $result = ['status' => true, 'message' => ''];
    
    // Validate date format
    if (!validateDate($date)) {
        $result['status'] = false;
        $result['message'] = "Invalid date format. Use YYYY-MM-DD";
        return $result;
    }
    
    // Validate time format
    if (!validateTime($startTime) || !validateTime($endTime)) {
        $result['status'] = false;
        $result['message'] = "Invalid time format. Use HH:MM";
        return $result;
    }
    
    // Check if date is in the past
    $currentDate = date('Y-m-d');
    if ($date < $currentDate) {
        $result['status'] = false;
        $result['message'] = "Cannot schedule for past dates";
        return $result;
    }
    
    // Check if end time is after start time
    if (strtotime($endTime) <= strtotime($startTime)) {
        $result['status'] = false;
        $result['message'] = "End time must be after start time";
        return $result;
    }
    
    // Check if the schedule is at least 30 minutes
    $startDateTime = new DateTime($startTime);
    $endDateTime = new DateTime($endTime);
    $interval = $startDateTime->diff($endDateTime);
    $minutesDiff = ($interval->h * 60) + $interval->i;
    
    if ($minutesDiff < 30) {
        $result['status'] = false;
        $result['message'] = "Schedule must be at least 30 minutes";
        return $result;
    }
    
    return $result;
}

/**
 * Validate form inputs
 * 
 * @param array $data Form data
 * @param array $rules Validation rules
 * @return array Result with status and errors
 */
function validateForm($data, $rules) {
    $errors = [];
    $isValid = true;
    
    foreach ($rules as $field => $fieldRules) {
        foreach ($fieldRules as $rule => $ruleValue) {
            // Required field
            if ($rule === 'required' && $ruleValue === true) {
                if (!isset($data[$field]) || trim($data[$field]) === '') {
                    $errors[$field] = ucfirst($field) . ' is required';
                    $isValid = false;
                    break; // Stop further validation for this field
                }
            }
            
            // Skip validation if field is empty and not required
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                continue;
            }
            
            // Email validation
            if ($rule === 'email' && $ruleValue === true) {
                if (!validateEmail($data[$field])) {
                    $errors[$field] = 'Invalid email format';
                    $isValid = false;
                    break;
                }
            }
            
            // Minimum length
            if ($rule === 'min_length') {
                if (strlen($data[$field]) < $ruleValue) {
                    $errors[$field] = ucfirst($field) . ' must be at least ' . $ruleValue . ' characters';
                    $isValid = false;
                    break;
                }
            }
            
            // Maximum length
            if ($rule === 'max_length') {
                if (strlen($data[$field]) > $ruleValue) {
                    $errors[$field] = ucfirst($field) . ' must not exceed ' . $ruleValue . ' characters';
                    $isValid = false;
                    break;
                }
            }
            
            // Numeric validation
            if ($rule === 'numeric' && $ruleValue === true) {
                if (!is_numeric($data[$field])) {
                    $errors[$field] = ucfirst($field) . ' must be a number';
                    $isValid = false;
                    break;
                }
            }
            
            // Minimum value
            if ($rule === 'min_value') {
                if (floatval($data[$field]) < $ruleValue) {
                    $errors[$field] = ucfirst($field) . ' must be at least ' . $ruleValue;
                    $isValid = false;
                    break;
                }
            }
            
            // Maximum value
            if ($rule === 'max_value') {
                if (floatval($data[$field]) > $ruleValue) {
                    $errors[$field] = ucfirst($field) . ' must not exceed ' . $ruleValue;
                    $isValid = false;
                    break;
                }
            }
            
            // Date validation
            if ($rule === 'date' && $ruleValue === true) {
                if (!validateDate($data[$field])) {
                    $errors[$field] = 'Invalid date format. Use YYYY-MM-DD';
                    $isValid = false;
                    break;
                }
            }
            
            // Time validation
            if ($rule === 'time' && $ruleValue === true) {
                if (!validateTime($data[$field])) {
                    $errors[$field] = 'Invalid time format. Use HH:MM';
                    $isValid = false;
                    break;
                }
            }
        }
    }
    
    return ['status' => $isValid, 'errors' => $errors];
}
?>