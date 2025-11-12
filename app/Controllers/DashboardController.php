<?php
/**
 * 대시보드 컨트롤러
 */
class DashboardController
{
    private $db;
    
    public function __construct()
    {
        $this->db = Db::getInstance();
    }
    
    public function index()
    {

        
        // KPI 데이터
        $stats = [
            'total_projects' => $this->getTotalProjects(),
            'active_projects' => $this->getActiveProjects(),
            'total_os' => $this->getTotalOS(),
            'total_agents' => $this->getTotalAgents(),
            'total_scans' => $this->getTotalScans(),
            'total_loc' => $this->getTotalLOC(),
        ];
        
        // 최근 스캔 결과
        $recentScans = $this->getRecentScans();
        
        // LOC 트렌드 데이터 (최근 10개 스캔)
        $locTrends = $this->getLocTrends();
        
        // 활성 프로젝트 목록 (스캔용)
        $activeProjects = $this->db->fetchAll(
            "SELECT id, name FROM projects WHERE is_active = 1 ORDER BY name"
        );
        
        $view = new View();
        $view->render('dashboard/index', [
            'stats' => $stats,
            'recent_scans' => $recentScans,
            'active_projects' => $activeProjects,
            'page_title' => 'Dashboard'
        ]);
    }
    
    private function getTotalProjects()
    {
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM projects");
        return $result['count'];
    }
    
    private function getActiveProjects()
    {
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM projects WHERE is_active = 1");
        return $result['count'];
    }
    
    private function getTotalOS()
    {
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM oses");
        return $result['count'];
    }
    
    private function getTotalAgents()
    {
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM agents");
        return $result['count'];
    }
    
    private function getRecentScans()
    {
        return $this->db->fetchAll("
            SELECT s.*, p.name as project_name 
            FROM scans s 
            LEFT JOIN projects p ON s.project_id = p.id 
            ORDER BY s.started_at DESC 
            LIMIT 5
        ");
    }
    

    
    private function getLocTrends()
    {
        return $this->db->fetchAll("
            SELECT 
                s.started_at,
                s.total_loc,
                p.name as project_name
            FROM scans s
            LEFT JOIN projects p ON s.project_id = p.id
            WHERE s.status = 'success'
            ORDER BY s.started_at DESC
            LIMIT 10
        ");
    }
    

    
    private function getTotalScans()
    {
        $result = $this->db->fetchOne("SELECT COUNT(*) as total FROM scans");
        return $result ? $result['total'] : 0;
    }
    
    private function getTotalLOC()
    {
        $result = $this->db->fetchOne("SELECT SUM(total_loc) as total FROM scans WHERE status IN ('completed', 'success') AND total_loc > 0");
        return $result ? $result['total'] : 0;
    }
}