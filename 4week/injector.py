#!/usr/bin/env python3
"""
injector.py - 랜덤 센서 데이터 생성 및 MySQL 주입기
4week 프로젝트: 실시간 모니터링 시스템

사용법:
    pip install mysql-connector-python
    python3 injector.py
"""

import random
import time
import sys
from datetime import datetime

try:
    import mysql.connector
    from mysql.connector import Error
except ImportError:
    print("[ERROR] mysql-connector-python 패키지가 필요합니다.")
    print("        pip install mysql-connector-python")
    sys.exit(1)

# ── DB 연결 설정 ──────────────────────────────────────────────
DB_CONFIG = {
    "host":     "localhost",
    "user":     "root",        # 필요 시 변경
    "password": "1234",            # 필요 시 변경
    "database": "sensor_db",
}

# ── 센서 목록 ─────────────────────────────────────────────────
SENSORS = ["SENSOR-01", "SENSOR-02", "SENSOR-03"]

# ── 데이터 생성 범위 ──────────────────────────────────────────
TEMP_RANGE     = (15.0, 40.0)   # °C
HUMIDITY_RANGE = (30.0, 90.0)   # %
PRESSURE_RANGE = (980.0, 1050.0)  # hPa

# ── 주입 간격(초) ─────────────────────────────────────────────
INTERVAL = 2


def generate_sensor_data(sensor_id: str) -> dict:
    """센서 ID에 대한 랜덤 측정값을 생성합니다."""
    return {
        "sensor_id":   sensor_id,
        "temperature": round(random.uniform(*TEMP_RANGE), 2),
        "humidity":    round(random.uniform(*HUMIDITY_RANGE), 2),
        "pressure":    round(random.uniform(*PRESSURE_RANGE), 2),
        "timestamp":   datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
    }


def insert_data(cursor, data: dict) -> None:
    """데이터를 sensor_data 테이블에 삽입합니다."""
    sql = """
        INSERT INTO sensor_data (sensor_id, temperature, humidity, pressure, timestamp)
        VALUES (%(sensor_id)s, %(temperature)s, %(humidity)s, %(pressure)s, %(timestamp)s)
    """
    cursor.execute(sql, data)


def main() -> None:
    print("=" * 55)
    print("  랜덤 센서 데이터 주입기 시작")
    print(f"  대상 DB : {DB_CONFIG['host']} / {DB_CONFIG['database']}")
    print(f"  주입 간격: {INTERVAL}초")
    print("  종료하려면 Ctrl+C 를 누르세요.")
    print("=" * 55)

    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        print("[OK] MySQL 연결 성공\n")
    except Error as e:
        print(f"[ERROR] MySQL 연결 실패: {e}")
        print("  → sql/schema.sql 을 먼저 실행하여 DB를 생성하세요.")
        sys.exit(1)

    count = 0
    try:
        while True:
            for sensor_id in SENSORS:
                data = generate_sensor_data(sensor_id)
                insert_data(cursor, data)
                count += 1
                print(
                    f"[{data['timestamp']}] {sensor_id:10s} | "
                    f"Temp={data['temperature']:5.2f}°C | "
                    f"Hum={data['humidity']:5.2f}% | "
                    f"Press={data['pressure']:7.2f}hPa"
                )

            conn.commit()
            print(f"  → {len(SENSORS)}건 커밋 완료 (누적: {count}건)\n")
            time.sleep(INTERVAL)

    except KeyboardInterrupt:
        print(f"\n[INFO] 사용자 중단 — 총 {count}건 삽입 완료")
    except Error as e:
        print(f"[ERROR] DB 오류: {e}")
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()
            print("[OK] MySQL 연결 종료")


if __name__ == "__main__":
    main()
