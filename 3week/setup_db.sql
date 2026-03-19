-- IoT 모니터링 DB 초기 설정
-- 실행: sudo mysql < setup_db.sql

CREATE DATABASE IF NOT EXISTS iot_monitor
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE iot_monitor;

CREATE TABLE IF NOT EXISTS sensor_data (
    id          BIGINT AUTO_INCREMENT PRIMARY KEY,
    sensor_id   VARCHAR(20)  NOT NULL COMMENT '센서 ID (e.g. S001)',
    location    VARCHAR(50)  NOT NULL COMMENT '설치 위치',
    temperature DECIMAL(5,2) NOT NULL COMMENT '온도 (°C)',
    humidity    DECIMAL(5,2) NOT NULL COMMENT '습도 (%)',
    pressure    DECIMAL(7,2) NOT NULL COMMENT '기압 (hPa)',
    status      ENUM('normal','warning','critical') DEFAULT 'normal',
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sensor_id   (sensor_id),
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB;

-- 전용 유저 생성
CREATE USER IF NOT EXISTS 'iot_user'@'localhost' IDENTIFIED BY 'Iot@Pass123!';
GRANT ALL PRIVILEGES ON iot_monitor.* TO 'iot_user'@'localhost';
FLUSH PRIVILEGES;

SELECT 'DB 설정 완료!' AS result;
SHOW TABLES;
