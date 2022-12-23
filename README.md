### Introduction

XDT signifie XML Dom Document Traversal.

C'est un petit package qui permet de gérer facilement les fichiers XML ou les chaînes de caractères XML en quelques lignes de codes.

A l'instar de XPATH, XDT est un outil pour traverser un document XML avec de simple sélecteurs CSS. XDT prend en compte les sélecteurs CSS et va au delà en adaptant les pseudo-sélecteurs personnaliser, comme: first, last, nth, eq. 

XDT comporte de nombreuses méthodes pour créer ou pour modifier un contenu XML.

# Xpower.md

### Prérequis
Aucune dépendance

### Installation
Avec composer

`$ composer require clicalmani/xpower`

### Utilisation

```php
<?php
# Une chaîne avec une structure XML 
$chaine = <<<LIVRES
<livres>
	<livre index="1">
		<titre>Kirikou et la sorcières maudite</titre>
		<date>23 Janvier 1993</date>
		<auteur>Jean Pierre Bemba</auteur>
		<edition>Boulevard</edition>
	</livre>
	<livre index="2">
		<titre>Dernière aventure de Simbad</titre>
		<date>23 Janvier 1990</date>
		<auteur>Roland Dubois</auteur>
		<edition>Marimard</edition>
	</livre>
</livres>
LIVRES;

$xdt = new Clicalmani\XPower\XDT;   // Créer une instance de XDT
$xdt->load($chaine, true, true);    // Charger XML comme une chaîne

// Obtenir la racine du document XML
print ($xdt->getDocumentRootElement()->name()); // Resultat: livres

// Obtenir les informations concernant le premier livre publié
$obj = $xdt->select('livre[index="1"]');	 // Il est également possible d'invoquer la méthode first(): $xdt->select('livre')->first();
print ($obj->children('titre')->val());          // Titre du livre: Kirikou ...

print ($obj->children('auteur')->val());         // Auteur du livre: Jean Pierre

print ($obj->children('date')->val());         // Date publication: 23 Janvier

print ($obj->children('edition')->val());         // Edition: Boulevard


// Modifier l'édition du livre 
$obj->children('edition')->val('Jean Gautier');  // Affecter le nom du nouveau editeur
$xdt->save();                                    // Enregistrer les modifications à la chaîne

$obj->toggleClass('en-stock');                         // Basculer la classe en-stock à l'élement sélectionné
$obj->insertAfter($xdt->select('livre[index="2"]'));   // Interchanger les positions des livres
$xdt->save();

print ('<pre>');
print (htmlentities($xdt->getDocumentRootElement()->html())); // Afficher la nouvelle structure
print ('</pre>');

/** Nous allons cette fois-ci charger la chaîne à partir d'un fichier XML contenant la même structure **/
$xdt->connect('livres.xml', true, true);    // L'extension .xml peut être omise
?>
```

### Documentation

> Voir doc
