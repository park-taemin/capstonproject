# 4week 프로젝트: 실시간 센서 데이터 모니터링 시스템

## 개요

Python `injector.py`가 랜덤 센서 데이터를 MySQL(LAMP)에 2초 간격으로 삽입하면,
**Node-RED**와 **Grafana** 두 가지 경로로 실시간 대시보드를 구성합니다.

---

## 시스템 구성 요소

| 구성 요소 | 역할 |
|-----------|------|
| `injector.py` | 랜덤 온도·습도·기압 데이터 생성 → MySQL 삽입 |
| MySQL (LAMP) | 센서 데이터 영구 저장소 (`sensor_db.sensor_data`) |
| Node-RED | MySQL 폴링 → 게이지·차트 대시보드 (포트 1880) |
| Grafana | MySQL 데이터소스 연동 → 시계열 대시보드 (포트 3000) |

---

## 전체 데이터 흐름

```mermaid
flowchart TD
    A([injector.py\n랜덤 데이터 생성]) -->|INSERT 2초 간격| B[(MySQL\nsensor_db.sensor_data)]

    B -->|SELECT 폴링 2초| C[Node-RED\nMySQL 노드]
    C --> D[함수 노드\n센서별 분리]
    D --> E[ui_gauge\n온도 게이지]
    D --> F[ui_gauge\n습도 게이지]
    D --> G[ui_gauge\n기압 게이지]
    D --> H[ui_chart\n시계열 차트]
    E & F & G & H --> I([Node-RED Dashboard\nlocalhost:1880/ui])

    B -->|자동 새로 고침 5초| J[Grafana\nMySQL 데이터소스]
    J --> K[Gauge 패널\n온도·습도·기압]
    J --> L[Time series 패널\n시계열 추이]
    J --> M[Time series 패널\n센서별 온도 비교]
    K & L & M --> N([Grafana Dashboard\nlocalhost:3000])
```

---

## 상세 동작 설명

### 1. injector.py — 데이터 생성 및 주입

- **센서 목록**: `SENSOR-01`, `SENSOR-02`, `SENSOR-03`
- **생성 범위**
  - 온도: 15 ~ 40 °C
  - 습도: 30 ~ 90 %
  - 기압: 980 ~ 1050 hPa
- 2초마다 센서 3개 × 1건 = **6건/분** 삽입

```mermaid
sequenceDiagram
    participant P as injector.py
    participant M as MySQL (sensor_db)

    loop 2초 간격
        P->>P: random.uniform() 으로 값 생성
        P->>M: INSERT INTO sensor_data
        M-->>P: OK (commit)
    end
```

### 2. Node-RED 흐름 (flow.json)

```mermaid
flowchart LR
    IN[Inject\n2초 타이머] --> SQL[MySQL 노드\nSELECT 최근 30건]
    SQL --> FN[Function\n센서별 분리]
    FN --> G1[ui_gauge\nTemperature]
    FN --> G2[ui_gauge\nHumidity]
    FN --> G3[ui_gauge\nPressure]
    FN --> CH[ui_chart\n온도 추이]
```

- Node-RED 대시보드: `http://localhost:1880/ui`
- 필요 팔레트: `node-red-dashboard`, `node-red-node-mysql`

### 3. Grafana 대시보드 (dashboard.json)

```mermaid
flowchart LR
    DS[(MySQL\nDataSource)] -->|rawSQL| P1[Gauge\n온도 최신값]
    DS -->|rawSQL| P2[Gauge\n습도 최신값]
    DS -->|rawSQL| P3[Gauge\n기압 최신값]
    DS -->|$__timeFilter| P4[Time series\n전체 추이]
    DS -->|센서별 GROUP| P5[Time series\n센서 비교]
```

- Grafana 대시보드: `http://localhost:3000`
- 새로 고침: 5초 자동
- 조회 범위: 최근 30분 (슬라이더로 조정 가능)

---

## 실행 순서

```bash
# 1. DB 초기화
mysql -u root < sql/schema.sql

# 2. 데이터 주입 시작
pip install mysql-connector-python
python3 injector.py

# 3-A. Node-RED 대시보드 확인
#   node-red 실행 후 flow.json 임포트
#   http://localhost:1880/ui

# 3-B. Grafana 대시보드 확인
#   datasource.yaml → /etc/grafana/provisioning/datasources/
#   dashboard.json  → Grafana UI에서 Import
#   http://localhost:3000
```

---

## 파일 구조

```
4week/
├── injector.py              # 랜덤 데이터 생성 및 MySQL 주입
├── sql/
│   └── schema.sql           # DB·테이블 생성 스크립트
├── node-red/
│   └── flow.json            # Node-RED 플로우 (대시보드 포함)
├── grafana/
│   ├── dashboard.json       # Grafana 대시보드 정의
│   └── datasource.yaml      # Grafana MySQL 데이터소스 프로비저닝
└── project.md               # 프로젝트 문서 (이 파일)
```
