<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$courseFilter = isset($_GET['course']) ? trim($_GET['course']) : '';
$viewRole = isset($_GET['view']) ? trim($_GET['view']) : 'students';
$current_page = basename($_SERVER['PHP_SELF']);

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

if ($viewRole === 'students') {
    $sql .= " AND role = 'student'";
} elseif ($viewRole === 'teachers') {
    $sql .= " AND role = 'teacher'";
}

if (!empty($courseFilter) && $viewRole === 'students') {
    $sql .= " AND course = ?";
    $params[] = $courseFilter;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR contact_number LIKE ?)";
    $searchLike = "%" . $search . "%";
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= "ssss";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$totalStudentsQuery = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student'");
$totalStudents = $totalStudentsQuery->fetch_assoc()['total'];

$totalTeachersQuery = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='teacher'");
$totalTeachers = $totalTeachersQuery->fetch_assoc()['total'];

$adminName = isset($_SESSION['name']) && !empty($_SESSION['name']) ? $_SESSION['name'] : 'Administrator';

/* ADMIN PHOTO */
$default_admin_photo = "../assets/southern.png";
$admin_photo = $default_admin_photo;
$admin_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

if ($admin_id > 0) {
    $admin_stmt = $conn->prepare("SELECT profile_photo FROM admin WHERE id = ?");
    $admin_stmt->bind_param("i", $admin_id);
    $admin_stmt->execute();
    $admin_data = $admin_stmt->get_result()->fetch_assoc();

    if ($admin_data && !empty($admin_data['profile_photo']) && file_exists("../assets/uploads/admin/" . $admin_data['profile_photo'])) {
        $admin_photo = "../assets/uploads/admin/" . $admin_data['profile_photo'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background:
                radial-gradient(circle at top left, rgba(18, 201, 107, 0.08), transparent 28%),
                radial-gradient(circle at bottom right, rgba(3, 59, 70, 0.10), transparent 30%),
                #f4f7f8;
            color: #1b1b1b;
            transition: background 0.25s ease, color 0.25s ease;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 235px;
            height: 100vh;
            background: linear-gradient(180deg, #063845 0%, #032f39 55%, #022933 100%);
            color: #fff;
            padding: 18px 14px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 10px 0 28px rgba(0,0,0,0.18);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-right: 1px solid rgba(255,255,255,0.06);
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.18);
            border-radius: 10px;
        }

        .sidebar-top {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .brand-mini {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 8px 2px;
        }

        .brand-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: linear-gradient(135deg, #b8e986, #8fbc67);
            box-shadow: 0 0 14px rgba(184,233,134,0.45);
            flex-shrink: 0;
        }

        .brand-text {
            font-size: 12px;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #c7e1e6;
            font-weight: 800;
        }

        .profile-box {
            position: relative;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 24px;
            padding: 20px 14px 18px;
            text-align: center;
            box-shadow: 0 12px 24px rgba(0,0,0,0.18);
            overflow: hidden;
        }

        .profile-box::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 72px;
            background: linear-gradient(135deg, rgba(143,188,103,0.35), rgba(118,179,222,0.22));
        }

        .profile-icon-wrap {
            position: relative;
            width: 98px;
            height: 98px;
            margin: 8px auto 12px;
            padding: 4px;
            border-radius: 50%;
            background: linear-gradient(135deg, #d0f0a9, #8fbc67);
            box-shadow: 0 10px 18px rgba(0,0,0,0.18);
            z-index: 2;
            overflow: hidden;
        }

        .profile-icon {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            display: block;
            border: 3px solid #fff;
            background: #ffffff;
        }

        .profile-box h3 {
            position: relative;
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 6px;
            line-height: 1.1;
            z-index: 2;
            letter-spacing: 0.5px;
        }

        .profile-box p {
            position: relative;
            font-size: 13px;
            color: #d9eef2;
            margin-bottom: 10px;
            word-break: break-word;
            line-height: 1.45;
            z-index: 2;
        }

        .admin-badge {
            display: inline-block;
            padding: 9px 15px;
            border-radius: 999px;
            background: linear-gradient(135deg, #10c96b, #2de07f);
            color: #ffffff;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .5px;
            position: relative;
            z-index: 2;
            box-shadow: 0 8px 18px rgba(16, 201, 107, 0.25);
        }

        .menu-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #b8d7dd;
            font-weight: 800;
            margin: 2px 6px 0;
        }

        .nav-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .side-btn,
        .dropdown-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            text-align: center;
            text-decoration: none;
            background: rgba(255,255,255,0.07);
            color: #fff;
            padding: 15px 16px;
            border-radius: 18px;
            font-weight: 800;
            font-size: 15px;
            transition: all .22s ease;
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 6px 14px rgba(0,0,0,0.10);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .side-btn::before,
        .dropdown-btn::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 0;
            background: linear-gradient(180deg, #bfe68f, #8fbc67);
            transition: width .22s ease;
            border-radius: 18px;
        }

        .side-btn span,
        .dropdown-btn span {
            position: relative;
            z-index: 2;
        }

        .side-btn:hover,
        .dropdown-btn:hover {
            transform: translateX(6px);
            background: rgba(255,255,255,0.14);
        }

        .side-btn:hover::before,
        .dropdown-btn:hover::before {
            width: 4px;
        }

        .side-btn.active,
        .dropdown-btn.active {
            background: linear-gradient(135deg, #18c96d, #36df84);
            color: #ffffff;
            border: none;
            box-shadow: 0 8px 18px rgba(16, 201, 107, 0.20);
        }

        .side-btn.active::before,
        .dropdown-btn.active::before {
            width: 5px;
            background: linear-gradient(180deg, #ffffff, #eaf7ff);
        }

        .dropdown {
            margin-bottom: 2px;
        }

        .dropdown-content {
            display: none;
            padding: 6px 8px 0;
        }

        .dropdown-content.show {
            display: block;
        }

        .dropdown-content a {
            display: block;
            text-decoration: none;
            background: rgba(255,255,255,0.10);
            color: #fff;
            padding: 12px 14px;
            border-radius: 14px;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 700;
            transition: 0.25s ease;
            border: 1px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .dropdown-content a:hover {
            background: rgba(18, 201, 107, 0.85);
            color: #fff;
            transform: translateX(3px);
        }

        .sidebar-bottom {
            margin-top: 18px;
        }

        .logout-btn {
            background: rgba(255,255,255,0.08) !important;
        }

        .logout-btn:hover {
            background: #d94c4c !important;
            color: #fff !important;
            transform: translateX(6px);
        }

        .logout-btn:hover::before {
            width: 4px;
            background: #fff;
        }

        .main-content {
            flex: 1;
            margin-left: 235px;
            min-width: 0;
        }

        .top-header {
            background: linear-gradient(135deg, #98c76b, #85b95d);
            color: #111;
            text-align: center;
            padding: 24px 20px;
            font-size: 25px;
            font-weight: 900;
            letter-spacing: 0.5px;
            transition: background 0.25s ease, color 0.25s ease;
        }

        .sub-header {
            background: #033b46;
            color: #00ff8c;
            text-align: center;
            padding: 14px 20px;
            font-size: 21px;
            font-weight: 900;
            letter-spacing: 0.4px;
            transition: background 0.25s ease, color 0.25s ease;
        }

        .content-area {
            padding: 28px 24px;
        }

        .top-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .search-form {
            flex: 1;
            min-width: 320px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-form input[type="text"] {
            flex: 1;
            min-width: 0;
            height: 54px;
            border: 2px solid #d6dee2;
            border-radius: 16px;
            padding: 0 18px;
            font-size: 15px;
            outline: none;
            transition: 0.25s ease;
            background: #fff;
            color: #1b1b1b;
        }

        .search-form input[type="text"]:focus {
            border-color: #12c96b;
            box-shadow: 0 0 0 4px rgba(18, 201, 107, 0.12);
        }

        .search-form button,
        .darkmode-toggle {
            height: 54px;
            padding: 0 24px;
            border: none;
            border-radius: 16px;
            color: #fff;
            font-weight: 800;
            cursor: pointer;
            transition: 0.25s ease;
        }

        .search-form button {
            background: linear-gradient(135deg, #0fb761, #0a944d);
        }

        .darkmode-toggle {
            background: #1f2937;
            color: #fff;
        }

        .search-form button:hover,
        .darkmode-toggle:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(0, 0, 0, 0.18);
        }

        .totals-box {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #fff;
            padding: 12px 16px;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
            font-weight: 800;
            color: #163037;
            transition: background 0.25s ease, color 0.25s ease, box-shadow 0.25s ease;
        }

        .count {
            min-width: 56px;
            height: 42px;
            border-radius: 12px;
            background: #f2f7f4;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            color: #0a944d;
            border: 2px solid #d8ebe0;
            transition: background 0.25s ease, color 0.25s ease, border-color 0.25s ease;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.07);
            padding: 14px;
            transition: background 0.25s ease, box-shadow 0.25s ease;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 980px;
            background: #fff;
        }

        thead th {
            background: linear-gradient(135deg, #eaf4eb, #dfeee2);
            color: #12353b;
            font-size: 15px;
            font-weight: 900;
            padding: 16px 12px;
            text-align: center;
            border-bottom: 2px solid #d8e3dd;
            transition: background 0.25s ease, color 0.25s ease, border-color 0.25s ease;
        }

        tbody td {
            padding: 15px 12px;
            text-align: center;
            border-bottom: 1px solid #e8eef0;
            font-size: 15px;
            vertical-align: middle;
            color: #1b1b1b;
            background: #fff;
            transition: background 0.25s ease, color 0.25s ease, border-color 0.25s ease;
        }

        tbody tr {
            background: #fff;
        }

        tbody tr:hover {
            background: #f8fcf9;
        }

        tbody tr:hover td {
            background: #f8fcf9;
        }

        .role-badge {
            display: inline-block;
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.4px;
        }

        .role-student {
            background: #e6f8ee;
            color: #0a944d;
        }

        .role-teacher {
            background: #e7f1ff;
            color: #1864c7;
        }

        .course-badge {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 999px;
            background: #f1f3f5;
            color: #374047;
            font-size: 12px;
            font-weight: 800;
            transition: background 0.25s ease, color 0.25s ease;
        }

        .password-mask {
            color: #6b7479;
            letter-spacing: 2px;
            font-weight: 700;
        }

        .action-group {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-btn {
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 12px;
            color: #fff;
            font-size: 13px;
            font-weight: 800;
            transition: 0.25s ease;
            display: inline-block;
            min-width: 72px;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        }

        .view-btn {
            background: #27a8f3;
        }

        .edit-btn {
            background: #f4c542;
            color: #1d1d1d;
        }

        .delete-btn {
            background: #ff5d57;
        }

        .empty-row {
            text-align: center;
            font-weight: 700;
            color: #6b7479;
            padding: 26px 10px;
        }

        /* DARK MODE - PAGE DARK, TABLE STAYS WHITE */
        body.dark-mode {
            background: #0f172a;
            color: #e5e7eb;
        }

        body.dark-mode .top-header {
            background: #6f9f4f;
            color: #f8fafc;
        }

        body.dark-mode .sub-header {
            background: #022c3a;
            color: #6fffc0;
        }

        body.dark-mode .totals-box {
            background: #111827;
            color: #e5e7eb;
            box-shadow: 0 12px 30px rgba(0,0,0,0.28);
        }

        body.dark-mode .count {
            background: #1f2937;
            color: #6ee7b7;
            border-color: #334155;
        }

        body.dark-mode .search-form input[type="text"] {
            background: #111827;
            color: #f8fafc;
            border-color: #334155;
        }

        body.dark-mode .search-form input[type="text"]::placeholder {
            color: #94a3b8;
        }

        body.dark-mode .darkmode-toggle {
            background: #6f9f4f;
            color: #f8fafc;
        }

        body.dark-mode .table-wrap {
            background: #ffffff !important;
            color: #1b1b1b !important;
            box-shadow: 0 12px 30px rgba(0,0,0,0.28);
        }

        body.dark-mode table {
            background: #ffffff !important;
        }

        body.dark-mode thead th {
            background: linear-gradient(135deg, #eaf4eb, #dfeee2) !important;
            color: #12353b !important;
            border-bottom-color: #d8e3dd !important;
        }

        body.dark-mode tbody tr {
            background: #ffffff !important;
        }

        body.dark-mode tbody td {
            color: #1b1b1b !important;
            border-bottom: 1px solid #e8eef0 !important;
            background: #ffffff !important;
        }

        body.dark-mode tbody tr:hover {
            background: #f8fcf9 !important;
        }

        body.dark-mode tbody tr:hover td {
            background: #f8fcf9 !important;
        }

        body.dark-mode .course-badge {
            background: #f1f3f5 !important;
            color: #374047 !important;
        }

        body.dark-mode .role-student {
            background: #e6f8ee !important;
            color: #0a944d !important;
        }

        body.dark-mode .role-teacher {
            background: #e7f1ff !important;
            color: #1864c7 !important;
        }

        body.dark-mode .password-mask,
        body.dark-mode .empty-row {
            color: #6b7479 !important;
        }

        body.dark-mode .dropdown-content a {
            background: rgba(255,255,255,0.08);
            color: #f8fafc;
            border-color: rgba(255,255,255,0.08);
        }

        body.dark-mode .dropdown-content a:hover {
            background: rgba(18, 201, 107, 0.85);
            color: #fff;
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 220px;
                padding: 20px 14px;
            }

            .profile-box h3 {
                font-size: 28px;
            }

            .main-content {
                margin-left: 220px;
            }

            .top-header {
                font-size: 20px;
            }

            .sub-header {
                font-size: 17px;
            }
        }

        @media (max-width: 700px) {
            .admin-wrapper {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                width: 100%;
                margin-left: 0;
            }

            .search-form {
                min-width: 100%;
            }

            .top-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .totals-box {
                justify-content: space-between;
            }

            .search-form button,
            .darkmode-toggle {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <div class="sidebar">
        <div class="sidebar-top">
            <div class="brand-mini">
                <span class="brand-dot"></span>
                <span class="brand-text">Admin Panel</span>
            </div>

            <div class="profile-box">
                <div class="profile-icon-wrap">
                    <img src="<?php echo $admin_photo; ?>" alt="Admin Profile" class="profile-icon" onerror="this.src='../assets/southern.png';">
                </div>
                <h3>ADMIN</h3>
                <p><?php echo htmlspecialchars($adminName); ?></p>
                <div class="admin-badge">ADMIN PANEL</div>
            </div>

            <div class="menu-label">Navigation</div>

            <div class="dropdown">
                <button
                    type="button"
                    class="dropdown-btn <?php echo ($viewRole === 'students' && !empty($courseFilter)) ? 'active' : ''; ?>"
                    onclick="toggleDropdown()"
                >
                    <span>DASHBOARD ⬇</span>
                </button>

                <div id="dropdownContent" class="dropdown-content <?php echo (!empty($courseFilter) && $viewRole === 'students') ? 'show' : ''; ?>">
                    <a href="admin.php?view=students&course=BSIT%201">BSIT 1</a>
                    <a href="admin.php?view=students&course=BSIT%202">BSIT 2</a>
                    <a href="admin.php?view=students&course=BSIT%203">BSIT 3</a>
                    <a href="admin.php?view=students&course=BSIT%204">BSIT 4</a>
                    <a href="admin.php?view=students">All Students</a>
                </div>
            </div>

            <div class="nav-group">
                <a class="side-btn <?php echo ($viewRole === 'teachers') ? 'active' : ''; ?>" href="admin.php?view=teachers">
                    <span>List of Teacher</span>
                </a>

                <a class="side-btn <?php echo ($viewRole === 'students' && empty($courseFilter)) ? 'active' : ''; ?>" href="admin.php?view=students">
                    <span>List of Students</span>
                </a>

                <a class="side-btn <?php echo ($current_page === 'admin_teacher_album.php') ? 'active' : ''; ?>" href="admin_teacher_album.php">
                    <span>Teacher Album</span>
                </a>

                <a class="side-btn <?php echo ($current_page === 'admin_change_password.php') ? 'active' : ''; ?>" href="admin_change_password.php">
                    <span>Change Password</span>
                </a>
            </div>
        </div>

        <div class="sidebar-bottom">
            <a class="side-btn logout-btn" href="../auth/logout.php">
                <span>Log Out</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">SOUTHERN PHILIPPINES INSTITUTE OF SCIENCE AND TECHNOLOGY</div>
        <div class="sub-header">CLEARANCE COLLEGE DEPARTMENT</div>

        <div class="content-area">
            <div class="top-controls">
                <form method="GET" action="admin.php" class="search-form">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($viewRole); ?>">
                    <?php if (!empty($courseFilter) && $viewRole === 'students'): ?>
                        <input type="hidden" name="course" value="<?php echo htmlspecialchars($courseFilter); ?>">
                    <?php endif; ?>

                    <input
                        type="text"
                        name="search"
                        placeholder="Search by name, email, or contact..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                    <button type="submit">Search</button>
                    <button type="button" class="darkmode-toggle" id="darkModeToggle" onclick="toggleDarkMode()">🌙 DARK MODE</button>
                </form>

                <div class="totals-box">
                    <?php if ($viewRole === 'students'): ?>
                        <span>
                            Total Students<?php echo !empty($courseFilter) ? " (" . htmlspecialchars($courseFilter) . ")" : ""; ?>:
                        </span>
                        <div class="count">
                            <?php
                            if (!empty($courseFilter)) {
                                $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role='student' AND course=?");
                                $countStmt->bind_param("s", $courseFilter);
                                $countStmt->execute();
                                $countRes = $countStmt->get_result()->fetch_assoc();
                                echo $countRes['total'];
                            } else {
                                echo $totalStudents;
                            }
                            ?>
                        </div>
                    <?php else: ?>
                        <span>Total Teachers:</span>
                        <div class="count"><?php echo $totalTeachers; ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>LIST OF <?php echo ($viewRole === 'students') ? 'STUDENTS' : 'TEACHERS'; ?></th>
                            <th>EMAIL</th>
                            <th>CONTACT</th>
                            <th>PASSWORD</th>
                            <?php if ($viewRole === 'students'): ?>
                                <th>COURSE</th>
                            <?php endif; ?>
                            <th>ROLE</th>
                            <th>User Management</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php $display_id = 1; ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $display_id; ?></td>
                                    <td><?php echo htmlspecialchars($row['lastname'] . ', ' . $row['firstname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                                    <td><span class="password-mask">********</span></td>

                                    <?php if ($viewRole === 'students'): ?>
                                        <td>
                                            <span class="course-badge">
                                                <?php echo htmlspecialchars($row['course']); ?>
                                            </span>
                                        </td>
                                    <?php endif; ?>

                                    <td>
                                        <span class="role-badge <?php echo ($row['role'] === 'student') ? 'role-student' : 'role-teacher'; ?>">
                                            <?php echo strtoupper(htmlspecialchars($row['role'])); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="action-group">
                                            <?php if ($viewRole === 'students'): ?>
                                                <a href="view_user.php?id=<?php echo $row['id']; ?>" class="action-btn view-btn">VIEW</a>
                                            <?php endif; ?>

                                            <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="action-btn edit-btn">EDIT</a>
                                            <a href="delete_user.php?id=<?php echo $row['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Delete this user?')">DELETE</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php $display_id++; ?>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo ($viewRole === 'students') ? '8' : '7'; ?>" class="empty-row">
                                    No records found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleDropdown() {
        document.getElementById("dropdownContent").classList.toggle("show");
    }

    window.addEventListener("click", function(e) {
        const btn = document.querySelector(".dropdown-btn");
        const menu = document.getElementById("dropdownContent");

        if (btn && menu && !btn.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.remove("show");
        }
    });

    function applyDarkModeState() {
        const isDark = localStorage.getItem('site_darkmode') === 'enabled';
        const btn = document.getElementById('darkModeToggle');

        if (isDark) {
            document.body.classList.add('dark-mode');
            if (btn) {
                btn.innerHTML = '☀️ LIGHT MODE';
            }
        } else {
            document.body.classList.remove('dark-mode');
            if (btn) {
                btn.innerHTML = '🌙 DARK MODE';
            }
        }
    }

    function toggleDarkMode() {
        const isDark = document.body.classList.contains('dark-mode');

        if (isDark) {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('site_darkmode', 'disabled');
        } else {
            document.body.classList.add('dark-mode');
            localStorage.setItem('site_darkmode', 'enabled');
        }

        applyDarkModeState();
    }

    document.addEventListener('DOMContentLoaded', function() {
        applyDarkModeState();
    });
</script>

</body>
</html>
