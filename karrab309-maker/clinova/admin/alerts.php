<?php
// admin/alerts.php - Alert Management

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

// Handle alert acknowledgment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $alertId = $_POST['alert_id'] ?? null;
    $action = $_POST['action'];

    if ($alertId && $action === 'acknowledge') {
        $stmt = $pdo->prepare("UPDATE alerts SET status = 'acknowledged' WHERE id = ?");
        $stmt->execute([$alertId]);
        $message = "Alert acknowledged successfully!";
    }
}

// Get alerts with pagination and filtering
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build filter conditions
$whereConditions = [];
$params = [];

$statusFilter = $_GET['status'] ?? '';
if ($statusFilter) {
    $whereConditions[] = "a.status = ?";
    $params[] = $statusFilter;
}

$typeFilter = $_GET['type'] ?? '';
if ($typeFilter) {
    $whereConditions[] = "a.indicator_type = ?";
    $params[] = $typeFilter;
}

$whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total alerts count
$countStmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM alerts a
    $whereClause
");
$countStmt->execute($params);
$totalAlerts = $countStmt->fetch()['total'];
$totalPages = ceil($totalAlerts / $perPage);

// Get alerts for current page
$stmt = $pdo->prepare("
    SELECT a.id, a.indicator_type, a.value, a.message, a.status, a.created_at,
           p.id as patient_id, u.name as patient_name, u.email as patient_email
    FROM alerts a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    $whereClause
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get filter options
$statusOptions = ['active', 'acknowledged'];
$typeStmt = $pdo->query("SELECT DISTINCT indicator_type FROM alerts ORDER BY indicator_type");
$typeOptions = $typeStmt->fetchAll(PDO::FETCH_COLUMN);

$adminInfo = getAdminInfo();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alert Management - Medical API Admin</title>
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

        .filters-bar {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .filter-group {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .filter-group label {
            font-weight: 500;
        }

        .filter-group select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .filter-btn:hover {
            background: #5a67d8;
        }

        .alerts-table {
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

        .alert-row.active {
            background-color: #fff5f5;
            border-left: 4px solid #dc3545;
        }

        .alert-row.acknowledged {
            background-color: #f8fff8;
            border-left: 4px solid #28a745;
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

        .alert-message {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .action-btn {
            padding: 0.375rem 0.75rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
            margin-right: 0.5rem;
            transition: background-color 0.3s;
        }

        .btn-acknowledge {
            background-color: #28a745;
            color: white;
        }

        .btn-acknowledge:hover {
            background-color: #218838;
        }

        .btn-view {
            background-color: #007bff;
            color: white;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view:hover {
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

        .message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                justify-content: space-between;
            }

            table {
                font-size: 0.8rem;
            }

            th, td {
                padding: 0.5rem;
            }

            .alert-message {
                max-width: 150px;
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
            <a href="alerts.php" class="active">Alerts</a>
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <?php if (isset($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="filters-bar">
            <div class="filter-group">
                <label>Status:</label>
                <select name="status" onchange="applyFilters()">
                    <option value="">All Status</option>
                    <?php foreach ($statusOptions as $status): ?>
                        <option value="<?php echo $status; ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>>
                            <?php echo ucfirst($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Type:</label>
                <select name="type" onchange="applyFilters()">
                    <option value="">All Types</option>
                    <?php foreach ($typeOptions as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $typeFilter === $type ? 'selected' : ''; ?>>
                            <?php echo ucfirst($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <strong>Total Alerts:</strong> <?php echo $totalAlerts; ?> |
                <strong>Page:</strong> <?php echo $page; ?> of <?php echo $totalPages; ?>
            </div>
        </div>

        <div class="alerts-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alerts as $alert): ?>
                        <tr class="alert-row <?php echo $alert['status']; ?>">
                            <td><?php echo $alert['id']; ?></td>
                            <td>
                                <div class="patient-info">
                                    <div class="patient-name"><?php echo htmlspecialchars($alert['patient_name']); ?></div>
                                    <div class="patient-email"><?php echo htmlspecialchars($alert['patient_email']); ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="alert-type <?php echo strtolower(str_replace(' ', '-', $alert['indicator_type'])); ?>">
                                    <?php echo ucfirst($alert['indicator_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($alert['value']); ?></td>
                            <td>
                                <div class="alert-message" title="<?php echo htmlspecialchars($alert['message']); ?>">
                                    <?php echo htmlspecialchars(substr($alert['message'], 0, 50)); ?>
                                    <?php if (strlen($alert['message']) > 50): ?>...<?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $alert['status']; ?>">
                                    <?php echo ucfirst($alert['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($alert['created_at'])); ?></td>
                            <td>
                                <?php if ($alert['status'] === 'active'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                        <button type="submit" name="action" value="acknowledge" class="action-btn btn-acknowledge"
                                                onclick="return confirm('Are you sure you want to acknowledge this alert?')">
                                            Acknowledge
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="patient_details.php?id=<?php echo $alert['patient_id']; ?>" class="action-btn btn-view">View Patient</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($statusFilter); ?>&type=<?php echo urlencode($typeFilter); ?>" class="page-btn">Previous</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($statusFilter); ?>&type=<?php echo urlencode($typeFilter); ?>" class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($statusFilter); ?>&type=<?php echo urlencode($typeFilter); ?>" class="page-btn">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function applyFilters() {
            const status = document.querySelector('select[name="status"]').value;
            const type = document.querySelector('select[name="type"]').value;

            let url = '?';
            if (status) url += 'status=' + encodeURIComponent(status) + '&';
            if (type) url += 'type=' + encodeURIComponent(type) + '&';

            window.location.href = url.slice(0, -1);
        }
    </script>
</body>
</html>
