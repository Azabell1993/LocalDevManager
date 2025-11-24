<?php
/**
 * C++ LOC 스캔 엔진 래퍼
 * Zabbix 스타일 네이티브 엔진 인터페이스
 */
class CppLocEngine
{
    private $enginePath;
    private $isEngineAvailable = false;
    
    public function __construct()
    {
        $this->enginePath = dirname(dirname(__DIR__)) . '/cpp_engine/build/loc_scanner_engine';
        $this->checkEngine();
    }
    
    private function checkEngine()
    {
        $this->isEngineAvailable = file_exists($this->enginePath) && is_executable($this->enginePath);
        
        if (!$this->isEngineAvailable) {
            error_log("C++ LOC Engine not found or not executable: " . $this->enginePath);
        }
    }
    
    public function isAvailable()
    {
        return $this->isEngineAvailable;
    }
    
    public function buildEngine()
    {
        $cppDir = dirname(dirname($this->enginePath));
        $output = [];
        $returnCode = 0;
        
        // Install dependencies if needed
        exec("cd '$cppDir' && make install-deps 2>&1", $output, $returnCode);
        
        // Build the engine
        $output = [];
        exec("cd '$cppDir' && make clean && make 2>&1", $output, $returnCode);
        
        if ($returnCode === 0) {
            $this->checkEngine();
            return [
                'success' => true,
                'message' => 'C++ engine built successfully',
                'output' => implode("\n", $output)
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to build C++ engine',
                'output' => implode("\n", $output),
                'return_code' => $returnCode
            ];
        }
    }
    
    public function getVersion()
    {
        if (!$this->isEngineAvailable) {
            return null;
        }
        
        $command = json_encode(['command' => 'version']);
        $result = $this->executeCommand($command);
        
        if ($result && isset($result['version'])) {
            return $result;
        }
        
        return null;
    }
    
    public function scanProject($projectPath)
    {
        if (!$this->isEngineAvailable) {
            throw new Exception('C++ LOC Engine is not available. Please build it first.');
        }
        
        if (!is_dir($projectPath)) {
            throw new Exception('Project path does not exist: ' . $projectPath);
        }
        
        $realPath = realpath($projectPath);
        
        // 배치 모드로 엔진 실행
        $command = escapeshellarg($this->enginePath) . ' ' . escapeshellarg($realPath);
        
        $startTime = microtime(true);
        $output = shell_exec($command . ' 2>&1');
        $endTime = microtime(true);
        
        if ($output === null) {
            throw new Exception('Failed to execute C++ engine');
        }
        
        // 새로운 출력 형식 파싱
        $result = $this->parseEngineOutput($output);
        $result['php_execution_time'] = round(($endTime - $startTime) * 1000, 2) . 'ms';
        
        return $result;
    }
    
    /**
     * C++ 엔진 출력 파싱 (구분자 기반 형식)
     */
    private function parseEngineOutput($output)
    {
        $lines = explode("\n", trim($output));
        $result = [];
        $languages = [];
        $inLanguages = false;
        $inScanResult = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if ($line === 'SCAN_RESULT_START') {
                $inScanResult = true;
                continue;
            }
            
            if ($line === 'SCAN_RESULT_END') {
                break;
            }
            
            if (!$inScanResult) {
                continue;
            }
            
            if ($line === 'LANGUAGES_START') {
                $inLanguages = true;
                continue;
            }
            
            if ($line === 'LANGUAGES_END') {
                $inLanguages = false;
                continue;
            }
            
            if ($inLanguages && strpos($line, 'LANG:') === 0) {
                // 언어 데이터 파싱: LANG:Python|FILES:5|TOTAL:150|CODE:120|COMMENTS:20|BLANK:10
                $parts = explode('|', substr($line, 5)); // "LANG:" 제거
                $langData = [];
                
                foreach ($parts as $part) {
                    if (strpos($part, ':') !== false) {
                        list($key, $value) = explode(':', $part, 2);
                        $langData[strtolower($key)] = $value;
                    }
                }
                
                if (isset($langData['lang'])) {
                    $languages[] = [
                        'language' => $langData['lang'],
                        'file_count' => intval($langData['files'] ?? 0),
                        'code_lines' => intval($langData['code'] ?? 0),
                        'total_lines' => intval($langData['total'] ?? 0),
                        'comment_lines' => intval($langData['comments'] ?? 0),
                        'blank_lines' => intval($langData['blank'] ?? 0)
                    ];
                }
                continue;
            }
            
            // 다른 데이터 파싱
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $key = strtolower(str_replace('_', '', $key));
                
                switch ($key) {
                    case 'totalfiles':
                        $result['total_files'] = intval($value);
                        break;
                    case 'totalloc':
                        $result['total_loc'] = intval($value);
                        break;
                    case 'status':
                        $result['status'] = $value;
                        break;
                    case 'projectpath':
                        $result['project_path'] = $value;
                        break;
                    case 'starttime':
                        $result['start_time'] = $value;
                        break;
                    case 'endtime':
                        $result['end_time'] = $value;
                        break;
                    case 'errormessage':
                        $result['error_message'] = $value;
                        break;
                }
            }
        }
        
        $result['languages'] = $languages;
        return $result;
    }
    
    private function executeCommand($jsonCommand)
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ];
        
        $process = proc_open($this->enginePath, $descriptorSpec, $pipes);
        
        if (!is_resource($process)) {
            throw new Exception('Failed to start C++ engine process');
        }
        
        // Send command to engine
        fwrite($pipes[0], $jsonCommand . "\n");
        fclose($pipes[0]);
        
        // Read response
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $returnCode = proc_close($process);
        
        if ($returnCode !== 0) {
            throw new Exception("C++ engine failed with code $returnCode: $error");
        }
        
        if (empty($output)) {
            throw new Exception('No output from C++ engine');
        }
        
        $result = json_decode(trim($output), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from C++ engine: ' . json_last_error_msg());
        }
        
        return $result;
    }
    
    /**
     * 성능 비교를 위한 벤치마크
     */
    public function benchmark($projectPath, $iterations = 3)
    {
        $times = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $result = $this->scanProject($projectPath);
            $end = microtime(true);
            
            $times[] = ($end - $start) * 1000; // milliseconds
        }
        
        return [
            'iterations' => $iterations,
            'times_ms' => $times,
            'avg_time_ms' => array_sum($times) / count($times),
            'min_time_ms' => min($times),
            'max_time_ms' => max($times)
        ];
    }
}