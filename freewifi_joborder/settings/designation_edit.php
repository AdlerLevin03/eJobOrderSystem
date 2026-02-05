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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message)) {
    $designation_code = $_POST['designation_code'] ?? '';
    $designation_name = $_POST['designation_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'Active';

    // Validate input
    if (empty($designation_code) || empty($designation_name)) {
        $error_message = 'Designation code and name are required.';
    } else if (strlen($designation_code) > 50) {
        $error_message = 'Designation code must not exceed 50 characters.';
    } else if (strlen($designation_name) > 100) {
        $error_message = 'Designation name must not exceed 100 characters.';
    } else if (strlen($description) > 500) {
        $error_message = 'Description must not exceed 500 characters.';
    } else {
        // Check for duplicate code (excluding current record)
        try {
            $check_query = "SELECT id FROM designations WHERE designation_code = ? AND id != ?";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->execute([$designation_code, $designation_id]);
            
            if ($check_stmt->rowCount() > 0) {
                $error_message = 'A designation with this code already exists.';
            } else {
                // Update database
                $update_query = "UPDATE designations 
                                SET designation_code = ?, designation_name = ?, description = ?, status = ? 
                                WHERE id = ?";
                $update_stmt = $pdo->prepare($update_query);
                $update_stmt->execute([$designation_code, $designation_name, $description, $status, $designation_id]);
                
                header('Location: designation_index.php');
                exit;
            }
        } catch (PDOException $e) {
            $error_message = 'Error updating designation: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Designation - Free WiFi Job Order System</title>
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
            display: flex;
            align-items: center;
            gap: 10px;
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

        .form-section {
            margin-bottom: 35px;
            padding-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }

        .form-section:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .form-group {
            margin-bottom: 0;
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
            text-transform: none;
            letter-spacing: 0;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            color: var(--text-primary);
            transition: all 0.3s ease;
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 1);
        }

        .form-input:hover,
        .form-textarea:hover,
        .form-select:hover {
            border-color: var(--primary-light);
            box-shadow: 0 4px 8px rgba(30, 64, 175, 0.1);
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: white;
            box-shadow: 0 0 0 4px rgba(30, 64, 175, 0.15), inset 0 0 0 1px rgba(30, 64, 175, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .char-counter {
            font-size: 13px;
            color: var(--primary-color);
            font-weight: 600;
            margin-top: 8px;
            padding: 6px 10px;
            background-color: #dbeafe;
            border-radius: 6px;
            display: inline-block;
            border-left: 3px solid var(--primary-color);
        }

        .char-counter.success {
            background-color: #dcfce7;
            color: var(--success-color);
            border-left-color: var(--success-color);
        }

        .char-counter.warning {
            background-color: #fef3c7;
            color: #d97706;
            border-left-color: #d97706;
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

        .btn-submit {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-submit:hover {
            background-color: #d97706;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
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

            .form-label {
                font-size: 13px;
            }

            .form-input,
            .form-textarea,
            .form-select {
                font-size: 16px;
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
                <h1>Edit Designation</h1>
                <p>Update designation information</p>
            </div>

            <!-- Error Message -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    ✗ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Edit Designation Form -->
            <?php if (!empty($designation)): ?>
                <form method="POST" action="">
                    <!-- Designation Information Section -->
                    <div class="form-section">
                        <div class="section-title">💼 Designation Information</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="designation_code" class="form-label">Designation Code *</label>
                                <input 
                                    type="text" 
                                    id="designation_code" 
                                    name="designation_code" 
                                    class="form-input"
                                    placeholder="e.g., ISR, EDR, APR"
                                    maxlength="50"
                                    value="<?php echo htmlspecialchars($designation['designation_code']); ?>"
                                    required
                                    onkeyup="updateCounter('designation_code', 50)"
                                >
                                <div class="char-counter" id="designation_code_counter"><?php echo strlen($designation['designation_code']); ?>/50 characters</div>
                            </div>

                            <div class="form-group">
                                <label for="designation_name" class="form-label">Designation Name *</label>
                                <input 
                                    type="text" 
                                    id="designation_name" 
                                    name="designation_name" 
                                    class="form-input"
                                    placeholder="e.g., Issuer, Endorser, Approver"
                                    maxlength="100"
                                    value="<?php echo htmlspecialchars($designation['designation_name']); ?>"
                                    required
                                    onkeyup="updateCounter('designation_name', 100)"
                                >
                                <div class="char-counter" id="designation_name_counter"><?php echo strlen($designation['designation_name']); ?>/100 characters</div>
                            </div>
                        </div>
                    </div>

                    <!-- Description and Status Section -->
                    <div class="form-section">
                        <div class="section-title">📝 Additional Information</div>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="description" class="form-label">Description</label>
                                <textarea 
                                    id="description" 
                                    name="description" 
                                    class="form-textarea"
                                    placeholder="Enter a detailed description of this designation..."
                                    maxlength="255"
                                    onkeyup="updateCounter('description', 255)"
                                ><?php echo htmlspecialchars($designation['description'] ?? ''); ?></textarea>
                                <div class="char-counter" id="description_counter"><?php echo strlen($designation['description'] ?? ''); ?>/255 characters</div>
                            </div>

                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="Active" <?php echo ($designation['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo ($designation['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="designation_index.php" class="btn btn-cancel">← Cancel</a>
                        <button type="submit" class="btn btn-submit">✓ Update Designation</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include '../includes/footer.php'; ?>

    <script>
        function updateCounter(fieldId, maxLength) {
            const field = document.getElementById(fieldId);
            const counter = document.getElementById(fieldId + '_counter');
            const length = field.value.length;
            
            counter.textContent = length + '/' + maxLength + ' characters';
            
            // Change color based on usage
            if (length === 0) {
                counter.classList.remove('success', 'warning');
            } else if (length === maxLength) {
                counter.classList.remove('success');
                counter.classList.add('warning');
            } else {
                counter.classList.remove('warning');
                counter.classList.add('success');
            }
        }

        // Initialize counters on page load
        document.addEventListener('DOMContentLoaded', function() {
            ['designation_code', 'designation_name', 'description'].forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    const maxLength = fieldId === 'designation_code' ? 50 : (fieldId === 'designation_name' ? 100 : 255);
                    updateCounter(fieldId, maxLength);
                }
            });
        });
    </script>
</body>
</html>
