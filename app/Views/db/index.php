<!-- Database Administration Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Database Administration</h2>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-info" onclick="refreshTables()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#connectionManagerModal">
            <i class="fas fa-database"></i> Connections
        </button>
        <button type="button" class="btn btn-outline-warning" onclick="vacuumDatabase()">
            <i class="fas fa-compress-arrows-alt"></i> Vacuum
        </button>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <?= htmlspecialchars($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- DBeaver-Style Database Explorer -->
<div class="db-explorer row">
    <div class="col-md-3 db-explorer-sidebar">
        <!-- Database Tree Navigator -->
        <div class="db-toolbar">
            <div class="db-breadcrumb" id="breadcrumb">
                <i class="fas fa-user-shield me-2"></i><?= Env::get('ROOT_USER', 'root') ?>@localhost > All Databases
            </div>
        </div>
        
        <div id="databaseTree" class="mt-3">
            <!-- JavaScript will populate this -->
        </div>
    </div>
    
    <div class="col-md-9 db-explorer-content">
        <!-- Content Area -->
        <div id="contentArea">
            <!-- Default Welcome Message -->
            <div class="content-loading">
                <i class="fas fa-database fa-3x text-muted mb-3"></i>
                <h5>데이터베이스 탐색기</h5>
                <p class="text-muted">왼쪽 트리에서 테이블을 선택하여 데이터를 조회하세요.</p>
            </div>
        </div>
    </div>
</div>

<!-- Database Statistics Cards -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-primary"><?= count($tables ?? []) ?></h4>
                <p class="card-text">Database Tables</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-success"><?= number_format(array_sum(array_column($tables ?? [], 'count'))) ?></h4>
                <p class="card-text">Total Records</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-info"><?= $total_stats['total_scans'] ?? 0 ?></h4>
                <p class="card-text">Total Scans</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-warning"><?= number_format($total_stats['total_loc'] ?? 0) ?></h4>
                <p class="card-text">Total LOC</p>
            </div>
        </div>
    </div>
</div>

        <!-- Query Console -->
        <div class="card mt-3">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-terminal me-2"></i>
                        SQL Query Console
                    </h6>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary" onclick="formatQuery()">
                            <i class="fas fa-indent me-1"></i>Format
                        </button>
                        <button class="btn btn-sm btn-outline-primary" onclick="executeQuery()">
                            <i class="fas fa-play me-1"></i>Execute
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- 샘플 쿼리 버튼들 -->
                <div class="mb-4 mx-3">
                    <label class="form-label small text-muted">Quick Templates:</label>
                    <div class="btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-info btn-sm me-1" onclick="insertSample('SHOW_TABLES')">
                            <i class="fas fa-list me-1"></i>Show Tables
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm me-1" onclick="insertSample('SHOW_COLUMNS')">
                            <i class="fas fa-columns me-1"></i>Show Columns
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm me-1" onclick="insertSample('TABLE_STATUS')">
                            <i class="fas fa-chart-bar me-1"></i>Table Status
                        </button>
                    </div>
                    <div class="btn-group-sm mt-2" role="group">
                        <button type="button" class="btn btn-outline-success btn-sm me-1" onclick="insertSample('PROJECTS')">
                            <i class="fas fa-folder me-1"></i>Projects
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm me-1" onclick="insertSample('SCANS')">
                            <i class="fas fa-search me-1"></i>Scans
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm me-1" onclick="insertSample('OSES')">
                            <i class="fas fa-desktop me-1"></i>OS List
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm me-1" onclick="insertSample('AGENTS')">
                            <i class="fas fa-robot me-1"></i>Agents
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm me-1" onclick="insertSample('STATS')">
                            <i class="fas fa-chart-pie me-1"></i>Statistics
                        </button>
                    </div>
                    <div class="ms-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearQuery()" style="border-radius: 0.375rem !important;">
                            <i class="fas fa-eraser me-1"></i>Clear
                        </button>
                    </div>
                </div>
                
                <div class="query-editor mb-4 mx-3">
                    <textarea id="sqlQuery" class="form-control font-monospace" rows="8" 
                              placeholder="-- SQL 쿼리를 입력하세요&#10;-- 예: SELECT * FROM projects LIMIT 10;&#10;-- 허용: SELECT, DESCRIBE, SHOW TABLES&#10;-- 금지: DROP, DELETE, UPDATE, INSERT"
                              style="border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></textarea>
                </div>
                <div id="queryResults" class="query-results mx-3 mb-4">
                    <!-- Results will appear here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Connection Manager Modal -->
<div class="modal fade" id="connectionManagerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-database me-2"></i>
                    Database Connections
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Current Connection</h6>
                        <div class="connection-info p-3 bg-light rounded">
                            <div class="d-flex align-items-center mb-2">
                                <span class="connection-status online me-2"></span>
                                <strong>Local MySQL</strong>
                            </div>
                            <small class="text-muted">
                                Host: localhost<br>
                                Database: <?= Env::get('DB_NAME', 'azabellcode') ?><br>
                                User: <?= Env::get('DB_USER', 'root') ?>
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Connection Details</h6>
                        <div class="connection-stats">
                            <div class="stat-item d-flex justify-content-between">
                                <span>Tables:</span>
                                <span class="fw-bold"><?= count($tables) ?></span>
                            </div>
                            <div class="stat-item d-flex justify-content-between">
                                <span>Total Records:</span>
                                <span class="fw-bold"><?= array_sum(array_column($tables, 'count')) ?></span>
                            </div>
                            <div class="stat-item d-flex justify-content-between">
                                <span>Database Size:</span>
                                <span class="fw-bold" id="dbSize">Calculating...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Database Tables (Hidden by default, will be moved to tree) -->
<div class="card mb-4" style="display: none;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">📊 Database Tables</h5>
        <small class="text-muted"><?= count($tables) ?> tables total</small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="40%">Table Name</th>
                        <th width="20%" class="text-center">Records</th>
                        <th width="40%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables as $table): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-table me-2 text-primary"></i>
                                    <div>
                                        <strong 
                                            class="table-name-hover" 
                                            data-table="<?= htmlspecialchars($table['name']) ?>"
                                            style="cursor: pointer; text-decoration: underline; text-decoration-style: dotted;"
                                            title="Click to view table structure">
                                            <?= htmlspecialchars($table['name']) ?>
                                        </strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php
                                            $descriptions = [
                                                'projects' => '프로젝트 정보',
                                                'scans' => '스캔 실행 결과',
                                                'scan_lang_stats' => '언어별 통계',
                                                'os_list' => '운영체제 목록',
                                                'agents' => '개발 도구/에이전트',
                                                'settings' => '시스템 설정'
                                            ];
                                            echo $descriptions[$table['name']] ?? 'Database table';
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?= $table['count'] > 1000 ? 'success' : ($table['count'] > 100 ? 'info' : 'secondary') ?> fs-6">
                                    <?= number_format($table['count']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="/db/table/<?= htmlspecialchars($table['name']) ?>" 
                                       class="btn btn-outline-primary" 
                                       title="View table data">
                                        <i class="fas fa-eye"></i> View Data
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-secondary" 
                                            onclick="showTableStructure('<?= htmlspecialchars($table['name']) ?>')"
                                            title="Show table structure">
                                        <i class="fas fa-info-circle"></i> Structure
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-info btn-sm" 
                                            onclick="showExportOptions('<?= htmlspecialchars($table['name']) ?>')"
                                            title="Export table data">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// DBeaver-style Database Explorer JavaScript
let currentDatabase = '<?= Env::get('DB_NAME', 'azabellcode') ?>';
let currentUser = '<?= Env::get('ROOT_USER', 'root') ?>'; // DB Admin에서는 root 사용자 표시
let activeTables = <?= json_encode($tables ?? []) ?>;
let allDatabases = <?= json_encode($all_databases ?? []) ?>;

// Initialize the database explorer
document.addEventListener('DOMContentLoaded', function() {
    initializeDatabaseTree();
    loadDatabaseStats();
});

// Database Tree Navigation
function initializeDatabaseTree() {
    const treeContainer = document.getElementById('databaseTree');
    
    // Add root node first (DB User)
    const rootNode = document.createElement('div');
    rootNode.className = 'tree-node tree-root expanded';
    rootNode.innerHTML = `
        <div class="tree-item" onclick="toggleNode(this)">
            <i class="fas fa-chevron-down tree-icon"></i>
            <i class="fas fa-user-shield me-2 text-warning"></i>
            <span class="tree-label">${currentUser}@localhost</span>
        </div>
        <div class="tree-children" style="display: block;" id="rootChildren">
        </div>
    `;
    
    const rootChildren = rootNode.querySelector('#rootChildren');
    
    // Add all databases
    allDatabases.forEach(database => {
        const databaseNode = document.createElement('div');
        databaseNode.className = `tree-node tree-database ${database.is_current ? 'expanded' : ''}`;
        
        const isSystemClass = database.is_system ? 'text-muted' : 'text-primary';
        const currentBadge = database.is_current ? '<span class="badge bg-success ms-2">current</span>' : '';
        const systemBadge = database.is_system ? '<small class="text-muted ms-2">(system)</small>' : '';
        
        databaseNode.innerHTML = `
            <div class="tree-item" onclick="toggleNode(this)" data-database="${database.name}">
                <i class="fas fa-chevron-${database.is_current ? 'down' : 'right'} tree-icon"></i>
                <i class="fas fa-database me-2 ${isSystemClass}"></i>
                <span class="tree-label">${database.name}</span>
                <small class="tree-count">(${database.table_count})</small>
                ${currentBadge}
                ${systemBadge}
            </div>
            <div class="tree-children" style="display: ${database.is_current ? 'block' : 'none'};" id="db-${database.name}">
                ${database.is_current ? generateTablesNode() : ''}
            </div>
        `;
        
        rootChildren.appendChild(databaseNode);
    });
    
    // Append root to tree container
    treeContainer.appendChild(rootNode);
    
    // Load tables for current database
    if (allDatabases.find(db => db.is_current)) {
        loadTableNodes();
    }
}

function generateTablesNode() {
    return `
        <div class="tree-node" id="tablesNode">
            <div class="tree-item" onclick="toggleNode(this)">
                <i class="fas fa-chevron-right tree-icon"></i>
                <i class="fas fa-table me-2 text-success"></i>
                <span class="tree-label">Tables (${activeTables.length})</span>
            </div>
            <div class="tree-children" id="tablesContainer"></div>
        </div>
    `;
}

function loadTableNodes() {
    const tablesContainer = document.getElementById('tablesContainer');
    
    activeTables.forEach(table => {
        const tableNode = document.createElement('div');
        tableNode.className = 'tree-node tree-table';
        tableNode.innerHTML = `
            <div class="tree-item" onclick="selectTable('${table.name}')">
                <i class="fas fa-table me-2 text-info"></i>
                <span class="tree-label">${table.name}</span>
                <small class="tree-count">(${table.count})</small>
            </div>
        `;
        tablesContainer.appendChild(tableNode);
    });
}

function toggleNode(element) {
    const node = element.parentElement;
    const children = node.querySelector('.tree-children');
    const icon = element.querySelector('.tree-icon');
    
    if (children) {
        if (children.style.display === 'none' || !children.style.display) {
            children.style.display = 'block';
            icon.className = 'fas fa-chevron-down tree-icon';
            node.classList.add('expanded');
        } else {
            children.style.display = 'none';
            icon.className = 'fas fa-chevron-right tree-icon';
            node.classList.remove('expanded');
        }
    }
}

function selectTable(tableName) {
    // Remove active state from all nodes
    document.querySelectorAll('.tree-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Add active state to selected table
    event.target.closest('.tree-item').classList.add('active');
    
    // Update content area
    loadTableContent(tableName);
    
    // Update breadcrumb with DB user
    updateBreadcrumb(`${currentUser}@localhost > ${currentDatabase} > Tables > ${tableName}`);
}

function loadTableContent(tableName) {
    const contentArea = document.getElementById('contentArea');
    
    // Show loading state
    contentArea.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary mb-3" role="status"></div>
            <div>Loading table data...</div>
        </div>
    `;
    
    // Simulate AJAX call (replace with actual endpoint)
    setTimeout(() => {
        displayTableContent(tableName, {
            rows: generateSampleData(tableName),
            columns: getTableColumns(tableName),
            structure: getTableStructure(tableName),
            indexes: getTableIndexes(tableName)
        });
    }, 800);
}

function displayTableContent(tableName, data) {
    const contentArea = document.getElementById('contentArea');
    
    contentArea.innerHTML = `
        <!-- Table Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
                <i class="fas fa-table me-2"></i>
                ${tableName}
            </h5>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary" onclick="refreshTableData('${tableName}')">
                    <i class="fas fa-sync me-1"></i>Refresh
                </button>
                <button class="btn btn-outline-success" onclick="exportTableData('${tableName}')">
                    <i class="fas fa-download me-1"></i>Export
                </button>
            </div>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-3" id="tableDataTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#dataTab">
                    <i class="fas fa-list me-1"></i>Data
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#structureTab">
                    <i class="fas fa-cog me-1"></i>Structure
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#indexesTab">
                    <i class="fas fa-key me-1"></i>Indexes
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <div class="tab-pane fade show active" id="dataTab">
                ${generateDataView(data.rows, data.columns)}
            </div>
            <div class="tab-pane fade" id="structureTab">
                ${generateStructureView(data.structure)}
            </div>
            <div class="tab-pane fade" id="indexesTab">
                ${generateIndexesView(data.indexes)}
            </div>
        </div>
    `;
}

// Legacy functions for backward compatibility
function refreshStats() {
    window.location.reload();
}

function vacuumDatabase() {
    if (confirm('This will optimize the database by reclaiming unused space. Continue?')) {
        // 로딩 상태 표시
        const originalText = event.target.innerHTML;
        event.target.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        event.target.disabled = true;
        
        fetch('/db/vacuum', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'csrf_token=' + encodeURIComponent('<?= Csrf::generate() ?>')
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // 성공 알림
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.d-flex.justify-content-between').after(alert);
                
                // 5초 후 자동 제거
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 5000);
                
                // 통계 새로고침
                setTimeout(() => location.reload(), 1000);
            } else {
                throw new Error(data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Vacuum error:', error);
            
            // 에러 알림
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show';
            alert.innerHTML = `
                <i class="fas fa-exclamation-triangle me-2"></i>
                Database optimization failed: ${error.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.d-flex.justify-content-between').after(alert);
        })
        .finally(() => {
            // 버튼 상태 복원
            event.target.innerHTML = originalText;
            event.target.disabled = false;
        });
    }
}

function exportTable(tableName, format = 'csv') {
    const url = '/db/export?table=' + encodeURIComponent(tableName) + '&format=' + format;
    window.location.href = url;
}

function showExportOptions(tableName) {
    const format = prompt(`Export table: ${tableName}\n\nChoose format:\n1. CSV\n2. JSON\n3. SQL\n\nEnter number (1-3):`, '1');

    if (format === null) return; // User cancelled

    let formatType = 'csv';
    switch(format.trim()) {
        case '2':
            formatType = 'json';
            break;
        case '3':
            formatType = 'sql';
            break;
        case '1':
        default:
            formatType = 'csv';
            break;
    }

    exportTable(tableName, formatType);
}

function showTableStructure(tableName) {
    // Simple modal alternative using a new window/alert
    const structureInfo = getTableStructureInfo(tableName);
    
    let message = `Table: ${tableName}\n\nColumns:\n`;
    structureInfo.forEach(col => {
        message += `• ${col.name} (${col.type})`;
        if (col.pk) message += ' [PRIMARY KEY]';
        if (col.notnull) message += ' [NOT NULL]';
        message += '\n';
    });
    
    alert(message);
}

function getTableStructureInfo(tableName) {
    // Predefined table structures for common tables
    const tableStructures = {
        'projects': [
            {name: 'id', type: 'INTEGER', pk: true, notnull: true},
            {name: 'name', type: 'TEXT', pk: false, notnull: true},
            {name: 'root_path', type: 'TEXT', pk: false, notnull: true},
            {name: 'description', type: 'TEXT', pk: false, notnull: false},
            {name: 'is_active', type: 'INTEGER', pk: false, notnull: true},
            {name: 'created_at', type: 'TIMESTAMP', pk: false, notnull: true},
            {name: 'updated_at', type: 'TIMESTAMP', pk: false, notnull: true}
        ],
        'scans': [
            {name: 'id', type: 'INTEGER', pk: true, notnull: true},
            {name: 'project_id', type: 'INTEGER', pk: false, notnull: true},
            {name: 'status', type: 'TEXT', pk: false, notnull: true},
            {name: 'total_files', type: 'INTEGER', pk: false, notnull: false},
            {name: 'total_loc', type: 'INTEGER', pk: false, notnull: false},
            {name: 'started_at', type: 'TIMESTAMP', pk: false, notnull: true},
            {name: 'completed_at', type: 'TIMESTAMP', pk: false, notnull: false}
        ],
        'scan_lang_stats': [
            {name: 'id', type: 'INTEGER', pk: true, notnull: true},
            {name: 'scan_id', type: 'INTEGER', pk: false, notnull: true},
            {name: 'language', type: 'TEXT', pk: false, notnull: true},
            {name: 'file_count', type: 'INTEGER', pk: false, notnull: true},
            {name: 'loc', type: 'INTEGER', pk: false, notnull: true},
            {name: 'comment_lines', type: 'INTEGER', pk: false, notnull: false},
            {name: 'blank_lines', type: 'INTEGER', pk: false, notnull: false}
        ]
    };
    
    return tableStructures[tableName] || [
        {name: 'Unknown', type: 'Structure not available', pk: false, notnull: false}
    ];
}



function generateDataView(rows, columns) {
    if (!rows || rows.length === 0) {
        return '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No data found in this table.</div>';
    }
    
    let html = '<div class="table-responsive"><table class="table table-striped table-hover table-sm">';
    
    // Headers
    html += '<thead class="table-dark"><tr>';
    columns.forEach(col => {
        html += `<th>${col}</th>`;
    });
    html += '</tr></thead>';
    
    // Rows
    html += '<tbody>';
    rows.slice(0, 50).forEach(row => { // Limit to 50 rows for performance
        html += '<tr>';
        columns.forEach(col => {
            const value = row[col] || '';
            html += `<td>${escapeHtml(String(value).substring(0, 100))}</td>`;
        });
        html += '</tr>';
    });
    html += '</tbody></table></div>';
    
    if (rows.length > 50) {
        html += `<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Showing first 50 of ${rows.length} records.</div>`;
    }
    
    return html;
}

function generateStructureView(structure) {
    let html = '<div class="table-responsive"><table class="table table-striped">';
    html += `
        <thead class="table-dark">
            <tr>
                <th>Column</th>
                <th>Type</th>
                <th>Null</th>
                <th>Key</th>
                <th>Default</th>
                <th>Extra</th>
            </tr>
        </thead>
        <tbody>
    `;
    
    structure.forEach(col => {
        html += `
            <tr>
                <td><strong>${col.Field}</strong></td>
                <td><code>${col.Type}</code></td>
                <td>${col.Null === 'YES' ? '<span class="text-success">✓</span>' : '<span class="text-danger">✗</span>'}</td>
                <td>${col.Key ? `<span class="badge bg-primary">${col.Key}</span>` : ''}</td>
                <td>${col.Default || '<em class="text-muted">NULL</em>'}</td>
                <td>${col.Extra || ''}</td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    return html;
}

function generateIndexesView(indexes) {
    if (!indexes || indexes.length === 0) {
        return '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No indexes found for this table.</div>';
    }
    
    let html = '<div class="table-responsive"><table class="table table-striped">';
    html += `
        <thead class="table-dark">
            <tr>
                <th>Key Name</th>
                <th>Unique</th>
                <th>Columns</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>
    `;
    
    indexes.forEach(idx => {
        html += `
            <tr>
                <td><strong>${idx.Key_name}</strong></td>
                <td>${idx.Non_unique === '0' ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'}</td>
                <td>${idx.Column_name}</td>
                <td><code>${idx.Index_type || 'BTREE'}</code></td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    return html;
}

// Query Console Functions
function executeQuery() {
    const query = document.getElementById('sqlQuery').value.trim();
    if (!query) {
        alert('SQL 쿼리를 입력해주세요');
        return;
    }
    
    const resultsDiv = document.getElementById('queryResults');
    resultsDiv.innerHTML = `
        <div class="text-center p-3">
            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
            쿼리 실행 중...
        </div>
    `;
    
    // 실제 AJAX 요청
    fetch('/db/execute-query', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ 
            query: query
        })
    })
    .then(response => {
        // 응답이 JSON인지 확인
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                throw new Error('서버에서 HTML 응답을 반환했습니다: ' + text.substring(0, 100));
            });
        }
        return response.json();
    })
    .then(data => {
        displayQueryResults(data);
    })
    .catch(error => {
        console.error('Query execution error:', error);
        displayQueryResults({
            error: '쿼리 실행 중 오류가 발생했습니다: ' + error.message
        });
    });
}

function displayQueryResults(data) {
    const resultsDiv = document.getElementById('queryResults');
    
    if (data.error) {
        resultsDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>오류:</strong> ${data.error}
            </div>
        `;
        return;
    }
    
    if (data.success) {
        if (data.type === 'select' && data.data && data.data.length > 0) {
            const columns = Object.keys(data.data[0]);
            let html = `
                <div class="alert alert-success">
                    <i class="fas fa-check me-2"></i>
                    ${data.message || '쿼리가 성공적으로 실행되었습니다.'}
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="table-dark">
                            <tr>
            `;
            
            // 컬럼 헤더
            columns.forEach(col => {
                html += `<th>${escapeHtml(col)}</th>`;
            });
            html += '</tr></thead><tbody>';
            
            // 데이터 행들
            data.data.forEach(row => {
                html += '<tr>';
                columns.forEach(col => {
                    const value = row[col];
                    let displayValue = '';
                    
                    if (value === null) {
                        displayValue = '<em class="text-muted">NULL</em>';
                    } else if (typeof value === 'string' && value.length > 100) {
                        displayValue = escapeHtml(value.substring(0, 100)) + '<span class="text-muted">...</span>';
                    } else {
                        displayValue = escapeHtml(String(value));
                    }
                    
                    html += `<td>${displayValue}</td>`;
                });
                html += '</tr>';
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        ${data.count}개 행 반환 | 실행 시간: <span id="queryTime">완료</span>
                    </small>
                </div>
            `;
            
            resultsDiv.innerHTML = html;
            
        } else if (data.type === 'select') {
            resultsDiv.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    쿼리가 성공적으로 실행되었습니다. 반환된 행이 없습니다.
                </div>
            `;
        } else {
            resultsDiv.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check me-2"></i>
                    ${data.message || '쿼리가 성공적으로 실행되었습니다.'}
                </div>
            `;
        }
    } else {
        resultsDiv.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                알 수 없는 응답 형식입니다.
            </div>
        `;
    }
}

function formatQuery() {
    const textarea = document.getElementById('sqlQuery');
    let query = textarea.value.trim();
    
    if (!query) {
        alert('포맷할 쿼리를 입력해주세요');
        return;
    }
    
    // SQL 포맷팅
    query = query.replace(/\s+/g, ' '); // 여러 공백을 하나로
    query = query.replace(/\s*,\s*/g, ',\n    '); // 콤마 후 줄바꿈
    query = query.replace(/\s+(SELECT|FROM|WHERE|JOIN|INNER JOIN|LEFT JOIN|RIGHT JOIN|ORDER BY|GROUP BY|HAVING|LIMIT)\s+/gi, '\n$1 ');
    query = query.replace(/\s+(AND|OR)\s+/gi, '\n    $1 ');
    query = query.replace(/^\s+/gm, '    '); // 들여쓰기 정리
    
    textarea.value = query.trim();
}

// Helper functions for mock data
function generateSampleData(tableName) {
    const mockData = {
        'projects': [
            { id: 1, name: 'MyWeb Project', root_path: '/var/www/myweb', is_active: 1, created_at: '2024-01-15 10:30:00' },
            { id: 2, name: 'API Service', root_path: '/var/api', is_active: 1, created_at: '2024-01-16 14:20:00' },
            { id: 3, name: 'Legacy App', root_path: '/old/app', is_active: 0, created_at: '2024-01-10 09:15:00' }
        ],
        'scans': [
            { id: 1, project_id: 1, status: 'completed', total_files: 245, total_loc: 12450, started_at: '2024-01-20 15:30:00' },
            { id: 2, project_id: 2, status: 'running', total_files: 89, total_loc: 4320, started_at: '2024-01-21 10:00:00' }
        ],
        'agents': [
            { id: 1, name: 'Production Agent', host: 'prod-server.local', status: 'active', last_seen: '2024-01-21 16:45:00' },
            { id: 2, name: 'Dev Agent', host: 'dev-server.local', status: 'active', last_seen: '2024-01-21 16:44:30' }
        ]
    };
    
    return mockData[tableName] || [];
}

function getTableColumns(tableName) {
    const columns = {
        'projects': ['id', 'name', 'root_path', 'is_active', 'created_at'],
        'scans': ['id', 'project_id', 'status', 'total_files', 'total_loc', 'started_at'],
        'agents': ['id', 'name', 'host', 'status', 'last_seen']
    };
    
    return columns[tableName] || ['id', 'data'];
}

function getTableStructure(tableName) {
    const structures = {
        'projects': [
            { Field: 'id', Type: 'int(11)', Null: 'NO', Key: 'PRI', Default: null, Extra: 'auto_increment' },
            { Field: 'name', Type: 'varchar(255)', Null: 'NO', Key: '', Default: null, Extra: '' },
            { Field: 'root_path', Type: 'text', Null: 'NO', Key: '', Default: null, Extra: '' },
            { Field: 'is_active', Type: 'tinyint(1)', Null: 'NO', Key: '', Default: '1', Extra: '' },
            { Field: 'created_at', Type: 'timestamp', Null: 'NO', Key: '', Default: 'CURRENT_TIMESTAMP', Extra: '' }
        ],
        'scans': [
            { Field: 'id', Type: 'int(11)', Null: 'NO', Key: 'PRI', Default: null, Extra: 'auto_increment' },
            { Field: 'project_id', Type: 'int(11)', Null: 'NO', Key: 'MUL', Default: null, Extra: '' },
            { Field: 'status', Type: 'enum("pending","running","completed","failed")', Null: 'NO', Key: '', Default: 'pending', Extra: '' },
            { Field: 'total_files', Type: 'int(11)', Null: 'YES', Key: '', Default: null, Extra: '' },
            { Field: 'total_loc', Type: 'bigint(20)', Null: 'YES', Key: '', Default: null, Extra: '' },
            { Field: 'started_at', Type: 'timestamp', Null: 'NO', Key: '', Default: 'CURRENT_TIMESTAMP', Extra: '' }
        ]
    };
    
    return structures[tableName] || [
        { Field: 'id', Type: 'int(11)', Null: 'NO', Key: 'PRI', Default: null, Extra: 'auto_increment' }
    ];
}

function getTableIndexes(tableName) {
    const indexes = {
        'projects': [
            { Key_name: 'PRIMARY', Non_unique: '0', Column_name: 'id', Index_type: 'BTREE' },
            { Key_name: 'idx_name', Non_unique: '1', Column_name: 'name', Index_type: 'BTREE' }
        ],
        'scans': [
            { Key_name: 'PRIMARY', Non_unique: '0', Column_name: 'id', Index_type: 'BTREE' },
            { Key_name: 'idx_project_id', Non_unique: '1', Column_name: 'project_id', Index_type: 'BTREE' }
        ]
    };
    
    return indexes[tableName] || [];
}

function generateQueryResults(query) {
    const lowerQuery = query.toLowerCase().trim();
    
    if (lowerQuery.includes('select')) {
        return {
            type: 'select',
            results: [
                { id: 1, name: 'Sample Result', value: 'Query executed successfully' },
                { id: 2, name: 'Another Row', value: 'Mock data returned' }
            ]
        };
    } else {
        return {
            type: 'modify',
            affected_rows: Math.floor(Math.random() * 10) + 1
        };
    }
}

// Utility functions
function updateBreadcrumb(path) {
    const breadcrumb = document.getElementById('breadcrumb');
    if (breadcrumb) {
        breadcrumb.innerHTML = `<i class="fas fa-user-shield me-2"></i>${path}`;
    }
}

function loadDatabaseStats() {
    // Mock stats loading
    const sizeElement = document.getElementById('dbSize');
    if (sizeElement) {
        setTimeout(() => {
            sizeElement.textContent = '15.2 MB';
        }, 1000);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function refreshTableData(tableName) {
    loadTableContent(tableName);
}

function exportTableData(tableName) {
    window.open(`/db/export?table=${tableName}`, '_blank');
}

function insertSample(type) {
    const sqlField = document.getElementById('sqlQuery');
    let sampleQuery = '';
    
    switch(type) {
        case 'SELECT':
            sampleQuery = 'SELECT * FROM projects LIMIT 10;';
            break;
        case 'SHOW_TABLES':
            sampleQuery = "SHOW TABLES;";
            break;
        case 'SHOW_COLUMNS':
            sampleQuery = "SHOW COLUMNS FROM projects;";
            break;
        case 'TABLE_STATUS':
            sampleQuery = "SHOW TABLE STATUS;";
            break;
        case 'DESCRIBE':
            sampleQuery = "DESCRIBE projects;";
            break;
        case 'PROJECTS':
            sampleQuery = 'SELECT id, name, root_path, is_active, scan_count, last_scan, created_at FROM projects ORDER BY created_at DESC LIMIT 10;';
            break;
        case 'SCANS':
            sampleQuery = 'SELECT s.id, p.name as project_name, s.status, s.total_files, s.total_loc, s.started_at, s.completed_at FROM scans s LEFT JOIN projects p ON s.project_id = p.id ORDER BY s.started_at DESC LIMIT 15;';
            break;
        case 'OSES':
            sampleQuery = 'SELECT id, name, version, arch, hostname, ip_address, access_level, status, created_at FROM oses ORDER BY created_at DESC;';
            break;
        case 'AGENTS':
            // agents table columns: id, name, version, os_id, notes, created_at, updated_at
            sampleQuery = 'SELECT id, name, version, os_id, created_at FROM agents ORDER BY created_at DESC LIMIT 15;';
            break;
        case 'STATS':
            // use counts compatible with existing schema
            sampleQuery = 'SELECT\n  (SELECT COUNT(*) FROM projects WHERE is_active = 1) as active_projects,\n  (SELECT COUNT(*) FROM scans) as total_scans,\n  (SELECT COUNT(*) FROM oses) as total_oses,\n  (SELECT COUNT(*) FROM agents) as total_agents;';
            break;
    }
    
    sqlField.value = sampleQuery;
    sqlField.focus();
}

function clearQuery() {
    document.getElementById('sqlQuery').value = '';
    document.getElementById('queryResults').innerHTML = '';
}

// System Monitoring Functions
function refreshSystemInfo() {
    const container = document.getElementById('system-monitoring');
    container.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">시스템 정보를 업데이트하는 중...</p>
        </div>
    `;
    
    fetch('/ajax/system-info')
        .then(response => response.json())
        .then(data => {
            renderSystemInfo(container, data);
        })
        .catch(error => {
            container.innerHTML = '<div class="alert alert-danger">시스템 정보 로드 실패: ' + error.message + '</div>';
        });
}

function renderSystemInfo(container, data) {
    const { server_info, memory_usage, disk_usage, cpu_load, php_info, database_info } = data;
    
    container.innerHTML = `
        <div class="row">
            <!-- CPU 및 메모리 -->
            <div class="col-md-6">
                <h6>💻 CPU & Memory</h6>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>CPU Load (1분)</span>
                        <span class="text-${cpu_load.usage_percentage > 80 ? 'danger' : cpu_load.usage_percentage > 50 ? 'warning' : 'success'}">
                            ${cpu_load.usage_percentage || 'N/A'}%
                        </span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-${cpu_load.usage_percentage > 80 ? 'danger' : cpu_load.usage_percentage > 50 ? 'warning' : 'success'}" 
                             style="width: ${Math.min(cpu_load.usage_percentage || 0, 100)}%"></div>
                    </div>
                    <small class="text-muted">
                        Load: ${cpu_load.load_average ? cpu_load.load_average['1min'] + ' / ' + cpu_load.load_average['5min'] + ' / ' + cpu_load.load_average['15min'] : 'N/A'} 
                        (Cores: ${cpu_load.core_count || 'N/A'})
                    </small>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>System Memory</span>
                        <span class="text-${memory_usage.system.usage_percentage > 80 ? 'danger' : memory_usage.system.usage_percentage > 50 ? 'warning' : 'success'}">
                            ${memory_usage.system.usage_percentage}%
                        </span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-${memory_usage.system.usage_percentage > 80 ? 'danger' : memory_usage.system.usage_percentage > 50 ? 'warning' : 'success'}" 
                             style="width: ${memory_usage.system.usage_percentage}%"></div>
                    </div>
                    <small class="text-muted">
                        Used: ${formatBytes(memory_usage.system.used)} / Total: ${formatBytes(memory_usage.system.total)}
                    </small>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>PHP Memory</span>
                        <span class="text-${memory_usage.php.usage_percentage > 80 ? 'danger' : memory_usage.php.usage_percentage > 50 ? 'warning' : 'success'}">
                            ${memory_usage.php.usage_percentage}%
                        </span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-info" style="width: ${memory_usage.php.usage_percentage}%"></div>
                    </div>
                    <small class="text-muted">
                        Current: ${formatBytes(memory_usage.php.current_usage)} / Peak: ${formatBytes(memory_usage.php.peak_usage)}
                    </small>
                </div>
            </div>
            
            <!-- 디스크 및 서버 정보 -->
            <div class="col-md-6">
                <h6>💾 Storage & Server</h6>
                ${disk_usage.main ? `
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Disk Usage</span>
                        <span class="text-${disk_usage.main.usage_percentage > 90 ? 'danger' : disk_usage.main.usage_percentage > 70 ? 'warning' : 'success'}">
                            ${disk_usage.main.usage_percentage}%
                        </span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-${disk_usage.main.usage_percentage > 90 ? 'danger' : disk_usage.main.usage_percentage > 70 ? 'warning' : 'success'}" 
                             style="width: ${disk_usage.main.usage_percentage}%"></div>
                    </div>
                    <small class="text-muted">
                        Free: ${formatBytes(disk_usage.main.free)} / Total: ${formatBytes(disk_usage.main.total)}
                    </small>
                </div>
                ` : ''}
                
                <div class="row">
                    <div class="col-6">
                        <h6 class="h6">Server Info</h6>
                        <small class="text-muted">
                            <strong>OS:</strong> ${server_info.operating_system}<br>
                            <strong>Host:</strong> ${server_info.hostname}<br>
                            <strong>Arch:</strong> ${server_info.architecture}<br>
                            <strong>Uptime:</strong> ${formatUptime(server_info.uptime)}
                        </small>
                    </div>
                    <div class="col-6">
                        <h6 class="h6">PHP Info</h6>
                        <small class="text-muted">
                            <strong>Version:</strong> ${php_info.version}<br>
                            <strong>SAPI:</strong> ${php_info.sapi}<br>
                            <strong>Memory:</strong> ${php_info.memory_limit}<br>
                            <strong>Extensions:</strong> ${php_info.extensions_count}
                        </small>
                    </div>
                </div>
                
                <div class="mt-3">
                    <h6 class="h6">Database Info</h6>
                    <small class="text-muted">
                        <strong>Type:</strong> ${database_info.type}<br>
                        <strong>Version:</strong> ${database_info.version || 'N/A'}<br>
                        <strong>Size:</strong> ${formatBytes(database_info.size)}<br>
                        <strong>Tables:</strong> ${database_info.tables_count}
                    </small>
                </div>
            </div>
        </div>
    `;
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatUptime(seconds) {
    if (!seconds) return 'N/A';
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return `${days}d ${hours}h ${minutes}m`;
}

// SQL Query Console 기능 초기화
document.addEventListener('DOMContentLoaded', function() {
    const sqlField = document.getElementById('sqlQuery');
    
    if (sqlField) {
        // SQL 키워드 검증 및 하이라이팅
        sqlField.addEventListener('input', function() {
            const value = this.value.toLowerCase();
            const dangerousKeywords = ['drop ', 'delete ', 'truncate ', 'update ', 'insert ', 'alter ', 'create '];
            
            let hasDangerous = false;
            for (let keyword of dangerousKeywords) {
                if (value.includes(keyword)) {
                    hasDangerous = true;
                    break;
                }
            }
            
            if (hasDangerous) {
                this.style.borderColor = '#dc3545';
                this.style.backgroundColor = '#fff5f5';
                this.title = '위험한 명령어가 포함되어 있습니다. SELECT와 DESCRIBE만 허용됩니다.';
            } else {
                this.style.borderColor = '';
                this.style.backgroundColor = '';
                this.title = '';
            }
        });
        
        // Enter 키로 쿼리 실행 (Ctrl+Enter)
        sqlField.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                executeQuery();
            }
        });
    }
    
    // 초기 시스템 정보 로드
    refreshSystemInfo();
    
    // 30초마다 자동 새로고침
    setInterval(refreshSystemInfo, 30000);
});
</script>