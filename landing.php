<!DOCTYPE html>
<html>
<head>
    <title>Milestone Hub - Project Management System</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #1a1a2e;
            background: #fff;
        }

        /* NAVBAR */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 20px 62px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 0.8px solid #e8e6e6;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-logo-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #47928e, #316461);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
        }

        .nav-logo-text { }
        .nav-logo-title {
            font-size: 16px;
            font-weight: 600;
            color: #316461;
            line-height: 1.2;
        }
        .nav-logo-sub {
            font-size: 10px;
            font-weight: 400;
            color: #47928e;
            letter-spacing: 0.3px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .nav-links a {
            font-size: 14px;
            color: #555;
            text-decoration: none;
        }

        .nav-links a:hover { color: #47928e; }

        .nav-btns {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-login {
            padding: 8px 20px;
            border: 1.5px solid #47928e;
            border-radius: 8px;
            color: #47928e;
            font-size: 14px;
            text-decoration: none;
            font-weight: 500;
        }

        .btn-register {
            padding: 8px 20px;
            background: linear-gradient(135deg, #47928e, #316461);
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            text-decoration: none;
            font-weight: 500;
        }

        /* HERO */
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a3533 0%, #316461 50%, #47928e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 100px 20px 60px;
            position: relative;
            overflow: hidden;
        }

        /* .hero::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: rgba(63, 137, 128, 0.1);
            top: -100px;
            right: -100px;
        } */

        /* .hero::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(63, 137, 128, 0.1);
            bottom: -100px;
            left: -100px;
        } */

        .hero-content { position: relative; z-index: 1; max-width: 700px; }

        .hero-badge {
            display: inline-block;
            background: rgba(63, 137, 128, 0.2);
            color: #a8d5d3;
            font-size: 13px;
            padding: 6px 16px;
            border-radius: 20px;
            margin-bottom: 24px;
            border: 1px solid rgba(63, 137, 128, 0.3);
        }

        .hero h1 {
            font-size: 52px;
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 20px;
        }

        .hero h1 span { color: #a8d5d3; }

        .hero p {
            font-size: 18px;
            color: #a8d5d3;
            line-height: 1.7;
            margin-bottom: 36px;
        }

        .hero-btns {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-btn-primary {
            padding: 14px 32px;
            background: #fff;
            color: #316461;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
        }

        .hero-btn-secondary {
            padding: 14px 32px;
            background: transparent;
            color: #fff;
            border: 2px solid rgba(255,255,255,0.4);
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
        }

        .hero-stats {
            display: flex;
            gap: 48px;
            justify-content: center;
            margin-top: 60px;
        }

        .hero-stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
        }

        .hero-stat-label {
            font-size: 13px;
            color: #a8d5d3;
            margin-top: 4px;
        }

        /* HOW IT WORKS */
        .section { padding: 80px 60px; }
        .section-center { text-align: center; }

        .section-tag {
            display: inline-block;
            background: #e8f5f5;
            color: #316461;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 20px;
            margin-bottom: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-title {
            font-size: 36px;
            font-weight: 700;
            color: #1a3533;
            margin-bottom: 12px;
        }

        .section-sub {
            font-size: 16px;
            color: #666;
            max-width: 560px;
            margin: 0 auto 48px;
            line-height: 1.7;
        }

        /* STEPS */
        .steps {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .step {
            text-align: center;
            padding: 28px 20px;
            border-radius: 16px;
            background: #f5fafa;
            border: 1px solid #e8f5f5;
        }

        .step-num {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #47928e, #316461);
            color: #fff;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .step h3 {
            font-size: 15px;
            font-weight: 600;
            color: #1a3533;
            margin-bottom: 8px;
        }

        .step p {
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }

        /* MILESTONE FLOW */
        .flow-section {
            background: #284947;
            padding: 80px 60px;
        }

        .flow-title {
            font-size: 36px;
            font-weight: 700;
            color: #fff;
            text-align: center;
            margin-bottom: 12px;
        }

        .flow-sub {
            font-size: 16px;
            color: #a8d5d3;
            text-align: center;
            margin-bottom: 48px;
        }

        .flow-steps {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            flex-wrap: wrap;
            max-width: 1000px;
            margin: 0 auto;
        }

        .flow-step {
            text-align: center;
            padding: 20px 16px;
            background: rgba(63, 137, 128, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(63, 137, 128, 0.2);
            width: 150px;
        }

        .flow-step-icon { font-size: 28px; margin-bottom: 8px; }
        .flow-step-name { font-size: 13px; font-weight: 600; color: #fff; margin-bottom: 4px; }
        .flow-step-desc { font-size: 11px; color: #a8d5d3; }
        .flow-arrow { font-size: 20px; color: #47928e; padding: 0 8px; }

        /* FEATURES */
        .features-bg { background: #f5fafa; }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .feature-card {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            border: 1px solid #e8f5f5;
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #47928e, #316461);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 16px;
        }

        .feature-card h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1a3533;
            margin-bottom: 10px;
        }

        .feature-card p {
            font-size: 14px;
            color: #666;
            line-height: 1.7;
        }

        /* ROLES */
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            max-width: 900px;
            margin: 0 auto;
        }

        .role-card {
            border-radius: 16px;
            padding: 32px 24px;
            text-align: center;
        }

        .role-card.client-card {
            background: linear-gradient(135deg, #316461, #47928e);
            color: #fff;
        }

        .role-card.freelancer-card {
            background: linear-gradient(135deg, #3b6a67, #316461);
            color: #fff;
        }

        .role-card.admin-card {
            background: #b3e1e1;
            border: 1px solid #e8f5f5;
        }

        .role-icon { font-size: 40px; margin-bottom: 16px; }

        .role-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .role-card.admin-card h3 { color: #1a3533; }

        .role-list { list-style: none; text-align: left; }

        .role-list li {
            font-size: 13px;
            padding: 6px 0;
            border-bottom: 0.5px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .role-card.admin-card .role-list li {
            color: #000000;
            border-bottom-color: #e8f5f5;
        }

        .role-list li::before {
            content: '✓';
            color: #a8d5d3;
            font-weight: 700;
            flex-shrink: 0;
        }

        .role-card.admin-card .role-list li::before { color: #47928e; }

        /* CTA */
        .cta-section {
            background: linear-gradient(135deg, #316461, #47928e);
            padding: 80px 60px;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 40px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 16px;
        }

        .cta-section p {
            font-size: 16px;
            color: #a8d5d3;
            margin-bottom: 36px;
        }

        .cta-btns { display: flex; gap: 14px; justify-content: center; }

        .cta-btn-primary {
            padding: 14px 36px;
            background: #fff;
            color: #316461;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
        }

        .cta-btn-secondary {
            padding: 14px 36px;
            background: transparent;
            color: #fff;
            border: 2px solid rgba(255,255,255,0.4);
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
        }

        /* FOOTER */
        .footer {
            background: #1a3533;
            padding: 40px 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .footer-logo { font-size: 16px; font-weight: 600; color: #fff; }
        .footer-text { font-size: 13px; color: #6b9e9b; }
        .footer-links { display: flex; gap: 20px; }
        .footer-links a { font-size: 13px; color: #6b9e9b; text-decoration: none; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-logo">
        <div class="nav-logo-icon">&#8862;</div>
        <div class="nav-logo-text">
            <div class="nav-logo-title">Milestone Hub</div>
            <div class="nav-logo-sub">Cilent-freelancer Management System</div>
        </div>
    </div>
    <div class="nav-links">
        <a href="#how-it-works">How it works</a>
        <a href="#features">Features</a>
        <a href="#roles">Who is it for</a>
    </div>
    <div class="nav-btns">
        <a href="index.php" class="btn-login">Login</a>
        <a href="register.php" class="btn-register">Get Started</a>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-content">
    
        <h1>Manage Freelance Projects with <span>Milestone Precision</span></h1>
        <p>Milestone Hub brings structure, transparency, and accountability to freelancer-client collaboration. Break projects into milestones, track progress, and approve phase by phase.</p>
        <div class="hero-btns">
            <a href="register.php" class="hero-btn-primary">Get Started Free</a>
            <a href="#how-it-works" class="hero-btn-secondary">See How It Works</a>
        </div>
        <div class="hero-stats">
            <div>
                <div class="hero-stat-value">3</div>
                <div class="hero-stat-label">Milestones per project</div>
            </div>
        
           
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="section section-center" id="how-it-works">
    <div class="section-tag">How it works</div>
    <h2 class="section-title">Simple 4-Step Process</h2>
    <p class="section-sub">From project creation to completion — every step is tracked and approved.</p>
    <div class="steps">
        <div class="step">
            <div class="step-num">1</div>
            <h3>Client posts project</h3>
            <p>Client creates a project with title, budget, and 3 milestones with deadlines.</p>
        </div>
        <div class="step">
            <div class="step-num">2</div>
            <h3>Freelancer applies</h3>
            <p>Freelancers browse open projects and send proposals with a message.</p>
        </div>
        <div class="step">
            <div class="step-num">3</div>
            <h3>Client accepts</h3>
            <p>Client reviews proposals and accepts one freelancer. Project becomes active.</p>
        </div>
        <div class="step">
            <div class="step-num">4</div>
            <h3>Milestone by milestone</h3>
            <p>Freelancer works and submits. Client approves. Next milestone unlocks automatically.</p>
        </div>
    </div>
</section>

<!-- MILESTONE FLOW -->
<section class="flow-section">
    <div class="flow-title">Milestone Status Pipeline</div>
    <div class="flow-sub">Every milestone goes through a strict sequential approval process</div>
    <div class="flow-steps">
        <div class="flow-step">
            <div class="flow-step-name">Pending</div>
            <div class="flow-step-desc">Milestone created</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-step">
            <div class="flow-step-name">Deposited</div>
            <div class="flow-step-desc">Client reserves funds</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-step">
            <div class="flow-step-name">In Progress</div>
            <div class="flow-step-desc">Freelancer working</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-step">
            <div class="flow-step-name">Under Review</div>
            <div class="flow-step-desc">Work submitted</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-step">
            
            <div class="flow-step-name">Approved</div>
            <div class="flow-step-desc">Next unlocks!</div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="section features-bg section-center" id="features">
    <div class="section-tag">Features</div>
    <h2 class="section-title">Everything You Need</h2>
    <p class="section-sub">Built specifically for Nepal's freelance market with simplicity and accountability in mind.</p>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">🔒</div>
            <h3>Milestone Locking</h3>
            <p>The next milestone is automatically locked until the client formally approves the previous one.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">👥</div>
            <h3>Role-Based Access</h3>
            <p>Admin, Client, and Freelancer each have their own dashboard showing only what is relevant to their role.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📋</div>
            <h3>Proposal System</h3>
            <p>Freelancers browse open projects and send proposals. Clients review and accept the best fit.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3>Activity Log</h3>
            <p>Every milestone status change is recorded with the user, timestamp, and note.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">💵</div>
            <h3>Payment Ledger</h3>
            <p>Each milestone tracks a budget amount with status Pending, Deposited, Released.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🛡️</div>
            <h3>Secure & Safe</h3>
            <p>Built with PHP sessions and PDO prepared statements to protect against SQL injection.</p>
        </div>
    </div>
</section>

<!-- ROLES -->
<section class="section section-center" id="roles">
    <div class="section-tag">Who is it for</div>
    <h2 class="section-title">Three Roles, One Platform</h2>
    <p class="section-sub">Everyone has a clear role and specific actions they can perform.</p>
    <div class="roles-grid">
        <div class="role-card client-card">
            <div class="role-icon">♟</div>
            <h3>Client</h3>
            <ul class="role-list">
                <li>Create projects and milestones</li>
                <li>Review freelancer proposals</li>
                <li>Accept the right freelancer</li>
                <li>Mark milestones as deposited</li>
                <li>Approve completed milestones</li>
            </ul>
        </div>
        <div class="role-card freelancer-card">
            <div class="role-icon">⊛</div>
            <h3>Freelancer</h3>
            <ul class="role-list">
                <li>Browse all open projects</li>
                <li>Send proposals with a message</li>
                <li>Work on assigned milestones</li>
                <li>Update milestone status</li>
                <li>Submit work for client review</li>
            </ul>
        </div>
        <div class="role-card admin-card">
            <div class="role-icon">♡ </div>
            <h3>Admin</h3>
            <ul class="role-list">
                <li>View all users on platform</li>
                <li>Monitor all projects</li>
                <li>Manage and delete users</li>
                <li>Full system oversight</li>
                <li>Access complete activity logs</li>
            </ul>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <h2>Ready to Get Started?</h2>
    <p>Join Milestone Hub today and bring structure to your freelance projects.</p>
    <div class="cta-btns">
        <a href="register.php" class="cta-btn-primary">Create Account</a>
        <a href="index.php" class="cta-btn-secondary">Login</a>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-logo">⊞ Milestone Hub</div>
    <div class="footer-text">© 2082 Milestone Hub — Prime College, BCA 4th Semester Project</div>
    <div class="footer-links">
        <a href="index.php">Login</a>
        <a href="register.php">Register</a>
    </div>
</footer>

</body>
</html>