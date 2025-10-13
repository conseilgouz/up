<?php

/**
 * Conversion d'un contenu au format CSV en liste avec point de conduite
 *
 * 1/  {up csv2list=emplacement-fichier} // le contenu est lu dans un fichier
 * 2/  {up csv2list}
 *        article 1;5€
 *        article 2;25€
 *     {/up csv2list}
 *
 * Ressources : <a href="https://lomart.fr/references/26-caracteres-unicode" target="_blank">caractères unicode</a>, <a href="https://lomart.fr/references/59-caracteres-speciaux" target="_blank">caractères spéciaux</a> et <a href="https://lomart.fr/references/24-couleurs-sures" target="_blank">couleurs sures</a>
 * @author   LOMART
 * @version  UP-1.6
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Layout-static
 */
defined('_JEXEC') or die;

class csv2list extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('csv2list.css');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // chemin vers fichier à afficher
            'separator' => ';', // séparateur des colonnes
            'HTML' => 'b,a,span,strong,i,em,u,mark,small,img,code', // 0= aucun traitement, 1=affiche le code, ou liste des tags a garder (strip_tags)
            'model' => 'stack', // nom de la classe modèle dans le fichier csv2list.css
            /* [st-LEADERS] Style des points de conduite */
            'leaders' => '0', // points de conduite
            'leaders-color' => '', // couleur points de conduite
            /* [st-UL]  style du bloc principal de la liste */
            'class' => '', // classe(s) pour la balise UL
            'style' => '', // style inline pour la balise UL
            'bgcolor' => '', // couleur de fond. #FFF par defaut
            /* [st-LI] style des lignes de la liste */
            'col-style-*' => '', // style inline pour les nièmes balise SPAN
            'list-style' => '', // code hexadecimal et couleur du caractère (bb,red ou 26BD,#F00)
            /* [st-HEADER] Style de l'entête */
            'header' => '0', // 0: pas de titre, 1: la première ligne du contenu sera le titre des colonnes
            'header-class' => '', // classe(s) pour la première balise LI si titre
            'header-style' => '', // style pour la première balise LI si titre
            'header-bgcolor' => '', // couleur de fond du titre
            /* [st-FOOTER] Style du pied de liste*/
            'footer' => '0', // 0: pas de pied, 1: la dernière ligne du contenu sera le pied des colonnes
            'footer-class' => '', // classe(s) pour la dernière balise LI si pied
            'footer-style' => '', // style pour la dernière balise LI si pied
            'footer-bgcolor' => '', // couleur de fond de la dernière ligne
            /* [st-annexe] style et options secondaires */
            'id' => '', // identifiant
            'css-head' => '' // règles CSS définies par le webmaster (ajout dans le head)
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        $id = '#' . $options['id'];

        // === css-head
        $this->load_css_head(str_ireplace('#id', $id, $options['css-head']));

        // ========================
        // === recup du contenu CSV
        // ========================
        // 1 - le texte entre les shortcodes
        $content = $this->content;

        // 2 - le contenu d'un fichier
        $filename = $options[__class__];
        if ($content == '' and $filename != '') {
            $content = $this->get_html_contents($filename);
        }
        if ($content == '') {
            $content = $this->msg_inline('csv2table - content not found ' . $filename);
        }

        // ============================
        // nettoyage et mise en tableau
        // ============================
        //        $content = $this->get_content_csv($content, 'a,img,strong,em');
        $content = $this->get_content_csv($content, false);

        // === Contenu et style de la liste
        foreach ($content as $key => $val) {
            if (trim($val)) {
                $csv[$key] = array_map('trim', str_getcsv($val, $options['separator'], '"', '\\'));
            }
        }
        // -- UL
        $attr_main['id'] = $options['id'];
        $attr_main['style'] = $options['style'];
        $attr_main['class'] = 'csv2list';
        $this->add_class($attr_main['class'], $options['model']);
        if ($options['leaders'] != '0') {
            $attr_main['class'] .= ' leaders';
            if ($options['leaders'] != '1') {
                $str = str_pad('', 160, str_replace('#', ' ', $options['leaders']));
                $css[] = $id . ' li:not(.header):not(.footer):after{content:"' . $str . '"}';
            }
            if ($options['leaders'] && $options['leaders-color']) {
                $css[] = $id . '.leaders li:after {color:' . $options['leaders-color'] . '}';
            }
        }
        $this->add_class($attr_main['class'], $options['class']);

        // -- LI
        if ($options['bgcolor']) {
            $css[] = $id . ',';
            $css[] = $id . ' li > span';
            $css[] = '{background:' . $options['bgcolor'] . '}';
        }
        if ($options['list-style']) {
            list($code, $color) = explode(',', $options['list-style'] . ',,');
            //$code = (ord($code[0]) != 92) ? chr(92) . $code : $code;
            $str = $id . ' li:not(.header):not(.footer):before {';
            $str .= ($code) ? 'content:"\\' . $code . '\a0";' : ''; // +espace
            $str .= ($color) ? 'color:' . $color . ';' : '';
            $str .= ($options['bgcolor']) ? 'background:' . $options['bgcolor'] . ';' : '';
            $css[] = $str . '}';
        }
        for ($i = 1; $i <= 6; $i++) {
            if ($options['col-style-' . $i]) {
                $css[] = $id . ' li:not(.header):not(.footer) span:nth-child(' . $i . ') {';
                $css[] = $options['col-style-' . $i] . '}';
            }
        }

        // === Titre (header)
        switch ($options['header']) {
            case '0':
                // pas de titre
                $csvHead = null;
                break;
            case '1':
                // 1ere ligne est le titre
                $csvHead = array_shift($csv);
                break;
            default:
                // la valeur est le titre au format csv
                $csvHead = str_getcsv($options['header'], $options['separator'], '"', '\\');
        }
        if (!is_null($csvHead)) {
            $attr_header['class'] = 'header';
            $this->add_class($attr_header['class'], $options['header-class']);
            $attr_header['style'] = $options['header-style'];
            if ($options['header-bgcolor']) {
                $css[] = $id . ' li.header,';
                $css[] = $id . ' li.header > span {';
                $css[] = 'background:' . $options['header-bgcolor'] . '}';
            }
        }

        // === Foot (pied de table)
        switch ($options['footer']) {
            case '0':
                // pas de pied
                break;
            case '1':
                // derniere ligne est le pied
                $csvFoot = array_pop($csv);
                break;
            default:
                // la valeur est le titre au format csv
                $csvFoot = str_getcsv($options['footer'], $options['separator'], '"', '\\');
        }
        if (isset($csvFoot)) {
            $attr_footer['class'] = 'footer';
            $this->add_class($attr_footer['class'], $options['footer-class']);
            $attr_footer['style'] = $options['footer-style'];
            if ($options['footer-bgcolor']) {
                $css[] = $id . ' li.footer,';
                $css[] = $id . ' li.footer > span {';
                $css[] = 'background:' . $options['footer-bgcolor'] . '}';
            }
        }

        // -- envoi du CSS dans le head
        if (isset($css)) {
            $this->load_css_head(implode(PHP_EOL, $css));
        }

        // =================================================== formattage HTML
        $html[] = $this->set_attr_tag('ul', $attr_main);

        // -- entete liste
        if ($csvHead) {
            $html[] = $this->set_attr_tag('li', $attr_header);
            foreach ($csvHead as $col) {
                $html[] = '<span>' . trim($col) . '</span>';
            }
            $html[] = '</li>';
        }
        // -- contenu table
        //$html[] = '<div class="list-body">';
        foreach ($csv as $lign) {
            $txt = '';
            $class = '';
            foreach ($lign as $key => $col) {
                $puce = '';
                if ($key == 0 && $col[0] == '[') {
                    list($arg, $col) = array_map('trim', explode(']', substr($col, 1)));
                    if (!strpos($arg, ',')) {
                        $class = ' class="' . $arg . '"';
                    } else {
                        list($code, $color) = explode(',', $arg);
                        $puce = '<span style="color:' . $color . ';';
                        $puce .= ($options['bgcolor']) ? 'background:' . $options['bgcolor'] : '';
                        $puce .= '">&#x' . $code . '&nbsp;</span>';
                    }
                }
                $txt .= '<span>' . $puce . $col . '</span>';
            }
            $html[] = '<li' . $class . '>' . $txt . '</li>';
        }
        //$html[] = '</div>';
        // -- pied de table
        if (isset($csvFoot)) {
            $html[] = $this->set_attr_tag('li', $attr_footer);
            foreach ($csvFoot as $col) {
                $html[] = '<span>' . trim($col) . '</span>';
            }
            $html[] = '</li>';
        }
        // -- c'est fini
        $html[] = '</ul>';
        return implode(PHP_EOL, $html);
    }

    // run
}

// class
