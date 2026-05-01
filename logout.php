<?php
require_once __DIR__ . '/includes/auth.php';

session_destroy();
session_start();
flash('success', 'تم تسجيل الخروج بنجاح');
redirect('index.php');
