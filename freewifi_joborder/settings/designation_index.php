<?php
require_once '../config/connection.php';

// Fetch all designations
$designations = [];
$total_designations = 0;
$active_designations = 0;
$inactive_designations = 0;

try {
    $query = "SELECT id, designation_code, designation_name, description, status, date_created 
              FROM designations 
              ORDER BY designation_name ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $designations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $total_designations = count($designations);
    foreach ($designations as $designation) {
        if ($designation['status'] === 'Active') {
            $active_designations++;
        } else {
            $inactive_designations++;
        }
    }
} catch (PDOException $e) {
    echo "Error fetching designations: " . $e->getMessage();
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    try {
        $delete_query = "DELETE FROM designations WHERE id = ?";
        $delete_stmt = $pdo->prepare($delete_query);
        $delete_stmt->execute([$delete_id]);
        
        // Refresh the page
        header('Location: designation_index.php');
        exit;
    } catch (PDOException $e) {
        echo "Error deleting designation: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Designations - Free WiFi Job Order System</title>
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
            margin: 0 auto;
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            padding: 50px;
            overflow-x: auto;
        }

        /* Centered Header */
        .page-title {
            text-align: center;
            margin-bottom: 10px;
        }

        .page-title h1 {
            font-size: 36px;
            color: var(--primary-color);
            margin-bottom: 5px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .page-subtitle {
            text-align: center;
            color: var(--text-secondary);
            font-size: 15px;
            font-weight: 500;
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 4px solid var(--accent-color);
        }

        /* Statistics Cards */
        .stats-container {
            display: flex;
            gap: 25px;
            margin-bottom: 40px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            color: white;
            padding: 28px 35px;
            border-radius: 12px;
            min-width: 220px;
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.25);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(30, 64, 175, 0.35);
        }

        .stat-card.active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);
        }

        .stat-card.active:hover {
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.35);
        }

        .stat-card.inactive {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.25);
        }

        .stat-card.inactive:hover {
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.35);
        }

        .stat-label {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.95;
            margin-bottom: 12px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 900;
        }

        /* Controls Section */
        .controls-section {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
            gap: 20px;
            flex-wrap: wrap;
            max-width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-add {
            background-color: var(--success-color);
            color: white;
            padding: 10px 20px;
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
            white-space: nowrap;
            flex-shrink: 0;
        }

        .btn-add:hover {
            background-color: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .search-bar {
            display: flex;
            align-items: center;
            background-color: #f3f4f6;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 8px 14px;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 200px;
            max-width: 400px;
        }

        .search-bar:focus-within {
            border-color: var(--primary-light);
            background-color: white;
            box-shadow: 0 4px 8px rgba(30, 64, 175, 0.1);
        }

        .search-bar input {
            border: none;
            background: transparent;
            font-size: 14px;
            color: var(--text-primary);
            width: 100%;
            padding: 0;
            margin: 0;
            box-shadow: none !important;
        }

        .search-bar input:focus {
            outline: none;
            box-shadow: none !important;
        }

        .search-bar input::placeholder {
            color: var(--text-secondary);
        }

        .search-icon {
            margin-right: 8px;
            font-size: 16px;
            color: var(--text-secondary);
        }

        .results-count {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
        }

        .list-header {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 20px;
            padding: 15px 0;
            border-bottom: 2px solid var(--accent-color);
        }

        .list-header-icon {
            font-size: 24px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        thead {
            background-color: var(--primary-color);
            color: white;
        }

        th {
            padding: 16px;
            text-align: left;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 2px solid var(--border-color);
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        tbody tr {
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background-color: #f9fafb;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.active {
            background-color: #dcfce7;
            color: var(--success-color);
        }

        .status-badge.inactive {
            background-color: #fee2e2;
            color: var(--danger-color);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 8px 12px;
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

        .btn-view {
            background-color: var(--primary-light);
            color: white;
        }

        .btn-view:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .btn-edit {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-edit:hover {
            background-color: #d97706;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
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
            color: var(--text-secondary);
            font-size: 15px;
            margin-bottom: 30px;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            main {
                padding: 30px 15px;
            }

            .container {
                padding: 30px 20px;
            }

            .page-title h1 {
                font-size: 28px;
            }

            .stats-container {
                flex-direction: column;
            }

            .controls-section {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-add {
                width: 100%;
                justify-content: center;
            }

            .search-bar {
                width: 100%;
                max-width: 100%;
            }

            .action-buttons {
                flex-wrap: wrap;
            }

            .btn-action {
                padding: 6px 10px;
                font-size: 11px;
            }
        }

        @media (max-width: 480px) {
            main {
                padding: 20px 10px;
            }

            .container {
                padding: 20px 15px;
            }

            .page-title h1 {
                font-size: 22px;
            }

            .stat-value {
                font-size: 28px;
            }

            .stat-label {
                font-size: 11px;
            }

            th, td {
                padding: 12px 8px;
                font-size: 12px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
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
            <!-- Page Title -->
            <div class="page-title">
                <h1>DESIGNATION MANAGEMENT</h1>
            </div>
            <div class="page-subtitle">Manage job role designations in the system</div>
    
            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-label">Total Designations</div>
                    <div class="stat-value"><?php echo $total_designations; ?></div>
                </div>
                <div class="stat-card active">
                    <div class="stat-label">Active Designations</div>
                    <div class="stat-value"><?php echo $active_designations; ?></div>
                </div>
                <div class="stat-card inactive">
                    <div class="stat-label">Inactive Designations</div>
                    <div class="stat-value"><?php echo $inactive_designations; ?></div>
                </div>
            </div>

            <!-- Controls Section -->
            <div class="controls-section">
                <a href="designation_add.php" class="btn-add">➕ Add New Designation</a>
                <div class="search-bar">
                    <span class="search-icon">🔍</span>
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="Search by name or code..." 
                        onkeyup="searchDesignations()"
                    >
                </div>
                <div class="results-count" id="resultsCount">Showing <?php echo $total_designations; ?> designations</div>
            </div>

            <!-- List Header -->
            <div class="list-header">
                <span class="list-header-icon">📋</span>
                <span>List of Designations</span>
            </div>

            <!-- Designations Table -->
            <?php if (!empty($designations)): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Designation Name</th>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($designations as $designation): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($designation['designation_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($designation['designation_code']); ?></td>
                                    <td><?php echo !empty($designation['description']) ? htmlspecialchars($designation['description']) : 'N/A'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($designation['status']); ?>">
                                            <?php echo htmlspecialchars($designation['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="designation_view.php?id=<?php echo htmlspecialchars($designation['id']); ?>" class="btn-action btn-view">View</a>
                                            <a href="designation_edit.php?id=<?php echo htmlspecialchars($designation['id']); ?>" class="btn-action btn-edit">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h2>No Designations Found</h2>
                    <p>There are currently no designations in the system. Create one to get started.</p>
                    <a href="designation_add.php" class="btn-add">➕ Add First Designation</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include '../includes/footer.php'; ?>

    <script>
        // Search functionality
        function searchDesignations() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.querySelector('table tbody');
            const rows = table.getElementsByTagName('tr');
            const resultsCount = document.getElementById('resultsCount');
            let visibleCount = 0;

            for (let i = 0; i < rows.length; i++) {
                const designationName = rows[i].cells[0].textContent.toLowerCase();
                const code = rows[i].cells[1].textContent.toLowerCase();
                const description = rows[i].cells[2].textContent.toLowerCase();
                
                if (designationName.includes(filter) || code.includes(filter) || description.includes(filter)) {
                    rows[i].style.display = '';
                    visibleCount++;
                } else {
                    rows[i].style.display = 'none';
                }
            }

            // Update results count
            if (resultsCount) {
                resultsCount.textContent = `Showing ${visibleCount} designation${visibleCount !== 1 ? 's' : ''}`;
            }

            // Show empty state if no results
            const tableWrapper = document.querySelector('.table-wrapper');
            
            if (visibleCount === 0 && tableWrapper) {
                if (!document.getElementById('noResultsMessage')) {
                    const noResults = document.createElement('div');
                    noResults.id = 'noResultsMessage';
                    noResults.className = 'empty-state';
                    noResults.innerHTML = `
                        <div class="empty-state-icon">🔍</div>
                        <h2>No Results Found</h2>
                        <p>No designations match your search criteria. Try a different search term.</p>
                    `;
                    tableWrapper.parentNode.insertBefore(noResults, tableWrapper.nextSibling);
                }
                tableWrapper.style.display = 'none';
            } else if (tableWrapper) {
                tableWrapper.style.display = '';
                const noResults = document.getElementById('noResultsMessage');
                if (noResults) {
                    noResults.remove();
                }
            }
        }
    </script>
</body>
</html>
