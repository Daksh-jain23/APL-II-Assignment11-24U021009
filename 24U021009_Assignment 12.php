<?php
session_start();

// Database credentials from environment variables (required for Render deployment)
$servername = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');
$port = getenv('DB_PORT') ?: 3306;

if (!$servername || !$username || !$dbname) {
    die("Database environment variables (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) are not set.");
}

// Avoid fatal uncaught exceptions for mysqli connection errors in PHP 8.1+
mysqli_report(MYSQLI_REPORT_OFF);

$db_error_msg = "";
// Create MySQL connection (suppress warnings with @ and handle gracefully)
$conn = @new mysqli($servername, $username, $password, $dbname, (int)$port);

if ($conn->connect_error) {
    $db_error_msg = htmlspecialchars($conn->connect_error);
}


// Single File API Handling for Content Fetching
if (isset($_GET['api']) && $_GET['api'] == 'sections') {
    header('Content-Type: application/json');
    $data = array();
    
    if (empty($db_error_msg)) {
        $sql = "SELECT menu_name, content FROM sections";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
    } else {
        // Return a fallback block if database is offline
        $data[] = array(
            "menu_name" => "Home",
            "content" => "<h1 class='hero-title'>Database Connection Offline</h1>
                          <p class='hero-theme' style='color:#ff5252;'>Error: " . $db_error_msg . "</p>
                          <p class='hero-date'>Please check your Render environment variables or database provider.</p>
                          <div class='container' style='background:#f8d7da; color:#721c24; margin-top:20px; border:1px solid #f5c6cb;'>
                              <h3>Cannot load content</h3>
                              <p>The backend failed to connect to FreeSQLDatabase.</p>
                          </div>"
        );
    }
    echo json_encode($data);
    exit;
}

// Login & Registration Form Processing
$message = "";
$messageType = "";
$lastAction = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $lastAction = $_POST['action'];
    
    if (!empty($db_error_msg)) {
        $message = "Database is offline. Actions cannot be performed!";
        $messageType = "error";
    } else {
        if ($_POST['action'] == 'register') {
            $first_name = $conn->real_escape_string($_POST['first_name']);
            $last_name = $conn->real_escape_string($_POST['last_name']);
            $email = $conn->real_escape_string($_POST['email']);
            
            // Hash password for security
            $pass = $_POST['password'];
            $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
            
            $contact = $conn->real_escape_string($_POST['contact']);
            $gender = $conn->real_escape_string($_POST['gender']);
            $education = $conn->real_escape_string($_POST['education']);
            $role = $conn->real_escape_string($_POST['role']);
            $state = $conn->real_escape_string($_POST['state']);
            $city = $conn->real_escape_string($_POST['city']);

            $checkDuplicate = $conn->query("SELECT email, contact_number FROM users WHERE email = '$email' OR contact_number = '$contact'");
            if($checkDuplicate->num_rows > 0) {
                $row = $checkDuplicate->fetch_assoc();
                if ($row['email'] === $email) {
                    $message = "Email already registered! Please login.";
                } else {
                    $message = "Contact number already registered!";
                }
                $messageType = "error";
            } else {
                $sql = "INSERT INTO users (first_name, last_name, email, password, contact_number, gender, education, role, state, city) 
                        VALUES ('$first_name', '$last_name', '$email', '$hashed_pass', '$contact', '$gender', '$education', '$role', '$state', '$city')";
                if ($conn->query($sql) === TRUE) {
                    $_SESSION['user_id'] = $conn->insert_id;
                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['email'] = $email;
                    $message = "Registration successful! Welcome, " . htmlspecialchars($first_name) . "!";
                    $messageType = "success";
                } else {
                    $message = "Error: " . $conn->error;
                    $messageType = "error";
                }
            }
        } else if ($_POST['action'] == 'login') {
            $email = $conn->real_escape_string($_POST['email']);
            $pass = $_POST['password'];

            $sql = "SELECT * FROM users WHERE email = '$email'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($pass, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['email'] = $user['email'];
                    $message = "Login successful! Welcome back, " . $user['first_name'] . ".";
                    $messageType = "success";
                } else {
                    $message = "Invalid password!";
                    $messageType = "error";
                }
            } else {
                $message = "User not found! Please register.";
                $messageType = "error";
            }
        } else if ($_POST['action'] == 'update_profile' && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $first_name = $conn->real_escape_string($_POST['first_name']);
            $last_name = $conn->real_escape_string($_POST['last_name']);
            $contact = $conn->real_escape_string($_POST['contact']);
            $education = $conn->real_escape_string($_POST['education']);
            $state = $conn->real_escape_string($_POST['state']);
            $city = $conn->real_escape_string($_POST['city']);

            // Check if new contact number is already taken by someone else
            $checkContact = $conn->query("SELECT id FROM users WHERE contact_number = '$contact' AND id != $user_id");
            if($checkContact->num_rows > 0) {
                $message = "Contact number already registered by another user!";
                $messageType = "error";
            } else {
                $sql = "UPDATE users SET first_name='$first_name', last_name='$last_name', contact_number='$contact', 
                        education='$education', state='$state', city='$city' WHERE id=$user_id";
                if ($conn->query($sql) === TRUE) {
                    $_SESSION['first_name'] = $first_name;
                    $message = "Profile updated successfully!";
                    $messageType = "success";
                } else {
                    $message = "Update failed: " . $conn->error;
                    $messageType = "error";
                }
            }
        } else if ($_POST['action'] == 'update_password' && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $current_pass = $_POST['current_password'];
            $new_pass = $_POST['new_password'];
            $confirm_pass = $_POST['confirm_password'];

            if ($new_pass !== $confirm_pass) {
                $message = "New passwords do not match!";
                $messageType = "error";
            } else {
                $sql = "SELECT password FROM users WHERE id=$user_id";
                $result = $conn->query($sql);
                $user = $result->fetch_assoc();

                if (password_verify($current_pass, $user['password'])) {
                    $hashed_new_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                    $sql_update = "UPDATE users SET password='$hashed_new_pass' WHERE id=$user_id";
                    if ($conn->query($sql_update) === TRUE) {
                        $message = "Password updated successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Update failed: " . $conn->error;
                        $messageType = "error";
                    }
                } else {
                    $message = "Current password is incorrect!";
                    $messageType = "error";
                }
            }
        }
    }
}

// Fetch current user details if logged in
$userData = null;
if ($isLoggedIn && empty($db_error_msg)) {
    $uid = $_SESSION['user_id'];
    $res = $conn->query("SELECT * FROM users WHERE id=$uid");
    if ($res && $res->num_rows > 0) {
        $userData = $res->fetch_assoc();
    }
}


// Logout Handling
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); // redirect without GET params
    exit;
}

$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICA2S 2026 - Assignment 12</title>
    <style>
        /* 
           EXISTING STYLES (Assignment 9) 
            */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background-color: #f0f2f5; padding-top: 60px; }
        nav { position: fixed; top: 0; width: 100%; background-color: #1e3347; color: #fff; z-index: 1000; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); display: flex; justify-content: center; align-items: center; padding: 0 10px; height: 60px; }
        .nav-inner { width: 100%; max-width: 1400px; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.4rem; font-weight: bold; white-space: nowrap; margin-right: 10px; }
        .nav-links { display: flex; list-style: none; align-items: center; }
        .nav-links li { margin-left: 5px; }
        .nav-links a { color: #fff; text-decoration: none; font-weight: 500; transition: color 0.3s; padding: 8px 6px; border-radius: 6px; font-size: 0.85rem; white-space: nowrap; cursor: pointer; }
        .nav-links a:hover { background-color: rgba(255, 255, 255, 0.1); color: #3498db; }
        .menu-toggle { display: none; flex-direction: column; cursor: pointer; }
        .bar { height: 3px; width: 25px; background-color: #fff; margin: 3px 0; transition: 0.4s; border-radius: 2px; }

        @media (max-width: 1250px) {
            .nav-links { display: none; flex-direction: column; width: 100%; position: absolute; top: 60px; left: 0; background-color: #1e3347; padding: 20px 0; border-bottom: 2px solid #3498db; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2); }
            .nav-links.active { display: flex; }
            .nav-links li { margin: 10px 0; text-align: center; width: 100%; }
            .nav-links a { display: block; font-size: 1.1rem; padding: 10px; }
            .menu-toggle { display: flex; }
            .nav-inner { justify-content: space-between; padding: 0 10px; }
        }

        section { padding: 20px 0px; display: flex; justify-content: center; align-items: center; scroll-margin-top: 80px; }
        #home { background-color: #2c3e50; color: #fff; text-align: center; display: flex; flex-direction: column; justify-content: center; min-height: 80vh; }
        #home .container { background-color: transparent; box-shadow: none; color: inherit; }
        .hero-title { color: #00ffff; font-size: 2.2rem; margin-bottom: 15px; font-weight: bold; }
        .hero-theme { color: #ecf0f1; font-size: 1.4rem; margin-bottom: 20px; font-style: italic; }
        .hero-date { color: #ecf0f1; margin-bottom: 20px; font-size: 1.1rem; }
        .hero-venue { color: #ecf0f1; font-weight: 600; margin-bottom: 20px; font-size: 1.1rem; }
        .hero-organizers { color: #1abc9c; margin-bottom: 30px; }
        .marquee-container { width: 100%; overflow: hidden; white-space: nowrap; margin-top: 20px; }
        .hero-mode { display: inline-block; color: #ff5252; font-weight: bold; font-size: 1.2rem; animation: marquee 15s linear infinite; padding-left: 100%; }
        @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-100%); } }

        .container { width: 100%; max-width: 1000px; background-color: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); min-height: 100px; }
        section:nth-child(even) .container { background-color: #f8f9fa; }
        section:nth-child(odd) .container { background-color: #ffffff; }
        h1, h2, h3 { margin-bottom: 10px; color: #2c3e50; }
        h2 { border-bottom: 2px solid #3498db; padding-bottom: 10px; display: inline-block; }
        p, ul { margin-bottom: 15px; }
        ul { margin-left: 20px; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }

        .card { background: #e8e8e8; padding: 30px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; height: 100%; overflow: hidden; display: flex; flex-direction: column; justify-content: flex-start; }
        .card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25); }
        .theme-card { background: rgb(245, 246, 255); border-left: 4px solid #3498db; }
        .theme-card:hover { border-left-color: #e74c3c; }
        .speaker-card { background: #fff; text-align: center; }
        .speaker-card h3 { color: #2c3e50; }

        footer { background-color: #2c3e50; color: #fff; text-align: center; padding: 30px 20px; margin-top: 40px; border-top: 3px solid #3498db; }
        .footer-content { max-width: 1000px; margin: 0 auto; }
        .footer-contact h4 { margin-bottom: 10px; color: #3498db; }
        footer a { color: #3498db; text-decoration: none; }
        @media (max-width: 1024px) { .nav-links a { font-size: 0.8rem; padding: 5px; margin-left: 5px; } }
        @media (max-width: 768px) {
            .nav-links { display: none; flex-direction: column; width: 100%; position: absolute; top: 60px; left: 0; background-color: #2c3e50; padding: 20px 0; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; }
            .nav-links.active { display: flex; }
            .nav-links li { margin: 10px 0; text-align: center; }
            .menu-toggle { display: flex; }
            .container { padding: 20px; }
        }
        #loading-status { text-align: center; padding: 20px; font-weight: bold; color: #555; }
        .error-msg { color: red; }
        .success-msg { color: green; }

        /* 
           NEW LOGIN & REGISTRATION MODAL STYLES
            */
        .modal-overlay {
            display: none;
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            justify-content: center; align-items: center;
            backdrop-filter: blur(5px);
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: white; border-radius: 12px;
            width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            position: relative; padding: 30px;
        }
        .close-modal {
            position: absolute; top: 15px; right: 20px;
            font-size: 24px; font-weight: bold; color: #888;
            cursor: pointer; transition: 0.2s;
        }
        .close-modal:hover { color: #e74c3c; }
        .modal-tabs { display: flex; border-bottom: 2px solid #eee; margin-bottom: 20px; }
        .tab-btn {
            flex: 1; padding: 10px; text-align: center; cursor: pointer;
            font-weight: 600; color: #7f8c8d; border-bottom: 3px solid transparent; transition: 0.3s;
        }
        .tab-btn.active { color: #3498db; border-color: #3498db; }
        .form-container { display: none; }
        .form-container.active { display: block; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9rem; color: #2c3e50; }
        .form-group label::after { content: " *"; color: red; }
        .form-group input, .form-group select {
            width: 100%; padding: 10px; border: 1px solid #ccc;
            border-radius: 6px; font-size: 1rem; transition: 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #3498db; outline: none; box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }
        .form-row { display: flex; gap: 10px; }
        .form-row .form-group { flex: 1; }
        .submit-btn {
            width: 100%; padding: 12px; background: #3498db; color: white;
            border: none; border-radius: 6px; font-size: 1rem; font-weight: bold;
            cursor: pointer; transition: 0.3s; margin-top: 10px;
        }
        .submit-btn:hover { background: #2980b9; }

        .login-btn-nav { background-color: #e74c3c !important; font-weight: bold !important; padding: 8px 15px !important; }
        .login-btn-nav:hover { background-color: #c0392b !important; color: #fff !important; }
        
        .alert-box {
            padding: 15px; margin-bottom: 20px; border-radius: 6px; text-align: center; font-weight: 600;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>

<body>

    <nav>
        <div class="nav-inner">
            <div class="logo">ICA2S 2026</div>
            <div class="menu-toggle" id="mobile-menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#committee">Committee</a></li>
                <li><a href="#dates">Important Dates</a></li>
                <li><a href="#speakers">Speakers</a></li>
                <li><a href="#workshop">Workshop</a></li>
                <li><a href="#submission">Submission</a></li>
                <li><a href="#special-session">Special Session</a></li>
                <li><a href="#registration">Registration</a></li>
                <li><a href="#sponsorship">Sponsorship</a></li>
                <li><a href="#contact">Contact</a></li>
                <?php if (!empty($db_error_msg)): ?>
                    <li><a class="login-btn-nav" style="background:#e74c3c; cursor:not-allowed;" title="Database Offline">DB Offline</a></li>
                <?php elseif ($isLoggedIn): ?>
                    <li><a class="login-btn-nav" id="nav-account-btn">My Account</a></li>
                    <li><a href="?action=logout" class="login-btn-nav">Logout (<?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?>)</a></li>
                <?php else: ?>
                    <li><a class="login-btn-nav" id="nav-login-btn">Login / Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Messages moved to Modal -->

    <!-- Logs removed -->

    <main>
        <section id="home"><div class="container"></div></section>
        <section id="committee"><div class="container"></div></section>
        <section id="dates"><div class="container"></div></section>
        <section id="speakers"><div class="container"></div></section>
        <section id="workshop"><div class="container"></div></section>
        <section id="submission"><div class="container"></div></section>
        <section id="special-session"><div class="container"></div></section>
        <section id="registration"><div class="container"></div></section>
        <section id="sponsorship"><div class="container"></div></section>
        <section id="contact"><div class="container"></div></section>
    </main>

    <footer>
        <div class="footer-content">
            <p>&copy; 2026 ICA2S Conference. All rights reserved.</p>
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <p><a href="mailto:ica2s@hotmail.com">ica2s@hotmail.com</a></p>
            </div>
            <div style="margin-top: 20px; line-height: 1.2;">
                <p style="margin-bottom: 5px;">Name - Daksh Jain</p>
                <p style="margin-bottom: 5px;">Scholar No. 24U021009</p>
                <p style="margin-bottom: 5px;">Branch - CSE (AI)</p>
            </div>
        </div>
    </footer>

    <!-- LOGIN & REGISTRATION MODAL -->
    <div class="modal-overlay" id="auth-modal">
        <div class="modal-content">
            <span class="close-modal" id="close-modal">&times;</span>
            <div class="modal-tabs">
                <div class="tab-btn active" id="tab-login">Login</div>
                <div class="tab-btn" id="tab-register">Register</div>
            </div>

            <?php if ($message != ""): ?>
                <div class="alert-box <?php echo $messageType == 'error' ? 'alert-error' : 'alert-success'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div class="form-container active" id="form-login">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label for="login-email">Email ID</label>
                        <input type="email" id="login-email" name="email" required placeholder="Enter registered email">
                    </div>
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" required placeholder="Enter your password">
                    </div>
                    <button type="submit" class="submit-btn">Login</button>
                </form>
            </div>

            <!-- Registration Form -->
            <div class="form-container" id="form-register">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="register">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email ID (Login ID)</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" name="contact" pattern="[0-9]{10}" title="10 digit valid phone number" required>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" required>
                                <option value="" disabled selected>Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Education</label>
                            <input type="text" name="education" required placeholder="e.g. B.Tech, M.Tech">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" required>
                                <option value="Student">Student</option>
                                <option value="Faculty">Faculty</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>State</label>
                            <input type="text" name="state" required>
                        </div>
                        <div class="form-group">
                            <label>City/Place</label>
                            <input type="text" name="city" required>
                        </div>
                    </div>
                    <button type="submit" class="submit-btn">Register</button>
                </form>
            </div>
        </div>
    </div>

    <!-- MY ACCOUNT MODAL -->
    <div class="modal-overlay" id="account-modal">
        <div class="modal-content">
            <span class="close-modal" id="close-account-modal">&times;</span>
            <div class="modal-tabs">
                <div class="tab-btn active" id="tab-profile">Profile Details</div>
                <div class="tab-btn" id="tab-security">Security</div>
            </div>

            <?php if (($lastAction == 'update_profile' || $lastAction == 'update_password') && $message != ""): ?>
                <div class="alert-box <?php echo $messageType == 'error' ? 'alert-error' : 'alert-success'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Profile Form -->
            <div class="form-container active" id="form-profile">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($userData['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($userData['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email ID (Read-only)</label>
                        <input type="email" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" disabled style="background-color: #f0f0f0; cursor: not-allowed;">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" name="contact" pattern="[0-9]{10}" value="<?php echo htmlspecialchars($userData['contact_number'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Education</label>
                            <input type="text" name="education" value="<?php echo htmlspecialchars($userData['education'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>State</label>
                            <input type="text" name="state" value="<?php echo htmlspecialchars($userData['state'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>City/Place</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($userData['city'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="submit-btn" style="background:#27ae60;">Update Profile</button>
                </form>
            </div>

            <!-- Password Update Form -->
            <div class="form-container" id="form-security">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="update_password">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required placeholder="Enter current password">
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required placeholder="Enter new password">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required placeholder="Confirm new password">
                    </div>
                    <button type="submit" class="submit-btn" style="background:#e67e22;">Change Password</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Navbar Toggle
        const menuToggle = document.getElementById('mobile-menu');
        const navLinks = document.querySelector('.nav-links');

        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });

        document.querySelectorAll('.nav-links a:not(.login-btn-nav)').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
            });
        });

        
        // Content Loading Logic (Modified to fetch from existing PHP script)
    
        document.addEventListener("DOMContentLoaded", async () => {
            // Section mapping: menu_name from DB → HTML section id
            const sectionMap = {
                "Home": "home",
                "Committee": "committee",
                "Important Dates": "dates",
                "Speakers": "speakers",
                "Workshop": "workshop",
                "Submission": "submission",
                "Special Session": "special-session",
                "Registration": "registration",
                "Sponsorship": "sponsorship",
                "Contact": "contact"
            };

            try {
                // Changing fetch from '/api/sections' to same PHP file via GET ?api=sections
                // Using URL params on the same page avoids needing separate server configuration
                const response = await fetch('?api=sections');
                if (!response.ok) throw new Error('Failed to fetch data from database');

                const rows = await response.json();

                rows.forEach(row => {
                    const menuName = row.menu_name ? row.menu_name.trim() : null;
                    const contentHtml = row.content;
                    if (menuName && sectionMap[menuName]) {
                        const sectionId = sectionMap[menuName];
                        const sectionContainer = document.querySelector(`#${sectionId} .container`);
                        if (sectionContainer && contentHtml) {
                            sectionContainer.innerHTML = contentHtml;
                        }
                    }
                });

            } catch (error) {
                console.error("Error loading from MySQL:", error);
            }
        });

        
        // Modal Logic
        const modal = document.getElementById('auth-modal');
        const navLoginBtn = document.getElementById('nav-login-btn');
        const closeModal = document.getElementById('close-modal');
        
        const tabLogin = document.getElementById('tab-login');
        const tabRegister = document.getElementById('tab-register');
        const formLogin = document.getElementById('form-login');
        const formRegister = document.getElementById('form-register');

        if(navLoginBtn) {
            navLoginBtn.addEventListener('click', () => {
                modal.classList.add('active');
            });
        }

        closeModal.addEventListener('click', () => {
            modal.classList.remove('active');
        });

        // Close on outside click
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });

        // Tab Switching
        tabLogin.addEventListener('click', () => {
            tabLogin.classList.add('active');
            tabRegister.classList.remove('active');
            formLogin.classList.add('active');
            formRegister.classList.remove('active');
        });

        tabRegister.addEventListener('click', () => {
            tabRegister.classList.add('active');
            tabLogin.classList.remove('active');
            formRegister.classList.add('active');
            formLogin.classList.remove('active');
        });

        // My Account Modal Logic
        const accountModal = document.getElementById('account-modal');
        const navAccountBtn = document.getElementById('nav-account-btn');
        const closeAccountModal = document.getElementById('close-account-modal');
        
        const tabProfile = document.getElementById('tab-profile');
        const tabSecurity = document.getElementById('tab-security');
        const formProfile = document.getElementById('form-profile');
        const formSecurity = document.getElementById('form-security');

        if(navAccountBtn) {
            navAccountBtn.addEventListener('click', () => {
                accountModal.classList.add('active');
            });
        }

        if(closeAccountModal) {
            closeAccountModal.addEventListener('click', () => {
                accountModal.classList.remove('active');
            });
        }

        window.addEventListener('click', (e) => {
            if (e.target === accountModal) {
                accountModal.classList.remove('active');
            }
        });

        if(tabProfile) {
            tabProfile.addEventListener('click', () => {
                tabProfile.classList.add('active');
                tabSecurity.classList.remove('active');
                formProfile.classList.add('active');
                formSecurity.classList.remove('active');
            });
        }

        if(tabSecurity) {
            tabSecurity.addEventListener('click', () => {
                tabSecurity.classList.add('active');
                tabProfile.classList.remove('active');
                formSecurity.classList.add('active');
                formProfile.classList.remove('active');
            });
        }

        <?php if ($message != ""): ?>
            // Show modal automatically on message
            <?php if ($lastAction == 'register' || $lastAction == 'login'): ?>
                modal.classList.add('active');
                <?php if ($lastAction == 'register'): ?>
                    tabRegister.click();
                <?php endif; ?>
            <?php elseif ($lastAction == 'update_profile' || $lastAction == 'update_password'): ?>
                accountModal.classList.add('active');
                <?php if ($lastAction == 'update_password'): ?>
                    tabSecurity.click();
                <?php endif; ?>
            <?php endif; ?>

            // Auto-close success message after 3 seconds
            <?php if ($messageType == 'success'): ?>
                setTimeout(() => {
                    modal.classList.remove('active');
                    accountModal.classList.remove('active');
                }, 3000);
            <?php endif; ?>
        <?php endif; ?>
    </script>
</body>
</html>
