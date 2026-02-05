<?php
require_once '../config/connection.php';

// Initialize form variables
$fname = '';
$mname = '';
$lname = '';
$suffix = '';
$prefix = '';
$gmail = '';
$cpno = '';
$department_id = '';
$position_id = '';
$status = 'Active';
$photo = null;

// Fetch departments for dropdown
$departments = [];
try {
    $query = "SELECT id, department_name FROM departments WHERE status = 'Active' ORDER BY department_name ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching departments: " . $e->getMessage();
}

// Fetch positions for dropdown
$positions = [];
try {
    $query = "SELECT id, position_name FROM positions WHERE status = 'Active' ORDER BY position_name ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching positions: " . $e->getMessage();
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = $_POST['fname'] ?? '';
    $mname = $_POST['mname'] ?? '';
    $lname = $_POST['lname'] ?? '';
    $suffix = $_POST['suffix'] ?? '';
    $prefix = $_POST['prefix'] ?? '';
    $gmail = $_POST['gmail'] ?? '';
    $cpno = $_POST['cpno'] ?? '';
    $department_id = $_POST['department_id'] ?? '';
    $position_id = $_POST['position_id'] ?? '';
    $status = $_POST['status'] ?? 'Active';

    // Handle photo upload (optional)
    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $_FILES['photo']['tmp_name'];
        $fileSize = $_FILES['photo']['size'];
        $fileType = mime_content_type($tmpPath);

        // Accept common image types and limit size to 2MB
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($fileType, $allowed)) {
            $error_message = 'Photo must be a JPG, PNG, or GIF image.';
        } elseif ($fileSize > 2 * 1024 * 1024) {
            $error_message = 'Photo must be smaller than 2MB.';
        } else {
            $photo = file_get_contents($tmpPath);
        }
    }

    // Validation
    if (empty($fname) || empty($lname) || empty($gmail) || empty($cpno) || empty($department_id) || empty($position_id)) {
        $error_message = 'First Name, Last Name, Email, Contact Number, Department, and Position are required.';
    } elseif (strlen($fname) > 255 || strlen($lname) > 255) {
        $error_message = 'First Name and Last Name must not exceed 255 characters.';
    } elseif (!filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (!preg_match('/^09\d{9}$/', $cpno)) {
        $error_message = 'Contact number must be 11 digits starting with 09.';
    } else {
        // Check if email already exists
        try {
            $check_query = "SELECT id FROM personnels WHERE gmail = ?";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->execute([$gmail]);
            
            if ($check_stmt->rowCount() > 0) {
                $error_message = 'This email address is already registered.';
            } else {
                // Insert personnel
                try {
                    $insert_query = "INSERT INTO personnels (fname, mname, lname, suffix, prefix, gmail, cpno, department_id, position_id, photo, status) 
                                    VALUES (:fname, :mname, :lname, :suffix, :prefix, :gmail, :cpno, :department_id, :position_id, :photo, :status)";
                    $insert_stmt = $pdo->prepare($insert_query);

                    // Bind values, bind photo as LOB if present or NULL otherwise
                    $insert_stmt->bindValue(':fname', $fname);
                    $insert_stmt->bindValue(':mname', $mname);
                    $insert_stmt->bindValue(':lname', $lname);
                    $insert_stmt->bindValue(':suffix', $suffix);
                    $insert_stmt->bindValue(':prefix', $prefix);
                    $insert_stmt->bindValue(':gmail', $gmail);
                    $insert_stmt->bindValue(':cpno', $cpno);
                    $insert_stmt->bindValue(':department_id', $department_id);
                    $insert_stmt->bindValue(':position_id', $position_id);
                    if ($photo !== null) {
                        $insert_stmt->bindValue(':photo', $photo, PDO::PARAM_LOB);
                    } else {
                        $insert_stmt->bindValue(':photo', null, PDO::PARAM_NULL);
                    }
                    $insert_stmt->bindValue(':status', $status);

                    $insert_stmt->execute();
                    
                    $success_message = 'Personnel added successfully!';
                    
                    // Clear form
                    $fname = $mname = $lname = $suffix = $gmail = $cpno = '';
                    $department_id = $position_id = '';
                    $photo = null;
                    
                    // Redirect after 2 seconds
                    header('Refresh: 2; url=personnel_index.php');
                } catch (PDOException $e) {
                    $error_message = 'Error adding personnel: ' . $e->getMessage();
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Error checking email: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Personnel - Free WiFi Job Order System</title>
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

        .form-section {
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

        label {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 8px;
        }

        input, select {
            width: 100%;
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

        input:hover, select:hover {
            border-color: var(--primary-light);
            box-shadow: 0 4px 8px rgba(30, 64, 175, 0.1);
            background-color: #f8fbff;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(30, 64, 175, 0.15), inset 0 0 0 1px rgba(30, 64, 175, 0.1);
        }

        input::placeholder {
            color: var(--text-secondary);
        }

        .char-counter {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 6px;
            font-weight: 500;
        }

        .char-counter.warning {
            color: var(--warning-color);
        }

        .char-counter.success {
            color: var(--success-color);
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

        .alert-success {
            background-color: #dcfce7;
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
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

        .form-actions {
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

        .btn-submit {
            background-color: var(--success-color);
            color: white;
        }

        .btn-submit:hover {
            background-color: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
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

        /* Modal Styles */
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

        .modal-button {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .modal-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
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

        /* Toggle Switch Styles */
        .toggle-group {
            display: flex;
            gap: 0;
            background-color: var(--border-color);
            border-radius: 8px;
            padding: 4px;
            width: fit-content;
        }

        .toggle-option {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            cursor: pointer;
        }

        .toggle-label {
            padding: 10px 16px;
            cursor: pointer;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            background-color: transparent;
            transition: all 0.3s ease;
            text-transform: none;
            margin: 0;
            white-space: nowrap;
        }

        .toggle-option input[type="radio"]:checked + .toggle-label {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(30, 64, 175, 0.2);
        }

        .toggle-option input[type="radio"]:hover + .toggle-label {
            color: var(--primary-color);
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

            input, select {
                padding: 10px 12px;
                font-size: 13px;
            }

            .btn {
                padding: 10px 20px;
                font-size: 13px;
            }

            .modal-content {
                max-width: 90%;
            }

            .modal-title {
                font-size: 18px;
            }

            .modal-message {
                font-size: 13px;
            }

            .toggle-group {
                flex-wrap: wrap;
                gap: 8px;
                background-color: transparent;
                padding: 0;
            }

            .toggle-label {
                padding: 8px 12px;
                font-size: 13px;
                border: 2px solid var(--border-color);
                border-radius: 6px;
            }

            .toggle-option input[type="radio"]:checked + .toggle-label {
                border-color: var(--primary-color);
                background-color: var(--primary-color);
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
                <h1>Add New Personnel</h1>
                <p>Register a new personnel member in the system</p>
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

            <!-- Add Form -->
            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Personal Information Section -->
                <div class="form-section">
                    <div class="section-title">👤 Personal Information</div>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Prefix</label>
                            <div class="toggle-group" style="margin-top: 8px;">
                                <div class="toggle-option">
                                    <input type="radio" id="prefix_none" name="prefix" value="" <?php echo ($prefix === '') ? 'checked' : ''; ?>>
                                    <label for="prefix_none" class="toggle-label">None</label>
                                </div>
                                <div class="toggle-option">
                                    <input type="radio" id="prefix_engr" name="prefix" value="Engr." <?php echo ($prefix === 'Engr.') ? 'checked' : ''; ?>>
                                    <label for="prefix_engr" class="toggle-label">Engr. (Engineer)</label>
                                </div>
                                <div class="toggle-option">
                                    <input type="radio" id="prefix_atty" name="prefix" value="Atty." <?php echo ($prefix === 'Atty.') ? 'checked' : ''; ?>>
                                    <label for="prefix_atty" class="toggle-label">Atty. (Attorney)</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="fname">First Name *</label>
                            <input type="text" id="fname" name="fname" placeholder="Enter first name" value="<?php echo htmlspecialchars($fname ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="mname">Middle Name</label>
                            <input type="text" id="mname" name="mname" placeholder="Enter middle name" value="<?php echo htmlspecialchars($mname ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="lname">Last Name *</label>
                            <input type="text" id="lname" name="lname" placeholder="Enter last name" value="<?php echo htmlspecialchars($lname ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="suffix">Suffix</label>
                            <select id="suffix" name="suffix">
                                <option value="">-- Select Suffix --</option>
                                <option value="Jr." <?php echo ($suffix === 'Jr.') ? 'selected' : ''; ?>>Jr.</option>
                                <option value="Sr." <?php echo ($suffix === 'Sr.') ? 'selected' : ''; ?>>Sr.</option>
                                <option value="I" <?php echo ($suffix === 'I') ? 'selected' : ''; ?>>I</option>
                                <option value="II" <?php echo ($suffix === 'II') ? 'selected' : ''; ?>>II</option>
                                <option value="III" <?php echo ($suffix === 'III') ? 'selected' : ''; ?>>III</option>
                                <option value="IV" <?php echo ($suffix === 'IV') ? 'selected' : ''; ?>>IV</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label for="photo">Photo</label>
                            <input type="file" id="photo" name="photo" accept="image/*">
                            <small style="font-size: 12px; color: var(--text-secondary); margin-top: 6px; font-weight: 500;">📋 Maximum file size: 2MB</small>
                        </div>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="form-section">
                    <div class="section-title">📞 Contact Information</div>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="gmail">Email Address (Gmail) *</label>
                            <input type="email" id="gmail" name="gmail" placeholder="example@gmail.com" value="<?php echo htmlspecialchars($gmail ?? ''); ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="cpno">Contact Number (11 digits, starts with 09) *</label>
                            <input 
                                type="text" 
                                id="cpno" 
                                name="cpno" 
                                placeholder="09xxxxxxxxx" 
                                pattern="09\d{9}"
                                maxlength="11"
                                value="<?php echo htmlspecialchars($cpno ?? ''); ?>"
                                required
                            >
                            <div class="char-counter" id="charCounter">0/11 digits</div>
                        </div>
                    </div>
                </div>

                <!-- Department & Position Section -->
                <div class="form-section">
                    <div class="section-title">🏢 Department & Position</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="department_id">Department *</label>
                            <select id="department_id" name="department_id" required>
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept['id']); ?>" <?php echo ($department_id == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="position_id">Position *</label>
                            <select id="position_id" name="position_id" required>
                                <option value="">-- Select Position --</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?php echo htmlspecialchars($pos['id']); ?>" <?php echo ($position_id == $pos['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pos['position_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Status is automatically set to Active -->
                        <input type="hidden" name="status" value="Active">
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="personnel_index.php" class="btn btn-cancel">← Cancel</a>
                    <button type="submit" class="btn btn-submit">➕ Add Personnel</button>
                </div>
            </form>
        </div>
    </main>

    <!-- Modal Warning -->
    <div id="warningModal" class="modal">
        <div class="modal-content">
            <div class="modal-icon">⚠️</div>
            <div class="modal-title">File Size Exceeded</div>
            <div class="modal-message" id="modalMessage">Selected photo is too large. Please upload an image up to 2MB in size.</div>
            <button class="modal-button" onclick="closeModal()">OK</button>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include '../includes/footer.php'; ?>

    <script>
        // Modal functions
        function showModal(message) {
            const modal = document.getElementById('warningModal');
            const modalMessage = document.getElementById('modalMessage');
            if (message) {
                modalMessage.textContent = message;
            }
            modal.classList.add('show');
        }

        function closeModal() {
            const modal = document.getElementById('warningModal');
            modal.classList.remove('show');
        }

        // Close modal when clicking outside of it
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('warningModal');
            if (event.target === modal) {
                closeModal();
            }
        });

        // Photo size check (2MB) with modal warning
        const photoInput = document.getElementById('photo');
        if (photoInput) {
            photoInput.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;
                const maxBytes = 2 * 1024 * 1024; // 2MB
                if (file.size > maxBytes) {
                    showModal('Selected photo is too large. Please upload an image up to 2MB in size.');
                    this.value = '';
                }
            });
        }

        // Contact number auto-format
        const contactNumberInput = document.getElementById('cpno');
        const charCounter = document.getElementById('charCounter');

        if (contactNumberInput) {
            contactNumberInput.addEventListener('focus', function() {
                if (this.value === '') {
                    this.value = '09';
                }
            });

            contactNumberInput.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                
                if (!value.startsWith('09')) {
                    value = '09' + value.replace(/^09/, '');
                }
                
                if (value.length > 11) {
                    value = value.substring(0, 11);
                }
                
                this.value = value;
                
                // Update character counter
                const digits = value.length;
                if (digits < 11) {
                    charCounter.textContent = `${digits}/11 digits`;
                    charCounter.classList.remove('success', 'warning');
                    charCounter.classList.add('warning');
                } else if (digits === 11) {
                    charCounter.textContent = `${digits}/11 digits`;
                    charCounter.classList.remove('warning');
                    charCounter.classList.add('success');
                }
            });

            // Initialize counter on page load
            const initialDigits = contactNumberInput.value.length;
            if (initialDigits > 0) {
                charCounter.textContent = `${initialDigits}/11 digits`;
                if (initialDigits === 11) {
                    charCounter.classList.add('success');
                } else {
                    charCounter.classList.add('warning');
                }
            }
        }
    </script>
</body>
</html>
