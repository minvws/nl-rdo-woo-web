import {Chart} from 'chart.js/auto'
import 'chartjs-adapter-moment';


const chart_init = (el) => {
    var json_el = document.getElementById('chartdata');
    if (!json_el) {
        return;
    }

    var sectionColors = [
        'rgba( 75, 192, 192, 1)',
        'rgba(255,  99, 132, 1)',
        'rgba(255, 205,  86, 1)',
        'rgba(255, 160, 122, 1)',
        'rgba(128, 0, 128, 1)',
        'rgba(0, 128, 128, 1)',
        'rgba(255, 69, 0, 1)',
        'rgba(70, 130, 180, 1)',
        'rgba(0, 0, 128, 1)',
    ];

    var data = JSON.parse(document.getElementById('chartdata').textContent);
    var groupedData = data.reduce(function (acc, entry) {
        if (!acc[entry.section]) {
            acc[entry.section] = {created_at: [], duration: []};
        }
        acc[entry.section].created_at.push(entry.created_at);
        acc[entry.section].duration.push(entry.duration);
        return acc;
    }, {});

    var datasets = [];
    var colorIndex = 0;
    for (var section in groupedData) {
        var color = sectionColors[colorIndex % sectionColors.length];
        var backgroundColor = color.replace('1)', '0.2)');

        if (groupedData.hasOwnProperty(section)) {
            datasets.push({
                label: section,
                data: groupedData[section].duration.map(function (duration, index) {
                    return {
                        x: new Date(groupedData[section].created_at[index]).getTime(),
                        y: duration
                    };
                }),
                borderColor: color,
                backgroundColor: color,
                pointRadius: 5,
                pointHoverRadius: 8,
                fill: false,
            });
        }

        colorIndex++;
    }

    var myChart = new Chart(el, {
        type: 'bar',
        data: {
            datasets: datasets,
        },
        options: {
            barThickness: 5,
            // Configure chart options here
            scales: {
                x: {
                    type: 'time',
                    position: 'bottom',
                    time: {
                        displayFormats: {
                            'millisecond': 'MMM DD, YYYY HH:mm:ss',
                            'second': 'MMM DD, YYYY HH:mm:ss',
                            'minute': 'MMM DD, YYYY HH:mm',
                            'hour': 'MMM DD, YYYY HH:00',
                        },
                    },
                    title: {
                        display: true,
                        text: 'Time',
                    },
                },
                y: {
                    type: 'linear',
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Duration (ms)',
                    },
                },
            },
        },
    });
}


document.addEventListener('DOMContentLoaded', function () {

    var el = document.getElementById("workerstats");
    if (el) {
        chart_init(el);
    }
});
