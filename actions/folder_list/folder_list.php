<?php
use Joomla\Component\Media\Administrator\Exception\FileExistsException;

/**
 * retourne la liste mise en forme du contenu d'un dossier sur le serveur
 *
 * syntaxe {up folder_list=folder_relative_path_on_server | template=##file## (##size)}0
 *
 * ##file## : chemin/nom.extension - pour copier/coller comme argument shortcode
 * ##dirname## : chemin (sans slash final)
 * ##basename## : nom et extension
 * ##filename## : nom sans extension (sans le point)
 * ##extension## : extension
 * ##relpath## : chemin relatif au chemin passé comme principal argument
 * ##size## : taille du fichier
 * ##date## : date dernière modification
 *
 * Motclé disponible pour le dossier en format liste (ul/li)
 * ##foldername## : nom du dossier (sans l'arboresccence)
 * ##folderpath## : chemin et nom du dossier (avec l'arboresccence)
 *
 * @version UP-2.5
 * @license <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @author Lomart
 * @tags   layout-dynamic
 *
 */

/*
 * v2.8 modification pour sortie au format liste (ul/li)
 * v2.9 bug sur ##date## et compatibilité PHP8
 */
defined('_JEXEC') or die();

class folder_list extends upAction
{

    function init()
    {
        return true;
    }

    function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // chemin relatif du dossier sur le serveur
            'mask' => '', // masque de sélection des fichiers
            'recursive' => '0', // niveaux d'exploration des sous-dossiers
            /* [st-tmpl] Modèle de mise en forme */
            'template' => '##file##', // modèle de mise en forme du résultat
            'template-folder' => '[b]##foldername##[/b]', // modèle de mise en forme pour les dossier en vue liste
            /* [st-tag] Balises pour les blocs parents et enfants */
            'main-tag' => '', // balise principale. indispensable pour utiliser id, class et style
            'item-tag' => 'p', // balise pour un fichier ou dossier
            /* [st-format] Format des éléments mot-clé */
            'date-format' => '%Y/%m/%d %H:%M', // format de la date
            'decimal' => '2', // nombre de décimales pour la taille du fichier
            /* [st-css] Styles CSS */
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '', // style ajouté dans le HEAD de la page
            'id' => '' // Identifiant
        );

        // === correction saisie path et mask
        if (empty($this->options_user['mask'])) {
            if (is_dir($this->options_user[__class__])) {
                $this->options_user['mask'] = '*';
                $this->options_user[__class__] = str_replace('\\', '/', $this->options_user[__class__]);
            } else {
                // on extrait le chemin et le masque
                $this->options_user['mask'] = basename($this->options_user[__class__]);
                $this->options_user[__class__] = str_replace('\\', '/', dirname($this->options_user[__class__]));
            }
        }
        /*
         * // === force tag si liste
         * if (!isset($this->options_user['main-tag']))
         * $this->options_user['main-tag'] ='';
         * if (!isset($this->options_user['item-tag']))
         * $this->options_user['item-tag'] ='';
         * if (strtolower($this->options_user['main-tag']) == 'ul' || strtolower($this->options_user['item-tag']) == 'li') {
         * $this->options_user['main-tag'] = 'ul';
         * $this->options_user['item-tag'] = 'li';
         * }
         */
        // === fusion et controle des options
        $this->options = $this->ctrl_options($options_def);
        $this->options['template'] = $this->get_bbcode($this->options['template']);
        $this->options['template-folder'] = $this->get_bbcode($this->options['template-folder']);

        // extraction des composantes de la recherche
        $path = $this->options[__class__];
        $mask = $this->options['mask'];

        // annuler les echappements du shortcode UP
        $mask = str_replace('\[', '§{', $mask);
        $mask = str_replace('\]', '§}', $mask);
        $mask = str_replace(']', '}', $mask);
        $mask = str_replace('[', '{', $mask);
        $mask = str_replace('§{', '[', $mask);
        $mask = str_replace('§}', ']', $mask);

        // === force tag si liste
        if (strtolower($this->options['main-tag']) == 'ul' || strtolower($this->options['item-tag']) == 'li') {
            $this->options['main-tag'] = 'ul';
            $this->options['item-tag'] = 'li';
        }

        // === CSS-HEAD
        $this->load_css_head($this->options['css-head']);

        // === Recupération de la liste
        $this->result = array();
        $treeview = (strtolower($this->options['main-tag']) == 'ul' || strtolower($this->options['item-tag']) == 'li');
        $this->glob_recursive($path, $mask, (int) $this->options['recursive'], GLOB_BRACE, $treeview);

        if ($treeview) {
            array_unshift($this->result, '<li>' . $path . '<ul>');
            array_push($this->result, '</ul></li>');
        }
        $out = (isset($this->result)) ? implode(PHP_EOL, $this->result) : '';

        // attributs du bloc principal
        if ($this->options['main-tag']) {
            $attr_main['id'] = $this->options['id'];
            $this->get_attr_style($attr_main, $this->options['class'], $this->options['style']);
            // code en retour
            $out = $this->set_attr_tag($this->options['main-tag'], $attr_main, $out);
        }

        return $out;
    }

    /*
     * glob_recursive
     * --------------
     * retourne les fichiers correspondants au masque
     * $max est le niveau d'exploration des sous-dossiers
     */
    function glob_recursive($path, $mask, $max = 0, $flags = 0, $treeview = false)
    {
        $files = glob($path . '/' . $mask, $flags);
        if ($max > 0) {
            $max --;
            foreach (glob($path . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
                if ($treeview) {
                    $tmpl = $this->options['template-folder'];
                    $tmpl = str_ireplace("##foldername##", basename($dir), $tmpl);
                    $tmpl = str_ireplace("##folderpath##", $dir, $tmpl);

                    $this->result[] = '<li>' . $tmpl . '<ul>';
                }
                $this->glob_recursive($dir, $mask, $max, $flags, $treeview);
                if ($treeview)
                    $this->result[] = '</ul></li>';
            }
        }
        foreach ($files as $file) {
            if (! is_dir($file)) // 5.1
                $this->make_item($file);
        }
    }

    /*
     * make_item
     * ---------
     * construit une ligne pour un fichier
     */
    function make_item($file)
    {
        $file = str_replace('\\', '/', $file);
        $abs_file = JPATH_ROOT . '/' . $file;
        $tag = $this->options['item-tag'];
        if (is_file($file)) {
            $tmpl = $this->options['template'];
            $tmpl = str_ireplace("##file##", $file, $tmpl);
            if (strpos($tmpl, '##') !== false) { // v2.8.2
                $info = pathinfo($this->get_url_absolute($file));
                $tmpl = str_ireplace("##dirname##", $info['dirname'], $tmpl);
                $tmpl = str_ireplace("##basename##", $info['basename'], $tmpl);
                $tmpl = str_ireplace("##filename##", $info['filename'], $tmpl);
                $tmpl = str_ireplace("##extension##", $info['extension'], $tmpl);
                $tmpl = str_ireplace("##size##", $this->human_filesize($file, $this->options['decimal']), $tmpl);
                $tmpl = str_ireplace("##date##", $this->up_date_format(date('Y-m-d H:i:s', filemtime($abs_file)), $this->options['date-format']), $tmpl);

                $relpath = trim(substr($info['dirname'], strlen($this->options[__class__])), "/");
                $relpath .= ($relpath) ? '/' : '';
                $tmpl = str_ireplace("##relpath##", $relpath, $tmpl);
            }
            // ajout tag
            $tmpl = '<' . $tag . '>' . $tmpl . '</' . $tag . '>';
        } else { // le dernier dossier du chemin
            $tmp = explode('/', $file);
            $lastdir = array_pop($tmp);
            $tmpl = '<' . $this->options['item-tag'] . '>' . $lastdir; // v2.9 pour php8
        }
        $this->result[] = $tmpl;
    }

    /*
     * human_filesize
     * --------------
     */
    function human_filesize($file, $decimals = 2)
    {
        $bytes = filesize($file);
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    function date_modif($file, $format = 'Y/m/d H:i')
    {
        return date($format, filemtime($file));
    }

    // run
}

// class
