<?php

/**
 *
 * @package plg_UP for Joomla! 3.0+
 * @version $Id: up.php 2025-11-01 $
 * @author Lomart
 * @copyright (c) 2025 Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 *
 * */
/*
v5.3.3 : php 8.4/8.5 compatibility
v5.3.3 : check/load actions from github 
*/

// namespace up;
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Version;

// #[AllowDynamicProperties] // php 8.4

class plgContentUP extends CMSPlugin
{
    public $upPath = 'plugins/content/up/';
    private $githubapikey = null;
    private $githuburl = 'https://api.github.com/repos/conseilgouz/up/contents/';
    private $api_token_1 = 'github#pat#';
    private $api_token_2 = '11AEUI53Q09kiUG4jTXBZD#';
    private $api_token_3 = 'NxhHfoiAknnIC6F5qyzR9gVt63lw8dS2pWs8tF6etlpE7PJGBIPdGU2Qz6S'; // default api key
    private $actionsha256 = [];

    public function __construct(&$subject, $params)
    {
        parent::__construct($subject, $params);
        $this->LoadLanguage();
        $this->loadActionsSha256();
    }

    public function onContentPrepare($context, &$article, &$params, $limitstart = 0)
    {
        $app = Factory::getApplication();
        $tdeb = microtime(true);
        $debug = false;

        if ($context == 'com_search.search') { // v2.9
            return;
        }
        if ($context == 'com_finder.indexer') { // v2.9
            // les identificateurs autorisés pour UP
            $tags = $this->params->def('tagname', 'up|xx');
            // les actions pour lesquelles il est dangereux de montrer le contenu
            // et celles avec des shortcodes internes
            $ActionMaskedContent = $this->params->def('searchActionMaskedContent', 'note|filter');

            // 1 - effacer les shortcodes et contenus des actions confidentielles
            $regex = '#(\{(?:' . $tags . ')\s*(?:' . $ActionMaskedContent . ').*\{\/(?:up|xx)\s*(?:' . $ActionMaskedContent . ').*\})#Ui';
            $article->text = preg_replace($regex, '', $article->text);

            // 2 - masquer les shortcodes ouvrants et/ou uniques
            $regex = '#(\{(?:' . $tags . '|===).*\})#Ui';
            $article->text = preg_replace($regex, '', $article->text);

            // 2 - masquer les shortcodes fermants
            $regex = '#(\{\/(?:' . $tags . ').*\})#Ui';
            $article->text = preg_replace($regex, '', $article->text);

            return;
        }

        // ========> DOIT-ON EXECUTER ?
        // uniquement en frontend
        if ($app->isClient('administrator')) {
            return false;
        }

        // Chargement systematique de la feuile de style
        if ($this->params->def('loadcss', '1')) {
            $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
            try { // conflit avec RegularLbas
                $wa->registerAndUseStyle('upcss', 'plugins/content/up/assets/up.css');
            } catch (\Joomla\CMS\WebAsset\Exception\InvalidActionException $e) {
                // ignore
            } catch (\Exception $e) {
                // ignore
            }
        }

        // sortie directe si pas de texte a traiter
        if (! isset($article->text)) {
            return false;
        }
        if (trim($article->text) == '') {
            return false;
        }
        // pas d'analyse pour les listes d'articles
        if ($context == "com_content.category") {
            if ($app->getInput()->get('layout', 0) !== 'blog') {
                return false;
            }
        }
        $loopId = 0;
        while (true && $loopId < 10) {

            // liste des shortcodes utilisables
            $tag = $this->params->def('tagname', 'up|xx');
            $regexopen = '/\{(?:' . $tag . ') *([^\s\=\|\{\}]+)/si';
            // retour direct si pas de upAction dans l'article
            if (! preg_match($regexopen, $article->text)) {
                return false;
            }

            // ==========> C'EST BON, IL FAUT Y ALLER !
            // fonctions utilitaires pour les actions
            include_once $this->upPath . 'upAction.php';

            // charger le dictionnaire
            $dico = file_get_contents('dico.json', FILE_USE_INCLUDE_PATH);
            $dico = json_decode($dico, true);

            /*
             * ****** PSEUDO-CODE
             * 0. doit-on supprimer les <p...>{up et }</p> ?
             * 1. on récupère la positions de tous les "{up " dans $article->text
             * => $openSC[$i][actionName] : nom de la l'action
             * => $openSC[$i][posDeb] : position de l'accolade ouvrante
             * 2. on parcours les shortcodes à partir du dernier
             * la fin du shortcode ouvrant est le 1er } suivant {
             * (note: les accolades des shortcodes enfants n'existent plus car traitées en premier)
             * $actionUsername = $openSC[$i][actionName]
             * $replaceDeb = $openSC[$i]['posDeb'];
             * $replaceLen = taille shortcode ouvrant {} inclus
             * 3. on analyse les options en nettoyant les <p> et </br>
             * note: les shortcodes inclus ne doivent pas renvoyer de <p> et </br> (ou les mettre en [p] et [br])
             * 4. on recherche un shortcode fermant et on actualise $replaceDeb & $replaceLen
             *
             */

            // Pour éviter nettoyage par editeur wysiwyg, on utilise un simili BBCode dans les arguments options
            $bbcode = array(
                '[br]'
            );
            $htmlcode = array(
                '<br>'
            );

            // ===== NETTOYAGE AJOUT EDITEURS (v1.8)
            // on supprime les balises P vides limitrophes ajoute par les editeurs wysiwyg
            $regex = '#(<p>)(\{\/?' . $tag . '\b.*\})(</p>)#U';
            $article->text = preg_replace($regex, '$2', $article->text);

            // ===== RECHERCHE DE TOUS LES SHORTCODES OUVRANTS
            if (! preg_match_all($regexopen, $article->text, $matches, PREG_OFFSET_CAPTURE)) {
                $this->info_debug('Error up.php 132');
            }
            $nbSC = count($matches[0]);
            for ($i = 0; $i < $nbSC; $i++) {
                $openSC[$i]['actionName'] = trim($matches[1][$i][0]);
                $openSC[$i]['posDeb'] = $matches[0][$i][1];
            }

            // liste des objets ACTION initialisés
            $classObjList = array();

            // ==== parcours des shortcodes ouvrants a partir du dernier
            for ($i = $nbSC - 1; $i >= 0; $i--) {
                // reset variables
                unset($actionUserName); // nom de l'action saisi dans le shortcode
                unset($actionClassName); // nom du dossier, script php et classe de l'action
                unset($options_user);
                $content = ''; // le contenu entre shortcodes
                unset($ret); // retour par action
                // identifiant unique pour l'action
                if (isset($article->id)) { // si article
                    // $loopStr = ($loopId > 0) ? '-' . $loopId : '';
                    $loopStr = ($loopId > 0) ? chr($loopId + 64) : '';
                    $options_user['id'] = 'up-' . $article->id . '-' . $loopStr. ($i + 1);
                    // $options_user['id'] = 'up-' . $article->id . '-' . ($i + 1);
                } else { // si module
                    $options_user['id'] = 'up-m' . uniqid();
                }
                // -- Le shortcode ouvrant complet : {up action=xx | opt=val}
                $SC = strstr(substr($article->text, $openSC[$i]['posDeb']), '}', true) . '}';

                // -- position pour remplacement au retour
                $replaceDeb = $openSC[$i]['posDeb'];
                $replaceLen = strlen($SC);
                // -- Les options du shortcode ouvrant : action=xx | opt=val
                // 29/9/19 pour shortcode multilignes en wysiwyg
                // <br /> est le saut de ligne utilise par LM-Prism, tiny et JCE (exemple démo)
                // J5 transforme <br /> en <br>
                $SC = str_ireplace(array(
                    '<p>',
                    '</p>',
                    '<br>',
                    '<br />',
                    '&nbsp;',
                    PHP_EOL,
                    '{',
                    '}'
                ), '', $SC);
                $SC = substr($SC, strpos($SC, ' '));
                // // -- analyse des options du shortcode
                $allParams = explode('|', $SC);
                foreach ($allParams as $param) {
                    // v1.8 supprime espace dur de TinyMCE
                    $param = preg_split("/=/", trim($param, " \t\n\r\0\x0B\xA0\xC2"), 2); // permet = dans argument
                    // le mot clé tel que saisi
                    $key = strtolower(trim($param[0]));
                    // sa valeur (true si aucune)
                    $value = (count($param) == 2) ? trim($param[1]) : true;
                    // suppression d'un saut de ligne <br> ou <br /> entre les options
                    /* $value = preg_replace('#\s*\<br\s?\/?>#i', '', $value); */
                    // la 1ere option est le nom de l'action
                    if (! isset($actionUserName)) {
                        $actionUserName = $key; // tel que saisi dans article
                        // LM 180921: l'argument principal est égal à vide et pas true
                        $value = (count($param) == 2) ? trim($param[1]) : '';
                        // le mot clé traduit pour le script action
                        $key = str_replace('-', '_', $key); // tel que le script (14/12/19)
                        if (array_key_exists($key, $dico)) {
                            $key = $dico[$key];
                        }
                        $key = str_replace('-', '_', $key); // tel que le script
                        $actionClassName = $key; // Nom dossier et classe
                    } else {
                        // le mot clé traduit pour le script action
                        if (array_key_exists($key, $dico)) {
                            $key = $dico[$key];
                        }
                    }
                    // guillemets double pour forcer un espace en tete ou fin - v3
                    if (isset($value[0]) && $value[0] == '"' && $value[strlen($value) - 1] == '"') {
                        $value = trim($value, '\"');
                    }
                    // analyse de l'argument de l'option
                    $options_user[$key] = $value;
                }
                // -- on recherche l'eventuel shortcode fermant
                $regexclose = '/\{\/(?:' . $tag . ')\s+' . $actionUserName . '.*\}/siU';
                if (preg_match($regexclose, $article->text, $matches, PREG_OFFSET_CAPTURE, $replaceDeb + $replaceLen)) {
                    // le contenu
                    $content_deb = $replaceDeb + $replaceLen;
                    $content_len = $matches[0][1] - $content_deb;
                    $content = substr($article->text, $content_deb, $content_len);

                    // suppression balise P fermante au début et ouvrante à la fin
                    // <p>{shortcode}</p>contenu<p>{/shortcode}</p>
                    $regex = array(
                        '#^</.*>#',
                        '#<[a-zA-Z =-_"]*>$#U'
                    );
                    $content = preg_replace($regex, '', $content);
                    // maj positions remplacement
                    $replaceLen = $replaceLen + $content_len + strlen($matches[0][0]);
                }
                // ==== EXECUTION DE L'ACTION
                $text = '';
                // le chemin du script
                $actionfile = 'actions/' . $actionClassName . '/' . $actionClassName . '.php';
                if ($this->params->def('checkgithub', 0)) {
                    // contrôle de version de l'action sur github
                    $this->checkactionsha256($actionClassName);
                }
                // Mini UP : chargement des actions au 1er appel
                if (!is_file($this->upPath.$actionfile)) { // mini UP : action non chargée
                    $this->githubapikey = $this->get_action_pref('github-key');
                    if (!$this->getGithubActionRec('actions/'.$actionClassName)) {
                        continue;  // error  ignore it
                    }
                    // exceptions : appel croisé dans les actions
                    if (($actionClassName == 'pdf_gallery')
                        || ($actionClassName == 'pdf')
                        || ($actionClassName == 'file_explorer')
                        || ($actionClassName == '_upgesterror')) {
                        if (!is_file($this->upPath.'actions/modal/modal.php')) {
                            if (!$this->getGithubActionRec('actions/modal')) {
                                continue;  // error  ignore it
                            }
                        }
                    }
                    if ($actionClassName == 'pdf_gallery') {
                        if (!is_file($this->upPath.'actions/pdf/pdf.php')) {
                            if (!$this->getGithubActionRec('actions/pdf')) {
                                continue;  // error  ignore it
                            }
                        }
                    }

                }

                // CHRONOMETRAGE ACTIONS // 5.2
                if (false) {
                    if (isset($timeStart)) {
                        $timeEnd = microtime(true);
                        $duration = ($timeEnd - $timeStart) * 1000;
                        $msg = sprintf('%8.2f : %s', $duration, $actionClassName);
                        file_put_contents('tmp/duration.log', $msg . PHP_EOL, FILE_APPEND);
                    } else {
                        file_put_contents('tmp/duration.log', '============================='. $options_user['id'] .' (en ms)'.PHP_EOL, FILE_APPEND);
                    }
                    $timeStart = microtime(true);
                }

                // --- instanciation de l'action
                // si premier appel de l'action
                if ($text == '') {
                    if (array_key_exists($actionClassName, $classObjList) == false) {
                        // on charge la classe de l'action
                        if (@include_once $actionfile) {
                            $classObjList[$actionClassName] = new $actionClassName($actionClassName);
                            $classObjList[$actionClassName]->actionUserName = $actionUserName;
                            $classObjList[$actionClassName]->firstInstance = true; // pour action unique par page dans run
                            $classObjList[$actionClassName]->article = $article; // pour load_js_file_head
                            $classObjList[$actionClassName]->init();
                            $objVersion = new Version(); // v2.6
                            $classObjList[$actionClassName]->J4 = ((int) $objVersion->getShortVersion() >= 4);
                            $classObjList[$actionClassName]->inedit = (!(isset($article->id) && empty($article->checked_out))); // v3.1
                        } else {
                            $msg = ($actionUserName == '') ? 'Syntax error' : 'non trouvée / not found'; // v2.7
                            $text = '&#x1F199; ' . $options_user['id'] . ' Action "<b>' . $openSC[$i]['actionName'] . '</b>" ' . $msg;
                            $app->enqueueMessage($text, 'error');
                        }
                    } else {
                        $classObjList[$actionClassName] = new $actionClassName($actionClassName);
                        $classObjList[$actionClassName]->actionUserName = $actionUserName;
                        $classObjList[$actionClassName]->firstInstance = false; // 07-18:pour action unique par page dans run
                        $classObjList[$actionClassName]->J4 = ((int) $objVersion->getShortVersion() >= 4); // v2.9
                    }
                }

                if ($text == '') {
                    // l'objet est cree et initialisé
                    $classObjList[$actionClassName]->options_user = $options_user;
                    $classObjList[$actionClassName]->content = $content;
                    $classObjList[$actionClassName]->article = $article;
                    $classObjList[$actionClassName]->actionprefs = $this->params->get('actionprefs');
                    $classObjList[$actionClassName]->usehelpsite = $this->params->get('usehelpsite', '2');
                    $classObjList[$actionClassName]->urlhelpsite = $this->params->get('urlhelpsite');
                    $classObjList[$actionClassName]->inprod = $this->params->def('inprod', 0); // v3.1
                    $classObjList[$actionClassName]->cssmsg = $this->params->def('cssmsg', ''); // v3.1
                    //                $classObjList[$actionClassName]->inedit = (!(isset($article->id) && empty($article->checked_out))); // v3.1
                    $classObjList[$actionClassName]->tarteaucitron = $this->params->def('tarteaucitron', false); // v2.4
                    $classObjList[$actionClassName]->trimA0 = $this->params->def('trimA0', true); // v3.0
                    $classObjList[$actionClassName]->demopage = '';
                    $classObjList[$actionClassName]->dico = $dico;
                    // 18-07-20 ajout pour remplacement par action
                    $classObjList[$actionClassName]->replace_deb = $replaceDeb;
                    $classObjList[$actionClassName]->replace_len = $replaceLen;
                    // on exécute l'action
                    $ret = $classObjList[$actionClassName]->run();
                }

                $ret = (isset($ret)) ? $ret : '';
                if (! is_array($ret)) {
                    // texte pour remplacement (méthode originelle)
                    // on remplace le shortcode par le code retourné par l'action
                    $article->text = substr_replace($article->text, $ret, $replaceDeb, $replaceLen);
                } else {
                    if (isset($ret['all'])) {
                        // l'action a traité l'intégralité des remplacements (cas action TOC)
                        $article->text = $ret['all'];
                    }
                    if (isset($ret['tag'])) {
                        // remplacement du shortcode
                        $article->text = substr_replace($article->text, $ret['tag'], $replaceDeb, $replaceLen);
                    }
                    // ajout en début d'article
                    if (isset($ret['before'])) {
                        $article->text = $ret['before'] . $article->text;
                    }
                    // ajout en fin d'article
                    if (isset($ret['after'])) {
                        $article->text = $article->text . $ret['after'];
                    }
                }
                $debug = ($debug || ! empty($options_user['debug']));
            } // fin parcours openSC

            unset($classObjList);
            $loopId++;
        } // while loopId

        if ($debug) { // v3
            $tfin = microtime(true);
            $msg = 'UP-' . $options_user['id'] . '-Execution time for ' . $nbSC . ' actions on the page or module : ' . (round(($tfin - $tdeb) * 1000, 3)) . ' ms';
            $app->enqueueMessage($msg);
        }
        return true;
    }

    // onContentPrepare

    /*
     * ==== lang
     * fonction utilitaire pour UP
     * @param [string] $str [alternative de traduction sous la forme "en=apple;fr=pomme"]
     * @return [string] [la traduction dans la langue]
     */
    public function lang($str)
    {
        // l'argument doit faire au minimum 10 caractères (fr=xx;en=xx)
        $out = trim($str);
        if (strlen($out) <= 10) {
            return $str;
        }

        // -- v1.6 : permettre l'arg commencant par lang[
        if (substr(strtolower($out), 0, 5) == 'lang[') {
            $out = (substr($out, -1, 1) == ']') ? substr($out, 5, - 1) : substr($out, 5);
        }
        // -- v3 : rétablir entité HTML (url)
        $out = str_replace('&amp;', '&', $out);

        // test langue uniquement sur les 2 premiers caractères
        $codelang = substr(Factory::getApplication()->getLanguage()->getTag(), 0, 2);

        // recherche du motif dans $str. Il faut au moins 2 langues
        if (preg_match_all('#\b(\w\w)\s*=\s*(.*);#U', $out . ';', $tmp) > 1) {
            if (isset($tmp[0][1])) {
                $trad = array_combine($tmp[1], $tmp[2]);
                if (isset($trad[$codelang])) {
                    $out = $trad[$codelang]; // dans la langue
                } elseif (isset($trad['en'])) {
                    $out = $trad['en']; // sinon en anglais
                } elseif ($str[2] == '=') {
                    $out = $trad[$tmp[1][0]]; // sinon le premier
                    // v1.8 - retour totalité car pb si url du type : index.php?option=com_content&amp;id=...
                    // v1.9 - on retourne le 1er si le 3e caractère est le signe égal
                }
                $out = trim($out);
            }
        }
        return $out;
    }

    /*
     * ==== info_debug
     * utilisé pour indiquer une erreur à son emplacement dans la page
     * $txt accepte la forme : en:hello;fr:bonjour
     * exemple : argument de paramètre manquant
     */
    public function info_debug($txt, $infoUP = true)
    {
        $txt = $this->lang($txt);
        if ($infoUP) {
            $txt = 'UP.' . $this->actionUserName . ' : ' . $txt;
        }
        return ' <span style="color:red;background:yellow;font-weight:bolder"> &#x279c; ' . $txt . '&nbsp;</span>';
    }

    /*
     * ==== onAjaxUp
     * appels AJAX pour toutes les actions
     */
    public function onAjaxUp()
    {
        $input = Factory::getApplication()->getInput();
        // Vérifie que l'action existe, sinon la charger (appel par upbtn.js)
        $exist = $input->get('exist', '', 'string');
        if ($exist) { // check plugin loaded
            $actionfile = 'actions/' . $exist . '/' . $exist . '.php';
            // Mini UP : chargement des actions au 1er appel
            if (!is_file('../'.$this->upPath.$actionfile)) { // mini UP : action non chargée
                $this->githubapikey = $this->get_action_pref('github-key');
                if (!$this->getGithubActionRec('actions/'.$exist, '../')) {
                    return true;  // error  ignore it
                }
            }
            return true;
        }
        // autres appels ajax
        $data = $input->get('data', '', 'string');
        parse_str($data, $output);
        if (! isset($output['action'])) {
            return 'err : action incorrect';
        }
        $actionClassName = $output['action'];
        $actionfile = $this->upPath . 'actions/' . $actionClassName . '/ajax_' . $actionClassName . '.php';

        if (@include_once $actionfile) {
            $return = $actionClassName::goAjax($input);
            return $return;
        } else {
            $text = 'Action Ajax ' . $actionClassName . ' non trouvée / not found';
            return 'err : ' . $text;
        }
    }
    /*
    * ==== getGithubActionRec
    * chargement d'une action avec ses sous-répertoires
    */
    private function getGithubActionRec($dir, $admin = '')
    {
        if (!$response = $this->getGithubAction($dir)) {
            $msg = 'Action '.$dir.' -> Erreur appel Github';
            Factory::getApplication()->enqueueMessage($msg);
            return false;
        }
        $action = json_decode($response);
        if (isset($action->message)) { // message d'erreur de github
            $msg = 'Action '.$dir.' -> '.$action->message;
            Factory::getApplication()->enqueueMessage($msg);
            return false;
        }
        $actionDir = $admin.$this->upPath.$dir;
        if (!is_dir($actionDir)) {
            mkdir($actionDir);
        }
        foreach ($action as $one) {
            if ($one->download_url) {
                $url = $one->download_url;
                try {
                    // ignorer les fichiers existants
                    if (!is_file($actionDir.'/'.$one->name)) {
                        copy($url, $actionDir.'/'.$one->name);
                    }
                } catch (\Exception $e) {
                }
            } else {// subdir
                $this->getGithubActionRec($one->path, $admin);
            }
        }
        return true;
    }
    /*
    * ==== getGithubAction
    * chargement d'un répertoire de github
    */
    private function getGithubAction($dir)
    {
        $url = $this->githuburl.$dir;
        try {
            $agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.3";
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_USERAGENT, $agent);
            curl_setopt($curl, CURLOPT_NOBODY, 0);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            if (!$this->githubapikey) { // pas de clé définie, on prend la clé par défaut
                $this->githubapikey = $this->api_token;
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                        "Authorization: token ".$this->githubapikey,
                        "User-Agent: PHP"
            ]);

            $response = curl_exec($curl);
            return $response;
        } catch (\RuntimeException $e) {
            return null;
        }
    }
    /*
     * ==== get_action_pref
     * Retourne la valeur pour une préf action (ex: apikey)
     * @param [string] $key le mot-clé
     * @return [string] valeur ou vide
    */
    private function get_action_pref($key, $default = null)
    {
        $regex = '#' . $key . ' *\= *(.*)\n#';
        if (preg_match($regex, $this->params->get('actionprefs'). PHP_EOL, $val) == 1) {
            return trim($val[1]);
        } elseif (! is_null($default)) {
            return $default;
        }
        return false;

    }
    /*
    *  Vérifie si UP-list-actions-version-v<versionUP>.txt existe
    */
    private function loadActionsSha256()
    {
        $file = $this->upPath.'/assets/UP-list-actions-version-v'.$this->_up_version().'.txt';
        if (!is_file($file)) {
            return false;
        }
        $readBuffer = file($file, FILE_IGNORE_NEW_LINES);
        $outBuffer = '';
        if (!$readBuffer) {// `file` couldn't read the htaccess we can't do anything at this point
            return '';
        }
        $cgLines = false;
        foreach ($readBuffer as $line) {
            $one = explode(':', $line);
            if (sizeof($one) > 1) {
                $this->actionsha256[$one[0]] = $one[1];
            }
        }
    }
    /*
    *  Vérifie la version du fichier <action>.php par rapport au fichier version des actions
    */
    private function checkactionsha256($action)
    {
        $dir = $this->upPath.'actions/' . $action;
        $file = $dir. '/' . $action . '.php';
        if (!is_dir($dir) || !is_file($file)) { // non trouvé : do nothing
            return;
        }
        $hash = hash_file('sha256', $file);
        if (array_key_exists($action, $this->actionsha256)) {
            if ($this->actionsha256[$action] != $hash) { // différent : suppression du répertoire
                $this->delete_directory($dir);
            }
        }
    }
    /*
    *  récupération de la version de UP
    */
    private function _up_version()
    {
        $vers = '?';
        $fic = $this->upPath . 'up.xml';
        if (file_exists($fic)) {
            $xml = simplexml_load_file($fic);
            $vers = $xml->version;
        }
        return $vers;
    }
    /* from https://www.w3docs.com/snippets/php/how-do-i-recursively-delete-a-directory-and-its-entire-contents-files-sub-dirs-in-php.html
    */
    private function delete_directory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..' || $item == 'custom') {
                continue;
            }
            if (!$this->delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }

}

// class
