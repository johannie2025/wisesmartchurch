<?php
require __DIR__ . '/_common.php';
[$s] = check_license(input(), true);
out(['ok' => true, 'plan' => $s['plan'], 'expires' => strtotime($s['expires_at'] . ' UTC'), 'user' => $s['uname']]);
