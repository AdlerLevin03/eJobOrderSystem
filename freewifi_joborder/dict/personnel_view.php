<?php
require_once '../config/connection.php';

// Initialize variables
$personnel = [];
$department_name = '';
$position_name = '';
$error_message = '';

// Get personnel ID from URL
$personnel_id = $_GET['id'] ?? '';

if (empty($personnel_id)) {
    header('Location: personnel_index.php');
    exit;
}

// Fetch personnel details with department and position info
try {
    $query = "SELECT p.*, d.department_name, pos.position_name 
              FROM personnels p 
              LEFT JOIN departments d ON p.department_id = d.id 
              LEFT JOIN positions pos ON p.position_id = pos.id 
              WHERE p.id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$personnel_id]);
    $personnel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$personnel) {
        $error_message = 'Personnel record not found.';
    } else {
        $department_name = $personnel['department_name'] ?? 'N/A';
        $position_name = $personnel['position_name'] ?? 'N/A';
    }
} catch (PDOException $e) {
    $error_message = 'Error fetching personnel: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Personnel - Free WiFi Job Order System</title>
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

        .personnel-card {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .photo-section {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .photo-container {
            width: 200px;
            height: 200px;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid var(--border-color);
            margin-bottom: 20px;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
        }

        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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

        .full-name-section {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            color: white;
            padding: 20px 24px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.15);
            text-align: center;
        }

        .full-name-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .full-name-value {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
            flex-wrap: wrap;
            padding-top: 25px;
            border-top: 2px solid var(--border-color);
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

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-secondary {
            background-color: var(--text-secondary);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #4b5563;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }

        /* Confirmation Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 90%;
            text-align: center;
            animation: slideUp 0.3s ease;
        }

        .modal-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--danger-color);
            margin-bottom: 12px;
        }

        .modal-message {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .modal-button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .modal-button-confirm {
            background-color: var(--danger-color);
            color: white;
        }

        .modal-button-confirm:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
        }

        .modal-button-cancel {
            background-color: var(--border-color);
            color: var(--text-secondary);
        }

        .modal-button-cancel:hover {
            background-color: #d1d5db;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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

            .personnel-card {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .photo-container {
                width: 150px;
                height: 150px;
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

            .photo-container {
                width: 120px;
                height: 120px;
                font-size: 60px;
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
                <h1>Personnel Details</h1>
                <p>View comprehensive personnel information</p>
            </div>

            <!-- Error Message -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    ✗ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Personnel Details -->
            <?php if (!empty($personnel)): ?>
                <div class="personnel-card">
                    <!-- Photo Section -->
                    <div class="photo-section">
                        <div class="photo-container">
                            <?php if (!empty($personnel['photo'])): ?>
                                <?php
                                    $imgInfo = @getimagesizefromstring($personnel['photo']);
                                    $mime = $imgInfo['mime'] ?? 'image/jpeg';
                                ?>
                                <img src="data:<?php echo htmlspecialchars($mime); ?>;base64,<?php echo base64_encode($personnel['photo']); ?>" alt="Personnel Photo">
                            <?php else: ?>
                                👤
                            <?php endif; ?>
                        </div>
                        <div class="status-badge <?php echo strtolower($personnel['status']); ?>">
                            <?php echo htmlspecialchars($personnel['status']); ?>
                        </div>
                    </div>

                    <!-- Info Section -->
                    <div class="info-section">
                        <!-- Full Name Section -->
                        <div class="full-name-section">
                            <div class="full-name-value"><?php 
                                $fullName = '';
                                if (!empty($personnel['prefix'])) $fullName .= $personnel['prefix'] . ' ';
                                if (!empty($personnel['fname'])) $fullName .= $personnel['fname'] . ' ';
                                if (!empty($personnel['mname'])) $fullName .= $personnel['mname'] . ' ';
                                if (!empty($personnel['lname'])) $fullName .= $personnel['lname'] . ' ';
                                if (!empty($personnel['suffix'])) $fullName .= $personnel['suffix'];
                                echo htmlspecialchars(trim($fullName));
                            ?></div>
                        </div>

                        <!-- Personal Information -->
                        <div>
                            <div class="section-title">👤 Personal Information</div>
                            <div class="info-group">
                                <div class="info-item">
                                    <div class="info-label">Prefix</div>
                                    <div class="info-value"><?php echo !empty($personnel['prefix']) ? htmlspecialchars($personnel['prefix']) : 'N/A'; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">First Name</div>
                                    <div class="info-value"><?php echo htmlspecialchars($personnel['fname']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Last Name</div>
                                    <div class="info-value"><?php echo htmlspecialchars($personnel['lname']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Middle Name</div>
                                    <div class="info-value"><?php echo !empty($personnel['mname']) ? htmlspecialchars($personnel['mname']) : 'N/A'; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Suffix</div>
                                    <div class="info-value"><?php echo !empty($personnel['suffix']) ? htmlspecialchars($personnel['suffix']) : 'N/A'; ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div>
                            <div class="section-title">📞 Contact Information</div>
                            <div class="info-group full">
                                <div class="info-item">
                                    <div class="info-label">Email Address</div>
                                    <div class="info-value"><a href="mailto:<?php echo htmlspecialchars($personnel['gmail']); ?>" style="color: var(--primary-color); text-decoration: none;"><?php echo htmlspecialchars($personnel['gmail']); ?></a></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Contact Number</div>
                                    <div class="info-value"><a href="tel:<?php echo htmlspecialchars($personnel['cpno']); ?>" style="color: var(--primary-color); text-decoration: none;"><?php echo htmlspecialchars($personnel['cpno']); ?></a></div>
                                </div>
                            </div>
                        </div>

                        <!-- Department & Position -->
                        <div>
                            <div class="section-title">🏢 Department & Position</div>
                            <div class="info-group">
                                <div class="info-item">
                                    <div class="info-label">Department</div>
                                    <div class="info-value"><?php echo htmlspecialchars($department_name); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Position</div>
                                    <div class="info-value"><?php echo htmlspecialchars($position_name); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="form-actions">
                    <a href="personnel_index.php" class="btn btn-secondary">← Back to List</a>
                    <a href="personnel_edit.php?id=<?php echo htmlspecialchars($personnel['id']); ?>" class="btn btn-primary">✏️ Edit Personnel</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include '../includes/footer.php'; ?>


</body>
</html>
