<?php
/*PhpDoc:
name: index.php
title: index.php - accueil de geotab
doc: |
journal: |
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
  Copiez votre fichier dans le cadre ci-dessous ou <a href='?action=doc'>affichez la doc</a> :<br>
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
L'objectif de cette application est de convertir les coordonnées Lambert93 contenues dans
un fichier tabulaire (Excel, ODS) en coordonnées géographiques en RGF93.

Pour cela:

  * copiez le contenu du fichier du tableur,
  * collez le dans le formulaire de la page par défaut,
  * réalisez la conversion,
  * copiez le tableau affiché,
  * collez le dans votre tableur.

Le séparateur est le caractère de tabulation.  
La première ligne du fichier doit contenir les noms des champs.  
Les coordonnées Lambert93 doivent être dans les champs x et y.  
Seules sont traitées les lignes pour lesquelles les champs x et y sont des nombres.  
La sortie reprend la première colonne qui est souvent une clé ce qui permet ainsi de vérifier
qu'il n'y a pas de décalage entre lignes.  
Les coordonnées géographiques sont fournies dans des champs lon et lat
et sont affichées avec 6 décimales ce qui correspond approximativement en métropole à une résolution de 10 cm.

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


// Menu
echo <<<EOT
<ul>
<li><a href='?file=$fname&amp;action=showAsTable'>Afficher le fichier comme table</a></li>
<li><a href='?file=$fname&amp;action=showAsText'>Afficher le fichier comme texte brut</a></li>
<li><a href='?file=$fname&amp;action=L93toGeo'>Convertir (x,y) en Lambert93 en (lon,lat) en RGF93</a></li>
<li><a href='?file=$fname&amp;action=delete'>Supprimer le fichier</a></li>
<li><a href='?file=$fname&amp;action=doc'>Documentation</a></li>
</ul>
EOT;

if ($action =='showAsTable') {
  if (!($file = fopen(__DIR__."/$fname",'r')))
    die("Erreur ouverture du fichier $fname");

  $header = fgetcsv($file, 1024, "\t", '"');
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
    if (isset($rec['x']) && is_numeric($rec['x'])) {
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

