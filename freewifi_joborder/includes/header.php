<?php
// Get the current page to set active nav item
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch departments for dropdown
$navbar_departments = [];
try {
    require_once __DIR__ . '/../config/connection.php';
    $query = "SELECT id,department_code, department_name, description, status FROM departments ORDER BY department_name ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $navbar_departments  = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $navbar_departments  = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Free WiFi Job Order System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #1e40af;
            --primary-dark: #1e3a8a;
            --primary-light: #3b82f6;
            --accent-color: #0891b2;
            --background-dark: #1f2937;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
        }

        /* Navigation Header */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 10px;
            height: 80px;
        }

        /* Logo and Branding */
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0;
            text-decoration: none;
            color: white;
            font-weight: 700;
            font-size: 18px;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .navbar-brand:hover {
            opacity: 0.95;
            transform: scale(1.02);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 15px 0 0;
            border-right: 2px solid rgba(255, 255, 255, 0.2);
            margin-right: 25px;
        }

        .logo-img {
            height: 60px;
            width: auto;
            display: block;
            transition: transform 0.3s ease;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .navbar-brand:hover .logo-img {
            transform: translateY(-2px);
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            gap: 1px;
            justify-content: center;
        }

        .brand-main {
            font-size: 18px;
            font-weight: 800;
            color: white;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            line-height: 1.1;
        }

        .brand-subtitle {
            font-size: 10px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.85);
            letter-spacing: 0.4px;
            text-transform: uppercase;
            line-height: 1;
        }

        /* Navigation Menu */
        .navbar-menu {
            display: flex;
            list-style: none;
            gap: 0;
            align-items: center;
            flex: 1;
        }

        .nav-item {
            position: relative;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 20px 18px;
            color: rgba(255, 255, 255, 0.95);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            height: 80px;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-bottom-color: var(--accent-color);
            color: white;
        }

        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
            border-bottom-color: var(--accent-color);
            color: white;
        }

        .nav-link i {
            margin-right: 6px;
        }

        /* Dropdown Menu */
        .nav-dropdown {
            position: relative;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 240px;
            border-radius: 12px;
            box-shadow: 0 10px 32px rgba(0, 0, 0, 0.12);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-12px);
            transition: all 0.3s ease;
            list-style: none;
            overflow: hidden;
            margin-top: 8px;
            border: 1px solid #e5e7eb;
        }

        .nav-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            padding: 14px 20px;
            color: var(--text-primary);
            text-decoration: none;
            display: block;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            font-size: 14px;
            font-weight: 500;
        }

        .dropdown-item:hover {
            background-color: #f0f4ff;
            border-left-color: var(--primary-color);
            padding-left: 24px;
            color: var(--primary-color);
        }

        .dropdown-item.disabled-item {
            color: #b0b0b0;
            cursor: not-allowed;
            opacity: 0.6;
            pointer-events: none;
        }

        .dropdown-item.disabled-item:hover {
            background-color: transparent;
            border-left-color: transparent;
            padding-left: 20px;
            color: #b0b0b0;
        }

        .dropdown-header {
            padding: 14px 20px 10px 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-secondary);
            background: linear-gradient(135deg, #70b1f1 0%, #f3f4f6 100%);
            border-bottom: 2px solid #e5e7eb;
        }

        /* Right Side Icons/User Menu */
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-icon-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .nav-icon-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        /* Tagline Section */
        .navbar-tagline {
            background-color: var(--background-dark);
            padding: 8px 0;
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .tagline-text {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar-container {
                height: 70px;
                padding: 0 10px;
            }

            .logo-container {
                gap: 6px;
                padding: 0 12px 0 0;
                margin-right: 15px;
            }

            .logo-img {
                height: 45px;
            }

            .brand-main {
                font-size: 15px;
            }

            .brand-subtitle {
                font-size: 9px;
            }

            .navbar-menu {
                display: none;
                position: absolute;
                top: 70px;
                left: 0;
                right: 0;
                flex-direction: column;
                background: var(--primary-color);
                gap: 0;
                border-top: 2px solid var(--accent-color);
                max-height: calc(100vh - 70px);
                overflow-y: auto;
            }

            .navbar-menu.active {
                display: flex;
            }

            .nav-item {
                width: 100%;
            }

            .nav-link {
                padding: 15px 20px;
                height: auto;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }

            .nav-link.active {
                background-color: rgba(255, 255, 255, 0.2);
                border-bottom: 1px solid var(--accent-color);
                border-left: 3px solid var(--accent-color);
            }

            .dropdown-menu {
                position: static;
                background: rgba(255, 255, 255, 0.1);
                opacity: 0;
                max-height: 0;
                visibility: hidden;
                transform: none;
                transition: all 0.3s ease;
                border-radius: 0;
                box-shadow: none;
                overflow: hidden;
                border: none;
                margin-top: 0;
            }

            .nav-dropdown.active .dropdown-menu {
                opacity: 1;
                visibility: visible;
                max-height: 500px;
            }

            .dropdown-item {
                padding-left: 40px;
                border-left: none;
                color: rgba(255, 255, 255, 0.9);
                font-weight: 500;
            }

            .dropdown-item:hover {
                background-color: rgba(255, 255, 255, 0.15);
                border-left: none;
                padding-left: 40px;
                color: white;
            }

            .dropdown-header {
                padding: 14px 20px 10px 20px;
                color: rgba(255, 255, 255, 0.7);
                background: transparent;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }

            .mobile-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .brand-text {
                display: none;
            }

            .navbar-right {
                gap: 10px;
            }
        }

        @media (max-width: 480px) {
            .navbar-container {
                height: 65px;
                padding: 0 8px;
            }

            .logo-container {
                gap: 4px;
                padding: 0 10px 0 0;
                margin-right: 10px;
            }

            .logo-img {
                height: 38px;
            }

            .brand-main {
                font-size: 12px;
                letter-spacing: 0.3px;
            }

            .brand-subtitle {
                font-size: 8px;
            }

            .navbar-right {
                gap: 8px;
            }

            .nav-icon-btn {
                width: 36px;
                height: 36px;
                font-size: 16px;
            }

            .navbar-tagline {
                font-size: 10px;
            }

            .tagline-text {
                padding: 0 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Main Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-container">
            <!-- Logo and Brand -->
            <a href="index.php" class="navbar-brand">
                <div class="logo-container">
                    <img src="../assets/bagongpilipinas.png" alt="Bagong Pilipinas Logo" class="logo-img">
                    <img src="../assets/dict_logo.png" alt="DICT Logo" class="logo-img">
                    <img src="../assets/freewifilogo.png" alt="Free WiFi Logo" class="logo-img">
                    <div class="brand-text">
                        <span class="brand-main">Free WiFi</span>
                        <span class="brand-subtitle">Job Order Form System</span>
                    </div>
                </div>
            </a>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                ☰
            </button>

            <!-- Navigation Menu -->
            <ul class="navbar-menu" id="navbarMenu">
                <li class="nav-item">
                    <a href="../index.php" class="nav-link <?php echo ($current_page === 'index.php' && strpos($_SERVER['REQUEST_URI'], 'clients') === false && strpos($_SERVER['REQUEST_URI'], 'personnel') === false && strpos($_SERVER['REQUEST_URI'], 'settings') === false) ? 'active' : ''; ?>">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../clients/index.php" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/clients/') !== false) ? 'active' : ''; ?>">
                        Clients
                    </a>
                </li>
                <li class="nav-item nav-dropdown" id="dictDropdown">
                    <a href="#" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/personnels/') !== false || strpos($_SERVER['REQUEST_URI'], '/dict/') !== false) ? 'active' : ''; ?>">
                        DICT ▼
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Personnel Management</li>
                        <li><a href="../dict/personnel_index.php" class="dropdown-item">Personnel</a></li>
                        <li><a href="../dict/personneldesignation_index.php" class="dropdown-item">Designation</a></li>
                    </ul>
                </li>

                <!-- <li class="nav-item nav-dropdown" id="departmentsDropdown">
                    <a href="#" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '#') !== false) ? 'active' : ''; ?>">
                       Departments ▼
                    </a>
                    <ul class="dropdown-menu">
                        <?php if (!empty($navbar_departments )): ?>
                            <?php foreach ($navbar_departments  as $dept): ?>
                                <?php if ($dept['status'] === 'Active'): ?>
                                    <li><a href="#" class="dropdown-item"><?php echo htmlspecialchars($dept['department_name']); ?></a></li>
                                <?php else: ?>
                                    <li><a href="#" class="dropdown-item disabled-item" onclick="return false;" title="Inactive Department"><?php echo htmlspecialchars($dept['department_name']); ?> (Inactive)</a></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </li> -->

                <li class="nav-item nav-dropdown" id="jobOrderDropdown">
                    <a href="#" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/joborders/') !== false) ? 'active' : ''; ?>">
                        Job Orders ▼
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Job Order Management</li>
                        <li><a href="../joborders/add.php" class="dropdown-item">Create New Job Order</a></li>
                        <li><a href="../joborders/assign_performer.php" class="dropdown-item">Assign Performer</a></a></li>
                        <li><a href="#" class="dropdown-item">View Job Orders</a></li>
                        <li><a href="#" class="dropdown-item">Print Job Orders</a></li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="#" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/users/') !== false) ? 'active' : ''; ?>">
                        Users Management
                    </a>
                </li>

                <li class="nav-item nav-dropdown" id="reportsDropdown">
                    <a href="#" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/reports/') !== false) ? 'active' : ''; ?>">
                        Reports ▼
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="#" class="dropdown-item">Monthly Reports</a></li>
                        <li><a href="#" class="dropdown-item">Client Summary</a></li>
                        <li><a href="#" class="dropdown-item">Completion Statistics</a></li>
                    </ul>
                </li>
                
                <li class="nav-item nav-dropdown" id="settingsDropdown">
                    <a href="#" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/settings/') !== false) ? 'active' : ''; ?>">
                        Settings ▼
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Organization Structure</li>
                        <li><a href="../settings/department_index.php" class="dropdown-item">Department</a></li>
                        <li><a href="../settings/position_index.php" class="dropdown-item">Position</a></li>
                        <li><a href="../settings/designation_index.php" class="dropdown-item">Designation</a></li>
                        <!-- <li><a href="../settings/positionlevels_index.php" class="dropdown-item">Position Level</a></li> -->

                        <!-- <li class="dropdown-header">JO Setting</li>
                        <li><a href="../settings/#" class="dropdown-item">Mode of Request</a></li> -->
                    </ul>
                </li>
            </ul>

            <!-- Right Side - User Menu -->
            <div class="navbar-right">
                <button class="nav-icon-btn" title="Notifications">
                    🔔
                </button>
                <button class="nav-icon-btn" title="User Profile">
                    👤
                </button>
            </div>
        </div>
    </nav>

    

    <script>
        // Mobile menu toggle functionality
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navbarMenu = document.getElementById('navbarMenu');
        const jobOrderDropdown = document.getElementById('jobOrderDropdown');
        const reportsDropdown = document.getElementById('reportsDropdown');

        // Toggle mobile menu
        mobileMenuBtn.addEventListener('click', function() {
            navbarMenu.classList.toggle('active');
        });

        // Close mobile menu when a nav link is clicked
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    navbarMenu.classList.remove('active');
                }
            });
        });

        // Mobile dropdown functionality
        if (window.innerWidth <= 768) {
            const dropdownToggles = document.querySelectorAll('.nav-dropdown > .nav-link');
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const parent = this.parentElement;
                    parent.classList.toggle('active');
                });
            });
        }

        // Adjust mobile menu on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                navbarMenu.classList.remove('active');
            }
        });
    </script>
</body>
</html>
