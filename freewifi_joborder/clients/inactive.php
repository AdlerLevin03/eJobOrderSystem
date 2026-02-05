<?php
require_once '../config/connection.php';

// Fetch all inactive clients from the database
try {
    $query = "SELECT * FROM clients WHERE status = 'Inactive' ORDER BY fname ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_inactive_clients = count($clients);
} catch (PDOException $e) {
    echo "Error fetching clients: " . $e->getMessage();
    $clients = [];
    $total_inactive_clients = 0;
}

// Function to format client full name
function getFullName($prefix, $fname, $mname, $lname, $suffix) {
    $name = '';
    if (!empty($prefix)) $name .= $prefix . ' ';
    if (!empty($fname)) $name .= $fname . ' ';
    if (!empty($mname)) $name .= $mname . ' ';
    if (!empty($lname)) $name .= $lname . ' ';
    if (!empty($suffix)) $name .= $suffix;
    return trim($name);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inactive Clients - Free WiFi Job Order System</title>
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

        .page-header {
            margin-bottom: 40px;
            text-align: center;
            border-bottom: 4px solid var(--accent-color);
            padding-bottom: 25px;
        }

        .page-header h1 {
            font-size: 36px;
            color: var(--danger-color);
            margin-bottom: 10px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .page-header p {
            color: var(--text-secondary);
            font-size: 15px;
            font-weight: 500;
        }

        .stats-bar {
            display: flex;
            gap: 25px;
            margin-bottom: 40px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .stat-card {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 28px 35px;
            border-radius: 12px;
            min-width: 220px;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.25);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: block;
            text-decoration: none;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.35);
        }

        .stat-card p {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.95;
            margin-bottom: 12px;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: 900;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            min-width: 80px;
        }

        .badge-inactive {
            background-color: #fee2e2;
            color: var(--danger-color);
        }

        .actions {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-edit {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-edit:hover {
            background-color: #d97706;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(245, 158, 11, 0.2);
        }

        .btn-view {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-view:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(30, 64, 175, 0.2);
        }

        .btn-back {
            background-color: var(--primary-color);
            color: white;
            padding: 13px 28px;
            font-size: 15px;
            width: auto;
            margin: 0;
        }

        .btn-back:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(30, 64, 175, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 80px 40px;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 72px;
            margin-bottom: 25px;
            opacity: 0.6;
            animation: float 3s ease-in-out infinite;
        }

        .empty-state h3 {
            font-size: 24px;
            color: var(--text-primary);
            margin-bottom: 12px;
            font-weight: 700;
        }

        .empty-state p {
            margin-bottom: 30px;
            font-size: 15px;
            color: var(--text-secondary);
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .search-section {
            display: flex;
            gap: 15px;
            margin-bottom: 35px;
            justify-content: center;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 280px;
            max-width: 450px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 13px 18px 13px 44px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            color: var(--text-primary);
            background-color: #f9fafb;
        }

        .search-box input:hover {
            border-color: var(--primary-light);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: white;
            box-shadow: 0 0 0 5px rgba(30, 64, 175, 0.1);
        }
    
        .search-box input::placeholder {
            color: var(--text-secondary);
        }

        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 18px;
            pointer-events: none;
        }

        .search-results {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 600;
            padding: 12px 18px;
            background-color: #f3f4f6;
            border-radius: 8px;
            min-width: 200px;
            text-align: center;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .search-results.active {
            background-color: #fee2e2;
            color: var(--danger-color);
            font-weight: 700;
            border-color: #fecaca;
        }

        .no-search-results {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
            background-color: #f9fafb;
            border-radius: 8px;
            margin-top: 20px;
        }

        .no-search-results-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.6;
        }

        .no-search-results h3 {
            font-size: 20px;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-weight: 700;
        }

        .no-search-results p {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .table-wrapper {
            width: 100%;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .table-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-title-icon {
            font-size: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        thead {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        th {
            padding: 18px 16px;
            text-align: left;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 2px solid #dc2626;
        }

        tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: #f3f4f6;
        }

        tbody tr:nth-child(even) {
            background-color: #fafbfc;
        }

        td {
            padding: 16px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .client-name {
            font-weight: 700;
            color: var(--danger-color);
        }

        @media (max-width: 1024px) {
            .container {
                padding: 30px;
            }

            .page-header {
                margin-bottom: 25px;
                padding-bottom: 15px;
            }

            .stats-bar {
                flex-direction: column;
                gap: 12px;
                margin-bottom: 25px;
            }

            .stat-card {
                min-width: 100%;
                padding: 18px 20px;
            }

            .stat-card .value {
                font-size: 28px;
            }

            th, td {
                padding: 10px 8px;
                font-size: 11px;
            }

            th {
                font-size: 10px;
            }

            .client-name {
                display: block;
                margin-bottom: 4px;
            }

            .badge {
                padding: 4px 8px;
                font-size: 10px;
                min-width: 70px;
            }

            .btn {
                padding: 5px 8px;
                font-size: 10px;
            }

            .actions {
                flex-direction: row;
                gap: 4px;
            }

            table {
                font-size: 11px;
            }

            .btn-back {
                padding: 12px 24px;
                font-size: 14px;
                width: 100%;
                margin-bottom: 15px;
            }

            .search-box {
                min-width: 100%;
            }

            .search-section {
                flex-direction: column;
                margin-bottom: 25px;
            }

            .search-results {
                width: 100%;
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
                <h1>INACTIVE CLIENTS</h1>
                <p>View all inactive clients in the system</p>
            </div>

            <!-- Statistics Bar -->
            <div class="stats-bar">
                <div class="stat-card">
                    <p>Total Inactive Clients</p>
                    <div class="value"><?php echo htmlspecialchars($total_inactive_clients); ?></div>
                </div>
            </div>

            <!-- Search Section with Back Button -->
            <?php if (count($clients) > 0): ?>
                <div class="search-section">
                    <a href="index.php" class="btn btn-back">← Back to All Clients</a>
                    <div class="search-box">
                        <span class="search-icon">🔍</span>
                        <input 
                            type="text" 
                            id="searchInput" 
                            placeholder="Search by name, agency, or contact number..."
                        >
                    </div>
                    <div class="search-results" id="searchResults">
                        Showing <?php echo count($clients); ?> inactive clients
                    </div>
                </div>
            <?php else: ?>
                <a href="index.php" class="btn btn-back">← Back to All Clients</a>
            <?php endif; ?>

            <!-- Inactive Clients Table -->
            <?php if (count($clients) > 0): ?>
                <div class="table-title">
                    <span class="table-title-icon">👥</span>
                    <span>List of Inactive Clients</span>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 30%;">Client Name</th>
                                <th style="width: 20%;">Agency</th>
                                <th style="width: 15%;">Contact Number</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 25%; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                            <tr>
                                <td class="client-name">
                                    <?php echo htmlspecialchars(getFullName(
                                        $client['prefix'] ?? '',
                                        $client['fname'] ?? '',
                                        $client['mname'] ?? '',
                                        $client['lname'] ?? '',
                                        $client['suffix'] ?? ''
                                    )); ?>
                                </td>
                                <td><?php echo htmlspecialchars($client['agency'] ?? '-'); ?></td>
                                <td>
                                    <?php if (!empty($client['cno'])): ?>
                                        <?php echo htmlspecialchars($client['cno']); ?>
                                    <?php else: ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-inactive">
                                        <?php echo htmlspecialchars($client['status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="edit.php?id=<?php echo htmlspecialchars($client['id']); ?>" class="btn btn-edit">Edit</a>
                                    <a href="view.php?id=<?php echo htmlspecialchars($client['id']); ?>" class="btn btn-view">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👥</div>
                    <h3>No Inactive Clients</h3>
                    <p>All clients are currently active in the system.</p>
                    <a href="index.php" class="btn btn-back">← Back to All Clients</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include '../includes/footer.php'; ?>

    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        const tableBody = document.querySelector('tbody');
        const tableRows = document.querySelectorAll('tbody tr');
        const totalClients = <?php echo count($clients); ?>;

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                let visibleCount = 0;

                tableRows.forEach(row => {
                    // Get text content from client name, agency, and contact number columns
                    const clientName = row.cells[0]?.textContent.toLowerCase() || '';
                    const agency = row.cells[1]?.textContent.toLowerCase() || '';
                    const contactNumber = row.cells[2]?.textContent.toLowerCase() || '';

                    // Check if search term matches any field
                    const matches = clientName.includes(searchTerm) || 
                                  agency.includes(searchTerm) || 
                                  contactNumber.includes(searchTerm);

                    if (searchTerm === '' || matches) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Update search results display
                if (searchTerm === '') {
                    searchResults.textContent = `Showing ${totalClients} inactive clients`;
                    searchResults.classList.remove('active');
                } else {
                    searchResults.textContent = `${visibleCount} of ${totalClients} results`;
                    searchResults.classList.add('active');
                }

                // Show "no results" message if no matches found
                let noResultsDiv = document.getElementById('noSearchResults');
                if (visibleCount === 0 && searchTerm !== '') {
                    if (!noResultsDiv) {
                        noResultsDiv = document.createElement('div');
                        noResultsDiv.id = 'noSearchResults';
                        noResultsDiv.className = 'no-search-results';
                        noResultsDiv.innerHTML = `
                            <div class="no-search-results-icon">🔍</div>
                            <h3>No Results Found</h3>
                            <p>No inactive clients match your search for "<strong>${this.value}</strong>"</p>
                        `;
                        tableBody.parentElement.parentElement.insertBefore(noResultsDiv, tableBody.parentElement.nextSibling);
                    }
                } else {
                    if (noResultsDiv) {
                        noResultsDiv.remove();
                    }
                }
            });
        }
    </script>
</body>
</html>
