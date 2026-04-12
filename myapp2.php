<?php
session_start();

// DB Connection
$conn = new mysqli("localhost", "root", "", "test_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>PHP Login/Register</title>
</head>
<body>
<div style="margin:0px auto; width:900px;">

<a href="?reg">Register</a> | 
<a href="?log">Login</a> | 
<a href="?">Home</a>

<hr>

<?php

// REGISTER
if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (name, email, phone, password)
            VALUES ('$name', '$email', '$phone', '$password')";

    if ($conn->query($sql) === TRUE) {
        echo "Registered Successfully!<br>";
    } else {
        echo "Error: " . $conn->error;
    }
}

// LOGIN
elseif (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['user'] = $row['email'];
            echo "Login Successful!<br>";
        } else {
            echo "Wrong Password!<br>";
        }
    } else {
        echo "User not found!<br>";
    }
}
// SHOW REGISTER FORM
elseif (isset($_GET['reg'])) {
    echo '
    <h2>Register</h2>
    <form method="POST">
        Name: <input type="text" name="name" required><br><br>
        Email: <input type="email" name="email" required><br><br>
        Phone: <input type="text" name="phone" required><br><br>
        Password: <input type="password" name="password" required><br><br>
        <button type="submit" name="register">Register</button>
    </form>
    ';
}

// SHOW LOGIN FORM
elseif (isset($_GET['log'])) {
    echo '
    <h2>Login</h2>
    <form method="POST">
        Email: <input type="email" name="email" required><br><br>
        Password: <input type="password" name="password" required><br><br>
        <button type="submit" name="login">Login</button>
    </form>
    ';
}

// LOGOUT
elseif (isset($_GET['logout'])) {
    session_destroy();
    header("Location: myapp2.php");
}


// DEFAULT (HOME)
else {
    echo "<h2>Welcome Page</h2>";
}

// SHOW USER IF LOGGED IN
if (isset($_SESSION['user'])) {
    echo "<h3>Welcome, " . $_SESSION['user'] . "</h3>";
    echo '<a href="?logout=true">Logout</a>';
}
?>

</body>
</html>