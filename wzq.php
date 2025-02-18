<?php
header('Content-Type: application/json');

// 学习增强版AI
function findBestMove($board) {
    // 加载历史胜利模式
    $winPatterns = loadWinPatterns();

    $bestScore = -PHP_INT_MAX;
    $bestMoves = [];

    // 先检查胜利机会
    for ($i = 0; $i < 15; $i++) {
        for ($j = 0; $j < 15; $j++) {
            if ($board[$i][$j] == 0) {
                // 检查是否直接胜利
                $board[$i][$j] = 2;
                if (checkWin($board, $i, $j, 2)) {
                    return [$i, $j];
                }
                $board[$i][$j] = 0;
            }
        }
    }

    // 检查玩家即将胜利的情况
    for ($i = 0; $i < 15; $i++) {
        for ($j = 0; $j < 15; $j++) {
            if ($board[$i][$j] == 0) {
                $board[$i][$j] = 1;
                if (checkWin($board, $i, $j, 1)) {
                    return [$i, $j]; // 必须阻挡
                }
                $board[$i][$j] = 0;
            }
        }
    }

    // 深度评估
    $scores = [];
    for ($i = 0; $i < 15; $i++) {
        for ($j = 0; $j < 15; $j++) {
            if ($board[$i][$j] == 0) {
                $score = evaluatePosition($board, $i, $j, 2); // AI视角
                $score += evaluatePosition($board, $i, $j, 1) * 0.8; // 对手威胁

                // 增加中心区域权重
                $distance = abs(7 - $i) + abs(7 - $j);
                $score += (14 - $distance) * 2;

                // 第二层预测
                $board[$i][$j] = 2;
                $score += predictOpponentMove($board, 1);
                $board[$i][$j] = 0;

                // 增加历史模式匹配得分
                $score += matchHistoryPatterns($i, $j, $winPatterns) * 300;

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMoves = [[$i, $j]];
                } elseif ($score == $bestScore) {
                    $bestMoves[] = [$i, $j];
                }
            }
        }
    }

    return $bestMoves[array_rand($bestMoves)];
}

// 预测对手最佳回应
function predictOpponentMove($board, $depth) {
    $maxScore = -PHP_INT_MAX;
    for ($i = 0; $i < 15; $i++) {
        for ($j = 0; $j < 15; $j++) {
            if ($board[$i][$j] == 0) {
                $score = evaluatePosition($board, $i, $j, 1);
                if ($score > $maxScore) {
                    $maxScore = $score;
                }
            }
        }
    }
    return -$maxScore * 0.5; // 负值表示对手优势
}

// 增强版棋型评估
function evaluatePosition($board, $row, $col, $player) {
    $score = 0;
    $directions = [[0,1], [1,0], [1,1], [1,-1]];

    foreach ($directions as [$dx, $dy]) {
        // 检测五个主要棋型
        $patterns = [
            'FIVE' => [2 => 100000, 1 => 100000],
            'LIVE_FOUR' => [2 => 10000, 1 => 5000],
            'CHONG_FOUR' => [2 => 1000, 1 => 800],
            'LIVE_THREE' => [2 => 500, 1 => 400],
            'SLEEP_THREE' => [2 => 100, 1 => 80],
            'LIVE_TWO' => [2 => 50, 1 => 30]
        ];

        // 当前方向分析
        [$forward, $backward] = scanDirection($board, $row, $col, $dx, $dy, $player);

        // 组合棋型判断
        $total = 1 + $forward['count'] + $backward['count'];
        $space = $forward['space'] + $backward['space'];

        if ($total >= 5) {
            $score += $patterns['FIVE'][$player];
        } else {
            // 活四判断
            if ($total == 4 && $space >= 2) {
                $score += $patterns['LIVE_FOUR'][$player];
            }
            // 冲四判断
            elseif ($total == 4 && $space == 1) {
                $score += $patterns['CHONG_FOUR'][$player];
            }
            // 活三判断
            elseif ($total == 3 && $space >= 2) {
                $score += $patterns['LIVE_THREE'][$player];
            }
            // 眠三判断
            elseif ($total == 3 && $space == 1) {
                $score += $patterns['SLEEP_THREE'][$player];
            }
            // 活二判断
            elseif ($total == 2 && $space >= 2) {
                $score += $patterns['LIVE_TWO'][$player];
            }
        }
    }

    return $score;
}

// 方向扫描函数
function scanDirection($board, $row, $col, $dx, $dy, $player) {
    $forward = ['count' => 0, 'space' => 0];
    $backward = ['count' => 0, 'space' => 0];

    // 正向扫描
    $x = $row + $dx;
    $y = $col + $dy;
    while ($x >= 0 && $x < 15 && $y >= 0 && $y < 15) {
        if ($board[$x][$y] == $player) {
            $forward['count']++;
        } else {
            if ($board[$x][$y] == 0) $forward['space']++;
            break;
        }
        $x += $dx;
        $y += $dy;
    }

    // 反向扫描
    $x = $row - $dx;
    $y = $col - $dy;
    while ($x >= 0 && $x < 15 && $y >= 0 && $y < 15) {
        if ($board[$x][$y] == $player) {
            $backward['count']++;
        } else {
            if ($board[$x][$y] == 0) $backward['space']++;
            break;
        }
        $x -= $dx;
        $y -= $dy;
    }

    return [$forward, $backward];
}

// 胜利检查函数
function checkWin($board, $row, $col, $player) {
    $directions = [[0,1], [1,0], [1,1], [1,-1]];
    foreach ($directions as [$dx, $dy]) {
        $count = 1;
        // 正向检测
        $x = $row + $dx;
        $y = $col + $dy;
        while ($x >= 0 && $x < 15 && $y >= 0 && $y < 15 && $board[$x][$y] == $player) {
            $count++;
            $x += $dx;
            $y += $dy;
        }
        // 反向检测
        $x = $row - $dx;
        $y = $col - $dy;
        while ($x >= 0 && $x < 15 && $y >= 0 && $y < 15 && $board[$x][$y] == $player) {
            $count++;
            $x -= $dx;
            $y -= $dy;
        }
        if ($count >= 5) return true;
    }
    return false;
}

// 加载胜利模式
function loadWinPatterns() {
    $patterns = [];
    $winFile = 'win.txt';

    if(file_exists($winFile)) {
        $lines = file($winFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach($lines as $line) {
            $moves = explode('|', $line);
            $patterns[] = array_map(function($move) {
                return explode(',', $move);
            }, $moves);
        }
    }
    return $patterns;
}

// 模式匹配
function matchHistoryPatterns($row, $col, $patterns) {
    $matchScore = 0;

    foreach($patterns as $pattern) {
        foreach($pattern as $index => $move) {
            // 检查当前棋盘是否符合历史模式的前几步
            if($index % 2 == 0 && $row == $move[1] && $col == $move[2]) {
                $similarity = checkPatternSimilarity($pattern, $index);
                $matchScore += $similarity * (1 / ($index + 1));
            }
        }
    }

    return $matchScore;
}

// 检查模式相似度
function checkPatternSimilarity($pattern, $currentIndex) {
    global $board;

    $similar = 0;
    for($i=0; $i<=$currentIndex; $i++) {
        $p = $pattern[$i];
        if($board[$p[1]][$p[2]] == $p[0]) {
            $similar++;
        }
    }
    return $similar / ($currentIndex + 1);
}

$input = json_decode(file_get_contents('php://input'), true);
$board = $input['board'];
$move = findBestMove($board);

echo json_encode([
    'row' => $move[0],
    'col' => $move[1]
]);
?>