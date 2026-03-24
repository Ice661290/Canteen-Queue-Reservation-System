<?php
session_start();
date_default_timezone_set('Asia/Bangkok');
include 'WebconDB.php';

// ตรวจสอบว่าเป็นร้านค้า
if (!isset($_SESSION['shopid'])) {
    header("Location: shop_login.php");
    exit();
}

$shopid = $_SESSION['shopid'];
$action = isset($_GET['action']) ? $_GET['action'] : 'queue';

// ดึงข้อมูลร้านค้า
$shop = $connect->query("SELECT * FROM shop WHERE ShopID = $shopid")->fetch_assoc();

// ตรวจสอบการส่งฟอร์มเพิ่ม/แก้ไขเมนู
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_food'])) {
        // เพิ่มเมนูใหม่ (ไม่มีรูปภาพ)
        $foodname = $_POST['food_name'];
        $price = $_POST['price'];
        $amount = $_POST['amount'];

        $stmt = $connect->prepare("INSERT INTO foodmenu (FoodName, Price, Amount, ShopID) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siii", $foodname, $price, $amount, $shopid);
        $stmt->execute();
    } elseif (isset($_POST['delete_food'])) {
        $foodid = $_POST['food_id'];
        $hasQueue = $connect->query("SELECT COUNT(*) as count FROM orderss WHERE FoodID = $foodid AND ShopID = $shopid AND Status != 'completed'")->fetch_assoc()['count'] > 0;

        if ($hasQueue) {
            $_SESSION['operation_message'] = "ไม่สามารถลบเมนูนี้ได้ เนื่องจากมีคิวที่กำลังทำอยู่";
        } else {
            $connect->query("DELETE FROM foodmenu WHERE FoodID = $foodid");
            $_SESSION['operation_message'] = "ลบเมนูสำเร็จ";
        }
        header("Location: shop_dashboard.php?action=menu");
        exit();
    } elseif (isset($_POST['update_food'])) {
        $foodid = $_POST['food_id'];
        $hasQueue = $connect->query("SELECT COUNT(*) as count FROM orderss WHERE FoodID = $foodid AND ShopID = $shopid AND Status != 'completed'")->fetch_assoc()['count'] > 0;

        if ($hasQueue) {
            $_SESSION['operation_message'] = "ไม่สามารถแก้ไขเมนูนี้ได้ เนื่องจากมีคิวที่กำลังทำอยู่";
        } else {
            $foodname = $_POST['food_name'];
            $price = $_POST['price'];
            $amount = $_POST['amount'];

            $stmt = $connect->prepare("UPDATE foodmenu SET FoodName=?, Price=?, Amount=? WHERE FoodID=?");
            $stmt->bind_param("siii", $foodname, $price, $amount, $foodid);
            $stmt->execute();
            $_SESSION['operation_message'] = "แก้ไขเมนูสำเร็จ";
        }
        header("Location: shop_dashboard.php?action=menu");
        exit();
    } elseif (isset($_POST['complete_order']) && isset($_POST['userid']) && isset($_POST['date']) && isset($_POST['time'])) {
        $userid = (int) $_POST['userid'];
        $date = $_POST['date'];
        $time = $_POST['time'];

        $stmt = $connect->prepare("UPDATE orderss SET Status = 'completed' WHERE UserID = ? AND ShopID = ? AND Dates = ? AND Times = ?");
        $stmt->bind_param("iiss", $userid, $shopid, $date, $time);
        $update_result = $stmt->execute();

        if ($update_result) {
            $stmt2 = $connect->prepare("
                SELECT o.*, f.FoodName, s.ShopName 
                FROM orderss o
                JOIN foodmenu f ON o.FoodID = f.FoodID
                JOIN shop s ON o.ShopID = s.ShopID
                WHERE o.UserID = ? AND o.ShopID = ? AND o.Dates = ? AND o.Times = ?
            ");
            $stmt2->bind_param("iiss", $userid, $shopid, $date, $time);
            $stmt2->execute();
            $completed_orders = $stmt2->get_result();

            if (!isset($_SESSION['completed_orders'])) {
                $_SESSION['completed_orders'] = [];
            }

            while ($order = $completed_orders->fetch_assoc()) {
                $_SESSION['completed_orders'][] = [
                    'shop_name' => $order['ShopName'],
                    'food_name' => $order['FoodName'],
                    'queue_number' => substr($order['UserID'], -2) . str_replace(':', '', substr($order['Times'], 0, 5)),
                    'date' => date('d/m/Y', strtotime($order['Dates'])) . ' ' . date('H:i', strtotime($order['Times'])),
                    'total' => $order['TotalPriceIncluded']
                ];
            }

            $_SESSION['operation_message'] = "ทำเครื่องหมายคำสั่งซื้อเป็นเสร็จเรียบร้อย";
            header("Location: shop_dashboard.php?action=queue");
            exit();
        } else {
            $_SESSION['operation_message'] = "เกิดข้อผิดพลาดในการอัปเดตคำสั่งซื้อ: " . $connect->error;
        }
    }
}

// ---------- ส่วนที่แก้ไขล่าสุด: จัดกลุ่มคำสั่งซื้อและจัดการปุ่มเสร็จ ----------
$orders = $connect->query("
    SELECT o.*, u.Name as UserName, f.FoodName, o.Status 
    FROM orderss o
    JOIN users u ON o.UserID = u.UserID
    JOIN foodmenu f ON o.FoodID = f.FoodID
    WHERE o.ShopID = $shopid
    ORDER BY o.Dates ASC, o.Times ASC
");

$grouped_orders = [];
while ($order = $orders->fetch_assoc()) {
    $key = $order['UserID'] . '-' . $order['Dates'] . '-' . $order['Times'];
    if (!isset($grouped_orders[$key])) {
        $order_date = $order['Dates'];
        $order_time = $order['Times'];
        $q_res = $connect->query("SELECT COUNT(DISTINCT Times) as c FROM orderss WHERE ShopID = $shopid AND Dates = '$order_date' AND Times <= '$order_time'");
        $q_row = $q_res->fetch_assoc();
        
        $grouped_orders[$key] = [
            'userid' => $order['UserID'],
            'username' => $order['UserName'],
            'date' => $order['Dates'],
            'time' => $order['Times'],
            'items' => [],
            'total' => 0,
            'queue_number' => $q_row['c'],
            'status' => $order['Status']
        ];
    }
    $grouped_orders[$key]['items'][] = $order['FoodName'] . ' (' . $order['TotalAmount'] . ')';
    $grouped_orders[$key]['total'] += $order['TotalPriceIncluded'];
}

// ดึงเมนูอาหารของร้าน
$menu_items = $connect->query("SELECT * FROM foodmenu WHERE ShopID = $shopid");

// ตรวจสอบคิวที่รอดำเนินการ (ยังไม่ completed)
$pending_query = $connect->query("SELECT COUNT(DISTINCT UserID, Dates) as pending_count FROM orderss WHERE ShopID = $shopid AND Status != 'completed'");
$pending_orders = $pending_query->fetch_assoc()['pending_count'];
?>




<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการร้านค้า - <?php echo $shop['ShopName']; ?></title>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: url('image/Shop_board.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
            margin: 0;
            padding: 0;
        }

        .dashboard-container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(51, 28, 28, 0.8);
            border-radius: 12px;
            padding: 30px;
        }

        .action-buttons {
            display: flex;
            margin-bottom: 20px;
        }

        .action-btn {
            padding: 12px 20px;
            margin-right: 10px;
            background-color: #d2691e;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 8px;
            font-size: 16px;
        }

        .action-btn:hover {
            background-color: #a0522d;
        }

        .back-btn {
            padding: 12px 20px;
            background-color: #f44336;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .section {
            display: none;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: rgba(0, 0, 0, 0.4);
            /* เดิมคือ #fff */
            color: #fff;
            /* ให้ตัวอักษรใน section เป็นสีขาว */
        }

        .section.active {
            display: block;
        }

        .food-form,
        .queue-table {
            margin-top: 20px;
        }

        .food-list {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .food-item-box {
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: rgba(0, 0, 0, 0.4);
            /* สีพื้นหลังสำหรับเมนูอาหาร */
            padding: 15px;
            text-align: center;
            color: #fff;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            /* เงาเล็กน้อย */
            transition: transform 0.3s ease-in-out;
        }

        .food-item-box:hover {
            transform: scale(1.05);
            /* ขยายขนาดเมื่อชี้เมาส์ */
        }

        .food-item-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }

        .food-item-actions button {
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s;
        }

        .food-item-actions button:first-child {
            background-color: #3498db;
            color: white;
        }

        .food-item-actions button:first-child:hover {
            background-color: #2980b9;
        }

        .food-item-actions button:last-child {
            background-color: #e74c3c;
            color: white;
        }

        .food-item-actions button:last-child:hover {
            background-color: #c0392b;
        }

        /* ปรับฟอร์มสำหรับการเพิ่มเมนู */
        .food-form {
            margin-bottom: 30px;
        }

        .food-form h3 {
            margin-bottom: 10px;
        }

        .food-form .form-group {
            margin-bottom: 15px;
        }

        .food-form .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .food-form .form-group input {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }

        .food-form button {
            padding: 10px 20px;
            background-color: #2ecc71;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .food-form button:hover {
            background-color: #27ae60;
        }

        .queue-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .queue-table th {
            background-color: rgba(0, 0, 0, 0.5);
            /* เดิมคือ #f2f2f2 */
            color: #fff;
        }

        .queue-table th {
            background-color: #f2f2f2;
        }

        .queue-table td button {
            padding: 6px 12px;
            background-color: #d2691e;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 8px;
        }

        .queue-table td button:hover {
            background-color: #a0522d;
        }



        /* เพิ่มสไตล์สำหรับข้อความแจ้งเตือน */
        .operation-message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* ปรับสไตล์ตารางคิวที่รอ */
        .queue-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            color: #333;
        }

        .queue-table th {
            background-color: #d2691e;
            color: white;
            padding: 10px;
            text-align: left;
        }

        .queue-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .queue-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .queue-table tr:hover {
            background-color: #f1f1f1;
        }

        @media (max-width: 768px) {
            .dashboard-container { margin: 10px; padding: 15px; }
            .food-list { grid-template-columns: 1fr; }
            .action-buttons { flex-direction: column; gap: 10px; }
            .action-btn { margin-right: 0; width: 100%; }
        }

        /* Custom Alerts for Shop Dashboard */
        .shop-modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 2000;
        }
        .shop-modal-box {
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            background: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 20px 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 2001;
            width: 90%;
            max-width: 350px;
        }
        .shop-modal-box h3 {
            color: #d2691e;
            margin-top: 0;
        }
        .shop-modal-box button {
            margin-top: 15px;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php if (isset($_SESSION['operation_message'])): ?>
            <div
                class="operation-message <?php echo strpos($_SESSION['operation_message'], 'ผิดพลาด') !== false ? 'error' : 'success'; ?>">
                <?php
                echo $_SESSION['operation_message'];
                unset($_SESSION['operation_message']);
                ?>
            </div>
        <?php endif; ?>

        <h1>จัดการร้านค้า - <?php echo htmlspecialchars($shop['ShopName']); ?></h1>

        <!-- แสดงการแจ้งเตือนหากมีออเดอร์รออยู่ -->
        <?php if ($pending_orders > 0): ?>
            <div class="operation-message"
                style="background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; margin-top: -10px; margin-bottom: 20px;">
                <strong>🔔 มีคำสั่งซื้อใหม่!</strong> ตอนนี้มีคิวรอดำเนินการอยู่ทั้งหมด <?php echo $pending_orders; ?> คิว
            </div>
        <?php endif; ?>

        <button class="back-btn" onclick="window.location.href='main.php'">กลับสู่หน้าหลัก</button>

        <div class="action-buttons">
            <button class="action-btn <?php echo $action == 'menu' ? 'active' : ''; ?>"
                onclick="window.location.href='shop_dashboard.php?action=menu'">
                เพิ่ม/ลบ/แก้ไข อาหาร
            </button>
            <button class="action-btn <?php echo $action == 'queue' ? 'active' : ''; ?>"
                onclick="window.location.href='shop_dashboard.php?action=queue'">
                คิวที่รอ
            </button>
        </div>

        <!-- ส่วนจัดการเมนูอาหาร -->
        <div id="menu-section" class="section <?php echo $action == 'menu' ? 'active' : ''; ?>">
            <h2>จัดการเมนูอาหาร</h2>

            <!-- ฟอร์มเพิ่มเมนูอาหาร -->
            <div class="food-form">
                <h3>เพิ่มเมนูใหม่</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="food_name">ชื่ออาหาร</label>
                        <input type="text" id="food_name" name="food_name" required>
                    </div>
                    <div class="form-group">
                        <label for="price">ราคา</label>
                        <input type="number" id="price" name="price" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="amount">จำนวน</label>
                        <input type="number" id="amount" name="amount" min="0" required>
                    </div>
                    <button type="submit" name="create_food">เพิ่มเมนู</button>
                </form>
            </div>

            <!-- ฟอร์มแก้ไขเมนูอาหาร -->
            <div class="food-form" id="edit-form" style="display:none;">
                <h3>แก้ไขเมนู</h3>
                <form method="POST">
                    <input type="hidden" name="food_id" id="edit_food_id">
                    <div class="form-group">
                        <label for="edit_food_name">ชื่ออาหาร</label>
                        <input type="text" id="edit_food_name" name="food_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_price">ราคา</label>
                        <input type="number" id="edit_price" name="price" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_amount">จำนวน</label>
                        <input type="number" id="edit_amount" name="amount" min="0" required>
                    </div>
                    <button type="submit" name="update_food">บันทึกการแก้ไข</button>
                    <button type="button" onclick="cancelEdit()" style="background-color:#e74c3c;">ยกเลิก</button>
                </form>
            </div>


            <!-- แสดงรายการเมนู -->
            <div class="food-list">
                <?php while ($item = $menu_items->fetch_assoc()): ?>
                    <?php
                    // Check if the menu item has active orders
                    $foodId = $item['FoodID'];
                    $hasActiveQueue = $connect->query("
            SELECT COUNT(*) as count 
            FROM orderss 
            WHERE FoodID = $foodId AND ShopID = $shopid AND Status != 'completed'
        ")->fetch_assoc()['count'] > 0;
                    ?>
                    <div class="food-item-box">
                        <h4><?php echo $item['FoodName']; ?></h4>
                        <div>ราคา: <?php echo $item['Price']; ?> บาท</div>
                        <div>จำนวน: <?php echo $item['Amount']; ?></div>
                        <div class="food-item-actions">
                            <?php if (!$hasActiveQueue): ?>
                                <button
                                    onclick="editFood(<?php echo $item['FoodID']; ?>, '<?php echo $item['FoodName']; ?>', <?php echo $item['Price']; ?>, <?php echo $item['Amount']; ?>)">แก้ไข</button>
                                <button
                                    onclick="deleteFood(<?php echo $item['FoodID']; ?>, '<?php echo $item['FoodName']; ?>')">ลบ</button>
                            <?php else: ?>
                                <button style="background-color: #ccc; cursor: not-allowed;" disabled>แก้ไข</button>
                                <button style="background-color: #ccc; cursor: not-allowed;" disabled>ลบ</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>


        <!-- ส่วนคิวที่รอ -->
        <div id="queue-section" class="section <?php echo $action == 'queue' ? 'active' : ''; ?>">
            <h2>คิวที่รออยู่</h2>

            <?php if (!empty($grouped_orders)): ?>
                <table class="queue-table">
                    <thead>
                        <tr>
                            <th>คิวที่</th>
                            <th>ชื่อผู้ใช้</th>
                            <th>รายการอาหาร</th>
                            <th>ยอดรวม</th>
                            <th>วัน/เวลา</th>
                            <th>สถานะ</th>
                            <th>การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $counter = 1;
                        foreach ($grouped_orders as $key => $order):
                            $dParts = explode('-', $order['date']);
                            $tParts = explode(':', $order['time']);
                            $order_time = "{$dParts[2]}/{$dParts[1]}/{$dParts[0]} {$tParts[0]}:{$tParts[1]}";
                            $statusColor = $order['status'] == 'completed' ? '#d4edda' : '#f8f9fa'; // Green for completed, light gray for in-progress
                            ?>
                            <tr style="background-color: <?php echo $statusColor; ?>;">
                                <td><?php echo $order['queue_number']; ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><?php echo implode(', ', $order['items']); ?></td>
                                <td><?php echo number_format($order['total']); ?> บาท</td>
                                <td><?php echo $order_time; ?></td>
                                <td>
                                    <?php echo $order['status'] == 'completed' ? 'เสร็จแล้ว' : 'กำลังทำ'; ?>
                                </td>
                                <td>
                                    <?php if ($order['status'] != 'completed'): ?>
                                        <form method="POST"
                                            onsubmit="event.preventDefault(); var f = this; showCustomConfirm('ยืนยันทำเครื่องหมายคำสั่งซื้อนี้เป็นเสร็จเรียบร้อย?', function(){ f.submit(); });">
                                            <input type="hidden" name="complete_order" value="1">
                                            <input type="hidden" name="userid" value="<?php echo $order['userid']; ?>">
                                            <input type="hidden" name="date" value="<?php echo $order['date']; ?>">
                                            <input type="hidden" name="time" value="<?php echo $order['time']; ?>">
                                            <button type="submit"
                                                style="background-color:#4CAF50;">เสร็จ</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                            $counter++;
                        endforeach;
                        ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color:#fff; text-align:center;">ไม่มีคิวที่รออยู่ในขณะนี้</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ฟอร์มแก้ไขเมนูอาหาร -->
    <div id="edit-form" class="food-form" style="display: none;">
        <h3>แก้ไขเมนูอาหาร</h3>
        <form method="POST">
            <input type="hidden" id="edit_food_id" name="food_id">
            <div class="form-group">
                <label for="edit_food_name">ชื่ออาหาร</label>
                <input type="text" id="edit_food_name" name="food_name" required>
            </div>
            <div class="form-group">
                <label for="edit_price">ราคา</label>
                <input type="number" id="edit_price" name="price" min="0" required>
            </div>
            <div class="form-group">
                <label for="edit_amount">จำนวน</label>
                <input type="number" id="edit_amount" name="amount" min="0" required>
            </div>
            <button type="submit" name="update_food">บันทึกการแก้ไข</button>
            <button type="button" onclick="cancelEdit()" style="background-color:#e74c3c;">ยกเลิก</button>
        </form>
    </div>



    <script>
        // ฟังก์ชันแก้ไขเมนูอาหาร
        function editFood(foodId, foodName, price, amount) {
            document.getElementById('edit_food_id').value = foodId;
            document.getElementById('edit_food_name').value = foodName;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_amount').value = amount;
            document.getElementById('edit-form').style.display = 'block';
            document.getElementById('edit-form').scrollIntoView({ behavior: 'smooth' });
        }

        function deleteFood(foodId, foodName) {
            showCustomConfirm('แน่ใจว่าต้องการลบเมนู "' + foodName + '" นี้หรือไม่?', function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'food_id';
                inputId.value = foodId;

                const inputDelete = document.createElement('input');
                inputDelete.type = 'hidden';
                inputDelete.name = 'delete_food';
                inputDelete.value = '1';

                form.appendChild(inputId);
                form.appendChild(inputDelete);
                document.body.appendChild(form);
                form.submit();
            });
        }

        function cancelEdit() {
            // รีเซ็ตค่าฟิลด์ในฟอร์มแก้ไข
            document.getElementById('edit_food_id').value = '';
            document.getElementById('edit_food_name').value = '';
            document.getElementById('edit_price').value = '';
            document.getElementById('edit_amount').value = '';

            // ซ่อนฟอร์มแก้ไข
            document.getElementById('edit-form').style.display = 'none';
        }
        function showCustomConfirm(msg, onConfirm) {
            document.getElementById('customConfirmMessage').textContent = msg;
            document.getElementById('customConfirmOverlay').style.display = 'block';
            window.currentConfirmCallback = onConfirm;
        }
        function confirmCustomYes() {
            document.getElementById('customConfirmOverlay').style.display = 'none';
            if (window.currentConfirmCallback) window.currentConfirmCallback();
        }
        function confirmCustomNo() {
            document.getElementById('customConfirmOverlay').style.display = 'none';
        }
    </script>

    <!-- Custom Modals -->
    <div class="shop-modal-overlay" id="customConfirmOverlay">
        <div class="shop-modal-box">
            <h3>ยืนยันการดำเนินการ</h3>
            <p id="customConfirmMessage" style="color: #333; font-size: 16px;"></p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button onclick="confirmCustomYes()" style="background-color: #2ecc71;">ยืนยัน</button>
                <button onclick="confirmCustomNo()" style="background-color: #e74c3c;">ยกเลิก</button>
            </div>
        </div>
    </div>
</body>

</html>