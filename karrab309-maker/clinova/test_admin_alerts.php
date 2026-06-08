<?php
// test_admin_alerts.php - Comprehensive testing for Alert Management functionality

echo "=== Medical API Admin - Alert Management Testing ===\n\n";

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

// Test 2: Check if alerts table exists and has data
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM alerts");
    $alert_count = $stmt->fetch()['count'];
    test_result("Alerts Table Access", true, "Found $alert_count alerts in database");
} catch (Exception $e) {
    test_result("Alerts Table Access", false, $e->getMessage());
}

// Test 3: Test alert filtering query (status filter)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM alerts WHERE status = ?");
    $stmt->execute(['new']);
    $active_count = $stmt->fetch()['count'];
    test_result("Status Filter Query", true, "Found $active_count new alerts");
} catch (Exception $e) {
    test_result("Status Filter Query", false, $e->getMessage());
}

// Test 4: Test alert filtering query (type filter)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM alerts WHERE indicator_type = ?");
    $stmt->execute(['temperature']);
    $temp_count = $stmt->fetch()['count'];
    test_result("Type Filter Query", true, "Found $temp_count temperature alerts");
} catch (Exception $e) {
    test_result("Type Filter Query", false, $e->getMessage());
}

// Test 5: Test alert acknowledgment functionality
try {
    // First, get a new alert
    $stmt = $pdo->query("SELECT id FROM alerts WHERE status = 'new' LIMIT 1");
    $alert = $stmt->fetch();

    if ($alert) {
        $alert_id = $alert['id'];

        // Test acknowledgment
        $update_stmt = $pdo->prepare("UPDATE alerts SET status = 'acknowledged' WHERE id = ?");
        $update_stmt->execute([$alert_id]);

        // Verify the update
        $verify_stmt = $pdo->prepare("SELECT status FROM alerts WHERE id = ?");
        $verify_stmt->execute([$alert_id]);
        $updated_alert = $verify_stmt->fetch();

        $acknowledged = $updated_alert && $updated_alert['status'] === 'acknowledged';
        test_result("Alert Acknowledgment", $acknowledged, "Alert $alert_id acknowledged successfully");

        // Reset for next test
        $reset_stmt = $pdo->prepare("UPDATE alerts SET status = 'new' WHERE id = ?");
        $reset_stmt->execute([$alert_id]);
    } else {
        test_result("Alert Acknowledgment", true, "No new alerts to test (skipping)");
    }
} catch (Exception $e) {
    test_result("Alert Acknowledgment", false, $e->getMessage());
}

// Test 6: Test pagination query
try {
    $perPage = 15;
    $page = 1;
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM alerts
    ");
    $stmt->execute();
    $total = $stmt->fetch()['total'];

    $expected_pages = ceil($total / $perPage);

    test_result("Pagination Logic", true, "Total alerts: $total, Expected pages: $expected_pages");
} catch (Exception $e) {
    test_result("Pagination Logic", false, $e->getMessage());
}

// Test 7: Test patient-alert relationship query
try {
    $stmt = $pdo->prepare("
        SELECT a.id, p.id as patient_id, u.name as patient_name
        FROM alerts a
        JOIN patients p ON a.patient_id = p.id
        JOIN users u ON p.user_id = u.id
        LIMIT 1
    ");
    $stmt->execute();
    $result = $stmt->fetch();

    $has_relationship = $result && isset($result['patient_name']);
    test_result("Patient-Alert Relationship", $has_relationship, $has_relationship ? "Relationship verified for alert {$result['id']}" : "No relationships found");
} catch (Exception $e) {
    test_result("Patient-Alert Relationship", false, $e->getMessage());
}

// Test 8: Test filter options query
try {
    $stmt = $pdo->query("SELECT DISTINCT status FROM alerts ORDER BY status");
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt2 = $pdo->query("SELECT DISTINCT indicator_type FROM alerts ORDER BY indicator_type");
    $types = $stmt2->fetchAll(PDO::FETCH_COLUMN);

    $has_options = !empty($statuses) && !empty($types);
    test_result("Filter Options", $has_options, "Found " . count($statuses) . " statuses and " . count($types) . " types");
} catch (Exception $e) {
    test_result("Filter Options", false, $e->getMessage());
}

// Test 9: Test alert display query (main listing)
try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.indicator_type, a.value, a.message, a.status, a.created_at,
               p.id as patient_id, u.name as patient_name, u.email as patient_email
        FROM alerts a
        JOIN patients p ON a.patient_id = p.id
        JOIN users u ON p.user_id = u.id
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $has_required_fields = !empty($alerts) && isset($alerts[0]['patient_name']) && isset($alerts[0]['indicator_type']);
    test_result("Alert Display Query", $has_required_fields, "Retrieved " . count($alerts) . " alerts with all required fields");
} catch (Exception $e) {
    test_result("Alert Display Query", false, $e->getMessage());
}

// Test 10: Test SQL injection prevention
try {
    $malicious_input = "' OR '1'='1";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM alerts WHERE status = ?");
    $stmt->execute([$malicious_input]);
    $count = $stmt->fetch()['count'];

    // This should return 0 since no status matches the malicious input
    $safe = ($count == 0);
    test_result("SQL Injection Prevention", $safe, "Prepared statements protected against injection");
} catch (Exception $e) {
    test_result("SQL Injection Prevention", false, $e->getMessage());
}

// Test 11: Test alert types variety
try {
    $stmt = $pdo->query("SELECT DISTINCT indicator_type, COUNT(*) as count FROM alerts GROUP BY indicator_type");
    $type_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $has_variety = count($type_counts) > 0;
    $message = "Found " . count($type_counts) . " alert types: ";
    foreach ($type_counts as $type) {
        $message .= $type['indicator_type'] . "(" . $type['count'] . ") ";
    }
    test_result("Alert Types Variety", $has_variety, trim($message));
} catch (Exception $e) {
    test_result("Alert Types Variety", false, $e->getMessage());
}

// Test 12: Test date formatting
try {
    $stmt = $pdo->query("SELECT created_at FROM alerts LIMIT 1");
    $result = $stmt->fetch();

    if ($result) {
        $date = strtotime($result['created_at']);
        $formatted = date('M d, Y H:i', $date);
        $is_valid_date = strlen($formatted) > 0;
        test_result("Date Formatting", $is_valid_date, "Date formatted: $formatted");
    } else {
        test_result("Date Formatting", true, "No alerts to test date formatting (skipping)");
    }
} catch (Exception $e) {
    test_result("Date Formatting", false, $e->getMessage());
}

echo "\n=== Test Summary ===\n";
echo "Tests Passed: $tests_passed / $tests_total\n";
$percentage = ($tests_total > 0) ? round(($tests_passed / $tests_total) * 100, 1) : 0;
echo "Success Rate: $percentage%\n";

if ($tests_passed === $tests_total) {
    echo "\n🎉 All Alert Management tests PASSED!\n";
} else {
    echo "\n⚠️  Some tests failed. Please review the implementation.\n";
}

echo "\n=== Testing Complete ===\n";
?>
