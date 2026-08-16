<?php
include '../includes/db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'client'){
    header("Location: ../index.php");
    exit();
}

$client_id = $_SESSION['user_id'];
$error = "";
$success = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $budget      = $_POST['budget'];

    $ms_titles  = $_POST['ms_title'];
    $ms_descs   = $_POST['ms_desc'];
    $ms_amounts = $_POST['ms_amount'];
    $ms_dates   = $_POST['ms_date'];

    // Insert project
    $stmt = $pdo->prepare("INSERT INTO projects (title, description, client_id, total_budget, status) VALUES (?, ?, ?, ?, 'open')");
    $stmt->execute([$title, $description, $client_id, $budget]);
    $project_id = $pdo->lastInsertId();

    // Insert 3 milestones
    for($i = 0; $i < 3; $i++){
        $locked = ($i == 0) ? 0 : 1;
        $stmt = $pdo->prepare("INSERT INTO milestones (project_id, title, description, amount, order_number, is_locked, due_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $project_id,
            $ms_titles[$i],
            $ms_descs[$i],
            $ms_amounts[$i],
            $i + 1,
            $locked,
            $ms_dates[$i]
        ]);
    }

    $success = "Project created successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Project - Milestone Hub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .form-card {
            background: #fff;
            border-radius: 12px;
            padding: 28px;
            border: 0.5px solid #e8e6e6;
            max-width: 750px;
        }

        .form-card h3 {
            font-size: 15px;
            font-weight: 500;
            color: #1b4332;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0eeee;
        }

        .field {
            margin-bottom: 16px;
        }

        .field label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #444;
            margin-bottom: 6px;
        }

        .field input,
        .field textarea,
        .field select {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e8e6e6;
            border-radius: 10px;
            font-size: 14px;
            color: #1a1a2e;
            background: #fafafa;
            outline: none;
            font-family: inherit;
        }

        .field input:focus,
        .field textarea:focus {
            border-color: #1b4332;
            background: #fff;
        }

        .field textarea {
            resize: vertical;
            min-height: 80px;
        }

        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .milestone-box {
            background: #f8f7ff;
            border: 1.5px solid #e8e6e6;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 16px;
        }

        .milestone-box h4 {
            font-size: 13px;
            font-weight: 600;
            color: #1b4332;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .milestone-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background:#1b4332;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }

        .lock-note {
            font-size: 11px;
            color: #9090a0;
            font-weight: 400;
        }

        .btn-submit {
            padding: 12px 32px;
            background: linear-gradient(135deg,	
#1b4332	
#1b4332);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            margin-top: 8px;
        }

        .btn-submit:hover { opacity: 0.9; }

        .alert {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
            max-width: 750px;
        }

        .alert-error   { background: #FEE2E2; color: #B91C1C; }
        .alert-success { background: #E1F5EE; color: #0F6E56; }
        .alert-success a { color: #0F6E56; font-weight: 600; }
    </style>
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
        <a href="dashboard.php" class="nav-item">Dashboard</a>
        <a href="create_project.php" class="nav-item active">+ Create project</a>
        <a href="approve_milestone.php" class="nav-item">Approve milestones</a>
         <a href="../landing.php" class="nav-item">← Home</a>
    </div>

    <!-- MAIN -->
    <div class="main">
        <h2 class="page-title">Create New Project</h2>

        <?php if($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success">
                Project created successfully! 
                <a href="dashboard.php">Go to dashboard →</a>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-card">

            <h3>Project Details</h3>

            <div class="field">
                <label>Project Title</label>
                <input type="text" name="title" placeholder="e.g. E-commerce website" required/>
            </div>

            <div class="field">
                <label>Project Description</label>
                <textarea name="description" placeholder="Describe what this project is about..." required></textarea>
            </div>

            <div class="field">
                <label>Total Budget (Rs.)</label>
                <input type="number" name="budget" placeholder="e.g. 45000" required/>
            </div>

            <h3 style="margin-top: 24px;">Define 3 Milestones</h3>

            <?php for($i = 0; $i < 3; $i++): ?>
            <div class="milestone-box">
                <h4>
                    <div class="milestone-num"><?= $i+1 ?></div>
                    Milestone <?= $i+1 ?>
                    <span class="lock-note">
                        <?= $i == 0 ? '— Unlocked by default' : '— Locked until previous is approved' ?>
                    </span>
                </h4>

                <div class="field">
                    <label>Title</label>
                    <input type="text" name="ms_title[]" placeholder="e.g. Wireframe design" required/>
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
                        <input type="date" name="ms_date[]" id="ms_date" min="<?= date('Y-m-d') ?>" required/>
                    </div>
                </div>
            </div>
            <?php endfor; ?>

            <button type="submit" class="btn-submit">Create Project</button>

        </form>
    </div>
</div>

</body>
</html>