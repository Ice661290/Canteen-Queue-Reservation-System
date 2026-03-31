# Canteen Queue Reservation System (The Kill)

A web application designed to facilitate food ordering and queue management within the canteen of Huachiew Chalermprakiet University. It features an intuitive user interface for students and staff to order food in advance, along with a dedicated dashboard for vendors to manage orders efficiently.

---

## 💻 Example Website

 * Login

   <img width="2560" height="1440" alt="Screenshot 2026-03-31 103258" src="https://github.com/user-attachments/assets/41c84f18-e51c-4acc-9108-a674df60a7ee" />

* Register

   <img width="2560" height="1440" alt="Screenshot 2026-03-31 110104" src="https://github.com/user-attachments/assets/74058dfd-c76c-4a68-9bad-bf0b04541230" />

* Main

   <img width="2560" height="1440" alt="Screenshot 2026-03-31 130557" src="https://github.com/user-attachments/assets/2f655684-ad25-421b-94d3-ede21f8172e7" />


* Shop

   <img width="2560" height="1440" alt="Screenshot 2026-03-31 104816" src="https://github.com/user-attachments/assets/7f5b4791-862b-4fe4-b18b-c17cce91f8b1" />


* Basket

   <img width="2560" height="1440" alt="Screenshot 2026-03-31 104651" src="https://github.com/user-attachments/assets/7ad3a81b-71a6-455b-aa6f-66ee6a053b34" />


* Bill

   <img width="2560" height="1440" alt="Screenshot 2026-03-31 104708" src="https://github.com/user-attachments/assets/2a3edeaa-3c61-4b2d-9975-ad0c8868c872" />

* Order status

   <img width="2560" height="1440" alt="Screenshot 2026-03-31 105009" src="https://github.com/user-attachments/assets/96a1f651-69b7-489f-89b7-950e906d03ee" />

* Receipt history

   <img width="2560" height="1440" alt="Screenshot 2026-03-31 105301" src="https://github.com/user-attachments/assets/4568ec50-d45f-45c6-b0d0-eecead5e2e26" />

* Shop dashboard - In queue

   <img width="2560" height="1440" alt="Screenshot 2026-03-31 105120" src="https://github.com/user-attachments/assets/e939a588-e040-49b7-916c-26509511c988" />

* Shop dashboard - Add/Edit/Delete Food

   <img width="2560" height="1440" alt="Screenshot 2026-03-31 105135" src="https://github.com/user-attachments/assets/1eff64af-1968-4cc6-a622-914b132115d8" />
   

## 🌟 Features

### 1. 🔐 User Management (Authentication)
* **Register:** Students can register using their student ID (UserID), name, and password. It includes password hashing for security and checks for duplicate IDs.
* **Unified Login:** A single login page can differentiate whether the user is a student or a vendor. The system checks the user database first; if no match is found, it checks the vendor database, automatically redirecting the user to the appropriate dashboard based on their role.

### 2. 🎓 User Features
* **Main Dashboard:** Displays a list of all available shops in the system, allowing users to click and view their menus.
* **Food Ordering and Shopping Cart:**
  * View menus, prices, and remaining stock in real-time.
  * Add items to the cart (utilizes sessionStorage for fast performance without needing to reload the page).
  * Adjust item quantities (increase/decrease) or remove items from the cart.
  * Features a Floating Cart Icon displaying the total number of selected items.
* **Checkout & Queue Generation:**
  * Upon confirmation, the system immediately deducts the food stock (utilizing Database Transactions to prevent data errors).
  * Automatically calculates the daily queue number for that specific shop.
  * Displays a receipt detailing the queue number and the number of people waiting ahead (Wait Queue).
* **Order Tracking:**
  * Includes a Clipboard icon to view the status of orders currently being prepared.
  * Features an on-screen popup alert when the shop marks the food as finished.
* **Receipt History:**
  * Includes a receipt icon to view past order history, retrieving order summaries from both the database and localStorage.

### 3. 🏪 Shop Dashboard Features
* **Menu Management:**
  * Add new menu items, along with setting prices and stock quantities.
  * Edit or delete existing menu items.
  * Guard Feature: Includes a safeguard that prevents vendors from deleting or editing menu items that are "currently in the queue" to avoid losing or corrupting customer order data.
* **Queue Management:**
  * Displays a notification banner if there are new orders or pending queues.
  * Shows a list of orders grouped by customer and order time (Grouped Orders), allowing the shop to see exactly what items a specific customer ordered in a single bill.
  * Displays an "in progress" status and provides a "Complete" button.
  * Once marked as complete, the status updates, and the system immediately sends a notification signal to the student's side.

## 🛠 Tools & Technologies

* **Frontend** 

| **HTML** | **CSS** | **JavaScript** |
| :---: | :---: | :---: |
| <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/html5/html5-original.svg" width="100" height="100"> | <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/css3/css3-original.svg" width="100" height="100"> | <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/javascript/javascript-original.svg" width="100" height="100"> |

* **Backend**

| **PHP** | 
| :---: | 
| <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/php/php-original.svg" width="100" height="100"> |

* **Local Environment & Database**

| **XAMPP** | **MySQL** |
| :---: | :---: |
| <img src="https://upload.wikimedia.org/wikipedia/commons/0/03/Xampp_logo.svg" width="100" height="100"> | <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/mysql/mysql-original-wordmark.svg" width="100" height="100"> |
* **Text Editor**
  
| **Vscode** | 
| :---: | 
| <a href="https://code.visualstudio.com/"><img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/vscode/vscode-original.svg" width="100" height="100"> |

* **Other**
  
| **Git** | **Github** |
| :---: | :---: |
| <a href="https://git-scm.com/"><img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/git/git-original.svg" width="100" height="100"> | <a href="https://github.com/"><img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/github/github-original.svg" width="100" height="100"> |

