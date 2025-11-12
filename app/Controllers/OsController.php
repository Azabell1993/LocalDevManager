<?php
/**
 * OS 관리 컨트롤러
 */
class OsController
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
            $whereClause = " WHERE (name LIKE ? OR version LIKE ? OR arch LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // 총 개수 조회
        $totalQuery = "SELECT COUNT(*) as total FROM oses" . $whereClause;
        $totalResult = $this->db->fetchAll($totalQuery, $params);
        $total = $totalResult[0]['total'];
        $totalPages = ceil($total / $limit);
        
        // 데이터 조회
        $oses = $this->db->fetchAll("
            SELECT * FROM oses 
            $whereClause
            ORDER BY name ASC
            LIMIT $limit OFFSET $offset
        ", $params);
        
        View::render('os.index', [
            'oses' => $oses,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $totalPages,
                'search' => $search
            ],
            'page_title' => 'OS Management'
        ]);
    }
    
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: /os');
                exit;
            }
            
            $name = trim($_POST['name'] ?? '');
            $version = trim($_POST['version'] ?? '');
            $arch = trim($_POST['arch'] ?? '');
            $hostname = trim($_POST['hostname'] ?? '');
            $ip_address = trim($_POST['ip_address'] ?? '');
            $access_level = trim($_POST['access_level'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            // 유효성 검사
            $errors = [];
            if (empty($name)) {
                $errors[] = 'OS name is required';
            }
            // hostname, ip_address, access_level은 선택사항으로 변경
            
            // IP 주소 형식 검증
            if (!empty($ip_address) && !filter_var($ip_address, FILTER_VALIDATE_IP)) {
                $errors[] = 'Invalid IP address format';
            }
            
            // 중복 체크 (호스트명) - hostname이 있을 때만
            if (!empty($hostname)) {
                $existing = $this->db->fetchOne(
                    "SELECT id FROM oses WHERE hostname = ?", 
                    [$hostname]
                );
                if ($existing) {
                    $errors[] = 'Hostname already exists';
                }
            }
            
            // IP 주소 중복 체크 - ip_address가 있을 때만
            if (!empty($ip_address)) {
                $existing = $this->db->fetchOne(
                    "SELECT id FROM oses WHERE ip_address = ?", 
                    [$ip_address]
                );
                if ($existing) {
                    $errors[] = 'IP address already exists';
                }
            }
            
            if (!empty($errors)) {
                $_SESSION['error'] = implode('<br>', $errors);
                $_SESSION['form_data'] = $_POST;
                header('Location: /os/create');
                exit;
            }
            
            // OS 생성
            $this->db->query("
                INSERT INTO oses (name, version, arch, hostname, ip_address, access_level, description, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?)
            ", [
                $name, 
                $version ?: null, 
                $arch ?: null, 
                $hostname ?: null, 
                $ip_address ?: null, 
                $access_level ?: 'basic', 
                $description ?: null, 
                date('Y-m-d H:i:s')
            ]);
            
            $_SESSION['success'] = 'OS created successfully';
            header('Location: /os');
            exit;
        }
        
        $form_data = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']);
        
        View::render('os.create', [
            'form_data' => $form_data,
            'csrf_token' => Csrf::generate(),
            'page_title' => 'Create OS'
        ]);
    }
    
    public function edit($id)
    {
        $os = $this->db->fetchOne("SELECT * FROM oses WHERE id = ?", [$id]);
        if (!$os) {
            $_SESSION['error'] = 'OS not found';
            header('Location: /os');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: /os');
                exit;
            }
            
            $name = trim($_POST['name'] ?? '');
            $version = trim($_POST['version'] ?? '');
            $arch = trim($_POST['arch'] ?? '');
            $hostname = trim($_POST['hostname'] ?? '');
            $ip_address = trim($_POST['ip_address'] ?? '');
            $access_level = trim($_POST['access_level'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            // 유효성 검사
            $errors = [];
            if (empty($name)) {
                $errors[] = 'OS name is required';
            }
            if (empty($version)) {
                $errors[] = 'OS version is required';
            }
            
            // 중복 체크 (자기 자신 제외)
            $existing = $this->db->fetchOne(
                "SELECT id FROM oses WHERE name = ? AND version = ? AND id != ?", 
                [$name, $version, $id]
            );
            if ($existing) {
                $errors[] = 'OS with same name and version already exists';
            }
            
            if (!empty($errors)) {
                $_SESSION['error'] = implode('<br>', $errors);
                $os = array_merge($os, $_POST);
            } else {
                // OS 업데이트
                $this->db->query("
                    UPDATE oses 
                    SET name = ?, version = ?, arch = ?, hostname = ?, ip_address = ?, access_level = ?, description = ?, updated_at = ?
                    WHERE id = ?
                ", [$name, $version, $arch, $hostname, $ip_address, $access_level, $description, date('Y-m-d H:i:s'), $id]);
                
                $_SESSION['success'] = 'OS updated successfully';
                header('Location: /os');
                exit;
            }
        }
        
        View::render('os.edit', [
            'os' => $os,
            'csrf_token' => Csrf::generate(),
            'page_title' => 'Edit OS'
        ]);
    }
    
    public function delete($id)
    {
        $os = $this->db->fetchOne("SELECT * FROM oses WHERE id = ?", [$id]);
        if (!$os) {
            $_SESSION['error'] = 'OS not found';
            header('Location: /os');
            exit;
        }
        
        // OS 삭제
        $this->db->query("DELETE FROM oses WHERE id = ?", [$id]);
        
        $_SESSION['success'] = 'OS deleted successfully';
        header('Location: /os');
        exit;
    }
    
    public function toggle($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method';
            header('Location: /os');
            exit;
        }
        
        $os = $this->db->fetchOne("SELECT * FROM oses WHERE id = ?", [$id]);
        if (!$os) {
            $_SESSION['error'] = 'OS not found';
            header('Location: /os');
            exit;
        }
        
        // is_active 컬럼이 없다면 추가하거나, 다른 컬럼으로 대체
        // 여기서는 status 컬럼을 사용한다고 가정
        $currentStatus = $os['status'] ?? 'inactive';
        $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
        
        try {
            $this->db->query("
                UPDATE oses 
                SET status = ?, updated_at = ?
                WHERE id = ?
            ", [$newStatus, date('Y-m-d H:i:s'), $id]);
            
            $_SESSION['success'] = "OS status changed to {$newStatus}";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to toggle OS status: ' . $e->getMessage();
        }
        
        header('Location: /os');
        exit;
    }
}