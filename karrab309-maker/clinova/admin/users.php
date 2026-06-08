<?php
// admin/users.php - User Management

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

// Handle user activation/deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = $_POST['user_id'] ?? null;
    $action = $_POST['action'];

    if ($userId && in_array($action, ['activate', 'deactivate'])) {
        $status = ($action === 'activate') ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE users SET activate = ? WHERE id = ?");
        $stmt->execute([$status, $userId]);

        $message = "User " . ($status ? 'activated' : 'deactivated') . " successfully!";
    }
}

// Get users with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total users count
$totalStmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $totalStmt->fetch()['total'];
$totalPages = ceil($totalUsers / $perPage);

// Get users for current page
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.email, u.name, u.role, u.activate, u.created_at,
           COUNT(p.id) as patient_count
    FROM users u
    LEFT JOIN patients p ON u.id = p.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$perPage, $offset]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$adminInfo = getAdminInfo();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Medical API Admin</title>
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
            max-width: 1200px;
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

        .users-table {
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

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .role-admin {
            background-color: #cce5ff;
            color: #004085;
        }

        .role-doctor {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .role-patient {
            background-color: #f8f9fa;
            color: #383d41;
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

        .btn-activate {
            background-color: #28a745;
            color: white;
        }

        .btn-activate:hover {
            background-color: #218838;
        }

        .btn-deactivate {
            background-color: #dc3545;
            color: white;
        }

        .btn-deactivate:hover {
            background-color: #c82333;
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
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <?php if (isset($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="stats-bar">
            <div>
                <strong>Total Users:</strong> <?php echo $totalUsers; ?> |
                <strong>Page:</strong> <?php echo $page; ?> of <?php echo $totalPages; ?>
            </div>
            <div>
                Welcome, <?php echo htmlspecialchars($adminInfo['username']); ?>!
            </div>
        </div>

        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Patients</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $user['activate'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $user['activate'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo $user['patient_count']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <?php if ($user['activate']): ?>
                                        <button type="submit" name="action" value="deactivate" class="action-btn btn-deactivate"
                                                onclick="return confirm('Are you sure you want to deactivate this user?')">
                                            Deactivate
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="activate" class="action-btn btn-activate"
                                                onclick="return confirm('Are you sure you want to activate this user?')">
                                            Activate
                                        </button>
                                    <?php endif; ?>
                                </form>
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
