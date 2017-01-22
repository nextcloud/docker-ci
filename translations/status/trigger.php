<?php

file_put_contents('/var/log/cronie/trigger', 'android');

header('Location: /index.php');
