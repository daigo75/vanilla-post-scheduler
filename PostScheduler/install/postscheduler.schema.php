<?php if (!defined('APPLICATION')) exit();
/**
{licence}
*/

require('plugin.schema.php');

/**
 * Handle schema changes.
 */
class PostSchedulerSchema extends PluginSchema {
	// @var string Stores the version of Vanilla. Used to determine which operations to perform.
	private $_VanillaVersion;

	public function __construct() {
		parent::__construct();

		// Split Vanilla version into its parts (major version, minor version, release, build)
		$VanillaVersionParts = explode('.', APPLICATION_VERSION);
		// Obtain Vanilla version without the period (i.e. 2.0 becomes 20)
		$this->_VanillaVersion = $VanillaVersionParts[0] . $VanillaVersionParts[1];
	}

	/**
	 * Adds the columns that will indicate if a Discussion is scheduled.
	 */
	private function AddScheduledDiscussionColumns() {
		Gdn::Structure()->Table('Discussion');

		Gdn::Structure()
			->Column('Scheduled', 'smallint', 0)
			->Column('ScheduleTime', 'datetime', true)
			->Set();

		$this->CreateIndex('Discussion',
											 'IX_DiscussionSchedule',
											 array('ScheduleTime'),
											 '');
	}

	/**
	 * Drops the columns that will indicate if a Discussion is scheduled.
	 */
	private function DropScheduledDiscussionColumns() {
		$this->DropIndex('Discussion', 'IX_DiscussionSchedule');

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
	 * Adds the columns that will indicate if an Activity Notification is scheduled.
	 */
	private function AddScheduledActivityNotificationColumns() {
		Gdn::Structure()->Table('Activity');

		$Method = 'AddActivityColumns_Vanilla' . $this->_VanillaVersion;
		if(method_exists($this, $Method)) {
			$this->$Method();
		}
	}

	/**
	 * Alters the Activity table on a Vanilla 2.0 installation.
	 *
	 * @see PostSchedulerSchema::AddScheduledActivityNotificationColumns().
	 */
	private function AddActivityColumns_Vanilla20() {
		Gdn::Structure()
			->Column('NotificationSent', 'smallint', 1)
			->Column('DiscussionID', 'smallint', true)
			->Set();

		$this->CreateIndex('Activity',
											 'IX_ActivityNotificationSent',
											 array('NotificationSent'),
											 '');
		$this->CreateIndex('Activity',
											 'IX_ActivityDiscussionID',
											 array('DiscussionID'),
											 '');
	}

	/**
	 * Alters the Activity table on a Vanilla 2.1b1 installation.
	 *
	 * @see PostSchedulerSchema::AddScheduledActivityNotificationColumns().
	 */
	private function AddActivityColumns_Vanilla21b1() {
		$this->CreateIndex('Activity',
											 'IX_ActivityNotificationSent',
											 array('Emailed'),
											 '');
		$this->CreateIndex('Activity',
											 'IX_ActivityDiscussionID',
											 array('RecordType', 'RecordID'),
											 '');
	}

	/**
	 * Drops the columns that will indicate if a Discussion is scheduled.
	 */
	private function DropScheduledActivityNotificationColumns() {
		$this->DropIndex('Discussion', 'IX_ActivityNotificationSent');
		$this->DropIndex('Discussion', 'IX_ActivityDiscussionID');

		Gdn::Structure()->Table('Activity');

		$Method = 'DropActivityColumns_Vanilla' . $this->_VanillaVersion;
		if(method_exists($this, $Method)) {
			$this->$Method();
		}
	}

	/**
	 * Drops columns added to Activity table on a Vanilla 2.0 installation.
	 *
	 * @see PostSchedulerSchema::DropScheduledActivityNotificationColumns().
	 */
	private function DropActivityColumns_Vanilla20() {
		// Drop "NotificationSent" column, if it exists
		if(Gdn::Structure()->ColumnExists('NotificationSent')) {
			Gdn::Structure()->DropColumn('NotificationSent');
		}

		// Drop "DiscussionID" column, if it exists
		if(Gdn::Structure()->ColumnExists('DiscussionID')) {
			Gdn::Structure()->DropColumn('DiscussionID');
		}
	}

	/**
	 * Drops columns added to Activity table on a Vanilla 2.1b1 installation.
	 *
	 * @see PostSchedulerSchema::DropScheduledActivityNotificationColumns().
	 */
	private function DropActivityColumns_Vanilla21b1() {
		// Nothing to do for Vanilla 2.1b1
	}

	/**
	 * Create all the Database Objects in the appropriate order.
	 */
	protected function CreateObjects() {
		$this->AddScheduledDiscussionColumns();
		$this->AddScheduledActivityNotificationColumns();
	}

	/**
	 * Delete the Database Objects.
	 */
	protected function DropObjects() {
		$this->DropScheduledDiscussionColumns();
		$this->DropScheduledActivityNotificationColumns();
	}
}
