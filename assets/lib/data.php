<?php

/*
 * @version  UP-3.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;

/*
 * ----------------------------------------------------------------------
 * get_data
 * ----------------------------------------------------------------------
 * $src : option principale de l'action
 * $cache_delay : $options['cache-delay']
 *
 * récupération des données brutes dans :
 * - un fichier sur le serveur
 * - sur un site externe si pas de cache ou copie locale périmée
 * - copie dans le cache
 *
 */
function get_data($src, $cache_delay)
{
    $src = strip_tags($src); // suppr mise en hyperlien par editeur
    if (! empty(preg_match('#^((https?\:)?\/\/)(?:[\da-z])*\.#i', $src, $match))) {
        // c'est une URL
        if ($cache_delay > 0) {
            $filecache = 'tmp/up-data-' . filename_secure(substr($src, strlen($match[1])));
            if (file_exists($filecache) && (filemtime($filecache) < strtotime('-' . ($cache_delay * 60) . 'second')) === false) {
                $src = $filecache;
            } else {
                // et ecrire le fichier cache
                $data = file_get_contents($src);
                file_put_contents($filecache, $data);
            }
        }
    }
    if (empty($data)) {
        $data = file_get_contents($src);
    }

    return $data;
}

/*
 * convert_data_to_array
 * ----------------------------------------------------------------------
 * conversion du fichier source en tableau
 * $fortable=true pour forcer le contenu dans un tableau
 */
function convert_data_to_array($data, $options, $fortable = false)
{
    if (empty($options['datatype'])) {
        $options['datatype'] = 'json'; // type par défaut
        // identification par extension fichier
        $ext = strtolower(pathinfo(reset($options), PATHINFO_EXTENSION));
        if (in_array($ext, array(
            'json',
            'xml',
            'csv'
        ))) {
            $options['datatype'] = $ext;
        } else {
            // identification par contenu
            if (strpos($data, '<?xml') !== false) {
                $options['datatype'] = 'xml';
            } elseif (preg_match('#(?:.*)\b(;{1})#U', $data, $matches)) {
                // si 1er caractère après le 1er mot est le séparateur
                if ($matches[1] == $options['csv-separator']) {
                    $options['datatype'] = 'csv';
                }
            }
        }
    }
    $data = trim($data);
    if (! empty($options['encoding'])) {
        $data = iconv($options['encoding'], 'UTF-8', $data);
    }
    switch (strtolower($options['datatype'])) {
        case 'json':
            $data = json_decode($data, true);
            break;
        case 'xml':
            $data = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA);
            $data = json_encode($data);
            if (empty($options['xml-attributes'])) {
                $data = preg_replace('#(\"@attributes\"\:\{)(.*)(\})#U', '$2', $data);
            }
            $data = json_decode($data, true);
            break;
        case 'csv':
            $data = explode(PHP_EOL, $data);
            // titres des champs/colonnes
            if ($options['csv-header-title'] > '') {
                $title = str_getcsv($options['csv-header-title'], $options['csv-separator'], '"', '\\');
            } else {
                if ($options['csv-header'] == 1) {
                    $title = str_getcsv($data[0], $options['csv-separator'], '"', '\\');
                }
            }
            if (empty($title)) {
                for ($i = 1; $i <= count($data[0]); $i++) {
                    $title[] = 'col-' . $i;
                }
            } else {
                $title = array_map('trim', $title);
            }
            if ($options['csv-header'] == 1) {
                unset($data[0]);
            }
            // contenu des données
            foreach ($data as $lign) {
                $lign = str_getcsv($lign, $options['csv-separator'], '"', '\\');
                if (count($title) === count($lign)) {
                    $out[] = array_combine($title, $lign);
                } else {
                    return '';
                }
            }
            $data = $out;
            break;
        default:
            return ''; // erreur
    }
    return $data;
}

/*
 * ----------------------------------------------------------------------
 * get_root
 * ----------------------------------------------------------------------
 * sélectionne la partie de $data correspondant au chemin de l'option 'root'
 *
 */
function get_root(&$data, $options)
{
    $kroot = explode('/', $options['lign-root']);
    foreach ($kroot as $k) {
        if (isset($data[$k])) {
            $data = $data[$k];
        } else {
            return false;
        }
    }
    return true;
}

/*
 * ----------------------------------------------------------------------
 * get_select
 * ----------------------------------------------------------------------
 * retourne les lignes indiquées dans l'option 'select'. ($this->options['select'])
 * 2 modes de selection :
 * - select=chp1:val1,chp2=val2 <- toutes les lignes avec chp1=val1 AND chp2=val2
 * - select=1,-2 <- la 1ere et avant-dernière lignes
 * note : 1 seul choix pour data-info (non depuis v5.1
 */
function get_select(&$data, $options)
{
    $select = $options['lign-select'];
    if (is_array($select)) {
        foreach ($select as $k1 => $v1) {
            $ind = null;
            if (strpos($k1, '/')) {
                list($k1, $ind) = array_map('trim', explode('/', $k1));
            } elseif (isset($data[0][$k1]) && is_array($data[0][$k1])) {
                $ind = 0; // si pas précisé, on prend le premier
            }
            if (array_key_exists($k1, $data[0]) === false) {
                return 'Column unknow : ' . $k1;
            }
            // boucle sur data
            $nb = count($data);
            for ($i = 0; $i < $nb; $i++) {
                $ok = false;
                if (is_null($ind)) {
                    $cell = trim(strtolower($data[$i][$k1]));
                } else {
                    $cell = trim(strtolower($data[$i][$k1][$ind])) ?? '';
                }
                foreach ($v1 as $v2) {
                    $ok = ($ok || strtolower($v2) == $cell);
                }
                if (! $ok) {
                    unset($data[$i]);
                }
            }
            $data = array_values($data); // reindex
        }
    } else {
        // selection des lignes avec la valeur (en general des indices)
        $out = array();
        $select = array_map("trim", explode(',', $select));
        foreach ($select as $kselect) {
            if (is_numeric($kselect)) {
                if ($kselect > 0) {
                    $kselect--;
                } else {
                    $kselect = count($data) - abs($kselect);
                }
            }
            if (isset($data[$kselect])) {
                $out[] = $data[$kselect];
            }
        }
        $data = $out;
    }
    return (empty($data) ? 'data-info - selection not found ' : '');
}

/*
 * ----------------------------------------------------------------------
 * sort_data v5.1
 * ----------------------------------------------------------------------
 * tri du tableau $data
 * $lign_sort = champ1:asc|desc:flag array_multisort, champ2:asc|desc:flag array_multisort, ...
 */
function sort_data(&$data, $lign_sort)
{
    if (! empty($data) && ! empty($lign_sort)) {
        $nb = 0;
        foreach ($lign_sort as $key => $val) {
            if (array_key_exists($key, $data[0])) {
                $val = (stripos($val, 'desc') !== false) ? SORT_DESC : SORT_ASC;
                $col[] = array_column($data, $key);
                $order[] = $val;
                $nb++;
            } else {
                return 'colonne inconnue : ' . $key;
            }
        }
        switch ($nb) {
            case 1:
                array_multisort($col[0], $order[0], SORT_NATURAL | SORT_FLAG_CASE, $data);
                break;
            case 2:
                array_multisort($col[0], $order[0], SORT_NATURAL | SORT_FLAG_CASE, $col[1], $order[1], SORT_NATURAL | SORT_FLAG_CASE, $data);
                break;
            default: // 3 maxi, le reste est ignoré
                array_multisort($col[0], $order[0], SORT_NATURAL | SORT_FLAG_CASE, $col[1], $order[1], SORT_NATURAL | SORT_FLAG_CASE, $col[2], $order[2], SORT_NATURAL | SORT_FLAG_CASE, $data);
                break;
        }
    }
    return '';
}

/*
 * ---------------------------------
 * get_filter v5.1
 * ---------------------------------
 * retourne le tableau avec les seules lignes remplissant les conditions >=, <=, ==, <>, ><
 */
function get_filter(&$data, $lign_filter)
{
    if (! empty($lign_filter)) {
        $match = array();
        $lign_filter = array_map('trim', explode(',', $lign_filter));
        foreach ($lign_filter as $condition) {
            $condition = html_entity_decode($condition);
            if (empty(preg_match('/(.*)(\>=|\<\=|\<\>|==|\>\<)(.*)/', $condition, $match))) {
                return 'incorrect filter : ' . $condition;
            }
            $colname = trim($match[1]) ?? '';
            $op = $match[2] ?? '';
            $val = trim($match[3]) ?? '';
            if (strpos($val, ';') !== false) {
                return 'semicolon (;) is not authorized in ';
            }
            $ind = null;
            if (strpos($colname, '/')) {
                list($colname, $ind) = array_map('trim', explode('/', $colname));
            } elseif (isset($data[0][$colname]) && is_array($data[0][$colname])) {
                $ind = 0; // si pas précisé, on prend le premier
            }
            $val = ($is_num = is_numeric($val)) ? (float) $val : strtolower($val);
            $data = array_values($data); // reindexer
            $nb = count($data);
            for ($i = 0; $i < $nb; $i++) {
                $ok = true;
                if (is_null($ind)) {
                    $cell = trim(strtolower($data[$i][$colname]));
                } else {
                    $cell = trim(strtolower($data[$i][$colname][$ind])) ?? '';
                }
                if ($is_num) {
                    $cell = (float) $cell;
                }
                if ($op == '>=') { // min
                    $ok = ($cell >= $val);
                } elseif ($op == '<=') { // max
                    $ok = ($cell <= $val);
                } elseif ($op == '==') { // egal
                    $ok = ($cell == $val);
                } elseif ($op == '<>' || $op == '!=') { // différent
                    $ok = ($cell != $val);
                } elseif ($op == '><') { // entre
                    list($valmin, $valmax) = array_map('trim', explode('-', $val));
                    $ok = ($cell >= $valmin && $cell <= $valmax);
                }
                if (! $ok) {
                    unset($data[$i]);
                }
            }
        }
    }
    return '';
}

/*
 * fix_options
 * ---------------------------------
 * vérifie et consolide les options
 * les options non disponibles sont créées pour eviter de les tester
 *
 */
function fix_options(&$options)
{
    // ---- select
    if (strpos($options['lign-select'], ':') !== false) {
        $options['lign-select'] = strtoarray($options['lign-select']);
        foreach ($options['lign-select'] as $k => $v) {
            $options['lign-select'][$k] = array_map('trim', explode(';', $v));
        }
    }
    // ---- col-include & col-exclude
    $options['col-include'] = (empty($options['col-include'])) ? array() : array_map('trim', explode(',', $options['col-include']));
    $options['col-exclude'] = (empty($options['col-exclude'])) ? array() : array_map('trim', explode(',', $options['col-exclude']));

    // ---- col-class
    $options['col-class'] = (empty($options['col-class'])) ? array() : strtoarray($options['col-class']);
    // ---- col-label
    $options['col-label'] = (empty($options['col-label'])) ? array() : strtoarray($options['col-label']);
    // ---- col-empty
    $options['col-empty'] = (empty($options['col-empty'])) ? array() : strtoarray($options['col-empty']);
    // ---- col-type
    $options['col-type'] = (empty($options['col-type'])) ? array() : strtoarray($options['col-type']);

    // ---- pour col-type=boolean
    $options['boolean-in'] = array_map('trim', explode(',', $options['boolean-in']));
    $options['boolean-in'] = array_pad($options['boolean-in'], 3, '');
    $options['boolean-out'] = array_map('trim', explode(',', $options['boolean-out']));
    $options['boolean-out'] = array_pad($options['boolean-out'], 3, '');
}

/*
 * get_col_value
 * ------------------------------
 * retourne la valeur et mise en forme
 */
function get_col_value($key, $rowdata, $options)
{
    $class = '';
    $type = '';
    $val = (isset($options['col-empty'][$key])) ? $options['col-empty'][$key] : '';
    if (strpos($key, '/') === false) {
        $val = (isset($rowdata[$key])) ? $rowdata[$key] : '';
        // format
        if (isset($options['col-class'][$key])) {
            $class = ' class="' . $options['col-class'][$key] . '"';
        }
        // type
        if (isset($options['col-type'][$key])) {
            $type = $options['col-type'][$key];
        }
    } else {
        // chemin vers une sous-clé
        $subdata = $rowdata;
        $keypath = explode('/', $key);
        $pathkey = '';
        foreach ($keypath as $subkey) {
            if (isset($options['col-class'][$pathkey . $subkey])) {
                $class = ' class="' . $options['col-class'][$pathkey . $subkey] . '"';
            }
            if (isset($options['col-type'][$pathkey . $subkey])) {
                $type = $options['col-type'][$pathkey . $subkey];
            }
            $subdata = (isset($subdata[$subkey])) ? $subdata[$subkey] : '';
            $pathkey .= $subkey . '/';
        }
        $val = $subdata;
    }

    $val = get_typed_value($val, $type, $options);
    return array(
        $val,
        $class
    );
}

/*
 * get_typed_value
 * --------------------------------
 * retourne la valeur enrichie selon son type
 *
 */
function get_typed_value($val, $type, $options)
{
    $val = (is_string($val)) ? trim($val) : $val;
    switch ($type) {
        case '':
            break;
        case 'compact':
            $out = '';
            array_to_string($out, $val);
            $val = $out;
            break;
        case 'date':
            $tmp = up_date_format($val, $options['date-format']);
            $val = ($tmp) ? $tmp : $val;
            break;
        case 'url':
            if (strpos($val, '//') !== false) {
                $text = parse_url($val, PHP_URL_HOST) . parse_url($val, PHP_URL_PATH);
                $val = '<a href="' . $val . '" target="' . $options['url-target'] . '">' . $text . '</a>';
            }
            break;
        case 'boolean':
            if ($val == $options['boolean-in'][0]) {
                $val = $options['boolean-out'][0];
            } elseif ($val == $options['boolean-in'][1]) {
                $val = $options['boolean-out'][1];
            } else {
                $val = $options['boolean-out'][2];
            }
            break;
        case 'image':
            if ($val) {

                $img = $options['image-path'] . $val;
                $imgsize = '';
                if ($options['image-max-size']) {
                    list($w, $h) = getimagesize($img);
                    $imgsize = ($h < $w) ? 'max-width' : 'max-height';
                    $imgsize = ' style="' . $imgsize . ':' . $options['image-max-size'];
                }
                $val = '<img src="' . $img . '" alt="' . pathinfo($val, PATHINFO_FILENAME) . '"' . $imgsize . '">';
            }
            break;
        default:
            if ($val) {
                if (strpos($type, '%') !== false) {
                    $out = ($val) ? sprintf($type, $val) : '';
                    if ($val != $out) { // format sprintf reconnu
                        $val = $out;
                    }
                } elseif (strpos($type, '##date##') !== false) {
                    $out = up_date_format($val, $options['date-format']);
                    $val = str_replace('##date##', $out, $type);
                } elseif (strpos($type, '##num##') !== false) {
                    if (is_numeric($val)) {
                        $dec = (floor($val) == $val) ? 0 : 2;
                        $out = number_format($val, $dec, '.', ' ');
                    } else {
                        $out = $val;
                    }
                    $val = str_replace('##num##', $out, $type);
                } elseif (stripos($type, 'MASK') === 0) {
                    $val = string_mask($val, substr($type, 4));
                }
            }
    }
    return $val;
}

/*
 * ---------------------------------------------------------------------
 * array_to_string
 * ---------------------------------------------------------------------
 * fonction recursive qui transforme un array ($arr) multidimension en chaine ($out)
 */
function array_to_string(&$out, $arr, $new = true)
{
    if (! is_array($arr)) {
        return;
    }
    foreach ($arr as $k => $v) {
        $label = (is_numeric($k)) ? '' : $k . ': ';
        if (is_array($v)) {
            $out .= $label . '[';
            array_to_string($out, $v, true);
            $out .= '], ';
        } else {
            if ($new === false) {
                $out .= ', ';
            }
            $new = false;
            $out .= $label . $v;
        }
    }
}

/*
 * =============================
 * UTILITIES
 * =============================
 */

/*
 * ----------------------------------------------------------------------
 * filename_secure
 * ----------------------------------------------------------------------
 * remplace les caractères à risque pour windows dans le nom du fichier
 */
function filename_secure($filename)
{
    $old = explode(',', '<,>,:,*,?,",/,\,|, ');
    $new = explode(',', '(,),-,_,#,,_,_,_,_');
    return str_replace($old, $new, $filename);
}

/*
 * ---------------------------------------------------------------------
 * strtoarray (version locale simplifiée de la méthode de upAction)
 * ---------------------------------------------------------------------
 * retourne une chaine au format 'un:1,2:deux'
 * sous la forme d'un tableau ['un']=>1 [2]=>'deux'
 */
function strtoarray($str)
{
    $arr = array();
    if (! empty($str)) {
        foreach (explode(',', $str) as $el) {
            if (strpos($el, ':') !== false) {
                list($k, $v) = array_map('trim', explode(':', $el, 2));
                $txt = $v[strlen($v) - 1];
                if ($v && $v[0] == '"' && $v[strlen($v) - 1] == '"') {
                    $v = trim($v, '"');
                }
                $arr[$k] = $v;
            }
        }
    }
    return $arr;
}

/*
 * ---------------------------------------------------------------------
 * up_date_format (version locale simplifiée de la méthode de upAction)
 * ---------------------------------------------------------------------
 * retourne une date formatée et localisée
 * $date : date au format AAAA-MM-JJ HH:MM:SS (celui stocké par Joomla) si vide = date et heure actuelles
 * $format : format sfrftime. Par défaut:%e %B %Y (ex: le %e %B %Y à %k:%M)
 * $locale : le code pays (en_US) ou NULL=celui en cours
 */
function up_date_format($date, $format = null, $locale = '', $http = true)
{
    if (empty($date)) {
        $date = time();
    }
    if (! is_integer($date)) {
        $date = strtotime($date);
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
            $locale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
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
 * string_mask : formatte une chaine selon un masque
 * -------------------------------------------------
 * les chiffres et lettres de $str remplace les # de $mask à partir de la fin
 * $str=0102030405 et $mask=(#)# ## ## ## ## => (0)1 02 03 04 05
 * $str=123456789 et $mask=##-## => 1234567-89 // les caractères en trop de $str sont laissé au début
 * $str=12 et $mask=##-## => 12 // les caractères en trop de $mask sont ignorés
 */
function string_mask($str, $mask)
{
    // conserver les lettres et chiffres
    $str = preg_replace('/\W/', '', $str);
    if (empty($str)) {
        return '';
    }

    $pos_str = strlen($str) - 1;
    $out = '';
    $prefix = substr($mask, 0, strpos($mask, '#'));
    $mask = substr($mask, strpos($mask, '#'));

    for ($i = strlen($mask) - 1; $i >= 0; $i--) {
        if ($mask[$i] == '#') {
            if ($pos_str >= 0) {
                $out = $str[$pos_str] . $out;
                $pos_str--;
            }
        } elseif ($pos_str >= 0) {
            $out = $mask[$i] . $out;
        }
    }

    return $prefix . substr($str, 0, $pos_str + 1) . $out;
}
