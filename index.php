<?php
require_once __DIR__ . '/user/config.php';
require_once __DIR__ . '/user/helpers.php';

if (is_logged_in()) {
    redirect(url(is_admin() ? 'admin/dashboard.php' : 'user/dashboard.php'));
} else {
    redirect(url('user/login.php'));
}