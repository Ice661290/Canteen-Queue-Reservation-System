<?php
session_start();
include 'WebconDB.php';

$error = ''; // แก้ไข: ป้องกัน warning หากยังไม่มีค่า error

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['UserID']) || empty($_POST['Password'])) {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        $userid = (int) $_POST['UserID'];
        $password = $_POST['Password'];

        $stmt = $connect->prepare("SELECT * FROM users WHERE UserID = ?");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['PassWord'])) {
                $_SESSION['userid'] = $user['UserID'];
                $_SESSION['name'] = $user['Name'];
                header("Location: main.php");
                exit();
            } else {
                $error = "รหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $stmt_shop = $connect->prepare("SELECT * FROM shop WHERE ShopID = ?");
            $stmt_shop->bind_param("i", $userid);
            $stmt_shop->execute();
            $result_shop = $stmt_shop->get_result();

            if ($result_shop->num_rows > 0) {
                $shop = $result_shop->fetch_assoc();
                if ($password === $shop['PassWord']) {
                    $_SESSION['shopid'] = $shop['ShopID'];
                    $_SESSION['shopname'] = $shop['ShopName'];
                    header("Location: shop_dashboard.php");
                    exit();
                } else {
                    $error = "รหัสผ่านไม่ถูกต้อง";
                }
            } else {
                $error = "ไม่พบผู้ใช้งานหรือร้านค้านี้";
            }
            $stmt_shop->close();
        }

        $stmt->close();
        $connect->close();
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | ระบบจองคิวอาหาร</title>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: url('image/HCU.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
            margin: 0;
            padding: 0;
        }

        .login-wrapper {
            max-width: 420px;
            margin: 80px auto;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            padding: 30px 40px;
        }

        .login-wrapper img {
            display: block;
            margin: 0 auto 15px;
            width: 80px;
        }

        .login-wrapper h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #d2691e;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            color: #5a2d0c;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #d2691e;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #d2691e;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #a0522d;
        }

        .alert {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }



        .register-link {
            text-align: center;
            margin-top: 15px;
        }

        .register-link a {
            color: #d2691e;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-wrapper {
                margin: 20px;
                padding: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="login-wrapper">
        <img src="image/university_logo.png" alt="โลโก้มหาวิทยาลัย">
        <h2>เข้าสู่ระบบสำหรับนักศึกษาและร้านค้า</h2>

        <?php if (!empty($error)): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>รหัสนักศึกษา / รหัสร้านค้า</label>
                <input type="text" name="UserID" required>
            </div>
            <div class="form-group" style="position: relative;">
                <label>รหัสผ่าน</label>
                <input type="password" name="Password" id="password" required
                    style="padding-right: 40px; box-sizing: border-box;">
                <span onclick="togglePassword()"
                    style="position: absolute; right: 10px; top: 33px; cursor: pointer; color: #d2691e; display: flex; align-items: center; justify-content: center;"
                    title="แสดง/ซ่อนรหัสผ่าน">
                    <!-- รูปตาเปิด -->
                    <svg id="eye-open" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <!-- รูปตาปิด -->
                    <svg id="eye-closed" style="display: block;" xmlns="http://www.w3.org/2000/svg" width="20"
                        height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24">
                        </path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                </span>
            </div>
            <button type="submit">เข้าสู่ระบบ</button>
            <div class="register-link">
                <a href="register.php">ยังไม่มีบัญชี? ลงทะเบียนที่นี่</a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword() {
            var x = document.getElementById("password");
            var eyeOpen = document.getElementById("eye-open");
            var eyeClosed = document.getElementById("eye-closed");

            if (x.type === "password") {
                x.type = "text";
                eyeOpen.style.display = "block";
                eyeClosed.style.display = "none";
            } else {
                x.type = "password";
                eyeOpen.style.display = "none";
                eyeClosed.style.display = "block";
            }
        }
    </script>

</body>

</html>