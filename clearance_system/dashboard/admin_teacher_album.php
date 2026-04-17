<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$current_page = basename($_SERVER['PHP_SELF']);
$admin_name = isset($_SESSION['name']) && !empty($_SESSION['name']) ? $_SESSION['name'] : 'Administrator';

/* ADMIN PROFILE PHOTO */
$default_admin_photo = "../assets/southern.png";
$admin_photo = $default_admin_photo;
$admin_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

if ($admin_id > 0) {
    $admin_stmt = $conn->prepare("SELECT profile_photo FROM admin WHERE id = ?");
    $admin_stmt->bind_param("i", $admin_id);
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result()->fetch_assoc();

    if ($admin_result && !empty($admin_result['profile_photo']) && file_exists("../assets/uploads/admin/" . $admin_result['profile_photo'])) {
        $admin_photo = "../assets/uploads/admin/" . $admin_result['profile_photo'];
    }
}

$upload_dir = "../assets/uploads/teacher_album/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$edit_mode = false;
$edit_id = 0;
$edit_teacher = [
    'teacher_name' => '',
    'teacher_email' => '',
    'teacher_contact' => '',
    'teacher_department' => '',
    'teacher_photo' => ''
];

/* EDIT MODE LOAD */
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_stmt = $conn->prepare("SELECT * FROM teacher_album WHERE id = ?");
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();

    if ($edit_result->num_rows > 0) {
        $edit_teacher = $edit_result->fetch_assoc();
        $edit_mode = true;
    }
}

/* ADD TEACHER */
if (isset($_POST['add_teacher_album'])) {
    $teacher_name = trim($_POST['teacher_name']);
    $teacher_email = trim($_POST['teacher_email']);
    $teacher_contact = trim($_POST['teacher_contact']);
    $teacher_department = trim($_POST['teacher_department']);
    $teacher_photo_name = "";

    if (!empty($teacher_name)) {
        if (isset($_FILES['teacher_photo']) && $_FILES['teacher_photo']['error'] === 0) {
            $file_tmp = $_FILES['teacher_photo']['tmp_name'];
            $file_name = $_FILES['teacher_photo']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($file_ext, $allowed)) {
                $teacher_photo_name = time() . "_" . rand(1000,9999) . "." . $file_ext;
                $destination = $upload_dir . $teacher_photo_name;
                move_uploaded_file($file_tmp, $destination);
            }
        }

        $insert = $conn->prepare("INSERT INTO teacher_album (teacher_name, teacher_photo, teacher_email, teacher_contact, teacher_department) VALUES (?, ?, ?, ?, ?)");
        $insert->bind_param("sssss", $teacher_name, $teacher_photo_name, $teacher_email, $teacher_contact, $teacher_department);

        if ($insert->execute()) {
            $message = "Teacher profile added successfully.";
        } else {
            $message = "Failed to add teacher profile.";
        }
    } else {
        $message = "Teacher name is required.";
    }
}

/* UPDATE TEACHER */
if (isset($_POST['update_teacher_album'])) {
    $update_id = intval($_POST['teacher_id']);
    $teacher_name = trim($_POST['teacher_name']);
    $teacher_email = trim($_POST['teacher_email']);
    $teacher_contact = trim($_POST['teacher_contact']);
    $teacher_department = trim($_POST['teacher_department']);
    $old_photo = trim($_POST['old_photo']);
    $teacher_photo_name = $old_photo;

    if (!empty($teacher_name)) {
        if (isset($_FILES['teacher_photo']) && $_FILES['teacher_photo']['error'] === 0) {
            $file_tmp = $_FILES['teacher_photo']['tmp_name'];
            $file_name = $_FILES['teacher_photo']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($file_ext, $allowed)) {
                $new_photo_name = time() . "_" . rand(1000,9999) . "." . $file_ext;
                $destination = $upload_dir . $new_photo_name;

                if (move_uploaded_file($file_tmp, $destination)) {
                    if (!empty($old_photo) && file_exists($upload_dir . $old_photo)) {
                        unlink($upload_dir . $old_photo);
                    }
                    $teacher_photo_name = $new_photo_name;
                }
            }
        }

        $update = $conn->prepare("UPDATE teacher_album SET teacher_name = ?, teacher_photo = ?, teacher_email = ?, teacher_contact = ?, teacher_department = ? WHERE id = ?");
        $update->bind_param("sssssi", $teacher_name, $teacher_photo_name, $teacher_email, $teacher_contact, $teacher_department, $update_id);

        if ($update->execute()) {
            header("Location: admin_teacher_album.php?updated=1");
            exit;
        } else {
            $message = "Failed to update teacher profile.";
        }
    } else {
        $message = "Teacher name is required.";
    }
}

/* DELETE TEACHER */
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);

    $photo_stmt = $conn->prepare("SELECT teacher_photo FROM teacher_album WHERE id = ?");
    $photo_stmt->bind_param("i", $delete_id);
    $photo_stmt->execute();
    $photo_result = $photo_stmt->get_result();

    if ($photo_result->num_rows > 0) {
        $photo_data = $photo_result->fetch_assoc();
        if (!empty($photo_data['teacher_photo']) && file_exists($upload_dir . $photo_data['teacher_photo'])) {
            unlink($upload_dir . $photo_data['teacher_photo']);
        }
    }

    $delete_stmt = $conn->prepare("DELETE FROM teacher_album WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);

    if ($delete_stmt->execute()) {
        header("Location: admin_teacher_album.php?deleted=1");
        exit;
    } else {
        $message = "Failed to delete teacher profile.";
    }
}

if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $message = "Teacher profile deleted successfully.";
}

if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $message = "Teacher profile updated successfully.";
}

/* GET ALL TEACHERS */
$teachers = $conn->query("SELECT * FROM teacher_album ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Teacher Album</title>
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, Helvetica, sans-serif;
        }

        :root{
            --bg-main:#ecf3f5;
            --bg-soft:#f8fbfc;
            --card:#ffffff;
            --card-2:rgba(255,255,255,0.78);
            --text:#0f172a;
            --muted:#64748b;
            --line:#d8e3e8;
            --primary:#0e8f5b;
            --primary-dark:#0a6e46;
            --accent:#8fbc67;
            --blue:#1e88e5;
            --danger:#e53e3e;
            --sidebar1:#063845;
            --sidebar2:#032f39;
            --sidebar3:#022933;
            --header:#8dbb5f;
            --subheader:#033b46;
            --subheader-text:#71ffc2;
            --shadow:0 12px 30px rgba(15, 23, 42, 0.08);
        }

        body{
            background:
                radial-gradient(circle at top left, rgba(16, 185, 129, 0.09), transparent 24%),
                radial-gradient(circle at bottom right, rgba(30, 136, 229, 0.08), transparent 22%),
                linear-gradient(180deg, #edf4f6 0%, #eaf1f4 100%);
            color:var(--text);
            min-height:100vh;
            transition:all .25s ease;
        }

        .admin-wrapper{
            display:flex;
            min-height:100vh;
        }

        .sidebar{
            position:fixed;
            top:0;
            left:0;
            width:250px;
            height:100vh;
            background:linear-gradient(180deg, var(--sidebar1) 0%, var(--sidebar2) 52%, var(--sidebar3) 100%);
            color:#fff;
            padding:18px 14px;
            overflow-y:auto;
            z-index:1000;
            border-right:1px solid rgba(255,255,255,0.08);
            box-shadow:16px 0 38px rgba(0,0,0,0.16);
            display:flex;
            flex-direction:column;
            justify-content:space-between;
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
            background:linear-gradient(180deg, rgba(255,255,255,0.12), rgba(255,255,255,0.06));
            border:1px solid rgba(255,255,255,0.10);
            border-radius:28px;
            padding:22px 14px 18px;
            text-align:center;
            box-shadow:0 16px 28px rgba(0,0,0,0.18);
            overflow:hidden;
            backdrop-filter:blur(8px);
        }

        .profile-box::before{
            content:"";
            position:absolute;
            top:0;
            left:0;
            right:0;
            height:82px;
            background:linear-gradient(135deg, rgba(143,188,103,0.45), rgba(108,190,255,0.22));
        }

        .profile-icon-wrap{
            position:relative;
            width:102px;
            height:102px;
            margin:8px auto 12px;
            padding:4px;
            border-radius:50%;
            background:linear-gradient(135deg, #edffd0, #8fbc67);
            box-shadow:0 10px 20px rgba(0,0,0,0.18);
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
            font-size:26px;
            margin-bottom:6px;
            font-weight:900;
            line-height:1.1;
            z-index:2;
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
            box-shadow:0 10px 20px rgba(0,0,0,0.14);
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

        .main-content{
            flex:1;
            min-width:0;
            margin-left:250px;
        }

        .top-header{
            background:linear-gradient(135deg, #98c76b 0%, #7dac4e 100%);
            color:#fefefe;
            text-align:center;
            padding:24px 20px 18px;
            font-size:19px;
            font-weight:900;
            letter-spacing:1px;
            text-transform:uppercase;
            box-shadow:0 6px 20px rgba(0,0,0,0.08);
            position:relative;
        }

        .top-header::after{
            content:"";
            position:absolute;
            left:50%;
            bottom:0;
            transform:translateX(-50%);
            width:180px;
            height:4px;
            border-radius:999px;
            background:rgba(255,255,255,0.45);
        }

        .sub-header{
            background:linear-gradient(135deg, #043f4b, #032f39);
            color:var(--subheader-text);
            text-align:center;
            padding:16px 20px;
            font-size:23px;
            font-weight:900;
            letter-spacing:1px;
            text-transform:uppercase;
            box-shadow:inset 0 -1px 0 rgba(255,255,255,0.05);
        }

        .content-area{
            padding:24px;
        }

        .hero-bar{
            display:grid;
            grid-template-columns:1.5fr .8fr .8fr;
            gap:18px;
            margin-bottom:22px;
        }

        .hero-card,
        .mini-stat{
            background:var(--card-2);
            border:1px solid rgba(255,255,255,0.62);
            box-shadow:var(--shadow);
            border-radius:24px;
            backdrop-filter:blur(12px);
            -webkit-backdrop-filter:blur(12px);
        }

        .hero-card{
            padding:22px 22px;
            position:relative;
            overflow:hidden;
        }

        .hero-card::before{
            content:"";
            position:absolute;
            right:-30px;
            top:-30px;
            width:120px;
            height:120px;
            background:radial-gradient(circle, rgba(143,188,103,.35), transparent 70%);
        }

        .hero-title{
            font-size:26px;
            font-weight:900;
            color:#063845;
            margin-bottom:8px;
        }

        .hero-text{
            color:#58707a;
            font-size:14px;
            line-height:1.6;
            max-width:700px;
        }

        .mini-stat{
            padding:20px;
            display:flex;
            flex-direction:column;
            justify-content:center;
            min-height:120px;
        }

        .mini-label{
            font-size:12px;
            font-weight:800;
            color:#67808a;
            text-transform:uppercase;
            letter-spacing:1px;
            margin-bottom:8px;
        }

        .mini-value{
            font-size:34px;
            font-weight:900;
            color:#063845;
            line-height:1;
        }

        .mini-sub{
            margin-top:8px;
            color:#6b7f88;
            font-size:13px;
            font-weight:700;
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
            background:linear-gradient(135deg, #111827, #1f2937);
            color:#fff;
            font-weight:900;
            cursor:pointer;
            transition:0.25s ease;
            box-shadow:0 12px 22px rgba(15, 23, 42, 0.18);
        }

        .darkmode-toggle:hover{
            transform:translateY(-2px);
        }

        .section-card{
            background:var(--card-2);
            border:1px solid rgba(255,255,255,0.62);
            border-radius:28px;
            box-shadow:var(--shadow);
            backdrop-filter:blur(12px);
            -webkit-backdrop-filter:blur(12px);
            padding:24px 22px;
            margin-bottom:22px;
            transition:all 0.25s ease;
        }

        .section-head{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:14px;
            margin-bottom:18px;
            flex-wrap:wrap;
        }

        .section-title{
            font-size:24px;
            font-weight:900;
            color:#033b46;
        }

        .section-subtitle{
            font-size:13px;
            font-weight:700;
            color:#6b7f88;
            margin-top:4px;
        }

        .message{
            margin-bottom:18px;
            padding:15px 18px;
            border-radius:16px;
            background:linear-gradient(135deg, #e9fbef, #f8fff9);
            color:#155724;
            font-weight:800;
            border:1px solid #cfead7;
            border-left:5px solid #8fbc67;
            box-shadow:0 6px 16px rgba(0,0,0,0.04);
        }

        .teacher-form{
            display:grid;
            grid-template-columns:repeat(2, 1fr);
            gap:18px;
        }

        .input-wrap{
            display:flex;
            flex-direction:column;
            gap:8px;
        }

        .input-label{
            font-size:13px;
            font-weight:900;
            color:#28464d;
        }

        .teacher-form input{
            width:100%;
            padding:15px 16px;
            border:1.5px solid var(--line);
            border-radius:16px;
            font-size:14px;
            outline:none;
            background:rgba(255,255,255,0.94);
            color:#1b1b1b;
            transition:0.22s ease;
        }

        .teacher-form input:focus{
            border-color:#12c96b;
            box-shadow:0 0 0 4px rgba(18, 201, 107, 0.12);
            transform:translateY(-1px);
        }

        .full-width{
            grid-column:1 / -1;
        }

        .upload-box{
            border:2px dashed #c4d5dc;
            border-radius:20px;
            padding:18px;
            background:linear-gradient(135deg, #fbfeff, #f2f7f9);
        }

        .upload-preview-row{
            display:flex;
            align-items:center;
            gap:16px;
            flex-wrap:wrap;
        }

        .current-preview{
            width:80px;
            height:80px;
            border-radius:18px;
            overflow:hidden;
            border:2px solid #dbe7eb;
            background:#fff;
            flex-shrink:0;
        }

        .current-preview img{
            width:100%;
            height:100%;
            object-fit:cover;
        }

        .upload-box input[type="file"]{
            border:none;
            box-shadow:none;
            padding:0;
            background:transparent;
        }

        .upload-note{
            margin-top:10px;
            font-size:12px;
            color:#667980;
            font-weight:700;
        }

        .form-actions{
            display:flex;
            align-items:center;
            gap:12px;
            flex-wrap:wrap;
            margin-top:4px;
        }

        .save-btn{
            border:none;
            border-radius:15px;
            background:linear-gradient(135deg, #11bc66, #0d8a4b);
            color:#fff;
            font-weight:900;
            padding:15px 30px;
            cursor:pointer;
            min-width:200px;
            box-shadow:0 12px 20px rgba(10, 148, 77, 0.18);
            transition:0.22s ease;
        }

        .save-btn:hover{
            transform:translateY(-2px);
        }

        .cancel-btn{
            display:inline-block;
            padding:15px 24px;
            border-radius:15px;
            background:#e5edf0;
            color:#1f3940;
            text-decoration:none;
            font-weight:900;
            transition:0.22s ease;
        }

        .cancel-btn:hover{
            transform:translateY(-2px);
        }

        .album-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(270px, 1fr));
            gap:22px;
        }

        .teacher-card{
            background:linear-gradient(180deg, rgba(255,255,255,0.95), rgba(247,251,252,0.92));
            border-radius:28px;
            padding:22px 18px 18px;
            text-align:center;
            border:1px solid #dfe8eb;
            box-shadow:0 12px 28px rgba(0,0,0,0.07);
            transition:0.25s ease;
            position:relative;
            overflow:hidden;
        }

        .teacher-card::before{
            content:"";
            position:absolute;
            top:0;
            left:0;
            right:0;
            height:96px;
            background:linear-gradient(135deg, rgba(143,188,103,0.24), rgba(88,170,222,0.12));
        }

        .teacher-card::after{
            content:"";
            position:absolute;
            right:-30px;
            bottom:-30px;
            width:120px;
            height:120px;
            background:radial-gradient(circle, rgba(30,136,229,.08), transparent 70%);
        }

        .teacher-card:hover{
            transform:translateY(-6px);
            box-shadow:0 18px 34px rgba(0,0,0,0.12);
        }

        .teacher-photo-wrap{
            width:116px;
            height:116px;
            border-radius:50%;
            margin:0 auto 14px;
            padding:4px;
            background:linear-gradient(135deg, #efffd7, #8fbc67);
            position:relative;
            z-index:2;
            box-shadow:0 12px 22px rgba(0,0,0,0.12);
        }

        .teacher-photo{
            width:100%;
            height:100%;
            border-radius:50%;
            object-fit:cover;
            background:#eee;
            border:3px solid #fff;
        }

        .teacher-name{
            font-size:22px;
            font-weight:900;
            color:#003b49;
            margin-bottom:10px;
            position:relative;
            z-index:2;
            line-height:1.15;
            text-transform:uppercase;
        }

        .teacher-meta{
            display:flex;
            flex-direction:column;
            gap:8px;
            margin-bottom:12px;
            position:relative;
            z-index:2;
        }

        .teacher-info{
            font-size:14px;
            color:#53646a;
            line-height:1.5;
            word-break:break-word;
            background:#f4f8fa;
            border:1px solid #e1eaee;
            padding:10px 12px;
            border-radius:14px;
        }

        .teacher-dept{
            display:inline-block;
            margin-top:4px;
            padding:9px 15px;
            border-radius:999px;
            background:#e5f5cf;
            color:#22411f;
            font-size:12px;
            font-weight:900;
            position:relative;
            z-index:2;
        }

        .card-actions{
            margin-top:16px;
            position:relative;
            z-index:2;
            display:flex;
            justify-content:center;
            gap:10px;
            flex-wrap:wrap;
        }

        .edit-btn,
        .delete-btn{
            display:inline-block;
            text-decoration:none;
            color:#fff;
            padding:11px 18px;
            border-radius:13px;
            font-weight:900;
            min-width:110px;
            transition:.22s ease;
        }

        .edit-btn{
            background:linear-gradient(135deg, #3ea7ff, #1e88e5);
            box-shadow:0 8px 16px rgba(30, 136, 229, 0.16);
        }

        .delete-btn{
            background:linear-gradient(135deg, #ff6460, #e93d38);
            box-shadow:0 8px 16px rgba(233, 61, 56, 0.16);
        }

        .edit-btn:hover,
        .delete-btn:hover{
            transform:translateY(-2px);
        }

        .empty-state{
            text-align:center;
            padding:42px 20px;
            border-radius:24px;
            background:linear-gradient(135deg, #f9fbfc, #f1f5f7);
            border:1px dashed #cfdadd;
        }

        .empty-state h3{
            color:#033b46;
            font-size:24px;
            margin-bottom:8px;
        }

        .empty-state p{
            color:#697a80;
            font-weight:700;
        }

        /* DARK MODE */
        body.dark-mode{
            background:
                radial-gradient(circle at top left, rgba(111,159,79,0.08), transparent 24%),
                radial-gradient(circle at bottom right, rgba(59,130,246,0.08), transparent 22%),
                #0b1220;
            color:#e5e7eb;
        }

        body.dark-mode .top-header{
            background:linear-gradient(135deg, #769f4d 0%, #5f843f 100%);
            color:#f8fafc;
        }

        body.dark-mode .sub-header{
            background:linear-gradient(135deg, #022c3a, #011f2a);
            color:#7dffc8;
        }

        body.dark-mode .hero-card,
        body.dark-mode .mini-stat,
        body.dark-mode .section-card{
            background:rgba(17, 24, 39, 0.82);
            border-color:rgba(148, 163, 184, 0.18);
            box-shadow:0 14px 26px rgba(0,0,0,0.28);
        }

        body.dark-mode .hero-title,
        body.dark-mode .mini-value,
        body.dark-mode .section-title,
        body.dark-mode .empty-state h3{
            color:#f8fafc;
        }

        body.dark-mode .hero-text,
        body.dark-mode .mini-label,
        body.dark-mode .mini-sub,
        body.dark-mode .section-subtitle,
        body.dark-mode .input-label,
        body.dark-mode .upload-note,
        body.dark-mode .empty-state p{
            color:#cbd5e1;
        }

        body.dark-mode .darkmode-toggle{
            background:linear-gradient(135deg, #8ab35d, #5f843f);
            color:#fff;
        }

        body.dark-mode .message{
            background:#1f2937;
            color:#d1fae5;
            border-color:#334155;
            border-left-color:#8ab35d;
        }

        body.dark-mode .teacher-form input{
            background:#0f172a;
            color:#f8fafc;
            border-color:#334155;
        }

        body.dark-mode .teacher-form input::placeholder{
            color:#94a3b8;
        }

        body.dark-mode .upload-box{
            background:#111827;
            border-color:#334155;
        }

        body.dark-mode .cancel-btn{
            background:#334155;
            color:#f8fafc;
        }

        body.dark-mode .teacher-card{
            background:linear-gradient(180deg, rgba(17,24,39,0.96), rgba(15,23,42,0.92));
            border-color:#334155;
        }

        body.dark-mode .teacher-card::before{
            background:linear-gradient(135deg, rgba(111,159,79,0.25), rgba(59,130,246,0.12));
        }

        body.dark-mode .teacher-name{
            color:#f8fafc;
        }

        body.dark-mode .teacher-info{
            color:#dbe5ef;
            background:#0f172a;
            border-color:#233044;
        }

        body.dark-mode .teacher-dept{
            background:#1f2937;
            color:#d9f99d;
        }

        body.dark-mode .current-preview{
            border-color:#334155;
            background:#0f172a;
        }

        body.dark-mode .empty-state{
            background:#111827;
            border-color:#334155;
        }

        @media (max-width: 1180px){
            .hero-bar{
                grid-template-columns:1fr 1fr;
            }

            .hero-card{
                grid-column:1 / -1;
            }
        }

        @media (max-width: 980px){
            .teacher-form{
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

            .top-header{
                font-size:17px;
                line-height:1.45;
                padding:18px 14px;
            }

            .sub-header{
                font-size:18px;
            }

            .hero-bar{
                grid-template-columns:1fr;
            }

            .top-actions{
                justify-content:stretch;
            }

            .darkmode-toggle{
                width:100%;
            }

            .section-head{
                align-items:flex-start;
            }
        }

        @media (max-width: 540px){
            .content-area{
                padding:14px;
            }

            .section-card,
            .hero-card,
            .mini-stat{
                padding:18px 16px;
                border-radius:22px;
            }

            .teacher-name{
                font-size:18px;
            }

            .save-btn,
            .cancel-btn{
                width:100%;
                text-align:center;
            }

            .form-actions{
                flex-direction:column;
                align-items:stretch;
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
                    <img src="<?php echo $admin_photo; ?>" alt="Admin Photo" class="profile-icon" onerror="this.src='../assets/southern.png';">
                </div>
                <h3>Admin</h3>
                <p><?php echo htmlspecialchars($admin_name); ?></p>
                <div class="admin-badge">ADMIN PANEL</div>
            </div>

            <div class="menu-label">Navigation</div>

            <div class="nav-group">
                <a class="side-btn" href="admin.php?view=students"><span>🏠 Dashboard</span></a>
                <a class="side-btn active" href="admin_teacher_album.php"><span>📚 Teacher Album</span></a>
                <a class="side-btn" href="admin_change_password.php"><span>🔒 Change Password</span></a>
            </div>
        </div>

        <div class="sidebar-bottom">
            <a class="side-btn logout-btn" href="../auth/logout.php"><span>↩ Log Out</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">SOUTHERN PHILIPPINES INSTITUTE OF SCIENCE AND TECHNOLOGY</div>
        <div class="sub-header">ADMIN TEACHER ALBUM</div>

        <div class="content-area">
            <div class="top-actions">
                <button type="button" class="darkmode-toggle" id="darkModeToggle" onclick="toggleDarkMode()">🌙 DARK MODE</button>
            </div>

            <div class="section-card">
                <div class="section-title"><?php echo $edit_mode ? 'Edit Teacher Album' : 'Add Teacher Album'; ?></div>

                <?php if (!empty($message)): ?>
                    <div class="message"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="teacher-form">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="teacher_id" value="<?php echo $edit_teacher['id']; ?>">
                        <input type="hidden" name="old_photo" value="<?php echo htmlspecialchars($edit_teacher['teacher_photo']); ?>">
                    <?php endif; ?>

                    <div class="input-wrap">
                        <label class="input-label">Teacher Name</label>
                        <input type="text" name="teacher_name" placeholder="Enter teacher full name" required value="<?php echo htmlspecialchars($edit_teacher['teacher_name']); ?>">
                    </div>

                    <div class="input-wrap">
                        <label class="input-label">Teacher Email</label>
                        <input type="email" name="teacher_email" placeholder="Enter teacher email" value="<?php echo htmlspecialchars($edit_teacher['teacher_email']); ?>">
                    </div>

                    <div class="input-wrap">
                        <label class="input-label">Contact Number</label>
                        <input type="text" name="teacher_contact" placeholder="Enter teacher contact number" value="<?php echo htmlspecialchars($edit_teacher['teacher_contact']); ?>">
                    </div>

                    <div class="input-wrap">
                        <label class="input-label">Department / Position</label>
                        <input type="text" name="teacher_department" placeholder="Enter department or position" value="<?php echo htmlspecialchars($edit_teacher['teacher_department']); ?>">
                    </div>

                    <div class="input-wrap full-width">
                        <label class="input-label">Teacher Photo <?php echo $edit_mode ? '(Optional - leave blank if no change)' : ''; ?></label>
                        <div class="upload-box">
                            <input type="file" name="teacher_photo" accept=".jpg,.jpeg,.png,.webp">
                            <div class="upload-note">Supported files: JPG, JPEG, PNG, WEBP</div>
                        </div>
                    </div>

                    <div class="full-width">
                        <?php if ($edit_mode): ?>
                            <button type="submit" name="update_teacher_album" class="save-btn">Update Teacher</button>
                            <a href="admin_teacher_album.php" class="cancel-btn">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_teacher_album" class="save-btn">Add Teacher</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="section-card">
                <div class="section-title">Teacher Album List</div>

                <?php if ($teachers && $teachers->num_rows > 0): ?>
                    <div class="album-grid">
                        <?php while ($teacher = $teachers->fetch_assoc()): ?>
                            <?php
                                $teacher_photo = (!empty($teacher['teacher_photo']) && file_exists($upload_dir . $teacher['teacher_photo']))
                                    ? $upload_dir . $teacher['teacher_photo']
                                    : "../assets/southern.png";
                            ?>
                            <div class="teacher-card">
                                <div class="teacher-photo-wrap">
                                    <img src="<?php echo $teacher_photo; ?>" alt="Teacher Photo" class="teacher-photo">
                                </div>

                                <div class="teacher-name"><?php echo htmlspecialchars($teacher['teacher_name']); ?></div>

                                <div class="teacher-info">
                                    <?php echo !empty($teacher['teacher_email']) ? htmlspecialchars($teacher['teacher_email']) : 'No email'; ?>
                                </div>

                                <div class="teacher-info">
                                    <?php echo !empty($teacher['teacher_contact']) ? htmlspecialchars($teacher['teacher_contact']) : 'No contact number'; ?>
                                </div>

                                <div class="teacher-dept">
                                    <?php echo !empty($teacher['teacher_department']) ? htmlspecialchars($teacher['teacher_department']) : 'Teacher'; ?>
                                </div>

                                <div class="card-actions">
                                    <a href="admin_teacher_album.php?edit=<?php echo $teacher['id']; ?>" class="edit-btn">Edit</a>
                                    <a href="admin_teacher_album.php?delete=<?php echo $teacher['id']; ?>" class="delete-btn" onclick="return confirm('Delete this teacher profile?')">Delete</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No Teacher Album Yet</h3>
                        <p>Start by adding a teacher profile above.</p>
                    </div>
                <?php endif; ?>
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

    document.addEventListener('DOMContentLoaded', function() {
        applyDarkModeState();
    });
</script>

</body>
</html>