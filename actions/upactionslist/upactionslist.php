<?php

/**
 * Liste des UP actions avec infos et paramètres
 *
 * {up upactionslist}  toutes les actions
 * {up upactionslist=action1, action2}  une ou plusieurs actions
 * {up upactionslist | md}  fichier marknote
 * {up upactionslist | csv}  fichier CSV
 *
 *
 *
 * @version  UP-1.0
 * @author   Lomart
 * @update   2019-10-25
 * @license  <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags UP
 */
// No direct access
defined('_JEXEC') or die();

/*
 * v1.4 - ajout param demo pour ne pas afficher le lien sur la page de demo
 * - ajout param class & style
 * - ajout message pour cliquer sur FAQ sur page demo
 * v1.6 - ajout 2 options pour générer la documentation dans un fichier CSV ou Markdown
 * v1.63 - gestion traduction
 * v1.7 - ajout option filter
 * v1.72 - pas d'affichage de la doc dans la liste générale si demosite=0
 * v2.5 - Possibilité d'exclure les actions commencant par exclude-prefix
 * - le lien demo non affiche si demopage=0, mais l'aide interne est disponible
 * - possibilité de surcharger dico.ini dans custom (general et actions)
 * v2.8 - ajout option without-custom pour afficher infos webmaster
 * v2.9 - ajout trad GB et upbtn dans doc-actions.csv
 * - prise en charge sous-titre dans l'aide intégrée
 * v5.1 - ajout blink pour lire la doc
 */
class upactionslist extends upAction
{

    function init()
    {
        // ===== Ajout dans le head (une seule fois)
        $this->load_file('/plugins/content/up/assets/js/faq.js');

        $css_code = '.upfaq {width: 100%;}';
        $css_code .= '.upfaq-button {';
        $css_code .= '	background-color: #069;';
        $css_code .= '	border-bottom: 1px solid #FFFFFF;';
        $css_code .= '	cursor: pointer;';
        $css_code .= '	padding: 5px 10px;';
        $css_code .= '	color: #FFFFFF;';
        $css_code .= '	font-weight:bold;';
        $css_code .= '}';
        $css_code .= '.upfaq-button small{color:#ddd}';
        $css_code .= '.upfaq-content{border-bottom:#369 3px solid}';
        $css_code .= '.upfaq ul{margin:0;list-style:square}';

        $css_code .= '.upfaq-content {';
        $css_code .= '	background-color: ##ddd;';
        $css_code .= '	display: none;';
        $css_code .= '	padding: 10px;';
        $css_code .= '}';

        $css_code .= '.upfaq-subtitle {margin:5px 0 0 0;padding:2px;background:#CFDEE5;color:#01457F;text-align:center;font-weight:bold}';

        $css_code .= '.bg-grey{background-color:#aaa;margin:5px 0;padding:5px}';

        $css_code .= '.blink{animation: blinker 1.5s linear infinite;}';
        $css_code .= '@keyframes blinker{50% {opacity:20;color:yellow;}}';

        $this->load_css_head($css_code);
    }

    function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        $options_def = array(
        /* [st-sel] Sélection des actions listées */
            $this->name => '', // liste des actions à récupérer. toutes par défaut
            'exclude-prefix' => '_,x_', // prefix des actions non listées. Separateur = comma
            'without-custom' => '0', // affiche les infos du dossier custom de l'action. 1 pour les masquer
            /* [st-demo] Affichage du lien vers la démo */
            'demo' => 1, // afficher le lien vers la demo
            /* [st-dico] Générer les fichiers JSON synonymes des noms utilisés par UP 'dico' */
            'make-dico' => '0', // consolide le fichier principal dico.json avec ceux des actions
            /* [st-doc] Création des fichiers documentation */
            'csv' => 0, // fichier doc-actions.csv avec les options des actions
            'comment' => 0, // fichier 'comment-actions.csv' avec les infos des entêtes scripts pour analyse
            'md' => 0, // enregistre la documentation au format markdown dans plugins/content/up/doc-actions.md
            /* [st-css] Style CSS */
            'id' => '', // identifiant
            'style' => '', // style ou class
            'class' => '', // idem style
            'filter' => '' // condition pour exécuter l'action
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === Filtrage
        if ($this->filter_ok($options['filter']) !== true) {
            return '';
        }

        // === liste des sous-dossiers du dossier actions (sauf _exemple)
        if ($options[__class__] == '') {
            // toutes sauf celles avec préfix
            $actionsList = $this->up_actions_list($options['exclude-prefix']);
        } else {
            // uniquement celles demandées
            // TODO : voir à conserver $dico en global
            // charger le dictionnaire
            $dico = file_get_contents($this->upPath . 'dico.json');
            $dico = json_decode($dico, true);

            $tmp = array_map('trim', explode(',', $options[__class__]));
            foreach ($tmp as $key) {
                if (array_key_exists($key, $dico)) {
                    $key = $dico[$key];
                }
                $actionsList[] = str_replace('-', '_', $key);
            }
        }

        // === CONSOLIDATION DU FICHIER DICO.JSON ===
        if ($options['make-dico']) {
            $dicoFolder = $this->up_actions_list();
            $dicoIni = $this->upPath . 'custom/dico.ini';
            if (file_exists($dicoIni) === false)
                $dicoIni = $this->upPath . 'dico.ini';
            $newDico = $this->load_inifile($dicoIni);
            // on recherche les dico des actions avec preference pour celui dans custom
            foreach ($dicoFolder as $dicoAction) {
                $dicoIni = $this->upPath . 'actions/' . $dicoAction . '/custom/dico.ini';
                if (file_exists($dicoIni) === false)
                    $dicoIni = $this->upPath . 'actions/' . $dicoAction . '/up/dico.ini';
                if (file_exists($dicoIni)) {
                    $tmp = $this->load_inifile($dicoIni);
                    $newDico = array_merge($newDico, $tmp);
                }
            }
            $dicoJson = $this->upPath . 'dico.json';
            if (file_put_contents($dicoJson, json_encode($newDico, JSON_UNESCAPED_SLASHES)) !== false) {
                $this->msg_info($this->trad_keyword('MAKE_DICO_OK'));
            } else {
                $this->msg_error($this->trad_keyword('MAKE_DICO_ERR'));
            }
        }

        // === MODE = CSV ===
        if ($options['csv']) {
            $fic = 'plugins/content/up/doc-actions.csv';
            $hfic = fopen($fic, 'w');
            if (! $hfic) {
                $this->msg_error($this->trad_keyword('LOAD_FIC_ERR', $fic));
            } else {
                fputcsv($hfic, array(
                    'Action',
                    'Option',
                    'Dico',
                    'Default',
                    'Comment',
                    'en-GB',
                    'UPbtn'
                ),",","\"","\n");
                foreach ($actionsList as $actionName) {

                    // === récupération des infos et options
                    $actinfos = $this->up_action_infos($actionName);
                    $actoptions = $this->up_action_options($actionName, true);
                    // la traduction anglaise des options - v2.9
                    $gb_file = $this->upPath . 'actions/' . $actionName . '/up/en-GB.ini';
                    $options_gb = parse_ini_file($gb_file);
                    // la traduction anglaise pour le bouton UP - v2.9
                    $upbtn_gb = array();
                    $gb_file = $this->upPath . 'actions/' . $actionName . '/up/upbtn-options.ini';
                    if (file_exists($gb_file))
                        $upbtn_gb = parse_ini_file($gb_file);

                    foreach ($actoptions as $val) {
                        $txt = array();
                        $txt[] = $actionName;
                        if (substr($val['key'], 0, 6) == '>>ST>>') {
                            $val['key'] = substr($val['key'], 6);
                            $txt[] = '## ' . $val['key'];
                        } else {
                            $txt[] = $val['key'];
                        }
                        $txt[] = $val['dico'];
                        $txt[] = trim($val['val'], '\' ');
                        $txt[] = $val['comment'];
                        $txt[] = (isset($options_gb[$val['key']])) ? $options_gb[$val['key']] : '';
                        $txt[] = (isset($upbtn_gb[$val['key']])) ? $upbtn_gb[$val['key']] : '';
                        fputcsv($hfic, $txt,",","\"","\n");
                    }
                }
                fclose($hfic);
                $this->msg_info($this->trad_keyword('SAVE_CSV_OK', $fic));
            }
        }

        // === MODE = COMMENT ===
        if ($options['comment']) {
            $header = array(
                'Action_name'
            );
            foreach ($actionsList as $actionName) {
                // === récupération des infos et options
                $actinfos = $this->action_comment($actionName);
                $infos = array();
                $infos['Action_name'] = $actionName;
                foreach ($actinfos as $k => $v) {
                    $k = strtolower($k);
                    if (! in_array($k, $header))
                        $header[] = $k;
                    $infos[$k] = $v;
                }
                $comment[] = $infos;
            }
            $fic = 'plugins/content/up/comment-actions.csv';
            $hfic = fopen($fic, 'w');
            if (! $hfic) {
                $this->msg_error($this->trad_keyword('LOAD_FIC_ERR', $fic));
            } else {
                fputcsv($hfic, $header, ";","\"","\n");
                foreach ($comment as $k => $infos) {
                    $txt = array();
                    foreach ($header as $k) {
                        if (isset($infos[$k])) {
                            $txt[$k] = trim($infos[$k]);
                        } else {
                            $txt[$k] = '';
                        }
                    }
                    fputcsv($hfic, $txt,";","\"","\n");
                }
                fclose($hfic);
                $this->msg_info($this->trad_keyword('SAVE_COMMENT_OK', $fic));
            }
        }

        // === MODE MARKDOWN ===
        if ($options['md']) {
            /* v2.5 sortie FR et GB automatique */
            foreach (array(
                'fr-FR',
                'en-GB'
            ) as $lang) {
                $fic = 'plugins/content/up/UP-doc-actions-v' . $this->get_upversion() . '-(' . strtolower(substr($lang, - 2, 2)) . ').md';
                $hfic = fopen($fic, 'w');
                if (! $hfic) {
                    $this->msg_error($this->trad_keyword('LOAD_FIC_ERR', $fic));
                } else {
                    // setlocale(LC_TIME, 'fr_FR.utf8', 'fra');
                    setlocale(LC_TIME, str_replace('-', '_', $lang) . '.utf8', substr($lang, 0, 2));
                    fwrite($hfic, '# UP-' . $this->get_upversion() . ' - Documentation actions' . PHP_EOL . $this->up_date_format('', '%e %B %Y') . PHP_EOL);
                    foreach ($actionsList as $actionName) {
                        // === récupération des infos et options
                        $actinfos = $this->up_action_infos($actionName, $lang);
                        $actoptions = $this->up_action_options($actionName, false, $lang);

                        $actionName .= $this->str_append('', $this->get_dico_synonym($actionName), ' ', ' (', ')');
                        $actionName = str_replace('_', '-', $actionName);
                        // === infos action
                        fwrite($hfic, '# ' . $actionName . PHP_EOL);
                        fwrite($hfic, '**' . $actinfos['_shortdesc'] . '**' . PHP_EOL . PHP_EOL);
                        fwrite($hfic, $actinfos['_longdesc'] . PHP_EOL);
                        // === infos options
                        // on ajoute un sous-titre sauf si la 1ere option en est un
                        if (! is_integer(key($actoptions)))
                            fwrite($hfic, '#### Options' . PHP_EOL);
                        foreach ($actoptions as $key => $val) {
                            if (is_integer($key)) {
                                fwrite($hfic, '#### ' . $val . PHP_EOL);
                            } else {
                                fwrite($hfic, '- **' . $key . '** : ' . $val . PHP_EOL);
                            }
                        }
                    }
                    fclose($hfic);
                    $this->msg_info($this->trad_keyword('SAVE_ACTIONS_OK', $fic));
                }
            }
        }

        // === CODE HTML EN RETOUR ===
        // <div id="upfaq">
        // <div class="upfaq-button">Button 1</div>
        // <div class="upfaq-content">Content<br />More Content<br /></div>
        // <div class="upfaq-button">Button 2</div>
        // <div class="upfaq-content">Content</div>
        // </div>
        // === code HTML
        $attr_main['class'] = 'upfaq';
        $this->get_attr_style($attr_main, $options['class'], $options['style']);

        $txt = $this->set_attr_tag('div', $attr_main);

        foreach ($actionsList as $actionName) {

            // === récupération des infos et options
            $actinfos = $this->up_action_infos($actionName);
            $actoptions = $this->up_action_options($actionName);
            if (is_string($actinfos)) {
                $txt .= $actinfos;
                continue;
            }

            // LM2001 - ne pas afficher la doc si liste complete demandée et demopage=0
            // if ($actinfos['_demopage'] == '0' && $options[__class__] == '') {
            // continue;
            // }

            $txt .= '<div class="upfaq-button bloc">';
            $txt .= '&#x1F199; ' . $actionName; // fleche et nom action
            $txt .= $this->str_append('', $this->get_dico_synonym($actionName), ' ', ' (', ')');
            $txt .= $this->str_append('', $actinfos['_shortdesc'], ' ', ' : <small>', '</small>');

            // ajout URL pour démo ()remplace _ par - pour alias Joomla
            // > 5 : pas une URL mais un mot-clé - LM-v2
            $demo = ($actinfos['_demopage'] != '0' && $options['demo'] == 1 && $this->usehelpsite > 0);
            if ($demo && strlen($actinfos['_demopage']) > 5) {
                $txt .= ' <small>&#x27A0; <a style="color:yellow;float:right;margin:0" href="';
                $txt .= $actinfos['_demopage'] . '"';
                if ($this->usehelpsite == 2) {
                    $txt .= ' target = "_blank"';
                }
                $txt .= '>DEMO</a></small>';
            } else {
                // incitation à cliquer pour lire la doc sur les pages demo où demo=0
                $txt .= '<p style="float:right;margin:0"><small class="blink">Cliquer pour lire la documentation</small></p>';
            }
            $txt .= '</div>';

            $txt .= '<div class="upfaq-content">';
            // la description longue
            $txt .= ($actinfos['_longdesc']) ? $actinfos['_longdesc'] : '';
            // les infos du sous-dossier custom
            if (empty($options['without-custom'])) {
                $txt .= $this->up_help_txt($actionName);
                $txt .= $this->up_prefset_list($actionName);
            }
            // les mots-clés
            $txt .= '<div style="background:#bbb;padding:3px">';
            /*
             * foreach ($actinfos as $key => $val) {
             * if ($key[0] != '_') {
             * $txt .= ' <span class="label">' . $key . '</span> ' . $val;
             * }
             * }
             */
            $txt .= $actinfos['_credit'];
            $txt .= '</div>';
            // les options
            $txt .= '<ul>';
            foreach ($actoptions as $key => $val) {
                if (is_integer($key)) {
                    $txt .= '</ul><p class="upfaq-subtitle">' . $val . '</p><ul>';
                } else {
                    $txt .= '<li><strong>' . $key . '</strong>: ' . $val . '</li>';
                }
            }
            $txt .= '</ul>';

            $txt .= '</div>';
        }
        $txt .= '</div>';

        return $txt;
    }

    // run
    function get_upversion()
    {
        $vers = '?';
        $fic = $this->upPath . 'up.xml';
        if (file_exists($fic)) {
            $xml = simplexml_load_file($fic);
            $vers = $xml->version;
        }
        return $vers;
    }

    function action_comment($action_name)
    {
        $actionFolder = $this->upPath . 'actions/' . $action_name . '/';
        if (! file_exists($actionFolder . $action_name . '.php')) {
            return 'Action <b>' . $action_name . '</b> : erreur de structure des dossiers.';
        }
        $tmp = file_get_contents($actionFolder . $action_name . '.php');

        $out = array();
        // info dans entete script
        $desc = array();
        if (preg_match('#\/\*\*(.*)\*\/#siU', $tmp, $desc)) {
            $desc = array_map('trim', explode('*', $desc[1]));

            foreach ($desc as $lign) {
                $lign = trim($lign, ' *');
                if ($lign && $lign[0] == '@') { // ligne avec @motcle contenu
                    list ($key, $val) = explode(' ', $lign . ' ', 2);
                    if (trim($val))
                        $out[$key] = $val;
                }
            }
        }

        return $out;
    }
}

// class







