<?php
// test_admin_login.php - Test script for admin login functionality

// Use SQLite for testing
$dsn = 'sqlite::memory:';
$username = '';
$password = '';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create admins table
    $pdo->exec("CREATE TABLE admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL
    )");

    // Insert test admin
    $testUsername = 'admin';
    $testPassword = 'securepassword123';
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
    $stmt->execute([$testUsername, $hashedPassword]);

    echo "Test database setup complete.\n";

} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}

// Function to verify admin credentials (copied from login.php)
function verifyAdminLogin($pdo, $admin_username, $admin_password) {
    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
    $stmt->execute([$admin_username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($admin_password, $admin['password_hash'])) {
        return $admin;
    }
    return false;
}

// Test cases
$tests = [
    ['username' => 'admin', 'password' => 'securepassword123', 'expected' => true, 'description' => 'Valid credentials'],
    ['username' => 'admin', 'password' => 'wrongpassword', 'expected' => false, 'description' => 'Wrong password'],
    ['username' => 'nonexistent', 'password' => 'securepassword123', 'expected' => false, 'description' => 'Non-existent user'],
    ['username' => '', 'password' => 'securepassword123', 'expected' => false, 'description' => 'Empty username'],
    ['username' => 'admin', 'password' => '', 'expected' => false, 'description' => 'Empty password'],
    ['username' => 'admin', 'password' => 'SecurePassword123', 'expected' => false, 'description' => 'Case-sensitive password'],
];

$passed = 0;
$total = count($tests);

foreach ($tests as $test) {
    $result = verifyAdminLogin($pdo, $test['username'], $test['password']);
    $success = ($result !== false) === $test['expected'];

    if ($success) {
        $passed++;
        echo "✓ PASS: {$test['description']}\n";
    } else {
        echo "✗ FAIL: {$test['description']}\n";
        echo "  Expected: " . ($test['expected'] ? 'login success' : 'login failure') . "\n";
        echo "  Got: " . ($result ? 'login success' : 'login failure') . "\n";
    }
}

echo "\nTest Results: {$passed}/{$total} tests passed\n";

if ($passed === $total) {
    echo "All tests passed! The admin login functionality is working correctly.\n";
} else {
    echo "Some tests failed. Please review the implementation.\n";
}

// Test SQL injection attempt
echo "\nTesting SQL injection resistance:\n";
$sqlInjectionUsername = "' OR '1'='1";
$sqlInjectionPassword = "' OR '1'='1";

$result = verifyAdminLogin($pdo, $sqlInjectionUsername, $sqlInjectionPassword);
if ($result === false) {
    echo "✓ PASS: SQL injection attempt blocked\n";
} else {
    echo "✗ FAIL: SQL injection vulnerability detected\n";
}

echo "\nTesting complete.\n";
?>
