<!-- Modern Dashboard Header -->
<div class="dashboard-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="dashboard-title mb-1">대시보드</h1>
            <p class="dashboard-subtitle text-muted mb-0">
                <i class="fas fa-calendar-alt me-1"></i>
                <?= date('Y년 m월 d일 H:i') ?> 기준
            </p>
        </div>
        <div class="dashboard-controls d-flex gap-2">
            <button onclick="refreshDashboard()" class="btn btn-outline-secondary btn-sm rounded-pill">
                <i class="fas fa-sync-alt me-1"></i>새로고침
            </button>
        </div>
    </div>
</div>

<!-- Main Dashboard Container -->
<div class="dashboard-container" id="dashboardGrid">
    
    <!-- Quick Actions Section (Top) -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="modern-card dashboard-widget" data-card="quick-actions">
                <div class="card-header-modern">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="action-icon me-3">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <div>
                                <h5 class="card-title-modern mb-0">빠른 작업</h5>
                                <small class="text-muted">자주 사용하는 기능들</small>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" id="toggleQuickActions">
                            <i class="fas fa-chevron-up" id="quickActionsIcon"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-3" id="quickActionsBody">
                    <div class="quick-actions-menu">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <a href="/projects/create" class="quick-menu-item">
                                    <i class="fas fa-plus text-primary"></i>
                                    <span>새 프로젝트</span>
                                    <i class="fas fa-chevron-right ms-auto"></i>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="/projects" class="quick-menu-item">
                                    <i class="fas fa-list text-info"></i>
                                    <span>프로젝트 관리</span>
                                    <i class="fas fa-chevron-right ms-auto"></i>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="/os/create" class="quick-menu-item">
                                    <i class="fas fa-desktop text-warning"></i>
                                    <span>OS 등록</span>
                                    <i class="fas fa-chevron-right ms-auto"></i>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="/agents" class="quick-menu-item">
                                    <i class="fas fa-robot text-success"></i>
                                    <span>에이전트</span>
                                    <i class="fas fa-chevron-right ms-auto"></i>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="/scans" class="quick-menu-item">
                                    <i class="fas fa-chart-line text-danger"></i>
                                    <span>스캔 결과</span>
                                    <i class="fas fa-chevron-right ms-auto"></i>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="/db" class="quick-menu-item">
                                    <i class="fas fa-database text-secondary"></i>
                                    <span>DB 관리</span>
                                    <i class="fas fa-chevron-right ms-auto"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Information Section -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="modern-card dashboard-widget" data-card="system-info">
                <div class="card-header-modern">
                    <div class="d-flex align-items-center">
                        <div class="system-icon me-3">
                            <i class="fas fa-server"></i>
                        </div>
                        <div>
                            <h5 class="card-title-modern mb-0">System Information</h5>
                            <small class="text-muted">서버 상태 및 환경 정보</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="system-info-grid">
                        <div class="system-info-item">
                            <div class="system-info-icon php">
                                <i class="fab fa-php"></i>
                            </div>
                            <div class="system-info-content">
                                <div class="system-info-label">PHP Version</div>
                                <div class="system-info-value"><?= PHP_VERSION ?></div>
                            </div>
                        </div>
                        
                        <div class="system-info-item">
                            <div class="system-info-icon database">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="system-info-content">
                                <div class="system-info-label">Database</div>
                                <div class="system-info-value">
                                    <?php
                                    try {
                                        $pdo = new PDO("mysql:host=localhost;dbname=azabellcode", "azabellcode", "password123");
                                        echo '<span class="text-success">MySQL (Connected)</span>';
                                    } catch (Exception $e) {
                                        echo '<span class="text-danger">MySQL (Error)</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="system-info-item">
                            <div class="system-info-icon server">
                                <i class="fas fa-server"></i>
                            </div>
                            <div class="system-info-content">
                                <div class="system-info-label">Server OS</div>
                                <div class="system-info-value"><?= php_uname('s') . ' ' . php_uname('r') ?></div>
                            </div>
                        </div>
                        
                        <div class="system-info-item">
                            <div class="system-info-icon memory">
                                <i class="fas fa-microchip"></i>
                            </div>
                            <div class="system-info-content">
                                <div class="system-info-label">Memory Limit</div>
                                <div class="system-info-value"><?= ini_get('memory_limit') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- KPI Cards Row -->
    <div class="row g-4 mb-5">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="modern-card dashboard-widget projects-card" data-card="projects">
                <div class="card-drag-handle" style="display: none;">
                    <i class="fas fa-grip-horizontal"></i>
                </div>
                <div class="card-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="card-content">
                    <div class="card-header-modern">
                        <h5 class="card-title-modern">프로젝트</h5>
                        <div class="dropdown">
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="/projects/create">
                                    <i class="fas fa-plus me-2"></i>새 프로젝트
                                </a>
                                <a class="dropdown-item" href="/projects">
                                    <i class="fas fa-list me-2"></i>프로젝트 관리
                                </a>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item" onclick="runAllProjectScans()">
                                    <i class="fas fa-sync me-2"></i>모든 프로젝트 스캔
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?= number_format($stats['total_projects']) ?></div>
                            <div class="stat-label">전체 프로젝트</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number text-success"><?= number_format($stats['active_projects']) ?></div>
                            <div class="stat-label">활성 프로젝트</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6 col-xl-4">
            <div class="modern-card dashboard-widget system-card" data-card="system">
                <div class="card-drag-handle" style="display: none;">
                    <i class="fas fa-grip-horizontal"></i>
                </div>
                <div class="card-icon">
                    <i class="fas fa-server"></i>
                </div>
                <div class="card-content">
                    <div class="card-header-modern">
                        <h5 class="card-title-modern">시스템</h5>
                        <div class="dropdown">
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="/os">
                                    <i class="fas fa-desktop me-2"></i>OS 목록
                                </a>
                                <a class="dropdown-item" href="/os/create">
                                    <i class="fas fa-plus me-2"></i>OS 등록
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="/agents">
                                    <i class="fas fa-robot me-2"></i>에이전트 관리
                                </a>
                                <a class="dropdown-item" href="/db">
                                    <i class="fas fa-database me-2"></i>DB 관리
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?= number_format($stats['total_os']) ?></div>
                            <div class="stat-label">OS 항목</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number text-info"><?= number_format($stats['total_agents']) ?></div>
                            <div class="stat-label">에이전트</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6 col-xl-4">
            <div class="modern-card dashboard-widget scans-card" data-card="scans">
                <div class="card-drag-handle" style="display: none;">
                    <i class="fas fa-grip-horizontal"></i>
                </div>
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-content">
                    <div class="card-header-modern">
                        <h5 class="card-title-modern">스캔 통계</h5>
                        <div class="dropdown">
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="/scans">
                                    <i class="fas fa-chart-line me-2"></i>스캔 히스토리
                                </a>
                                <a class="dropdown-item" href="/scans/create">
                                    <i class="fas fa-play me-2"></i>새 스캔 실행
                                </a>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item" onclick="runAllProjectScans()">
                                    <i class="fas fa-sync-alt me-2"></i>전체 프로젝트 스캔
                                </button>
                                <button class="dropdown-item" onclick="showEngineStatus()">
                                    <i class="fas fa-cogs me-2"></i>엔진 상태 확인
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?= number_format($stats['total_scans'] ?? 0) ?></div>
                            <div class="stat-label">총 스캔 수</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number text-warning"><?= number_format($stats['total_loc'] ?? 0) ?></div>
                            <div class="stat-label">총 LOC</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics Section -->
    <div class="row g-5 mb-5">
        <!-- Chart Column -->
        <div class="col-12 col-lg-8">


            <?php if (!empty($loc_trends)): ?>
            <div class="modern-card chart-card dashboard-widget" data-card="trend-chart">
                <div class="card-drag-handle" style="display: none;">
                    <i class="fas fa-grip-horizontal"></i>
                </div>
                <div class="card-header-modern">
                    <div class="d-flex align-items-center">
                        <div class="chart-icon me-3">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <h5 class="card-title-modern mb-0">LOC Trend</h5>
                            <small class="text-muted">최근 스캔 결과 추이</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Column -->
        <div class="col-12 col-lg-4">
            <!-- Recent Scans Card -->
            <?php if (!empty($recent_scans)): ?>
            <div class="modern-card dashboard-widget mb-4" data-card="recent-scans">
                <div class="card-drag-handle" style="display: none;">
                    <i class="fas fa-grip-horizontal"></i>
                </div>
                <div class="card-header-modern">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="activity-icon me-3">
                                <i class="fas fa-history"></i>
                            </div>
                            <div>
                                <h5 class="card-title-modern mb-0">Recent Scans</h5>
                                <small class="text-muted">최근 스캔 활동</small>
                            </div>
                        </div>
                        <a href="/scans" class="btn btn-outline-primary btn-sm rounded-pill">
                            <i class="fas fa-eye me-1"></i>전체 보기
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="activity-list">
                        <?php foreach ($recent_scans as $index => $scan): ?>
                        <div class="activity-item <?= $index === count($recent_scans) - 1 ? 'last-item' : '' ?>">
                            <div class="activity-indicator">
                                <?php if ($scan['status'] === 'success'): ?>
                                    <div class="status-dot success"></div>
                                <?php elseif ($scan['status'] === 'failed'): ?>
                                    <div class="status-dot danger"></div>
                                <?php elseif ($scan['status'] === 'running'): ?>
                                    <div class="status-dot info pulsing"></div>
                                <?php else: ?>
                                    <div class="status-dot secondary"></div>
                                <?php endif; ?>
                            </div>
                            <div class="activity-content">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="activity-title"><?= htmlspecialchars($scan['project_name'] ?? 'Unknown') ?></h6>
                                        <p class="activity-time">
                                            <i class="fas fa-clock me-1"></i><?= Helpers::timeAgo($scan['started_at']) ?>
                                        </p>
                                    </div>
                                    <div class="activity-meta">
                                        <?php if ($scan['status'] === 'success'): ?>
                                            <span class="badge badge-success">완료</span>
                                        <?php elseif ($scan['status'] === 'failed'): ?>
                                            <span class="badge badge-danger">실패</span>
                                        <?php elseif ($scan['status'] === 'running'): ?>
                                            <span class="badge badge-info">실행중</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary"><?= ucfirst($scan['status']) ?></span>
                                        <?php endif; ?>
                                        <?php if ($scan['total_loc']): ?>
                                            <small class="loc-count"><?= number_format($scan['total_loc']) ?> LOC</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="modern-card dashboard-widget mb-4" data-card="recent-scans">
                <div class="card-drag-handle" style="display: none;">
                    <i class="fas fa-grip-horizontal"></i>
                </div>
                <div class="card-header-modern">
                    <div class="d-flex align-items-center">
                        <div class="activity-icon me-3">
                            <i class="fas fa-history"></i>
                        </div>
                        <div>
                            <h5 class="card-title-modern mb-0">Recent Scans</h5>
                            <small class="text-muted">최근 스캔 활동</small>
                        </div>
                    </div>
                </div>
                <div class="card-body text-center py-5">
                    <div class="empty-state">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted mb-2">아직 스캔 결과가 없습니다</h6>
                        <p class="text-muted mb-4">프로젝트를 스캔하여 결과를 여기서 확인하세요.</p>
                        <a href="/scans/create" class="btn btn-primary rounded-pill">
                            <i class="fas fa-play me-1"></i>첫 번째 스캔 시작
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>


        </div>
    </div>
    

    
</div> <!-- End of dashboard-container -->

</div> <!-- End of dashboard-widgets container -->

<!-- Chart Data -->

<?php if (!empty($loc_trends)): ?>
<script>
window.locTrends = <?= json_encode($loc_trends) ?>;
</script>
<?php endif; ?>

<script>
// Dashboard 기능들
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard loaded');
    
    // 빠른 작업 토글 기능 초기화
    initQuickActionsToggle();
    
    console.log('Dashboard initialized');
});

// 빠른 작업 토글 기능
function initQuickActionsToggle() {
    const toggleBtn = document.getElementById('toggleQuickActions');
    const actionsBody = document.getElementById('quickActionsBody');
    const icon = document.getElementById('quickActionsIcon');
    
    console.log('Toggle elements:', {
        toggleBtn: !!toggleBtn,
        actionsBody: !!actionsBody,
        icon: !!icon
    });
    
    if (!toggleBtn || !actionsBody || !icon) {
        console.error('빠른 작업 토글 요소를 찾을 수 없습니다.');
        return;
    }
    
    // 초기 상태는 열린 상태
    let isExpanded = true;
    
    toggleBtn.addEventListener('click', function() {
        console.log('토글 버튼 클릭됨, 현재 상태:', isExpanded);
        isExpanded = !isExpanded;
        
        if (isExpanded) {
            // 펼치기
            console.log('빠른 작업 펼치기');
            actionsBody.classList.remove('d-none');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
            toggleBtn.title = '빠른 작업 숨기기';
        } else {
            // 접기
            console.log('빠른 작업 접기');
            actionsBody.classList.add('d-none');
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
            toggleBtn.title = '빠른 작업 보기';
        }
    });
}

// 간소화된 대시보드 기능





// 전체 프로젝트 스캔 실행
function runAllProjectScans() {
    if (!confirm('모든 활성 프로젝트에 대해 LOC 스캔을 실행하시겠습니까?\n\n이 작업은 시간이 걸릴 수 있습니다.')) {
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>스캔 중...';
    button.disabled = true;
    
    fetch('/ajax/run-all-scans', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`성공적으로 ${data.scanned_count}개 프로젝트 스캔을 시작했습니다.`);
            // 페이지 새로고침하여 업데이트된 통계 표시
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert('스캔 실행 중 오류가 발생했습니다: ' + (data.message || '알 수 없는 오류'));
        }
    })
    .catch(error => {
        console.error('스캔 실행 오류:', error);
        alert('스캔 실행 중 네트워크 오류가 발생했습니다.');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// 엔진 상태 확인
function showEngineStatus() {
    fetch('/ajax/engine-status')
    .then(response => response.json())
    .then(data => {
        let statusMessage = '🔧 C++ LOC 스캔 엔진 상태\n\n';
        
        if (data.cpp_engine) {
            statusMessage += '✅ C++ 엔진: 사용 가능\n';
            statusMessage += `📁 경로: ${data.cpp_engine.path}\n`;
            statusMessage += `⚡ 마지막 스캔: ${data.cpp_engine.last_used || '없음'}\n`;
        } else {
            statusMessage += '❌ C++ 엔진: 빌드 필요\n';
        }
        
        if (data.php_engine) {
            statusMessage += '\n✅ PHP 백업 엔진: 사용 가능';
        }
        
        alert(statusMessage);
    })
    .catch(error => {
        console.error('엔진 상태 확인 오류:', error);
        alert('엔진 상태를 확인할 수 없습니다.');
    });
}

// 통계 새로고침 (현대적인 방식)
function refreshDashboard() {
    const button = event.target;
    const originalHTML = button.innerHTML;
    
    // 로딩 상태 표시
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>새로고침 중...';
    button.disabled = true;
    
    // 현재 시간 업데이트
    const subtitleElement = document.querySelector('.dashboard-subtitle');
    if (subtitleElement) {
        const now = new Date();
        const koreanTime = now.toLocaleString('ko-KR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
        subtitleElement.innerHTML = `<i class="fas fa-calendar-alt me-1"></i>${koreanTime} 기준`;
    }
    
    // 실제 새로고침 (부드러운 전환 효과)
    setTimeout(() => {
        location.reload();
    }, 500);
}
</script>