<?php
require_once '../config/connection.php';

// Get client ID from query parameter
$client_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$client_id) {
    header('Location: index.php');
    exit;
}

// Fetch client details
try {
    $query = "SELECT * FROM clients WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();

    if (!$client) {
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    echo "Error fetching client: " . $e->getMessage();
    exit;
}

// Function to format client full name
function getFullName($prefix, $fname, $mname, $lname, $suffix) {
    $name = '';
    if (!empty($prefix)) $name .= $prefix . ' ';
    if (!empty($fname)) $name .= $fname . ' ';
    if (!empty($mname)) $name .= $mname . ' ';
    if (!empty($lname)) $name .= $lname . ' ';
    if (!empty($suffix)) $name .= $suffix;
    return trim($name);
}

$fullName = getFullName($client['prefix'] ?? '', $client['fname'] ?? '', $client['mname'] ?? '', $client['lname'] ?? '', $client['suffix'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Client - Free WiFi Job Order System</title>
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

        .client-card {
            background: linear-gradient(135deg, #f0f9ff 0%, #f0f4ff 100%);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 30px;
        }

        .client-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 25px;
            border-bottom: 2px solid var(--border-color);
        }

        .client-name-display {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 10px;
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

        .contact-value {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
        }

        .contact-value:hover {
            text-decoration: underline;
            color: var(--primary-color);
        }

        .full-width {
            grid-column: 1 / -1;
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

        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
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

            .client-card {
                padding: 25px;
            }

            .client-name-display {
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

            .client-card {
                padding: 20px;
            }

            .client-name-display {
                font-size: 20px;
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
                <h1>Client Details</h1>
                <p>View complete client information</p>
            </div>

            <!-- Client Card -->
            <div class="client-card">
                <!-- Client Header -->
                <div class="client-header">
                    <div class="client-name-display"><?php echo htmlspecialchars($fullName); ?></div>
                </div>

                <!-- Personal Information Section -->
                <div class="info-section">
                    <div class="section-title">📋 Personal Information</div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Prefix</div>
                            <div class="info-value <?php echo empty($client['prefix']) ? 'empty' : ''; ?>">
                                <?php echo !empty($client['prefix']) ? htmlspecialchars($client['prefix']) : 'N/A'; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">First Name</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($client['fname'] ?? '-'); ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Middle Name</div>
                            <div class="info-value <?php echo empty($client['mname']) ? 'empty' : ''; ?>">
                                <?php echo !empty($client['mname']) ? htmlspecialchars($client['mname']) : 'N/A'; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Last Name</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($client['lname'] ?? '-'); ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Suffix</div>
                            <div class="info-value <?php echo empty($client['suffix']) ? 'empty' : ''; ?>">
                                <?php echo !empty($client['suffix']) ? htmlspecialchars($client['suffix']) : 'N/A'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Organization Information Section -->
                <div class="info-section">
                    <div class="section-title">🏢 Organization Information</div>
                    <div class="info-grid">
                        <div class="info-item full-width">
                            <div class="info-label">Agency</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($client['agency'] ?? '-'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="info-section">
                    <div class="section-title">📞 Contact Information</div>
                    <div class="info-grid">
                        <div class="info-item full-width">
                            <div class="info-label">Contact Number</div>
                            <div class="info-value">
                                <?php if (!empty($client['cno'])): ?>
                                    <a href="tel:<?php echo htmlspecialchars($client['cno']); ?>" class="contact-value">
                                        <?php echo htmlspecialchars($client['cno']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="empty">N/A</span>
                                <?php endif; ?>
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
                                <span class="status-badge">Active</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="index.php" class="btn btn-back">← Back to Clients</a>
                <button onclick="editClient(<?php echo $client['id']; ?>)" class="btn btn-edit">✎ Edit Client</button>
                
            </div>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include '../includes/footer.php'; ?>

    <script>
        function editClient(clientId) {
            // Redirect to edit page (to be implemented)
            window.location.href = `edit.php?id=${clientId}`;
        }
    </script>
</body>
</html>
