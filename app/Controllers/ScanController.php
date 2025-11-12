<?php
/**
 * 스캔 컨트롤러 - LOC 스캔 관리
 */
class ScanController
{
    private $db;
    private $scanner;
    
    public function __construct()
    {
        $this->db = Db::getInstance();
        $this->scanner = new HybridLocScanner();
    }
    
    public function index()
    {
        // 페이징 파라미터
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $limit = in_array($limit, [5, 10, 15, 30]) ? intval($limit) : 10;
        $offset = intval(($page - 1) * $limit);
        
        // 검색 및 필터 파라미터
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        
        // 기본 쿼리
        $whereClause = '';
        $params = [];
        
        if ($search) {
            $whereClause .= " WHERE (p.name LIKE ? OR s.status LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($status) {
            $whereClause .= $search ? " AND" : " WHERE";
            $whereClause .= " s.status = ?";
            $params[] = $status;
        }
        
        // 총 개수 조회
        $totalQuery = "SELECT COUNT(*) as total FROM scans s LEFT JOIN projects p ON s.project_id = p.id" . $whereClause;
        $totalResult = $this->db->fetchAll($totalQuery, $params);
        $total = $totalResult[0]['total'];
        $totalPages = ceil($total / $limit);
        
        // 데이터 조회
        $scans = $this->db->fetchAll("
            SELECT s.*, p.name as project_name 
            FROM scans s 
            LEFT JOIN projects p ON s.project_id = p.id 
            $whereClause
            ORDER BY s.started_at DESC
            LIMIT $limit OFFSET $offset
        ", $params);
        
        View::render('scans.index', [
            'scans' => $scans,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $totalPages,
                'search' => $search,
                'status' => $status
            ],
            'page_title' => 'Scan History'
        ]);
    }
    
    public function view($id)
    {
        $scan = $this->db->fetchOne("
            SELECT s.*, 
                   p.name as project_name, 
                   p.root_path as project_path,
                   s.started_at as start_time,
                   s.completed_at as end_time,
                   s.engine_used as engine_type,
                   CASE 
                       WHEN s.execution_time_ms > 0 
                       THEN CONCAT(ROUND(s.execution_time_ms / 1000, 2), 's')
                       ELSE 'N/A'
                   END as execution_time
            FROM scans s 
            LEFT JOIN projects p ON s.project_id = p.id 
            WHERE s.id = ?
        ", [$id]);
        
        if (!$scan) {
            $_SESSION['error'] = 'Scan not found';
            header('Location: /scans');
            exit;
        }
        
        // scan_lang_stats 테이블이 삭제되었으므로 기본 스캔 정보만 표시
        // TODO: 향후 언어별 상세 분석이 필요하면 새로운 구조로 구현
        $languageStats = [];
        
        // 프로젝트 정보도 함께 가져오기
        $project = $this->db->fetchOne("
            SELECT * FROM projects WHERE id = ?
        ", [$scan['project_id']]);
        
        View::render('scans.view', [
            'scan' => $scan,
            'project' => $project,
            'language_stats' => $languageStats,
            'page_title' => 'Scan Details'
        ]);
    }
    
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: /scans');
                exit;
            }
            
            $project_id = intval($_POST['project_id']);
            
            // 프로젝트 존재 확인
            $project = $this->db->fetchOne("SELECT * FROM projects WHERE id = ? AND is_active = 1", [$project_id]);
            if (!$project) {
                $_SESSION['error'] = 'Project not found or inactive';
                header('Location: /scans');
                exit;
            }
            
            try {
                $scanId = $this->scanner->scanProject($project_id);
                $_SESSION['success'] = 'Scan completed successfully';
                header('Location: /scans/' . $scanId);
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = 'Scan failed: ' . $e->getMessage();
                header('Location: /scans');
                exit;
            }
        }
        
        // 활성 프로젝트 목록
        $projects = $this->db->fetchAll("
            SELECT id, name FROM projects 
            WHERE is_active = 1 
            ORDER BY name
        ");
        
        View::render('scans.create', [
            'projects' => $projects,
            'csrf_token' => Csrf::generate(),
            'page_title' => 'New Scan'
        ]);
    }
    
    public function delete($id)
    {
        $scan = $this->db->fetchOne("SELECT * FROM scans WHERE id = ?", [$id]);
        if (!$scan) {
            $_SESSION['error'] = 'Scan not found';
            header('Location: /scans');
            exit;
        }
        
        // 트랜잭션 시작
        $this->db->beginTransaction();
        
        try {
            // 언어 통계 삭제
            $this->db->query("DELETE FROM scan_lang_stats WHERE scan_id = ?", [$id]);
            
            // 스캔 삭제
            $this->db->query("DELETE FROM scans WHERE id = ?", [$id]);
            
            $this->db->commit();
            
            $_SESSION['success'] = 'Scan deleted successfully';
            header('Location: /scans');
            exit;
        } catch (Exception $e) {
            $this->db->rollback();
            $_SESSION['error'] = 'Failed to delete scan: ' . $e->getMessage();
            header('Location: /scans');
            exit;
        }
    }
    
    public function ajax_scan_status($id)
    {
        header('Content-Type: application/json');
        
        $scan = $this->db->fetchOne("SELECT status, error_message FROM scans WHERE id = ?", [$id]);
        
        if (!$scan) {
            echo json_encode(['error' => 'Scan not found']);
            exit;
        }
        
        echo json_encode($scan);
        exit;
    }
    
    public function ajax_run_scan()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $project_id = intval($input['project_id'] ?? 0);
        
        if (!$project_id) {
            echo json_encode(['error' => 'Project ID required']);
            exit;
        }
        
        // 프로젝트 존재 확인
        $project = $this->db->fetchOne("SELECT * FROM projects WHERE id = ? AND is_active = 1", [$project_id]);
        if (!$project) {
            echo json_encode(['error' => 'Project not found or inactive']);
            exit;
        }
        
        try {
            $scanId = $this->scanner->scanProject($project_id);
            echo json_encode([
                'success' => true,
                'scan_id' => $scanId,
                'message' => 'Scan started successfully'
            ]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Scan failed: ' . $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * 엔진 상태 확인 (AJAX)
     */
    public function ajax_engine_status()
    {
        header('Content-Type: application/json');
        
        try {
            $status = $this->scanner->getEngineStatus();
            echo json_encode($status);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * C++ 엔진 빌드 (AJAX)
     */
    public function ajax_build_engine()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        try {
            $result = $this->scanner->buildCppEngine();
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * 성능 벤치마크 (AJAX)
     */
    public function ajax_benchmark()
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $project_id = intval($input['project_id'] ?? 0);
        
        if (!$project_id) {
            echo json_encode(['error' => 'Project ID required']);
            exit;
        }
        
        try {
            $project = $this->db->fetchOne("SELECT * FROM projects WHERE id = ?", [$project_id]);
            if (!$project) {
                throw new Exception('Project not found');
            }
            
            $result = $this->scanner->benchmark($project['root_path'], 3);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}