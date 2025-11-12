<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Add New Agent</h2>
    <a href="/agents" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Agents
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
        <h5 class="mb-0">Agent Information</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="/agents/create">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Agent Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required
                               placeholder="e.g., Visual Studio Code, Docker, Node.js">
                        <div class="form-text">Enter the name of the development tool or agent</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="version" class="form-label">Version</label>
                        <input type="text" class="form-control" id="version" name="version" 
                               value="<?= htmlspecialchars($_POST['version'] ?? '') ?>"
                               placeholder="e.g., 1.85.0, 20.10.2, 18.19.0">
                        <div class="form-text">Version number or release identifier</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">Select Category</option>
                            <option value="IDE" <?= (($_POST['category'] ?? '') === 'IDE') ? 'selected' : '' ?>>IDE/Editor</option>
                            <option value="Compiler" <?= (($_POST['category'] ?? '') === 'Compiler') ? 'selected' : '' ?>>Compiler</option>
                            <option value="Runtime" <?= (($_POST['category'] ?? '') === 'Runtime') ? 'selected' : '' ?>>Runtime</option>
                            <option value="Framework" <?= (($_POST['category'] ?? '') === 'Framework') ? 'selected' : '' ?>>Framework</option>
                            <option value="Database" <?= (($_POST['category'] ?? '') === 'Database') ? 'selected' : '' ?>>Database</option>
                            <option value="Container" <?= (($_POST['category'] ?? '') === 'Container') ? 'selected' : '' ?>>Container</option>
                            <option value="VCS" <?= (($_POST['category'] ?? '') === 'VCS') ? 'selected' : '' ?>>Version Control</option>
                            <option value="Testing" <?= (($_POST['category'] ?? '') === 'Testing') ? 'selected' : '' ?>>Testing Tool</option>
                            <option value="Build" <?= (($_POST['category'] ?? '') === 'Build') ? 'selected' : '' ?>>Build Tool</option>
                            <option value="Other" <?= (($_POST['category'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                        <div class="form-text">Type of development tool</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="os_id" class="form-label">Operating System</label>
                        <select class="form-select" id="os_id" name="os_id">
                            <option value="">Select OS</option>
                            <?php if (!empty($os_list)): ?>
                                <?php foreach ($os_list as $os): ?>
                                    <option value="<?= $os['id'] ?>" <?= (($_POST['os_id'] ?? '') == $os['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($os['name']) ?>
                                        <?php if ($os['version']): ?>
                                            <?= htmlspecialchars($os['version']) ?>
                                        <?php endif; ?>
                                        <?php if ($os['arch']): ?>
                                            (<?= htmlspecialchars($os['arch']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">Target operating system for this agent</div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="install_path" class="form-label">Installation Path</label>
                <input type="text" class="form-control" id="install_path" name="install_path" 
                       value="<?= htmlspecialchars($_POST['install_path'] ?? '') ?>"
                       placeholder="e.g., /usr/local/bin/node, C:\Program Files\Docker">
                <div class="form-text">Path where the agent/tool is installed (optional)</div>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"
                          placeholder="Additional notes about configuration, usage, or setup..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                <div class="form-text">Optional notes and configuration details</div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" 
                                   <?= (($_POST['is_active'] ?? '1') === '1') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">
                                Active Agent
                            </label>
                            <div class="form-text">Include in active development environment</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="/agents" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Agent
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-suggest common tools based on name input
document.getElementById('name').addEventListener('input', function() {
    const name = this.value.toLowerCase();
    const categorySelect = document.getElementById('category');
    const versionInput = document.getElementById('version');
    const pathInput = document.getElementById('install_path');
    
    // Auto-suggest category based on common tool names
    const suggestions = {
        'visual studio code': { category: 'IDE', path: '/Applications/Visual Studio Code.app' },
        'vscode': { category: 'IDE', path: '/usr/local/bin/code' },
        'intellij': { category: 'IDE', path: '/Applications/IntelliJ IDEA.app' },
        'sublime': { category: 'IDE', path: '/Applications/Sublime Text.app' },
        'atom': { category: 'IDE', path: '/Applications/Atom.app' },
        'docker': { category: 'Container', path: '/usr/local/bin/docker' },
        'node': { category: 'Runtime', path: '/usr/local/bin/node' },
        'nodejs': { category: 'Runtime', path: '/usr/local/bin/node' },
        'python': { category: 'Runtime', path: '/usr/local/bin/python3' },
        'java': { category: 'Runtime', path: '/usr/bin/java' },
        'git': { category: 'VCS', path: '/usr/local/bin/git' },
        'mysql': { category: 'Database', path: '/usr/local/mysql/bin/mysql' },
        'postgresql': { category: 'Database', path: '/usr/local/bin/psql' },
        'mongodb': { category: 'Database', path: '/usr/local/bin/mongod' },
        'redis': { category: 'Database', path: '/usr/local/bin/redis-server' },
        'nginx': { category: 'Other', path: '/usr/local/bin/nginx' },
        'apache': { category: 'Other', path: '/usr/local/bin/httpd' },
        'webpack': { category: 'Build', path: '/usr/local/bin/webpack' },
        'gradle': { category: 'Build', path: '/usr/local/bin/gradle' },
        'maven': { category: 'Build', path: '/usr/local/bin/mvn' },
        'npm': { category: 'Build', path: '/usr/local/bin/npm' },
        'yarn': { category: 'Build', path: '/usr/local/bin/yarn' },
        'jest': { category: 'Testing', path: '/usr/local/bin/jest' },
        'junit': { category: 'Testing', path: '' }
    };
    
    // Find matching suggestion
    for (const [key, suggestion] of Object.entries(suggestions)) {
        if (name.includes(key)) {
            if (!categorySelect.value) {
                categorySelect.value = suggestion.category;
            }
            if (!pathInput.value && suggestion.path) {
                pathInput.value = suggestion.path;
            }
            break;
        }
    }
});

// Category-based path suggestions
document.getElementById('category').addEventListener('change', function() {
    const category = this.value;
    const pathInput = document.getElementById('install_path');
    
    if (!pathInput.value) {
        const pathSuggestions = {
            'IDE': '/Applications/',
            'Runtime': '/usr/local/bin/',
            'Database': '/usr/local/bin/',
            'Container': '/usr/local/bin/',
            'VCS': '/usr/local/bin/',
            'Build': '/usr/local/bin/',
            'Testing': '/usr/local/bin/'
        };
        
        if (pathSuggestions[category]) {
            pathInput.placeholder = 'e.g., ' + pathSuggestions[category] + 'tool-name';
        }
    }
});
</script>