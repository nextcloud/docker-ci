<?php

$filename = __DIR__ . '/stats';

$lines = explode(PHP_EOL, file_get_contents($filename));

$elements = array_map(function($line) {
    return explode(' ', $line);
}, $lines);

?>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <title><?php echo $elements[count($elements)-1][1]; ?> untriaged issues</title>
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js"></script>
        <style>
            body {
                font-family: sans-serif;
            }
            .page {
                max-width: 800px;
                margin: 0 auto;
            }
        </style>
    </head>
    <body>
        <div class="page">
            <h1><?php echo $elements[count($elements)-1][1]; ?> untriaged issues</h1>
            <p>The total number of untriaged issues needs to be close to 0. <a href="https://github.com/nextcloud/server/issues?utf8=✓&amp;q=is%3Aissue%20is%3Aopen%20-label%3Aenhancement%20-label%3Aspec%20-label%3Asecurity%20-label%3Abug%20-label%3Apapercut%20-label%3Aoverview%20%20-label%3A%22technical%20debt%22%20">Triage issues on Github.</a></p>
            <canvas id="myChart"></canvas>
        </div>
        <script>
            var ctx = document.getElementById('myChart').getContext('2d');
            var chart = new Chart(ctx, {
                // The type of chart we want to create
                type: 'line',

                // The data for our dataset
                data: {
                    datasets: [{
                        label: "Untriaged issues",
                        fill: false,
                        borderColor: 'rgb(255, 99, 132)',
                        data: [
                            <?php
                            foreach($elements as $element) {
                                vprintf("{x: new Date('%s'), y: %s},\n", $element);
                            }
                            ?>
                        ],
                    }]
                },

                // Configuration options go here
                options: {
                    scales: {
                        xAxes: [{
                            type: 'time',
                            time: {
                                unit: 'day'
                            },
                            ticks: {
                                callback: function(dataLabel, index) {
                                    // Hide the label of every 7th dataset. stats start at august 30 -> move it to only show mondays
                                    return (index + 5) % 7 === 0 ? dataLabel : '';
                                }
                            }
                        }],
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                            },
                        }],
                    }
                }
            });
        </script>
    </body>
</html>
