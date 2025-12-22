<?php
/**
 *
 * @package plg_UP for Joomla! 3.0+
 * @version $Id: up.php 2025-11-06 $
 * @author Lomart
 * @copyright (c) 2025 Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 *
 * */

defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;

?>
  <button class="btn btn-primary" type="button" id="compile" style="margin-left:55%;width:20%;display:none;margin-bottom:1em" title="<?php echo Text::_('UP_COMPILE_DESC');?>">
    <?php echo Text::_('UP_COMPILE_LABEL'); ?>
  </button>
  <div id="compile_message" aria-live="polite"></div>
