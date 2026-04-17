<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$current_page = basename($_SERVER['PHP_SELF']);

/* STUDENT INFO */
$user_stmt = $conn->prepare("SELECT firstname, lastname, email, course, profile_photo FROM users WHERE id = ? AND role = 'student'");
$user_stmt->bind_param("i", $student_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

if (!$user) {
    die("Student not found.");
}

$default_photo = "../assets/southern.png";
if (!empty($user['profile_photo']) && file_exists("../assets/uploads/profile/" . $user['profile_photo'])) {
    $student_photo = "../assets/uploads/profile/" . $user['profile_photo'];
} else {
    $student_photo = $default_photo;
}

/* GET TEACHER ALBUM ONLY */
$teachers = [];
$teacher_query = "SELECT id, teacher_name, teacher_photo, teacher_email, teacher_contact, teacher_department 
                  FROM teacher_album 
                  ORDER BY teacher_name ASC";
$teacher_result = $conn->query($teacher_query);

if ($teacher_result && $teacher_result->num_rows > 0) {
    while ($row = $teacher_result->fetch_assoc()) {
        $teachers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Teachers</title>
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, Helvetica, sans-serif;
        }

        body{
            background:#dfe3e6;
            color:#1b1b1b;
        }

        .page{
            display:flex;
            min-height:100vh;
        }

        /* ===== SIDEBAR ===== */
        .sidebar{
            position:fixed;
            top:0;
            left:0;
            width:235px;
            height:100vh;
            background:linear-gradient(180deg, #063845 0%, #032f39 55%, #022933 100%);
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

        .student-card{
            position:relative;
            background:rgba(255,255,255,0.08);
            border:1px solid rgba(255,255,255,0.10);
            border-radius:24px;
            padding:20px 14px 18px;
            text-align:center;
            box-shadow:0 12px 24px rgba(0,0,0,0.18);
            overflow:hidden;
        }

        .student-card::before{
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

        .student-card img{
            width:100%;
            height:100%;
            border-radius:50%;
            object-fit:cover;
            border:3px solid #fff;
            display:block;
            background:#eee;
        }

        .student-name{
            position:relative;
            font-size:26px;
            font-weight:800;
            margin-bottom:6px;
            line-height:1.1;
            z-index:2;
            word-break:break-word;
        }

        .student-email{
            position:relative;
            font-size:13px;
            color:#d9eef2;
            margin-bottom:10px;
            word-break:break-word;
            line-height:1.45;
            z-index:2;
        }

        .student-course-wrap{
            position:relative;
            z-index:2;
            margin-top:6px;
        }

        .student-course{
            display:inline-block;
            padding:9px 15px;
            border-radius:999px;
            background:linear-gradient(135deg, #a3cd76, #c5ec8f);
            color:#12341b;
            font-size:12px;
            font-weight:800;
            letter-spacing:.5px;
        }

        .nav-title{
            font-size:12px;
            text-transform:uppercase;
            letter-spacing:1px;
            color:#b8d7dd;
            font-weight:800;
            margin:2px 6px 0;
        }

        .nav-menu{
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .nav-menu a{
            display:flex;
            align-items:center;
            gap:12px;
            text-decoration:none;
            background:rgba(255,255,255,0.07);
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
        }

        .nav-menu a::before{
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

        .nav-menu a span{
            position:relative;
            z-index:2;
        }

        .nav-menu a:hover{
            transform:translateX(6px);
            background:rgba(255,255,255,0.14);
        }

        .nav-menu a:hover::before{
            width:4px;
        }

        .nav-menu a.active{
            background:linear-gradient(135deg, #86bbe3, #aad8f6);
            color:#072733;
            border:none;
            box-shadow:0 8px 18px rgba(0,0,0,0.16);
        }

        .nav-menu a.active::before{
            width:5px;
            background:linear-gradient(180deg, #ffffff, #eaf7ff);
        }

        .nav-icon{
            width:22px;
            text-align:center;
            font-size:18px;
            flex-shrink:0;
        }

        .sidebar-bottom{
            margin-top:18px;
        }

        .logout-btn{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:12px;
            text-decoration:none;
            color:#fff;
            background:rgba(255,255,255,0.08);
            padding:15px 16px;
            border-radius:18px;
            font-weight:800;
            font-size:15px;
            border:1px solid rgba(255,255,255,0.08);
            box-shadow:0 6px 14px rgba(0,0,0,0.10);
            transition:all .22s ease;
            position:relative;
            overflow:hidden;
        }

        .logout-btn::before{
            content:"";
            position:absolute;
            left:0;
            top:0;
            bottom:0;
            width:0;
            background:#fff;
            transition:width .22s ease;
            border-radius:18px;
        }

        .logout-btn span{
            position:relative;
            z-index:2;
        }

        .logout-btn:hover{
            background:#d94c4c;
            color:#fff;
            transform:translateX(6px);
        }

        .logout-btn:hover::before{
            width:4px;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content{
            flex:1;
            margin-left:235px;
            min-height:100vh;
            width:calc(100% - 235px);
        }

        .top-header{
            background:#8fbc67;
            color:#000;
            text-align:center;
            font-size:24px;
            font-weight:900;
            padding:18px 20px;
            text-transform:uppercase;
            letter-spacing:0.4px;
        }

        .sub-header{
            background:#013844;
            text-align:center;
            padding:14px;
            font-size:18px;
            font-weight:900;
            color:#00ffb3;
            letter-spacing:1px;
            text-transform:uppercase;
        }

        .content-wrap{
            padding:28px 22px;
        }

        .teacher-section{
            background:linear-gradient(135deg, #ffffff, #f7fbf9);
            border-radius:26px;
            padding:26px 22px;
            box-shadow:0 14px 34px rgba(0,0,0,0.10);
            border:1px solid rgba(0,0,0,0.04);
        }

        .teacher-section h2{
            font-size:34px;
            margin-bottom:10px;
            color:#033b46;
            font-weight:900;
        }

        .teacher-section p{
            color:#53646a;
            margin-bottom:24px;
            font-size:15px;
        }

        .teacher-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));
            gap:22px;
            align-items:start;
        }

        .teacher-card{
            background:linear-gradient(180deg, #ffffff, #f4f9fa);
            border-radius:24px;
            padding:22px 18px;
            text-align:center;
            border:1px solid #dfe8eb;
            box-shadow:0 10px 22px rgba(0,0,0,0.07);
            position:relative;
            overflow:hidden;
            transition:0.22s ease;
            min-height:330px;
        }

        .teacher-card::before{
            content:"";
            position:absolute;
            top:0;
            left:0;
            right:0;
            height:82px;
            background:linear-gradient(135deg, rgba(143,188,103,0.20), rgba(88,170,222,0.12));
        }

        .teacher-card:hover{
            transform:translateY(-4px);
            box-shadow:0 16px 28px rgba(0,0,0,0.10);
        }

        .teacher-photo-wrap{
            width:112px;
            height:112px;
            border-radius:50%;
            margin:0 auto 14px;
            padding:4px;
            background:linear-gradient(135deg, #d0f0a9, #8fbc67);
            position:relative;
            z-index:2;
            box-shadow:0 12px 20px rgba(0,0,0,0.10);
        }

        .teacher-card img{
            width:100%;
            height:100%;
            border-radius:50%;
            object-fit:cover;
            background:#eee;
            border:3px solid #fff;
        }

        .teacher-card h3{
            font-size:18px;
            margin-bottom:10px;
            color:#003b49;
            line-height:1.35;
            min-height:50px;
            display:flex;
            align-items:center;
            justify-content:center;
            position:relative;
            z-index:2;
            font-weight:900;
            text-transform:uppercase;
        }

        .teacher-card .email{
            font-size:14px;
            color:#53646a;
            margin-bottom:10px;
            word-break:break-word;
            min-height:38px;
            position:relative;
            z-index:2;
        }

        .teacher-card .contact{
            font-size:14px;
            color:#2f434a;
            margin-bottom:10px;
            min-height:20px;
            position:relative;
            z-index:2;
        }

        .teacher-card .department{
            font-size:13px;
            color:#22411f;
            font-weight:800;
            min-height:18px;
            position:relative;
            z-index:2;
            margin-bottom:12px;
        }

        .teacher-badge{
            display:inline-block;
            margin-top:10px;
            padding:10px 20px;
            border-radius:20px;
            background:#b6d56e;
            color:#16341c;
            font-size:13px;
            font-weight:800;
            position:relative;
            z-index:2;
        }

        .empty-box{
            background:linear-gradient(135deg, #f9fbfc, #f1f5f7);
            border:1px dashed #cfdadd;
            border-radius:20px;
            padding:34px;
            text-align:center;
            color:#53646a;
            font-weight:700;
        }

        @media (max-width: 900px){
            .page{
                flex-direction:column;
            }

            .sidebar{
                position:relative;
                width:100%;
                height:auto;
            }

            .main-content{
                margin-left:0;
                width:100%;
            }

            .top-header{
                font-size:18px;
            }

            .teacher-section h2{
                font-size:26px;
            }

            .teacher-grid{
                grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="page">

        <aside class="sidebar">
            <div class="sidebar-top">
                <div class="brand-mini">
                    <span class="brand-dot"></span>
                    <span class="brand-text">Student Panel</span>
                </div>

                <div class="student-card">
                    <div class="profile-ring">
                        <img src="<?php echo $student_photo; ?>" alt="Student Photo">
                    </div>
                    <div class="student-name"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></div>
                    <div class="student-email"><?php echo htmlspecialchars($user['email']); ?></div>
                    <div class="student-course-wrap">
                        <span class="student-course"><?php echo htmlspecialchars($user['course']); ?></span>
                    </div>
                </div>

                <div class="nav-title">Navigation</div>

                <div class="nav-menu">
                    <a href="student.php">
                        <span class="nav-icon">🏠</span>
                        <span>Dashboard</span>
                    </a>

                    <a href="student_result.php">
                        <span class="nav-icon">📄</span>
                        <span>Result</span>
                    </a>

                    <a href="change_password.php">
                        <span class="nav-icon">🔐</span>
                        <span>Change Password</span>
                    </a>

                    <a href="all_teachers.php" class="active">
                        <span class="nav-icon">👨‍🏫</span>
                        <span>All Teachers</span>
                    </a>
                </div>
            </div>

            <div class="sidebar-bottom">
                <a href="../auth/logout.php" class="logout-btn">
                    <span>↩</span>
                    <span>Log Out</span>
                </a>
            </div>
        </aside>

        <main class="main-content">
            <div class="top-header">SOUTHERN PHILIPPINES INSTITUTE OF SCIENCE AND TECHNOLOGY</div>
            <div class="sub-header">LIST OF ALL TEACHERS</div>

            <div class="content-wrap">
                <div class="teacher-section">
                    <h2>All Teachers in Southern</h2>
                    <p>Here is the complete list of teachers added by the administrator.</p>

                    <?php if (!empty($teachers)): ?>
                        <div class="teacher-grid">
                            <?php foreach ($teachers as $teacher): ?>
                                <?php
                                    if (!empty($teacher['teacher_photo']) && file_exists("../assets/uploads/teacher_album/" . $teacher['teacher_photo'])) {
                                        $teacher_photo = "../assets/uploads/teacher_album/" . $teacher['teacher_photo'];
                                    } else {
                                        $teacher_photo = "../assets/southern.png";
                                    }
                                ?>
                                <div class="teacher-card">
                                    <div class="teacher-photo-wrap">
                                        <img src="<?php echo $teacher_photo; ?>" alt="Teacher Photo">
                                    </div>

                                    <h3><?php echo htmlspecialchars($teacher['teacher_name']); ?></h3>

                                    <div class="email">
                                        <?php echo !empty($teacher['teacher_email']) ? htmlspecialchars($teacher['teacher_email']) : 'No email available'; ?>
                                    </div>

                                    <div class="contact">
                                        <?php echo !empty($teacher['teacher_contact']) ? htmlspecialchars($teacher['teacher_contact']) : 'No contact available'; ?>
                                    </div>

                                    <div class="department">
                                        <?php echo !empty($teacher['teacher_department']) ? htmlspecialchars($teacher['teacher_department']) : 'Teacher'; ?>
                                    </div>

                                    <div class="teacher-badge">TEACHER</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-box">
                            No teachers found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

    </div>
</body>
</html>