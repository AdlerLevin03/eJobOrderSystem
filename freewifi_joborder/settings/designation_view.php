<?php
require_once '../config/connection.php';

// Initialize variables
$designation_id = $_GET['id'] ?? '';
$designation = [];
$error_message = '';

if (empty($designation_id)) {
    header('Location: designation_index.php');
    exit;
}

// Fetch designation details
try {
    $query = "SELECT id, designation_code, designation_name, description, status 
              FROM designations 
              WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$designation_id]);
    $designation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$designation) {
        $error_message = 'Designation not found.';
    }
} catch (PDOException $e) {
    $error_message = 'Error fetching designation: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Designation - Free WiFi Job Order System</title>
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
        }

        .page-header {
            margin-bottom: 40px;
            text-align: center;
            border-bottom: 4px solid var(--accent-color);
            padding-bottom: 25px;
        }

        .page-header h1 {
            font-size: 32px;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
            letter-spacing: -0.5px;
        }

        .page-header p {
            color: var(--text-secondary);
            font-size: 15px;
            font-weight: 500;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 600;
            animation: slideDown 0.3s ease;
        }

        .alert-error {
            background-color: #fee2e2;
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .info-section {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary-color);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent-color);
        }

        .info-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .info-group.full {
            grid-column: 1 / -1;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 6px;
        }

        .info-value {
            font-size: 15px;
            color: var(--text-primary);
            font-weight: 500;
        }

        .designation-name-section {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            color: white;
            padding: 20px 24px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.15);
            text-align: center;
        }

        .designation-name-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .designation-name-value {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.active {
            background-color: #dcfce7;
            color: var(--success-color);
        }

        .status-badge.inactive {
            background-color: #fee2e2;
            color: var(--danger-color);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid var(--border-color);
        }

        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-edit {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-edit:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }

        .btn-cancel {
            background-color: var(--text-secondary);
            color: white;
        }

        .btn-cancel:hover {
            background-color: #4b5563;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
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

            .info-group {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .form-actions {
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

            .section-title {
                font-size: 14px;
                margin-bottom: 12px;
            }

            .info-group {
                grid-template-columns: 1fr;
                gap: 15px;
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
                <h1>Designation Details</h1>
                <p>View designation information</p>
            </div>

            <!-- Error Message -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    ✗ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Designation Details -->
            <?php if (!empty($designation)): ?>
                <!-- Designation Name Section -->
                <div class="designation-name-section">
                    <div class="designation-name-label">Designation</div>
                    <div class="designation-name-value"><?php echo htmlspecialchars($designation['designation_name']); ?></div>
                </div>

                <!-- Designation Information -->
                <div class="info-section">
                    <div>
                        <div class="section-title">📋 Designation Information</div>
                        <div class="info-group">
                            <div class="info-item">
                                <div class="info-label">Designation Code</div>
                                <div class="info-value"><?php echo htmlspecialchars($designation['designation_code']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Status</div>
                                <div class="info-value">
                                    <span class="status-badge <?php echo strtolower($designation['status']); ?>">
                                        <?php echo htmlspecialchars($designation['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <?php if (!empty($designation['description'])): ?>
                        <div>
                            <div class="section-title">📝 Description</div>
                            <div class="info-item">
                                <div class="info-value"><?php echo htmlspecialchars($designation['description']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div class="form-actions">
                    <a href="designation_index.php" class="btn btn-cancel">← Back to List</a>
                    <a href="designation_edit.php?id=<?php echo htmlspecialchars($designation['id']); ?>" class="btn btn-edit">✏️ Edit Designation</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>
