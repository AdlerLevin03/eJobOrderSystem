<?php
require_once '../config/connection.php';

// Get department ID from query parameter
$department_id = isset($_GET['view']) ? intval($_GET['view']) : null;

if (!$department_id) {
    header('Location: department_index.php');
    exit;
}

// Fetch department details
try {
    $query = "SELECT * FROM departments WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$department_id]);
    $department = $stmt->fetch();

    if (!$department) {
        header('Location: department_index.php');
        exit;
    }
} catch (PDOException $e) {
    echo "Error fetching department: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Department - Free WiFi Job Order System</title>
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
            --background-color: #f9fafb;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background-color: var(--background-color);
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
            padding: 50px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .container {
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            padding: 50px;
            overflow-x: auto;
        }

        .page-header {
            margin-bottom: 40px;
            text-align: center;
            border-bottom: 4px solid var(--accent-color);
            padding-bottom: 25px;
        }

        .page-header h1 {
            font-size: 36px;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .page-header p {
            color: var(--text-secondary);
            font-size: 15px;
            font-weight: 500;
        }

        .department-card {
            background: linear-gradient(135deg, #f0f9ff 0%, #f0f4ff 100%);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 30px;
        }

        .department-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 25px;
            border-bottom: 2px solid var(--border-color);
        }

        .department-name-display {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .department-code-display {
            font-size: 14px;
            font-weight: 700;
            color: var(--accent-color);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-section {
            margin-bottom: 35px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary-color);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent-color);
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .info-item {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--accent-color);
            transition: all 0.3s ease;
        }

        .info-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .info-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 8px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            word-break: break-word;
        }

        .info-value.empty {
            color: var(--text-secondary);
            font-style: italic;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            background-color: #dcfce7;
            color: var(--success-color);
        }

        .status-badge.inactive {
            background-color: #fee2e2;
            color: var(--danger-color);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back {
            background-color: var(--text-secondary);
            color: white;
        }

        .btn-back:hover {
            background-color: #4b5563;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }

        .btn-edit {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-edit:hover {
            background-color: #d97706;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            main {
                padding: 30px 15px;
            }

            .container {
                padding: 30px 20px;
            }

            .page-header h1 {
                font-size: 28px;
            }

            .department-card {
                padding: 25px;
            }

            .department-name-display {
                font-size: 24px;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .info-item {
                padding: 15px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            main {
                padding: 20px 10px;
            }

            .container {
                padding: 20px 15px;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .page-header {
                margin-bottom: 30px;
                padding-bottom: 20px;
            }

            .department-card {
                padding: 20px;
            }

            .department-name-display {
                font-size: 20px;
            }

            .department-code-display {
                font-size: 12px;
            }

            .section-title {
                font-size: 14px;
                margin-bottom: 15px;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .info-item {
                padding: 12px;
                border-left-width: 3px;
            }

            .info-label {
                font-size: 11px;
            }

            .info-value {
                font-size: 14px;
            }

            .btn {
                padding: 10px 20px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include '../includes/header.php'; ?>

    <!-- Main Content -->
    <main>
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Department Details</h1>
                <p>View complete department information</p>
            </div>

            <!-- Department Card -->
            <div class="department-card">
                <!-- Department Header -->
                <div class="department-header">
                    <div class="department-name-display"><?php echo htmlspecialchars($department['department_name']); ?></div>
                    <div class="department-code-display"><?php echo htmlspecialchars($department['department_code']); ?></div>
                </div>

                <!-- Department Information Section -->
                <div class="info-section">
                    <div class="section-title">🏢 Department Information</div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Department Code</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($department['department_code']); ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Department Name</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($department['department_name']); ?>
                            </div>
                        </div>
                        <div class="info-item full-width">
                            <div class="info-label">Description</div>
                            <div class="info-value <?php echo empty($department['description']) ? 'empty' : ''; ?>">
                                <?php echo !empty($department['description']) ? htmlspecialchars($department['description']) : 'N/A'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Section -->
                <div class="info-section">
                    <div class="section-title">✓ Status</div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="status-badge <?php echo $department['status'] === 'Inactive' ? 'inactive' : ''; ?>">
                                    <?php echo htmlspecialchars($department['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="department_index.php" class="btn btn-back">← Back to Departments</a>
                <a href="department_edit.php?id=<?php echo htmlspecialchars($department['id']); ?>" class="btn btn-edit">✎ Edit Department</a>
            </div>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>
