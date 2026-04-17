<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    header("Location: teacher.php");
    exit;
}

$class_id = intval($_GET['class_id']);
$message = "";
$message_type = "";

/* TEACHER INFO */
$user_stmt = $conn->prepare("SELECT firstname, lastname, email, profile_photo FROM users WHERE id = ? AND role = 'teacher'");
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

/* GET CLASS INFO */
$class_stmt = $conn->prepare("SELECT * FROM teacher_classes WHERE id = ? AND teacher_id = ?");
$class_stmt->bind_param("ii", $class_id, $teacher_id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
$class = $class_result->fetch_assoc();

if (!$class) {
    header("Location: teacher.php");
    exit;
}

/* DELETE CLASS */
if (isset($_POST['delete_class'])) {
    $delete_class_id = intval($_POST['delete_class_id']);

    if ($delete_class_id === $class_id) {
        $conn->begin_transaction();

        try {
            /* DELETE RELATED REQUESTS FIRST */
            $delete_requests_stmt = $conn->prepare("DELETE FROM class_requests WHERE class_id = ?");
            $delete_requests_stmt->bind_param("i", $class_id);
            $delete_requests_stmt->execute();

            /* DELETE CLASS */
            $delete_class_stmt = $conn->prepare("DELETE FROM teacher_classes WHERE id = ? AND teacher_id = ?");
            $delete_class_stmt->bind_param("ii", $class_id, $teacher_id);
            $delete_class_stmt->execute();

            $conn->commit();

            header("Location: teacher.php?deleted=1");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Failed to delete class.";
            $message_type = "error";
        }
    } else {
        $message = "Invalid class deletion request.";
        $message_type = "error";
    }
}

/* SAVE REVIEW */
if (isset($_POST['save_review'])) {
    $request_id = intval($_POST['request_id']);
    $result = trim($_POST['result']);
    $comment = trim($_POST['comment']);
    $date_signed = date("Y-m-d");
    $status = "Reviewed";

    $update_stmt = $conn->prepare("UPDATE class_requests SET status = ?, result = ?, comment = ?, date_signed = ? WHERE id = ? AND class_id = ?");
    $update_stmt->bind_param("ssssii", $status, $result, $comment, $date_signed, $request_id, $class_id);

    if ($update_stmt->execute()) {
        $message = "Student request updated successfully.";
        $message_type = "success";
    } else {
        $message = "Failed to update request.";
        $message_type = "error";
    }
}

/* GET REQUESTS */
$request_stmt = $conn->prepare("
    SELECT 
        cr.id,
        cr.subject,
        cr.status,
        cr.result,
        cr.comment,
        cr.date_signed,
        u.firstname,
        u.lastname,
        u.email,
        u.course
    FROM class_requests cr
    LEFT JOIN users u ON cr.student_id = u.id
    WHERE cr.class_id = ?
    ORDER BY cr.id DESC
");
$request_stmt->bind_param("i", $class_id);
$request_stmt->execute();
$requests = $request_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Requests</title>
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, sans-serif;
        }

        :root{
            --bg:#d9d9d9;
            --card:#ffffff;
            --text:#003b49;
            --sub:#555;
            --header:#8fbc67;
            --subheader:#003b49;
            --accent:#003b49;
        }

        .dark-mode:root{
            --bg:linear-gradient(135deg,#032b32,#053842,#032f35);
            --card:rgba(16,70,61,0.35);
            --text:#ffffff;
            --sub:#d6e9e3;
            --header:#8fbc67;
            --subheader:rgba(0,59,73,0.7);
            --accent:rgba(5,76,63,0.8);
        }

        body{
            background:var(--bg);
            transition:.3s;
        }

        .wrapper{
            display:flex;
            min-height:100vh;
        }

        .sidebar{
            width:210px;
            background:#003b49;
            color:white;
            padding:20px 10px;
            text-align:center;
        }

        .profile-img{
            width:90px;
            height:90px;
            border-radius:50%;
            object-fit:cover;
            border:3px solid #fff;
            margin-bottom:12px;
        }

        .sidebar h3{
            font-size:18px;
            margin-bottom:4px;
        }

        .sidebar p{
            font-size:14px;
            margin-bottom:25px;
            word-break:break-word;
        }

        .sidebar a{
            display:block;
            text-decoration:none;
            background:#fff;
            color:#000;
            padding:16px;
            border-radius:30px;
            margin:14px 0;
            font-weight:bold;
            font-size:16px;
            text-align:center;
        }

        .sidebar a.active{
            background:#8fbc67;
        }

        .main-content{
            flex:1;
            background:var(--bg);
        }

        .top-header{
            background:var(--header);
            text-align:center;
            padding:20px 10px;
            font-size:24px;
            font-weight:bold;
            text-transform:uppercase;
        }

        .sub-header{
            background:var(--subheader);
            color:#00ff84;
            text-align:center;
            padding:12px 10px;
            font-size:24px;
            font-weight:bold;
            text-transform:uppercase;
            backdrop-filter:blur(10px);
        }

        .content{
            padding:25px;
        }

        .info-card,
        .table-card{
            background:var(--card);
            border-radius:18px;
            padding:22px;
            box-shadow:0 8px 20px rgba(0,0,0,0.15);
            margin-bottom:20px;
            backdrop-filter:blur(12px);
            border:1px solid rgba(255,255,255,0.15);
        }

        .info-card h2,
        .table-title{
            color:var(--text);
        }

        .info-card p{
            color:var(--sub);
            margin-bottom:6px;
        }

        .message{
            padding:14px 16px;
            border-radius:12px;
            margin-bottom:18px;
            font-weight:bold;
        }

        .message.success{
            background:#d4edda;
            color:#155724;
            border:1px solid #b7dfbe;
        }

        .message.error{
            background:#f8d7da;
            color:#721c24;
            border:1px solid #efb7be;
        }

        .table-title{
            font-size:24px;
            font-weight:bold;
            margin-bottom:16px;
        }

        .table-wrap{
            overflow-x:auto;
        }

        /* WHITE TABLE KAHIT DARK MODE */
        .table-wrap table{
            width:100%;
            border-collapse:collapse;
            background:#ffffff !important;
        }

        .table-wrap th,
        .table-wrap td{
            border:1px solid #d8d8d8 !important;
            padding:12px 10px;
            text-align:center;
            font-size:14px;
            color:#222 !important;
            background:#ffffff !important;
        }

        .table-wrap th{
            background:#8fbc67 !important;
            color:#000 !important;
        }

        .table-wrap tr:nth-child(even) td{
            background:#fafafa !important;
        }

        /* WHITE FORM SA ACTION COLUMN */
        .table-wrap select,
        .table-wrap textarea{
            width:100%;
            border:1px solid #ccc;
            border-radius:8px;
            padding:8px 10px;
            font-size:13px;
            outline:none;
            background:#ffffff !important;
            color:#222 !important;
        }

        .table-wrap textarea{
            resize:vertical;
            min-height:60px;
        }

        .save-btn{
            background:#003b49;
            color:#fff;
            border:none;
            border-radius:10px;
            padding:10px 14px;
            font-size:13px;
            font-weight:bold;
            cursor:pointer;
            margin-top:8px;
        }

        .save-btn:hover{
            opacity:0.9;
        }

        .status-badge{
            display:inline-block;
            padding:6px 12px;
            border-radius:20px;
            font-size:12px;
            font-weight:bold;
        }

        .requesting{
            background:#fff3cd;
            color:#856404 !important;
        }

        .reviewed{
            background:#d4edda;
            color:#155724 !important;
        }

        .action-buttons{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-top:14px;
        }

        .back-btn{
            display:inline-block;
            text-decoration:none;
            background:#003b49;
            color:#fff !important;
            padding:10px 16px;
            border-radius:10px;
            font-weight:bold;
            border:none;
            cursor:pointer;
        }

        .delete-class-btn{
            display:inline-block;
            text-decoration:none;
            background:#c0392b;
            color:#fff;
            padding:10px 16px;
            border-radius:10px;
            font-weight:bold;
            border:none;
            cursor:pointer;
        }

        .delete-class-btn:hover{
            background:#a93226;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="sidebar">
        <img src="<?php echo $photo; ?>" alt="Profile" class="profile-img" onerror="this.src='../assets/southern.png';">
        <h3><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h3>
        <p><?php echo htmlspecialchars($user['email']); ?></p>

        <a href="teacher.php">Dashboard</a>
        <a href="teacher_request.php?class_id=<?php echo $class_id; ?>" class="active">List of Request</a>
        <a href="change_password.php">Change Password</a>
        <a href="../auth/logout.php">Log Out</a>
    </div>

    <div class="main-content">
        <div class="top-header">
            SOUTHERN PHILIPPINES INSTITUTE OF SCIENCE AND TECHNOLOGY
        </div>

        <div class="sub-header">
            CLASS REQUESTS
        </div>

        <div class="content">

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="info-card">
                <h2><?php echo htmlspecialchars($class['subject']); ?></h2>
                <p><strong>Course:</strong> <?php echo htmlspecialchars($class['course']); ?></p>
                <p><strong>Class Code:</strong> <?php echo htmlspecialchars($class['class_code']); ?></p>

                <div class="action-buttons">
                    <a href="teacher.php" class="back-btn">← Back to Dashboard</a>

                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this class? This will also remove all student requests under this class.');" style="display:inline;">
                        <input type="hidden" name="delete_class_id" value="<?php echo $class_id; ?>">
                        <button type="submit" name="delete_class" class="delete-class-btn">Delete Class</button>
                    </form>
                </div>
            </div>

            <div class="table-card">
                <div class="table-title">Student Requests</div>

                <div class="table-wrap">
                    <table>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Course</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Result</th>
                            <th>Comment</th>
                            <th>Date Signed</th>
                            <th>Action</th>
                        </tr>

                        <?php
                        $count = 1;
                        if ($requests->num_rows > 0):
                            while ($row = $requests->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['course']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td>
                                <?php if ($row['status'] === 'Reviewed'): ?>
                                    <span class="status-badge reviewed">Reviewed</span>
                                <?php else: ?>
                                    <span class="status-badge requesting">Requesting</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo !empty($row['result']) ? htmlspecialchars($row['result']) : 'Pending'; ?></td>
                            <td><?php echo !empty($row['comment']) ? htmlspecialchars($row['comment']) : '---'; ?></td>
                            <td><?php echo !empty($row['date_signed']) ? htmlspecialchars($row['date_signed']) : '---'; ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">

                                    <select name="result" required>
                                        <option value="">Select</option>
                                        <option value="Passed" <?php echo ($row['result'] === 'Passed') ? 'selected' : ''; ?>>Passed</option>
                                        <option value="Failed" <?php echo ($row['result'] === 'Failed') ? 'selected' : ''; ?>>Failed</option>
                                        <option value="Incomplete" <?php echo ($row['result'] === 'Incomplete') ? 'selected' : ''; ?>>Incomplete</option>
                                    </select>

                                    <textarea name="comment" placeholder="Comment" required><?php echo htmlspecialchars($row['comment'] ?? ''); ?></textarea>

                                    <button type="submit" name="save_review" class="save-btn">Save</button>
                                </form>
                            </td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="10" style="color:#222;background:#ffffff;">No student requests found.</td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
(function () {
    const savedTheme = localStorage.getItem("site_theme");
    if (savedTheme === "dark") {
        document.documentElement.classList.add("dark-mode");
    }
})();
</script>

</body>
</html>