<?php
require_once __DIR__ . '/../config/connection.php';

$error_message = '';
$success_message = '';
$selected_job_order = null;
$assigned_performers = [];

// Handle AJAX request to fetch assigned performers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_performers') {
    $job_order_id = !empty($_POST['job_order_id']) ? (int)$_POST['job_order_id'] : null;
    
    if ($job_order_id) {
        try {
            // Get assigned performers for this job order
            $stmt = $pdo->prepare("
                SELECT jp.personnel_id
                FROM joborder_performers jp
                WHERE jp.job_order_id = ?
            ");
            $stmt->execute([$job_order_id]);
            $assigned = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $assigned_ids = array_map('intval', $assigned);
            
            // Get all available performers
            $stmt = $pdo->prepare("
                SELECT p.id, p.fname, p.mname, p.lname, p.suffix, p.prefix
                FROM personnels p
                INNER JOIN personnel_designations pd ON p.id = pd.personnel_id
                INNER JOIN designations d ON pd.designation_id = d.id
                WHERE p.status = 'Active' AND d.designation_name = 'Performer'
                ORDER BY p.fname ASC
            ");
            $stmt->execute();
            $all_performers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get assigned performers with timestamps
            $stmt = $pdo->prepare("
                SELECT jp.id, jp.personnel_id, p.fname, p.mname, p.lname, p.suffix, p.prefix, jp.assigned_at
                FROM joborder_performers jp
                INNER JOIN personnels p ON jp.personnel_id = p.id
                WHERE jp.job_order_id = ?
                ORDER BY p.fname ASC
            ");
            $stmt->execute([$job_order_id]);
            $performers_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'performers' => [],
                'available_performers' => [],
                'assigned_ids' => $assigned_ids
            ];
            
            // Add assigned performers
            foreach ($performers_list as $p) {
                $response['performers'][] = [
                    'personnel_id' => (int)$p['personnel_id'],
                    'job_order_id' => (int)$job_order_id,
                    'name' => getFullName($p['fname'], $p['mname'], $p['lname'], $p['suffix'], $p['prefix']),
                    'assigned_at' => $p['assigned_at']
                ];
            }
            
            // Add all available performers (both assigned and unassigned)
            foreach ($all_performers as $p) {
                $response['available_performers'][] = [
                    'id' => (int)$p['id'],
                    'name' => getFullName($p['fname'], $p['mname'], $p['lname'], $p['suffix'], $p['prefix']),
                    'assigned' => in_array((int)$p['id'], $assigned_ids)
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

// Fetch active performers
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.fname, p.mname, p.lname, p.suffix, p.prefix
        FROM personnels p
        INNER JOIN personnel_designations pd ON p.id = pd.personnel_id
        INNER JOIN designations d ON pd.designation_id = d.id
        WHERE p.status = 'Active' AND d.designation_name = 'Performer'
        ORDER BY p.fname ASC
    ");
    $stmt->execute();
    $performers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $performers = [];
}

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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_order_id = !empty($_POST['job_order_id']) ? (int)$_POST['job_order_id'] : null;
    $action = !empty($_POST['action']) ? trim($_POST['action']) : null;
    $personnel_ids = !empty($_POST['personnel_id']) ? $_POST['personnel_id'] : [];

    // Handle add multiple performers
    if ($action === 'add' && $job_order_id && !empty($personnel_ids)) {
        // Ensure personnel_ids is an array
        if (!is_array($personnel_ids)) {
            $personnel_ids = [$personnel_ids];
        }
        
        // Convert all to integers and filter
        $personnel_ids = array_map('intval', array_filter($personnel_ids));
        
        if (!empty($personnel_ids)) {
            try {
                $pdo->beginTransaction();
                
                $added_count = 0;
                $duplicate_count = 0;
                
                foreach ($personnel_ids as $personnel_id) {
                    // Check if performer is already assigned
                    $stmt = $pdo->prepare("SELECT id FROM joborder_performers WHERE job_order_id = ? AND personnel_id = ?");
                    $stmt->execute([$job_order_id, $personnel_id]);
                    
                    if (!$stmt->fetch()) {
                        // Add performer
                        $insert = $pdo->prepare("INSERT INTO joborder_performers (job_order_id, personnel_id) VALUES (?, ?)");
                        $insert->execute([$job_order_id, $personnel_id]);
                        $added_count++;
                    } else {
                        $duplicate_count++;
                    }
                }
                
                $pdo->commit();
                
                if ($added_count > 0) {
                    $success_message = $added_count . ' performer(s) assigned successfully.';
                    if ($duplicate_count > 0) {
                        $success_message .= ' (' . $duplicate_count . ' already assigned)';
                    }
                } elseif ($duplicate_count > 0) {
                    $error_message = 'All selected performers are already assigned to this job order.';
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error_message = 'Error assigning performers: ' . $e->getMessage();
            }
        } else {
            $error_message = 'No performers selected.';
        }
    }

    // Handle remove performer
    if ($action === 'remove' && $job_order_id && !empty($_POST['personnel_id'])) {
        $personnel_id = (int)$_POST['personnel_id'];
        try {
            $pdo->beginTransaction();

            $delete = $pdo->prepare("DELETE FROM joborder_performers WHERE job_order_id = ? AND personnel_id = ?");
            $delete->execute([$job_order_id, $personnel_id]);

            $pdo->commit();
            $success_message = 'Performer removed successfully.';
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error_message = 'Error removing performer: ' . $e->getMessage();
        }
    }

    // Keep the job order selected after action
    if ($job_order_id) {
        try {
            $stmt = $pdo->prepare("SELECT id, joborderid, client_id FROM job_orders WHERE id = ?");
            $stmt->execute([$job_order_id]);
            $selected_job_order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($selected_job_order) {
                // Get client name
                $stmt = $pdo->prepare("SELECT prefix, fname, mname, lname, suffix FROM clients WHERE id = ?");
                $stmt->execute([$selected_job_order['client_id']]);
                $client = $stmt->fetch(PDO::FETCH_ASSOC);
                $selected_job_order['client_name'] = $client ? getFullName($client['fname'], $client['mname'], $client['lname'], $client['suffix'], $client['prefix']) : 'Unknown';
            }
        } catch (PDOException $e) {
            $selected_job_order = null;
        }

        // Fetch assigned performers
        try {
            $stmt = $pdo->prepare("
                SELECT jp.id, jp.personnel_id, p.fname, p.mname, p.lname, p.suffix, p.prefix, jp.assigned_at
                FROM joborder_performers jp
                INNER JOIN personnels p ON jp.personnel_id = p.id
                WHERE jp.job_order_id = ?
                ORDER BY p.fname ASC
            ");
            $stmt->execute([$job_order_id]);
            $assigned_performers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $assigned_performers = [];
        }
    }
}

// Fetch all job orders with status and client info
try {
    $stmt = $pdo->prepare("
        SELECT jo.id, jo.joborderid, jo.client_id, jo.status, jo.date_received, c.prefix, c.fname, c.mname, c.lname, c.suffix, c.agency
        FROM job_orders jo
        LEFT JOIN clients c ON jo.client_id = c.id
        ORDER BY jo.id DESC
    ");
    $stmt->execute();
    $job_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $job_orders = [];
}

// Count performers for each job order
$performer_counts = [];
$job_order_performers = [];
if (!empty($job_orders)) {
    try {
        $stmt = $pdo->prepare("SELECT job_order_id, COUNT(*) as count FROM joborder_performers GROUP BY job_order_id");
        $stmt->execute();
        $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($counts as $c) {
            $performer_counts[$c['job_order_id']] = $c['count'];
        }
    } catch (PDOException $e) {
    }
    
    // Fetch performers with their designations for each job order
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                jp.job_order_id, 
                p.id as personnel_id,
                p.fname, p.mname, p.lname, p.suffix, p.prefix,
                d.designation_name
            FROM joborder_performers jp
            INNER JOIN personnels p ON jp.personnel_id = p.id
            LEFT JOIN personnel_designations pd ON p.id = pd.personnel_id
            LEFT JOIN designations d ON pd.designation_id = d.id WHERE d.designation_name = 'Performer' OR d.designation_name IS NULL
            ORDER BY jp.job_order_id, p.fname ASC
        ");
        $stmt->execute();
        $performers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($performers as $performer) {
            $job_order_id = $performer['job_order_id'];
            $personnel_id = $performer['personnel_id'];
            
            // Skip duplicates by checking if this personnel_id already exists for this job order
            if (!isset($job_order_performers[$job_order_id])) {
                $job_order_performers[$job_order_id] = [];
            }
            
            $already_exists = false;
            foreach ($job_order_performers[$job_order_id] as $existing) {
                if ($existing['personnel_id'] == $personnel_id) {
                    $already_exists = true;
                    break;
                }
            }
            
            if (!$already_exists) {
                $job_order_performers[$job_order_id][] = [
                    'personnel_id' => $personnel_id,
                    'name' => getFullName($performer['fname'], $performer['mname'], $performer['lname'], $performer['suffix'], $performer['prefix']),
                    'designation' => $performer['designation_name'] ?? 'Performer'
                ];
            }
        }
    } catch (PDOException $e) {
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Assign Performers to Job Order</title>
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
            display: flex; 
            justify-content: center; 
            align-items: flex-start; 
        }

        .container {
            max-width: 1500px;
            width: 100%;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);   
            padding: 36px;
        }

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
            display: flex; 
            align-items: center; 
            gap: 12px; 
            margin: 18px 0 12px 0;
        }

        .section-icon {
            background: #6d28d9; color: white; 
            width: 34px; 
            height: 34px; 
            border-radius: 8px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 16px;
        }

        .section-title { 
            color: var(--primary-color); 
            font-weight: 800; 
            letter-spacing: 0.6px; 
            text-transform: uppercase; 
            font-size: 14px; 
        }

        .section-underline { 
            height: 3px; 
            background: var(--accent-color); 
            width: 100%; 
            margin-top: 8px; 
            border-radius: 3px; 
        }

        .form-row { 
            display: flex; 
            gap: 16px; 
            margin-bottom: 16px; 
        }

        .form-row .col {
            flex: 1; 
        }

        label { 
            display: block; 
            font-weight: 700; 
            margin-bottom: 8px; 
            font-size: 13px; 
            color: var(--text-primary); 
        }

        input[type=text], select, textarea {
            width: 100%; 
            padding: 14px 16px; 
            border: 1px solid var(--border-color); 
            border-radius: 10px; 
            background: white; 
            color: var(--text-primary);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.06);
            transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
        }

        input[type=text]:focus, select:focus, textarea:focus {
            outline: none; 
            border-color: var(--primary-color); 
            transform: translateY(-2px); 
            box-shadow: 0 12px 30px rgba(30, 64, 175, 0.08);
        }

        .joborder-search-input {
            width: 100% !important;
            padding: 14px 16px !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 10px !important;
            background: white !important;
            color: var(--text-primary) !important;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.06) !important;
            transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease !important;
        }

        .joborder-search-input:focus {
            outline: none !important;
            border-color: var(--primary-color) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 30px rgba(30, 64, 175, 0.08) !important;
        }

        .joborder-dropdown-list {
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
        }

        .joborder-dropdown-list.active {
            display: block;
        }

        .joborder-dropdown-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-color);
            transition: background-color .08s ease;
            color: var(--text-primary);
            font-size: 14px;
        }

        .joborder-dropdown-item:hover,
        .joborder-dropdown-item.highlighted {
            background-color: #f3f4f6;
            color: var(--primary-color);
            font-weight: 600;
        }

        .joborder-dropdown-item:last-child {
            border-bottom: none;
        }

        .joborder-dropdown-empty {
            padding: 12px 16px;
            color: var(--text-secondary);
            font-size: 13px;
            text-align: center;
        }

        .joborder-combobox {
            position: relative;
            width: 100%;
        }

        .job-orders-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .job-orders-table thead {
            background: #f3f4f6;
        }

        .job-orders-table th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 700;
            color: var(--text-primary);
            font-size: 13px;
            border-bottom: 2px solid var(--border-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .job-orders-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            font-size: 14px;
        }

        .job-orders-table tbody tr {
            transition: background-color .2s ease;
        }

        .job-orders-table tbody tr:hover {
            background-color: #f9fafb;
        }

        .job-orders-table tbody tr:last-child td {
            border-bottom: none;
        }

        .job-order-id {
            font-weight: 600;
            color: var(--primary-color);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-scheduled {
            background: #dbeafe;
            color: #0c2340;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .performer-count {
            background: #ede9fe;
            color: #5b21b6;
            padding: 4px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            display: inline-block;
        }

        .performer-names-cell {
            display: flex;
            flex-direction: column;
            gap: 6px;
            max-width: 400px;
        }

        .performer-name-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 13px;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.4;
        }

        .table-actions {
            display: flex;
            gap: 8px;
        }

        .performer-list {
            background: #f9fafb;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .performer-item {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all .2s ease;
        }

        .performer-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-color: var(--primary-color);
        }

        .performer-item:last-child {
            margin-bottom: 0;
        }

        .performer-name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .performer-date {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .btn { 
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%); 
            color: white; 
            padding: 10px 16px; 
            border-radius: 8px; 
            border: none; 
            cursor: pointer; 
            font-weight: 700;
            transition: transform .12s ease, box-shadow .12s ease;
            font-size: 13px;
        }

        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 12px 30px rgba(30, 64, 175, 0.12); 
        }

        .btn-danger {
            background: linear-gradient(135deg, #f87171 0%, var(--danger-color) 100%);
        }

        .btn-danger:hover {
            box-shadow: 0 12px 30px rgba(239, 68, 68, 0.12);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .alert { 
            padding: 12px 14px; 
            border-radius: 8px; 
            margin-bottom: 16px; 
            font-weight: 600; 
        }
        
        .alert-error { 
            background: #fee2e2; color: var(--danger-color); 
            border-left: 4px solid var(--danger-color); 
        }

        .alert-success { 
            background: #dcfce7; color: var(--success-color); 
            border-left: 4px solid var(--success-color); 
        }

        .required-indicator { 
            color: var(--danger-color);
            font-weight: 700; 
            margin-left: 4px; 
        }

        .no-performers {
            text-align: center;
            padding: 32px 16px;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .form-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .form-section.full {
            grid-column: 1 / -1;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn .2s ease;
            overflow-y: auto;
        }

        /* Remove confirmation modal should appear above performer modal */
        #removeConfirmModal {
            z-index: 3000;
        }

        .modal.active {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 20px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp .3s ease;
            margin-top: 20px;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 24px;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }

        .modal-header h2 {
            font-size: 22px;
            color: var(--primary-color);
            font-weight: 800;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--text-secondary);
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .2s ease;
            border-radius: 6px;
        }

        .modal-close:hover {
            background: rgba(0, 0, 0, 0.1);
            color: var(--text-primary);
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border-color);
            background: #f9fafb;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .modal-info-box {
            background: #f0f9ff;
            border-left: 4px solid var(--accent-color);
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .modal-info-box p {
            margin: 4px 0;
            font-size: 13px;
        }

        .modal-info-box .job-order-title {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 14px;
        }

        .modal-info-box .client-name {
            color: var(--text-secondary);
        }

        /* Performer Checkbox List */
        .performer-checkbox-container {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 12px;
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 16px;
        }

        .performer-checkbox-item {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            border-radius: 6px;
            transition: background-color .2s ease;
            margin-bottom: 4px;
        }

        .performer-checkbox-item:hover {
            background-color: #f3f4f6;
        }

        .performer-checkbox-item:last-child {
            margin-bottom: 0;
        }

        .performer-checkbox-item input[type="checkbox"] {
            margin-right: 12px;
            cursor: pointer;
            width: 16px;
            height: 16px;
        }

        .performer-checkbox-item label {
            flex: 1;
            margin: 0;
            cursor: pointer;
            font-weight: 500;
        }

        .performer-empty-message {
            padding: 20px;
            text-align: center;
            color: var(--text-secondary);
            font-size: 13px;
        }

        .selected-performers-badge {
            display: inline-block;
            background: #ede9fe;
            color: #5b21b6;
            padding: 4px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            margin-left: 8px;
        }

        /* Remove Confirmation Modal */
        #removeConfirmModal .modal-header {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-bottom-color: var(--danger-color);
        }

        #removeConfirmModal .modal-header h2 {
            color: var(--danger-color);
        }

        .remove-confirm-text {
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--danger-color);
        }

        .remove-confirm-text p {
            margin: 0;
            color: var(--text-primary);
            font-weight: 500;
        }

        .remove-confirm-text .performer-name-confirm {
            color: var(--danger-color);
            font-weight: 700;
            font-size: 15px;
            margin-top: 8px;
        }

        #removeConfirmModal .modal-footer {
            background: white;
        }

        .btn-confirm-remove {
            background: linear-gradient(135deg, #f87171 0%, var(--danger-color) 100%) !important;
        }

        .btn-confirm-remove:hover {
            box-shadow: 0 12px 30px rgba(239, 68, 68, 0.2) !important;
        }

        @media (max-width: 768px) {
            .form-section {
                grid-template-columns: 1fr;
            }
            .container { padding: 20px; }
            .page-header h1 { font-size: 26px; }
            .performer-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .modal-content {
                max-width: 100%;
                margin-top: 0;
            }
            .modal-header {
                padding: 16px;
            }
            .modal-header h2 {
                font-size: 18px;
            }
            .modal-body {
                padding: 16px;
            }
            .modal-footer {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main>
        <div class="container">
            <div class="page-header">
                <h1>Assign Performers</h1>
                <p>Manage personnel performers assignment for job orders</p>
                <div class="accent-hr"></div>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <div class="section-header">
                <div class="section-icon">📋</div>
                <div>
                    <div class="section-title">Job Orders List</div>
                    <div class="section-underline"></div>
                </div>
            </div>

            <?php if (empty($job_orders)): ?>
                <div class="no-performers" style="padding: 40px 20px;">
                    No job orders found. Create a job order first.
                </div>
            <?php else: ?>
                <div style="overflow-x: auto; margin-bottom: 24px;">
                    <table class="job-orders-table">
                        <thead>
                            <tr>
                                <th>Job Order ID</th>
                                <th>Client</th>
                                <th>Agency</th>
                                <th>Status</th>
                                <th>Date Received</th>
                                <th>Number of Performer</th>
                                <th>Name's of Performer</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($job_orders as $jo): ?>
                                <tr data-job-order-id="<?php echo (int)$jo['id']; ?>">
                                    <td><span class="job-order-id"><?php echo htmlspecialchars($jo['joborderid']); ?></span></td>
                                    <td><?php echo htmlspecialchars(getFullName($jo['fname'] ?? '', $jo['mname'] ?? '', $jo['lname'] ?? '', $jo['suffix'] ?? '', $jo['prefix'] ?? '')); ?></td>
                                    <td>
                                        <?php 
                                            $agency = !empty($jo['agency']) ? htmlspecialchars($jo['agency']) : '<span style="color: #d1d5db;">N/A</span>';
                                            echo $agency;
                                        ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($jo['status']); ?>">
                                            <?php echo htmlspecialchars($jo['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(date('M d, Y', strtotime($jo['date_received'] ?? 'now'))); ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $performers = $job_order_performers[$jo['id']] ?? [];
                                            echo count($performers);
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $performers = $job_order_performers[$jo['id']] ?? [];
                                            if (empty($performers)): 
                                        ?>
                                            <span style="color: #d1d5db;">—</span>
                                        <?php else: ?>
                                            <div class="performer-names-cell">
                                                <?php foreach ($performers as $perf): ?>
                                                    <div class="performer-name-badge"><?php echo htmlspecialchars($perf['name']); ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button type="button" class="btn btn-sm" onclick="openPerformerModal(<?php echo (int)$jo['id']; ?>, '<?php echo htmlspecialchars(addslashes($jo['joborderid'])); ?>', '<?php echo htmlspecialchars(addslashes(getFullName($jo['fname'] ?? '', $jo['mname'] ?? '', $jo['lname'] ?? '', $jo['suffix'] ?? '', $jo['prefix'] ?? ''))); ?>')">
                                                Manage
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Remove Confirmation Modal -->
            <div id="removeConfirmModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Remove Performer</h2>
                        <button type="button" class="modal-close" onclick="closeRemoveConfirmModal()">&times;</button>
                    </div>

                    <div class="modal-body">
                        <div class="remove-confirm-text">
                            <p>Are you sure you want to remove this performer from the job order?</p>
                            <div class="performer-name-confirm" id="removeConfirmPerformerName">--</div>
                        </div>
                        <p style="color: var(--text-secondary); font-size: 13px; margin-bottom: 20px;">This action cannot be undone.</p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn" style="background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);" onclick="closeRemoveConfirmModal()">Cancel</button>
                        <form method="POST" action="" style="display: inline;" id="removeConfirmForm">
                            <input type="hidden" name="job_order_id" id="removeConfirmJobOrderId">
                            <input type="hidden" name="personnel_id" id="removeConfirmPersonnelId">
                            <button type="submit" name="action" value="remove" class="btn btn-confirm-remove">Remove Performer</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal for Managing Performers -->
            <div id="performerModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="modalTitle">Assign Performer</h2>
                        <button type="button" class="modal-close" onclick="closePerformerModal()">&times;</button>
                    </div>

                    <div class="modal-body">
                        <form method="POST" action="" id="performerForm">
                            <input type="hidden" name="job_order_id" id="modalJobOrderId">
                            
                            <div class="modal-info-box">
                                <p class="job-order-title" id="modalJobOrderTitle">--</p>
                                <p class="client-name">Client: <span id="modalClientName">--</span></p>
                            </div>

                            <div style="margin-bottom: 16px;">
                                <label style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <span>Select Performers<span class="required-indicator">*</span></span>
                                    <span class="selected-performers-badge" id="selectedCount">0 selected</span>
                                </label>
                                <div class="performer-checkbox-container" id="performerCheckboxContainer">
                                    <div class="performer-empty-message">Loading performers...</div>
                                </div>
                            </div>

                            <div style="display: flex; gap: 10px; margin-bottom: 24px;">
                                <button type="submit" name="action" value="add" class="btn" id="assignBtn" disabled>Assign Selected Performers</button>
                                <button type="button" onclick="clearSelectedPerformers()" class="btn" style="background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);">Clear Selection</button>
                            </div>
                        </form>

                        <div class="section-header">
                            <div class="section-icon">👥</div>
                            <div>
                                <div class="section-title">Assigned Performers (<span id="performerCount">0</span>)</div>
                                <div class="section-underline"></div>
                            </div>
                        </div>

                        <div class="performer-list" id="performerListContainer">
                            <div class="no-performers">
                                No performers assigned yet. Select a performer above and click "Assign Performer" to add.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn" style="background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);" onclick="closePerformerModal()">Close</button>
                    </div>
                </div>
            </div>

            <?php if ($selected_job_order): ?>
                <!-- Hidden script to auto-open modal if job order was just submitted -->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        openPerformerModal(<?php echo (int)$selected_job_order['id']; ?>, '<?php echo htmlspecialchars(addslashes($selected_job_order['joborderid'])); ?>', '<?php echo htmlspecialchars(addslashes($selected_job_order['client_name'])); ?>', true);
                    });
                </script>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Modal Management
        const performerModal = document.getElementById('performerModal');
        const removeConfirmModal = document.getElementById('removeConfirmModal');
        let currentJobOrderId = null;
        let selectedPerformerIds = new Set();
        let removeConfirmJobOrderId = null;
        let removeConfirmPersonnelId = null;

        function openPerformerModal(jobOrderId, jobOrderTitle, clientName, autoLoad = false) {
            currentJobOrderId = jobOrderId;
            selectedPerformerIds.clear();
            
            // Update modal content
            document.getElementById('modalJobOrderId').value = jobOrderId;
            document.getElementById('modalJobOrderTitle').textContent = jobOrderTitle;
            document.getElementById('modalClientName').textContent = clientName;
            document.getElementById('modalTitle').textContent = 'Assign Performers to ' + jobOrderTitle;
            
            // Load assigned performers and available performers
            loadAssignedPerformers(jobOrderId);
            
            // Show modal
            performerModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closePerformerModal() {
            performerModal.classList.remove('active');
            document.body.style.overflow = 'auto';
            currentJobOrderId = null;
            selectedPerformerIds.clear();
            document.getElementById('performerForm').reset();
            updateSelectedCount();
        }

        function openRemoveConfirmModal(jobOrderId, personnelId, performerName) {
            removeConfirmJobOrderId = jobOrderId;
            removeConfirmPersonnelId = personnelId;
            document.getElementById('removeConfirmJobOrderId').value = jobOrderId;
            document.getElementById('removeConfirmPersonnelId').value = personnelId;
            document.getElementById('removeConfirmPerformerName').textContent = performerName;
            removeConfirmModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeRemoveConfirmModal() {
            removeConfirmModal.classList.remove('active');
            document.body.style.overflow = 'auto';
            removeConfirmJobOrderId = null;
            removeConfirmPersonnelId = null;
            document.getElementById('removeConfirmForm').reset();
        }

        function loadAssignedPerformers(jobOrderId) {
            // Fetch assigned performers and available performers via AJAX
            const formData = new FormData();
            formData.append('action', 'fetch_performers');
            formData.append('job_order_id', jobOrderId);

            fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayPerformers(data.performers);
                    displayPerformerCheckboxes(data.available_performers);
                    document.getElementById('performerCount').textContent = data.performers.length;
                    
                    // Update the performer count in the main table
                    updatePerformerCountInTable(jobOrderId, data.performers.length);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function updatePerformerCountInTable(jobOrderId, count) {
            // Find the specific row with matching data-job-order-id
            const row = document.querySelector(`.job-orders-table tbody tr[data-job-order-id="${jobOrderId}"]`);
            if (row) {
                const performerCountSpan = row.querySelector('.performer-count');
                if (performerCountSpan) {
                    performerCountSpan.textContent = count + ' assigned';
                }
            }
        }

        function displayPerformerCheckboxes(availablePerformers) {
            const container = document.getElementById('performerCheckboxContainer');
            
            if (!availablePerformers || availablePerformers.length === 0) {
                container.innerHTML = '<div class="performer-empty-message">No performers available.</div>';
                return;
            }

            let html = '';
            availablePerformers.forEach(performer => {
                const isChecked = performer.assigned ? 'checked disabled' : '';
                const isDisabledClass = performer.assigned ? 'style="opacity: 0.5; cursor: not-allowed;"' : '';
                
                html += `
                    <div class="performer-checkbox-item" ${isDisabledClass}>
                        <input 
                            type="checkbox" 
                            id="performer_${performer.id}" 
                            name="personnel_id" 
                            value="${performer.id}"
                            ${isChecked}
                            onchange="updateSelectedCount()"
                        >
                        <label for="performer_${performer.id}">
                            ${performer.name}
                            ${performer.assigned ? '<span style="margin-left: 8px; font-size: 11px; color: #10b981;">(assigned)</span>' : ''}
                        </label>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('#performerCheckboxContainer input[type="checkbox"]:checked:not(:disabled)');
            selectedPerformerIds.clear();
            
            checkboxes.forEach(cb => {
                selectedPerformerIds.add(cb.value);
            });

            const count = selectedPerformerIds.size;
            document.getElementById('selectedCount').textContent = count + ' selected';
            document.getElementById('assignBtn').disabled = count === 0;
        }

        function clearSelectedPerformers() {
            const checkboxes = document.querySelectorAll('#performerCheckboxContainer input[type="checkbox"]:not(:disabled)');
            checkboxes.forEach(cb => {
                cb.checked = false;
            });
            selectedPerformerIds.clear();
            updateSelectedCount();
        }

        function displayPerformers(performers) {
            const container = document.getElementById('performerListContainer');
            
            if (performers.length === 0) {
                container.innerHTML = '<div class="no-performers">No performers assigned yet. Select performers above and click "Assign Selected Performers" to add.</div>';
                return;
            }

            let html = '';
            performers.forEach(performer => {
                const assignedDate = new Date(performer.assigned_at).toLocaleString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                html += `
                    <div class="performer-item">
                        <div>
                            <div class="performer-name">${performer.name}</div>
                            <div class="performer-date">Assigned: ${assignedDate}</div>
                        </div>
                            <button type="button" class="btn btn-danger btn-sm" onclick="openRemoveConfirmModal(${performer.job_order_id}, ${performer.personnel_id}, '${performer.name}')">
                                Remove
                            </button>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // Close modal when clicking outside
        performerModal.addEventListener('click', (e) => {
            if (e.target === performerModal) {
                closePerformerModal();
            }
        });

        removeConfirmModal.addEventListener('click', (e) => {
            if (e.target === removeConfirmModal) {
                closeRemoveConfirmModal();
            }
        });

        // Handle form submission
        document.getElementById('performerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            
            // Add job order ID
            formData.append('job_order_id', document.getElementById('modalJobOrderId').value);
            formData.append('action', 'add');
            
            // Collect all checked checkboxes
            const checkedCheckboxes = document.querySelectorAll('#performerCheckboxContainer input[type="checkbox"]:checked:not(:disabled)');
            
            if (checkedCheckboxes.length === 0) {
                alert('Please select at least one performer.');
                return;
            }
            
            // Add each checked performer to FormData
            checkedCheckboxes.forEach(checkbox => {
                formData.append('personnel_id[]', checkbox.value);
            });
            
            // Submit via AJAX
            fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Extract success message from response
                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');
                const successAlert = doc.querySelector('.alert-success');
                
                if (successAlert) {
                    // Show success message in modal
                    const modalBody = performerModal.querySelector('.modal-body');
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'alert alert-success';
                    messageDiv.style.marginBottom = '20px';
                    messageDiv.textContent = successAlert.textContent;
                    
                    // Insert at the top of modal body
                    modalBody.insertBefore(messageDiv, modalBody.firstChild);
                    
                    // Auto-remove message and reload after 2 seconds
                    setTimeout(() => {
                        messageDiv.remove();
                        if (currentJobOrderId) {
                            loadAssignedPerformers(currentJobOrderId);
                            selectedPerformerIds.clear();
                            updateSelectedCount();
                        }
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const modalBody = performerModal.querySelector('.modal-body');
                const errorMessage = document.createElement('div');
                errorMessage.className = 'alert alert-error';
                errorMessage.style.marginBottom = '20px';
                errorMessage.textContent = 'Error assigning performers. Please try again.';
                
                modalBody.insertBefore(errorMessage, modalBody.firstChild);
                
                setTimeout(() => {
                    errorMessage.remove();
                }, 3000);
            });
        });

        // Handle remove confirm form submission
        document.getElementById('removeConfirmForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const jobOrderId = document.getElementById('removeConfirmJobOrderId').value;
            const personnelId = document.getElementById('removeConfirmPersonnelId').value;
            
            const formData = new FormData();
            formData.append('job_order_id', jobOrderId);
            formData.append('personnel_id', personnelId);
            formData.append('action', 'remove');
            
            // Submit via AJAX
            fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Close remove confirmation modal
                closeRemoveConfirmModal();
                
                // Show success message in main performer modal
                const modalBody = performerModal.querySelector('.modal-body');
                const successMessage = document.createElement('div');
                successMessage.className = 'alert alert-success';
                successMessage.style.marginBottom = '20px';
                successMessage.textContent = 'Performer removed successfully!';
                
                modalBody.insertBefore(successMessage, modalBody.firstChild);
                
                // Remove message and reload performers after 2 seconds
                setTimeout(() => {
                    successMessage.remove();
                    if (currentJobOrderId) {
                        loadAssignedPerformers(currentJobOrderId);
                    }
                }, 2000);
            })
            .catch(error => {
                console.error('Error:', error);
                closeRemoveConfirmModal();
                
                const modalBody = performerModal.querySelector('.modal-body');
                const errorMessage = document.createElement('div');
                errorMessage.className = 'alert alert-error';
                errorMessage.style.marginBottom = '20px';
                errorMessage.textContent = 'Error removing performer. Please try again.';
                
                modalBody.insertBefore(errorMessage, modalBody.firstChild);
                
                setTimeout(() => {
                    errorMessage.remove();
                }, 3000);
            });
        });

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (performerModal.classList.contains('active')) {
                    closePerformerModal();
                }
                if (removeConfirmModal.classList.contains('active')) {
                    closeRemoveConfirmModal();
                }
            }
        });
    </script>
</body>
</html>
