// models/Reservation.php (continuation)
    public function read() {
        $query = "SELECT r.*, f.name as facility_name, u.first_name, u.last_name 
                FROM " . $this->table_name . " r
                LEFT JOIN facilities f ON r.facility_id = f.id
                LEFT JOIN users u ON r.user_id = u.id
                ORDER BY r.start_time DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    user_id = :user_id,
                    facility_id = :facility_id,
                    start_time = :start_time,
                    end_time = :end_time,
                    number_of_people = :number_of_people,
                    status = :status";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->facility_id = htmlspecialchars(strip_tags($this->facility_id));
        $this->start_time = htmlspecialchars(strip_tags($this->start_time));
        $this->end_time = htmlspecialchars(strip_tags($this->end_time));
        $this->number_of_people = htmlspecialchars(strip_tags($this->number_of_people));
        $this->status = htmlspecialchars(strip_tags($this->status));

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":facility_id", $this->facility_id);
        $stmt->bindParam(":start_time", $this->start_time);
        $stmt->bindParam(":end_time", $this->end_time);
        $stmt->bindParam(":number_of_people", $this->number_of_people);
        $stmt->bindParam(":status", $this->status);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    facility_id = :facility_id,
                    start_time = :start_time,
                    end_time = :end_time,
                    number_of_people = :number_of_people,
                    status = :status
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize and bind
        $this->facility_id = htmlspecialchars(strip_tags($this->facility_id));
        $this->start_time = htmlspecialchars(strip_tags($this->start_time));
        $this->end_time = htmlspecialchars(strip_tags($this->end_time));
        $this->number_of_people = htmlspecialchars(strip_tags($this->number_of_people));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":facility_id", $this->facility_id);
        $stmt->bindParam(":start_time", $this->start_time);
        $stmt->bindParam(":end_time", $this->end_time);
        $stmt->bindParam(":number_of_people", $this->number_of_people);
        $stmt->bindParam(":status", $this->status);
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

    public function addAttractions($reservation_id, $attraction_ids) {
        $query = "INSERT INTO reservation_attractions (reservation_id, attraction_id) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);

        foreach($attraction_ids as $attraction_id) {
            $stmt->bindParam(1, $reservation_id);
            $stmt->bindParam(2, $attraction_id);
            if(!$stmt->execute()) {
                return false;
            }
        }
        return true;
    }
}

// models/Schedule.php
<?php
class Schedule {
    private $conn;
    private $table_name = "schedules";

    public $id;
    public $employee_id;
    public $facility_id;
    public $work_date;
    public $start_time;
    public $end_time;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT s.*, f.name as facility_name, 
                        CONCAT(u.first_name, ' ', u.last_name) as employee_name
                FROM " . $this->table_name . " s
                LEFT JOIN facilities f ON s.facility_id = f.id
                LEFT JOIN users u ON s.employee_id = u.id
                ORDER BY s.work_date, s.start_time";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readByEmployee($employee_id) {
        $query = "SELECT s.*, f.name as facility_name
                FROM " . $this->table_name . " s
                LEFT JOIN facilities f ON s.facility_id = f.id
                WHERE s.employee_id = ?
                ORDER BY s.work_date, s.start_time";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $employee_id);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    employee_id = :employee_id,
                    facility_id = :facility_id,
                    work_date = :work_date,
                    start_time = :start_time,
                    end_time = :end_time";

        $stmt = $this->conn->prepare($query);

        // Sanitize and bind
        $this->employee_id = htmlspecialchars(strip_tags($this->employee_id));
        $this->facility_id = htmlspecialchars(strip_tags($this->facility_id));
        $this->work_date = htmlspecialchars(strip_tags($this->work_date));
        $this->start_time = htmlspecialchars(strip_tags($this->start_time));
        $this->end_time = htmlspecialchars(strip_tags($this->end_time));

        $stmt->bindParam(":employee_id", $this->employee_id);
        $stmt->bindParam(":facility_id", $this->facility_id);
        $stmt->bindParam(":work_date", $this->work_date);
        $stmt->bindParam(":start_time", $this->start_time);
        $stmt->bindParam(":end_time", $this->end_time);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    employee_id = :employee_id,
                    facility_id = :facility_id,
                    work_date = :work_date,
                    start_time = :start_time,
                    end_time = :end_time
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize and bind
        $this->employee_id = htmlspecialchars(strip_tags($this->employee_id));
        $this->facility_id = htmlspecialchars(strip_tags($this->facility_id));
        $this->work_date = htmlspecialchars(strip_tags($this->work_date));
        $this->start_time = htmlspecialchars(strip_tags($this->start_time));
        $this->end_time = htmlspecialchars(strip_tags($this->end_time));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":employee_id", $this->employee_id);
        $stmt->bindParam(":facility_id", $this->facility_id);
        $stmt->bindParam(":work_date", $this->work_date);
        $stmt->bindParam(":start_time", $this->start_time);
        $stmt->bindParam(":end_time", $this->end_time);
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
