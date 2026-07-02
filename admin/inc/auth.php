<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function login_user($email, $password)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        // Set minimal session
        $_SESSION['admin'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'] ?? 'Admin'
        ];
        return true;
    }
    return false;
}

function is_logged_in()
{
    return !empty($_SESSION['admin']);
}

function require_login()
{
    if (!is_logged_in()) {
        // redirect to login page
        header('Location: login.php');
        exit;
    }
}

function current_user()
{
    return $_SESSION['admin'] ?? null;
}

function logout_user()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

?>
