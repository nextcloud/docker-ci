<?php

$logPath = '/var/log/cronie/';

if (gethostname() !== 'transifex-sync') {
	$logPath = __DIR__ .  '/../log/';
}