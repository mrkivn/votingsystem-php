<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting System</title>
    <link rel="stylesheet" href="/public/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <header class="main-header">
            <div class="logo">VOTE<span class="highlight">BOX</span></div>
            <nav>
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="/dashboard">Dashboard</a>
                    <a href="/logout" class="btn-secondary">Logout</a>
                <?php else: ?>
                    <a href="/">Login</a>
                    <a href="/register">Register</a>
                <?php endif; ?>
            </nav>
        </header>
        
        <main class="content-wrapper">
            <?php echo $content; ?>
        </main>

        <footer class="main-footer">
            <p>&copy; <?php echo date('Y'); ?> Voting System. Built with PHP.</p>
        </footer>
    </div>
</body>
</html>
