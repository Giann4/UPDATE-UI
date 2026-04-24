<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Student ID not found.");
}

$student_id = intval($_GET['id']);

/* STUDENT INFO */
$user_stmt = $conn->prepare("SELECT firstname, lastname, email, contact_number, course, profile_photo FROM users WHERE id = ? AND role = 'student'");
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Student Result View</title>
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, sans-serif;
        }

        :root{
            --bg-main:#d9d9d9;
            --sidebar1:rgba(6,56,69,0.90);
            --sidebar2:rgba(3,47,57,0.90);
            --header:#8fbc67;
            --subheader:#003b49;
            --subheader-text:#00ff84;
            --text:#0f172a;
            --muted:#64748b;
            --shadow:0 12px 30px rgba(0,0,0,0.10);
        }

        body{
            background:#d9d9d9;
        }

        .wrapper{
            display:flex;
            min-height:100vh;
        }

        /* SIDEBAR */
        .sidebar{
            position:fixed;
            top:0;
            left:0;
            width:250px;
            height:100vh;
            background:linear-gradient(180deg, var(--sidebar1) 0%, var(--sidebar2) 100%);
            color:#fff;
            padding:18px 14px;
            overflow-y:auto;
            z-index:1000;
            border-right:1px solid rgba(255,255,255,0.08);
            box-shadow:16px 0 38px rgba(0,0,0,0.16);
            display:flex;
            flex-direction:column;
            justify-content:space-between;
            backdrop-filter:blur(12px);
            -webkit-backdrop-filter:blur(12px);
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
            background:linear-gradient(135deg, #d7ff96, #8fbc67);
            box-shadow:0 0 15px rgba(184,233,134,0.55);
            flex-shrink:0;
        }

        .brand-text{
            font-size:12px;
            letter-spacing:1.4px;
            text-transform:uppercase;
            color:#cce6ea;
            font-weight:900;
        }

        .profile-box{
            position:relative;
            background:linear-gradient(180deg, rgba(255,255,255,0.14), rgba(255,255,255,0.06));
            border:1px solid rgba(255,255,255,0.10);
            border-radius:28px;
            padding:22px 14px 18px;
            text-align:center;
            box-shadow:0 18px 30px rgba(0,0,0,0.18);
            overflow:hidden;
            backdrop-filter:blur(10px);
            -webkit-backdrop-filter:blur(10px);
        }

        .profile-box::before{
            content:"";
            position:absolute;
            top:0;
            left:0;
            right:0;
            height:86px;
            background:linear-gradient(135deg, rgba(143,188,103,0.42), rgba(108,190,255,0.18));
        }

        .profile-icon-wrap{
            position:relative;
            width:104px;
            height:104px;
            margin:8px auto 12px;
            padding:4px;
            border-radius:50%;
            background:linear-gradient(135deg, #edffd0, #8fbc67);
            box-shadow:0 12px 22px rgba(0,0,0,0.18);
            z-index:2;
            overflow:hidden;
        }

        .profile-icon{
            width:100%;
            height:100%;
            border-radius:50%;
            object-fit:cover;
            display:block;
            border:3px solid #fff;
            background:#fff;
        }

        .profile-box h3{
            position:relative;
            font-size:28px;
            margin-bottom:6px;
            font-weight:900;
            line-height:1.08;
            z-index:2;
            color:#fff;
            text-transform:uppercase;
        }

        .profile-box p{
            position:relative;
            font-size:13px;
            color:#daf2f6;
            margin-bottom:10px;
            word-break:break-word;
            line-height:1.45;
            z-index:2;
        }

        .admin-badge{
            display:inline-block;
            padding:10px 16px;
            border-radius:999px;
            background:linear-gradient(135deg, #0fd36f, #35e883);
            color:#fff;
            font-size:12px;
            font-weight:900;
            letter-spacing:.6px;
            position:relative;
            z-index:2;
            box-shadow:0 10px 18px rgba(16, 201, 107, 0.25);
        }

        .menu-label{
            font-size:12px;
            text-transform:uppercase;
            letter-spacing:1.2px;
            color:#b8d7dd;
            font-weight:900;
            margin:4px 6px 0;
        }

        .nav-group{
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .side-btn{
            display:flex;
            align-items:center;
            gap:12px;
            width:100%;
            text-decoration:none;
            background:rgba(255,255,255,0.08);
            color:#fff;
            padding:15px 16px;
            border-radius:18px;
            font-weight:800;
            font-size:15px;
            transition:all .22s ease;
            border:1px solid rgba(255,255,255,0.08);
            box-shadow:0 6px 14px rgba(0,0,0,0.10);
            position:relative;
            overflow:hidden;
            backdrop-filter:blur(8px);
            -webkit-backdrop-filter:blur(8px);
        }

        .side-btn::before{
            content:"";
            position:absolute;
            left:0;
            top:0;
            bottom:0;
            width:0;
            background:linear-gradient(180deg, #d9ff9f, #8fbc67);
            transition:width .22s ease;
            border-radius:18px;
        }

        .side-btn span{
            position:relative;
            z-index:2;
        }

        .side-btn:hover{
            transform:translateX(6px);
            background:rgba(255,255,255,0.14);
        }

        .side-btn:hover::before{
            width:4px;
        }

        .side-btn.active{
            background:linear-gradient(135deg, #dff4ff, #bde5ff);
            color:#062d38;
            border:none;
            box-shadow:0 12px 22px rgba(0,0,0,0.14);
        }

        .side-btn.active::before{
            width:5px;
            background:linear-gradient(180deg, #ffffff, #eaf7ff);
        }

        .sidebar-bottom{
            margin-top:18px;
        }

        .logout-btn{
            background:rgba(255,255,255,0.08) !important;
        }

        .logout-btn:hover{
            background:#d94c4c !important;
            color:#fff !important;
        }

        .logout-btn:hover::before{
            width:4px;
            background:#fff;
        }

        /* MAIN */
        .main-content{
            flex:1;
            margin-left:250px;
        }

        .top-header{
            background:#8fbc67;
            text-align:center;
            padding:20px 10px;
            font-size:24px;
            font-weight:bold;
            text-transform:uppercase;
            letter-spacing:0.5px;
        }

        .sub-header{
            background:#003b49;
            color:#00ff84;
            text-align:center;
            padding:12px 10px;
            font-size:24px;
            font-weight:bold;
            text-transform:uppercase;
        }

        .content{
            padding:25px;
        }

        .welcome-box{
            background:#fff;
            border-radius:16px;
            padding:22px;
            margin-bottom:20px;
            box-shadow:0 4px 12px rgba(0,0,0,0.08);
        }

        .welcome-box h2{
            color:#003b49;
            margin-bottom:8px;
            font-size:28px;
        }

        .welcome-box p{
            color:#444;
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
            background:#fff;
            border-radius:16px;
            padding:20px;
            box-shadow:0 4px 12px rgba(0,0,0,0.08);
            text-align:center;
        }

        .stat-card h4{
            color:#003b49;
            font-size:15px;
            margin-bottom:10px;
        }

        .stat-card .number{
            font-size:28px;
            font-weight:bold;
        }

        .card{
            background:#fff;
            border-radius:18px;
            padding:20px;
            box-shadow:0 4px 12px rgba(0,0,0,0.08);
            margin-bottom:20px;
        }

        .card-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:15px;
            margin-bottom:18px;
            flex-wrap:wrap;
        }

        .card-title h3{
            color:#003b49;
            font-size:28px;
            margin-bottom:6px;
        }

        .card-title p{
            color:#555;
            font-size:14px;
        }

        .action-buttons{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
        }

        .print-btn,
        .download-btn,
        .back-btn{
            color:#fff;
            border:none;
            padding:12px 28px;
            font-weight:bold;
            border-radius:12px;
            cursor:pointer;
            font-size:15px;
            box-shadow:0 4px 10px rgba(0,0,0,0.15);
            transition:0.2s ease;
            text-decoration:none;
            display:inline-block;
        }

        .print-btn{
            background:#ff3131;
        }

        .download-btn{
            background:#1677ff;
        }

        .back-btn{
            background:#198754;
        }

        .print-btn:hover,
        .download-btn:hover,
        .back-btn:hover{
            opacity:0.92;
            transform:translateY(-1px);
        }

        .clearance-head{
            text-align:center;
            margin-bottom:18px;
            line-height:1.4;
        }

        .clearance-head .small{
            font-size:14px;
            color:#333;
        }

        .clearance-head .main{
            font-size:22px;
            font-weight:bold;
            color:#003b49;
        }

        .info-grid{
            display:grid;
            grid-template-columns:repeat(4, 1fr);
            gap:12px;
            margin-bottom:18px;
        }

        .info-box{
            border:1px solid #d4d4d4;
            border-radius:12px;
            background:#f8f8f8;
            padding:14px;
            text-align:center;
        }

        .info-box label{
            display:block;
            font-size:13px;
            color:#666;
            margin-bottom:6px;
            font-weight:bold;
        }

        .info-box span{
            font-size:17px;
            color:#111;
            font-weight:bold;
            word-break:break-word;
        }

        .request-text{
            text-align:center;
            font-size:14px;
            color:#333;
            line-height:1.6;
            margin:8px 0 18px;
            padding:0 10px;
        }

        .table-wrap{
            overflow-x:auto;
            border-radius:14px;
        }

        table{
            width:100%;
            border-collapse:collapse;
            overflow:hidden;
            border-radius:14px;
        }

        table th{
            background:#8fbc67;
            color:#000;
            padding:14px 10px;
            font-size:14px;
            border:1px solid #cfcfcf;
        }

        table td{
            padding:14px 10px;
            text-align:center;
            border:1px solid #d8d8d8;
            background:#fff;
            font-size:14px;
        }

        table tr:nth-child(even) td{
            background:#fafafa;
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
            color:#333;
        }

        .empty-state{
            text-align:center;
            padding:35px 20px;
            color:#666;
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

        @media (max-width: 900px){
            .wrapper{
                flex-direction:column;
            }

            .sidebar{
                width:100%;
                height:auto;
                position:relative;
            }

            .main-content{
                margin-left:0;
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
            .download-btn,
            .back-btn{
                width:100%;
                text-align:center;
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
                margin-bottom:8px;
            }

            .print-header{
                text-align:center;
                margin-bottom:8px;
            }

            .print-header h1{
                font-size:18px;
                font-weight:900;
                line-height:1.2;
                margin:0 0 8px;
                text-transform:uppercase;
            }

            .print-line{
                border-top:1.5px solid #000;
                margin:6px 0;
            }

            .print-header h2{
                font-size:14px;
                font-weight:800;
                margin:0;
                text-transform:uppercase;
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
                <span class="brand-text">Admin Panel</span>
            </div>

            <div class="profile-box">
                <div class="profile-icon-wrap">
                    <img src="<?php echo $photo; ?>" alt="Profile" class="profile-icon" onerror="this.src='../assets/southern.png';">
                </div>
                <h3><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h3>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
                <div class="admin-badge">STUDENT VIEW</div>
            </div>

            <div class="menu-label">Navigation</div>

            <div class="nav-group">
                <a href="admin.php?view=students" class="side-btn"><span>🏠 Back to Students</span></a>
                <a href="#" class="side-btn active"><span>📄 Student Result Copy</span></a>
            </div>
        </div>

        <div class="sidebar-bottom">
            <a href="../auth/logout.php" class="side-btn logout-btn"><span>↩ Log Out</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            SOUTHERN PHILIPPINES INSTITUTE OF SCIENCE AND TECHNOLOGY
        </div>

        <div class="sub-header">
            CLEARANCE COLLEGE DEPARTMENT
        </div>

        <div class="content">

            <div class="welcome-box">
                <h2>Admin View - <?php echo htmlspecialchars($user['firstname']); ?> 👋</h2>
                <p>
                    This is the copied clearance result view of the selected student. The admin can review, print, and keep a record of the student clearance result.
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
                        <p>Printable academic clearance record - Admin Copy</p>
                    </div>

                    <div class="action-buttons">
                        <a href="admin.php?view=students" class="back-btn">BACK</a>
                        <button class="download-btn" onclick="downloadAsImage()">DOWNLOAD</button>
                        <button class="print-btn" onclick="window.print()">PRINT</button>
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

<!-- PRINT-ONLY LAYOUT -->
<div id="printLayout">
    <div class="print-paper">
        <div class="print-top-mini">
            <span><?php echo date("n/j/y, g:i A"); ?></span>
            <span>Admin Copy - Student Result</span>
        </div>

        <div class="print-header">
            <h1>SOUTHERN PHILIPPINES INSTITUTE OF SCIENCE AND TECHNOLOGY</h1>
            <div class="print-line"></div>
            <h2>CLEARANCE COLLEGE DEPARTMENT</h2>
        </div>

        <div class="print-subhead-left">
            <h3>Student Clearance Result</h3>
            <p>Printable academic clearance record - Admin Copy</p>
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
                margin-bottom:8px;
            }

            .print-header{
                text-align:center;
                margin-bottom:8px;
            }

            .print-header h1{
                font-size:18px;
                font-weight:900;
                line-height:1.2;
                margin:0 0 8px;
                text-transform:uppercase;
            }

            .print-line{
                border-top:1.5px solid #000;
                margin:6px 0;
            }

            .print-header h2{
                font-size:14px;
                font-weight:800;
                margin:0;
                text-transform:uppercase;
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
        link.download = 'admin_student_clearance_result.png';
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