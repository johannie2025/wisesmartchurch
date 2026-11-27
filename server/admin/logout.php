<?php
require_once __DIR__ . '/../lib.php';
admin_start(); $_SESSION = []; session_destroy(); header('Location: login.php');
