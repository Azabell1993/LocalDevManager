# LocalDevManager - 사실 개발 관리 프로그램

> **빠른 시작:** MySQL 데이터베이스 설정 → `./install.sh` 실행 → http://localhost:8081 접속

<img width="1599" height="1259" alt="스크린샷 2025-11-11 오후 6 05 28" src="https://github.com/user-attachments/assets/adefc4a2-5ae5-4522-9a1f-2b5898d92ba4" />

이 프로그램은 로컬 개발 환경에서 프로젝트를 관리하고, 코드 라인 수를 스캔하며, OS와 에이전트 정보를 관리하는 특수 도구입니다.

## 설치 전 준비사항

1. **MySQL 서버 실행 확인**
   ```bash
   # MySQL 서비스 상태 확인
   brew services list | grep mysql
   # 또는
   sudo systemctl status mysql
   ```

2. **데이터베이스 설정 (app/Core/Db.php의 기본값 사용)**
   ```sql
   -- MySQL 루트 계정으로 접속 후 실행
   CREATE DATABASE mycomp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'mycomp_user'@'localhost' IDENTIFIED BY 'mycomp_pass123';
   GRANT ALL PRIVILEGES ON mycomp_db.* TO 'mycomp_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

   > 추가로, `app/Core/Db.php` 파일에서 기본값을 수정하세요.
   ``` Db.php
    $host = Env::get('DB_HOST', 'localhost');
    $port = Env::get('DB_PORT', '3306');
    $dbname = Env::get('DB_NAME', 'mycomp_db');
    $username = Env::get('DB_USER', 'mycomp_user');
    $password = Env::get('DB_PASS', 'mycomp_pass123');
    ```

3. **자동 설치 실행**
   ```bash
   chmod +x install.sh
   ./install.sh
   ```
   
   **설치 스크립트 처리 내용:**
   - `.env` 파일 생성 및 데이터베이스 연결 확인
   - 테이블 마이그레이션 실행
   - 샘플 데이터 생성
   - C++ LOC 엔진 빌드 (선택사항)

## 빠른 설치

### 자동 설치 (권장)

```bash
# 1. 저장소 클론
git clone https://github.com/Azabell1993/myComp.git
cd myComp

# 2. 설치 스크립트 실행
chmod +x install.sh
./install.sh
```

### MySQL root 계정 문제 해결

만약 root DB 계정으로 연결이 되지 않거나 권한 오류가 발생한다면 아래 명령어로 root 비밀번호를 재설정하세요:

```sql
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '1234';
FLUSH PRIVILEGES;
EXIT;
```

**스마트 설치 시스템:**
- **기존 설치 자동 감지**: .env 파일, 데이터베이스, 테이블 상태 확인
- **선택적 설치 옵션**:
  - 기존 설치 유지 (권장)
  - 완전 초기화 후 재설치
  - 설치 중단
- **필요한 부분만 설치**: 중복 작업 방지, 빠른 설치

**설치 스크립트 처리 내용:**
- 시스템 요구사항 확인 (PHP 7.4+, MySQL, 필수 확장)
- 기존 설치 상태 검사 및 사용자 선택 제공
- 설정 파일 위치: .env (프로젝트 루트)
- 데이터베이스 연결 설정 및 테스트
- 데이터베이스 및 테이블 생성
- 마이그레이션 자동 실행
- C++ LOC 엔진 빌드 (선택사항)
- 디렉토리 권한 설정

### 수동 설치

자동 설치가 실패하는 경우 수동으로 설치할 수 있습니다:

## 완료된 구현 사항

### Core Framework (완료)
- **MVC 아키텍처**: 경량 PHP MVC 패턴 구현
- **데이터베이스**: MySQL 지원 (PDO 기반)
- **라우팅**: 동적 라우팅 시스템 with 파라미터 지원
- **보안**: CSRF 토큰, SQL 인젝션 방지, XSS 보호
- **환경설정**: .env 파일 기반 설정 관리

### Controllers (완료)
- **DashboardController**: KPI 통계, 차트 데이터, 빠른 액션
- **ProjectController**: 프로젝트 CRUD, 스캔 실행, 활성화 토글
- **OsController**: OS 정보 관리 (이름, 버전, 타입, 메모)
- **AgentController**: 에이전트 관리 (이름, 버전, 제공업체, 설명)
- **ScanController**: 스캔 기록, 상세 조회, AJAX 스캔 실행
- **DbAdminController**: DB 테이블 조회, 쿼리 실행, 백업/내보내기

### Services (완료)
- **LocScanner**: 20+ 언어 지원 LOC 스캔 엔진
  - 재귀적 디렉토리 탐색
  - 언어별 자동 감지 (파일 확장자)
  - 주석 제거 후 정확한 LOC 계산
  - 빌드/캐시 디렉토리 자동 제외
  - UTF-8 인코딩 & 바이너리 파일 감지

### Database Schema (완료)
```sql
projects: 프로젝트 정보 (id, name, path, description, is_active)
oses: OS 정보 (id, name, version, type, notes)
agents: 에이전트 정보 (id, name, version, provider, description)
scans: 스캔 기록 (id, project_id, status, total_loc, started_at, completed_at)
scan_lang_stats: 언어별 LOC 통계 (scan_id, language, file_count, loc)
settings: 시스템 설정 (key, value, type)
```

### Frontend Assets (완료)
- **CSS**: 반응형 디자인, 그리드 시스템, 컴포넌트 스타일
- **JavaScript**: Chart.js 통합, AJAX 스캔, 실시간 상태 확인
- **Charts**: 언어별 LOC 파이차트, 트렌드 라인차트

### Routing System (완료)
```
/ → Dashboard
/projects → 프로젝트 관리
/os → OS 관리  
/agents → 에이전트 관리
/scans → 스캔 기록
/db → DB 관리자
/ajax/* → AJAX 엔드포인트
```

## 지원 언어 (20개+)

**C/C++** (.c, .cpp, .cc, .cxx, .h, .hpp) • **Java** (.java) • **Python** (.py) • **PHP** (.php) • **JavaScript** (.js) • **TypeScript** (.ts) • **C#** (.cs) • **Go** (.go) • **Rust** (.rs) • **Ruby** (.rb) • **Swift** (.swift) • **Kotlin** (.kt, .kts) • **Scala** (.scala) • **Perl** (.pl, .pm) • **Shell** (.sh, .bash) • **PowerShell** (.ps1) • **R** (.r, .R) • **MATLAB** (.m) • **Lua** (.lua) • **Haskell** (.hs)

## 설치 및 실행

### 요구사항
- PHP 8.2+ with SQLite3 extension
- 웹서버 또는 PHP 내장 서버

#### 1. 시스템 요구사항

- **PHP**: 7.4 이상
- **MySQL**: 5.7 이상 또는 MariaDB 10.2 이상  
- **PHP 확장**: PDO, PDO_MySQL, JSON, mbstring

#### 2. 환경 설정

```bash
# 환경설정 파일 생성 (프로젝트 루트에)
cp .env.example .env

# 데이터베이스 설정 편집
nano .env
```

#### 3. 데이터베이스 설정

**MySQL 데이터베이스 및 사용자 생성:**

```sql
-- MySQL 루트 계정으로 접속 후 실행
CREATE DATABASE mycomp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mycomp_user'@'localhost' IDENTIFIED BY 'mycomp_pass123';
GRANT ALL PRIVILEGES ON mycomp_db.* TO 'mycomp_user'@'localhost';
FLUSH PRIVILEGES;
```

**데이터베이스 연결 설정 (app/Core/Db.php):**

현재 기본 설정값:
```php
DB_HOST = 'localhost'
DB_PORT = '3306'
DB_NAME = 'mycomp_db'
DB_USER = 'mycomp_user'
DB_PASS = 'mycomp_pass123'
```

**보안 권고사항:**
- 운영 환경에서는 반드시 강력한 패스워드로 변경하세요
- 데이터베이스 사용자 권한을 최소한으로 제한하세요
- `.env` 파일을 통해 설정을 관리하는 것을 권장합니다

**환경별 설정 방법:**

1. **개발 환경 (기본값 사용):**
   - 위의 SQL로 데이터베이스와 사용자 생성
   - 별도 설정 불필요

2. **사용자 정의 설정:**
   ```bash
   # .env 파일 생성
   echo "DB_HOST=localhost" > .env
   echo "DB_PORT=3306" >> .env
   echo "DB_NAME=your_database" >> .env
   echo "DB_USER=your_username" >> .env
   echo "DB_PASS=your_password" >> .env
   ```

3. **연결 테스트:**
   ```bash
   # MySQL 클라이언트로 연결 확인
   mysql -u mycomp_user -pmycomp_pass123 mycomp_db -e "SELECT 'Connection OK' as status;"
   ```

#### 4. 마이그레이션 실행

**방법 1: 자동 설치 스크립트 사용 (권장)**
```bash
./install.sh
```

**방법 2: 수동 마이그레이션 실행**
```bash
# 생성한 사용자 계정으로 마이그레이션 실행
mysql -u mycomp_user -pmycomp_pass123 mycomp_db < database/migrations/001_init.sql

# 또는 루트 계정 사용 (비추천)
mysql -u root -p mycomp_db < database/migrations/001_init.sql
```

**마이그레이션 파일 설명:**
- `001_init.sql`: 기본 테이블 생성 및 샘플 데이터
- `002_mysql_init.sql`: MySQL 특화 설정 (필요시)
- `003_upgrade.sql`: 업그레이드용 스키마 변경 (필요시)

**설치 확인:**
```bash
# 테이블 생성 확인
mysql -u mycomp_user -pmycomp_pass123 mycomp_db -e "SHOW TABLES;"

# 샘플 데이터 확인
mysql -u mycomp_user -pmycomp_pass123 mycomp_db -e "SELECT COUNT(*) as total_records FROM projects;"
```

## 서버 실행

### PHP 내장 서버 (개발용)

```bash
php -S localhost:8081 -t public
```

### Apache/Nginx

Document Root를 `public` 디렉토리로 설정하세요.

브라우저에서 http://localhost:8081 접속

## 주요 기능

### Dashboard
- 프로젝트/OS/에이전트 KPI 통계
- 언어별 LOC 분포 파이차트
- 최근 스캔 트렌드 라인차트
- 빠른 스캔 및 액션 버튼

### Project Management
- 프로젝트 등록/수정/삭제
- 경로 유효성 검증
- 활성/비활성 토글
- 원클릭 LOC 스캔

### LOC Scanner
- 20개 언어 자동감지
- 주석 제거 정확한 계산
- 제외 디렉토리: `node_modules`, `vendor`, `build`, `.git` 등
- 실시간 스캔 상태 모니터링

### Database Admin
- 테이블 조회 및 페이징
- 안전한 SELECT 쿼리 실행
- 테이블별 백업/내보내기
- VACUUM 데이터베이스 최적화

## 보안 기능

- **CSRF Protection** - 모든 폼에 토큰 검증  
- **SQL Injection Prevention** - PDO Prepared Statements  
- **XSS Protection** - HTML 출력 이스케이핑  
- **Input Validation** - 서버사이드 유효성 검증  
- **Query Restriction** - DB Admin에서 SELECT만 허용  

## 아키텍처

```
Local Development Manager
├── public/           # Web Root
│   ├── index.php        # Router Entry Point
│   └── assets/          # CSS, JS, Images
├── app/
│   ├── Core/            # Framework Classes
│   ├── Controllers/     # Request Handlers  
│   ├── Services/        # Business Logic
│   └── Views/           # HTML Templates
├── database/
│   ├── migrations/      # Schema Files
│   └── seeds/          # Sample Data
└── storage/         # Backups & Logs
```

이 프로그램은 개발자가 로컬 환경에서 프로젝트 현황을 체계적으로 관리하고, 정확한 코드 규모 측정을 통해 개발 생산성을 향상시킬 수 있도록 설계된 전문 도구입니다.
