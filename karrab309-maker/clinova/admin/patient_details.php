<?php
// admin/patient_details.php - Detailed Patient View

require_once 'session_check.php';
requireAdminLogin();

// Get patient ID from URL
$patientId = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$patientId) {
    header("Location: patients.php");
    exit;
}

// Database configuration
$host = 'localhost';
$dbname = 'medical_api';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get patient details
$patientStmt = $pdo->prepare("
    SELECT p.*, u.username, u.email, u.name, u.created_at as user_created_at
    FROM patients p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$patientStmt->execute([$patientId]);
$patient = $patientStmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    header("Location: patients.php");
    exit;
}

// Get patient's operations
$operationsStmt = $pdo->prepare("
    SELECT o.*, u.name as doctor_name
    FROM operations o
    LEFT JOIN users u ON o.doctor_id = u.id
    WHERE o.patient_id = ?
    ORDER BY o.operation_date DESC
");
$operationsStmt->execute([$patientId]);
$operations = $operationsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get patient's health indicators (last 10)
$indicatorsStmt = $pdo->prepare("
    SELECT * FROM health_indicators
    WHERE patient_id = ?
    ORDER BY recorded_at DESC
    LIMIT 10
");
$indicatorsStmt->execute([$patientId]);
$indicators = $indicatorsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get patient's alerts
$alertsStmt = $pdo->prepare("
    SELECT * FROM alerts
    WHERE patient_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$alertsStmt->execute([$patientId]);
$alerts = $alertsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get patient's messages (last 5)
$messagesStmt = $pdo->prepare("
    SELECT m.*, u_sender.name as sender_name, u_receiver.name as receiver_name
    FROM messages m
    LEFT JOIN users u_sender ON m.sender_id = u_sender.id
    LEFT JOIN users u_receiver ON m.receiver_id = u_receiver.id
    WHERE m.sender_id = ? OR m.receiver_id = ?
    ORDER BY m.created_at DESC
    LIMIT 5
");
$messagesStmt->execute([$patient['user_id'], $patient['user_id']]);
$messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);

$adminInfo = getAdminInfo();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Details - Medical API Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 1.5rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .patient-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .patient-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .info-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .info-card h3 {
            color: #667eea;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .info-item {
            margin-bottom: 0.5rem;
        }

        .info-label {
            font-weight: 600;
            color: #555;
        }

        .info-value {
            color: #333;
        }

        .medical-history {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .data-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .data-section h3 {
            color: #667eea;
            margin-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .data-table tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background-color: #fed7d7;
            color: #c53030;
        }

        .status-acknowledged {
            background-color: #c6f6d5;
            color: #22543d;
        }

        .alert-type {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }

        .alert-type.temperature {
            background-color: #ffeaa7;
            color: #d63031;
        }

        .alert-type.pain {
            background-color: #fd79a8;
            color: #e84393;
        }

        .alert-type.dressing {
            background-color: #a29bfe;
            color: #6c5ce7;
        }

        .message-preview {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 1rem;
            padding: 0.5rem 1rem;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .patient-info {
                grid-template-columns: 1fr;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .data-table {
                font-size: 0.8rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>Medical API Admin</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php">Users</a>
            <a href="patients.php">Patients</a>
            <a href="alerts.php">Alerts</a>
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <a href="patients.php" class="back-btn">← Back to Patients</a>

        <div class="patient-header">
            <h2>Patient Details: <?php echo htmlspecialchars($patient['name']); ?></h2>

            <div class="patient-info">
                <div class="info-card">
                    <h3>Basic Information</h3>
                    <div class="info-item">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($patient['name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Username:</span>
                        <span class="info-value"><?php echo htmlspecialchars($patient['username']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($patient['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Age:</span>
                        <span class="info-value"><?php echo $patient['age']; ?> years</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Gender:</span>
                        <span class="info-value"><?php echo ucfirst($patient['gender']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Registered:</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($patient['user_created_at'])); ?></span>
                    </div>
                </div>

                <div class="info-card">
                    <h3>Medical History</h3>
                    <div class="medical-history">
                        <?php echo nl2br(htmlspecialchars($patient['medical_history'])); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div class="data-section">
                <h3>Recent Operations</h3>
                <?php if (empty($operations)): ?>
                    <p>No operations recorded.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($operations as $operation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($operation['operation_type']); ?></td>
                                    <td><?php echo htmlspecialchars($operation['doctor_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($operation['operation_date'])); ?></td>
                                    <td><?php echo htmlspecialchars(substr($operation['notes'], 0, 50)); ?><?php if (strlen($operation['notes']) > 50): ?>...<?php endif; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="data-section">
                <h3>Recent Health Indicators</h3>
                <?php if (empty($indicators)): ?>
                    <p>No health indicators recorded.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Recorded</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($indicators as $indicator): ?>
                                <tr>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $indicator['indicator_type'])); ?></td>
                                    <td><?php echo htmlspecialchars($indicator['value']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($indicator['recorded_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-grid">
            <div class="data-section">
                <h3>Recent Alerts</h3>
                <?php if (empty($alerts)): ?>
                    <p>No alerts recorded.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alerts as $alert): ?>
                                <tr>
                                    <td>
                                        <span class="alert-type <?php echo strtolower(str_replace(' ', '-', $alert['indicator_type'])); ?>">
                                            <?php echo ucfirst($alert['indicator_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($alert['value']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($alert['message'], 0, 50)); ?><?php if (strlen($alert['message']) > 50): ?>...<?php endif; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $alert['status']; ?>">
                                            <?php echo ucfirst($alert['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($alert['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="data-section">
                <h3>Recent Messages</h3>
                <?php if (empty($messages)): ?>
                    <p>No messages found.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>From/To</th>
                                <th>Message</th>
                                <th>Read</th>
                                <th>Sent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $message): ?>
                                <tr>
                                    <td>
                                        <?php if ($message['sender_id'] == $patient['user_id']): ?>
                                            To: <?php echo htmlspecialchars($message['receiver_name']); ?>
                                        <?php else: ?>
                                            From: <?php echo htmlspecialchars($message['sender_name']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="message-preview" title="<?php echo htmlspecialchars($message['content']); ?>">
                                            <?php echo htmlspecialchars(substr($message['content'], 0, 50)); ?><?php if (strlen($message['content']) > 50): ?>...<?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo $message['read_status'] ? 'Yes' : 'No'; ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
