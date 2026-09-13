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
$error     = "";
$success   = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $budget      = $_POST['budget'];
    $ms_titles   = $_POST['ms_title'];
    $ms_descs    = $_POST['ms_desc'];
    $ms_amounts  = $_POST['ms_amount'];
    $ms_dates    = $_POST['ms_date'];

    if(empty($title) || empty($description) || empty($budget)){
        $error = "Please fill in all project details.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO projects (title, description, client_id, total_budget, status) VALUES (?, ?, ?, ?, 'open')");
        $stmt->execute([$title, $description, $client_id, $budget]);
        $project_id = $pdo->lastInsertId();

        for($i = 0; $i < 3; $i++){
            $locked = ($i == 0) ? 0 : 1;
            $stmt = $pdo->prepare("INSERT INTO milestones (project_id, title, description, amount, order_number, is_locked, due_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$project_id, $ms_titles[$i], $ms_descs[$i], $ms_amounts[$i], $i + 1, $locked, $ms_dates[$i]]);
        }
        $success = "Project created successfully!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Project - Milestone Hub</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f4f0; color: #1a1a2e; }

        .topbar {
            background: linear-gradient(135deg, #1a3533, #316461);
            padding: 0 28px; height: 80px;
            display: flex; align-items: center; justify-content: space-between;
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            box-shadow: 0 2px 20px rgba(0,0,0,0.15);
        }
        .topbar-left { display: flex; align-items: center; gap: 10px; }
        .logo-icon { width: 34px; height: 34px; border-radius: 8px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 16px; color: #fff; }
        .logo-text { font-size: 16px; font-weight: 600; color: #fff; }
        .logo-sub  { font-size: 10px; color: rgba(255,255,255,0.6); }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .user-name { font-size: 13px; font-weight: 500; color: #fff; }
        .user-role { font-size: 11px; color: rgba(255,255,255,0.6); }
        .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #47928e, #95d5b2); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; color: #fff; }
        .btn-logout { padding: 7px 14px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; font-size: 12px; text-decoration: none; }

        .sidebar { width: 220px; background: #fff; position: fixed; top: 60px; left: 0; bottom: 0; padding: 24px 0; overflow-y: auto; box-shadow: 2px 0 20px rgba(0,0,0,0.05); }
        .sidebar-section { padding: 0 16px; margin-bottom: 8px; }
        .sidebar-label { font-size: 10px; font-weight: 600; color: #aaa; text-transform: uppercase; letter-spacing: 1px; padding: 0 8px; margin-bottom: 6px; margin-top: 16px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; font-size: 13px; color: #666; text-decoration: none; margin-bottom: 2px; transition: all 0.2s; }
        .nav-item:hover { background: #f0f9f7; color: #316461; }
        .nav-item.active { background: linear-gradient(135deg, #e8f5f5, #d0eeec); color: #316461; font-weight: 500; }
        .nav-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 15px; background: #f5f5f5; flex-shrink: 0; }
        .nav-item.active .nav-icon { background: linear-gradient(135deg, #47928e, #316461); color: #fff; }

        .main { margin-left: 220px; margin-top: 60px; padding: 28px; min-height: calc(100vh - 60px); }

        .page-header { margin-bottom: 24px; }
        .page-title { font-size: 22px; font-weight: 700; color: #1a3533; }
        .page-sub   { font-size: 13px; color: #888; margin-top: 3px; }

        .form-wrapper { max-width: 800px; }

        .form-section {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .form-section-title {
            font-size: 15px;
            font-weight: 600;
            color: #1a3533;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1.5px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .field { margin-bottom: 16px; }

        .field label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #444;
            margin-bottom: 7px;
        }

        .field input,
        .field textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #e8e8e8;
            border-radius: 10px;
            font-size: 14px;
            color: #1a1a2e;
            background: #fafafa;
            outline: none;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        .field input:focus,
        .field textarea:focus {
            border-color: #47928e;
            background: #fff;
        }

        .field textarea { resize: vertical; min-height: 90px; }

        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        /* Milestone box */
        .milestone-box {
            background: linear-gradient(135deg, #f8fffe, #f0faf9);
            border: 1.5px solid #d0eeec;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 16px;
            position: relative;
        }

        .milestone-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .milestone-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #47928e, #316461);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .milestone-title-text {
            font-size: 14px;
            font-weight: 600;
            color: #316461;
        }

        .milestone-lock-note {
            font-size: 11px;
            color: #888;
            font-weight: 400;
        }

        .btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 32px;
            background: linear-gradient(135deg, #47928e, #316461);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            box-shadow: 0 4px 14px rgba(49,100,97,0.3);
        }

        .btn-submit:hover { opacity: 0.9; }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-error   { background: #FEE2E2; color: #B91C1C; }
        .alert-success { background: #e8f5f5; color: #316461; }
        .alert-success a { color: #316461; font-weight: 600; text-decoration: underline; }
    </style>
</head>
<body>

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

<div class="sidebar">
    <div class="sidebar-section">
        <div class="sidebar-label">Main Menu</div>
        <a href="dashboard.php" class="nav-item"><div class="nav-icon">⊞</div> Dashboard</a>
        <a href="create_project.php" class="nav-item active"><div class="nav-icon">⊕</div> Create project</a>
        <a href="my_projects.php" class="nav-item"><div class="nav-icon">▤</div> My projects</a>
        <a href="approve_milestone.php" class="nav-item"><div class="nav-icon">✓</div> Approve milestones</a>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-label">Account</div>
        <a href="../logout.php" class="nav-item"><div class="nav-icon">⇥</div> Logout</a>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <div class="page-title">Create New Project</div>
        <div class="page-sub">Define your project and break it into 3 milestones</div>
    </div>

    <div class="form-wrapper">

        <?php if($error): ?>
            <div class="alert alert-error">⚠ <?= $error ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success">✅ <?= $success ?> <a href="my_projects.php">View my projects →</a></div>
        <?php endif; ?>

        <form method="POST">

            <!-- Project Details -->
            <div class="form-section">
                <div class="form-section-title">📋 Project Details</div>

                <div class="field">
                    <label>Project Title</label>
                    <input type="text" name="title" placeholder="e.g. E-commerce website development" required/>
                </div>
                <div class="field">
                    <label>Project Description</label>
                    <textarea name="description" placeholder="Describe what this project is about, what needs to be delivered..." required></textarea>
                </div>
                <div class="field">
                    <label>Total Budget (Rs.)</label>
                    <input type="number" name="budget" placeholder="e.g. 45000" required/>
                </div>
            </div>

            <!-- Milestones -->
            <div class="form-section">
                <div class="form-section-title">🎯 Define 3 Milestones</div>

                <?php for($i = 0; $i < 3; $i++): ?>
                <div class="milestone-box">
                    <div class="milestone-header">
                        <div class="milestone-num"><?= $i+1 ?></div>
                        <div>
                            <div class="milestone-title-text">Milestone <?= $i+1 ?></div>
                            <div class="milestone-lock-note">
                                <?= $i == 0 ? '🔓 Unlocked by default' : '⛉ Locked until previous milestone is approved' ?>
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <label>Milestone Title</label>
                        <input type="text" name="ms_title[]" placeholder="e.g. Wireframe & UI Design" required/>
                    </div>
                    <div class="field">
                        <label>Description</label>
                        <textarea name="ms_desc[]" placeholder="What will be delivered in this milestone..." required></textarea>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label>Amount (Rs.)</label>
                            <input type="number" name="ms_amount[]" placeholder="e.g. 15000" required/>
                        </div>
                        <div class="field">
                            <label>Due Date</label>
                            <input type="date" name="ms_date[]" required/>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <button type="submit" class="btn-submit">🚀 Create Project</button>

        </form>
    </div>
</div>

</body>
</html>