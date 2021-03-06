<?php
/*PhpDoc:
name: guesscrs.inc.php
title: guesscrs.inc.php - deviner le système de coordonnées projeté d'un ensemble de coordonnées
doc: |
  L'idée est de définir un ensemble de boites correspondant aux CRS les plus utilisés pour les zones géographiques
  les plus souvent rencontrées et de tester si les coordonnées passées sont dans une des boites.
  Cette première version est restreinte à:
    - la liste des CRS officiels des territoires habités français cad hors Terres Australes et Cliperton
    - les CRS recommandés pour l'Europe
    - le CRS périmé Lambert II
journal: |
  4/2/2020:
    Ajout du test geoInCrsLimits()
  2/2/2020:
    création
*/
require_once __DIR__.'/../../geovect/coordsys/full.inc.php';

class GuessCrs {
  static $geoRegions = [ // liste des régions structurée selon le schéma ci-dessous
    /*
      [ codeRegion => [ // code ISO 3166-1
          'name'=> nom de la région,
          'limits'=> ['westlimit'=> latitude, 'southlimit'=> longitude, 'eastlimit'=> latitude, 'northlimit'=> longitude],
          'crs'=> [
            codeCrs => [
              'name'=> nom du système de coordonnées,
              'westlimit'=> latitude?, 'southlimit'=> longitude?, 'eastlimit'=> latitude?, 'northlimit'=> longitude?
            ]
          ]
        ]
      ]
    */
    'FX'=> [
      'name'=> "France métropolitaine",
      'limits'=> [ 'westlimit'=> -5.16, 'southlimit'=> 41.33, 'eastlimit'=> 9.57, 'northlimit'=> 51.09 ],
      'crs'=> [ // liste de CRS potentiels en utilisant le code de crsregistre.yaml avec bbox correspondant
        'IGNF:LAMB93'=> [ 'name'=> "Lambert93", 'westlimit'=> -5.5, 'southlimit'=> 41, 'eastlimit'=> 10, 'northlimit'=> 52 ],
        'IGNF:LAMBCC42'=> [ 'name'=> "Lambert CC42", 'southlimit'=> 41, 'northlimit'=> 43 ],
        'IGNF:LAMBCC43'=> [ 'name'=> "Lambert CC43", 'southlimit'=> 42, 'northlimit'=> 44 ],
        'IGNF:LAMBCC44'=> [ 'name'=> "Lambert CC44", 'southlimit'=> 43, 'northlimit'=> 45 ],
        'IGNF:LAMBCC45'=> [ 'name'=> "Lambert CC45", 'southlimit'=> 44, 'northlimit'=> 46 ],
        'IGNF:LAMBCC46'=> [ 'name'=> "Lambert CC46", 'southlimit'=> 45, 'northlimit'=> 47 ],
        'IGNF:LAMBCC47'=> [ 'name'=> "Lambert CC47", 'southlimit'=> 46, 'northlimit'=> 48 ],
        'IGNF:LAMBCC48'=> [ 'name'=> "Lambert CC48", 'southlimit'=> 47, 'northlimit'=> 49 ],
        'IGNF:LAMBCC49'=> [ 'name'=> "Lambert CC49", 'southlimit'=> 48, 'northlimit'=> 50 ],
        'IGNF:LAMBCC50'=> [ 'name'=> "Lambert CC50", 'southlimit'=> 49, 'northlimit'=> 51 ],
        'IGNF:LAMB2E'=> [ 'name'=> "Lambert II E", 'southlimit'=> 42.33, 'northlimit'=> 51.14 ],
      ],
    ],
    'GP'=> [
      'name'=> "Guadeloupe",
      'limits'=> [ 'westlimit'=> -61.81, 'southlimit'=> 15.83, 'eastlimit'=> -61.00, 'northlimit'=> 16.52 ],
      'crs'=> [
        'UTM20N-RGAF09LonLatDd'=> ['name'=> "UTM20N / RGAF09"],
      ],
    ],
    'MQ'=> [
      'name'=> "Martinique",
      'limits'=> [ 'westlimit'=> -61.24, 'southlimit'=> 14.38, 'eastlimit'=> -60.80, 'northlimit'=> 14.89 ],
      'crs'=> [
        'UTM20N-RGAF09LonLatDd'=> ['name'=> "UTM20N / RGAF09"],
      ],
    ],
    'GF'=> [
      'name'=> "Guyane",
      'limits'=> [ 'westlimit'=> -54.61, 'southlimit'=> 2.11, 'eastlimit'=> -51.63, 'northlimit'=> 5.75 ],
      'crs'=> [
        'UTM22N-RGFG95LonLatDd'=> ['name'=> "UTM22N / RGFG95"],
      ],
    ],
    'RE'=> [
      'name'=> "La Réunion",
      'limits'=> [ 'westlimit'=> 55.21, 'southlimit'=> -21.40, 'eastlimit'=> 55.84, 'northlimit'=> -20.87 ],
      'crs'=> [
        'UTM40S-RGR92LonLatDd'=> ['name'=> "UTM40S / RGR92"],
      ],
    ],
    'YT'=> [
      'name'=> "Mayotte",
      'limits'=> [ 'westlimit'=> 44.95, 'southlimit'=> -13.08, 'eastlimit'=> 45.31, 'northlimit'=> -12.58 ],
      'crs'=> [
        'UTM38S-RGM04LonLatDd'=> ['name'=> "UTM38S / RGM04"],
      ],
    ],
    'PM'=> [
      'name'=> "Saint-Pierre-et-Miquelon",
      'limits'=> [ 'westlimit'=> -56.52, 'southlimit'=> 46.74, 'eastlimit'=> -56.11, 'northlimit'=> 47.15 ],
      'crs'=> [
        'UTM21N-RGSPM06LonLatDd'=> ['name'=> "UTM21N / RGSPM06"],
      ],
    ],
    'PF'=> [
      'name'=> "Polynésie française",
      'limits'=> [ 'westlimit'=> -154.73, 'southlimit'=> -27.66, 'eastlimit'=> -134.44, 'northlimit'=> -7.86 ],
      'crs'=> [
        'UTM06S-RGPFLonLatDd'=> ['name'=> "UTM06S / RGPF"],
      ],
    ],
    'WF'=> [
      'name'=> "Îles Wallis et Futuna",
      'limits'=> [ 'westlimit'=> -178.19, 'southlimit'=> -14.37, 'eastlimit'=> -176.12, 'northlimit'=> -13.17 ],
      'crs'=> [
        'UTM01S-RGWF96LonLatDd'=> ['name'=> "UTM01S / RGWF96"],
      ],
    ],
    'NC'=> [
      'name'=> "Nouvelle-Calédonie",
      'limits'=> [ 'westlimit'=> 158.18, 'southlimit'=> -23.03, 'eastlimit'=> 168.96, 'northlimit'=> -17.90 ],
      'crs'=> [
        'LambertNouvelleCaledonie'=> ['name'=> "Lambert Nouvelle-Calédonie"],
      ],
    ],
    'EU'=> [
      'name'=> "Europe",
      'limits'=> ['westlimit'=> -16.1, 'southlimit'=> 40.18, 'eastlimit'=> 32.88, 'northlimit'=> 84.17],
      'crs'=> [
        'IGNF:ETRS89LCC'=> [ 'name'=> "ETRS89 / LCC Europe", 'southlimit'=> 27, 'northlimit'=> 71 ],
        'UTM29N-ETRS89LonLatDd'=> [ 'name'=> "ETRS89 / UTM zone 29N", 'westlimit'=> -12, 'eastlimit'=> 6 ],
        'UTM30N-ETRS89LonLatDd'=> [ 'name'=> "ETRS89 / UTM zone 30N", 'westlimit'=> -6, 'eastlimit'=> 0 ],
        'UTM31N-ETRS89LonLatDd'=> [ 'name'=> "ETRS89 / UTM zone 31N", 'westlimit'=> 0, 'eastlimit'=> 6 ],
        'UTM32N-ETRS89LonLatDd'=> [ 'name'=> "ETRS89 / UTM zone 32N", 'westlimit'=> 6, 'eastlimit'=> 12 ],
        'UTM33N-ETRS89LonLatDd'=> [ 'name'=> "ETRS89 / UTM zone 33N", 'westlimit'=> 12, 'eastlimit'=> 18 ],
      ]
    ],
  ];
  
  // retourne la liste des régions avec son code et son nom
  static function regions(): array {
    $result = [];
    foreach (self::$geoRegions as $code => $region)
      $result[$code] = $region['name'];
    return $result;
  }
  
  // prend en param. une Feature Collection GeoJSON non vide encodée en Php et retourne un array
  // [region -> ['name'-> name, 'crs' -> [codeCrs -> ['title'-> title, 'proportion'-> proportion]]]]
  static function guess(array $fcoll): array {
    $result = []; // [ codeRegion => [ codeCrs => proportion ]]
    foreach (self::$geoRegions as $codeRegion => $region) {
      $result[$codeRegion] = ['name'=> $region['name'], 'crs'=>[]];
      foreach ($region['crs'] as $codeCrs => $crsLimits) {
        $crs = CRS::create($codeCrs);
        //echo "<h2>$codeCrs</h2>\n";
        //echo "<pre>crs="; print_r($crs); echo "</pre>\n";
        $result[$codeRegion]['crs'][$codeCrs] = [
          'name'=> $crsLimits['name'],
          'proportion'=> 0,
        ];
        foreach($fcoll['features'] as $feature) {
          if ($feature['geometry']['type'] <> 'Point')
            die("Erreur ligne ".__LINE__);
          $coord = $feature['geometry']['coordinates'];
          //echo "<pre>coord="; print_r($coord); echo "</pre>\n";
          $geo = $crs->geo($coord);
          //print_r($geo);
          if (self::geoInLimits($geo, $region, $codeCrs)) {
            //echo " in $codeRegion $codeCrs<br>\n";
            $result[$codeRegion]['crs'][$codeCrs]['proportion']++;
          }
          else {
            //echo " NOT in $codeRegion $codeCrs<br>\n";
          }
        }
        if ($result[$codeRegion]['crs'][$codeCrs]['proportion'] == 0)
          unset($result[$codeRegion]['crs'][$codeCrs]);
        else
          $result[$codeRegion]['crs'][$codeCrs]['proportion'] /= count($fcoll['features']);
      }
      if (!$result[$codeRegion]['crs'])
        unset($result[$codeRegion]);
    }
    return $result;
  }
  
  // Teste si un point défini en coord. géo. dans $pt est dans les limites de la region et du crs
  static function geoInLimits(array $pt, array $region, string $codeCrs): bool {
    // teste si la longitude du point est dans l'intervalle des longitude de la région
    if (($pt[0] < $region['limits']['westlimit']) || ($pt[0] > $region['limits']['eastlimit']))
      return false;
    // teste si la latitude du point est dans l'intervalle des latitudes de la région
    if (($pt[1] < $region['limits']['southlimit']) || ($pt[1] > $region['limits']['northlimit']))
      return false;
    // si le crs défini une longitude min alors vérifie que la longitude du point est supérieure à ce min
    if (isset($region['crs'][$codeCrs]['westlimit']) && ($pt[0] < $region['crs'][$codeCrs]['westlimit']))
      return false;
    // si le crs défini une longitude max alors vérifie que la longitude du point est inférieure à ce max
    if (isset($region['crs'][$codeCrs]['eastlimit']) && ($pt[0] > $region['crs'][$codeCrs]['eastlimit']))
      return false;
    // si le crs défini une latitude min alors vérifie que la latitude du point est supérieure à ce min
    if (isset($region['crs'][$codeCrs]['southlimit']) && ($pt[1] < $region['crs'][$codeCrs]['southlimit']))
      return false;
    // si le crs défini une latitude max alors vérifie que la latitude du point est inférieure à ce max
    if (isset($region['crs'][$codeCrs]['northlimit']) && ($pt[1] > $region['crs'][$codeCrs]['northlimit']))
      return false;
    return true;
  }
  
  // Teste si un point en coord géo. est dans les limites définies pour un CRS
  // cad s'il est correct pour au moins une région pour laquelle ce CRS est défini 
  static function geoInCrsLimits(array $pt, string $codeCrs): bool {
    foreach (self::$geoRegions as $region) {
      if (isset($region['crs'][$codeCrs])) {
        if (self::geoInLimits($pt, $region, $codeCrs))
          return true;
      }
    }
    return false;
  }
  
  // Teste si le point en coord. géo. est dans au moins une des régions
  // Renvoie alors le code de la région ou '' si aucune région ne correspond
  static function geoInRegionLimits(array $pt): string {
    foreach (self::$geoRegions as $codeRegion => $region) {
      // teste si la longitude du point est dans l'intervalle des longitudes de la région
      if (($pt[0] >= $region['limits']['westlimit']) && ($pt[0] <= $region['limits']['eastlimit'])
      // teste si la latitude du point est dans l'intervalle des latitudes de la région
        && ($pt[1] >= $region['limits']['southlimit']) && ($pt[1] <= $region['limits']['northlimit']))
        return $codeRegion;
    }
    return '';
  }
};


    
