<?php

use Hubzero\Content\Migration\Base;

// No direct access
defined('_HZEXEC_') or die();

/**
 * Migration script for adding Newsletter - Feedaggregator plugin
 **/
class Migration20170831000000PlgNewsletterFeedaggregator extends Base
{
	/**
	 * Up
	 **/
	public function up()
	{
		$this->addPluginEntry('newsletter', 'feedaggregator');
	}

	/**
	 * Down
	 **/
	public function down()
	{
		$this->deletePluginEntry('newsletter', 'feedaggregator');
	}
}