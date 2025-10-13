<?php

/**
 * Affiche un bouton pour charger le contenu d'un article ou un fichier
 *
 * il est possible de demander un mot de passe
 *
 * syntaxe {up ajax-view=999 | btn-label=Voir un article}
 * syntaxe {up ajax-view=fichier.ext | btn-label=afficher un fichier}
 * syntaxe {up ajax-view=fichier.jpg | btn-label=[img src='photo-mini.jpg'][br]Cliquer pour voir l'image en grand | password=1234}
 *
 * @author   LOMART
 * @version  UP-2.9
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags Editor
 */
defined('_JEXEC') or die();


class ajax_view extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('ajax_ajax_view.js');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // id article ou chemin vers fichier txt ou image
            /* [st-btn] Style du bouton d'appel du contenu ajax */
            'btn-label' => 'en=load content;fr=charger le contenu', // texte sur le bouton appel
            'btn-style' => 'btn btn-primary', // style et classe du bouton appel
            'btn-tag' => 'div', // balise principale
            /* [st-password]Demande de mot de passe */
            'password' => '', // mot de passe pour télécharger le fichier
            /* [st-annexe]options secondaires */
            'HTML' => '0', // 0= aucun traitement, 1=affiche le code, ou liste des tags à garder (ex: img,a)
            'EOL' => '0', // forcer un retour à la ligne
            'ext-images' => 'jpg,png,gif,webp', // les extensions des fichiers images autorisés
            /* [st-style] Style */
            'id' => '', // identifiant
            'main-tag' => 'div', // balise principale
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // règles CSS définies par le webmaster (ajout dans le head)
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        $options['btn-label'] = $this->get_bbcode($options['btn-label']);

        // === css-head
        $this->load_css_head($options['css-head']);

        // controle validite contenu
        $target = $options[__CLASS__];
        if ((int) $target > 0) {
            $type = 'artid'; // id d'un article Joomla
        } else {
            // fichier
            $ext = pathinfo($target, PATHINFO_EXTENSION);
            if (in_array($ext, explode(',', $options['ext-images']))) {
                $type = 'image';
            } elseif (in_array($ext, explode(',', 'txt,html,csv'))) {
                $type = 'text';
            } else {
                return $this->msg_inline($this->trad_keyword('unauthorized_file', $target)); //v31
            }
        }

        // === Password
        if (! empty($options['password'])) {
            $key = file_get_contents($this->actionPath . 'info.key');
            if ($key === false) {
                $key = bin2hex(random_bytes(5));
                file_put_contents($this->actionPath . 'info.key', $key);
            }
            $target = openssl_encrypt($target, 'aes128', $key, 0, '1234567812345678');
        }

        // === attributs du bouton
        $attr_btn = array();
        $attr_btn['data-id'] = $options['id'];
        $attr_btn['data-content'] = $target;
        $attr_btn['data-type'] = $type;
        $attr_btn['data-html'] = $options['HTML'];
        $attr_btn['data-eol'] = $options['EOL'];
        if (! empty($options['password'])) {
            $attr_btn['data-md5'] = password_hash($options['password'], PASSWORD_DEFAULT);
        }
        $this->get_attr_style($attr_btn, $options['btn-style'], 'ajax-view-btn', $options['id']);

        // attributs du bloc résultat (le principal)
        $attr_result = array();
        $this->get_attr_style($attr_result, $options['class'], $options['style'], $options['id'], 'ajax-view-result;display:none');

        // code en retour
        $html[] = $this->set_attr_tag($options['btn-tag'], $attr_btn, $options['btn-label']);
        $html[] = $this->set_attr_tag($options['main-tag'], $attr_result, true);

        return implode(PHP_EOL, $html);
    }

    // run
}

// class
