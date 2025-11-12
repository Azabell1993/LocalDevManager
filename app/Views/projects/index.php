<div class="row">
    <div class="col">
        <h2>Project Management</h2>
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="card-title">프로젝트 관리</h3>
                    <div class="d-flex align-items-center gap-2">
                    <!-- Quick Actions Buttons -->
                    <div class="btn-group" role="group">
                        <a href="/scans" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-chart-bar me-1"></i>스캔 결과
                        </a>
                    </div>

                    <a href="/projects/create" class="btn btn-primary">새 프로젝트</a>
                </div>
                </div>

                <!-- 검색 필터 -->
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="searchInput" placeholder="프로젝트 이름, 경로로 검색...">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="statusFilter">
                            <option value="">모든 상태</option>
                            <option value="active">활성</option>
                            <option value="inactive">비활성</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted small" id="projectCount"><?= count($projects) ?>개 프로젝트</span>
                            <button class="btn btn-sm btn-outline-primary" id="refreshProjects" title="새로고침">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($projects)): ?>
                    <p class="text-center text-muted">등록된 프로젝트가 없습니다.</p>
                    <div class="text-center">
                        <a href="/projects/create" class="btn btn-primary">첫 프로젝트 등록</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 22%;">프로젝트명</th>
                                    <th style="width: 32%;">경로</th>
                                    <th class="text-center" style="width: 10%;">상태</th>
                                    <th class="text-center" style="width: 10%;">스캔 횟수</th>
                                    <th class="text-center" style="width: 14%;">마지막 스캔</th>
                                    <th class="text-center" style="width: 12%;">액션</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                <tr class="<?= !$project['path_exists'] ? 'table-warning' : '' ?>">
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($project['name']) ?></strong>
                                            <?php if (!$project['path_exists']): ?>
                                                <span class="badge bg-warning text-dark ms-2">
                                                    <i class="fas fa-exclamation-triangle"></i> 경로 없음
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($project['description']): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($project['description']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <code class="<?= !$project['path_exists'] ? 'text-danger' : 'text-muted' ?>">
                                            <?= htmlspecialchars($project['root_path'] ?? $project['path'] ?? '경로 없음') ?>
                                        </code>
                                        <?php if (!$project['path_exists']): ?>
                                            <br><small class="text-danger"><i class="fas fa-times"></i> 디렉토리가 존재하지 않습니다</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!$project['path_exists']): ?>
                                            <span class="badge bg-danger">경로 없음</span>
                                        <?php elseif ($project['is_active']): ?>
                                            <span class="badge bg-success">활성</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">비활성</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <?php if (!$project['path_exists']): ?>
                                            <span class="text-muted">-</span>
                                        <?php else: ?>
                                            <span class="badge bg-info rounded-pill px-3"><?= number_format($project['scan_count']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <?php if (!$project['path_exists']): ?>
                                            <span class="text-muted">-</span>
                                        <?php elseif ($project['last_scan']): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= Helpers::timeAgo($project['last_scan']) ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">없음</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <!-- 첫 번째 줄: 코드보기, 활성화 -->
                                            <div class="d-flex gap-1 justify-content-center mb-1">
                                                <!-- 코드 보기 버튼 -->
                                                <button class="btn btn-sm <?= $project['path_exists'] ? 'btn-primary' : 'btn-outline-secondary' ?>" 
                                                        data-action="open-vscode" 
                                                        data-project-path="<?= htmlspecialchars($project['root_path'] ?? $project['path'] ?? '') ?>"
                                                        data-project-id="<?= $project['id'] ?>"
                                                        <?= !$project['path_exists'] ? 'disabled' : '' ?>
                                                        title="<?= $project['path_exists'] ? 'VS Code로 프로젝트 열기' : '프로젝트 경로가 존재하지 않습니다' ?>">
                                                    <i class="fas fa-<?= $project['path_exists'] ? 'code' : 'exclamation-triangle' ?>"></i>
                                                </button>
                                                
                                                <!-- 활성화 토글 버튼 -->
                                                <form method="POST" action="/projects/<?= $project['id'] ?>/toggle" class="d-inline">
                                                    <?= Csrf::field() ?>
                                                    <button type="submit" 
                                                            class="btn btn-sm <?= $project['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?>" 
                                                            title="<?= $project['is_active'] ? '프로젝트 비활성화' : '프로젝트 활성화' ?>">
                                                        <i class="fas fa-<?= $project['is_active'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <!-- 두 번째 줄: 수정, 통계 -->
                                            <div class="d-flex gap-1 justify-content-center">
                                                <!-- 수정 버튼 -->
                                                <a href="/projects/<?= $project['id'] ?>/edit" 
                                                   class="btn btn-sm btn-outline-warning" 
                                                   title="프로젝트 정보 수정">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <!-- 통계 버튼 -->
                                                <button class="btn btn-sm <?= $project['path_exists'] ? 'btn-outline-info' : 'btn-outline-secondary' ?>" 
                                                        data-action="show-stats" 
                                                        data-project-id="<?= $project['id'] ?>"
                                                        <?= !$project['path_exists'] ? 'disabled' : '' ?>
                                                        title="<?= $project['path_exists'] ? '언어 사용량 통계 보기' : '프로젝트 경로가 존재하지 않아 통계를 볼 수 없습니다' ?>">
                                                    <i class="fas fa-chart-bar"></i>
                                                </button>
                                            </div>
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
                        <nav aria-label="프로젝트 페이지네이션">
                            <ul class="pagination mb-0">
                                <!-- 첫 페이지 -->
                                <?php if ($pagination['page'] > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= buildPaginationUrl(1, $pagination) ?>" title="첫 페이지">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= buildPaginationUrl($pagination['page'] - 1, $pagination) ?>" title="이전 페이지">
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
                                
                                // 시작이 1이 아니면 ... 표시
                                if ($start > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= buildPaginationUrl(1, $pagination) ?>">1</a>
                                    </li>
                                    <?php if ($start > 2): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- 현재 페이지 주변 번호들 -->
                                <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?= $i == $pagination['page'] ? 'active' : '' ?>">
                                        <?php if ($i == $pagination['page']): ?>
                                            <span class="page-link"><?= $i ?></span>
                                        <?php else: ?>
                                            <a class="page-link" href="<?= buildPaginationUrl($i, $pagination) ?>"><?= $i ?></a>
                                        <?php endif; ?>
                                    </li>
                                <?php endfor; ?>

                                <!-- 끝이 마지막 페이지가 아니면 ... 표시 -->
                                <?php if ($end < $pagination['totalPages']): ?>
                                    <?php if ($end < $pagination['totalPages'] - 1): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= buildPaginationUrl($pagination['totalPages'], $pagination) ?>"><?= $pagination['totalPages'] ?></a>
                                    </li>
                                <?php endif; ?>

                                <!-- 다음 페이지 -->
                                <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= buildPaginationUrl($pagination['page'] + 1, $pagination) ?>" title="다음 페이지">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= buildPaginationUrl($pagination['totalPages'], $pagination) ?>" title="마지막 페이지">
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
    </div>
</div>

<?php
// 페이징 URL 생성 함수
function buildPaginationUrl($page, $pagination) {
    $params = [];
    if (isset($_GET['search']) && $_GET['search'] !== '') {
        $params['search'] = $_GET['search'];
    }
    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $params['status'] = $_GET['status'];
    }
    $params['page'] = $page;
    $params['limit'] = $pagination['limit'];
    
    return '/projects?' . http_build_query($params);
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const clearSearch = document.getElementById('clearSearch');
    const refreshProjects = document.getElementById('refreshProjects');
    const projectCount = document.getElementById('projectCount');
    const tableRows = document.querySelectorAll('.table tbody tr');
    
    // 검색 기능
    function filterProjects() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;
        let visibleCount = 0;
        
        tableRows.forEach(row => {
            const projectName = row.querySelector('td:first-child strong').textContent.toLowerCase();
            const projectPath = row.querySelector('td:nth-child(2) code').textContent.toLowerCase();
            const projectStatus = row.querySelector('.badge-success, .badge-secondary');
            const isActive = projectStatus && projectStatus.classList.contains('badge-success');
            
            const matchesSearch = projectName.includes(searchTerm) || projectPath.includes(searchTerm);
            const matchesStatus = !statusValue || 
                (statusValue === 'active' && isActive) || 
                (statusValue === 'inactive' && !isActive);
            
            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        projectCount.textContent = `${visibleCount}개 프로젝트`;
        
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
                        <p class="text-muted mb-0">검색 조건에 맞는 프로젝트가 없습니다.</p>
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
    searchInput.addEventListener('input', filterProjects);
    statusFilter.addEventListener('change', filterProjects);
    
    // 검색 초기화
    clearSearch.addEventListener('click', function() {
        searchInput.value = '';
        statusFilter.value = '';
        filterProjects();
        searchInput.focus();
    });
    
    // 새로고침
    refreshProjects.addEventListener('click', function() {
        location.reload();
    });
    
    // 키보드 단축키 (Ctrl+F 또는 Cmd+F로 검색 포커스)
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            searchInput.focus();
        }
    });

    // 페이지 크기 변경 처리
    const limitSelect = document.getElementById('limitSelect');
    if (limitSelect) {
        limitSelect.addEventListener('change', function() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('limit', this.value);
            currentUrl.searchParams.set('page', '1'); // 첫 페이지로 이동
            window.location.href = currentUrl.toString();
        });
    }

    // 작업하기 버튼 이벤트 처리
    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-action="open-vscode"]')) {
            e.preventDefault();
            const button = e.target.closest('[data-action="open-vscode"]');
            const projectPath = button.getAttribute('data-project-path');
            const projectId = button.getAttribute('data-project-id');

            if (!projectPath) {
                showPathErrorModal('프로젝트 경로 정보가 없습니다.', null, projectId);
                return;
            }

            // 버튼 비활성화
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>경로 확인 중...';

            // 서버에 경로 존재 여부 확인
            fetch('/ajax/check-project-path', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ path: projectPath })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.exists) {
                    // 경로가 존재하면 VS Code 열기 시도
                    if (data.readable) {
                        openVSCode(projectPath);
                    } else {
                        showPathErrorModal('프로젝트 경로에 접근 권한이 없습니다.', projectPath, projectId);
                    }
                } else {
                    // 경로가 존재하지 않으면 모달 표시
                    showPathErrorModal(data.message || '프로젝트 경로가 존재하지 않습니다.', projectPath, projectId);
                }
            })
            .catch(error => {
                console.error('Path check error:', error);
                showPathErrorModal('경로 확인 중 오류가 발생했습니다.', null, projectId);
            })
            .finally(() => {
                // 버튼 복원
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-code me-1"></i>작업하기';
            });
        }
    });
});

// VS Code 열기 함수
function openVSCode(projectPath) {
    // 실제로는 VS Code를 열 수 없으므로 안내 메시지 표시
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-code me-2"></i>VS Code 열기
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        프로젝트 경로가 확인되었습니다!
                    </div>
                    <p><strong>프로젝트 경로:</strong></p>
                    <code class="d-block bg-light p-2 rounded">${projectPath}</code>
                    <p class="mt-3 mb-2"><strong>VS Code로 열기:</strong></p>
                    <div class="bg-dark text-light p-3 rounded">
                        <code>code "${projectPath}"</code>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        위 명령어를 터미널에 입력하여 VS Code로 프로젝트를 열 수 있습니다.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                    <button type="button" class="btn btn-primary" onclick="copyToClipboard('code &quot;${projectPath}&quot;')">
                        <i class="fas fa-copy me-1"></i>명령어 복사
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    modal.addEventListener('hidden.bs.modal', function () {
        document.body.removeChild(modal);
    });
}

// 경로 오류 모달 표시
function showPathErrorModal(message, projectPath = null, projectId = null) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'pathErrorModal';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>프로젝트 경로 오류
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle me-2"></i>
                        ${message}
                    </div>
                    ${projectPath ? `
                        <p><strong>확인된 경로:</strong></p>
                        <code class="d-block bg-light p-2 rounded text-danger">${projectPath}</code>
                    ` : ''}
                    <div class="mt-3">
                        <strong>해결 방법:</strong>
                        <ul class="mt-2">
                            <li>프로젝트 경로가 올바른지 확인하세요</li>
                            <li>프로젝트 편집에서 경로를 수정하세요</li>
                            <li>해당 디렉토리가 존재하는지 확인하세요</li>
                            <li>접근 권한을 확인하세요</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                    ${projectId ? `
                        <a href="/projects/${projectId}/edit" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i>프로젝트 편집
                        </a>
                    ` : ''}
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();

    modal.addEventListener('hidden.bs.modal', function () {
        document.body.removeChild(modal);
    });
}

// 클립보드 복사 함수
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // 성공 피드백
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check me-1"></i>복사됨!';
        button.classList.remove('btn-primary');
        button.classList.add('btn-success');

        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('btn-success');
            button.classList.add('btn-primary');
        }, 2000);
    });
}
</script>

<style>
.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.table-warning {
    background-color: rgba(255, 193, 7, 0.1);
    border-left: 3px solid #ffc107;
}
</style>