<?php if (!defined('APPLICATION')) exit();

/**
{licence}
*/

/**
 * Contains handles and methods to alter the standard workflow of Vanilla
 * Activity Notifications.
 *
 * This class is compatible only with Vanilla 2.1b1.
 */
class ActivityManager21b1 extends ActivityManager {
	/* @var int Indicates that a Notification has been scheduled. The value "103"
	 * comes from the fact that "3" means "pending". We add 100 to make sure that
	 * we won't cause a conflict with a new possible value introduced by Vanilla
	 * Team, who, hopefully, will add new values sequentially.
	 */
	const SENT_SCHEDULED = 103;

	// @var array Keeps track of the count of Notifications for each User. Used to increase the count when Scheduled Notifications are processed.
	private $_UserNotificationCounts = array();

	// @var array Holds a list of Discussions, using their ID as a key. Since
	// the same Discussion can generate multiple Activity Notifications, this
	// variable will allow not to query the database every time.
	private $_Discussions = array();

	/**
	 * Retrieves a Discussion using its ID as a key.
	 *
	 * @param int DiscussionID The ID of the Discussion.
	 * @return stdClass An object containing the Discussion data.
	 */
	private function GetDiscussionByID($DiscussionID) {
		// Without a valid Discussion ID, there's no point in proceeding
		if(empty($DiscussionID) || $DiscussionID <= 0) {
			return null;
		}

		// Check if we have the discussion already stored, to avoid fetching it again
		$Discussion = GetValue($DiscussionID, $this->_Discussions);
		if(!empty($Discussion)) {
			return $Discussion;
		}

		// Retrieve the Discussion details and store them before returning them
		$DiscussionModel = new DiscussionModel();
		$this->_Discussions[$DiscussionID] = $DiscussionModel->GetID($DiscussionID);

		return $this->_Discussions[$DiscussionID];
	}

	/**
	 * Alters the SQL of a ActivityModel to hide the Activities that are
	 * scheduled to be sent at a later time.
	 *
	 * @see ActivityManager::ActivityModel_AfterActivityQuery_Handler()
	 */
	public function ActivityModel_AfterActivityQuery_Handler($Sender) {
		$Now = gmdate('Y-m-d H:i:s');
		$Sender->SQL
			->LeftJoin('Discussion d', '(a.RecordType = \'Discussion\') AND (d.DiscussionID = a.RecordID)')
			->BeginWhereGroup()
			->Where('d.Scheduled', null)
			->OrWhere('d.Scheduled', 0)
			->OrWhere('d.ScheduleTime <=', $Now)
			->EndWhereGroup();
	}

	/**
	 * Processes all the Scheduled Notifications and sends them by email. Additionally,
	 * it increases the count of the notifications that should be shown on the website
	 * to each User who chose to be notified that way.
	 *
	 * @param ActivityModel ActivityModel The Activity Model that will be used to
	 * retrieve the Scheduled Notifications.
	 */
	public function SendScheduledNotifications(ActivityModel $ActivityModel) {
		// Retrieve all the Notifications scheduled, due to be sent and not yet sent
		// The filters on Schedule flag and Time are added by
		// PostSchedulerPlugin::ActivityModel_AfterActivityQuery_Handler() method.
		$ActivityNotificationsToSend = $ActivityModel->GetWhere('a.Emailed', self::SENT_SCHEDULED)->Result();

		foreach($ActivityNotificationsToSend as $Activity) {
			// Queue each Activity Notification for sending and update the
			// Notified and Emailed flag to indicate that the notification can now be
			// delivered
			$ActivityModel->SQL->Put('Activity',
															 array('Emailed' => ActivityModel::SENT_PENDING),
															 array('ActivityID' => $Activity['ActivityID']));
			// Send the notification by email. Unfortunately, the QueueNotification()
			// method used in Vanilla 2.0 doesn't work in Vanilla 2.1 as the Activity
			// Model is very buggy
			$ActivityModel->Email($Activity);

			// If "Notified" is not "pending", it means that the notification on the
			// website has already been displayed, or that User doesn't want to see it.
			// In such case, just move on.
			if($Activity['Notified'] != ActivityModel::SENT_PENDING) {
				continue;
			}

			// Increment the count of new Notifications for the User
			$UserID = $Activity['NotifyUserID'];
			if(isset($this->_UserNotificationCounts[$UserID])) {
				$this->_UserNotificationCounts[$UserID] += 1;
			}
			else {
				$this->_UserNotificationCounts[$UserID] = 1;
			}
		}
		// Update Notifications Count for the User
		$this->UpdateUserNotificationsCount();
	}

	/**
	 * Updates the count of unread Notifications for a list of Users.
	 */
	private function UpdateUserNotificationsCount() {
		foreach($this->_UserNotificationCounts as $UserID => $NewNotificationsCount) {
			if($NewNotificationsCount > 0) {
				$CurrentNotificationsCount = Gdn::UserModel()->GetID($UserID)->CountNotifications;
				$TotalNotificationsCount = $CurrentNotificationsCount + $NewNotificationsCount;
				Gdn::UserModel()->SetField($UserID, 'CountNotifications', $TotalNotificationsCount);
			}
		}
	}


	/**
	 * Handler of ActivityModel::BeforeProcessingActivityNotifications.
	 *
	 * Fired before an Activity is processed and saved by Activity Model. This
	 * method checks if the Activity should be scheduled and, in case, it sets the
	 * appropriate fields.
	 *
	 * @param Controller Sender Sender controller instance.
	 */
	public function ActivityModel_BeforeProcessingActivityNotifications_Handler($Sender) {
		$Activity = &$Sender->EventArguments['Activity'];

		// If Activity is not related to a Discussion, don't do anything
		if(strcasecmp($Activity['RecordType'], 'discussion') != 0) {
			return;
		}

		// RecordID contains the ID of the entity to which the Activity is related.
		// In our case, it's the Discussion ID
		$Discussion = $this->GetDiscussionByID($Activity['RecordID']);

		$Now = gmdate('Y-m-d H:i:s');
		/* If Discussion is scheduled and not yet due to be displayed, flag the
		 * Activity as Scheduled, so that it won't be sent by email.There is no
		 * need to alter field "Notified" to make sure that the Notification won't
		 * be displayed to the User when he will access the forum, as the Activity
		 * will be filtered out by the query, amended in ActivityModel_AfterActivityQuery_Handler().
		 */
		if(GetValue('Scheduled', $Discussion) == PostSchedulerPlugin::SCHEDULED_YES &&
			 GetValue('ScheduleTime', $Discussion) > $Now) {
			// Override "Emailed" field, setting it to a value not recognised by the
			// standard ActivityModule. This will allow the plugin to easily identify
			// the Notifications to be sent at a later time
			if($Activity['Emailed'] == ActivityModel::SENT_PENDING) {
				$Activity['Emailed'] = self::SENT_SCHEDULED;
			}

			// Override "Notified" field to prevent ActivityModel from incrementing
			// the Notification count on User's record. This will prevent discrepancies
			// in the User Interface (without this trick, the User would see a number
			// informing him of new Notifications, but then the list would be empty as
			// the query is filtered
			if($Activity['Notified'] == ActivityModel::SENT_PENDING) {
				$Activity['Notified_Removed'] = 1;
				unset($Activity['Notified']);
			}
		}
	}

	/**
	 * Handler of event ActivityModel::BeforeSave. Triggered just before an Activity
	 * is saved to database. This method restores some data that was removed by
	 * ActivityManager21b1::ActivityModel_BeforeProcessingActivityNotifications_Handler().
	 *
	 * @param Controller Sender Sender controller instance.
	 * @see ActivityManager21b1::ActivityModel_BeforeProcessingActivityNotifications_Handler().
	 */
	public function ActivityModel_BeforeSave_Handler($Sender) {
		$Activity = &$Sender->EventArguments['Activity'];

		// If field Notified does not exists, it means that it was removed,
		// in order to prevent Vanilla from increasing the Notification Count for
		// the User too early, when new Discussions is scheduled to appear at a
		// later time. At this point, Notification Count has already been processed,
		// therefore we can restore the original value of "Notified" field
		if(!isset($Activity['Notified'])) {
			$Activity['Notified'] = ActivityModel::SENT_PENDING;
		}
	}
}
