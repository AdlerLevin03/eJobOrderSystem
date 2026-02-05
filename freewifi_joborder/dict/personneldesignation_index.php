<?php
require_once '../config/connection.php';

$error_message = '';
$success_message = '';

// Handle assignment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_personnel_id'], $_POST['assign_designation_id'])) {
    $personnel_id = (int) $_POST['assign_personnel_id'];
    $designation_id = (int) $_POST['assign_designation_id'];

    try {
        // Check if personnel already has this designation assigned
        $check = $pdo->prepare("SELECT id FROM personnel_designations WHERE personnel_id = ? AND designation_id = ?");
        $check->execute([$personnel_id, $designation_id]);
        
        if ($check->rowCount() > 0) {
            $error_message = 'This personnel is already assigned to this designation.';
        } else {
            try {
                // Insert new assignment
                $insert = $pdo->prepare("INSERT INTO personnel_designations (personnel_id, designation_id) VALUES (?, ?)");
                $insert->execute([$personnel_id, $designation_id]);

                $success_message = 'Designation assigned successfully!';
            } catch (PDOException $e) {
                $error_message = 'Error assigning designation: ' . $e->getMessage();
            }
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = 'Error assigning designation: ' . $e->getMessage();
    }
}

// Handle unassign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unassign_id'])) {
    $assignment_id = (int) $_POST['unassign_id'];
    try {
        $pdo->prepare("DELETE FROM personnel_designations WHERE id = ?")
            ->execute([$assignment_id]);
        $success_message = 'Assignment removed successfully!';
    } catch (PDOException $e) {
        $error_message = 'Error removing assignment: ' . $e->getMessage();
    }
}

// Fetch all designations
try {
    $stmt = $pdo->prepare("SELECT id, designation_code, designation_name FROM designations WHERE status = 'Active' ORDER BY designation_name ASC");
    $stmt->execute();
    $designations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $designations = [];
    $error_message = 'Error fetching designations: ' . $e->getMessage();
}

// Fetch all active personnel
try {
    $stmt = $pdo->prepare("SELECT id, fname, mname, lname, suffix, prefix FROM personnels WHERE status = 'Active' ORDER BY fname ASC");
    $stmt->execute();
    $all_personnel = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_personnel = [];
}

// Fetch currently assigned personnel with designations
try {
    $query = "SELECT pd.id, p.id as personnel_id, p.fname, p.mname, p.lname, p.suffix, p.prefix, p.gmail, p.cpno,
                     d.id as designation_id, d.designation_name, d.designation_code
              FROM personnel_designations pd
              JOIN personnels p ON pd.personnel_id = p.id
              JOIN designations d ON pd.designation_id = d.id
              ORDER BY d.designation_name ASC, p.fname ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $assignments = [];
    $error_message = 'Error fetching assignments: ' . $e->getMessage();
}

// Count personnel by designation type
$stat_counts = [
    'issuer' => 0,
    'performer' => 0,
    'approver' => 0,
    'endorser' => 0
];

try {
    $designationMap = ['Issuer', 'Performer', 'Approver', 'Endorser'];
    foreach ($designationMap as $designationType) {
        $countStmt = $pdo->prepare(
            "SELECT COUNT(DISTINCT pd.personnel_id) as count 
             FROM personnel_designations pd 
             JOIN designations d ON pd.designation_id = d.id 
             WHERE d.designation_name = ?"
        );
        $countStmt->execute([$designationType]);
        $result = $countStmt->fetch(PDO::FETCH_ASSOC);
        $stat_counts[strtolower($designationType)] = (int)$result['count'];
    }
} catch (PDOException $e) {
    // Keep default values if query fails
}

function getFullName($fname, $mname, $lname, $suffix, $prefix = '') {
    $name = '';
    if (!empty($prefix)) $name .= $prefix . ' ';
    if (!empty($fname)) $name .= $fname . ' ';
    if (!empty($mname)) $name .= $mname . ' ';
    if (!empty($lname)) $name .= $lname . ' ';
    if (!empty($suffix)) $name .= $suffix;
    return trim($name);
}

function getDesignationColor($designation_name) {
    $colorMap = [
        'Issuer' => '#ef4444',      // red
        'Performer' => '#8b5cf6',   // purple
        'Approver' => '#f59e0b',    // orange
        'Endorser' => '#10b981',    // green
    ];
    return $colorMap[$designation_name] ?? '#3b82f6'; // default blue
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Personnel to Designations - Free WiFi Job Order System</title>
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
            max-width: 1200px;
            width: 100%;
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
            display: flex;
            align-items: center;
            gap: 10px;
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

        .controls {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 20px;
            flex-wrap: nowrap;
            margin-bottom: 40px;
            width: 100%;
        }

        .btn-assign-new {
            background-color: var(--success-color);
            color: white;
            padding: 12px 26px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            height: 44px;
            flex-shrink: 0;
        }

        .btn-assign-new:hover {
            background-color: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .table-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-wrapper {
            overflow-x: auto;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
        }

        th {
            padding: 16px;
            text-align: left;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: #f9fafb;
        }

        .personnel-name {
            font-weight: 700;
            color: var(--text-primary);
        }

        .designation-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-change {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-change:hover {
            background-color: #d97706;
            transform: translateY(-2px);
        }

        .btn-remove {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-remove:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-state h2 {
            font-size: 24px;
            color: var(--text-primary);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .empty-state p {
            margin-bottom: 20px;
        }

        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 50px;
        }

        .stat-card {
            padding: 30px;
            border-radius: 14px;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
            border-radius: 50%;
        }

        .stat-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            transform: translateY(-4px);
        }

        .stat-card.issuer {
            background: #ef4444;
        }

        .stat-card.issuer .stat-number,
        .stat-card.issuer .stat-label {
            color: white;
        }

        .stat-card.performer {
            background: #8b5cf6;
        }

        .stat-card.performer .stat-number,
        .stat-card.performer .stat-label {
            color: white;
        }

        .stat-card.approver {
            background: #f59e0b;
        }

        .stat-card.approver .stat-number,
        .stat-card.approver .stat-label {
            color: white;
        }

        .stat-card.endorser {
            background: #10b981;
        }

        .stat-card.endorser .stat-number,
        .stat-card.endorser .stat-label {
            color: white;
        }

        .stat-number {
            font-size: 40px;
            font-weight: 900;
            margin-bottom: 12px;
            letter-spacing: -1px;
            position: relative;
            z-index: 1;
        }

        .stat-label {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
        }

        /* Search Box */
        .search-container {
            display: flex;
            gap: 20px;
            flex: 1;
            flex-wrap: nowrap;
            align-items: center;
        }

        .search-box {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 40px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: white;
            height: 44px;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(30, 64, 175, 0.15);
        }

        .search-box::before {
            content: '🔍';
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            z-index: 2;
        }

        .search-results-info {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 600;
            white-space: nowrap;
            background: linear-gradient(135deg, #f0f4ff 0%, #f8faff 100%);
            padding: 10px 18px;
            border-radius: 8px;
            border: 1px solid #dbeafe;
            height: 44px;
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
        }

        .no-results-icon {
            font-size: 48px;
            margin-bottom: 15px;
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
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 90%;
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            margin-bottom: 30px;
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 15px;
        }

        .modal-header h2 {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
        }

        .form-group select {
            padding: 12px 14px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-group select:hover {
            border-color: var(--primary-light);
        }

        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(30, 64, 175, 0.15);
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .btn-modal {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-modal-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-modal-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }

        .btn-modal-cancel {
            background-color: var(--text-secondary);
            color: white;
        }

        .btn-modal-cancel:hover {
            background-color: #4b5563;
            transform: translateY(-2px);
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

            .controls {
                flex-direction: column;
            }

            .btn-assign-new {
                width: 100%;
                justify-content: center;
            }

            th, td {
                padding: 12px 8px;
                font-size: 12px;
            }

            .modal-content {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main>
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>PERSONNEL'S DESIGNATION MANAGEMENT</h1>
                <p>Manage and assign personnel roles and designations</p>
            </div>

            <!-- Alerts -->
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

            

            <!-- Stats Cards -->
            <div class="stats-grid">
                 <div class="stat-card approver">
                    <div class="stat-number"><?php echo $stat_counts['approver']; ?></div>
                    <div class="stat-label">Approver</div>
                </div>
                <div class="stat-card endorser">
                    <div class="stat-number"><?php echo $stat_counts['endorser']; ?></div>
                    <div class="stat-label">Endorser</div>
                </div>
                <div class="stat-card issuer">
                    <div class="stat-number"><?php echo $stat_counts['issuer']; ?></div>
                    <div class="stat-label">Issuer</div>
                </div>
                <div class="stat-card performer">
                    <div class="stat-number"><?php echo $stat_counts['performer']; ?></div>
                    <div class="stat-label">Performer</div>
                </div>
            </div>

            <!-- Controls and Search -->
            <div class="controls">
                <button class="btn-assign-new" onclick="openAssignModal()">➕ Assign Designation</button>
                <div class="search-container">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search by personnel name, email, contact, or designation..." onkeyup="filterTable()">
                    </div>
                    <div class="search-results-info">
                        <span id="resultsCount">Showing <?php echo count($assignments); ?> results</span>
                    </div>
                </div>
            </div>

            <!-- Assignments Table -->
            <?php if (!empty($assignments)): ?>
                <div class="table-title">
                    <span>👥</span>
                    <span>Current Assignments</span>
                </div>
                <div id="noResultsMessage" class="no-results" style="display: none;">
                    <div class="no-results-icon">🔍</div>
                    <h3>No Results Found</h3>
                    <p>Try adjusting your search terms</p>
                </div>
                <div class="table-wrapper" id="tableWrapper">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 25%">Designation</th>
                                <th style="width: 30%">Personnel Name</th>
                                <th style="width: 15%">Email</th>
                                <th style="width: 15%">Contact</th>
                                <th style="width: 15%; text-align: center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="assignmentTableBody">
                            <?php foreach ($assignments as $assign): ?>
                                <tr class="searchable-row" data-searchtext="<?php echo htmlspecialchars(strtolower(getFullName($assign['fname'] ?? '', $assign['mname'] ?? '', $assign['lname'] ?? '', $assign['suffix'] ?? '', $assign['prefix'] ?? '') . ' ' . ($assign['gmail'] ?? '') . ' ' . ($assign['cpno'] ?? '') . ' ' . ($assign['designation_name'] ?? ''))); ?>">
                                    <td>
                                        <span class="designation-badge" style="background-color: <?php echo getDesignationColor($assign['designation_name'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($assign['designation_name']); ?>
                                        </span>
                                    </td>
                                    <td class="personnel-name">
                                        <?php echo htmlspecialchars(getFullName($assign['fname'] ?? '', $assign['mname'] ?? '', $assign['lname'] ?? '', $assign['suffix'] ?? '', $assign['prefix'] ?? '')); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($assign['gmail'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($assign['cpno'] ?? '-'); ?></td>
                                    <td class="action-buttons" style="justify-content: center">
                                        <button type="button" class="btn-action btn-remove" onclick="openRemoveConfirmModal(<?php echo (int)$assign['id']; ?>, '<?php echo htmlspecialchars(getFullName($assign['fname'] ?? '', $assign['mname'] ?? '', $assign['lname'] ?? '', $assign['suffix'] ?? '', $assign['prefix'] ?? '')); ?>', '<?php echo htmlspecialchars($assign['designation_name']); ?>')">Remove</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👥</div>
                    <h2>No Assignments Yet</h2>
                    <p>No personnel have been assigned to designations yet.</p>
                    <button class="btn-assign-new" onclick="openAssignModal()">➕ Create First Assignment</button>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <!-- Assignment Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Assign Personnel to Designation</h2>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="assignPersonnelSelect">Select Personnel *</label>
                    <select id="assignPersonnelSelect" name="assign_personnel_id" required>
                        <option value="">-- Choose Personnel --</option>
                        <?php foreach ($all_personnel as $p): ?>
                            <option value="<?php echo (int)$p['id']; ?>">
                                <?php echo htmlspecialchars(getFullName($p['fname'] ?? '', $p['mname'] ?? '', $p['lname'] ?? '', $p['suffix'] ?? '', $p['prefix'] ?? '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="assignDesignationSelect">Select Designation *</label>
                    <select id="assignDesignationSelect" name="assign_designation_id" required>
                        <option value="">-- Choose Designation --</option>
                        <?php foreach ($designations as $d): ?>
                            <option value="<?php echo (int)$d['id']; ?>">
                                <?php echo htmlspecialchars($d['designation_name'] . ' (' . $d['designation_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-modal-cancel" onclick="closeAssignModal()">Cancel</button>
                    <button type="submit" class="btn-modal btn-modal-primary">✓ Assign</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Remove Confirmation Modal -->
    <div id="removeConfirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Removal</h2>
            </div>
            <div style="margin-bottom: 30px;">
                <p style="color: var(--text-primary); margin-bottom: 15px; font-size: 15px;">Are you sure you want to remove this designation assignment?</p>
                <div style="background-color: #f9fafb; padding: 15px; border-radius: 8px; border-left: 4px solid var(--warning-color);">
                    <div style="margin-bottom: 10px;">
                        <span style="font-weight: 600; color: var(--text-secondary); display: block; font-size: 12px; text-transform: uppercase;">Personnel</span>
                        <span id="removeConfirmPersonnel" style="font-weight: 700; color: var(--text-primary); font-size: 14px;"></span>
                    </div>
                    <div>
                        <span style="font-weight: 600; color: var(--text-secondary); display: block; font-size: 12px; text-transform: uppercase;">Designation</span>
                        <span id="removeConfirmDesignation" style="font-weight: 700; color: var(--text-primary); font-size: 14px;"></span>
                    </div>
                </div>
            </div>

            <form id="removeConfirmForm" method="POST" action="">
                <input type="hidden" id="removeConfirmId" name="unassign_id" value="">
                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-modal-cancel" onclick="closeRemoveConfirmModal()">Cancel</button>
                    <button type="submit" class="btn-modal" style="background-color: var(--danger-color); color: white;">✓ Remove Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAssignModal() {
            document.getElementById('assignPersonnelSelect').value = '';
            document.getElementById('assignPersonnelSelect').disabled = false;
            document.getElementById('assignDesignationSelect').value = '';
            document.getElementById('assignModal').classList.add('show');
        }

        function closeAssignModal() {
            document.getElementById('assignModal').classList.remove('show');
        }

        function openRemoveConfirmModal(assignmentId, personnelName, designationName) {
            document.getElementById('removeConfirmId').value = assignmentId;
            document.getElementById('removeConfirmPersonnel').textContent = personnelName;
            document.getElementById('removeConfirmDesignation').textContent = designationName;
            document.getElementById('removeConfirmModal').classList.add('show');
        }

        function closeRemoveConfirmModal() {
            document.getElementById('removeConfirmModal').classList.remove('show');
        }

        function filterTable() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('.searchable-row');
            const tableWrapper = document.getElementById('tableWrapper');
            const noResultsMessage = document.getElementById('noResultsMessage');
            let visibleCount = 0;

            if (searchTerm === '') {
                // Show all rows
                rows.forEach(row => {
                    row.style.display = '';
                    visibleCount++;
                });
                tableWrapper.style.display = '';
                noResultsMessage.style.display = 'none';
            } else {
                // Filter rows based on search term
                rows.forEach(row => {
                    const searchText = row.getAttribute('data-searchtext');
                    if (searchText.includes(searchTerm)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Show/hide no results message
                if (visibleCount === 0) {
                    tableWrapper.style.display = 'none';
                    noResultsMessage.style.display = 'block';
                } else {
                    tableWrapper.style.display = '';
                    noResultsMessage.style.display = 'none';
                }
            }

            // Update results count
            document.getElementById('resultsCount').textContent = `Showing ${visibleCount} of <?php echo count($assignments); ?> results`;
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const assignModal = document.getElementById('assignModal');
            const removeModal = document.getElementById('removeConfirmModal');
            if (event.target === assignModal) {
                closeAssignModal();
            }
            if (event.target === removeModal) {
                closeRemoveConfirmModal();
            }
        });
    </script>
</body>
</html>
