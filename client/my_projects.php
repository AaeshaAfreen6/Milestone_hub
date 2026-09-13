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

// Handle filter
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$query = "SELECT * FROM projects WHERE client_id = ?";
$params = [$client_id];

if($filter != 'all'){
    $query .= " AND status = ?";
    $params[] = $filter;
}

if(!empty($search)){
    $query .= " AND title LIKE ?";
    $params[] = "%$search%";
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count stats
$all_stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM projects WHERE client_id = ? GROUP BY status");
$all_stmt->execute([$client_id]);
$counts = ['all' => 0, 'open' => 0, 'active' => 0, 'completed' => 0];
while($row = $all_stmt->fetch(PDO::FETCH_ASSOC)){
    $counts[$row['status']] = $row['count'];
    $counts['all'] += $row['count'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Projects - Milestone Hub</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f4f0;
            color: #1a1a2e;
        }

        /* TOPBAR */
        .topbar {
            background: linear-gradient(135deg, #1a3533, #316461);
            padding: 0 28px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            box-shadow: 0 2px 20px rgba(0,0,0,0.15);
        }

        .topbar-left { display: flex; align-items: center; gap: 10px; }
        .logo-icon {
            width: 34px; height: 34px; border-radius: 8px;
            background: rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; color: #fff;
        }
        .logo-text { font-size: 16px; font-weight: 600; color: #fff; }
        .logo-sub  { font-size: 10px; color: rgba(255,255,255,0.6); }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .user-name { font-size: 13px; font-weight: 500; color: #fff; }
        .user-role { font-size: 11px; color: rgba(255,255,255,0.6); }
        .user-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, #47928e, #95d5b2);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 600; color: #fff;
        }
        .btn-logout {
            padding: 7px 14px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px; color: #fff;
            font-size: 12px; text-decoration: none;
        }

        /* SIDEBAR */
        .sidebar {
            width: 220px; background: #fff;
            position: fixed; top: 60px; left: 0; bottom: 0;
            padding: 24px 0; overflow-y: auto;
            box-shadow: 2px 0 20px rgba(0,0,0,0.05);
        }
        .sidebar-section { padding: 0 16px; margin-bottom: 8px; }
        .sidebar-label {
            font-size: 10px; font-weight: 600; color: #aaa;
            text-transform: uppercase; letter-spacing: 1px;
            padding: 0 8px; margin-bottom: 6px; margin-top: 16px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 10px;
            font-size: 13px; color: #666; text-decoration: none;
            margin-bottom: 2px; transition: all 0.2s;
        }
        .nav-item:hover { background: #f0f9f7; color: #316461; }
        .nav-item.active {
            background: linear-gradient(135deg, #e8f5f5, #d0eeec);
            color: #316461; font-weight: 500;
        }
        .nav-icon {
            width: 32px; height: 32px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; background: #f5f5f5; flex-shrink: 0;
        }
        .nav-item.active .nav-icon {
            background: linear-gradient(135deg, #47928e, #316461);
            color: #fff;
        }

        /* MAIN */
        .main {
            margin-left: 220px; margin-top: 60px;
            padding: 28px; min-height: calc(100vh - 60px);
        }

        /* PAGE HEADER */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .page-title { font-size: 22px; font-weight: 700; color: #1a3533; }
        .page-sub   { font-size: 13px; color: #888; margin-top: 3px; }

        .btn-create {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #47928e, #316461);
            color: #fff;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(49,100,97,0.3);
        }

        /* FILTER TABS */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            border: 1.5px solid #e0e0e0;
            color: #666;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-tab:hover { border-color: #47928e; color: #47928e; }

        .filter-tab.active {
            background: linear-gradient(135deg, #47928e, #316461);
            color: #fff;
            border-color: transparent;
        }

        .filter-count {
            display: inline-block;
            background: rgba(255,255,255,0.25);
            border-radius: 10px;
            padding: 1px 7px;
            font-size: 11px;
            margin-left: 4px;
        }

        .filter-tab:not(.active) .filter-count {
            background: #f0f0f0;
            color: #888;
        }

        /* SEARCH BAR */
        .search-bar {
            background: #fff;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .search-bar input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 14px;
            color: #1a1a2e;
            font-family: inherit;
            background: transparent;
        }

        .search-bar button {
            padding: 8px 18px;
            background: linear-gradient(135deg, #47928e, #316461);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 13px;
            cursor: pointer;
            font-family: inherit;
        }

        /* PROJECT CARDS */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 18px;
        }

        .project-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            border-top: 4px solid transparent;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }

        .project-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .project-card.open      { border-top-color: #185FA5; }
        .project-card.active    { border-top-color: #47928e; }
        .project-card.completed { border-top-color: #5F5E5A; }

        .card-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .project-title {
            font-size: 15px;
            font-weight: 600;
            color: #1a3533;
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .project-desc {
            font-size: 12px;
            color: #888;
            line-height: 1.5;
            margin-bottom: 14px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 20px;
            flex-shrink: 0;
        }

        .badge-open      { background: #EBF5FB; color: #185FA5; }
        .badge-active    { background: #e8f5f5; color: #316461; }
        .badge-completed { background: #F1EFE8; color: #5F5E5A; }

        /* Milestone pills */
        .milestone-pills {
            display: flex;
            gap: 6px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .ms-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 500;
        }

        .ms-pill.approved   { background: #e8f5f5; color: #316461; }
        .ms-pill.in-progress{ background: #FEF3C7; color: #92400E; }
        .ms-pill.pending    { background: #F1EFE8; color: #888; }
        .ms-pill.locked     { background: #F1EFE8; color: #aaa; }
        .ms-pill.under-review{ background: #EEEDFE; color: #534AB7; }
        .ms-pill.deposited  { background: #EBF5FB; color: #185FA5; }

        /* Progress bar */
        .progress-section { margin-bottom: 16px; }

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
        }

        /* Info row */
        .info-row {
            display: flex;
            gap: 16px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: #666;
        }

        .info-item strong { color: #1a3533; }

        /* Card footer */
        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 0.5px solid #f0eeee;
        }

        .budget-tag {
            font-size: 15px;
            font-weight: 700;
            color: #316461;
        }

        .card-actions { display: flex; gap: 8px; }

        .btn-proposals {
            padding: 7px 14px;
            background: #EBF5FB;
            color: #185FA5;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
        }

        .btn-view {
            padding: 7px 14px;
            background: linear-gradient(135deg, #47928e, #316461);
            color: #fff;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            grid-column: 1 / -1;
        }

        .empty-icon  { font-size: 56px; margin-bottom: 16px; }
        .empty-title { font-size: 18px; font-weight: 600; color: #1a3533; margin-bottom: 8px; }
        .empty-sub   { font-size: 13px; color: #888; margin-bottom: 24px; }

        .btn-create-large {
            display: inline-block;
            padding: 12px 28px;
            background: linear-gradient(135deg, #47928e, #316461);
            color: #fff;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
        }

        /* Result count */
        .result-count {
            font-size: 13px;
            color: #888;
            margin-bottom: 14px;
        }

        .result-count span { font-weight: 600; color: #316461; }
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
        <div>
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
        <a href="dashboard.php" class="nav-item">
            <div class="nav-icon">⊞</div> Dashboard
        </a>
        <a href="create_project.php" class="nav-item">
            <div class="nav-icon">⊕</div> Create project
        </a>
        <a href="my_projects.php" class="nav-item active">
            <div class="nav-icon">▤</div> My projects
        </a>
        <a href="approve_milestone.php" class="nav-item">
            <div class="nav-icon">✓</div> Approve milestones
        </a>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-label">Account</div>
        <a href="../logout.php" class="nav-item">
            <div class="nav-icon">⇥</div> Logout
        </a>
    </div>
</div>

<!-- MAIN -->
<div class="main">

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <div class="page-title">My Projects</div>
            <div class="page-sub">Manage and track all your freelance projects</div>
        </div>
        <a href="create_project.php" class="btn-create">➕ New Project</a>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-bar">
        <a href="my_projects.php" class="filter-tab <?= $filter == 'all' ? 'active' : '' ?>">
            All <span class="filter-count"><?= $counts['all'] ?></span>
        </a>
        <a href="my_projects.php?filter=open" class="filter-tab <?= $filter == 'open' ? 'active' : '' ?>">
            Open <span class="filter-count"><?= $counts['open'] ?></span>
        </a>
        <a href="my_projects.php?filter=active" class="filter-tab <?= $filter == 'active' ? 'active' : '' ?>">
            Active <span class="filter-count"><?= $counts['active'] ?></span>
        </a>
        <a href="my_projects.php?filter=completed" class="filter-tab <?= $filter == 'completed' ? 'active' : '' ?>">
            Completed <span class="filter-count"><?= $counts['completed'] ?></span>
        </a>
    </div>

    <!-- Search -->
    <form method="GET" action="my_projects.php">
        <input type="hidden" name="filter" value="<?= $filter ?>"/>
        <div class="search-bar">
            <span style="font-size:16px;color:#aaa">🔍</span>
            <input type="text" name="search" placeholder="Search projects by title..." value="<?= htmlspecialchars($search) ?>"/>
            <button type="submit">Search</button>
            <?php if(!empty($search)): ?>
                <a href="my_projects.php?filter=<?= $filter ?>" style="font-size:12px;color:#888;text-decoration:none;white-space:nowrap">✕ Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Result Count -->
    <div class="result-count">
        Showing <span><?= count($projects) ?></span> project(s)
        <?php if(!empty($search)): ?>
            for "<span><?= htmlspecialchars($search) ?></span>"
        <?php endif; ?>
    </div>

    <!-- Projects Grid -->
    <div class="projects-grid">

        <?php if(count($projects) == 0): ?>
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <div class="empty-title">No projects found</div>
                <div class="empty-sub">
                    <?php if(!empty($search)): ?>
                        No projects match your search. Try a different keyword.
                    <?php else: ?>
                        You have no <?= $filter != 'all' ? $filter : '' ?> projects yet.
                    <?php endif; ?>
                </div>
                <a href="create_project.php" class="btn-create-large">+ Create New Project</a>
            </div>

        <?php else: ?>

            <?php foreach($projects as $project): ?>
                <?php
                // Get milestones
                $ms_stmt = $pdo->prepare("SELECT * FROM milestones WHERE project_id = ? ORDER BY order_number");
                $ms_stmt->execute([$project['id']]);
                $milestones = $ms_stmt->fetchAll(PDO::FETCH_ASSOC);

                $ms_total = count($milestones);
                $ms_done  = 0;
                foreach($milestones as $m){
                    if($m['status'] == 'approved') $ms_done++;
                }
                $progress = $ms_total > 0 ? ($ms_done / $ms_total) * 100 : 0;

                // Get pending proposals
                $pr_stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM proposals WHERE project_id = ? AND status = 'pending'");
                $pr_stmt->execute([$project['id']]);
                $pending_proposals = $pr_stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

                // Get freelancer name
                $fl_name = null;
                if($project['freelancer_id']){
                    $fl_stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                    $fl_stmt->execute([$project['freelancer_id']]);
                    $fl = $fl_stmt->fetch(PDO::FETCH_ASSOC);
                    $fl_name = $fl['name'] ?? null;
                }
                ?>

                <div class="project-card <?= $project['status'] ?>">
                    <div class="card-head">
                        <div style="flex:1;min-width:0">
                            <div class="project-title"><?= htmlspecialchars($project['title']) ?></div>
                        </div>
                        <span class="badge badge-<?= $project['status'] ?>" style="margin-left:10px"><?= ucfirst($project['status']) ?></span>
                    </div>

                    <div class="project-desc"><?= htmlspecialchars($project['description']) ?></div>

                    <!-- Info Row -->
                    <div class="info-row">
                        <div class="info-item">
                            📅 <strong><?= date('d M Y', strtotime($project['created_at'])) ?></strong>
                        </div>
                        <?php if($fl_name): ?>
                            <div class="info-item">
                                👤 <strong><?= htmlspecialchars($fl_name) ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if($pending_proposals > 0): ?>
                            <div class="info-item" style="color:#92400E">
                                ⚠ <strong><?= $pending_proposals ?> proposal(s)</strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Milestone Pills -->
                    <?php if(count($milestones) > 0): ?>
                        <div class="milestone-pills">
                            <?php foreach($milestones as $m): ?>
                                <?php
                                $pill_class = 'pending';
                                $pill_icon  = '⏳';
                                if($m['is_locked'])              { $pill_class = 'locked';       $pill_icon = '⛉'; }
                                elseif($m['status'] == 'approved')     { $pill_class = 'approved';     $pill_icon = '✓'; }
                                elseif($m['status'] == 'under_review') { $pill_class = 'under-review'; $pill_icon = '👀'; }
                                elseif($m['status'] == 'in_progress')  { $pill_class = 'in-progress';  $pill_icon = '⚙'; }
                                elseif($m['status'] == 'deposited')    { $pill_class = 'deposited';    $pill_icon = '⛉'; }
                                ?>
                                <span class="ms-pill <?= $pill_class ?>">
                                    <?= $pill_icon ?> <?= htmlspecialchars($m['title']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Progress -->
                    <div class="progress-section">
                        <div class="progress-label">
                            <span>Milestone Progress</span>
                            <span><?= $ms_done ?>/<?= $ms_total ?> approved</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width:<?= $progress ?>%"></div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer">
                        <div class="budget-tag">Rs. <?= number_format($project['total_budget'], 2) ?></div>
                        <div class="card-actions">
                            <?php if($project['status'] == 'open' && $pending_proposals > 0): ?>
                                <a href="view_project.php?id=<?= $project['id'] ?>" class="btn-proposals">
                                    📋 <?= $pending_proposals ?> Proposal(s)
                                </a>
                            <?php endif; ?>
                            <a href="view_project.php?id=<?= $project['id'] ?>" class="btn-view">View →</a>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</div>

</body>
</html>