document.addEventListener('DOMContentLoaded', function () {

    // 1. Initialize Chart.js
    const ctx = document.getElementById('salaryChart');
    if (ctx) {
        // Collect data from the hidden table or data attributes if we had them. 
        // For now, let's parse the table to get real data for the chart.
        const grades = {};
        document.querySelectorAll('tbody tr').forEach(row => {
            const grade = row.cells[2].innerText;
            if (grade) {
                grades[grade] = (grades[grade] || 0) + 1;
            }
        });

        const chartData = {
            labels: Object.keys(grades),
            datasets: [{
                label: 'Effectif par Grade',
                data: Object.values(grades),
                backgroundColor: [
                    '#059669', '#10b981', '#34d399', '#6ee7b7', '#a7f3d0'
                ],
                borderWidth: 0,
                borderRadius: 4
            }]
        };

        new Chart(ctx, {
            type: 'doughnut',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            font: { family: "'Inter', sans-serif" }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }

    // 2. Search & Filter Functionality
    const searchInput = document.getElementById('searchInput');
    const gradeFilter = document.getElementById('gradeFilter');

    function filterTable() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const gradeTerm = gradeFilter ? gradeFilter.value : '';
        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const nameText = row.cells[1].innerText.toLowerCase(); // Name column
            const gradeText = row.cells[2].innerText; // Grade column

            const matchesSearch = nameText.includes(searchTerm);
            const matchesGrade = gradeTerm === '' || gradeText.includes(gradeTerm);

            row.style.display = (matchesSearch && matchesGrade) ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('keyup', filterTable);
    if (gradeFilter) gradeFilter.addEventListener('change', filterTable);

    // 3. Modal Functionality (Details & Add)
    const modal = document.getElementById('detailModal');
    const addModal = document.getElementById('addModal');

    // Close Details Modal
    const closeBtn = document.querySelector('.close');
    if (closeBtn) closeBtn.onclick = () => modal.style.display = "none";

    // Close Add Modal
    const closeAddBtn = document.querySelector('.close-add');
    if (closeAddBtn) closeAddBtn.onclick = () => addModal.style.display = "none";

    // Close on outside click
    window.onclick = (event) => {
        if (event.target == modal) modal.style.display = "none";
        if (event.target == addModal) addModal.style.display = "none";
    }

    // Open Add Modal Global Function
    window.openAddModal = function () {
        if (addModal) addModal.style.display = "block";
    }

    // Handle Add Employee Form
    const addForm = document.getElementById('addEmployeeForm');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(addForm);

            fetch('api_employee.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Employé ajouté avec succès !');
                        location.reload(); // Reload to show new employee
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(err => console.error('Error:', err));
        });
    }

    // Bind click events to "view" buttons
    window.openDetails = function (matricule) {
        fetch(`get_details.php?matricule=${matricule}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                // Populate Modal
                document.getElementById('modalTitle').innerText = `Détails: ${data.nom}`;

                let content = `
                    <div class="detail-row"><span>Matricule</span> <span>${data.matricule}</span></div>
                    <div class="detail-row"><span>Grade</span> <span>${data.grade}</span></div>
                    <div class="detail-row"><span>Salaire de Base</span> <span>${formatMoney(data.salaireBase)}</span></div>
                    <br>
                    <strong>Indemnités:</strong>
                `;

                if (data.indemnites.length > 0) {
                    data.indemnites.forEach(ind => {
                        content += `<div class="detail-row" style="padding-left:1rem; color:#64748b;">
                            <span>${ind.libelle}</span> <span>+ ${formatMoney(ind.montant)}</span>
                        </div>`;
                    });
                } else {
                    content += `<div class="detail-row" style="padding-left:1rem; color:#64748b;">Aucune indemnité</div>`;
                }

                content += `
                    <div class="detail-row"><span>Total Indemnités</span> <span>${formatMoney(data.totalIndemnites)}</span></div>
                    <div class="detail-row"><span>Impôt (5%)</span> <span style="color:#ef4444;">- ${formatMoney(data.impot)}</span></div>
                    <div class="detail-row total"><span>Salaire Net</span> <span>${formatMoney(data.salaireNet)}</span></div>
                `;

                document.getElementById('modalBody').innerHTML = content;
                modal.style.display = "block";
            })
            .catch(err => console.error('Error:', err));
    };

    // Delete Employee Function
    window.deleteEmployee = function (matricule) {
        if (confirm(`Êtes-vous sûr de vouloir supprimer l'employé ${matricule} ?`)) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('matricule', matricule);

            fetch('api_employee.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(err => console.error('Error:', err));
        }
    };

    function formatMoney(amount) {
        return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
    }
});
