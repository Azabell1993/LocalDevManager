<?php
/**
 * Database Export View
 */
header('Content-Type: application/json');

if ($export_type === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $table_name . '_export.csv"');
    
    if (!empty($data)) {
        // CSV 헤더
        $headers = array_keys($data[0]);
        echo implode(',', array_map(function($h) { return '"' . str_replace('"', '""', $h) . '"'; }, $headers)) . "\n";
        
        // CSV 데이터
        foreach ($data as $row) {
            $csvRow = array_map(function($value) {
                return '"' . str_replace('"', '""', $value ?? '') . '"';
            }, array_values($row));
            echo implode(',', $csvRow) . "\n";
        }
    }
} elseif ($export_type === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $table_name . '_export.json"');
    
    echo json_encode([
        'table' => $table_name,
        'exported_at' => date('Y-m-d H:i:s'),
        'total_records' => count($data),
        'data' => $data
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} elseif ($export_type === 'sql') {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $table_name . '_export.sql"');
    
    echo "-- Database Export for table: {$table_name}\n";
    echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    
    if (!empty($data)) {
        $columns = array_keys($data[0]);
        $columnsList = '`' . implode('`, `', $columns) . '`';
        
        foreach ($data as $row) {
            $values = array_map(function($value) {
                return $value === null ? 'NULL' : "'" . str_replace("'", "''", $value) . "'";
            }, array_values($row));
            
            echo "INSERT INTO `{$table_name}` ({$columnsList}) VALUES (" . implode(', ', $values) . ");\n";
        }
    }
} else {
    // 에러 처리
    echo json_encode(['error' => 'Invalid export type']);
}
exit;
?>