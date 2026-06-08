<?php
// admin/patients.php - Patient Management

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

// Get patients with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total patients count
$totalStmt = $pdo->query("SELECT COUNT(*) as total FROM patients");
$totalPatients = $totalStmt->fetch()['total'];
$totalPages = ceil($totalPatients / $perPage);

// Get patients for current page
$stmt = $pdo->prepare("
    SELECT p.id, p.age, p.gender, p.medical_history, p.created_at,
           u.username, u.email, u.name,
           COUNT(o.id) as operations_count,
           COUNT(hi.id) as indicators_count,
           COUNT(a.id) as alerts_count
    FROM patients p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN operations o ON p.id = o.patient_id
    LEFT JOIN health_indicators hi ON p.id = hi.patient_id
    LEFT JOIN alerts a ON p.id = a.patient_id AND a.status = 'active'
    GROUP BY p.id, u.username, u.email, u.name
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$perPage, $offset]);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$adminInfo = getAdminInfo();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management - Medical API Admin</title>
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
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .stats-bar {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .patients-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .patient-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .patient-name {
            font-weight: 600;
            color: #333;
        }

        .patient-email {
            color: #666;
            font-size: 0.9rem;
        }

        .gender-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }

        .gender-male {
            background-color: #cce5ff;
            color: #004085;
        }

        .gender-female {
            background-color: #fce4ec;
            color: #ad1457;
        }

        .gender-other {
            background-color: #f8f9fa;
            color: #383d41;
        }

        .stats-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .stat-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 500;
            background-color: #e9ecef;
            color: #495057;
        }

        .stat-badge.alerts {
            background-color: #f8d7da;
            color: #721c24;
        }

        .medical-history {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .view-btn {
            padding: 0.375rem 0.75rem;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .view-btn:hover {
            background-color: #0056b3;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }

        .page-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
            background: white;
            color: #007bff;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .page-btn:hover {
            background: #007bff;
            color: white;
        }

        .page-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .stats-bar {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            table {
                font-size: 0.8rem;
            }

            th, td {
                padding: 0.5rem;
            }

            .stats-badges {
                flex-direction: column;
                align-items: flex-start;
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
            <a href="patients.php" class="active">Patients</a>
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="stats-bar">
            <div>
                <strong>Total Patients:</strong> <?php echo $totalPatients; ?> |
                <strong>Page:</strong> <?php echo $page; ?> of <?php echo $totalPages; ?>
            </div>
            <div>
                Welcome, <?php echo htmlspecialchars($adminInfo['username']); ?>!
            </div>
        </div>

        <div class="patients-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient Info</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Medical History</th>
                        <th>Statistics</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td><?php echo $patient['id']; ?></td>
                            <td>
                                <div class="patient-info">
                                    <div class="patient-name"><?php echo htmlspecialchars($patient['name']); ?></div>
                                    <div class="patient-email"><?php echo htmlspecialchars($patient['email']); ?></div>
                                </div>
                            </td>
                            <td><?php echo $patient['age']; ?> years</td>
                            <td>
                                <span class="gender-badge gender-<?php echo strtolower($patient['gender']); ?>">
                                    <?php echo ucfirst($patient['gender']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="medical-history" title="<?php echo htmlspecialchars($patient['medical_history']); ?>">
                                    <?php echo htmlspecialchars(substr($patient['medical_history'], 0, 50)); ?>
                                    <?php if (strlen($patient['medical_history']) > 50): ?>...<?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="stats-badges">
                                    <span class="stat-badge">Operations: <?php echo $patient['operations_count']; ?></span>
                                    <span class="stat-badge">Indicators: <?php echo $patient['indicators_count']; ?></span>
                                    <?php if ($patient['alerts_count'] > 0): ?>
                                        <span class="stat-badge alerts">Alerts: <?php echo $patient['alerts_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($patient['created_at'])); ?></td>
                            <td>
                                <a href="patient_details.php?id=<?php echo $patient['id']; ?>" class="view-btn">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="page-btn">Previous</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="page-btn">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
