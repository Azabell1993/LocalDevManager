<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Add New OS</h2>
    <a href="/os" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to OS List
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
        <h5 class="mb-0">OS Information</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="/os/create">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">OS Name<span class="text-danger">*</span></label>
                        <select class="form-select" id="name" name="name" required>
                            <option value="">Select OS</option>
                            <option value="Windows" <?= (($_POST['name'] ?? '') === 'Windows') ? 'selected' : '' ?>>Windows</option>
                            <option value="macOS" <?= (($_POST['name'] ?? '') === 'macOS') ? 'selected' : '' ?>>macOS</option>
                            <option value="Linux" <?= (($_POST['name'] ?? '') === 'Linux') ? 'selected' : '' ?>>Linux</option>
                            <option value="Ubuntu" <?= (($_POST['name'] ?? '') === 'Ubuntu') ? 'selected' : '' ?>>Ubuntu</option>
                            <option value="CentOS" <?= (($_POST['name'] ?? '') === 'CentOS') ? 'selected' : '' ?>>CentOS</option>
                            <option value="Debian" <?= (($_POST['name'] ?? '') === 'Debian') ? 'selected' : '' ?>>Debian</option>
                            <option value="RedHat" <?= (($_POST['name'] ?? '') === 'RedHat') ? 'selected' : '' ?>>Red Hat Enterprise Linux</option>
                            <option value="FreeBSD" <?= (($_POST['name'] ?? '') === 'FreeBSD') ? 'selected' : '' ?>>FreeBSD</option>
                        </select>
                        <div class="form-text">Select the operating system type</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="version" class="form-label">Version</label>
                        <input type="text" class="form-control" id="version" name="version" 
                               value="<?= htmlspecialchars($_POST['version'] ?? '') ?>"
                               placeholder="e.g., 11, Monterey, 20.04, etc.">
                        <div class="form-text">OS version or codename</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="arch" class="form-label">Architecture</label>
                        <select class="form-select" id="arch" name="arch">
                            <option value="">Select Architecture</option>
                            <option value="x86_64" <?= (($_POST['arch'] ?? '') === 'x86_64') ? 'selected' : '' ?>>x86_64 (64-bit)</option>
                            <option value="ARM64" <?= (($_POST['arch'] ?? '') === 'ARM64') ? 'selected' : '' ?>>ARM64</option>
                            <option value="x86" <?= (($_POST['arch'] ?? '') === 'x86') ? 'selected' : '' ?>>x86 (32-bit)</option>
                            <option value="ARM" <?= (($_POST['arch'] ?? '') === 'ARM') ? 'selected' : '' ?>>ARM</option>
                        </select>
                        <div class="form-text">System architecture</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="hostname" class="form-label">Hostname</label>
                        <input type="text" class="form-control" id="hostname" name="hostname" 
                               value="<?= htmlspecialchars($_POST['hostname'] ?? '') ?>"
                               placeholder="예: my-server, workstation-01, dev-machine">
                        <div class="form-text">Enter the hostname or identifier of the actual computer.</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="ip_address" class="form-label">IP Address</label>
                        <input type="text" class="form-control" id="ip_address" name="ip_address" 
                               value="<?= htmlspecialchars($_POST['ip_address'] ?? '') ?>"
                               placeholder="e.g., 192.168.1.100" pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$">
                        <div class="form-text">Device IP address</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="access_level" class="form-label">Access Level</label>
                        <select class="form-select" id="access_level" name="access_level">
                            <option value="">Select Access Level</option>
                            <option value="admin" <?= (($_POST['access_level'] ?? '') === 'admin') ? 'selected' : '' ?>>관리자 (Admin)</option>
                            <option value="user" <?= (($_POST['access_level'] ?? '') === 'user') ? 'selected' : '' ?>>사용자 (User)</option>
                            <option value="readonly" <?= (($_POST['access_level'] ?? '') === 'readonly') ? 'selected' : '' ?>>읽기 전용 (Read Only)</option>
                            <option value="maintenance" <?= (($_POST['access_level'] ?? '') === 'maintenance') ? 'selected' : '' ?>>유지보수 (Maintenance)</option>
                            <option value="basic" <?= (($_POST['access_level'] ?? '') === 'basic') ? 'selected' : '' ?>>기본 (Basic)</option>
                        </select>
                        <div class="form-text">User access level</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Optional description or notes about this OS"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        <div class="form-text">Additional notes or description</div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="/os" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save OS
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-suggest versions based on OS selection
document.getElementById('name').addEventListener('change', function() {
    const versionField = document.getElementById('version');
    const archField = document.getElementById('arch');
    
    // Clear previous values
    versionField.value = '';
    
    // Set common architecture based on OS
    switch(this.value) {
        case 'Windows':
            versionField.placeholder = 'e.g., 11, 10, Server 2022';
            archField.value = 'x86_64';
            break;
        case 'macOS':
            versionField.placeholder = 'e.g., Monterey, Big Sur, 12.6';
            archField.value = 'ARM64';
            break;
        case 'Linux':
        case 'Ubuntu':
        case 'Debian':
        case 'CentOS':
            versionField.placeholder = 'e.g., 20.04, 8.5, 11';
            archField.value = 'x86_64';
            break;
        default:
            versionField.placeholder = 'Enter version';
            archField.value = '';
    }
});
</script>