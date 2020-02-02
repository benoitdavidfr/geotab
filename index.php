<?php
/*PhpDoc:
name: index.php
title: index.php - accueil de geotab
doc: |
journal: |
  2/2/2020:
    ajout en béta de la possibilité de deviner le système de coordonnées, voir guesscrs.inc.php
    doc à compléter sur cette fonctionnalité !
  1/2/2020:
    création
*/
use Michelf\MarkdownExtra;

function randomFileName(): string {
  return 'file'.rand(1000, 9999);
}

echo "<!DOCTYPE HTML><html><head><meta charset='UTF-8'><title>geotab</title></head><body>\n";

// première utilisation OU confirmation de suppression
if (!isset($_POST['input']) && (!isset($_GET['action']) || in_array($_GET['action'], ['confirmedDelete','input']))) {
  if (isset($_GET['action']) && ($_GET['action']=='confirmedDelete')) {
    unlink(__DIR__."/$_GET[file]");
    echo "<b>Fichier détruit</b></p>\n";
  }
  echo <<<EOT
  Copiez le contenu du fichier dans le cadre ci-dessous ou <a href='?action=doc'>affichez la doc</a> :<br>
  <form method='post'><table border=1>
  <tr><td><textarea name='input' rows=20 cols=130></textarea></form></td></tr>
  <tr><td><input type='submit'></td></tr>
  </table></form>

EOT;
  die();
}

// Affichage de la doc
if (isset($_GET['action']) && ($_GET['action']=='doc')) {
  require_once __DIR__."/../../markdown/markdown/PHPMarkdownLib1.8.0/Michelf/MarkdownExtra.inc.php";
  $doc = <<<EOT
# Documentation
L'objectif de cette application est de convertir les coordonnées projetées contenues dans un fichier tabulaire (Excel, ODS)
en coordonnées géographiques en degrés décimaux.

Si vous savez que les coordonnées sont définies en Lambert 93 alors :

  * copiez le contenu du fichier du tableur,
  * collez le dans le formulaire de la page par défaut,
  * réalisez la conversion en utilisant 'Convertir (x,y) en Lambert93 -> (lon,lat) en RGF93',
  * copiez le tableau affiché,
  * collez le dans votre tableur.

Si vous ne connaissez pas le système de coordonnées ou si ce n'est pas Lambert 93 alors pour réaliser la conversion :

  * trouvez le système de cordonnées avec 'Deviner le système de coordonnées',
  * affichez la carte pour vérifier les éventuels systèmes de cordonnées trouvés,
  * convertissez les coordonnées projetées en coordonnées géographiques.

Dans le texte d'origine :
  
  * le séparateur est le caractère de tabulation,
  * la première ligne doit contenir les noms des champs,
  * les coordonnées projetées (par exemple Lambert93) doivent correspondre aux champs **`x`** et **`y`**,
  * les lignes pour lesquelles les champs `x` et `y` ne sont pas des nombres sont ignorées,
  * la sortie en coord. géo. reprend la première colonne qui est souvent une clé, permettant ainsi de vérifier
    qu'il n'y a pas de décalage entre lignes,
  * les coordonnées géographiques sont fournies dans des champs **`lon`** et **`lat`**
    et sont affichées avec 6 décimales ce qui correspond à une résolution meilleure que le mètre.

Attention :

  * seuls sont gérés les [CRS officiels](https://www.legifrance.gouv.fr/eli/arrete/2019/3/5/TRED1803160A/jo/texte)
    des territoires habités français cad hors Terres Australes et Cliperton
    
Le code source de l'application est disponible
sur [https://github.com/benoitdavidfr/geotab](https://github.com/benoitdavidfr/geotab).

Version du 2/2/2020.

EOT;
  echo MarkdownExtra::defaultTransform($doc);
  if (isset($_GET['file']))
    echo "<a href='?file=$_GET[file]&amp;action=showAsTable'><b>Retour au menu</b>";
  else
    echo "<a href='?action=input'><b>Retour au formulaire</b>";
  die();
}

if (isset($_POST['input'])) {  // lecture du fichier en entrée et création du fichier
  $input = $_POST['input'];
  $fname = randomFileName();
  file_put_contents(__DIR__."/$fname", $input);
  $action = 'showAsTable'; // action par défaut
}
elseif (isset($_GET['file']) && isset($_GET['action'])) { // ou récupération du nom du fichier
  $fname = $_GET['file'];
  $action = $_GET['action'];
}
else { // ou erreur
  die("Erreur ligne ".__LINE__);
}

// Actions

if ($action =='delete') {
  echo "<a href='?file=$fname&amp;action=confirmedDelete'>Confirmer la suppression du fichier</a>";
  die();
}


{ // Menu
  echo <<<EOT
  <ul>
  <li><a href='?file=$fname&amp;action=showAsTable'>Afficher le fichier comme table</a></li>
  <li><a href='?file=$fname&amp;action=showAsText'>Afficher le fichier comme texte brut</a></li>
  <li><a href='?file=$fname&amp;action=guessCrs'>Deviner le système de coordonnées (béta)</a></li>
  <li><a href='map.php?file=$fname&crs=IGNF:LAMB93'>Afficher le fichier en Lambert93 sous la forme d'une carte</a></li>
  <li><a href='?file=$fname&amp;action=L93toGeo'>Convertir (x,y) en Lambert93 -> (lon,lat) en RGF93</a></li>
  <li><a href='?file=$fname&amp;action=delete'>Supprimer le fichier</a></li>
  <li><a href='?file=$fname&amp;action=doc'>Documentation</a></li>
  </ul>
EOT;
}

if ($action =='showAsTable') {
  if (!($file = fopen(__DIR__."/$fname",'r')))
    die("Erreur ouverture du fichier $fname");

  $header = fgetcsv($file, 1024, "\t", '"');
  if (!$header) {
    die("Erreur de lecture de la première ligne qui doit être une liste de champs séparés par le caractère tabulation");
  }
  echo "<table border=1>\n";
  echo "<th>",implode('</th><th>', $header), "</th>\n";
  while ($record = fgetcsv($file, 1024, "\t", '"')) {
    echo "<tr><td>",implode('</td><td>', $record), "</td></tr>\n";
    foreach ($header as $i => $k)
      $rec[$k] = $record[$i];
  }
  echo "</table>\n";
  die();
}

if ($action =='showAsText') {
  echo "<pre>",file_get_contents(__DIR__."/$fname"),"</pre";
  die();
}

if ($action =='guessCrs') {
  require_once __DIR__.'/guesscrs.inc.php';
  $features = [];
  $bbox = []; // bbox des features dans le sys coord

  if (!($file = fopen(__DIR__."/$fname",'r')))
    die("Erreur ouverture du fichier $fname");

  $header = fgetcsv($file, 1024, "\t", '"');
  if (!$header)
    die("Erreur de lecture de la première ligne qui doit être une liste de champs séparés par le caractère tabulation");
  while ($record = fgetcsv($file, 1024, "\t", '"')) {
    foreach ($header as $i => $k)
      $rec[$k] = $record[$i];
    if (isset($rec['x']) && is_numeric($rec['x']) && isset($rec['y']) && is_numeric($rec['y'])) {
      //echo "code=$rec[code], x=$rec[x], y=$rec[y] -> ";
      //printf("%.6f, %.6f<br>\n", $geo[0], $geo[1]);
      //printf("<tr><td>%s</td><td>%.6f</td><td>%.6f</td>", $record[0], $geo[0], $geo[1]);
      $features[] = [
        'type'=> 'Feature',
        'properties'=> $rec,
        'geometry'=> [
          'type'=> 'Point',
          'coordinates'=> [$rec['x'], $rec['y']],
        ],
      ];
      if (!$bbox)
        $bbox = [$rec['x'], $rec['y'], $rec['x'], $rec['y']];
      else
        $bbox = [min($bbox[0],$rec['x']), min($bbox[1],$rec['y']), max($bbox[2],$rec['x']), max($bbox[3],$rec['y'])];
    }
  }
  if (!$features)
    die("Erreur aucun objet géographique défini");
  $guess = GuessCrs::guess(['type'=>'FeatureCollection', 'features'=>$features]);
  echo "<h2>Proportion des coordonnées compatibles avec les CRS testés dans les différentes régions</h2><ul>\n";
  foreach ($guess as $codeRegion => $region) {
    echo "<li>$region[name] ($codeRegion)<ul>\n";
    foreach ($region['crs'] as $codeCrs => $crs) {
      printf("<li>%s : %.0f%%", $crs['name'], $crs['proportion']*100);
      if ($crs['proportion'] <> 0) {
        $crs = CRS::create($codeCrs);
        $sw = $crs->geo([$bbox[0], $bbox[1]]);
        $ne = $crs->geo([$bbox[2], $bbox[3]]);
        echo " <a href='map.php?file=$fname&amp;crs=$codeCrs&amp;bbox=$sw[0],$sw[1],$ne[0],$ne[1]'>carte</a>,\n";
        echo " <a href='?file=$fname&amp;action=CrsToGeo&amp;crs=$codeCrs'>convertir en coord. géo.</a>, \n";
        echo " <a href='geojson.php?file=$fname&amp;crs=$codeCrs'>afficher en GeoJSON</a>, \n";
      }
      echo "</li>\n";
    }
    echo "</ul></li>\n";
  }
  echo "</ul>\n";
  echo '<pre>',json_encode($guess, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),"</pre>\n";
  die();
}

if ($action =='L93toGeo') {
  require_once __DIR__.'/../../geovect/coordsys/light.inc.php';

  echo "<h2>Conversion des champs (x,y) définis Lambert93 en champs (lon,lat) en RGF93 en degrés décimaux</h2>\n";
  if (!($file = fopen(__DIR__."/$fname",'r')))
    die("Erreur ouverture du fichier $fname");

  $header = fgetcsv($file, 1024, "\t", '"');
  echo "<table border=1><th>$header[0]</th><th>lon</th><th>lat</th>\n";
  while ($record = fgetcsv($file, 1024, "\t", '"')) {
    foreach ($header as $i => $k)
      $rec[$k] = $record[$i];
    if (isset($rec['x']) && is_numeric($rec['x']) && isset($rec['y']) && is_numeric($rec['y'])) {
      //echo "code=$rec[code], x=$rec[x], y=$rec[y] -> ";
      $geo = Lambert93::geo([$rec['x'], $rec['y']]);
      //printf("%.6f, %.6f<br>\n", $geo[0], $geo[1]);
      printf("<tr><td>%s</td><td>%.6f</td><td>%.6f</td>", $record[0], $geo[0], $geo[1]);
    }
  }
  fclose($file);
  echo "</table>\n";
  die();
}

if ($action =='CrsToGeo') {
  require_once __DIR__.'/../../geovect/coordsys/full.inc.php';

  if (!isset($_GET['crs']))
    die("Erreur paramètre crs absent");
  $crs = CRS::create($_GET['crs']);

  echo "<h2>Conversion des champs (x,y) définis $_GET[crs] en champs (lon,lat) en degrés décimaux</h2>\n";
  if (!($file = fopen(__DIR__."/$fname",'r')))
    die("Erreur ouverture du fichier $fname");

  $header = fgetcsv($file, 1024, "\t", '"');
  echo "<table border=1><th>$header[0]</th><th>lon</th><th>lat</th>\n";
  while ($record = fgetcsv($file, 1024, "\t", '"')) {
    foreach ($header as $i => $k)
      $rec[$k] = $record[$i];
    if (isset($rec['x']) && is_numeric($rec['x']) && isset($rec['y']) && is_numeric($rec['y'])) {
      //echo "code=$rec[code], x=$rec[x], y=$rec[y] -> ";
      $geo = $crs->geo([$rec['x'], $rec['y']]);
      //printf("%.6f, %.6f<br>\n", $geo[0], $geo[1]);
      printf("<tr><td>%s</td><td>%.6f</td><td>%.6f</td>", $record[0], $geo[0], $geo[1]);
    }
  }
  fclose($file);
  echo "</table>\n";
  die();
}

die("Erreur action '$action' inconnue");