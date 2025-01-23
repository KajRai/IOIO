-- sql/mcwr_db.sql
CREATE DATABASE IF NOT EXISTS mcwr_db;
USE mcwr_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('manager', 'cashier', 'lifeguard', 'maintenance') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE attractions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facility_id INT,
    name VARCHAR(100) NOT NULL,
    status ENUM('active', 'maintenance', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id)
);

CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    facility_id INT,
    work_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES users(id),
    FOREIGN KEY (facility_id) REFERENCES facilities(id)
);

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    facility_id INT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    number_of_people INT NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (facility_id) REFERENCES facilities(id)
);

CREATE TABLE reservation_attractions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT,
    attraction_id INT,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    FOREIGN KEY (attraction_id) REFERENCES attractions(id)
);

CREATE TABLE maintenance_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attraction_id INT,
    maintenance_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    type ENUM('routine', 'emergency') DEFAULT 'routine',
    status ENUM('planned', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attraction_id) REFERENCES attractions(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Insert sample data
INSERT INTO users (first_name, last_name, email, password, role) VALUES
('Adam', 'Wilkowski', 'adam.wilkowski@mcwr.pl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager'),
('Tobiasz', 'Sala', 'tobiasz.sala@mcwr.net', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier');

INSERT INTO facilities (name, address) VALUES
('Aquapark Centrum', 'ul. Centralna 1, 00-001 Warszawa'),
('AquaZone Północ', 'ul. Północna 15, 00-002 Warszawa');

-- config/config.php
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

// models/User.php
<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $password;
    public $role;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($email, $password) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if(password_verify($password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET 
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    password = :password,
                    role = :role";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->role = htmlspecialchars(strip_tags($this->role));

        // Bind values
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":email", $this->email);
        
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        $stmt->bindParam(":password", $password_hash);
        
        $stmt->bindParam(":role", $this->role);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET 
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    role = :role
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind values
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}

// models/Facility.php
<?php
class Facility {
    private $conn;
    private $table_name = "facilities";

    public $id;
    public $name;
    public $address;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET name=:name, address=:address";
        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->address = htmlspecialchars(strip_tags($this->address));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":address", $this->address);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                SET 
                    name = :name,
                    address = :address
                WHERE id = :id";
    
        $stmt = $this->conn->prepare($query);
    
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->id = htmlspecialchars(strip_tags($this->id));
    
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':id', $this->id);
    
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
    
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);
    
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
