<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin'){
    header("Location: ../index.php");
    exit();
}

// Handle delete user
if(isset($_GET['delete_user'])){
    $user_id = $_GET['delete_user'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$user_id]);
    header("Location: dashboard.php?success=User deleted successfully");
    exit();
}

// Handle delete project
if(isset($_GET['delete_project'])){
    $project_id = $_GET['delete_project'];
    $stmt = $pdo->prepare("DELETE FROM milestone_logs WHERE milestone_id IN (SELECT id FROM milestones WHERE project_id = ?)");
    $stmt->execute([$project_id]);
    $stmt = $pdo->prepare("DELETE FROM milestones WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $stmt = $pdo->prepare("DELETE FROM proposals WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    header("Location: dashboard.php?success=Project deleted successfully");
    exit();
}

// Get stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
$total_clients     = $pdo->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();
$total_freelancers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='freelancer'")->fetchColumn();
$total_projects    = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$active_projects   = $pdo->query("SELECT COUNT(*) FROM projects WHERE status='active'")->fetchColumn();
$completed         = $pdo->query("SELECT COUNT(*) FROM projects WHERE status='completed'")->fetchColumn();
$open_projects     = $pdo->query("SELECT COUNT(*) FROM projects WHERE status='open'")->fetchColumn();
$total_proposals   = $pdo->query("SELECT COUNT(*) FROM proposals")->fetchColumn();

// Get all users
$stmt = $pdo->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all projects
$stmt = $pdo->query("
    SELECT p.*, 
    u1.name as client_name, 
    u2.name as freelancer_name
    FROM projects p
    JOIN users u1 ON p.client_id = u1.id
    LEFT JOIN users u2 ON p.freelancer_id = u2.id
    ORDER BY p.created_at DESC
");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent activity logs
$stmt = $pdo->query("
    SELECT ml.*, u.name as changed_by_name, m.title as milestone_title, p.title as project_title
    FROM milestone_logs ml
    JOIN users u ON ml.changed_by = u.id
    JOIN milestones m ON ml.milestone_id = m.id
    JOIN projects p ON m.project_id = p.id
    ORDER BY ml.changed_at DESC
    LIMIT 10
");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Milestone Hub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat {
            background: #fff;
            border-radius: 12px;
            padding: 18px;
            border: 0.5px solid #e8e6e6;
        }

        .stat-label {
            font-size: 12px;
            color: #888780;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 600;
            color: #47928e;
        }

        .tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 20px;
            background: #f0f0f0;
            padding: 4px;
            border-radius: 10px;
            width: fit-content;
        }

        .tab {
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #888;
            cursor: pointer;
            border: none;
            background: transparent;
            font-family: inherit;
        }

        .tab.active {
            background: #fff;
            color: #316461;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            border: 0.5px solid #e8e6e6;
        }

        .data-table thead tr {
            background: #316461;
            color: #fff;
        }

        .data-table thead th {
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 500;
            text-align: left;
        }

        .data-table tbody tr {
            border-bottom: 0.5px solid #f0eeee;
        }

        .data-table tbody tr:hover {
            background: #f8fffe;
        }

        .data-table tbody tr:last-child {
            border-bottom: none;
        }

        .data-table tbody td {
            padding: 12px 16px;
            font-size: 13px;
            color: #333;
        }

        .badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 500;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .badge-admin      { background: #e8f5f5; color: #316461; }
        .badge-client     { background: #E6F1FB; color: #185FA5; }
        .badge-freelancer { background: #FEF3C7; color: #92400E; }
        .badge-open       { background: #e8f5f5; color: #316461; }
        .badge-active     { background: #E1F5EE; color: #0F6E56; }
        .badge-completed  { background: #F1EFE8; color: #5F5E5A; }

        .btn-delete {
            padding: 5px 12px;
            background: #FEE2E2;
            color: #B91C1C;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            font-family: inherit;
            text-decoration: none;
        }

        .btn-delete:hover {
            background: #FECACA;
        }

        .alert-success {
            background: #e8f5f5;
            color: #316461;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 500;
            color: #1a3533;
            margin-bottom: 14px;
        }

        .log-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 0.5px solid #f0eeee;
        }

        .log-item:last-child { border-bottom: none; }

        .log-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #47928e;
            margin-top: 4px;
            flex-shrink: 0;
        }

        .log-text {
            font-size: 13px;
            color: #333;
            line-height: 1.5;
        }

        .log-time {
            font-size: 11px;
            color: #aaa;
            margin-top: 2px;
        }

        .log-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px 20px;
            border: 0.5px solid #e8e6e6;
        }

        .empty-row td {
            text-align: center;
            padding: 40px;
            color: #888780;
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-left">
        <span class="logo">Milestone Hub</span>
        <span style="color:#6b9e9b;font-size:12px;margin-left:8px">Admin Panel</span>
    </div>
    <div class="topbar-right">
        <span class="username">👤 <?= $_SESSION['user_name'] ?> (Aaesha)</span>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="layout">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <a href="dashboard.php" class="nav-item active">Dashboard</a>
        <a href="dashboard.php?tab=users" class="nav-item">Manage Users</a>
        <a href="dashboard.php?tab=projects" class="nav-item">All Projects</a>
        <a href="dashboard.php?tab=logs" class="nav-item">Activity Logs</a>
        <a href="../landing.php" class="nav-item">← Home</a>
    </div>

    <!-- MAIN -->
    <div class="main">
        <h2 class="page-title">Admin Dashboard</h2>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="stats">
            <div class="stat">
                <div class="stat-label">👥 Total Users</div>
                <div class="stat-value"><?= $total_users ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">♟ Clients</div>
                <div class="stat-value"><?= $total_clients ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">⊛Freelancers</div>
                <div class="stat-value"><?= $total_freelancers ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">📁 Total Projects</div>
                <div class="stat-value"><?= $total_projects ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">🟢 Open Projects</div>
                <div class="stat-value"><?= $open_projects ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">⚡ Active Projects</div>
                <div class="stat-value"><?= $active_projects ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">✅ Completed</div>
                <div class="stat-value"><?= $completed ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">📋 Total Proposals</div>
                <div class="stat-value"><?= $total_proposals ?></div>
            </div>
        </div>

        <!-- TABS -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('users', this)">👥 Users</button>
            <button class="tab" onclick="showTab('projects', this)">📁 Projects</button>
            <button class="tab" onclick="showTab('logs', this)">📊 Activity Logs</button>
        </div>

        <!-- USERS TAB -->
        <div class="tab-content active" id="tab-users">
            <p class="section-title">All Users (<?= count($users) ?>)</p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($users) == 0): ?>
                        <tr class="empty-row"><td colspan="6">No users found</td></tr>
                    <?php else: ?>
                        <?php foreach($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><strong><?= htmlspecialchars($user['name']) ?></strong></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><span class="badge badge-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span></td>
                                <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <?php if($user['role'] != 'admin'): ?>
                                        <a href="dashboard.php?delete_user=<?= $user['id'] ?>"
                                           class="btn-delete"
                                           onclick="return confirm('Are you sure you want to delete this user?')">
                                           Delete
                                        </a>
                                    <?php else: ?>
                                        <span style="font-size:12px;color:#aaa">Protected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PROJECTS TAB -->
        <div class="tab-content" id="tab-projects">
            <p class="section-title">All Projects (<?= count($projects) ?>)</p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Project Title</th>
                        <th>Client</th>
                        <th>Freelancer</th>
                        <th>Budget</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($projects) == 0): ?>
                        <tr class="empty-row"><td colspan="8">No projects found</td></tr>
                    <?php else: ?>
                        <?php foreach($projects as $project): ?>
                            <tr>
                                <td><?= $project['id'] ?></td>
                                <td><strong><?= htmlspecialchars($project['title']) ?></strong></td>
                                <td><?= htmlspecialchars($project['client_name']) ?></td>
                                <td><?= $project['freelancer_name'] ? htmlspecialchars($project['freelancer_name']) : '<span style="color:#aaa">Not assigned</span>' ?></td>
                                <td>Rs. <?= number_format($project['total_budget'], 2) ?></td>
                                <td><span class="badge badge-<?= $project['status'] ?>"><?= ucfirst($project['status']) ?></span></td>
                                <td><?= date('d M Y', strtotime($project['created_at'])) ?></td>
                                <td>
                                    <a href="dashboard.php?delete_project=<?= $project['id'] ?>"
                                       class="btn-delete"
                                       onclick="return confirm('Delete this project and all its milestones?')">
                                       Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- LOGS TAB -->
        <div class="tab-content" id="tab-logs">
            <p class="section-title">Recent Activity Logs</p>
            <div class="log-card">
                <?php if(count($logs) == 0): ?>
                    <p style="text-align:center;color:#888;padding:20px">No activity logs yet.</p>
                <?php else: ?>
                    <?php foreach($logs as $log): ?>
                        <div class="log-item">
                            <div class="log-dot"></div>
                            <div>
                                <div class="log-text">
                                    <strong><?= htmlspecialchars($log['changed_by_name']) ?></strong>
                                    changed
                                    <strong><?= htmlspecialchars($log['milestone_title']) ?></strong>
                                    from
                                    <span style="color:#B91C1C"><?= ucfirst(str_replace('_', ' ', $log['old_status'])) ?></span>
                                    to
                                    <span style="color:#316461"><?= ucfirst(str_replace('_', ' ', $log['new_status'])) ?></span>
                                    in project
                                    <strong><?= htmlspecialchars($log['project_title']) ?></strong>
                                </div>
                                <div class="log-time"><?= date('d M Y h:i A', strtotime($log['changed_at'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
function showTab(name, el) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    // Remove active from all tabs
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    // Show selected tab
    document.getElementById('tab-' + name).classList.add('active');
    el.classList.add('active');
}

// Auto open tab from URL
<?php if(isset($_GET['tab'])): ?>
    const tabName = '<?= $_GET['tab'] ?>';
    const tabEl = document.querySelector(`.tab[onclick*="${tabName}"]`);
    if(tabEl) showTab(tabName, tabEl);
<?php endif; ?>
</script>

</body>
</html>