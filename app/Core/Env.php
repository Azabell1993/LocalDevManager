<?php
/**
 * 환경 변수 로더
 */
class Env
{
    private static $vars = [];
    
    public static function load($path = null)
    {
        if ($path === null) {
            $path = __DIR__ . '/../../.env';
        }
        
        if (!file_exists($path)) {
            // .env 파일이 없으면 설치 모드로 간주하고 빈 설정으로 유지
            return;
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // 주석 무시
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            // KEY=VALUE 형식 파싱
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // 따옴표 제거
                if (($value[0] === '"' && $value[-1] === '"') || 
                    ($value[0] === "'" && $value[-1] === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                self::$vars[$key] = $value;
                $_ENV[$key] = $value;
            }
        }
    }
    
    public static function get($key, $default = null)
    {
        return self::$vars[$key] ?? $_ENV[$key] ?? $default;
    }
}