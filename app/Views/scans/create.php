<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>New Scan</h2>
    <a href="/scans" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Scans
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

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Run LOC Scan</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="/scans/create">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="mb-4">
                <label for="project_id" class="form-label">Select Project <span class="text-danger">*</span></label>
                <select class="form-control" id="project_id" name="project_id" required>
                    <option value="">Choose a project to scan...</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?= $project['id'] ?>" <?= (($_POST['project_id'] ?? '') == $project['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($project['name']) ?>
                            <small class="text-muted"><?= htmlspecialchars($project['root_path']) ?></small>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Only active projects are available for scanning</div>
            </div>
            
            <!-- Project Preview -->
            <div id="project-preview" class="card mb-4" style="display: none;">
                <div class="card-body">
                    <h6>Project Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Path:</strong> <span id="preview-path" class="text-muted"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong> 
                            <span class="badge badge-success">Active</span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <strong>Description:</strong> <span id="preview-description" class="text-muted"></span>
                    </div>
                </div>
            </div>
            
            <!-- Engine Selection -->
            <div class="mb-4">
                <label class="form-label">Scanning Engine</label>
                <div class="engine-selection">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="engine" id="engine_auto" value="auto" checked>
                        <label class="form-check-label" for="engine_auto">
                            <strong>Auto (Recommended)</strong>
                            <div class="text-muted small">Use C++ engine if available, fallback to PHP</div>
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="engine" id="engine_cpp" value="cpp">
                        <label class="form-check-label" for="engine_cpp">
                            <strong>C++ Engine</strong>
                            <div class="text-muted small">High performance native scanner</div>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="engine" id="engine_php" value="php">
                        <label class="form-check-label" for="engine_php">
                            <strong>PHP Engine</strong>
                            <div class="text-muted small">Reliable fallback scanner</div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Options -->
            <div class="card mb-4">
                <div class="card-header">
                    <button class="btn btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#advancedOptions">
                        <i class="fas fa-cog"></i> Advanced Options
                    </button>
                </div>
                <div class="collapse" id="advancedOptions">
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="include_comments" name="include_comments" checked>
                            <label class="form-check-label" for="include_comments">
                                Count comment lines separately
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="include_blank" name="include_blank" checked>
                            <label class="form-check-label" for="include_blank">
                                Count blank lines separately
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="detailed_report" name="detailed_report" checked>
                            <label class="form-check-label" for="detailed_report">
                                Generate detailed language breakdown
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="/scans" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary btn-lg" id="start-scan-btn">
                    <i class="fas fa-play"></i> Start Scan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const projectSelect = document.getElementById('project_id');
    const projectPreview = document.getElementById('project-preview');
    const previewPath = document.getElementById('preview-path');
    const previewDescription = document.getElementById('preview-description');
    
    // Project data (would be passed from PHP)
    const projectData = {
        <?php foreach ($projects as $project): ?>
        '<?= $project['id'] ?>': {
            path: '<?= addslashes($project['root_path']) ?>',
            description: '<?= addslashes($project['description'] ?? 'No description') ?>'
        },
        <?php endforeach; ?>
    };
    
    projectSelect.addEventListener('change', function() {
        const selectedId = this.value;
        if (selectedId && projectData[selectedId]) {
            const project = projectData[selectedId];
            previewPath.textContent = project.path;
            previewDescription.textContent = project.description;
            projectPreview.style.display = 'block';
        } else {
            projectPreview.style.display = 'none';
        }
    });
    
    // Engine availability check
    fetch('/ajax/engine-status')
        .then(response => response.json())
        .then(data => {
            const cppRadio = document.getElementById('engine_cpp');
            const cppLabel = cppRadio.nextElementSibling;
            
            if (!data.cpp_available) {
                cppRadio.disabled = true;
                cppLabel.classList.add('text-muted');
                cppLabel.querySelector('.text-muted').textContent = 'Not available - Build required';
            }
        })
        .catch(error => {
            console.error('Engine status check failed:', error);
        });
    
    // Form submission with progress
    document.querySelector('form').addEventListener('submit', function(e) {
        const startBtn = document.getElementById('start-scan-btn');
        startBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting Scan...';
        startBtn.disabled = true;
    });
});
</script>