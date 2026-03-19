<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IoT 실시간 모니터링</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
  :root {
    --bg:       #0d1117;
    --panel:    #161b22;
    --border:   #30363d;
    --text:     #e6edf3;
    --muted:    #7d8590;
    --normal:   #3fb950;
    --warning:  #d29922;
    --critical: #f85149;
    --blue:     #58a6ff;
    --purple:   #bc8cff;
    --teal:     #39d353;
    --orange:   #f0883e;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif; font-size: 13px; }

  /* ── 헤더 ── */
  header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 24px;
    background: var(--panel); border-bottom: 1px solid var(--border);
    position: sticky; top: 0; z-index: 100;
  }
  header h1 { font-size: 1rem; font-weight: 700; letter-spacing: .3px; }
  header h1 em { color: var(--blue); font-style: normal; }
  .hdr-right { display: flex; align-items: center; gap: 18px; color: var(--muted); font-size: 12px; }
  #live-dot {
    width: 9px; height: 9px; border-radius: 50%; background: var(--muted);
    animation: blink 1.4s ease-in-out infinite;
  }
  #live-dot.on { background: var(--normal); }
  @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.25} }

  /* ── 레이아웃 ── */
  main { padding: 20px 24px; display: flex; flex-direction: column; gap: 20px; }

  .panel {
    background: var(--panel); border: 1px solid var(--border); border-radius: 10px; padding: 18px;
  }
  .panel-title {
    font-size: 12px; font-weight: 600; color: var(--muted);
    text-transform: uppercase; letter-spacing: .8px; margin-bottom: 14px;
  }

  /* ── 게이지 행 ── */
  .gauges-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 14px;
  }
  @media(max-width:900px){ .gauges-row{ grid-template-columns: repeat(3,1fr); } }
  .gauge-card {
    background: var(--panel); border: 1px solid var(--border); border-radius: 10px;
    padding: 14px; text-align: center;
    transition: border-color .3s;
  }
  .gauge-card.normal   { border-top: 3px solid var(--normal); }
  .gauge-card.warning  { border-top: 3px solid var(--warning); }
  .gauge-card.critical { border-top: 3px solid var(--critical); animation: cblink .7s infinite; }
  @keyframes cblink { 0%,100%{border-top-color:var(--critical)} 50%{border-top-color:#450a0a} }
  .gauge-sid  { font-weight: 700; font-size: .95rem; margin-bottom: 2px; }
  .gauge-loc  { color: var(--muted); font-size: 10px; margin-bottom: 10px; }
  .gauge-wrap { position: relative; width: 110px; height: 110px; margin: 0 auto 10px; }
  .gauge-wrap canvas { width: 100% !important; height: 100% !important; }
  .gauge-center {
    position: absolute; inset: 0;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    pointer-events: none;
  }
  .gauge-val  { font-size: 1.15rem; font-weight: 700; line-height: 1.1; }
  .gauge-unit { font-size: 10px; color: var(--muted); }
  .gauge-meta { display: flex; justify-content: space-around; font-size: 11px; color: var(--muted); }
  .gauge-meta span b { color: var(--text); }

  /* ── 실시간 라인 차트 3개 ── */
  .charts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 14px;
  }
  @media(max-width:960px){ .charts-grid{ grid-template-columns: 1fr; } }
  .chart-box { position: relative; height: 200px; }

  /* ── 상태 바 ── */
  .status-bar {
    display: flex; gap: 10px; flex-wrap: wrap;
  }
  .status-chip {
    display: flex; align-items: center; gap: 7px;
    background: var(--bg); border: 1px solid var(--border);
    border-radius: 999px; padding: 5px 12px; font-size: 12px;
  }
  .chip-dot { width: 8px; height: 8px; border-radius: 50%; }
  .chip-dot.normal   { background: var(--normal); }
  .chip-dot.warning  { background: var(--warning); }
  .chip-dot.critical { background: var(--critical); }
  .chip-val { font-weight: 600; }
  .chip-time { color: var(--muted); font-size: 10px; }
</style>
</head>
<body>

<header>
  <h1>🌡 IoT <em>실시간 모니터링</em></h1>
  <div class="hdr-right">
    <span id="rec-count">레코드: --</span>
    <span id="last-upd">--:--:--</span>
    <span id="live-dot"></span>
  </div>
</header>

<main>

  <!-- 게이지 섹션 -->
  <div>
    <div class="panel-title">센서별 현재 온도</div>
    <div class="gauges-row" id="gauges-row"></div>
  </div>

  <!-- 실시간 라인 차트 3개 -->
  <div class="panel">
    <div class="panel-title">실시간 추이 (최근 60포인트)</div>
    <div class="charts-grid">
      <div>
        <div style="font-size:11px;color:var(--muted);margin-bottom:6px;">🌡 온도 (°C)</div>
        <div class="chart-box"><canvas id="chartTemp"></canvas></div>
      </div>
      <div>
        <div style="font-size:11px;color:var(--muted);margin-bottom:6px;">💧 습도 (%)</div>
        <div class="chart-box"><canvas id="chartHum"></canvas></div>
      </div>
      <div>
        <div style="font-size:11px;color:var(--muted);margin-bottom:6px;">🌀 기압 (hPa)</div>
        <div class="chart-box"><canvas id="chartPres"></canvas></div>
      </div>
    </div>
  </div>

  <!-- 현재 상태 칩 -->
  <div class="panel">
    <div class="panel-title">현재 상태</div>
    <div class="status-bar" id="status-bar"></div>
  </div>

</main>

<script>
const API      = 'api.php';
const REFRESH  = 2500;   // ms
const MAX_PTS  = 60;     // 라인차트 최대 포인트 수

const SENSOR_COLORS = ['#58a6ff','#bc8cff','#3fb950','#f0883e','#f85149'];
const SENSORS       = ['S001','S002','S003','S004','S005'];

/* ── Chart.js 기본 설정 ── */
Chart.defaults.color      = '#7d8590';
Chart.defaults.borderColor= '#30363d';

/* ── 게이지 차트 생성 (도넛 반원) ── */
const gaugeCharts = {};

function makeGaugeChart(canvasId, color) {
  return new Chart(document.getElementById(canvasId), {
    type: 'doughnut',
    data: {
      datasets: [{
        data: [0, 100],
        backgroundColor: [color, '#21262d'],
        borderWidth: 0,
        circumference: 180,
        rotation: 270,
      }]
    },
    options: {
      responsive: false,
      cutout: '72%',
      plugins: { legend: { display: false }, tooltip: { enabled: false } },
      animation: { duration: 600 }
    }
  });
}

function updateGauge(chart, value, minV, maxV, color) {
  const pct  = Math.max(0, Math.min(100, ((value - minV) / (maxV - minV)) * 100));
  chart.data.datasets[0].data = [pct, 100 - pct];
  chart.data.datasets[0].backgroundColor[0] = color;
  chart.update();
}

/* ── 라인 차트 생성 ── */
function makeLineChart(canvasId) {
  return new Chart(document.getElementById(canvasId), {
    type: 'line',
    data: { labels: [], datasets: [] },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 400 },
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 8, padding: 10, font: { size: 11 } } },
        tooltip: { bodyFont: { size: 11 } }
      },
      scales: {
        x: { ticks: { maxTicksLimit: 6, maxRotation: 0, font: { size: 10 } } },
        y: { grid: { color: '#21262d' }, ticks: { font: { size: 10 } } }
      }
    }
  });
}

const chartTemp = makeLineChart('chartTemp');
const chartHum  = makeLineChart('chartHum');
const chartPres = makeLineChart('chartPres');

/* ── 슬라이딩 윈도우 데이터 저장소 ── */
const store = {};
SENSORS.forEach(sid => { store[sid] = { labels:[], temp:[], hum:[], pres:[] }; });

/* ── 게이지 카드 초기 렌더 ── */
function initGauges() {
  const row = document.getElementById('gauges-row');
  row.innerHTML = '';
  SENSORS.forEach((sid, i) => {
    const color = SENSOR_COLORS[i];
    row.innerHTML += `
      <div class="gauge-card normal" id="gc-${sid}">
        <div class="gauge-sid">${sid}</div>
        <div class="gauge-loc" id="loc-${sid}">--</div>
        <div class="gauge-wrap">
          <canvas id="gauge-${sid}" width="110" height="110"></canvas>
          <div class="gauge-center">
            <span class="gauge-val" id="gval-${sid}" style="color:${color}">--</span>
            <span class="gauge-unit">°C</span>
          </div>
        </div>
        <div class="gauge-meta">
          <span>💧<b id="ghum-${sid}">--</b>%</span>
          <span>🌀<b id="gpres-${sid}">--</b></span>
        </div>
      </div>`;
  });
  SENSORS.forEach((sid, i) => {
    gaugeCharts[sid] = makeGaugeChart(`gauge-${sid}`, SENSOR_COLORS[i]);
  });
}

/* ── 상태별 색상 ── */
function statusColor(s) {
  return s === 'critical' ? 'var(--critical)' : s === 'warning' ? 'var(--warning)' : 'var(--normal)';
}

/* ── 최신 데이터로 게이지 + 상태 칩 갱신 ── */
function renderLatest(data) {
  const bar = document.getElementById('status-bar');
  bar.innerHTML = '';

  data.forEach(d => {
    const sid   = d.sensor_id;
    const color = SENSOR_COLORS[SENSORS.indexOf(sid)];
    const sc    = statusColor(d.status);

    // 게이지 카드
    const card = document.getElementById(`gc-${sid}`);
    if (card) {
      card.className = `gauge-card ${d.status}`;
      document.getElementById(`loc-${sid}`).textContent   = d.location;
      document.getElementById(`gval-${sid}`).textContent  = (+d.temperature).toFixed(1);
      document.getElementById(`ghum-${sid}`).textContent  = (+d.humidity).toFixed(1);
      document.getElementById(`gpres-${sid}`).textContent = (+d.pressure).toFixed(0);
      updateGauge(gaugeCharts[sid], +d.temperature, -10, 45, sc);
    }

    // 상태 칩
    const t = new Date(d.recorded_at).toLocaleTimeString('ko-KR');
    bar.innerHTML += `
      <div class="status-chip">
        <span class="chip-dot ${d.status}"></span>
        <span style="color:${color};font-weight:700">${sid}</span>
        <span>${d.location}</span>
        <span class="chip-val" style="color:${sc}">${(+d.temperature).toFixed(1)}°C</span>
        <span class="chip-time">${t}</span>
      </div>`;
  });
}

/* ── 히스토리 → 슬라이딩 라인 차트 갱신 ── */
function renderHistory(data) {
  // 전체 타임스탬프 정렬 (중복 제거)
  const allTimes = [...new Set(data.map(d => d.recorded_at))].sort();
  const window   = allTimes.slice(-MAX_PTS);
  const labels   = window.map(t =>
    new Date(t).toLocaleTimeString('ko-KR', { hour:'2-digit', minute:'2-digit', second:'2-digit' })
  );

  // 센서별 그룹
  const groups = {};
  data.forEach(d => {
    if (!groups[d.sensor_id]) groups[d.sensor_id] = {};
    groups[d.sensor_id][d.recorded_at] = d;
  });

  function buildDatasets(field) {
    return SENSORS.map((sid, i) => ({
      label: sid,
      data: window.map(t => groups[sid]?.[t] ? +groups[sid][t][field] : null),
      borderColor: SENSOR_COLORS[i],
      backgroundColor: SENSOR_COLORS[i] + '18',
      tension: 0.4, fill: false, pointRadius: 0, borderWidth: 2, spanGaps: true,
    }));
  }

  chartTemp.data.labels   = labels;
  chartTemp.data.datasets = buildDatasets('temperature');
  chartTemp.update();

  chartHum.data.labels   = labels;
  chartHum.data.datasets = buildDatasets('humidity');
  chartHum.update();

  chartPres.data.labels   = labels;
  chartPres.data.datasets = buildDatasets('pressure');
  chartPres.update();
}

/* ── API 호출 & 갱신 ── */
async function refresh() {
  try {
    const [latest, history, countRes] = await Promise.all([
      fetch(`${API}?action=latest`).then(r => r.json()),
      fetch(`${API}?action=history&minutes=10`).then(r => r.json()),
      fetch(`${API}?action=count`).then(r => r.json()),
    ]);

    if (latest.status  === 'ok') renderLatest(latest.data);
    if (history.status === 'ok') renderHistory(history.data);
    if (countRes.status === 'ok')
      document.getElementById('rec-count').textContent = `레코드: ${countRes.total.toLocaleString()}건`;

    document.getElementById('last-upd').textContent =
      new Date().toLocaleTimeString('ko-KR');
    document.getElementById('live-dot').classList.add('on');

  } catch(e) {
    document.getElementById('live-dot').classList.remove('on');
  }
}

initGauges();
refresh();
setInterval(refresh, REFRESH);
</script>
</body>
</html>
