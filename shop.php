<?php
session_start();
include 'WebconDB.php';

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$shopid = isset($_GET['shopid']) ? $_GET['shopid'] : null;
if (!$shopid) {
    header("Location: main.php");
    exit();
}

$shop = $connect->query("SELECT * FROM shop WHERE ShopID = $shopid")->fetch_assoc();
$menu = $connect->query("SELECT * FROM foodmenu WHERE ShopID = $shopid");

if (!isset($_SESSION['cart'][$shopid])) {
    $_SESSION['cart'][$shopid] = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $shop['ShopName']; ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background: #f5f5f5;
            padding-bottom: 100px;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 40px 20px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 12px;
            margin-top: 30px;
            border: 2px solid #d2691e;
        }
        h2 {
            margin-top: 0;
            color: #2c3e50;
        }

        .back-button {
            display: inline-block;
            margin: 20px;
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

        .cart-icon {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .menu-items {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 30px;
        }
        .menu-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff8e1;
            border: 1px solid #d2691e;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .menu-item-name {
            margin: 0 0 5px 0;
            color: #d2691e;
            font-size: 18px;
            font-weight: bold;
        }

        .menu-item-price {
            color: #e67e22;
            font-weight: bold;
        }

        .menu-item-stock {
            color: #666;
            font-size: 0.9em;
        }

        .menu-item-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-input {
            width: 50px;
            padding: 5px;
            text-align: center;
        }

        .add-to-cart-btn {
            padding: 8px 15px;
            background-color: #2ecc71;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .add-to-cart-btn:hover {
            background-color: #27ae60;
        }

        /* ตะกร้า */
        .cart-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .cart-container {
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 500px;
            max-height: 80vh;
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            overflow-y: auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .cart-items {
            margin-bottom: 20px;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .item-total {
            font-weight: bold;
        }
        .checkout-btn {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
        }
        .checkout-btn:hover {
            background-color: #218838;
        }
        .close-cart-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }
        .cart-item-actions button {
            padding: 5px 10px;
            background-color: #f2f2f2;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        .cart-item-actions button:hover {
            background-color: #e6e6e6;
        }
        .remove-btn {
            background-color: #e74c3c !important;
            color: white;
        }

        @media (max-width: 768px) {
            .container { padding: 20px 15px; margin-top: 15px; }
            .cart-container { width: 90%; max-width: 500px; padding: 15px; }
            .menu-item { flex-direction: column; align-items: flex-start; }
            .menu-item-actions { margin-top: 15px; width: 100%; justify-content: space-between; }
        }

        /* Custom Alerts for Shop */
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
            background-color: #d2691e;
            color: white;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="cart-icon" onclick="toggleCart()">🛒
    <div class="cart-count" id="cart-count">0</div>
</div>

<a href="main.php" class="back-button">← ย้อนกลับ</a>

<div class="container">
    <h2><?php echo $shop['ShopName']; ?></h2>

    <div class="menu-items">
        <?php while($item = $menu->fetch_assoc()): ?>
        <div class="menu-item">
            <div>
                <div class="menu-item-name"><?php echo $item['FoodName']; ?></div>
                <div class="menu-item-price">ราคา: <?php echo $item['Price']; ?> บาท</div>
                <div class="menu-item-stock">คงเหลือ: <?php echo $item['Amount']; ?></div>
            </div>
            <div class="menu-item-actions">
                <?php if ($item['Amount'] > 0): ?>
                <input type="number" class="quantity-input" id="quantity-<?php echo $item['FoodID']; ?>" min="1" max="<?php echo $item['Amount']; ?>" value="1">
                <button class="add-to-cart-btn" onclick="addToCart(<?php echo $item['FoodID']; ?>, '<?php echo $item['FoodName']; ?>', <?php echo $item['Price']; ?>, <?php echo $item['Amount']; ?>)">เพิ่ม</button>
            <?php else: ?>
                <span style="color: red; font-weight: bold;">หมดแล้ว</span>
            <?php endif; ?>

            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- ตะกร้าสินค้า -->
<div class="cart-overlay" id="cart-overlay">
    <div class="cart-container">
        <div class="cart-header">
            <h3>ตะกร้าสินค้า</h3>
            <button class="close-cart-btn" onclick="toggleCart()">&times;</button>
        </div>
        <div class="cart-items" id="cart-items">
            <p>ไม่มีสินค้าในตะกร้า</p>
        </div>
        <div class="cart-total" id="cart-total">รวม: 0 บาท</div>
        <button class="checkout-btn" onclick="checkout()">สั่งซื้อ</button>
    </div>
</div>

<!-- Custom Modals -->
<div class="shop-modal-overlay" id="customAlertOverlay">
    <div class="shop-modal-box">
        <h3>แจ้งเตือน</h3>
        <p id="customAlertMessage" style="color: #333; font-size: 16px;"></p>
        <button onclick="closeCustomAlert()">ตกลง</button>
    </div>
</div>

<div class="shop-modal-overlay" id="customConfirmOverlay">
    <div class="shop-modal-box">
        <h3>ยืนยันการสั่งซื้อ</h3>
        <p id="customConfirmMessage" style="color: #333; font-size: 16px;"></p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button onclick="confirmCustomYes()" style="background-color: #2ecc71;">ยืนยัน</button>
            <button onclick="confirmCustomNo()" style="background-color: #e74c3c;">ยกเลิก</button>
        </div>
    </div>
</div>

<script>
let cart = JSON.parse(sessionStorage.getItem('cart_<?php echo $shopid; ?>')) || {};

// Custom Modal Functions
function showCustomAlert(msg) {
    document.getElementById('customAlertMessage').textContent = msg;
    document.getElementById('customAlertOverlay').style.display = 'block';
}
function closeCustomAlert() {
    document.getElementById('customAlertOverlay').style.display = 'none';
}
function showCustomConfirm(msg, onConfirm) {
    document.getElementById('customConfirmMessage').textContent = msg;
    document.getElementById('customConfirmOverlay').style.display = 'block';
    window.currentConfirmCallback = onConfirm;
}
function confirmCustomYes() {
    document.getElementById('customConfirmOverlay').style.display = 'none';
    if(window.currentConfirmCallback) window.currentConfirmCallback();
}
function confirmCustomNo() {
    document.getElementById('customConfirmOverlay').style.display = 'none';
}

function updateCartCount() {
    let total = 0;
    for (const id in cart) total += cart[id].quantity;
    document.getElementById('cart-count').textContent = total;
}

function addToCart(id, name, price, maxStock) {
    let qty = parseInt(document.getElementById('quantity-' + id).value);
    if (!qty || qty <= 0) return showCustomAlert('กรุณาใส่จำนวนให้ถูกต้อง');

    let currentQty = cart[id] ? cart[id].quantity : 0;
    if (currentQty + qty > maxStock) {
        showCustomAlert(`ไม่สามารถเพิ่มเกินจำนวนคงเหลือ (${maxStock} ชิ้น)`);
        return;
    }

    cart[id] = cart[id] || { name, price, quantity: 0 };
    cart[id].quantity += qty;
    saveCart();
    showCustomAlert('เพิ่มสินค้าแล้ว');
}


function adjustQuantity(id, change) {
    const input = document.querySelector(`.cart-item input[data-foodid="${id}"]`);
    let val = parseInt(input.value) + change;
    if (val < 1) val = 1;
    input.value = val;
    updateCartItem(id, val);
}

function updateCartItem(id, qty) {
    if (qty <= 0) delete cart[id];
    else cart[id].quantity = qty;
    saveCart();
}

function removeFromCart(id) {
    delete cart[id];
    saveCart();
}

function saveCart() {
    sessionStorage.setItem('cart_<?php echo $shopid; ?>', JSON.stringify(cart));
    updateCartDisplay();
    updateCartCount();
}

function updateCartDisplay() {
    const div = document.getElementById('cart-items');
    const totalDiv = document.getElementById('cart-total');
    if (Object.keys(cart).length === 0) {
        div.innerHTML = '<p>ไม่มีสินค้าในตะกร้า</p>';
        totalDiv.textContent = 'รวม: 0 บาท';
        return;
    }
    let html = '', total = 0;
    for (const id in cart) {
        const item = cart[id];
        const sum = item.price * item.quantity;
        total += sum;
        html += `
        <div class="cart-item">
            <div>
                <strong>${item.name}</strong><br>
                <small>ราคาต่อหน่วย: ${item.price} บาท</small>
            </div>
            <div class="cart-item-actions">
                <button onclick="adjustQuantity(${id}, -1)">-</button>
                <input type="number" class="quantity-input" data-foodid="${id}" value="${item.quantity}" min="1" onchange="updateCartItem(${id}, parseInt(this.value))">
                <button onclick="adjustQuantity(${id}, 1)">+</button>
                <button class="remove-btn" onclick="removeFromCart(${id})">ลบ</button>
            </div>
            <div class="item-total">${sum} บาท</div>
        </div>`;
    }
    div.innerHTML = html;
    totalDiv.textContent = `รวม: ${total} บาท`;
}

function toggleCart() {
    const overlay = document.getElementById('cart-overlay');
    overlay.style.display = (overlay.style.display === 'block') ? 'none' : 'block';
    updateCartDisplay();
}

function checkout() {
    if (Object.keys(cart).length === 0) return showCustomAlert('ไม่มีสินค้า');
    showCustomConfirm('ยืนยันการสั่งซื้อ ใช่หรือไม่?', function() {
        let params = [];
        for (const id in cart) params.push(`food_${id}=${cart[id].quantity}`);
        sessionStorage.removeItem('cart_<?php echo $shopid; ?>'); // ล้างตะกร้า
        location.href = `payment.php?shopid=<?php echo $shopid; ?>&` + params.join('&');
    });
}


window.onload = updateCartCount;
</script>

</body>
</html>
