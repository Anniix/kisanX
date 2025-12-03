<?php
// php/get_location.php

header('Content-Type: application/json');

// --- MOCK CONFIGURATION ---
$FARM_LAT = 19.1000; 
$FARM_LON = 72.8500;
$C_LAT = 19.0750; 
$C_LON = 72.8800;
$MAX_DELIVERY_TIME_SEC = 600; // Simulated 10 minutes max ride time (10 mins * 60 sec)
$PREP_TIME_SEC = 30; // 30 seconds mock prep time
$AVG_SPEED_KPH = 28; 

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$progress_file = 'order_' . $order_id . '_progress.txt';

// --- MOCK ROUTE DEFINITION (Realistic Waypoints) ---
$ROUTE_WAYPOINTS = [
    // Farm Location (Start)
    ['lat' => 19.1000, 'lon' => 72.8500], 
    ['lat' => 19.0980, 'lon' => 72.8510], 
    ['lat' => 19.0950, 'lon' => 72.8530], // Leaving the farm area
    ['lat' => 19.0900, 'lon' => 72.8550], 
    ['lat' => 19.0880, 'lon' => 72.8580], // Joining a main road
    ['lat' => 19.0870, 'lon' => 72.8610],
    ['lat' => 19.0860, 'lon' => 72.8640],
    ['lat' => 19.0855, 'lon' => 72.8670],
    ['lat' => 19.0850, 'lon' => 72.8700],
    ['lat' => 19.0845, 'lon' => 72.8730], // Turning onto Air Port Road vicinity
    ['lat' => 19.0830, 'lon' => 72.8740],
    ['lat' => 19.0810, 'lon' => 72.8760],
    ['lat' => 19.0790, 'lon' => 72.8770],
    ['lat' => 19.0770, 'lon' => 72.8780],
    // Customer (End)
    ['lat' => 19.0750, 'lon' => 72.8800]
];

// Calculate total distance of the *actual* polyline route
$TOTAL_ROUTE_DISTANCE = 0;
for ($i = 0; $i < count($ROUTE_WAYPOINTS) - 1; $i++) {
    $TOTAL_ROUTE_DISTANCE += calculate_distance(
        $ROUTE_WAYPOINTS[$i]['lat'], $ROUTE_WAYPOINTS[$i]['lon'], 
        $ROUTE_WAYPOINTS[$i+1]['lat'], $ROUTE_WAYPOINTS[$i+1]['lon']
    );
}

// --- PROGRESS SIMULATION ---
if (!file_exists($progress_file)) {
    $start_time = time();
    file_put_contents($progress_file, $start_time);
} else {
    $start_time = (int)file_get_contents($progress_file);
}

$elapsed_time_sec = time() - $start_time;

// --- STATUS DETERMINATION ---
$status = 'ordered';

if ($elapsed_time_sec < $PREP_TIME_SEC) {
    $status = 'processing';
} elseif ($elapsed_time_sec < ($PREP_TIME_SEC + $MAX_DELIVERY_TIME_SEC)) {
    $status = 'shipped';
} else {
    $status = 'delivered';
    if (file_exists($progress_file)) {
        unlink($progress_file); 
    }
}

// --- LOCATION AND STATS CALCULATION ---

$rider_lat = $FARM_LAT;
$rider_lon = $FARM_LON;
$distance_remaining = $TOTAL_ROUTE_DISTANCE;
$eta_minutes = 10; 
$current_speed = 0;
$route_coords = []; 

if ($status === 'shipped') {
    $ride_time_elapsed = $elapsed_time_sec - $PREP_TIME_SEC;
    $progress_factor = min(1, $ride_time_elapsed / $MAX_DELIVERY_TIME_SEC);
    $distance_covered = $TOTAL_ROUTE_DISTANCE * $progress_factor;
    $cumulative_distance = 0;
    
    $found_segment = false;
    
    for ($i = 0; $i < count($ROUTE_WAYPOINTS) - 1; $i++) {
        $start = $ROUTE_WAYPOINTS[$i];
        $end = $ROUTE_WAYPOINTS[$i+1];
        $segment_distance = calculate_distance($start['lat'], $start['lon'], $end['lat'], $end['lon']);
        
        $route_coords[] = ['lat' => $start['lat'], 'lon' => $start['lon']];
        
        if ($cumulative_distance + $segment_distance >= $distance_covered) {
            $distance_into_segment = $distance_covered - $cumulative_distance;
            $segment_progress = $distance_into_segment / $segment_distance;

            $rider_lat = $start['lat'] + ($end['lat'] - $start['lat']) * $segment_progress;
            $rider_lon = $start['lon'] + ($end['lon'] - $start['lon']) * $segment_progress;
            
            $route_coords[] = ['lat' => $rider_lat, 'lon' => $rider_lon];

            $found_segment = true;
            break;
        }
        
        $cumulative_distance += $segment_distance;
    }

    if (!$found_segment) {
        $rider_lat = $C_LAT;
        $rider_lon = $C_LON;
        $route_coords = $ROUTE_WAYPOINTS;
    }

    $distance_remaining = $TOTAL_ROUTE_DISTANCE - $distance_covered;
    if ($distance_remaining < 0) $distance_remaining = 0;

    $base_eta_minutes = ($distance_remaining / $AVG_SPEED_KPH) * 60;
    
    if ($distance_remaining > 0) {
        $eta_minutes = max(1, min(10, $base_eta_minutes)); 
    } else {
        $eta_minutes = 0;
    }
    
    $current_speed = $AVG_SPEED_KPH * (0.9 + 0.2 * sin($elapsed_time_sec / 10)); 
}

$data = [
    'order_id' => $order_id,
    'status' => $status, 
    'latitude' => $rider_lat,
    'longitude' => $rider_lon,
    'distance' => $distance_remaining, 
    'total_distance' => $TOTAL_ROUTE_DISTANCE, 
    'speed' => $current_speed, 
    'eta' => $eta_minutes, 
    'route_coords' => $route_coords 
];


echo json_encode($data);

function calculate_distance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}
?>