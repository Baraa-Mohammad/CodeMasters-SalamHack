<?php
require_once __DIR__ . '/includes/auth.php';

$user = require_login();
redirect(dashboard_for_role($user['role']));
