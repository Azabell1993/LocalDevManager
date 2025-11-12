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
