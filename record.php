<?php
$action = $_GET['action'] ?? '';

// 记录文件路径
$ls_file = '../ls.txt';
$win_file = '../win.txt';

header('Content-Type: application/json');

switch($action) {
    case 'record':
        $player = (int)$_GET['player'];
        $row = (int)$_GET['row'];
        $col = (int)$_GET['col'];
        
        // 记录格式: 玩家,行,列|玩家,行,列...
        file_put_contents($ls_file, "$player,$row,$col|", FILE_APPEND);
        break;
        
    case 'save_win':
        if(file_exists($ls_file)) {
            $data = file_get_contents($ls_file);
            file_put_contents($win_file, rtrim($data, '|').PHP_EOL, FILE_APPEND);
            file_put_contents($ls_file, '');
        }
        break;
        
    case 'clear':
        file_put_contents($ls_file, '');
        break;
}

echo json_encode(['status' => 'success']);
?>