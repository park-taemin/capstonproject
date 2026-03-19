#!/bin/bash
# IoT 모니터링 시스템 초기 설정 스크립트
# 실행: bash setup.sh

set -e
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
ok()   { echo -e "${GREEN}[OK]${NC} $1"; }
warn() { echo -e "${YELLOW}[!]${NC}  $1"; }
err()  { echo -e "${RED}[ERR]${NC} $1"; exit 1; }

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "================================================"
echo "  IoT 센서 모니터링 시스템 설정"
echo "================================================"

# 1. MySQL DB / 테이블 / 유저 생성
echo ""
echo "▶ [1/4] MySQL DB 설정..."

# auth_socket 방식 시도 → 실패하면 비밀번호 방식으로 재시도
if sudo mysql -u root < "$SCRIPT_DIR/setup_db.sql" 2>/dev/null; then
    ok "DB 설정 완료 (auth_socket)"
else
    warn "auth_socket 실패. root 비밀번호를 입력하세요 (mysql_reset.sh로 'root' 설정했다면 root 입력):"
    read -s -p "  MySQL root 비밀번호: " MYSQL_ROOT_PW
    echo ""
    if mysql -u root -p"${MYSQL_ROOT_PW}" < "$SCRIPT_DIR/setup_db.sql" 2>/dev/null; then
        ok "DB 설정 완료 (비밀번호 인증)"
    else
        echo ""
        echo "  MySQL root 접근 실패. 아래 명령으로 비밀번호를 초기화하세요:"
        echo ""
        echo "  sudo systemctl stop mysql"
        echo "  sudo mysqld_safe --skip-grant-tables &"
        echo "  sleep 2"
        echo "  mysql -u root -e \"ALTER USER 'root'@'localhost' IDENTIFIED BY ''; FLUSH PRIVILEGES;\""
        echo "  sudo systemctl restart mysql"
        echo "  bash setup.sh   # 다시 실행"
        err "MySQL 설정 실패"
    fi
fi

# 2. PHP 파일 배포
echo ""
echo "▶ [2/4] PHP 파일 배포 (/var/www/html/iot/)..."
sudo mkdir -p /var/www/html/iot
sudo cp "$SCRIPT_DIR/iot/api.php"       /var/www/html/iot/
sudo cp "$SCRIPT_DIR/iot/dashboard.php" /var/www/html/iot/
sudo chown -R www-data:www-data /var/www/html/iot
sudo chmod -R 644 /var/www/html/iot/*.php
ok "PHP 파일 배포 완료"

# 3. PHP MySQL 드라이버 확인
echo ""
echo "▶ [3/4] PHP MySQL 드라이버 확인..."
if php -m | grep -q "pdo_mysql"; then
    ok "pdo_mysql 드라이버 활성화됨"
else
    warn "pdo_mysql 드라이버 설치 중..."
    sudo apt-get install -y php-mysql
    sudo systemctl restart apache2
    ok "php-mysql 설치 및 Apache 재시작 완료"
fi

# 4. Python 패키지 확인
echo ""
echo "▶ [4/4] Python 패키지 확인..."
if python3 -c "import mysql.connector" 2>/dev/null; then
    ok "mysql-connector-python 이미 설치됨"
else
    warn "mysql-connector-python 설치 중..."
    pip3 install mysql-connector-python --break-system-packages 2>/dev/null \
      || pip3 install mysql-connector-python
    ok "mysql-connector-python 설치 완료"
fi

echo ""
echo "================================================"
echo -e "  ${GREEN}설정 완료!${NC}"
echo "================================================"
echo ""
echo "  대시보드:  http://localhost/iot/dashboard.php"
echo "  API:       http://localhost/iot/api.php?action=latest"
echo ""
echo "  데이터 주입 시작:"
echo "    python3 $SCRIPT_DIR/injector.py"
echo ""
echo "  옵션:"
echo "    --interval 2   (삽입 간격 초, 기본 2)"
echo "    --count 100    (100배치 후 종료)"
echo "================================================"
