// views/employee/list.php (continuation)
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for employee management -->
<div class="modal fade" id="employeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dodaj pracownika</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="employeeForm">
                    <input type="hidden" id="employeeId">
                    <div class="mb-3">
                        <label class="form-label">Imię</label>
                        <input type="text" class="form-control" id="firstName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nazwisko</label>
                        <input type="text" class="form-control" id="lastName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hasło</label>
                        <input type="password" class="form-control" id="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stanowisko</label>
                        <select class="form-control" id="role" required>
                            <option value="">Wybierz stanowisko</option>
                            <option value="manager">Kierownik</option>
                            <option value="cashier">Kasjer</option>
                            <option value="lifeguard">Ratownik</option>
                            <option value="maintenance">Konserwator</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Obiekt</label>
                        <select class="form-control" id="facilitySelect" required>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                <button type="button" class="btn btn-primary" id="saveEmployee">Zapisz</button>
            </div>
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>

<script src="<?php echo SITE_URL; ?>/assets/js/employee.js"></script>

// assets/js/employee.js
$(document).ready(function() {
    loadEmployees();
    loadFacilities();

    $('#saveEmployee').click(function() {
        const employeeData = {
            id: $('#employeeId').val(),
            first_name: $('#firstName').val(),
            last_name: $('#lastName').val(),
            email: $('#email').val(),
            password: $('#password').val(),
            role: $('#role').val(),
            facility_id: $('#facilitySelect').val()
        };

        if (!validateEmployeeData(employeeData)) {
            return;
        }

        const isEdit = !!employeeData.id;
        const url = SITE_URL + '/api/employee/' + (isEdit ? 'update.php' : 'create.php');

        $.ajax({
            url: url,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(employeeData),
            success: function(response) {
                if (response.status === 'success') {
                    $('#employeeModal').modal('hide');
                    showAlert('success', isEdit ? 'Pracownik został zaktualizowany.' : 'Pracownik został dodany.');
                    loadEmployees();
                    resetForm();
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Wystąpił błąd podczas ' + (isEdit ? 'aktualizacji' : 'dodawania') + ' pracownika.');
            }
        });
    });

    function loadEmployees() {
        $.ajax({
            url: SITE_URL + '/api/employee/read.php',
            type: 'GET',
            success: function(response) {
                if (response.records) {
                    displayEmployees(response.records);
                }
            },
            error: function() {
                showAlert('danger', 'Nie udało się załadować listy pracowników.');
            }
        });
    }

    function displayEmployees(employees) {
        const tbody = $('#employeesTable tbody');
        tbody.empty();

        employees.forEach(function(employee) {
            const row = `
                <tr>
                    <td>${employee.first_name} ${employee.last_name}</td>
                    <td>${employee.email}</td>
                    <td>${translateRole(employee.role)}</td>
                    <td>${employee.facility_name}</td>
                    <td>${formatDate(employee.created_at)}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editEmployee(${employee.id})">
                            Edytuj
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteEmployee(${employee.id})">
                            Usuń
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    window.editEmployee = function(id) {
        $.ajax({
            url: SITE_URL + `/api/employee/read_one.php?id=${id}`,
            type: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    populateForm(response.employee);
                    $('#employeeModal').modal('show');
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Nie udało się załadować danych pracownika.');
            }
        });
    };

    window.deleteEmployee = function(id) {
        if (confirm('Czy na pewno chcesz usunąć tego pracownika?')) {
            $.ajax({
                url: SITE_URL + '/api/employee/delete.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id: id }),
                success: function(response) {
                    if (response.status === 'success') {
                        showAlert('success', 'Pracownik został usunięty.');
                        loadEmployees();
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function() {
                    showAlert('danger', 'Nie udało się usunąć pracownika.');
                }
            });
        }
    };

    function validateEmployeeData(data) {
        if (!data.first_name || !data.last_name || !data.email || !data.role || !data.facility_id) {
            showAlert('danger', 'Wypełnij wszystkie wymagane pola.');
            return false;
        }

        if (!data.id && !data.password) {
            showAlert('danger', 'Hasło jest wymagane dla nowego pracownika.');
            return false;
        }

        if (!validateEmail(data.email)) {
            showAlert('danger', 'Podany adres email jest nieprawidłowy.');
            return false;
        }

        return true;
    }

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function translateRole(role) {
        const roles = {
            'manager': 'Kierownik',
            'cashier': 'Kasjer',
            'lifeguard': 'Ratownik',
            'maintenance': 'Konserwator'
        };
        return roles[role] || role;
    }

    function resetForm() {
        $('#employeeForm')[0].reset();
        $('#employeeId').val('');
        $('#password').prop('required', true);
    }

    function populateForm(employee) {
        $('#employeeId').val(employee.id);
        $('#firstName').val(employee.first_name);
        $('#lastName').val(employee.last_name);
        $('#email').val(employee.email);
        $('#role').val(employee.role);
        $('#facilitySelect').val(employee.facility_id);
        $('#password').prop('required', false);
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
