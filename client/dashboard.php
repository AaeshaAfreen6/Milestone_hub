<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/db.php';
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../index.php");
    exit();
}

// Only allow client
if($_SESSION['user_role'] != 'client'){
    header("Location: ../index.php");
    exit();
}

$client_id = $_SESSION['user_id'];

// Get all projects by this client
$stmt = $pdo->prepare("SELECT * FROM projects WHERE client_id = ? ORDER BY created_at DESC");
$stmt->execute([$client_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count stats
$total     = count($projects);
$active    = 0;
$open      = 0;
$completed = 0;

foreach($projects as $p){
    if($p['status'] == 'active')    $active++;
    if($p['status'] == 'open')      $open++;
    if($p['status'] == 'completed') $completed++;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Client Dashboard - Milestone Hub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>  

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-left">
        <span class="logo">Milestone Hub</span>
    </div>
    <div class="topbar-right">
        <span class="username">Welcome, <?= $_SESSION['user_name'] ?></span>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="layout">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <a href="dashboard.php" class="nav-item active">Dashboard</a>
        <a href="create_project.php" class="nav-item">+ Create project</a>
        <a href="approve_milestone.php" class="nav-item">Approve milestones</a>
         <a href="../landing.php" class="nav-item">← Home</a>
    </div>

    <!-- MAIN -->
    <div class="main">
        <h2 class="page-title">Client Dashboard</h2>

        <!-- STATS -->
        <div class="stats">
            <div class="stat">
                <div class="stat-label">Total projects</div>
                <div class="stat-value"><?= $total ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">Open</div>
                <div class="stat-value"><?= $open ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">Active</div>
                <div class="stat-value"><?= $active ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">Completed</div>
                <div class="stat-value"><?= $completed ?></div>
            </div>
        </div>

        <!-- PROJECTS -->
        <h3 class="section-title">My Projects</h3>

        <?php if(count($projects) == 0): ?>
            <div class="empty">
                No projects yet. <a href="create_project.php">Create your first project</a>
            </div>
        <?php else: ?>
            <?php foreach($projects as $project): ?>
                <div class="card">
                    <div class="card-head">
                        <div>
                            <div class="card-title"><?= htmlspecialchars($project['title']) ?></div>
                            <div class="card-sub">Budget: Rs. <?= number_format($project['total_budget'], 2) ?></div>
                        </div>
                        <span class="badge badge-<?= $project['status'] ?>"><?= ucfirst($project['status']) ?></span>
                    </div>
                    <div class="card-footer">
                        <span class="date">Created: <?= date('d M Y', strtotime($project['created_at'])) ?></span>
                        <a href="view_project.php?id=<?= $project['id'] ?>" class="btn btn-primary">View project</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>

</body>
</html>