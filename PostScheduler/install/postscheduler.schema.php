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
	 * Adds the columns that will indicate if a Discussion is scheduled.
	 */
	private function AddScheduledDiscussionColumns() {
		Gdn::Structure()->Table('Discussion');

		Gdn::Structure()
			->Column('Scheduled', 'smallint', 0)
			->Column('ScheduleTime', 'datetime', true)
			->Set();
	}

	/**
	 * Drops the columns that will indicate if a Discussion is scheduled.
	 */
	private function DropScheduledDiscussionColumns() {
		Gdn::Structure()->Table('Discussion');

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
		$this->AddScheduledDiscussionColumns();
	}

	/**
	 * Delete the Database Objects.
	 */
	protected function DropObjects() {
		$this->DropScheduledDiscussionColumns();
	}
}
