# EasyGo Project Structure Guide

This project follows a strict **Separation of Concerns** principle to ensure maintainability and scalability.

## 📁 Directory Layout

### 1. Controllers (`app/Http/Controllers`)
- **Api/**: Contains all logic for the Flutter mobile application.
    - **Rider/**: Ride booking, rider profile, and rider-specific statistics.
    - **Driver/**: Ride acceptance, vehicle management, and driver earnings.
    - **Common/**: Shared features like Location tracking and Carpool (Ride Sharing).
- **Admin/**: Contains all logic for the Web Admin Dashboard.
    - **UserManagement/**: Approving drivers, managing riders, and document verification.
    - **RideManagement/**: Monitoring live rides and viewing ride history.
    - **Finance/**: Handling wallets, transactions, and payouts.
    - **Settings/**: Reviews, reports, and system promotions.
- **Auth/**: Authentication logic for both Web and API (Sanctum).

### 2. Models (`app/Models`)
- Standard Eloquent models representing the database tables (Driver, Rider, Ride, Vehicle, etc.).

### 3. Traits (`app/Traits`)
- **RideMetricsTrait**: Shared logic for calculating real-time statistics to avoid code duplication.

### 4. Routes (`routes/`)
- **api.php**: All mobile app endpoints (v1 prefix).
- **web.php**: All admin dashboard and web-related routes.

## 🛠 Tech Stack
- **Backend**: Laravel 11
- **Database**: MySQL
- **Real-time**: API Polling (5s interval recommended)
- **Auth**: Laravel Sanctum

---
*Last Updated: April 30, 2026*
