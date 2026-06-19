<?php
$jsonStr = file_get_contents('api_response.json');
$data = json_decode($jsonStr, true);

$patientKeys = [];
$roomKeys = [];
$bedKeys = [];

if (isset($data['data']) && is_array($data['data'])) {
    foreach ($data['data'] as $floor) {
        foreach ($floor['wings'] ?? [] as $wing) {
            foreach ($wing['rooms'] ?? [] as $room) {
                foreach (array_keys($room) as $k) {
                    $roomKeys[$k] = true;
                }
                foreach ($room['beds'] ?? [] as $bed) {
                    foreach (array_keys($bed) as $k) {
                        $bedKeys[$k] = true;
                    }
                    if (isset($bed['patient']) && is_array($bed['patient'])) {
                        foreach (array_keys($bed['patient']) as $k) {
                            $patientKeys[$k] = true;
                        }
                    }
                }
            }
        }
    }
}

echo "Room keys: " . implode(', ', array_keys($roomKeys)) . "\n";
echo "Bed keys: " . implode(', ', array_keys($bedKeys)) . "\n";
echo "Patient keys: " . implode(', ', array_keys($patientKeys)) . "\n";
