<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Add New Project</h2>
    <a href="/projects" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Projects
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <h5 class="mb-0">
            <i class="fas fa-folder-plus me-2"></i>
            Project Information
        </h5>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="/projects/create">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-4">
                        <label for="name" class="form-label fw-semibold">Project Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="name" name="name" 
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required
                               placeholder="e.g., My Web Application">
                        <div class="form-text text-muted">Enter a descriptive name for your project</div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-4">
                        <label for="is_active" class="form-label fw-semibold">Status</label>
                        <select class="form-select form-select-lg" id="is_active" name="is_active">
                            <option value="1" <?= (($_POST['is_active'] ?? '1') === '1') ? 'selected' : '' ?>>
                                <i class="fas fa-check-circle"></i> Active
                            </option>
                            <option value="0" <?= (($_POST['is_active'] ?? '1') === '0') ? 'selected' : '' ?>>
                                <i class="fas fa-pause-circle"></i> Inactive
                            </option>
                        </select>
                        <div class="form-text text-muted">Active projects can be scanned</div>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="path" class="form-label fw-semibold">Project Path <span class="text-danger">*</span></label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-light">
                        <i class="fas fa-folder text-muted"></i>
                    </span>
                    <input type="text" class="form-control" id="path" name="path" 
                           value="<?= htmlspecialchars($_POST['path'] ?? '') ?>" required
                           placeholder="/path/to/your/project">
                    <button type="button" class="btn btn-info px-4" id="checkPathBtn">
                        <i class="fas fa-search me-1"></i> 경로 확인
                    </button>
                </div>
                <div class="form-text text-muted">Absolute path to the project root directory</div>
                
                <!-- 경로 확인 상태 표시 -->
                <div id="pathStatus" class="mt-2"></div>
                <div id="pathValidationStatus" class="mt-2">
                    <i class="fas fa-info-circle me-1 text-muted"></i>
                    <span class="text-muted">프로젝트를 생성하려면 먼저 "경로 확인" 버튼을 클릭해주세요.</span>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="description" class="form-label fw-semibold">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"
                          placeholder="Brief description of the project..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                <div class="form-text text-muted">Optional project description</div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center pt-4 mt-3 border-top">
                <a href="/projects" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-arrow-left me-2"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-lg px-4" id="createProjectBtn" disabled>
                    <i class="fas fa-plus me-2"></i> Create Project
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// 경로 확인 상태 관리 변수
let isPathValidated = false;

// Auto-suggest project name based on path
document.getElementById('path').addEventListener('input', function() {
    const path = this.value.trim();
    const nameInput = document.getElementById('name');
    
    if (path && !nameInput.value.trim()) {
        const pathParts = path.replace(/[\/\\]+$/, '').split(/[\/\\]/);
        const lastPart = pathParts[pathParts.length - 1];
        if (lastPart) {
            nameInput.value = lastPart.replace(/[-_]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }
    }
    
    // 경로가 변경되면 버튼 비활성화 및 상태 초기화
    disableCreateButton('경로가 변경되었습니다. "경로 확인" 버튼을 다시 클릭해주세요.');
    document.getElementById('pathStatus').innerHTML = '';
});

// Create Project 버튼 상태 관리 함수들
function enableCreateButton() {
    const createBtn = document.getElementById('createProjectBtn');
    const statusText = document.getElementById('pathValidationStatus');
    
    createBtn.disabled = false;
    createBtn.classList.remove('btn-secondary');
    createBtn.classList.add('btn-success');
    createBtn.innerHTML = '<i class="fas fa-check me-2"></i> Create Project';
    isPathValidated = true;
    
    statusText.innerHTML = `
        <i class="fas fa-check-circle text-success me-1"></i>
        <span class="text-success fw-semibold">경로 확인 완료! 프로젝트를 생성할 수 있습니다.</span>
    `;
}

function disableCreateButton(message = '프로젝트를 생성하려면 먼저 "경로 확인" 버튼을 클릭해주세요.') {
    const createBtn = document.getElementById('createProjectBtn');
    const statusText = document.getElementById('pathValidationStatus');
    
    createBtn.disabled = true;
    createBtn.classList.remove('btn-success');
    createBtn.classList.add('btn-secondary');
    createBtn.innerHTML = '<i class="fas fa-plus me-2"></i> Create Project';
    isPathValidated = false;
    
    statusText.innerHTML = `
        <i class="fas fa-info-circle me-1"></i>
        <span class="text-muted">${message}</span>
    `;
}

// 경로 확인 결과 표시 함수들
function showPathSuccess() {
    const statusText = document.getElementById('pathValidationStatus');
    statusText.innerHTML = `
        <i class="fas fa-check-circle text-success me-1"></i>
        <span class="text-success">경로 확인 완료! 경로가 존재합니다.</span>
    `;
}

function showPathError(message = '경로가 존재하지 않습니다.') {
    const statusText = document.getElementById('pathValidationStatus');
    statusText.innerHTML = `
        <i class="fas fa-exclamation-triangle text-warning me-1"></i>
        <span class="text-warning">${message}</span>
    `;
}

function resetPathStatus() {
    const statusText = document.getElementById('pathValidationStatus');
    statusText.innerHTML = `
        <i class="fas fa-info-circle me-1"></i>
        경로 확인 기능을 사용하여 경로 존재 여부를 미리 확인할 수 있습니다.
    `;
}

// 경로가 변경될 때 상태 초기화
document.getElementById('path').addEventListener('input', function() {
    resetPathStatus();
});

// 경로 확인 버튼
document.getElementById('checkPathBtn').addEventListener('click', function() {
    const path = document.getElementById('path').value.trim();
    const statusDiv = document.getElementById('pathStatus');
    
    if (!path) {
        statusDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>경로를 입력해주세요.</div>';
        return;
    }
    
    // 로딩 상태
    statusDiv.innerHTML = '<div class="text-primary"><i class="fas fa-spinner fa-spin me-2"></i>경로 확인 중...</div>';
    
    // AJAX 요청으로 경로 확인
    fetch('/ajax/check-project-path', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ path: path })
    })
    .then(response => response.json())
    .then(data => {
        if (data.exists) {
            statusDiv.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>✅ 경로가 존재합니다!</strong>
                    <br><small>경로: ${data.path}</small>
                </div>
            `;
            // 경로 확인 성공 시 Create Project 버튼 활성화
            enableCreateButton();
        } else {
            statusDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>
                    <strong>❌ 경로가 존재하지 않습니다!</strong>
                    <br><small>확인된 경로: ${data.path}</small>
                    <br><small class="text-muted">유효한 경로를 입력하거나 해당 디렉토리를 먼저 생성해주세요.</small>
                </div>
            `;
            // 경로가 존재하지 않으면 버튼 비활성화 유지
            disableCreateButton('경로가 존재하지 않습니다. 유효한 경로를 확인해주세요.');
        }
    })
    .catch(error => {
        console.error('Path check error:', error);
        statusDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>경로 확인 중 오류가 발생했습니다.</div>';
        disableCreateButton('경로 확인 중 오류가 발생했습니다. 다시 시도해주세요.');
    });
});

// 폼 제출 시 경로 확인 필수 검증
document.querySelector('form').addEventListener('submit', function(e) {
    const pathInput = document.getElementById('path');
    const createBtn = document.getElementById('createProjectBtn');
    
    // Create Project 버튼이 비활성화된 경우 폼 제출 방지
    if (createBtn.disabled || !isPathValidated) {
        e.preventDefault();
        
        // Bootstrap 모달로 알림 표시
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>경로 확인 필요
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            <strong>프로젝트를 생성하기 전에 경로 확인이 필요합니다.</strong>
                        </p>
                        <p class="mb-3">
                            "경로 확인" 버튼을 클릭하여 입력한 경로가 존재하는지 확인해주세요.
                        </p>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            경로가 존재해야만 프로젝트 생성이 가능합니다.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">확인</button>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="focusPathCheck">
                            <i class="fas fa-search me-1"></i>경로 확인하기
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // 경로 확인하기 버튼 클릭 시 경로 입력란으로 포커스
        modal.querySelector('#focusPathCheck').addEventListener('click', function() {
            pathInput.focus();
            pathInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        
        // 모달 제거
        modal.addEventListener('hidden.bs.modal', function () {
            modal.remove();
        });
        
        return false;
    }
});

// 페이지 로드시 초기 상태 설정
document.addEventListener('DOMContentLoaded', function() {
    disableCreateButton();
});
</script>