<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'client'){
    header("Location: ../index.php");
    exit();
}

$client_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM projects WHERE client_id = ? ORDER BY created_at DESC");
$stmt->execute([$client_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total     = count($projects);
$active    = 0;
$open      = 0;
$completed = 0;

foreach($projects as $p){
    if($p['status'] == 'active')    $active++;
    if($p['status'] == 'open')      $open++;
    if($p['status'] == 'completed') $completed++;
}

// Get recent activity
$stmt = $pdo->prepare("
    SELECT ml.*, u.name as changed_by_name, m.title as milestone_title, p.title as project_title
    FROM milestone_logs ml
    JOIN users u ON ml.changed_by = u.id
    JOIN milestones m ON ml.milestone_id = m.id
    JOIN projects p ON m.project_id = p.id
    WHERE p.client_id = ?
    ORDER BY ml.changed_at DESC
    LIMIT 5
");
$stmt->execute([$client_id]);
$recent_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Client Dashboard - Milestone Hub</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f4f0;
            color: #1a1a2e;
        }

        /* ── TOPBAR ── */
        .topbar {
            background: linear-gradient(135deg, #1a3533, #316461);
            padding: 0 28px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            box-shadow: 0 2px 20px rgba(0,0,0,0.15);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: rgba(255,255,255,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #fff;
        }

        .logo-text {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
        }

        .logo-sub {
            font-size: 10px;
            color: rgba(255,255,255,0.6);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-size: 13px;
            font-weight: 500;
            color: #fff;
        }

        .user-role {
            font-size: 11px;
            color: rgba(255,255,255,0.6);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #47928e, #95d5b2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
        }

        .btn-logout {
            padding: 7px 14px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-logout:hover {
            background: rgba(255,255,255,0.2);
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 220px;
            background: #fff;
            position: fixed;
            top: 60px;
            left: 0;
            bottom: 0;
            padding: 24px 0;
            overflow-y: auto;
            box-shadow: 2px 0 20px rgba(0,0,0,0.05);
        }

        .sidebar-section {
            padding: 0 16px;
            margin-bottom: 8px;
        }

        .sidebar-label {
            font-size: 10px;
            font-weight: 600;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 8px;
            margin-bottom: 6px;
            margin-top: 16px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 13px;
            color: #666;
            text-decoration: none;
            margin-bottom: 2px;
            transition: all 0.2s;
        }

        .nav-item:hover {
            background: #f0f9f7;
            color: #316461;
        }

        .nav-item.active {
            background: linear-gradient(135deg, #e8f5f5, #d0eeec);
            color: #316461;
            font-weight: 500;
        }

        .nav-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            background: #f5f5f5;
            flex-shrink: 0;
        }

        .nav-item.active .nav-icon {
            background: linear-gradient(135deg, #47928e, #316461);
            color: #fff;
        }

        /* ── MAIN ── */
        .main {
            margin-left: 220px;
            margin-top: 60px;
            padding: 28px;
            min-height: calc(100vh - 60px);
        }

        /* ── WELCOME BANNER ── */
        .welcome-banner {
            background: linear-gradient(135deg, #316461 0%, #47928e 50%, #74b9b5 100%);
            border-radius: 16px;
            padding: 28px 32px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            top: -60px;
            right: 100px;
        }

        .welcome-banner::after {
            content: '';
            position: absolute;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            bottom: -40px;
            right: 40px;
        }

        .welcome-text { position: relative; z-index: 1; }

        .welcome-text h2 {
            font-size: 22px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 6px;
        }

        .welcome-text p {
            font-size: 13px;
            color: rgba(255,255,255,0.8);
        }

        .welcome-btn {
            position: relative;
            z-index: 1;
            padding: 11px 24px;
            background: #fff;
            color: #316461;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        /* ── STATS ── */
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            top: -20px;
            right: -20px;
            opacity: 0.08;
        }

        .stat-card.total::before  { background: #316461; }
        .stat-card.open::before   { background: #185FA5; }
        .stat-card.active::before { background: #47928e; }
        .stat-card.done::before   { background: #5F5E5A; }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 12px;
        }

        .stat-card.total .stat-icon  { background: linear-gradient(135deg, #316461, #47928e); }
        .stat-card.open .stat-icon   { background: linear-gradient(135deg, #185FA5, #2980b9); }
        .stat-card.active .stat-icon { background: linear-gradient(135deg, #47928e, #74b9b5); }
        .stat-card.done .stat-icon   { background: linear-gradient(135deg, #5F5E5A, #888); }

        .stat-value {
            font-size: 30px;
            font-weight: 700;
            color: #1a3533;
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            color: #888;
        }

        /* ── GRID ── */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 20px;
        }

        /* ── SECTION HEADER ── */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            color: #1a3533;
        }

        .section-link {
            font-size: 12px;
            color: #47928e;
            text-decoration: none;
            font-weight: 500;
        }

        /* ── PROJECT CARDS ── */
        .project-card {
            background: #fff;
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            border-left: 4px solid transparent;
            transition: box-shadow 0.2s;
        }

        .project-card:hover {
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        .project-card.open     { border-left-color: #185FA5; }
        .project-card.active   { border-left-color: #47928e; }
        .project-card.completed{ border-left-color: #5F5E5A; }

        .project-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .project-title {
            font-size: 15px;
            font-weight: 600;
            color: #1a3533;
            margin-bottom: 3px;
        }

        .project-meta {
            font-size: 12px;
            color: #888;
        }

        .badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 20px;
            flex-shrink: 0;
        }

        .badge-open      { background: #EBF5FB; color: #185FA5; }
        .badge-active    { background: #e8f5f5; color: #316461; }
        .badge-completed { background: #F1EFE8; color: #5F5E5A; }

        /* Milestone progress bar */
        .milestone-progress {
            margin: 12px 0;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #888;
            margin-bottom: 6px;
        }

        .progress-bar {
            height: 6px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #47928e, #316461);
            border-radius: 10px;
            transition: width 0.3s;
        }

        .project-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 12px;
            border-top: 0.5px solid #f0eeee;
            margin-top: 8px;
        }

        .project-budget {
            font-size: 13px;
            font-weight: 600;
            color: #316461;
        }

        .btn-view {
            padding: 6px 14px;
            background: linear-gradient(135deg, #47928e, #316461);
            color: #fff;
            border-radius: 8px;
            font-size: 12px;
            text-decoration: none;
            font-weight: 500;
        }

        /* ── ACTIVITY LOG ── */
        .activity-card {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            height: fit-content;
        }

        .activity-item {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 0.5px solid #f5f5f5;
        }

        .activity-item:last-child { border-bottom: none; }

        .activity-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #47928e;
            margin-top: 4px;
            flex-shrink: 0;
        }

        .activity-text {
            font-size: 12px;
            color: #444;
            line-height: 1.5;
        }

        .activity-time {
            font-size: 11px;
            color: #aaa;
            margin-top: 3px;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .empty-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a3533;
            margin-bottom: 8px;
        }

        .empty-sub {
            font-size: 13px;
            color: #888;
            margin-bottom: 20px;
        }

        .btn-create {
            display: inline-block;
            padding: 10px 24px;
            background: linear-gradient(135deg, #47928e, #316461);
            color: #fff;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-left">
        <div class="logo-icon">⊞</div>
        <div>
            <div class="logo-text">Milestone Hub</div>
            <div class="logo-sub">Project Management System</div>
        </div>
    </div>
    <div class="topbar-right">
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
            <div class="user-role">Client</div>
        </div>
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-section">
        <div class="sidebar-label">Main Menu</div>

        <a href="dashboard.php" class="nav-item active">
            <div class="nav-icon">🏠</div>
            Dashboard
        </a>
        <a href="create_project.php" class="nav-item">
            <div class="nav-icon">➕</div>
            Create project
        </a>
        <a href="view_project.php" class="nav-item">
            <div class="nav-icon">📁</div>
            My projects
        </a>
        <a href="approve_milestone.php" class="nav-item">
            <div class="nav-icon">✅</div>
            Approve milestones
        </a>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-label">Account</div>
        <a href="../logout.php" class="nav-item">
            <div class="nav-icon">🚪</div>
            Logout
        </a>
    </div>
</div>

<!-- MAIN -->
<div class="main">

    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="welcome-text">
            <h2>Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>! 👋</h2>
            <p>Here is an overview of your projects and milestones.</p>
        </div>
        <a href="create_project.php" class="welcome-btn">+ Create New Project</a>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card total">
            <div class="stat-icon" style="color:#fff">📁</div>
            <div class="stat-value"><?= $total ?></div>
            <div class="stat-label">Total Projects</div>
        </div>
        <div class="stat-card open">
            <div class="stat-icon" style="color:#fff">🔓</div>
            <div class="stat-value"><?= $open ?></div>
            <div class="stat-label">Open Projects</div>
        </div>
        <div class="stat-card active">
            <div class="stat-icon" style="color:#fff">⚡</div>
            <div class="stat-value"><?= $active ?></div>
            <div class="stat-label">Active Projects</div>
        </div>
        <div class="stat-card done">
            <div class="stat-icon" style="color:#fff">🏆</div>
            <div class="stat-value"><?= $completed ?></div>
            <div class="stat-label">Completed</div>
        </div>
    </div>

    <!-- Projects + Activity -->
    <div class="grid-2">

        <!-- Projects -->
        <div>
            <div class="section-header">
                <div class="section-title">My Projects</div>
                <a href="create_project.php" class="section-link">+ New project</a>
            </div>

            <?php if(count($projects) == 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <div class="empty-title">No projects yet</div>
                    <div class="empty-sub">Create your first project and start working with freelancers</div>
                    <a href="create_project.php" class="btn-create">+ Create Project</a>
                </div>
            <?php else: ?>
                <?php foreach($projects as $project): ?>
                    <?php
                    $ms = $pdo->prepare("SELECT COUNT(*) as total, SUM(status='approved') as done FROM milestones WHERE project_id = ?");
                    $ms->execute([$project['id']]);
                    $ms_data = $ms->fetch(PDO::FETCH_ASSOC);
                    $ms_total = $ms_data['total'] ?? 3;
                    $ms_done  = $ms_data['done']  ?? 0;
                    $progress = $ms_total > 0 ? ($ms_done / $ms_total) * 100 : 0;

                    $pr = $pdo->prepare("SELECT COUNT(*) as total FROM proposals WHERE project_id = ? AND status = 'pending'");
                    $pr->execute([$project['id']]);
                    $pr_count = $pr->fetch(PDO::FETCH_ASSOC)['total'];
                    ?>
                    <div class="project-card <?= $project['status'] ?>">
                        <div class="project-card-head">
                            <div>
                                <div class="project-title"><?= htmlspecialchars($project['title']) ?></div>
                                <div class="project-meta">
                                    Created: <?= date('d M Y', strtotime($project['created_at'])) ?>
                                    <?php if($pr_count > 0): ?>
                                        &nbsp;·&nbsp; <span style="color:#92400E;font-weight:500">⚠ <?= $pr_count ?> pending proposal(s)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="badge badge-<?= $project['status'] ?>"><?= ucfirst($project['status']) ?></span>
                        </div>

                        <!-- Progress bar -->
                        <div class="milestone-progress">
                            <div class="progress-label">
                                <span>Milestone Progress</span>
                                <span><?= $ms_done ?> / <?= $ms_total ?> approved</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $progress ?>%"></div>
                            </div>
                        </div>

                        <div class="project-card-footer">
                            <div class="project-budget">Rs. <?= number_format($project['total_budget'], 2) ?></div>
                            <a href="view_project.php?id=<?= $project['id'] ?>" class="btn-view">View project →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Activity Log -->
        <div>
            <div class="section-header">
                <div class="section-title">Recent Activity</div>
            </div>
            <div class="activity-card">
                <?php if(count($recent_logs) == 0): ?>
                    <div style="text-align:center;padding:30px;color:#aaa;font-size:13px">
                        No activity yet
                    </div>
                <?php else: ?>
                    <?php foreach($recent_logs as $log): ?>
                        <div class="activity-item">
                            <div class="activity-dot"></div>
                            <div>
                                <div class="activity-text">
                                    <strong><?= htmlspecialchars($log['changed_by_name']) ?></strong>
                                    updated <strong><?= htmlspecialchars($log['milestone_title']) ?></strong>
                                    to <span style="color:#316461;font-weight:500"><?= ucfirst(str_replace('_', ' ', $log['new_status'])) ?></span>
                                </div>
                                <div class="activity-time"><?= date('d M Y h:i A', strtotime($log['changed_at'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>