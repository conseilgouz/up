<?php

/**
 * UP - Universal Plugin
 * Classe assurant la compatibilité avec UP version pré-6
 * @author    Pascal
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class upAction extends Lomart\Plugin\Content\Up\Extension\Up
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
// l'appel à cette fonction utilise des arguments variables, on la laisse dans la classe de compatibilité telle quelle
    public function get_attr_style($attr_array, ...$args)
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

    public function load_file($ficpath, $options = array(), $attributes = array())
    {
        return UpHelper::load_file($this,$ficpath, $options, $attributes);
    }
    public function get_asset_path($url)
    {
        return UpHelper::get_asset_path($this,$url);
    }

    public function load_js_file_body($ficpath)
    {
        return UpHelper::load_js_file_body($this,$ficpath);
    }
    public function load_js_code($code, $in_head = true)
    {
        return UpHelper::load_js_code($this,$code, $in_head);
    }
    public function load_jquery_code($code, $in_head = true)
    {
        return UpHelper::load_jquery_code($this,$code, $in_head);
    }
    public function load_css_head($code, $id = null)
    {
        return UpHelper::load_css_head($this,$code, $id);
    }
    public function load_custom_code_head($code)
    {
        return UpHelper::load_custom_code_head($this,$code);
    }
    public function get_html_contents($url, $timeout = 10, $url2 = '')
    {
        return UpHelper::get_html_contents($this,$url, $timeout, $url2);
    }
    public function get_url_relative($url, $urlencode = false)
    {
        return UpHelper::get_url_relative($this,$url, $urlencode);
    }
    public function get_url_absolute($url, $urlencode = false)
    {
        return UpHelper::get_url_absolute($this,$url, $urlencode);
    }
    public function myUrlEncode($url)
    {
        return UpHelper::myUrlEncode($this,$url);
    }
    public function on_server($url)
    {
        return UpHelper::on_server($this,$url);
    }
    public function load_upcss()
    {
        return UpHelper::load_upcss($this);
    }
    public function get_custom_path($file, $path = null, $alert = true)
    {
        return UpHelper::get_custom_path($this,$file, $path, $alert);
    }
    public function load_inifile($file, $sections = false, $alert = true)
    {
        return UpHelper::load_inifile($this,$file, $sections, $alert);
    }
    public function str_append($str, $add, $sep = ' ', $prefix = '', $suffix = '')
    {
        return UpHelper::str_append($this,$str, $add, $sep, $prefix, $suffix);
    }
    public function add_str($str, $add, $sep = ' ', $prefix = '', $suffix = '')
    {
        return UpHelper::add_str($this,$str, $add, $sep, $prefix, $suffix);
    }
    public function add_class($str, $newclass, $prefix = '')
    {
        return UpHelper::add_class($this,$str, $newclass, $prefix);
    }
    public function add_style($str, $property, $val)
    {
        return UpHelper::add_style($this,$str, $property, $val);
    }
    public function kw_replace(&$tmpl, $keyword, $replace)
    {
        return UpHelper::kw_replace($this,$tmpl, $keyword, $replace);
    }
    public function ctrl_unit($size, $unit = 'px,%,em,rem')
    {
        return UpHelper::ctrl_unit($this,$size, $unit);
     }
    public function convert_size($size, $unit_target = 'px')
    {
        return UpHelper::convert_size($this,$size, $unit_target);
    }
    public function link_humanize($unc, $capitalize = true)
    {
        return UpHelper::link_humanize($this,$unc, $capitalize);
     }
    public function import_content($content)
    {
        return UpHelper::import_content($this,$content);
    }
    public function preg_string($regex, $source)
    {
        return UpHelper::preg_string($this,$regex, $source);
    }
    public function strtoarray($str, $row = ',', $col = ':', $quote = true)
    {
        return UpHelper::strtoarray($this,$str, $row, $col, $quote);
    }
    public function supertrim($str, $add = '')
    {
        return UpHelper::supertrim($this,$str, $add);
    }
    public function spaceNormalize($str, $add = '')
    {
        return UpHelper::spaceNormalize($this,$str,$add);
    }
    public function get_attr_tag($tag, $force = 'id,class,style')
    {
        return UpHelper::get_attr_tag($this,$tag, $force);
    }
    public function set_attr_tag($tag, $attr, $close = false, $doublequote = true, $bbcode = false)
    {
        return UpHelper::set_attr_tag($this,$tag, $attr, $close, $doublequote, $bbcode);
    }
    public function clean_HTML($content, $tags = false, $forceEOL = false)
    {
        return UpHelper::clean_HTML($this,$content, $tags, $forceEOL);
    }
    public function get_code($code, $quote = false)
    {
        return UpHelper::get_code($this,$code, $quote);
    }
    public function get_bbcode($arg, $tags = null)
    {
        return UpHelper::get_bbcode($this,$arg, $tags);
    }
    public function ctrl_options($options_def, $js_options_def = [], $optmask = '')
    {
        return UpHelper::ctrl_options($this,$options_def, $js_options_def, $optmask);
    }
    public function set_option_user_if_true($option, $val)
    {
        return UpHelper::set_option_user_if_true($this,$option, $val);
    }
    public function js_actualise($actionName, $val, &$options, &$js_options_def)
    {
        return UpHelper::js_actualise($this,$actionName, $val, $options, $js_options_def);
    }
    public function only_using_options($options_def, $options_user = null)
    {
        return UpHelper::only_using_options($this,$options_def, $options_user);
    }
    public function ctrl_argument($arg, $autorized_list, $debug = true)
    {
        return UpHelper::ctrl_argument($this,$arg, $autorized_list, $debug);
    }
    public function get_action_pref($key, $default = null)
    {
        return UpHelper::get_action_pref($this,$key, $default);
    }
    public function params_decode($str, $sep_param = ',', $sep_key = ':', $quote = '"', $echap = '\\')
    {
        return UpHelper::params_decode($this,$str, $sep_param, $sep_key, $quote, $echap);
    }
    public function get_db_value($select, $table, $where)
    {
        return UpHelper::get_db_value($this,$select, $table, $where);
    }
    public function get_jsontoarray($filename, $ficpath = '')
    {
        return UpHelper::get_jsontoarray($this,$filename, $ficpath);
    }
    public function json_arrtostr($array, $mode = 1, $bracket = true)
    {
        return UpHelper::json_arrtostr($this,$array, $mode, $bracket);
    }
    public function ctrl_content_exists()
    {
        return UpHelper::ctrl_content_exists($this);
    }
    public function ctrl_content_parts($content)
    {
        return UpHelper::ctrl_content_parts($this,$content);
    }
    public function get_content_parts($content)
    {
        return UpHelper::get_content_parts($this,$content);
    }
    public function get_content_shortcode($content, $keyword = '.*')
    {
        return UpHelper::get_content_shortcode($this,$content, $keyword);
    }
    public function get_content_csv($content, $cleanTags = '', $bbcode = '')
    {
        return UpHelper::get_content_csv($this,$content, $cleanTags, $bbcode);
    }
    public function filter_ok($conditions, $if_empty = true)
    {
        return UpHelper::filter_ok($this,$conditions, $if_empty);
    }
    public function set_demopage($webpage = '')
    {
        return UpHelper::set_demopage($this,$webpage);
    }
    public function up_actions_list($exclude_prefix = '_,x_')
    {
        return UpHelper::up_actions_list($this,$exclude_prefix);
    }
    public function up_prefset_list($action_name = null, $full = true)
    {
        return UpHelper::up_prefset_list($this,$action_name, $full);
    }
    public function get_dico_synonym($keyword)
    {
        return UpHelper::get_dico_synonym($this,$keyword);
    }
    public function shortcode2code($str)
    {
        return UpHelper::shortcode2code($this,$str);
    }
    public function up_action_infos($action_name, $lang = null)
    {
        return UpHelper::up_action_infos($this,$action_name, $lang);
    }
    public function up_action_options($action_name, $to_csv = false, $lang = null)
    {
        return UpHelper::up_action_options($this,$action_name, $to_csv, $lang);
    }
    public function up_help_txt($actionName = null)
    {
        return UpHelper::up_help_txt($this,$actionName);
    }
    public function sreplace($old, $new, $src, $nb = 1)
    {
        return UpHelper::sreplace($this,$old, $new, $src, $nb);
    }
    public function trad_keyword($key, $str = '')
    {
        return UpHelper::trad_keyword($this,$key, $str);
    }
    public function set_locale($tag = '')
    {
        UpHelper::set_locale($this,$tag);
    }
    public function up_date_format($date, $format = null, $locale = '', $http = true)
    {
        return UpHelper::up_date_format($this,$date, $format, $locale, $http);
    }
    public function up_strtotime($date)
    {
        return UpHelper::up_strtotime($this,$date);
    }
    public function mail2admin($suject, $text)
    {
        return UpHelper::mail2admin($this,$suject, $text);
    }
    public function msg_journal($text)
    {
        return UpHelper::msg_journal($this,$text);
    }
    public function msg_error($text)
    {
        return UpHelper::msg_error($this,$text);
    }
    public function msg_info($text = ' ', $title = '')
    {
        return UpHelper::msg_info($this,$text, $title);
    }
    public function msg_inline($text)
    {
        return UpHelper::msg_inline($this,$text);
    }
    public function replace_class2style($classAndStyle, $optionName = 'option_style')
    {
        return UpHelper::replace_class2style($this,$classAndStyle, $optionName);
    }
    public function ctrl_timer($info = '')
    {
        return UpHelper::ctrl_timer($this,$info);
    }
    // fin class upaction
}
