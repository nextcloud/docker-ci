<?php

$logPath = '/var/log/cronie/';
$triggeredName =	file_exists('/var/log/cronie/trigger') ? trim(file_get_contents('/var/log/cronie/trigger')) : false;

if (gethostname() !== 'transifex-sync') {
	$logPath = __DIR__ .  '/log/';
	$triggeredName =	file_exists(__DIR__. '/trigger') ? trim(file_get_contents(__DIR__. '/trigger')) : false;
}

class ResultInfo {
	/** @var string */
	public $name;
	/** @var string */
	public $arguments;
	/** @var DateTime */
	public $startDate;
	/** @var DateTime */
	public $endDate;
	/** @var string */
	public $errorMessage = '';

	public function getJson(): string {
		$data = [
			'name' => $this->name,
			'arguments' => $this->arguments,
			'start' => $this->startDate->format(\DateTime::ISO8601),
			'end' => $this->endDate->format(\DateTime::ISO8601),
			'error' => $this->errorMessage
		];
		return json_encode($data);
	}

	public function readJson(string $json): bool {
	    $data = json_decode($json, true);

	    if (is_null($data)) {
	        return false;
        }

        if (!isset($data['name']) || !isset($data['arguments']) || !isset($data['start']) || !isset($data['end']) || !isset($data['error'])) {
	        return false;
        }

		$this->name = $data['name'];
		$this->arguments = $data['arguments'];
		$this->startDate = new DateTime($data['start']);
		$this->endDate = new DateTime($data['end']);
        $this->errorMessage = $data['error'];

        return true;
    }
}

$runs = [];

$files = glob($logPath . '*.json');

sort($files);

foreach ($files as $file) {
    $logname = str_replace('json', 'log', $file);
    if (!file_exists($logname)) {
        continue;
    }

    $logLines = explode(PHP_EOL, file_get_contents($logname));
    $json = file_get_contents($file);

    $result = new ResultInfo();
    if (!$result->readJson($json)) {
        continue;
    }

    $diff = $result->startDate->diff($result->endDate);
    $format = '%ss';
    if ($diff->i > 0) {
		$format = '%im%ss';
	}

	$info = [
        'logLines' => str_replace(['"', '\''], ['\"', '\\\''], htmlentities(implode('\n', array_slice($logLines, max(sizeof($logLines) - 20, 0))))),
        'status' => $result->errorMessage === '' ? 'success' : 'error',
        'duration' => $diff->format($format),
        'start' => $result->startDate->format('Y-m-d H:i:s'),
    ];

	$id = $result->name . ' ' . $result->arguments;
    if (!isset($runs[$id])) {
		$runs[$id] = [];
        for ($i = 0; $i < 5; $i++) {
			$runs[$id][] = [
                'logLines' => '',
                'status' => 'unknown',
                'duration' => false,
                'start' => '',
            ];
        }
	}
	array_unshift($runs[$id], $info);
}

?>

<html>
<head>
	<meta charset="utf-8">

	<title>Cronie</title>
	<link rel="stylesheet" href="static/vendor/pure/pure-min.css">
	<link rel="stylesheet" href="static/style.css">

</head>
<body>

<h1>Scheduled tasks at Nextcloud</h1>

<div class="pure-g">
	<div class="pure-u-1-1">
		<table class="overview pure-table pure-table-horizontal" id="tasks">
			<thead>
			<tr>
				<th>Name</th>
				<th colspan="5" class="status">Status</th>
			</tr>
			</thead>
            <?php foreach ($runs as $name => $list) { ?>
                <tr>
                    <td><?php
                    echo '<a href="/trigger.php?j=' . trim($name) . '">' . trim(str_replace(' nextcloud ', ' ', $name)) . '</a>';
                    if ($triggeredName === trim($name)) {
                        echo ' (triggered)';
                    }
                    ?></td>
					<?php
                    $i = 0;
					foreach ($list as $element) {
					        if($i >= 5) { continue; } $i++;
					    ?>

                        <td class="status <?php echo $element['status']; ?>" title="<?php echo $element['start']; ?>" onmouseover="showDetails('<?php echo $element['logLines']; ?>')"
                            onclick="showDetails('<?php echo $element['logLines']; ?>')" onmouseout="hideDetails()">
                            <img src="static/img/<?php echo $element['status']; ?>.svg">
							<?php if($element['duration'] !== '') { ?>
                                <span class="time"><?php echo $element['duration']; ?></span>
                            <?php } ?>
                        </td>
                        <?php
					}
					?>
                </tr>
				<?php
			}
            ?>
		</table>
	</div>
</div>
<div class="pure-g" style="width: 100%; margin-bottom: 250px;">
	<div class="pure-u-1-1">
		<p class="footer">
			Last updated: <?php echo((new DateTime())->format('Y-m-d H:i:s'));?>
		</p>
	</div>
</div>
<div class="pure-g details-bar hidden">
	<div class="pure-u-1-1">
      <pre id="details">
      </pre>
	</div>
</div>
<script>
    var detailsNode = document.getElementById('details');
    function showDetails(logs) {
        console.log('show', logs);
        detailsNode.innerHTML = logs;
        detailsNode.parentNode.parentNode.classList.remove('hidden');
    }
    function hideDetails() {
        console.log('hide');
        detailsNode.parentNode.parentNode.classList.add('hidden');
    }
</script>
</body>
</html
