<?php
/**
 * IoT 센서 데이터 JSON API
 * Ajax 요청으로 대시보드에서 주기적으로 호출됩니다.
 *
 * GET /iot/api.php?action=latest   : 센서별 최신 1건
 * GET /iot/api.php?action=history  : 최근 N분 시계열 (sensor_id 필터 가능)
 * GET /iot/api.php?action=stats    : 센서별 통계 (평균·최대·최소)
 * GET /iot/api.php?action=count    : 총 레코드 수
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ── DB 접속 정보 ─────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'iot_user');
define('DB_PASS', 'Iot@Pass123!');
define('DB_NAME', 'iot_monitor');

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ── 라우팅 ───────────────────────────────────────────────────────
$action = $_GET['action'] ?? 'latest';

try {
    $pdo = get_pdo();

    switch ($action) {

        // 센서별 최신 데이터 1건씩
        case 'latest':
            $sql = "
                SELECT d.*
                FROM sensor_data d
                INNER JOIN (
                    SELECT sensor_id, MAX(recorded_at) AS max_time
                    FROM sensor_data
                    GROUP BY sensor_id
                ) t ON d.sensor_id = t.sensor_id AND d.recorded_at = t.max_time
                ORDER BY d.sensor_id
            ";
            $rows = $pdo->query($sql)->fetchAll();
            json_response(['status' => 'ok', 'data' => $rows, 'count' => count($rows)]);

        // 최근 N분 시계열 데이터 (기본 10분)
        case 'history':
            $minutes   = min((int)($_GET['minutes'] ?? 10), 60);
            $sensor_id = isset($_GET['sensor_id']) ? trim($_GET['sensor_id']) : null;
            if ($sensor_id) {
                $sql = "
                    SELECT sensor_id, location, temperature, humidity, pressure, status, recorded_at
                    FROM sensor_data
                    WHERE recorded_at >= NOW() - INTERVAL :min MINUTE
                      AND sensor_id = :sid
                    ORDER BY recorded_at
                    LIMIT 1000
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':min', $minutes, PDO::PARAM_INT);
                $stmt->bindValue(':sid', $sensor_id);
            } else {
                $sql = "
                    SELECT sensor_id, location, temperature, humidity, pressure, status, recorded_at
                    FROM sensor_data
                    WHERE recorded_at >= NOW() - INTERVAL :min MINUTE
                    ORDER BY sensor_id, recorded_at
                    LIMIT 1000
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':min', $minutes, PDO::PARAM_INT);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll();
            json_response(['status' => 'ok', 'data' => $rows, 'minutes' => $minutes]);

        // 센서별 통계 (최근 1시간)
        case 'stats':
            $sql = "
                SELECT
                    sensor_id,
                    location,
                    ROUND(AVG(temperature), 2) AS temp_avg,
                    ROUND(MAX(temperature), 2) AS temp_max,
                    ROUND(MIN(temperature), 2) AS temp_min,
                    ROUND(AVG(humidity),    2) AS hum_avg,
                    ROUND(MAX(humidity),    2) AS hum_max,
                    ROUND(MIN(humidity),    2) AS hum_min,
                    COUNT(*) AS total_readings,
                    SUM(status = 'warning')  AS warning_count,
                    SUM(status = 'critical') AS critical_count
                FROM sensor_data
                WHERE recorded_at >= NOW() - INTERVAL 1 HOUR
                GROUP BY sensor_id, location
                ORDER BY sensor_id
            ";
            $rows = $pdo->query($sql)->fetchAll();
            json_response(['status' => 'ok', 'data' => $rows]);

        // 총 레코드 수
        case 'count':
            $row = $pdo->query("SELECT COUNT(*) AS total FROM sensor_data")->fetch();
            json_response(['status' => 'ok', 'total' => (int)$row['total']]);

        default:
            json_response(['status' => 'error', 'message' => '알 수 없는 액션'], 400);
    }

} catch (PDOException $e) {
    json_response(['status' => 'error', 'message' => $e->getMessage()], 500);
}
