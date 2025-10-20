<?php
class Session {
    // Start session
    public static function start() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Set session variable
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    // Get session variable
    public static function get($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    // Check if session variable exists
    public static function exists($key) {
        return isset($_SESSION[$key]);
    }

    // Remove session variable
    public static function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    // Destroy session
    public static function destroy() {
        session_destroy();
    }

    // Set flash message
    public static function setFlash($key, $message) {
        $_SESSION['flash'][$key] = $message;
    }

    // Get flash message
    public static function getFlash($key) {
        $message = null;
        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
        }
        return $message;
    }

    // Check if user is logged in
    public static function isLoggedIn() {
        return self::exists('user_id');
    }

    // Check if user is admin
    public static function isAdmin() {
        return self::exists('is_admin') && self::get('is_admin') == true;
    }

    // Set user session
    public static function setUser($user_id, $username, $is_admin = false) {
        self::set('user_id', $user_id);
        self::set('username', $username);
        self::set('is_admin', $is_admin);
    }

    // Get user ID
    public static function getUserId() {
        return self::get('user_id');
    }

    // Get username
    public static function getUsername() {
        return self::get('username');
    }
    
    // Check if flash message exists
    public static function hasFlash($key) {
        return isset($_SESSION['flash'][$key]);
    }
}
?>