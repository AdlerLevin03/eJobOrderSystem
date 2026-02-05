<?php
require_once '../config/connection.php';

// Get position ID from query parameter
$position_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$position_id) {
    header('Location: position_index.php');
    exit;
}

// Fetch existing position data
try {
    $query = "SELECT * FROM positions WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$position_id]);
    $position = $stmt->fetch();

    if (!$position) {
        header('Location: position_index.php');
        exit;
    }
} catch (PDOException $e) {
    echo "Error fetching position: " . $e->getMessage();
    exit;
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $position_code = trim($_POST['position_code'] ?? '');
    $position_name = trim($_POST['position_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    // Validation
    if (empty($position_code)) {
        $error_message = 'Position Code is required.';
    } elseif (strlen($position_code) > 50) {
        $error_message = 'Position Code must not exceed 50 characters.';
    } elseif (empty($position_name)) {
        $error_message = 'Position Name is required.';
    } elseif (strlen($position_name) > 100) {
        $error_message = 'Position Name must not exceed 100 characters.';
    } elseif (strlen($description) > 255) {
        $error_message = 'Description must not exceed 255 characters.';
    } else {
        // Update position
        try {
            $update_query = "UPDATE positions SET position_code = ?, position_name = ?, description = ?, status = ?, date_updated = NOW() WHERE id = ?";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([$position_code, $position_name, $description ?: null, $status, $position_id]);
            
            $success_message = 'Position updated successfully!';
            
            // Refresh position data
            $query = "SELECT * FROM positions WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$position_id]);
            $position = $stmt->fetch();
            
            // Redirect after 2 seconds
            header('Refresh: 2; url=position_index.php');
        } catch (PDOException $e) {
            $error_message = 'Error updating position: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Position - Free WiFi Job Order System</title>
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

        .alert-success {
            background-color: #dcfce7;
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
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
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            color: var(--text-primary);
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.075);
        }

        .form-group input:hover,
        .form-group select:hover,
        .form-group textarea:hover {
            border-color: var(--primary-light);
            box-shadow: 0 4px 8px rgba(30, 64, 175, 0.1);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(30, 64, 175, 0.15), inset 0 0 0 1px rgba(30, 64, 175, 0.1);
        }

        .form-group input::placeholder {
            color: #9ca3af;
            font-weight: 500;
        }

        .form-group textarea {
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
            background-color: var(--primary-color);
            color: white;
        }

        .btn-submit:hover {
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

            .form-grid {
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
                margin-bottom: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            label {
                font-size: 12px;
            }

            input, select, textarea {
                padding: 10px 12px;
                font-size: 13px;
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
                <h1>Edit Position</h1>
                <p>Update position information</p>
            </div>

            <!-- Error/Success Messages -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    ✗ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    ✓ <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- Edit Form -->
            <form method="POST" action="">
                <!-- Position Information Section -->
                <div class="form-section">
                    <div class="section-title">📋 Position Information</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="position_code">Position Code *</label>
                            <input 
                                type="text" 
                                id="position_code" 
                                name="position_code" 
                                placeholder="e.g., POS001" 
                                value="<?php echo htmlspecialchars($position['position_code'] ?? ''); ?>"
                                maxlength="50"
                                required
                            >
                            <div class="char-counter" id="codeCounter">0/50 characters</div>
                        </div>

                        <div class="form-group">
                            <label for="position_name">Position Name *</label>
                            <input 
                                type="text" 
                                id="position_name" 
                                name="position_name" 
                                placeholder="Enter position name"
                                value="<?php echo htmlspecialchars($position['position_name'] ?? ''); ?>"
                                maxlength="100"
                                required
                            >
                            <div class="char-counter" id="nameCounter">0/100 characters</div>
                        </div>
                    </div>
                </div>

                <!-- Description and Status Section -->
                <div class="form-section">
                    <div class="section-title">📝 Additional Information</div>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="description">Description</label>
                            <textarea 
                                id="description" 
                                name="description" 
                                placeholder="Enter position description (optional)"
                                maxlength="255"
                            ><?php echo htmlspecialchars($position['description'] ?? ''); ?></textarea>
                            <div class="char-counter" id="descCounter">0/255 characters</div>
                        </div>

                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="Active" <?php echo ($position['status'] ?? 'Active') === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo ($position['status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="position_index.php" class="btn btn-cancel">← Cancel</a>
                    <button type="submit" class="btn btn-submit">💾 Update Position</button>
                </div>
            </form>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include '../includes/footer.php'; ?>

    <script>
        // Character counter for position code
        const codeInput = document.getElementById('position_code');
        const codeCounter = document.getElementById('codeCounter');

        codeInput.addEventListener('input', function() {
            const length = this.value.length;
            const maxLength = 50;
            
            if (length === 0) {
                codeCounter.textContent = '0/50 characters';
                codeCounter.classList.remove('success', 'warning');
            } else if (length === maxLength) {
                codeCounter.textContent = `✓ ${length}/50 characters`;
                codeCounter.classList.add('success');
                codeCounter.classList.remove('warning');
            } else if (length >= 35) {
                codeCounter.textContent = `${length}/50 characters`;
                codeCounter.classList.add('warning');
                codeCounter.classList.remove('success');
            } else {
                codeCounter.textContent = `${length}/50 characters`;
                codeCounter.classList.remove('success', 'warning');
            }
        });

        // Initialize counter on page load
        codeInput.dispatchEvent(new Event('input'));

        // Character counter for position name
        const nameInput = document.getElementById('position_name');
        const nameCounter = document.getElementById('nameCounter');

        nameInput.addEventListener('input', function() {
            const length = this.value.length;
            const maxLength = 100;
            
            if (length === 0) {
                nameCounter.textContent = '0/100 characters';
                nameCounter.classList.remove('success', 'warning');
            } else if (length === maxLength) {
                nameCounter.textContent = `✓ ${length}/100 characters`;
                nameCounter.classList.add('success');
                nameCounter.classList.remove('warning');
            } else if (length >= 70) {
                nameCounter.textContent = `${length}/100 characters`;
                nameCounter.classList.add('warning');
                nameCounter.classList.remove('success');
            } else {
                nameCounter.textContent = `${length}/100 characters`;
                nameCounter.classList.remove('success', 'warning');
            }
        });

        // Initialize counter on page load
        nameInput.dispatchEvent(new Event('input'));

        // Character counter for description
        const descInput = document.getElementById('description');
        const descCounter = document.getElementById('descCounter');

        descInput.addEventListener('input', function() {
            const length = this.value.length;
            const maxLength = 255;
            
            if (length === 0) {
                descCounter.textContent = '0/255 characters';
                descCounter.classList.remove('success', 'warning');
            } else if (length === maxLength) {
                descCounter.textContent = `✓ ${length}/255 characters`;
                descCounter.classList.add('success');
                descCounter.classList.remove('warning');
            } else if (length >= 180) {
                descCounter.textContent = `${length}/255 characters`;
                descCounter.classList.add('warning');
                descCounter.classList.remove('success');
            } else {
                descCounter.textContent = `${length}/255 characters`;
                descCounter.classList.remove('success', 'warning');
            }
        });

        // Initialize counter on page load
        descInput.dispatchEvent(new Event('input'));
    </script>
</body>
</html>
