<?php

class InstallController {
    
    public function index() {
        // .env 파일 존재 여부 확인
        $envExists = file_exists(__DIR__ . '/../../.env');
        $dbConnected = false;
        $tablesExist = false;
        $installStatus = '';
        
        if ($envExists) {
            try {
                $db = Db::getInstance();
                $dbConnected = true;
                
                // 주요 테이블 존재 확인
                $tables = ['projects', 'scans', 'oses', 'agents'];
                $existing_tables = 0;
                
                foreach ($tables as $table) {
                    try {
                        $result = $db->fetchAll("SHOW TABLES LIKE ?", [$table]);
                        if (!empty($result)) {
                            $existing_tables++;
                        }
                    } catch (Exception $e) {
                        continue;
                    }
                }
                
                $tablesExist = ($existing_tables >= 4);
                
                if ($tablesExist) {
                    $installStatus = 'complete';
                } else {
                    $installStatus = 'needs_tables';
                }
                
            } catch (Exception $e) {
                $installStatus = 'db_error';
            }
        } else {
            $installStatus = 'no_env';
        }
        
        // 설치 페이지 표시 (레이아웃 없이)
        $viewPath = __DIR__ . '/../Views/install/index.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo "설치 페이지를 찾을 수 없습니다.";
        }
    }
    
    public function check() {
        // AJAX로 설치 상태 확인
        header('Content-Type: application/json');
        
        try {
            $db = Db::getInstance();
            
            // 주요 테이블 존재 확인
            $tables = ['projects', 'scans', 'oses', 'agents'];
            $existing_tables = 0;
            
            foreach ($tables as $table) {
                $result = $db->fetchAll("SHOW TABLES LIKE ?", [$table]);
                if (!empty($result)) {
                    $existing_tables++;
                }
            }
            
            $is_installed = ($existing_tables >= 4);
            
            echo json_encode([
                'installed' => $is_installed,
                'tables_count' => $existing_tables,
                'required_tables' => count($tables)
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'installed' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}