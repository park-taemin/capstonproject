# Solar AI 캡스톤 프로젝트 정리

## 프로젝트 개요

태양광 쉼터 스마트 모니터링 & AI 분석 시스템으로, 하드웨어 시뮬레이션부터 AI 예측/이상감지까지 통합하는 3인 프로젝트입니다.

---

## 블록도 1 — 하드웨어 레이어 (Modbus RTU)

```mermaid
graph LR
    subgraph RTU1["RP2040 Connect #1 — 센서 시뮬레이터"]
        S1[태양광 발전량 센서]
        S2[전압 / 전류 센서]
        S3[온도 / 조도 센서]
        S1 & S2 & S3 --> MB1[Modbus RTU Slave\nRS485]
    end

    subgraph RTU2["RP2040 Connect #2 — 에어컨 제어기"]
        C1[Coil : 에어컨 ON/OFF]
        MB2[Modbus RTU Slave\nRS485 + Coil 기능]
        MB2 --> C1
    end

    MB1 -->|RS485 버스| HOST[Ubuntu 24.04\nModbus Master]
    MB2 -->|RS485 버스| HOST
    HOST -->|Python\nwrite_coil| MB2
```

---

## 블록도 2 — 서버 / 데이터 파이프라인 (Ubuntu LAMP)

```mermaid
graph TD
    subgraph HW["하드웨어 입력"]
        RTU1[RP2040 #1\nModbus RTU 센서]
        CAM[USB 카메라\n사람 감지]
    end

    subgraph Ubuntu["Ubuntu 24.04  VMware"]
        PY1[Python Modbus 수신기\npymodbus]
        PY2[Python 카메라 처리\nOpenCV / YOLOv8]
        DB[(MySQL\n센서 데이터 저장)]
        GRAF[Grafana\n실시간 모니터링]
        PY3[에어컨 제어 로직\nPython]
    end

    subgraph RTU2["RP2040 #2 — 에어컨"]
        AC[에어컨 ON/OFF\nModbus Coil]
    end

    RTU1 -->|RS485| PY1
    PY1 --> DB
    DB --> GRAF

    CAM --> PY2
    PY2 -->|사람 없음 감지| PY3
    PY3 -->|write_coil OFF| AC
```

---

## 블록도 3 — AI 모델 역할 분담 (3인)

```mermaid
graph TD
    DB[(MySQL\n센서 DB)]

    subgraph P1["담당자 1 — 발전량 예측 AI"]
        LSTM[LSTM 모델\n시계열 학습]
        PRED[발전량 예측값\n출력]
        DB --> LSTM --> PRED
    end

    subgraph P2["담당자 2 — 이상감지 AI"]
        ADET[Autoencoder / Isolation Forest\n이상 패턴 감지]
        ALERT[이상 알림\n임계값 초과 탐지]
        DB --> ADET --> ALERT
    end

    subgraph P3["담당자 3 — 원인 분류 + 대시보드"]
        XGB[XGBoost / Random Forest\n이상 원인 분류]
        SHAP[SHAP 시각화\n원인 설명]
        DASH[Streamlit 통합 대시보드]
        DB --> XGB --> SHAP --> DASH
        PRED --> DASH
        ALERT --> DASH
    end
```

---

## 블록도 4 — 전체 시스템 동작 흐름

```mermaid
flowchart TD
    PANEL["☀️ 태양광 패널"]

    PANEL -->|"DC 전력"| INV
    PANEL -->|"발전량 · 전압 · 전류 · 온도 · 조도 측정"| RP1

    RP1["🔌 RP2040 #1\n센서 시뮬레이터"]
    RP1 -->|"RS485 Modbus RTU"| MODBUS

    MODBUS["📥 Modbus 수신기\n(pymodbus)"]
    MODBUS --> MYSQL

    MYSQL[("🗄️ MySQL\n센서 DB")]
    MYSQL --> GRAFANA
    MYSQL --> LSTM & ANOM & XGB

    GRAFANA["📈 Grafana\n실시간 모니터링"]

    LSTM["① LSTM\n발전량 예측"]
    ANOM["② Autoencoder\n이상 감지"]
    XGB["③ XGBoost + SHAP\n원인 분류"]
    LSTM & ANOM & XGB --> DASH

    DASH["📊 Streamlit\n통합 대시보드"]

    CAM["📷 USB 카메라"]
    CAM --> CV

    CV["👁️ 점유 감지\n(OpenCV / YOLOv8)"]
    CV -->|"사람 없음 감지"| CTRL

    CTRL["⚙️ 에어컨 제어기\n(write_coil)"]
    CTRL -->|"write_coil OFF"| RP2

    RP2["❄️ RP2040 #2\n제어 신호 출력"]
    RP2 -->|"ON / OFF 신호"| INV

    INV["🔄 인버터\n(DC → AC 변환)"]
    INV -->|"AC 전력"| AC

    AC["🌬️ 에어컨"]

    %% ── 스타일 ──
    classDef power   fill:#fef3c7,stroke:#f59e0b,color:#78350f
    classDef device  fill:#dbeafe,stroke:#3b82f6,color:#1e3a5f
    classDef collect fill:#dcfce7,stroke:#16a34a,color:#14532d
    classDef db      fill:#f3e8ff,stroke:#9333ea,color:#3b0764
    classDef ai      fill:#fef9c3,stroke:#ca8a04,color:#713f12
    classDef out     fill:#ffe4e6,stroke:#e11d48,color:#881337
    classDef ctrl    fill:#e0f2fe,stroke:#0284c7,color:#0c4a6e

    class PANEL,INV,AC power
    class RP1,RP2,CAM device
    class MODBUS,CV collect
    class MYSQL db
    class LSTM,ANOM,XGB ai
    class DASH,GRAFANA out
    class CTRL ctrl
```

---

## 역할 분담 요약표

| 담당자 | 담당 영역 | 핵심 기술 |
|--------|-----------|-----------|
| **1번** | 발전량 예측 AI | LSTM, 시계열 전처리, 예측 정확도 평가 |
| **2번** | 이상감지 AI | Autoencoder / Isolation Forest, 임계값 설계 |
| **3번** | 원인 분류 + 대시보드 | XGBoost/RF, SHAP, Streamlit 통합 UI |

> **공통 인프라** (협력): RP2040 Modbus 펌웨어, Ubuntu LAMP 구축, MySQL 스키마 설계
