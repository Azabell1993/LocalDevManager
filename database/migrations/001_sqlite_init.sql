-- SQLite 데이터베이스 초기화 스크립트

-- 프로젝트 테이블
CREATE TABLE IF NOT EXISTS projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    root_path TEXT NOT NULL UNIQUE,
    description TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- OS 테이블
CREATE TABLE IF NOT EXISTS oses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    version TEXT,
    arch TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Agent 테이블
CREATE TABLE IF NOT EXISTS agents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    version TEXT,
    os_id INTEGER,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (os_id) REFERENCES oses(id) ON DELETE SET NULL
);

-- 스캔 테이블
CREATE TABLE IF NOT EXISTS scans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id INTEGER NOT NULL,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    total_files INTEGER DEFAULT 0,
    total_loc INTEGER DEFAULT 0,
    status TEXT DEFAULT 'queued',
    error_message TEXT,
    engine_used TEXT DEFAULT 'hybrid',
    execution_time_ms INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- 스캔 언어별 통계 테이블
CREATE TABLE IF NOT EXISTS scan_lang_stats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    scan_id INTEGER NOT NULL,
    language TEXT NOT NULL,
    file_count INTEGER DEFAULT 0,
    loc INTEGER DEFAULT 0,
    comment_lines INTEGER DEFAULT 0,
    blank_lines INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scan_id) REFERENCES scans(id) ON DELETE CASCADE
);

-- 설정 테이블
CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT,
    type TEXT DEFAULT 'string',
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 시스템 로그 테이블
CREATE TABLE IF NOT EXISTS system_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    level TEXT DEFAULT 'info',
    message TEXT NOT NULL,
    context TEXT,
    user_agent TEXT,
    ip_address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 인덱스 생성
CREATE INDEX IF NOT EXISTS idx_projects_is_active ON projects(is_active);
CREATE INDEX IF NOT EXISTS idx_agents_os_id ON agents(os_id);
CREATE INDEX IF NOT EXISTS idx_scans_project_id ON scans(project_id);
CREATE INDEX IF NOT EXISTS idx_scans_status ON scans(status);
CREATE INDEX IF NOT EXISTS idx_scans_started_at ON scans(started_at);
CREATE INDEX IF NOT EXISTS idx_scan_lang_stats_scan_id ON scan_lang_stats(scan_id);
CREATE INDEX IF NOT EXISTS idx_scan_lang_stats_language ON scan_lang_stats(language);
CREATE INDEX IF NOT EXISTS idx_system_logs_level ON system_logs(level);
CREATE INDEX IF NOT EXISTS idx_system_logs_created_at ON system_logs(created_at);

-- 트리거: 업데이트 시 updated_at 자동 갱신
CREATE TRIGGER IF NOT EXISTS update_projects_updated_at 
AFTER UPDATE ON projects
BEGIN
    UPDATE projects SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_oses_updated_at 
AFTER UPDATE ON oses
BEGIN
    UPDATE oses SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_agents_updated_at 
AFTER UPDATE ON agents
BEGIN
    UPDATE agents SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_settings_updated_at 
AFTER UPDATE ON settings
BEGIN
    UPDATE settings SET updated_at = CURRENT_TIMESTAMP WHERE key = NEW.key;
END;

-- 초기 데이터 삽입
INSERT OR IGNORE INTO settings (key, value, type, description) VALUES
('app_version', '1.0.0', 'string', 'Application version'),
('last_backup', NULL, 'string', 'Last database backup timestamp'),
('cpp_engine_path', './cpp_engine/build/loc_scanner_engine', 'string', 'Path to C++ LOC scanner engine'),
('default_scan_engine', 'hybrid', 'string', 'Default scanning engine (hybrid, cpp, php)'),
('max_scan_timeout', '300', 'number', 'Maximum scan timeout in seconds'),
('enable_detailed_logging', 'true', 'boolean', 'Enable detailed system logging');

-- 샘플 OS 데이터
INSERT OR IGNORE INTO oses (id, name, version, arch) VALUES
(1, 'macOS', 'Monterey', 'ARM64'),
(2, 'macOS', 'Big Sur', 'x86_64'),
(3, 'Windows', '11', 'x86_64'),
(4, 'Ubuntu', '20.04', 'x86_64'),
(5, 'CentOS', '8', 'x86_64');

-- 샘플 에이전트 데이터
INSERT OR IGNORE INTO agents (id, name, version, os_id, notes) VALUES
(1, 'VS Code', '1.74.0', 1, 'Primary development environment'),
(2, 'Git', '2.39.0', 1, 'Version control system'),
(3, 'Docker', '20.10.21', 1, 'Container platform'),
(4, 'Node.js', '18.12.1', 1, 'JavaScript runtime'),
(5, 'PHP', '8.2.0', 1, 'PHP interpreter');