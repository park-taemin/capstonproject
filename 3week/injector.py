#!/usr/bin/env python3
"""
IoT 센서 데이터 시뮬레이터
MySQL iot_monitor.sensor_data 테이블에 가상 센서 데이터를 주기적으로 삽입합니다.

사용법:
    python3 injector.py              # 기본 (2초 간격, 무한 루프)
    python3 injector.py --interval 5 # 5초 간격
    python3 injector.py --count 100  # 100건 삽입 후 종료
"""

import time
import random
import argparse
import signal
import sys
from datetime import datetime

try:
    import mysql.connector
except ImportError:
    print("[ERROR] mysql-connector-python 패키지가 없습니다.")
    print("        실행: pip3 install mysql-connector-python")
    sys.exit(1)

# ── DB 접속 정보 ────────────────────────────────────────────────
DB_CONFIG = {
    "host":     "localhost",
    "user":     "iot_user",
    "password": "Iot@Pass123!",
    "database": "iot_monitor",
}

# ── 센서 정의 ────────────────────────────────────────────────────
SENSORS = [
    {"id": "S001", "location": "서버실 A",    "temp_base": 22.0, "hum_base": 45.0, "pres_base": 1013.0},
    {"id": "S002", "location": "서버실 B",    "temp_base": 23.5, "hum_base": 48.0, "pres_base": 1012.0},
    {"id": "S003", "location": "사무실 1층",  "temp_base": 24.0, "hum_base": 55.0, "pres_base": 1011.5},
    {"id": "S004", "location": "사무실 2층",  "temp_base": 25.0, "hum_base": 52.0, "pres_base": 1010.8},
    {"id": "S005", "location": "외부 옥상",   "temp_base": 10.0, "hum_base": 70.0, "pres_base": 1008.0},
]

INSERT_SQL = """
    INSERT INTO sensor_data (sensor_id, location, temperature, humidity, pressure, status)
    VALUES (%s, %s, %s, %s, %s, %s)
"""

running = True

def signal_handler(sig, frame):
    global running
    print("\n[INFO] 중지 신호 수신. 종료 중...")
    running = False

def generate_reading(sensor: dict) -> tuple:
    """센서 한 개의 랜덤 측정값을 생성합니다."""
    # 가끔 이상값(anomaly) 발생
    anomaly = random.random() < 0.05   # 5% 확률
    spike   = random.random() < 0.02   # 2% 확률 (critical)

    temp = round(sensor["temp_base"] + random.uniform(-2.0, 2.0) + (8 if anomaly else 0), 2)
    hum  = round(sensor["hum_base"]  + random.uniform(-5.0, 5.0) + (20 if spike else 0), 2)
    pres = round(sensor["pres_base"] + random.uniform(-3.0, 3.0), 2)

    # 범위 클램핑
    temp = max(-40.0, min(85.0, temp))
    hum  = max(0.0,   min(100.0, hum))
    pres = max(900.0, min(1100.0, pres))

    # 상태 판단
    if temp > 35 or hum > 90:
        status = "critical"
    elif temp > 28 or hum > 75:
        status = "warning"
    else:
        status = "normal"

    return (sensor["id"], sensor["location"], temp, hum, pres, status)


def run(interval: float, max_count: int):
    global running
    signal.signal(signal.SIGINT,  signal_handler)
    signal.signal(signal.SIGTERM, signal_handler)

    print("=" * 60)
    print("  IoT 센서 데이터 인젝터 시작")
    print(f"  DB     : {DB_CONFIG['database']}@{DB_CONFIG['host']}")
    print(f"  센서   : {len(SENSORS)}개")
    print(f"  간격   : {interval}초")
    print(f"  최대   : {'무한' if max_count == 0 else max_count}건")
    print("  중지   : Ctrl+C")
    print("=" * 60)

    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        print("[OK] MySQL 연결 성공\n")
    except mysql.connector.Error as e:
        print(f"[ERROR] MySQL 연결 실패: {e}")
        sys.exit(1)

    count = 0
    try:
        while running:
            if max_count > 0 and count >= max_count:
                break

            batch = [generate_reading(s) for s in SENSORS]
            cursor.executemany(INSERT_SQL, batch)
            conn.commit()
            count += len(batch)

            ts = datetime.now().strftime("%H:%M:%S")
            for row in batch:
                sid, loc, temp, hum, pres, stat = row
                icon = {"normal": "✓", "warning": "⚠", "critical": "✗"}[stat]
                print(f"  [{ts}] {icon} {sid} ({loc:10s}) "
                      f"온도:{temp:6.2f}°C  습도:{hum:5.2f}%  기압:{pres:7.2f}hPa  [{stat}]")

            if running:
                time.sleep(interval)

    except mysql.connector.Error as e:
        print(f"\n[ERROR] DB 오류: {e}")
    finally:
        cursor.close()
        conn.close()
        print(f"\n[INFO] 총 {count}건 삽입 완료. 연결 종료.")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="IoT 센서 데이터 MySQL 인젝터")
    parser.add_argument("--interval", type=float, default=2.0,
                        help="데이터 삽입 간격 (초, 기본값: 2)")
    parser.add_argument("--count",    type=int,   default=0,
                        help="삽입할 최대 배치 수 (0=무한, 기본값: 0)")
    args = parser.parse_args()
    run(args.interval, args.count)
