<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Scan History</h2>
    <a href="/scans/create" class="btn btn-primary">
        <i class="fas fa-play"></i> New Scan
    </a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <?= htmlspecialchars($_SESSION['success']) ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">LOC Scan Results</h5>
        </div>
        
        <!-- 검색 필터 -->
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="searchInput" placeholder="프로젝트 이름으로 검색...">
                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="statusFilter">
                    <option value="">모든 상태</option>
                    <option value="completed">완료됨</option>
                    <option value="running">실행 중</option>
                    <option value="failed">실패</option>
                    <option value="queued">대기 중</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small" id="scanCount"><?= count($scans) ?>개 스캔</span>
                    <button class="btn btn-sm btn-outline-primary" id="refreshScans" title="새로고침">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($scans)): ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No scans found</h5>
                <p class="text-muted">Start by running your first LOC scan on a project.</p>
                <a href="/scans/create" class="btn btn-primary">Run First Scan</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Status</th>
                            <th>Total LOC</th>
                            <th>Started</th>
                            <th>Duration</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scans as $scan): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($scan['project_name'] ?? 'Unknown Project') ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'success' => 'success',
                                        'running' => 'warning',
                                        'failed' => 'danger',
                                        'queued' => 'info'
                                    ][$scan['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $statusClass ?>">
                                        <?= ucfirst($scan['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($scan['total_loc'] > 0): ?>
                                        <strong><?= number_format($scan['total_loc']) ?></strong>
                                        <small class="text-muted">lines</small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('M j, Y H:i', strtotime($scan['started_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($scan['completed_at']): ?>
                                        <?php
                                        $start = new DateTime($scan['started_at']);
                                        $end = new DateTime($scan['completed_at']);
                                        $duration = $end->diff($start);
                                        ?>
                                        <small class="text-muted">
                                            <?php if ($duration->h > 0): ?>
                                                <?= $duration->h ?>h 
                                            <?php endif; ?>
                                            <?php if ($duration->i > 0): ?>
                                                <?= $duration->i ?>m 
                                            <?php endif; ?>
                                            <?= $duration->s ?>s
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 align-items-center">
                                        <!-- View Details Button -->
                                        <a href="/scans/<?= $scan['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="View Scan Details">
                                            <i class="fas fa-eye me-1"></i>
                                            View
                                        </a>
                                        
                                        <?php if ($scan['status'] === 'running'): ?>
                                            <!-- Running Status -->
                                            <button type="button" class="btn btn-sm btn-warning" disabled>
                                                <i class="fas fa-spinner fa-spin me-1"></i>
                                                Running
                                            </button>
                                        <?php elseif ($scan['status'] === 'completed'): ?>
                                            <!-- Archive Button instead of Delete -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary" 
                                                    onclick="archiveScan(<?= $scan['id'] ?>, '<?= htmlspecialchars($scan['project_name']) ?>')"
                                                    title="Archive Scan">
                                                <i class="fas fa-archive me-1"></i>
                                                Archive
                                            </button>
                                        <?php else: ?>
                                            <!-- Failed Status -->
                                            <span class="badge bg-danger">Failed</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- 간단한 페이징 UI -->
            <?php if (isset($pagination) && $pagination['totalPages'] > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="d-flex align-items-center">
                    <select class="form-select form-select-sm me-3" id="limitSelect" style="width: auto;">
                        <option value="10" <?= $pagination['limit'] == 10 ? 'selected' : '' ?>>10개</option>
                        <option value="15" <?= $pagination['limit'] == 15 ? 'selected' : '' ?>>15개</option>
                        <option value="30" <?= $pagination['limit'] == 30 ? 'selected' : '' ?>>30개</option>
                    </select>
                    <small class="text-muted">총 <?= $pagination['total'] ?>개</small>
                </div>
                <nav>
                    <ul class="pagination mb-0">
                        <?php if ($pagination['page'] > 1): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?= $pagination['page'] - 1 ?>&limit=<?= $pagination['limit'] ?>">이전</a></li>
                        <?php endif; ?>
                        <li class="page-item active"><span class="page-link"><?= $pagination['page'] ?></span></li>
                        <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?= $pagination['page'] + 1 ?>&limit=<?= $pagination['limit'] ?>">다음</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function deleteScan(id, projectName) {
    if (confirm('Are you sure you want to delete this scan result?\n\n' + projectName)) {
        // Create and submit form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/scans/' + id + '/delete';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= Csrf::generate() ?>';
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-refresh for running scans and search functionality
document.addEventListener('DOMContentLoaded', function() {
    const runningScans = document.querySelectorAll('.badge.bg-warning');
    if (runningScans.length > 0) {
        setTimeout(() => {
            window.location.reload();
        }, 5000); // Refresh every 5 seconds if there are running scans
    }
    
    // 검색 기능 초기화
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const clearSearch = document.getElementById('clearSearch');
    const refreshScans = document.getElementById('refreshScans');
    const scanCount = document.getElementById('scanCount');
    const tableRows = document.querySelectorAll('.table tbody tr');
    
    // 검색 기능
    function filterScans() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();
        let visibleCount = 0;
        
        tableRows.forEach(row => {
            const projectName = row.querySelector('td:first-child strong').textContent.toLowerCase();
            const statusBadge = row.querySelector('.badge');
            const currentStatus = statusBadge ? statusBadge.textContent.toLowerCase() : '';
            
            const matchesSearch = projectName.includes(searchTerm);
            const matchesStatus = !statusValue || currentStatus.includes(statusValue);
            
            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        scanCount.textContent = `${visibleCount}개 스캔`;
        
        // 검색 결과가 없을 때 메시지 표시
        const tbody = document.querySelector('.table tbody');
        let noResultsRow = document.getElementById('noResultsRow');
        
        if (visibleCount === 0 && tableRows.length > 0) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.id = 'noResultsRow';
                noResultsRow.innerHTML = `
                    <td colspan="6" class="text-center py-4">
                        <i class="fas fa-search fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">검색 조건에 맞는 스캔이 없습니다.</p>
                        <small class="text-muted">다른 검색어를 시도해보세요.</small>
                    </td>
                `;
                tbody.appendChild(noResultsRow);
            }
        } else if (noResultsRow) {
            noResultsRow.remove();
        }
    }
    
    // 검색 이벤트 리스너
    if (searchInput) {
        searchInput.addEventListener('input', filterScans);
        statusFilter.addEventListener('change', filterScans);
        
        // 검색 초기화
        clearSearch.addEventListener('click', function() {
            searchInput.value = '';
            statusFilter.value = '';
            filterScans();
            searchInput.focus();
        });
        
        // 새로고침
        refreshScans.addEventListener('click', function() {
            location.reload();
        });
        
        // 키보드 단축키
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }

    // 페이지 크기 변경
    const limitSelect = document.getElementById('limitSelect');
    if (limitSelect) {
        limitSelect.addEventListener('change', function() {
            const params = new URLSearchParams(window.location.search);
            params.set('page', '1');
            params.set('limit', this.value);
            window.location.href = '?' + params.toString();
        });
    }
});
</script>