<?php
include 'includes/db.php';
session_start();

$error = "";
$success = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role     = $_POST['role'];

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if($stmt->rowCount() > 0){
        $error = "Email already registered. Please login.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashed, $role]);
        $success = "Registration successful! You can now login.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Milestone Hub</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #406a62 0%, #4c7c7d 50%, #283a36 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            padding: 36px 32px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .logo {
            text-align: center;
            margin-bottom: 24px;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: linear-gradient(135deg,#47928e, rgb(6, 62, 53));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 26px;
            color: #fff;
        }

        .logo h2 {
            font-size: 22px;
            font-weight: 600;
            color:  #1a1a2e;
            margin-bottom: 4px;
        }

        .logo p {
            font-size: 13px;
            color: #9090a0;
        }

        .divider {
            height: 1px;
            background: #f0eeee;
            margin: 20px 0;
        }

        .field {
            margin-bottom: 16px;
        }

        .field label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #444;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrap .icon {
            position: absolute;
            left: 14px;
            font-size: 16px;
            color: #9090a0;
        }

        .input-wrap input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1.5px solid #e8e6e6;
            border-radius: 10px;
            font-size: 14px;
            color: #32514f;
            background: #fafafa;
            outline: none;
            font-family: inherit;
        }

        .input-wrap input:focus {
            border-color: #47928e;
            background: #fff;
        }

        .roles {
            display: flex;
            gap: 8px;
            margin-bottom: 4px;
        }

        .role-btn {
            flex: 1;
            padding: 10px 4px;
            border-radius: 10px;
            border: 1.5px solid #e8e6e6;
            background: #fafafa;
            color: #888;
            font-size: 13px;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }

        .role-btn.active {
            border-color: #3d6c66;
            background: #f0eeff;
            color: #335b52;
            font-weight: 500;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg,#47928e, rgb(6, 62, 53));
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            margin-top: 4px;
        }

        .btn-submit:hover { opacity: 0.9; }

        .footer-link {
            text-align: center;
            margin-top: 18px;
            font-size: 13px;
            color: #9090a0;
        }

        .footer-link a {
            color: #209973;
            text-decoration: none;
            font-weight: 500;
        }

        .alert {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .alert-error   { background: #FEE2E2; color: #B91C1C; }
        .alert-success { background: #E1F5EE; color: #0F6E56; }
    </style>
</head>
<body>

<div class="card">
    <div class="logo">
        <div class="logo-icon">⊞</div>
        <h2>Create Account</h2>
        <p>Milestone Hub — Freelancer Client Management</p>
    </div>

    <div class="divider"></div>

    <?php if($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success"><?= $success ?> <a href="index.php">Login here</a></div>
    <?php endif; ?>

    <form method="POST">

        <div class="field">
            <label>Register as</label>
            <div class="roles">
                <button type="button" class="role-btn" onclick="setRole(this,'client')">♟ Client</button>
                <button type="button" class="role-btn active" onclick="setRole(this,'freelancer')">⊛ Freelancer</button>
            </div>
            <input type="hidden" name="role" id="role_input" value="freelancer"/>
        </div>

        <div class="field">
            <label>Full Name</label>
            <div class="input-wrap">
                <span class="icon">♟</span>
                <input type="text" name="name" placeholder="Enter your full name" required/>
            </div>
        </div>

        <div class="field">
            <label>Email Address</label>
            <div class="input-wrap">
                <span class="icon">✉</span>
                <input type="email" name="email" placeholder="Enter your email" required/>
            </div>
        </div>

        <div class="field">
            <label>Password</label>
            <div class="input-wrap">
                <span class="icon">🔒</span>
                <input type="password" name="password" placeholder="Create a password" required/>
            </div>
        </div>

        <button type="submit" class="btn-submit">Create Account</button>

        <div class="footer-link">Already have an account? <a href="index.php">Login here</a></div>

    </form>
</div>

<script>
function setRole(el, role){
    document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('role_input').value = role;
}
</script>

</body>
</html>