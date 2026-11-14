<?php
// ========================================================================
// PROJECT: GYM MEMBERSHIP AND SLOT REGISTER
// ENGINE 2: LOGIC & RULE / BACKEND (PHP + MySQL)
// KLE Technological University - BCA V Semester
// ========================================================================

require_once "db.php";

// Read action from URL (e.g., api.php?action=get_slots)
$action = $_GET['action'] ?? '';

// Read JSON data sent by frontend or testing tools
$input = json_decode(file_get_contents("php://input"), true) ?? $_POST;

// ------------------------------------------------------------------------
// HAND-BUILT ALGORITHM: Slot Capacity & Duplicate Allocation Checker
// (This is the algorithm required by your teacher's slide!)
// ------------------------------------------------------------------------
class GymSlotCapacityChecker {
    private $maxCapacity;
    private $currentBookings;

    public function __construct($maxCapacity, $currentBookingsList) {
        $this->maxCapacity = (int)$maxCapacity;
        $this->currentBookings = $currentBookingsList; // Array of member IDs
    }

    // Rule 1: Checks if slot is already full (Time: O(1))
    public function isFull() {
        return count($this->currentBookings) >= $this->maxCapacity;
    }

    // Rule 2: Checks if member already booked this slot (Duplicate check)
    public function isDuplicate($memberId) {
        foreach ($this->currentBookings as $bookedId) {
            if ($bookedId == $memberId) {
                return true;
            }
        }
        return false;
    }

    // Calculate remaining seats
    public function getAvailableSeats() {
        return max(0, $this->maxCapacity - count($this->currentBookings));
    }
}

// ------------------------------------------------------------------------
// 1. ACTION: Get All Gym Slots with Available Capacity
// URL: api.php?action=get_slots
// ------------------------------------------------------------------------
if ($action === 'get_slots') {
    $date = $_GET['date'] ?? date('Y-m-d');
    
    $query = "SELECT s.*, 
              (s.max_capacity - IFNULL((
                  SELECT COUNT(*) FROM slot_bookings b 
                  WHERE b.slot_id = s.slot_id 
                  AND b.booking_date = '$date' 
                  AND b.status = 'Confirmed'
              ), 0)) AS available_seats
              FROM gym_slots s";
              
    $result = $conn->query($query);
    $slots = [];
    while ($row = $result->fetch_assoc()) {
        $slots[] = $row;
    }
    
    echo json_encode(["status" => "success", "date" => $date, "data" => $slots]);
    exit();
}

// ------------------------------------------------------------------------
// 2. ACTION: Register New Member (FR2)
// URL: api.php?action=add_member
// ------------------------------------------------------------------------
if ($action === 'add_member') {
    $code    = trim($input['member_code'] ?? '');
    $name    = trim($input['full_name'] ?? '');
    $email   = trim($input['email'] ?? '');
    $phone   = trim($input['phone'] ?? '');
    $gender  = trim($input['gender'] ?? 'Male');
    $address = trim($input['address'] ?? '');

    // Rule: Mandatory fields cannot be empty
    if (empty($code) || empty($name) || empty($email) || empty($phone)) {
        echo json_encode(["status" => "error", "message" => "All fields (code, name, email, phone) are mandatory."]);
        exit();
    }

    // Rule: Check for duplicate member ID, email or phone
    $check = $conn->query("SELECT member_id FROM members WHERE member_code = '$code' OR email = '$email' OR phone = '$phone'");
    if ($check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Member Code, Email or Phone already exists."]);
        exit();
    }

    $sql = "INSERT INTO members (member_code, full_name, email, phone, gender, address) 
            VALUES ('$code', '$name', '$email', '$phone', '$gender', '$address')";
            
    if ($conn->query($sql)) {
        echo json_encode(["status" => "success", "message" => "Member Registered Successfully!", "member_id" => $conn->insert_id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to save member."]);
    }
    exit();
}

// ------------------------------------------------------------------------
// 3. ACTION: Book a Workout Slot using Hand-Built Algorithm (FR5)
// URL: api.php?action=book_slot
// ------------------------------------------------------------------------
if ($action === 'book_slot') {
    $member_id    = (int)($input['member_id'] ?? 0);
    $slot_id      = (int)($input['slot_id'] ?? 0);
    $booking_date = trim($input['booking_date'] ?? date('Y-m-d'));

    if ($member_id <= 0 || $slot_id <= 0) {
        echo json_encode(["status" => "error", "message" => "Valid Member ID and Slot ID are required."]);
        exit();
    }

    // 1. Fetch slot max capacity
    $slotRes = $conn->query("SELECT slot_name, max_capacity FROM gym_slots WHERE slot_id = $slot_id");
    if ($slotRes->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Invalid Slot Selected."]);
        exit();
    }
    $slotData = $slotRes->fetch_assoc();
    $maxCapacity = $slotData['max_capacity'];

    // 2. Fetch list of existing bookings on this date for this slot
    $bookRes = $conn->query("SELECT member_id FROM slot_bookings WHERE slot_id = $slot_id AND booking_date = '$booking_date' AND status = 'Confirmed'");
    $existingBookings = [];
    while ($row = $bookRes->fetch_assoc()) {
        $existingBookings[] = $row['member_id'];
    }

    // 3. RUN HAND-BUILT ALGORITHM
    $checker = new GymSlotCapacityChecker($maxCapacity, $existingBookings);

    if ($checker->isDuplicate($member_id)) {
        echo json_encode(["status" => "error", "message" => "Rule Violation: You have already booked this slot for $booking_date."]);
        exit();
    }

    if ($checker->isFull()) {
        echo json_encode(["status" => "error", "message" => "Capacity Full: This slot is fully booked ({$maxCapacity} members max)."]);
        exit();
    }

    // 4. Save confirmed booking
    $sql = "INSERT INTO slot_bookings (member_id, slot_id, booking_date, status) VALUES ($member_id, $slot_id, '$booking_date', 'Confirmed')";
    if ($conn->query($sql)) {
        echo json_encode([
            "status" => "success", 
            "message" => "Slot Booked Successfully!",
            "remaining_seats" => $checker->getAvailableSeats() - 1
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Booking failed."]);
    }
    exit();
}

// ------------------------------------------------------------------------
// 4. ACTION: Get All Members (FR7)
// URL: api.php?action=get_members
// ------------------------------------------------------------------------
if ($action === 'get_members') {
    $result = $conn->query("SELECT * FROM members ORDER BY member_id DESC");
    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $members]);
    exit();
}

// ------------------------------------------------------------------------
// 5. ACTION: Summary Report for Dashboard (FR8)
// URL: api.php?action=get_reports
// ------------------------------------------------------------------------
if ($action === 'get_reports') {
    $totalMembers = $conn->query("SELECT COUNT(*) AS total FROM members")->fetch_assoc()['total'];
    $totalBookings = $conn->query("SELECT COUNT(*) AS total FROM slot_bookings WHERE status = 'Confirmed'")->fetch_assoc()['total'];
    $totalSlots = $conn->query("SELECT COUNT(*) AS total FROM gym_slots")->fetch_assoc()['total'];

    echo json_encode([
        "status" => "success",
        "data" => [
            "total_members" => (int)$totalMembers,
            "total_slots" => (int)$totalSlots,
            "confirmed_bookings" => (int)$totalBookings
        ]
    ]);
    exit();
}

// Default message if no action is provided
echo json_encode(["status" => "running", "message" => "Gym Backend API is up and running!"]);
?>