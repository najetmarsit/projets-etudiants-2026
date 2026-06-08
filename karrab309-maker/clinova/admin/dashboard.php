<?php
// admin/dashboard.php - Admin Dashboard

require_once 'session_check.php';
requireAdminLogin();

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

// Get dashboard statistics
$stats = [];

// Total users
$userStmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $userStmt->fetch()['total'];

// Active users
$activeUserStmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE activate = 1");
$stats['active_users'] = $activeUserStmt->fetch()['total'];

// Total patients
$patientStmt = $pdo->query("SELECT COUNT(*) as total FROM patients");
$stats['total_patients'] = $patientStmt->fetch()['total'];

// Total operations
$operationStmt = $pdo->query("SELECT COUNT(*) as total FROM operations");
$stats['total_operations'] = $operationStmt->fetch()['total'];

// Active alerts
$alertStmt = $pdo->query("SELECT COUNT(*) as total FROM alerts WHERE status = 'active'");
$stats['active_alerts'] = $alertStmt->fetch()['active'];

// Recent health indicators (last 24 hours)
$recentIndicatorsStmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM health_indicators
    WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$recentIndicatorsStmt->execute();
$stats['recent_indicators'] = $recentIndicatorsStmt->fetch()['total'];

// Recent messages (last 7 days)
$recentMessagesStmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM messages
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$recentMessagesStmt->execute();
$stats['recent_messages'] = $recentMessagesStmt->fetch()['total'];

// Get recent alerts
$recentAlertsStmt = $pdo->prepare("
    SELECT a.id, a.indicator_type, a.value, a.message, a.created_at,
           p.id as patient_id, u.name as patient_name
    FROM alerts a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.status = 'active'
    ORDER BY a.created_at DESC
    LIMIT 5
");
$recentAlertsStmt->execute();
$recentAlerts = $recentAlertsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent user registrations
$recentUsersStmt = $pdo->prepare("
    SELECT id, username, email, name, role, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 5
");
$recentUsersStmt->execute();
$recentUsers = $recentUsersStmt->fetchAll(PDO::FETCH_ASSOC);

$adminInfo = getAdminInfo();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Medical API Admin</title>
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

        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .welcome-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .welcome-section h2 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #667eea;
        }

        .stat-card p {
            color: #666;
            font-size: 0.9rem;
        }

        .stat-card.alerts h3 {
            color: #dc3545;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .recent-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .recent-section h3 {
            color: #667eea;
            margin-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }

        .recent-item {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .recent-item:last-child {
            border-bottom: none;
        }

        .recent-item .info {
            flex: 1;
        }

        .recent-item .info strong {
            display: block;
            color: #333;
        }

        .recent-item .info small {
            color: #666;
        }

        .recent-item .time {
            color: #999;
            font-size: 0.8rem;
        }

        .alert-item {
            border-left: 4px solid #dc3545;
            background-color: #fff5f5;
        }

        .view-all {
            display: inline-block;
            margin-top: 1rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .nav-links {
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .welcome-section {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1> Clinova - Medical API Admin</h1>
        <div class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="users.php">Users</a>
            <a href="patients.php">Patients</a>
            <a href="alerts.php">Alerts</a>
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="welcome-section">
            <h2>Welcome back, <?php echo htmlspecialchars($adminInfo['username']); ?>!</h2>
            <p>Here's an overview of your medical API system.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['total_users']; ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['active_users']; ?></h3>
                <p>Active Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['total_patients']; ?></h3>
                <p>Total Patients</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['total_operations']; ?></h3>
                <p>Total Operations</p>
            </div>
            <div class="stat-card alerts">
                <h3><?php echo $stats['active_alerts']; ?></h3>
                <p>Active Alerts</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['recent_indicators']; ?></h3>
                <p>Indicators (24h)</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['recent_messages']; ?></h3>
                <p>Messages (7 days)</p>
            </div>
        </div>

        <div class="content-grid">
            <div class="recent-section">
                <h3>Recent Alerts</h3>
                <?php if (empty($recentAlerts)): ?>
                    <p>No active alerts at the moment.</p>
                <?php else: ?>
                    <?php foreach ($recentAlerts as $alert): ?>
                        <div class="recent-item alert-item">
                            <div class="info">
                                <strong><?php echo htmlspecialchars($alert['patient_name']); ?></strong>
                                <small><?php echo htmlspecialchars($alert['indicator_type']); ?>: <?php echo htmlspecialchars($alert['value']); ?> - <?php echo htmlspecialchars($alert['message']); ?></small>
                            </div>
                            <div class="time"><?php echo date('M d, H:i', strtotime($alert['created_at'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="alerts.php" class="view-all">Manage Alerts</a>
            </div>

            <div class="recent-section">
                <h3>Recent Registrations</h3>
                <?php if (empty($recentUsers)): ?>
                    <p>No recent registrations.</p>
                <?php else: ?>
                    <?php foreach ($recentUsers as $user): ?>
                        <div class="recent-item">
                            <div class="info">
                                <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                <small><?php echo htmlspecialchars($user['email']); ?> - <?php echo ucfirst($user['role']); ?></small>
                            </div>
                            <div class="time"><?php echo date('M d, H:i', strtotime($user['created_at'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="users.php" class="view-all">View All Users</a>
            </div>
        </div>
    </div>
</body>
</html>
