<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

/* STUDENT INFO */
$user_stmt = $conn->prepare("SELECT firstname, lastname, email, contact_number, course, profile_photo FROM users WHERE id = ?");
$user_stmt->bind_param("i", $student_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

if (!$user) {
    die("Student not found.");
}

$default_photo = "../assets/southern.png";
if (!empty($user['profile_photo']) && file_exists("../assets/uploads/profile/" . $user['profile_photo'])) {
    $photo = "../assets/uploads/profile/" . $user['profile_photo'];
} else {
    $photo = $default_photo;
}

/* HEADER LOGOS */
$left_logo  = "../assets/logo1.png";
$right_logo = "../assets/logo2.png";

/* RESULT QUERY */
$stmt = $conn->prepare("
    SELECT 
        cr.subject,
        cr.result,
        cr.comment,
        cr.date_signed,
        CONCAT(u.lastname, ', ', u.firstname) AS instructor_name
    FROM class_requests cr
    LEFT JOIN teacher_classes tc ON cr.class_id = tc.id
    LEFT JOIN users u ON tc.teacher_id = u.id
    WHERE cr.student_id = ? AND cr.status = 'Reviewed'
    ORDER BY cr.id DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$total_subjects = 0;
$total_passed = 0;
$total_failed = 0;
$total_incomplete = 0;

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
    $total_subjects++;

    if ($row['result'] === 'Passed') {
        $total_passed++;
    } elseif ($row['result'] === 'Failed') {
        $total_failed++;
    } elseif ($row['result'] === 'Incomplete') {
        $total_incomplete++;
    }
}

$full_name = $user['lastname'] . ', ' . $user['firstname'];
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Result</title>

    <script>
    (function () {
        const savedTheme = localStorage.getItem("site_theme");
        if (savedTheme === "dark") {
            document.documentElement.classList.add("dark-mode");
        }
    })();
    </script>

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, sans-serif;
        }

        :root{
            --body-bg:#d9d9d9;
            --main-bg:#d9d9d9;

            --top-header-bg:#8fbc67;
            --top-header-text:#000;
            --sub-header-bg:#003b49;
            --sub-header-text:#00ff84;

            --card-bg:#ffffff;
            --card-border:transparent;
            --card-shadow:0 4px 12px rgba(0,0,0,0.08);

            --title-text:#003b49;
            --body-text:#333;
            --muted-text:#666;

            --theme-btn-bg:#ffffff;
            --theme-btn-text:#003b49;
            --theme-btn-border:#d8d8d8;

            --table-head-bg:#8fbc67;
            --table-head-text:#000;
            --table-cell-text:#222;
            --table-border:#d8d8d8;
            --table-even:#fafafa;

            --info-bg:#f8f8f8;
            --info-border:#d4d4d4;
            --info-label:#666;
            --info-value:#111;
        }

        .dark-mode:root{
            --body-bg:#082f36;
            --main-bg:
                radial-gradient(circle at top right, rgba(34,115,84,0.22), transparent 28%),
                radial-gradient(circle at bottom left, rgba(25,110,78,0.18), transparent 30%),
                linear-gradient(135deg, #032b32 0%, #053842 55%, #032f35 100%);

            --top-header-bg:#8fbc67;
            --top-header-text:#000;
            --sub-header-bg:rgba(0,59,73,0.78);
            --sub-header-text:#00ff84;

            --card-bg:rgba(16,70,61,0.35);
            --card-border:1px solid rgba(255,255,255,0.14);
            --card-shadow:0 10px 30px rgba(0,0,0,0.16);

            --title-text:#ffffff;
            --body-text:#e1efea;
            --muted-text:#d7ebe4;

            --theme-btn-bg:rgba(255,255,255,0.10);
            --theme-btn-text:#ffffff;
            --theme-btn-border:rgba(255,255,255,0.16);

            --table-head-bg:#8fbc67;
            --table-head-text:#000;
            --table-cell-text:#222;
            --table-border:#d8d8d8;
            --table-even:#fafafa;

            --info-bg:rgba(255,255,255,0.06);
            --info-border:rgba(255,255,255,0.14);
            --info-label:#d7ebe4;
            --info-value:#ffffff;
        }

        body{
            background:var(--body-bg);
            min-height:100vh;
            transition:background .25s ease;
        }

        .wrapper{
            min-height:100vh;
        }

        .sidebar{
            position:fixed;
            top:0;
            left:0;
            width:235px;
            height:100vh;
            background:
                linear-gradient(180deg, #063845 0%, #032f39 55%, #022933 100%);
            color:#fff;
            padding:18px 14px;
            overflow-y:auto;
            z-index:1000;
            box-shadow:10px 0 28px rgba(0,0,0,0.18);
            display:flex;
            flex-direction:column;
            justify-content:space-between;
            border-right:1px solid rgba(255,255,255,0.06);
        }

        .sidebar::-webkit-scrollbar{
            width:6px;
        }

        .sidebar::-webkit-scrollbar-thumb{
            background:rgba(255,255,255,0.18);
            border-radius:10px;
        }

        .sidebar-top{
            display:flex;
            flex-direction:column;
            gap:16px;
        }

        .brand-mini{
            display:flex;
            align-items:center;
            gap:10px;
            padding:6px 8px 2px;
        }

        .brand-dot{
            width:12px;
            height:12px;
            border-radius:50%;
            background:linear-gradient(135deg, #b8e986, #8fbc67);
            box-shadow:0 0 14px rgba(184,233,134,0.45);
            flex-shrink:0;
        }

        .brand-text{
            font-size:12px;
            letter-spacing:1.2px;
            text-transform:uppercase;
            color:#c7e1e6;
            font-weight:800;
        }

        .profile-card{
            position:relative;
            background:rgba(255,255,255,0.08);
            border:1px solid rgba(255,255,255,0.10);
            border-radius:24px;
            padding:20px 14px 18px;
            text-align:center;
            box-shadow:0 12px 24px rgba(0,0,0,0.18);
            overflow:hidden;
        }

        .profile-card::before{
            content:"";
            position:absolute;
            top:0;
            left:0;
            right:0;
            height:72px;
            background:linear-gradient(135deg, rgba(143,188,103,0.35), rgba(118,179,222,0.22));
        }

        .profile-ring{
            position:relative;
            width:98px;
            height:98px;
            margin:8px auto 12px;
            padding:4px;
            border-radius:50%;
            background:linear-gradient(135deg, #d0f0a9, #8fbc67);
            box-shadow:0 10px 18px rgba(0,0,0,0.18);
            z-index:2;
        }

        .profile-img{
            width:100%;
            height:100%;
            border-radius:50%;
            object-fit:cover;
            border:3px solid #fff;
            display:block;
            background:#eee;
        }

        .profile-card h3{
            position:relative;
            font-size:26px;
            margin-bottom:6px;
            line-height:1.1;
            font-weight:800;
            z-index:2;
        }

        .profile-card p{
            position:relative;
            font-size:13px;
            color:#d9eef2;
            margin-bottom:10px;
            word-break:break-word;
            line-height:1.45;
            z-index:2;
        }

        .course-badge{
            position:relative;
            display:inline-block;
            margin-top:6px;
            padding:9px 15px;
            border-radius:999px;
            background:linear-gradient(135deg, #a3cd76, #c5ec8f);
            color:#12341b;
            font-size:12px;
            font-weight:800;
            letter-spacing:.5px;
            z-index:2;
        }

        .nav-title{
            font-size:12px;
            text-transform:uppercase;
            letter-spacing:1px;
            color:#b8d7dd;
            font-weight:800;
            margin:2px 6px 0;
        }

        .nav-group{
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .sidebar a{
            display:flex;
            align-items:flex-start;
            gap:12px;
            text-decoration:none;
            background:rgba(255,255,255,0.07);
            color:#fff;
            padding:15px 16px;
            border-radius:18px;
            font-weight:800;
            font-size:15px;
            text-align:left;
            transition:all .22s ease;
            border:1px solid rgba(255,255,255,0.08);
            box-shadow:0 6px 14px rgba(0,0,0,0.10);
            position:relative;
            overflow:hidden;
        }

        .sidebar a::before{
            content:"";
            position:absolute;
            left:0;
            top:0;
            bottom:0;
            width:0;
            background:linear-gradient(180deg, #bfe68f, #8fbc67);
            transition:width .22s ease;
            border-radius:18px;
        }

        .sidebar a span{
            position:relative;
            z-index:2;
        }

        .sidebar a:hover{
            transform:translateX(6px);
            background:rgba(255,255,255,0.14);
        }

        .sidebar a:hover::before{
            width:4px;
        }

        .sidebar a.active{
            background:linear-gradient(135deg, #86bbe3, #aad8f6);
            color:#072733;
            border:none;
            box-shadow:0 8px 18px rgba(0,0,0,0.16);
        }

        .sidebar a.active::before{
            width:5px;
            background:linear-gradient(180deg, #ffffff, #eaf7ff);
        }

        .nav-icon{
            width:22px;
            text-align:center;
            font-size:18px;
            flex-shrink:0;
            margin-top:1px;
        }

        .nav-text{
            display:block;
            line-height:1.25;
            word-break:break-word;
        }

        .logout-link{
            margin-top:18px;
            background:rgba(255,255,255,0.08) !important;
        }

        .logout-link:hover{
            background:#d94c4c !important;
            color:#fff !important;
            transform:translateX(6px);
        }

        .logout-link:hover::before{
            width:4px;
            background:#fff;
        }

        .main-content{
            margin-left:235px;
            min-height:100vh;
            width:calc(100% - 235px);
            background:var(--main-bg);
            transition:background .25s ease;
        }

        .top-header{
            background:var(--top-header-bg);
            color:var(--top-header-text);
            text-align:center;
            padding:20px 10px;
            font-size:24px;
            font-weight:bold;
            text-transform:uppercase;
            letter-spacing:0.5px;
        }

        .sub-header{
            background:var(--sub-header-bg);
            color:var(--sub-header-text);
            text-align:center;
            padding:12px 10px;
            font-size:24px;
            font-weight:bold;
            text-transform:uppercase;
            backdrop-filter:blur(8px);
            -webkit-backdrop-filter:blur(8px);
        }

        .content{
            padding:25px;
        }

        .page-actions{
            display:flex;
            justify-content:flex-end;
            margin-bottom:18px;
        }

        .theme-toggle-btn{
            background:var(--theme-btn-bg);
            color:var(--theme-btn-text);
            border:1px solid var(--theme-btn-border);
            border-radius:16px;
            padding:14px 18px;
            font-size:15px;
            font-weight:bold;
            cursor:pointer;
            transition:0.25s ease;
            backdrop-filter:blur(12px);
            -webkit-backdrop-filter:blur(12px);
            box-shadow:0 8px 22px rgba(0,0,0,0.12);
            min-width:170px;
        }

        .theme-toggle-btn:hover{
            transform:translateY(-2px);
        }

        .welcome-box{
            background:var(--card-bg);
            border:1px solid var(--card-border);
            border-radius:16px;
            padding:22px;
            margin-bottom:20px;
            box-shadow:var(--card-shadow);
            backdrop-filter:blur(14px);
            -webkit-backdrop-filter:blur(14px);
        }

        .welcome-box h2{
            color:var(--title-text);
            margin-bottom:8px;
            font-size:28px;
        }

        .welcome-box p{
            color:var(--body-text);
            font-size:15px;
            line-height:1.5;
        }

        .stats-grid{
            display:grid;
            grid-template-columns:repeat(4, 1fr);
            gap:16px;
            margin-bottom:20px;
        }

        .stat-card{
            background:var(--card-bg);
            border:1px solid var(--card-border);
            border-radius:16px;
            padding:20px;
            box-shadow:var(--card-shadow);
            text-align:center;
            backdrop-filter:blur(14px);
            -webkit-backdrop-filter:blur(14px);
        }

        .stat-card h4{
            color:var(--title-text);
            font-size:15px;
            margin-bottom:10px;
        }

        .stat-card .number{
            font-size:28px;
            font-weight:bold;
            color:var(--title-text);
        }

        .card{
            background:var(--card-bg);
            border:1px solid var(--card-border);
            border-radius:18px;
            padding:20px;
            box-shadow:var(--card-shadow);
            margin-bottom:20px;
            backdrop-filter:blur(14px);
            -webkit-backdrop-filter:blur(14px);
        }

        .card-header{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:15px;
            margin-bottom:18px;
            flex-wrap:wrap;
        }

        .card-title h3{
            color:var(--title-text);
            font-size:28px;
            margin-bottom:6px;
        }

        .card-title p{
            color:var(--muted-text);
            font-size:14px;
        }

        .action-buttons{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
        }

        .print-btn,
        .download-btn{
            color:#fff;
            border:none;
            padding:12px 28px;
            font-weight:bold;
            border-radius:12px;
            cursor:pointer;
            font-size:15px;
            box-shadow:0 4px 10px rgba(0,0,0,0.15);
            transition:0.2s ease;
        }

        .print-btn{
            background:#ff3131;
        }

        .download-btn{
            background:#1677ff;
        }

        .print-btn:hover,
        .download-btn:hover{
            opacity:0.92;
            transform:translateY(-1px);
        }

        .inside-doc-header{
            margin:10px 0 20px;
        }

        .inside-doc-header-top{
            display:grid;
            grid-template-columns:90px 1fr 90px;
            align-items:center;
            gap:12px;
        }

        .inside-logo-box{
            display:flex;
            justify-content:center;
            align-items:center;
        }

        .inside-logo{
            width:78px;
            height:78px;
            object-fit:contain;
            display:block;
        }

        .inside-doc-header-text{
            text-align:center;
            line-height:1.15;
        }

        .inside-doc-header-text h1{
            font-size:20px;
            font-weight:900;
            color:var(--title-text);
            text-transform:uppercase;
            margin:0 0 6px;
            letter-spacing:0.3px;
        }

        .inside-address{
            font-size:12px;
            color:var(--body-text);
            margin:0;
            line-height:1.35;
        }

        .inside-double-line{
            margin-top:10px;
        }

        .inside-double-line span{
            display:block;
            height:3px;
            background:#2aa66a;
            margin:3px 0;
            border-radius:2px;
        }

        .clearance-head{
            text-align:center;
            margin-bottom:18px;
            line-height:1.4;
        }

        .clearance-head .main{
            font-size:22px;
            font-weight:bold;
            color:var(--title-text);
        }

        .clearance-head .small{
            font-size:14px;
            color:var(--body-text);
        }

        .info-grid{
            display:grid;
            grid-template-columns:repeat(4, 1fr);
            gap:12px;
            margin-bottom:18px;
        }

        .info-box{
            border:1px solid var(--info-border);
            border-radius:12px;
            background:var(--info-bg);
            padding:14px;
            text-align:center;
        }

        .info-box label{
            display:block;
            font-size:13px;
            color:var(--info-label);
            margin-bottom:6px;
            font-weight:bold;
        }

        .info-box span{
            font-size:17px;
            color:var(--info-value);
            font-weight:bold;
            word-break:break-word;
        }

        .request-text{
            text-align:center;
            font-size:14px;
            color:var(--body-text);
            line-height:1.6;
            margin:8px 0 18px;
            padding:0 10px;
        }

        .table-wrap{
            overflow-x:auto;
            border-radius:14px;
        }

        .table-wrap table{
            width:100%;
            border-collapse:collapse;
            overflow:hidden;
            border-radius:14px;
            background:#ffffff !important;
        }

        .table-wrap table th{
            background:#8fbc67 !important;
            color:#000 !important;
            padding:14px 10px;
            font-size:14px;
            border:1px solid #cfcfcf !important;
        }

        .table-wrap table td{
            padding:14px 10px;
            text-align:center;
            border:1px solid #d8d8d8 !important;
            background:#ffffff !important;
            color:#222 !important;
            font-size:14px;
        }

        .table-wrap table tr:nth-child(even) td{
            background:#fafafa !important;
        }

        .status-badge{
            display:inline-block;
            min-width:100px;
            padding:8px 14px;
            border-radius:20px;
            font-weight:bold;
            font-size:13px;
        }

        .status-passed{
            background:#d4edda;
            color:#155724;
        }

        .status-failed{
            background:#f8d7da;
            color:#721c24;
        }

        .status-incomplete{
            background:#fff3cd;
            color:#856404;
        }

        .comment-text{
            font-weight:600;
            color:#222 !important;
        }

        .empty-state{
            text-align:center;
            padding:35px 20px;
            color:#666 !important;
            font-weight:bold;
        }

        #printLayout{
            display:none;
        }

        @media (max-width: 1100px){
            .stats-grid,
            .info-grid{
                grid-template-columns:repeat(2, 1fr);
            }
        }

        @media (max-width: 768px){
            .sidebar{
                position:relative;
                width:100%;
                height:auto;
                display:block;
                padding:18px 14px;
            }

            .main-content{
                margin-left:0;
                width:100%;
            }

            .content{
                padding:15px;
            }

            .stats-grid,
            .info-grid{
                grid-template-columns:1fr;
            }

            .card-title h3{
                font-size:22px;
            }

            .welcome-box h2{
                font-size:24px;
            }

            .action-buttons{
                width:100%;
            }

            .print-btn,
            .download-btn{
                width:100%;
            }

            .page-actions{
                justify-content:stretch;
            }

            .theme-toggle-btn{
                width:100%;
            }

            .inside-doc-header-top{
                grid-template-columns:1fr;
            }

            .inside-logo-box{
                display:none;
            }

            .inside-doc-header-text h1{
                font-size:17px;
            }

            .inside-address{
                font-size:11px;
            }

            .top-header{
                font-size:18px;
                padding:16px 8px;
            }

            .sub-header{
                font-size:18px;
                padding:10px 8px;
            }
        }

        @media print{
            @page{
                size:A4 portrait;
                margin:10mm;
            }

            body{
                background:#fff !important;
            }

            body *{
                visibility:hidden;
            }

            #printLayout,
            #printLayout *{
                visibility:visible;
            }

            #printLayout{
                display:block !important;
                position:absolute;
                left:0;
                top:0;
                width:100%;
                background:#fff;
            }

            .print-paper{
                width:100%;
                max-width:760px;
                margin:0 auto;
                color:#000;
                padding:0;
            }

            .print-top-mini{
                display:flex;
                justify-content:space-between;
                font-size:10px;
                margin-bottom:10px;
            }

            .print-header{
                margin-bottom:14px;
            }

            .print-header-top{
                display:grid;
                grid-template-columns:90px 1fr 90px;
                align-items:center;
                gap:12px;
                margin-bottom:10px;
            }

            .print-logo-box{
                display:flex;
                justify-content:center;
                align-items:center;
            }

            .print-logo{
                width:78px;
                height:78px;
                object-fit:contain;
                display:block;
            }

            .print-header-text{
                text-align:center;
                line-height:1.15;
            }

            .print-header-text h1{
                font-size:20px;
                font-weight:900;
                margin:0 0 6px;
                text-transform:uppercase;
                letter-spacing:0.3px;
                color:#000 !important;
            }

            .print-address{
                font-size:11px;
                margin:0;
                line-height:1.3;
                color:#000 !important;
            }

            .print-double-line{
                margin-top:8px;
            }

            .print-double-line span{
                display:block;
                height:3px;
                background:#2aa66a !important;
                margin:3px 0;
                -webkit-print-color-adjust:exact;
                print-color-adjust:exact;
            }

            .print-subhead-left{
                margin:12px 0 8px;
            }

            .print-subhead-left h3{
                font-size:14px;
                margin:0 0 2px;
                font-weight:800;
            }

            .print-subhead-left p{
                font-size:11px;
                margin:0;
            }

            .print-center-title{
                text-align:center;
                margin:8px 0 12px;
            }

            .print-center-title h2{
                font-size:16px;
                margin:0 0 4px;
                font-weight:800;
            }

            .print-center-title p{
                margin:0;
                font-size:11px;
                line-height:1.4;
            }

            .print-info-grid{
                display:grid;
                grid-template-columns:1fr 1fr;
                gap:8px;
                margin-bottom:12px;
            }

            .print-info-box{
                border:1px solid #000;
                text-align:center;
                padding:10px 8px;
                min-height:54px;
            }

            .print-info-box span{
                display:block;
                font-size:10px;
                margin-bottom:4px;
                color:#444;
                font-weight:700;
            }

            .print-info-box strong{
                font-size:12px;
                font-weight:800;
            }

            .print-message{
                text-align:center;
                font-size:11px;
                line-height:1.5;
                margin:10px auto 14px;
                max-width:92%;
            }

            .print-result-table{
                width:100%;
                border-collapse:collapse;
                font-size:10px;
            }

            .print-result-table th,
            .print-result-table td{
                border:1px solid #000;
                padding:5px 4px;
                text-align:center;
                vertical-align:middle;
                background:#fff !important;
                color:#000 !important;
            }

            .print-result-table th{
                font-weight:800;
                background:#f3f3f3 !important;
                -webkit-print-color-adjust:exact;
                print-color-adjust:exact;
            }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="sidebar">
        <div class="sidebar-top">
            <div class="brand-mini">
                <span class="brand-dot"></span>
                <span class="brand-text">Student Panel</span>
            </div>

            <div class="profile-card">
                <div class="profile-ring">
                    <img src="<?php echo $photo; ?>" alt="Profile" class="profile-img" onerror="this.src='../assets/southern.png';">
                </div>
                <h3><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h3>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
                <div class="course-badge">
                    <?php echo !empty($user['course']) ? htmlspecialchars($user['course']) : 'STUDENT'; ?>
                </div>
            </div>

            <div class="nav-title">Navigation</div>

            <div class="nav-group">
                <a href="student.php" class="<?php echo ($current_page == 'student.php') ? 'active' : ''; ?>">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Dashboard</span>
                </a>

                <a href="student_result.php" class="<?php echo ($current_page == 'student_result.php') ? 'active' : ''; ?>">
                    <span class="nav-icon">📄</span>
                    <span class="nav-text">Result</span>
                </a>

                <a href="change_password.php" class="<?php echo ($current_page == 'change_password.php') ? 'active' : ''; ?>">
                    <span class="nav-icon">🔒</span>
                    <span class="nav-text">Change Password</span>
                </a>

                <a href="all_teachers.php" class="<?php echo ($current_page == 'all_teachers.php') ? 'active' : ''; ?>">
                    <span class="nav-icon">👨‍🏫</span>
                    <span class="nav-text">List of All Teacher's in Southern</span>
                </a>
            </div>
        </div>

        <a href="../auth/logout.php" class="logout-link">
            <span class="nav-icon">↩</span>
            <span class="nav-text">Log Out</span>
        </a>
    </div>

    <div class="main-content">
        <div class="top-header">
            SOUTHERN PHILIPPINES INSTITUTE OF SCIENCE AND TECHNOLOGY
        </div>

        <div class="sub-header">
            CLEARANCE COLLEGE DEPARTMENT
        </div>

        <div class="content">

            <div class="page-actions">
                <button type="button" class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleTheme()">🌙 Dark Mode: Off</button>
            </div>

            <div class="welcome-box">
                <h2>Hi, <?php echo htmlspecialchars($user['firstname']); ?> 👋</h2>
                <p>
                    Here is your official clearance result summary. You can review your approved subjects and print this page anytime.
                </p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Total Reviewed Subjects</h4>
                    <div class="number"><?php echo $total_subjects; ?></div>
                </div>

                <div class="stat-card">
                    <h4>Passed</h4>
                    <div class="number"><?php echo $total_passed; ?></div>
                </div>

                <div class="stat-card">
                    <h4>Failed</h4>
                    <div class="number"><?php echo $total_failed; ?></div>
                </div>

                <div class="stat-card">
                    <h4>Incomplete</h4>
                    <div class="number"><?php echo $total_incomplete; ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <h3>Student Clearance Result</h3>
                        <p>Printable academic clearance record</p>
                    </div>

                    <div class="action-buttons">
                        <button class="download-btn" onclick="downloadAsImage()">DOWNLOAD</button>
                        <button class="print-btn" onclick="window.print()">PRINT</button>
                    </div>
                </div>

                <div class="inside-doc-header">
                    <div class="inside-doc-header-top">
                        <div class="inside-logo-box">
                            <img src="<?php echo $left_logo; ?>" alt="Left Logo" class="inside-logo" onerror="this.style.display='none';">
                        </div>

                        <div class="inside-doc-header-text">
                            <h1>SOUTHERN PHILIPPINES INSTITUTE<br>OF SCIENCE AND TECHNOLOGY</h1>
                            <p class="inside-address">Tia Maria Bldg. E. Aguinaldo Highway, Anabu 2A, Imus City, Cavite, 4103</p>
                        </div>

                        <div class="inside-logo-box">
                            <img src="<?php echo $right_logo; ?>" alt="Right Logo" class="inside-logo" onerror="this.style.display='none';">
                        </div>
                    </div>

                    <div class="inside-double-line">
                        <span></span>
                        <span></span>
                    </div>
                </div>

                <div class="clearance-head">
                    <div class="main">Student Clearance</div>
                    <div class="small">College Department</div>
                    <div class="small">School Year 2025-2026</div>
                </div>

                <div class="info-grid">
                    <div class="info-box">
                        <label>Name</label>
                        <span><?php echo htmlspecialchars($user['lastname'] . ', ' . $user['firstname']); ?></span>
                    </div>

                    <div class="info-box">
                        <label>Course</label>
                        <span><?php echo htmlspecialchars($user['course']); ?></span>
                    </div>

                    <div class="info-box">
                        <label>Email</label>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>

                    <div class="info-box">
                        <label>Contact</label>
                        <span><?php echo htmlspecialchars($user['contact_number']); ?></span>
                    </div>
                </div>

                <div class="request-text">
                    Good day. I would like to respectfully request clearance for this semester.
                    I have completed all required academic responsibilities. If there are remaining
                    requirements or concerns, please let me know so I can comply immediately.
                </div>

                <div class="table-wrap">
                    <table>
                        <tr>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Instructor</th>
                            <th>Comment</th>
                            <th>Status</th>
                            <th>Date Signed</th>
                        </tr>

                        <?php if (count($rows) > 0): ?>
                            <?php foreach ($rows as $index => $row): ?>
                                <?php
                                $badgeClass = 'status-passed';
                                if ($row['result'] === 'Failed') {
                                    $badgeClass = 'status-failed';
                                } elseif ($row['result'] === 'Incomplete') {
                                    $badgeClass = 'status-incomplete';
                                }
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($row['instructor_name'] ?: 'N/A'); ?></td>
                                    <td class="comment-text"><?php echo htmlspecialchars($row['comment']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($row['result']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        echo !empty($row['date_signed'])
                                            ? date("F d, Y", strtotime($row['date_signed']))
                                            : 'N/A';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">No reviewed results yet.</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="printLayout">
    <div class="print-paper">
        <div class="print-top-mini">
            <span><?php echo date("n/j/y, g:i A"); ?></span>
            <span>Student Result</span>
        </div>

        <div class="print-header">
            <div class="print-header-top">
                <div class="print-logo-box">
                    <img src="<?php echo $left_logo; ?>" alt="Left Logo" class="print-logo" onerror="this.style.display='none';">
                </div>

                <div class="print-header-text">
                    <h1>SOUTHERN PHILIPPINES INSTITUTE<br>OF SCIENCE AND TECHNOLOGY</h1>
                    <p class="print-address">Tia Maria Bldg. E. Aguinaldo Highway, Anabu 2A, Imus City, Cavite, 4103</p>
                </div>

                <div class="print-logo-box">
                    <img src="<?php echo $right_logo; ?>" alt="Right Logo" class="print-logo" onerror="this.style.display='none';">
                </div>
            </div>

            <div class="print-double-line">
                <span></span>
                <span></span>
            </div>
        </div>

        <div class="print-subhead-left">
            <h3>Student Clearance Result</h3>
            <p>Printable academic clearance record</p>
        </div>

        <div class="print-center-title">
            <h2>Student Clearance</h2>
            <p>College Department</p>
            <p>School Year 2025-2026</p>
        </div>

        <div class="print-info-grid">
            <div class="print-info-box">
                <span>Name</span>
                <strong><?php echo htmlspecialchars($full_name); ?></strong>
            </div>
            <div class="print-info-box">
                <span>Course</span>
                <strong><?php echo htmlspecialchars($user['course']); ?></strong>
            </div>
            <div class="print-info-box">
                <span>Email</span>
                <strong><?php echo htmlspecialchars($user['email']); ?></strong>
            </div>
            <div class="print-info-box">
                <span>Contact</span>
                <strong><?php echo htmlspecialchars($user['contact_number']); ?></strong>
            </div>
        </div>

        <div class="print-message">
            Good day. I would like to respectfully request clearance for this semester. I have completed all required academic responsibilities. If there are remaining requirements or concerns, please let me know so I can comply immediately.
        </div>

        <table class="print-result-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Subject</th>
                    <th>Instructor</th>
                    <th>Comment</th>
                    <th>Status</th>
                    <th>Date Signed</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) > 0): ?>
                    <?php foreach ($rows as $index => $row): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td><?php echo htmlspecialchars($row['instructor_name'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['comment']); ?></td>
                            <td><?php echo htmlspecialchars($row['result']); ?></td>
                            <td>
                                <?php
                                echo !empty($row['date_signed'])
                                    ? date("F d, Y", strtotime($row['date_signed']))
                                    : 'N/A';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No reviewed results yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script>
function applyThemeButton() {
    const btn = document.getElementById("themeToggleBtn");
    const isDark = document.documentElement.classList.contains("dark-mode");

    if (!btn) return;

    btn.textContent = isDark ? "☀️ Dark Mode: On" : "🌙 Dark Mode: Off";
}

function toggleTheme() {
    document.documentElement.classList.toggle("dark-mode");

    if (document.documentElement.classList.contains("dark-mode")) {
        localStorage.setItem("site_theme", "dark");
    } else {
        localStorage.setItem("site_theme", "light");
    }

    applyThemeButton();
}

document.addEventListener("DOMContentLoaded", function () {
    applyThemeButton();
});

function downloadAsImage() {
    const oldTemp = document.getElementById('tempDownloadWrapper');
    if (oldTemp) {
        oldTemp.remove();
    }

    const tempWrapper = document.createElement('div');
    tempWrapper.id = 'tempDownloadWrapper';
    tempWrapper.style.position = 'absolute';
    tempWrapper.style.left = '-99999px';
    tempWrapper.style.top = '0';
    tempWrapper.style.width = '900px';
    tempWrapper.style.background = '#ffffff';
    tempWrapper.style.padding = '20px';
    tempWrapper.style.zIndex = '-1';

    tempWrapper.innerHTML = `
        <style>
            .print-paper{
                width:100%;
                max-width:760px;
                margin:0 auto;
                color:#000;
                padding:0;
                background:#fff;
                font-family:Arial, sans-serif;
            }
            .print-top-mini{
                display:flex;
                justify-content:space-between;
                font-size:10px;
                margin-bottom:10px;
            }
            .print-header{
                margin-bottom:14px;
            }
            .print-header-top{
                display:grid;
                grid-template-columns:90px 1fr 90px;
                align-items:center;
                gap:12px;
                margin-bottom:10px;
            }
            .print-logo-box{
                display:flex;
                justify-content:center;
                align-items:center;
            }
            .print-logo{
                width:78px;
                height:78px;
                object-fit:contain;
                display:block;
            }
            .print-header-text{
                text-align:center;
                line-height:1.15;
            }
            .print-header-text h1{
                font-size:20px;
                font-weight:900;
                margin:0 0 6px;
                text-transform:uppercase;
                letter-spacing:0.3px;
            }
            .print-address{
                font-size:11px;
                margin:0;
                line-height:1.3;
            }
            .print-double-line{
                margin-top:8px;
            }
            .print-double-line span{
                display:block;
                height:3px;
                background:#2aa66a;
                margin:3px 0;
            }
            .print-subhead-left{
                margin:12px 0 8px;
            }
            .print-subhead-left h3{
                font-size:14px;
                margin:0 0 2px;
                font-weight:800;
            }
            .print-subhead-left p{
                font-size:11px;
                margin:0;
            }
            .print-center-title{
                text-align:center;
                margin:8px 0 12px;
            }
            .print-center-title h2{
                font-size:16px;
                margin:0 0 4px;
                font-weight:800;
            }
            .print-center-title p{
                margin:0;
                font-size:11px;
                line-height:1.4;
            }
            .print-info-grid{
                display:grid;
                grid-template-columns:1fr 1fr;
                gap:8px;
                margin-bottom:12px;
            }
            .print-info-box{
                border:1px solid #000;
                text-align:center;
                padding:10px 8px;
                min-height:54px;
            }
            .print-info-box span{
                display:block;
                font-size:10px;
                margin-bottom:4px;
                color:#444;
                font-weight:700;
            }
            .print-info-box strong{
                font-size:12px;
                font-weight:800;
            }
            .print-message{
                text-align:center;
                font-size:11px;
                line-height:1.5;
                margin:10px auto 14px;
                max-width:92%;
            }
            .print-result-table{
                width:100%;
                border-collapse:collapse;
                font-size:10px;
            }
            .print-result-table th,
            .print-result-table td{
                border:1px solid #000;
                padding:5px 4px;
                text-align:center;
                vertical-align:middle;
                background:#fff !important;
                color:#000 !important;
            }
            .print-result-table th{
                font-weight:800;
                background:#f3f3f3 !important;
            }
        </style>
        ${document.getElementById('printLayout').innerHTML}
    `;

    document.body.appendChild(tempWrapper);

    const target = tempWrapper.querySelector('.print-paper');

    html2canvas(target, {
        scale: 2,
        useCORS: true,
        backgroundColor: '#ffffff'
    }).then(function(canvas) {
        const link = document.createElement('a');
        link.download = 'student_clearance_result.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
        tempWrapper.remove();
    }).catch(function(error) {
        console.error(error);
        alert('Failed to download image.');
        tempWrapper.remove();
    });
}
</script>

</body>
</html>