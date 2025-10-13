<?php

/**
 * affiche la timeline Tweeter
 * {up tweeter-timeline=<tweet id>}
 *
 * voir : <a href='https://dev.twitter.com/web/embedded-timelines/parameters'>dev.twitter.com/web/embedded-timelines/parameters</a>
 * @author      PMLECONTE
 * @version     UP-1.3
 * @license     <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags        Widget
 */
defined('_JEXEC') or die;

class tweeter_timeline extends upAction
{
    public function init()
    {
        $this->load_file('tweeter_timeline.js');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        $options_def = array(
          __class__ => '', // tweet id
          /* [st-tweeter] Paramétres pour API tweeter*/
          'theme' => '', // light ou dark
          'link-color' => '', // couleur des liens en hexa
          'border-color' => '', // couleur des bordures en hexa
          'height' => '400', // hauteur
          'width' => '', // largeur
          'tweet-limit' => '', // nombre maxi de tweet, sinon indéfini
          'chrome' => '', // noheader nofooter noborders transparent noscrollbar
          'lang' => 'fr', // code langage
          /* [st-css] Style CSS*/
          'id' => '', // identifiant
          'style' => '', // classes et style inline bloc parent
          'class' => '', // classe bloc parent (obsolète)
        );
        $this->set_option_user_if_true(__class__, $this->actionUserName);

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // attributs pour div externe
        $attr_outer['id'] = $options['id'];
        $this->get_attr_style($attr_outer, $options['class'], $options['style']);

        // attributs pour div interne
        $attr_in['class'] = 'twitter-timeline';
        $attr_in['href'] = 'https://twitter.com/' . $options[__class__];
        $attr_in['data-theme'] = $options['theme'];
        $attr_in['data-link-color'] = $options['link-color'];
        $attr_in['data-border-color'] = $options['border-color'];
        $attr_in['data-height'] = $options['height'];
        $attr_in['data-width'] = $options['width'];
        $attr_in['data-tweet-limit'] = $options['tweet-limit'];
        $attr_in['data-chrome'] = $options['chrome'];
        $attr_in['data-lang'] = $options['lang'];
        $attr_in['target'] = '_blank';


        $out = $this->set_attr_tag('div', $attr_outer);
        $out .= $this->set_attr_tag('a', $attr_in);
        if (empty($this->content)) {
            $out .= '<i class="fab fa-square-x-twitter fs150"></i> ' . $this->lang('en=follow;fr=suivre') .' ' . $options[__class__];
        } else {
            $out .= $this->content;
        }
        $out .= '</a>';
        $out .= '</div>';

        return $out;
    }

    // run
}

// class
