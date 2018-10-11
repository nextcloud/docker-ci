<?php

$lines = explode(PHP_EOL, file_get_contents(__DIR__ . '/stats'));

$elements = array_map(function($line) {
    return explode(' ', $line);
}, $lines);

$lines = explode(PHP_EOL, file_get_contents(__DIR__ . '/stats-android'));

$elementsAndroid = array_filter(array_map(function($line) {
    if (strlen($line) < 5) {
        return null;
    }
    list($date, $enhancements, $approvedBugs, $nonApprovedBugs, $untriaged) = explode(' ', $line);
    return [
        'date' => $date,
        'enhancements' => $enhancements,
        'approvedBugs' => $approvedBugs,
        'nonApprovedBugs' => $nonApprovedBugs,
        'untriaged' => $untriaged,
    ];
}, $lines));

$lines = explode(PHP_EOL, file_get_contents(__DIR__ . '/stats-android-pr'));

$elementsAndroidPR = array_filter(array_map(function($line) {
    if (strlen($line) < 4) {
        return null;
    }
    list($date, $oneWeekOld, $twoWeeksOld, $olderThan2Weeks) = explode(' ', $line);
    return [
        'date' => $date,
        'oneWeekOld' => $oneWeekOld,
        'twoWeeksOld' => $twoWeeksOld,
        'olderThan2Weeks' => $olderThan2Weeks,
    ];
}, $lines));

?>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <title><?php echo $elements[count($elements)-1][1]; ?> untriaged server issues</title>
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
            <h1><?php echo $elements[count($elements)-1][1]; ?> untriaged server issues</h1>
            <p>The total number of untriaged issues needs to be close to 0. <a href="https://github.com/nextcloud/server/issues?utf8=✓&amp;q=is%3Aissue%20is%3Aopen%20-label%3Aenhancement%20-label%3Aspec%20-label%3Asecurity%20-label%3Abug%20-label%3Apapercut%20-label%3Aoverview%20%20-label%3A%22technical%20debt%22%20">Triage issues on Github.</a></p>
            <canvas id="myChart"></canvas>
            <h1>Android issue overview</h1>
            <p>The total number of untriaged issues needs to be close to 0. <a href="https://github.com/nextcloud/android/issues?utf8=✓&amp;q=is%3Aissue+is%3Aopen+-label%3Abug+-label%3Aenhancement+-label%3Aoverview">Triage issues on Github.</a></br>
            The total number of approved bugs should to be 0. <a href="https://github.com/nextcloud/android/issues?q=is%3Aissue+is%3Aopen+label%3Abug+sort%3Aupdated-desc+label%3Aapproved">Fix bugs on Github.</a></p>
            <canvas id="myChartAndroid"></canvas>
            <h1>Android PR overview</h1>
            <p>The number of open PRs should be close to 0. <a href="https://github.com/nextcloud/android/pulls">Review PRs on Github.</a></br>
            <canvas id="myChartAndroidPR"></canvas>
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
                                    // Hide the label of every 7th dataset. stats start at august 30 -> move it to only show mondays. "null" hides the grid lines - "" would only hide the label
                                    return (index + 2) % 7 === 0 ? dataLabel : null;
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

            var ctxAndroid = document.getElementById('myChartAndroid').getContext('2d');
            var chartAndroid = new Chart(ctxAndroid, {
                // The type of chart we want to create
                type: 'bar',

                // The data for our dataset
                data: {
                    labels: [
                    <?php
                    foreach($elementsAndroid as $element) {
                        echo ("\"" . $element['date'] . "\",");
                    }
                    ?>
                    ],
                    datasets: [{
                        label: "Enhancements",
                        backgroundColor: 'rgb(14, 138, 22)',
                        data: [
                            <?php
                            foreach($elementsAndroid as $element) {
                                echo ($element['enhancements'] . ",");
                            }
                            ?>
                        ],
                    },
                    {
                        label: "Approved bugs",
                        backgroundColor: 'rgb(0, 82, 204)',
                        data: [
                            <?php
                            foreach($elementsAndroid as $element) {
                                echo ($element['approvedBugs'] . ",");
                            }
                            ?>
                        ],
                    },
                    {
                        label: "Non-approved bugs",
                        backgroundColor: 'rgb(238, 7, 1)',
                        data: [
                            <?php
                            foreach($elementsAndroid as $element) {
                                echo ($element['nonApprovedBugs'] . ",");
                            }
                            ?>
                        ],
                    },
                    {
                        label: "Untriaged issues",
                        backgroundColor: 'rgb(154, 154, 154)',
                        data: [
                            <?php
                            foreach($elementsAndroid as $element) {
                                echo ($element['untriaged'] . ",");
                            }
                            ?>
                        ],
                    }]
                },

                // Configuration options go here
                options: {
                    scales: {
                        xAxes: [{
                            stacked: true,
                            ticks: {
                                callback: function(dataLabel, index) {
                                    // Hide the label of every 7th dataset. stats start at august 30 -> move it to only show mondays. "null" hides the grid lines - "" would only hide the label
                                    return (index + 2) % 7 === 0 ? dataLabel : null;
                                }
                            }
                        }],
                        yAxes: [{
                            stacked: true,
                        }],
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false
                    },
                    responsive: true,
                }
            });
            
            var ctxAndroidPR = document.getElementById('myChartAndroidPR').getContext('2d');
            var chartAndroidPR = new Chart(ctxAndroidPR, {
                // The type of chart we want to create
                type: 'bar',

                // The data for our dataset
                data: {
                    labels: [
                    <?php
                    foreach($elementsAndroidPR as $element) {
                        echo ("\"" . $element['date'] . "\",");
                    }
                    ?>
                    ],
                    datasets: [
                    {
                        label: "Older than two weeks",
                        backgroundColor: 'rgb(238, 7, 1)',
                        data: [
                            <?php
                            foreach($elementsAndroidPR as $element) {
                                echo ($element['olderThan2Weeks'] . ",");
                            }
                            ?>
                        ],
                    },
                    {
                        label: "Last week",
                        backgroundColor: 'rgb(0, 82, 204)',
                        data: [
                            <?php
                            foreach($elementsAndroidPR as $element) {
                                echo ($element['twoWeeksOld'] . ",");
                            }
                            ?>
                        ],
                    },
                    {
                        label: "Within this week",
                        backgroundColor: 'rgb(14, 138, 22)',
                        data: [
                            <?php
                            foreach($elementsAndroidPR as $element) {
                                echo ($element['oneWeekOld'] . ",");
                            }
                            ?>
                        ],
                    }]
                },

                // Configuration options go here
                options: {
                    scales: {
                        xAxes: [{
                            stacked: true,
                            ticks: {
                                callback: function(dataLabel, index) {
                                    // Hide the label of every 7th dataset. stats start at august 30 -> move it to only show mondays. "null" hides the grid lines - "" would only hide the label
                                    return (index + 2) % 7 === 0 ? dataLabel : null;
                                }
                            }
                        }],
                        yAxes: [{
                            stacked: true,
                        }],
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false
                    },
                    responsive: true,
                }
            });
        </script>
    </body>
</html>
