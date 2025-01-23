// assets/js/reservation.js (continuation)
                        <button class="btn btn-sm btn-danger" onclick="deleteReservation(${reservation.id})">
                            Usuń
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function loadFacilities() {
        $.ajax({
            url: SITE_URL + '/api/facility/read.php',
            type: 'GET',
            success: function(response) {
                if (response.records) {
                    const select = $('#facilitySelect');
                    select.empty();
                    select.append('<option value="">Wybierz obiekt</option>');
                    response.records.forEach(function(facility) {
                        select.append(`<option value="${facility.id}">${facility.name}</option>`);
                    });
                }
            },
            error: function() {
                showAlert('danger', 'Nie udało się załadować listy obiektów.');
            }
        });
    }

    function formatDateTime(date) {
        return date.toLocaleString('pl-PL', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function translateStatus(status) {
        const translations = {
            'pending': 'Oczekująca',
            'confirmed': 'Potwierdzona',
            'cancelled': 'Anulowana'
        };
        return translations[status] || status;
    }

    window.editReservation = function(id) {
        $.ajax({
            url: SITE_URL + `/api/reservation/read_one.php?id=${id}`,
            type: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    populateEditForm(response.reservation);
                    $('#reservationModal').modal('show');
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Nie udało się załadować danych rezerwacji.');
            }
        });
    };

    window.deleteReservation = function(id) {
        if (confirm('Czy na pewno chcesz usunąć tę rezerwację?')) {
            $.ajax({
                url: SITE_URL + '/api/reservation/delete.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id: id }),
                success: function(response) {
                    if (response.status === 'success') {
                        showAlert('success', 'Rezerwacja została usunięta.');
                        loadReservations();
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function() {
                    showAlert('danger', 'Nie udało się usunąć rezerwacji.');
                }
            });
        }
    };

    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('#alertContainer').html(alertHtml);
        
        // Auto-hide alert after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
});

// views/schedule/list.php
<?php
require_once '../../config/config.php';
require_once '../../utils/Auth.php';

Auth::requireLogin();
include_once '../templates/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Grafik Pracy</h2>
        <?php if ($_SESSION['user_role'] === 'manager'): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleModal">
            Dodaj zmianę
        </button>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">Zakres dat</span>
                        <input type="date" class="form-control" id="startDate">
                        <input type="date" class="form-control" id="endDate">
                        <button class="btn btn-outline-primary" id="loadSchedule">Pokaż</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped" id="scheduleTable">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Godzina rozpoczęcia</th>
                            <th>Godzina zakończenia</th>
                            <th>Obiekt</th>
                            <?php if ($_SESSION['user_role'] === 'manager'): ?>
                            <th>Pracownik</th>
                            <th>Akcje</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($_SESSION['user_role'] === 'manager'): ?>
<!-- Modal for schedule entry -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dodaj zmianę</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="scheduleForm">
                    <div class="mb-3">
                        <label class="form-label">Pracownik</label>
                        <select class="form-control" id="employeeSelect" required>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Obiekt</label>
                        <select class="form-control" id="facilitySelect" required>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data</label>
                        <input type="date" class="form-control" id="workDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Godzina rozpoczęcia</label>
                        <input type="time" class="form-control" id="startTime" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Godzina zakończenia</label>
                        <input type="time" class="form-control" id="endTime" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                <button type="button" class="btn btn-primary" id="saveSchedule">Zapisz</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include_once '../templates/footer.php'; ?>

<script src="<?php echo SITE_URL; ?>/assets/js/schedule.js"></script>

// assets/js/schedule.js
$(document).ready(function() {
    // Set default date range to current week
    const today = new Date();
    const startOfWeek = new Date(today);
    startOfWeek.setDate(today.getDate() - today.getDay());
    const endOfWeek = new Date(today);
    endOfWeek.setDate(startOfWeek.getDate() + 6);

    $('#startDate').val(startOfWeek.toISOString().split('T')[0]);
    $('#endDate').val(endOfWeek.toISOString().split('T')[0]);

    loadSchedule();
    if ($('#employeeSelect').length) {
        loadEmployees();
    }
    if ($('#facilitySelect').length) {
        loadFacilities();
    }

    $('#loadSchedule').click(loadSchedule);

    $('#saveSchedule').click(function() {
        const scheduleData = {
            employee_id: $('#employeeSelect').val(),
            facility_id: $('#facilitySelect').val(),
            work_date: $('#workDate').val(),
            start_time: $('#startTime').val(),
            end_time: $('#endTime').val()
        };

        if (!validateScheduleData(scheduleData)) {
            return;
        }

        $.ajax({
            url: SITE_URL + '/api/schedule/create.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(scheduleData),
            success: function(response) {
                if (response.status === 'success') {
                    $('#scheduleModal').modal('hide');
                    showAlert('success', 'Zmiana została dodana do grafiku.');
                    loadSchedule();
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Wystąpił błąd podczas dodawania zmiany.');
            }
        });
    });

    function loadSchedule() {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();

        $.ajax({
            url: SITE_URL + `/api/schedule/read.php?start_date=${startDate}&end_date=${endDate}`,
            type: 'GET',
            success: function(response) {
                if (response.records) {
                    displaySchedule(response.records);
                }
            },
            error: function() {
                showAlert('danger', 'Nie udało się załadować grafiku.');
            }
        });
    }

    function validateScheduleData(data) {
        for (let key in data) {
            if (!data[key]) {
                showAlert('danger', 'Wypełnij wszystkie wymagane pola.');
                return false;
            }
        }
        return true;
    }

    function displaySchedule(schedules) {
        const tbody = $('#scheduleTable tbody');
        tbody.empty();

        schedules.forEach(function(schedule) {
            const row = `
                <tr>
                    <td>${formatDate(schedule.work_date)}</td>
                    <td>${schedule.start_time}</td>
                    <td>${schedule.end_time}</td>
                    <td>${schedule.facility_name}</td>
                    ${$('#employeeSelect').length ? `<td>${schedule.employee_name}</td>` : ''}
                    ${$('#employeeSelect').length ? `
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editSchedule(${schedule.id})">
                            Edytuj
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteSchedule(${schedule.id})">
                            Usuń
                        </button>
                    </td>
                    ` : ''}
                </tr>
            `;
            tbody.append(row);
        });
    }

    function formatDate(date) {
        return new Date(date).toLocaleDateString('pl-PL');
    }

    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('#alertContainer').html(alertHtml);
        
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
});
