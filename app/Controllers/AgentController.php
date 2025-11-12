<?php
/**
 * 에이전트 관리 컨트롤러
 */
class AgentController
{
    private $db;
    
    public function __construct()
    {
        $this->db = Db::getInstance();
    }
    
    public function index()
    {
        // 페이징 파라미터
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $limit = in_array($limit, [5, 10, 15, 30]) ? intval($limit) : 10;
        $offset = intval(($page - 1) * $limit);
        
        // 검색 파라미터
        $search = $_GET['search'] ?? '';
        
        // 기본 쿼리
        $whereClause = '';
        $params = [];
        
        if ($search) {
            $whereClause = " WHERE (name LIKE ? OR version LIKE ? OR notes LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // 총 개수 조회
        $totalQuery = "SELECT COUNT(*) as total FROM agents" . $whereClause;
        $totalResult = $this->db->fetchAll($totalQuery, $params);
        $total = $totalResult[0]['total'];
        $totalPages = ceil($total / $limit);
        
        // 데이터 조회
        $agents = $this->db->fetchAll("
            SELECT a.*, o.name as os_name, o.version as os_version 
            FROM agents a
            LEFT JOIN oses o ON a.os_id = o.id
            $whereClause
            ORDER BY a.name ASC
            LIMIT $limit OFFSET $offset
        ", $params);
        
        View::render('agents.index', [
            'agents' => $agents,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $totalPages,
                'search' => $search
            ],
            'page_title' => 'Agent Management'
        ]);
    }
    
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: /agents');
                exit;
            }
            
            $name = trim($_POST['name'] ?? '');
            $version = trim($_POST['version'] ?? '');
            $os_id = !empty($_POST['os_id']) ? intval($_POST['os_id']) : null;
            $notes = trim($_POST['notes'] ?? '');
            
            // 유효성 검사
            $errors = [];
            if (empty($name)) {
                $errors[] = 'Agent name is required';
            }
            
            // 중복 체크 (이름 + 버전 조합)
            $existing = $this->db->fetchOne(
                "SELECT id FROM agents WHERE name = ? AND version = ?", 
                [$name, $version]
            );
            if ($existing) {
                $errors[] = 'Agent with same name and version already exists';
            }
            
            if (!empty($errors)) {
                $_SESSION['error'] = implode('<br>', $errors);
                $_SESSION['form_data'] = $_POST;
                header('Location: /agents/create');
                exit;
            }
            
            // 에이전트 생성
            $this->db->query("
                INSERT INTO agents (name, version, os_id, notes, created_at) 
                VALUES (?, ?, ?, ?, ?)
            ", [$name, $version, $os_id, $notes, date('Y-m-d H:i:s')]);
            
            $_SESSION['success'] = 'Agent created successfully';
            header('Location: /agents');
            exit;
        }
        
        $form_data = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']);
        
        // OS 목록 조회
        $os_list = $this->db->fetchAll("SELECT id, name, version, arch FROM oses ORDER BY name ASC");
        
        View::render('agents.create', [
            'form_data' => $form_data,
            'os_list' => $os_list,
            'csrf_token' => Csrf::generate(),
            'page_title' => 'Create Agent'
        ]);
    }
    
    public function edit($id)
    {
        $agent = $this->db->fetchOne("SELECT * FROM agents WHERE id = ?", [$id]);
        if (!$agent) {
            $_SESSION['error'] = 'Agent not found';
            header('Location: /agents');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: /agents');
                exit;
            }
            
            $name = trim($_POST['name'] ?? '');
            $version = trim($_POST['version'] ?? '');
            $os_id = !empty($_POST['os_id']) ? intval($_POST['os_id']) : null;
            $notes = trim($_POST['notes'] ?? '');
            
            // 유효성 검사
            $errors = [];
            if (empty($name)) {
                $errors[] = 'Agent name is required';
            }
            
            // 중복 체크 (자기 자신 제외)
            $existing = $this->db->fetchOne(
                "SELECT id FROM agents WHERE name = ? AND version = ? AND id != ?", 
                [$name, $version, $id]
            );
            if ($existing) {
                $errors[] = 'Agent with same name and version already exists';
            }
            
            if (!empty($errors)) {
                $_SESSION['error'] = implode('<br>', $errors);
                $agent = array_merge($agent, $_POST);
            } else {
                // 에이전트 업데이트
                $this->db->query("
                    UPDATE agents 
                    SET name = ?, version = ?, os_id = ?, notes = ?, updated_at = ?
                    WHERE id = ?
                ", [$name, $version, $os_id, $notes, date('Y-m-d H:i:s'), $id]);
                
                $_SESSION['success'] = 'Agent updated successfully';
                header('Location: /agents');
                exit;
            }
        }
        
        // OS 목록 조회
        $os_list = $this->db->fetchAll("SELECT id, name, version, arch FROM oses ORDER BY name ASC");
        
        View::render('agents.edit', [
            'agent' => $agent,
            'os_list' => $os_list,
            'csrf_token' => Csrf::generate(),
            'page_title' => 'Edit Agent'
        ]);
    }
    
    public function delete($id)
    {
        $agent = $this->db->fetchOne("SELECT * FROM agents WHERE id = ?", [$id]);
        if (!$agent) {
            $_SESSION['error'] = 'Agent not found';
            header('Location: /agents');
            exit;
        }
        
        // 에이전트 삭제
        $this->db->query("DELETE FROM agents WHERE id = ?", [$id]);
        
        $_SESSION['success'] = 'Agent deleted successfully';
        header('Location: /agents');
        exit;
    }
    
    public function toggle($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method';
            header('Location: /agents');
            exit;
        }
        
        $agent = $this->db->fetchOne("SELECT * FROM agents WHERE id = ?", [$id]);
        if (!$agent) {
            $_SESSION['error'] = 'Agent not found';
            header('Location: /agents');
            exit;
        }
        
        // status 컬럼이 있는지 확인하고 토글
        $currentStatus = $agent['status'] ?? 'inactive';
        $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
        
        try {
            $this->db->query("
                UPDATE agents 
                SET status = ?, updated_at = ?
                WHERE id = ?
            ", [$newStatus, date('Y-m-d H:i:s'), $id]);
            
            $_SESSION['success'] = "Agent status changed to {$newStatus}";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to toggle agent status: ' . $e->getMessage();
        }
        
        header('Location: /agents');
        exit;
    }
}