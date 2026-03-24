<?php
session_start(); // เริ่ม session
date_default_timezone_set('Asia/Bangkok'); // ตั้งค่า timezone ประเทศไทย
include 'WebconDB.php'; // เชื่อมต่อฐานข้อมูล

// ออกจากระบบ
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่าล็อกอินหรือไม่
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

// ดึงข้อมูลร้านค้าจากฐานข้อมูล
$shops = $connect->query("SELECT * FROM shop");

// Fetch order statuses for the logged-in user
$order_statuses = $connect->query("
    SELECT s.ShopID, s.ShopName, o.Status, f.FoodName 
    FROM shop s
    LEFT JOIN orderss o ON s.ShopID = o.ShopID AND o.UserID = {$_SESSION['userid']}
    LEFT JOIN foodmenu f ON o.FoodID = f.FoodID
    WHERE s.ShopID IN (SELECT ShopID FROM orderss WHERE UserID = {$_SESSION['userid']})
");



// ---------- ส่วนที่แก้ไขล่าสุด: แจ้งเตือนเมื่ออาหารพร้อม ----------
if (isset($_SESSION['user_notify_' . $_SESSION['userid']])) {
    $notify = $_SESSION['user_notify_' . $_SESSION['userid']];
    echo "<div class='alert'>อาหารที่ร้าน {$notify['shop_name']} คิวที่ {$notify['queue']} พร้อมรับแล้ว!</div>";
    unset($_SESSION['user_notify_' . $_SESSION['userid']]);
}
?>




<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลัก | ระบบจองอาหาร</title>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: #f8f8f8;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .dashboard {
            width: 80%;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 2px solid #d2691e;
        }

        .top-right-profile {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 15px;
            color: #d2691e;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }

        .welcome-text {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
        }

        .logout-btn-small {
            background-color: #d2691e;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            cursor: pointer;
            font-size: 14px;
        }

        .logout-btn-small:hover {
            background-color: #a0522d;
        }

        .shop-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff8e1;
            border: 1px solid #d2691e;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .shop-info h4 {
            margin: 0;
            font-size: 18px;
            color: #d2691e;
        }

        .shop-info p {
            font-size: 14px;
            color: #555;
        }

        button {
            background-color: #d2691e;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 20px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #a0522d;
        }

        .restaurant-list h3 {
            text-align: left;
            font-size: 20px;
            margin-top: 20px;
            margin-bottom: 10px;
            color: #333;
        }

        /* เพิ่มสไตล์ไอคอนใบเสร็จ */
        .receipt-icon {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background-color: #d2691e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .receipt-icon:hover {
            background-color: #a0522d;
            transform: scale(1.1);
        }

        .receipt-badge {
            display: none;
        }

        .alert {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border-radius: 5px;
            margin: 10px 0;
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .content {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .order-status {
            flex: 1;
            background: #fff8e1;
            border: 1px solid #d2691e;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .order-status h3 {
            margin-bottom: 15px;
            color: #d2691e;
        }

        .status-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .status-card p {
            margin: 5px 0;
            font-size: 14px;
            color: #333;
        }

        .restaurant-list {
            flex: 2;
            padding-bottom: 80px;
            /* เพิ่มระยะห่างกันไอคอนด้านล่างทับ */
        }

        /* Order status button styling */
        .order-status-icon {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 60px;
            height: 60px;
            background-color: #d2691e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .order-status-icon:hover {
            background-color: #a0522d;
            transform: scale(1.1);
        }

        .order-status-badge {
            display: none;
        }

        @media (max-width: 768px) {
            .dashboard { width: 90%; margin: 20px auto; padding: 15px; }
            .restaurant-list { flex: 1 1 100%; padding-bottom: 60px; }
            .shop-card { flex-direction: column; align-items: flex-start; gap: 10px; }
            .shop-card button { width: 100%; }
        }

        /* Custom Modals */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 2000;
        }
        .modal-container {
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            background-color: #fcfcfc;
            border-radius: 12px;
            padding: 20px;
            overflow-y: auto;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            border: none;
            z-index: 2001;
        }
        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #555;
            background: none;
            border: none;
            padding: 0;
        }
        .modal-close:hover { color: #e74c3c; }
    </style>
</head>

<body>
    <div class="dashboard">
        <div class="top-right-profile">
            <div class="welcome-text">ยินดีต้อนรับ, <?php echo $_SESSION['name']; ?></div>
            <form method="post" style="margin: 0;">
                <button type="submit" name="logout" class="logout-btn-small">ออกจากระบบ</button>
            </form>
        </div>

        <div class="content">
            <!-- Right section for shop list -->
            <div class="restaurant-list">
                <h3>ร้านค้าทั้งหมด</h3>
                <?php while ($shop = $shops->fetch_assoc()): ?>
                    <div class="shop-card">
                        <div class="shop-info">
                            <h4><?php echo $shop['ShopName']; ?></h4>
                            <p>ShopID: <?php echo $shop['ShopID']; ?></p>
                        </div>
                        <button onclick="location.href='shop.php?shopid=<?php echo $shop['ShopID']; ?>'">เลือก</button>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>


    <!-- ส่วนแสดงไอคอนใบเสร็จ -->
    <div class="receipt-icon" onclick="showReceiptHistory()">
        📃
        <div class="receipt-badge" id="receipt-badge">0</div>
    </div>

    <!-- Order status button -->
    <div class="order-status-icon" onclick="showOrderStatus()">
        📋
        <div class="order-status-badge" id="order-status-badge">0</div>
    </div>

    <!-- Custom Modal Structures -->
    <div class="modal-overlay" id="mainModalOverlay">
        <div class="modal-container">
            <button class="modal-close" onclick="closeMainModal()">&times;</button>
            <div id="mainModalContent"></div>
        </div>
    </div>

    <div class="modal-overlay" id="customAlertOverlay">
        <div class="modal-container" style="max-width: 400px; text-align: center; background: #ffffff;">
            <h3 id="customAlertTitle" style="color: #d2691e; margin-top: 0;">แจ้งเตือน</h3>
            <p id="customAlertMessage" style="font-size: 16px; color: #333; margin: 20px 0;"></p>
            <button onclick="closeCustomAlert()" style="padding: 10px 30px; border: none; background-color: #d2691e; color: white; border-radius: 5px; cursor: pointer;">ตกลง</button>
        </div>
    </div>

    <script>
        // ฟังก์ชันจัดการ Custom Modals
        function showCustomAlert(msg) {
            document.getElementById('customAlertMessage').textContent = msg;
            document.getElementById('customAlertOverlay').style.display = 'block';
        }
        function closeCustomAlert() {
            document.getElementById('customAlertOverlay').style.display = 'none';
        }
        function openMainModal(htmlContent) {
            document.getElementById('mainModalContent').innerHTML = htmlContent;
            document.getElementById('mainModalOverlay').style.display = 'block';
        }
        function closeMainModal() {
            document.getElementById('mainModalOverlay').style.display = 'none';
        }

        // ฟังก์ชันแสดงประวัติใบเสร็จ (ดึงจากฐานข้อมูลตรงๆ)
        function showReceiptHistory() {
            const receipts = <?php
            $receipt_query = $connect->query("
                SELECT o.ShopID, s.ShopName, o.Dates, o.Times, SUM(o.TotalPriceIncluded) as total, COUNT(o.OrderID) as items_count 
                FROM orderss o 
                JOIN shop s ON o.ShopID = s.ShopID 
                WHERE o.UserID = {$_SESSION['userid']} AND o.Status = 'completed'
                GROUP BY o.ShopID, o.Dates, o.Times 
                ORDER BY o.Dates DESC, o.Times DESC
            ");
            echo json_encode($receipt_query->fetch_all(MYSQLI_ASSOC));
            ?>;

            if (receipts.length === 0) {
                showCustomAlert('ยังไม่มีประวัติใบเสร็จ');
            } else {
                let historyHTML = '<div style="padding:10px;">';
                historyHTML += '<h2 style="color:#d2691e;text-align:center;margin-top:0;">ประวัติใบเสร็จ</h2>';

                receipts.forEach((receipt, index) => {
                    const dParts = receipt.Dates.split('-');
                    const tParts = receipt.Times.split(':');
                    const dateStr = `${dParts[2]}/${dParts[1]}/${dParts[0]} ${tParts[0]}:${tParts[1]}`;

                    historyHTML += `<div style="border:1px solid #d2691e;border-radius:8px;padding:15px;margin-bottom:15px; background-color:#fff8e1; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">`;
                    historyHTML += `<h3 style="margin-top:0; color:#d2691e;">ใบเสร็จ #${index + 1}</h3>`;
                    historyHTML += `<p><strong>ร้าน:</strong> ${receipt.ShopName}</p>`;
                    historyHTML += `<p><strong>วันที่:</strong> ${dateStr}</p>`;
                    historyHTML += `<p><strong>จำนวนรายการ:</strong> ${receipt.items_count} อย่าง</p>`;
                    historyHTML += `<p><strong>รวมทั้งสิ้น:</strong> <span style="color:#e74c3c; font-weight:bold;">${parseFloat(receipt.total).toLocaleString()} บาท</span></p>`;
                    historyHTML += `</div>`;
                });

                historyHTML += '</div>';

                openMainModal(historyHTML);
            }
        }

        // อัปเดตจำนวนใบเสร็จ
        function updateReceiptBadge() {
            const receipts = <?php echo json_encode($receipt_query->fetch_all(MYSQLI_ASSOC)); ?>;
            // The badge expects the count
            document.getElementById('receipt-badge').textContent = receipts ? receipts.length : 0;
        }

        // อัปเดตครั้งแรกเมื่อโหลดหน้า
        window.onload = function () {
            updateReceiptBadge();
            updateOrderStatusBadge();
        };

        // Function to display all order statuses in a popup
        function showOrderStatus() {
            const orders = <?php
            $order_statuses = $connect->query("
                SELECT o.ShopID, s.ShopName, o.Dates, o.Times, o.Status, SUM(o.TotalPriceIncluded) as total, 
                GROUP_CONCAT(CONCAT(f.FoodName, ' (', o.TotalAmount, ')') SEPARATOR ', ') as items 
                FROM orderss o
                JOIN shop s ON o.ShopID = s.ShopID
                JOIN foodmenu f ON o.FoodID = f.FoodID
                WHERE o.UserID = {$_SESSION['userid']} AND o.Status != 'completed'
                GROUP BY o.ShopID, o.Dates, o.Times
                ORDER BY o.Dates DESC, o.Times DESC
            ");
            
            $orders_list = [];
            while($row = $order_statuses->fetch_assoc()) {
                $shopid = $row['ShopID'];
                $order_date = $row['Dates'];
                $order_time = $row['Times'];
                
                $q_res = $connect->query("SELECT COUNT(DISTINCT Times) as count FROM orderss WHERE ShopID = $shopid AND Dates = '$order_date' AND Times <= '$order_time'");
                $q_row = $q_res->fetch_assoc();
                $row['queue_number'] = $q_row['count'];
                
                $orders_list[] = $row;
            }
            
            echo json_encode($orders_list);
            ?>;

            if (orders.length === 0) {
                showCustomAlert('ไม่มีคำสั่งซื้อที่กำลังดำเนินการในขณะนี้');
            } else {
                let statusHTML = '<div style="padding:10px;">';
                statusHTML += '<h2 style="color:#d2691e;text-align:center;margin-top:0;">สถานะคำสั่งซื้อที่กำลังทำ</h2>';

                orders.forEach((order, index) => {
                    const dParts = order.Dates.split('-');
                    const tParts = order.Times.split(':');
                    const dateStr = `${tParts[0]}:${tParts[1]}`;

                    statusHTML += `
                    <div style="border:1px solid #d2691e;border-radius:8px;padding:15px;margin-bottom:15px;background-color:#fff8e1;color:#333;box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <h3 style="margin-top:0; color:#d2691e;">คิวที่ ${order.queue_number} - ${order.ShopName}</h3>
                        <p><strong>เวลาสั่ง:</strong> ${dateStr}</p>
                        <p><strong>รายการ:</strong> ${order.items}</p>
                        <p><strong>ยอดชำระ:</strong> ${parseFloat(order.total).toLocaleString()} บาท</p>
                        <p><strong>สถานะ:</strong> <span style="background-color:#f39c12; color:white; padding: 3px 8px; border-radius: 4px;">กำลังทำ</span></p>
                    </div>
                `;
                });

                statusHTML += '</div>';

                openMainModal(statusHTML);
            }
        }

        // Update the order status badge count
        function updateOrderStatusBadge() {
            const orders = <?php echo json_encode($order_statuses->fetch_all(MYSQLI_ASSOC)); ?>;
            // The badge expects the count of pending orders
            document.getElementById('order-status-badge').textContent = orders ? orders.length : 0;
        }
    </script>
</body>

</html>