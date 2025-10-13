<?php

/**
 * Création du script PHP classe2style
 *
 * Génère un script permettant de traduire des noms de classe en style
 *
 * syntaxe {up upclass2style=pathCSSfile1,pathCSSfile2,...}
 *
 * @version  UP-5.2
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="" target"_blank">script xxx de xxx</a>
 * @tags    UP
 *
 */
defined('_JEXEC') or die;

class upclass2style extends upAction
{
    public function init()
    {
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
        __class__ => '', // chemin du fichier CSS
        'up-style' => 1, // inclure la feuille de style de UP
          'list' => 'compact', // affiche uniquement le nom des classes. Toutes autres valeurs affiche une liste détaillée (class:propriétés)
          'id' => '',
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        if (isset($this->options_user['list'])) {
            $compact = (soundex($options['list']) == 'C512');
            if ($compact) {
                $this->load_css_head('#id span[white-space:nowrap]') ;
            }
            $inifile = 'plugins/content/up/assets/lib/custom/class2style.ini';
            if (file_exists($inifile) === false) {
                $inifile = 'plugins/content/up/assets/lib/class2style.ini';
            }
            $items = parse_ini_file($inifile);
            $list = '<div id="'.$options['id'].'" style="text-align:justify">';
            foreach ($items as $class => $style) {
                if ($compact) {
                    $list .= '<span>'. $class.'</span> &#x25AA ';
                } else {
                    $list .= '<b>'.$class.'</b> : '.$style.'<br>';
                }
            }
            $list = rtrim($list, ' &#x25AA');
            $list .= '</div>';

            return $list;
        }

        $cssfilelist = array_map('trim', explode(',', $options[__class__]));
        if ($options['up-style']) {
            $cssfilelist[] = 'plugins/content/up/assets/up.css';
        }

        foreach ($cssfilelist as $cssfile) {
            if (!empty($cssfile)) {
                $stylesheet = file_get_contents($cssfile);
                $this->classContent = array(); // les classes contenant. Ex: [class*="bd-red"]

                // ----- suppression retour à la ligne
                $regex = "/(([\n\r\t]))/";
                $stylesheet = preg_replace($regex, '', $stylesheet);

                // ----- suppression commentaires
                $regex = "/(\/\*.*\*\/)/U";
                $stylesheet = preg_replace($regex, '', $stylesheet);

                // ----- suppression des @media
                $regex = "/(@media .*\}\})/U";
                $stylesheet = preg_replace($regex, '', $stylesheet);

                // ----- tableau des classes et styles
                $regex = "/(.*)\{(.*)\}/U";
                preg_match_all($regex, $stylesheet, $matches);
                $classes = $matches[1];
                $styles = $matches[2];
                for ($i = 0; $i < count($classes); $i++) {
                    $list_classes = explode(',', $classes[$i]);
                    foreach ($list_classes as $class) {
                        $class = trim($class);
                        // les classes débutant par
                        if (str_starts_with($class, '[class')) {
                            $regex = '/.*\"(.*)\"/U';
                            preg_match_all($regex, $class, $matches);
                            $this->classContent[$matches[1][0]] = $styles[$i];
                        }


                        if ($class[0] == '.') { // que les classes
                            $class = substr($class, 1);
                            if ($this->is_class_simple($class)) {
                                $result[$class] = str_replace('\'', '"', $styles[$i]); // nom police entre guillemets
                            }
                        }
                    }
                }
                // on ajoute les classes contenant aux classes ciblées
                foreach ($this->classContent as $class => $style) {
                    foreach ($result as $class2 => $style2) {
                        if (strpos($class2, $class) !== false) {
                            $result[$class2] = $style2 .';'. $style;
                        }
                    }
                }
            }
        }

        // ----- création fichier INI
        if (!empty($result)) {
            $ini = '';
            ksort($result);
            $regex = '/\s*([:;{}])\s*/';
            foreach ($result as $class => $style) {
                // suppression espaces inutiles
                $style = preg_replace($regex, '$1', trim($style));
                $ini .= strtolower($class) . ' = "' . $style . '"'."\n";
            }
        }

        if (empty($options[__CLASS__])) {
            // si uniquement la stylesheet de UP, on remplace
            // et on supprime un custom précédent TODO
            file_put_contents('plugins/content/up/assets/lib/class2style.ini', $ini);
            if (file_exists('plugins/content/up/assets/lib/custom/class2style.ini')) {
                unlink('plugins/content/up/assets/lib/custom/class2style.ini');
            }
        } else {
            // si perso, on met dans custom
            if (!file_exists('plugins/content/up/assets/lib/custom')) {
                mkdir('plugins/content/up/assets/lib/custom', 775, true);
            }
            file_put_contents('plugins/content/up/assets/lib/custom/class2style.ini', $ini);
        }


        return 'Récupération terminée';
    }

    // run

    public function is_class_simple($class)
    {
        $mots = array('.',' ','>','*','[',':hover', ':before', ':after', ':focus', ':active', ':visited', ':link', ':first-child', ':last-child', ':nth-child', ':nth-last-child', ':nth-of-type', ':nth-last-of-type', ':first-of-type', ':last-of-type', ':only-child', ':only-of-type', ':empty', ':checked', ':enabled', ':disabled', ':read-only', ':read-write', ':required', ':optional', ':valid', ':invalid', ':in-range', ':out-of-range', ':target', ':lang', ':root', ':empty', ':not', ':first-line', ':first-letter', ':selection');
        foreach ($mots as $mot) {
            if (stripos($class, $mot) !== false) {
                return false;
            }
        }
        return true;
    }
}

// class
