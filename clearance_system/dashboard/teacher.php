<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$message = "";
$message_type = "";

/* TEACHER INFO */
$user_stmt = $conn->prepare("SELECT firstname, lastname, email, contact_number, profile_photo FROM users WHERE id = ? AND role = 'teacher'");
$user_stmt->bind_param("i", $teacher_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

if (!$user) {
    die("Teacher not found.");
}

$default_photo = "../assets/southern.png";
if (!empty($user['profile_photo']) && file_exists("../assets/uploads/profile/" . $user['profile_photo'])) {
    $photo = "../assets/uploads/profile/" . $user['profile_photo'];
} else {
    $photo = $default_photo;
}

/* RANDOM CLASS CODE FUNCTION */
function generateClassCode($length = 8) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $code;
}

/* CREATE CLASS */
if (isset($_POST['create_class'])) {
    $subject = trim($_POST['subject']);
    $course = trim($_POST['course']);

    if (!empty($subject) && !empty($course)) {

        do {
            $class_code = generateClassCode(8);
            $check = $conn->prepare("SELECT id FROM teacher_classes WHERE class_code = ?");
            $check->bind_param("s", $class_code);
            $check->execute();
            $check_result = $check->get_result();
        } while ($check_result->num_rows > 0);

        $insert = $conn->prepare("INSERT INTO teacher_classes (teacher_id, subject, course, class_code) VALUES (?, ?, ?, ?)");
        $insert->bind_param("isss", $teacher_id, $subject, $course, $class_code);

        if ($insert->execute()) {
            $message = "Class created successfully. Generated code: " . $class_code;
            $message_type = "success";
        } else {
            $message = "Failed to create class.";
            $message_type = "error";
        }
    } else {
        $message = "Please fill in all fields.";
        $message_type = "error";
    }
}

/* GET TEACHER CLASSES */
$stmt = $conn->prepare("SELECT * FROM teacher_classes WHERE teacher_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$classes = $stmt->get_result();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>

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
            --body-bg:#d6d6d6;
            --main-bg:#d6d6d6;
            --top-header-bg:#8fbc67;
            --top-header-text:#000;
            --sub-header-bg:#003b49;
            --sub-header-text:#00ff84;

            --card-bg:#ffffff;
            --card-border:transparent;
            --card-shadow:0 4px 12px rgba(0,0,0,0.08);

            --card-title:#003b49;
            --card-text:#444;
            --muted-text:#666;

            --message-success-bg:#d4edda;
            --message-success-text:#155724;
            --message-success-border:#b7dfbe;

            --message-error-bg:#f8d7da;
            --message-error-text:#721c24;
            --message-error-border:#efb7be;

            --section-title:#003b49;

            --create-btn-bg:#003b49;
            --create-btn-text:#fff;
            --create-btn-border:transparent;

            --form-card-bg:#ffffff;
            --form-card-border:transparent;
            --form-label:#003b49;
            --input-bg:#fafafa;
            --input-text:#111;
            --input-border:#ccc;
            --input-placeholder:#777;

            --save-btn-bg:#8fbc67;
            --save-btn-text:#000;

            --class-card-bg:#ffffff;
            --class-card-border:transparent;
            --class-title:#003b49;
            --class-text:#333;
            --course-badge-bg:#e8f4db;
            --course-badge-text:#264d00;
            --code-box-bg:#f7f7f7;
            --code-box-border:#bbb;
            --code-box-text:#444;
            --code-box-strong:#003b49;
            --join-btn-bg:#003b49;
            --join-btn-text:#fff;

            --empty-bg:#ffffff;
            --empty-text:#666;

            --theme-btn-bg:#ffffff;
            --theme-btn-text:#003b49;
            --theme-btn-border:#d8d8d8;
        }

        .dark-mode:root{
            --body-bg:#082f36;
            --main-bg:
                radial-gradient(circle at top right, rgba(34, 115, 84, 0.22), transparent 28%),
                radial-gradient(circle at bottom left, rgba(25, 110, 78, 0.18), transparent 30%),
                linear-gradient(135deg, #032b32 0%, #053842 55%, #032f35 100%);
            --top-header-bg:#8fbc67;
            --top-header-text:#000;
            --sub-header-bg:rgba(0,59,73,0.78);
            --sub-header-text:#00ff84;

            --card-bg:rgba(16, 70, 61, 0.38);
            --card-border:1px solid rgba(255,255,255,0.14);
            --card-shadow:0 10px 30px rgba(0,0,0,0.16);

            --card-title:#ffffff;
            --card-text:#e1efea;
            --muted-text:#d7ebe4;

            --message-success-bg:rgba(53, 117, 74, 0.35);
            --message-success-text:#eaffef;
            --message-success-border:rgba(183,223,190,0.35);

            --message-error-bg:rgba(122, 34, 45, 0.28);
            --message-error-text:#fff1f2;
            --message-error-border:rgba(239,183,190,0.35);

            --section-title:#ffffff;

            --create-btn-bg:rgba(19, 99, 74, 0.42);
            --create-btn-text:#ffffff;
            --create-btn-border:1px solid rgba(255,255,255,0.18);

            --form-card-bg:rgba(14, 67, 58, 0.38);
            --form-card-border:1px solid rgba(255,255,255,0.14);
            --form-label:#eafff8;
            --input-bg:rgba(255,255,255,0.08);
            --input-text:#ffffff;
            --input-border:rgba(255,255,255,0.16);
            --input-placeholder:#d5e6df;

            --save-btn-bg:linear-gradient(135deg, #97c96f, #b8df6c);
            --save-btn-text:#102a16;

            --class-card-bg:rgba(16, 70, 61, 0.34);
            --class-card-border:1px solid rgba(255,255,255,0.14);
            --class-title:#ffffff;
            --class-text:#e0efea;
            --course-badge-bg:rgba(184,223,108,0.16);
            --course-badge-text:#d9f4b5;
            --code-box-bg:rgba(255,255,255,0.06);
            --code-box-border:rgba(255,255,255,0.22);
            --code-box-text:#d9ebe6;
            --code-box-strong:#d7ff98;
            --join-btn-bg:rgba(5, 76, 63, 0.75);
            --join-btn-text:#fff;

            --empty-bg:rgba(16, 70, 61, 0.34);
            --empty-text:#e7f3ef;

            --theme-btn-bg:rgba(255,255,255,0.10);
            --theme-btn-text:#ffffff;
            --theme-btn-border:1px solid rgba(255,255,255,0.16);
        }

        body{
            background:var(--body-bg);
            transition:background .25s ease;
        }

        .wrapper{
            display:flex;
            min-height:100vh;
        }

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

        .profile-name{
            position:relative;
            font-size:26px;
            font-weight:800;
            margin-bottom:6px;
            line-height:1.1;
            z-index:2;
            word-break:break-word;
        }

        .profile-email{
            position:relative;
            font-size:13px;
            color:#d9eef2;
            margin-bottom:10px;
            word-break:break-word;
            line-height:1.45;
            z-index:2;
        }

        .role-badge{
            display:inline-block;
            padding:9px 15px;
            border-radius:999px;
            background:linear-gradient(135deg, #a3cd76, #c5ec8f);
            color:#12341b;
            font-size:12px;
            font-weight:800;
            letter-spacing:.5px;
            position:relative;
            z-index:2;
            margin-top:6px;
        }

        .nav-title{
            font-size:12px;
            text-transform:uppercase;
            letter-spacing:1px;
            color:#b8d7dd;
            font-weight:800;
            margin:2px 6px 0;
        }

        .sidebar-menu{
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .nav-link{
            display:flex;
            align-items:center;
            gap:12px;
            text-decoration:none;
            background:rgba(255,255,255,0.07);
            color:#fff;
            padding:15px 16px;
            min-height:52px;
            border-radius:18px;
            font-weight:800;
            font-size:15px;
            transition:all .22s ease;
            border:1px solid rgba(255,255,255,0.08);
            box-shadow:0 6px 14px rgba(0,0,0,0.10);
            position:relative;
            overflow:hidden;
        }

        .nav-link::before{
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

        .nav-link span{
            position:relative;
            z-index:2;
        }

        .nav-link:hover{
            transform:translateX(6px);
            background:rgba(255,255,255,0.14);
        }

        .nav-link:hover::before{
            width:4px;
        }

        .nav-link.active{
            background:linear-gradient(135deg, #86bbe3, #aad8f6);
            color:#072733;
            border:none;
            box-shadow:0 8px 18px rgba(0,0,0,0.16);
        }

        .nav-link.active::before{
            width:5px;
            background:linear-gradient(180deg, #ffffff, #eaf7ff);
        }

        .nav-icon{
            width:22px;
            text-align:center;
            font-size:18px;
            flex-shrink:0;
        }

        .logout-btn{
            display:flex;
            align-items:center;
            gap:12px;
            text-decoration:none;
            padding:15px 16px;
            min-height:52px;
            border-radius:18px;
            background:rgba(255,255,255,0.08);
            border:1px solid rgba(255,255,255,0.08);
            color:#fff;
            font-size:15px;
            font-weight:800;
            box-shadow:0 6px 14px rgba(0,0,0,0.10);
            transition:all .22s ease;
            position:relative;
            overflow:hidden;
            margin-top:18px;
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
            transform:translateX(6px);
            background:#d94c4c;
            color:#fff;
        }

        .logout-btn:hover::before{
            width:4px;
        }

        .main-content{
            flex:1;
            margin-left:235px;
            min-height:100vh;
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
        }

        .sub-header{
            background:var(--sub-header-bg);
            color:var(--sub-header-text);
            text-align:center;
            padding:12px 10px;
            font-size:24px;
            font-weight:bold;
            text-transform:uppercase;
            border-bottom:1px solid rgba(255,255,255,0.08);
            backdrop-filter:blur(8px);
            -webkit-backdrop-filter:blur(8px);
        }

        .content{
            padding:25px;
        }

        .welcome-box{
            background:var(--card-bg);
            border:var(--card-border);
            border-radius:22px;
            padding:24px;
            margin-bottom:20px;
            box-shadow:var(--card-shadow);
            backdrop-filter:blur(14px);
            -webkit-backdrop-filter:blur(14px);
            transition:.25s ease;
        }

        .welcome-box h2{
            color:var(--card-title);
            margin-bottom:8px;
            font-size:28px;
        }

        .welcome-box p{
            color:var(--card-text);
            font-size:15px;
            line-height:1.5;
        }

        .message{
            padding:14px 16px;
            border-radius:14px;
            margin-bottom:20px;
            font-weight:bold;
            font-size:14px;
            backdrop-filter:blur(10px);
            -webkit-backdrop-filter:blur(10px);
        }

        .message.success{
            background:var(--message-success-bg);
            color:var(--message-success-text);
            border:1px solid var(--message-success-border);
        }

        .message.error{
            background:var(--message-error-bg);
            color:var(--message-error-text);
            border:1px solid var(--message-error-border);
        }

        .top-actions{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:15px;
            margin-bottom:18px;
            flex-wrap:wrap;
        }

        .action-right{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            align-items:center;
        }

        .section-title{
            color:var(--section-title);
            font-size:28px;
            font-weight:bold;
        }

        .create-btn{
            background:var(--create-btn-bg);
            color:var(--create-btn-text);
            border:var(--create-btn-border);
            border-radius:16px;
            padding:14px 22px;
            font-size:15px;
            font-weight:bold;
            cursor:pointer;
            transition:0.25s ease;
            backdrop-filter:blur(12px);
            -webkit-backdrop-filter:blur(12px);
            box-shadow:0 8px 22px rgba(0,0,0,0.16);
        }

        .create-btn:hover{
            transform:translateY(-2px);
            opacity:0.95;
        }

        .theme-toggle-btn{
            background:var(--theme-btn-bg);
            color:var(--theme-btn-text);
            border:var(--theme-btn-border);
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

        .create-form-card{
            display:none;
            background:var(--form-card-bg);
            border:var(--form-card-border);
            border-radius:22px;
            padding:22px;
            box-shadow:var(--card-shadow);
            margin-bottom:22px;
            backdrop-filter:blur(14px);
            -webkit-backdrop-filter:blur(14px);
        }

        .create-form-card.show{
            display:block;
        }

        .create-form-card h3{
            color:var(--card-title);
            font-size:24px;
            margin-bottom:8px;
        }

        .create-form-card p{
            color:var(--muted-text);
            font-size:14px;
            margin-bottom:18px;
        }

        .form-grid{
            display:grid;
            grid-template-columns:1fr 1fr auto;
            gap:12px;
            align-items:end;
        }

        .form-group label{
            display:block;
            margin-bottom:8px;
            color:var(--form-label);
            font-weight:bold;
            font-size:14px;
        }

        .form-group input{
            width:100%;
            height:48px;
            border:1px solid var(--input-border);
            border-radius:14px;
            padding:0 14px;
            font-size:14px;
            outline:none;
            background:var(--input-bg);
            color:var(--input-text);
            backdrop-filter:blur(10px);
            -webkit-backdrop-filter:blur(10px);
        }

        .form-group input::placeholder{
            color:var(--input-placeholder);
        }

        .form-group input:focus{
            border-color:#9fdc9a;
            box-shadow:0 0 0 3px rgba(143,188,103,0.18);
        }

        .save-btn{
            height:48px;
            background:var(--save-btn-bg);
            color:var(--save-btn-text);
            border:none;
            border-radius:14px;
            padding:0 18px;
            font-size:14px;
            font-weight:bold;
            cursor:pointer;
            box-shadow:0 8px 20px rgba(0,0,0,0.14);
        }

        .save-btn:hover{
            opacity:0.94;
        }

        .class-grid{
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));
            gap:20px;
        }

        .class-card{
            background:var(--class-card-bg);
            border:var(--class-card-border);
            border-radius:24px;
            padding:24px;
            box-shadow:var(--card-shadow);
            min-height:280px;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
            transition:0.22s ease;
            backdrop-filter:blur(14px);
            -webkit-backdrop-filter:blur(14px);
        }

        .class-card:hover{
            transform:translateY(-3px);
            box-shadow:0 16px 34px rgba(0,0,0,0.20);
        }

        .class-top{
            margin-bottom:18px;
        }

        .course-badge{
            display:inline-block;
            background:var(--course-badge-bg);
            color:var(--course-badge-text);
            border:1px solid rgba(184,223,108,0.24);
            padding:8px 14px;
            border-radius:999px;
            font-size:13px;
            font-weight:bold;
            margin-bottom:14px;
        }

        .subject-name{
            font-size:30px;
            font-weight:bold;
            color:var(--class-title);
            margin-bottom:8px;
            text-transform:uppercase;
            word-break:break-word;
        }

        .teacher-name{
            font-size:17px;
            color:var(--class-text);
            margin-bottom:14px;
            font-weight:bold;
        }

        .class-code{
            background:var(--code-box-bg);
            border:1px dashed var(--code-box-border);
            border-radius:14px;
            padding:12px;
            text-align:center;
            font-size:14px;
            color:var(--code-box-text);
            margin-bottom:18px;
        }

        .class-code strong{
            color:var(--code-box-strong);
            font-size:16px;
            letter-spacing:1px;
        }

        .join-btn{
            display:inline-block;
            text-align:center;
            background:var(--join-btn-bg);
            color:var(--join-btn-text);
            text-decoration:none;
            padding:12px 20px;
            border-radius:14px;
            font-weight:bold;
            transition:0.2s ease;
            border:1px solid rgba(255,255,255,0.12);
            box-shadow:0 8px 18px rgba(0,0,0,0.14);
        }

        .join-btn:hover{
            opacity:0.95;
        }

        .empty-box{
            background:var(--empty-bg);
            border:var(--card-border);
            border-radius:22px;
            padding:40px 20px;
            text-align:center;
            color:var(--empty-text);
            box-shadow:var(--card-shadow);
            font-weight:bold;
            backdrop-filter:blur(14px);
            -webkit-backdrop-filter:blur(14px);
        }

        @media (max-width: 900px){
            .wrapper{
                flex-direction:column;
            }

            .sidebar{
                position:relative;
                width:100%;
                height:auto;
                display:block;
                min-height:auto;
            }

            .main-content{
                margin-left:0;
            }

            .content{
                padding:15px;
            }

            .welcome-box h2,
            .section-title,
            .subject-name{
                font-size:24px;
            }

            .form-grid{
                grid-template-columns:1fr;
            }

            .top-actions{
                flex-direction:column;
                align-items:stretch;
            }

            .action-right{
                width:100%;
                flex-direction:column;
            }

            .create-btn,
            .theme-toggle-btn{
                width:100%;
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
                <span class="brand-text">Teacher Panel</span>
            </div>

            <div class="profile-card">
                <div class="profile-ring">
                    <img src="<?php echo $photo; ?>" alt="Profile" class="profile-img" onerror="this.src='../assets/southern.png';">
                </div>

                <div class="profile-name">
                    <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                </div>

                <div class="profile-email">
                    <?php echo htmlspecialchars($user['email']); ?>
                </div>

                <div class="role-badge">TEACHER</div>
            </div>

            <div class="nav-title">Navigation</div>

            <div class="sidebar-menu">
                <a href="teacher.php" class="nav-link <?php echo ($current_page == 'teacher.php') ? 'active' : ''; ?>">
                    <span class="nav-icon">🏠</span>
                    <span>Dashboard</span>
                </a>

                <a href="change_password.php" class="nav-link <?php echo ($current_page == 'change_password.php') ? 'active' : ''; ?>">
                    <span class="nav-icon">🔒</span>
                    <span>Change Password</span>
                </a>
            </div>
        </div>

        <div class="sidebar-bottom">
            <a href="../auth/logout.php" class="logout-btn">
                <span class="nav-icon">↩</span>
                <span>Log Out</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            SOUTHERN PHILIPPINES INSTITUTE OF SCIENCE AND TECHNOLOGY
        </div>

        <div class="sub-header">
            TEACHER DASHBOARD
        </div>

        <div class="content">

            <div class="welcome-box">
                <h2>Hi, <?php echo htmlspecialchars($user['firstname']); ?> 👋</h2>
                <p>
                    Manage your class boards here. Create a class, get a random class code automatically, and open each class to review student requests.
                </p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="top-actions">
                <div class="section-title">My Class Boards</div>

                <div class="action-right">
                    <button type="button" class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleTheme()">🌙 Dark Mode: Off</button>
                    <button class="create-btn" onclick="toggleCreateForm()">+ Create Class</button>
                </div>
            </div>

            <div class="create-form-card" id="createFormCard">
                <h3>Create New Class</h3>
                <p>Fill in the subject and course. The system will generate a random class code automatically.</p>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="subject" placeholder="Enter subject" required>
                        </div>

                        <div class="form-group">
                            <label>Course</label>
                            <input type="text" name="course" placeholder="Example: BSIT 3" required>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="create_class" class="save-btn">Save Class</button>
                        </div>
                    </div>
                </form>
            </div>

            <?php if ($classes->num_rows > 0): ?>
                <div class="class-grid">
                    <?php while ($class = $classes->fetch_assoc()): ?>
                        <div class="class-card">
                            <div class="class-top">
                                <div class="course-badge"><?php echo htmlspecialchars($class['course']); ?></div>
                                <div class="subject-name"><?php echo htmlspecialchars($class['subject']); ?></div>
                                <div class="teacher-name"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></div>

                                <div class="class-code">
                                    Random Class Code<br>
                                    <strong><?php echo htmlspecialchars($class['class_code']); ?></strong>
                                </div>
                            </div>

                            <a href="teacher_request.php?class_id=<?php echo $class['id']; ?>" class="join-btn">Open Class</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-box">
                    No class boards yet. Click “Create Class” to add your first subject.
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function toggleCreateForm() {
    const formCard = document.getElementById("createFormCard");
    formCard.classList.toggle("show");
}

function applyThemeButton() {
    const btn = document.getElementById("themeToggleBtn");
    const isDark = document.documentElement.classList.contains("dark-mode");

    if (!btn) return;

    if (isDark) {
        btn.textContent = "☀️ Dark Mode: On";
    } else {
        btn.textContent = "🌙 Dark Mode: Off";
    }
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
</script>

</body>
</html>