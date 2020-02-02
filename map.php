<?php
/*PhpDoc:
name: map.php
title: map.php - Dessine une carte
doc: |
  Prend en paramètres:
    - file: le nom du fichier à afficher
    - bbox rectangle englobant [ lonmin,latmin,lonmax,latmax ] à afficher (optionnel)
journal: |
  1/2/2020:
    creation
*/
if (!isset($_GET['file']))
  die("Erreur paramètre file obligatoire");
if (!isset($_GET['crs']))
  die("Erreur paramètre crs obligatoire");

function level(array $bbox): int {
  $dll = max($bbox[2]-$bbox[0], ($bbox[3]-$bbox[1])/cos(($bbox[2]+$bbox[0])/2/180*pi()));
  if ($dll < 1e-3)
    $level = 18;
  elseif ($dll < 1e-2)
    $level = 17;
  elseif ($dll < 2e-2)
    $level = 16;
  elseif ($dll < 5e-2)
    $level = 15; // ok
  elseif ($dll < 0.1)
    $level = 14;
  elseif ($dll < 0.2)
    $level = 13; // ok
  elseif ($dll < 0.5)
    $level = 11;
  elseif ($dll < 1)
    $level = 10; // ok
  elseif ($dll < 2)
    $level = 9;
  elseif ($dll < 5)
    $level = 8;
  elseif ($dll < 10)
    $level = 6;
  elseif ($dll < 20)
    $level = 5;
  elseif ($dll < 40)
    $level = 4;
  else
    $level = 3;
  //echo "dll=$dll -> level=$level<br>\n";
  return $level;
}

if (isset($_GET['bbox'])) {
  $bbox = explode(',', $_GET['bbox']);
  //print_r($bbox); die();
  $lon = ($bbox[0]+$bbox[2])/2;
  $lat = ($bbox[1]+$bbox[3])/2;
  $level = level($bbox); // variable utilisé dans le code JavaScript pour définir la vue
}
elseif (isset($_GET['lon']) && isset($_GET['lat']) && isset($_GET['level'])) {
  $lon = floatval($_GET['lon']);
  $lat = floatval($_GET['lat']);
  $level = intval($_GET['level']); // variable utilisé dans le code JavaScript pour définir la vue
}
else  {
  $lon = 1;
  $lat = 45;
  $level = 7; // variable utilisé dans le code JavaScript pour définir la vue
}
$center = json_encode([$lat, $lon]); // variable utilisé dans le code JavaScript pour définir la vue

//echo "<pre>"; print_r($_SERVER); die;
$path = dirname($_SERVER['PHP_SELF']);
$pointspath = "$path/geojson.php?file=$_GET[file]&crs=$_GET[crs]"; // variable utilisée dans le code JS
?>

<!DOCTYPE HTML><html>
  <!-- carte simple utilisant les clés choisirgeoportail -->
  <!-- code utilisant les variables Php $center $level $pointspath $cvhullpath -->
  <head>
    <title>carte</title>
    <meta charset="UTF-8">
    <!-- meta nécessaire pour le mobile -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <!-- styles nécessaires pour le mobile -->
    <link rel="stylesheet" href="https://visu.gexplor.fr/viewer.css">
    <!-- styles et src de Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.0/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.6/dist/leaflet.js"></script>
    <!-- plug-in d'appel des GeoJSON en AJAX -->
    <script src='lib/leaflet/leaflet-ajax.js'></script>
    <!-- Include the Control.Coordinates plugin -->
    <link rel='stylesheet' href='lib/leaflet/Control.Coordinates.css'>
    <script src='lib/leaflet/Control.Coordinates.js'></script>
    <!-- plug-in Overlapping Marker Spiderfier for Leaflet
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OverlappingMarkerSpiderfier-Leaflet/0.2.6/oms.min.js"></script>.
    -->
  </head>
  <body>
    <div id="map" style="height: 100%; width: 100%"></div>
    <script>
var map = L.map('map').setView(<?php echo $center; ?>, <?php echo $level; ?>); // view pour la zone
L.control.scale({position:'bottomleft', metric:true, imperial:false}).addTo(map);

// activation du plug-in Control.Coordinates
var c = new L.Control.Coordinates();
c.addTo(map);
map.on('click', function(e) { c.setCoordinates(e); });

// activation du plug-in Control.Coordinates
var c = new L.Control.Coordinates();
c.addTo(map);
map.on('click', function(e) { c.setCoordinates(e); });

var wmtsurl = 'https://wxs.ign.fr/choisirgeoportail/geoportail/wmts?'
            + 'service=WMTS&version=1.0.0&request=GetTile&tilematrixSet=PM&height=256&width=256&'
            + 'tilematrix={z}&tilecol={x}&tilerow={y}';
var attrIGN = "&copy; <a href='http://www.ign.fr'>IGN</a>";

var onEachFeature = function (feature, layer) {
  //layer.bindPopup('<pre>'+JSON.stringify(feature,null,' ')+'</pre>');
  if (feature.href)
    href = feature.href;
  else
    href = '';
  layer.bindPopup(href+'<pre>'+JSON.stringify(feature.properties,null,' ')+'</pre>');
  if (feature.properties.Nom)
    layer.bindTooltip(feature.properties.Nom);
};

var baseLayers = {
  "Plan IGN V2" : new L.TileLayer(
      wmtsurl + '&layer=GEOGRAPHICALGRIDSYSTEMS.PLANIGNV2&format=image/png&style=normal',
      {"format":"image/png","minZoom":0,"maxZoom":18,"attribution":attrIGN}
  ),
  "Plan IGN V1" : new L.TileLayer(
      wmtsurl + '&layer=GEOGRAPHICALGRIDSYSTEMS.PLANIGN&format=image/jpeg&style=normal',
      {"format":"image/jpeg","minZoom":0,"maxZoom":18,"attribution":attrIGN}
  ),
  "ScanExpress" : new L.TileLayer(
      wmtsurl + '&layer=GEOGRAPHICALGRIDSYSTEMS.MAPS.SCAN-EXPRESS.STANDARD&format=image/jpeg&style=normal',
      {"format":"image/jpeg","minZoom":0,"maxZoom":18,"attribution":attrIGN}
  ),
  "Cartes IGN classiques" : new L.TileLayer(
      wmtsurl + '&layer=GEOGRAPHICALGRIDSYSTEMS.MAPS&format=image/jpeg&style=normal',
      {"format":"image/jpeg","minZoom":0,"maxZoom":18,"attribution":attrIGN}
  ),
  "OSM" : new L.TileLayer(
      'http://{s}.tile.osm.org/{z}/{x}/{y}.png',
      { "format":"image/png", "minZoom":0,"maxZoom":19, "detectRetina": false,
        attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'}
   ),
   "Ortho-Photos" : new L.TileLayer(
      wmtsurl + '&layer=ORTHOIMAGERY.ORTHOPHOTOS&format=image/jpeg&style=normal',
      {"format":"image/jpeg" ,"minZoom":0, "maxZoom":20, "attribution":attrIGN}
  ),
  "Altitude" : new L.TileLayer(
      wmtsurl + '&layer=ELEVATION.SLOPES&format=image/jpeg&style=normal',
      {"format":"image/jpeg", "minZoom":6, "maxZoom":14, "attribution":attrIGN}
  ),
};

var overlays = {
  "Hydrographie" : new L.TileLayer(
    'http://igngp.geoapi.fr/tile.php/hydrographie/{z}/{x}/{y}.png',
    { format: 'image/png', minZoom: 6, maxZoom: 18, detectRetina: true,
      attribution: attrIGN
    }
  ),
  "Parcelles cadastrales (orange)" : new L.TileLayer(
      wmtsurl + '&layer=CADASTRALPARCELS.PARCELS&format=image/png&style=bdparcellaire_o',
      {"format":"image/png","minZoom":0,"maxZoom":20,"attribution":attrIGN}
  ),
  "BD Uni j+1" : new L.TileLayer(
      wmtsurl + '&layer=GEOGRAPHICALGRIDSYSTEMS.MAPS.BDUNI.J1&format=image/png&style=normal',
      {"format":"image/png","minZoom":0,"maxZoom":20,"attribution":attrIGN}
  ),
  "Fichier" : new L.GeoJSON.AJAX(<?php echo "'$pointspath'"; ?>, {
    minZoom: 0, maxZoom: 21, onEachFeature: onEachFeature
  }),
  "Dénominations géographiques" : new L.TileLayer(
    'http://igngp.geoapi.fr/tile.php/toponymes/{z}/{x}/{y}.png',
    { format: 'image/png', minZoom: 6, maxZoom: 18, detectRetina: false,
      attribution: attrIGN
    }
  ),
};
      
map.addLayer(baseLayers["Plan IGN V1"]);
map.addLayer(overlays["Fichier"]);

L.control.layers(baseLayers, overlays).addTo(map);

      </script>
    </body>
</html>
