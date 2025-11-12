<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>OS Management</h2>
    <a href="/os/create" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New OS
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
            <h5 class="mb-0">Operating Systems</h5>
        </div>
        
        <!-- 검색 필터 -->
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="searchInput" placeholder="OS 이름, 호스트명, IP로 검색...">
                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="statusFilter">
                    <option value="">모든 상태</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small" id="osCount"><?= count($oses) ?>개 OS</span>
                    <button class="btn btn-sm btn-outline-primary" id="refreshOs" title="새로고침">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($oses)): ?>
            <div class="text-center py-5">
                <i class="fas fa-desktop fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No OS entries found</h5>
                <p class="text-muted">Start by adding your first operating system entry.</p>
                <a href="/os/create" class="btn btn-primary">Add First OS</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>OS Name</th>
                            <th>Version</th>
                            <th>Architecture</th>
                            <th>Hostname</th>
                            <th>IP Address</th>
                            <th>Access Level</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($oses as $os): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fab fa-<?= strtolower($os['name']) === 'windows' ? 'windows' : (strtolower($os['name']) === 'macos' ? 'apple' : 'linux') ?> me-2 text-primary"></i>
                                        <div>
                                            <strong><?= htmlspecialchars($os['name']) ?></strong>
                                            <?php if (!empty($os['description'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars(mb_substr($os['description'], 0, 30) . (mb_strlen($os['description']) > 30 ? '...' : '')) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($os['version'])): ?>
                                        <?= htmlspecialchars($os['version']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($os['arch'])): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($os['arch']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Unknown</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($os['hostname'])): ?>
                                        <code title="Hostname: <?= htmlspecialchars($os['hostname']) ?>"><?= htmlspecialchars($os['hostname']) ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($os['ip_address'])): ?>
                                        <code title="IP Address: <?= htmlspecialchars($os['ip_address']) ?>"><?= htmlspecialchars($os['ip_address']) ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $accessLevel = $os['access_level'] ?? 'user';
                                    $badgeClass = match($accessLevel) {
                                        'admin' => 'bg-danger',
                                        'user' => 'bg-primary',
                                        'readonly' => 'bg-secondary',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= ucfirst($accessLevel) ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $status = $os['status'] ?? 'active';
                                    $statusClass = $status === 'active' ? 'bg-success' : 'bg-warning';
                                    ?>
                                    <span class="badge <?= $statusClass ?>"><?= ucfirst($status) ?></span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('M j, Y', strtotime($os['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 align-items-center flex-wrap">
                                        <!-- View Details Button -->
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                onclick="showOsDetails(<?= $os['id'] ?>, '<?= htmlspecialchars($os['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($os['version'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($os['arch'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($os['hostname'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($os['ip_address'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($os['access_level'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($os['description'] ?? '', ENT_QUOTES) ?>')"
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <!-- Edit Button -->
                                        <a href="/os/<?= $os['id'] ?>/edit" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Edit OS">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <!-- Status Toggle Switch -->
                                        <form method="POST" action="/os/<?= $os['id'] ?>/toggle" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= Csrf::generate() ?>">
                                            <button type="submit" 
                                                    class="btn btn-sm <?= ($os['status'] ?? 'active') === 'active' ? 'btn-success' : 'btn-outline-secondary' ?>" 
                                                    title="<?= ($os['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Activate' ?> OS">
                                                <i class="fas fa-<?= ($os['status'] ?? 'active') === 'active' ? 'toggle-on' : 'toggle-off' ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- 페이징 UI -->
            <?php if (isset($pagination) && $pagination['totalPages'] > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-4">
                <!-- 페이지 크기 선택 -->
                <div class="d-flex align-items-center">
                    <label class="form-label me-2 mb-0">페이지당:</label>
                    <select class="form-select form-select-sm me-3" id="limitSelect" style="width: auto;">
                        <option value="5" <?= $pagination['limit'] == 5 ? 'selected' : '' ?>>5개</option>
                        <option value="10" <?= $pagination['limit'] == 10 ? 'selected' : '' ?>>10개</option>
                        <option value="15" <?= $pagination['limit'] == 15 ? 'selected' : '' ?>>15개</option>
                        <option value="30" <?= $pagination['limit'] == 30 ? 'selected' : '' ?>>30개</option>
                    </select>
                    <small class="text-muted">
                        총 <?= number_format($pagination['total']) ?>개 중 
                        <?= number_format(($pagination['page'] - 1) * $pagination['limit'] + 1) ?>-<?= number_format(min($pagination['page'] * $pagination['limit'], $pagination['total'])) ?>개 표시
                    </small>
                </div>

                <!-- 페이지 네비게이션 -->
                <nav aria-label="OS 페이지네이션">
                    <ul class="pagination mb-0">
                        <!-- 첫 페이지 -->
                        <?php if ($pagination['page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildOsPaginationUrl(1, $pagination) ?>" title="첫 페이지">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildOsPaginationUrl($pagination['page'] - 1, $pagination) ?>" title="이전 페이지">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-double-left"></i></span>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-left"></i></span>
                            </li>
                        <?php endif; ?>

                        <!-- 페이지 번호들 -->
                        <?php
                        $start = max(1, $pagination['page'] - 2);
                        $end = min($pagination['totalPages'], $pagination['page'] + 2);
                        
                        for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item <?= $i == $pagination['page'] ? 'active' : '' ?>">
                                <?php if ($i == $pagination['page']): ?>
                                    <span class="page-link"><?= $i ?></span>
                                <?php else: ?>
                                    <a class="page-link" href="<?= buildOsPaginationUrl($i, $pagination) ?>"><?= $i ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>

                        <!-- 다음 페이지 -->
                        <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildOsPaginationUrl($pagination['page'] + 1, $pagination) ?>" title="다음 페이지">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildOsPaginationUrl($pagination['totalPages'], $pagination) ?>" title="마지막 페이지">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-right"></i></span>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-double-right"></i></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// OS 페이징 URL 생성 함수
function buildOsPaginationUrl($page, $pagination) {
    $params = [];
    if (isset($_GET['search']) && $_GET['search'] !== '') {
        $params['search'] = $_GET['search'];
    }
    $params['page'] = $page;
    $params['limit'] = $pagination['limit'];
    
    return '/os?' . http_build_query($params);
}
?>



<script>
// Initialize Bootstrap tooltips and search functionality
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // 검색 기능 초기화
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const clearSearch = document.getElementById('clearSearch');
    const refreshOs = document.getElementById('refreshOs');
    const osCount = document.getElementById('osCount');
    const tableRows = document.querySelectorAll('.table tbody tr');
    
    // 검색 기능
    function filterOs() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();
        let visibleCount = 0;
        
        tableRows.forEach(row => {
            const osName = row.querySelector('td:first-child strong').textContent.toLowerCase();
            const hostname = row.querySelector('td:nth-child(4) code')?.textContent.toLowerCase() || '';
            const ipAddress = row.querySelector('td:nth-child(5) code')?.textContent.toLowerCase() || '';
            const statusBadge = row.querySelector('td:nth-child(7) .badge');
            const currentStatus = statusBadge ? statusBadge.textContent.toLowerCase() : '';
            
            const matchesSearch = osName.includes(searchTerm) || 
                                hostname.includes(searchTerm) || 
                                ipAddress.includes(searchTerm);
            const matchesStatus = !statusValue || currentStatus.includes(statusValue);
            
            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        osCount.textContent = `${visibleCount}개 OS`;
        
        // 검색 결과가 없을 때 메시지 표시
        const tbody = document.querySelector('.table tbody');
        let noResultsRow = document.getElementById('noResultsRow');
        
        if (visibleCount === 0 && tableRows.length > 0) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.id = 'noResultsRow';
                noResultsRow.innerHTML = `
                    <td colspan="9" class="text-center py-4">
                        <i class="fas fa-search fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">검색 조건에 맞는 OS가 없습니다.</p>
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
        searchInput.addEventListener('input', filterOs);
        statusFilter.addEventListener('change', filterOs);
        
        // 검색 초기화
        clearSearch.addEventListener('click', function() {
            searchInput.value = '';
            statusFilter.value = '';
            filterOs();
            searchInput.focus();
        });
        
        // 새로고침
        refreshOs.addEventListener('click', function() {
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

    // 페이지 크기 변경 처리
    const limitSelect = document.getElementById('limitSelect');
    if (limitSelect) {
        limitSelect.addEventListener('change', function() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('limit', this.value);
            currentUrl.searchParams.set('page', '1');
            window.location.href = currentUrl.toString();
        });
    }
});

function showOsDetails(id, name, version, arch, hostname, ipAddress, accessLevel, description) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fab fa-${name.toLowerCase() === 'windows' ? 'windows' : (name.toLowerCase() === 'macos' ? 'apple' : 'linux')} me-2"></i>
                        OS Details: ${name}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">OS Name:</label>
                            <div class="form-control-plaintext">${name}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Version:</label>
                            <div class="form-control-plaintext">${version || '<span class="text-muted">-</span>'}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Architecture:</label>
                            <div class="form-control-plaintext">${arch ? '<span class="badge bg-info">' + arch + '</span>' : '<span class="text-muted">-</span>'}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Access Level:</label>
                            <div class="form-control-plaintext">${accessLevel ? '<span class="badge bg-' + (accessLevel === 'admin' ? 'danger' : accessLevel === 'user' ? 'primary' : 'secondary') + '">' + accessLevel.charAt(0).toUpperCase() + accessLevel.slice(1) + '</span>' : '<span class="text-muted">-</span>'}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Hostname:</label>
                            <div class="form-control-plaintext">${hostname ? '<code>' + hostname + '</code>' : '<span class="text-muted">-</span>'}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">IP Address:</label>
                            <div class="form-control-plaintext">${ipAddress ? '<code>' + ipAddress + '</code>' : '<span class="text-muted">-</span>'}</div>
                        </div>
                        ${description ? `
                        <div class="col-12">
                            <label class="form-label fw-bold">Description:</label>
                            <div class="form-control-plaintext">${description}</div>
                        </div>
                        ` : ''}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="/os/${id}/edit" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Edit OS
                    </a>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    bsModal.show();
    
    // Remove modal from DOM after it's hidden
    modal.addEventListener('hidden.bs.modal', function () {
        document.body.removeChild(modal);
    });
}

function deleteOs(id, name) {
    if (confirm('Are you sure you want to delete this OS entry?\n\n' + name)) {
        // Create and submit form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/os/' + id + '/delete';

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= Csrf::generate() ?>';

        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>