#!/bin/bash
# MySQL root 비밀번호 초기화 스크립트

set -e
echo "▶ MySQL 정지..."
sudo systemctl stop mysql

echo "▶ skip-grant-tables 설정 파일 생성..."
sudo tee /etc/mysql/mysql.conf.d/reset.cnf > /dev/null <<'CONF'
[mysqld]
skip-grant-tables
CONF

echo "▶ MySQL 재시작..."
sudo systemctl start mysql
sleep 3

echo "▶ root 비밀번호 설정 (Root@1234! — 정책 충족)..."
mysql -u root --connect-expired-password <<'SQL'
FLUSH PRIVILEGES;
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'Root@1234!';
FLUSH PRIVILEGES;
SQL

echo "▶ 임시 설정 제거 및 재시작..."
sudo rm /etc/mysql/mysql.conf.d/reset.cnf
sudo systemctl restart mysql
sleep 3

echo ""
echo "▶ 접속 테스트..."
mysql -u root -p'Root@1234!' -e "SELECT 'MySQL root 초기화 성공!' AS result;"

echo ""
echo "▶ DB/테이블/유저 생성..."
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
mysql -u root -p'Root@1234!' < "$SCRIPT_DIR/setup_db.sql"

echo ""
echo "▶ PHP 파일 배포..."
sudo mkdir -p /var/www/html/iot
sudo cp "$SCRIPT_DIR/iot/api.php"       /var/www/html/iot/
sudo cp "$SCRIPT_DIR/iot/dashboard.php" /var/www/html/iot/
sudo chown -R www-data:www-data /var/www/html/iot
sudo chmod 644 /var/www/html/iot/*.php

echo ""
echo "▶ PHP mysql 드라이버 확인..."
if ! php -m | grep -q pdo_mysql; then
    sudo apt-get install -y php-mysql
    sudo systemctl restart apache2
fi

echo ""
echo "================================================"
echo "  완료!"
echo "  대시보드: http://localhost/iot/dashboard.php"
echo "  주입 시작: python3 $SCRIPT_DIR/injector.py"
echo "================================================"
