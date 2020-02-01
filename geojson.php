<?php
/*PhpDoc:
name: geojson.php
title: geojson.php - Expose en GeoJSON le fichier

*/
require_once __DIR__.'/../../geovect/coordsys/light.inc.php';

$features = [];

if (!isset($_GET['file'])) {
  header('HTTP/1.1 404 Not Found');
  die("Erreur paramètre file absent");
}
if (!($file = fopen(__DIR__."/$_GET[file]",'r'))) {
  header('HTTP/1.1 404 Not Found');
  die("Erreur ouverture du fichier $_GET[file]");
}

$header = fgetcsv($file, 1024, "\t", '"');
while ($record = fgetcsv($file, 1024, "\t", '"')) {
  foreach ($header as $i => $k)
    $rec[$k] = $record[$i];
  if (isset($rec['x']) && is_numeric($rec['x'])) {
    //echo "code=$rec[code], x=$rec[x], y=$rec[y] -> ";
    $geo = Lambert93::geo([$rec['x'], $rec['y']]);
    //printf("%.6f, %.6f<br>\n", $geo[0], $geo[1]);
    //printf("<tr><td>%s</td><td>%.6f</td><td>%.6f</td>", $record[0], $geo[0], $geo[1]);
  }
  $features[] = [
    'type'=> 'Feature',
    'properties'=> $rec,
    'geometry'=> [
      'type'=> 'Point',
      'coordinates'=> $geo,
    ],
  ];
}
header('Content-Type: application/json');
echo json_encode(['type'=>'FeatureCollection', 'features'=>$features],  JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
