// api/reservation/read.php (continuation)
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $reservation_item = array(
            "id" => $row['id'],
            "facility_name" => $row['facility_name'],
            "client_name" => $row['first_name'] . ' ' . $row['last_name'],
            "start_time" => $row['start_time'],
            "end_time" => $row['end_time'],
            "number_of_people" => $row['number_of_people'],
            "status" => $row['status'],
            "created_at" => $row['created_at']
        );
        array_push($reservations_arr["records"], $reservation_item);
    }

    http_response_code(200);
    echo json_encode($reservations_arr);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "Nie znaleziono rezerwacji."));
}

// api/reservation/update.php
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Reservation.php';
include_once '../../utils/Auth.php';

Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();

$reservation = new Reservation($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->id)) {
    $reservation->id = $data->id;
    $reservation->facility_id = $data->facility_id;
    $reservation->start_time = $data->start_time;
    $reservation->end_time = $data->end_time;
    $reservation->number_of_people = $data->number_of_people;
    $reservation->status = $data->status;

    if($reservation->update()) {
        http_response_code(200);
        echo json_encode(array(
            "status" => "success",
            "message" => "Rezerwacja została zaktualizowana."
        ));
    } else {
        http_response_code(503);
        echo json_encode(array(
            "status" => "error",
            "message" => "Nie udało się zaktualizować rezerwacji."
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error",
        "message" => "Brak wymaganych danych."
    ));
}

// api/reservation/delete.php
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Reservation.php';
include_once '../../utils/Auth.php';

Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();

$reservation = new Reservation($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->id)) {
    $reservation->id = $data->id;

    if($reservation->delete()) {
        http_response_code(200);
        echo json_encode(array(
            "status" => "success",
            "message" => "Rezerwacja została usunięta."
        ));
    } else {
        http_response_code(503);
        echo json_encode(array(
            "status" => "error",
            "message" => "Nie udało się usunąć rezerwacji."
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error",
        "message" => "Brak ID rezerwacji."
    ));
}

// views/auth/login.php
<?php
require_once '../../config/config.php';
if(isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL);
    exit();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Logowanie</h2>
                        <div id="alertContainer"></div>
                        <form id="loginForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Hasło</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Zaloguj się</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: '<?php echo SITE_URL; ?>/api/auth/login.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        email: $('#email').val(),
                        password: $('#password').val()
                    }),
                    success: function(response) {
                        if(response.status === 'success') {
                            window.location.href = '<?php echo SITE_URL; ?>';
                        } else {
                            showAlert('danger', response.message);
                        }
                    },
                    error: function(xhr) {
                        let message = 'Wystąpił błąd podczas logowania.';
                        if(xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        showAlert('danger', message);
                    }
                });
            });

            function showAlert(type, message) {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                $('#alertContainer').html(alertHtml);
            }
        });
    </script>
</body>
</html>

// views/reservation/list.php
<?php
require_once '../../config/config.php';
require_once '../../utils/Auth.php';

Auth::requireLogin();
include_once '../templates/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Rezerwacje</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reservationModal">
            Nowa Rezerwacja
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="reservationsTable">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Klient</th>
                            <th>Obiekt</th>
                            <th>Liczba osób</th>
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

<!-- Modal for new reservation -->
<div class="modal fade" id="reservationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nowa Rezerwacja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reservationForm">
                    <div class="mb-3">
                        <label class="form-label">Obiekt</label>
                        <select class="form-control" id="facilitySelect" required>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data</label>
                        <input type="date" class="form-control" id="reservationDate" required>
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
                        <label class="form-label">Liczba osób</label>
                        <input type="number" class="form-control" id="numberOfPeople" required min="1">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                <button type="button" class="btn btn-primary" id="saveReservation">Zapisz</button>
            </div>
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>

<script src="<?php echo SITE_URL; ?>/assets/js/reservation.js"></script>
