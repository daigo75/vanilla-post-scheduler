<?php if (!defined('APPLICATION')) exit();
/**
{licence}
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
			->Column('ScheduleTime', 'datetime')
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
