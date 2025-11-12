<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>설치 필요 - myComp</title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .install-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            max-width: 600px;
            width: 90%;
            text-align: center;
        }
        .install-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1.5rem;
        }
        .install-title {
            color: #2c3e50;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .install-subtitle {
            color: #7f8c8d;
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        .command-box {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 1.5rem;
            border-radius: 10px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 1.1rem;
            margin: 2rem 0;
            position: relative;
        }
        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #3498db;
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .copy-btn:hover {
            background: #2980b9;
        }
        .step-list {
            text-align: left;
            margin: 2rem 0;
        }
        .step-item {
            display: flex;
            align-items: center;
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .step-number {
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 10px;
            margin: 1.5rem 0;
        }
        .refresh-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .refresh-btn:hover {
            background: #229954;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="install-card">
        <div class="install-icon">
            <i class="fas fa-cogs"></i>
        </div>
        
        <h1 class="install-title">필수 설치</h1>
        <p class="install-subtitle">개발 관리 시스템을 사용하기 위해 데이터베이스 설치가 필요합니다.</p>
        
        <?php if ($installStatus === 'no_env'): ?>
            <div class="warning-box" style="background: #ffebee; border-color: #ffcdd2; color: #c62828;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>현재 상태:</strong> .env 파일을 찾을 수 없습니다.<br>
                <small>.env 파일을 확인하여 데이터베이스 설정이 올바른지 확인하세요.</small>
            </div>
        <?php elseif ($installStatus === 'db_error'): ?>
            <div class="warning-box" style="background: #fff3e0; border-color: #ffcc02; color: #e65100;">
                <i class="fas fa-database"></i>
                <strong>현재 상태:</strong> 데이터베이스 연결에 실패했습니다.<br>
                <small>.env 파일의 데이터베이스 설정을 확인하고 MySQL 서비스가 실행 중인지 확인하세요.</small>
            </div>
        <?php elseif ($installStatus === 'needs_tables'): ?>
            <div class="warning-box">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>현재 상태:</strong> 데이터베이스 테이블이 설치되지 않았습니다.<br>
                <small>프로젝트, 스캔, OS 정보, 에이전트 관리를 위한 테이블들이 필요합니다.</small>
            </div>
        <?php elseif ($installStatus === 'complete'): ?>
            <div class="warning-box" style="background: #e8f5e8; border-color: #c8e6c9; color: #2e7d32;">
                <i class="fas fa-check-circle"></i>
                <strong>설치 완료:</strong> 모든 테이블이 설치되어 있습니다.<br>
                <small>잠시 후 대시보드로 자동 이동됩니다...</small>
            </div>
            <script>
                setTimeout(() => {
                    location.href = '/';
                }, 3000);
            </script>
        <?php endif; ?>
        
        <div class="step-list">
            <?php if ($installStatus === 'no_env'): ?>
                <div class="step-item" style="border-left-color: #e53e3e;">
                    <div class="step-number" style="background: #e53e3e;">!</div>
                    <div>
                        <strong>.env 파일 확인</strong><br>
                        <small class="text-muted">프로젝트 루트에 .env 파일이 없습니다. 설치 스크립트가 자동으로 생성합니다.</small>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="step-item">
                <div class="step-number">1</div>
                <div>
                    <strong>터미널 열기</strong><br>
                    <small class="text-muted"> 프로젝트 루트 디렉토리로 이동해주세요</small>
                </div>
            </div>
            
            <div class="step-item">
                <div class="step-number">2</div>
                <div>
                    <strong>설치 스크립트 실행</strong><br>
                    <small class="text-muted">아래 명령어를 터미널에 복사해서 실행하세요</small>
                </div>
            </div>
        </div>
        
        <div class="command-box">
            <button class="copy-btn" onclick="copyCommand()">
                <i class="fas fa-copy"></i> 복사
            </button>
            ./install.sh
        </div>
        
        <div class="step-list">
            <div class="step-item">
                <div class="step-number">3</div>
                <div>
                    <strong>설치 완료 대기</strong><br>
                    <small class="text-muted">설치 스크립트가 완료되면 자동으로 대시보드로 이동됩니다 (30초마다 확인)</small>
                </div>
            </div>
            
            <div class="step-item">
                <div class="step-number">4</div>
                <div>
                    <strong>수동 새로고침</strong><br>
                    <small class="text-muted">설치가 완료되었는데 자동 이동이 안 되면 아래 버튼을 클릭하세요</small>
                </div>
            </div>
        </div>
        
        <button class="refresh-btn" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> 페이지 새로고침
        </button>
        
        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #ecf0f1;">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                설치 과정에서 문제가 발생하면 README.md 파일을 참고하세요.
            </small>
        </div>
    </div>

    <script>
        function copyCommand() {
            navigator.clipboard.writeText('./install.sh').then(function() {
                const btn = document.querySelector('.copy-btn');
                btn.innerHTML = '<i class="fas fa-check"></i> 복사됨!';
                btn.style.background = '#27ae60';
                
                setTimeout(() => {
                    btn.innerHTML = '<i class="fas fa-copy"></i> 복사';
                    btn.style.background = '#3498db';
                }, 2000);
            });
        }
        
        // 자동 새로고침 체크 (30초마다)
        setInterval(() => {
            fetch('/ajax/install/check')
                .then(response => response.json())
                .then(data => {
                    if (data.installed) {
                        location.href = '/';
                    }
                })
                .catch(() => {
                    // 무시 (아직 설치되지 않음)
                });
        }, 30000);
    </script>
</body>
</html>