-- 4week 프로젝트: 실시간 센서 데이터 모니터링
-- 데이터베이스 및 테이블 생성

CREATE DATABASE IF NOT EXISTS sensor_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sensor_db;

CREATE TABLE IF NOT EXISTS sensor_data (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    sensor_id   VARCHAR(20)    NOT NULL,
    temperature DECIMAL(5, 2)  NOT NULL,
    humidity    DECIMAL(5, 2)  NOT NULL,
    pressure    DECIMAL(7, 2)  NOT NULL,
    timestamp   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sensor_id  (sensor_id),
    INDEX idx_timestamp  (timestamp)
) ENGINE=InnoDB;

-- 모니터링용 뷰: 최근 100건
CREATE OR REPLACE VIEW v_recent_sensor AS
SELECT *
FROM sensor_data
ORDER BY timestamp DESC
LIMIT 100;
