<?php
session_start();
date_default_timezone_set('Asia/Bangkok');
include 'WebconDB.php';

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

// รับข้อมูลจาก URL และตรวจสอบความถูกต้อง
$shopid = isset($_GET['shopid']) ? (int) $_GET['shopid'] : 0;
if ($shopid <= 0) {
    $_SESSION['order_error'] = "ร้านค้าไม่ถูกต้อง";
    header("Location: main.php");
    exit();
}

$userid = $_SESSION['userid'];

// ดึงข้อมูลร้านค้า
$shop = $connect->query("SELECT * FROM shop WHERE ShopID = $shopid");
if (!$shop || $shop->num_rows == 0) {
    $_SESSION['order_error'] = "ไม่พบร้านค้านี้";
    header("Location: main.php");
    exit();
}
$shop = $shop->fetch_assoc();

// สร้างข้อมูลคำสั่งซื้อ
$order_items = [];
$total = 0;
$current_time = time();

// เริ่ม Transaction สำหรับความปลอดภัยของข้อมูล
$connect->begin_transaction();

// คำนวณคิวของวันนี้ สำหรับร้านนี้
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

// นับบิลที่สั่งซื้อวันนี้ของร้านนี้ เพื่อกำหนดลำดับคิว
$queue_result = $connect->query("SELECT COUNT(DISTINCT Times) as count FROM orderss WHERE ShopID = $shopid AND Dates = '$current_date'");
$queue_row = $queue_result->fetch_assoc();
$queue_number = $queue_row['count'] + 1;

// นับจำนวนคิวที่ยังไม่เสร็จ (รอคิว) ก่อนบันทึกใบเสร็จใหม่
$wait_result = $connect->query("SELECT COUNT(DISTINCT CONCAT(Dates, Times)) as wait_count FROM orderss WHERE ShopID = $shopid AND Status != 'completed'");
$wait_row = $wait_result->fetch_assoc();
$wait_queue = $wait_row['wait_count'];

try {
    foreach ($_GET as $key => $value) {
        if (strpos($key, 'food_') === 0) {
            $foodid = (int) str_replace('food_', '', $key);
            $quantity = (int) $value;

            if ($foodid <= 0 || $quantity <= 0)
                continue;

            // ดึงข้อมูลอาหารและตรวจสอบสต็อก
            $food = $connect->query("SELECT * FROM foodmenu WHERE FoodID = $foodid AND ShopID = $shopid");
            if (!$food || $food->num_rows == 0)
                continue;
            $food = $food->fetch_assoc();

            if ($food['Amount'] < $quantity) {
                throw new Exception("สินค้า {$food['FoodName']} มีไม่พอ");
            }

            $subtotal = $food['Price'] * $quantity;
            $total += $subtotal;

            $order_items[] = [
                'id' => $foodid,
                'name' => $food['FoodName'],
                'price' => $food['Price'],
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];

            // บันทึกคำสั่งซื้อ
            $sql = "INSERT INTO orderss (UserID, ShopID, FoodID, Dates, Times, TotalAmount, TotalPriceIncluded) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("iiisssi", $userid, $shopid, $foodid, $current_date, $current_time, $quantity, $subtotal);
            $stmt->execute();
            $stmt->close();

            // อัปเดตสต็อก
            $new_amount = $food['Amount'] - $quantity;
            $connect->query("UPDATE foodmenu SET Amount = $new_amount WHERE FoodID = $foodid");
        }
    }

    // Commit transaction หากสำเร็จทั้งหมด
    $connect->commit();

    // บันทึกข้อมูลใบเสร็จใน Session
    // ในส่วนท้ายของ payment.php ก่อนแสดงใบเสร็จ
    $_SESSION['last_order'] = [
        'shop_id' => $shopid,
        'shop_name' => $shop['ShopName'],
        'queue_number' => $queue_number,
        'time' => date('d/m/Y H:i', strtotime($current_date . ' ' . $current_time)),
        'items' => $order_items,
        'total' => $total
    ];

    // บันทึกลง localStorage สำหรับประวัติ
    $receiptData = [
        'shop_name' => $shop['ShopName'],
        'queue_number' => $queue_number,
        'date' => date('d/m/Y H:i', strtotime($current_date . ' ' . $current_time)),
        'total' => $total,
        'items' => $order_items
    ];

    echo "<script>
    let receipts = JSON.parse(localStorage.getItem('receipts')) || [];
    receipts.push(" . json_encode($receiptData) . ");
    localStorage.setItem('receipts', JSON.stringify(receipts));
</script>";

} catch (Exception $e) {
    // Rollback หากเกิดข้อผิดพลาด
    $connect->rollback();
    $_SESSION['order_error'] = $e->getMessage();
    header("Location: shop.php?shopid=$shopid");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จ</title>
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .receipt-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 350px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            border: 2px solid #000;
        }

        .receipt-header {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px dashed #e0e0e0;
            padding-bottom: 15px;
        }

        .queue-info {
            background-color: #f8f8f8;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .queue-info div:first-child {
            font-size: 20px;
            font-weight: bold;
            color: #e74c3c;
        }

        .queue-info div:last-child {
            font-size: 14px;
            color: #7f8c8d;
        }

        .receipt-info {
            margin-bottom: 20px;
            font-size: 14px;
            color: #555;
        }

        .receipt-info div {
            margin-bottom: 5px;
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .receipt-table th {
            text-align: left;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-weight: normal;
            color: #7f8c8d;
        }

        .receipt-table td {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .align-right {
            text-align: right !important;
        }

        .align-center {
            text-align: center !important;
        }

        .receipt-total {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
            padding: 15px 0;
            border-top: 2px dashed #e0e0e0;
            margin-top: 10px;
        }

        .back-button {
            display: block;
            text-align: center;
            background-color: #3498db;
            color: white;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #2980b9;
        }

        /* Decorations */
        .corner-decoration {
            position: absolute;
            width: 50px;
            height: 50px;
            border: 2px solid #e0e0e0;
        }

        .top-left {
            top: 10px;
            left: 10px;
            border-right: none;
            border-bottom: none;
            border-radius: 15px 0 0 0;
        }

        .top-right {
            top: 10px;
            right: 10px;
            border-left: none;
            border-bottom: none;
            border-radius: 0 15px 0 0;
        }

        .bottom-left {
            bottom: 10px;
            left: 10px;
            border-right: none;
            border-top: none;
            border-radius: 0 0 0 15px;
        }

        .bottom-right {
            bottom: 10px;
            right: 10px;
            border-left: none;
            border-top: none;
            border-radius: 0 0 15px 0;
        }

        @media (max-width: 480px) {
            .receipt-container { width: 90%; max-width: 350px; padding: 20px; box-sizing: border-box; }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="receipt-container">
        <div class="corner-decoration top-left"></div>
        <div class="corner-decoration top-right"></div>
        <div class="corner-decoration bottom-left"></div>
        <div class="corner-decoration bottom-right"></div>

        <div class="receipt-header">ใบเสร็จ</div>

        <div class="queue-info">
            <div>คิวที่ <?php echo $queue_number; ?></div>
            <div>รออีก <?php echo $wait_queue; ?> คิว</div>
        </div>

        <div class="receipt-info">
            <div><strong>ShopID:</strong> <?php echo $shopid; ?></div>
            <div><strong>ชื่อร้าน:</strong> <?php echo htmlspecialchars($shop['ShopName']); ?></div>
            <div><strong>วันที่:</strong> <?php echo date('d/m/Y H:i', strtotime($current_date . ' ' . $current_time)); ?></div>
        </div>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th>รายการ</th>
                    <th class="align-center">จำนวน</th>
                    <th class="align-right">ราคา</th>
                    <th class="align-right">รวม</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $index => $item): ?>
                    <tr>
                        <td><?php echo ($index + 1) . '. ' . htmlspecialchars($item['name']); ?></td>
                        <td class="align-center"><?php echo $item['quantity']; ?></td>
                        <td class="align-right"><?php echo number_format($item['price']); ?></td>
                        <td class="align-right"><?php echo number_format($item['subtotal']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="receipt-total">
            รวมทั้งหมด <?php echo number_format($total); ?> บาท
        </div>

        <a href="main.php" class="back-button">กลับสู่หน้าหลัก</a>
    </div>
</body>

</html>