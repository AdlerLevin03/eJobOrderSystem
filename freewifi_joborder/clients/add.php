<?php
require_once '../config/connection.php';

$success_message = '';
$error_message = '';
$form_data = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $prefix = trim($_POST['prefix'] ?? '');
    $fname = trim($_POST['fname'] ?? '');
    $mname = trim($_POST['mname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $cno = trim($_POST['cno'] ?? '');
    $agency = trim($_POST['agency'] ?? '');

    // Store form data for repopulation on error
    $form_data = compact('prefix', 'fname', 'mname', 'lname', 'suffix', 'cno', 'agency');

    // Validation
    if (empty($fname) || empty($lname)) {
        $error_message = 'First Name and Last Name are required fields.';
    } elseif (strlen($fname) > 255 || strlen($lname) > 255) {
        $error_message = 'First Name and Last Name must not exceed 255 characters.';
    } elseif (!empty($cno)) {
        // Validate contact number: must be exactly 11 digits and start with 09
        if (!preg_match('/^09\d{9}$/', $cno)) {
            $error_message = 'Contact Number must be exactly 11 digits and start with 09 (e.g., 09171234567).';
        }
    } elseif (empty($agency)) {
        $error_message = 'Agency/Organization is a required field.';
    }
    
    if (empty($error_message)) {
        try {
            $query = "INSERT INTO clients (prefix, fname, mname, lname, suffix, cno, agency) 
                     VALUES (:prefix, :fname, :mname, :lname, :suffix, :cno, :agency)";
            $stmt = $pdo->prepare($query);
            
            $stmt->execute([
                ':prefix' => $prefix ?: null,
                ':fname' => $fname,
                ':mname' => $mname ?: null,
                ':lname' => $lname,
                ':suffix' => $suffix ?: null,
                ':cno' => $cno ?: null,
                ':agency' => $agency ?: null
            ]);

            $success_message = 'Client added successfully! Redirecting...';
            // Clear form data on success
            $form_data = [];
            // Redirect after 2 seconds
            header('refresh:2;url=index.php');
        } catch (PDOException $e) {
            $error_message = 'Error adding client: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Client - Free WiFi Job Order System</title>
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
            padding: 40px 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 40px;
        }

        .page-header {
            margin-bottom: 35px;
            border-bottom: 3px solid var(--accent-color);
            padding-bottom: 20px;
        }

        .page-header h1 {
            font-size: 32px;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-weight: 700;
        }

        .page-header p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            border-left: 4px solid;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border-color: var(--success-color);
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border-color: var(--danger-color);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
            letter-spacing: 0.3px;
        }

        .form-group label .required {
            color: var(--danger-color);
            margin-left: 4px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            color: var(--text-primary);
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.75);
        }

        .form-group input:hover,
        .form-group select:hover,
        .form-group textarea:hover {
            border-color: var(--primary-light);
            box-shadow: 0 4px 8px rgba(30, 64, 175, 0.1);
            background-color: #f8fbff;
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

        .form-hint {
            font-size: 12px;
            color: #000000;
            margin-top: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
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
                <h1>Add New Client</h1>
                <p>Fill in the client information below to add a new client to the system</p>
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

            <!-- Add Client Form -->
            <form method="POST" action="">
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h3>Personal Information</h3>

                    <!-- Name Fields -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="prefix">
                                Prefix/Title
                            </label>
                            <select id="prefix" name="prefix">
                                <option value="">-- Select Prefix --</option>
                                <option value="Mr." <?php echo ($form_data['prefix'] ?? '') === 'Mr.' ? 'selected' : ''; ?>>Mr.</option>
                                <option value="Ms." <?php echo ($form_data['prefix'] ?? '') === 'Ms.' ? 'selected' : ''; ?>>Ms.</option>
                                <option value="Mrs." <?php echo ($form_data['prefix'] ?? '') === 'Mrs.' ? 'selected' : ''; ?>>Mrs.</option>
                                <option value="Dr." <?php echo ($form_data['prefix'] ?? '') === 'Dr.' ? 'selected' : ''; ?>>Dr.</option>
                                <option value="Prof." <?php echo ($form_data['prefix'] ?? '') === 'Prof.' ? 'selected' : ''; ?>>Prof.</option>
                                <option value="Atty." <?php echo ($form_data['prefix'] ?? '') === 'Atty.' ? 'selected' : ''; ?>>Atty.</option>
                                <option value="Engr." <?php echo ($form_data['prefix'] ?? '') === 'Engr.' ? 'selected' : ''; ?>>Engr.</option>
                                <option value="Arch." <?php echo ($form_data['prefix'] ?? '') === 'Arch.' ? 'selected' : ''; ?>>Arch.</option>
                                <option value="Rev." <?php echo ($form_data['prefix'] ?? '') === 'Rev.' ? 'selected' : ''; ?>>Rev.</option>
                                <option value="Fr." <?php echo ($form_data['prefix'] ?? '') === 'Fr.' ? 'selected' : ''; ?>>Fr.</option>
                                <option value="Sr." <?php echo ($form_data['prefix'] ?? '') === 'Sr.' ? 'selected' : ''; ?>>Sr.</option>
                                <option value="Br." <?php echo ($form_data['prefix'] ?? '') === 'Br.' ? 'selected' : ''; ?>>Br.</option>
                                <option value="Hon." <?php echo ($form_data['prefix'] ?? '') === 'Hon.' ? 'selected' : ''; ?>>Hon.</option>
                                <option value="Gov." <?php echo ($form_data['prefix'] ?? '') === 'Gov.' ? 'selected' : ''; ?>>Gov.</option>
                                <option value="Mayor" <?php echo ($form_data['prefix'] ?? '') === 'Mayor' ? 'selected' : ''; ?>>Mayor</option>
                                <option value="Cong." <?php echo ($form_data['prefix'] ?? '') === 'Cong.' ? 'selected' : ''; ?>>Cong.</option>
                                <option value="Sen." <?php echo ($form_data['prefix'] ?? '') === 'Sen.' ? 'selected' : ''; ?>>Sen.</option>
                                <option value="Gen." <?php echo ($form_data['prefix'] ?? '') === 'Gen.' ? 'selected' : ''; ?>>Gen.</option>
                                <option value="Col." <?php echo ($form_data['prefix'] ?? '') === 'Col.' ? 'selected' : ''; ?>>Col.</option>
                                <option value="Capt." <?php echo ($form_data['prefix'] ?? '') === 'Capt.' ? 'selected' : ''; ?>>Capt.</option>
                                <option value="Judge" <?php echo ($form_data['prefix'] ?? '') === 'Judge' ? 'selected' : ''; ?>>Judge</option>
                                <option value="Brgy. Capt." <?php echo ($form_data['prefix'] ?? '') === 'Brgy. Capt.' ? 'selected' : ''; ?>>Brgy. Capt.</option>
                                <option value="Insp." <?php echo ($form_data['prefix'] ?? '') === 'Insp.' ? 'selected' : ''; ?>>Insp.</option>
                                <option value="Dir." <?php echo ($form_data['prefix'] ?? '') === 'Dir.' ? 'selected' : ''; ?>>Dir.</option>
                                <option value="Sec." <?php echo ($form_data['prefix'] ?? '') === 'Sec.' ? 'selected' : ''; ?>>Sec.</option>
                                <option value="VP" <?php echo ($form_data['prefix'] ?? '') === 'VP' ? 'selected' : ''; ?>>VP</option>
                                <option value="Pres." <?php echo ($form_data['prefix'] ?? '') === 'Pres.' ? 'selected' : ''; ?>>Pres.</option>

                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fname">
                                First Name
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="fname" 
                                name="fname" 
                                placeholder="Enter first name"
                                value="<?php echo htmlspecialchars($form_data['fname'] ?? ''); ?>"
                                required
                                maxlength="255"
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="mname">
                                Middle Name
                            </label>
                            <input 
                                type="text" 
                                id="mname" 
                                name="mname" 
                                placeholder="Enter middle name"
                                value="<?php echo htmlspecialchars($form_data['mname'] ?? ''); ?>"
                                maxlength="255"
                            >
                        </div>

                        <div class="form-group">
                            <label for="lname">
                                Last Name
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="lname" 
                                name="lname" 
                                placeholder="Enter last name"
                                value="<?php echo htmlspecialchars($form_data['lname'] ?? ''); ?>"
                                required
                                maxlength="255"
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="suffix">
                                Suffix
                            </label>
                            <select id="suffix" name="suffix">
                                <option value="">-- Select Suffix --</option>
                                <option value="Jr." <?php echo ($form_data['suffix'] ?? '') === 'Jr.' ? 'selected' : ''; ?>>Jr.</option>
                                <option value="Sr." <?php echo ($form_data['suffix'] ?? '') === 'Sr.' ? 'selected' : ''; ?>>Sr.</option>
                                <option value="II" <?php echo ($form_data['suffix'] ?? '') === 'II' ? 'selected' : ''; ?>>II</option>
                                <option value="III" <?php echo ($form_data['suffix'] ?? '') === 'III' ? 'selected' : ''; ?>>III</option>
                                <option value="IV" <?php echo ($form_data['suffix'] ?? '') === 'IV' ? 'selected' : ''; ?>>IV</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="cno">
                                Contact Number
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="tel" 
                                id="cno" 
                                name="cno" 
                                placeholder="e.g., 09171234567"
                                value="<?php echo htmlspecialchars($form_data['cno'] ?? ''); ?>"
                                pattern="09\d{9}"
                                maxlength="11"
                                inputmode="numeric"
                                required
                            >
                            <p class="form-hint">Must be 11 digits starting with 09 (e.g., 09171234567)</p>
                            <div class="character-counter" id="cnoCounter">0/11 digits</div>
                        </div>
                    </div>
                </div>

                <!-- Organization Information Section -->
                <div class="form-section">
                    <h3>Organization Information</h3>

                    <div class="form-group">
                        <label for="agency">
                            Agency/Organization
                            <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="agency" 
                            name="agency" 
                            placeholder="Enter agency or organization name"
                            value="<?php echo htmlspecialchars($form_data['agency'] ?? ''); ?>"
                            maxlength="255"
                            required
                        >
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Client</button>
                </div>
            </form>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include '../includes/footer.php'; ?>

    <script>
        // Character counter for contact number
        const cnoInput = document.getElementById('cno');
        const cnoCounter = document.getElementById('cnoCounter');

        // Auto-format contact number to start with 09
        cnoInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, ''); // Remove all non-digits
            
            // Ensure it starts with 09
            if (value.length > 0 && !value.startsWith('09')) {
                // If user didn't start with 09, add it
                if (value.startsWith('9')) {
                    value = '0' + value;
                } else {
                    value = '09' + value;
                }
            }
            
            // Limit to 11 digits
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            
            // Update input value
            this.value = value;
            
            // Update counter
            const length = value.length;
            const maxLength = 11;
            
            if (length === 0) {
                cnoCounter.textContent = '0/11 digits';
                cnoCounter.classList.remove('full', 'warning');
            } else if (length === maxLength) {
                cnoCounter.textContent = '✓ 11/11 digits - Complete';
                cnoCounter.classList.add('full');
                cnoCounter.classList.remove('warning');
            } else if (length >= 9) {
                cnoCounter.textContent = `${length}/11 digits - Almost there`;
                cnoCounter.classList.add('warning');
                cnoCounter.classList.remove('full');
            } else {
                cnoCounter.textContent = `${length}/11 digits`;
                cnoCounter.classList.remove('full', 'warning');
            }
        });

        // Set initial value to 09 when input is focused and empty
        cnoInput.addEventListener('focus', function() {
            if (this.value === '') {
                this.value = '09';
                // Trigger input event to update counter
                this.dispatchEvent(new Event('input'));
            }
        });

        // Initialize counter with pre-filled value
        cnoInput.dispatchEvent(new Event('input'));
    </script>
</body>
</html>
