<?php
/**
 * 부트스트랩 클래스
 */
class Bootstrap
{
    public static function init()
    {
        // 세션 시작
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // 타임존 설정
        $timezone = Env::get('APP_TIMEZONE', 'Asia/Seoul');
        date_default_timezone_set($timezone);
        
        // 오류 보고 설정
        if (Env::get('APP_DEBUG', false)) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
        
        // 뷰에 전역 데이터 공유
        View::share([
            'app_name' => Env::get('APP_NAME', 'Development Manager'),
            'csrf_field' => Csrf::field(),
        ]);
        
        // 플래시 메시지 정리 (다음 요청을 위해)
        register_shutdown_function(function() {
            if (isset($_SESSION['_flash_clear'])) {
                Helpers::clearFlash();
                unset($_SESSION['_flash_clear']);
            }
        });
        
        // 현재 요청 후 플래시 메시지 정리 예약
        $_SESSION['_flash_clear'] = true;
    }
    
    public static function handleException($e)
    {
        $message = $e->getMessage();
        
        if (Env::get('APP_DEBUG', false)) {
            $message .= "\n\nFile: " . $e->getFile() . "\nLine: " . $e->getLine();
            $message .= "\n\nTrace:\n" . $e->getTraceAsString();
        }
        
        http_response_code(500);
        
        if (headers_sent()) {
            echo "<div class='alert alert-danger'>" . nl2br(htmlspecialchars($message)) . "</div>";
        } else {
            View::render('layout.error', ['message' => $message]);
        }
    }
}