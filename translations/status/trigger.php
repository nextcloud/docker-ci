<?php


if (gethostname() === 'transifex-sync') {
	file_put_contents('/var/log/cronie/trigger', 'android');
} else {
	file_put_contents(__DIR__ . '/trigger', 'android');
}

header('Location: /');
