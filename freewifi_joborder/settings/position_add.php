<?php
require_once '../config/connection.php';

$success_message = '';
$error_message = '';
$form_data = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $position_code = trim($_POST['position_code'] ?? '');
    $position_name = trim($_POST['position_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');

    // Store form data for repopulation on error
    $form_data = compact('position_code', 'position_name', 'description', 'status');

    // Validation
    if (empty($position_code)) {
        $error_message = 'Position Code is a required field.';
    } elseif (strlen($position_code) > 50) {
        $error_message = 'Position Code must not exceed 50 characters.';
    } elseif (empty($position_name)) {
        $error_message = 'Position Name is a required field.';
    } elseif (strlen($position_name) > 100) {
        $error_message = 'Position Name must not exceed 100 characters.';
    } elseif (strlen($description) > 255) {
        $error_message = 'Description must not exceed 255 characters.';
    }
    
    if (empty($error_message)) {
        try {
            $query = "INSERT INTO positions (position_code, position_name, description, status, date_created) 
                     VALUES (:position_code, :position_name, :description, :status, NOW())";
            $stmt = $pdo->prepare($query);
            
            $stmt->execute([
                ':position_code' => $position_code,
                ':position_name' => $position_name,
                ':description' => $description ?: null,
                ':status' => $status
            ]);

            $success_message = 'Position added successfully! Redirecting...';
            // Clear form data on success
            $form_data = [];
            // Redirect after 2 seconds
            header('refresh:2;url=position_index.php');
        } catch (PDOException $e) {
            $error_message = 'Error adding position: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Position - Free WiFi Job Order System</title>
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

        .alert-danger {
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 20px;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
        }

        .required {
            color: var(--danger-color);
            font-weight: 700;
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 1);
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

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }

        .form-section:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .form-section h3 {
            font-size: 16px;
            color: var(--text-primary);
            margin-bottom: 20px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }

        .btn-secondary {
            background-color: var(--border-color);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background-color: #d1d5db;
        }

        .character-counter {
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

        .character-counter.full {
            background-color: #dcfce7;
            color: var(--success-color);
            border-left-color: var(--success-color);
        }

        .character-counter.warning {
            background-color: #fef3c7;
            color: #d97706;
            border-left-color: #d97706;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            main {
                padding: 25px 15px;
            }

            .container {
                padding: 25px;
            }

            .page-header h1 {
                font-size: 26px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .form-actions {
                flex-direction: column;
                gap: 10px;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            main {
                padding: 15px 10px;
            }

            .container {
                padding: 20px;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .page-header {
                margin-bottom: 25px;
                padding-bottom: 15px;
            }

            .form-group label {
                font-size: 13px;
            }

            .form-group input,
            .form-group select,
            .form-group textarea {
                padding: 10px 12px;
                font-size: 13px;
            }

            .alert {
                padding: 12px 15px;
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
                <h1>Add New Position</h1>
                <p>Fill in the position information below to add a new position to the system</p>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    ✓ <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    ✗ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Add Position Form -->
            <form method="POST" action="">
                <!-- Position Information Section -->
                <div class="form-section">
                    <h3>Position Information</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="position_code">
                                Position Code
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="position_code" 
                                name="position_code" 
                                placeholder="e.g., POS001"
                                value="<?php echo htmlspecialchars($form_data['position_code'] ?? ''); ?>"
                                required
                                maxlength="50"
                            >
                            <div class="character-counter" id="codeCounter">0/50 characters</div>
                        </div>

                        <div class="form-group">
                            <label for="position_name">
                                Position Name
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="position_name" 
                                name="position_name" 
                                placeholder="Enter position name"
                                value="<?php echo htmlspecialchars($form_data['position_name'] ?? ''); ?>"
                                required
                                maxlength="100"
                            >
                            <div class="character-counter" id="nameCounter">0/100 characters</div>
                        </div>
                    </div>

                    <div class="form-row full">
                        <div class="form-group">
                            <label for="description">
                                Description
                            </label>
                            <textarea 
                                id="description" 
                                name="description" 
                                placeholder="Enter position description (optional)"
                                maxlength="255"
                            ><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                            <div class="character-counter" id="descCounter">0/255 characters</div>
                        </div>
                    </div>

                    <!-- Status is automatically set to Active -->
                    <input type="hidden" name="status" value="Active">
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="position_index.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Position</button>
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
                codeCounter.classList.remove('full', 'warning');
            } else if (length === maxLength) {
                codeCounter.textContent = '✓ 50/50 characters - Complete';
                codeCounter.classList.add('full');
                codeCounter.classList.remove('warning');
            } else if (length >= 35) {
                codeCounter.textContent = `${length}/50 characters`;
                codeCounter.classList.add('warning');
                codeCounter.classList.remove('full');
            } else {
                codeCounter.textContent = `${length}/50 characters`;
                codeCounter.classList.remove('full', 'warning');
            }
        });

        // Initialize counter with pre-filled value
        codeInput.dispatchEvent(new Event('input'));

        // Character counter for position name
        const nameInput = document.getElementById('position_name');
        const nameCounter = document.getElementById('nameCounter');

        nameInput.addEventListener('input', function() {
            const length = this.value.length;
            const maxLength = 100;
            
            if (length === 0) {
                nameCounter.textContent = '0/100 characters';
                nameCounter.classList.remove('full', 'warning');
            } else if (length === maxLength) {
                nameCounter.textContent = '✓ 100/100 characters - Complete';
                nameCounter.classList.add('full');
                nameCounter.classList.remove('warning');
            } else if (length >= 70) {
                nameCounter.textContent = `${length}/100 characters`;
                nameCounter.classList.add('warning');
                nameCounter.classList.remove('full');
            } else {
                nameCounter.textContent = `${length}/100 characters`;
                nameCounter.classList.remove('full', 'warning');
            }
        });

        // Initialize counter with pre-filled value
        nameInput.dispatchEvent(new Event('input'));

        // Character counter for description
        const descInput = document.getElementById('description');
        const descCounter = document.getElementById('descCounter');

        descInput.addEventListener('input', function() {
            const length = this.value.length;
            const maxLength = 255;
            
            if (length === 0) {
                descCounter.textContent = '0/255 characters';
                descCounter.classList.remove('full', 'warning');
            } else if (length === maxLength) {
                descCounter.textContent = '✓ 255/255 characters - Complete';
                descCounter.classList.add('full');
                descCounter.classList.remove('warning');
            } else if (length >= 180) {
                descCounter.textContent = `${length}/255 characters`;
                descCounter.classList.add('warning');
                descCounter.classList.remove('full');
            } else {
                descCounter.textContent = `${length}/255 characters`;
                descCounter.classList.remove('full', 'warning');
            }
        });

        // Initialize counter with pre-filled value
        descInput.dispatchEvent(new Event('input'));
    </script>
</body>
</html>
