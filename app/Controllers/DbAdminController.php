<?php
/**
 * 데이터베이스 관리 컨트롤러
 */
class DbAdminController
{
    private $db;
    
    public function __construct()
    {
        $this->db = Db::getInstance();
    }
    
    /**
     * Root 계정으로 MySQL 연결하여 모든 데이터베이스 조회
     */
    private function getRootDbConnection()
    {
        static $rootDb = null;
        
        if ($rootDb === null) {
            try {
                $host = Env::get('DB_HOST', 'localhost');
                $port = Env::get('DB_PORT', '3306');
                $username = Env::get('ROOT_USER', 'root');
                $password = Env::get('ROOT_PASSWORD', '1234');
                
                $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                $rootDb = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);
            } catch (PDOException $e) {
                error_log("Root MySQL 연결 실패: " . $e->getMessage());
                return null;
            }
        }
        
        return $rootDb;
    }
    
    public function index()
    {
        // 현재 데이터베이스의 테이블 목록과 행 수 가져오기
        $tables = $this->getTableInfo();
        
        // Root 계정으로 모든 데이터베이스 목록 가져오기
        $allDatabases = $this->getAllDatabases();
        
        // 최근 스캔 통계 (MySQL용)
        $recentScans = $this->db->fetchAll("
            SELECT COUNT(*) as count, status 
            FROM scans 
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY status
        ");
        
        // 전체 LOC 통계 (MySQL용)
        $totalStats = $this->db->fetchOne("
            SELECT 
                COUNT(DISTINCT s.project_id) as projects_scanned,
                COALESCE(SUM(s.total_loc), 0) as total_loc,
                COALESCE(AVG(s.total_loc), 0) as avg_loc_per_project,
                COUNT(s.id) as total_scans
            FROM scans s
            WHERE s.status = 'completed'
        ");
        
        View::render('db.index', [
            'tables' => $tables,
            'all_databases' => $allDatabases,
            'recent_scans' => $recentScans,
            'total_stats' => $totalStats,
            'page_title' => 'Database Administration'
        ]);
    }
    
    /**
     * Root 권한으로 모든 데이터베이스 조회
     */
    private function getAllDatabases()
    {
        $rootDb = $this->getRootDbConnection();
        if (!$rootDb) {
            return [];
        }
        
        try {
            $stmt = $rootDb->prepare("SHOW DATABASES");
            $stmt->execute();
            $databases = $stmt->fetchAll();
            
            $result = [];
            foreach ($databases as $db) {
                $dbName = $db['Database'];
                
                // 시스템 데이터베이스와 사용자 데이터베이스 구분
                $isSystem = in_array($dbName, ['information_schema', 'mysql', 'performance_schema', 'sys']);
                
                // 각 데이터베이스의 테이블 수 조회
                try {
                    $tableCountStmt = $rootDb->prepare("
                        SELECT COUNT(*) as table_count 
                        FROM INFORMATION_SCHEMA.TABLES 
                        WHERE TABLE_SCHEMA = ?
                    ");
                    $tableCountStmt->execute([$dbName]);
                    $tableCount = $tableCountStmt->fetch()['table_count'];
                } catch (Exception $e) {
                    $tableCount = 0;
                }
                
                $result[] = [
                    'name' => $dbName,
                    'is_system' => $isSystem,
                    'table_count' => $tableCount,
                    'is_current' => $dbName === Env::get('DB_NAME', 'azabellcode')
                ];
            }
            
            // 현재 DB를 먼저, 그 다음 사용자 DB, 마지막에 시스템 DB 순으로 정렬
            usort($result, function($a, $b) {
                if ($a['is_current']) return -1;
                if ($b['is_current']) return 1;
                if ($a['is_system'] !== $b['is_system']) {
                    return $a['is_system'] ? 1 : -1;
                }
                return strcmp($a['name'], $b['name']);
            });
            
            return $result;
            
        } catch (Exception $e) {
            error_log("모든 데이터베이스 조회 실패: " . $e->getMessage());
            return [];
        }
    }
    
    public function table($tableName)
    {
        // 허용된 테이블만 접근
        $allowedTables = ['projects', 'oses', 'agents', 'scans', 'scan_lang_stats', 'settings'];
        if (!in_array($tableName, $allowedTables)) {
            $_SESSION['error'] = 'Access to this table is not allowed';
            header('Location: /db');
            exit;
        }
        
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        // 테이블 스키마 정보
        $schema = $this->db->fetchAll("PRAGMA table_info({$tableName})");
        
        // 총 행 수
        $totalResult = $this->db->fetchOne("SELECT COUNT(*) as count FROM {$tableName}");
        $total = $totalResult['count'];
        $totalPages = ceil($total / $limit);
        
        // 데이터 가져오기
        $data = $this->db->fetchAll("SELECT * FROM {$tableName} LIMIT {$limit} OFFSET {$offset}");
        
        View::render('db.table', [
            'table_name' => $tableName,
            'schema' => $schema,
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'total_pages' => $totalPages,
            'limit' => $limit,
            'page_title' => "Table: {$tableName}"
        ]);
    }
    
    public function query()
    {
        $result = null;
        $error = null;
        $query = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify($_POST['csrf_token'])) {
                $error = 'Invalid CSRF token';
            } else {
                $query = trim($_POST['query'] ?? '');
                
                if (empty($query)) {
                    $error = 'Query is required';
                } else {
                    // 안전성을 위한 간단한 체크 (SELECT만 허용)
                    $queryUpper = strtoupper(trim($query));
                    if (!preg_match('/^SELECT\s/i', $queryUpper)) {
                        $error = 'Only SELECT queries are allowed for security reasons';
                    } else {
                        try {
                            $start = microtime(true);
                            $result = $this->db->fetchAll($query);
                            $end = microtime(true);
                            $executionTime = round(($end - $start) * 1000, 2);
                            
                            $result = [
                                'data' => $result,
                                'count' => count($result),
                                'execution_time' => $executionTime
                            ];
                        } catch (Exception $e) {
                            $error = 'Query error: ' . $e->getMessage();
                        }
                    }
                }
            }
        }
        
        View::render('db.query', [
            'query' => $query,
            'result' => $result,
            'error' => $error,
            'csrf_token' => Csrf::generate(),
            'page_title' => 'SQL Query'
        ]);
    }
    
    public function export()
    {
        // GET 요청: 단일 테이블 내보내기
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $tableName = $_GET['table'] ?? '';
            $format = $_GET['format'] ?? 'csv';
            
            if (empty($tableName)) {
                $_SESSION['error'] = 'Table name is required';
                header('Location: /db');
                exit;
            }
            
            try {
                // 테이블 데이터 조회
                $data = $this->db->fetchAll("SELECT * FROM `{$tableName}`");
                
                View::render('db.export', [
                    'table_name' => $tableName,
                    'export_type' => $format,
                    'data' => $data
                ]);
                exit;
                
            } catch (Exception $e) {
                $_SESSION['error'] = 'Export failed: ' . $e->getMessage();
                header('Location: /db');
                exit;
            }
        }
        
        // POST 요청: 다중 테이블 내보내기 (기존 기능 유지)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: /db');
                exit;
            }
            
            $tables = $_POST['tables'] ?? [];
            if (empty($tables)) {
                $_SESSION['error'] = 'Please select at least one table to export';
                header('Location: /db');
                exit;
            }
            
            try {
                $filename = 'db_export_' . date('Y-m-d_H-i-s') . '.sql';
                $filepath = dirname(__DIR__, 2) . '/storage/backups/' . $filename;
                
                // 백업 디렉토리 생성
                if (!is_dir(dirname($filepath))) {
                    mkdir(dirname($filepath), 0755, true);
                }
                
                $this->exportTablesToSql($tables, $filepath);
                
                // 파일 다운로드
                header('Content-Type: application/sql');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                
                // 임시 파일 삭제
                unlink($filepath);
                exit;
                
            } catch (Exception $e) {
                $_SESSION['error'] = 'Export failed: ' . $e->getMessage();
                header('Location: /db');
                exit;
            }
        }
        
        // 내보내기 페이지 표시
        $tables = $this->getTableInfo();
        
        View::render('db.export', [
            'tables' => $tables,
            'csrf_token' => Csrf::generate(),
            'page_title' => 'Database Export'
        ]);
    }
    
    public function vacuum()
    {
        // AJAX 요청인지 확인
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify($_POST['csrf_token'])) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                    exit;
                } else {
                    $_SESSION['error'] = 'Invalid CSRF token';
                    header('Location: /db');
                    exit;
                }
            }
            
            try {
                $dbDriver = Env::get('DB_DRIVER', 'mysql');
                
                if ($dbDriver === 'sqlite') {
                    // SQLite용 VACUUM
                    $this->db->query("VACUUM");
                    $message = 'Database vacuumed successfully. Unused space has been reclaimed.';
                } else {
                    // MySQL용 테이블 최적화
                    $tables = $this->db->fetchAll("
                        SELECT TABLE_NAME as name 
                        FROM INFORMATION_SCHEMA.TABLES 
                        WHERE TABLE_SCHEMA = DATABASE()
                    ");
                    
                    $optimizedTables = [];
                    foreach ($tables as $table) {
                        $tableName = $table['name'];
                        $this->db->query("OPTIMIZE TABLE `{$tableName}`");
                        $optimizedTables[] = $tableName;
                    }
                    
                    $message = 'Database optimized successfully. ' . count($optimizedTables) . ' tables were optimized.';
                }
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true, 
                        'message' => $message,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    exit;
                } else {
                    $_SESSION['success'] = $message;
                }
                
            } catch (Exception $e) {
                $errorMessage = 'Database optimization failed: ' . $e->getMessage();
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $errorMessage]);
                    exit;
                } else {
                    $_SESSION['error'] = $errorMessage;
                }
            }
        }
        
        if (!$isAjax) {
            header('Location: /db');
            exit;
        }
    }
    
    private function getTableInfo()
    {
        $tables = [];
        $dbDriver = Env::get('DB_DRIVER', 'mysql');
        
        try {
            if ($dbDriver === 'sqlite') {
                // SQLite용 테이블 목록 조회
                $tableNames = $this->db->fetchAll("
                    SELECT name 
                    FROM sqlite_master 
                    WHERE type='table' AND name NOT LIKE 'sqlite_%'
                    ORDER BY name
                ");
            } else {
                // MySQL용 테이블 목록 조회
                $tableNames = $this->db->fetchAll("
                    SELECT TABLE_NAME as name 
                    FROM INFORMATION_SCHEMA.TABLES 
                    WHERE TABLE_SCHEMA = DATABASE()
                    ORDER BY TABLE_NAME
                ");
            }
            
            foreach ($tableNames as $table) {
                $name = $table['name'];
                $count = $this->db->fetchOne("SELECT COUNT(*) as count FROM `{$name}`");
                $tables[] = [
                    'name' => $name,
                    'count' => $count['count']
                ];
            }
        } catch (Exception $e) {
            error_log('Error getting table info: ' . $e->getMessage());
            // 기본 테이블들이라도 보여주기
            $defaultTables = ['projects', 'oses', 'agents', 'scans', 'scan_lang_stats', 'settings'];
            foreach ($defaultTables as $name) {
                try {
                    $count = $this->db->fetchOne("SELECT COUNT(*) as count FROM `{$name}`");
                    $tables[] = [
                        'name' => $name,
                        'count' => $count['count'] ?? 0
                    ];
                } catch (Exception $e2) {
                    // 테이블이 존재하지 않는 경우 무시
                }
            }
        }
        
        return $tables;
    }
    
    private function exportTablesToSql($tables, $filepath)
    {
        $fp = fopen($filepath, 'w');
        if (!$fp) {
            throw new Exception('Cannot create export file');
        }
        
        fwrite($fp, "-- MySQL Database Export\n");
        fwrite($fp, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
        
        foreach ($tables as $tableName) {
            // MySQL 테이블 스키마
            $schema = $this->db->fetchOne("SHOW CREATE TABLE `{$tableName}`");
            if ($schema) {
                fwrite($fp, "-- Table: {$tableName}\n");
                fwrite($fp, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                fwrite($fp, $schema['Create Table'] . ";\n\n");
                
                // 테이블 데이터
                $data = $this->db->fetchAll("SELECT * FROM `{$tableName}`");
                if (!empty($data)) {
                    fwrite($fp, "-- Data for table {$tableName}\n");
                    foreach ($data as $row) {
                        $values = array_map(function($value) {
                            return $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                        }, array_values($row));
                        
                        fwrite($fp, "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n");
                    }
                    fwrite($fp, "\n");
                }
            }
        }
        
        fclose($fp);
    }
    
    /**
     * 시스템 모니터링 API
     */
    public function getSystemInfo()
    {
        header('Content-Type: application/json');
        
        $systemInfo = [
            'server_info' => $this->getServerInfo(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'cpu_load' => $this->getCpuLoad(),
            'php_info' => $this->getPhpInfo(),
            'database_info' => $this->getDatabaseInfo()
        ];
        
        echo json_encode($systemInfo);
        exit;
    }
    
    private function getServerInfo()
    {
        return [
            'hostname' => gethostname(),
            'operating_system' => php_uname('s'),
            'os_version' => php_uname('r'),
            'architecture' => php_uname('m'),
            'server_time' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
            'uptime' => $this->getSystemUptime()
        ];
    }
    
    private function getMemoryUsage()
    {
        // PHP 메모리 사용량
        $php_memory = [
            'current_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true),
            'limit' => $this->convertToBytes(ini_get('memory_limit')),
            'usage_percentage' => 0
        ];
        
        if ($php_memory['limit'] > 0) {
            $php_memory['usage_percentage'] = round(($php_memory['current_usage'] / $php_memory['limit']) * 100, 2);
        }
        
        // 시스템 메모리 사용량 (macOS/Linux)
        $system_memory = $this->getSystemMemory();
        
        return [
            'php' => $php_memory,
            'system' => $system_memory
        ];
    }
    
    private function getSystemMemory()
    {
        $system_memory = [
            'total' => 0,
            'used' => 0,
            'free' => 0,
            'usage_percentage' => 0
        ];
        
        if (PHP_OS_FAMILY === 'Darwin') { // macOS
            // 총 메모리
            $total = shell_exec('sysctl -n hw.memsize');
            $system_memory['total'] = intval($total);
            
            // 사용 중인 메모리 (vm_stat 사용)
            $vm_stat = shell_exec('vm_stat');
            if ($vm_stat) {
                preg_match('/Pages free:\s+(\d+)/', $vm_stat, $free_match);
                preg_match('/Pages active:\s+(\d+)/', $vm_stat, $active_match);
                preg_match('/Pages inactive:\s+(\d+)/', $vm_stat, $inactive_match);
                preg_match('/Pages wired down:\s+(\d+)/', $vm_stat, $wired_match);
                
                $page_size = 4096; // macOS page size is typically 4KB
                
                if (isset($free_match[1], $active_match[1], $inactive_match[1], $wired_match[1])) {
                    $free_pages = intval($free_match[1]);
                    $active_pages = intval($active_match[1]);
                    $inactive_pages = intval($inactive_match[1]);
                    $wired_pages = intval($wired_match[1]);
                    
                    $system_memory['free'] = $free_pages * $page_size;
                    $system_memory['used'] = ($active_pages + $inactive_pages + $wired_pages) * $page_size;
                }
            }
        } elseif (PHP_OS_FAMILY === 'Linux') {
            // Linux 메모리 정보
            $meminfo = file_get_contents('/proc/meminfo');
            if ($meminfo) {
                preg_match('/MemTotal:\s+(\d+) kB/', $meminfo, $total_match);
                preg_match('/MemAvailable:\s+(\d+) kB/', $meminfo, $available_match);
                
                if (isset($total_match[1], $available_match[1])) {
                    $system_memory['total'] = intval($total_match[1]) * 1024;
                    $system_memory['free'] = intval($available_match[1]) * 1024;
                    $system_memory['used'] = $system_memory['total'] - $system_memory['free'];
                }
            }
        }
        
        if ($system_memory['total'] > 0) {
            $system_memory['usage_percentage'] = round(($system_memory['used'] / $system_memory['total']) * 100, 2);
        }
        
        return $system_memory;
    }
    
    private function getDiskUsage()
    {
        $disk_info = [];
        
        // 현재 디렉토리의 디스크 사용량
        $total_space = disk_total_space('.');
        $free_space = disk_free_space('.');
        $used_space = $total_space - $free_space;
        
        if ($total_space > 0) {
            $disk_info['main'] = [
                'total' => $total_space,
                'used' => $used_space,
                'free' => $free_space,
                'usage_percentage' => round(($used_space / $total_space) * 100, 2),
                'path' => realpath('.')
            ];
        }
        
        return $disk_info;
    }
    
    private function getCpuLoad()
    {
        $cpu_info = [
            'load_average' => null,
            'core_count' => null,
            'usage_percentage' => null
        ];
        
        // CPU 코어 수
        if (PHP_OS_FAMILY === 'Darwin') {
            $cpu_info['core_count'] = intval(shell_exec('sysctl -n hw.ncpu'));
        } elseif (PHP_OS_FAMILY === 'Linux') {
            $cpu_info['core_count'] = intval(shell_exec('nproc'));
        }
        
        // 시스템 로드 평균 (Unix/Linux/macOS)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if ($load) {
                $cpu_info['load_average'] = [
                    '1min' => round($load[0], 2),
                    '5min' => round($load[1], 2),
                    '15min' => round($load[2], 2)
                ];
                
                // CPU 사용률 계산 (로드 평균 / 코어 수 * 100)
                if ($cpu_info['core_count'] > 0) {
                    $cpu_info['usage_percentage'] = round(($load[0] / $cpu_info['core_count']) * 100, 2);
                }
            }
        }
        
        return $cpu_info;
    }
    
    private function getPhpInfo()
    {
        return [
            'version' => PHP_VERSION,
            'sapi' => php_sapi_name(),
            'extensions_count' => count(get_loaded_extensions()),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'display_errors' => ini_get('display_errors') ? 'On' : 'Off'
        ];
    }
    
    private function getDatabaseInfo()
    {
        $db_info = [
            'type' => 'MySQL',
            'version' => null,
            'size' => 0,
            'tables_count' => 0
        ];
        
        try {
            // MySQL 버전
            $version = $this->db->fetchOne("SELECT VERSION() as version");
            if ($version) {
                $db_info['version'] = $version['version'];
            }
            
            // 데이터베이스 크기
            $dbname = Env::get('DB_NAME', 'azabellcode');
            $size = $this->db->fetchOne("
                SELECT 
                    ROUND(SUM(data_length + index_length), 2) as size
                FROM information_schema.TABLES 
                WHERE table_schema = ?
            ", [$dbname]);
            
            if ($size && $size['size']) {
                $db_info['size'] = $size['size'];
            }
            
            // 테이블 수
            $tables = $this->db->fetchAll("
                SELECT COUNT(*) as count 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = ?
            ", [$dbname]);
            
            if ($tables && isset($tables[0]['count'])) {
                $db_info['tables_count'] = $tables[0]['count'];
            }
            
        } catch (Exception $e) {
            error_log('Database info error: ' . $e->getMessage());
        }
        
        return $db_info;
    }
    
    private function getSystemUptime()
    {
        $uptime = null;
        
        if (PHP_OS_FAMILY === 'Darwin') {
            $boot_time = shell_exec('sysctl -n kern.boottime | awk \'{print $4}\' | sed \'s/,//\'');
            if ($boot_time) {
                $uptime = time() - intval($boot_time);
            }
        } elseif (PHP_OS_FAMILY === 'Linux') {
            $uptime_info = file_get_contents('/proc/uptime');
            if ($uptime_info) {
                $uptime = floatval(explode(' ', $uptime_info)[0]);
            }
        }
        
        return $uptime;
    }
    
    private function convertToBytes($value)
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = intval($value);
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * SQL 쿼리 실행 (AJAX)
     */
    public function executeQuery()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $query = trim($input['query'] ?? '');
        
        if (empty($query)) {
            echo json_encode(['error' => '쿼리가 제공되지 않았습니다']);
            return;
        }
        
        // 보안 체크: 허용된 명령어만 실행 (화이트리스트 방식)
        $allowedKeywords = ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN'];
        $upperQuery = strtoupper($query);
        $isAllowed = false;
        
        foreach ($allowedKeywords as $keyword) {
            if (preg_match('/^\s*' . $keyword . '\b/', $upperQuery)) {
                $isAllowed = true;
                break;
            }
        }
        
        if (!$isAllowed) {
            echo json_encode(['error' => "보안상 해당 명령어는 사용할 수 없습니다. SELECT, SHOW, DESCRIBE, DESC, EXPLAIN만 허용됩니다."]);
            return;
        }
        
        try {
            // 쿼리 실행
            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute();
            
            // SELECT 쿼리인지 확인
            if (stripos($upperQuery, 'SELECT') === 0 || stripos($upperQuery, 'SHOW') === 0 || stripos($upperQuery, 'DESCRIBE') === 0 || stripos($upperQuery, 'DESC') === 0) {
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'type' => 'select',
                    'data' => $results,
                    'count' => count($results),
                    'message' => count($results) . '개 행이 반환되었습니다.'
                ]);
            } else {
                // 기타 쿼리
                $affected = $stmt->rowCount();
                echo json_encode([
                    'success' => true,
                    'type' => 'modify',
                    'affected_rows' => $affected,
                    'message' => "쿼리가 성공적으로 실행되었습니다. {$affected}개 행이 영향을 받았습니다."
                ]);
            }
            
        } catch (PDOException $e) {
            echo json_encode([
                'error' => 'SQL 오류: ' . $e->getMessage()
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'error' => '쿼리 실행 중 오류가 발생했습니다: ' . $e->getMessage()
            ]);
        }
    }
}