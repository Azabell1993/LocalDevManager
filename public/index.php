<?php
/**
 * 메인 진입점
 * 사설망 개발 관리 프로그램
 */

// 세션 시작
session_start();

// 에러 리포팅
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 오토로더
spl_autoload_register(function ($class) {
    $directories = [
        __DIR__ . '/../app/Core/',
        __DIR__ . '/../app/Controllers/',
        __DIR__ . '/../app/Models/',
        __DIR__ . '/../app/Services/',
    ];

    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// 환경 설정 로드
Env::load(__DIR__ . '/../.env');

// .env 파일이 없으면 설치 페이지로 리다이렉트
$current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (!file_exists(__DIR__ . '/../.env') && !str_starts_with($current_path, '/install') && !str_starts_with($current_path, '/ajax/install')) {
    header('Location: /install');
    exit;
}

// 부트스트랩
Bootstrap::init();

// 설치 상태 확인 (설치 관련 경로가 아닌 경우에만)
if (!str_starts_with($current_path, '/install') && !str_starts_with($current_path, '/ajax/install')) {
    try {
        $db = Db::getInstance();
        
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
                // 테이블 확인 실패 시 계속 진행
                continue;
            }
        }
        
        // 주요 테이블이 모두 없으면 설치 페이지로 리다이렉트
        if ($existing_tables < 4) {
            header('Location: /install');
            exit;
        }
        
    } catch (Exception $e) {
        // 데이터베이스 연결 실패 시 설치 페이지로 리다이렉트
        header('Location: /install');
        exit;
    }
}

// 라우트 정의
// 설치 관련
Router::get('/install', 'InstallController@index');
Router::get('/ajax/install/check', 'InstallController@check');

// 대시보드
Router::get('/', 'DashboardController@index');
Router::get('/dashboard', 'DashboardController@index');
Router::post('/dashboard', 'DashboardController@handleAjax');
Router::get('/ajax/dashboard/layout', 'DashboardController@getDashboardLayout');
Router::post('/ajax/dashboard/layout', 'DashboardController@saveDashboardLayout');

// 프로젝트
Router::get('/projects', 'ProjectController@index');
Router::get('/projects/create', 'ProjectController@create');
Router::post('/projects/create', 'ProjectController@create');
Router::get('/projects/{id}/edit', 'ProjectController@edit');
Router::post('/projects/{id}/edit', 'ProjectController@edit');
Router::post('/projects/{id}/delete', 'ProjectController@delete');
Router::post('/projects/{id}/scan', 'ProjectController@scan');
Router::post('/projects/{id}/toggle', 'ProjectController@toggle');

// 프로젝트 API
Router::post('/ajax/projects/{id}/open-vscode', 'ProjectController@openVscode');
Router::post('/ajax/projects/open-explorer', 'ProjectController@openExplorer');
Router::get('/ajax/projects/{id}/stats', 'ProjectController@getStats');
Router::post('/ajax/projects/{id}/cpp-loc-scan', 'ProjectController@cppLocScan');
Router::post('/ajax/check-project-path', 'ProjectController@checkPath');
Router::post('/ajax/projects/{id}/cpp-loc-scan', 'ProjectController@cppLocScan');

// OS 관리
Router::get('/os', 'OsController@index');
Router::get('/os/create', 'OsController@create');
Router::post('/os/create', 'OsController@create');
Router::get('/os/{id}/edit', 'OsController@edit');
Router::post('/os/{id}/edit', 'OsController@edit');
Router::post('/os/{id}/delete', 'OsController@delete');
Router::post('/os/{id}/toggle', 'OsController@toggle');

// 에이전트 관리
Router::get('/agents', 'AgentController@index');
Router::get('/agents/create', 'AgentController@create');
Router::post('/agents/create', 'AgentController@create');
Router::get('/agents/{id}/edit', 'AgentController@edit');
Router::post('/agents/{id}/edit', 'AgentController@edit');
Router::post('/agents/{id}/delete', 'AgentController@delete');
Router::post('/agents/{id}/toggle', 'AgentController@toggle');

// 스캔 관리
Router::get('/scans', 'ScanController@index');
Router::get('/scans/create', 'ScanController@create');
Router::post('/scans/create', 'ScanController@create');
Router::get('/scans/{id}', 'ScanController@view');
Router::post('/scans/{id}/delete', 'ScanController@delete');

// AJAX 라우트
Router::get('/ajax/scan-status/{id}', 'ScanController@ajax_scan_status');
Router::post('/ajax/run-scan', 'ScanController@ajax_run_scan');
Router::get('/ajax/engine-status', 'ScanController@ajax_engine_status');
Router::post('/ajax/build-engine', 'ScanController@ajax_build_engine');
Router::post('/ajax/benchmark', 'ScanController@ajax_benchmark');

// 프로젝트 관리 AJAX
Router::post('/ajax/check-project-path', 'ProjectController@checkPath');

// 대시보드 레이아웃 관리
Router::post('/ajax/save-dashboard-layout', 'DashboardController@saveDashboardLayout');
Router::get('/ajax/get-dashboard-layout', 'DashboardController@getDashboardLayout');

// 데이터베이스 관리
Router::get('/db', 'DbAdminController@index');
Router::get('/db/table/{tableName}', 'DbAdminController@table');
Router::get('/db/query', 'DbAdminController@query');
Router::post('/db/query', 'DbAdminController@query');
Router::get('/db/export', 'DbAdminController@export');
Router::post('/db/export', 'DbAdminController@export');
Router::post('/db/vacuum', 'DbAdminController@vacuum');
Router::post('/db/execute-query', 'DbAdminController@executeQuery');

// 시스템 모니터링 API
Router::get('/ajax/system-info', 'DbAdminController@getSystemInfo');

// 정적 파일 처리 (개발용)
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg)$/', $requestUri)) {
    $filePath = __DIR__ . $requestUri;
    if (file_exists($filePath)) {
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
        ];

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

        header("Content-Type: {$mimeType}");
        readfile($filePath);
        exit;
    }
}

// 라우팅 실행
try {
    Router::dispatch();
} catch (Exception $e) {
    // 에러 처리
    http_response_code(500);
    echo '<h1>Internal Server Error</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    if (Env::get('APP_DEBUG', false)) {
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
}