<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>스캔 상세 정보</h2>
    <div>
        <a href="/scans" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 목록으로
        </a>
        <a href="/scans/create" class="btn btn-primary">
            <i class="fas fa-play"></i> 새 스캔
        </a>
    </div>
</div>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if ($scan): ?>
<!-- 스캔 기본 정보 -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle"></i> 스캔 정보
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">프로젝트</h6>
                        <p class="mb-3"><?= htmlspecialchars($scan['project_name'] ?? '알 수 없음') ?></p>
                        
                        <h6 class="text-muted">스캔 경로</h6>
                        <p class="mb-3">
                            <code><?= htmlspecialchars($scan['project_path'] ?? '알 수 없음') ?></code>
                        </p>
                        
                        <h6 class="text-muted">스캔 엔진</h6>
                        <p class="mb-3">
                            <span class="badge bg-info"><?= htmlspecialchars($scan['engine_type'] ?? 'Unknown') ?></span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">스캔 시작</h6>
                        <p class="mb-3"><?= htmlspecialchars($scan['start_time']) ?></p>
                        
                        <h6 class="text-muted">스캔 완료</h6>
                        <p class="mb-3"><?= htmlspecialchars($scan['end_time'] ?? '진행 중') ?></p>
                        
                        <h6 class="text-muted">상태</h6>
                        <p class="mb-3">
                            <?php if ($scan['status'] === 'completed'): ?>
                                <span class="badge bg-success">완료</span>
                            <?php elseif ($scan['status'] === 'running'): ?>
                                <span class="badge bg-warning">실행 중</span>
                            <?php elseif ($scan['status'] === 'failed'): ?>
                                <span class="badge bg-danger">실패</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($scan['status']) ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar"></i> 스캔 결과 요약
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h3 class="text-primary"><?= number_format($scan['total_files']) ?></h3>
                    <small class="text-muted">총 파일 수</small>
                </div>
                
                <div class="text-center mb-3">
                    <h3 class="text-success"><?= number_format($scan['total_loc']) ?></h3>
                    <small class="text-muted">총 코드 라인</small>
                </div>
                
                <?php if (isset($scan['execution_time'])): ?>
                <div class="text-center">
                    <h6 class="text-info"><?= htmlspecialchars($scan['execution_time']) ?></h6>
                    <small class="text-muted">실행 시간</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($language_stats)): ?>
<!-- 언어별 통계 -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-code"></i> 언어별 통계
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-8">
                <!-- 언어별 테이블 -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>언어</th>
                                <th class="text-end">파일 수</th>
                                <th class="text-end">코드 라인</th>
                                <th class="text-end">주석 라인</th>
                                <th class="text-end">빈 라인</th>
                                <th class="text-end">비율</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($language_stats as $stat): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($stat['language']) ?></strong>
                                </td>
                                <td class="text-end"><?= number_format($stat['file_count']) ?></td>
                                <td class="text-end">
                                    <strong><?= number_format($stat['loc']) ?></strong>
                                </td>
                                <td class="text-end"><?= number_format($stat['comment_lines']) ?></td>
                                <td class="text-end"><?= number_format($stat['blank_lines']) ?></td>
                                <td class="text-end">
                                    <span class="badge bg-primary"><?= number_format($stat['loc_percentage'], 1) ?>%</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-4">
                <!-- 언어 분포 차트 -->
                <canvas id="languageChart" width="400" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// 언어별 분포 차트
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('languageChart').getContext('2d');
    
    const languages = <?= json_encode(array_column($language_stats, 'language')) ?>;
    const locs = <?= json_encode(array_column($language_stats, 'loc')) ?>;
    
    const colors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
    ];
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: languages,
            datasets: [{
                data: locs,
                backgroundColor: colors.slice(0, languages.length),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
        <h4>스캔을 찾을 수 없습니다</h4>
        <p class="text-muted">요청하신 스캔 결과를 찾을 수 없습니다.</p>
        <a href="/scans" class="btn btn-primary">스캔 목록으로 돌아가기</a>
    </div>
</div>
<?php endif; ?>