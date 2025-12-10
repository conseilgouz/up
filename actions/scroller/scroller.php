<?php

/**
 * Défilement vertical d'une liste d'éléments
 *
 * syntaxe : {up scroller}suite d'éléments{/up scroller}
 *
 * Attention :
 * - définir un style="height:..." aux images (pas de height="...")
 *
 * @author   LOMART
 * @version  UP-1.7
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit <a href="https://www.jqueryscript.net/slider/Responsive-jQuery-News-Ticker-Plugin-with-Bootstrap-3-Bootstrap-News-Box.html">Responsive jQuery News Ticker de gagi270683 adapté pour UP</a>
 * @tags    layout-dynamic
 */
/*
 * v2.11 - suppression test chargement XML
 * v2.6 - remplacement script JS pour éviter freeze
 */
defined('_JEXEC') or die;

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class scroller extends Lomart\Plugin\Content\Up\Extension\Up
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        UpHelper::load_file($this,'scroller.css');
        UpHelper::load_file($this,'jquery.bootstrap.newsbox.js');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => '', // nombre d'éléments
            /* [st-css] Style CSS*/
            'id' => '', // Identifiant
            'class' => '', // classe(s) pour la balise principale
            'style' => '', // style inline pour la balise principale
            'css-head' => '', // règles CSS définies par le webmaster (ajout dans le head)
        );

        // ===== paramétres attendus par le script JS
        // important: valeurs par défaut entre guillemets pour les chaines
        // attention: le nom des options est transmis au script sous la forme indiqué ici.
        $js_options_def = array(
        /* [st-JS] paramètres Javascript pour configuration */
            'newsPerPage' => 4, // nombre de blocs par page. Idem option principale
            'navigation' => true, // affiche la navigation haut/bas
            'autoplay' => true, // active la lecture automatique
            'direction' => 'up', // sens de défilement (up/down)
            'animationSpeed' => 'normal', // normal, slow ou fast
            'newsTickerInterval' => 4000, // autoplay interval en ms
            'pauseOnHover' => true // arrêt défilement lors survol souris
        );
        // -- non reprises
        //            'runAfterPageLoad' => false,
        //            'direction' => 'up', // sens du défilement (up/down)
        //            'speed' => 'medium', // slow (5000), medium (3000), fast (1200) ou durée en millisecondes
        //            'viewable' => '3', // nombre d'élément ou hauteur du bloc en px (idem scroller)
        //            'pause' => 1 // stop on hover

        // ==== Compatibilité descendante avec scroller_v1
        $this->option_user_replace('runafterpageload', 'autoplay');
        $this->option_user_replace('speed', 'animationspeed');
        $this->option_user_replace('viewable', 'newsperpage');
        $this->option_user_replace('pause', 'pauseonhover');

        // ==== transfert des options vers options JS
        if ($this->options_user[__class__] <> $options_def[__class__]) {
            $this->options_user['newsperpage'] = $this->options_user[__class__];
        }
        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def, $js_options_def);

        // =========== le code JS
        // les options saisies par l'utilisateur concernant le script JS
        // cela évite de toutes les renvoyer au script JS
        $js_options = UpHelper::only_using_options($this,$js_options_def);

        // -- conversion en chaine Json
        // il existe 2 modes: mode1=normal, mode2=sans guillemets
        $js_params = UpHelper::json_arrtostr($this,$js_options);

        // -- initialisation
        $js_code = '$(".' . $options['id'] . '").bootstrapNews(';
        $js_code .= $js_params;
        $js_code .= ');';
        UpHelper::load_jquery_code($this,$js_code);

        // === css-head
        UpHelper::load_css_head($this,$options['css-head']);

        // === Style du bloc principal
        $attr_main['id'] = $options['id'];
        $attr_main['class'] = $options['id'];
        $attr_main['style'] = '';
        UpHelper::get_attr_style($this,$attr_main, 'card', $options['style'], $options['class']);

        // === Contenu a scroller
        $content = '';
        if (UpHelper::ctrl_content_parts($this,$this->content) === true) {
            // === on récupére les parties séparées par {====}
            $parts = UpHelper::get_content_parts($this,$this->content);
            foreach ($parts as $part) {
                $content .= '<item>' . $part . '</item>';
            }
            $content = UpHelper::set_attr_tag($this,'div', $attr_main, $content);
        } else {
            // analyse de la structure du contenu
            require_once($this->upPath . '/assets/lib/simple_html_dom.php');
            $html = new simple_html_dom();
            $html->load('<body>' . $this->content . '</body>');
            $nb = count($html->find('body', 0)->children());
            if ($nb == 1) {
                // on affecte les attributs au bloc externe
                //                $main = $html->find('body', 0)->firstchild();
                //                $main->id = $attr_main['id'];
                //                $main->class = $attr_main['class'];
                //                $main->style = $attr_main['style'];
                //                $content = $main->save();
                foreach ($html->find('body', 0)->firstChild()->children() as $child) {
                    $content .= '<item class="new-item">' . $child->outertext . '</item>';
                }
                $content = UpHelper::set_attr_tag($this,'div', $attr_main, $content);
            } else {
                $firstTag = $html->find('body', 0)->firstChild()->tag;
                // on wrappe les blocs
                foreach ($html->find('body', 0)->children() as $child) {
                    $child->tag = $firstTag;
                    $content .= '<item class="new-item">' . $child->outertext . '</item>';
                }
                $content = UpHelper::set_attr_tag($this,'div', $attr_main, $content);
            }
            $html->clear();
        }
        // ============================= RETOUR

        return $content;
    }

    // run

    /*
     * option_user_replace
     * conversion des options de la première version de cette action vers celles de la nouvelle
     */
    public function option_user_replace($old, $new)
    {
        $old = strtolower($old);
        $new = strtolower($new);
        if (isset($this->options_user[$old])) {
            $this->options_user[$new] = $this->options_user[$old];
            unset($this->options_user[$old]);
        }
    }

}

// class
