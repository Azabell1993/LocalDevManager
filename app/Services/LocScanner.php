<?php
/**
 * LOC 스캐너 서비스
 */
class LocScanner
{
    private $db;
    
    // 무시할 디렉토리
    private $ignoreDirs = [
        '.git', 'node_modules', 'vendor', 'build', 'dist', 
        '.idea', '.vscode', '__pycache__', 'out', 'target'
    ];
    
    // 언어별 확장자 매핑
    private $languageExtensions = [
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
    
    // 언어별 주석 패턴
    private $commentPatterns = [
        'C' => ['single' => '//', 'multi_start' => '/*', 'multi_end' => '*/'],
        'C++' => ['single' => '//', 'multi_start' => '/*', 'multi_end' => '*/'],
        'Java' => ['single' => '//', 'multi_start' => '/*', 'multi_end' => '*/'],
        'Python' => ['single' => '#'],
        'PHP' => ['single' => ['//', '#'], 'multi_start' => '/*', 'multi_end' => '*/'],
        'JavaScript' => ['single' => '//', 'multi_start' => '/*', 'multi_end' => '*/'],
        'TypeScript' => ['single' => '//', 'multi_start' => '/*', 'multi_end' => '*/'],
        'Shell' => ['single' => '#'],
        'Batch' => ['single' => 'REM'],
        'Go' => ['single' => '//', 'multi_start' => '/*', 'multi_end' => '*/'],
        'Rust' => ['single' => '//', 'multi_start' => '/*', 'multi_end' => '*/'],
        'Kotlin' => ['single' => '//', 'multi_start' => '/*', 'multi_end' => '*/'],
        'C#' => ['single' => '//', 'multi_start' => '/*', 'multi_end' => '*/'],
        'Ruby' => ['single' => '#'],
        'Swift' => ['single' => '//', 'multi_start' => '/*', 'multi_end' => '*/'],
    ];
    
    public function __construct()
    {
        $this->db = Db::getInstance();
    }
    
    public function scanProject($projectId)
    {
        // 프로젝트 정보 가져오기
        $project = $this->db->fetchOne(
            "SELECT * FROM projects WHERE id = ?", 
            [$projectId]
        );
        
        if (!$project) {
            throw new Exception("프로젝트를 찾을 수 없습니다.");
        }
        
        $rootPath = $project['root_path'];
        
        // 경로 존재 확인
        if (!is_dir($rootPath)) {
            throw new Exception("프로젝트 경로가 존재하지 않습니다: " . $rootPath);
        }
        
        // 스캔 레코드 생성
        $scanId = $this->createScanRecord($projectId);
        
        try {
            // 파일 스캔 실행
            $results = $this->scanDirectory($rootPath);
            
            // 결과 저장
            $this->saveResults($scanId, $results);
            
            // 스캔 완료 처리
            $this->completeScan($scanId, $results);
            
            return $scanId;
            
        } catch (Exception $e) {
            // 스캔 실패 처리
            $this->failScan($scanId, $e->getMessage());
            throw $e;
        }
    }
    
    private function createScanRecord($projectId)
    {
        $this->db->query(
            "INSERT INTO scans (project_id, started_at, status) VALUES (?, datetime('now'), 'running')",
            [$projectId]
        );
        
        return $this->db->lastInsertId();
    }
    
    private function scanDirectory($path)
    {
        $results = [];
        $totalFiles = 0;
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            
            // 무시 디렉토리 체크
            $relativePath = str_replace($path, '', $file->getPath());
            if ($this->shouldIgnoreDirectory($relativePath)) {
                continue;
            }
            
            // 언어 결정
            $language = $this->getFileLanguage($file->getExtension());
            if (!$language) {
                continue;
            }
            
            // 파일 분석
            try {
                $loc = $this->countLines($file->getPathname(), $language);
                
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
    
    private function shouldIgnoreDirectory($path)
    {
        foreach ($this->ignoreDirs as $ignoreDir) {
            if (strpos($path, DIRECTORY_SEPARATOR . $ignoreDir) !== false || 
                strpos($path, $ignoreDir . DIRECTORY_SEPARATOR) === 0) {
                return true;
            }
        }
        return false;
    }
    
    private function getFileLanguage($extension)
    {
        $extension = strtolower($extension);
        
        foreach ($this->languageExtensions as $language => $extensions) {
            if (in_array($extension, $extensions)) {
                return $language;
            }
        }
        
        return null;
    }
    
    private function countLines($filePath, $language)
    {
        $content = file_get_contents($filePath);
        
        if ($content === false) {
            throw new Exception("파일을 읽을 수 없습니다: " . $filePath);
        }
        
        // UTF-8 인코딩 확인
        if (!mb_check_encoding($content, 'UTF-8')) {
            // 바이너리 파일로 간주하고 건너뛰기
            return 0;
        }
        
        $lines = explode("\n", $content);
        $codeLines = 0;
        $inMultiLineComment = false;
        
        $patterns = $this->commentPatterns[$language] ?? [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // 빈 줄 무시
            if (empty($line)) {
                continue;
            }
            
            // 다중행 주석 처리
            if (isset($patterns['multi_start']) && isset($patterns['multi_end'])) {
                // 다중행 주석 시작
                if (!$inMultiLineComment && strpos($line, $patterns['multi_start']) !== false) {
                    $inMultiLineComment = true;
                    // 같은 줄에서 끝나는지 확인
                    if (strpos($line, $patterns['multi_end']) !== false) {
                        $inMultiLineComment = false;
                    }
                    continue;
                }
                
                // 다중행 주석 중
                if ($inMultiLineComment) {
                    if (strpos($line, $patterns['multi_end']) !== false) {
                        $inMultiLineComment = false;
                    }
                    continue;
                }
            }
            
            // 단일행 주석 처리
            if (isset($patterns['single'])) {
                $singlePatterns = is_array($patterns['single']) ? $patterns['single'] : [$patterns['single']];
                $isComment = false;
                
                foreach ($singlePatterns as $pattern) {
                    if (strpos($line, $pattern) === 0) {
                        $isComment = true;
                        break;
                    }
                }
                
                if ($isComment) {
                    continue;
                }
            }
            
            // 코드 라인으로 카운트
            $codeLines++;
        }
        
        return $codeLines;
    }
    
    private function saveResults($scanId, $results)
    {
        foreach ($results['languages'] as $language => $stats) {
            $this->db->query(
                "INSERT INTO scan_lang_stats (scan_id, language, file_count, loc) VALUES (?, ?, ?, ?)",
                [$scanId, $language, $stats['files'], $stats['loc']]
            );
        }
    }
    
    private function completeScan($scanId, $results)
    {
        $this->db->query(
            "UPDATE scans SET finished_at = datetime('now'), total_files = ?, total_loc = ?, status = 'success' WHERE id = ?",
            [$results['total_files'], $results['total_loc'], $scanId]
        );
    }
    
    private function failScan($scanId, $message)
    {
        $this->db->query(
            "UPDATE scans SET finished_at = datetime('now'), status = 'failed', message = ? WHERE id = ?",
            [$message, $scanId]
        );
    }
}