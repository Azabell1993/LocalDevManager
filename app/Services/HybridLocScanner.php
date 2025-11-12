<?php
/**
 * 하이브리드 LOC 스캐너 - C++ 엔진 + PHP 폴백
 * Zabbix 스타일 고성능 네이티브 엔진
 */
class HybridLocScanner
{
    private $db;
    private $cppEngine;
    private $phpScanner; // 기존 PHP 스캐너를 폴백으로 사용
    
    public function __construct()
    {
        $this->db = Db::getInstance();
        $this->cppEngine = new CppLocEngine();
        $this->phpScanner = new LocScanner(); // 기존 PHP 스캐너
    }
    
    /**
     * C++ 엔진 상태 확인
     */
    public function getEngineStatus()
    {
        return [
            'cpp_available' => $this->cppEngine->isAvailable(),
            'cpp_version' => $this->cppEngine->getVersion(),
            'php_fallback' => true
        ];
    }
    
    /**
     * C++ 엔진 빌드
     */
    public function buildCppEngine()
    {
        return $this->cppEngine->buildEngine();
    }
    
    /**
     * 프로젝트 스캔 (C++ 엔진 우선, PHP 폴백)
     */
    public function scanProject($projectId)
    {
        $project = $this->db->fetchOne("SELECT * FROM projects WHERE id = ?", [$projectId]);
        if (!$project || !$project['is_active']) {
            throw new Exception('프로젝트를 찾을 수 없거나 비활성 상태입니다.');
        }
        
        if (!is_dir($project['root_path'])) {
            throw new Exception('프로젝트 경로가 존재하지 않습니다: ' . $project['root_path']);
        }
        
        // 스캔 시작
        $scanId = $this->createScanRecord($projectId);
        
        try {
            // C++ 엔진 사용 시도
            if ($this->cppEngine->isAvailable()) {
                $result = $this->scanWithCppEngine($project['root_path'], $scanId);
                $this->updateScanRecord($scanId, 'success', $result, 'cpp');
            } else {
                // PHP 폴백
                $result = $this->scanWithPhpEngine($project['root_path'], $scanId);
                $this->updateScanRecord($scanId, 'success', $result, 'php');
            }
            
            return $scanId;
            
        } catch (Exception $e) {
            // C++ 엔진 실패 시 PHP 폴백 시도
            if ($this->cppEngine->isAvailable()) {
                try {
                    error_log("C++ engine failed, falling back to PHP: " . $e->getMessage());
                    $result = $this->scanWithPhpEngine($project['root_path'], $scanId);
                    $this->updateScanRecord($scanId, 'success', $result, 'php_fallback');
                    return $scanId;
                } catch (Exception $phpError) {
                    $this->updateScanRecord($scanId, 'failed', null, null, $phpError->getMessage());
                    throw $phpError;
                }
            } else {
                $this->updateScanRecord($scanId, 'failed', null, null, $e->getMessage());
                throw $e;
            }
        }
    }
    
    /**
     * C++ 엔진을 사용한 스캔
     */
    private function scanWithCppEngine($projectPath, $scanId)
    {
        $startTime = microtime(true);
        
        $cppResult = $this->cppEngine->scanProject($projectPath);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // C++ 결과를 데이터베이스 형식으로 변환
        $totalLoc = $cppResult['total_loc'];
        
        // 언어별 통계 저장
        if (isset($cppResult['languages'])) {
            foreach ($cppResult['languages'] as $langData) {
                $this->db->query("
                    INSERT INTO scan_lang_stats 
                    (scan_id, language, file_count, loc) 
                    VALUES (?, ?, ?, ?)
                ", [
                    $scanId,
                    $langData['language'],
                    $langData['file_count'],
                    $langData['code_lines']
                ]);
            }
        }
        
        return [
            'total_files' => $cppResult['total_files'],
            'total_loc' => $totalLoc,
            'execution_time' => $executionTime,
            'engine' => 'cpp',
            'cpp_result' => $cppResult
        ];
    }
    
    /**
     * PHP 엔진을 사용한 스캔 (폴백)
     */
    private function scanWithPhpEngine($projectPath, $scanId)
    {
        $startTime = microtime(true);
        
        // LocScanner의 private scanDirectory 메서드를 복제해서 사용
        $results = $this->scanDirectoryPhp($projectPath);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // 언어별 통계 저장
        if (isset($results['languages'])) {
            foreach ($results['languages'] as $language => $data) {
                $this->db->query("
                    INSERT INTO scan_lang_stats 
                    (scan_id, language, file_count, loc) 
                    VALUES (?, ?, ?, ?)
                ", [
                    $scanId,
                    $language,
                    $data['files'],
                    $data['loc']
                ]);
            }
        }
        
        return [
            'total_files' => $results['total_files'],
            'total_loc' => $results['total_loc'],
            'execution_time' => $executionTime,
            'engine' => 'php'
        ];
    }
    
    /**
     * PHP 디렉토리 스캔 (LocScanner 복제)
     */
    private function scanDirectoryPhp($path)
    {
        $results = [];
        $totalFiles = 0;
        
        // 무시할 디렉토리
        $ignoreDirs = [
            '.git', 'node_modules', 'vendor', 'build', 'dist', 
            '.idea', '.vscode', '__pycache__', 'out', 'target'
        ];
        
        // 언어별 확장자 매핑
        $languageExtensions = [
            'C' => ['c', 'h'],
            'C++' => ['cpp', 'cc', 'cxx', 'hpp', 'hh', 'hxx'],
            'Java' => ['java'],
            'Python' => ['py'],
            'PHP' => ['php', 'phtml'],
            'JavaScript' => ['js', 'mjs'],
            'TypeScript' => ['ts', 'tsx'],
            'HTML' => ['html', 'htm'],
            'CSS' => ['css'],
            'SQL' => ['sql'],
            'Shell' => ['sh'],
            'Batch' => ['bat', 'cmd'],
            'Go' => ['go'],
            'Rust' => ['rs'],
            'Kotlin' => ['kt', 'kts'],
            'C#' => ['cs'],
            'Ruby' => ['rb'],
            'Swift' => ['swift'],
            'Dart' => ['dart'],
            'Scala' => ['scala'],
            'R' => ['r'],
            'Perl' => ['pl', 'pm'],
            'Lua' => ['lua'],
            'Vue' => ['vue'],
            'XML' => ['xml'],
            'JSON' => ['json'],
            'YAML' => ['yml', 'yaml'],
            'Markdown' => ['md', 'markdown'],
        ];
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            
            // 무시 디렉토리 체크
            $relativePath = str_replace($path, '', $file->getPath());
            $shouldIgnore = false;
            foreach ($ignoreDirs as $ignoreDir) {
                if (strpos($relativePath, DIRECTORY_SEPARATOR . $ignoreDir) !== false) {
                    $shouldIgnore = true;
                    break;
                }
            }
            if ($shouldIgnore) continue;
            
            // 언어 결정
            $extension = strtolower($file->getExtension());
            $language = null;
            foreach ($languageExtensions as $lang => $extensions) {
                if (in_array($extension, $extensions)) {
                    $language = $lang;
                    break;
                }
            }
            
            if (!$language) continue;
            
            // 파일 분석 (간단한 라인 카운트)
            try {
                $loc = count(file($file->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                
                if (!isset($results[$language])) {
                    $results[$language] = ['files' => 0, 'loc' => 0];
                }
                
                $results[$language]['files']++;
                $results[$language]['loc'] += $loc;
                $totalFiles++;
                
            } catch (Exception $e) {
                // 파일 읽기 실패는 무시하고 계속 진행
                continue;
            }
        }
        
        return [
            'languages' => $results,
            'total_files' => $totalFiles,
            'total_loc' => array_sum(array_column($results, 'loc'))
        ];
    }
    
    /**
     * 성능 벤치마크
     */
    public function benchmark($projectPath, $iterations = 3)
    {
        $results = [
            'cpp_engine' => null,
            'php_engine' => null,
            'performance_improvement' => null
        ];
        
        if ($this->cppEngine->isAvailable()) {
            $results['cpp_engine'] = $this->cppEngine->benchmark($projectPath, $iterations);
        }
        
        // PHP 엔진 벤치마크 (간단한 측정)
        $phpTimes = [];
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            // 직접 디렉토리 스캔 (DB 저장 제외)
            $this->scanDirectoryPhp($projectPath);
            $end = microtime(true);
            $phpTimes[] = ($end - $start) * 1000;
        }
        
        $results['php_engine'] = [
            'iterations' => $iterations,
            'times_ms' => $phpTimes,
            'avg_time_ms' => array_sum($phpTimes) / count($phpTimes),
            'min_time_ms' => min($phpTimes),
            'max_time_ms' => max($phpTimes)
        ];
        
        // 성능 개선 계산
        if ($results['cpp_engine'] && $results['php_engine']) {
            $cppAvg = $results['cpp_engine']['avg_time_ms'];
            $phpAvg = $results['php_engine']['avg_time_ms'];
            $improvement = (($phpAvg - $cppAvg) / $phpAvg) * 100;
            
            $results['performance_improvement'] = [
                'cpp_faster_by_percent' => round($improvement, 2),
                'speed_multiplier' => round($phpAvg / $cppAvg, 2)
            ];
        }
        
        return $results;
    }
    
    private function createScanRecord($projectId)
    {
        $this->db->query("
            INSERT INTO scans (project_id, status, started_at) 
            VALUES (?, 'running', ?)
        ", [$projectId, date('Y-m-d H:i:s')]);
        
        return $this->db->lastInsertId();
    }
    
    private function updateScanRecord($scanId, $status, $result = null, $engine = null, $errorMessage = null)
    {
        $totalLoc = $result ? $result['total_loc'] : 0;
        $completedAt = date('Y-m-d H:i:s');
        
        $this->db->query("
            UPDATE scans 
            SET status = ?, total_loc = ?, completed_at = ?, error_message = ?
            WHERE id = ?
        ", [$status, $totalLoc, $completedAt, $errorMessage, $scanId]);
        
        // 엔진 정보를 설정 테이블에 기록 (통계용)
        if ($engine) {
            // MySQL 호환을 위해 REPLACE INTO 사용 (key는 MySQL 예약어이므로 백틱 사용)
            $this->db->query("
                REPLACE INTO settings (`key`, `value`, `type`) 
                VALUES ('last_scan_engine', ?, 'string')
            ", [$engine]);
        }
    }
}