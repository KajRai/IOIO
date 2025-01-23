// models/Maintenance.php
<?php
class Maintenance {
    private $conn;
    private $table_name = "maintenance_schedules";

    public $id;
    public $attraction_id;
    public $maintenance_date;
    public $start_time;
    public $end_time;
    public $type;
    public $status;
    public $created_by;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT m.*, a.name as attraction_name, f.name as facility_name,
                        CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                FROM " . $this->table_name . " m
                LEFT JOIN attractions a ON m.attraction_id = a.id
                LEFT JOIN facilities f ON a.facility_id = f.id
                LEFT JOIN users u ON m.created_by = u.id
                ORDER BY m.maintenance_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    attraction_id = :attraction_id,
                    maintenance_date = :maintenance_date,
                    start_time = :start_time,
                    end_time = :end_time,
                    type = :type,
                    status = :status,
                    created_by = :created_by";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->attraction_id = htmlspecialchars(strip_tags($this->attraction_id));
        $this->maintenance_date = htmlspecialchars(strip_tags($this->maintenance_date));
        $this->start_time = htmlspecialchars(strip_tags($this->start_time));
        $this->end_time = htmlspecialchars(strip_tags($this->end_time));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->created_by = htmlspecialchars(strip_tags($this->created_by));

        // Bind values
        $stmt->bindParam(":attraction_id", $this->attraction_id);
        $stmt->bindParam(":maintenance_date", $this->maintenance_date);
        $stmt->bindParam(":start_time", $this->start_time);
        $stmt->bindParam(":end_time", $this->end_time);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":created_by", $this->created_by);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    maintenance_date = :maintenance_date,
                    start_time = :start_time,
                    end_time = :end_time,
                    status = :status
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize and bind
        $this->maintenance_date = htmlspecialchars(strip_tags($this->maintenance_date));
        $this->start_time = htmlspecialchars(strip_tags($this->start_time));
        $this->end_time = htmlspecialchars(strip_tags($this->end_time));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":maintenance_date", $this->maintenance_date);
        $stmt->bindParam(":start_time", $this->start_time);
        $stmt->bindParam(":end_time", $this->end_time);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}

// views/maintenance/list.php
<?php
require_once '../../config/config.php';
require_once '../../utils/Auth.php';

Auth::requireLogin();
Auth::requireRole('maintenance');

include_once '../templates/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Harmonogram Konserwacji</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#maintenanceModal">
            Zaplanuj konserwację
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="maintenanceTable">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Godziny</th>
                            <th>Atrakcja</th>
                            <th>Obiekt</th>
                            <th>Typ</th>
                            <th>Status</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for maintenance -->
<div class="modal fade" id="maintenanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Zaplanuj konserwację</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="maintenanceForm">
                    <div class="mb-3">
                        <label class="form-label">Obiekt</label>
                        <select class="form-control" id="facilitySelect" required>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Atrakcja</label>
                        <select class="form-control" id="attractionSelect" required>
                            <option value="">Najpierw wybierz obiekt</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data</label>
                        <input type="date" class="form-control" id="maintenanceDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Godzina rozpoczęcia</label>
                        <input type="time" class="form-control" id="startTime" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Godzina zakończenia</label>
                        <input type="time" class="form-control" id="endTime" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Typ konserwacji</label>
                        <select class="form-control" id="maintenanceType" required>
                            <option value="routine">Rutynowa</option>
                            <option value="emergency">Awaryjna</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                <button type="button" class="btn btn-primary" id="saveMaintenance">Zapisz</button>
            </div>
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>

<script src="<?php echo SITE_URL; ?>/assets/js/maintenance.js"></script>

// assets/js/maintenance.js
$(document).ready(function() {
    loadMaintenanceSchedule();
    loadFacilities();

    $('#facilitySelect').change(function() {
        const facilityId = $(this).val();
        if (facilityId) {
            loadAttractions(facilityId);
        } else {
            $('#attractionSelect').html('<option value="">Najpierw wybierz obiekt</option>');
        }
    });

    $('#saveMaintenance').click(function() {
        const maintenanceData = {
            attraction_id: $('#attractionSelect').val(),
            maintenance_date: $('#maintenanceDate').val(),
            start_time: $('#startTime').val(),
            end_time: $('#endTime').val(),
            type: $('#maintenanceType').val(),
            status: 'planned'
        };

        if (!validateMaintenanceData(maintenanceData)) {
            return;
        }

        $.ajax({
            url: SITE_URL + '/api/maintenance/create.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(maintenanceData),
            success: function(response) {
                if (response.status === 'success') {
                    $('#maintenanceModal').modal('hide');
                    showAlert('success', 'Konserwacja została zaplanowana.');
                    loadMaintenanceSchedule();
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Wystąpił błąd podczas planowania konserwacji.');
            }
        });
    });

    function loadMaintenanceSchedule() {
        $.ajax({
            url: SITE_URL + '/api/maintenance/read.php',
            type: 'GET',
            success: function(response) {
                if (response.records) {
                    displayMaintenanceSchedule(response.records);
                }
            },
            error: function() {
                showAlert('danger', 'Nie udało się załadować harmonogramu konserwacji.');
            }
        });
    }

    function displayMaintenanceSchedule(schedules) {
        const tbody = $('#maintenanceTable tbody');
        tbody.empty();

        schedules.forEach(function(schedule) {
            const row = `
                <tr>
                    <td>${formatDate(schedule.maintenance_date)}</td>
                    <td>${schedule.start_time} - ${schedule.end_time}</td>
                    <td>${schedule.attraction_name}</td>
                    <td>${schedule.facility_name}</td>
                    <td>${translateMaintenanceType(schedule.type)}</td>
                    <td>${translateMaintenanceStatus(schedule.status)}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="updateMaintenanceStatus(${schedule.id}, 'completed')">
                            Zakończ
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="updateMaintenanceStatus(${schedule.id}, 'cancelled')">
                            Anuluj
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function translateMaintenanceType(type) {
        const types = {
            'routine': 'Rutynowa',
            'emergency': 'Awaryjna'
        };
        return types[type] || type;
    }

    function translateMaintenanceStatus(status) {
        const statuses = {
            'planned': 'Zaplanowana',
            'in_progress': 'W trakcie',
            'completed': 'Zakończona',
            'cancelled': 'Anulowana'
        };
        return statuses[status] || status;
    }

    window.updateMaintenanceStatus = function(id, status) {
        $.ajax({
            url: SITE_URL + '/api/maintenance/update_status.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                id: id,
                status: status
            }),
            success: function(response) {
                if (response.status === 'success') {
                    showAlert('success', 'Status konserwacji został zaktualizowany.');
                    loadMaintenanceSchedule();
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Nie udało się zaktualizować statusu konserwacji.');
            }
        });
    };

    function validateMaintenanceData(data) {
        for (let key in data) {
            if (!data[key]) {
                showAlert('danger', 'Wypełnij wszystkie wymagane pola.');
                return false;
            }
        }
        return true;
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
