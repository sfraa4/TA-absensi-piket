<?php
$base_url = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights/';
$models = [
    'ssd_mobilenetv1_model-weights_manifest.json',
    'ssd_mobilenetv1_model-shard1',
    'ssd_mobilenetv1_model-shard2',
    'face_landmark_68_model-weights_manifest.json',
    'face_landmark_68_model-shard1',
    'face_recognition_model-weights_manifest.json',
    'face_recognition_model-shard1',
    'face_recognition_model-shard2',
    'tiny_face_detector_model-weights_manifest.json',
    'tiny_face_detector_model-shard1'
];

$dir = __DIR__ . '/assets/models/';
if (!is_dir($dir)) mkdir($dir, 0777, true);

foreach ($models as $file) {
    echo "Downloading $file...\n";
    $content = file_get_contents($base_url . $file);
    file_put_contents($dir . $file, $content);
}

echo "Downloading face-api.min.js...\n";
$js = file_get_contents('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js');
file_put_contents(__DIR__ . '/assets/js/face-api.min.js', $js);

echo "Done.";
