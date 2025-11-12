<?php
/**
 * 프로젝트 컨트롤러
 */
class ProjectController
{
    private $db;
    private $locScanner;
    
    public function __construct()
    {
        $this->db = Db::getInstance();
        $this->locScanner = new LocScanner();
    }
    
    public function index()
    {
        // 페이징 파라미터
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $limit = in_array($limit, [5, 10, 15, 30]) ? intval($limit) : 10; // 허용된 값 확장
        $offset = intval(($page - 1) * $limit);
        
        // 검색 파라미터
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        
        // 기본 쿼리
        $whereClause = '';
        $params = [];
        
        if ($search) {
            $whereClause .= " WHERE (p.name LIKE ? OR p.root_path LIKE ? OR p.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($status) {
            $whereClause .= $search ? " AND" : " WHERE";
            $whereClause .= " p.is_active = ?";
            $params[] = ($status === 'active') ? 1 : 0;
        }
        
        // 총 개수 조회
        $totalQuery = "SELECT COUNT(*) as total FROM projects p" . $whereClause;
        $totalResult = $this->db->fetchAll($totalQuery, $params);
        $total = $totalResult[0]['total'];
        $totalPages = ceil($total / $limit);
        
        // 데이터 조회 (LIMIT과 OFFSET을 직접 쿼리에 포함)
        $projects = $this->db->fetchAll("
            SELECT p.*, 
                   (SELECT COUNT(*) FROM scans s WHERE s.project_id = p.id) as scan_count,
                   (SELECT s.started_at FROM scans s WHERE s.project_id = p.id ORDER BY s.started_at DESC LIMIT 1) as last_scan
            FROM projects p 
            $whereClause
            ORDER BY p.created_at DESC
            LIMIT $limit OFFSET $offset
        ", $params);

        // 각 프로젝트의 경로 존재 여부 체크
        foreach ($projects as &$project) {
            $project['path_exists'] = is_dir($project['root_path'] ?? $project['path'] ?? '');
            
            // 경로가 존재하지 않으면 통계 데이터 0으로 설정
            if (!$project['path_exists']) {
                $project['scan_count'] = 0;
                $project['last_scan'] = null;
            }
        }
        
        View::render('projects.index', [
            'projects' => $projects,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $totalPages,
                'search' => $search,
                'status' => $status
            ],
            'page_title' => 'Projects'
        ]);
    }
    
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: /projects');
                exit;
            }
            
            $name = trim($_POST['name'] ?? '');
            $path = rtrim(trim($_POST['path'] ?? ''), '/\\');
            $description = trim($_POST['description'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // 유효성 검사
            $errors = [];
            if (empty($name)) {
                $errors[] = 'Project name is required';
            }
            if (empty($path)) {
                $errors[] = 'Project path is required';
            } elseif (!is_dir($path)) {
                $errors[] = 'Project path does not exist';
            }
            
            // 중복 이름 체크
            $existing = $this->db->fetchOne("SELECT id FROM projects WHERE name = ?", [$name]);
            if ($existing) {
                $errors[] = 'Project name already exists';
            }
            
            // 중복 경로 체크
            $existing = $this->db->fetchOne("SELECT id FROM projects WHERE root_path = ?", [$path]);
            if ($existing) {
                $errors[] = 'Project path already exists';
            }
            
            if (!empty($errors)) {
                $_SESSION['error'] = implode('<br>', $errors);
                $_SESSION['form_data'] = $_POST;
                header('Location: /projects/create');
                exit;
            }
            
            // 프로젝트 생성
            $this->db->query("
                INSERT INTO projects (name, root_path, description, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?)
            ", [$name, $path, $description, $is_active, date('Y-m-d H:i:s')]);
            
            $_SESSION['success'] = 'Project created successfully';
            header('Location: /projects');
            exit;
        }
        
        $form_data = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']);
        
        View::render('projects.create', [
            'form_data' => $form_data,
            'csrf_token' => Csrf::generate(),
            'page_title' => 'Create Project'
        ]);
    }
    
    public function edit($id)
    {
        $project = $this->db->fetchOne("SELECT * FROM projects WHERE id = ?", [$id]);
        if (!$project) {
            $_SESSION['error'] = 'Project not found';
            header('Location: /projects');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: /projects');
                exit;
            }
            
            $name = trim($_POST['name'] ?? '');
            $path = rtrim(trim($_POST['path'] ?? ''), '/\\');
            $description = trim($_POST['description'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // 유효성 검사
            $errors = [];
            if (empty($name)) {
                $errors[] = 'Project name is required';
            }
            if (empty($path)) {
                $errors[] = 'Project path is required';
            } elseif (!is_dir($path)) {
                $errors[] = 'Project path does not exist';
            }
            
            // 중복 이름 체크 (자기 자신 제외)
            $existing = $this->db->fetchOne("SELECT id FROM projects WHERE name = ? AND id != ?", [$name, $id]);
            if ($existing) {
                $errors[] = 'Project name already exists';
            }
            
            // 중복 경로 체크 (자기 자신 제외)
            $existing = $this->db->fetchOne("SELECT id FROM projects WHERE root_path = ? AND id != ?", [$path, $id]);
            if ($existing) {
                $errors[] = 'Project path already exists';
            }
            
            if (!empty($errors)) {
                $_SESSION['error'] = implode('<br>', $errors);
                $project = array_merge($project, $_POST); // 입력한 값으로 덮어쓰기
            } else {
                // 프로젝트 업데이트
                $this->db->query("
                    UPDATE projects 
                    SET name = ?, root_path = ?, description = ?, is_active = ?, updated_at = ?
                    WHERE id = ?
                ", [$name, $path, $description, $is_active, date('Y-m-d H:i:s'), $id]);
                
                $_SESSION['success'] = 'Project updated successfully';
                header('Location: /projects');
                exit;
            }
        }
        
        View::render('projects.edit', [
            'project' => $project,
            'csrf_token' => Csrf::generate(),
            'page_title' => 'Edit Project'
        ]);
    }
    
    public function delete($id)
    {
        $project = $this->db->fetchOne("SELECT * FROM projects WHERE id = ?", [$id]);
        if (!$project) {
            $_SESSION['error'] = 'Project not found';
            header('Location: /projects');
            exit;
        }
        
        // 스캔 데이터가 있는지 확인
        $scans = $this->db->fetchOne("SELECT COUNT(*) as count FROM scans WHERE project_id = ?", [$id]);
        
        if ($scans['count'] > 0) {
            $_SESSION['error'] = 'Cannot delete project with scan history. Deactivate instead.';
            header('Location: /projects');
            exit;
        }
        
        // 프로젝트 삭제
        $this->db->query("DELETE FROM projects WHERE id = ?", [$id]);
        
        $_SESSION['success'] = 'Project deleted successfully';
        header('Location: /projects');
        exit;
    }
    
    public function scan($id)
    {
        $project = $this->db->fetchOne("SELECT * FROM projects WHERE id = ?", [$id]);
        if (!$project) {
            $_SESSION['error'] = 'Project not found';
            header('Location: /projects');
            exit;
        }
        
        if (!$project['is_active']) {
            $_SESSION['error'] = 'Cannot scan inactive project';
            header('Location: /projects');
            exit;
        }
        
        try {
            $scanId = $this->locScanner->scanProject($id);
            $_SESSION['success'] = 'Project scan completed successfully';
            header('Location: /scans/' . $scanId);
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Scan failed: ' . $e->getMessage();
            header('Location: /projects');
            exit;
        }
    }
    
    public function toggle($id)
    {
        $project = $this->db->fetchOne("SELECT * FROM projects WHERE id = ?", [$id]);
        if (!$project) {
            $_SESSION['error'] = 'Project not found';
            header('Location: /projects');
            exit;
        }
        
        $new_status = $project['is_active'] ? 0 : 1;
        $this->db->query("UPDATE projects SET is_active = ?, updated_at = ? WHERE id = ?", 
            [$new_status, date('Y-m-d H:i:s'), $id]);
        
        $status_text = $new_status ? 'activated' : 'deactivated';
        $_SESSION['success'] = "Project {$status_text} successfully";
        header('Location: /projects');
        exit;
    }
    
    /**
     * VS Code로 프로젝트 열기
     */
    public function openVscode($id)
    {
        header('Content-Type: application/json');
        
        $project = $this->db->fetchOne("SELECT * FROM projects WHERE id = ?", [$id]);
        if (!$project) {
            echo json_encode(['success' => false, 'message' => 'Project not found']);
            exit;
        }
        
        $projectPath = $project['root_path'] ?? $project['path'];
        
        if (!is_dir($projectPath)) {
            echo json_encode(['success' => false, 'message' => 'Project directory does not exist']);
            exit;
        }
        
        // VS Code 명령어 실행
        $command = sprintf('cd %s && code .', escapeshellarg($projectPath));
        $output = [];
        $returnCode = 0;
        
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'VS Code opened successfully',
                'project_path' => $projectPath
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to open VS Code: ' . implode(' ', $output)
            ]);
        }
        exit;
    }
    
    /**
     * Finder에서 프로젝트 폴더 열기
     */
    public function openExplorer()
    {
        header('Content-Type: application/json');
        
        $projectPath = $_POST['project_path'] ?? '';
        
        if (!is_dir($projectPath)) {
            echo json_encode(['success' => false, 'message' => 'Directory does not exist']);
            exit;
        }
        
        // macOS Finder 열기
        $command = sprintf('open %s', escapeshellarg($projectPath));
        $output = [];
        $returnCode = 0;
        
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Finder opened successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to open Finder: ' . implode(' ', $output)
            ]);
        }
        exit;
    }
    
    /**
     * 프로젝트 언어 통계 조회
     */
    public function getStats($id)
    {
        header('Content-Type: application/json');
        
        $project = $this->db->fetchOne("SELECT * FROM projects WHERE id = ?", [$id]);
        if (!$project) {
            echo json_encode(['success' => false, 'message' => 'Project not found']);
            exit;
        }
        
        // 최근 스캔 결과 조회
        $latestScan = $this->db->fetchOne("
            SELECT * FROM scans 
            WHERE project_id = ? AND status = 'completed' 
            ORDER BY completed_at DESC 
            LIMIT 1
        ", [$id]);
        
        if (!$latestScan) {
            // 스캔 데이터가 없으면 실시간으로 프로젝트 경로를 분석
            $projectPath = $project['root_path'] ?? $project['path'];
            $languageStats = $this->scanProjectLanguages($projectPath);
            
            echo json_encode([
                'success' => true,
                'project' => [
                    'id' => $project['id'],
                    'name' => $project['name'],
                    'path' => $projectPath
                ],
                'scan_info' => [
                    'scan_date' => null,
                    'total_files' => array_sum(array_column($languageStats, 'file_count')),
                    'total_loc' => 'Not scanned yet',
                    'execution_time' => 'Real-time analysis'
                ],
                'language_stats' => $languageStats,
                'summary' => [
                    'total_files' => array_sum(array_column($languageStats, 'file_count')),
                    'total_loc' => 'Run full scan for LOC',
                    'languages_count' => count($languageStats)
                ],
                'is_realtime' => true,
                'needs_full_scan' => true
            ]);
            exit;
        }
        
        // 언어별 통계 조회
        $langStats = $this->db->fetchAll("
            SELECT language, file_count, loc, comment_lines, blank_lines
            FROM scan_lang_stats 
            WHERE scan_id = ?
            ORDER BY loc DESC
        ", [$latestScan['id']]);
        
        // 총계 계산
        $totalFiles = array_sum(array_column($langStats, 'file_count'));
        $totalLoc = array_sum(array_column($langStats, 'loc'));
        
        // 언어별 비율 계산
        foreach ($langStats as &$stat) {
            $stat['file_percentage'] = $totalFiles > 0 ? round(($stat['file_count'] / $totalFiles) * 100, 1) : 0;
            $stat['loc_percentage'] = $totalLoc > 0 ? round(($stat['loc'] / $totalLoc) * 100, 1) : 0;
        }
        
        echo json_encode([
            'success' => true,
            'project' => [
                'id' => $project['id'],
                'name' => $project['name'],
                'path' => $project['root_path'] ?? $project['path']
            ],
            'scan_info' => [
                'scan_date' => $latestScan['completed_at'],
                'total_files' => $latestScan['total_files'],
                'total_loc' => $latestScan['total_loc'],
                'execution_time' => ($latestScan['execution_time_ms'] ?? 0) . 'ms'
            ],
            'language_stats' => $langStats,
            'summary' => [
                'total_files' => $totalFiles,
                'total_loc' => $totalLoc,
                'languages_count' => count($langStats)
            ],
            'is_realtime' => false
        ]);
        exit;
    }

    /**
     * C++ 엔진을 통한 실시간 LOC 측정
     */
    public function cppLocScan($id)
    {
        header('Content-Type: application/json');
        
        $project = $this->db->fetchOne("SELECT * FROM projects WHERE id = ?", [$id]);
        if (!$project) {
            echo json_encode(['success' => false, 'message' => 'Project not found']);
            exit;
        }

        $projectPath = $project['root_path'] ?? $project['path'];
        if (!is_dir($projectPath)) {
            echo json_encode(['success' => false, 'message' => 'Project directory does not exist']);
            exit;
        }

        try {
            // C++ 엔진 실행
            $cppEnginePath = dirname(__DIR__, 2) . '/cpp_engine/build/loc_scanner_engine';
            
            if (!file_exists($cppEnginePath)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'C++ LOC engine not found. Please build the engine first.',
                    'need_build' => true
                ]);
                exit;
            }

            // 절대 경로로 C++ 엔진 실행
            $command = escapeshellarg($cppEnginePath) . ' ' . escapeshellarg($projectPath) . ' 2>&1';
            $output = shell_exec($command);
            
            if ($output === null) {
                throw new Exception('Failed to execute C++ LOC scanner');
            }
            


            // C++ 엔진 출력 파싱 (SCAN_RESULT_START ~ SCAN_RESULT_END 형식)
            if (!preg_match('/SCAN_RESULT_START(.*?)SCAN_RESULT_END/s', $output, $matches)) {
                throw new Exception('Invalid C++ scanner output format: ' . $output);
            }
            
            $scanData = $matches[1];
            $lines = array_filter(array_map('trim', explode("\n", $scanData)));
            
            $result = [];
            $languageData = [];
            $inLanguageSection = false;
            
            foreach ($lines as $line) {
                if ($line === 'LANGUAGES_START') {
                    $inLanguageSection = true;
                    continue;
                } else if ($line === 'LANGUAGES_END') {
                    $inLanguageSection = false;
                    continue;
                }
                
                if ($inLanguageSection) {
                    // LANG:PHP|FILES:31|TOTAL:6805|CODE:5810|COMMENTS:218|BLANK:777
                    if (preg_match('/LANG:([^|]+)\|FILES:(\d+)\|TOTAL:(\d+)\|CODE:(\d+)\|COMMENTS:(\d+)\|BLANK:(\d+)/', $line, $langMatches)) {
                        $languageData[] = [
                            'language' => $langMatches[1],
                            'file_count' => (int)$langMatches[2],
                            'loc' => (int)$langMatches[4], // CODE lines
                            'comment_lines' => (int)$langMatches[5],
                            'blank_lines' => (int)$langMatches[6],
                            'total_lines' => (int)$langMatches[3]
                        ];
                    }
                } else {
                    // 기타 메타데이터 파싱
                    if (preg_match('/([^:]+):(.+)/', $line, $metaMatches)) {
                        $key = trim($metaMatches[1]);
                        $value = trim($metaMatches[2]);
                        
                        switch ($key) {
                            case 'TOTAL_FILES':
                                $result['total_files'] = (int)$value;
                                break;
                            case 'TOTAL_LOC':
                                $result['total_loc'] = (int)$value;
                                break;
                            case 'START_TIME':
                                $result['start_time'] = $value;
                                break;
                            case 'END_TIME':
                                $result['end_time'] = $value;
                                break;
                        }
                    }
                }
            }
            
            // 언어 통계 변환 및 비율 계산
            $languageStats = [];
            $totalFiles = $result['total_files'] ?? 0;
            $totalLoc = $result['total_loc'] ?? 0;
            
            foreach ($languageData as $lang) {
                $languageStats[] = [
                    'language' => $lang['language'],
                    'file_count' => $lang['file_count'],
                    'loc' => $lang['loc'],
                    'comment_lines' => $lang['comment_lines'],
                    'blank_lines' => $lang['blank_lines'],
                    'file_percentage' => $totalFiles > 0 ? round(($lang['file_count'] / $totalFiles) * 100, 1) : 0,
                    'loc_percentage' => $totalLoc > 0 ? round(($lang['loc'] / $totalLoc) * 100, 1) : 0
                ];
            }

            // LOC 기준으로 정렬
            usort($languageStats, function($a, $b) {
                return $b['loc'] - $a['loc'];
            });

            // 스캔 결과를 데이터베이스에 저장
            $scanId = $this->saveScanResults($id, $languageStats, $totalFiles, $totalLoc, 'C++');
            
            // 프로젝트의 스캔 횟수와 마지막 스캔 시간 업데이트
            $this->db->query("
                UPDATE projects 
                SET scan_count = (SELECT COUNT(*) FROM scans WHERE project_id = ? AND status = 'completed'),
                    last_scan = NOW()
                WHERE id = ?
            ", [$id, $id]);

            echo json_encode([
                'success' => true,
                'engine' => 'C++',
                'execution_time' => 'Fast',
                'scan_id' => $scanId,
                'language_stats' => $languageStats,
                'summary' => [
                    'total_files' => $totalFiles,
                    'total_loc' => $totalLoc,
                    'languages_count' => count($languageStats)
                ]
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'C++ LOC scan failed: ' . $e->getMessage(),
                'need_build' => !file_exists($cppEnginePath ?? ''),
                'requested_path' => $projectPath
            ]);
        }
        
        exit;
    }

    /**
     * 실시간으로 프로젝트 경로의 언어 분포 분석
     */
    private function scanProjectLanguages($projectPath)
    {
        if (!is_dir($projectPath)) {
            return [];
        }

        // 언어별 파일 확장자 매핑
        $languageExtensions = [
            'PHP' => ['php', 'phtml', 'php3', 'php4', 'php5', 'phps'],
            'JavaScript' => ['js', 'jsx', 'mjs'],
            'TypeScript' => ['ts', 'tsx'],
            'Python' => ['py', 'pyw', 'pyc', 'pyo', 'pyd'],
            'Java' => ['java'],
            'C' => ['c', 'h'],
            'C++' => ['cpp', 'cc', 'cxx', 'c++', 'hpp', 'hh', 'hxx'],
            'C#' => ['cs'],
            'Go' => ['go'],
            'Rust' => ['rs'],
            'Ruby' => ['rb', 'rbw'],
            'HTML' => ['html', 'htm', 'xhtml'],
            'CSS' => ['css', 'scss', 'sass', 'less'],
            'SQL' => ['sql', 'mysql', 'pgsql'],
            'Shell' => ['sh', 'bash', 'zsh', 'fish'],
            'JSON' => ['json'],
            'XML' => ['xml', 'xsl', 'xsd'],
            'YAML' => ['yml', 'yaml'],
            'Markdown' => ['md', 'markdown'],
            'Text' => ['txt', 'text'],
        ];

        $languageStats = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($projectPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                
                // 언어 찾기
                $language = 'Other';
                foreach ($languageExtensions as $lang => $extensions) {
                    if (in_array($extension, $extensions)) {
                        $language = $lang;
                        break;
                    }
                }

                // 통계 업데이트
                if (!isset($languageStats[$language])) {
                    $languageStats[$language] = [
                        'language' => $language,
                        'file_count' => 0,
                        'loc' => 'N/A',
                        'comment_lines' => 'N/A',
                        'blank_lines' => 'N/A',
                        'file_percentage' => 0,
                        'loc_percentage' => 0
                    ];
                }
                $languageStats[$language]['file_count']++;
            }
        }

        // 배열을 파일 수 기준으로 정렬
        uasort($languageStats, function($a, $b) {
            return $b['file_count'] - $a['file_count'];
        });

        // 비율 계산
        $totalFiles = array_sum(array_column($languageStats, 'file_count'));
        foreach ($languageStats as &$stat) {
            $stat['file_percentage'] = $totalFiles > 0 ? round(($stat['file_count'] / $totalFiles) * 100, 1) : 0;
        }

        return array_values($languageStats);
    }
    
    /**
     * 스캔 결과를 데이터베이스에 저장
     */
    private function saveScanResults($projectId, $languageStats, $totalFiles, $totalLoc, $engine = 'C++')
    {
        try {
            // 스캔 레코드 생성
            $this->db->query("
                INSERT INTO scans (project_id, started_at, completed_at, total_files, total_loc, status, engine_used, execution_time_ms)
                VALUES (?, NOW(), NOW(), ?, ?, 'completed', ?, 0)
            ", [$projectId, $totalFiles, $totalLoc, $engine]);
            
            $scanId = $this->db->lastInsertId();
            
            // 언어별 통계 저장
            foreach ($languageStats as $langStat) {
                $this->db->query("
                    INSERT INTO scan_lang_stats (scan_id, language, file_count, loc, comment_lines, blank_lines)
                    VALUES (?, ?, ?, ?, ?, ?)
                ", [
                    $scanId,
                    $langStat['language'],
                    $langStat['file_count'],
                    $langStat['loc'],
                    $langStat['comment_lines'] ?? 0,
                    $langStat['blank_lines'] ?? 0
                ]);
            }
            
            return $scanId;
        } catch (Exception $e) {
            error_log("Failed to save scan results: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 프로젝트 경로 존재 여부 확인 (AJAX)
     */
    public function checkPath()
    {
        error_log("checkPath method called");
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Method not POST: " . $_SERVER['REQUEST_METHOD']);
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $projectPath = $input['path'] ?? '';
        error_log("Received path: " . $projectPath);
        
        if (empty($projectPath)) {
            error_log("Empty path provided");
            echo json_encode(['success' => false, 'exists' => false, 'message' => '경로가 제공되지 않았습니다']);
            return;
        }
        
        // 경로 존재 여부 확인
        $exists = file_exists($projectPath) && is_dir($projectPath);
        $readable = $exists && is_readable($projectPath);
        error_log("Path exists: " . ($exists ? 'true' : 'false') . ", readable: " . ($readable ? 'true' : 'false'));
        
        echo json_encode([
            'success' => true,
            'exists' => $exists,
            'readable' => $readable,
            'path' => $projectPath,
            'message' => $exists 
                ? ($readable ? '경로가 존재하고 접근 가능합니다' : '경로는 존재하지만 접근 권한이 없습니다')
                : '경로가 존재하지 않습니다'
        ]);
    }
}