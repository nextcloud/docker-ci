<?php

$job = '';
if(isset($_GET['j'])) {
	$job = $_GET['j'];
}

if (preg_match('/^[a-zA-Z0-9 _-]+$/', $job) !== 1) {
	print('Invalid job id');
	die();
}

if (gethostname() === 'transifex-sync') {
	file_put_contents('/var/log/cronie/trigger', $job);
} else {
	file_put_contents(__DIR__ . '/trigger', $job);
}

header('Location: /');
