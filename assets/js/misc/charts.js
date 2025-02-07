import { Chart } from 'chart.js/auto';
import 'chartjs-adapter-moment';

const chartInit = (element) => {
  const jsonElement = document.getElementById('chartdata');
  if (!jsonElement) {
    return null;
  }

  const sectionColors = [
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

  const data = JSON.parse(document.getElementById('chartdata').textContent);
  const groupedData = data.reduce((acc, entry) => {
    if (!acc[entry.section]) {
      // eslint-disable-next-line no-param-reassign
      acc[entry.section] = { created_at: [], duration: [] };
    }
    acc[entry.section].created_at.push(entry.created_at);
    acc[entry.section].duration.push(entry.duration);
    return acc;
  }, {});

  const datasets = [];
  Array.from(Object.keys(groupedData)).forEach((section, index) => {
    const color = sectionColors[index % sectionColors.length];

    if (groupedData[section]) {
      datasets.push({
        label: section,
        data: groupedData[section].duration.map((duration, dataIndex) => ({
          x: new Date(groupedData[section].created_at[dataIndex]).getTime(),
          y: duration,
        })),
        borderColor: color,
        backgroundColor: color,
        pointRadius: 5,
        pointHoverRadius: 8,
        fill: false,
      });
    }
  });

  return new Chart(element, {
    type: 'bar',
    data: {
      datasets,
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
              millisecond: 'MMM DD, YYYY HH:mm:ss',
              second: 'MMM DD, YYYY HH:mm:ss',
              minute: 'MMM DD, YYYY HH:mm',
              hour: 'MMM DD, YYYY HH:00',
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
};

document.addEventListener('DOMContentLoaded', () => {
  const element = document.getElementById('js-workerstats');
  if (element) {
    chartInit(element);
  }
});
