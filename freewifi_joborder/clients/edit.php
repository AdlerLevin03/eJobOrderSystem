<?php
require_once '../config/connection.php';

// Get client ID from query parameter
$client_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$client_id) {
    header('Location: index.php');
    exit;
}

// Fetch existing client data
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

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prefix = $_POST['prefix'] ?? '';
    $fname = $_POST['fname'] ?? '';
    $mname = $_POST['mname'] ?? '';
    $lname = $_POST['lname'] ?? '';
    $suffix = $_POST['suffix'] ?? '';
    $cno = $_POST['cno'] ?? '';
    $agency = $_POST['agency'] ?? '';
    $status = $_POST['status'] ?? 'Active';

    // Validation
    if (empty($fname) || empty($lname) || empty($cno) || empty($agency)) {
        $error_message = 'First Name, Last Name, Contact Number, and Agency are required.';
    } elseif (strlen($fname) > 255 || strlen($lname) > 255) {
        $error_message = 'First Name and Last Name must not exceed 255 characters.';
    } elseif (!preg_match('/^09\d{9}$/', $cno)) {
        $error_message = 'Contact number must be 11 digits starting with 09.';
    } else {
        // Update client
        try {
            $update_query = "UPDATE clients SET prefix = ?, fname = ?, mname = ?, lname = ?, suffix = ?, cno = ?, agency = ?, status = ? WHERE id = ?";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([$prefix, $fname, $mname, $lname, $suffix, $cno, $agency, $status, $client_id]);
            
            $success_message = 'Client updated successfully!';
            
            // Refresh client data
            $query = "SELECT * FROM clients WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$client_id]);
            $client = $stmt->fetch();
            
            // Redirect after 2 seconds
            header('Refresh: 2; url=index.php');
        } catch (PDOException $e) {
            $error_message = 'Error updating client: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Client - Free WiFi Job Order System</title>
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.75);
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
                <h1>Edit Client</h1>
                <p>Update client information</p>
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
                <!-- Personal Information Section -->
                <div class="form-section">
                    <div class="section-title">📋 Personal Information</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="prefix">Prefix</label>
                            <select id="prefix" name="prefix">
                                <option value="">-- Select Prefix --</option>
                                <option value="Mr." <?php echo ($client['prefix'] ?? '') === 'Mr.' ? 'selected' : ''; ?>>Mr.</option>
                                <option value="Ms." <?php echo ($client['prefix'] ?? '') === 'Ms.' ? 'selected' : ''; ?>>Ms.</option>
                                <option value="Mrs." <?php echo ($client['prefix'] ?? '') === 'Mrs.' ? 'selected' : ''; ?>>Mrs.</option>
                                <option value="Dr." <?php echo ($client['prefix'] ?? '') === 'Dr.' ? 'selected' : ''; ?>>Dr.</option>
                                <option value="Prof." <?php echo ($client['prefix'] ?? '') === 'Prof.' ? 'selected' : ''; ?>>Prof.</option>
                                <option value="Atty." <?php echo ($client['prefix'] ?? '') === 'Atty.' ? 'selected' : ''; ?>>Atty.</option>
                                <option value="Engr." <?php echo ($client['prefix'] ?? '') === 'Engr.' ? 'selected' : ''; ?>>Engr.</option>
                                <option value="Arch." <?php echo ($client['prefix'] ?? '') === 'Arch.' ? 'selected' : ''; ?>>Arch.</option>
                                <option value="Rev." <?php echo ($client['prefix'] ?? '') === 'Rev.' ? 'selected' : ''; ?>>Rev.</option>
                                <option value="Fr." <?php echo ($client['prefix'] ?? '') === 'Fr.' ? 'selected' : ''; ?>>Fr.</option>
                                <option value="Sr." <?php echo ($client['prefix'] ?? '') === 'Sr.' ? 'selected' : ''; ?>>Sr.</option>
                                <option value="Br." <?php echo ($client['prefix'] ?? '') === 'Br.' ? 'selected' : ''; ?>>Br.</option>
                                <option value="Hon." <?php echo ($client['prefix'] ?? '') === 'Hon.' ? 'selected' : ''; ?>>Hon.</option>
                                <option value="Gov." <?php echo ($client['prefix'] ?? '') === 'Gov.' ? 'selected' : ''; ?>>Gov.</option>
                                <option value="Mayor" <?php echo ($client['prefix'] ?? '') === 'Mayor' ? 'selected' : ''; ?>>Mayor</option>
                                <option value="Cong." <?php echo ($client['prefix'] ?? '') === 'Cong.' ? 'selected' : ''; ?>>Cong.</option>
                                <option value="Sen." <?php echo ($client['prefix'] ?? '') === 'Sen.' ? 'selected' : ''; ?>>Sen.</option>
                                <option value="Gen." <?php echo ($client['prefix'] ?? '') === 'Gen.' ? 'selected' : ''; ?>>Gen.</option>
                                <option value="Col." <?php echo ($client['prefix'] ?? '') === 'Col.' ? 'selected' : ''; ?>>Col.</option>
                                <option value="Capt." <?php echo ($client['prefix'] ?? '') === 'Capt.' ? 'selected' : ''; ?>>Capt.</option>
                                <option value="Judge" <?php echo ($client['prefix'] ?? '') === 'Judge' ? 'selected' : ''; ?>>Judge</option>
                                <option value="Brgy. Capt." <?php echo ($client['prefix'] ?? '') === 'Brgy. Capt.' ? 'selected' : ''; ?>>Brgy. Capt.</option>
                                <option value="Insp." <?php echo ($client['prefix'] ?? '') === 'Insp.' ? 'selected' : ''; ?>>Insp.</option>
                                <option value="Dir." <?php echo ($client['prefix'] ?? '') === 'Dir.' ? 'selected' : ''; ?>>Dir.</option>
                                <option value="Sec." <?php echo ($client['prefix'] ?? '') === 'Sec.' ? 'selected' : ''; ?>>Sec.</option>
                                <option value="VP" <?php echo ($client['prefix'] ?? '') === 'VP' ? 'selected' : ''; ?>>VP</option>
                                <option value="Pres." <?php echo ($client['prefix'] ?? '') === 'Pres.' ? 'selected' : ''; ?>>Pres.</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fname">First Name *</label>
                            <input type="text" id="fname" name="fname" placeholder="Enter first name" value="<?php echo htmlspecialchars($client['fname'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="mname">Middle Name</label>
                            <input type="text" id="mname" name="mname" placeholder="Enter middle name" value="<?php echo htmlspecialchars($client['mname'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="lname">Last Name *</label>
                            <input type="text" id="lname" name="lname" placeholder="Enter last name" value="<?php echo htmlspecialchars($client['lname'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="suffix">Suffix</label>
                            <select id="suffix" name="suffix">
                                <option value="">-- Select Suffix --</option>
                                <option value="Jr." <?php echo ($client['suffix'] === 'Jr.') ? 'selected' : ''; ?>>Jr.</option>
                                <option value="Sr." <?php echo ($client['suffix'] === 'Sr.') ? 'selected' : ''; ?>>Sr.</option>
                                <option value="I" <?php echo ($client['suffix'] === 'I') ? 'selected' : ''; ?>>I</option>
                                <option value="II" <?php echo ($client['suffix'] === 'II') ? 'selected' : ''; ?>>II</option>
                                <option value="III" <?php echo ($client['suffix'] === 'III') ? 'selected' : ''; ?>>III</option>
                                <option value="IV" <?php echo ($client['suffix'] === 'IV') ? 'selected' : ''; ?>>IV</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="cno">Contact Number (11 digits, starts with 09) *</label>
                            <input 
                                type="text" 
                                id="cno" 
                                name="cno" 
                                placeholder="09xxxxxxxxx" 
                                pattern="09\d{9}"
                                maxlength="11"
                                value="<?php echo htmlspecialchars($client['cno'] ?? ''); ?>"
                                required
                            >
                            <div class="char-counter" id="charCounter">0/11 digits</div>
                        </div>
                    </div>
                </div>

                <!-- Organization Information Section -->
                <div class="form-section">
                    <div class="section-title">🏢 Organization Information</div>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="agency">Agency *</label>
                            <input type="text" id="agency" name="agency" placeholder="Enter agency name" value="<?php echo htmlspecialchars($client['agency'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group full-width">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="Active" <?php echo ($client['status'] ?? 'Active') === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo ($client['status'] ?? 'Active') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="index.php" class="btn btn-cancel">← Cancel</a>
                    <button type="submit" class="btn btn-submit">💾 Update Client</button>
                </div>
            </form>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include '../includes/footer.php'; ?>

    <script>
        // Contact number auto-format
        const contactNumberInput = document.getElementById('cno');
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
