#!/bin/bash

# 프로젝트 설치 스크립트
# 작성자: Azabell1993
# 설명: 프로젝트 초기 설정 및 데이터베이스 마이그레이션을 수행합니다.

set -e  # 에러 발생 시 스크립트 중단
shopt -s nullglob  # glob에 일치 파일이 없으면 패턴을 제거

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# 로그 함수들
log_info()    { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
log_error()   { echo -e "${RED}[ERROR]${NC} $1"; }

# 배너
print_banner() {
    echo -e "${BLUE}================================================${NC}"
    echo -e "${BLUE}    사설망 개발 관리 프로그램 설치${NC}"
    echo -e "${BLUE}    프로젝트 및 데이터베이스 관리${NC}"
    echo -e "${BLUE}================================================${NC}"
    echo
}

# 기존 설치 상태 확인 (0=유지, 1=설치/재설치 진행)
check_existing_installation() {
    log_info "기존 설치 상태를 확인하는 중..."
    
    local has_env=false
    local has_database=false
    local has_tables=false
    local tables_count=0
    
    # .env 파일 존재 확인
    if [ -f ".env" ]; then
        has_env=true
        log_info "✓ .env 파일이 존재합니다."
    fi
    
    # 데이터베이스 연결 및 테이블 확인
    if [ "$has_env" = true ]; then
        local db_check_result
        db_check_result=$(php -r "
        \$config = [];
        if (file_exists('.env')) {
            \$lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach (\$lines as \$line) {
                if (strpos(\$line, '=') !== false && \$line[0] !== '#') {
                    list(\$key, \$value) = explode('=', \$line, 2);
                    \$config[trim(\$key)] = trim(\$value, '\"\\\"');
                }
            }
        }

        try {
            \$dsn = \"mysql:host={\$config['DB_HOST']};port={\$config['DB_PORT']};dbname={\$config['DB_NAME']};charset=utf8mb4\";
            \$pdo = new PDO(\$dsn, \$config['DB_USER'], \$config['DB_PASS']);
            \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            \$stmt = \$pdo->query('SHOW TABLES');
            \$all_tables = \$stmt->fetchAll(PDO::FETCH_COLUMN);
            
            \$main_tables = ['projects', 'scans', 'oses', 'agents'];
            \$existing_main_tables = [];
            foreach (\$main_tables as \$table) {
                if (in_array(\$table, \$all_tables)) {
                    \$existing_main_tables[] = \$table;
                }
            }
            
            echo json_encode([
                'database_exists' => true,
                'tables_count' => count(\$all_tables),
                'main_tables_count' => count(\$existing_main_tables),
                'tables' => \$all_tables,
                'main_tables' => \$existing_main_tables
            ]);
        } catch (PDOException \$e) {
            echo json_encode(['database_exists' => false, 'error' => \$e->getMessage()]);
        }
        " 2>/dev/null)
        
        if [ -n "$db_check_result" ]; then
            local db_exists
            db_exists=$(echo "$db_check_result" | php -r "echo json_decode(file_get_contents('php://stdin'), true)['database_exists'] ? 'true' : 'false';")
            if [ "$db_exists" = "true" ]; then
                has_database=true
                tables_count=$(echo "$db_check_result" | php -r "echo json_decode(file_get_contents('php://stdin'), true)['tables_count'] ?? 0;")
                local main_tables_count
                main_tables_count=$(echo "$db_check_result" | php -r "echo json_decode(file_get_contents('php://stdin'), true)['main_tables_count'] ?? 0;")
                if [ "$tables_count" -gt 0 ]; then
                    has_tables=true
                    log_info "✓ 데이터베이스 연결 가능하며 ${tables_count}개의 테이블이 존재합니다."
                    if [ "$main_tables_count" -ge 4 ]; then
                        log_info "✓ 주요 테이블 ${main_tables_count}개가 모두 설치되어 있습니다."
                    else
                        log_warning "⚠ 주요 테이블 중 ${main_tables_count}개만 설치되어 있습니다."
                    fi
                else
                    log_info "✓ 데이터베이스 연결은 가능하지만 테이블이 없습니다."
                fi
            else
                log_warning "⚠ 데이터베이스 연결에 실패했습니다."
            fi
        fi
    fi
    
    # 테이블 목록 조회
    local table_list=""
    if [ "$has_database" = true ]; then
        table_list=$(php -r "
        \$config = [];
        if (file_exists('.env')) {
            \$lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach (\$lines as \$line) {
                if (strpos(\$line, '=') !== false && \$line[0] !== '#') {
                    list(\$key, \$value) = explode('=', \$line, 2);
                    \$config[trim(\$key)] = trim(\$value, '\"\\\"');
                }
            }
        }
        try {
            \$dsn = \"mysql:host={\$config['DB_HOST']};port={\$config['DB_PORT']};dbname={\$config['DB_NAME']};charset=utf8mb4\";
            \$pdo = new PDO(\$dsn, \$config['DB_USER'], \$config['DB_PASS']);
            \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            \$stmt = \$pdo->query('SHOW TABLES');
            \$tables = \$stmt->fetchAll(PDO::FETCH_COLUMN);
            echo implode(', ', \$tables);
        } catch (PDOException \$e) {
            echo '';
        }
        " 2>/dev/null)
    fi
    
    # 설치 상태 요약
    echo ""
    echo "현재 설치 상태:"
    echo "  .env 파일: $([ "$has_env" = true ] && echo " 있음 " || echo " 없음 ")"
    echo "  데이터베이스: $([ "$has_database" = true ] && echo " 연결됨 " || echo " 연결 안됨 ")"
    echo "  테이블: $([ "$has_tables" = true ] && echo " ${tables_count}개 있음 " || echo " 없음 ")"
    if [ -n "$table_list" ]; then
        echo "  테이블 목록: $table_list"
    fi
    echo ""
    
    # 분기
    if [ "$has_env" = true ] && [ "$has_database" = true ] && [ "$has_tables" = true ]; then
        log_warning "완전한 설치가 감지되었습니다!"
        echo "다음 옵션 중 하나를 선택하세요:"
        echo "1) 기존 설치 유지 (권장)"
        echo "2) 완전 초기화 후 재설치"
        echo "3) 설치 중단"
        echo ""
        read -p "선택하세요 (1-3): " install_option
        case $install_option in
            1)
                log_info "기존 설치를 유지합니다."
                return 0
                ;;
            2)
                log_warning "⚠️  완전 초기화를 진행합니다!"
                echo "   • 모든 테이블 삭제 후 재설치"
                read -p "정말로 계속하시겠습니까? (yes/no): " confirm
                if [ "$confirm" = "yes" ]; then
                    log_info "기존 테이블을 삭제하는 중..."
                    if ! php -r "
                        \$config = [];
                        if (file_exists('.env')) {
                            \$lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                            foreach (\$lines as \$line) {
                                if (strpos(\$line, '=') !== false && \$line[0] !== '#') {
                                    list(\$key, \$value) = explode('=', \$line, 2);
                                    \$config[trim(\$key)] = trim(\$value, '\"\\\"');
                                }
                            }
                        }
                        try {
                            \$dsn = \"mysql:host={\$config['DB_HOST']};port={\$config['DB_PORT']};dbname={\$config['DB_NAME']};charset=utf8mb4\";
                            \$pdo = new PDO(\$dsn, \$config['DB_USER'], \$config['DB_PASS']);
                            \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            \$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                            \$stmt = \$pdo->query('SHOW TABLES');
                            \$tables = \$stmt->fetchAll(PDO::FETCH_COLUMN);
                            foreach (\$tables as \$table) {
                                \$pdo->exec(\"DROP TABLE IF EXISTS \$table\");
                                echo \"테이블 '\$table' 삭제됨\\n\";
                            }
                            \$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                            echo '테이블 삭제 완료';
                            exit(0);
                        } catch (PDOException \$e) {
                            echo '테이블 삭제 중 오류: ' . \$e->getMessage();
                            exit(1);
                        }
                    "; then
                        log_error "테이블 삭제 실패"
                        exit 1
                    fi
                    log_success "기존 데이터 삭제 완료. 재설치를 진행합니다."
                    return 1
                else
                    log_info "초기화를 취소하고 설치를 중단합니다."
                    exit 0
                fi
                ;;
            3)
                log_info "설치를 중단합니다."
                exit 0
                ;;
            *)
                log_error "잘못된 선택입니다. 설치를 중단합니다."
                exit 1
                ;;
        esac
    elif [ "$has_env" = true ] && [ "$has_database" = true ] && [ "$has_tables" = false ]; then
        log_info "환경 설정은 있지만 테이블이 없습니다. 테이블 생성을 진행합니다."
        return 1
    elif [ "$has_env" = true ] && [ "$has_database" = false ]; then
        log_warning "환경 설정은 있지만 데이터베이스 연결에 실패했습니다."
        read -p "데이터베이스 설정을 다시 구성하시겠습니까? (y/N): " reconfigure
        if [[ $reconfigure =~ ^[Yy]$ ]]; then
            return 1
        else
            log_info "설치를 중단합니다."
            exit 0
        fi
    fi
    
    return 1
}

# 시스템 요구사항 확인
check_requirements() {
    log_info "시스템 요구사항을 확인하는 중..."
    if ! command -v php &> /dev/null; then
        log_error "PHP가 설치되어 있지 않습니다. PHP 7.4 이상을 설치해주세요."
        exit 1
    fi
    PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2 | cut -d'.' -f1,2)
    log_info "PHP 버전: $PHP_VERSION"
    
    if ! command -v mysql &> /dev/null; then
        log_warning "MySQL 클라이언트가 설치되어 있지 않습니다."
        log_info "MySQL 서버가 실행 중인지 확인하고 계속 진행합니다."
    fi
    
    log_info "필수 PHP 확장을 확인하는 중..."
    php -m | grep -qi "pdo"        && log_success "PHP 확장 'PDO' 사용 가능"        || { log_error "PDO 확장 필요"; exit 1; }
    php -m | grep -qi "pdo_mysql"  && log_success "PHP 확장 'pdo_mysql' 사용 가능"  || { log_error "pdo_mysql 확장 필요"; exit 1; }
    if php -m | grep -qi "json" || php -v | grep -q "8\.[0-9]"; then
        log_success "PHP 확장 'json' 사용 가능"
    else
        log_error "json 확장 필요"; exit 1
    fi
    php -m | grep -qi "mbstring"   && log_success "PHP 확장 'mbstring' 사용 가능"   || { log_error "mbstring 확장 필요"; exit 1; }
}

# 환경 설정 파일 확인
setup_environment() {
    log_info "환경 설정 파일을 확인하는 중..."
    if [ ! -f ".env" ]; then
        log_info ".env 파일이 없습니다. 새로 생성합니다."
        setup_database_config
        create_env_file
    else
        log_info ".env 파일이 이미 존재합니다."
    fi
}

# 데이터베이스 설정 입력받기
setup_database_config() {
    log_info "데이터베이스 설정을 구성합니다..."
    echo -e "${YELLOW}데이터베이스 연결 정보를 입력해주세요:${NC}"
    echo ""
    
    read -p "MySQL 호스트 (기본값: localhost): " DB_HOST; DB_HOST=${DB_HOST:-localhost}
    read -p "MySQL 포트 (기본값: 3306): " DB_PORT; DB_PORT=${DB_PORT:-3306}
    read -p "데이터베이스 이름 (기본값: mycomp_db): " DB_NAME; DB_NAME=${DB_NAME:-mycomp_db}
    read -p "MySQL 사용자명 (기본값: mycomp_user): " DB_USER; DB_USER=${DB_USER:-mycomp_user}
    echo ""
    echo -e "${CYAN}💡 참고: 현재 .env 파일에 설정된 기본 사용자는 'mycomp_user'입니다${NC}"
    read -s -p "사용자 '$DB_USER'의 MySQL 비밀번호: " DB_PASS; echo
    
    # .env 파일이 있으면 기존 파일을 업데이트
    if [ -f ".env" ]; then
        sed -i.bak "s/DB_HOST=.*/DB_HOST=$DB_HOST/" .env
        sed -i.bak "s/DB_PORT=.*/DB_PORT=$DB_PORT/" .env
        sed -i.bak "s/DB_NAME=.*/DB_NAME=$DB_NAME/" .env
        sed -i.bak "s/DB_USER=.*/DB_USER=$DB_USER/" .env
        sed -i.bak "s/DB_PASS=.*/DB_PASS=$DB_PASS/" .env
        rm -f .env.bak
        log_success "데이터베이스 설정이 완료되었습니다."
    fi
}

# .env 파일 생성
create_env_file() {
    log_info ".env 파일을 생성하는 중..."
    
    cat > .env << EOF
# 데이터베이스 설정 (MySQL)
DB_DRIVER=mysql
DB_HOST=$DB_HOST
DB_PORT=$DB_PORT
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASS=$DB_PASS

ROOT_USER=root
ROOT_PASSWORD=1234

# 애플리케이션 설정
APP_TIMEZONE=Asia/Seoul
APP_DEBUG=true
APP_NAME="Development Manager"

# 보안 설정
APP_SECRET=dev-secret-key-2024
EOF

    log_success ".env 파일이 생성되었습니다."
}

# 데이터베이스 연결 테스트
test_database_connection() {
    log_info "데이터베이스 연결을 테스트하는 중..."
    if ! php -r "
    \$config = [];
    if (file_exists('.env')) {
        \$lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach (\$lines as \$line) {
            if (strpos(\$line, '=') !== false && \$line[0] !== '#') {
                list(\$key, \$value) = explode('=', \$line, 2);
                \$config[trim(\$key)] = trim(\$value);
            }
        }
    }
    try {
        \$dsn = \"mysql:host={\$config['DB_HOST']};port={\$config['DB_PORT']};charset=utf8mb4\";
        \$pdo = new PDO(\$dsn, \$config['DB_USER'], \$config['DB_PASS']);
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo \"데이터베이스 연결 성공\n\";
        exit(0);
    } catch (PDOException \$e) {
        echo \"데이터베이스 연결 실패: \" . \$e->getMessage() . \"\n\";
        exit(1);
    }
    "; then
        log_error "데이터베이스 연결에 실패했습니다. 설정/MySQL 상태를 확인하세요."
        exit 1
    else
        log_success "데이터베이스 연결이 성공했습니다."
    fi
}

# 데이터베이스 생성
create_database() {
    log_info "데이터베이스를 생성하는 중..."
    source .env
    if ! php -r "
    \$config = [];
    if (file_exists('.env')) {
        \$lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach (\$lines as \$line) {
            if (strpos(\$line, '=') !== false && \$line[0] !== '#') {
                list(\$key, \$value) = explode('=', \$line, 2);
                \$config[trim(\$key)] = trim(\$value);
            }
        }
    }
    try {
        \$dsn = \"mysql:host={\$config['DB_HOST']};port={\$config['DB_PORT']};charset=utf8mb4\";
        \$pdo = new PDO(\$dsn, \$config['DB_USER'], \$config['DB_PASS']);
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        \$stmt = \$pdo->prepare(\"SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?\");
        \$stmt->execute([\"{\$config['DB_NAME']}\"]);
        if (\$stmt->rowCount() > 0) {
            echo \"데이터베이스 '{\$config['DB_NAME']}'가 이미 존재합니다.\n\";
        } else {
            \$pdo->exec(\"CREATE DATABASE {\$config['DB_NAME']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci\");
            echo \"데이터베이스 '{\$config['DB_NAME']}'가 생성되었습니다.\n\";
        }
        exit(0);
    } catch (PDOException \$e) {
        echo \"데이터베이스 생성 실패: \" . \$e->getMessage() . \"\n\";
        exit(1);
    }
    "; then
        log_error "데이터베이스 생성에 실패했습니다."
        exit 1
    else
        log_success "데이터베이스 준비가 완료되었습니다."
    fi
}

# 마이그레이션 실행
run_migrations() {
    log_info "데이터베이스 마이그레이션을 실행하는 중..."
    if [ -d "database/migrations" ]; then
        local any=false
        for migration in database/migrations/*.sql; do
            if [ -f "$migration" ]; then
                local filename
                filename=$(basename "$migration")
                
                # SQLite 전용 파일은 건너뛰기
                if [[ "$filename" == *"sqlite"* ]]; then
                    log_info "SQLite 전용 파일 건너뛰기: $filename"
                    continue
                fi
                
                any=true
                log_info "마이그레이션 실행: $filename"
                if ! php -r "
                \$config = [];
                if (file_exists('.env')) {
                    \$lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach (\$lines as \$line) {
                        if (strpos(\$line, '=') !== false && \$line[0] !== '#') {
                            list(\$key, \$value) = explode('=', \$line, 2);
                            \$config[trim(\$key)] = trim(\$value);
                        }
                    }
                }
                try {
                    \$dsn = \"mysql:host={\$config['DB_HOST']};port={\$config['DB_PORT']};dbname={\$config['DB_NAME']};charset=utf8mb4\";
                    \$pdo = new PDO(\$dsn, \$config['DB_USER'], \$config['DB_PASS']);
                    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    \$sql = file_get_contents('$migration');
                    \$pdo->exec(\$sql);
                    echo \"$filename 실행 완료\n\";
                    exit(0);
                } catch (PDOException \$e) {
                    echo \"$filename 실행 실패: \" . \$e->getMessage() . \"\n\";
                    exit(1);
                }
                "; then
                    log_error "마이그레이션 실행 중 오류가 발생했습니다."
                    exit 1
                else
                    log_success "$filename 마이그레이션 완료"
                fi
            fi
        done
        if [ "$any" = false ]; then
            log_warning "실행할 마이그레이션(.sql) 파일이 없습니다."
        fi
    else
        log_warning "database/migrations 디렉토리를 찾을 수 없습니다."
    fi
}

# 디렉토리 권한 설정
setup_permissions() {
    log_info "디렉토리 권한을 설정하는 중..."
    mkdir -p logs storage/backups
    chmod 755 logs storage storage/backups
    if [ -d "cpp_engine" ]; then
        chmod 755 cpp_engine
        [ -f "cpp_engine/Makefile" ] && chmod 644 cpp_engine/Makefile
    fi
    log_success "디렉토리 권한 설정이 완료되었습니다."
}

# C++ LOC 스캔 엔진 빌드 (선택사항)
build_cpp_engine() {
    log_info "C++ LOC 스캔 엔진 빌드 여부를 확인합니다..."
    if [ -d "cpp_engine" ] && [ -f "cpp_engine/Makefile" ]; then
        read -p "C++ LOC 스캔 엔진을 빌드하시겠습니까? (y/N): " build_engine
        if [[ $build_engine =~ ^[Yy]$ ]]; then
            if command -v make &> /dev/null && command -v g++ &> /dev/null; then
                log_info "C++ 엔진을 빌드하는 중..."
                ( cd cpp_engine && make clean && make ) || log_warning "C++ 엔진 빌드 실패. PHP 백업 엔진 사용."
                log_success "C++ LOC 스캔 엔진 빌드 절차 완료"
            else
                log_warning "make 또는 g++ 미설치. PHP 백업 엔진을 사용합니다."
            fi
        else
            log_info "C++ 엔진 빌드를 건너뜁니다. PHP 백업 엔진을 사용합니다."
        fi
    else
        log_info "C++ 엔진 소스가 없습니다. PHP 백업 엔진을 사용합니다."
    fi
}

# 설치 완료 메시지
print_completion() {
    echo
    log_success "=========================================="
    log_success "    설치가 완료되었습니다!"
    log_success "=========================================="
    echo
    log_info "다음 단계:"
    echo "  1. PHP 내장 서버 실행:"
    echo "     php -S localhost:8081 -t public"
    echo
    echo "  2. 브라우저에서 접속:"
    echo "     http://localhost:8081"
    echo
    echo "  3. 설정 파일 위치:"
    echo "     .env"
    echo
    log_info "문제가 발생하면 logs/ 디렉토리의 로그를 확인해주세요."
    echo
}

# 메인
main() {
    print_banner
    
    # 프로젝트 루트 확인
    if [ ! -f "public/index.php" ] || [ ! -d "app" ]; then
        log_error "프로젝트 루트 디렉토리에서 실행해주세요."
        log_error "public/index.php와 app 디렉토리가 있는 위치에서 실행하세요."
        exit 1
    fi
    
    check_requirements
    
    # 기존 설치 확인 → 설치 모드 결정
    if check_existing_installation; then
        installation_mode=0   # 유지
    else
        installation_mode=1   # 새설치/재설치
    fi
    log_info "설치 모드: $installation_mode (0=유지, 1=새설치)"
    
    if [ $installation_mode -eq 0 ]; then
        log_info "기존 설치를 확인하고 필요한 부분만 업데이트합니다."
        setup_environment
        setup_permissions
        build_cpp_engine
        log_success "설치 확인이 완료되었습니다!"
        print_completion
    else
        log_info "전체 설치를 진행합니다."
        setup_environment
        log_info "환경 설정 확인 완료."
        
        # 데이터베이스 연결 테스트
        test_database_connection
        
        create_database
        run_migrations
        setup_permissions
        build_cpp_engine
        print_completion
    fi
}

# Ctrl+C 시그널 핸들링
trap 'echo -e "\n'${RED}'설치가 중단되었습니다.'${NC}'"; exit 1' INT

# 실행
main "$@"