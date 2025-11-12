-- MySQL 데이터베이스 초기화 스크립트 (처음 설치용)

-- 1. 프로젝트 테이블
CREATE TABLE IF NOT EXISTS projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    root_path VARCHAR(4096) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    scan_count INT DEFAULT 0,
    last_scan TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_root_path (root_path(255))
);

-- 2. OS 테이블
CREATE TABLE IF NOT EXISTS oses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    version VARCHAR(50),
    arch VARCHAR(20),
    hostname VARCHAR(100) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    access_level VARCHAR(20) DEFAULT 'basic',
    description TEXT DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Agent 테이블
CREATE TABLE IF NOT EXISTS agents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    version VARCHAR(20),
    os_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (os_id) REFERENCES oses(id) ON DELETE SET NULL
);

-- 4. 스캔 테이블
CREATE TABLE IF NOT EXISTS scans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    total_files INT DEFAULT 0,
    total_loc INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'queued',
    error_message TEXT,
    engine_used VARCHAR(10) DEFAULT 'hybrid',
    execution_time_ms INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- 5. 스캔 언어별 통계 테이블
CREATE TABLE IF NOT EXISTS scan_lang_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    scan_id INT NOT NULL,
    language VARCHAR(50) NOT NULL,
    file_count INT DEFAULT 0,
    loc INT DEFAULT 0,
    comment_lines INT DEFAULT 0,
    blank_lines INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scan_id) REFERENCES scans(id) ON DELETE CASCADE
);

-- 6. 설정 테이블
CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` TEXT,
    `type` ENUM('string','number','boolean','json') DEFAULT 'string',
    `description` TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 7. 시스템 로그 테이블
CREATE TABLE IF NOT EXISTS system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    level ENUM('debug','info','warning','error','critical') DEFAULT 'info',
    message TEXT NOT NULL,
    context JSON,
    user_agent TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. 인덱스 생성 (에러 무시)
CREATE INDEX idx_projects_is_active ON projects(is_active);
CREATE INDEX idx_projects_scan_count ON projects(scan_count);
CREATE INDEX idx_agents_os_id ON agents(os_id);
CREATE INDEX idx_scans_project_id ON scans(project_id);
CREATE INDEX idx_scans_status ON scans(status);
CREATE INDEX idx_scans_started_at ON scans(started_at);
CREATE INDEX idx_scans_engine_used ON scans(engine_used);
CREATE INDEX idx_scan_lang_stats_scan_id ON scan_lang_stats(scan_id);
CREATE INDEX idx_scan_lang_stats_language ON scan_lang_stats(language);
CREATE INDEX idx_system_logs_level ON system_logs(level);
CREATE INDEX idx_system_logs_created_at ON system_logs(created_at);

-- 9. 초기 설정 데이터
INSERT IGNORE INTO settings (`key`, `value`, `type`, `description`) VALUES
('app_version', '1.0.0', 'string', 'Application version'),
('last_backup', NULL, 'string', 'Last database backup timestamp'),
('cpp_engine_path', './cpp_engine/build/loc_scanner_engine', 'string', 'Path to C++ LOC scanner engine'),
('default_scan_engine', 'hybrid', 'string', 'Default scanning engine (hybrid, cpp, php)'),
('max_scan_timeout', '300', 'number', 'Maximum scan timeout in seconds'),
('enable_detailed_logging', 'true', 'boolean', 'Enable detailed system logging');

-- 10. 샘플 OS 데이터
INSERT IGNORE INTO oses (id, name, version, arch, hostname, ip_address, access_level, description, status) VALUES
(1, 'macOS', 'Monterey', 'ARM64', 'MacBook-Pro-M1', '192.168.1.100', 'admin', 'Development MacBook Pro with M1 chip', 'active'),
(2, 'macOS', 'Big Sur', 'x86_64', 'iMac-Intel', '192.168.1.101', 'user', 'Intel-based iMac for testing', 'active'),
(3, 'Windows', '11', 'x86_64', 'WIN-DEV-01', '192.168.1.102', 'user', 'Windows development machine', 'active'),
(4, 'Ubuntu', '20.04', 'x86_64', 'ubuntu-server', '192.168.1.103', 'admin', 'Ubuntu server for production', 'active'),
(5, 'CentOS', '8', 'x86_64', 'centos-web', '192.168.1.104', 'user', 'CentOS web server', 'inactive');

-- 11. 샘플 에이전트 데이터
INSERT IGNORE INTO agents (id, name, version, os_id, notes) VALUES
(1, 'VS Code', '1.74.0', 1, 'Primary development environment'),
(2, 'Git', '2.39.0', 1, 'Version control system'),
(3, 'Docker', '20.10.21', 1, 'Container platform'),
(4, 'Node.js', '18.12.1', 1, 'JavaScript runtime'),
(5, 'PHP', '8.2.0', 1, 'PHP interpreter');