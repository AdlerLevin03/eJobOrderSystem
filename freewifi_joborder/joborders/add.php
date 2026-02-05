<?php
require_once __DIR__ . '/../config/connection.php';

$error_message = '';
$success_message = '';

// Fetch clients
try {
    $stmt = $pdo->prepare("SELECT id, prefix, fname, mname, lname, suffix, agency FROM clients WHERE status = 'Active' ORDER BY fname ASC");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $clients = [];
}

// Fetch personnels with designated roles grouped by designation type
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.fname, p.mname, p.lname, p.suffix, p.prefix, d.designation_name
        FROM personnels p
        INNER JOIN personnel_designations pd ON p.id = pd.personnel_id
        INNER JOIN designations d ON pd.designation_id = d.id
        WHERE p.status = 'Active'
        ORDER BY d.designation_name ASC, p.fname ASC
    ");
    $stmt->execute();
    $all_personnels_with_designation = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_personnels_with_designation = [];
}

// Group personnel by designation type
$issuers = array_filter($all_personnels_with_designation, fn($p) => $p['designation_name'] === 'Issuer');
$endorsers = array_filter($all_personnels_with_designation, fn($p) => $p['designation_name'] === 'Endorser');
$approvers = array_filter($all_personnels_with_designation, fn($p) => $p['designation_name'] === 'Approver');
$performers = array_filter($all_personnels_with_designation, fn($p) => $p['designation_name'] === 'Performer');

function getFullName($f, $m, $l, $s, $p = '', $designation = '') {
    $name = '';
    if (!empty($p)) $name .= $p . ' ';
    if (!empty($f)) $name .= $f . ' ';
    if (!empty($m)) $name .= $m . ' ';
    if (!empty($l)) $name .= $l . ' ';  
    if (!empty($s)) $name .= $s;
    $result = trim($name);
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize
    $client_id = !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null;
    $modeofrequest = !empty($_POST['modeofrequest']) ? trim($_POST['modeofrequest']) : null;
    
    // If "Other" is selected, use the custom input
    if ($modeofrequest === 'Other' && !empty($_POST['modeofrequest_other'])) {
        $modeofrequest = trim($_POST['modeofrequest_other']);
    }
    
    $date_received = !empty($_POST['date_received']) ? $_POST['date_received'] : null;
    $date_scheduled = !empty($_POST['date_scheduled']) ? $_POST['date_scheduled'] : null;
    $issued_id = !empty($_POST['issued_id']) ? (int)$_POST['issued_id'] : null;
    $endorsed_id = !empty($_POST['endorsed_id']) ? (int)$_POST['endorsed_id'] : null;
    $approved_id = !empty($_POST['approved_id']) ? (int)$_POST['approved_id'] : null;
    $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $status = !empty($_POST['status']) ? trim($_POST['status']) : 'Pending';
    $verified_id = !empty($_POST['verified_id']) ? (int)$_POST['verified_id'] : null;
    $remarks = !empty($_POST['remarks']) ? trim($_POST['remarks']) : null;

    // Handle file upload (letter PDF) - do this before database transaction
    $letter_filename = null;
    if (isset($_FILES['letter_file']) && $_FILES['letter_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['letter_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                $error_message = 'Only PDF files are allowed for the letter upload.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error_message = 'Letter file size must be 5MB or less.';
            } else {
                $lettersDir = __DIR__ . '/../letters/';
                if (!is_dir($lettersDir)) {
                    mkdir($lettersDir, 0755, true);
                }
                $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file['name']);
                $destination = $lettersDir . $safeName;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $letter_filename = $safeName;
                } else {
                    $error_message = 'Failed to move uploaded letter file.';
                }
            }
        } else {
            $error_message = 'Error uploading file. Code: ' . $file['error'];
        }
    }

    // If no errors, proceed with ID generation and insertion in a transaction
    if (empty($error_message)) {
        try {
            // Start transaction for atomic ID generation and insertion
            $pdo->beginTransaction();

            // Generate joborderid with row locking to prevent race conditions
            $year = date('Y');
            $prefix = "Free Wifi {$year} ";
            $like = $prefix . '%';
            
            // SELECT ... FOR UPDATE locks the rows to prevent concurrent ID generation
            $stmt = $pdo->prepare("SELECT joborderid FROM job_orders WHERE joborderid LIKE ? ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $stmt->execute([$like]);
            $last = $stmt->fetchColumn();
            
            if ($last) {
                $lastNum = (int)substr($last, -4);
                $nextNum = $lastNum + 1;
            } else {
                $nextNum = 1;
            }
            
            $seq = str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            $joborderid = $prefix . $seq;

            // Insert the job order
            $insert = $pdo->prepare(
                "INSERT INTO job_orders (joborderid, client_id, modeofrequest, date_received, date_scheduled, issued_id, endorsed_id, approved_id, description, start_date, start_time, end_date, end_time, status, verified_id, remarks, letter_file)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $insert->execute([
                $joborderid,
                $client_id,
                $modeofrequest,
                $date_received,
                $date_scheduled,
                $issued_id,
                $endorsed_id,
                $approved_id,
                $description,
                $start_date,
                $start_time,
                $end_date,
                $end_time,
                $status,
                $verified_id,
                $remarks,
                $letter_filename
            ]);

            // Commit transaction
            $pdo->commit();

            $success_message = 'Job order created successfully with ID: ' . $joborderid;
            // Clear POST values to reset form
            $_POST = [];
        } catch (PDOException $e) {
            // Rollback transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = 'Error creating job order: ' . $e->getMessage();
            // remove uploaded file on error
            if ($letter_filename && file_exists(__DIR__ . '/../letters/' . $letter_filename)) {
                @unlink(__DIR__ . '/../letters/' . $letter_filename);
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Job Order</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

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

        html, body { height: 100%; }

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
        display:flex; 
        justify-content:center; 
        align-items:flex-start; }

        .container {
            max-width: 980px;
            width: 100%;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);   
            padding: 36px;
        }

        /* Page header like personnel page */
        .page-header {
            text-align: center;
            margin-bottom: 22px;
            padding-bottom: 18px;
            border-bottom: none;
        }

        .page-header h1 {
            font-size: 34px;
            color: var(--primary-color);
            font-weight: 800;
            margin-bottom: 8px;
        }

        .page-header p {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 18px;
            font-weight: 600;
        }

        .accent-hr {
            height: 4px;
            background: linear-gradient(90deg, var(--accent-color), var(--primary-light));
            width: 90%;
            margin: 0 auto 18px auto;
            border-radius: 4px;
            border: none;
        }

        .section-header {
            display:flex; 
            align-items:center; 
            gap:12px; 
            margin: 18px 0 12px 0;
        }

        .section-icon {
            background: #6d28d9; color: white; 
            width:34px; 
            height:34px; 
            border-radius:8px; 
            display:flex; 
            align-items:center; 
            justify-content:center; 
            font-size:16px;
        }

        .section-title { 
        color: var(--primary-color); 
        font-weight:800; 
        letter-spacing:0.6px; 
        text-transform:uppercase; 
        font-size:14px; 
        }

        .section-underline { 
        height:3px; 
        background: var(--accent-color); 
        width:100%; 
        margin-top:8px; 
        border-radius:3px; 
        }

        .form-row { 
        display:flex; 
        gap:16px; 
        margin-bottom:16px; 
        }

        .form-row .col {
         flex:1; 
         }

        label { 
        display:block; 
        font-weight:700; 
        margin-bottom:8px; 
        font-size:13px; 
        color:var(--text-primary); 
        }

        /* Elevated input style */
        input[type=text], input[type=date], input[type=time], select, textarea {
            width:100%; 
            padding:14px 16px; 
            border:1px solid var(--border-color); 
            border-radius:10px; 
            background:white; 
            color:var(--text-primary);
            box-shadow: 0 6px 14px rgba(0,0,0,1);
            transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
        }

        input[type=text]:focus, input[type=date]:focus, input[type=time]:focus, select:focus, textarea:focus {
            outline: none; 
            border-color: var(--primary-color); 
            transform: translateY(-2px); 
            box-shadow: 0 12px 30px rgba(30,64,175,0.08);
        }

        textarea { min-height:100px; resize:vertical; }

        /* Searchable combobox styling */
        .client-combobox {
            position: relative;
            width: 100%;
        }

        .client-search-input {
            width: 100% !important;
            padding: 14px 16px !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 10px !important;
            background: white !important;
            color: var(--text-primary) !important;
            box-shadow: 0 6px 14px rgba(0,0,0,1) !important;
            transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease !important;
        }

        .client-search-input:focus {
            outline: none !important;
            border-color: var(--primary-color) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 30px rgba(30,64,175,0.08) !important;
        }

        .client-dropdown-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--border-color);
            border-top: none;
            border-radius: 0 0 10px 10px;
            max-height: 280px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 1);
        }

        .client-dropdown-list.active {
            display: block;
        }

        .client-dropdown-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-color);
            transition: background-color .08s ease;
            color: var(--text-primary);
            font-size: 14px;
        }

        .client-dropdown-item:hover,
        .client-dropdown-item.highlighted {
            background-color: #f3f4f6;
            color: var(--primary-color);
            font-weight: 600;
        }

        .client-dropdown-item:last-child {
            border-bottom: none;
        }

        .client-dropdown-empty {
            padding: 12px 16px;
            color: var(--text-secondary);
            font-size: 13px;
            text-align: center;
        }

        .modeofrequest-other-input {
            width: 100% !important;
            padding: 14px 16px !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 10px !important;
            background: white !important;
            color: var(--text-primary) !important;
            box-shadow: 0 6px 14px rgba(0,0,0,0.06) !important;
            transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease !important;
        }

        .modeofrequest-other-input:focus {
            outline: none !important;
            border-color: var(--primary-color) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 30px rgba(30,64,175,0.08) !important;
        }

        .verified-client-display {
            width: 100% !important;
            padding: 14px 16px !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 10px !important;
            background-color: #f3f4f6 !important;
            color: var(--text-primary) !important;
            box-shadow: 0 6px 14px rgba(0,0,0,1) !important;
            cursor: not-allowed !important;
        }

        .file-input-container { 
        border:1px solid var(--border-color); 
        padding:12px; 
        border-radius:8px; 
        box-shadow: 0 6px 14px rgba(7,10,37,0.04); background:white; 
        }

        .file-note { 
            color:var(--text-secondary); 
            font-size:12px; 
            margin-top:8px; 
        }

        #file_upload_status {
            margin-top: 8px;
            font-size: 13px;
            min-height: 20px;
            display: flex;
            align-items: center;
        }

        .btn { 
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%); 
            color:white; 
            padding:10px 18px; 
            border-radius:8px; 
            border:none; 
            cursor:pointer; 
            font-weight:700; 
        }

        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 12px 30px rgba(30,64,175,0.12); 
        }

        .alert { 
            padding:12px 14px; 
            border-radius:8px; 
            margin-bottom:16px; 
            font-weight:600; 
        }
        
        .alert-error { 
            background:#fee2e2; color:var(--danger-color); 
            border-left:4px solid var(--danger-color); 
        }

        .alert-success { 
            background:#dcfce7; color:var(--success-color); 
            border-left:4px solid var(--success-color); 
        }

        a.back-link { 
            color:var(--text-secondary); 
            text-decoration:none; margin-right:12px; 
        }

        .required-indicator { 
            color: var(--danger-color);
            font-weight: 700; 
            margin-left: 4px; 
        }

        @media (max-width:768px) {
            .form-row { flex-direction:column; }
            .container { padding:20px; }
            .page-header h1 { font-size:26px; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main>
        <div class="container">
            <div class="page-header">
                <h1>Create Job Order</h1>
                <p>Register a new job order in the system</p>
                <div class="accent-hr"></div>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <div class="section-header">
                <div class="section-icon">📄</div>
                <div>
                    <div class="section-title">Job Order Details</div>
                    <div class="section-underline"></div>
                </div>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="col">
                        <label for="client_search">Client<span class="required-indicator">*</span></label>
                        <div class="client-combobox">
                            <input type="text" id="client_search" class="client-search-input" placeholder="Type to search client..." autocomplete="off" require>
                            <div id="client_dropdown" class="client-dropdown-list"></div>
                            <input type="hidden" name="client_id" id="client_id" required>
                        </div>
                    </div>
                    <div class="col">
                        <label for="modeofrequest">Mode of Request<span class="required-indicator">*</span></label>
                        <select name="modeofrequest" id="modeofrequest" required>
                            <option value="">-- Choose Mode --</option>
                            <option value="DICT Initiative" <?php echo (isset($_POST['modeofrequest']) && $_POST['modeofrequest'] === 'DICT Initiative') ? 'selected' : ''; ?>>DICT Initiative</option>
                            <option value="SMS" <?php echo (isset($_POST['modeofrequest']) && $_POST['modeofrequest'] === 'SMS') ? 'selected' : ''; ?>>SMS</option>
                            <option value="Letter" <?php echo (isset($_POST['modeofrequest']) && $_POST['modeofrequest'] === 'Letter') ? 'selected' : ''; ?>>Letter</option>
                            <option value="Email" <?php echo (isset($_POST['modeofrequest']) && $_POST['modeofrequest'] === 'Email') ? 'selected' : ''; ?>>Email</option>
                            <option value="Network Monitoring" <?php echo (isset($_POST['modeofrequest']) && $_POST['modeofrequest'] === 'Network Monitoring') ? 'selected' : ''; ?>>Network Monitoring</option>
                            <option value="Other" <?php echo (isset($_POST['modeofrequest']) && $_POST['modeofrequest'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <input type="text" id="modeofrequest_other" class="modeofrequest-other-input" placeholder="Please specify..." style="display: none; margin-top: 8px;" value="<?php echo htmlspecialchars($_POST['modeofrequest_other'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="col">
                        <label for="date_received">Date Received</label>
                        <input type="date" name="date_received" id="date_received" value="<?php echo htmlspecialchars($_POST['date_received'] ?? date('Y-m-d')); ?>">
                    </div>
                    <div class="col">
                        <label for="date_scheduled">Date Scheduled</label>
                        <input type="date" name="date_scheduled" id="date_scheduled" value="<?php echo htmlspecialchars($_POST['date_scheduled'] ?? ''); ?>">
                    </div>
                </div>

                <div style="margin-bottom:12px;">
                    <label for="description">Job Order Description</label>
                    <textarea name="description" id="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="col">
                        <label for="issued_id">Issued By <span class="required-indicator">*</span></label>
                        <select name="issued_id" id="issued_id" required>
                            <option value="">-- Choose Personnel --</option>
                            <?php foreach ($issuers as $p): ?>
                                <option value="<?php echo (int)$p['id']; ?>" <?php echo (isset($_POST['issued_id']) && $_POST['issued_id'] == $p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars(getFullName($p['fname'],$p['mname'],$p['lname'],$p['suffix'],$p['prefix'],$p['designation_name'])); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col">
                        <label for="endorsed_id">Endorsed By</label>
                        <select name="endorsed_id" id="endorsed_id">
                            <option value="">-- Choose Personnel --</option>
                            <?php foreach ($endorsers as $p): ?>
                                <option value="<?php echo (int)$p['id']; ?>" <?php echo (isset($_POST['endorsed_id']) && $_POST['endorsed_id'] == $p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars(getFullName($p['fname'],$p['mname'],$p['lname'],$p['suffix'],$p['prefix'],$p['designation_name'])); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="col">
                        <label for="approved_id">Approved By</label>
                        <select name="approved_id" id="approved_id">
                            <option value="">-- Choose Personnel --</option>
                            <?php foreach ($approvers as $p): ?>
                                <option value="<?php echo (int)$p['id']; ?>" <?php echo (isset($_POST['approved_id']) && $_POST['approved_id'] == $p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars(getFullName($p['fname'],$p['mname'],$p['lname'],$p['suffix'],$p['prefix'],$p['designation_name'])); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="col">
                        <label for="start_date">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                    </div>
                    <div class="col">
                        <label for="start_time">Start Time</label>
                        <input type="time" name="start_time" id="start_time" value="<?php echo htmlspecialchars($_POST['start_time'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="col">
                        <label for="end_date">End Date</label>
                        <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                    </div>
                    <div class="col">
                        <label for="end_time">End Time</label>
                        <input type="time" name="end_time" id="end_time" value="<?php echo htmlspecialchars($_POST['end_time'] ?? ''); ?>">
                    </div>
                </div>

                

                <div style="margin-bottom:12px;">
                    <label for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks"><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>
                </div>

                <div style="margin-bottom:15px;" >
                        <label for="verified_id">Verified By</label>
                        <input type="text" id="verified_id" class="verified-client-display" placeholder="Selected client will appear here" readonly>
                        <input type="hidden" name="verified_id" id="verified_id_hidden">
                </div>

                <div class="form-row">
                    <div class="col">
                        <label for="letter_file">Upload Letter (PDF)</label>
                        <input type="file" name="letter_file" id="letter_file" accept="application/pdf" onchange="displayFileName(this)">
                        <div id="file_upload_status" style="margin-top: 8px; font-size: 12px; color: var(--text-secondary);"></div>
                    </div>
                    <div class="col">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="Pending" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Scheduled" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="Completed" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>

                <div style="text-align:right; margin-top:18px;">
                    <a href="index.php" style="margin-right:10px; color:#6b7280;">← Back</a>
                    <button type="submit" class="btn">Create Job Order</button>
                </div>
            </form>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Client combobox search functionality
        (function() {
            const clientsData = <?php echo json_encode($clients); ?>;
            const searchInput = document.getElementById('client_search');
            const dropdown = document.getElementById('client_dropdown');
            const hiddenInput = document.getElementById('client_id');
            let highlightedIndex = -1;

            function renderDropdown(filter = '') {
                highlightedIndex = -1;
                dropdown.innerHTML = '';
                
                const filtered = clientsData.filter(client => {
                    const fullName = getClientDisplayName(client).toLowerCase();
                    return fullName.includes(filter.toLowerCase());
                });

                if (filtered.length === 0) {
                    dropdown.innerHTML = '<div class="client-dropdown-empty">No clients found</div>';
                    dropdown.classList.add('active');
                    return;
                }

                filtered.forEach((client, index) => {
                    const item = document.createElement('div');
                    item.className = 'client-dropdown-item';
                    item.textContent = getClientDisplayName(client);
                    item.dataset.id = client.id;
                    item.dataset.index = index;

                    item.addEventListener('click', () => selectClient(client));
                    item.addEventListener('mouseenter', () => {
                        document.querySelectorAll('.client-dropdown-item').forEach(el => el.classList.remove('highlighted'));
                        item.classList.add('highlighted');
                        highlightedIndex = index;
                    });

                    dropdown.appendChild(item);
                });

                if (filtered.length > 0) {
                    dropdown.classList.add('active');
                }
            }

            function getClientDisplayName(client) {
                let name = '';
                if (client.prefix) name += client.prefix + ' ';
                if (client.fname) name += client.fname + ' ';
                if (client.mname) name += client.mname + ' ';
                if (client.lname) name += client.lname + ' ';
                if (client.suffix) name += client.suffix;
                name = name.trim();
                if (client.agency) name += ' — ' + client.agency;
                return name;
            }

            function selectClient(client) {
                hiddenInput.value = client.id;
                searchInput.value = getClientDisplayName(client);
                dropdown.classList.remove('active');
                dropdown.innerHTML = '';
                highlightedIndex = -1;
                
                // Auto-populate Verified By with client name
                const verifiedDisplay = document.getElementById('verified_id');
                const verifiedHidden = document.getElementById('verified_id_hidden');
                verifiedDisplay.value = getClientDisplayName(client);
                verifiedHidden.value = client.id;
            }

            function handleKeydown(e) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const items = dropdown.querySelectorAll('.client-dropdown-item');
                    if (items.length > 0) {
                        highlightedIndex = (highlightedIndex + 1) % items.length;
                        document.querySelectorAll('.client-dropdown-item').forEach(el => el.classList.remove('highlighted'));
                        items[highlightedIndex].classList.add('highlighted');
                        items[highlightedIndex].scrollIntoView({ block: 'nearest' });
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const items = dropdown.querySelectorAll('.client-dropdown-item');
                    if (items.length > 0) {
                        highlightedIndex = (highlightedIndex - 1 + items.length) % items.length;
                        document.querySelectorAll('.client-dropdown-item').forEach(el => el.classList.remove('highlighted'));
                        items[highlightedIndex].classList.add('highlighted');
                        items[highlightedIndex].scrollIntoView({ block: 'nearest' });
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    const highlighted = dropdown.querySelector('.client-dropdown-item.highlighted');
                    if (highlighted) {
                        const clientId = highlighted.dataset.id;
                        const client = clientsData.find(c => c.id == clientId);
                        if (client) selectClient(client);
                    }
                } else if (e.key === 'Escape') {
                    dropdown.classList.remove('active');
                }
            }

            searchInput.addEventListener('input', (e) => {
                const filter = e.target.value;
                renderDropdown(filter);
            });

            searchInput.addEventListener('focus', () => {
                renderDropdown(searchInput.value);
            });

            searchInput.addEventListener('keydown', handleKeydown);

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.client-combobox')) {
                    dropdown.classList.remove('active');
                }
            });

            // Restore selected value on page load if it was previously submitted
            const selectedId = document.getElementById('client_id').value;
            if (selectedId) {
                const selectedClient = clientsData.find(c => c.id == selectedId);
                if (selectedClient) {
                    searchInput.value = getClientDisplayName(selectedClient);
                }
            }
        })();

        // Mode of Request "Other" toggle
        (function() {
            const modeSelect = document.getElementById('modeofrequest');
            const otherInput = document.getElementById('modeofrequest_other');

            function toggleOtherInput() {
                if (modeSelect.value === 'Other') {
                    otherInput.style.display = 'block';
                    otherInput.focus();
                } else {
                    otherInput.style.display = 'none';
                    otherInput.value = '';
                }
            }

            modeSelect.addEventListener('change', toggleOtherInput);

            // Initialize on page load
            toggleOtherInput();
        })();

        // File upload display
        function displayFileName(input) {
            const statusDiv = document.getElementById('file_upload_status');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const fileName = file.name;
                const fileSize = (file.size / 1024).toFixed(2);
                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);

                // Validate file type
                if (file.type !== 'application/pdf') {
                    statusDiv.innerHTML = '<span style="color: var(--danger-color); font-weight: 600;">❌ Only PDF files are allowed</span>';
                    input.value = '';
                    return;
                }

                // Validate file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    statusDiv.innerHTML = '<span style="color: var(--danger-color); font-weight: 600;">❌ File size exceeds 5MB limit (' + fileSizeMB + ' MB)</span>';
                    input.value = '';
                    return;
                }

                // Show success
                statusDiv.innerHTML = '<span style="color: var(--success-color); font-weight: 600;">✓ File selected: ' + fileName + ' (' + fileSize + ' KB)</span>';
            } else {
                statusDiv.innerHTML = '';
            }
        }
    </script>
</body>
</html>
