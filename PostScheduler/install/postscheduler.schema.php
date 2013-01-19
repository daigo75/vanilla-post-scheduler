<?php if (!defined('APPLICATION')) exit();
/**
Copyright (c) 2013 Diego Zanella (http://dev.pathtoenlightenment.net)

@package PostScheduler for Vanilla Forums 2.0
@author Diego Zanella <diego@pathtoenlightenment.net>
@copyright Copyright (c) 2013 Diego Zanella (http://dev.pathtoenlightenment.net)
@license http://dev.pathtoenlightenment.net/noncommercial-licence/ Noncommercial Licence

Any usage in websites generating revenue, from any source, is prohibited.
*/

require('plugin.schema.php');

/**
 * Handle schema changes.
 */
class PostSchedulerSchema extends PluginSchema {
	/**
	 * Create the table which will store the History of Cron Executions.
	 */
	private function Add_FeaturedColumn($TableName) {
		Gdn::Structure()->Table($TableName);

		Gdn::Structure()
			->Column('Scheduled', 'smallint', 0)
			->Column('ScheduleTime', 'datetime', true)
			->Set();
	}

	private function Drop_FeaturedColumn($TableName) {
		Gdn::Structure()->Table($TableName);

		// Drop "Scheduled" column, if it exists
		if(Gdn::Structure()->ColumnExists('Scheduled')) {
			Gdn::Structure()->DropColumn('Scheduled');
		}
		// Drop "ScheduleTime" column, if it exists
		if(Gdn::Structure()->ColumnExists('ScheduleTime')) {
			Gdn::Structure()->DropColumn('ScheduleTime');
		}
	}

	/**
	 * Create all the Database Objects in the appropriate order.
	 */
	protected function CreateObjects() {
		$this->Add_FeaturedColumn('Discussion');
	}

	/**
	 * Delete the Database Objects.
	 */
	protected function DropObjects() {
		$this->Drop_FeaturedColumn('Discussion');
	}
}
