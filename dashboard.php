<?php
require 'database/connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user details from DB
$stmt = $conn->prepare("SELECT full_name, email, age, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$user_name  = $user['full_name'] ?? 'User';
$first_name = explode(' ', trim($user_name))[0];

$member_since = isset($user['created_at'])
    ? date('M j, Y', strtotime($user['created_at']))
    : 'N/A';

$hour = (int) date('H');
if ($hour < 12)      $greeting = "Good morning";
elseif ($hour < 18)  $greeting = "Good afternoon";
else                 $greeting = "Good evening";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --ink:     #0e0e12;
            --surface: #16161e;
            --raised:  #1e1e28;
            --border:  rgba(255,255,255,0.07);
            --gold:    #c9a96e;
            --gold-bg: rgba(201,169,110,0.08);
            --text:    #e8e4dc;
            --muted:   #7a7880;
            --success: #5fcf80;
            --warn:    #e5a550;
            --info:    #6ea8d4;
            --sidebar: 240px;
        }
        html, body { height: 100%; }
        body { font-family: 'DM Sans', sans-serif; background: var(--ink); color: var(--text); min-height: 100vh; display: flex; overflow-x: hidden; }
        body::before { content: ''; position: fixed; inset: 0; background: radial-gradient(ellipse 40% 50% at 0% 0%, rgba(201,169,110,0.05) 0%, transparent 60%), radial-gradient(ellipse 50% 40% at 100% 100%, rgba(100,80,180,0.04) 0%, transparent 60%); pointer-events: none; z-index: 0; }

        .sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: var(--sidebar); background: var(--surface); border-right: 1px solid var(--border); display: flex; flex-direction: column; z-index: 10; animation: slideIn 0.5s cubic-bezier(0.22,1,0.36,1) both; }
        @keyframes slideIn { from { transform: translateX(-20px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .sidebar::after { content: ''; position: absolute; top: 0; right: 0; bottom: 0; width: 1px; background: linear-gradient(to bottom, transparent, rgba(201,169,110,0.2) 30%, rgba(201,169,110,0.2) 70%, transparent); }
        .sidebar-logo { padding: 1.75rem 1.5rem 1.5rem; display: flex; align-items: center; gap: 0.75rem; border-bottom: 1px solid var(--border); }
        .sidebar-logo-text { font-family: 'Cormorant Garamond', serif; font-size: 1.25rem; font-weight: 500; letter-spacing: 0.03em; color: var(--text); }
        .sidebar-logo-text span { color: var(--gold); }
        .sidebar-nav { flex: 1; padding: 1.25rem 0.75rem; display: flex; flex-direction: column; gap: 0.25rem; }
        .nav-label { font-size: 0.65rem; letter-spacing: 0.16em; text-transform: uppercase; color: var(--muted); padding: 0.75rem 0.75rem 0.4rem; font-weight: 500; }
        .nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 0.85rem; border-radius: 8px; text-decoration: none; color: var(--muted); font-size: 0.85rem; font-weight: 400; transition: background 0.18s, color 0.18s; position: relative; cursor: pointer; border: none; background: none; width: 100%; text-align: left; }
        .nav-item:hover { background: rgba(255,255,255,0.04); color: var(--text); }
        .nav-item.active { background: var(--gold-bg); color: var(--gold); }
        .nav-item.active::before { content: ''; position: absolute; left: 0; top: 20%; bottom: 20%; width: 2px; background: var(--gold); border-radius: 2px; }
        .nav-item svg { flex-shrink: 0; opacity: 0.8; }
        .nav-item.active svg { opacity: 1; }
        .sidebar-footer { padding: 1rem 0.75rem 1.5rem; border-top: 1px solid var(--border); }
        .user-chip { display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 0.85rem; border-radius: 8px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); }
        .avatar { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #c9a96e, #a07c45); display: flex; align-items: center; justify-content: center; font-family: 'Cormorant Garamond', serif; font-size: 0.9rem; font-weight: 600; color: #0e0e12; flex-shrink: 0; }
        .user-chip-info { flex: 1; min-width: 0; }
        .user-chip-name { font-size: 0.8rem; font-weight: 500; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-chip-role { font-size: 0.7rem; color: var(--muted); }

        .main { margin-left: var(--sidebar); flex: 1; display: flex; flex-direction: column; min-height: 100vh; position: relative; z-index: 1; }
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 1.4rem 2.5rem; border-bottom: 1px solid var(--border); background: rgba(14,14,18,0.6); backdrop-filter: blur(12px); position: sticky; top: 0; z-index: 5; animation: fadeDown 0.5s 0.1s cubic-bezier(0.22,1,0.36,1) both; }
        @keyframes fadeDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .topbar-greeting h2 { font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; font-weight: 500; color: var(--text); }
        .topbar-greeting p { font-size: 0.78rem; color: var(--muted); margin-top: 0.1rem; }
        .topbar-actions { display: flex; align-items: center; gap: 0.75rem; }
        .icon-btn { width: 36px; height: 36px; border-radius: 8px; background: rgba(255,255,255,0.04); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--muted); transition: background 0.18s, color 0.18s; text-decoration: none; }
        .icon-btn:hover { background: rgba(255,255,255,0.08); color: var(--text); }
        .logout-btn { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 8px; background: rgba(224,108,117,0.08); border: 1px solid rgba(224,108,117,0.2); color: #e06c75; font-family: 'DM Sans', sans-serif; font-size: 0.78rem; font-weight: 500; letter-spacing: 0.04em; text-decoration: none; cursor: pointer; transition: background 0.18s; }
        .logout-btn:hover { background: rgba(224,108,117,0.14); }

        .content { padding: 2.5rem; display: flex; flex-direction: column; gap: 2rem; animation: fadeUp 0.6s 0.2s cubic-bezier(0.22,1,0.36,1) both; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; }
        .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; position: relative; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(0,0,0,0.3); }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px; }
        .stat-card.gold::before   { background: linear-gradient(to right, transparent, var(--gold), transparent); }
        .stat-card.green::before  { background: linear-gradient(to right, transparent, var(--success), transparent); }
        .stat-card.blue::before   { background: linear-gradient(to right, transparent, var(--info), transparent); }
        .stat-card.orange::before { background: linear-gradient(to right, transparent, var(--warn), transparent); }
        .stat-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.1rem; }
        .stat-card.gold   .stat-icon { background: rgba(201,169,110,0.1); color: var(--gold); }
        .stat-card.green  .stat-icon { background: rgba(95,207,128,0.1);  color: var(--success); }
        .stat-card.blue   .stat-icon { background: rgba(110,168,212,0.1); color: var(--info); }
        .stat-card.orange .stat-icon { background: rgba(229,165,80,0.1);  color: var(--warn); }
        .stat-value { font-family: 'Cormorant Garamond', serif; font-size: 2rem; font-weight: 500; line-height: 1; margin-bottom: 0.35rem; }
        .stat-label { font-size: 0.75rem; color: var(--muted); letter-spacing: 0.04em; }
        .stat-badge { position: absolute; top: 1.25rem; right: 1.25rem; font-size: 0.68rem; font-weight: 500; padding: 0.2rem 0.55rem; border-radius: 20px; }
        .badge-up   { background: rgba(95,207,128,0.1);  color: var(--success); }
        .badge-info { background: rgba(110,168,212,0.1); color: var(--info); }

        .bottom-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
        .panel-head { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .panel-head h3 { font-family: 'Cormorant Garamond', serif; font-size: 1.1rem; font-weight: 500; color: var(--text); }
        .panel-head span { font-size: 0.72rem; color: var(--muted); letter-spacing: 0.06em; text-transform: uppercase; }
        .panel-body { padding: 1.5rem; }
        .profile-rows { display: flex; flex-direction: column; }
        .profile-row { display: flex; align-items: center; justify-content: space-between; padding: 0.9rem 0; border-bottom: 1px solid var(--border); }
        .profile-row:last-child { border-bottom: none; }
        .row-label { font-size: 0.75rem; color: var(--muted); letter-spacing: 0.06em; text-transform: uppercase; }
        .row-value { font-size: 0.875rem; color: var(--text); text-align: right; }
        .row-value.gold { color: var(--gold); }
        .activity-list { display: flex; flex-direction: column; }
        .activity-item { display: flex; align-items: flex-start; gap: 1rem; padding: 0.9rem 0; border-bottom: 1px solid var(--border); }
        .activity-item:last-child { border-bottom: none; }
        .activity-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
        .dot-gold { background: var(--gold); } .dot-green { background: var(--success); } .dot-blue { background: var(--info); } .dot-orange { background: var(--warn); }
        .activity-text p { font-size: 0.845rem; color: var(--text); line-height: 1.4; }
        .activity-text time { font-size: 0.72rem; color: var(--muted); }

        .banner { border-radius: 10px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; border: 1px solid rgba(95,207,128,0.2); background: rgba(95,207,128,0.06); }
        .banner svg { flex-shrink: 0; color: var(--success); }
        .banner-text p { font-size: 0.845rem; color: var(--text); margin-bottom: 0.2rem; }
        .banner-text span { font-size: 0.75rem; color: var(--muted); }
        .section-title { font-size: 0.72rem; font-weight: 500; color: var(--muted); letter-spacing: 0.12em; text-transform: uppercase; margin-bottom: -0.75rem; }

        @media (max-width: 900px) { :root { --sidebar: 200px; } .bottom-grid { grid-template-columns: 1fr; } }
        @media (max-width: 680px) { .sidebar { transform: translateX(-100%); } .main { margin-left: 0; } .content { padding: 1.5rem; } .topbar { padding: 1rem 1.5rem; } }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <svg width="28" height="28" viewBox="0 0 36 36" fill="none">
            <polygon points="18,3 33,12 33,24 18,33 3,24 3,12" fill="none" stroke="#c9a96e" stroke-width="1.2" opacity="0.8"/>
            <circle cx="18" cy="18" r="3" fill="#c9a96e"/>
        </svg>
        <span class="sidebar-logo-text">My<span>App</span></span>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-label">Menu</span>
        <a class="nav-item active" href="dashboard.php">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>
        <a class="nav-item" href="#">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profile
        </a>
        <a class="nav-item" href="#">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Security
        </a>
        <span class="nav-label">Account</span>
        <a class="nav-item" href="#">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Settings
        </a>
        <a class="nav-item" href="?logout=1">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign Out
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="avatar"><?= strtoupper(substr($first_name, 0, 1)) ?></div>
            <div class="user-chip-info">
                <div class="user-chip-name"><?= htmlspecialchars($first_name) ?></div>
                <div class="user-chip-role">Member</div>
            </div>
        </div>
    </div>
</aside>

<main class="main">
    <header class="topbar">
        <div class="topbar-greeting">
            <h2><?= $greeting ?>, <?= htmlspecialchars($first_name) ?></h2>
            <p><?= date('l, F j, Y') ?></p>
        </div>
        <div class="topbar-actions">
            <a class="icon-btn" href="#" title="Notifications">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </a>
            <a class="logout-btn" href="?logout=1">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Sign Out
            </a>
        </div>
    </header>

    <div class="content">

        <div class="banner">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <div class="banner-text">
                <p>Two-factor authentication is active</p>
                <span>Your account is protected with email OTP verification</span>
            </div>
        </div>

        <span class="section-title">Overview</span>
        <div class="stats-grid">
            <div class="stat-card gold">
                <div class="stat-badge badge-info">Active</div>
                <div class="stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                <div class="stat-value"><?= htmlspecialchars($first_name) ?></div>
                <div class="stat-label">Logged in as</div>
            </div>
            <div class="stat-card green">
                <div class="stat-badge badge-up">↑ Secure</div>
                <div class="stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                <div class="stat-value"><?= $user['age'] ?? '—' ?></div>
                <div class="stat-label">Age on file</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                <div class="stat-value"><?= $member_since ?></div>
                <div class="stat-label">Member since</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <div class="stat-value" id="live-time"><?= date('H:i') ?></div>
                <div class="stat-label">Current time</div>
            </div>
        </div>

        <span class="section-title">Details</span>
        <div class="bottom-grid">
            <div class="panel">
                <div class="panel-head"><h3>Account Profile</h3><span>Your info</span></div>
                <div class="panel-body">
                    <div class="profile-rows">
                        <div class="profile-row"><span class="row-label">Full Name</span><span class="row-value"><?= htmlspecialchars($user['full_name'] ?? '—') ?></span></div>
                        <div class="profile-row"><span class="row-label">Email</span><span class="row-value"><?= htmlspecialchars($user['email'] ?? '—') ?></span></div>
                        <div class="profile-row"><span class="row-label">Age</span><span class="row-value"><?= htmlspecialchars($user['age'] ?? '—') ?></span></div>
                        <div class="profile-row"><span class="row-label">Account ID</span><span class="row-value gold">#<?= str_pad($user_id, 5, '0', STR_PAD_LEFT) ?></span></div>
                        <div class="profile-row"><span class="row-label">MFA Status</span><span class="row-value" style="color:var(--success)">Email OTP Active</span></div>
                        <div class="profile-row"><span class="row-label">Member Since</span><span class="row-value"><?= $member_since ?></span></div>
                    </div>
                </div>
            </div>
            <div class="panel">
                <div class="panel-head"><h3>Recent Activity</h3><span>Latest events</span></div>
                <div class="panel-body">
                    <div class="activity-list">
                        <div class="activity-item"><div class="activity-dot dot-green"></div><div class="activity-text"><p>Signed in successfully</p><time><?= date('M j, Y · H:i') ?></time></div></div>
                        <div class="activity-item"><div class="activity-dot dot-blue"></div><div class="activity-text"><p>OTP verified via email</p><time><?= date('M j, Y · H:i') ?></time></div></div>
                        <div class="activity-item"><div class="activity-dot dot-gold"></div><div class="activity-text"><p>Account created</p><time><?= $member_since ?></time></div></div>
                        <div class="activity-item"><div class="activity-dot dot-blue"></div><div class="activity-text"><p>Profile information saved</p><time><?= $member_since ?></time></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    function updateClock() {
        const el = document.getElementById('live-time');
        if (!el) return;
        const now = new Date();
        el.textContent = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
    }
    setInterval(updateClock, 1000);
</script>
</body>
</html>