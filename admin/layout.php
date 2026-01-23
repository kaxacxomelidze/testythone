<?php
// admin/layout.php
require __DIR__ . '/config.php';

$title = $title ?? 'Admin';
$content = $content ?? '';

$role = $_SESSION['admin_role'] ?? 'admin';
$user = $_SESSION['admin_user'] ?? ($_SESSION['admin_name'] ?? 'admin');
$isSuper = ($role === 'super');
?>
<!doctype html>
<html lang="ka">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=h($title)?></title>
  <style>
    :root{
      --bg:#0b1220; --card:#111c33; --line:#1e2a45;
      --txt:#e5e7eb; --muted:#94a3b8; --ac:#2563eb; --bad:#ef4444; --ok:#16a34a;
      --radius:16px;
    }
    *{box-sizing:border-box}
    body{margin:0;background:linear-gradient(180deg,#0b1220,#0a1020);color:var(--txt);font:700 14px/1.5 system-ui}
    a{color:inherit;text-decoration:none}
    .app{display:grid;grid-template-columns:260px 1fr;min-height:100vh}
    .side{
      border-right:1px solid var(--line);
      background:rgba(17,28,51,.55);
      padding:14px;
      position:sticky; top:0; height:100vh;
      backdrop-filter: blur(6px);
    }
    .brand{display:flex;align-items:center;gap:10px;padding:10px;border-radius:14px}
    .logo{width:38px;height:38px;border-radius:12px;background:rgba(37,99,235,.18);border:1px solid rgba(37,99,235,.35)}
    .muted{color:var(--muted);font-weight:900}
    .menu{margin-top:14px;display:flex;flex-direction:column;gap:8px}
    .item{
      display:flex;align-items:center;gap:10px;
      padding:10px 12px;border-radius:14px;border:1px solid transparent;
      color:rgba(229,231,235,.88);
    }
    .item:hover{border-color:rgba(37,99,235,.35);background:rgba(37,99,235,.08)}
    .item.active{background:rgba(37,99,235,.16);border-color:rgba(37,99,235,.35);color:#fff}
    .main{padding:18px}
    .topbar{
      display:flex;justify-content:space-between;align-items:center;
      padding:12px 14px;border:1px solid var(--line);border-radius:var(--radius);
      background:rgba(17,28,51,.55);backdrop-filter: blur(6px);
    }
    .card{
      margin-top:14px;
      background:rgba(17,28,51,.55);
      border:1px solid var(--line);
      border-radius:var(--radius);
      padding:14px;
      box-shadow:0 18px 40px rgba(0,0,0,.25);
    }
    .btn{
      padding:10px 12px;border-radius:12px;border:1px solid var(--line);
      background:rgba(17,28,51,.55);color:var(--txt);cursor:pointer;font-weight:1000;
    }
    .btn.ac{background:rgba(37,99,235,.18);border-color:rgba(37,99,235,.4)}
    .btn.bad{background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.35)}
    .pill{padding:6px 10px;border-radius:999px;border:1px solid var(--line);font-weight:1000}
    @media(max-width:980px){
      .app{grid-template-columns:1fr}
      .side{position:relative;height:auto}
    }
  </style>
</head>
<body>
  <div class="app">

    <aside class="side">
      <div class="brand">
        <div class="logo"></div>
        <div>
          <div style="font-weight:1000">Admin Panel</div>
          <div class="muted"><?=h($user)?> • <?=h($role)?></div>
        </div>
      </div>

      <nav class="menu">
        <a class="item <?=str_ends_with($_SERVER['PHP_SELF'],'/index.php') ? 'active':''?>" href="index.php">
          Slider / Settings
        </a>
<a class="item <?=str_ends_with($_SERVER['PHP_SELF'],'/news.php') ? 'active':''?>" href="news.php">News</a>
<a class="item <?=str_ends_with($_SERVER['PHP_SELF'],'/camps.php') ? 'active':''?>" href="camps.php">Camps</a>

<a class="item <?=str_ends_with($_SERVER['PHP_SELF'],'/camp_applicants.php') ? 'active':''?>" href="camp_applicants.php">
  Camp Applicants
</a>
<a class="item <?=str_ends_with($_SERVER['PHP_SELF'],'/admin_grants.php') ? 'active':''?>" href="admin_grants.php">
  grants 
</a>
        <?php if ($isSuper): ?>
          <a class="item <?=str_ends_with($_SERVER['PHP_SELF'],'/admins.php') ? 'active':''?>" href="admins.php">
            Admins
          </a>

          <!-- ✅ ADMIN LOGS -->
          <a class="item <?=str_ends_with($_SERVER['PHP_SELF'],'/admin_logs.php') ? 'active':''?>" href="admin_logs.php">
            Admin Logs
          </a>
        <?php endif; ?>

        <a class="item" href="logout.php">Logout</a>
      </nav>
    </aside>

    <main class="main">
      <div class="topbar">
        <div><?=h($title)?></div>
        <span class="pill"><?= $isSuper ? 'SUPER ADMIN' : 'ADMIN' ?></span>
      </div>

      <?= $content ?>
    </main>

  </div>
</body>
</html>
