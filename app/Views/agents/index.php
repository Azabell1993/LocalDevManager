<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Agent Management</h2>
    <a href="/agents/create" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Agent
    </a>
</div>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Development Agents</h5>
        </div>
        
        <!-- 검색 필터 -->
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="searchInput" placeholder="에이전트 이름, 버전으로 검색...">
                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="osFilter">
                    <option value="">모든 OS</option>
                    <option value="windows">Windows</option>
                    <option value="macos">macOS</option>
                    <option value="linux">Linux</option>
                    <option value="ubuntu">Ubuntu</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small" id="agentCount"><?= count($agents) ?>개 에이전트</span>
                    <button class="btn btn-sm btn-outline-primary" id="refreshAgents" title="새로고침">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($agents)): ?>
            <div class="text-center py-5">
                <i class="fas fa-robot fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No agents found</h5>
                <p class="text-muted">Start by adding your first development agent or tool.</p>
                <a href="/agents/create" class="btn btn-primary">Add First Agent</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Agent Name</th>
                            <th>Version</th>
                            <th>Operating System</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agents as $agent): ?>
                            <tr>
                                <td>
                                    <i class="fas fa-cog me-2 text-primary"></i>
                                    <strong><?= htmlspecialchars($agent['name']) ?></strong>
                                    <?php if (!empty($agent['notes'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($agent['notes']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($agent['version'])): ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($agent['version']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($agent['os_name'])): ?>
                                        <i class="fab fa-<?= strtolower($agent['os_name']) === 'windows' ? 'windows' : (strtolower($agent['os_name']) === 'macos' ? 'apple' : 'linux') ?> me-2"></i>
                                        <?= htmlspecialchars($agent['os_name']) ?>
                                        <?php if (!empty($agent['os_version'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars($agent['os_version']) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('M j, Y', strtotime($agent['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 align-items-center">
                                        <!-- Edit Button -->
                                        <a href="/agents/<?= $agent['id'] ?>/edit" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Edit Agent">
                                            <i class="fas fa-edit me-1"></i>
                                            Edit
                                        </a>
                                        
                                        <!-- Status Toggle Switch -->
                                        <form method="POST" action="/agents/<?= $agent['id'] ?>/toggle" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= Csrf::generate() ?>">
                                            <button type="submit" 
                                                    class="btn btn-sm status-toggle <?= ($agent['status'] ?? 'active') === 'active' ? 'btn-success' : 'btn-outline-secondary' ?>" 
                                                    title="<?= ($agent['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Activate' ?> Agent">
                                                <i class="fas fa-<?= ($agent['status'] ?? 'active') === 'active' ? 'toggle-on' : 'toggle-off' ?> me-1"></i>
                                                <span class="status-text"><?= ($agent['status'] ?? 'active') === 'active' ? 'ON' : 'OFF' ?></span>
                                            </button>
                                        </form>
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
// 검색 기능 초기화
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const osFilter = document.getElementById('osFilter');
    const clearSearch = document.getElementById('clearSearch');
    const refreshAgents = document.getElementById('refreshAgents');
    const agentCount = document.getElementById('agentCount');
    const tableRows = document.querySelectorAll('.table tbody tr');
    
    // 검색 기능
    function filterAgents() {
        const searchTerm = searchInput.value.toLowerCase();
        const osValue = osFilter.value.toLowerCase();
        let visibleCount = 0;
        
        tableRows.forEach(row => {
            const agentName = row.querySelector('td:first-child strong').textContent.toLowerCase();
            const version = row.querySelector('td:nth-child(2) .badge')?.textContent.toLowerCase() || '';
            const osName = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
            
            const matchesSearch = agentName.includes(searchTerm) || version.includes(searchTerm);
            const matchesOs = !osValue || osName.includes(osValue);
            
            if (matchesSearch && matchesOs) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        agentCount.textContent = `${visibleCount}개 에이전트`;
        
        // 검색 결과가 없을 때 메시지 표시
        const tbody = document.querySelector('.table tbody');
        let noResultsRow = document.getElementById('noResultsRow');
        
        if (visibleCount === 0 && tableRows.length > 0) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.id = 'noResultsRow';
                noResultsRow.innerHTML = `
                    <td colspan="5" class="text-center py-4">
                        <i class="fas fa-search fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">검색 조건에 맞는 에이전트가 없습니다.</p>
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
        searchInput.addEventListener('input', filterAgents);
        osFilter.addEventListener('change', filterAgents);
        
        // 검색 초기화
        clearSearch.addEventListener('click', function() {
            searchInput.value = '';
            osFilter.value = '';
            filterAgents();
            searchInput.focus();
        });
        
        // 새로고침
        refreshAgents.addEventListener('click', function() {
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
            window.location.href = `?page=1&limit=${this.value}`;
        });
    }
});

function deleteAgent(id, name) {
    if (confirm('Are you sure you want to delete this agent?\n\n' + name)) {
        // Create and submit form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/agents/' + id + '/delete';
        
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