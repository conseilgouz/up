## 07/11/2025 - version 5.4.2

#### Modifications internes des actions

- pdf : minification des fichiers .mjs, suppression de la librairie wasm, suppression répertoire mobile (inutilisé), use Joomla JQuery

#### Modification interne

- up.php :  cleanup checkfiles
- install.php : cleanup checkfiles, clean up updated actions before installing new ones

## 06/11/2025 - version 5.4.1

#### Modifications actions

- pdf : suppression de l'option bgbtns, prise en charge du fichier custom/custom.css

#### Modifications internes des actions

- Joomla 6 : remplacement $db->getQuery(true) par $db->createQuery()

#### Modifications internes

- modification install.php : force cache clean à l'installation/mise à jour de UP
- contrôle du fichier version sur github une fois par jour si verif. github activé
- ajout de l'affichage du no de version sur les paramètres du plugin
- déplacement des variables publiques dans up.php

## 03/11/2025 - version 5.4

#### Modifications actions

- pdf : ajout de l'option bgbtns : magazine : couleur arrière-plan des boutons

#### Modification interne

- package sans les actions, qui seront téléchargées quand nécessaires à partir de github
- vérification des versions des actions et rechargement depuis github si différence
- nouveau paramètre : vérification des actions sur github

#### Modifications internes des actions

- compatibilité PHP 8.4 : définition des variables dans upAction.php
- compatibilité PHP 8.5 : deprecated curl_close, imagedestroy 
- jcontent_by_categories : compatibilité Joomla 6 : remplacement de getInstance()
- jcontent_by_subcat : compatibilité Joomla 6 : remplacement de getInstance()
- jcontent_in_content : compatibilité Joomla 6 : remplacement de getInstance()
- jcontent_info : compatibilité Joomla 6 : remplacement de getInstance()
- jcontent_list : compatibilité Joomla 6 : remplacement de getInstance()
- jcontent_metadata : compatibilité Joomla 6 : remplacement de getInstance()
- sql : suppression notice si champ vide
- media_plyr : suppression du mode debug sur le js, caché l'audio player html5

## 12/09/2025 - version 5.3.2

#### Modifications actions

- pdf_gallery : erreur de packaging

## 12/09/2025 - version 5.3.1

#### Modifications actions

- pdf_gallery : ajout de l'option flip pour affichage des pdf en mode flipbook

#### Modifications internes

- modification up.php : conflit avec Regularlabs Module Anywhere

## 01/09/2025 - version 5.3

#### Modifications actions

- facebook_timeline : bug facebook sur l'option tabs=timeline, appel library facebook version 13.0
- form-select : correctif sur les actions liées, par exemple, jmenus_list
- jmenus_list : nouveau paramètre : nohidden : ignorer les menus cachés, nouvelle valeur ##hidden## pour identifier les menus cachés
- icon : ajout aria-label pour les icônes fontawesome
- media_youtube : ajout de l'option facade : chargement d'une image au lieu de la vidéo
- website : ajout gwebsite-key à créer sur https://developers.google.com/speed/docs/insights/v5/get-started, ignore erreur google si l'image a été créée

#### Modifications internes

- compatibilité Joomla 6.x : suppression getDbo()
- compatibilité Joomla 6.x : suppression getUser()
- compatibilité Joomla 6.x : suppression ->input
- compatibilité Joomla 6.x : suppression JLoader::register
- compatibilité Joomla 6.x : suppression AbstractMenu::getInstance
- compatibilité Joomla 6.x : suppression BaseDatabaseModel::getInstance
- compatibilité Joomla 6.x : suppression Factory::getLanguage
- compatibilité Joomla 6.x : use webassets

## 08/07/2025 - version 5.2

#### Nouvelles actions

- jcontent-image
- addhtml
- mq
- upclass2style
- grid
- loadmodule
- lorem-serie
- geocode
- upfilescleaner
- get

#### Modifications actions

- lorem : nouvelle version php sans utilisation API
- jnews : new-delai selon la date de tri
- flexbox : add options : alternate & alternate-order
- flexauto : fix divers
- bg-slideshow : fix js path
- upsearch : neutralisation caractères spéciaux dans le texte retourné
- sql : ajout option overflow
- box : nouveau modèle bg-image-only
- upbtn_makefile : prise en charge des nouveautés du plugin éditeur upbtn
  - ajout filtrage sur nom et description des actions
  - bouton "Insérer shortcode ouvrant + fermant"
- lorem-flickr : API bloqué, remplace par une simulation d'emplacement du style lorem-place
- upscsscompiler : ugrade scssphp 2.0.1 et ajout option "map"
- hr, icon, listup : prise en charge des classes fonticon 6 pour Joomla 5
- markdown : update script parsedown vers 1.8
- pdf : update pdfjs 5.3.31
- tweeter-timeline : affiche uniquement un lien pour afficher la timeline

#### Modifications internes

- suppression BR lors récupération des parties de contenu séparées par {===}
- suppression des doubles espaces et sauts de ligne dans les styles pour le HEAD
- possibilité d'appeler des actions dans prefs.ini et snippets
- remplacement du nom des classes par le style correspondant pour l'option css-head de toutes les actions.

## 13/06/2025 - version 5.1.4

- update pdf/pdfjs vers version 5.3.31 (Pascal)
- fix css image-hover

## 15/03/2025 - version 5.1.3

- fix class2style

## 12/03/2025 - version 5.1.2

- fix bg-slideshow
- fix flexauto et flexbox : conflit avec bootstrap
- suppression BR lors récupération des parties de contenu séparées par {===}

## 10/01/2025 - version 5.1.1

- fix flexauto et flexbox
- correction pour JED

## 10/01/2025 - version 5.1

#### Nouvelles actions

- **media-audio** : proposer un ou plusieurs fichiers audio d'un dossier sur le serveur
- **replace** : remplacer du texte par un autre
- **file-explorer** : Explorer un dossier avec prévisualisation et téléchargement.
- **text-blink** : faire clignoter du texte
- **jmenu-metadata** : affiche les menus avec les metadonnées : index, follow, date publication, etc
- **slider-tiny** : slider
- **lorem-place** : image avec dimensions et texte

#### Modifications actions

- **sql** : ajout options variable-1 à variable-12
- **div, span** : saisie rapide classes et styles. Tous les attributs sont acceptés
- **sitemap** : modification entête XML
- **table-by-columns, table-by-rows, table-fixe** : info pour balises interdites et manquantes
- **tab** : correction css pour fond des flèches transparentes
- **jmenu-list** : ajout des mots-clé ##level## et ##image##. Fix main-tag. plus d'info sur la démo
- **osmap ** :
  - update version leaflet de 1.6 à 1.8
  - correction URL des tiles
- **bg-slideshow** : utilise la valeur de shuffle pour sélectionner un nombre d'images au hasard dans le dossier.
- **jcontent-by-categories** :
  - ajout option featured
  - suppression saut de ligne pour ##intro##
- **php** : ajout option authorized-functions pour permettre ponctuellement l'utilisation d'une fonction interdite
- **data-info, data2list, data2table** :
  - ajout options lign-filter, lign-sort et lign-max.
  - lign-select affiche toutes les lignes pour data-info
- **file-download** : ajout option file-by-date et file-max
- **upactionslist** : ajout CSS blink pour "lire la doc"
- **filter** : ajout des comparaisons equal, smaller, bigger
- **icon** : prise en charge entité HTML. Exemple: {up=&raquo;,red,2rem}
- **lorem-flickr** : reprise totale suite aux changements API
- **tabslide** : supprime effet action=hover sur mobile + max-width sur panel
- **jextensions-list** : suppression valeur par défaut pour minimal-id inutile en J4+
- **flexauto et flexbox** : réecriture des actions pour faciliter le ciblage CSS des cellules

#### Modifications internes

up.css : ajout classe badge-orange
upAction.php : function js_actualise()

---

## 01/12/2023 - version 5.0

Prête pour Joomla 5 sans plugin compatibilité

#### Modifications actions

- **file-download** : ajout option sort-order (asc|desc)
- **pdf** : ajout option zoom (merci Pascal)
- **pdf-gallery** : fix sur date fichier
- **media-plyr** : fix download pour mp4 et mp3.

## 01/07/2023 - version 3.1.1

---

- **sql** : fix case des noms de colonnes
- **osmap** : prise en charge des tuiles supprimées ou déplacées par le fournisseur
- **pdf** : possibilité d'afficher plusieurs fichier PDF correspondant à un masque. Ajout option maxi

---

## 01/11/2023 - version 3.1

---

#### Nouvelles actions

- **jcontent-metadata** : liste des articles avec les métadonnées
- **media-video** : galerie videos locale en HTMML5
- **random** : sélectionne une ou plusieurs valeurs dans une liste ou un dossier
- **snippet** : sauver et récupérer des bouts de texte

#### Modifications actions

- **addclass** : remplacement jquery par js vanilla
- **addfilehead** : ajout option filter
- **bg-slideshow** : fix css pour J4/cassiopeia
- **cache-cleaner** : ajout des options folder-cache, folder-exclude et file-mask
- **chart** : fix resize sur toutes les instances
- **csv2table** :
  - css : centrage horizontal et vertical des titres
  - bbcode sur header
- \*\*date :
  - utilisation de la nouvelle version de up_date_format
  - ajout option timezone
- **image-gallery** :
  - ajout option grid-ratio pour forcer hauteur image pour layout=grid-x-x-x
  - ajout option sort-desc pour tri alphanumérique naturel descendant des images d'un dossier
  - rotation automatique des images (iphone)
- **image-logo** : bbcode sur argument principal
- **jcontent-by-categories, jcontent-in-content, jcontent-by-subcat, jcontent-by-tags, jcategories-by-tags, jcontent-metadata** : ajout option 'content-plugin' pour prise en charge des plugins de contenu
- **jcontent-by-categories, jcontent-by-subcat, jcontent-by-tags, jcontent-in-content, jcontent-info** : prise en charge des customs fields
- **data2table** : ajout option col-list pour définir ordre des colonnes de niveau 1

- **lang** : {up lang} retourne le meilleur code langage selon lang-order
- **meteo-concept** : fix message si hors période
- **readmore** : nouvelle version
  - bouton en haut ou en bas
  - laisse apparaitre une partie du texte avec ou sans masque dégradé
- **sitemap** : fix divers
- **slider-owl** : ajout option max-height pour désactiver l'égalisation en hauteur des blocs
- **tab** : possibilité de changer d'onglet avec un lien (avec ancre) dans la page
- **table-sort** : l'option col-type permet le tri des colonnes par date
- **text-fit** : fix quote sur selecteur + path absolute pour fontfile

#### revision de toutes les actions pour

- affichage messages d'erreur selon le nouveau paramètre "développeent/production"
  - barcode
- nouvelle prise en charge des mots-clés
- compatibilité php 8.2.6
- appel API Joommla

#### Modifications internes

- methode up_date_format intègre les traitements anciennement réalisés par l'action date
- possibilité de surcharger les fichiers langages en ajoutant .custom à la fin du nom. ex: fr-FR.custom.ini

---

## 20/05/2023 - version 3.0

---

#### Nouvelles actions

- **popup**
- **masonry**
- **date**
- **data2list**
- **data2table**
- **data-info**
- **addfilehead**
- **site-stats**

#### Modifications actions

- **pdf-gallery** : ajout option label-replace
- **image-gallery** :
  - options pour proposer le téléchargement de l'image haute définition
  - l'option random est valide pour toutes les photos issues d'un dossier
  - ajout option "legend-template" pour prise en charge nouvelle version humanize
- **treeview** : ajout option icon-size pour définir la taille de l'icône en responsive
- **icon** : l'option size accepte plusieurs valeurs pour responsive
- **modal** : possibilité de parcourir le contenu de toutes les modales d'une page
- **form-select** :
  - ajout option size pour le nombre de lignes visibles
  - ajout options btn et btn-style pour valider la sélection par un bouton
  - ajout options label et label-style pour ajouter un texte au-dessus du select
  - ajout option filter
- **media-youtube** : ajout de l'option ratio pour utiliser la propriété CSS aspect-ratio (compatibilité avec cookieck)
- **sitemap** : modif entete urlset + url pour menu
- **jcontent-by-tags, jcontent-by-subcat, jcontent-by-categories, jcategories-by-tags, jcontent-in-content, jcontent-info** : fix ##intro## et ##intro,100##
- **jcontent-by-categories, popover** : ajout option filter
- **image-hover** : fix conflit css

#### Modifications internes

- new: get_db_value($select, $table, $where) pour récupérer une valeur unique dans la DB
- fix: sous-titre pour l'option aide (?)
- possibilité d'entourer l'argument d'une option par des guillemets doubles pour préserver un espace au début ou à la fin. ex: option=" texte "
- ajout fonction interne supertrim pour supprimer tous les types d'espaces
- amélioration de la méthode link_humanize
- ajout paramètre "A0=space" au plugin. Cela concerne uniquement les langues asiatiques
- suppression de l'action lorem-placeimg, redirigé vers lorem_flickr

---

## 02/01/2023 - version 2.9.2

---

- **jcategories-by-tags, jcontent-by-categories, jcontent-by-subcat, jcontent-by-tags, jcontent-info, jcontent-in-content** : fix tags-list, tags-link, intro-text
- **marquee, filter** : fix doc
- **cell** : fix oubli virgule
- **jcontent-by-tags** : ajout option current pour ne pas afficher l'article courant

---

## 23/06/2022 - version 2.9.1

---

- **csv2table** : bug nettoyage balise HTML
- **file-in-content** : fix test si timestamp
- **up jcontent-by-categories** : ajout sort-by=random (merci ManuelVoileux)
- **addcsshead** : fix si pas de contenu

---

## 15/06/2022 - version 2.9

---

#### Nouvelle action

- \*\*ajax-view
- \*\*file-in-content
- \*\*file-office-view
- \*\*scroll-indicator
- \*\*site-visit
- \*\*lorem-flickr
- \*\*gotop
- \*\*meteo-concept

#### Modifications actions

- **osmap** : update leaflet
- **jcontent-by-subcat, jcontent-by-categories, jcontent-info** : ajout mots-clés ##upnb## et ##uplist##
- **jcontent-by-tags** : le template peut être mis comme contenu
- **jcontent-info** : ajout motclé ##tags-link## pour récupérer les tags avec un lien vers la liste des articles avec le tag (Merci Deny)
- **faq & tab** : ajout option preserve-tag
- **image-gallery** :

  - création des vignettes (srcset) dans le dossier tmp pour éviter la sauvegarde par Akeeba Backup
  - ajout d'une fonction lazyload par Pascal Leconte

- **addcsshead** : syntaxe avec code css comme contenu
- **treeview** : ajout icônes fichiers
- Utilisation $primary et $secondary dans SCSS pour csv2table, toc,
- **pdf-gallery** : le template peut être mis comme contenu
- **link** : support phone et url (ex:skype) et ajout option filter (phone si mobile)
- **pdf** :
  - mode magazine directement dans la page en mode pdfjs
  - ajout option tag pour choisir la la balise du bloc principal
- \*\*gmap, media-youtube, media-vimeo, bg-video
  - ajout option RGPD pour ne pas appliquer localement la règle générale
- compatibilité date PHP8 pour folder-list, jcontent-by-catégories,, jcontent-by-subcat, jcontent-by-tags, jcontent-in-content, upsearch, jcategories-by-tags, sql
- **upactionslist** :
  - ajout trad GB et upbtn dans doc-actions.csv
  - prise en charge des sous-titres
- **readmore** : ajout options textmore-class et textless-class pour styler le bloc inline du bouton
- **modal** : fix class si label est le contenu pour url
- **upscsscompiler** : update scssphp v1.11.0 (compatibilité php 8.1)
- **upbtn-makefile** : prise en charge des sous-titres
- **slider-owl** : ajout option css-head

#### Modifications internes

- fix option debug si valeur est un array
- up.css :
  - ajout classe hidden, print-no-break
  - ajout couleurs : $darkPrimary, $palePrimary, $darkSecondary, $paleSecondary
- get_bbcode : mise en url relative des attributs src
- fonction set_locale deprecated et ajout up_date_format
- fonction trad_keyword : ajout argument $str pour remplacer %s dans le message

## 15/05/2022 - version 2.8.2

---

- **image-gallery** : test si dossier sans image
- **csv2table** : fix si justif non indiquée
- **filter** : fix retour

---

## 09/05/2022 - version 2.8.1

---

- **pdf-gallery** : ajout option popup-width et popup-height pour modifier la taille de la fenêtre modale de visualisation du PDF
- **icon ** : ajout option title (pascal)
- **php** : correction mineure

---

## 06/05/2022 - version 2.8

---

#### Nouvelle action

- **pdf-gallery**

#### Modifications actions

- ajout option css-head aux actions table-xxx (responsive)
- **slideshow-billboard** : prise en charge image avec extension en majuscule
- **page-search** : force int sur options positions
- **printer** : ajout règle cspour masquer le bouton lors appel externe à l'action
- **csv2table** : ajout option col-list et model noborder
- **filter** : fix si condition alternative non spécifiée dans contenu
- **facebook-timeline** : update sdk + ajout options options defer, asynchronous, crossorigin (par Pascal)
- **jcontent-by-categories** : ajout mot-clé ##cat-link##
- **folder-list** : prise en charge treeview + divers
- **table-by-columns, table-by-rows, table-flip** : ajout option css-head
- **php** : ajout option tag pour insertion class et style
- **upactionslist** : ajout option without-custom pour afficher infos webmaster
- **upbtn-makefile** : ajout infos webmaster
- **upprefset** : chgt nom fichier custom/info.txt en help.txt

#### Modifications internes

- option ? retourne les options et infos custom

---

## 10/02/2022 - version 2.7

---

#### Nouvelle action

- \*\*sitemap
- \*\*page-search
- \*\*website-preview
- \*\*link

#### Modifications actions

- ajout informations debug pour media-plyr, image-gallery
- **jextensions-list** : ajout option 'author-exclude' pour J4
- **markdown** : fix lecture fichier

#### Modifications actions

- ajout classes maxw[s|m]100|200|400|600|800
- badge : couleur pour les liens

---

## 31/10/2021 - version 2.6.1

---

- update upbtn/upbtn.js pour compatibilité J4
- fix upbtn-options.ini pour dernières actions
- **listup** : fix min/maj pour les noms de couleurs
- image-smartphoto

#### Modifications actions

- **file-download** : ajout messages sur analyse option principale

---

## 24/10/2021 - version 2.6

---

#### Nouvelle action

- **attr** : ajoute des attributs à la première balise du contenu
- **listup** : personnaliser les listes simples ou numérotées
- **csv-info** : récupérer une valeur dans un fichier CSV
- **cache-cleaner** : supprime tous les fichiers cache d'un type.com_content par défaut

#### Modifications actions

- **filter**
  - ajout options : return-true et return-false (pour mono-shortcode)
- **jcontent-info **
  - ajout ##catid##
  - prise en charge article courant (si dans module)
- **scroller**
  - remplacement script JS pour éviter freeze
- **countdown**
  - format des dates identique à countdown-simple
  - ajout option filtre
- **countdown-simple**
  - format des dates identique à countdown
  - prise en charge des dates par iOS
- **tab **
  - auto : valeur minimum de 999 ms
  - fix : erreur dans fichier css
- **media-youtube** : fix si tarteaucitron
- **addcodehead** : fix substitution entite HTML
- **readmore** : ajout option panel-style pour mettre en évidence le contenu
- **upbtn-makefile** : ajout option without-custom pour création zip UP

#### Compatibilité J4

- **slideshow-billboard** : fix jquery (merci Pascal)
- **jcontent-by-categories** : fix toutes les catégories
- **jcontent-list** : fix toutes les catégories
- **upsearch** : fix toutes les catégories

#### Modifications internes

- ajout script assets/lib/simple_html_dom.php (pour listup)

---

## 17/06/2021 - version 2.5.2

---

- **jcontent-info** : ajout ##navpath## et ##catpath##

---

## 15/06/2021 - version 2.5.1

---

- **upscsscompiler** : fix export css correction version zip
- **jcontent-info** : ajout mot-clé ##cat-id## et utilisation dans modules

---

## 04/06/2021 - version 2.5

---

#### Nouvelle action

- **popover**
- **color**
- **jcontent-by-subcat**
- **jcontent-info**
- **treeview**
- **folder-list**
- **upsearch**

#### Modifications actions

- **website** : fix. suppr \ en fin nom, prise en charge query dans url
- **upactionslist** :
  - option exclude-prefix
  - demopage=0 affiche le bandeau bleu mais pas de lien vers une démo
  - possibilité de surcharger dico.ini dans custom (general et actions)
- **upscsscompiler** :
  - creation assets/colorname.ini
  - option without-custom
  - non exécution par upscsscompiler=0
- **upbtn-makefile** :
  - renommage up/options.ini en up/upbtn-options.ini
  - possibilité de surcharger up/upbtn-options.ini dans custom
- **faq**
  - ajout option css-head
  - modification nom des classes pour identifier chaque onglet
- \*\*box
  - ajout mot-clé\*\* : ##link## ##target## ##action-text##
  - fix : autoriser les shortcodes de LM-Prism
- **sql**
  - retour valeur brute pour count, min, max, sum, avg

#### Modifications generales

- MAJ action suite au déplacement \_variables.scss
- ctrl_options : option principale accepte la valeur de prefs.ini [options]
- deplacement assets/\_variables.scss vers assets/custom/\_variables.scss
- renommage actions/ACTION/up/options.ini en upbtn-options.ini

#### Modifications internes

- script install : on conserve le fichier perso "up/asset/\_variables.scss"
- add set_locale() et maj actions concernées

---

## 07/04/2021 - version 2.4

---

Prise en charge des services pour le script RGPD TarteAuCitron

- media-youtube - option play-on-visible non prise en charge
- media-vimeo
- bg-video - ajout option height pour forcer hauteur video youtube et vimeo

#### Nouvelle action

- \*\*text-typewriter

#### Modifications actions

- **jcontent-by-categories** : ajout mot-clé ##content##
- **lang** : réecriture du code
  - ajout option info pour connaitre la langue du navigateur client
  - seul les 2 premiers caractères du tag langue sont pris en compte (en-US => en)
- **tabslide** : update script JS (v2017 -> v2019)

#### Modifications internes

- trad_keyword($txt, $arg1, $argN) ajout variables comme fichier traduction Joomla
- ctrl_options : création de 12 valeurs pour options se terminant par -\*

---

## 12/02/2021 - version 2.3

---

#### Nouvelle action

- **mapael** : cartes SVG interactives
- **table-sort** : pour trier, filtrer et paginer une table
- **field** : affiche un custom field

#### Modifications actions

- **osmap** : fix si marqueur se termine par -icon qui implique un -shadow
- **csv2table** :
  - fix import csv (merci Eddy)
  - possibilité de style de 6 à 12 colonnes
  - suppression espaces ajoutés par TinyMCE
- **clock-gmt** : fix offset 0 (merci smlcol)
- **tabslide** : fix z-index
- **file-download** : ajout de l'option 'file-mask' pour sélectionner les fichiers d'un dossier
- **image-compare** : fix centrage poignee
- **imagemap** : possibilité de saisie du contenu (areas) en bbcode pour eviter effacement par editeur
- **jcontent-by-tags** : ajout motcles pour customFields

#### Modifications internes

- suppression espace dur par les fonctions trim($str, " \t\n\r\v\0\x0B\xA0\xC2")
- new function : get_code pour convertir un code saisi dans un shortcode
- load_file : reecriture et creation get_asset_custom. MAJ upactionslist & faq
- chgt nom get_full_url -> get_url_absolute
- chgt nom get_url -> get_url_relative
- load_css_head : $id=null pour forcer #id

---

## 10/12/2020 - version 2.2

---

#### Nouvelle action

- **countdown-simple** : Affiche un compte à rebours simple et facilement configurable
- **text-fit** : ajuste la taille d'un texte à celle du bloc qui le contient
- **chart-org** : pour réaliser un organigramme pyramidal

#### Modifications actions

- **html** : ajout saisie classes dans options principale. ex: {up html=h1.t-rouge.bg-jaune}
- **slider-owl** : fix navigationText
- **osmap** : fix pour insertion dans onglet
  - update vers leaflet 1.6.0 - utilisation CDN
- **pdf** : fix largeur popup (modif css de modal-flashy)
- **website** :
  - récupération par la méthode get_html_contents pour gestion timeout
  - ajout options timeout=10 et renew=30 (0 pour jamais)
- \*\*upbtn-makefile
  - export des fichiers vers un sous-dossier de tmp
- \*\*addScript
  - nettoyage balises P et BR.
  - conversion des entités HTML créées par les éditeurs wysiwyg

### UPBTN

- fix SC fermant avec tiret au lieu underscore

---

## 05/07/2020 - version 2.1.1

---

- **anim-aos, scroller** : suppression test chargement XML
- **php-error** : action supprimée de la version de base, disponible séparément pour les développeurs

---

## 15/06/2020 - version 2.1

---

Une version pour gérer le plugin bouton : upbtn

#### Nouvelle action

- **upbtn-makefile** : création des fichiers utilisés par le plugin bouton

#### Modifications actions

- **website** : prise en compte v5 de l'api google
- **image-random** : ajout option 'path-only'. le chemin de l'image pour utilisation par une autre action. ex: bg-image
- **slider-owl** : fix pour items=1
- **pdf** : ajout option background pour couleur fond perdu du PDF (merci Pascal)

#### Modifications internes

- up_action_infos & up_action_options : ajout param pour forcer la langue
- load_inifile : [new] contenu du fichier INI avec alerte si fichier mal structuré
- filter_ok : ajout des filtres artid, catid, menuid
  {up bg-image=images/photos/Ecureuil-rouge-eurasien.jpg | bg-attachment=fixed | filter=catid:8}
  changer fond ou header du site
- ctrl_unit : $size -> &$size. test si $size vide

---

## 31/05/2020 - version 2.0

---

#### Nouvelles actions

- **div** : saisie rapide d'un bloc DIV en wysiwyg
- **span** : saisie rapide d'un bloc SPAN en wysiwyg
- **donation** : faire un don avec Paypal

#### CSS

- ajout classe text-col-no-break (break-inside:avoid)

---

## 17/04/2020 - version 1.9.5

---

- Le webmaster peut créer un fichier "custom/help.txt" au format HTML/BBCode qui sera affiché par ? et debug

#### Nouvelles actions

- **tooltip** : info-bulles
- **media-vimeo** : affichage vidéo VIMEO (auteur: Pascal)
- **iframe** : affiche un contenu externe
- **jcat-image** : affiche l'image de sa catégorie dans un article

#### Modifications actions

- **box** : refonte complête. Possibilité de template et de multibox
- **addclass** : si selector non spécifié. Le parent est calculé par rapport à l'emplacement du shortcode
- **image-gallery** : (une suggestion de Marc)
  - création d'une galerie à partir d'images insérées entre les shortcodes
  - suppression automatique des images (srcset) obsolètes
  - ajout option shuffle-reverse pour inverser ordre des dossiers
- **file-download** :
  - file\*.zip = la dernière version du fichier
- **website** : bug sur lang
- **lorem** : strip_tags si max-words ou max-chars
- **tabslide** : largeur maxi sur mobile
- **media-youtube** : marche/arret selon visibilité vidéo à l'écran (par Pascal)
- **html** : prise en charge class & style non différenciés

#### Modifications internes

- ctrl_options :
  - prefs.ini[options] pris en charge pour only_using_options
  - possibilité prefs.ini par défaut en racine du dossier action (voir action box)
- get_bbcode : ajout a dans les balises par défaut
- up*actions_list : les actions dont le nom débute par x* sont ignorées (option privée)
- set_attr_tag : optionnel si tag commence par un underscore et pas d'attribut

### CSS

- ajout classes : u, u-hover, ud, ud-hover : underline & underline dotted

---

## 17/04/2020 - version 1.9.1

---

#### Modifications actions

- **modal** : ajout option filter (pascal) + bug overlayClose
- **file-download** : blocage extensions dangereuses et gestion icon

---

## 16/04/2020 - version 1.9

---

#### Nouvelles actions

- **form-select**

#### Modifications actions

- **image-gallery** : boutons shuffle responsives
- **jmenus_list** : option main-tag pour retour autre que liste arborescente
- **file-download** : prise en charge PDF,TXT,... Bravo Pascal

#### Modifications internes

- info_debug : changement look et option pour ajouter nom action
- trad_argument remplacé par lang
- get_attr_style accepte un nombre d'arguments variable
- get_custom_path : retourne chemin vers fichier custom s'il existe

---

## 08/04/2020 - version 1.8.2

---

#### Nouvelle action

- **note** : ajoute des commentaires visibles dans un éditeur WYSIWYG et pas sur le site
- **image-random** : affiche aléatoirement une des images d'un dossier

#### Divers

- compatibilité J4 du script d'installation (merci pascal)

---

## 30/03/2020 - version 1.8.1

---

#### Modifications actions

- **upsccscompiler** : fix force et force-filter

#### Modifications internes

- translation : scindé en trad_argument et trad_keyword

### CSS

- annulation de la possibilité (v1.8) de surcharge du fichier \_variables.scss dans le sous-dossier assets/custom
  voir article developpeur : Utiliser la feuille de style UP
- remise en service script install pour préserver assets/\_variables.scss

---

## 30/03/2020 - version 1.8

---

#### Nouvelles actions

- **file-download** : gestion téléchargements avec stats et mot de passe
- **jextensions-list** : liste des extension installées
- **jcategories-list** : liste des catégories
- **jmenus-list** : liste des menus
- **jmodules-list** : liste des modules
- **upPrefSet** : liste des prefset de tout ou partie des actions pour documentation interne
- **barcode **
- **chart** : statistiques
- **sql** : requete SQL avec mise en forme
- **image-logo** : ajoute une image ou du texte comme légende d'une image
- **printer** : propose l'impression
- **image-secure** : compliquer la récupération d'une image
- **bbcode** : saisir du code HTML dans un éditeur wysiwyg

#### Modifications actions

- \_example_simple & \_example_full_options : mise à jour pour prefset
- **flexauto** :
  - ajout options bloc-style & css-head.
  - Ajout séparateur {===}.
- **googlefont** : tag pour contenu selon son type (block ou inline)
- **upScssCompiler** : update SCSSPHP version 1.0.6
- **jcategories-by-tags** : alt défaut = src humanize
- **csv2table** : saut de ligne dans contenu CSV avec [br]
- **image-gallery** : ajout tri shuffle par Pascal
- **image-magnify** : fix class/style. reprise image-magnify pour imgzoom
- **meteo-france** : possibilité d'indiquer une ville non française
- **toc** : si item tronqué par maxlen, texte complet dans tooltip title

#### Modifications internes

- up_prefset_list : retourne la liste des prefset avec leurs options
- ctrl_options : ajout argument $optmask pour tester si une options non prévue est permise
- get_content_parts : suppression <br /> ajouté par TinyMCE + fix bug sur mi-tag
- up.php : suppression espaces durs ajouté par TinyMCE
- traduction : plus retour 1ére alternative car conflit si url avec ;id=
- prefset : prise en charge prefset comme argument option principale
- get_bbcode : permet de saisir du HTML comme argument d'options en remplacant les <> par [].
  exemple : [b class="foo"]gras\[1\][/b] -> <b class="foo">gras[1]</b>

#### CSS

- support des noms anglais pour les couleurs. ex: la class t-rouge & t-red pour du texte rouge
- possibilité de surcharge du fichier \_variables.scss dans le sous-dossier assets/custom
- !important sur les classes fg-c..
- ajout partiel \_print.scss avec classe noprint

---

## 04/01/2020 - version 1.7.2

---

#### Modifications actions

- **upActionsList** : pas d'affichage de la doc dans la liste générale si demosite=0
  Cela permet de mettre une action en test sur le site sans qu'elle soit visible.
  Elle sera visible sur la page démo qui doit être en accés restreint
- **JContent-by-categories** : fix regex sur ##intro-text,xxx##
- **anim-aos** : fix UTF lors prise en charge globale de la page
- **icon** :
  - info=2 renvoit les icons de prefs.ini à la place du shortcode, 1 dans debug
  - fix prise en charge prefset

#### Modifications internes

- get_content_parts : supprime balise fermante au début et fermante à la fin

---

## 30/12/2019 - version 1.7.1

---

- **TOC** : ajout maxlen
- **JCxxx** : les mots-clés sont encadrés par ## au lieu d'accolades. ex: ##mot##

---

## 14/12/2019 - version 1.7

---

#### Général

- possibilité d'inclure des shortcodes comme argument d'option d'un shortcode
  ex : { up readmore={up icon=plus} Ouvrir}
- possibilité de définir des jeux d'options (prefset) pour toutes les actions
- addcsshead et toutes les options base-css ou css-head
  possibilité de saisir des crochet [] en les échappant par \
  ex: { up addcsshead=\[test="foo"\] li:nth-of-type(odd)[color:red]}
- filter :
  - ajout server-host et server-ip
  - possibilité condition inverse par !server-ip:localhost
- correction bugs provoquant des notices

#### Nouvelles actions

- **TOC** : sommaire
- **counter** : compteur/décompteur animé
- **scroller** : faire défiler du contenu verticalement
- **hr** : lignes horizontales
- **jnews (jcontent-by-categories)** : les derniers articles pour présentation évoluée
- **jcontent-in-content** : un article dans un article
- **jcontent-by-tags** : les articles d'un mot-clé
- **jcategories-by-tags** : les catégories d'un mot-clé
- **php-error** : gérer les messages PHP in-situ
- **lang** : propose une alternative (texte, image, code) selon la langue visiteur

#### Modifications actions

- **php** : corrige les caractéres <> convertis par les éditeur wysiwyg
- **icon** :
  - ajout unicode et image.
  - raccourci saisie et collection dans custom/prefs.ini
  - création règle css
- **tab** :
  - ajout option css-head, espace-vertical et content_display.
  - ajout fichier SCSS et variables pour personnaliser les couleurs
  - modif CSS: contenu = width 100%
- **article-category** :
  - changement de nom : jcontent_list
  - filtrage sur catégorie sur catégorie courante
- **html** : gestion des balises auto-fermantes
- **addcodehead** : nouveau mode saisie par attribut=valeur
- **slideshow-billboard** : ajout zoom-suffix pour compatibilité avec l'action modal
- **modal** : option zoom-suffix en remplacement de la constante '-mini'
- **upActionsList** : ajout option filter
- **filter** : l'argument principal est géré comme l'option filter d'une action
- **upScssCompiler** : update vers version 0.8.4 de leafo
- **anim-aos** : ajout option once

#### Nouvelles méthodes internes

- load_js_file_body : permet de charger un fichier js à la fin du contenu de l'article avec gestion dossier custom

#### Modifications internes

- get_action_pref : ajout argument $default (utile pour timezone)
- set_attr_tag : force span si tag vide mais attribut
- filter :
  - inverser la condition avec !
  - pour period le séparateur peut être virgule ou tiret. ex: 20191220-20200103
- up_action_options : valeur par défaut, neutralisation du code HTML
- ctrl_options
  - prise en charge prefset. ordre priorité : shortcode, prefset, options
  - debug : affiche les entités html pour les valeurs d'options
  - debug : affiche la valeur retenue entre shortcode, prefset, options
  - debug et aide (?) : affiche les sections (sauf options) du prefs.ini

#### UP.CSS

- ajout .fg-auto-7 à .fg-auto-12
- ajout .ff-mono et .ff-cursive : web safe fonts
- img.left et img.right : ajout vertical-align:top;
- .fg-vspace-[between|arround|evenly|start|center|end] : répartition verticale des blocs du bloc
- .m-child-raz[-1|-2] : suppression marge haute du premier bloc et basse du dernier bloc enfant ou petit-enfant
- .badge, .badge-rouge, .badge-bleu, .badge-vert

---

## 05/11/2019 - version 1.6.3

---

#### Général

- création des traductions anglaises pour toutes les actions

#### Nouvelles actions

- **anim-aos** : effets d'animation sur des blocs textes ou images (Pascal Leconte)

#### Modifications actions

- **upactionslist** : gestion traduction
- **filter** : utilisation de filter_ok
- **snowfall** : ajout option filter
- **corner** : ajout option filter, suppression datemin et datemax
- **tab** : correction regex pour balise titre avec attributs
- **pdf** : correction valeur défaut 'download-text'
- **osmap** : mise à jour vers Leaflet 1.5.1
- **article-category** : correction current-catid
- **modal** : correction sur contenu inline
- **readmore** : correction valeur defaut pour bouton

#### Nouvelles méthodes

- **filter_ok** : traitement de l'option généralisée 'filter=period:1223,0105;mobile=0'

#### Modifications internes

- rétablissement traduction dans ctrl_options et annulation dans up.php

---

## 25/10/2019 - version 1.6.2

---

- suppression code de debug dans up.php

#### Modifications actions

- **tab** : ajout option auto par pleconte et correction regex

---

## 25/10/2019 - version 1.6.1

---

Reprise complète des documentations actions

#### Modifications actions

- **lorem** : nouveau param 'tag=DIV'.
  Pour avoir un texte sans aucun tag, utilisez : {up lorem=2,plaintext | tag=0}

---

## 15/10/2019 - version 1.6 (compatible Joomla 4.0)

---

#### Nouvelles actions

- **csv2list** : liste avec point de conduite à partir de contenu au format CSV
- **csv2def** : liste de définition à partir de fichier au format CSV ou de saisie wysiwyg
- **csv2table** : table à partir de fichier ou de saisie au format CSV
- **image-pannellum** : affiche un panorama 3d à partir d'une image equirectangular
- **file-view** : affiche le contenu d'un fichier texte, csv ou html
- **corner** : badge en coin ou ruban sur un bloc ou le body

#### Modifications actions

- **image-gallery** : correction texte description
- **AddCssHead** : possibilité de charger un fichier
- toutes les actions avec le parametre base-css, le mot-clé '#id' est remplacé par l'ID de l'instance.
- **upactionslist** : ajout 2 options pour générer la documentation dans un fichier CSV ou Markdown

#### Nouvelles méthodes

- **clean_HTML** : retourne un contenu avec les balises HTML visibles, à l'identique ou avec seulement quelques balises
- **params_decode** : retourne un tableau avec des options sous la forme key:val,"key":" val:2",key:lang[en=yes;fr=oui],...

#### Modifications internes

- reprise complete up.php pour evaluation des shortcodes enfants en premier.
  permet de créer une table par csv2table qui pourra etre modifiée par table-by-rows
- suppression des balises P dans le shortcode si saisie en wysiwyg
- json_arrtostr : ajout mode=3 pour prise en charge param array. Actions concernée : slider-owl (itemsXX).
- load_css_head : remplace le tag '#id' par l'ID de l'instance de l'action
- only_using_options : ajout argument pour tester d'autres jeux d'options
- get_content_shortcode : modif regex pour trouver le mot clé exact
- suppression traduction dans ctrl_options (voir incidence)
- set_attr_tag : ajout option pour choisir le type de guillemets

#### CSS

- ajout important aux règles prioritaires (spacing, )

---

## 20/10/2018 - version 1.5

---

#### Nouvelles actions

- **lorem-unsplash** : images aléatoires
- **lorem-placeimg** : images aléatoires
- **bg-video** : ajouter des vidéos en fond de site ou dans un bloc
- **bg-image** : ajouter des images et règles CSS en fond de site ou dans un bloc
- **bg-slideshow** : ajouter un slideshow en fond de site ou dans un bloc
- **center** : centrer du contenu dans un bloc
- **snowfall** : faire tomber de la neige ou d'autres images

#### Modifications actions

- **tabslide** : modification z-index
- **filter** : period récurrentes - period=1224,010210 -> tous les ans du 24/12 minuit au 01/01 à 10h

#### CSS

- ajout classes bg30,bg50 et bg80 pour fond blanc translucide
- ajout classe up-center pour centrer verticalement du contenu avec neutralisation des marges son significatives
- ajout classes w[s|m]25, w[s|m]50, w[s|m]75, w[s|m]100 : largeur en pourcentage
- modif fg-row : annulation stretch vertical

#### Nouvelles méthodes

- get_attr_style : ventile les classes et styles dans un tableau attribut

#### Modifications internes

- Argument principal : n'est plus forcé à true, pour pouvoir saisir une valeur 1
  MAJ actions concernées : article_category, box, clocks_gmt, countdown, html, lorempixel, marquee, meteo_france, pdf, readmore, slideshow_billboard, tab
- json_arrtostr : ajout paramètre pour retour sans accolades
- msg_error : id action dans message
- json_arrtostr : mode 2 : pas de guillemets si entouré de crochets

---

## 20/8/2018 - version 1.4

---

#### Nouvelles actions

- **clocks-gmt** : horloge mondiale
- **image-gallery** : image(s) dans lightbox avec gestion taille fichier selon device
- **image-rollover** : change image au survol souris
- **modal** : affiche html, video, fichier, iframe dans fenêtre modale
- **PDF** : affichage dans contenu, fenêtre modale ou lien pour télécharger
- **website** : lien vers un site web avec génération automatique screenshot

#### Modifications actions

- **adclass**
  - prise en charge grands-parents
- **article-category**
  - possibilité de plusieurs catégories séparées par des virgules
  - filtrage sur auteur(s)
  - titre uniquement si résultat
- **faq**
  - ajout classe active sur titre ouvert
- **icon**
  - ajout prefix pour prise en charge plusieurs polices d'icônes
- **meteo-france**
  - prise en charge https
- **tab**
  - prise en charge class et style
- \*\*slideshow-billboard
  - fix test sur type contenu (dossier ou content)
- **upactionslist**
  - ajout param demo pour ne pas afficher le lien sur la page de demo
  - ajout param class & style
  - ajout message pour cliquer sur FAQ sur page demo

#### Nouvelles méthodes

- prise en charge des préférences webmaster par fichier custom/prefs.ini

#### Modifications internes

- ajout obj->firstInstance pour faire action lors du 1er run
- ajout obj->replace_deb & replace_len pour traitement remplacement par action
- ajout possibilité d'un retour dans array
  - all : totalité $article->text (gestion globale article par action)
  - tag : le texte pour remplacer le shortcode
  - before/after : texte à ajouter en début ou fin de $article->text
- ajout uniqid() pour id shortcode hors article
- ajout méthode get_option_is_valid(param_name) retourne valeur ou msg erreur
- ajout méthode get_url_full : retourne une url absolue
- ajout méthode on_server(url) : true si URL sur le server
- ajout méthode sreplace(old, new, src, nb = 1) : remplace les nb occurrences de old par new dans src.
  (version simplfiée de sprintf qui retourne false si le nbre d'arguments diffère)
- changement de nom : load_script_head -> load_js_code
- suppression méthode add_options_json : utiliser prefs.ini pour les personnalisations

#### Corrections internes

- UP
  - test si 2e shortcode ouvrant avant fermant pour autoriser à une action d'utiliser les 2 formes de shortcode (court et long)
  - suppression balise br ajoutée avant | pour aérer le shortcode
- ctrl_argument :
  - accepte valeur vide
  - retour valeur (modif actions : article_category & upscsscompiler)
- ctrl_content_parts : espace dans chaine recherchée
- ctrl_options :
  - prise en charge custom/prefs.ini
  - traduction de tous les arguments texte commençant par xx=
  - case insensitive options JS de prefs.ini
- get_action_pref : regex pour saut ligne
- info_debug : prise en charge traduction
- lang & translate : pb parenthèses
- link_humanize : suppression compteur (0xx-) devant le nom du fichier. 01-lion.jpg = lion.jpg
- load_file : prise en charge cdn
- msg_info : modif des actions utilisatrices (meteo, upscsscompiler)
- set_attr_tag :
  - possibilité de passage de contenu avec $close
  - retour vide si demande fermeture sans attribut

---

## 29/6/2018 - version 1.33

---

- action tab (prise en charge class et style, accordion)
- action faq (ajout classe active sur titre ouvert)
- **modif interne**
- chgt nom : load_script_head -> load_js_code

---

## 28/6/2018 - version 1.32

---

- possibilité de personnalisation par sous-dossier custom
- nouvelle action : **kawa** ;-)
- tab : correction bug sur forcage accordion. Suppression espace sous onglets. CSS ul,li, prise en charge attributs dans regex. (merci woluweb)
- lorem : appel du serveur en https
- **modif interne**
- link_humanise. ajout param $capitalize

---

## 24/6/2018 - version 1.31

---

- nouvelle action : addScript
- ajout load_custom_code_head supprimé par erreur

---

## 23/6/2018 - version 1.3

---

- nouvelle action : **OSMap **
- nouvelle action : **facebook-timeline**
- nouvelle action : **tweeter-timeline**
- markdown: suppression commentaires YAML et gestion chemin images
- slider-owl : correction css inline
- **modif interne**
- json_arrtostr : retourne {} si vide
- ajout load_script_head($code)
- ajout get_jsontoarray($filename)
- ajout strtoarray($str)
- ajout get_content_shortcode($content, $key) array des shortcodes internes

---

## 7/2/2018 - version 1.2

---

- nouvelle action : **markdown**
- ajout nouvelles actions dans le zip de base : imagemap
- correction bug upAction.php pour compatibilité PHP 7.2
- modif load_css_head pour
  - permettre sélecteur avec `&gt;` au lieu de `>`
  - suppression tags HTML dans argument multilignes
- up.xml : ajout note sur utilisation

---

## 15/12/2017 - ajout actions

---

- **imagemap** : création d'une image clicable et responsive

---

## 3/12/17 - version 1.1

---

- ajout action **sound-hover**
- modif action **html** pour fermeture auto des balises
- ajout action **media-plyr**
- ajout action **slider-owl**
- ajout action **image-slideshow**
- ajout compilateur SCSS
- script install pour préserver les configurations
- bug: prise en compte argument false en json
- reprise script exemple
- fonction link_humanize
- json_arrtostr. guillemets si argument chaine
- controle non chevauchement des actions.
  Permet de mixer shortcode simple et avec contenu (voir slideshow-billboard)
- correction str_append pour prise en charge de 0
