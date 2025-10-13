<?php

defined('_JEXEC') or die();

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;

function demo_today()
{
    $date = new Date('now');
    $date->setTimezone(Factory::getApplication()->getIdentity()->getTimezone());
    return $date->format('\l\e l d F Y à G\hi');
}
