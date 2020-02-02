<?php
/*PhpDoc:
name: geojson.php
title: geojson.php - Expose en GeoJSON le fichier CSV défini en paramètre
doc: |
  le paramètre file définit le nom du fichier
  le paramètre crs définit le CRS dans lequel les coordonnées sont définies
    si ce sont des coordonnées projetées alors elles doivent être définies dans les champs x et y
    si ce sont des coordonnées géographiques alors elles doivent être définies dans les champs lon et lat
*/
require_once __DIR__.'/../../geovect/coordsys/full.inc.php';

$features = [];

if (!isset($_GET['crs'])) {
  header('HTTP/1.1 400 Bad Request');
  die("Erreur paramètre crs absent");
}
$crs = ($_GET['crs'] <> 'WGS84LonLatDd') ? CRS::create($_GET['crs']) : null;

if (!isset($_GET['file'])) {
  header('HTTP/1.1 400 Bad Request');
  die("Erreur paramètre file absent");
}
if (!($file = fopen(__DIR__."/$_GET[file]",'r'))) {
  header('HTTP/1.1 400 Bad Request');
  die("Erreur ouverture du fichier $_GET[file]");
}

$header = fgetcsv($file, 1024, "\t", '"');
if ($crs && (!in_array('x', $header) || !in_array('y', $header))) {
  header('HTTP/1.1 400 Bad Request');
  die("Erreur champ x ou y absent alors que le CRS est projeté");
}
if (!$crs && (!in_array('lon', $header) ||!in_array('lat', $header))) {
  header('HTTP/1.1 400 Bad Request');
  die("Erreur champ lon ou lat absent alors que le CRS est en coord. géo.");
}
while ($record = fgetcsv($file, 1024, "\t", '"')) {
  foreach ($header as $i => $k)
    $rec[$k] = $record[$i];
  if ($crs && isset($rec['x']) && is_numeric($rec['x']) && isset($rec['y']) && is_numeric($rec['y'])) {
    $lonlat = $crs->geo([str_replace(',','.',$rec['x']), str_replace(',','.',$rec['y'])]);
  }
  elseif (!$crs && isset($rec['lon']) && isset($rec['lat'])) {
    $lonlat = [floatval(str_replace(',','.',$rec['lon'])), floatval(str_replace(',','.',$rec['lat']))];
  }
  else
    continue;
  $features[] = [
    'type'=> 'Feature',
    'properties'=> $rec,
    'geometry'=> [
      'type'=> 'Point',
      'coordinates'=> $lonlat,
    ],
  ];
}
header('Content-Type: application/json');
echo json_encode(['type'=>'FeatureCollection', 'features'=>$features],  JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
