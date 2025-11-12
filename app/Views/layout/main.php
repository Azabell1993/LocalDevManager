<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? '로컬용 개발 관리 프로그램' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/app.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?= Csrf::generate() ?>">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <h1>사설망 개발 관리 프로그램</h1>
            <div class="subtitle">프로젝트 및 데이터베이스 관리</div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="nav">
        <div class="container">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="/dashboard" class="nav-link <?= Helpers::isActiveRoute('/dashboard', '/') ? 'active' : '' ?>">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/projects" class="nav-link <?= Helpers::isActiveRoute('/projects') ? 'active' : '' ?>">
                        Projects
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/os" class="nav-link <?= Helpers::isActiveRoute('/os') ? 'active' : '' ?>">
                        OS List
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/agents" class="nav-link <?= Helpers::isActiveRoute('/agents') ? 'active' : '' ?>">
                        Agents
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/scans" class="nav-link <?= Helpers::isActiveRoute('/scans') ? 'active' : '' ?>">
                        Scan History
                    </a>
                </li>

                <li class="nav-item">
                    <a href="/db" class="nav-link <?= Helpers::isActiveRoute('/db') ? 'active' : '' ?>">
                        DB Admin
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main">
        <div class="container">
            <!-- Flash Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error'] ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Page Content -->
            <?= $content ?>
        </div>
    </main>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript -->
    <script src="/assets/js/app.js"></script>
    
    <!-- Page specific scripts -->
    <?php if (isset($scripts)): ?>
        <?= $scripts ?>
    <?php endif; ?>
</body>
</html>