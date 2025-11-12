<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Project</h2>
    <a href="/projects" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Projects
    </a>
</div>

<div class="card shadow-lg border-0">
    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <h5 class="mb-0">
            <i class="fas fa-edit me-2"></i>Project Information
        </h5>
    </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="/projects/<?= $project['id'] ?>" novalidate>
                    <?= Csrf::field() ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="name" class="form-label fw-bold">Project Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="name" name="name" 
                                       value="<?= htmlspecialchars($project['name'] ?? '') ?>" 
                                       placeholder="Enter a descriptive name for your project" required>
                                <div class="form-text">Enter a descriptive name for your project</div>
                                <div class="invalid-feedback">프로젝트 이름을 입력해주세요.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="is_active" class="form-label fw-bold">Status</label>
                                <select class="form-select form-select-lg" id="status" name="is_active">
                                    <option value="1" <?= ($project['is_active'] ?? 0) ? 'selected' : '' ?>>Active</option>
                                    <option value="0" <?= !($project['is_active'] ?? 0) ? 'selected' : '' ?>>Inactive</option>
                                </select>
                                <div class="form-text">Active projects can be scanned</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="path" class="form-label fw-bold">Project Path <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light">
                                <i class="fas fa-folder text-primary"></i>
                            </span>
                            <input type="text" class="form-control" id="path" name="path" 
                                   value="<?= htmlspecialchars($project['root_path'] ?? '') ?>" 
                                   placeholder="Absolute path to the project root directory" required>
                            <button type="button" class="btn btn-success" id="checkPathBtn">
                                <i class="fas fa-check-circle"></i> 경로 확인
                            </button>
                        </div>
                        <div class="form-text">Absolute path to the project root directory</div>
                        <div class="invalid-feedback">프로젝트 경로를 입력해주세요.</div>
                        <div id="pathStatus" class="mt-2"></div>
                        <div class="alert alert-info mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            프로젝트를 생성하려면 먼저 "경로 확인" 버튼을 클릭해주세요.
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-4">
                                <label for="description" class="form-label fw-bold">Description</label>
                                <textarea class="form-control form-control-lg" id="description" name="description" rows="4" 
                                          placeholder="Brief description of the project..."><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
                                <div class="form-text">Optional project description</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="/projects" class="btn btn-secondary btn-lg">Cancel</a>
                        <div class="text-end">
                            <button type="submit" class="btn btn-warning btn-lg px-4" id="updateProjectBtn" disabled>
                                <i class="fas fa-save me-2"></i> Update Project
                            </button>
                            <div class="mt-2">
                                <small class="text-muted" id="pathValidationStatus">
                                    <i class="fas fa-info-circle me-1"></i>
                                    경로를 변경한 경우 확인을 권장합니다.
                                </small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Update Project 버튼 상태 관리 함수들
function enableUpdateButton() {
    const updateBtn = document.getElementById('updateProjectBtn');
    const statusText = document.getElementById('pathValidationStatus');
    
    updateBtn.disabled = false;
    updateBtn.classList.remove('btn-secondary');
    updateBtn.classList.add('btn-warning');
    
    statusText.innerHTML = `
        <i class="fas fa-check-circle text-success me-1"></i>
        <span class="text-success">경로 확인 완료! 프로젝트를 수정할 수 있습니다.</span>
    `;
}

function disableUpdateButton(message = '프로젝트를 수정하려면 먼저 경로를 확인해주세요.') {
    const updateBtn = document.getElementById('updateProjectBtn');
    const statusText = document.getElementById('pathValidationStatus');
    
    updateBtn.disabled = true;
    updateBtn.classList.remove('btn-warning');
    updateBtn.classList.add('btn-secondary');
    
    statusText.innerHTML = `
        <i class="fas fa-info-circle me-1"></i>
        ${message}
    `;
}

// 경로가 변경될 때마다 상태 업데이트
document.getElementById('path').addEventListener('input', function() {
    disableUpdateButton('경로가 변경되었습니다. 경로를 다시 확인해주세요.');
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
            // 경로 확인 성공 시 Update 버튼 활성화
            enableUpdateButton();
        } else {
            statusDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>
                    <strong>❌ 경로가 존재하지 않습니다!</strong>
                    <br><small>확인된 경로: ${data.path}</small>
                    <br><small class="text-muted">유효한 경로를 입력하거나 해당 디렉토리를 먼저 생성해주세요.</small>
                </div>
            `;
            // 경로 확인 실패 시 메시지 업데이트
            disableUpdateButton('경로가 존재하지 않습니다. 유효한 경로를 확인해주세요.');
        }
    })
    .catch(error => {
        console.error('Path check error:', error);
        statusDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>경로 확인 중 오류가 발생했습니다.</div>';
        // 오류 시 메시지 업데이트
        disableUpdateButton('경로 확인 중 오류가 발생했습니다. 다시 시도해주세요.');
    });
});

// 폼 제출 시 경로 존재 여부 확인
document.querySelector('form').addEventListener('submit', function(e) {
    const pathInput = document.getElementById('path');
    const statusDiv = document.getElementById('pathStatus');
    const updateBtn = document.getElementById('updateProjectBtn');
    
    // Update Project 버튼이 비활성화된 경우 폼 제출 방지
    if (updateBtn.disabled) {
        e.preventDefault();
        
        if (pathInput.value.trim()) {
            // 경로는 있지만 확인하지 않은 경우
            statusDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i><strong>"경로 확인" 버튼을 클릭해서 경로를 먼저 확인해주세요.</strong></div>';
            pathInput.focus();
        } else {
            // 경로가 없는 경우
            pathInput.focus();
            pathInput.classList.add('is-invalid');
        }
        return false;
    }
});

// 실시간 유효성 검사
document.getElementById('name').addEventListener('input', function() {
    this.classList.remove('is-invalid');
});

document.getElementById('path').addEventListener('input', function() {
    this.classList.remove('is-invalid');
});
</script>