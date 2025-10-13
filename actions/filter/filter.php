<?php

/**
 * affiche du contenu si toutes les conditions sont remplies
 *
 * <i>Reprise du plugin <a href="https://lomart.fr/extensions/lm-filter">LM-Filter</a> de Lomart</i>
 *
 * SYNTAXE 1 (condition comme option):
 * {up filter | datemax=20171225} contenu si vrai  {====} contenu si faux {/up filter}
 * Le contenu si faux est optionnel. Il doit être après le contenu si vrai et séparé par {===} (au minima 3 signes égal)
 *
 * SYNTAXE 2 (condition comme argument principal):
 * {up filter=datemax:20171225} contenu si vrai  {====} contenu si faux {/up filter}
 *
 * SYNTAXE 3 (mono-shortcode):
 * {up filter | guest=0  | return-true=REGISTER  | return-false=GUEST}
 *   --> guest=0 est vrai si un utilisateur est connecté
 *   --> la valeur retournée est saisie comme option
 *
 * SYNTAXE 4 (multi-conditions et négation):
 * {up filter=!day:1,7 ; hperiod:0900-1200,1500-1900 | return-true=OUVERT  | return-false=FERME}
 *   --> vrai si pas lundi ou dimanche et entre 9-12h ou 15-19h
 *
 * @author    Lomart
 * @version   UP-1.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-2.0.html" target="_blank">GNU/GPL</a>
 * @tags      Editor
 */

/*
 * v1.63 - utilisation de filter_ok
 * v1.7  - l'argument principal est géré comme l'option filter d'une action
 * v2.6  - ajout options : return-true et return-false (pour mono-shortcode)
 * v2.8  - fix si condition alternative non spécifiée dans contenu
 * v2.82 - fix retour
 * v5.0 - bbcode pour return-true et return-false
 * v5.1 - ajout equal, smaller et bigger
 *      - fix return-true et return-false
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Environment\Browser;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class filter extends upAction
{
    public function init()
    {
        // aucune
    }

    public function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        // ===== valeur paramétres par défaut
        // il est indispensable de tous les définir ici
        $options_def = array(
            $this->name => '', // condition sous la forme cond1:val1,val2 ; cond2:val1,val2
            /* [st-cond] Les conditions */
            'datemax' => '', // vrai jusqu'à cette date AAAAMMJJHHMM
            'datemin' => '', // vrai à partir de cette date AAAAMMJJHHMM
            'period' => '', // vrai entre ces dates AAAAMMJJHHMM-AAAAMMJJHHMM
            'day' => '', // liste des jours autorisés. 1=lundi, 7=dimanche
            'month' => '', // liste des mois autorisés. 1=janvier, ...
            'hmax' => '', // vrai jusqu'à cette heure HHMM
            'hmin' => '', // vrai à partir de cette heure HHMM
            'hperiod' => '', // vrai entre ces heures HHMM-HHMM
            'guest' => '', // vrai si utilisateur invité
            'admin' => '', // vrai si admin connecté
            'user' => '', // liste des userid autorisé. ex: 700,790
            'username' => '', // liste des username autorisé. ex: admin,lomart
            'group' => '', // liste des usergroup autorisé. ex: 8,12
            'lang' => '', // liste des langues autorisées. ex: fr,ca
            'mobile' => '', // vrai si affiché sur un mobile
            'homepage' => '', // vrai si affiché sur la page d'accueil
            'server-host' => '', // vrai si le domaine du serveur contient un des termes de la liste
            'server-ip' => '', // vrai si l'adresse IP du serveur des dans la liste
            'artid' => '', // vrai si l'ID de l'article courant est dans la liste
            'catid' => '', // vrai si l'ID de la catégorie de l'article courant est dans la liste
            'menuid' => '', // vrai si l'ID du menu actif est dans la liste
            'equal' => '',  // vrai si op1 = op2 no case sensitive
            'smaller' => '',  // vrai si op1 > op2 no case sensitive
            'bigger' => '',  // vrai si op1 < op2 no case sensitive
            /* [st-return] retour pour version mono-shortcode */
            'return-true' => '1', // valeur retournée si vrai et pas de contenu
            'return-false' => '0', // valeur retournée si faux et pas de contenu
            /* [st-divers] Divers*/
            'info' => '0', // affiche les valeurs actuelles des arguments pour les conditions
            'id' => '', // identifiant
        );

        // ===== fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // ===== Affichage des valeurs actuelles des arguments pour les conditions
        $user = Factory::getApplication()->getIdentity();
        //$language = Factory::getApplication()->getLanguage();
        if ($options['info']) {
            date_default_timezone_set('Europe/Paris');
            $infos['Date'] = date('YmdHi');
            $infos['Hour'] = date('Hi');
            $infos['Day'] = (date("w")) ? date("w") : 7;
            $infos['Month'] = (date("n")) ? date("n") : date("n") + 1;
            $infos['Guest'] = $user->guest;
            $infos['Admin'] = intval(in_array(8, $user->groups));
            $infos['User'] = $user->id;
            $infos['Username'] = $user->username;
            $infos['Group'] = implode(',', $user->groups);
            $infos['Lang'] = substr(Factory::getApplication()->getLanguage()->getTag(), 0, 2);
            // ---
            $browser = Browser::getInstance();
            $infos['Mobile'] = intval($browser->isMobile());
            // --- HOMEPAGE
            $root_link = str_replace('/index.php', '', Uri::root());
            $current_link = preg_replace('/index.php(\/)?/', '', Uri::current(true));
            $infos['Homepage'] = intval($current_link == $root_link);
            // --- SERVER
            $infos['server-host'] = $_SERVER['HTTP_HOST'];
            $infos['server-ip'] = $_SERVER['SERVER_ADDR'];
            // --- ID
            $app = Factory::getApplication();
            $infos['artid'] = $app->getInput()->get('id');
            $input = $app->getInput();
            $infos['catid'] = 'nc';
            if ($input->getCmd('option') == 'com_content' && $input->getCmd('view') == 'article') {
                $cmodel = new Joomla\Component\Content\Site\Model\ArticleModel(array('ignore_request' => true));
                $app       = Factory::getApplication();
                $appParams = $app->getParams();
                $params = $appParams;
                $cmodel->setState('params', $appParams);
                $infos['catid'] = $cmodel->getItem($app->getInput()->get('id'))->catid;
            }
            $infos['view'] = $app->getInput()->getCmd('view');
            $infos['menuid'] = $app->getMenu()->getActive()->id;
            // affichage
            $txt = '<p><b>FILTER VALUES :</b></p>';
            foreach ($infos as $k => $v) {
                $txt .= '<p><b>' . $k . '</b> : ' . $v . '</p>';
            }
            $this->msg_info($txt);
        }
        // ===== récupérer contenu vrai et contenu faux (fix v2.8.2)
        $out_true = $options['return-true'];
        $out_false = $options['return-false'];
        if ($this->content) {
            // le contenu est entre les shortcode
            // nb parts |     vrai     |    faux
            //    0     | return-true  | return-false
            //    1     |   $tmp[0]    |    vide
            //    2     |   $tmp[0]    |   $tmp[1]
            $tmp = $this->get_content_parts($this->content);
            $out_true = $tmp[0];
            $out_false = (isset($tmp[1])) ? $tmp[1] : '';
        }

        // -- Les options indiquées dans le shortcode
        if ($options[__class__]) {
            $conditions = $options[__class__];
        } else {
            $conditions = $this->only_using_options($options_def);
            unset($conditions['info']);
            unset($conditions['id']);
            unset($conditions['return-true']);
            unset($conditions['return-false']);
        }
        // ===== Remplit-on les conditions
        if ($this->filter_ok($conditions) !== true) {
            return $this->get_bbcode($out_false);
        } else {
            return $this->get_bbcode($out_true);
        }
    }

    // run
}

// class
