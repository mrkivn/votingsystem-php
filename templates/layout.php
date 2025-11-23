<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting System</title>
    <style>
        :root {
            --bg-color: #f4f3f0;
            --text-color: #1a1a1a;
            --primary-color: #ff4d4d; /* Vibrant Red */
            --secondary-color: #1a1a1a;
            --accent-color: #4d79ff; /* Blue */
            --border-color: #1a1a1a;
            --card-bg: #ffffff;
            --shadow: 4px 4px 0px 0px #1a1a1a;
            --font-main: 'Space Grotesk', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: var(--font-main);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .app-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 40px;
        }

        .logo {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -1px;
        }

        .highlight {
            color: var(--primary-color);
        }

        nav a {
            margin-left: 20px;
            font-weight: 500;
            position: relative;
        }

        nav a:hover {
            color: var(--primary-color);
        }

        .btn-secondary {
            border: 2px solid var(--border-color);
            padding: 8px 16px;
            border-radius: 0;
            background: transparent;
            transition: all 0.2s;
        }

        .btn-secondary:hover {
            background: var(--border-color);
            color: #fff;
        }

        .content-wrapper {
            flex: 1;
        }

        /* Neo-Brutalist Card Style */
        .card {
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0px 0px var(--border-color);
        }

        h1, h2, h3 {
            margin-bottom: 1rem;
            font-weight: 700;
        }

        input, button, select {
            font-family: var(--font-main);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            background: #fff;
            font-size: 1rem;
            outline: none;
        }

        input:focus {
            border-color: var(--primary-color);
        }

        .btn-primary {
            background: var(--primary-color);
            color: #fff;
            border: 2px solid var(--border-color);
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: all 0.2s;
        }

        .btn-primary:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0px 0px var(--border-color);
        }

        .main-footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 2px solid var(--border-color);
            text-align: center;
            font-size: 0.9rem;
            opacity: 0.7;
        }
    </style>
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
