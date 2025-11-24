/**
 * 사설망 개발 관리 프로그램 JavaScript
 */

class DevManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initCharts();
        this.setupAjax();
    }

    setupEventListeners() {
        // 드롭다운 토글
        this.setupDropdowns();

        // 폼 제출 확인
        document.querySelectorAll('form[data-confirm]').forEach(form => {
            form.addEventListener('submit', (e) => {
                const message = form.getAttribute('data-confirm');
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });

        // 삭제 버튼 확인
        document.querySelectorAll('[data-action="delete"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (!confirm('정말로 삭제하시겠습니까?')) {
                    e.preventDefault();
                }
            });
        });

        // 스캔 버튼
        document.querySelectorAll('[data-action="scan"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const projectId = btn.getAttribute('data-project-id');
                this.runScan(projectId);
            });
        });

        // VS Code 열기 버튼
        document.querySelectorAll('[data-action="open-vscode"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const projectId = btn.getAttribute('data-project-id');
                const projectPath = btn.getAttribute('data-project-path');
                this.openVsCode(projectId, projectPath);
            });
        });

        // 통계 보기 버튼
        document.querySelectorAll('[data-action="show-stats"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const projectId = btn.getAttribute('data-project-id');
                if (projectId) {
                    this.showProjectStats(projectId);
                } else {
                    console.error('Project ID not found');
                }
            });
        });

        // Finder 열기 버튼
        document.querySelectorAll('[data-action="explorer"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const projectPath = btn.getAttribute('data-project-path');
                this.openExplorer(projectPath);
            });
        });

        // 자동 새로고침 토글
        const autoRefreshToggle = document.getElementById('autoRefresh');
        if (autoRefreshToggle) {
            autoRefreshToggle.addEventListener('change', (e) => {
                if (e.target.checked) {
                    this.startAutoRefresh();
                } else {
                    this.stopAutoRefresh();
                }
            });
        }

        // 엔진 상태 업데이트
        this.updateEngineStatus();
        setInterval(() => this.updateEngineStatus(), 10000); // 10초마다 업데이트
    }

    setupDropdowns() {
        // 드롭다운 토글 버튼
        document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const dropdown = toggle.closest('.dropdown');
                const dropdownMenu = dropdown.querySelector('.dropdown-menu');
                const isOpen = dropdown.classList.contains('show');
                
                // 모든 드롭다운 닫기
                document.querySelectorAll('.dropdown.show').forEach(d => {
                    d.classList.remove('show');
                    const menu = d.querySelector('.dropdown-menu');
                    if (menu) {
                        menu.classList.remove('show');
                    }
                });
                
                // 현재 드롭다운 토글
                if (!isOpen) {
                    dropdown.classList.add('show');
                    if (dropdownMenu) {
                        dropdownMenu.classList.add('show');
                        dropdownMenu.style.display = 'block';
                        dropdownMenu.style.opacity = '1';
                        dropdownMenu.style.transform = 'translateY(0) scale(1)';
                    }
                }
            });
        });

        // 드롭다운 아이템 클릭
        document.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const dropdown = item.closest('.dropdown');
                if (dropdown) {
                    dropdown.classList.remove('show');
                }
            });
        });

        // 바깥 영역 클릭 시 드롭다운 닫기
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown.show').forEach(dropdown => {
                    dropdown.classList.remove('show');
                    const menu = dropdown.querySelector('.dropdown-menu');
                    if (menu) {
                        menu.classList.remove('show');
                        menu.style.display = '';
                        menu.style.opacity = '';
                        menu.style.transform = '';
                    }
                });
            }
        });

        // ESC 키로 드롭다운 닫기
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.dropdown.show').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });
    }

    updateEngineStatus() {
        fetch('/ajax/engine-status')
        .then(response => response.json())
        .then(data => {
            const statusElement = document.getElementById('engine-status');
            const statusBadge = document.getElementById('engine-status-badge');
            const statusText = document.getElementById('engine-status-text');
            
            if (statusElement && statusBadge && statusText) {
                if (data.available) {
                    statusBadge.className = 'status-indicator online';
                    statusText.textContent = 'C++ Engine Online';
                    statusElement.className = 'engine-widget status-card success';
                } else {
                    statusBadge.className = 'status-indicator offline';
                    statusText.textContent = 'C++ Engine Offline';
                    statusElement.className = 'engine-widget status-card error';
                }
            }
        })
        .catch(error => {
            console.error('Engine status check error:', error);
        });
    }

    initCharts() {
        // Chart.js가 로드된 경우에만 실행
        if (typeof Chart === 'undefined') return;

        // 언어별 LOC 파이 차트
        const languageChartCtx = document.getElementById('languageChart');
        if (languageChartCtx && window.languageStats) {
            new Chart(languageChartCtx, {
                type: 'pie',
                data: {
                    labels: window.languageStats.map(item => item.language),
                    datasets: [{
                        data: window.languageStats.map(item => item.total_loc),
                        backgroundColor: [
                            '#667eea', '#764ba2', '#f093fb', '#f5576c',
                            '#4facfe', '#00f2fe', '#43e97b', '#38f9d7',
                            '#ffecd2', '#fcb69f', '#a8edea', '#fed6e3'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${context.label}: ${value.toLocaleString()} LOC (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // LOC 트렌드 라인 차트
        const trendChartCtx = document.getElementById('trendChart');
        if (trendChartCtx && window.locTrends) {
            const dates = window.locTrends.map(item => {
                return new Date(item.started_at).toLocaleDateString('ko-KR');
            });
            const values = window.locTrends.map(item => item.total_loc);

            new Chart(trendChartCtx, {
                type: 'line',
                data: {
                    labels: dates.reverse(),
                    datasets: [{
                        label: 'Total LOC',
                        data: values.reverse(),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    setupAjax() {
        // CSRF 토큰을 자동으로 포함
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            this.csrfToken = csrfToken.getAttribute('content');
        }
    }

    runScan(projectId) {
        if (!projectId) {
            this.showAlert('프로젝트 ID가 필요합니다.', 'danger');
            return;
        }

        const btn = document.querySelector(`[data-project-id="${projectId}"]`);
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> 스캔 중...';
        }

        fetch('/ajax/run-scan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                project_id: projectId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showAlert('스캔이 시작되었습니다.', 'success');
                // 스캔 결과 페이지로 리다이렉트
                window.location.href = '/scans/' + data.scan_id;
            } else {
                this.showAlert(data.error || '스캔 실행 중 오류가 발생했습니다.', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showAlert('네트워크 오류가 발생했습니다.', 'danger');
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '스캔';
            }
        });
    }

    showAlert(message, type = 'info') {
        // 기존 알림 제거
        document.querySelectorAll('.alert.fade-in').forEach(alert => {
            alert.remove();
        });

        // 새 알림 생성
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} fade-in`;
        alert.innerHTML = message;
        alert.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease-out;
        `;

        document.body.appendChild(alert);

        // 3초 후 자동 제거
        setTimeout(() => {
            alert.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        }, 3000);
    }

    startAutoRefresh() {
        if (this.refreshInterval) return;
        
        this.refreshInterval = setInterval(() => {
            // 현재 페이지가 스캔 상세 페이지인 경우만 새로고침
            if (window.location.pathname.includes('/scans/')) {
                const scanId = window.location.pathname.split('/').pop();
                this.checkScanStatus(scanId);
            }
        }, 5000); // 5초마다 체크
    }

    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    checkScanStatus(scanId) {
        fetch(`/ajax/scan-status/${scanId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' || data.status === 'failed') {
                // 완료된 경우 페이지 새로고침
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Status check error:', error);
        });
    }

    // 유틸리티 메서드들
    formatNumber(num) {
        return num.toLocaleString('ko-KR');
    }

    formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('ko-KR') + ' ' + date.toLocaleTimeString('ko-KR');
    }

    // 프로젝트 관련 메소드들
    openVsCode(projectId, projectPath) {
        this.showAlert('VS Code를 실행 중입니다...', 'info', 2000);
        
        fetch(`/ajax/projects/${projectId}/open-vscode`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showAlert(`VS Code가 성공적으로 열렸습니다: ${projectPath}`, 'success');
            } else {
                this.showAlert(`VS Code 실행 실패: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            console.error('VS Code 실행 오류:', error);
            this.showAlert('VS Code 실행 중 오류가 발생했습니다.', 'error');
        });
    }

    openExplorer(projectPath) {
        this.showAlert('Finder를 여는 중입니다...', 'info', 2000);
        
        fetch('/ajax/projects/open-explorer', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ project_path: projectPath })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showAlert('success', 'Finder가 성공적으로 열렸습니다.');
            } else {
                this.showAlert('error', `Finder 실행 실패: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Finder 실행 오류:', error);
            this.showAlert('error', 'Finder 실행 중 오류가 발생했습니다.');
        });
    }

    showProjectStats(projectId) {
        // 모달 생성
        const modal = this.createStatsModal();
        document.body.appendChild(modal);
        
        // Bootstrap 모달 인스턴스 생성
        const bsModal = new bootstrap.Modal(modal, {
            backdrop: true,      // 어두운 배경 표시
            keyboard: true,      // ESC 키로 닫기 가능
            focus: true         // 포커스 자동 설정
        });
        
        // 모달 표시
        bsModal.show();
        
        // 실시간 스캔 시작
        this.performRealTimeScan(projectId, modal);
        
        // 모달 인스턴스 저장
        this.currentModal = bsModal;
        
        // 모달이 완전히 숨겨진 후 DOM에서 제거
        modal.addEventListener('hidden.bs.modal', function () {
            modal.remove();
        });
    }

    performRealTimeScan(projectId, modal) {
        const modalBody = modal.querySelector('.modal-body');
        modalBody.innerHTML = `
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">로딩 중...</span>
                </div>
                <p class="mt-2">통계 데이터를 불러오는 중...</p>
            </div>
        `;
        
        // Bootstrap이 모달 표시를 담당
        
        // C++ 엔진으로 실시간 스캔 실행
        modalBody.innerHTML = `
            <div class="text-center mb-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">스캔 중...</span>
                </div>
                <p class="mt-2"><strong>실시간 분석 중...</strong></p>
                <small class="text-muted">C++ 엔진을 사용하여 프로젝트를 스캔하고 있습니다.</small>
            </div>
        `;

        // 실시간 C++ LOC 스캔 실행
        fetch(`/ajax/projects/${projectId}/cpp-loc-scan`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 실시간 데이터로 모달 업데이트
                this.updateStatsModalWithLOC(modalBody, data);
            } else {
                modalBody.innerHTML = `
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> 스캔 실행 실패</h5>
                        <p>${data.message}</p>
                        ${data.need_build ? '<p class="mb-0"><small>C++ 엔진을 먼저 빌드해주세요.</small></p>' : ''}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('C++ LOC 스캔 오류:', error);
            modalBody.innerHTML = '<div class="alert alert-danger">통계 데이터 로드 중 오류가 발생했습니다.</div>';
        });
    }

    createStatsModal() {
        // 기존 모달 제거
        const existingModal = document.querySelector('.project-stats-modal');
        if (existingModal) {
            existingModal.remove();
        }

        const modal = document.createElement('div');
        modal.className = 'modal fade project-stats-modal';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('aria-labelledby', 'projectStatsModalLabel');
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = `
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="projectStatsModalLabel">
                            <i class="fas fa-chart-bar me-2"></i>프로젝트 언어 통계
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- 내용이 여기에 로드됩니다 -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                    </div>
                </div>
            </div>
        `;

        // Bootstrap이 백드롭과 키보드 이벤트를 처리
        
        return modal;
    }

    renderProjectStats(container, data) {
        const { project, scan_info, language_stats, summary, is_realtime } = data;
        
        // 실시간 분석인지 완전한 스캔인지에 따라 다른 UI 표시
        const scanInfoHtml = is_realtime ? `
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>실시간 파일 분석</strong> - C++ 엔진으로 정확한 LOC 측정이 가능합니다.
                <div class="btn-group ms-2">
                    <button class="btn btn-sm btn-success" onclick="devManager.runCppLOCScan(${project.id})">
                        <i class="fas fa-code me-1"></i>C++ LOC 측정
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="devManager.runScan(${project.id})" data-bs-dismiss="modal">
                        <i class="fas fa-play me-1"></i>전체 스캔
                    </button>
                </div>
            </div>
        ` : '';

        const scanDateInfo = scan_info.scan_date ? 
            `마지막 스캔: ${this.formatDate(scan_info.scan_date)}<br>실행시간: ${scan_info.execution_time}` :
            `분석 시간: ${scan_info.execution_time}`;

        container.innerHTML = `
            ${scanInfoHtml}
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6>📁 ${project.name}</h6>
                    <small class="text-muted">${project.path}</small>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        ${scanDateInfo}
                    </small>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-4 text-center">
                    <h4 class="text-primary">${this.formatNumber(summary.total_files)}</h4>
                    <small>총 파일 수</small>
                </div>
                <div class="col-md-4 text-center">
                    <h4 class="text-success">${summary.total_loc === 'Run full scan for LOC' ? 'N/A' : this.formatNumber(summary.total_loc)}</h4>
                    <small>총 코드 라인</small>
                </div>
                <div class="col-md-4 text-center">
                    <h4 class="text-info">${summary.languages_count}</h4>
                    <small>사용 언어 수</small>
                </div>
            </div>

            <!-- 언어별 파이 차트 -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="mb-3">📊 언어 분포 (파일 기준)</h6>
                    <div class="chart-container" style="height: 300px; position: relative;">
                        <canvas id="languageFilesChart"></canvas>
                    </div>
                </div>
                ${!is_realtime ? `
                <div class="col-md-6">
                    <h6 class="mb-3">📈 언어 분포 (LOC 기준)</h6>
                    <div class="chart-container" style="height: 300px; position: relative;">
                        <canvas id="languageLinesChart"></canvas>
                    </div>
                </div>
                ` : `
                <div class="col-md-6 d-flex align-items-center justify-content-center">
                    <div class="text-center text-muted">
                        <i class="fas fa-chart-pie fa-3x mb-2"></i>
                        <p>LOC 차트는 전체 스캔 후<br>이용할 수 있습니다</p>
                    </div>
                </div>
                `}
            </div>

            <h6 class="mb-3">언어별 상세 통계</h6>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>언어</th>
                            <th>파일 수</th>
                            <th>코드 라인</th>
                            <th>주석</th>
                            <th>빈 줄</th>
                            <th>파일 비율</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${language_stats.map((lang, index) => {
                            const colors = ['#4F46E5', '#059669', '#DC2626', '#D97706', '#0284C7', '#7C3AED', '#EC4899', '#059669'];
                            const color = colors[index % colors.length];
                            return `
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="language-color" style="width: 12px; height: 12px; border-radius: 50%; background-color: ${color}; margin-right: 8px;"></div>
                                        <strong>${lang.language}</strong>
                                    </div>
                                </td>
                                <td>${this.formatNumber(lang.file_count)}</td>
                                <td class="text-success">${lang.loc === 'N/A' ? 'N/A' : this.formatNumber(lang.loc)}</td>
                                <td class="text-muted">${lang.comment_lines === 'N/A' ? 'N/A' : this.formatNumber(lang.comment_lines)}</td>
                                <td class="text-muted">${lang.blank_lines === 'N/A' ? 'N/A' : this.formatNumber(lang.blank_lines)}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                            <div class="progress-bar" 
                                                 style="width: ${lang.file_percentage}%; background-color: ${color};"
                                                 title="${lang.file_percentage}%"></div>
                                        </div>
                                        <small class="text-nowrap">${lang.file_percentage}%</small>
                                    </div>
                                </td>
                            </tr>
                        `;}).join('')}
                    </tbody>
                </table>
            </div>
        `;

        // 차트 렌더링
        setTimeout(() => {
            this.renderLanguageCharts(language_stats, is_realtime);
        }, 100);
    }

    renderLanguageCharts(languageStats, isRealtime) {
        const colors = ['#4F46E5', '#059669', '#DC2626', '#D97706', '#0284C7', '#7C3AED', '#EC4899', '#059669'];
        
        // 파일 수 기준 차트
        const filesCtx = document.getElementById('languageFilesChart');
        if (filesCtx) {
            new Chart(filesCtx, {
                type: 'doughnut',
                data: {
                    labels: languageStats.map(lang => lang.language),
                    datasets: [{
                        data: languageStats.map(lang => lang.file_count),
                        backgroundColor: colors.slice(0, languageStats.length),
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const percentage = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percent = ((percentage / total) * 100).toFixed(1);
                                    return `${context.label}: ${percentage} 파일 (${percent}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // LOC 기준 차트 (스캔 완료된 경우만)
        if (!isRealtime) {
            const linesCtx = document.getElementById('languageLinesChart');
            if (linesCtx) {
                new Chart(linesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: languageStats.map(lang => lang.language),
                        datasets: [{
                            data: languageStats.map(lang => lang.loc),
                            backgroundColor: colors.slice(0, languageStats.length),
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const percentage = context.parsed;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percent = ((percentage / total) * 100).toFixed(1);
                                        return `${context.label}: ${percentage.toLocaleString()} LOC (${percent}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
    }

    closeModal() {
        if (this.currentModal) {
            this.currentModal.hide();
            this.currentModal = null;
        } else {
            // 폴백: 모든 열린 모달 찾아서 닫기
            const modals = document.querySelectorAll('.modal.show');
            modals.forEach(modal => {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            });
        }
    }



    // C++ 엔진을 통한 실시간 LOC 측정
    runCppLOCScan(projectId) {
        this.showAlert('C++ 엔진으로 LOC를 측정하는 중...', 'info');
        
        fetch(`/ajax/projects/${projectId}/cpp-loc-scan`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 모달 업데이트
                this.updateStatsModalWithLOC(data.language_stats, data.summary);
                this.showAlert(`C++ LOC 측정 완료! 총 ${data.summary.total_loc.toLocaleString()} 라인`, 'success');
            } else {
                this.showAlert(`C++ LOC 측정 실패: ${data.error || '알 수 없는 오류'}`, 'danger');
            }
        })
        .catch(error => {
            console.error('C++ LOC 스캔 오류:', error);
            this.showAlert('C++ LOC 측정 중 오류가 발생했습니다.', 'danger');
        });
    }

    // 실시간 LOC 데이터로 모달 완전히 새로 렌더링
    updateStatsModalWithLOC(modalBody, data) {
        const stats = data.language_stats || [];
        const summary = data.summary || {};
        
        modalBody.innerHTML = `
            <div class="project-stats-content">
                <!-- 프로젝트 헤더 -->
                <div class="text-center mb-4">
                    <h4 class="text-primary mb-2">📊 프로젝트 언어 통계</h4>
                    <p class="text-muted mb-1">분석 시간: Real-time analysis</p>
                    <span class="badge bg-success px-3 py-2">${data.engine || 'C++'}</span>
                </div>

                <!-- 주요 통계 카드 -->
                <div class="row g-3 mb-4">
                    <div class="col-4">
                        <div class="stats-card bg-primary bg-opacity-10 p-3 rounded text-center">
                            <h3 class="text-primary mb-1">${(summary.total_files || 0).toLocaleString()}</h3>
                            <small class="text-muted fw-bold">총 파일 수</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stats-card bg-success bg-opacity-10 p-3 rounded text-center">
                            <h3 class="text-success mb-1">${(summary.total_loc || 0).toLocaleString()}</h3>
                            <small class="text-muted fw-bold">총 코드 라인</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stats-card bg-info bg-opacity-10 p-3 rounded text-center">
                            <h3 class="text-info mb-1">${summary.languages_count || 0}</h3>
                            <small class="text-muted fw-bold">사용 언어 수</small>
                        </div>
                    </div>
                </div>

                <!-- 차트 영역 -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container bg-light rounded p-3">
                            <h6 class="mb-3">📈 언어 분포 (파일 기준)</h6>
                            <div style="position: relative; height: 250px;">
                                <canvas id="languageChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container bg-light rounded p-3">
                            <h6 class="mb-3">📊 코드 라인 분포</h6>
                            <div style="position: relative; height: 250px;">
                                <canvas id="locChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 언어별 상세 통계 테이블 -->
                <div class="stats-table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">📋 언어별 상세 통계</h6>
                        <small class="text-muted">${stats.length}개 언어 감지됨</small>
                    </div>
                    
                    ${stats.length > 0 ? `
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="fw-bold">언어</th>
                                    <th class="text-end fw-bold">파일 수</th>
                                    <th class="text-end fw-bold">코드 라인</th>
                                    <th class="text-end fw-bold">주석</th>
                                    <th class="text-end fw-bold">빈 줄</th>
                                    <th class="text-end fw-bold">비율</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${stats.map((lang, index) => `
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="language-indicator bg-${this.getLanguageColor(index)} me-2"></div>
                                                <strong>${lang.language}</strong>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-light text-dark">${lang.file_count.toLocaleString()}</span>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-success">${lang.loc.toLocaleString()}</strong>
                                        </td>
                                        <td class="text-end">
                                            <span class="text-muted">${lang.comment_lines.toLocaleString()}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="text-muted">${lang.blank_lines.toLocaleString()}</span>
                                        </td>
                                        <td class="text-end">
                                            <div class="progress" style="width: 60px; height: 8px;">
                                                <div class="progress-bar bg-${this.getLanguageColor(index)}" 
                                                     style="width: ${lang.loc_percentage || 0}%"></div>
                                            </div>
                                            <small class="text-muted">${(lang.loc_percentage || 0).toFixed(1)}%</small>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    ` : `
                    <div class="text-center py-4">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <p class="text-muted">언어별 통계 데이터를 불러오는 중...</p>
                    </div>
                    `}
                </div>
                
            </div>
        `;

        // 차트 렌더링
        setTimeout(() => {
            this.renderLanguageChart(stats);
            this.renderLocChart(stats);
        }, 100);
    }

    // 언어별 색상 매핑
    getLanguageColor(index) {
        const colors = ['primary', 'success', 'warning', 'info', 'danger', 'secondary', 'dark'];
        return colors[index % colors.length];
    }

    // 언어 분포 차트 렌더링 (파일 기준)
    renderLanguageChart(languageStats) {
        const canvas = document.getElementById('languageChart');
        if (!canvas || !window.Chart) return;

        const ctx = canvas.getContext('2d');
        
        // 기존 차트 제거
        if (this.fileChart) {
            this.fileChart.destroy();
        }

        const colors = [
            '#6f42c1', '#20c997', '#fd7e14', '#e83e8c', 
            '#6610f2', '#0dcaf0', '#dc3545', '#ffc107'
        ];

        const labels = languageStats.map(lang => lang.language);
        const data = languageStats.map(lang => lang.file_count);

        this.fileChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 8,
                            font: { size: 10 },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed.toLocaleString()} 파일 (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // LOC 분포 차트 렌더링
    renderLocChart(languageStats) {
        const canvas = document.getElementById('locChart');
        if (!canvas || !window.Chart) return;

        const ctx = canvas.getContext('2d');
        
        // 기존 차트 제거
        if (this.locChart) {
            this.locChart.destroy();
        }

        const colors = [
            '#6f42c1', '#20c997', '#fd7e14', '#e83e8c', 
            '#6610f2', '#0dcaf0', '#dc3545', '#ffc107'
        ];

        const labels = languageStats.map(lang => lang.language);
        const locData = languageStats.map(lang => lang.loc);

        this.locChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: locData,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 8,
                            font: { size: 10 },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed.toLocaleString()} LOC (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
}

// CSS 애니메이션 추가
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .fade-in {
        animation: slideInRight 0.3s ease-out;
    }
`;
document.head.appendChild(style);

// DOM이 로드되면 초기화
document.addEventListener('DOMContentLoaded', function() {
    window.devManager = new DevManager();
    
    // 알림 메시지 자동 사라짐
    setTimeout(() => {
        document.querySelectorAll('.alert:not(.fade-in)').forEach(alert => {
            alert.style.transition = 'opacity 0.3s ease-out';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        });
    }, 5000);
});