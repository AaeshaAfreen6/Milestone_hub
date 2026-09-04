<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'freelancer'){
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Freelancer Dashboard - Milestone Hub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

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
    <div class="sidebar">
        <a href="dashboard.php" class="nav-item active">Dashboard</a>
        <a href="browse_project.php" class="nav-item">Browse projects</a>
        <a href="update_milestone.php" class="nav-item">My milestones</a>
        <a href="my_proposals.php" class="nav-item">My proposals</a>
         <a href="../landing.php" class="nav-item">← Home</a>
    </div>

    <div class="main">
        <h2 class="page-title">Freelancer Dashboard</h2>
        <p style="color:#888;font-size:14px">Welcome back, <?= $_SESSION['user_name'] ?>! Browse open projects and send proposals.</p>

        <div style="margin-top:24px">
            <a href="browse_project.php" style="
                display:inline-block;
                padding:12px 24px;
                background:linear-gradient(135deg,#2d6a4f,#1b4332);
                color:#fff;
                border-radius:10px;
                text-decoration:none;
                font-size:14px;
                font-weight:500;
            ">Browse Open Projects →</a>
        </div>
    </div>
</div>

</body>
</html> 