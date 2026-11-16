const ctx = document.getElementById('chartPendapatan');

if (ctx) {
    new Chart(ctx, {
        data: {
            labels: labelsPendapatan,
            datasets: [
                {
                    type: 'bar',
                    label: 'Pendapatan Bulanan',
                    data: dataPendapatan,
                    backgroundColor: '#4CAF50',
                    borderRadius: 8
                },
                {
                    type: 'line',
                    label: 'Tren Pendapatan',
                    data: dataPendapatan,
                    borderColor: '#FF9800',
                    backgroundColor: '#FF9800',
                    tension: 0.4,
                    fill: false,
                    pointRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return context.dataset.label + ': Rp ' +
                                context.raw.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
}
