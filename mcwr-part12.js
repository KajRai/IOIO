// api/employee/update.php (continuation)
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/User.php';
include_once '../../utils/Auth.php';

Auth::requireLogin();
Auth::requireRole('manager');

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->id) &&
    !empty($data->first_name) &&
    !empty($data->last_name) &&
    !empty($data->email) &&
    !empty($data->role) &&
    !empty($data->facility_id)
) {
    $user->id = $data->id;
    $user->first_name = $data->first_name;
    $user->last_name = $data->last_name;
    $user->email = $data->email;
    $user->role = $data->role;
    $user->facility_id = $data->facility_id;

    if($user->update()) {
        http_response_code(200);
        echo json_encode(array(
            "status" => "success",
            "message" => "Dane pracownika zostały zaktualizowane."
        ));
    } else {
        http_response_code(503);
        echo json_encode(array(
            "status" => "error",
            "message" => "Nie udało się zaktualizować danych pracownika."
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error",
        "message" => "Brak wymaganych danych."
    ));
}

// api/employee/delete.php
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/User.php';
include_once '../../utils/Auth.php';

Auth::requireLogin();
Auth::requireRole('manager');

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->id)) {
    $user->id = $data->id;

    if($user->delete()) {
        http_response_code(200);
        echo json_encode(array(
            "status" => "success",
            "message" => "Pracownik został usunięty."
        ));
    } else {
        http_response_code(503);
        echo json_encode(array(
            "status" => "error",
            "message" => "Nie udało się usunąć pracownika."
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error",
        "message" => "Brak ID pracownika."
    ));
}

// api/maintenance/update_status.php
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Maintenance.php';
include_once '../../utils/Auth.php';

Auth::requireLogin();
Auth::requireRole('maintenance');

$database = new Database();
$db = $database->getConnection();

$maintenance = new Maintenance($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->id) && !empty($data->status)) {
    $maintenance->id = $data->id;
    $maintenance->status = $data->status;

    if($maintenance->updateStatus()) {
        // If maintenance is completed or cancelled, update the attraction status
        if($data->status === 'completed' || $data->status === 'cancelled') {
            $attraction = new Attraction($db);
            $attraction->id = $maintenance->attraction_id;
            $attraction->status = 'active';
            $attraction->updateStatus();
        }

        http_response_code(200);
        echo json_encode(array(
            "status" => "success",
            "message" => "Status konserwacji został zaktualizowany."
        ));
    } else {
        http_response_code(503);
        echo json_encode(array(
            "status" => "error",
            "message" => "Nie udało się zaktualizować statusu konserwacji."
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error",
        "message" => "Brak wymaganych danych."
    ));
}

// assets/js/main.js (additional global functions)
// Global AJAX setup
$.ajaxSetup({
    beforeSend: function(xhr) {
        // Add CSRF token if needed
    },
    error: function(xhr, status, error) {
        if(xhr.status === 401) {
            // Unauthorized - redirect to login
            window.location.href = SITE_URL + '/views/auth/login.php';
        } else if(xhr.status === 403) {
            // Forbidden
            showAlert('danger', 'Brak uprawnień do wykonania tej operacji.');
        }
    }
});

// Global functions
function formatDate(dateString) {
    const options = { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('pl-PL', options);
}

function formatDateTime(dateString) {
    const options = { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('pl-PL', options);
}

function showAlert(type, message, container = '#alertContainer') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $(container).html(alertHtml);
    
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Additional styles
// assets/css/style.css additions
.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

.btn-action {
    margin-right: 0.25rem;
}

.table th {
    background-color: #f8f9fa;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.sidebar {
    min-height: calc(100vh - 56px);
    background-color: #f8f9fa;
    padding: 1rem;
}

.form-label {
    font-weight: 500;
}

.required::after {
    content: "*";
    color: red;
    margin-left: 0.25rem;
}

.alert {
    margin-bottom: 1rem;
}

// README.md
# MCWR - System Zarządzania Centrum Wodnej Rekreacji

System zarządzania dla Miejskiego Centrum Wodnej Rekreacji, umożliwiający obsługę rezerwacji, zarządzanie pracownikami, grafikami oraz konserwacjami.

## Wymagania systemowe

- PHP 7.4 lub nowszy
- MySQL 5.7 lub nowszy
- Serwer WWW (np. Apache, Nginx)
- Composer (zarządzanie zależnościami PHP)

## Instalacja

1. Sklonuj repozytorium:
```bash
git clone https://github.com/twoja-organizacja/mcwr.git
```

2. Zainstaluj zależności:
```bash
composer install
```

3. Utwórz bazę danych i zaimportuj strukturę:
```bash
mysql -u root -p < sql/mcwr_db.sql
```

4. Skonfiguruj połączenie z bazą danych w pliku `config/config.php`

5. Ustaw uprawnienia dla katalogów:
```bash
chmod -R 755 .
chmod -R 777 uploads/
```

## Struktura katalogów

- `api/` - Endpointy API REST
- `assets/` - Pliki CSS, JavaScript i obrazy
- `config/` - Pliki konfiguracyjne
- `models/` - Modele danych
- `utils/` - Klasy pomocnicze
- `views/` - Szablony widoków
- `sql/` - Pliki SQL z strukturą bazy danych

## Funkcjonalności

- Zarządzanie pracownikami
- Zarządzanie obiektami i atrakcjami
- System rezerwacji
- Grafiki pracy
- Harmonogram konserwacji
- Raportowanie i statystyki

## Użytkownicy systemu

- **Kierownik** - pełny dostęp do systemu
- **Kasjer** - obsługa rezerwacji i karnetów
- **Ratownik** - dostęp do grafiku pracy
- **Konserwator** - zarządzanie harmonogramem konserwacji

## Autorzy

- Wojciech Surdyk
- Arkadiusz Mulkityn
- Paweł Nieczepa
- Kajetan Rainko

## Licencja

Ten projekt jest objęty licencją MIT.
