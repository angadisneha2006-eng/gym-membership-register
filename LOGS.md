# Engineering Logbook - Gym Membership and Slot Register
**Name:** Sneha S Angadi / Veena V Hiremath  
**Engines Owned:** Engine 1 (Database) & Engine 2 (Backend / Logic)  

---

### [Log - Day 1]
- **Tasks Done:**
  - Designed the relational schema in MySQL for Members, Gym Slots, and Bookings.
  - Set up Apache & MySQL using XAMPP.
  - Built `db.php` for database connection with CORS support.
  - Implemented `api.php` with rule-checking logic and hand-built slot capacity algorithm.
  - Created `index.html` to test live API endpoints.

- **Errors Encountered & How Fixed:**
  - **Issue 1:** When testing duplicate registration, the server gave a database crash error instead of a friendly message.
    - *Fix:* Added an explicit `SELECT member_id` check in `api.php` before the insert query to return a clean `"Member already exists"` error.
  - **Issue 2:** CORS blocking issues when testing from external browser tabs.
    - *Fix:* Added `header("Access-Control-Allow-Origin: *")` inside `db.php`.

- **Engine Status:** 
  - Engine 1 (Database): Ready & verified in phpMyAdmin.
  - Engine 2 (Backend): Tested and ready for Frontend & Testing integration.