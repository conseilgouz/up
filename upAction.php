<?php

/**
 * UP - Universal Plugin
 * Fonctions utilitaires pour les actions
 * @author    Lomart
 * @version   1.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 */
defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Environment\Browser;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;

class upAction extends plgContentUP
{
    public function __construct($name)
    {
        $this->name = $name;
        $this->upPath = str_replace('/', DIRECTORY_SEPARATOR, $this->upPath);
        $this->actionPath = $this->upPath . 'actions' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;

        if ($this->name == '') {
            throw new \Exception('Programming error upAction.construct');
        }

        return true;
    }

    /*
     * ===============================
     * FICHIERS & FLUX
     * ===============================
     */

    /*
     * ==== load_file
     * charge un fichier CSS ou JS du dossier d'une action
     * 27-6-18: prise en charge dossier custom
     * @param string $ficname : chemin, nom et extension du fichier
     * @return none
     */
    public function load_file($ficpath, $options = array(), $attributes = array())
    {
        $ficpath = $this->get_asset_path($ficpath);
        if ($ficpath != false) {
            switch (strtolower(pathinfo($ficpath, PATHINFO_EXTENSION))) {
                case 'css':
                    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
                    $ficpath = str_replace('\\', '/', $ficpath);
                    $explode = explode('/', $ficpath);
                    $name = $explode[sizeof($explode) - 1];
                    $name = str_replace('.', '', $name);
                    $wa->registerAndUseStyle($name, $ficpath);
                    // HTMLHelper::stylesheet($ficpath, $options, $attributes);
                    return true;

                case 'js':
                    HTMLHelper::_('jquery.framework');
                    $ficpath = str_replace('\\', '/', $ficpath);
                    $explode = explode('/', $ficpath);
                    $name = $explode[sizeof($explode) - 1];
                    $name = str_replace('.', '', $name);
                    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
                    $wa->registerAndUseScript($name, $ficpath);
                    // HTMLHelper::script($ficpath, $options, $attributes);
                    return true;

                default:
                    $this->msg_error(Text::sprintf('UP_FIC_BAD_EXT', $ficpath));
                    return false;
            }
        }
    }

    /*
     * get_asset_path
     * retourne le chemin vers un fichier css ou js d'une action
     * en vérifiant si une version perso existe dans le dossier custom
     * contient :// ou debute par // = url (cdn)
     * debute par / = chemin/fichier à partir racine site
     * sinon : chemin/fichier dans dossier action courante
     */
    public function get_asset_path($url)
    {
        $url = str_replace('\\', '/', trim($url));
        if (strpos($url, '://') !== false or substr($url, 0, 2) == '//') {
            // URL
            return $url;
        } elseif ($url[0] === '/') {
            // Chemin absolu, on supprime le slash de debut
            $url = ltrim($url, '/');
        } else {
            if (file_exists($this->actionPath . 'custom/' . $url) == true) {
                // fichier dans dossier de l'action
                $url = $this->actionPath . 'custom/' . $url;
            } else {
                $url = $this->actionPath . $url;
            }
        }
        if (file_exists($url) == false) {
            $this->msg_error(Text::sprintf('UP_FIC_NOT_FOUND', $url));
            return false;
        }

        return $url;
    }

    /*
     * ==== load_js_file_body
     * charge un fichier JS à la fin du contenu de l'article
     * Par defaut, le fichier est dans le dossier de l'action avec prise en charge sous-dossier custom
     * @param string $ficpath : chemin, nom et extension du fichier
     * @return none
     */
    public function load_js_file_body($ficpath)
    {
        $ficpath = $this->get_asset_path($ficpath);
        if (strtolower(pathinfo($ficpath, PATHINFO_EXTENSION)) == 'js') {
            $out = '<script type="text/javascript" src="' . $ficpath . '" defer></script>';
            $this->article->text .= $out;
            return true;
        } else {
            $this->msg_error(Text::sprintf('UP_FIC_BAD_EXT', $ficpath));
            return false;
        }
    }

    /*
     * ==== load_js_code
     * Ajoute du code JS dans le head de la page
     */
    public function load_js_code($code, $in_head = true)
    {
        if (strlen($this->supertrim($code)) > 0) {
            if ($in_head) {
                // $doc = Factory::getDocument();
                // $doc->addScriptDeclaration($code);
                $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
                $wa->addInlineScript($code);
                return '';
            } else {
                return '<script>' . $code . '</script>';
            }
        }
    }

    /*
     * ==== load_jquery_code
     * ajoute du code jQuery ($code) en l'encapsulant
     * Par défaut le code est ajouté dans le head ($in_head)
     * sinon, il sera à la position d'appel
     */
    public function load_jquery_code($code, $in_head = true)
    {
        HTMLHelper::_('jquery.framework'); // v52
        $tmp = 'jQuery(document).ready(function($) {';
        $tmp .= $code;
        $tmp .= '});';
        if ($in_head) {
            // ajout du code dans head
            // $doc = Factory::getDocument();
            // $doc->addScriptDeclaration($tmp);
            $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
            $wa->addInlineScript($tmp);
            return '';
        } else {
            // code pour insertion dans code
            $tmp = '<script>' . $tmp . '</script>';
            return $tmp;
        }
    }

    /*
     * ==== load_css_head
     * Ajoute du code CSS ($code) dans le head
     */
    public function load_css_head($code, $id = null)
    {
        if (trim($code)) { // v1.2
            // ---- remplacement ID
            if (is_null($id)) { // v2.3
                $id = isset($this->options_user['id']) ? '#' . $this->options_user['id'] : '';
            }
            if ($id) { // v2.3
                $id = '#' . ltrim($id, ' #');
            }
            $code = str_ireplace('#id', $id, $code); // v1.6
            // ---- supprime saut de ligne
            if (empty($this->trimA0)) {
                $code = preg_replace('/[ \t\n\r\0\x0B\xA0]+/', ' ', $code);
            } else {
                $code = preg_replace('/[ \t\n\r\0\x0B\xA0\xC2]+/', ' ', $code); // pb pour japon
            }
            // ---- bbcode
            $code = strip_tags($code);
            $code = str_replace('\[', '\{', $code);
            $code = str_replace('\]', '\}', $code);
            $code = str_replace('[', '{', $code);
            $code = str_replace(']', '}', $code);
            $code = str_replace('&gt;', '>', $code);
            $code = str_replace('&lt;', '<', $code);
            $code = str_replace('\{', '[', $code);
            $code = str_replace('\}', ']', $code);
            // ---- subtitution des classes
            // $regex = '/(?:.*@media.*)?\{(.*)\}/U';
            $regex = '#\{(.*)[\}@]#U';
            if (preg_match_all($regex, $code, $matches)) {
                foreach ($matches[1] as $classStyle) {
                    $classStyle = trim($classStyle, ';');
                    $style = $this->replace_class2style($classStyle, 'css-head');
                    if ($classStyle != $style) {
                        $code = str_replace($classStyle, $style, $code);
                    }
                }
            }
            // ---- ajout css dans head
            // $doc = Factory::getDocument();
            // $doc->addStyleDeclaration($code);

            $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
            $wa->addInlineStyle($code);

            return true;
        }
        return false;
    }


    /*
     * ==== load_custom_code_head
     * Ajoute du code libre ($code) dans le head de la page
     * exemple :
     * <link href="https://fonts.googleapis.com/css?family=xxx" rel="stylesheet">
     */
    public function load_custom_code_head($code)
    {
        if (strlen($this->supertrim($code)) > 0) {
            $doc = Factory::getApplication()->getDocument();
            $doc->addCustomTag($code);
            return true;
        }
        return false;
    }

    /*
     * ==== get_html_contents
     * Récupère un flux sur le web ($url) avec un timeout de 5s ($timeout)
     * @return [string] [le contenu recuperer]
     * NOTE : il peut être utile de fournir une URL encodée : urlencode($url)
     */
    public function get_html_contents($url, $timeout = 10, $url2 = '')
    {
        $ctx = stream_context_create(array(
            'http' => array(
                'timeout' => $timeout
            )
        ));

        $niv = ini_get('display_errors'); // v4
        ini_set('display_errors', 0);
        $out = file_get_contents($url, 0, $ctx);
        if ($out === false) {
            if ($url2 != '') {
                $out = file_get_contents($url2, 0, $ctx);
                ini_set('display_errors', $niv);
                if ($out !== false) {
                    return $out;
                }
            }
            $this->msg_error(Text::sprintf('UP_TIMEOUT_FOR', $url));
            ini_set('display_errors', $niv);
            return '';
        } else {
            ini_set('display_errors', $niv);
            return $out;
        }
    }

    /*
     * ==== get_url_relative (ancien nom 2.3 : get_url)
     * retourne l'url sous forme relative
     * ajoute le dossier racine du site si besoin
     * images/foo.png -> images/foo.png OU /rootFolder/images/foo.png
     * //unsite.fr/foo -> //unsite.fr/foo
     * ftp://foo.png -> ftp://foo.png
     */
    public function get_url_relative($url, $urlencode = false)
    {
        $url = trim($url);
        $url = str_replace('\\', '/', $url);
        if (strpos($url, '//') === false) {
            $root = Uri::root(true);
            if ($url[0] != '/') {
                $url = '/' . $url;
            }
            $url = $root . $url;
        }
        if ($urlencode) {
            $url = urlencode($url);
        }
        return $url;
    }

    /*
     * ==== get_url_absolute (ancien nom get_full_url)
     * retourne l'URL sous forme absolue
     * images/foo.png -> https://site.fr/images/foo.png
     * //unsite.fr/foo -> //unsite.fr/foo
     * ftp://foo.png -> ftp://foo.png
     */
    public function get_url_absolute($url, $urlencode = false)
    {
        $url = trim($url);
        $url = str_replace('\\', '/', $url);
        // if (strpos($url, '//') === false) { // 5.1
        $url = Uri::root() . $url;
        // }
        if ($urlencode) {
            $url = urlencode($url);
        }
        return $url;
    }

    /**
     * encoder les URL selon la RFC 3986.
     */
    public function myUrlEncode($url)
    {
        $entities = array(
            '%21',
            '%2A',
            '%27',
            '%28',
            '%29',
            '%3B',
            '%3A',
            '%40',
            '%26',
            '%3D',
            '%2B',
            '%24',
            '%2C',
            '%2F',
            '%3F',
            '%25',
            '%23',
            '%5B',
            '%5D'
        );
        $replacements = array(
            '!',
            '*',
            "'",
            "(",
            ")",
            ";",
            ":",
            "@",
            "&",
            "=",
            "+",
            "$",
            ",",
            "/",
            "?",
            "%",
            "#",
            "[",
            "]"
        );
        return str_replace($entities, $replacements, urlencode($url));
    }

    /*
     * ==== on_server
     * Retourne TRUE si l'URL est sur le serveur
     */
    public function on_server($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        return ($_SERVER['HTTP_HOST'] == $host || $host == null);
    }

    /*
     * ==== load_upcss
     * a appeller par la méthode init d'une action
     * pour forcer le chargement de la feuille de style de UP
     */
    public function load_upcss()
    {
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('upcss', $this->upPath . 'assets/up.css');
        return true;
    }

    /*
     * ==== get_custom_path
     * ajoute custom à $path si le $file existe dans ce dossier
     * $file : nom du fichier
     * $path : chemin vers fichier. si NULL = chemin de l'action
     * retourne chemin relatif complet vers le fichier
     * ou false si aucun des 2 fichiers n'existe
     */
    public function get_custom_path($file, $path = null, $alert = true)
    {
        if (is_null($path)) {
            $path = $this->actionPath;
        }
        if (file_exists($path . 'custom/' . $file) === true) {
            return $path . 'custom/' . $file;
        } elseif (file_exists($path . $file) === true) {
            return $path . $file;
        }
        // aucun fichier n'existe
        if ($alert) {
            $this->msg_error(Text::sprintf('UP_FIC_NOT_FOUND', $path . $file));
        }
        return false;
    }

    /*
     * Retourne un tableau avec le contenu du fichier INI
     * Gère existence et cohérence fichier
     * $alert=false permet de tester l'existance silencieusement
     * Retour : un array vide ou avec le contenu du INI
     */
    public function load_inifile($file, $sections = false, $alert = true)
    {
        if (file_exists($file) === false) {
            if ($alert) {
                $this->msg_error(Text::sprintf('UP_FIC_NOT_FOUND', $file));
            }
            return array();
        }
        $out = parse_ini_file($file, $sections);
        if ($out === false) {
            $this->msg_error(Text::sprintf('UP_SYNTAX_ERROR', $file));
            $out = array();
        }
        return $out;
    }

    /*
     * ===============================
     * CHAINE DE CARACTERES
     * ===============================
     */

    /*
     * ==== str_append
     * Ajoute une chaine 'non vide' à une autre en insérant un séparateur
     * ex: str_append('titre','soustitre',' ','<small>','</small>')
     * retourne: 'titre <small>soustitre</small>'
     * @param string $str chaine cible
     * @param string $add chaine à ajouter
     * @param string $sep séparateur
     * @param string $prefix texte avant la chaine
     * @param string $suffix texte après la chaine
     * @return string chaine completée
     */
    public function str_append($str, $add, $sep = ' ', $prefix = '', $suffix = '')
    {
        $str = (is_null($str) ? '' : $str); // v2.9
        $add = (empty($add)) ? '' : trim($add);
        if (! empty($add)) {
            $str = trim($str);
            if ($str && substr($str, strlen($sep) * -1) != $sep) {
                $str .= $sep;
            }
            $str .= $prefix . $add . $suffix;
        }
        return $str;
    }

    /* ==== versions raccourcies de str_append qui modifie directement la chaine d'origine */
    public function add_str(&$str, $add, $sep = ' ', $prefix = '', $suffix = '')
    {
        $str = $this->str_append($str, $add, $sep, $prefix, $suffix);
        return $str;
    }

    public function add_class(&$str, $newclass, $prefix = '')
    {
        $str = $this->str_append($str, $newclass, ' ', $prefix);
        return $str;
    }

    public function add_style(&$str, $property, $val)
    {
        $str = (string) $this->str_append($str, $val, ';', $property . ':');
        return $str;
    }

    /*
     * ==== kw_replace
     * $tmpl : chaine dans laquelle est fait le remplacement
     * $keyword : le mot-clé seul
     * $replace : valeur de remplacement
     * Formes admises :
     * ##keyword## : uniquement le keyword qui sera remplacé
     * ##keyword=condition # label:<b>%%</b>## : $keyword, condition et modèle. %% est l'emplacement remplacé
     */
    public function kw_replace(&$tmpl, $keyword, $replace)
    {
        $regex = '/\#\#' . $keyword . '([ =!<>\[]?.*)\#\#/Ui';
        preg_match_all($regex, $tmpl ?? '', $matches);
        for ($i = 0; $i < count($matches[0]); $i++) {
            $replace_val = $replace;
            if (empty($matches[1][$i])) {
                // le mot clé seul
                $model = '';
            } else {
                list($condition, $model) = explode(' # ', $matches[1][$i] . ' # ', 2);
                $condition = trim($condition);
                $model = trim($model, ' #');
                if ($condition) {
                    $compare_val = substr($condition, 1);
                    switch ($condition[0]) {
                        case '':
                            break;
                        case '=':
                            $replace_val = (! empty($replace_val) && strtolower($replace_val) == strtolower($compare_val)) ? $replace_val : '';
                            break;
                        case '!':
                            $ok = (! empty($replace_val));
                            $v1 = strtolower($replace_val);
                            $v2 = $compare_val;
                            $replace_val = (! empty($replace_val) && strtolower($replace_val) != strtolower($compare_val)) ? $replace_val : '';
                            break;
                        case '>':
                            $replace_val = (! empty($replace_val) && strtolower($replace_val) >= strtolower($compare_val)) ? $replace_val : '';
                            break;
                        case '<':
                            $replace_val = (! empty($replace_val) && strtolower($replace_val) < strtolower($compare_val)) ? $replace_val : '';
                            break;
                        case '[':
                            $choix = $this->strtoarray(trim($compare_val, ']'), ',', ':', false);
                            $replace_val = (isset($choix[$replace_val])) ? $choix[$replace_val] : $replace_val;
                            break;
                        default: // la fin d'un motclé avec la même racine
                            $replace_val = null;
                    }
                }
            }

            // le remplacement
            if (! is_null($replace_val)) { // non concerné
                if (! empty($model)) {
                    if (strpos($model, '%%') !== false) {
                        $replace_val = ($replace_val == '') ? '' : str_replace('%%', $replace_val, $model);
                    }
                }
                $tmpl = str_replace($matches[0][$i], $replace_val, $tmpl);
            }
        }
    }

    /*
     * ==== ctrl_unit
     * Retourne $size complété par $unit[0] si nécessaire
     * auto et inherit ne sont pas géré volontairement
     * $size valeur. ex: 10px, 10, 15%
     * $unit liste des unités autorisées.
     * @return
     */
    public function ctrl_unit(&$size, $unit = 'px,%,em,rem')
    {
        if (empty(trim($size))) {
            return trim($size);
        }
        $unit = array_map('trim', explode(',', strtolower($unit)));
        if (preg_match('#([0-9.]*)(.*)#', strtolower($size), $match)) {
            $size = intval($match[1]);
            if ($size > 0) {
                $size .= (in_array($match[2], $unit)) ? $match[2] : $unit[0];
            }
        }
        return $size;
    }

    /*
     * ==== convert_size
     * utilisé pour vérifier qu'un argument de taille utilise une unité permise
     * exemple
     * list($height_val, $height_unit) = $this->convert_size($height);
     * em, %, auto et inherit ne peuvent pas être géré
     * $size valeur. ex: 10px, 10, 1rem
     * $unit_target unité cible pour la conversion.
     * @return tableau avec [1] l'unité cible et [0] valeur dans cette unité
     */
    public function convert_size($size, $unit_target = 'px')
    {
        $val = (int) $size;
        $unit = substr($size, strlen(strval(intval($size))));
        switch (strtolower($unit)) {
            case 'px':
                $out[0] = ($unit_target == 'px') ? $val : ($val / 16);
                break;
            case 'rem':
                $out[0] = ($unit_target == 'px') ? $val * 16 : $val;
                break;
            default:
                $out[0] = $val; // a défaut !!!!
        }
        $out[1] = $unit_target;
        return $out;
    }

    /*
     * ==== link_humanize
     * Retourne l'UNC nettoyé des chemins, extensions, underscore, tiret
     * un tiret = espace, 2 tirets = 1 tirets
     * 3.0: underscore=tiret, 3tirets ou + = séparateur pour image-gallery" -- "
     * un compteur sour la forme 0123- devant le nom est supprimé. doit commencé par 0 et finir par un tiret
     * @var $unc [string] chemin fichier ou url (en général une image)
     * $capitalize [bool] 1ere lettre en majuscule
     * @return [string]
     */
    public function link_humanize($unc, $capitalize = true)
    {
        $out = pathinfo($unc, PATHINFO_FILENAME);
        // les underscores en tirets
        $out = str_replace('_', '-', $out);
        // supprime un compteur (00x-) au début
        $out = preg_replace('#^0[0-9]+\-#', '', $out);
        // 3 tirets ou plus comme séparateur
        $out = preg_replace('#(\-{3,})#', ' ?? ', $out);
        // 2 tirets ou plus comme tiret simple
        $out = preg_replace('#(\-{2})#', '?', $out);
        // on restaure les tirets à conserver
        $out = strtr($out, '-?', ' -');
        if ($capitalize) {
            $out = ucfirst($out);
        }
        return $out;
    }

    /*
     * ==== import_content($content)
     * retourne $content après prise en charge des plugins de contenu
     */
    public function import_content($content)
    {
        // recup content
        PluginHelper::importPlugin('content');
        // $out = ($item->fulltext == '') ? ($item->introtext) : ($item->fulltext);
        return HTMLHelper::_('content.prepare', $content);
    }

    /*
     * ==== preg_string
     * version rapide qui retourne la chaine trouvée par la regex
     * ex: preg_string('#alt="(.*)"#i', '<img alt="label">');
     * retourne label
     */
    public function preg_string($regex, $source)
    {
        if (preg_match($regex, $source, $match)) {
            return $match[1];
        }
        return '';
    }

    /*
     * ==== strtoarray
     * retourne une chaine au format 'un:1,2:deux'
     * sous la forme d'un tableau ['un']=>1 [2]=>'deux'
     * v1.8 : ajout $quote (pour )eviter quote pour sql_select > format.list
     * v1.8 : ajout array_map('trim',..
     */
    public function strtoarray($str, $row = ',', $col = ':', $quote = true)
    {
        $arr = array();
        if (! empty($str)) {
            foreach (explode($row, $str) as $el) {
                $el = (strpos($el, $col) === false) ? $el . $col . '' : $el;
                list($k, $v) = array_map('trim', explode($col, $el, 2));
                // supprime guillemet double pour préserver un espace - v3
                if ($v && $v[0] == '"' && $v[strlen($v) - 1] == '"') {
                    $v = trim($v, '\"');
                }
                $k = (! is_numeric($k) && $quote) ? "'" . $k . "'" : $k;
                $v = (! is_numeric($v) && $quote) ? "'" . $v . "'" : $v;
                $arr[$k] = $v;
            }
        }
        return $arr;
    }

    /*
     * ==== supertrim
     * supprime tous les types d'espace aux extrémités d'une chaine
     */
    public function supertrim($str, $add = '')
    {
        if (empty($str)) { // 5.1
            return '';
        }
        if (stripos($str, '<br>') < 5) { // v52
            $str = str_ireplace('<br>', '', $str);
        }
        if (stripos($str, '<br>') > strlen($str) - 5) {
            $str = str_ireplace('<br>', '', $str);
        }
        if (empty($this->trimA0)) {
            return trim($str, $add . " \t\n\r\0\x0B\xC2");
        } else {
            return trim($str, $add . " \t\n\r\0\x0B\xA0\xC2"); // pb pour japon
        }
    }

    /*
     * ==== spaceNormalize 5.1
     * remplace tous les espaces par des espaces simples
     */
    public function spaceNormalize($str, $add = '')
    {
        if (empty($str)) {
            return '';
        }
        $search = explode(',', "\t,\n,\r,\0,\x0B,\xC2" . $add);
        if (! empty($this->trimA0)) {
            $search[] = "\xA0";
        }
        return str_replace($search, ' ', $str);
    }

    /*
     * ===============================
     * HTML - MISE EN FORME
     * ===============================
     */

    /*
     * ==== get_attr_tag
     * retourne un array tous les attributs de la balise HTML ($tag)
     * $force est la liste des attributs a créer pour s'assurer de leurs disponibilités
     * ----------------------------------------------
     * Utilisation : modifier les attributs avant de reconstruire la balise
     */
    public function get_attr_tag($tag, $force = 'id,class,style')
    {
        if (empty($tag)) {
            return array();
        }
        // création du tableau avec les valeurs forcées
        foreach (explode(',', $force) as $key) {
            $attr[$key] = '';
        }
        // récupération des attributs de la balise
        if (preg_match_all('# (.*)="(.*)"#U', $tag, $matches)) {
            $tmp = array_combine(array_change_key_case($matches[1]), $matches[2]);
            $attr = array_merge($attr, $tmp);
        }
        return $attr;
    }

    /*
     * ==== set_attr_tag
     * retourne une chaine balise HTML avec ses attributs non vides
     * @var $tag string balise HTML. un underscore au début rend la balise optionnelle si pas d'attribut
     * @var $attr array liste des attributs (x=>null attribut sans valeur)
     * @var $close bool ou str tag fermant si true ou contenu avant balise fermante
     * ----------------------------------------------
     * Utilisations :
     * reconstruire la balise apres modification des attributs
     * ----------------------------------------------
     * 2/11/19 : recup attributs seuls si $tag=''
     * v2.5 : retourne $close si $tag='0'
     * *******************************
     */
    public function set_attr_tag($tag, $attr, $close = false, $doublequote = true, $bbcode = false)
    {
        // v2.5 si $tag=0 ou vide, on retourne le contenu sans tag et attributs
        if (empty($tag)) {
            return $close;
        }
        // si aucun attribut
        if (count(array_filter($attr)) == 0) {
            // inutile de retourner <div></div>
            if ($close === true) {
                return '';
            }
            if ($tag[0] === '_') {
                return $close;
            }
        }
        $tag = ltrim($tag, '_');
        $opentag = ($bbcode) ? '[' : '<';
        $closetag = ($bbcode) ? ']' : '>';
        // c'est parti
        $out = ($tag) ? $opentag . $tag : '';
        foreach ($attr as $key => $val) {
            if ($val === null) {
                // attribut sans valeur
                $out .= ' ' . $key;
            } elseif (is_string($val)) {
                if (trim($val) !== '') {
                    if ($doublequote) {
                        $out .= ' ' . $key . '="' . trim($val) . '"';
                    } else {
                        $out .= ' ' . $key . "='" . trim($val) . "'";
                    }
                }
            }
        }
        $out .= ($tag) ? $closetag : '';
        if ($close === false) { // juste la balise ouvrante
            return $out;
        }

        if ($close !== true) { // on ajoute le contenu
            $out .= $close;
        }
        $out .= $opentag . '/' . $tag . $closetag;

        return $out;
    }

    /*
     * Actualise $attr_array avec les valeurs d'options
     * analyse et ventile class et style
     * exemple:
     * get_attr_style($attr_main, $options['class'], $options['style'])
     * utilisé par center pour passer les infos dans une seule option
     * get_attr_style($attr_inner, $options[__class__]);
     */
    public function get_attr_style(&$attr_array, ...$args)
    {
        foreach ($args as $arg) {
            // $infos = preg_split("/[\s;\xC2\xA0]+/", $arg);
            $infos = array_map('trim', explode(';', $arg));
            foreach ($infos as $info) {
                if (strpos($info, ':')) {
                    $attr_array['style'] = isset($attr_array['style']) ? $attr_array['style'] : '';
                    $attr_array['style'] .= ($attr_array['style']) ? ';' . $info : $info;
                } else {
                    $attr_array['class'] = isset($attr_array['class']) ? $attr_array['class'] : '';
                    $attr_array['class'] .= ($attr_array['class']) ? ' ' . $info : $info;
                }
            }
        }
        return $attr_array;
    }

    /*
     * Retourne $content après nettoyage/mise en forme
     * '0' : retourne a l'identique
     * '1' : neutralise le code HTML qui devient lisible
     * liste des tags autorises sous la forme 'a,img,b'
     */
    public function clean_HTML($content, $tags = false, $forceEOL = false)
    {
        switch ($tags) {
            case '0': // aucun traitement
                break;
            case '1': // on affiche le code
                $content = htmlspecialchars($content);
                break;
            default: // on supprime toutes les balises sauf $tags et on affiche
                $tags = str_replace(' ', '', $tags);
                $tags = '<' . str_replace(',', '><', $tags) . '>';
                $content = strip_tags($content, $tags);
                break;
        }
        if ($forceEOL) {
            $content = nl2br(trim($content));
        }
        return $content;
    }

    /*
     * Utilisé pour convertir du code
     * saisie user : .foo[content:'\[red\]']
     * converti en : .foo{content:'[red]'}
     */
    public function get_code($code, $quote = false)
    {
        if ($quote) { // v51 pour passer code json
            $code = preg_replace('/[^a-zA-Z0-9:\[\]\,]/', '', $code);
            $code = str_replace(array(
                '[',
                ']',
                ':',
                ','
            ), array(
                '["',
                '"]',
                '":"',
                '","'
            ), $code);
            $code = str_replace(array(
                ':"[',
                ']"'
            ), array(
                ':[',
                ']'
            ), $code);
        }
        $code = strip_tags($code);
        $code = html_entity_decode($code); // v5.1
        $code = str_replace(array(
            '[',
            ']'
        ), array(
            '{',
            '}'
        ), $code);
        $code = str_replace(array(
            '\{',
            '\}'
        ), array(
            '[',
            ']'
        ), $code);
        $code = str_replace('&gt;', '>', $code);
        $code = str_replace('&lt;', '<', $code);
        return $code;
    }

    /*
     * remplace du code HTML sous la forme BBCode
     * exemple : [b class="foo"]gras\[1\][/b] -> <b class="foo">gras[1]</b>
     * $tags est la liste des balises HTML autorisées
     * - vide : la liste par defaut
     * - xx|yy : uniquement les balises xx et yy
     * - +xx|yy : la liste par defaut + les balises xx et yy
     */
    public function get_bbcode($arg, $tags = null)
    {
        if (empty($arg)) { // v3
            return;
        }
        $arg = html_entity_decode($arg);
        if (strpos($arg, '[') !== false) {
            // --- les balises à conserver
            $deftags = 'a|br|br /|p|h2|h3|h4|h5|h6|div|span|b|i|u|img |small|sup|sub|quote|ul|ol|li|code|mark|tt|kbd';
            if (empty($tags)) { // ou null
                $tags = $deftags;
            } elseif ($tags[0] == '+') {
                $tags = $deftags . '|' . substr($tags, 1);
            }

            // --- neutraliser les crochets échappés
            $arg = str_replace('\[', '§*', $arg);
            $arg = str_replace('\]', '*§', $arg);
            // --- conversion en html
            $regex = '#\[(\/?(' . $tags . ')\b.*)\]#iU';
            $arg = preg_replace($regex, '<$1>', $arg);
            // --- restaurer les crochets neutralisés
            $arg = str_replace('§*', '[', $arg);
            $arg = str_replace('*§', ']', $arg);
            // --- normaliser les URL
            if (stripos($arg, 'src=') !== false) {
                $regex = '#src=[\'"]{1}(.*)[\'"]{1}#iUm';
                preg_match_all($regex, $arg, $res);
                foreach ($res[1] as $url) {
                    str_replace($url, $this->get_url_absolute($url), $arg);
                }
            }
        }
        return $arg;
    }

    /*
     * ===============================
     * OPTIONS ACTIONS
     * ===============================
     */

    /*
     * ==== CTRL_OPTIONS
     * retourne un array avec toutes les options geres par l'action
     * avec les valeurs saisies dans le shortcode
     * ou celles dans custom/prefs.ini (v1.4)
     * ou celles du jeu d'options dans prefs.ini (v1.7)
     * la recherche des keys est case-insensitive
     * les cles retournees sont case-sensitive
     * $optmask est une regex pour vérifier si une options non définie est permise (v1.8)
     * toutes= '#.*#', se termine par= '#\-(?:mot1|mot2)$#'
     * ----------------------------------------------
     * Utilisation : tableau de toutes les options pretes a l'emploi
     * *******************************
     */
    public function ctrl_options($options_def, $js_options_def = [], $optmask = '')
    {
        // === création options génériques
        $options_def['prefset'] = (isset($options_def['prefset'])) ? $options_def['prefset'] : '';
        foreach ($options_def as $key => $val) {
            // -- créer les options indicées pour éviter les erreurs
            // todo : les créer par le script action ??
            if (substr($key, -2) == '-*') {
                for ($i = 1; $i <= 12; $i++) {
                    $options_def[substr($key, 0, -2) . '-' . $i] = '';
                }
            }
        }

        // === si l'action n'a pas d'argument, on met la valeur par defaut
        /*
         * v2.5 pour prise en charge valeur prefs.ini [options]
         * if ($this->options_user[$this->name] === '') {
         * $this->options_user[$this->name] = $options_def[$this->name];
         * }
         */
        // === fusion tableau def
        // il s'agit des valeurs par défaut définies par le developpeur de l'action
        $out = array_merge($options_def, $js_options_def);

        // -- table de correspondance pour recherche case insensitive
        foreach ($out as $key => $val) {
            $out_lowercase[strtolower($key)] = $key;
        }
        // -- recherche prefs webmaster et prefset dans dossier custom de l'action
        $pref_user_file = $this->get_custom_path('prefs.ini', null, false);
        if ($pref_user_file !== false) {
            $pref_user = $this->load_inifile($pref_user_file, true);
            if ($pref_user !== false) {
                $sets = array(); // list prefset
                // si option principale est le nom d'une section
                if (isset($pref_user[$this->options_user[$this->name]])) {
                    $sets[] = $this->options_user[$this->name];
                    $this->options_user['prefset'] = $this->options_user[$this->name]; // pour debug
                    $this->options_user[$this->name] = ''; // pour arret traitement
                } elseif (isset($this->options_user['prefset'])) {
                    // si prefset argumenté
                    if (! isset($pref_user[$this->options_user['prefset']])) {
                        $this->msg_error(Text::sprintf('UP_FIC_NOT_FOUND', $this->options_user['prefset']));
                    } else {
                        $sets[] = $this->options_user['prefset'];
                    }
                }

                // le jeu d'options par defaut
                if (isset($pref_user['options'])) {
                    $sets[] = 'options';
                }

                foreach ($sets as $set) {
                    foreach ((array) $pref_user[$set] as $key => $val) {
                        $k2 = (isset($out_lowercase[strtolower($key)])) ? $out_lowercase[strtolower($key)] : null;
                        if ($k2) {
                            settype($val, gettype($out[$key]));
                            $out[$k2] = $val;
                            // si prefset, on ajoute pour only_using_option
                            // v1.9.2 if ($set != 'options' && !array_key_exists(strtolower($key), $this->options_user)) {
                            if (! array_key_exists(strtolower($key), $this->options_user)) {
                                $this->options_user[strtolower($key)] = $val;
                            }
                        } else {
                            if ($optmask && preg_match($optmask, $key) == 1) {
                                // on affecte seulement si pas dans surchargé dans shortcode
                                if (! isset($this->options_user[strtolower($key)])) {
                                    $this->options_user[strtolower($key)] = $val;
                                }
                                $out[strtolower($key)] = $val;
                            } else {
                                $this->msg_error(Text::sprintf('UP_PREFSET_NOT_FOUND', $key));
                            }
                        }
                    }
                }
            } else {
                $this->msg_error(Text::sprintf('UP_SYNTAX_ERROR', $pref_user_file));
            }
        }

        // -- ajout des valeurs saisies par utilisateur
        foreach ($this->options_user as $key => $val) {
            if (array_key_exists($key, $out_lowercase)) {
                $key = $out_lowercase[$key];
                if (! is_bool($out[$key]) && is_string($val)) { // v3.0 admet true et false
                    $val = (strtolower($val) == 'true') ? 1 : $val;
                    $val = (strtolower($val) == 'false') ? 0 : $val;
                    settype($val, gettype($out[$key]));
                }
                // egal valeur saisie sauf si key=nom action sans argument
                if ($key != $this->name || $val != '') {
                    $out[$key] = $val;
                }
            } else {
                if ($optmask && preg_match($optmask, $key) == 1) {
                    $this->options_user[strtolower($key)] = $val;
                    $out[strtolower($key)] = $val;
                } else {
                    // on prévient si le motclé n'est pas géré
                    if (! in_array($key, array(
                        'id',
                        '?',
                        'debug'
                    )) && substr($key, -1, 1) != '*') {
                        $this->msg_error(Text::sprintf('UP_UNKNOWN_OPTION', $key . '=' . $val));
                        $this->options_user['?'] = true; // force affichage aide (1 seule fois)
                    }
                }
            }
        }
        // -- traduction pour
        foreach ($out as $key => $val) {
            if (is_string($val) && $val) {
                $out[$key] = $this->lang($val);
            }
        }

        // demande d'aide
        if (array_key_exists('?', $this->options_user)) {
            $info = $this->up_action_options($this->name);
            $title = $this->name;
            if ($this->usehelpsite > 0 && $this->demopage != '') {
                $title .= ' [ <a href="' . $this->demopage . '"';
                if ($this->usehelpsite == 2) {
                    $title .= ' target = "_blank"';
                }
                $title .= '>DEMO</a>]';
            }
            $txt = '<div>';
            $infos = $this->up_action_infos($this->name); // mod v2.8
            $txt .= $infos['_shortdesc'] . '<br>';
            $txt .= $infos['_longdesc'];
            $info_webmaster = $this->up_help_txt(); // v1.9.5
            $info_webmaster .= $this->up_prefset_list(); // v1.9.5
            if ($info_webmaster) {
                $txt .= '<hr>' . $info_webmaster;
            }
            $txt .= '<hr>';
            foreach ($info as $key => $val) {
                if (is_numeric($key)) {
                    $txt .= "<b>&#x25A0; <u>$val</u></b><br>"; // v3.0
                } else {
                    $txt .= "<b>$key</b>&nbsp;:&nbsp;$val<br>";
                }
            }
            $txt .= '</div>';
            $this->msg_info($txt, Text::sprintf('UP_ACTION_OPTIONS', $title));
        }
        // demande debug
        if (array_key_exists('debug', $this->options_user)) {
            $debug = '<ul>';
            foreach ($out as $key => $val) {
                if (is_array($val)) {
                    $val = '[' . implode(',', $val) . ']';
                }
                $debug .= "<li><b>$key</b>&nbsp;=>&nbsp;" . htmlentities($val) . "</li>";
            }
            $debug .= '</ul>';
            $debug .= $this->up_help_txt(); // v1.9.5
            $debug .= $this->up_prefset_list();
            $this->msg_info($debug, Text::sprintf('UP_INFOS_DEBUG', $this->actionUserName));
        }

        // -- on retourne un array avec les cles dans la case attendue par le script
        // et les valeurs saisies par utilisateur
        return $out;
    }

    /*
     * ==== set_option_user_if_true
     * affecte $val au paramètre user si saisi sans argument (égal à true)
     * modifie directement le contenu de la propriété options_user
     * ------ exemple pour media_plyr
     * $this->set_option_user_if_true('mp4', $ficname . '.mp4');
     */
    public function set_option_user_if_true($option, $val)
    {
        if (isset($this->options_user[$option])) {
            if ($this->options_user[$option] == 1 || $this->options_user[$option] == '') { // v2.7-php8
                $this->options_user[$option] = $val;
            }
        }
    }

    /*
     * ==== js_actualise // v5.1
     * pour la prise compte par only_using_options, une option doit
     * - valeur différente de celle de $js_options_def
     * - saisie par utlisateur
     * - optionnel: actualiser pour lecture dans $options
     */
    public function js_actualise($actionName, $val, &$options, &$js_options_def)
    {
        $valnull = (is_numeric($val)) ? 9999999999 : '9999999999';
        $js_options_def[$actionName] = $valnull;
        $options[$actionName] = $val;
        $this->options_user[strtolower($actionName)] = $val;
    }

    /*
     * ==== only_using_options
     * retourne un array avec uniquement les parametres saisi dans le shortcode
     * la recherche des keys est case-insensitive
     * ----------------------------------------------
     * Utilisations :
     * - isoler les parametres JS
     * - reduire la chaine json d'initialisation
     */
    public function only_using_options($options_def, $options_user = null)
    {
        $out = [];
        // permet de tester un autre jeu d'options. ex: image_pannellum
        if (is_null($options_user)) {
            $options_user = $this->options_user;
        } else {
            // on force key en minuscule
            $options_user = array_change_key_case($options_user, CASE_LOWER);
        }
        // -- table pour recherche case insensitive
        foreach ($options_def as $key => $val) {
            $options_key[strtolower($key)] = $key;
        }

        // -- recup des params JS du shortcode
        foreach ($options_user as $key => $val) {
            if (array_key_exists($key, $options_key)) {
                $key = $options_key[$key];
                $type = gettype($options_def[$key]);
                if (is_bool($options_def[$key]) && is_string($val)) { // v3.0
                    $val = (strtolower($val) == 'true' || $val == 1) ? true : false;
                } else {
                    settype($val, gettype($options_def[$key]));
                }
                if ($val != $options_def[$key]) { // pas si valeur par defaut
                    $out[$key] = $val;
                }
            }
        }
        // -- on retourne un array avec la cle dans la case attendue par le script
        return $out;
    }

    /*
     * ==== ctrl_argument
     * contrôle que l'argument soit dans la liste (sep virgule)
     * corrige la case silencieusement si nécessaire
     * retourne l'argument ou le 1er si non trouvé
     * 12/07/18: teste valeur vide. ex: ',un,deux' ou 'un,,deux'
     */
    public function ctrl_argument($arg, $autorized_list, $debug = true)
    {
        $array_autorized_list = array_map('trim', explode(',', $autorized_list));
        foreach ($array_autorized_list as $val) {
            if (trim(strtolower($arg)) == trim(strtolower($val))) {
                return $val;
            }
        }
        if ($debug) {
            $this->msg_error(Text::sprintf('UP_UNKNOWN_ARGUMENT', $arg, $autorized_list));
        }
        return $array_autorized_list[0]; // on force sur 1er pour éviter erreur
    }

    /*
     * ==== get_action_pref
     * Retourne la valeur pour une préf action (ex: apikey)
     * @param [string] $key le mot-clé
     * @return [string] valeur ou vide
     */
    public function get_action_pref($key, $default = null)
    {
        $regex = '#' . $key . ' *\= *(.*)\n#';
        if (preg_match($regex, $this->actionprefs . PHP_EOL, $val) == 1) {
            return trim($val[1]);
        } elseif (! is_null($default)) {
            return $default;
        }
        return false;
    }

    /*
     * === params_decode (v1.6)
     * Analyse une LIGNE de paramètres ($params)separes par $sep_param
     * Chaque parametre est composee d'un mot-cle, de sep_key et d'une valeur :
     * 'key1:val1, " key:2 ":" v""a\"2,0 ", key3:val3:x, key4, key5:false, key6:lang[fr=oui;en=yes]'
     * - ['key1'] => 'val1' : parametre simple
     * - ['key:2'] => ' v"a"2,0 ' :
     * - separateurs autorises entre guillemets.
     * - guillemets permis si double ("") ou echappe (\")
     * - on conserve les espaces entre guillemets seulement pour val
     * - ['key3'] => 'val3:x' : on ignore sep_key avant sep_param
     * - ['key4'] => true : key sans valeur = true
     * - ['key5'] => false : les valeurs true, false et null sont affecte comme TRUE, FALSE et NULL
     * - ['key6'] => 'oui' : les valeurs sont traduites si lang[..]
     * ----
     * a utiliser pour une liste d'options non gerees par l'action pour un script JS
     * Retourne un array qui pourra etre utilise comme sous-cle :
     * $js_params[key] = param_decode($str);
     * ou combiner avec les options JS
     * $js_params = array_merge($js_params, param_decode($str);
     */
    public function params_decode($str, $sep_param = ',', $sep_key = ':', $quote = '"', $echap = '\\')
    {
        $iskey = true; // on debute toujours par une key
        $yaquote = false; // test si entre guillemets
        $mot = ''; // key ou val non encore affecte
        $key = ''; // key en cours
        $dico = array(
            'true' => true,
            'false' => false,
            'null' => null
        );

        // ajout sep_param en fin pour affecter le dernier
        $str .= $sep_param;
        // analyse
        for ($i = 0; $i < strlen($str); $i++) {
            if ($str[$i] == $sep_param) {
                if ($yaquote) {
                    // si entre quotes : on conserve
                    $mot .= $sep_param;
                } else {
                    if ($iskey) { // arg sans valeur = true
                        $key = trim($mot);
                        $out[$key] = true;
                    } else {
                        // --- on enregistre le param
                        // on garde les espaces entre guillemets
                        $s2 = str_replace(chr(11), ' ', trim($mot, " \t\n\r\0"));
                        // on type true,false,null
                        if (array_key_exists(strtolower($s2), $dico)) {
                            $s2 = $dico[strtolower($s2)];
                        }
                        // on traduit
                        if (substr(strtolower($s2), 0, 5) == 'lang[') {
                            $s2 = $this->lang($s2);
                        }
                        // on ajoute au tableau resultat
                        $out[$key] = $s2;
                    }
                    $iskey = true;
                    $mot = '';
                }
            } elseif ($str[$i] == $sep_key) {
                if ($yaquote || ! $iskey) {
                    // si entre quotes ou sep_param dans valeur : on conserve
                    $mot .= $sep_key;
                } else {
                    // on recupere la key propre
                    $key = trim($mot);
                    $mot = '';
                    $iskey = false;
                }
            } elseif ($str[$i] == $quote || $str[$i] == $echap) {
                if ($i < strlen($str) - 1 && $str[$i + 1] == $quote) {
                    $mot .= $str[$i + 1];
                    $i++; // quote doublé ou echappé
                } else {
                    $yaquote = ! $yaquote;
                }
            } else {
                if ($yaquote && $str[$i] == ' ') {
                    // on conserve les espaces entre quotes
                    $mot .= chr(11); // VT
                } else {
                    $mot .= $str[$i];
                }
            }
        }

        return $out;
    }

    /*
     * ==== get_db_value
     * retourne une valeur unique
     * $select : non du champ a retourner
     * $table : nom de la table (sans #__)
     * $where : condition sous la forme : nomChamp=valeur
     *
     */
    public function get_db_value($select, $table, $where)
    {
        list($k, $v) = explode('=', $where);
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select($db->quoteName($select))
            ->from($db->quoteName('#__' . $table))
            ->where($db->quoteName($k) . '="' . $v . '"');
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }

    /*
     * ===============================
     * JSON
     * ===============================
     */

    /*
     * ==== get_jsontoarray
     * retourne le contenu d'un fichier json dans un array
     */
    public function get_jsontoarray($filename, $ficpath = '')
    {
        if ($ficpath == '') {
            $filename = $this->actionPath . $filename;
        }
        if (file_exists($filename)) {
            $tmp = file_get_contents($filename);
            return json_decode($tmp, true);
        } else {
            $this->msg_error(Text::sprintf('UP_FILE_NOT_FOUND', $filename));
            return false;
        }
    }

    /*
     * ==== json_arrtostr
     * Retourne une chaîne JSON à partir de $array.
     * mode:1 = fct php json_encode
     * mode:2 = fct perso sans guillemets
     * mode:3 = fct php json_encode + suppression doubles crochets si array
     * bracket si on entoure d'accolade
     */
    public function json_arrtostr($array, $mode = 1, $bracket = true)
    {
        if (empty($array)) {
            return ($bracket) ? '{}' : '';
        }
        switch ($mode) {
            // méthode PHP
            case 1:
                $out = json_encode($array, JSON_UNESCAPED_SLASHES);
                if (! $bracket) {
                    $out = substr($out, 1, -1);
                }
                break;

                // méthode perso sans guillemet et gestion sous-clés
            case 2:
                $out = '';
                foreach ($array as $key => $val) {
                    if (trim($val) || $val == 0) {
                        // guillemet autour des arguments texte sauf si [1,2,3]
                        if (is_string($val) && $val[0] != '[') {
                            $val = '"' . $val . '"';
                        }
                        // ajout séparateur
                        $out .= ($out) ? ',' : '';
                        // si c'est une sous-clé
                        if (strpos($val, ':') > 0) {
                            $out .= $key . ':' . ' {
								' . $val . '
							}';
                        } else {
                            $out .= $key . ':' . $val;
                        }
                    }
                }
                if ($out) {
                    $out = ($bracket) ? ' {' . $out . '}' : $out;
                }
                break;
                // méthode PHP avec identification array
            case 3:
                $out = json_encode($array, JSON_UNESCAPED_SLASHES);
                $out = str_replace(array(
                    '["[',
                    ']"]'
                ), array(
                    '[',
                    "]"
                ), $out);
                if (! $bracket) {
                    $out = substr($out, 1, -1);
                }
                break;
        }
        return $out;
    }

    /*
     * ===============================
     * CONTENU ACTIONS
     * ===============================
     */

    /*
     * ==== ctrl_content_exists
     * teste si le shortode contient du contenu, affiche un message si besoin
     * @return [bool] [true si contenu]
     */
    public function ctrl_content_exists()
    {
        if (trim($this->content) == '') {
            $this->msg_error(Text::_('UP_NO_CONTENT'));
            return false;
        }
        return true;
    }

    /*
     * ==== ctrl_content_parts
     * retourne vrai si $content contient différentes parties séparées par {===}
     */
    public function ctrl_content_parts($content)
    {
        $ok = strpos($content, '{===') !== false;
        return $ok;
    }

    /*
     * ==== get_content_parts
     * retourne un array avec les différentes parties séparées par {===}
     * en supprimant les balises <p> mise par l'éditeur wysiwyg
     * v1.7: {=== texte } est permis. supprime partie vide
     */
    public function get_content_parts($content)
    {
        $content_part = array();
        $tmp = preg_split('/(?:\<(?:p|div)\>)?\{\={3,}.*\}(?:\<\/(?:p|div)\>)?/iU', $content);
        foreach ($tmp as $key => $val) {
            $val = trim($val);
            // on supprime les BR de début et fin v5.2
            $val = preg_replace('/^(\<br\s?\/?\>)(.*)/iU', '$2', $val);
            $val = preg_replace('/(.*)(\<br\s?\/?\>)$/iU', '$1', $val);
            // on supprime les mi-tags
            if (substr($val, 0, 4) == '</p>') {
                $val = substr($val, 5);
            }
            if (substr($val, -3, 3) == '<p>') {
                $val = substr($val, 0, strlen($val) - 3);
            }

            $content_part[] = $this->supertrim($val);
        }
        return $content_part;
    }

    /*
     * ==== get_content_shortcode
     * retourne un tableau avec tous les shortcodes avec $keyword
     * Exemple pour : $content = {key=1 | opt=xyz}{foo=x}{key=2 | foo=abc}
     * retourne : Array (
     * [0] => Array ( [key] => 1 [opt] => xyz )
     * [1] => Array ( [key] => 2 [foo] => abc ) )
     */
    public function get_content_shortcode($content, $keyword = '.*')
    {
        $content = strip_tags($content);
        $regex = '#\{(' . $keyword . '[\s\=\|].*)\}#siU';
        $out = array();
        $i = 0;
        if (preg_match_all($regex, $content, $matches) > 0) {
            foreach ($matches[1] as $item) {
                $arr = explode('|', $item);
                foreach ($arr as $sc) {
                    $tmp = preg_split("/=/", trim($sc), 2);
                    $key = $this->supertrim($tmp[0]);
                    $key = strtolower($key); // v2.3
                    // sa valeur (true si aucune)
                    $value = (count($tmp) == 2) ? trim($tmp[1]) : true;
                    $out[$i][$key] = $value;
                }
                $i++;
            }
        }
        return $out;
    }

    /*
     * === get_content_csv
     * retourne chaque ligne du texte dans un tableau
     * $content : le texte à analyser
     * $cleanTags : on supprime toutes les balises HTML
     * sauf celles indiquées ("ul,a")
     * ou aucune si false
     * $bbcode : on convertit les bbcodes par défaut si vide
     * les bbcodes indiqués (a|br|p)
     * sauf si false
     * ---
     * utilisation
     */
    public function get_content_csv($content, $cleanTags = '', $bbcode = '')
    {
        // === nettoyage éditeur wysiwyg
        if (str_contains($content, '<br')) { //5.2
            $content = str_replace('<p>', '', $content);
        } else {
            $content = str_replace('<p>', '<br>', $content);
        }
        $content = str_replace('</p>', '', $content);
        $content = str_replace('<br />', PHP_EOL, $content);
        $content = str_replace('<br>', PHP_EOL, $content); // v4

        // === Analyse et nettoyage du contenu
        if ($cleanTags !== false) {
            $cleanTags = '<' . implode('><', explode(',', $cleanTags)) . '>';
            $content = strip_tags($content, $cleanTags);
        }

        // === Supprime espace et saut de ligne
        $content = trim($content);

        // ===
        if ($bbcode !== false) {
            if ($bbcode === '') {
                $content = $this->get_bbcode($content);
            } else {
                $content = $this->get_bbcode($content, $bbcode);
            }
        }
        // === retourne un tableau des lignes
        $out = array_map('trim', explode(PHP_EOL, $content));
        return $out;
    }

    /*
     * ==== filter_ok
     * retourne True si toutes les conditions sont remplies
     * $conditions est un tableau type condition => valeur
     * ou une chaine : type:val;type:valmin-valmax
     * v2.5 : $if_empty = retour si pas de conditions
     * v5.1 : ajout comparaison smaller, equal, bigger
     */
    public function filter_ok($conditions, $if_empty = true)
    {
        if (is_string($conditions)) {
            if (trim($conditions) == '') {
                return $if_empty;
            }
            $conditions = $this->params_decode($conditions, ';', ':');
        }
        date_default_timezone_set('Europe/Paris');
        $user = Factory::getApplication()->getIdentity();
        foreach ($conditions as $key => $val) {
            $ok = false;
            $not = ($key[0] == '!');
            $key = ($not) ? substr($key, 1) : $key;

            // v5.1
            switch ($key) {
                // --------------- Date
                case 'datemax':
                    $val = (str_pad($val, 12, '9'));
                    $ok = (date('YmdHi') <= $val);
                    break;
                case 'datemin':
                    $val = (str_pad($val, 12, '0'));
                    $ok = (date('YmdHi') >= $val);
                    break;
                case 'period':
                    $now = date('YmdHi');
                    $sep = (strpos($val, '-') == 0) ? ',' : '-';
                    $plages = array_map('trim', explode($sep, $val));
                    // normaliser les dates en YYYYMMJJHHMM
                    foreach ($plages as $key => $plage) {
                        // si mois sans année
                        $plage = (substr($plage, 0, 2) > '12') ? $plage : date('Y') . $plage;
                        $plages[$key] = str_pad($plage, 12, '0');
                    }
                    // si date fin < date début (ex: 1225,0102)
                    if ($plages[0] > $plages[1]) {
                        $plages[1] = date('YmdHi', strtotime("+1 year", strtotime($plages[1])));
                    }
                    $ok = ($now > $plages[0] && $now < $plages[1]);
                    break;
                case 'day':
                    $tmp = (date("w")) ? date("w") : 7;
                    $ok = (in_array($tmp, explode(',', $val)));
                    break;
                case 'month':
                    $tmp = (date("n")) ? date("n") : date("n") + 1;
                    $ok = (in_array($tmp, explode(',', $val)));
                    break;

                    // --------------- Heure
                case 'hmax':
                    $val = (str_pad($val, 4, '0'));
                    $ok = (date('Hi') <= $val);
                    break;
                case 'hmin':
                    $val = (str_pad($val, 4, '0'));
                    $ok = (date('Hi') >= $val);
                    break;
                case 'hperiod':
                    $plages = explode(',', trim($val));
                    $now = date('Hi');
                    foreach ($plages as $plage) {
                        $heure = explode('-', trim($plage) . '-');
                        $ok = $ok || ((str_pad($heure[0], 4, '0') <= $now) && ($now <= str_pad($heure[1], 4, '0')));
                    }
                    break;

                    // --------------- Utilisateur
                case 'guest':
                    $ok = ($user->guest == intval($val));
                    break;
                case 'admin':
                    $ok = ($val == intval(in_array(8, $user->groups)));
                    break;
                case 'user':
                    $ok = (in_array($user->id, explode(',', $val)));
                    break;
                case 'username':
                    $ok = (in_array($user->username, explode(',', $val)));
                    break;
                case 'group':
                    foreach ($user->groups as $tmp) {
                        $ok = $ok || in_array($tmp, explode(',', $val));
                    }
                    break;

                    // --------------- Langue
                case 'lang':
                    $lang = strtolower(Factory::getApplication()->getLanguage()->getTag());
                    $ok = array_intersect(explode('-', $lang), explode(',', $val));
                    break;

                    // --------------- Divers
                case 'mobile':
                    $browser = Browser::getInstance();
                    $ok = ($browser->isMobile() == $val);
                    break;
                case 'homepage':
                    // j'utilise une comparaison d'url au lieu de la méthode classique
                    // qui ne distingue pas le blog d'un article
                    $root_link = str_replace('/index.php', '', Uri::root());
                    $current_link = preg_replace('/index.php(\/)?/', '', Uri::current(true));
                    $ok = (intval($current_link == $root_link) == $val);
                    break;

                    // --------------- webmaster
                case 'server-host':
                    foreach (explode(',', $val) as $host) {
                        $ok = ($ok || (stripos($_SERVER['HTTP_HOST'], $host) !== false));
                    }
                    break;
                case 'server-ip':
                    $ip = $_SERVER['SERVER_ADDR'];
                    $tab = array_map('trim', explode(',', $val));
                    $ok = (in_array($ip, $tab) === true);
                    break;
                    // --- ID
                case 'artid':
                    $app = Factory::getApplication();
                    $artid = $app->getInput()->get('id');
                    $tab = array_map('trim', explode(',', $val));
                    $ok = (in_array($artid, $tab) === true);
                    break;
                case 'catid':
                    $app = Factory::getApplication();
                    $input = $app->getInput();
                    if ($input->getCmd('option') == 'com_content' && $input->getCmd('view') == 'article') {
                        $cmodel   = new Joomla\Component\Content\Site\Model\ArticleModel(array('ignore_request' => true));
                        $app       = Factory::getApplication();
                        $appParams = $app->getParams();
                        $params = $appParams;
                        $cmodel->setState('params', $appParams);
                        $catid = $cmodel->getItem($app->getInput()->get('id'))->catid;
                    }
                    $tab = array_map('trim', explode(',', $val));
                    $ok = (in_array($catid, $tab) === true);
                    break;
                case 'menuid':
                    $app = Factory::getApplication();
                    $menuid = $app->getMenu()->getActive()->id;
                    $tab = array_map('trim', explode(',', $val));
                    $ok = (in_array($menuid, $tab) === true);
                    break;
                    // --- Comparaison
                case 'equal':
                    list($op1, $op2) = array_map('trim', explode(',', strtolower($val)));
                    $ok = ($op1 == $op2);
                    break;
                case 'smaller':
                    list($op1, $op2) = array_map('trim', explode(',', strtolower($val)));
                    $ok = ($op1 > $op2);
                    break;
                case 'bigger':
                    list($op1, $op2) = array_map('trim', explode(',', strtolower($val)));
                    $ok = ($op1 < $op2);
                    break;
            } // switch
            if ($ok == $not) {
                return $key;
            }
        } // foreach
        return true;
    }

    /*
     * ===============================
     * GESTION INTERNE UP
     * ===============================
     */

    /*
     * ==== set_demopage
     * affecte la propriété demopage avec l'URL de la page d'aide
     * v1.8 : si 0, upActionsList n'affiche pas la doc lors demande pour toutes les actions
     * uniquement pour l'action seule lors préparation de la page demo
     */
    public function set_demopage($webpage = '')
    {
        if ($webpage == '') {
            // on remplace les underscores du nom de la classe
            // par des tirets pour compatibilité avec les alias Joomla
            $this->demopage = $this->urlhelpsite . '/demo/action-' . str_replace('_', '-', $this->name);
        } else {
            $this->demopage = $webpage;
        }
    }

    /*
     * ==== up_actions_list
     * @return [array] la liste des actions
     */
    public function up_actions_list($exclude_prefix = '_,x_')
    {
        $actionsFolder = __DIR__ . DIRECTORY_SEPARATOR . 'actions' . DIRECTORY_SEPARATOR;
        $list = array(); // retour si vide
        $actionsPathList = glob($actionsFolder . '*', GLOB_ONLYDIR);

        $prefix = array_map('trim', explode(',', $exclude_prefix));
        foreach ($actionsPathList as $e) {
            $file = substr($e, strlen($actionsFolder));
            $ok = true;
            foreach ($prefix as $p) {
                $res = stripos($file, $p);
                $ok = ($ok && stripos($file, $p) !== 0);
            }
            $phpfile = $actionsFolder . $file . DIRECTORY_SEPARATOR . $file . '.php'; // v2.6 si dossier vide
            if ($ok && file_exists($phpfile)) {
                $list[] = $file;
            }
        }
        return $list;
    }

    /*
     * ==== up_prefset_list (v1.7)
     * @return [string] liste des sections du prefs.ini
     */
    public function up_prefset_list($action_name = null, $full = true)
    {
        if (is_null($action_name)) {
            $pref_user_file = $this->actionPath . 'custom/prefs.ini';
        } else {
            $pref_user_file = $this->upPath . 'actions/' . $action_name . '/custom/prefs.ini';
        }
        if (file_exists($pref_user_file)) {
            $pref_user = $this->load_inifile($pref_user_file, true);
            if (isset($pref_user)) {
                if ($full === false) {
                    $out = implode(', ', array_keys($pref_user));
                } else {
                    $out = '';
                    foreach ($pref_user as $pref => $opts) {
                        if ($pref == 'options' && empty($opts)) {
                            continue;
                        }
                        $pref .= ($pref == 'options') ? ' (default)' : '';
                        $new = true;
                        $out .= '<br><b><u>' . $pref . '</u> : </b> ';
                        foreach ($opts as $opt => $val) {
                            $out .= ($new) ? '' : '<b> | </b>';
                            $out .= '<b>' . $opt . '</b>=' . htmlentities($val);
                            $new = false;
                        }
                    }
                }
            }
        }
        return (empty($out)) ? '' : '<b>&#x1f7e9; ' . $this->actionUserName . ' PREFS.INI</b> : ' . $out;
    }

    /*
     * ==== get_dico_synonym
     * Retourne une liste de tous les synonymes d'un mot-clé
     * @param [string] $keyword [nom du mot clé]
     * @return [string] [synonyme sour la forme: 1,un,one,ein ]
     */
    public function get_dico_synonym($keyword)
    {
        $out = array();
        foreach ($this->dico as $key => $val) {
            if ($val == $keyword) {
                $out[] = $key;
            }
        }
        return implode(',', $out);
    }

    /*
     * ==== shortcode2code
     * Retourne la chaine avec un shortcode UP neutralisé pour doc
     * @param [string] $str [ligne à annalyser]
     * @return [string] [ligne avec shortcode neutralisé]
     */
    public function shortcode2code($str)
    {
        $motif = '#(?:\&\#123;|\{)(.*)(?:\&\#125;|\})#U';
        $replace = '<code><b>{</b>$1<b>}</b></code>';
        $out = preg_replace($motif, $replace, $str);
        $out = str_replace('[', '<b>[</b>', $out);
        return $out;
    }

    /*
     * ==== up_action_infos
     * Retourne les infos dans l'entête du script PHP de l'action
     * @param [string] $action_name nom de l'action
     * @param [string] $keys les infos a chercher
     * @return [array] les infos de l'entete sous la forme : key => commentaire
     */
    public function up_action_infos($action_name, $lang = null)
    {
        $actionFolder = $this->upPath . 'actions/' . $action_name . '/';
        if (! file_exists($actionFolder . $action_name . '.php')) {
            return 'Action <b>' . $action_name . '</b> : erreur de structure des dossiers.';
        }
        $tmp = file_get_contents($actionFolder . $action_name . '.php');

        $out = array(); // v1.2
        // info dans entete script
        $desc = array();
        if (preg_match('#\/\*\*(.*)\*\/#siU', $tmp, $desc)) {
            $desc = array_map('trim', explode(' * ', $desc[1])); // v3 ' * ' évite les * dans texte
            $desc = str_replace('{', '&#123;', $desc); // inactive les shortcodes dans commentaires
            $out['_shortdesc'] = '';
            $out['_longdesc'] = '';
            $out['_credit'] = '';

            foreach ($desc as $lign) {
                $lign = trim($lign, ' *');
                if ($lign) {
                    if ($lign[0] == '@') { // ligne avec @motcle contenu - mod v2.8
                        list($key, $val) = explode(' ', $lign . ' ', 2);
                        if (trim($val)) {
                            $out['_credit'] .= '<b>' . $key . ': </b>' . $val . '  ';
                        }
                    } else {
                        // ligne description
                        if ($out['_shortdesc'] > '') {
                            $lign = $this->shortcode2code($lign);
                            $this->add_str($out['_longdesc'], $lign, '<br />');
                        } else {
                            $out['_shortdesc'] = $lign;
                        }
                    }
                }
            }
        }

        // Traduction disponible ?
        if (is_null($lang)) {
            $lang = Factory::getApplication()->getLanguage()->getTag();
        }
        $infos_trad = array();
        if (file_exists($actionFolder . 'up/' . $lang . '.ini')) {
            $filename = $actionFolder . 'up/' . $lang . '.ini';
            $str = file_get_contents($filename);
            $infos_trad = $this->load_inifile($actionFolder . 'up/' . $lang . '.ini');
            if (isset($infos_trad['shortdesc'])) {
                $out['_shortdesc'] = $infos_trad['shortdesc'];
            }
            if (isset($infos_trad['longdesc'])) {
                $out['_longdesc'] = $this->shortcode2code($infos_trad['longdesc']);
            }
        }

        // Site de démonstration
        $out['_demopage'] = '';
        if (preg_match('#\$this->set_demopage\([w"]?(.*)[w"]?\)#', $tmp, $arrtmp) === 1) {
            if ($arrtmp[1] == '') {
                $out['_demopage'] = $this->urlhelpsite . '/demo/action-' . str_replace('_', '-', $action_name);
            } else {
                $out['_demopage'] = $arrtmp[1];
            }
        }
        return $out;
    }

    /*
     * ==== up_action_options (interne)
     * Retourne un tableau avec les options de l'action
     * @param [string] $action_name nom de l'action
     * @return [array] les options sous la forme: option=defaut => commentaire
     */
    public function up_action_options($action_name, $to_csv = false, $lang = null)
    {
        // on récupère le script php
        $actionFolder = $this->upPath . 'actions/' . $action_name . '/';
        if (! file_exists($actionFolder . $action_name . '.php')) {
            return 'Action <b>' . $action_name . '</b> : erreur de structure des dossiers.';
        }
        $tmp = file_get_contents($actionFolder . $action_name . '.php');

        // Traduction disponible ?
        if (is_null($lang)) {
            $lang = Factory::getApplication()->getLanguage()->getTag();
        }
        $comment_trad = array();
        if (file_exists($actionFolder . 'up/' . $lang . '.ini')) {
            $comment_trad = $this->load_inifile($actionFolder . 'up/' . $lang . '.ini');
        }

        // options définies
        $optlist = array();
        $regexs = array(
            '/\$options_def.*\((.*\);)/siU',
            '/\$js_options_def.*\((.*\);)/siU'
        );
        $i = 0;
        foreach ($regexs as $regex) {
            $nboptions = $i;
            // le contenu de $options_def ou $js_options_def
            if (preg_match($regex, $tmp, $deflist)) {
                $search = array(
                    '__class__',
                    '$this->name'
                );
                $deflist = str_replace($search, '\'' . $action_name . '\'', $deflist[1]);
                // les lignes avec une option
                $regex2 = '/\'(.*)\' *\=\>(.*)[\r\n]|(?:\/\*.*\*\/)[\r\n]/siU'; // v2.9
                preg_match_all($regex2, $deflist, $options);
                for ($i = 0; $i < count($options[0]); $i++) {
                    $opt = array();
                    $optionName = $options[1][$i]; // l'option
                    if (! empty($optionName)) {
                        // === Une option avec son commentaire
                        $key = $optionName;
                        list($val, $comment) = explode('//', $options[2][$i] . '//', 2);
                        $opt['key'] = $key;
                        $opt['val'] = htmlspecialchars(trim($val, ' ,\'/'));
                        $opt['dico'] = $this->get_dico_synonym($key);
                        $opt['comment'] = trim($comment, ' ,/');
                        if ($to_csv) {
                            if (isset($comment_trad[$optionName])) {
                                $opt['comment'] = $comment_trad[$optionName]; // commentaire traduit
                            }
                            $optlist[] = $opt;
                        } else {
                            $this->add_str($key, $opt['dico'], ' ', '(', ')');
                            $this->add_str($key, $opt['val'], ' = '); // option=defaut
                            if (isset($comment_trad[$optionName])) {
                                $optlist[$key] = $comment_trad[$optionName]; // commentaire traduit
                            } else {
                                $optlist[$key] = $opt['comment']; // commentaire du script php
                            }
                        }
                    } else {
                        preg_match('#\/\* *(?:\[(.*)\])?(.*)\*\/#', $options[0][$i], $subtitle);
                        $key = (isset($subtitle[1])) ? $subtitle[1] : $i;
                        $comment = (isset($comment_trad[$key])) ? $comment_trad[$key] : trim($subtitle[2] ?? '');
                        if ($to_csv) {
                            $opt['key'] = '>>ST>>' . $key;
                            $opt['val'] = '';
                            $opt['dico'] = '';
                            $opt['comment'] = $comment;
                            $optlist[$nboptions + $i] = $opt;
                        } else {
                            // sous-titre sur une seule ligne sous la forme [key] commentaires pour traduction
                            $optlist[$nboptions + $i] = $comment;
                        }
                    }
                }
            }
        }

        // unset($optlist['id']); // inutile, jamais argumenté dans shortcode
        return $optlist;
    }

    /*
     * up_help_txt
     * v1.9.5 - ajout infos webmaster
     */
    public function up_help_txt($actionName = null)
    {
        $txt = '';
        if (is_null($actionName)) {
            $infoFile = $this->actionPath . 'custom/help.txt';
        } else {
            $infoFile = $this->upPath . 'actions/' . $actionName . '/custom/help.txt';
        }
        if (file_exists($infoFile)) {
            $txt = file_get_contents($infoFile);
            $txt = $this->get_bbcode($txt);
            // ajout saut de ligne si texte pur
            if (strpos($txt, '<p>') === false && strpos($txt, '<br>') === false) {
                $txt = nl2br($txt);
            }
            $txt = '<div><b>&#x1F199; WEBMASTER NOTES</b></div><div>' . $txt . '</div>';
        }
        return $txt;
    }

    /*
     * ===============================
     * TRADUCTION
     * ===============================
     */

    /*
     * ==== lang
     * pour info, cette méthode est en fin de up.php
     */

    /*
     * ==== sreplace
     * remplace les nb occurrences de $old par $new dans $src
     * A utiliser à la place de sprintf ou Text::sprintf
     * qui retourne FALSE si erreur nombre d'argument
     */
    public function sreplace($old, $new, $src, $nb = 1)
    {
        $len = strlen($old);
        for ($i = 0; $i < $nb; $i++) {
            $pos = strpos($src, $old);
            if ($pos) {
                $src = substr_replace($src, $new, $pos, $len);
            }
        }
        return $src;
    }

    /*
     * ==== trad_keyword
     * recherche la traduction dans les fichiers langues de l'action
     * utilisé par les scripts action pour afficher des messages
     * note: les arguments doivent utiliser la syntaxe : fr=xx;en=xx ou lang[fr=xx;en=xx]
     */
    public function trad_keyword($key, $str = '')
    {
        // un mot clé ne contient pas d'espace
        if (strpos($key, ' ') !== false) {
            return $key;
        }

        // langue du navigateur client
        $lang = Factory::getApplication()->getLanguage()->getTag();

        // les traductions globales à UP
        if (! isset($this->tradup)) {
            $this->tradup = array();
            $inifile = $this->upPath . 'language/' . $lang . '/' . $lang . '.plg_content_up.ini';
            if (! file_exists($inifile)) {
                $inifile = $this->upPath . 'language/en-GB/en-GB.plg_content_up.ini';
            }
            $this->tradup = $this->load_inifile($inifile);
            // v31 custom
            $inifile = $this->upPath . 'language/' . $lang . '/' . $lang . '.plg_content_up.custom.ini';
            if (! file_exists($inifile)) {
                $inifile = $this->upPath . 'language/en-GB/en-GB.plg_content_up.custom.ini';
            }
            if (file_exists($inifile)) {
                $this->tradup = array_merge($this->tradup, $this->load_inifile($inifile));
            }
        }
        // les traductions de l'action
        if (! isset($this->tradaction)) {
            $this->tradaction = array();
            $inifile = $this->actionPath . 'up/' . $lang . '.ini';
            if (! file_exists($inifile)) {
                $inifile = $this->actionPath . 'up/en-GB.ini';
            }
            if (file_exists($inifile)) {
                $this->tradaction = $this->load_inifile($inifile);
                // v31 trad custom
                $inifile = $this->actionPath . 'up/' . $lang . '.custom.ini';
                if (! file_exists($inifile)) {
                    $inifile = $this->actionPath . 'up/en-GB.custom.ini';
                }
                if (file_exists($inifile)) {
                    $this->tradaction = array_merge($this->tradaction, $this->load_inifile($inifile));
                }
            }
        }
        $tmp = array_merge($this->tradup, $this->tradaction);

        $out = '';
        if (isset($tmp[$key])) {
            $out = $tmp[$key];
            $values = func_get_args(); // v2.4
            if (count($values) > 1) {
                unset($values[0]);
                $out = vsprintf($out, $values);
            }
        }
        return $out;
    }

    /*
     * === set_locale (v2.5) DEPRECATED
     * fixe la locale pour strftime
     * $tag : les codes langue séparés pr des virgules
     * si vide : le code de Joomla
     */
    public function set_locale($tag = '')
    {
        if (empty($tag)) {
            $tag = Factory::getApplication()->getLanguage()->getTag();
            $tag .= ',' . str_replace('-', '_', $tag);
        }
        $locale = setLocale(LC_TIME, explode(',', $tag));
    }

    /*
     * === up_date_format (v2.9)
     * retourne une date formatée et localisée
     * $date : date au format AAAA-MM-JJ HH:MM:SS (celui stocké par Joomla) si vide = date et heure actuelles
     * $format : format sfrftime. Par défaut:%e %B %Y (ex: le %e %B %Y à %k:%M)
     * $locale : le code pays (en_US) ou NULL=celui en cours
     */
    public function up_date_format($date, $format = null, $locale = '', $http = true)
    {
        // phase 1 : récupérer le timestamp
        if (empty($date)) {
            $date = time();
        } else {
            $date = $this->up_strtotime($date);
        }
        // le format d'affichage (conversion)
        if (! is_null($format)) {
            $fmt_old = array(
                '%y',
                '%Y',
                '%m',
                '%b',
                '%B',
                '%d',
                '%e',
                '%a',
                '%A',
                '%U',
                '%l',
                '%I',
                '%k',
                '%H',
                '%M',
                '%P',
                '%p'
            );
            $fmt_new = array(
                'yy',
                'yyyy',
                'MM',
                'MMM',
                'MMMM',
                'dd',
                'd',
                'EEE',
                'EEEE',
                'w',
                'h',
                'hh',
                'H',
                'HH',
                'mm',
                'a',
                'A'
            );
            for ($i = 0; $i < count($fmt_new); $i++) {
                $fmt_new[$i] = '\'' . $fmt_new[$i] . '\'';
            }
            $format = str_replace($fmt_old, $fmt_new, $format, $nbtag);
            if ($nbtag) {
                $format = '\'' . $format . '\'';
                $format = str_replace('\'\'', '', $format);
            }
        }
        // la locale de Joomla par defaut
        if (empty($locale)) {
            if ($http) {
                $locale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'fr_FR'); // v5.2
            } else {
                $locale = Factory::getApplication()->getLanguage()->getTag();
                $locale .= ',' . str_replace('-', '_', $locale);
            }
        }

        // le formatteur et retour
        $fmt = datefmt_create($locale, IntlDateFormatter::FULL, IntlDateFormatter::FULL, null, IntlDateFormatter::GREGORIAN, $format);
        return datefmt_format($fmt, $date);
    }

    /*
     * === up_strtotime($date)
     * retourne une date interprétable par strtotime
     * après traduction des termes dans la langue navigateur en anglais
     * ou mise au format AAAA-MM-JJ ou JJ-MM-AAAA
     */
    public function up_strtotime($date)
    {
        // traduction inutile, car uniquement des chiffres. ex: '25122023'
        if (is_numeric($date)) {
            // ajouter année sur 4 digits
            // au format YYYYMMDD. On ajoute l'année pour une date dans le futur
            if ((substr($date, 0, 2) <= '12')) {
                $date2 = date('Y') . $date;
                if ($date2 . '2359' < date('YmdHi')) {
                    $date2 = (intval(date('Y') + 1)) . $date;
                }
                $date = $date2;
            }
        } elseif (preg_match("/[a-zA-Z]/", $date)) {
            // traduire en angleis
            $date_terms_en = array(
                'now',
                'first day of this month',
                'last day of this month',
                'first day of next month',
                'last day of next month',
                'previous',
                'next',
                'year',
                'year',
                'month',
                'day',
                'week',
                'hour',
                'second',
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
                'january',
                'february',
                'march',
                'april',
                'may',
                'june',
                'july',
                'august',
                'september',
                'october',
                'november',
                'december'
            );
            if (! isset($this->date_terms)) {
                // les termes dans la langue du site
                $this->date_terms = $this->trad_keyword('DATE_TERMS');
                $this->date_terms = str_replace(array(
                    "\n",
                    "\r"
                ), '', $this->date_terms);
                $this->date_terms = explode(',', $this->date_terms);
                if (count($this->date_terms) != count($date_terms_en)) {
                    $this->msg_error($this->trad_keyword('DATE_TERMS_ERROR'));
                }
            }
            $date = str_ireplace($this->date_terms, $date_terms_en, $date);
        } else {
            // remplacer espace et slash par des tirets
            // format date admis : AAAA-MM-JJ ou JJ-MM-AAAA ou AAAAMMJJ
            $date = str_replace('/', '-', $date);
            // $date = str_replace(' ', '-', $date); sep entre date et heure
            $date = str_replace('--', '-', $date);
        }

        return strtotime($date);
    }

    /*
     * ===============================
     * MESSAGES
     * ===============================
     */

    /*
     * === mail2admin - v31
     * envoi un mail à l'admin du site
     */
    public function mail2admin($suject, $text)
    {
        try {
            $mailer = Factory::getContainer()->get(Joomla\CMS\Mail\MailerFactoryInterface::class)->createMailer();
            $config = Factory::getApplication()->getConfig();
            $mailto = $config->get('mailfrom');
            $site = $config->get('fromname');

            $mailer->setSender(array(
                $mailto,
                $site
            ));
            $mailer->addRecipient($mailto);
            $mailer->setSubject($site . ': error on ' . $suject);
            $mailer->setBody($text);

            $status = $mailer->Send();
        } catch (Exception $e) {
            $this->msg_inline($e->getMessage());
        }
    }

    /*
     * === msg_journal - v31
     * ajoute un fichier de suivi des erreurs
     */
    public function msg_journal($text)
    {
        $text = trim($text, '@');
        $filepath = JPATH_BASE . '/UP/error/';
        if (! file_exists($filepath)) {
            $ok = mkdir($filepath, 0755, true);
        }

        $winchar = array(
            ' ',
            '\\',
            '/',
            ':',
            '*',
            '?',
            '\"',
            '<',
            '>'
        );
        $cleantext = str_replace($winchar, '-', $text);
        $subject = $this->options_user['id'] . '--' . $this->actionUserName . '--' . $cleantext;
        $filename = $filepath . trim(substr($subject, 0, 50)) . '.err';
        if (! file_exists($filename)) {
            $msg = date('Y-m-d H:i') . " " . Factory::getApplication()->getIdentity()->username;
            $msg .= "\n" . Uri::getInstance();
            $msg .= "\n------ MESSAGE ------";
            $msg .= "\n" . $text;
            $msg .= "\n------ OPTIONS ------";
            foreach ($this->options_user as $key => $val) {
                $msg .= "\n" . $key . ' = ' . $val;
            }
            file_put_contents($filename, $msg);
            $this->mail2admin($subject, $msg);
        }
    }

    /*
     * ==== msg_error
     * ajoute un message d'erreur dans la file des messages de Joomla
     * on affiche le nom de l'action tel que saisi par le rédacteur
     */
    public function msg_error($text)
    {
        if (! $this->inprod || ! empty($this->inedit)) {
            $app = Factory::getApplication();
            $app->enqueueMessage('<b>[' . $this->options_user['id'] . ' ' . $this->actionUserName . ']</b> ' . $text, 'error');
        } else {
            $this->msg_journal($text);
        }
    }

    /*
     * ==== msg_info
     * ajoute un message d'information dans la file des messages de Joomla
     */
    public function msg_info($text = ' ', $title = '')
    {
        if ($text[0] == '@') {
            $this->msg_journal($text);
            $text = substr($text, 1);
        }

        $app = Factory::getApplication();
        if ($title) {
            $app->enqueueMessage('<b>[UP] ' . $title . '</b><br>' . $text, 'notice');
        } else {
            $app->enqueueMessage('<b>[UP ' . $this->actionUserName . ']</b><br>' . $text, 'notice');
        }
    }

    /**
     * ** pour info, info_debug est dans up.php ***
     */
    /*
     * ==== msg_inline
     * utilisé pour indiquer une erreur à son emplacement dans la page
     * $txt accepte la forme : en:hello;fr:bonjour
     */
    public function msg_inline($text)
    {
        $text = trim($this->lang($text));
        if (!empty($text)) { // v52
            if ($text[0] == '@' && ($this->inprod || empty($this->inedit))) {
                $this->msg_journal($text);
                $text = substr($text, 1);
            }
            if ((str_starts_with($text, '<') && str_ends_with($text, '>')) === false) {
                $reset = (! $this->inprod || ! empty($this->inedit)) ? 'display:inline' : '';
                $this->get_attr_style($attr, $this->cssmsg, $reset);
                $text = $this->set_attr_tag('span', $attr, $text);
            }
        }
        return $text;
    }

    /*
    * subtitue les noms de classes par leurs propriétés
    */

    public function replace_class2style($classAndStyle, $optionName = 'option_style')
    {
        $styleOnly = '';
        if ($classAndStyle) {
            $msgerr = '';
            $parts = array_map('trim', explode(';', $classAndStyle));
            foreach ($parts as $part) {
                if (!empty($part) && strpos($part, ':') === false) {
                    if (!isset($this->class2style)) {
                        $inifile = $this->get_custom_path('class2style.ini', $this->upPath . 'assets/lib/');
                        $this->class2style = ($inifile !== false) ? parse_ini_file($inifile) : '';
                    }
                    if (isset($this->class2style[strtolower($part)])) {
                        $part = $this->class2style[strtolower($part)];
                    } else {
                        $msgerr .= $part .', ';
                    }
                }
                $styleOnly .= ';'  . $part;
            }
            if ($msgerr) {
                $this->msg_error(sprintf('Classe(s) invalide(s) dans %s : %s', $optionName, rtrim($msgerr, ' ,')));
            }
        }
        return trim($styleOnly, ';');
    }

    /*
    * Chronométre les temps d'éxécution
    */
    public function ctrl_timer($info = '')
    {
        if (empty($this->options_user['debug'])) {
            return;
        }
        $app = Factory::getApplication();
        if (isset($this->timeStart)) {
            $this->timeEnd = microtime(true);
            $duration = ($this->timeEnd - $this->timeStart) * 1000;
            $msg = sprintf('%8.2fs : %s %s', $duration, $this->name, $info);
            $app->enqueueMessage($msg);
        } else {
            $msg = sprintf('%8.2fs : %s %s', '00000000', $this->name, $info);
            $app->enqueueMessage($msg);
            $this->timeStart = microtime(true);
        }

    }
    // fin class upaction
}
