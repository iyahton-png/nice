<?php
// 에러 리포팅 설정 (실습 및 디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 세션 시작 (방문자 카운트 및 임시 메모 저장용)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 방문 횟수 카운터 초기화 및 증가
if (!isset($_SESSION['visit_count'])) {
    $_SESSION['visit_count'] = 1;
} else {
    $_SESSION['visit_count']++;
}

// 실습용 메모 리스트 세션 초기화
if (!isset($_SESSION['memos'])) {
    $_SESSION['memos'] = [
        [
            'author' => 'XAMPP 관리자',
            'content' => 'XAMPP Apache와 PHP가 성공적으로 연동되었습니다!',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
}

$feedback_message = '';
$feedback_type = ''; // 'success' or 'error'

// POST 요청 처리: 새 메모 등록
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_memo') {
        $author = trim($_POST['author'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if (!empty($author) && !empty($content)) {
            // XSS 방지를 위한 htmlspecialchars 처리
            $_SESSION['memos'][] = [
                'author' => htmlspecialchars($author, ENT_QUOTES, 'UTF-8'),
                'content' => htmlspecialchars($content, ENT_QUOTES, 'UTF-8'),
                'created_at' => date('Y-m-d H:i:s')
            ];
            $feedback_message = '메모가 성공적으로 세션에 저장되었습니다!';
            $feedback_type = 'success';
        } else {
            $feedback_message = '작성자와 내용을 모두 입력해주세요.';
            $feedback_type = 'error';
        }
    } elseif ($_POST['action'] === 'clear_memos') {
        $_SESSION['memos'] = [];
        $feedback_message = '모든 메모가 초기화되었습니다.';
        $feedback_type = 'success';
    }
}

// XAMPP MariaDB/MySQL 기본 접속 테스트 (기본 설정: localhost, 사용자: root, 비밀번호: 없음)
$db_status = false;
$db_error_message = '';
$db_version = '';

try {
    // 경고 에러를 예외로 처리하기 위한 드라이버 설정
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @mysqli_connect('localhost', 'root', '', '', 3306);
    if ($conn) {
        $db_status = true;
        $db_version = mysqli_get_server_info($conn);
        mysqli_close($conn);
    } else {
        $db_error_message = mysqli_connect_error();
    }
} catch (Exception $e) {
    $db_error_message = $e->getMessage();
}

$server_info = [
    'php_version'     => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root'   => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'server_name'     => $_SERVER['SERVER_NAME'] ?? 'localhost',
    'server_port'     => $_SERVER['SERVER_PORT'] ?? '80',
    'current_file'    => __FILE__,
    'current_time'    => date('Y년 m월 d일 H시 i분 s초')
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XAMPP PHP 완벽 실습 대시보드</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@400;500;600;700&family=Fira+Code:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Pretendard', sans-serif;
        }
        code, pre {
            font-family: 'Fira Code', monospace;
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen pb-16">

    <header class="bg-slate-800/90 border-b border-slate-700/80 sticky top-0 z-30 backdrop-blur-md">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-orange-500 flex items-center justify-center font-bold text-white shadow-lg text-lg">
                    🐘
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-100 flex items-center gap-2">
                        XAMPP PHP 실습 대시보드
                        <span class="text-xs bg-emerald-950 text-emerald-400 border border-emerald-500/40 px-2 py-0.5 rounded-full font-normal">
                            PHP v<?= htmlspecialchars($server_info['php_version']) ?>
                        </span>
                    </h1>
                    <p class="text-xs text-slate-400">Apache & MariaDB 로컬 가동 상태 확인 및 실습</p>
                </div>
            </div>

            <!-- 세션 방문 수치 -->
            <div class="flex items-center gap-2 bg-slate-900/80 px-3 py-1.5 rounded-lg border border-slate-700 text-xs text-slate-300">
                <i class="fa-solid fa-arrows-rotate text-sky-400"></i>
                <span>새로고침 누적(세션):</span>
                <span class="font-bold text-amber-400 text-sm"><?= $_SESSION['visit_count'] ?>회</span>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 mt-8 space-y-8">

        <?php if (!empty($feedback_message)): ?>
            <div class="p-4 rounded-xl border flex items-center gap-3 text-sm <?= $feedback_type === 'success' ? 'bg-emerald-950/80 border-emerald-500/50 text-emerald-200' : 'bg-rose-950/80 border-rose-500/50 text-rose-200' ?>">
                <i class="fa-solid <?= $feedback_type === 'success' ? 'fa-circle-check text-emerald-400' : 'fa-circle-exclamation text-rose-400' ?> text-lg"></i>
                <span><?= $feedback_message ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-gradient-to-r from-blue-950/80 via-slate-800 to-indigo-950/80 border border-blue-500/30 rounded-2xl p-6 shadow-xl">
            <h2 class="text-base font-bold text-blue-300 flex items-center gap-2 mb-2">
                <i class="fa-solid fa-circle-info text-blue-400"></i>
                XAMPP 실습 실행 가이드
            </h2>
            <div class="text-xs sm:text-sm text-slate-300 space-y-1.5 leading-relaxed">
                <p>1. <strong>XAMPP Control Panel</strong>을 열고 <span class="bg-slate-700 text-emerald-300 px-1.5 py-0.5 rounded font-mono">Apache</span> 와 <span class="bg-slate-700 text-emerald-300 px-1.5 py-0.5 rounded font-mono">MySQL</span>의 <strong>Start</strong> 버튼을 클릭합니다.</p>
                <p>2. 이 파일을 <code class="bg-slate-950 text-amber-300 px-2 py-0.5 rounded border border-slate-700">C:\xampp\htdocs\test.php</code> 경로에 저장하세요.</p>
                <p>3. 웹 브라우저 주소창에 <code class="bg-slate-950 text-sky-300 px-2 py-0.5 rounded border border-slate-700">http://localhost/test.php</code> 로 접속하면 바로 작동합니다.</p>
            </div>
        </div>

        <section>
            <h2 class="text-xl font-bold text-slate-100 flex items-center gap-2 mb-4">
                <i class="fa-solid fa-server text-indigo-400"></i>
                1. 서버 환경 및 XAMPP 상태 진단
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- 웹 서버 상태 -->
                <div class="bg-slate-800/90 border border-slate-700 rounded-xl p-4 flex flex-col justify-between">
                    <span class="text-xs text-slate-400 font-medium">Apache 웹 서버</span>
                    <div class="my-2 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-sm font-semibold text-emerald-300">정상 작동 중</span>
                    </div>
                    <span class="text-xs text-slate-400 truncate" title="<?= htmlspecialchars($server_info['server_software']) ?>">
                        <?= htmlspecialchars($server_info['server_software']) ?>
                    </span>
                </div>

                <!-- PHP 버전 -->
                <div class="bg-slate-800/90 border border-slate-700 rounded-xl p-4 flex flex-col justify-between">
                    <span class="text-xs text-slate-400 font-medium">PHP 엔진 버전</span>
                    <div class="my-2">
                        <span class="text-xl font-bold text-amber-400">PHP <?= htmlspecialchars($server_info['php_version']) ?></span>
                    </div>
                    <span class="text-xs text-slate-400">포트 번호: <?= htmlspecialchars($server_info['server_port']) ?></span>
                </div>

                <!-- MySQL DB 연동 상태 -->
                <div class="bg-slate-800/90 border border-slate-700 rounded-xl p-4 flex flex-col justify-between">
                    <span class="text-xs text-slate-400 font-medium">MariaDB / MySQL 연결</span>
                    <div class="my-2 flex items-center gap-2">
                        <?php if ($db_status): ?>
                            <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                            <span class="text-sm font-semibold text-emerald-300">접속 성공 (v<?= htmlspecialchars($db_version) ?>)</span>
                        <?php else: ?>
                            <span class="w-3 h-3 rounded-full bg-rose-400"></span>
                            <span class="text-sm font-semibold text-rose-300">미연결 (MySQL Start 필요)</span>
                        <?php endif; ?>
                    </div>
                    <span class="text-xs text-slate-400 truncate">
                        <?= $db_status ? 'localhost (root@3306)' : 'XAMPP MySQL을 켜주세요' ?>
                    </span>
                </div>

                <!-- 서버 로컬 시간 -->
                <div class="bg-slate-800/90 border border-slate-700 rounded-xl p-4 flex flex-col justify-between">
                    <span class="text-xs text-slate-400 font-medium">PHP 서버 시각</span>
                    <div class="my-2">
                        <span class="text-sm font-bold text-sky-400"><?= date('H:i:s') ?></span>
                    </div>
                    <span class="text-xs text-slate-400"><?= date('Y-m-d (D)') ?></span>
                </div>

            </div>
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- 2. POST 폼 데이터 전송 실습 -->
            <div class="bg-slate-800/90 border border-slate-700 rounded-2xl p-6 shadow-lg flex flex-col justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-100 flex items-center gap-2 mb-2">
                        <i class="fa-solid fa-paper-plane text-sky-400"></i>
                        2. PHP POST 폼 전송 실습
                    </h2>
                    <p class="text-xs text-slate-400 mb-4">
                        입력한 폼 데이터를 PHP의 <code class="text-sky-300 font-mono">$_POST</code> 슈퍼글로벌 변수로 수신하여 세션에 보관합니다.
                    </p>

                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="action" value="add_memo">

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1" for="author">
                                작성자 이름
                            </label>
                            <input 
                                type="text" 
                                id="author" 
                                name="author" 
                                placeholder="예: 홍길동" 
                                required
                                class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition-colors"
                            >
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1" for="content">
                                메모 및 실습 소감
                            </label>
                            <textarea 
                                id="content" 
                                name="content" 
                                rows="3" 
                                placeholder="PHP 코드가 웹 서버에서 실시간으로 해석되고 있습니다." 
                                required
                                class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition-colors resize-none"
                            ></textarea>
                        </div>

                        <button 
                            type="submit" 
                            class="w-full bg-sky-600 hover:bg-sky-500 text-white text-sm font-semibold py-2.5 rounded-lg shadow-md transition-all flex items-center justify-center gap-2"
                        >
                            <i class="fa-solid fa-plus"></i>
                            <span>PHP로 데이터 전송하기 (POST)</span>
                        </button>
                    </form>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-700/60 text-xs text-slate-400">
                    💡 <code class="text-amber-300">htmlspecialchars()</code> 함수를 사용하여 웹 보안(XSS 공격)을 방어합니다.
                </div>
            </div>

            <!-- 3. PHP 세션 기반 데이터 출력 실습 -->
            <div class="bg-slate-800/90 border border-slate-700 rounded-2xl p-6 shadow-lg flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-lg font-bold text-slate-100 flex items-center gap-2">
                            <i class="fa-solid fa-database text-emerald-400"></i>
                            3. 세션 메모 목록 (<?= count($_SESSION['memos']) ?>개)
                        </h2>

                        <!-- 메모 전체 삭제 폼 -->
                        <?php if (!empty($_SESSION['memos'])): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="clear_memos">
                                <button 
                                    type="submit" 
                                    class="text-xs text-rose-400 hover:text-rose-300 bg-rose-950/60 hover:bg-rose-900/60 border border-rose-500/40 px-2.5 py-1 rounded-md transition-all"
                                >
                                    목록 비우기
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-400 mb-4">
                        브라우저를 닫기 전까지 <code class="text-emerald-300 font-mono">$_SESSION</code> 배열에 데이터가 안전하게 유지됩니다.
                    </p>

                    <!-- 메모 리스트 -->
                    <div class="space-y-2.5 max-h-[260px] overflow-y-auto pr-1">
                        <?php if (empty($_SESSION['memos'])): ?>
                            <div class="text-center py-12 text-slate-500 text-sm">
                                등록된 메모가 없습니다. 왼쪽 폼에서 첫 메모를 작성해보세요!
                            </div>
                        <?php else: ?>
                            <?php foreach (array_reverse($_SESSION['memos']) as $memo): ?>
                                <div class="bg-slate-900/90 border border-slate-700/80 rounded-xl p-3 text-sm space-y-1">
                                    <div class="flex items-center justify-between text-xs text-slate-400">
                                        <span class="font-bold text-sky-400 flex items-center gap-1.5">
                                            <i class="fa-regular fa-user"></i>
                                            <?= $memo['author'] ?>
                                        </span>
                                        <span><?= $memo['created_at'] ?></span>
                                    </div>
                                    <p class="text-slate-200 text-xs sm:text-sm leading-relaxed whitespace-pre-wrap"><?= $memo['content'] ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-700/60 text-xs text-slate-400 flex justify-between items-center">
                    <span>세션 ID: <span class="font-mono text-slate-300"><?= substr(session_id(), 0, 10) ?>...</span></span>
                    <span class="text-emerald-400 font-semibold">Active Session</span>
                </div>
            </div>

        </section>

        <section class="bg-slate-800/90 border border-slate-700 rounded-2xl p-6 shadow-lg">
            <h2 class="text-lg font-bold text-slate-100 flex items-center gap-2 mb-3">
                <i class="fa-solid fa-code text-amber-400"></i>
                4. XAMPP에서 꼭 알아야 할 주요 내장 변수 & 함수
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-mono">
                
                <div class="bg-slate-950 p-4 rounded-xl border border-slate-800">
                    <span class="text-amber-400 font-bold block mb-1">$_SERVER['DOCUMENT_ROOT']</span>
                    <p class="text-slate-400 break-all"><?= htmlspecialchars($server_info['document_root']) ?></p>
                    <span class="text-slate-500 mt-2 block font-sans">htdocs 폴더의 실제 PC 경로입니다.</span>
                </div>

                <div class="bg-slate-950 p-4 rounded-xl border border-slate-800">
                    <span class="text-sky-400 font-bold block mb-1">__FILE__ (현재 파일 절대 경로)</span>
                    <p class="text-slate-400 break-all"><?= htmlspecialchars($server_info['current_file']) ?></p>
                    <span class="text-slate-500 mt-2 block font-sans">실행 중인 PHP 스크립트 파일의 위치입니다.</span>
                </div>

                <div class="bg-slate-950 p-4 rounded-xl border border-slate-800">
                    <span class="text-emerald-400 font-bold block mb-1">phpinfo() 바로가기</span>
                    <p class="text-slate-400">http://localhost/dashboard/phpinfo.php</p>
                    <span class="text-slate-500 mt-2 block font-sans">PHP의 모든 모듈과 설정값을 상세히 조회합니다.</span>
                </div>

            </div>
        </section>

    </main>

    <footer class="max-w-6xl mx-auto px-4 mt-12 text-center text-xs text-slate-400">
        <p>XAMPP 로컬 개발 환경 실습 파일 | PHP &amp; Apache &amp; MariaDB</p>
    </footer>

</body>
</html>