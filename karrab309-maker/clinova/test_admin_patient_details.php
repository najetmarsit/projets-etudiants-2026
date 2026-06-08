<?php
// test_admin_patient_details.php - Comprehensive testing for Patient Details functionality

echo "=== Medical API Admin - Patient Details Testing ===\n\n";

// Database configuration
$host = 'localhost';
$dbname = 'laravel';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

$tests_passed = 0;
$tests_total = 0;

function test_result($test_name, $passed, $message = '') {
    global $tests_passed, $tests_total;
    $tests_total++;
    if ($passed) {
        $tests_passed++;
        echo "✓ $test_name: PASSED\n";
    } else {
        echo "✗ $test_name: FAILED";
        if ($message) echo " - $message";
        echo "\n";
    }
}

// Test 1: Database connection
test_result("Database Connection", true, "Connected to $dbname");

// Test 2: Check if patients table exists and has data
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM patients");
    $patient_count = $stmt->fetch()['count'];
    test_result("Patients Table Access", true, "Found $patient_count patients in database");
} catch (Exception $e) {
    test_result("Patients Table Access", false, $e->getMessage());
}

// Test 3: Test patient details query
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.email, u.name, u.created_at as user_created_at
        FROM patients p
        JOIN users u ON p.user_id = u.id
        LIMIT 1
    ");
    $stmt->execute();
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    $has_required_fields = $patient && isset($patient['name']) && isset($patient['email']) && isset($patient['age']);
    test_result("Patient Details Query", $has_required_fields, $has_required_fields ? "Patient data retrieved successfully" : "Missing required fields");
} catch (Exception $e) {
    test_result("Patient Details Query", false, $e->getMessage());
}

// Test 4: Test patient operations query
try {
    // Get a patient with operations
    $stmt = $pdo->prepare("
        SELECT p.id
        FROM patients p
        JOIN operations o ON p.id = o.patient_id
        LIMIT 1
    ");
    $stmt->execute();
    $patient_with_ops = $stmt->fetch();

    if ($patient_with_ops) {
        $patient_id = $patient_with_ops['id'];

        $ops_stmt = $pdo->prepare("
            SELECT o.*, u.name as doctor_name
            FROM operations o
            LEFT JOIN users u ON o.doctor_id = u.id
            WHERE o.patient_id = ?
            ORDER BY o.operation_date DESC
        ");
        $ops_stmt->execute([$patient_id]);
        $operations = $ops_stmt->fetchAll(PDO::FETCH_ASSOC);

        $has_operations = !empty($operations);
        test_result("Patient Operations Query", $has_operations, "Found " . count($operations) . " operations for patient $patient_id");
    } else {
        test_result("Patient Operations Query", true, "No patients with operations found (skipping detailed test)");
    }
} catch (Exception $e) {
    test_result("Patient Operations Query", false, $e->getMessage());
}

// Test 5: Test patient health indicators query
try {
    // Get a patient with health indicators
    $stmt = $pdo->prepare("
        SELECT p.id
        FROM patients p
        JOIN health_indicators hi ON p.id = hi.patient_id
        LIMIT 1
    ");
    $stmt->execute();
    $patient_with_hi = $stmt->fetch();

    if ($patient_with_hi) {
        $patient_id = $patient_with_hi['id'];

        $hi_stmt = $pdo->prepare("
            SELECT * FROM health_indicators
            WHERE patient_id = ?
            ORDER BY recorded_at DESC
            LIMIT 10
        ");
        $hi_stmt->execute([$patient_id]);
        $indicators = $hi_stmt->fetchAll(PDO::FETCH_ASSOC);

        $has_indicators = !empty($indicators);
        test_result("Health Indicators Query", $has_indicators, "Found " . count($indicators) . " health indicators for patient $patient_id");
    } else {
        test_result("Health Indicators Query", true, "No patients with health indicators found (skipping detailed test)");
    }
} catch (Exception $e) {
    test_result("Health Indicators Query", false, $e->getMessage());
}

// Test 6: Test patient alerts query
try {
    // Get a patient with alerts
    $stmt = $pdo->prepare("
        SELECT p.id
        FROM patients p
        JOIN alerts a ON p.id = a.patient_id
        LIMIT 1
    ");
    $stmt->execute();
    $patient_with_alerts = $stmt->fetch();

    if ($patient_with_alerts) {
        $patient_id = $patient_with_alerts['id'];

        $alerts_stmt = $pdo->prepare("
            SELECT * FROM alerts
            WHERE patient_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $alerts_stmt->execute([$patient_id]);
        $alerts = $alerts_stmt->fetchAll(PDO::FETCH_ASSOC);

        $has_alerts = !empty($alerts);
        test_result("Patient Alerts Query", $has_alerts, "Found " . count($alerts) . " alerts for patient $patient_id");
    } else {
        test_result("Patient Alerts Query", true, "No patients with alerts found (skipping detailed test)");
    }
} catch (Exception $e) {
    test_result("Patient Alerts Query", false, $e->getMessage());
}

// Test 7: Test patient messages query
try {
    // Get a patient with messages
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.id, u.id as user_id
        FROM patients p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
        WHERE m.id IS NOT NULL
        LIMIT 1
    ");
    $stmt->execute();
    $patient_with_messages = $stmt->fetch();

    if ($patient_with_messages) {
        $patient_id = $patient_with_messages['id'];
        $user_id = $patient_with_messages['user_id'];

        $messages_stmt = $pdo->prepare("
            SELECT m.*, u_sender.name as sender_name, u_receiver.name as receiver_name
            FROM messages m
            LEFT JOIN users u_sender ON m.sender_id = u_sender.id
            LEFT JOIN users u_receiver ON m.receiver_id = u_receiver.id
            WHERE m.sender_id = ? OR m.receiver_id = ?
            ORDER BY m.created_at DESC
            LIMIT 5
        ");
        $messages_stmt->execute([$user_id, $user_id]);
        $messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);

        $has_messages = !empty($messages);
        test_result("Patient Messages Query", $has_messages, "Found " . count($messages) . " messages for patient $patient_id");
    } else {
        test_result("Patient Messages Query", true, "No patients with messages found (skipping detailed test)");
    }
} catch (Exception $e) {
    test_result("Patient Messages Query", false, $e->getMessage());
}

// Test 8: Test invalid patient ID handling (simulated)
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.email, u.name, u.created_at as user_created_at
        FROM patients p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([999999]); // Non-existent ID
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $handles_invalid = ($result === false);
    test_result("Invalid Patient ID Handling", $handles_invalid, "Correctly returns no data for invalid ID");
} catch (Exception $e) {
    test_result("Invalid Patient ID Handling", false, $e->getMessage());
}

// Test 9: Test medical history data integrity
try {
    $stmt = $pdo->query("SELECT medical_history FROM patients WHERE medical_history IS NOT NULL AND medical_history != '' LIMIT 1");
    $result = $stmt->fetch();

    if ($result) {
        $has_content = strlen(trim($result['medical_history'])) > 0;
        test_result("Medical History Data", $has_content, "Medical history data exists and has content");
    } else {
        test_result("Medical History Data", true, "No medical history data found (skipping content check)");
    }
} catch (Exception $e) {
    test_result("Medical History Data", false, $e->getMessage());
}

// Test 10: Test patient-user relationship integrity
try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM patients p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE u.id IS NULL
    ");
    $orphaned_patients = $stmt->fetch()['count'];

    $relationship_intact = ($orphaned_patients == 0);
    test_result("Patient-User Relationship", $relationship_intact, $relationship_intact ? "All patients have valid user relationships" : "Found $orphaned_patients orphaned patients");
} catch (Exception $e) {
    test_result("Patient-User Relationship", false, $e->getMessage());
}

// Test 11: Test data display formatting (age validation)
try {
    $stmt = $pdo->query("SELECT age FROM patients WHERE age IS NOT NULL LIMIT 5");
    $ages = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $valid_ages = true;
    foreach ($ages as $age) {
        if (!is_numeric($age) || $age < 0 || $age > 150) {
            $valid_ages = false;
            break;
        }
    }

    test_result("Age Data Validation", $valid_ages, "All age values are within valid range");
} catch (Exception $e) {
    test_result("Age Data Validation", false, $e->getMessage());
}

// Test 12: Test gender data consistency
try {
    $stmt = $pdo->query("SELECT DISTINCT gender FROM patients WHERE gender IS NOT NULL");
    $genders = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $valid_genders = true;
    $allowed_genders = ['male', 'female', 'other'];

    foreach ($genders as $gender) {
        if (!in_array(strtolower($gender), $allowed_genders)) {
            $valid_genders = false;
            break;
        }
    }

    test_result("Gender Data Consistency", $valid_genders, "All gender values are valid: " . implode(', ', $genders));
} catch (Exception $e) {
    test_result("Gender Data Consistency", false, $e->getMessage());
}

echo "\n=== Test Summary ===\n";
echo "Tests Passed: $tests_passed / $tests_total\n";
$percentage = round(($tests_passed / $tests_total) * 100, 1);
echo "Success Rate: $percentage%\n";

if ($tests_passed === $tests_total) {
    echo "\n🎉 All Patient Details tests PASSED!\n";
} else {
    echo "\n⚠️  Some tests failed. Please review the implementation.\n";
}

echo "\n=== Testing Complete ===\n";
?>
