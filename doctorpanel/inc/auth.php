<?php
require_once __DIR__ . '/../../admin/inc/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function login_doctor($email, $password)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM doctors WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $doctor = $stmt->fetch();
    if ($doctor && password_verify($password, $doctor['password'] ?? '')) {
        // Set doctor session
        $_SESSION['doctor'] = [
            'id' => $doctor['id'],
            'email' => $doctor['email'],
            'name' => $doctor['name'],
            'specialty' => $doctor['specialty']
        ];
        return true;
    }
    return false;
}

function is_doctor_logged_in()
{
    return !empty($_SESSION['doctor']);
}

function require_doctor_login()
{
    if (!is_doctor_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function current_doctor()
{
    return $_SESSION['doctor'] ?? null;
}

function logout_doctor()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
}

function get_doctor_data($doctor_id)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM doctors WHERE id = ? LIMIT 1');
    $stmt->execute([$doctor_id]);
    return $stmt->fetch();
}
?>
