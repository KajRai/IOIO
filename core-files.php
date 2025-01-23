// config/config.php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mcwr_db');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SITE_NAME', 'MCWR - System Zarządzania');
define('SITE_URL', 'http://localhost/mcwr');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration
session_start();

// config/database.php
<?php
require_once 'config.php';

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }
        return $this->conn;
    }
}

// utils/Auth.php
<?php
class Auth {
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . SITE_URL . '/views/auth/login.php');
            exit();
        }
    }

    public static function requireRole($role) {
        self::requireLogin();
        if ($_SESSION['user_role'] !== $role) {
            header('HTTP/1.1 403 Forbidden');
            exit('Unauthorized access');
        }
    }
}

// utils/Validator.php
<?php
class Validator {
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    public static function validateTime($time) {
        $t = DateTime::createFromFormat('H:i', $time);
        return $t && $t->format('H:i') === $time;
    }

    public static function sanitizeString($str) {
        return htmlspecialchars(strip_tags($str));
    }
}

// views/templates/header.php
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include_once 'navigation.php'; ?>

// views/templates/navigation.php
<?php if (Auth::isLoggedIn()): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo SITE_URL; ?>">MCWR</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/views/reservation/list.php">Rezerwacje</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/views/schedule/list.php">Grafik</a>
                </li>
                <?php if ($_SESSION['user_role'] === 'manager'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/views/facility/list.php">Obiekty</a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/api/auth/logout.php">Wyloguj</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>

// views/templates/footer.php
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>

// index.php
<?php
require_once 'config/config.php';
require_once 'utils/Auth.php';

if (!Auth::isLoggedIn()) {
    header('Location: ' . SITE_URL . '/views/auth/login.php');
    exit();
}

include_once 'views/templates/header.php';
?>

<div class="container mt-4">
    <h1>Panel Główny</h1>
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Rezerwacje</h5>
                    <p class="card-text">Zarządzaj rezerwacjami klientów.</p>
                    <a href="<?php echo SITE_URL; ?>/views/reservation/list.php" class="btn btn-primary">Przejdź</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Grafik</h5>
                    <p class="card-text">Sprawdź harmonogram pracy.</p>
                    <a href="<?php echo SITE_URL; ?>/views/schedule/list.php" class="btn btn-primary">Przejdź</a>
                </div>
            </div>
        </div>
        <?php if ($_SESSION['user_role'] === 'manager'): ?>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Obiekty</h5>
                    <p class="card-text">Zarządzaj obiektami i atrakcjami.</p>
                    <a href="<?php echo SITE_URL; ?>/views/facility/list.php" class="btn btn-primary">Przejdź</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'views/templates/footer.php'; ?>

// assets/css/style.css
.navbar {
    margin-bottom: 2rem;
}

.card {
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    transition: box-shadow 0.3s ease;
}

.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.25);
}

.table-responsive {
    margin-top: 1rem;
}

.btn-action {
    margin-right: 0.5rem;
}

.alert {
    margin-top: 1rem;
    margin-bottom: 1rem;
}

// assets/js/main.js
$(document).ready(function() {
    // Handle form submissions
    $('form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    }
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Wystąpił błąd podczas przetwarzania żądania.');
            },
            complete: function() {
                submitBtn.prop('disabled', false);
            }
        });
    });

    // Alert helper function
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
