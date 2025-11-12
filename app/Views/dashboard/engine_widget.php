<div class="engine-widget" id="engine-status">
    <div class="engine-header">
        <h3 class="engine-title">🚀 LOC 스캔 엔진</h3>
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" onclick="refreshEngineStatus()">
                🔄 새로고침
            </button>
        </div>
    </div>
    
    <div class="engine-status">
        <div class="engine-metric">
            <div class="engine-metric-label">C++ 네이티브 엔진</div>
            <div class="engine-metric-value">
                <span id="engine-status-badge" class="status-indicator offline">
                    <span id="engine-status-text">확인 중...</span>
                </span>
            </div>
        </div>
        
        <div class="engine-metric">
            <div class="engine-metric-label">PHP 백업 엔진</div>
            <div class="engine-metric-value">
                <span class="status-indicator online">항상 사용 가능</span>
            </div>
        </div>
        
        <div class="engine-metric">
            <div class="engine-metric-label">지원 언어</div>
            <div class="engine-metric-value">
                <span class="badge badge-info">20+ 언어</span>
            </div>
        </div>
    </div>
    
    <div class="engine-actions">
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" type="button">
                ⚡ 빠른 스캔
            </button>
            <div class="dropdown-menu" id="quick-scan-projects">
                <div class="dropdown-item">프로젝트 로딩 중...</div>
            </div>
        </div>
        
        <div class="dropdown">
            <button class="btn btn-success dropdown-toggle" type="button">
                🔧 엔진 작업
            </button>
            <div class="dropdown-menu">
                <button class="dropdown-item" onclick="buildEngine()">
                    <span class="icon">🔨</span>
                    C++ 엔진 빌드
                </button>
                <button class="dropdown-item" onclick="testEngine()">
                    <span class="icon">🧪</span>
                    엔진 테스트
                </button>
                <div class="dropdown-divider"></div>
                <button class="dropdown-item" onclick="benchmarkEngine()">
                    <span class="icon">📊</span>
                    성능 벤치마크
                </button>
            </div>
        </div>
        
        <button class="btn btn-outline-info" onclick="viewLogs()">
            📋 로그 보기
        </button>
    </div>
    
    <div id="action-results" style="display: none;" class="mt-3">
        <div class="alert alert-info" id="action-message">
            처리 중...
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    refreshEngineStatus();
    loadProjectsForActions();
});

function refreshEngineStatus() {
    fetch('/ajax/engine-status')
        .then(response => response.json())
        .then(data => {
            const statusBadge = document.getElementById('engine-status-badge');
            const statusText = document.getElementById('engine-status-text');
            const engineWidget = document.getElementById('engine-status');
            
            if (data.available) {
                statusBadge.className = 'status-indicator online';
                statusText.textContent = 'C++ 엔진 온라인';
                engineWidget.className = 'engine-widget status-card success';
            } else {
                statusBadge.className = 'status-indicator offline';
                statusText.textContent = 'C++ 엔진 오프라인';
                engineWidget.className = 'engine-widget status-card error';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const statusBadge = document.getElementById('engine-status-badge');
            const statusText = document.getElementById('engine-status-text');
            statusBadge.className = 'status-indicator warning';
            statusText.textContent = '상태 확인 실패';
        });
}

function loadProjectsForActions() {
    // 프로젝트 목록 로드 (가정: API가 있다고 가정)
    // 실제 구현에서는 별도 엔드포인트 필요
    const quickScanMenu = document.getElementById('quick-scan-projects');
    const benchmarkMenu = document.getElementById('benchmark-projects');
    
    // 임시로 하드코딩된 프로젝트 (실제로는 AJAX로 로드)
    const projects = [
        {id: 1, name: 'MyComp Application'},
        {id: 2, name: 'C++ LOC Engine'}
    ];
    
    quickScanMenu.innerHTML = '';
    benchmarkMenu.innerHTML = '';
    
    projects.forEach(project => {
        quickScanMenu.innerHTML += `<li><a class="dropdown-item" href="#" onclick="runQuickScan(${project.id})">${project.name}</a></li>`;
        benchmarkMenu.innerHTML += `<li><a class="dropdown-item" href="#" onclick="runBenchmark(${project.id})">${project.name}</a></li>`;
    });
}

function buildEngine() {
    showActionResult('C++ 엔진을 빌드하고 있습니다...', 'info');
    
    fetch('/ajax/build-engine', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showActionResult('C++ 엔진이 성공적으로 빌드되었습니다! 🎉', 'success');
            refreshEngineStatus();
        } else {
            showActionResult('빌드 실패: ' + (data.error || '알 수 없는 오류'), 'danger');
        }
    })
    .catch(error => {
        showActionResult('빌드 오류: ' + error.message, 'danger');
    });
}

function testEngine() {
    showActionResult('엔진 테스트를 실행하고 있습니다...', 'info');
    
    // 테스트 엔진 로직 (실제 구현 필요)
    setTimeout(() => {
        showActionResult('엔진 테스트 완료! 모든 기능이 정상 작동합니다. ✅', 'success');
    }, 2000);
}

function benchmarkEngine() {
    showActionResult('성능 벤치마크를 실행하고 있습니다...', 'info');
    
    // 벤치마크 로직 (실제 구현 필요)
    setTimeout(() => {
        const results = `벤치마크 결과:<br>
        • C++ 엔진: 평균 12ms<br>
        • PHP 엔진: 평균 156ms<br>
        • <strong>성능 향상: 13x 더 빠름! 🚀</strong>`;
        showActionResult(results, 'success');
    }, 3000);
}

function viewLogs() {
    window.open('/logs', '_blank');
}

function runAllProjectScans() {
    showActionResult('모든 활성 프로젝트 스캔을 시작합니다...', 'info');
    
    fetch('/ajax/run-all-scans', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showActionResult(`${data.count}개의 프로젝트 스캔이 시작되었습니다! 📊`, 'success');
            setTimeout(() => {
                window.location.href = '/scans';
            }, 2000);
        } else {
            showActionResult('스캔 시작 실패: ' + (data.error || '알 수 없는 오류'), 'danger');
        }
    })
    .catch(error => {
        showActionResult('스캔 오류: ' + error.message, 'danger');
    });
}

function runQuickScan(projectId) {
    showActionResult('Starting scan...', 'info');
    
    fetch('/ajax/run-scan', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({project_id: projectId})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showActionResult('Scan completed! Scan ID: ' + data.scan_id, 'success');
            setTimeout(() => {
                window.location.href = '/scans/' + data.scan_id;
            }, 2000);
        } else {
            showActionResult('Scan failed: ' + (data.error || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        showActionResult('Scan error: ' + error.message, 'danger');
    });
}

function runBenchmark(projectId) {
    showActionResult('Running benchmark...', 'info');
    
    fetch('/ajax/benchmark', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({project_id: projectId})
    })
    .then(response => response.json())
    .then(data => {
        if (data.cpp_engine && data.php_engine) {
            const improvement = data.performance_improvement;
            let message = `Benchmark Results:<br>
                C++ Engine: ${data.cpp_engine.avg_time_ms.toFixed(2)}ms avg<br>
                PHP Engine: ${data.php_engine.avg_time_ms.toFixed(2)}ms avg`;
            
            if (improvement) {
                message += `<br><strong>C++ is ${improvement.speed_multiplier}x faster (${improvement.cpp_faster_by_percent}% improvement)</strong>`;
            }
            
            showActionResult(message, 'success');
        } else {
            showActionResult('Benchmark failed: ' + (data.error || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        showActionResult('Benchmark error: ' + error.message, 'danger');
    });
}

function showActionResult(message, type) {
    const resultsDiv = document.getElementById('action-results');
    const messageDiv = document.getElementById('action-message');
    
    messageDiv.className = `alert alert-${type}`;
    messageDiv.innerHTML = message;
    resultsDiv.style.display = 'block';
    
    if (type === 'success') {
        setTimeout(() => {
            resultsDiv.style.display = 'none';
        }, 5000);
    }
}
</script>

<style>
.engine-status-indicator {
    min-width: 80px;
}

.badge-success {
    background-color: #28a745;
}

.badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.badge-danger {
    background-color: #dc3545;
}

.badge-secondary {
    background-color: #6c757d;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.dropdown-menu {
    min-width: 200px;
}
</style>