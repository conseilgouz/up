<?php
/*
 * @version  UP-5.2
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 */

defined('_JEXEC') or die;

// ##motcle## <- value : le style de base
// ##motcle # %% ## <- rawvalue : style par modèle
// ##motcle # %directory% ## <- valeur de la propriété du CF
// ##motcle=condition##
// ##motcle=condition # %rawvalue%##
// ##motcle # test:%%## // %%=rawvalue
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\CMS\Factory;

function kw_cf_replace(&$tmpl, &$item)
{
    $sep = ',';
    $artFields = FieldsHelper::getFields('com_content.article', $item, true);
    // creation acces par nom ou id
    foreach ($artFields as $Field) {
        $Fields[strtolower($Field->name)] = $Field;
        $Fields[strtolower($Field->id)] = $Field;
        // $foo = FieldsHelper::render($Field->context, 'field.render', array('field' => $Field));
    }
    // les mots-clés restants
    //$kw['all'] = array();
    preg_match_all('/\#\#(.*)\#\#/Ui', $tmpl, $matches);

    // foreach ($kw['all'][1] as $kw) {
    for ($i = 0; $i < count($matches[0]); $i ++) {
        $kw['all'] = $matches[0][$i]; // ##motcle=condition # model##
        $kw['arg'] = strpbrk($matches[1][$i], ' <>=![#'); // =condition # model
        $kw['cond'] = trim(strstr($kw['arg'] . ' # ', ' # ', true)); // =condition
        $kw['model'] = trim(strstr($kw['arg'] . ' # ', ' # ', false), ' #'); // model
        $kw['key'] = ($kw['arg'] === false) ? trim($matches[1][$i]) : trim(substr($matches[1][$i], 0, - strlen($kw['arg']))); // motcle
        $kw['ind'] = null; // indice array CF liste
        $kw['cfid'] = null; // ID d'un CF du subform
        if (str_contains($kw['key'], '_')) {
            list ($kw['key'], $kw['ind']) = array_map('trim', explode('_', $kw['key'], 2));
            if (str_contains($kw['ind'], 'info') && isset($Fields[$kw['key']])) {
                kw_info($Fields[$kw['key']]);
                $kw['ind'] = null;
            }
            if ($kw['ind'] && str_contains($kw['ind'], '_')) {
                list ($kw['cfid'], $kw['ind']) = explode('_', $kw['ind']);
            }
        }
        if (! isset($Fields[$kw['key']]))
            continue;
        $kw['obj'] = $Fields[$kw['key']]; // objet cible

        // === données ciblées
        $kw['rawvalue'] = $kw['obj']->rawvalue;
        $kw['value'] = $kw['obj']->value;

        if (is_array($kw['rawvalue'])) {
            // liste
            $kw['rawvalue'] = kw_get_data($kw['rawvalue'], $kw['ind']);
            if (is_array($kw['rawvalue']) && (str_contains($kw['arg'], '#') === false))
                $kw['rawvalue'] = implode($sep, $kw['rawvalue']);
        } elseif (isset($kw['rawvalue'][0]) && $kw['rawvalue'][0] == '{') {
            // ##subform## value
            // ##subform_1## value du premier subform
            // ##subform_cfid_0## rawvalue de tous les cfid
            // ##subform_cfid_1## premeier rawvalue du cfid
            $kw['value'] = 'n.a.';
            if (empty($kw['cfid'])) {
                if (empty($kw['ind'])) {
                    // ##subform## : value de tous les CF internes de toutes les lignes du subform
                    $kw['rawvalue'] = $kw['obj']->value;
                    $kw['value'] = $kw['rawvalue'];
                } else {
                    // ##subform_1## : value de tous les CF internes de la ligne demandée du subform
                    if (preg_match_all('#<li>(.*)<\/li>#Us', $kw['obj']->value, $htmls))
                        $kw['rawvalue'] = kw_get_data($htmls[0], $kw['ind']);
                }
            } else {
                // on cible un CF interne
                $json = json_decode($kw['rawvalue'], true);
                $cfname = 'field' . $kw['cfid'];
                if (! isset($json['row0'][$cfname])) {
                    $kw['rawvalue'] = 'ERROR';
                } else {
                    if ($kw['ind'] > 0) {
                        // ##subform_cfid_ind## : le CF interne d'une ligne du subform
                        $kw['rawvalue'] = $json['row' . $kw['ind']][$cfname];
                    } else {
                        // ##subform_cfid_0## : toutes les valeurs d'un CF interne pour toutes les lignes du subform
                        $kw['rawvalue'] = array();
                        foreach ($json as $row) {
                            if (is_array($row[$cfname])) {
                                if ($row[$cfname][0] != - 1)
                                    $kw['rawvalue'][] = implode($sep, $row[$cfname]);
                            } else {
                                $kw['rawvalue'][] = $row[$cfname];
                            }
                        }
                    }
                }
            }
            // if (is_array($kw['rawvalue']))
            // $kw['rawvalue'] = implode($sep, $kw['rawvalue']);
        }

        if (is_array($kw['rawvalue']) && ! $kw['model'])
            $kw['rawvalue'] = implode($sep, $kw['rawvalue']);

        if (is_array($kw['rawvalue'])) {
            // Mise en forme de chaque element selonn model
            $out = '';
            foreach ($kw['rawvalue'] as $data) {
                // $out .= '<li>' . kw_kw_get_replace($kw, $data) . '</li>';
                $out .= kw_get_replace($kw, explode($sep, $data));
            }
            // $tmpl = str_replace($kw['all'], '<ul>' . $out . '</ul>', $tmpl);
            $tmpl = str_replace($kw['all'], $out, $tmpl);
        } else {
            // valeur unique
            $str = kw_get_replace($kw, $kw['rawvalue']);
            $tmpl = str_replace($kw['all'], $str ?? '', $tmpl);
        }
    }
}

function kw_get_replace($kw, $replace)
{
    // ==== remplacer
    $replace_val = $replace;
    if ($kw['cond']) {
        $compare_val = substr($kw['cond'], 1);
        switch ($kw['cond'][0]) {
            case '':
                break;
            case '=':
                $replace_val = (strtolower($replace_val) == $compare_val) ? $replace_val : '';
                break;
            case '!':
                $replace_val = (strtolower($replace_val) != $compare_val) ? $replace_val : '';
                break;
            case '>':
                $replace_val = (strtolower($replace_val) >= $compare_val) ? $replace_val : '';
                break;
            case '<':
                $replace_val = (strtolower($replace_val) < $compare_val) ? $replace_val : '';
                break;
            case '[':
                $choix = kw_strtoarray(trim($compare_val, ']'), ',', ':', false);
                $replace_val = (isset($choix[$replace_val])) ? $choix[$replace_val] : $replace_val;
                break;
            default: // la fin d'un motclé avec la même racine
                $replace_val = NULL;
        }
    }
    // }

    // le remplacement
    if (! is_null($replace_val)) { // non concerné
        if (empty($kw['model'])) {
            $tmpl = $replace_val;
        } else {
            $tmpl = $kw['model'];
            if (strpos($tmpl, '%%') !== false) {
                if (is_array($replace_val)) {
                    $out = '';
                    foreach ($replace_val as $v)
                        $out .= ($v == '') ? '' : str_replace('%%', $v, $tmpl);
                    $tmpl = $out;
                } else {
                    $tmpl = ($replace_val == '') ? '' : str_replace('%%', $replace_val, $tmpl);
                }
            }
            // autres champs
            $others = array();
            if (preg_match_all('#%(.*)%#U', $tmpl, $others)) {
                foreach ($others[1] as $other) {
                    if ($other == 'value') {
                        $str = $kw['value'];
                    } elseif ($other == 'rawvalue') {
                        $str = $kw['rawvalue'];
                    } else {
                        $str = (isset($kw['obj']->$other)) ? $kw['obj']->$other : $kw['obj']->fieldparams->get($other, 'ERROR');
                    }
                    $tmpl = str_replace('%' . $other . '%', $str, $tmpl);
                }
            }
        }
        // $tmpl = str_replace($kw['tag'], $kw['model'], $tmpl);
        return $tmpl;
    }
}

function kw_get_data($array, $indice)
{
    if (! is_null($indice)) {
        $indice = ($indice > 0) ? $indice - 1 : count($array) + $indice;
        $array = (isset($array[$indice])) ? $array[$indice] : 'ERROR';
    }
    return $array;
}

function kw_info($cfobj)
{
    if (is_null($cfobj))
        return;
    $str = '';
    foreach ($cfobj as $k => $v) {
        if ($k == 'rawvalue')
            $foo = 'debug';
        if (is_object($v))
            $v = $cfobj->$k->toArray();
        if (is_array($v)) {
            $tmp = '';
            kw_array_to_string($tmp, $v);
            $str .= '<li><i>' . $k . ':</i> ' . htmlentities($tmp) . '</li>';
        } else {
            $v = ($v) ? $v : '-';
            $str .= '<li><i>' . $k . ':</i> ' . htmlentities(($v) ? $v : '-') . '</li>';
        }
    }
    $app = Factory::getApplication();
    $app->enqueueMessage('<b>CUSTOM FIELD INFOS</b><ul>' . $str . '</ul>', 'notice');
}

/*
 * ---------------------------------------------------------------------
 * kw_array_to_string
 * ---------------------------------------------------------------------
 * fonction recursive qui transforme un array ($arr) multidimension en chaine ($out)
 */
function kw_array_to_string(&$out, $arr, $new = true)
{
    if (! is_array($arr))
        return;
    foreach ($arr as $k => $v) {
        $label = (is_numeric($k)) ? '' : $k . ': ';
        if (is_object($v))
            $v = var_export($v, true);
        if (is_array($v)) {
            $out .= $label . ', [';
            kw_array_to_string($out, $v, true);
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
 * ==== kw_strtoarray
 * retourne une chaine au format 'un:1,2:deux'
 * sous la forme d'un tableau ['un']=>1 [2]=>'deux'
 * v1.8 : ajout $quote (pour )eviter quote pour sql_select > format.list
 * v1.8 : ajout array_map('trim',..
 */
function kw_strtoarray($str, $row = ',', $col = ':', $quote = true)
{
    $arr = array();
    if (! empty($str)) {
        foreach (explode($row, $str) as $el) {
            list ($k, $v) = array_map('trim', explode($col, $el, 2));
            // supprime guillemet double pour préserver un espace - v3
            if ($v && $v[0] == '"' && $v[strlen($v) - 1] == '"')
                $v = trim($v, '\"');
            $k = (! is_numeric($k) && $quote) ? "'" . $k . "'" : $k;
            $v = (! is_numeric($v) && $quote) ? "'" . $v . "'" : $v;
            $arr[$k] = $v;
        }
    }
    return $arr;
}

