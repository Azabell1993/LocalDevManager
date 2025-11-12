<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit OS</h2>
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
        <form method="POST" action="/os/<?= $os['id'] ?>/edit">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">OS Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="name" name="name" required>
                            <option value="">Select OS</option>
                            <option value="Windows" <?= ($os['name'] === 'Windows') ? 'selected' : '' ?>>Windows</option>
                            <option value="macOS" <?= ($os['name'] === 'macOS') ? 'selected' : '' ?>>macOS</option>
                            <option value="Linux" <?= ($os['name'] === 'Linux') ? 'selected' : '' ?>>Linux</option>
                            <option value="Ubuntu" <?= ($os['name'] === 'Ubuntu') ? 'selected' : '' ?>>Ubuntu</option>
                            <option value="CentOS" <?= ($os['name'] === 'CentOS') ? 'selected' : '' ?>>CentOS</option>
                            <option value="Debian" <?= ($os['name'] === 'Debian') ? 'selected' : '' ?>>Debian</option>
                            <option value="RedHat" <?= ($os['name'] === 'RedHat') ? 'selected' : '' ?>>Red Hat Enterprise Linux</option>
                            <option value="FreeBSD" <?= ($os['name'] === 'FreeBSD') ? 'selected' : '' ?>>FreeBSD</option>
                        </select>
                        <div class="form-text">Select the operating system type</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="version" class="form-label">Version</label>
                        <input type="text" class="form-control" id="version" name="version" 
                               value="<?= htmlspecialchars($os['version'] ?? '') ?>"
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
                            <option value="x86_64" <?= ($os['arch'] === 'x86_64') ? 'selected' : '' ?>>x86_64 (64-bit)</option>
                            <option value="ARM64" <?= ($os['arch'] === 'ARM64') ? 'selected' : '' ?>>ARM64</option>
                            <option value="x86" <?= ($os['arch'] === 'x86') ? 'selected' : '' ?>>x86 (32-bit)</option>
                            <option value="ARM" <?= ($os['arch'] === 'ARM') ? 'selected' : '' ?>>ARM</option>
                        </select>
                        <div class="form-text">System architecture</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="hostname" class="form-label">Hostname</label>
                        <input type="text" class="form-control" id="hostname" name="hostname" 
                               value="<?= htmlspecialchars($os['hostname'] ?? '') ?>"
                               placeholder="e.g., my-server, workstation-01">
                        <div class="form-text">Enter the hostname or identifier of the actual computer</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="ip_address" class="form-label">IP Address</label>
                        <input type="text" class="form-control" id="ip_address" name="ip_address" 
                               value="<?= htmlspecialchars($os['ip_address'] ?? '') ?>"
                               placeholder="e.g., 192.168.1.100">
                        <div class="form-text">System IP address</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="access_level" class="form-label">Access Level</label>
                        <select class="form-select" id="access_level" name="access_level">
                            <option value="">Select Access Level</option>
                            <option value="admin" <?= ($os['access_level'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                            <option value="user" <?= ($os['access_level'] === 'user') ? 'selected' : '' ?>>User</option>
                            <option value="readonly" <?= ($os['access_level'] === 'readonly') ? 'selected' : '' ?>>Read Only</option>
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
                                  placeholder="Optional description or notes about this OS"><?= htmlspecialchars($os['description'] ?? '') ?></textarea>
                        <div class="form-text">Additional notes or description</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="created_at" class="form-label">Created</label>
                        <input type="text" class="form-control" 
                               value="<?= date('Y-m-d H:i:s', strtotime($os['created_at'])) ?>" 
                               disabled>
                        <div class="form-text">Registration date</div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <div>
                    <a href="/os" class="btn btn-secondary">Cancel</a>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update OS
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-suggest versions based on OS selection
document.getElementById('name').addEventListener('change', function() {
    const versionField = document.getElementById('version');
    const archField = document.getElementById('arch');
    
    // Set common architecture based on OS (only if not already set)
    if (!archField.value) {
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
        }
    }
});
</script>