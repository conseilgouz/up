<?php

/**
 *
 * @package plg_UP for Joomla!
 * @author Lomart
 * @copyright (c) 2026 Lomart
 * @license   <a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 *
 * */
/*
v5.3.3 : php 8.4/8.5 compatibility
v5.3.3 : check/load actions from github
v5.4.1 : variables publiques dans up.php
v5.4.2 : cleanup checkfiles
v6.0.14 : activer si API ou administrator
v6.0.21 : suppression de l'appel à upAction.php
          set github key if defined
v6.1.0 : add admin actions list
         secure ajax calls
*/

namespace Lomart\Plugin\Content\Up\Extension;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Version;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\File;
use Joomla\Registry\Registry;
use Lomart\Plugin\Content\Up\Helper\UpHelper;
use Joomla\Component\Content\Administrator\Model\ArticleModel;
use Joomla\Component\Modules\Administrator\Model\ModuleModel;

// #[AllowDynamicProperties] // php 8.4

class UP extends CMSPlugin implements SubscriberInterface
{
    public $upPath = 'plugins/content/up/';
    public $actionPath;
    public $actionprefs;
    public $actionUserName;
    public $array_subtitle;
    public $article;
    public $artid;
    public $art_attr;
    public $art_model;
    public $attr;
    public $attr_download;
    public $attr_style_icon_image;
    public $attr_view;
    public $basepath;
    public $categories;
    public $catItems;
    public $catRootIDs;
    public $cat_attr;
    public $cat_model;
    public $class2style;
    public $content;
    public $cssmsg;
    public $catIndex;
    public $date_terms;
    public $debug;
    public $debugMsg;
    public $decorate;
    public $demopage;
    public $dico;
    public $dirLogs;
    public $ext_types;
    public $filepath;
    public $firstInstance;
    public $folders_exclude;
    public $frequency;
    public $inedit;
    public $inprod;
    public $info;
    public $invalid;
    public $J4;
    public $level;
    public $link;
    public $main_class;
    public $multicpt;
    public $name;
    public $nivacces;
    public $options;
    public $options_user;
    public $out;
    public $priority;
    public $replace_len;
    public $replace_deb;
    public $result;
    public $srcset_path;
    public $styles_main;
    public $tags_list_attr;
    public $tarteaucitron;
    public $tradaction;
    public $trad;
    public $tradup;
    public $trimA0;
    public $today;
    public $urlhelpsite;
    public $usehelpsite;
    public $valid_type;
    public $varStyle;
    public $varStyleString;
    public $withoutCustom;

    public $githubapikey = null;
    public $githuburl = 'https://api.github.com/repos/conseilgouz/up/contents/';
    public $githuburlzip = 'https://api.github.com/repos/conseilgouz/up6-actionszip/contents/';
    public $actionsha256 = [];
    // liste des actions disponibles dans le répertoire zip de Github
    public $actionsZip = ['box', 'image_gallery','mapael','marquee','meteo_concept','pdf','slider_tiny','upscsscompiler'];
    public $admin_actions = ['addclass','addcsshead','addcodehead','addfilehead','addhtml','addscript','cache_cleaner','get','html','image_secure','jextensions_list','jmenus_list','jmenus_metadata',
                             'jmodules_list','php','php_error','site_stat','site_visit','sitemap','snippet','sql','upactionslist','upbtn_makefile','upclass2style',
                             'upfilescleaner','upscsscompiler','upprefset'
                             ];

    /**
     * @inheritDoc
     *
     * @return string[]
     *
     * @since 4.1.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepare' 	    => 'onContentPrepare',
            'onContentBeforeSave'   => 'onContentBeforeSave',
            'onExtensionBeforeSave'  => 'onExtensionBeforeSave',
            'onAjaxUp'              => 'onAjaxUp'
        ];
    }


    public function __construct(&$subject, $config = [])
    {
        parent::__construct($subject, $config);
        if (!$config) { // on vient d'une action : recharger les paramètres généraux du plugin
            $up = PluginHelper::getPlugin('content', 'up');
            $this->params = new Registry();
            $this->params->set('def', json_decode($up->params));
        }
        $this->LoadLanguage();
        UpHelper::loadActionsSha256($this);
    }
    public function onExtensionBeforeSave(\Joomla\CMS\Event\Model\BeforeSaveEvent $event)
    {
        $context = $event->getContext();
        if ($context != 'com_modules.module') {
            return;
        }
        $user = Factory::getApplication()->getIdentity();
        if ($user->authorise('core.manage')) {
            return;
        }
        $table   = $event->getItem();
        $isNew   = $event->getIsNew();
        $data    = $event->getData();
        $model = new ModuleModel();
        $old = $model->getItem($data['id']);
        $oldtext = $old->content;
        $newtext = $data['content'];
        $this->checkUPAdmin($event, $newtext, $oldtext);
    }
    public function onContentBeforeSave(\Joomla\CMS\Event\Model\BeforeSaveEvent $event)
    {
        $context = $event->getContext();
        $table   = $event->getItem();
        $isNew   = $event->getIsNew();
        $data    = $event->getData();
        $user = Factory::getApplication()->getIdentity();
        if ($user->authorise('core.manage')) {
            return;
        }
        if ($context != 'com_content.article' && $context != 'com_content.form' && $context != 'com_categories.category') {
            $tag = $this->params->def('tagname', 'up');
            foreach ($data as $one) {
                if (is_string($one) && strpos($one, '{'.$tag.' ') !== false) {
                    $event->addResult(false);
                    $this->getApplication()->enqueueMessage('Non autorisé : up dans '.$context, 'error');
                    return false;
                }
            }
            return;
        }
        if ($context == 'com_categories.category') {
            $model = new \Joomla\Component\Categories\Administrator\Model\CategoryModel();
            $newtext = $data['description'];
            $old = $model->getItem($data['id']);
            $oldtext = $old->description;
        } else {// article
            $model = new ArticleModel();
            $newtext = $data['articletext'];
            $old = $model->getItem($data['id']);
            $oldtext = $old->introtext.$old->fulltext;
        }
        $this->checkUPAdmin($event, $newtext, $oldtext);
    }
    private function checkUPAdmin($event, $newtext, $oldtext)
    {
        $tag = $this->params->def('tagname', 'up');
        $regexopen = '/\{(?:' . $tag . ') *([^\s\=\|\{\}]+)/si';
        if (! preg_match($regexopen, $newtext)) {
            return true; // pas de UP
        }
        $admin_actions = $this->params->def('actionadmin', '');
        if (!$admin_actions) {// non défini : on prend les valeurs par défaut
            $admin_actions = $this->admin_actions;
            ;
        } else {
            $admin_actions = explode(',', $admin_actions);
        }
        $regex = '#(<p>)(\{\/?' . $tag . '\b.*\})(</p>)#U';
        // récuperation de l'ancienne version
        $oldtext = preg_replace($regex, '$2', $oldtext);
        // on a des balises UP : vérification des balises réservées
        $newtext = preg_replace($regex, '$2', $newtext);
        // ===== RECHERCHE DE TOUS LES SHORTCODES OUVRANTS
        if (! preg_match_all($regexopen, $newtext, $matches, PREG_OFFSET_CAPTURE)) {
            UpHelper::info_debug($this, 'Error up.php 132');
        }
        $nbSC = count($matches[0]);
        $openSC = [];
        $ix = 0;
        for ($i = 0; $i < $nbSC; $i++) {
            $deb = $matches[1][$i][1];
            $end = strpos($newtext, '}', $matches[1][$i][1]);
            $str = substr($newtext, $deb, $end - $deb);
            $one = $this->normalize_action_name($str);
            if (!in_array($one, $admin_actions)) {
                continue;
            }
            $openSC[$ix]['posDeb'] = $deb;
            $openSC[$ix]['posEnd'] = $end;
            $openSC[$ix]['actionName'] = $one;
            $ix++;
        }
        $nbSC = count($openSC);
        if (!$nbSC) { // aucun mot réservé : on sort
            return;
        }
        // check in previous version
        if (! preg_match_all($regexopen, $oldtext, $matches, PREG_OFFSET_CAPTURE)) {
            UpHelper::info_debug($this, 'Error up.php 132');
        }
        $nbOldSC = count($matches[0]);
        $oldActions = [];
        for ($i = 0; $i < $nbOldSC; $i++) {
            $deb = $matches[1][$i][1];
            $end = strpos($oldtext, '}', $matches[1][$i][1]);
            $str = substr($oldtext, $deb, $end - $deb);
            $one = $this->normalize_action_name($str);
            if (!in_array($one, $admin_actions)) {
                continue;
            }
            $oldActions[] = str_replace('<br />', '<br>', substr($oldtext, $deb, $end - $deb)); // normalize <br>
        }
        $nbOldSC = count($oldActions);
        // les actions non autorisées étaient-elles dans l'ancienne version ?
        // Si oui et qu'elles n'ont pas été modifiées, c'est OK
        // Si non, erreur
        for ($i = 0; $i < $nbSC; $i++) {
            $actionNew = substr($newtext, $openSC[$i]['posDeb'], $openSC[$i]['posEnd'] - $openSC[$i]['posDeb']);
            $actionNew = str_replace('<br />', '<br>', $actionNew); // normalize <br>
            if (in_array($actionNew, $oldActions)) {
                continue;
            }
            $event->addResult(false);
            $this->getApplication()->enqueueMessage('Non autorisé : '.$actionNew, 'error');
            // return false;
        }

    }
    private function normalize_action_name(String $action): String
    {
        // charger le dictionnaire
        if (!$this->dico) {
            $this->dico = file_get_contents(JPATH_SITE.'/'.$this->upPath.'dico.json');
            $this->dico = json_decode($this->dico, true);
        }
        $param = preg_split("/=/", trim($action, " \t\n\r\0\x0B\xA0\xC2"), 2); // permet = dans argument
        $action = str_ireplace(array(
                    '<p>',
                    '</p>',
                    '<br>',
                    '<br/>',
                    '<br />',
                    '&nbsp;',
                    PHP_EOL,
                    '{',
                    '}'
                ), '', $param[0]);
        $action = str_replace('-', '_', $action);
        $action = trim(strtolower($action));
        if (isset($this->dico[$action])) {
            $action = $this->dico[$action];
            $action = str_replace('-', '_', $action); // tel que le script
            $action = trim(strtolower($action));
        }
        return $action;
    }
    public function onContentPrepare(ContentPrepareEvent $event) // ($context, &$article, &$params, $limitstart = 0)
    {
        $context = $event->getContext();
        $article = $event->getItem();
        $params = $event->getParams();
        $app = Factory::getApplication();
        $tdeb = microtime(true);
        $debug = false;

        // ========> DOIT-ON EXECUTER ?
        if ($app->isClient('administrator') && ($context != 'com_content.article')) {
            return;
        }
        if ($context == 'com_search.search') { // v2.9
            return;
        }
        if ($context == 'com_finder.indexer') { // v2.9
            // les identificateurs autorisés pour UP
            $tags = $this->params->def('tagname', 'up');
            // les actions pour lesquelles il est dangereux de montrer le contenu
            // et celles avec des shortcodes internes
            $ActionMaskedContent = $this->params->def('searchActionMaskedContent', 'note|filter');

            // 1 - effacer les shortcodes et contenus des actions confidentielles
            $regex = '#(\{(?:' . $tags . ') *(?:' . $ActionMaskedContent . ').*\{\/(?:up)\s*(?:' . $ActionMaskedContent . ').*\})#Ui';
            $article->text = preg_replace($regex, '', $article->text);

            // 2 - masquer les shortcodes ouvrants et/ou uniques
            $regex = '#(\{(?:' . $tags . '|===).*\})#Ui';
            $article->text = preg_replace($regex, '', $article->text);

            // 2 - masquer les shortcodes fermants
            $regex = '#(\{\/(?:' . $tags . ').*\})#Ui';
            $article->text = preg_replace($regex, '', $article->text);

            return;
        }

        // sortie directe si pas de texte a traiter
        if (! isset($article->text)) {
            return false;
        }
        if (trim($article->text) == '') {
            return false;
        }
        // Chargement systematique de la feuile de style
        if ($this->params->def('loadcss', '1')) {
            $wa = $app->getDocument()->getWebAssetManager();
            try { // conflit avec RegularLbas
                $wa->registerAndUseStyle('upcss', 'plugins/content/up/assets/up.css');
            } catch (\Joomla\CMS\WebAsset\Exception\InvalidActionException $e) {
                // ignore
            } catch (\Exception $e) {
                // ignore
            }
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
            $tag = $this->params->def('tagname', 'up');
            $regexopen = '/\{(?:' . $tag . ') *([^\s\=\|\{\}]+)/si';
            // retour direct si pas de upAction dans l'article
            if (! preg_match($regexopen, $article->text)) {
                return false;
            }

            // ==========> C'EST BON, IL FAUT Y ALLER !
            // fonctions utilitaires pour les actions
            // include_once $this->upPath . 'upAction.php';

            // charger le dictionnaire
            $this->dico = file_get_contents(JPATH_SITE.'/'.$this->upPath.'dico.json');
            $this->dico = json_decode($this->dico, true);

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
                UpHelper::info_debug($this, 'Error up.php 132');
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
                unset($this->actionUserName); // nom de l'action saisi dans le shortcode
                unset($this->actionClassName); // nom du dossier, script php et classe de l'action
                unset($this->options_user);
                $content = ''; // le contenu entre shortcodes
                unset($ret); // retour par action
                // identifiant unique pour l'action
                if (isset($article->id)) { // si article
                    // $loopStr = ($loopId > 0) ? '-' . $loopId : '';
                    $loopStr = ($loopId > 0) ? chr($loopId + 64) : '';
                    $this->options_user['id'] = 'up-' . $article->id . '-' . $loopStr. ($i + 1);
                    // $options_user['id'] = 'up-' . $article->id . '-' . ($i + 1);
                } else { // si module
                    $this->options_user['id'] = 'up-m' . uniqid();
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
                $actionClassName = "";
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
                    if (! isset($this->actionUserName)) {
                        $this->actionUserName = $key; // tel que saisi dans article
                        // LM 180921: l'argument principal est égal à vide et pas true
                        $value = (count($param) == 2) ? trim($param[1]) : '';
                        // le mot clé traduit pour le script action
                        $key = str_replace('-', '_', $key); // tel que le script (14/12/19)
                        if (array_key_exists($key, $this->dico)) {
                            $key = $this->dico[$key];
                        }
                        $key = str_replace('-', '_', $key); // tel que le script
                        $actionClassName = $key; // Nom dossier et classe : Joomla 6/namespace
                    } else {
                        // le mot clé traduit pour le script action
                        if (array_key_exists($key, $this->dico)) {
                            $key = $this->dico[$key];
                        }
                    }
                    // guillemets double pour forcer un espace en tete ou fin - v3
                    if (isset($value[0]) && $value[0] == '"' && $value[strlen($value) - 1] == '"') {
                        $value = trim($value, '\"');
                    }
                    // analyse de l'argument de l'option
                    $this->options_user[$key] = $value;
                }
                // -- on recherche l'eventuel shortcode fermant
                $regexclose = '/\{\/(?:' . $tag . ')\s+' . $this->actionUserName . '.*\}/siU';
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
                // action autorisée ?
                $contexts = ['com_content.featured','com_content.article','com_content.form',
                             'com_content.category','com_content.categories','com_categories.category',
                             'com_modules.module','mod_custom.content'];
                if (!in_array($context, $contexts)) {
                    $admin_actions = $this->params->def('actionadmin', '');
                    if (!$admin_actions) {// non défini : on prend les valeurs par défaut
                        $admin_actions = $this->admin_actions;
                    } else {
                        $admin_actions = explode(',', $admin_actions);
                    }
                    $user = Factory::getApplication()->getIdentity();
                    if (!$user->authorise('core.manage') && in_array(strtolower($actionClassName), $admin_actions)) {
                        continue; // non autorisé : on passe
                    }
                }
                // ==== EXECUTION DE L'ACTION
                $text = '';
                // le chemin du script
                $actionfile = 'actions/' . $actionClassName . '/' . $actionClassName . '.php';
                if ($this->params->def('checkgithub', 0)) {
                    // contrôle de version de l'action sur github
                    UpHelper::checkactionsha256($this, $actionClassName);
                }
                // Mini UP : chargement des actions au 1er appel
                if (!is_file(JPATH_SITE.'/'.$this->upPath.$actionfile) && $this->is_github_action($actionClassName)) { // mini UP : action non chargée
                    $this->githubapikey = UpHelper::get_action_pref($this, 'github-key');
                    // récupération de l'action sous format zip
                    if (!UpHelper::getGithubActionZip($this, $actionClassName)) {
                        continue;  // error  ignore it
                    }
                    // exceptions : appel croisé dans les actions
                    if (($actionClassName == 'pdf_gallery')
                        || ($actionClassName == 'pdf')
                        || ($actionClassName == 'file_explorer')
                        || ($actionClassName == '_upgesterror')) {
                        if (!is_file(JPATH_SITE.'/'.$this->upPath.'actions/modal/modal.php')) {
                            if (!UpHelper::getGithubActionZip($this, 'modal')) {
                                continue;  // error  ignore it
                            }
                        }
                    }
                    if ($actionClassName == 'pdf_gallery') {
                        if (!is_file(JPATH_SITE.'/'.$this->upPath.'actions/pdf/pdf.php')) {
                            if (!UpHelper::getGithubActionZip($this, 'pdf')) {
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
                // include_once JPATH_SITE.'/'.$this->upPath . 'upAction.php'; // compatibilité UP avant 6
                // --- instanciation de l'action
                // si premier appel de l'action
                if ($text == '') {
                    if (array_key_exists($actionClassName, $classObjList) == false) {
                        // on charge la classe de l'action
                        if (@include_once JPATH_SITE.'/'.$this->upPath.$actionfile) {
                            $classObjList[$actionClassName] = new $actionClassName($actionClassName);
                            $classObjList[$actionClassName]->actionUserName = $this->actionUserName;
                            $classObjList[$actionClassName]->firstInstance = true; // pour action unique par page dans run
                            $classObjList[$actionClassName]->article = $article; // pour load_js_file_head
                            $objVersion = new Version(); // v2.6
                            $classObjList[$actionClassName]->J4 = ((int) $objVersion->getShortVersion() >= 4);
                            $classObjList[$actionClassName]->inedit = (!(isset($article->id) && empty($article->checked_out))); // v3.1
                            $classObjList[$actionClassName]->name = $actionClassName;
                            $classObjList[$actionClassName]->upPath =  str_replace('/', DIRECTORY_SEPARATOR, $this->upPath);
                            $classObjList[$actionClassName]->actionPath = $this->upPath . 'actions' . DIRECTORY_SEPARATOR . $actionClassName . DIRECTORY_SEPARATOR;
                            $classObjList[$actionClassName]->init();
                        } else {
                            $msg = ($this->actionUserName == '') ? 'Syntax error' : 'non trouvée / not found'; // v2.7
                            $text = '&#x1F199; ' . $this->options_user['id'] . ' Action "<b>' . $openSC[$i]['actionName'] . '</b>" ' . $msg;
                            $app->enqueueMessage($text, 'error');
                        }
                    } else {
                        $classObjList[$actionClassName] = new $actionClassName($actionClassName);
                        $classObjList[$actionClassName]->actionUserName = $this->actionUserName;
                        $classObjList[$actionClassName]->firstInstance = false; // pour action unique par page dans run
                        $classObjList[$actionClassName]->article = $article; // pour load_js_file_head
                        $objVersion = new Version(); // v2.6
                        $classObjList[$actionClassName]->J4 = ((int) $objVersion->getShortVersion() >= 4);
                        $classObjList[$actionClassName]->inedit = (!(isset($article->id) && empty($article->checked_out))); // v3.1
                        $classObjList[$actionClassName]->name = $actionClassName;
                        $classObjList[$actionClassName]->upPath =  str_replace('/', DIRECTORY_SEPARATOR, $this->upPath);
                        $classObjList[$actionClassName]->actionPath = $this->upPath . 'actions' . DIRECTORY_SEPARATOR . $actionClassName . DIRECTORY_SEPARATOR;
                        $classObjList[$actionClassName]->init();
                    }
                }

                if ($text == '') {
                    // l'objet est cree et initialisé
                    $classObjList[$actionClassName]->options_user = $this->options_user;
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
                    $classObjList[$actionClassName]->dico = $this->dico;
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
                $debug = ($debug || ! empty($this->options_user['debug']));
            } // fin parcours openSC

            unset($classObjList);
            $loopId++;
        } // while loopId

        if ($debug) { // v3
            $tfin = microtime(true);
            $msg = 'UP-' . $this->options_user['id'] . '-Execution time for ' . $nbSC . ' actions on the page or module : ' . (round(($tfin - $tdeb) * 1000, 3)) . ' ms';
            $app->enqueueMessage($msg);
        }
        return true;
    }

    // onContentPrepare

    /*
     * ==== onAjaxUp
     * appels AJAX pour toutes les actions
     */
    public function onAjaxUp($event)
    {
        Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

        $input = Factory::getApplication()->getInput();
        $data = $input->get('data', '', 'string');

        // Vérifie que l'action existe, sinon la charger (appel par upbtn.js)
        $exist = $input->get('exist', '', 'string');
        if ($exist) { // check plugin loaded
            // securité : seuls les admin peuvent activer une action absente
            $user = Factory::getApplication()->getIdentity();
            if (!$user->authorise('core.manage')) {
                return 'Error : action non chargée';
            }
            $actionfile = 'actions/' . $exist . '/' . $exist . '.php';
            // Mini UP : chargement des actions au 1er appel
            if ($this->is_github_action($exist)) { // mini UP : action non chargée
                if (!is_file(JPATH_SITE.'/'.$this->upPath.$actionfile)) { // mini UP : action non chargée
                    $this->githubapikey = UpHelper::get_action_pref($this, 'github-key');
                    if (!UpHelper::getGithubActionZip($this, $exist)) {
                        return $event->addResult(false); // non trouvé : erreur
                    }
                }
            } else {
                // $event->addMessage('Non trouvé');
                return $event->addResult(false);
            }
            return $event->addResult(true);
        }
        parse_str($data, $output);
        $up = $output['upid'];
        if (!$up || strpos($up, 'up-') === false) {
            die('No UP');
        }
        $session = Factory::getApplication()->getSession();
        $actionClassName = $session->get($up);
        if (!$actionClassName || !is_file(JPATH_SITE.'/'.$this->upPath . 'actions/' . $actionClassName . '/ajax_' . $actionClassName . '.php')) {
            die('No session');
        }
        // autres appels ajax
        if (isset($output['compil'])) { // SCSS compil ?
            $user = Factory::getApplication()->getIdentity();
            if (!$user->authorise('core.manage')) {
                return 'Error : non autorisé';
            }
            $arr = [];
            $arr['s'] = $input->get('s', '', 'integer');
            $arr['sl'] = $input->get('sl', '', 'integer');
            $arr['m'] = $input->get('m', '', 'integer');
            $arr['l'] = $input->get('l', '', 'integer');
            $arr['xl'] = $input->get('xl', '', 'integer');
            // mise à jour du fichier assets/custom/_variables.scss
            $this->store_scss($arr);
            // lancement de l'action upscsscompiler
            $this->compile_scss();
            // clear cache
            $cacheModel = Factory::getApplication()->bootComponent('com_cache')->getMVCFactory()->createModel('Cache', 'Administrator', ['ignore_request' => true]);
            $cache = $cacheModel->getCache() ?? null;
            if ($cache) {
                foreach ($cache->getAll() as $group) {
                    $cache->clean($group->group);
                }
            }
            $res = $event->addResult(true);
            return $res;
        }
        if (!$actionClassName) { // message incorrect
            $res = [false,'err : action incorrect'];
            return $event->addResult(json_encode($res));
        }
        $actionfile = JPATH_SITE.'/'.$this->upPath . 'actions/' . $actionClassName . '/ajax_' . $actionClassName . '.php';
        if (@include_once $actionfile) {
            $return = $actionClassName::goAjax($input);
            return $event->addResult($return);
        } else {
            echo 'Action Ajax non trouvée / not found';
            return $event->addResult(false);
        }
    }
    // check that action exists on github
    private function is_github_action(String $exist)
    {
        $file = JPATH_SITE.'/'.$this->upPath.'assets/UP-list-actions-version.txt';
        $actions = [];
        if (!is_file($file)) {
            return false;
        }
        $readBuffer = file($file, FILE_IGNORE_NEW_LINES);
        if (!$readBuffer) {// `file` couldn't read the htaccess we can't do anything at this point
            return '';
        }
        foreach ($readBuffer as $line) {
            $one = explode(':', $line);
            if (sizeof($one) > 1) {
                $actions[] = $one[0];
            }
        }
        return in_array($exist, $actions);
    }
    // sauvegarde des valeurs saisies et écriture dans custom/_variables.scss
    public function store_scss($sizes)
    {
        $basePath = JPATH_SITE .'/'. $this->upPath.'assets/';
        $scss_file = JPATH_SITE .'/'. $this->upPath.'assets/custom/_variables.scss';
        copy($scss_file, $basePath . 'custom/_variables.scss.bak');
        $current = [];
        $out = '';
        $readBuffer = file($scss_file, FILE_IGNORE_NEW_LINES);
        foreach ($readBuffer as $line) {
            if (substr($line, 0, 12) != '$breakpoint-') {
                $out .= $line.PHP_EOL;
                continue;
            }
            $one = explode(':', $line);
            $b = trim($one[0], '$breakpoint-');
            $s = trim($one[1], 'px;');
            $current[$b] = $s;
        }
        foreach ($sizes as $size => $val) {
            if (($val === 0) && isset($current[$size])) {
                unset($current[$size]);
            } elseif ($val) {
                $current[$size] = $val;
            }
        }
        foreach ($current as $size => $val) {
            $out .= '$breakpoint-'.$size.':'.$val.'px;'.PHP_EOL;
        }
        File::write($scss_file, $out);
    }
    // compilation des scss avec le fichier custom
    public function compile_scss()
    {
        $actionfile = 'actions/upscsscompiler/upscsscompiler.php';
        if (!is_file(JPATH_SITE.'/'.$this->upPath.$actionfile)) { // action non chargée
            $this->githubapikey = UpHelper::get_action_pref($this, 'github-key');
            if (!UpHelper::getGithubActionZip($this, 'upscsscompiler')) {
                return false;
            }
        }
        if (! class_exists('ScssPhp\ScssPhp\Compiler')) {
            require_once(JPATH_SITE.'/'.$this->upPath.'actions/upscsscompiler/vendor/autoload.php');
        }
        $scss_compiler = new \ScssPhp\ScssPhp\Compiler();
        $basePath = JPATH_SITE .'/'. $this->upPath.'assets/';
        $fileScss = $basePath . 'up.scss';
        $fileCss = str_replace('.scss', '.css', $fileScss);
        $scss_compiler->setImportPaths(pathinfo($fileScss, PATHINFO_DIRNAME));
        try {
            $string_sass = file_get_contents($fileScss);
            $result = $scss_compiler->compileString($string_sass);
            if ($result > '') {
                file_put_contents($fileCss, $result->getCss());
            }
        } catch (\Throwable $e) {
            $msg = UpHelper::trad_keyword($this, 'COMPIL_ERR');
            $msg .= str_replace($basePath, '', $fileScss);
            UpHelper::msg_error($this, $msg . '<br>' . $e->getmessage());
            return false;
        }
    }
}

// class
