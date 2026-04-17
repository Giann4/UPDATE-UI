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

/* UPLOAD / SAVE CROPPED PROFILE PHOTO */
if (isset($_POST['upload_photo'])) {

    $new_file_name = "";
    $target_file = "";

    if (isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])) {

        $cropped_image = trim($_POST['cropped_image']);

        if (preg_match('/^data:image\/([a-zA-Z0-9]+);base64,/', $cropped_image, $matches)) {
            $file_ext = strtolower($matches[1]);

            if ($file_ext === 'jpeg') {
                $file_ext = 'jpg';
            }

            $allowed = ['jpg', 'png', 'gif', 'webp'];

            if (!in_array($file_ext, $allowed)) {
                $message = "Invalid cropped image format.";
                $message_type = "error";
            } else {
                $image_data = substr($cropped_image, strpos($cropped_image, ',') + 1);
                $image_data = str_replace(' ', '+', $image_data);
                $decoded_image = base64_decode($image_data);

                if ($decoded_image === false) {
                    $message = "Invalid cropped image data.";
                    $message_type = "error";
                } elseif (strlen($decoded_image) > 8 * 1024 * 1024) {
                    $message = "Cropped image is too large. Max 8MB only.";
                    $message_type = "error";
                } else {
                    $new_file_name = $user_role . "_" . $user_id . "_" . time() . "." . $file_ext;
                    $target_file = $upload_dir . $new_file_name;

                    if (file_put_contents($target_file, $decoded_image) !== false) {

                        if (!empty($user['profile_photo'])) {
                            $old_file = $upload_dir . $user['profile_photo'];
                            if (file_exists($old_file)) {
                                @unlink($old_file);
                            }
                        }

                        $update_photo = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                        $update_photo->bind_param("si", $new_file_name, $user_id);

                        if ($update_photo->execute()) {
                            $message = "Profile photo updated successfully.";
                            $message_type = "success";
                            $user['profile_photo'] = $new_file_name;
                        } else {
                            if (file_exists($target_file)) {
                                @unlink($target_file);
                            }
                            $message = "Photo saved, but database update failed.";
                            $message_type = "error";
                        }
                    } else {
                        $message = "Failed to save cropped photo.";
                        $message_type = "error";
                    }
                }
            }
        } else {
            $message = "Invalid cropped image format.";
            $message_type = "error";
        }
    }

    elseif (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
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
            $mime = mime_content_type($file_tmp);
            $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if (!in_array($mime, $allowed_mime)) {
                $message = "Invalid image file.";
                $message_type = "error";
            } else {
                if ($file_ext === 'jpeg') {
                    $file_ext = 'jpg';
                }

                $new_file_name = $user_role . "_" . $user_id . "_" . time() . "." . $file_ext;
                $target_file = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $target_file)) {

                    if (!empty($user['profile_photo'])) {
                        $old_file = $upload_dir . $user['profile_photo'];
                        if (file_exists($old_file)) {
                            @unlink($old_file);
                        }
                    }

                    $update_photo = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                    $update_photo->bind_param("si", $new_file_name, $user_id);

                    if ($update_photo->execute()) {
                        $message = "Profile photo uploaded successfully.";
                        $message_type = "success";
                        $user['profile_photo'] = $new_file_name;
                    } else {
                        if (file_exists($target_file)) {
                            @unlink($target_file);
                        }
                        $message = "Photo uploaded, but database update failed.";
                        $message_type = "error";
                    }
                } else {
                    $message = "Failed to upload photo.";
                    $message_type = "error";
                }
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

    <script>
    (function () {
        const savedTheme = localStorage.getItem("site_theme");
        if (savedTheme === "dark") {
            document.documentElement.classList.add("dark-mode");
        }
    })();
    </script>

    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">

    <style>
        .page-grid{
            display:grid;
            grid-template-columns:2fr 1fr;
            gap:20px;
            align-items:start;
        }

        .page-actions{
            display:flex;
            justify-content:flex-end;
            gap:10px;
            margin-bottom:18px;
            flex-wrap:wrap;
        }

        .card-title{
            font-size:28px;
            font-weight:bold;
            margin-bottom:8px;
            color:var(--title-text);
        }

        .card-subtitle{
            color:var(--muted-text);
            font-size:14px;
            margin-bottom:20px;
        }

        .required{
            color:#ff4d4f;
        }

        .input-wrap{
            position:relative;
        }

        .form-group input{
            width:100%;
            height:52px;
            padding:0 74px 0 16px;
        }

        .toggle-password{
            position:absolute;
            right:10px;
            top:50%;
            transform:translateY(-50%);
            background:var(--theme-btn-bg);
            color:var(--theme-btn-text);
            border:var(--theme-btn-border);
            border-radius:8px;
            padding:6px 10px;
            font-size:12px;
            font-weight:bold;
            cursor:pointer;
            min-width:55px;
            backdrop-filter:blur(10px);
            -webkit-backdrop-filter:blur(10px);
        }

        .toggle-password:hover{
            opacity:0.95;
        }

        .submit-btn{
            margin-top:8px;
        }

        .page-grid > .card{
            background:var(--card-bg);
            border:1px solid rgba(255,255,255,0.14);
            border-radius:24px;
            padding:22px;
            box-shadow:0 10px 26px rgba(0,0,0,0.14);
            backdrop-filter:blur(14px);
            -webkit-backdrop-filter:blur(14px);
            overflow:hidden;
        }

        .dark-mode .page-grid > .card{
            border:1px solid rgba(255,255,255,0.16);
            box-shadow:0 12px 28px rgba(0,0,0,0.18);
        }

        .profile-panel-card{
            text-align:center;
            background:var(--card-bg);
            border:1px solid rgba(255,255,255,0.14);
            border-radius:24px;
            padding:22px;
            box-shadow:0 10px 26px rgba(0,0,0,0.14);
            backdrop-filter:blur(14px);
            -webkit-backdrop-filter:blur(14px);
        }

        .big-photo{
            width:150px;
            height:150px;
            border-radius:50%;
            object-fit:cover;
            border:5px solid #003b49;
            margin:0 auto 18px;
            display:block;
            background:#0f3c43;
        }

        .dark-mode .big-photo{
            border-color:#8fbc67;
        }

        .upload-row{
            display:flex;
            justify-content:flex-end;
            margin-bottom:12px;
            gap:8px;
            flex-wrap:wrap;
        }

        .upload-btn,
        .crop-action-btn,
        .upload-submit{
            display:inline-block;
            background:var(--theme-btn-bg);
            color:var(--theme-btn-text);
            border:var(--theme-btn-border);
            padding:8px 12px;
            border-radius:10px;
            font-size:13px;
            font-weight:bold;
            cursor:pointer;
            backdrop-filter:blur(10px);
            -webkit-backdrop-filter:blur(10px);
        }

        .upload-submit{
            margin-top:10px;
            background:var(--secondary-btn-bg);
            color:var(--secondary-btn-text);
            border:none;
            padding:10px 16px;
            font-size:14px;
        }

        .upload-btn:hover,
        .crop-action-btn:hover,
        .upload-submit:hover{
            opacity:0.95;
        }

        .hidden-file{
            display:none;
        }

        .selected-file{
            margin-top:8px;
            font-size:13px;
            color:var(--muted-text);
            word-break:break-word;
            min-height:20px;
        }

        .profile-info{
            margin-top:14px;
        }

        .profile-panel-card .profile-info,
        .profile-panel-card .profile-info *{
            box-shadow:none !important;
            outline:none !important;
        }

        .profile-panel-card .profile-info .info-block{
            margin-bottom:20px;
            padding:0 !important;
            border:none !important;
            border-bottom:none !important;
            border-top:none !important;
            background:transparent !important;
            box-shadow:none !important;
            outline:none !important;
        }

        .profile-panel-card .profile-info .info-block::before,
        .profile-panel-card .profile-info .info-block::after{
            content:none !important;
            display:none !important;
            border:none !important;
            box-shadow:none !important;
        }

        .profile-panel-card .profile-info hr{
            display:none !important;
            border:none !important;
            height:0 !important;
            margin:0 !important;
            padding:0 !important;
        }

        .info-label{
            color:var(--title-text);
            font-size:13px;
            font-weight:bold;
            margin-bottom:6px;
            text-transform:uppercase;
            letter-spacing:0.5px;
        }

        .info-value{
            font-size:16px;
            font-weight:bold;
            color:var(--body-text);
            word-break:break-word;
            border:none !important;
            box-shadow:none !important;
            outline:none !important;
        }

        .helper-box{
            margin-top:18px;
            background:rgba(255,255,255,0.04);
            border:1px solid rgba(255,255,255,0.10);
            border-radius:12px;
            padding:14px;
            text-align:left;
            box-shadow:none;
            backdrop-filter:var(--glass-blur);
            -webkit-backdrop-filter:var(--glass-blur);
        }

        .helper-box h4{
            color:var(--title-text);
            font-size:15px;
            margin-bottom:8px;
        }

        .helper-box p{
            color:var(--body-text);
            font-size:13px;
            line-height:1.5;
        }

        .preview-note{
            margin-top:8px;
            font-size:12px;
            color:var(--muted-text);
        }

        .crop-modal{
            position:fixed;
            inset:0;
            background:rgba(0,0,0,0.75);
            display:none;
            align-items:center;
            justify-content:center;
            z-index:9999;
            padding:20px;
        }

        .crop-modal.show{
            display:flex;
        }

        .crop-modal-box{
            width:100%;
            max-width:820px;
            background:#0c3f45;
            border:1px solid rgba(255,255,255,0.12);
            border-radius:24px;
            padding:20px;
            box-shadow:0 20px 60px rgba(0,0,0,0.30);
        }

        .dark-mode .crop-modal-box{
            background:#0f2f33;
        }

        .crop-modal-header{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            margin-bottom:14px;
        }

        .crop-modal-title{
            color:#fff;
            font-size:22px;
            font-weight:bold;
        }

        .crop-close{
            background:transparent;
            border:none;
            color:#fff;
            font-size:32px;
            line-height:1;
            cursor:pointer;
        }

        .crop-container{
            width:100%;
            max-height:500px;
            overflow:hidden;
            border-radius:18px;
            background:#102f34;
        }

        .crop-container img{
            display:block;
            max-width:100%;
        }

        .crop-modal-actions{
            display:flex;
            justify-content:flex-end;
            gap:10px;
            margin-top:16px;
            flex-wrap:wrap;
        }

        .crop-cancel-btn,
        .crop-apply-btn{
            border:none;
            border-radius:12px;
            padding:11px 16px;
            font-size:14px;
            font-weight:bold;
            cursor:pointer;
        }

        .crop-cancel-btn{
            background:rgba(255,255,255,0.10);
            color:#fff;
        }

        .crop-apply-btn{
            background:#a9d466;
            color:#163328;
        }

        @media (max-width: 950px){
            .page-grid{
                grid-template-columns:1fr;
            }
        }

        @media (max-width: 700px){
            .page-actions{
                justify-content:stretch;
            }

            .theme-toggle-btn{
                width:100%;
            }

            .welcome-box h2,
            .card-title{
                font-size:24px;
            }

            .crop-modal-box{
                padding:14px;
            }

            .crop-container{
                max-height:380px;
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
                    <span class="nav-text">Dashboard</span>
                </a>

                <?php if ($user_role === 'student'): ?>
                    <a href="student_result.php" class="<?php echo ($current_page == 'student_result.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">📄</span>
                        <span class="nav-text">Result</span>
                    </a>
                <?php endif; ?>

                <a href="change_password.php" class="<?php echo ($current_page == 'change_password.php') ? 'active' : ''; ?>">
                    <span class="nav-icon">🔒</span>
                    <span class="nav-text">Change Password</span>
                </a>

                <?php if ($user_role === 'student'): ?>
                    <a href="all_teachers.php" class="<?php echo ($current_page == 'all_teachers.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">👨‍🏫</span>
                        <span class="nav-text">List of All Teacher's in Southern</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <a href="../auth/logout.php" class="logout-btn">
            <span class="nav-icon">↩</span>
            <span class="nav-text">Log Out</span>
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

            <div class="page-actions">
                <button type="button" class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleTheme()">🌙 Dark Mode: Off</button>
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

                <div class="profile-panel-card">
                    <form method="POST" enctype="multipart/form-data" id="photoForm">
                        <div class="upload-row">
                            <label for="fileInput" class="upload-btn">UPLOAD</label>
                        </div>

                        <input type="file" id="fileInput" name="profile_photo" class="hidden-file" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
                        <input type="hidden" name="cropped_image" id="croppedImageInput">

                        <img src="<?php echo $photo; ?>" alt="Profile" id="mainPreviewPhoto" class="big-photo" onerror="this.src='../assets/southern.png';">

                        <div id="selectedFile" class="selected-file">No file selected</div>
                        <div class="preview-note">After choosing a photo, crop it first before saving.</div>

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

<div class="crop-modal" id="cropModal">
    <div class="crop-modal-box">
        <div class="crop-modal-header">
            <div class="crop-modal-title">Crop Profile Photo</div>
            <button type="button" class="crop-close" id="closeCropModal">&times;</button>
        </div>

        <div class="crop-container">
            <img id="cropImage" src="" alt="Crop Preview">
        </div>

        <div class="crop-modal-actions">
            <button type="button" class="crop-cancel-btn" id="cancelCropBtn">Cancel</button>
            <button type="button" class="crop-apply-btn" id="applyCropBtn">Crop & Preview</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>

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

    const fileInput = document.getElementById("fileInput");
    const selectedFile = document.getElementById("selectedFile");
    const cropModal = document.getElementById("cropModal");
    const cropImage = document.getElementById("cropImage");
    const closeCropModal = document.getElementById("closeCropModal");
    const cancelCropBtn = document.getElementById("cancelCropBtn");
    const applyCropBtn = document.getElementById("applyCropBtn");
    const mainPreviewPhoto = document.getElementById("mainPreviewPhoto");
    const croppedImageInput = document.getElementById("croppedImageInput");
    const sidebarPhoto = document.querySelector(".profile-img");

    let cropper = null;
    let originalPreview = mainPreviewPhoto.getAttribute("src");

    fileInput.addEventListener("change", function () {
        const file = this.files[0];

        if (!file) {
            selectedFile.textContent = "No file selected";
            return;
        }

        selectedFile.textContent = file.name;

        const allowedTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
        if (!allowedTypes.includes(file.type)) {
            alert("Only JPG, JPEG, PNG, GIF, and WEBP files are allowed.");
            this.value = "";
            croppedImageInput.value = "";
            selectedFile.textContent = "No file selected";
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            cropImage.src = e.target.result;
            cropModal.classList.add("show");

            if (cropper) {
                cropper.destroy();
            }

            cropper = new Cropper(cropImage, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: "move",
                autoCropArea: 1,
                responsive: true,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false
            });
        };
        reader.readAsDataURL(file);
    });

    function closeCropModalFunc(resetFile = false) {
        cropModal.classList.remove("show");

        if (cropper) {
            cropper.destroy();
            cropper = null;
        }

        if (resetFile) {
            fileInput.value = "";
            croppedImageInput.value = "";
            selectedFile.textContent = "No file selected";
            mainPreviewPhoto.src = originalPreview;
            if (sidebarPhoto) {
                sidebarPhoto.src = originalPreview;
            }
        }
    }

    closeCropModal.addEventListener("click", function () {
        closeCropModalFunc(true);
    });

    cancelCropBtn.addEventListener("click", function () {
        closeCropModalFunc(true);
    });

    cropModal.addEventListener("click", function (e) {
        if (e.target === cropModal) {
            closeCropModalFunc(true);
        }
    });

    applyCropBtn.addEventListener("click", function () {
        if (!cropper) return;

        const canvas = cropper.getCroppedCanvas({
            width: 500,
            height: 500,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: "high"
        });

        if (!canvas) {
            alert("Unable to crop image.");
            return;
        }

        const croppedData = canvas.toDataURL("image/png");
        croppedImageInput.value = croppedData;

        mainPreviewPhoto.src = croppedData;
        if (sidebarPhoto) {
            sidebarPhoto.src = croppedData;
        }

        closeCropModalFunc(false);
    });

    document.getElementById("photoForm").addEventListener("submit", function (e) {
        if (fileInput.files.length > 0 && croppedImageInput.value === "") {
            e.preventDefault();
            alert("Please crop the selected image first before saving.");
        }
    });
});
</script>

</body>
</html>