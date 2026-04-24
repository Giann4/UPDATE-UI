<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
$adminName = isset($_SESSION['name']) && !empty($_SESSION['name']) ? $_SESSION['name'] : 'Administrator';
$roleFilter = isset($_GET['role_filter']) ? trim($_GET['role_filter']) : 'all';

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

/* RESTORE */
if (isset($_GET['restore']) && !empty($_GET['restore'])) {
    $restore_id = intval($_GET['restore']);
    $restore_stmt = $conn->prepare("UPDATE users SET is_deleted = 0, deleted_at = NULL WHERE id = ?");
    $restore_stmt->bind_param("i", $restore_id);
    $restore_stmt->execute();

    header("Location: recently_deleted.php?msg=restored&role_filter=" . urlencode($roleFilter));
    exit;
}

/* PERMANENT DELETE */
if (isset($_GET['permanent_delete']) && !empty($_GET['permanent_delete'])) {
    $delete_id = intval($_GET['permanent_delete']);
    $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    $delete_stmt->execute();

    header("Location: recently_deleted.php?msg=deleted&role_filter=" . urlencode($roleFilter));
    exit;
}

/* FETCH DELETED USERS WITH ROLE FILTER */
$sql = "SELECT * FROM users WHERE is_deleted = 1";
$params = [];
$types = "";

if ($roleFilter === 'teacher') {
    $sql .= " AND role = ?";
    $params[] = 'teacher';
    $types .= "s";
} elseif ($roleFilter === 'student') {
    $sql .= " AND role = ?";
    $params[] = 'student';
    $types .= "s";
}

$sql .= " ORDER BY deleted_at DESC";

if (!empty($params)) {
    $deleted_stmt = $conn->prepare($sql);
    $deleted_stmt->bind_param($types, ...$params);
    $deleted_stmt->execute();
    $deleted_users = $deleted_stmt->get_result();
} else {
    $deleted_users = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recently Deleted</title>
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

        body.dark-mode {
            background: #0f172a;
            color: #e5e7eb;
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

        .side-btn {
            display: flex;
            align-items: center;
            justify-content: center;
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
            position: relative;
            overflow: hidden;
        }

        .side-btn::before {
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

        .side-btn span {
            position: relative;
            z-index: 2;
        }

        .side-btn:hover {
            transform: translateX(6px);
            background: rgba(255,255,255,0.14);
        }

        .side-btn:hover::before {
            width: 4px;
        }

        .side-btn.active {
            background: linear-gradient(135deg, #18c96d, #36df84);
            color: #ffffff;
            border: none;
            box-shadow: 0 8px 18px rgba(16, 201, 107, 0.20);
        }

        .side-btn.active::before {
            width: 5px;
            background: linear-gradient(180deg, #ffffff, #eaf7ff);
        }

        .sidebar-bottom {
            margin-top: 18px;
        }

        .logout-btn:hover {
            background: #d94c4c !important;
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
            transition: background 0.25s ease, color 0.25s ease;
        }

        .sub-header {
            background: #033b46;
            color: #00ff8c;
            text-align: center;
            padding: 14px 20px;
            font-size: 21px;
            font-weight: 900;
            transition: background 0.25s ease, color 0.25s ease;
        }

        body.dark-mode .top-header {
            background: #6f9f4f;
            color: #f8fafc;
        }

        body.dark-mode .sub-header {
            background: #022c3a;
            color: #6fffc0;
        }

        .content-area {
            padding: 28px 24px;
        }

        .top-tools {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .search-bar-wrap {
            flex: 1;
            min-width: 280px;
        }

        .search-bar-wrap input {
            width: 100%;
            max-width: 520px;
            height: 54px;
            border: 2px solid #d6dee2;
            border-radius: 16px;
            padding: 0 18px;
            font-size: 15px;
            outline: none;
            background: #fff;
            color: #1b1b1b;
            transition: 0.25s ease;
            box-shadow: 0 6px 16px rgba(0,0,0,0.04);
        }

        .search-bar-wrap input:focus {
            border-color: #12c96b;
            box-shadow: 0 0 0 4px rgba(18, 201, 107, 0.12);
        }

        body.dark-mode .search-bar-wrap input {
            background: #111827;
            color: #f8fafc;
            border-color: #334155;
            box-shadow: 0 8px 18px rgba(0,0,0,0.18);
        }

        body.dark-mode .search-bar-wrap input::placeholder {
            color: #94a3b8;
        }

        .role-filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .filter-btn {
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 14px;
            background: #e9eff1;
            color: #12353b;
            font-size: 14px;
            font-weight: 800;
            transition: 0.25s ease;
            border: 2px solid transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 95px;
        }

        .filter-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.08);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #18c96d, #36df84);
            color: #fff;
            box-shadow: 0 8px 18px rgba(16, 201, 107, 0.18);
        }

        body.dark-mode .filter-btn {
            background: #1f2937;
            color: #f8fafc;
            border-color: #334155;
        }

        body.dark-mode .filter-btn.active {
            background: linear-gradient(135deg, #18c96d, #36df84);
            color: #fff;
            border-color: transparent;
        }

        .darkmode-toggle {
            height: 48px;
            padding: 0 18px;
            border: none;
            border-radius: 14px;
            background: #1f2937;
            color: #fff;
            font-weight: 800;
            cursor: pointer;
            transition: 0.25s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 150px;
            box-shadow: 0 10px 18px rgba(0, 0, 0, 0.18);
        }

        .darkmode-toggle:hover {
            transform: translateY(-1px);
        }

        body.dark-mode .darkmode-toggle {
            background: #6f9f4f;
            color: #f8fafc;
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
        }

        tbody td {
            padding: 15px 12px;
            text-align: center;
            border-bottom: 1px solid #e8eef0;
            font-size: 15px;
            vertical-align: middle;
            color: #1b1b1b;
            background: #fff;
        }

        tbody tr:hover td {
            background: #f8fcf9;
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
            min-width: 110px;
            text-align: center;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        }

        .restore-btn {
            background: #10b981;
        }

        .permanent-btn {
            background: #ef4444;
        }

        .empty-row {
            text-align: center;
            font-weight: 700;
            color: #6b7479;
            padding: 26px 10px;
        }

        /* DARK MODE - PAGE DARK, TABLE STAYS WHITE */
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

        body.dark-mode tbody td {
            color: #1b1b1b !important;
            border-bottom: 1px solid #e8eef0 !important;
            background: #ffffff !important;
        }

        body.dark-mode tbody tr:hover td {
            background: #f8fcf9 !important;
        }

        body.dark-mode .empty-row {
            color: #6b7479 !important;
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 220px;
            }

            .main-content {
                margin-left: 220px;
            }

            .top-header {
                font-size: 21px;
            }

            .sub-header {
                font-size: 18px;
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
                margin-left: 0;
            }

            .search-bar-wrap input {
                max-width: 100%;
            }

            .top-tools {
                flex-direction: column;
                align-items: stretch;
            }

            .role-filter-group {
                justify-content: flex-start;
            }

            .darkmode-toggle {
                min-width: 100%;
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

            <div class="nav-group">
                <a class="side-btn" href="admin.php?view=teachers"><span>List of Teacher</span></a>
                <a class="side-btn" href="admin.php?view=students"><span>List of Students</span></a>
                <a class="side-btn active" href="recently_deleted.php"><span>Recently Deleted</span></a>
                <a class="side-btn" href="admin_teacher_album.php"><span>Teacher Album</span></a>
                <a class="side-btn" href="admin_change_password.php"><span>Change Password</span></a>
            </div>
        </div>

        <div class="sidebar-bottom">
            <a class="side-btn logout-btn" href="../auth/logout.php"><span>Log Out</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">SOUTHERN PHILIPPINES INSTITUTE OF SCIENCE AND TECHNOLOGY</div>
        <div class="sub-header">RECENTLY DELETED USERS</div>

        <div class="content-area">
            <div class="top-tools">
                <div class="search-bar-wrap">
                    <input 
                        type="text" 
                        id="liveSearch" 
                        placeholder="Search by name, email, contact, role, course, or ID..."
                        autocomplete="off"
                    >
                </div>

                <div class="role-filter-group">
                    <a href="recently_deleted.php?role_filter=all" class="filter-btn <?php echo ($roleFilter === 'all') ? 'active' : ''; ?>">ALL</a>

                    <button type="button" class="darkmode-toggle" id="darkModeToggle" onclick="toggleDarkMode()">🌙 DARK MODE</button>

                    <a href="recently_deleted.php?role_filter=teacher" class="filter-btn <?php echo ($roleFilter === 'teacher') ? 'active' : ''; ?>">TEACHER</a>
                    <a href="recently_deleted.php?role_filter=student" class="filter-btn <?php echo ($roleFilter === 'student') ? 'active' : ''; ?>">STUDENT</a>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>FULL NAME</th>
                            <th>EMAIL</th>
                            <th>CONTACT</th>
                            <th>ROLE</th>
                            <th>COURSE</th>
                            <th>DELETED AT</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody id="deletedUsersTable">
                        <?php if ($deleted_users && $deleted_users->num_rows > 0): ?>
                            <?php while ($row = $deleted_users->fetch_assoc()): ?>
                                <?php
                                    $search_text = strtolower(
                                        $row['id'] . ' ' .
                                        $row['lastname'] . ' ' .
                                        $row['firstname'] . ' ' .
                                        $row['lastname'] . ', ' . $row['firstname'] . ' ' .
                                        $row['email'] . ' ' .
                                        $row['contact_number'] . ' ' .
                                        $row['role'] . ' ' .
                                        ($row['course'] ?? '') . ' ' .
                                        $row['deleted_at']
                                    );
                                ?>
                                <tr class="searchable-row" data-search="<?php echo htmlspecialchars($search_text); ?>">
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['lastname'] . ', ' . $row['firstname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                                    <td><?php echo strtoupper(htmlspecialchars($row['role'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['course'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['deleted_at']); ?></td>
                                    <td>
                                        <div class="action-group">
                                            <a href="recently_deleted.php?restore=<?php echo $row['id']; ?>&role_filter=<?php echo urlencode($roleFilter); ?>" class="action-btn restore-btn" onclick="return confirm('Restore this user?')">RESTORE</a>
                                            <a href="recently_deleted.php?permanent_delete=<?php echo $row['id']; ?>&role_filter=<?php echo urlencode($roleFilter); ?>" class="action-btn permanent-btn" onclick="return confirm('Permanently delete this user?')">DELETE</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr id="noDataRow">
                                <td colspan="8" class="empty-row">No recently deleted users found.</td>
                            </tr>
                        <?php endif; ?>

                        <tr id="noSearchResultRow" style="display: none;">
                            <td colspan="8" class="empty-row">No matching users found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
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

document.addEventListener("DOMContentLoaded", function () {
    applyDarkModeState();

    const searchInput = document.getElementById("liveSearch");
    const rows = document.querySelectorAll(".searchable-row");
    const noSearchResultRow = document.getElementById("noSearchResultRow");

    if (searchInput) {
        searchInput.addEventListener("keyup", function () {
            const value = this.value.toLowerCase().trim();
            let visibleCount = 0;

            rows.forEach(function (row) {
                const searchText = row.getAttribute("data-search");

                if (searchText.includes(value)) {
                    row.style.display = "";
                    visibleCount++;
                } else {
                    row.style.display = "none";
                }
            });

            if (noSearchResultRow) {
                noSearchResultRow.style.display = visibleCount === 0 ? "" : "none";
            }
        });
    }
});
</script>

</body>
</html>