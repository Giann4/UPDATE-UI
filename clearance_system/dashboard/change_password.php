<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$message = "";
$message_type = "";

$user_stmt = $conn->prepare("SELECT id, firstname, lastname, email, contact_number, profile_photo, password, role FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}

$upload_dir = "../assets/uploads/profile/";
$default_photo = "../assets/southern.png";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

/* UPLOAD PROFILE PHOTO */
if (isset($_POST['upload_photo'])) {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
        $file_name = $_FILES['profile_photo']['name'];
        $file_tmp = $_FILES['profile_photo']['tmp_name'];
        $file_size = $_FILES['profile_photo']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($file_ext, $allowed)) {
            $message = "Only JPG, JPEG, PNG, GIF, and WEBP files are allowed.";
            $message_type = "error";
        } elseif ($file_size > 5 * 1024 * 1024) {
            $message = "File size must not exceed 5MB.";
            $message_type = "error";
        } else {
            $new_file_name = $user_role . "_" . $user_id . "_" . time() . "." . $file_ext;
            $target_file = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                if (!empty($user['profile_photo'])) {
                    $old_file = $upload_dir . $user['profile_photo'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }

                $update_photo = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                $update_photo->bind_param("si", $new_file_name, $user_id);

                if ($update_photo->execute()) {
                    $message = "Profile photo uploaded successfully.";
                    $message_type = "success";
                    $user['profile_photo'] = $new_file_name;
                } else {
                    $message = "Photo uploaded, but database update failed.";
                    $message_type = "error";
                }
            } else {
                $message = "Failed to upload photo.";
                $message_type = "error";
            }
        }
    } else {
        $message = "Please choose an image file first.";
        $message_type = "error";
    }
}

/* CHANGE PASSWORD */
if (isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in all password fields.";
        $message_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "New password and confirm password do not match.";
        $message_type = "error";
    } elseif (strlen($new_password) < 4) {
        $message = "New password must be at least 4 characters.";
        $message_type = "error";
    } else {
        $stored_password = $user['password'];
        $password_matched = false;
        $new_hashed_password = "";

        if ($stored_password === md5($current_password)) {
            $password_matched = true;
            $new_hashed_password = md5($new_password);
        } elseif (password_verify($current_password, $stored_password)) {
            $password_matched = true;
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        } elseif ($stored_password === $current_password) {
            $password_matched = true;
            $new_hashed_password = md5($new_password);
        }

        if (!$password_matched) {
            $message = "Current password is incorrect.";
            $message_type = "error";
        } else {
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_hashed_password, $user_id);

            if ($update_stmt->execute()) {
                $message = "Password changed successfully.";
                $message_type = "success";
                $user['password'] = $new_hashed_password;
            } else {
                $message = "Failed to update password.";
                $message_type = "error";
            }
        }
    }
}

/* PROFILE PHOTO PATH */
if (!empty($user['profile_photo']) && file_exists($upload_dir . $user['profile_photo'])) {
    $photo = $upload_dir . $user['profile_photo'];
} else {
    $photo = $default_photo;
}

/* DYNAMIC LINKS */
$dashboard_link = ($user_role === 'teacher') ? 'teacher.php' : 'student.php';
$page_title = ($user_role === 'teacher') ? 'TEACHER CHANGE PASSWORD' : 'CHANGE PASSWORD';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, sans-serif;
        }

        body{
            background:#d9d9d9;
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

        .profile-card-side{
            position:relative;
            background:rgba(255,255,255,0.08);
            border:1px solid rgba(255,255,255,0.10);
            border-radius:24px;
            padding:20px 14px 18px;
            text-align:center;
            box-shadow:0 12px 24px rgba(0,0,0,0.18);
            overflow:hidden;
        }

        .profile-card-side::before{
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

        .sidebar-name{
            position:relative;
            font-size:26px;
            font-weight:800;
            margin-bottom:6px;
            line-height:1.1;
            z-index:2;
        }

        .sidebar-email{
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
        }

        .logout-btn{
            margin-top:18px;
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
            padding:20px 10px;
            font-size:24px;
            font-weight:bold;
            text-transform:uppercase;
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

        .message{
            padding:14px 16px;
            border-radius:12px;
            margin-bottom:18px;
            font-weight:bold;
            font-size:14px;
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

        .page-grid{
            display:grid;
            grid-template-columns:2fr 1fr;
            gap:20px;
            align-items:start;
        }

        .card{
            background:#fff;
            border-radius:18px;
            padding:24px;
            box-shadow:0 4px 12px rgba(0,0,0,0.08);
        }

        .card-title{
            color:#003b49;
            font-size:28px;
            font-weight:bold;
            margin-bottom:8px;
        }

        .card-subtitle{
            color:#666;
            font-size:14px;
            margin-bottom:20px;
        }

        .form-group{
            margin-bottom:18px;
        }

        .form-group label{
            display:block;
            margin-bottom:8px;
            color:#003b49;
            font-weight:bold;
            font-size:15px;
        }

        .required{
            color:red;
        }

        .input-wrap{
            position:relative;
        }

        .form-group input{
            width:100%;
            height:52px;
            border:1px solid #cfcfcf;
            border-radius:12px;
            padding:0 70px 0 16px;
            font-size:15px;
            outline:none;
            transition:0.2s ease;
            background:#fafafa;
        }

        .form-group input:focus{
            border-color:#8fbc67;
            background:#fff;
            box-shadow:0 0 0 3px rgba(143,188,103,0.18);
        }

        .toggle-password{
            position:absolute;
            right:10px;
            top:50%;
            transform:translateY(-50%);
            background:#f1f1f1;
            border:1px solid #ccc;
            border-radius:8px;
            padding:6px 10px;
            font-size:12px;
            font-weight:bold;
            color:#333;
            cursor:pointer;
            min-width:55px;
        }

        .toggle-password:hover{
            background:#e7e7e7;
        }

        .submit-btn{
            margin-top:8px;
            background:#003b49;
            color:#fff;
            border:none;
            border-radius:14px;
            padding:14px 28px;
            font-size:16px;
            font-weight:bold;
            cursor:pointer;
            transition:0.2s ease;
        }

        .submit-btn:hover{
            background:#002d38;
            transform:translateY(-1px);
        }

        .profile-card{
            text-align:center;
        }

        .big-photo{
            width:150px;
            height:150px;
            border-radius:50%;
            object-fit:cover;
            border:5px solid #003b49;
            margin:0 auto 18px;
            display:block;
        }

        .upload-row{
            display:flex;
            justify-content:flex-end;
            margin-bottom:12px;
        }

        .upload-btn{
            display:inline-block;
            background:#f7f7f7;
            border:1px solid #ddd;
            color:#333;
            padding:8px 12px;
            border-radius:10px;
            font-size:13px;
            font-weight:bold;
            cursor:pointer;
        }

        .upload-btn:hover{
            background:#efefef;
        }

        .hidden-file{
            display:none;
        }

        .upload-submit{
            margin-top:10px;
            background:#8fbc67;
            color:#000;
            border:none;
            border-radius:12px;
            padding:10px 16px;
            font-size:14px;
            font-weight:bold;
            cursor:pointer;
        }

        .upload-submit:hover{
            opacity:0.92;
        }

        .selected-file{
            margin-top:8px;
            font-size:13px;
            color:#555;
            word-break:break-word;
        }

        .profile-info{
            margin-top:10px;
        }

        .info-block{
            margin-bottom:20px;
        }

        .info-label{
            color:#003b49;
            font-size:13px;
            font-weight:bold;
            margin-bottom:6px;
            text-transform:uppercase;
            letter-spacing:0.5px;
        }

        .info-value{
            font-size:16px;
            font-weight:bold;
            color:#111;
            word-break:break-word;
        }

        .helper-box{
            margin-top:18px;
            background:#f8f8f8;
            border:1px solid #e4e4e4;
            border-radius:12px;
            padding:14px;
            text-align:left;
        }

        .helper-box h4{
            color:#003b49;
            font-size:15px;
            margin-bottom:8px;
        }

        .helper-box p{
            color:#555;
            font-size:13px;
            line-height:1.5;
        }

        @media (max-width: 950px){
            .page-grid{
                grid-template-columns:1fr;
            }
        }

        @media (max-width: 700px){
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

            .content{
                padding:15px;
            }

            .welcome-box h2,
            .card-title{
                font-size:24px;
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
                <span class="brand-text"><?php echo $user_role === 'teacher' ? 'Teacher Panel' : 'Student Panel'; ?></span>
            </div>

            <div class="profile-card-side">
                <div class="profile-ring">
                    <img src="<?php echo $photo; ?>" alt="Profile" class="profile-img" onerror="this.src='../assets/southern.png';">
                </div>

                <div class="sidebar-name">
                    <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                </div>

                <div class="sidebar-email">
                    <?php echo htmlspecialchars($user['email']); ?>
                </div>

                <div class="role-badge">
                    <?php echo strtoupper(htmlspecialchars($user_role)); ?>
                </div>
            </div>

            <div class="menu-label">Navigation</div>

            <div class="nav-group">
                <a href="<?php echo $dashboard_link; ?>" class="<?php echo ($current_page == basename($dashboard_link)) ? 'active' : ''; ?>">
                    <span class="nav-icon">🏠</span>
                    <span>Dashboard</span>
                </a>

                <?php if ($user_role === 'student'): ?>
                    <a href="student_result.php" class="<?php echo ($current_page == 'student_result.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">📄</span>
                        <span>Result</span>
                    </a>
                <?php endif; ?>

                <a href="change_password.php" class="active">
                    <span class="nav-icon">🔒</span>
                    <span>Change Password</span>
                </a>
            </div>
        </div>

        <a href="../auth/logout.php" class="logout-btn">
            <span class="nav-icon">↩</span>
            <span>Log Out</span>
        </a>
    </div>

    <div class="main-content">
        <div class="top-header">
            SOUTHERN PHILIPPINES INSTITUTE OF SCIENCE AND TECHNOLOGY
        </div>

        <div class="sub-header">
            <?php echo $page_title; ?>
        </div>

        <div class="content">

            <div class="welcome-box">
                <h2>Hi, <?php echo htmlspecialchars($user['firstname']); ?> 👋</h2>
                <p>
                    Keep your account secure by updating your password regularly and uploading a profile photo if needed.
                </p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="page-grid">

                <div class="card">
                    <div class="card-title">Update Your Password</div>
                    <div class="card-subtitle">Enter your current password and choose a new one.</div>

                    <form method="POST">
                        <div class="form-group">
                            <label>Current Password <span class="required">*</span></label>
                            <div class="input-wrap">
                                <input type="password" name="current_password" id="current_password" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('current_password', this)">Show</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>New Password <span class="required">*</span></label>
                            <div class="input-wrap">
                                <input type="password" name="new_password" id="new_password" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('new_password', this)">Show</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Confirm Password <span class="required">*</span></label>
                            <div class="input-wrap">
                                <input type="password" name="confirm_password" id="confirm_password" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">Show</button>
                            </div>
                        </div>

                        <button type="submit" name="change_password" class="submit-btn">Save New Password</button>
                    </form>
                </div>

                <div class="card profile-card">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="upload-row">
                            <label for="fileInput" class="upload-btn">UPLOAD</label>
                        </div>

                        <input type="file" id="fileInput" name="profile_photo" class="hidden-file" accept=".jpg,.jpeg,.png,.gif,.webp" onchange="showFileName(this)">

                        <img src="<?php echo $photo; ?>" alt="Profile" class="big-photo" onerror="this.src='../assets/southern.png';">

                        <div id="selectedFile" class="selected-file">No file selected</div>

                        <button type="submit" name="upload_photo" class="upload-submit">Save Photo</button>
                    </form>

                    <div class="profile-info">
                        <div class="info-block">
                            <div class="info-label">Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></div>
                        </div>

                        <div class="info-block">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>

                        <div class="info-block">
                            <div class="info-label">Contact</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['contact_number']); ?></div>
                        </div>
                    </div>

                    <div class="helper-box">
                        <h4>Password Tips</h4>
                        <p>
                            Use a strong password with a mix of letters, numbers, and symbols. Avoid easy-to-guess passwords.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);

    if (input.type === "password") {
        input.type = "text";
        btn.textContent = "Hide";
    } else {
        input.type = "password";
        btn.textContent = "Show";
    }
}

function showFileName(input) {
    const selectedFile = document.getElementById("selectedFile");

    if (input.files.length > 0) {
        selectedFile.textContent = input.files[0].name;
    } else {
        selectedFile.textContent = "No file selected";
    }
}
</script>

</body>
</html>
