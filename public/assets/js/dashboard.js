document.addEventListener("DOMContentLoaded", function(e) {
    // Show/hide sidebar
    const showNavbar = (toggleId, navId, bodyId, headerId) => {
        const toggle = document.getElementById(toggleId),
            nav = document.getElementById(navId),
            bodypd = document.getElementById(bodyId),
            headerpd = document.getElementById(headerId);

        if (toggle && nav && bodypd && headerpd) {
            toggle.addEventListener('click', () => {
                nav.classList.toggle('show');
                toggle.classList.toggle('bx-x');
                bodypd.classList.toggle('body-pd');
                headerpd.classList.toggle('body-pd');
            });
        }
    }

    showNavbar('header-toggle', 'nav-bar', 'body-pd', 'header');

    // Load dashboard data
    const loadDashboardData = () => {
        fetch('batch_operations.php?action=dashboard_stats')
            .then(response => response.json())
            .then(data => {
                // Update stats
                document.getElementById('totalBatches').textContent = data.total_batches;
                document.getElementById('processingCount').textContent = data.processing;
                document.getElementById('averageQuality').textContent = data.average_quality;
                document.getElementById('totalWeight').textContent = data.total_weight;

                // Update recent batches table
                const tableBody = document.getElementById('recentBatchesTable');
                tableBody.innerHTML = '';
                
                data.recent_batches.forEach(batch => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${batch.batch_number}</td>
                        <td>${batch.farm_name}</td>
                        <td>${batch.weight_kg}</td>
                        <td>${batch.quality_grade}</td>
                        <td><span class="badge bg-${getStatusColor(batch.processing_status)}">${batch.processing_status}</span></td>
                    `;
                    tableBody.appendChild(row);
                });

                // Update quality distribution chart
                updateQualityChart(data.quality_distribution);
            })
            .catch(error => {
                console.error('Error loading dashboard data:', error);
                alert('Error loading dashboard data. Please try again.');
            });
    };

    // Initialize quality chart
    let qualityChart = null;
    const updateQualityChart = (data) => {
        const ctx = document.getElementById('qualityChart').getContext('2d');
        
        if (qualityChart) {
            qualityChart.destroy();
        }

        qualityChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.quality_category),
                datasets: [{
                    data: data.map(item => item.count),
                    backgroundColor: [
                        '#4723D9',
                        '#36A2EB',
                        '#FFCE56',
                        '#FF6384'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    };

    // Helper function for status colors
    const getStatusColor = (status) => {
        switch (status.toLowerCase()) {
            case 'processing':
                return 'primary';
            case 'completed':
                return 'success';
            case 'pending':
                return 'warning';
            default:
                return 'secondary';
        }
    };

    // Form submission handlers
    window.submitBatch = () => {
        const form = document.getElementById('addBatchForm');
        if (form.checkValidity()) {
            const formData = {
                farm_id: document.getElementById('farmSelect').value,
                weight_kg: document.getElementById('batchWeight').value,
                quality_grade: document.getElementById('qualityGrade').value
            };

            fetch('api/batches.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Batch added successfully!');
                    $('#addBatchModal').modal('hide');
                    loadDashboardData();
                } else {
                    alert('Error adding batch: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding batch. Please try again.');
            });
        } else {
            form.reportValidity();
        }
    };

    window.submitFarm = () => {
        const form = document.getElementById('addFarmForm');
        if (form.checkValidity()) {
            const formData = {
                farm_name: document.getElementById('farmName').value,
                location: document.getElementById('farmLocation').value,
                contact: document.getElementById('farmContact').value
            };

            fetch('api/farms.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Farm added successfully!');
                    $('#addFarmModal').modal('hide');
                    loadDashboardData();
                } else {
                    alert('Error adding farm: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding farm. Please try again.');
            });
        } else {
            form.reportValidity();
        }
    };

    // Load farms for the select dropdown
    const loadFarms = () => {
        fetch('api/farms.php')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('farmSelect');
                select.innerHTML = '<option value="">Choose...</option>';
                data.farms.forEach(farm => {
                    const option = document.createElement('option');
                    option.value = farm.id;
                    option.textContent = farm.farm_name;
                    select.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading farms:', error);
            });
    };

    // Initial load
    loadDashboardData();
    loadFarms();

    // Refresh data every 30 seconds
    setInterval(loadDashboardData, 30000);
}); 