<?php
include 'WebconDB.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid = isset($_POST['UserID']) ? trim($_POST['UserID']) : '';
    $name = isset($_POST['Name']) ? trim($_POST['Name']) : '';
    $password = $_POST['Password'] ?? '';
    $confirm_password = $_POST['ConfirmPassword'] ?? '';

    if (empty($userid) || empty($name) || empty($password) || empty($confirm_password)) {
        $error = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
    } elseif ($password !== $confirm_password) {
        $error = "รหัสผ่านไม่ตรงกัน";
    } else {
        $stmt_check = $connect->prepare("SELECT UserID FROM users WHERE UserID = ?");
        $stmt_check->bind_param("i", $userid);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows > 0) {
            $error = "รหัสนักศึกษานี้ถูกใช้งานแล้ว";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $connect->prepare("INSERT INTO users (UserID, Name, PassWord) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $userid, $name, $hashed_password);

            if ($stmt->execute()) {
                header("Location: login.php?registered=1");
                exit();
            } else {
                $error = "เกิดข้อผิดพลาดในการสมัครสมาชิก: " . $connect->error;
                error_log("Registration error: " . $connect->error);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก | ระบบจองอาหารนักศึกษา</title>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: url('image/food-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .register-container {
            max-width: 420px;
            margin: 60px auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 35px 40px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.25);
        }

        h2 {
            text-align: center;
            color: #a0522d;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 6px;
            color: #5a2d0c;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #caaa84;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 22px;
        }

        button {
            padding: 10px 20px;
            border: none;
            background-color: #d2691e;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: #a0522d;
        }

        .alert {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 10px 18px;
            background-color: #bdc3c7;
            color: #2c3e50;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            transition: background-color 0.2s ease;
        }

        .back-button:hover {
            background-color: #95a5a6;
        }

        @media screen and (max-width: 480px) {
            .register-container {
                margin: 30px 20px;
                padding: 25px;
            }

            .button-group {
                flex-direction: column;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <a href="login.php" class="back-button">← ย้อนกลับ</a>
    <div class="register-container">
        <h2>สมัครสมาชิกระบบจองอาหาร</h2>
        <?php if (!empty($error)): ?>
            <div class="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>รหัสนักศึกษา (UserID)</label>
                <input type="number" name="UserID" required>
            </div>
            <div class="form-group">
                <label>ชื่อ (Name)</label>
                <input type="text" name="Name" required>
            </div>
            <div class="form-group">
                <label>รหัสผ่าน (Password)</label>
                <input type="password" name="Password" required>
            </div>
            <div class="form-group">
                <label>ยืนยันรหัสผ่าน (Confrim Password) </label>
                <input type="password" name="ConfirmPassword" required>
            </div>
            <div class="button-group">
                <button type="submit" style="width: 100%;">สมัคร</button>
            </div>
        </form>
    </div>
</body>
</html>
