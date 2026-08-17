<?php
/**
 * SFI Queuing System - Root Index
 * Redirects to login or dashboard based on session.
 */

require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
    redirect('admin/dashboard.php');
} else {
    redirect('login/');
}
