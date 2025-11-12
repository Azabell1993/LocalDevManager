<?php
/**
 * 헬퍼 클래스
 */
class Helpers
{
    /**
     * 리다이렉트
     */
    public static function redirect($path)
    {
        header("Location: " . $path);
        exit;
    }

    /**
     * 이전 입력값 가져오기
     */
    public static function old($key, $default = '')
    {
        return $_SESSION['old_input'][$key] ?? $default;
    }

    /**
     * 에러 메시지 가져오기
     */
    public static function errors($key = null)
    {
        if ($key === null) {
            return $_SESSION['errors'] ?? [];
        }
        return $_SESSION['errors'][$key] ?? [];
    }

    /**
     * 플래시 메시지
     */
    public static function flash($key, $value = null)
    {
        if ($value === null) {
            $result = $_SESSION['flash'][$key] ?? null;
            unset($_SESSION['flash'][$key]);
            return $result;
        } else {
            $_SESSION['flash'][$key] = $value;
        }
    }

    /**
     * 에러 설정
     */
    public static function setError($key, $message)
    {
        $_SESSION['errors'][$key][] = $message;
    }

    /**
     * 이전 입력값 저장
     */
    public static function setOldInput()
    {
        $_SESSION['old_input'] = $_POST;
    }

    /**
     * 플래시 데이터 삭제
     */
    public static function clearFlash()
    {
        unset($_SESSION['errors']);
        unset($_SESSION['old_input']);
        unset($_SESSION['flash']);
    }

    /**
     * 파일 크기 포맷팅
     */
    public static function formatFileSize($bytes)
    {
        if ($bytes == 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
    
    /**
     * 현재 라우트가 활성 상태인지 확인
     */
    public static function isActiveRoute($route, $additionalRoute = null)
    {
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // 정확히 일치하는 경우
        if ($currentPath === $route) {
            return true;
        }
        
        // 추가 라우트 체크 (홈페이지용)
        if ($additionalRoute && $currentPath === $additionalRoute) {
            return true;
        }
        
        // 하위 경로 체크 (예: /projects/create가 /projects에 포함)
        if ($route !== '/' && strpos($currentPath, $route . '/') === 0) {
            return true;
        }
        
        return false;
    }

    /**
     * 시간 경과 포맷팅
     */
    public static function timeAgo($datetime)
    {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return '방금 전';
        if ($time < 3600) return floor($time/60) . '분 전';
        if ($time < 86400) return floor($time/3600) . '시간 전';
        if ($time < 2592000) return floor($time/86400) . '일 전';
        if ($time < 31536000) return floor($time/2592000) . '월 전';
        return floor($time/31536000) . '년 전';
    }

    /**
     * URL 생성
     */
    public static function url($path = '')
    {
        $protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host . '/' . ltrim($path, '/');
    }
}