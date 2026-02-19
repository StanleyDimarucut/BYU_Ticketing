<?php
require_once 'db.php';
require_once 'includes/auth.php';

$user = require_login();

// Restrict to Admin/Technician
if ($user['role'] !== 'admin' && $user['role'] !== 'technician') {
    $_SESSION['flash_error'] = 'Access denied.';
    header("Location: index.php");
    exit;
}

$page_title = 'Reports';
$page_heading = 'Reports & Analytics';
require_once 'includes/header.php';

// --- Data Fetching ---

// 1. Overall Stats
$stats = [
    'total' => 0,
    'open' => 0,
    'closed' => 0,
    'avg_resolution_hours' => 0
];

// Total
$res = $conn->query("SELECT COUNT(*) as c FROM tickets");
$stats['total'] = $res->fetch_assoc()['c'];

// Open (includes 'In Progress')
$res = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE status != 'Closed'");
$stats['open'] = $res->fetch_assoc()['c'];

// Closed
$res = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE status = 'Closed'");
$stats['closed'] = $res->fetch_assoc()['c'];


// 2. Chart Data (Status Distribution)
$chart_labels = [];
$chart_data = [];
$res = $conn->query("SELECT status, COUNT(*) as c FROM tickets GROUP BY status");
while ($row = $res->fetch_assoc()) {
    $chart_labels[] = $row['status'];
    $chart_data[] = $row['c'];
}

// 3. Filtered Report Data
$params = [];
$types = "";
$where = ["1=1"];

// Filters
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';

if ($start_date) {
    $where[] = "created_at >= ?";
    $params[] = $start_date . " 00:00:00";
    $types .= "s";
}
if ($end_date) {
    $where[] = "created_at <= ?";
    $params[] = $end_date . " 23:59:59";
    $types .= "s";
}
if ($status_filter) {
    $where[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
if ($priority_filter) {
    $where[] = "priority = ?";
    $params[] = $priority_filter;
    $types .= "s";
}

$sql = "SELECT id, subject, status, priority, created_at FROM tickets WHERE " . implode(" AND ", $where) . " ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$report_result = $stmt->get_result();
$report_rows = $report_result->fetch_all(MYSQLI_ASSOC);

?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Stat Cards -->
    <div
        class="bg-white border-2 border-[#262626] rounded-xl p-6 shadow-sm flex flex-col items-center justify-center text-center hover:shadow-md transition-shadow">
        <h3 class="text-4xl font-black text-[#262626] mb-2"><?= $stats['total'] ?></h3>
        <span class="text-xs font-bold text-[#525252] uppercase tracking-widest">Total Tickets</span>
    </div>

    <div
        class="bg-white border-2 border-[#262626] rounded-xl p-6 shadow-sm flex flex-col items-center justify-center text-center hover:shadow-md transition-shadow">
        <h3 class="text-4xl font-black text-blue-600 mb-2"><?= $stats['open'] ?></h3>
        <span class="text-xs font-bold text-[#525252] uppercase tracking-widest">Active</span>
    </div>

    <div
        class="bg-white border-2 border-[#262626] rounded-xl p-6 shadow-sm flex flex-col items-center justify-center text-center hover:shadow-md transition-shadow">
        <h3 class="text-4xl font-black text-green-600 mb-2"><?= $stats['closed'] ?></h3>
        <span class="text-xs font-bold text-[#525252] uppercase tracking-widest">Closed</span>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- Pie Chart Container -->
    <div class="bg-white border-2 border-[#262626] rounded-xl p-6 shadow-sm flex flex-col h-80">
        <h3 class="text-lg font-bold text-[#262626] uppercase tracking-wide mb-4 border-b-2 border-[#262626]/10 pb-2">
            Status Distribution</h3>
        <div class="flex-1 relative w-full min-h-0">
            <canvas id="statusChart"></canvas>
        </div>
    </div>

    <!-- Empty State / Future Widget -->
    <div
        class="bg-[#f5e6a3]/20 border-2 border-dashed border-[#262626]/20 rounded-xl p-6 flex flex-col items-center justify-center text-center h-80">
        <svg class="w-12 h-12 text-[#262626]/20 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        <span class="text-sm font-bold text-[#262626]/40 uppercase tracking-wide">More analytics coming soon</span>
    </div>
</div>

<!-- Report Generator Section -->
<section class="bg-white border-2 border-[#262626] rounded-xl p-6 shadow-sm mb-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 gap-4">
        <h2 class="text-lg font-bold text-[#262626] uppercase tracking-wide">Generate Report</h2>

        <!-- Action Buttons -->
        <div class="flex gap-2 relative">
            <a href="reports.php"
                class="px-3 py-2 border-2 border-[#262626] text-[#262626] text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-gray-100 transition-colors">
                Reset
            </a>

            <!-- Export Dropdown -->
            <div class="relative group">
                <button
                    class="px-4 py-2 bg-[#262626] text-[#f5e6a3] text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-black transition-colors flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div
                    class="absolute right-0 mt-2 w-48 bg-white border-2 border-[#262626] rounded-lg shadow-xl z-50 invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all duration-200">
                    <a href="export_report.php?<?= http_build_query($_GET) ?>" target="_blank"
                        class="block px-4 py-2 text-sm text-[#262626] hover:bg-[#f5e6a3] font-medium border-b border-[#262626]/10 first:rounded-t-lg">
                        CSV
                    </a>
                    <button onclick="window.print()"
                        class="block w-full text-left px-4 py-2 text-sm text-[#262626] hover:bg-[#f5e6a3] font-medium border-b border-[#262626]/10">
                        Print
                    </button>
                    <button onclick="generatePDF()"
                        class="block w-full text-left px-4 py-2 text-sm text-[#262626] hover:bg-[#f5e6a3] font-medium last:rounded-b-lg">
                        PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div>
            <label class="block text-xs font-bold text-[#525252] uppercase tracking-wide mb-1">Start Date</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>"
                class="w-full px-3 py-2 border-2 border-[#262626]/20 rounded-lg focus:outline-none focus:border-[#262626] focus:ring-0 text-sm font-medium">
        </div>
        <div>
            <label class="block text-xs font-bold text-[#525252] uppercase tracking-wide mb-1">End Date</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>"
                class="w-full px-3 py-2 border-2 border-[#262626]/20 rounded-lg focus:outline-none focus:border-[#262626] focus:ring-0 text-sm font-medium">
        </div>
        <div>
            <label class="block text-xs font-bold text-[#525252] uppercase tracking-wide mb-1">Status</label>
            <select name="status"
                class="w-full px-3 py-2 border-2 border-[#262626]/20 rounded-lg focus:outline-none focus:border-[#262626] focus:ring-0 text-sm font-medium bg-white">
                <option value="">All</option>
                <?php foreach (['Open', 'In Progress', 'Closed'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>>
                        <?= $s ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-[#525252] uppercase tracking-wide mb-1">Priority</label>
            <select name="priority"
                class="w-full px-3 py-2 border-2 border-[#262626]/20 rounded-lg focus:outline-none focus:border-[#262626] focus:ring-0 text-sm font-medium bg-white">
                <option value="">All</option>
                <?php foreach (['low', 'normal', 'high', 'emergency'] as $p): ?>
                    <option value="<?= $p ?>" <?= $priority_filter === $p ? 'selected' : '' ?>>
                        <?= ucfirst($p) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit"
                class="w-full px-4 py-2 border-2 border-[#262626] bg-[#f5e6a3] text-[#262626] text-sm font-bold uppercase tracking-wide rounded-lg hover:bg-[#262626] hover:text-[#f5e6a3] transition-colors">
                Apply Filters
            </button>
        </div>
    </form>

    <!-- Results Table -->
    <div class="overflow-x-auto border border-[#262626]/10 rounded-lg">
        <table class="w-full text-sm text-left">
            <thead class="bg-[#f5e6a3] text-[#262626] font-bold text-xs uppercase border-b-2 border-[#262626]">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Subject</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Priority</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#262626]/10">
                <?php if (empty($report_rows)): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-[#525252] italic">No tickets found for selected
                            criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($report_rows as $row): ?>
                        <tr class="bg-white hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-[#525252]">#
                                <?= $row['id'] ?>
                            </td>
                            <td class="px-4 py-3 text-[#262626]">
                                <?= date('M j, Y', strtotime($row['created_at'])) ?>
                            </td>
                            <td class="px-4 py-3 font-medium text-[#262626]">
                                <?= htmlspecialchars($row['subject']) ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="<?= status_badge_class($row['status']) ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-[#525252] capitalize">
                                <?= htmlspecialchars($row['priority']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
    // Initialize Chart.js
    const ctx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                data: <?= json_encode($chart_data) ?>,
                backgroundColor: [
                    '#eab308', // gold (Open/In Progress)
                    '#22c55e', // green (Closed)
                    '#3b82f6', // blue
                    '#f87171'  // red
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return context.label + ': ' + context.raw;
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
</script>



<!-- html2pdf.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    function generatePDF() {
        // Clone main element to avoid messing with current view
        const originalMain = document.querySelector('main');
        const element = originalMain.cloneNode(true);

        // Remove interactive elements, filters, stats, and charts
        element.querySelectorAll('button, a, form, .border-dashed, .grid.gap-6').forEach(el => el.remove());

        // Find the "Generate Report" heading and change it to "Ticket Report"
        const heading = Array.from(element.querySelectorAll('h2')).find(h => h.textContent.includes('Generate Report'));
        if (heading) heading.textContent = 'Ticket Report';

        // Wrapper for PDF generation with white background
        const wrapper = document.createElement('div');
        wrapper.className = 'pdf-wrapper';
        wrapper.style.padding = '40px';
        wrapper.style.background = 'white';
        wrapper.style.width = '100%';
        wrapper.appendChild(element);
        document.body.appendChild(wrapper);

        const opt = {
            margin: 0.5,
            filename: 'ticket_report_<?= date('Y-m-d') ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 1.5, useCORS: true },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' },
            pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
        };

        html2pdf().set(opt).from(wrapper).save().then(() => {
            document.body.removeChild(wrapper);
        });
    }
</script>

<style>
    @media print {
        @page {
            size: landscape;
            margin: 0.5in;
        }

        body {
            background: white !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        header,
        nav,
        form,
        .no-print,
        button,
        a {
            display: none !important;
        }

        main {
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: none !important;
        }

        /* Force Grid Layout for Print (only for visible grids if any remain) */
        .grid {
            display: grid !important;
        }

        /* Hide stats, charts, and report generation controls for print */
        .grid.grid-cols-1.md\:grid-cols-3,
        /* Stats Cards */
        .grid.grid-cols-1.md\:grid-cols-2,
        /* Charts */
        .border-dashed,
        .flex.justify-between,
        /* Hide the "Generate Report" header container */
        .group.relative,
        /* Hide export dropdown container */
        h2.text-lg,
        /* Hide "Generate Report" heading */
        footer,
        section.border-2>div.flex,
        form.grid

        /* Specificity fix: override .grid display property */
            {
            display: none !important;
        }

        /* Ensure table section has no border/shadow in print */
        section.border-2 {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Ensure table overflow is visible */
        .overflow-x-auto {
            overflow: visible !important;
            border: none !important;
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>