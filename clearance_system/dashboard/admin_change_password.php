<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$message_type = "";
$current_page = basename($_SERVER['PHP_SELF']);

$admin_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$admin_name = isset($_SESSION['name']) && !empty($_SESSION['name']) ? $_SESSION['name'] : 'Administrator';
$admin_email = "admin@gmail.com";
$default_photo = "../assets/southern.png";
$upload_dir = "../assets/uploads/admin/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

/* GET ADMIN INFO */
if ($admin_id > 0) {
    $admin_stmt = $conn->prepare("SELECT id, name, email, password, profile_photo FROM admin WHERE id = ?");
    $admin_stmt->bind_param("i", $admin_id);
    $admin_stmt->execute();
    $admin = $admin_stmt->get_result()->fetch_assoc();

    if ($admin) {
        $admin_name = $admin['name'];
        $admin_email = $admin['email'];
    } else {
        die("Admin not found.");
    }
} else {
    die("Admin session not found.");
}

/* UPLOAD / SAVE CROPPED ADMIN PHOTO */
if (isset($_POST['upload_photo'])) {

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
                    $new_file_name = "admin_" . $admin_id . "_" . time() . "." . $file_ext;
                    $target_file = $upload_dir . $new_file_name;

                    if (file_put_contents($target_file, $decoded_image) !== false) {

                        if (!empty($admin['profile_photo'])) {
                            $old_file = $upload_dir . $admin['profile_photo'];
                            if (file_exists($old_file)) {
                                @unlink($old_file);
                            }
                        }

                        $update_photo = $conn->prepare("UPDATE admin SET profile_photo = ? WHERE id = ?");
                        $update_photo->bind_param("si", $new_file_name, $admin_id);

                        if ($update_photo->execute()) {
                            $message = "Admin profile photo uploaded successfully.";
                            $message_type = "success";
                            $admin['profile_photo'] = $new_file_name;
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
    } else {
        $message = "Please choose and crop an image first.";
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
        $stored_password = $admin['password'];
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
            $update_stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_hashed_password, $admin_id);

            if ($update_stmt->execute()) {
                $message = "Admin password changed successfully.";
                $message_type = "success";
                $admin['password'] = $new_hashed_password;
            } else {
                $message = "Failed to update admin password.";
                $message_type = "error";
            }
        }
    }
}

/* ADMIN PHOTO PATH */
if (!empty($admin['profile_photo']) && file_exists($upload_dir . $admin['profile_photo'])) {
    $admin_photo = $upload_dir . $admin['profile_photo'];
} else {
    $admin_photo = $default_photo;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Change Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, Helvetica, sans-serif;
        }

        body{
            background:
                radial-gradient(circle at top left, rgba(18, 201, 107, 0.08), transparent 28%),
                radial-gradient(circle at bottom right, rgba(3, 59, 70, 0.10), transparent 30%),
                #edf2f4;
            color:#1b1b1b;
            transition: background 0.25s ease, color 0.25s ease;
        }

        body.dark-mode{
            background:#0f172a;
            color:#e5e7eb;
        }

        .admin-wrapper{
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

        .profile-box{
            position:relative;
            background:rgba(255,255,255,0.08);
            border:1px solid rgba(255,255,255,0.10);
            border-radius:24px;
            padding:20px 14px 18px;
            text-align:center;
            box-shadow:0 12px 24px rgba(0,0,0,0.18);
            overflow:hidden;
        }

        .profile-box::before{
            content:"";
            position:absolute;
            top:0;
            left:0;
            right:0;
            height:72px;
            background:linear-gradient(135deg, rgba(143,188,103,0.35), rgba(118,179,222,0.22));
        }

        .profile-icon-wrap{
            position:relative;
            width:98px;
            height:98px;
            margin:8px auto 12px;
            padding:4px;
            border-radius:50%;
            background:linear-gradient(135deg, #d0f0a9, #8fbc67);
            box-shadow:0 10px 18px rgba(0,0,0,0.18);
            z-index:2;
            overflow:hidden;
        }

        .profile-icon{
            width:100%;
            height:100%;
            border-radius:50%;
            object-fit:cover;
            border:3px solid #fff;
            background:#fff;
            display:block;
        }

        .profile-box h3{
            position:relative;
            font-size:26px;
            margin-bottom:6px;
            font-weight:800;
            line-height:1.1;
            z-index:2;
            letter-spacing:0.5px;
        }

        .profile-box p{
            position:relative;
            font-size:13px;
            color:#d9eef2;
            margin-bottom:10px;
            word-break:break-word;
            line-height:1.45;
            z-index:2;
        }

        .admin-badge{
            display:inline-block;
            padding:9px 15px;
            border-radius:999px;
            background:linear-gradient(135deg, #10c96b, #2de07f);
            color:#fff;
            font-size:12px;
            font-weight:800;
            letter-spacing:.5px;
            position:relative;
            z-index:2;
            box-shadow:0 8px 18px rgba(16, 201, 107, 0.25);
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

        .side-btn{
            display:flex;
            align-items:center;
            justify-content:center;
            width:100%;
            text-align:center;
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
            margin-bottom:0;
        }

        .side-btn::before{
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
            background:linear-gradient(135deg, #18c96d, #36df84);
            color:#ffffff;
            border:none;
            box-shadow:0 8px 18px rgba(16, 201, 107, 0.20);
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
            transform:translateX(6px);
        }

        .logout-btn:hover::before{
            width:4px;
            background:#fff;
        }

        .main-content{
            flex:1;
            min-width:0;
            margin-left:235px;
        }

        .top-header{
            background:linear-gradient(135deg, #98c76b, #85b95d);
            color:#111;
            text-align:center;
            padding:24px 20px;
            font-size:25px;
            font-weight:900;
            letter-spacing:0.5px;
            transition: background 0.25s ease, color 0.25s ease;
        }

        .sub-header{
            background:#033b46;
            color:#00ff8c;
            text-align:center;
            padding:14px 20px;
            font-size:21px;
            font-weight:900;
            letter-spacing:0.4px;
            transition: background 0.25s ease, color 0.25s ease;
        }

        body.dark-mode .top-header{
            background:#6f9f4f;
            color:#f8fafc;
        }

        body.dark-mode .sub-header{
            background:#022c3a;
            color:#6fffc0;
        }

        .content-area{
            padding:28px 24px;
        }

        .top-actions{
            display:flex;
            justify-content:flex-end;
            margin-bottom:18px;
        }

        .darkmode-toggle{
            height:52px;
            padding:0 24px;
            border:none;
            border-radius:16px;
            background:#1f2937;
            color:#fff;
            font-weight:800;
            cursor:pointer;
            transition:0.25s ease;
            box-shadow:0 10px 18px rgba(0, 0, 0, 0.18);
        }

        .darkmode-toggle:hover{
            transform:translateY(-1px);
        }

        body.dark-mode .darkmode-toggle{
            background:#6f9f4f;
            color:#f8fafc;
        }

        .page-grid{
            display:grid;
            grid-template-columns:2fr 1fr;
            gap:22px;
            align-items:start;
        }

        .card,
        .profile-panel-card{
            background:#fff;
            border-radius:24px;
            box-shadow:0 12px 30px rgba(0,0,0,0.07);
            padding:26px;
            transition: background 0.25s ease, color 0.25s ease, box-shadow 0.25s ease;
        }

        body.dark-mode .card,
        body.dark-mode .profile-panel-card{
            background:#111827;
            box-shadow:0 12px 30px rgba(0,0,0,0.28);
        }

        .card h2{
            font-size:34px;
            color:#033b46;
            margin-bottom:8px;
        }

        body.dark-mode .card h2{
            color:#e5f3ff;
        }

        .card-sub{
            color:#56666c;
            margin-bottom:22px;
            font-size:15px;
        }

        body.dark-mode .card-sub{
            color:#cbd5e1;
        }

        .message{
            margin-bottom:18px;
            padding:14px 16px;
            border-radius:14px;
            font-weight:800;
            font-size:14px;
        }

        .message.success{
            background:#e9fff1;
            color:#0d7f40;
            border:1px solid #a9e1bf;
        }

        .message.error{
            background:#ffeaea;
            color:#b10000;
            border:1px solid #ffb8b8;
        }

        body.dark-mode .message.success{
            background:#1f2937;
            color:#d1fae5;
            border-color:#35546b;
        }

        body.dark-mode .message.error{
            background:#3a1f1f;
            color:#ffd5d5;
            border-color:#7a3d3d;
        }

        .form-group{
            margin-bottom:18px;
        }

        .form-group label{
            display:block;
            font-size:14px;
            font-weight:800;
            color:#1d3c43;
            margin-bottom:8px;
        }

        body.dark-mode .form-group label{
            color:#cbd5e1;
        }

        .required{
            color:#ff4d4f;
        }

        .input-wrap{
            position:relative;
        }

        .form-group input{
            width:100%;
            height:56px;
            padding:0 80px 0 16px;
            border:1.5px solid #d6dee2;
            border-radius:14px;
            font-size:15px;
            outline:none;
            transition:0.25s ease;
            background:#fff;
            color:#1b1b1b;
        }

        .form-group input:focus{
            border-color:#12c96b;
            box-shadow:0 0 0 4px rgba(18, 201, 107, 0.12);
        }

        body.dark-mode .form-group input{
            background:#111827;
            color:#f8fafc;
            border-color:#334155;
        }

        body.dark-mode .form-group input::placeholder{
            color:#94a3b8;
        }

        .toggle-password{
            position:absolute;
            right:10px;
            top:50%;
            transform:translateY(-50%);
            border:none;
            border-radius:10px;
            background:#edf3f5;
            color:#26464d;
            padding:8px 12px;
            font-size:12px;
            font-weight:800;
            cursor:pointer;
        }

        body.dark-mode .toggle-password{
            background:#334155;
            color:#f8fafc;
        }

        .save-btn{
            border:none;
            border-radius:14px;
            background:linear-gradient(135deg, #0fb761, #0a944d);
            color:#fff;
            font-weight:800;
            padding:15px 24px;
            cursor:pointer;
            min-width:200px;
            font-size:15px;
            box-shadow:0 10px 20px rgba(10, 148, 77, 0.18);
        }

        .save-btn:hover{
            opacity:0.95;
            transform:translateY(-1px);
        }

        .upload-row{
            display:flex;
            justify-content:flex-end;
            margin-bottom:12px;
        }

        .upload-btn{
            display:inline-block;
            background:#edf3f5;
            color:#26464d;
            border:none;
            padding:10px 14px;
            border-radius:10px;
            font-size:13px;
            font-weight:800;
            cursor:pointer;
        }

        .upload-btn:hover{
            opacity:0.95;
        }

        body.dark-mode .upload-btn{
            background:#334155;
            color:#f8fafc;
        }

        .hidden-file{
            display:none;
        }

        .big-photo{
            width:150px;
            height:150px;
            border-radius:50%;
            object-fit:cover;
            border:5px solid #8fbc67;
            margin:0 auto 16px;
            display:block;
            background:#f1f1f1;
        }

        .selected-file{
            margin-top:8px;
            font-size:13px;
            color:#56666c;
            word-break:break-word;
            min-height:20px;
            text-align:center;
        }

        body.dark-mode .selected-file{
            color:#cbd5e1;
        }

        .upload-submit-wrap{
            display:flex;
            justify-content:center;
            margin-top:14px;
        }

        .upload-submit{
            border:none;
            border-radius:14px;
            background:#a9d466;
            color:#163328;
            font-weight:800;
            padding:12px 18px;
            cursor:pointer;
            font-size:14px;
            min-width:140px;
        }

        .info-block{
            margin-top:18px;
            text-align:center;
        }

        .info-label{
            color:#56666c;
            font-size:13px;
            font-weight:800;
            margin-bottom:6px;
            text-transform:uppercase;
        }

        .info-value{
            font-size:18px;
            font-weight:800;
            color:#163037;
            word-break:break-word;
        }

        body.dark-mode .info-label{
            color:#94a3b8;
        }

        body.dark-mode .info-value{
            color:#f8fafc;
        }

        .helper-box{
            margin-top:22px;
            background:#f5f9f7;
            border:1px solid #dce9e1;
            border-radius:16px;
            padding:16px;
        }

        .helper-box h4{
            color:#1d3c43;
            font-size:16px;
            margin-bottom:8px;
        }

        .helper-box p{
            color:#56666c;
            font-size:13px;
            line-height:1.5;
        }

        body.dark-mode .helper-box{
            background:#1f2937;
            border-color:#334155;
        }

        body.dark-mode .helper-box h4{
            color:#f8fafc;
        }

        body.dark-mode .helper-box p{
            color:#cbd5e1;
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

        @media (max-width: 900px){
            .admin-wrapper{
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

            .card,
            .profile-panel-card{
                max-width:100%;
            }

            .top-header{
                font-size:20px;
            }

            .sub-header{
                font-size:17px;
            }

            .top-actions{
                justify-content:stretch;
            }

            .darkmode-toggle{
                width:100%;
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
                    <img src="<?php echo $admin_photo; ?>" alt="Admin Profile" class="profile-icon" id="sidebarAdminPhoto" onerror="this.src='../assets/southern.png';">
                </div>
                <h3>ADMIN</h3>
                <p><?php echo htmlspecialchars($admin_name); ?></p>
                <div class="admin-badge">ADMIN PANEL</div>
            </div>

            <div class="menu-label">Navigation</div>

            <div class="nav-group">
                <a class="side-btn" href="admin.php?view=students"><span>Dashboard</span></a>
                <a class="side-btn" href="admin.php?view=teachers"><span>List of Teacher</span></a>
                <a class="side-btn" href="admin_teacher_album.php"><span>Teacher Album</span></a>
                <a class="side-btn active" href="admin_change_password.php"><span>Change Password</span></a>
            </div>
        </div>

        <div class="sidebar-bottom">
            <a class="side-btn logout-btn" href="../auth/logout.php"><span>Log Out</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">SOUTHERN PHILIPPINES INSTITUTE OF SCIENCE AND TECHNOLOGY</div>
        <div class="sub-header">ADMIN CHANGE PASSWORD</div>

        <div class="content-area">
            <div class="top-actions">
                <button type="button" class="darkmode-toggle" id="darkModeToggle" onclick="toggleDarkMode()">🌙 DARK MODE</button>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="page-grid">
                <div class="card">
                    <h2>Change Your Password</h2>
                    <div class="card-sub">Enter your current password and set a new one for your admin account.</div>

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

                        <button type="submit" name="change_password" class="save-btn">Save New Password</button>
                    </form>
                </div>

                <div class="profile-panel-card">
                    <form method="POST" enctype="multipart/form-data" id="adminPhotoForm">
                        <div class="upload-row">
                            <label for="fileInput" class="upload-btn">UPLOAD</label>
                        </div>

                        <input type="file" id="fileInput" name="profile_photo" class="hidden-file" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
                        <input type="hidden" name="cropped_image" id="croppedImageInput">

                        <img src="<?php echo $admin_photo; ?>" alt="Admin Profile" class="big-photo" id="mainPreviewPhoto" onerror="this.src='../assets/southern.png';">

                        <div id="selectedFile" class="selected-file">No file selected</div>

                        <div class="upload-submit-wrap">
                            <button type="submit" name="upload_photo" class="upload-submit">Save Photo</button>
                        </div>
                    </form>

                    <div class="info-block">
                        <div class="info-label">Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($admin_name); ?></div>
                    </div>

                    <div class="info-block">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($admin_email); ?></div>
                    </div>

                    <div class="helper-box">
                        <h4>Admin Security Tips</h4>
                        <p>Use a strong password and update your admin profile photo to keep your account organized and secure.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="crop-modal" id="cropModal">
    <div class="crop-modal-box">
        <div class="crop-modal-header">
            <div class="crop-modal-title">Crop Admin Photo</div>
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

    const fileInput = document.getElementById("fileInput");
    const selectedFile = document.getElementById("selectedFile");
    const cropModal = document.getElementById("cropModal");
    const cropImage = document.getElementById("cropImage");
    const closeCropModal = document.getElementById("closeCropModal");
    const cancelCropBtn = document.getElementById("cancelCropBtn");
    const applyCropBtn = document.getElementById("applyCropBtn");
    const mainPreviewPhoto = document.getElementById("mainPreviewPhoto");
    const sidebarAdminPhoto = document.getElementById("sidebarAdminPhoto");
    const croppedImageInput = document.getElementById("croppedImageInput");
    const adminPhotoForm = document.getElementById("adminPhotoForm");

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
            sidebarAdminPhoto.src = originalPreview;
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
        sidebarAdminPhoto.src = croppedData;

        closeCropModalFunc(false);
    });

    adminPhotoForm.addEventListener("submit", function (e) {
        if (fileInput.files.length > 0 && croppedImageInput.value === "") {
            e.preventDefault();
            alert("Please crop the selected image first before saving.");
        }
    });
});
</script>

</body>
</html>