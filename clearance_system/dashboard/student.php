<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$current_page = basename($_SERVER['PHP_SELF']);
$message = "";

/* STUDENT INFO */
$user_stmt = $conn->prepare("SELECT firstname, lastname, email, course, profile_photo FROM users WHERE id = ?");
$user_stmt->bind_param("i", $student_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

if (!$user) {
    die("Student not found.");
}

$photo = !empty($user['profile_photo'])
    ? "../assets/uploads/profile/" . $user['profile_photo']
    : "../assets/southern.png";

/* SAVE REQUEST */
if (isset($_POST['submit_request'])) {
    $subject = trim($_POST['subject']);
    $class_code = trim($_POST['class_code']);

    if (!empty($subject) && !empty($class_code)) {
        $check_class = $conn->prepare("SELECT id, subject FROM teacher_classes WHERE class_code = ?");
        $check_class->bind_param("s", $class_code);
        $check_class->execute();
        $class_result = $check_class->get_result();

        if ($class_result->num_rows > 0) {
            $class = $class_result->fetch_assoc();
            $class_id = $class['id'];

            if (strtolower($class['subject']) == strtolower($subject)) {
                $check_existing = $conn->prepare("SELECT id FROM class_requests WHERE student_id = ? AND class_id = ?");
                $check_existing->bind_param("ii", $student_id, $class_id);
                $check_existing->execute();
                $existing_result = $check_existing->get_result();

                if ($existing_result->num_rows > 0) {
                    $message = "You already requested this subject.";
                } else {
                    $insert = $conn->prepare("INSERT INTO class_requests (student_id, class_id, subject, status) VALUES (?, ?, ?, 'Requesting')");
                    $insert->bind_param("iis", $student_id, $class_id, $subject);

                    if ($insert->execute()) {
                        $message = "Request submitted successfully.";
                    } else {
                        $message = "Failed to submit request.";
                    }
                }
            } else {
                $message = "Subject does not match the class code.";
            }
        } else {
            $message = "Invalid class code.";
        }
    } else {
        $message = "Please fill in all fields.";
    }
}

/* STUDENT REQUESTS */
$stmt = $conn->prepare("
    SELECT cr.id, cr.subject, cr.status, cr.result, cr.comment, cr.date_signed
    FROM class_requests cr
    WHERE cr.student_id = ?
    ORDER BY cr.id DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$requests = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, sans-serif;
        }

        body{
            background:#dfe3e6;
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

        .profile-name{
            position:relative;
            font-size:26px;
            font-weight:800;
            margin-bottom:6px;
            line-height:1.1;
            z-index:2;
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

        .profile-course{
            position:relative;
            z-index:2;
            margin-top:6px;
        }

        .student-badge{
            display:inline-block;
            padding:9px 15px;
            border-radius:999px;
            background:linear-gradient(135deg, #a3cd76, #c5ec8f);
            color:#12341b;
            font-size:12px;
            font-weight:800;
            letter-spacing:.5px;
        }

        .menu-label{
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
            transform:translateX(6px);
        }

        .logout-btn:hover::before{
            width:4px;
            background:#fff;
        }

        .main-content{
            flex:1;
            margin-left:235px;
        }

        .top-header{
            background:#8fbc67;
            text-align:center;
            padding:22px 10px;
            font-size:22px;
            font-weight:bold;
            text-transform:uppercase;
            letter-spacing:.5px;
        }

        .sub-header{
            background:#003b49;
            color:#00ff84;
            text-align:center;
            padding:14px 10px;
            font-size:20px;
            font-weight:bold;
            text-transform:uppercase;
            letter-spacing:1px;
        }

        .content{
            padding:24px;
        }

        .box{
            background:#fdfdfd;
            border-radius:18px;
            padding:22px;
            margin-bottom:24px;
            box-shadow:0 6px 18px rgba(0,0,0,0.08);
        }

        .welcome-box{
            background:linear-gradient(135deg, #ffffff, #f4f8f4);
            border-left:6px solid #8fbc67;
        }

        .box h2{
            margin-bottom:15px;
            color:#003b49;
            font-size:20px;
        }

        .welcome-box h2{
            font-size:32px;
            margin-bottom:8px;
        }

        .welcome-box p{
            font-size:15px;
            color:#444;
            line-height:1.6;
        }

        .message{
            margin-bottom:15px;
            padding:14px 16px;
            border-radius:10px;
            background:#e7f5e7;
            color:#155724;
            font-weight:bold;
            border-left:5px solid #8fbc67;
        }

        .request-form{
            display:grid;
            grid-template-columns:1fr 1fr auto;
            gap:12px;
            align-items:center;
        }

        .request-form input{
            padding:13px 14px;
            border:1px solid #cfcfcf;
            border-radius:10px;
            font-size:14px;
            outline:none;
            transition:.2s ease;
        }

        .request-form input:focus{
            border-color:#8fbc67;
            box-shadow:0 0 0 3px rgba(143,188,103,0.15);
        }

        .request-form button{
            padding:13px 22px;
            border:none;
            background:#003b49;
            color:#fff;
            border-radius:10px;
            font-weight:bold;
            cursor:pointer;
            transition:.2s ease;
        }

        .request-form button:hover{
            background:#025264;
        }

        .table-title{
            font-size:24px;
            margin-bottom:18px;
            color:#003b49;
            font-weight:bold;
        }

        .table-responsive{
            width:100%;
            overflow-x:auto;
        }

        table{
            width:100%;
            border-collapse:collapse;
            min-width:800px;
        }

        table th, table td{
            border:1px solid #d7d7d7;
            padding:14px 10px;
            text-align:center;
            font-size:14px;
        }

        table th{
            background:#8fbc67;
            color:#000;
            font-size:14px;
        }

        table tr:nth-child(even){
            background:#fafafa;
        }

        .status-requesting{
            background:#fff3cd;
            color:#856404;
            font-weight:bold;
            padding:7px 14px;
            border-radius:20px;
            display:inline-block;
            min-width:150px;
        }

        .status-reviewed{
            background:#d4edda;
            color:#155724;
            font-weight:bold;
            padding:7px 14px;
            border-radius:20px;
            display:inline-block;
            min-width:100px;
        }

        .result-passed{
            color:green;
            font-weight:bold;
        }

        .result-failed{
            color:red;
            font-weight:bold;
        }

        .result-incomplete{
            color:orange;
            font-weight:bold;
        }

        .no-data{
            text-align:center;
            padding:20px;
            font-weight:bold;
            color:#666;
        }

        @media (max-width: 1000px){
            .wrapper{
                flex-direction:column;
            }

            .sidebar{
                position:relative;
                width:100%;
                height:auto;
                display:block;
                padding:18px 14px;
            }

            .main-content{
                margin-left:0;
            }

            .request-form{
                grid-template-columns:1fr;
            }

            .top-header{
                font-size:18px;
            }

            .sub-header{
                font-size:18px;
            }

            .welcome-box h2{
                font-size:26px;
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
                    <img src="<?php echo $photo; ?>" alt="Profile" class="profile-img">
                </div>

                <div class="profile-name">
                    <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                </div>

                <div class="profile-email">
                    <?php echo htmlspecialchars($user['email']); ?>
                </div>

                <div class="profile-course">
                    <span class="student-badge">
                        <?php echo !empty($user['course']) ? htmlspecialchars($user['course']) : 'STUDENT'; ?>
                    </span>
                </div>
            </div>

            <div class="menu-label">Navigation</div>

            <div class="nav-group">
                <a href="student.php" class="<?php echo ($current_page == 'student.php') ? 'active' : ''; ?>">
                    <span class="nav-icon">🏠</span>
                    <span>Dashboard</span>
                </a>

                <a href="student_result.php" class="<?php echo ($current_page == 'student_result.php') ? 'active' : ''; ?>">
                    <span class="nav-icon">📄</span>
                    <span>Result</span>
                </a>

                <a href="change_password.php" class="<?php echo ($current_page == 'change_password.php') ? 'active' : ''; ?>">
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
            STUDENT DASHBOARD
        </div>

        <div class="content">
            <div class="box welcome-box">
                <h2>Hi, <?php echo htmlspecialchars($user['firstname']); ?> 👋</h2>
                <p>Welcome to your clearance dashboard. Dito mo pwedeng i-request ang subjects at makita ang status ng clearance mo.</p>
            </div>

            <div class="box">
                <h2>Request Clearance Subject</h2>

                <?php if (!empty($message)): ?>
                    <div class="message"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <form method="POST" class="request-form">
                    <input type="text" name="subject" placeholder="Enter Subject" required>
                    <input type="text" name="class_code" placeholder="Enter Class Code" required>
                    <button type="submit" name="submit_request">Request</button>
                </form>
            </div>

            <div class="box">
                <div class="table-title">My Clearance Requests</div>

                <div class="table-responsive">
                    <table>
                        <tr>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Result</th>
                            <th>Comment</th>
                            <th>Date Signed</th>
                        </tr>

                        <?php
                        $count = 1;
                        if ($requests->num_rows > 0):
                            while ($row = $requests->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td>
                                <?php if ($row['status'] == 'Requesting'): ?>
                                    <span class="status-requesting">Waiting for Approval</span>
                                <?php elseif ($row['status'] == 'Reviewed'): ?>
                                    <span class="status-reviewed">Reviewed</span>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($row['status']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if ($row['result'] == 'Passed') {
                                    echo '<span class="result-passed">Passed</span>';
                                } elseif ($row['result'] == 'Failed') {
                                    echo '<span class="result-failed">Failed</span>';
                                } elseif ($row['result'] == 'Incomplete') {
                                    echo '<span class="result-incomplete">Incomplete</span>';
                                } else {
                                    echo 'Pending';
                                }
                                ?>
                            </td>
                            <td><?php echo !empty($row['comment']) ? htmlspecialchars($row['comment']) : '---'; ?></td>
                            <td><?php echo !empty($row['date_signed']) ? htmlspecialchars($row['date_signed']) : '---'; ?></td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="6" class="no-data">No requests found.</td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>