<?php
/**
 * 뷰 렌더링 관리자
 */
class View
{
    private static $data = [];
    private static $layout = 'layout.main';
    
    public static function render($view, $data = [])
    {
        self::$data = array_merge(self::$data, $data);
        
        // 뷰 파일 경로
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewPath)) {
            throw new Exception("뷰 파일을 찾을 수 없습니다: " . $viewPath);
        }
        
        // 데이터를 변수로 추출
        extract(self::$data);
        
        // 뷰 내용을 버퍼에 캡처
        ob_start();
        include $viewPath;
        $content = ob_get_clean();
        
        // 레이아웃에 포함하여 렌더링
        if (self::$layout) {
            $layoutPath = __DIR__ . '/../Views/' . str_replace('.', '/', self::$layout) . '.php';
            if (file_exists($layoutPath)) {
                include $layoutPath;
            } else {
                echo $content;
            }
        } else {
            echo $content;
        }
    }
    
    public static function share($key, $value = null)
    {
        if (is_array($key)) {
            self::$data = array_merge(self::$data, $key);
        } else {
            self::$data[$key] = $value;
        }
    }
    
    public static function escape($value)
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
    
    public static function setLayout($layout)
    {
        self::$layout = $layout;
    }
}