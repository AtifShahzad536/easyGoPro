# EasyGo API Documentation

**Base URL:** `http://172.20.10.2:8000`  
**Last Updated:** April 29, 2026

---

## Table of Contents

1. [Authentication APIs](#authentication-apis) (Public)
2. [Driver APIs](#driver-apis) (Protected)
3. [Rider APIs](#rider-apis) (Protected)
4. [Common APIs](#common-apis) (Driver & Rider)
5. [Error Codes](#error-codes)

---

## Authentication APIs (Public)

### 1. Driver Register
```
POST /api/v1/auth/register
```
**Headers:** `Content-Type: multipart/form-data`

**Body:**
```
role: driver (required)
full_name: string (required, max 255)
mobile_number: string (required, unique)
cnic: string (required, unique)
cnic_name: string (required, max 255)
email: string (optional, unique)
date_of_birth: date (required, YYYY-MM-DD)
gender: male/female/other (required)
profile_photo: file (optional, image, max 2MB)
```

**Response:**
```json
{
  "status": "success",
  "message": "Driver registered successfully",
  "access_token": "token_here",
  "token_type": "Bearer",
  "role": "driver",
  "user": { ...user_data with profile_photo_url... }
}
```

---

### 2. Rider Register
```
POST /api/v1/auth/register
```
**Headers:** `Content-Type: multipart/form-data`

**Body:**
```
role: rider (required)
full_name: string (required, max 255)
mobile_number: string (required, unique)
display_name: string (optional, max 255)
email: string (optional, unique)
gender: male/female/other (optional)
date_of_birth: date (optional, YYYY-MM-DD)
profile_photo: file (optional, image, max 2MB)
```

**Response:**
```json
{
  "status": "success",
  "message": "Rider registered successfully",
  "access_token": "token_here",
  "token_type": "Bearer",
  "role": "rider",
  "user": { ...user_data with profile_photo_url... }
}
```

---

### 3. Login
```
POST /api/v1/auth/login
```
**Headers:** `Content-Type: application/json`

**Body:**
```json
{
  "mobile_number": "03001234567",
  "role": "driver"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Login successful",
  "access_token": "token_here",
  "token_type": "Bearer",
  "role": "driver",
  "user": { ...user_data with profile_photo_url... }
}
```

---

### 4. Check Driver Phone Exists
```
POST /api/v1/auth/check-driver-phone
```

**Body:**
```json
{
  "mobile_number": "03001234567"
}
```

**Response (if exists - 409):**
```json
{
  "status": "error",
  "message": "This phone number is already registered",
  "exists": true
}
```

**Response (if available - 200):**
```json
{
  "status": "success",
  "message": "Phone number is available",
  "exists": false
}
```

---

### 5. Check Rider Phone Exists
```
POST /api/v1/auth/check-rider-phone
```

*(Same as driver phone check)*

---

## Driver APIs (Protected)

**Required Header:** `Authorization: Bearer YOUR_ACCESS_TOKEN`

### 6. Get Driver Profile (Me)
```
GET /api/v1/driver/me
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "full_name": "Zaeem",
    "mobile_number": "03474979594",
    "email": null,
    "profile_photo": "profile_photos/drivers/xxxxx.jpg",
    "profile_photo_url": "http://localhost:8000/storage/profile_photos/drivers/xxxxx.jpg",
    "cnic": "3460380326605",
    "cnic_name": "Zaeem Ahmed",
    "date_of_birth": "1990-01-01",
    "gender": "male",
    "status": "online",
    "is_available": true
  },
  "role": "driver"
}
```

---

### 7. Register Vehicle
```
POST /api/v1/driver/vehicle/register
```

**Body:**
```json
{
  "vehicle_type": "car",
  "make": "Toyota",
  "model": "Corolla",
  "year": 2020,
  "color": "White",
  "license_plate": "ABC-123",
  "seating_capacity": 4
}
```

---

### 8. Upload Driver Documents
```
POST /api/v1/driver/documents/upload
```
**Headers:** `Content-Type: multipart/form-data`

**Body:**
```
documents[0][type]: cnic_front
documents[0][file]: [file]
documents[1][type]: cnic_back
documents[1][file]: [file]
documents[2][type]: driving_license
documents[2][file]: [file]
```

---

### 9. Get Driver Status
```
GET /api/v1/driver/status
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "driver_id": 1,
    "status": "online",
    "is_available": true,
    "last_updated": "2025-04-15 12:30:00"
  }
}
```

---

### 10. Update Driver Status
```
POST /api/v1/driver/status/update
```

**Body:**
```json
{
  "status": "online"
}
```
*Values: online, offline, busy, on_ride, inactive*

---

### 11. Update Driver Location
```
POST /api/v1/driver/location/update
```

**Body:**
```json
{
  "latitude": 31.5204,
  "longitude": 74.3587,
  "place_name": "Lahore, Pakistan"
}
```

---

### 12. Get Driver Location
```
GET /api/v1/driver/location
```

---

### 13. Get Driver Statistics
```
GET /api/v1/driver/statistics
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "earnings": {
        "total_earnings": 45000.00,
        "wallet_balance": 12000.00,
        "total_withdrawn": 33000.00,
        "available_balance": 12000.00
    },
    "trips": {
        "total_trips": 150,
        "completed_trips": 142,
        "cancelled_trips": 8,
        "completion_rate": 94.67,
        "cancellation_rate": 5.33
    },
    "rating": {
        "average_rating": 4.85
    },
    "activity": {
        "total_online_minutes": 5400,
        "total_online_hours": 90,
        "last_trip_at": "2025-04-15 12:30:00"
    }
  }
}
```

---

### 14. Get Dashboard Summary
```
GET /api/v1/driver/statistics/dashboard
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "today_trips": 5,
    "today_earnings": 1500.00,
    "weekly_trips": 25,
    "weekly_earnings": 7500.00,
    "rating": 4.8
  }
}
```

---

### 15. Get Trip Statistics
```
GET /api/v1/driver/statistics/trips
```

---

### 16. Get Earnings History
```
GET /api/v1/driver/statistics/earnings
```

---

### 17. Update Statistics
```
POST /api/v1/driver/statistics/update
```

---

## Carpool APIs (Driver)

### 18. Publish Carpool Ride
```
POST /api/v1/driver/carpool/publish
```

**Body:**
```json
{
  "vehicle_id": 1,
  "origin_address": "Johar Town, Lahore",
  "origin_lat": 31.5204,
  "origin_lng": 74.3587,
  "destination_address": "Blue Area, Islamabad",
  "destination_lat": 33.6844,
  "destination_lng": 73.0479,
  "ride_date": "2025-04-20",
  "ride_time": "08:30",
  "available_seats": 2,
  "fare_per_seat": 1000,
  "notes": "Non-smoking car, AC available"
}
```

---

### 19. Get My Published Rides
```
GET /api/v1/driver/carpool/my-rides
```

---

### 20. Edit Carpool Ride
```
PUT /api/v1/driver/carpool/edit/{rideId}
```

---

### 21. Cancel Carpool Ride
```
POST /api/v1/driver/carpool/cancel/{rideId}
```

---

### 22. Delete Carpool Ride
```
DELETE /api/v1/driver/carpool/delete/{rideId}
```

---

## Rider APIs (Protected)

**Required Header:** `Authorization: Bearer YOUR_ACCESS_TOKEN`

### 23. Get Rider Profile
```
GET /api/v1/rider/profile
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "full_name": "Ali Khan",
    "mobile_number": "03001234567",
    "email": "ali@example.com",
    "profile_photo": "http://localhost:8000/storage/profile_photos/riders/xxxxx.jpg",
    "gender": "male",
    "date_of_birth": "1995-05-15",
    "rating": 4.92,
    "is_active": true,
    "created_at": "2025-04-01 10:00:00"
  }
}
```

---

### 24. Update Rider Profile
```
PUT /api/v1/rider/profile
```
**Headers:** `Content-Type: multipart/form-data`

---

### 25. Get Rider Location
```
GET /api/v1/rider/location
```

---

### 26. Update Rider Location
```
POST /api/v1/rider/location/update
```

---

### 27. Find Nearby Drivers
```
GET /api/v1/rider/drivers/nearby?latitude=31.5204&longitude=74.3587&radius=5
```

**Query Parameters:**
- `latitude` (required)
- `longitude` (required)
- `radius` (optional, default 5km)

---

## Carpool APIs (Rider)

### 28. Search Available Carpool Rides
```
GET /api/v1/rider/carpool/search
```

**Query Parameters:**
```
?origin_lat=31.5204&origin_lng=74.3587&destination_lat=33.6844&destination_lng=73.0479&ride_date=2025-04-20
```

---

## Location & Saved Places APIs (Rider)

### 29. Get Destination Screen Data
```
GET /api/v1/rider/destination-screen
```

**Response:** Combined saved places + recent searches + max stops allowed

---

### 30. Get Saved Places
```
GET /api/v1/rider/saved-places
```

---

### 31. Save Place
```
POST /api/v1/rider/saved-places
```

**Body:**
```json
{
  "type": "home",
  "name": "Home",
  "address": "123 Main Street, Lahore",
  "place_name": "My Sweet Home",
  "latitude": 31.5204,
  "longitude": 74.3587
}
```

---

### 32. Update Saved Place
```
PUT /api/v1/rider/saved-places/{id}
```

---

### 33. Delete Saved Place
```
DELETE /api/v1/rider/saved-places/{id}
```

---

### 34. Get Recent Searches
```
GET /api/v1/rider/recent-searches?limit=10
```

---

### 35. Save Recent Search
```
POST /api/v1/rider/recent-searches
```

---

### 36. Clear Recent Searches
```
DELETE /api/v1/rider/recent-searches
```

---

### 37. Search Locations
```
GET /api/v1/rider/search-locations?query=Liberty
```

---

### 38. Get Place Details
```
GET /api/v1/rider/place-details/{place_id}
```

---

## Ride Booking APIs (Rider)

### 39. Estimate Fare
```
POST /api/v1/rider/estimate-fare
```

**Body:**
```json
{
  "pickup": {
    "latitude": 31.5204,
    "longitude": 74.3587
  },
  "destination": {
    "latitude": 31.4810,
    "longitude": 74.3226
  },
  "stops": [
    {
      "latitude": 31.5204,
      "longitude": 74.3483
    }
  ]
}
```

**Response:** Vehicle options with prices, distance, duration, max 4 stops

---

### 40. Book Ride
```
POST /api/v1/rider/book-ride
```

**Body:**
```json
{
  "pickup": {
    "place_name": "Home",
    "address": "Johar Town, Lahore",
    "latitude": 31.5204,
    "longitude": 74.3587
  },
  "destination": {
    "place_name": "Office",
    "address": "DHA Phase 6, Lahore",
    "latitude": 31.4810,
    "longitude": 74.3226
  },
  "stops": [...],
  "ride_type": "economy",
  "payment_method": "cash"
}
```

---

### 41. Get Ride Details
```
GET /api/v1/rider/rides/{rideId}
```

---

### 42. Cancel Ride
```
POST /api/v1/rider/rides/{rideId}/cancel
```

---

## Common APIs

---

## Rider Statistics APIs

### 43. Get Rider Statistics
```
GET /api/v1/rider/statistics
```
**Response:**
```json
{
  "status": "success",
  "data": {
    "spending": {
        "total_spent": 5000.00,
        "wallet_balance": 150.00,
        "total_refunded": 0.00
    },
    "trips": {
        "total_trips": 12,
        "completed_trips": 10,
        "cancelled_trips": 2,
        "completion_rate": 83.33
    },
    "rating": {
        "average_rating": 4.92
    },
    "last_ride_at": "2026-04-29 10:00:00",
    "updated_at": "2026-04-29 12:00:00"
  }
}
```

---

### 44. Get Rider Dashboard Summary
```
GET /api/v1/rider/statistics/dashboard
```

---

## Ride Rating APIs (New)

### 45. Rate Driver (By Rider)
```
PATCH /api/v1/rider/rides/{rideId}/rate
```
**Body:**
```json
{
  "rating": 5,
  "review": "Great!"
}
```

---

### 46. Rate Rider (By Driver)
```
PATCH /api/v1/driver/rides/{rideId}/rate
```
**Body:**
```json
{
  "rating": 5,
  "review": "Polite"
}
```

---

## Error Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 401 | Unauthorized (Invalid or missing token) |
| 403 | Forbidden (Not allowed) |
| 404 | Not Found |
| 409 | Conflict (e.g., phone already exists) |
| 422 | Validation Error |
| 500 | Internal Server Error |

---

## Notes

1. All protected APIs require Bearer token in Authorization header
2. Token obtained from login or register APIs
3. Profile photos returned as full URLs for direct use in mobile apps
4. All dates in YYYY-MM-DD format
5. All amounts in PKR (Pakistani Rupees)
6. Carpool rides require driver to be verified before publishing
7. Time format is 24-hour (HH:MM)
8. Max 4 stops allowed per ride

---

*Documentation maintained by EasyGo Team*
