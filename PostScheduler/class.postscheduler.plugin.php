<?php if (!defined('APPLICATION')) exit();
/**
{licence}
*/

// Define the plugin
$PluginInfo['PostScheduler'] = array(
	'Name' => 'Post Scheduler',
	'Description' => 'Allows to schedule a Discussion to become visible at from a specific date and time.',
	'Version' => '13.02.24',
	'RequiredApplications' => array('Vanilla' => '2.0.10'),
	'RequiredTheme' => FALSE,
  'RequiredPlugins' => array('Logger' => '13.02.01'),
	'HasLocale' => FALSE,
	'SettingsUrl' => '/plugin/postscheduler',
	'SettingsPermission' => 'Garden.AdminUser.Only',
	'Author' => 'Diego Zanella',
	'AuthorEmail' => 'diego@pathtoenlightenment.net',
	'AuthorUrl' => 'http://dev.pathtoenlightenment.net',
	'RegisterPermissions' => array('Plugins.PostScheduler.Manage',
																 'Plugins.PostScheduler.ScheduleDiscussions',
																 'Plugins.PostScheduler.ViewAllScheduled'
																 ),
);

// Load validation functions
require(PATH_PLUGINS . '/PostScheduler/lib/postscheduler.validation.php');

/**
 * Allows to schedule a Discussion to become visible from a specific date and time.
 */
class PostSchedulerPlugin extends Gdn_Plugin {
	// @var Logger Internal Logger.
	private $Log;

	// Values for the Discussion.Schedule field
	const SCHEDULED_YES = 1;
	const SCHEDULED_NO = 0;

	// Values for the Activity.Sent field
	const SENT_YES = 1;
	const SENT_NO = 0;

	// Default jQuery UI Theme to be used by default
	const DEFAULT_UI_THEME = 'redmond';

	// @var array Holds a list of Discussions, using their Route as a key. It's
	// used to schedule Activity Notifications, which only contain the Discussion
	// Route. Since the same Discussion can generate multiple Activity
	// Notifications, this variable will allow not to query the database every
	// time.
	private $_DiscussionsByRoute = array();

	/**
	 * Plugin constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->Log = LoggerPlugin::GetLogger();
	}

	/**
	 * Base_Render_Before Event Hook
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function Base_Render_Before($Sender) {
		$Sender->AddCssFile('postscheduler.css', 'plugins/PostScheduler/design/css');

		// Add the Sprites for some menu items (Vanilla 2.1 only)
		if(Gdn::PluginManager()->CheckPlugin('Sprites')) {
			$Sender->AddCssFile('sprites.css', 'plugins/PostScheduler/design/css');
		}
	}

	/**
	 * Create a method called "PostScheduler" on the PluginController
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function PluginController_PostScheduler_Create($Sender) {
		/*
		 * If you build your views properly, this will be used as the <title> for your page, and for the header
		 * in the dashboard. Something like this works well: <h1><?php echo T($this->Data['Title']); ?></h1>
		 */
		$Sender->Title('Post Scheduler Plugin');
		$Sender->AddSideMenu('plugin/postscheduler');

		$Sender->Form = new Gdn_Form();

		$this->Dispatch($Sender, $Sender->RequestArgs);
	}

	public function Controller_Index($Sender) {
		// Prevent non-admins from accessing this page
		$Sender->Permission('Vanilla.Settings.Manage');
		$Sender->SetData('PluginDescription', $this->GetPluginKey('Description'));

		//$Validation = new Gdn_Validation();
		//$ConfigurationModel = new Gdn_ConfigurationModel($Validation);
		//$ConfigurationModel->SetField(array(
		//));

		// Set the model on the form.
		//$Sender->Form->SetModel($ConfigurationModel);

		//// If seeing the form for the first time...
		//if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
		//	// Apply the config settings to the form.
		//	//$Sender->Form->SetData($ConfigurationModel->Data);
		//} else {
		//	$ConfigurationModel->Validation->ApplyRule('Plugin.PostScheduler.TrimSize', 'Integer');
		//
		//	$Saved = $Sender->Form->Save();
		//	if ($Saved) {
		//		$Sender->StatusMessage = T("Your changes have been saved.");
		//	}
		//}

		// GetView() looks for files inside plugins/PluginFolderName/views/ and returns their full path. Useful!
		$Sender->Render($this->GetView('postscheduler_generalsettings_view.php'));
	}

	/**
	 * Add a link to the dashboard menu
	 *
	 * By grabbing a reference to the current SideMenu object we gain access to its methods, allowing us
	 * to add a menu link to the newly created /plugin/PostScheduler method.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function Base_GetAppSettingsMenuItems_Handler($Sender) {
		$Menu = $Sender->EventArguments['SideMenu'];
		$Menu->AddLink('Add-ons', $this->GetPluginKey('Name'), 'plugin/postscheduler', 'Garden.AdminUser.Only');
	}

	/**
	 * Add Controller to display Scheduled Discussions.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function DiscussionsController_Scheduled_Create($Sender) {
		// Replace standard View with the "Scheduled Discussions" view
		$this->ShowScheduledDiscussions($Sender, GetValue(0, $Args, 'p1'));
	}

	/**
	 * Vanilla 2.0 Event Handler.
	 * Adds a "Scheduled" link to tabs in Index page.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function Base_AfterDiscussionTabs_Handler($Sender) {
		// If User is not allowed to schedule Discussions, he cannot have a list of
		// them, therefore the menu item doesn't need to be displayed
		if(!Gdn::Session()->CheckPermission('Plugins.PostScheduler.ScheduleDiscussions')) {
			return;
		}

		// TODO Review code to display a Sprite in Vanilla 2.1
		$CssClass = $Sender->RequestMethod == 'scheduled' ? 'Active' : '';
		echo Wrap(Anchor(T('Mine (Scheduled)'),
										 '/discussions/scheduled',
										 'TabLink'),
							'li',
							array('class' => 'MyScheduledDiscussions ' . $CssClass)
						 );
	}

	/**
	 * Vanilla 2.1 Event Handler.
	 * Calls PostSchedulerPlugin::Base_AfterDiscussionTabs_Handler().
	 *
	 * @see PostSchedulerPlugin::Base_AfterDiscussionTabs_Handler().
	 */
	public function DiscussionsController_AfterDiscussionFilters_Handler($Sender) {
		return $this->Base_AfterDiscussionTabs_Handler($Sender);
	}

	/**
	 * Vanilla 2.1 Event Handler.
	 * Calls PostSchedulerPlugin::Base_AfterDiscussionTabs_Handler().
	 *
	 * @see PostSchedulerPlugin::Base_AfterDiscussionTabs_Handler().
	 */
	public function DiscussionController_AfterDiscussionFilters_Handler($Sender) {
		return $this->Base_AfterDiscussionTabs_Handler($Sender);
	}

	/**
	 * Alters the SQL of a DiscussionModel to only show the Discussions scheduled
	 * by current User and still not displayed.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	private function ShowUserScheduledDiscussions($Sender) {
		$Now = gmdate('Y-m-d H:i:s');
		$Sender->SQL
			->BeginWhereGroup()
			->Where('d.InsertUserID', Gdn::Session()->UserID)
			->Where('d.Scheduled', self::SCHEDULED_YES)
			->Where('d.ScheduleTime >', $Now)
			->EndWhereGroup();
	}

	/**
	 * Alters the SQL of a DiscussionModel to hide the Discussions that are
	 * scheduled by other Users, and not yet due to be displayed.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	private function FilterScheduledDiscussions($Sender) {
		$Now = gmdate('Y-m-d H:i:s');
		$Sender->SQL
			->BeginWhereGroup()
			->Where('d.InsertUserID', Gdn::Session()->UserID)
			->OrWhere('d.Scheduled', null)
			->OrWhere('d.Scheduled', 0)
			->OrWhere('d.ScheduleTime <=', $Now)
			->EndWhereGroup();
	}

	/**
	 * Converts a Date/Time string into the corresponding	UTC Date/Time, based on
	 * the time difference between the two. All date/times are expected and
	 * returned in YYYY-MM-DD HH:MM:SS format.
	 *
	 * @param string DateTime An ISO Formatted date/time string.
	 * @param float HourOffset A number indicating the time difference between
	 * the date/time passed in time and Server time, in hours.
	 * @return string An ISO Formatted date/time string in UTC time zone.
	 */
	public static function DateTimeToUTCDateTime($DateTime, $HourOffset) {
		// Calculate User's time difference, in seconds
		$TimeOffset = $HourOffset * 3600;

		/* Subtract User's time offset to calculate the correspondant time in Server
		 * time zone.
		 */
		$ServerTimeStamp = Gdn_Format::ToTimestamp($DateTime) - $TimeOffset;
		// Convert Server Timestamp into a UTC Date/Time
		$UTCDateTime = gmdate('Y-m-d H:i:s', $ServerTimeStamp);

		return $UTCDateTime;
	}

	/**
	 * Converts a UTC Date/Time string into a Server TimeZone Date/Time. All
	 * date/times are expected and returned in YYYY-MM-DD HH:MM:SS format.
	 *
	 * @param string DateTime An ISO Formatted date/time string in UTC zone.
	 * @param string TimeZone The destination Time Zone to convert date/time.
	 * @return string An ISO Formatted date/time string in Server time zone.
	 */
	public static function UTCDateTimeToLocalDateTime($DateTime, $TimeZone = null) {
		// Assume default time zone, if none has been specified
		if(empty($TimeZone)) {
			$TimeZone = date_default_timezone_get();
		}

		// Instantiate a new DateTime object with original UTC Date/Time and convert
		// it to the destination Time Zone
		$LocalDateTime = new DateTime($DateTime, new DateTimeZone('UTC'));
		$LocalDateTime->setTimezone(new DateTimeZone($TimeZone));

		// Format the date/time in ISO format
		$Result = $LocalDateTime->format('Y-m-d H:i:s');

		return $Result;
	}

	/**
	 * Vanilla 2.0 Event Handler.
	 * Displays schedule information for scheduled discussions.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function DiscussionsController_DiscussionMeta_Handler($Sender) {
		$Discussion = &$Sender->EventArguments['Discussion'];

		$Now = gmdate('Y-m-d H:i:s');

		if($Discussion->Scheduled == self::SCHEDULED_YES &&
			 $Discussion->ScheduleTime > $Now) {
			echo Wrap(sprintf(T('Discussion will be displayed on %s.'),
												Gdn_Format::Date(self::UTCDateTimeToLocalDateTime($Discussion->ScheduleTime),
																				 T('Date.DefaultDateTimeFormat'))),
								'div',
								array('class' => 'PostInfo ScheduleTime'));

			//var_dump($Discussion->ScheduleTime);
		}
	}

	/**
	 * Vanilla 2.0 Event Handler.
	 * Displays schedule information for scheduled discussions.
	 *
	 * @param Controller Sender Sending controller instance.
	 * @see PostScheduler::DiscussionsController_DiscussionMeta_Handler().
	 */
	public function CategoriesController_DiscussionMeta_Handler($Sender) {
		$this->DiscussionsController_DiscussionMeta_Handler($Sender);
	}

	/**
	 * Vanilla 2.1 Event Handler.
	 * Calls PostSchedulerPlugin::DiscussionsController_DiscussionMeta_Handler().
	 *
	 * @see PostSchedulerPlugin::DiscussionsController_DiscussionMeta_Handler().
	 */
	public function DiscussionController_AfterDiscussionBody_Handler($Sender) {
		return $this->DiscussionsController_DiscussionMeta_Handler($Sender);
	}

	/**
	 * Vanilla 2.0 Event Handler.
	 * Calls PostSchedulerPlugin::DiscussionsController_DiscussionMeta_Handler().
	 *
	 * @see PostSchedulerPlugin::DiscussionsController_DiscussionMeta_Handler().
	 */
	public function DiscussionController_AfterCommentBody_Handler($Sender) {
		return $this->DiscussionsController_DiscussionMeta_Handler($Sender);
	}

	/**
	 * Handler of Event DiscussionModel::BeforeGet.
	 * Alter SQL of Discussions Model to only retrieve Scheduled discussions.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function DiscussionModel_BeforeGet_Handler($Sender) {
		// Handle requests to displaying the list of User's Scheduled Discussions
		if(Gdn::Controller()->RequestMethod == 'scheduled') {
			$this->ShowUserScheduledDiscussions($Sender);
			return;
		}

		// If User is authorised to see all the Discussions (scheduled or not), just
		// return, no filtering needed
		if(Gdn::Session()->CheckPermission('Plugins.PostScheduler.ViewAllScheduled')){
			return;
		}
		// Filter normal Discussions list by hiding the scheduled Discussions
		$this->FilterScheduledDiscussions($Sender);
	}

	/**
	 * Replaces the value of FirstDate field with the ScheduleDate for discussions
	 * that were scheduled and should now be displayed.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function DiscussionModel_AfterAddColumns_Handler($Sender) {
		$DiscussionData = &$Sender->EventArguments['Data'];

		$Now = gmdate('Y-m-d H:i:s');

		foreach($DiscussionData as $Discussion) {
			if($Discussion->Scheduled == self::SCHEDULED_YES &&
				 $Discussion->ScheduleTime <= $Now) {
				$Discussion->FirstDate = $Discussion->ScheduleTime;
			}
		}
	}

	/**
	 * Handler of Event PostController::DiscussionFormOptions.
	 * If User is authorised to schedule discussions, adds the HTML controls
	 * required to do it to the Discussion Add/Edit page.
	 *
 	 * @param Controller Sender Sending controller instance.
 	 */
  public function PostController_DiscussionFormOptions_Handler($Sender) {
		if(Gdn::Session()->CheckPermission('Plugins.PostScheduler.ScheduleDiscussions')) {
			$Sender->EventArguments['Options'] .= $this->GetSchedulerControls($Sender);
		}
  }

	/**
	 * Loads the CSS and JavaScript files required by the plugin.
	 */
	public function PostController_BeforeFormInputs_Handler($Sender) {
		// If User is not authorised to schedule a discussion, there's no need to load
		// the files for the UI
		if(!Gdn::Session()->CheckPermission('Plugins.PostScheduler.ScheduleDiscussions')) {
			return;
		}

		//$Sender->AddJsFile('jquery.ui.packed.js');
		$UITheme = C('Plugin.PostScheduler.UITheme', self::DEFAULT_UI_THEME);

		$Sender->AddCssFile('jquery-ui-1.10.0.custom.min.css', 'plugins/PostScheduler/design/jquery-ui/' . $UITheme);

		$Sender->RemoveJsFile('jquery-ui-1.8.17.custom.min.js');
		// Load jQuery UI from Google CDN, for faster delivery
		$Sender->AddJsFile('http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.0/jquery-ui.min.js', '');
		$Sender->AddJsFile('jquery-ui-timepicker-addon.js', 'plugins/PostScheduler/js');
		$Sender->AddJsFile('postscheduler.js', 'plugins/PostScheduler/js');

		//var_dump($Sender);die();
	}

	/**
	 * Adds validation rules for the scheduling of Discussions.
	 *
	 * @param Gdn_Validation Validation The Validation object associated with a
	 * DiscussionModel instance.
	 */
	private function SetDiscussionValidation(Gdn_Validation $Validation) {
		$this->Log->trace('Setting Validation Rules for Scheduled Discussion...');

		$Validation->AddRule('UserAuthorisedToSchedulePost', 'function:UserAuthorisedToSchedulePost');
		$Validation->AddRule('CheckNoReplies', 'function:CheckNoReplies');
		$Validation->AddRule('ValidateScheduleTime', 'function:ValidateScheduleTime');

		$Validation->ApplyRule('Scheduled',
													 'UserAuthorisedToSchedulePost',
													 T('You are not authorised to schedule a Discussion.'));
		$Validation->ApplyRule('ScheduleTime',
													 'CheckNoReplies',
													 T('Discussion cannot be scheduled because it already received replies.'));
		$Validation->ApplyRule('ScheduleTime',
													 'ValidateScheduleTime',
													 T('Schedule Time is not a valid date/time.'));
	}

	/**
	 * Handler of Event DiscussionModel::BeforeSaveDiscussion.
	 * Adds validation related to Post scheduling.
	 *
 	 * @param Controller Sender Sending controller instance.
 	 */
	public function DiscussionModel_BeforeSaveDiscussion_Handler($Sender) {
		$FormPostValues = &$Sender->EventArguments['FormPostValues'];
		//var_dump($FormPostValues);die();

		// If User is trying to schedule a discussion, add the related validation rules
		if($FormPostValues['Scheduled'] == PostSchedulerPlugin::SCHEDULED_YES) {
			$this->SetDiscussionValidation($Sender->Validation);
		}

		//var_dump($FormPostValues['ScheduleTime']);
		$FormPostValues['ScheduleTime'] = self::DateTimeToUTCDateTime($FormPostValues['ScheduleTime'],
																																	Gdn::Session()->User->HourOffset);
		//var_dump($FormPostValues['ScheduleTime']);
		//die();
	}

	/**
	 * Handler of Event DiscussionsController::BeforeDiscussionName.
	 * Adds CSS classes to Discussion entries, to highlight the scheduled ones.
	 *
 	 * @param Controller Sender Sending controller instance.
 	 */
	public function DiscussionsController_BeforeDiscussionName_Handler($Sender) {
		$Discussion = $Sender->EventArguments['Discussion'];

		if($Discussion->Scheduled == self::SCHEDULED_YES) {
			$Sender->EventArguments['CssClass'] .= ' Scheduled';
		}

		$Now = gmdate('Y-m-d H:i:s');
		if($Discussion->ScheduleTime > $Now) {
			$Sender->EventArguments['CssClass'] .= ' NotYetVisible';
		}
	}

	/**
	 * Handler of Event CategoriesController::BeforeDiscussionName.
	 * Adds CSS classes to Discussion entries, to highlight the scheduled ones.
	 *
 	 * @param Controller Sender Sending controller instance.
 	 * @see PostScheduler::DiscussionsController_BeforeDiscussionName_Handler().
 	 */
	public function CategoriesController_BeforeDiscussionName_Handler($Sender) {
		$this->DiscussionsController_BeforeDiscussionName_Handler($Sender);
	}

	/**
	 * Render the controls used to schedule a Post.
	 *
 	 * @param Controller Sender Sending controller instance.
	 */
	protected function GetSchedulerControls($Sender, $Wrap = FALSE) {
		$View = $Sender->FetchView('postscheduler_controls_view', '', 'plugins/PostScheduler');
		if($Wrap) {
			return Wrap($View, 'div', array('class' => 'P'));
		}
		else {
			return $View;
		}
	}

	private function GetDiscussionByRoute($Route) {
		if(empty($Route)) {
			return null;
		}

		// Check if we have the discussion already stored, to avoid fetching it again
		$Discussion = GetValue($Route, $this->_DiscussionsByRoute);
		if(!empty($Discussion)) {
			return $Discussion;
		}

		// Check if the Route is related to a Discussion. If not, we don't need to
		// do anything.
		$RegExMatches = array();
		if(preg_match('/^\/discussion\/([0-9]+?)\//i', $Route, $RegExMatches) != 1) {
			return null;
		}

		// Return the Discussion ID, returned by capturing it in a RegEx group
		$DiscussionID = GetValue(1, $RegExMatches, -1);

		// Without a Discussion Id, there's no point in proceeding
		if($DiscussionID <= 0) {
			return null;
		}

		// Retrieve the Discussion details and store them before returning them
		$DiscussionModel = new DiscussionModel();
		$this->_DiscussionsByRoute[$Route] = $DiscussionModel->GetID($DiscussionID);

		return $this->_DiscussionsByRoute[$Route];
	}

	public function ActivityModel_BeforeActivityInsert_Handler($Sender) {
		// We need the Route to find out if an Activity is linked to a Discussion.
		// This is because Activity entries have no link to other entities, and the
		// Route is the only one we can use
		$Route = GetValue('Route', $Sender->EventArguments['Fields']);

		$Discussion = $this->GetDiscussionByRoute($Route);

		// No need to do anything if the Discussion is not existing
		if(empty($Discussion)) {
			return;
		}

		// Pass the Schedule flag and Time to the Activity model, which will save
		// them to the database
		$Sender->EventArguments['Fields']['Scheduled'] = $Discussion->Scheduled;
		$Sender->EventArguments['Fields']['ScheduleTime'] = $Discussion->ScheduleTime;
		// Add the DiscussionID to the Activity, as it will be used to reschedule
		// the Notification whenever the Discussion will be updated
		$Sender->EventArguments['Fields']['DiscussionID'] = $Discussion->DiscussionID;

		/* NotificationSent allows to identify Activities that have already been sent
		 * to Users. If a Discussion is scheduled in the future, then the notification
		 * won't be sent straight away, therefore Sent field wil be set to zero. If,
		 * instead, a Discussion is NOT scheduled, or scheduled in the past (which
		 * doesn't make much sense, but it can happen), the related Activity will
		 * be sent immediately. The Sent flag is, therefore, set to 1.
		 */
		$NotificationSent =
			$Discussion->Scheduled == self::SCHEDULED_YES &&
			$Discussion->ScheduleTime > gmdate('Y-m-d H:i:s') ? self::SENT_NO : self::SENT_YES;
		$Sender->EventArguments['Fields']['NotificationSent'] = $NotificationSent;
	}

	/**
	 * Alters the SQL of a ActivityModel to hide the Activities that are
	 * scheduled to be sent at a later time. This will prevent them from being
	 * sent immediately when a Discussion is created.
	 *
	 * @param Controller Sender Sending controller instance.
	 */
	public function ActivityModel_AfterActivityQuery_Handler($Sender) {
		$Now = gmdate('Y-m-d H:i:s');
		$Sender->SQL
			->Join('Discussion d', 'd.DiscussionID = a.DiscussionID', 'inner')
			->BeginWhereGroup()
			->Where('d.Scheduled', null)
			->OrWhere('d.Scheduled', 0)
			->OrWhere('d.ScheduleTime <=', $Now)
			->EndWhereGroup();
	}

	/**
	 * Retrieves all the pending scheduled notifications which are due to be sent
	 * and sends them to the recipients.
	 *
	 * @return bool True, if all the notifications were sent successfully, False otherwise.
	 */
	protected function SendScheduledNotifications() {
		$this->Log->Info(T('Sending scheduled notifications...'));
		$ActivityModel = new ActivityModel();

		// Retrieve all the Notifications scheduled, due to be sent and not yet sent
		// The filters on Schedule flag and Time are added by
		// PostSchedulerPlugin::ActivityModel_AfterActivityQuery_Handler() method.
		$ActivityNotificationsToSend = $ActivityModel->GetWhere('a.NotificationSent', 0)->Result();

		try {
			$ActivityModel->Database->BeginTransaction();
			foreach($ActivityNotificationsToSend as $Activity) {
				// Queue each Activity Notification for sending and update the
				// NotificationSent flag to indicate that the entry has been processed
				$ActivityModel->QueueNotification($Activity->ActivityID);
				$ActivityModel->Update(array('NotificationSent' => self::SENT_YES),
															 array('ActivityID' => $Activity->ActivityID));
			}
			// Send all queued Notifications
			$ActivityModel->SendNotificationQueue();
		}
		catch(Exception $e) {
			$this->Log->error($ErrMsg = sprintf(T('Error occurred while sending scheduled Notifications. Error message: %s'),
																					$e->getMessage()));

			$ActivityModel->Database->RollbackTransaction();
			return false;
		}

		$ActivityModel->Database->CommitTransaction();
		$this->Log->Info(sprintf(T('%d scheduled notifications sent successfully. Operation completed.'),
														 count($ActivityNotificationsToSend)));
		return true;
	}

	/**
	 * Plugin setup
	 *
	 * This method is fired only once, immediately after the plugin has been enabled in the /plugins/ screen,
	 * and is a great place to perform one-time setup tasks, such as database structure changes,
	 * addition/modification ofconfig file settings, filesystem changes, etc.
	 */
	public function Setup() {
		// Set up the plugin's default values

		// Create Database Objects needed by the Plugin
		require('install/postscheduler.schema.php');
		PostSchedulerSchema::Install();
	}

	/**
	 * Cleanup operations to be performend when the Plugin is disabled, but not
	 * permanently removed.
	 */
	public function OnDisable() {
	}

	/**
	* Plugin cleanup
	*
	* This method is fired only once, when the plugin is removed, and is a great place to
	* perform cleanup tasks such as deletion of unsued files and folders.
	*/
	public function CleanUp() {
		// Drop Database Objects created by the Plugin
		require('install/postscheduler.schema.php');
		PostSchedulerSchema::Uninstall();
	}

	/**
	 * Renders the Scheduled Discussions page.
	 * This method is an almost exact copy of DiscussionController::Index(), with
	 * the exception that it doesn't load Announcements separately. Announcements
	 * are retrieved as any other Discussion created by the User.
	 *
 	 * @param Controller Sender Sending controller instance.
	 * @param int Page The page to display (used by Pager).
	 */
	private function ShowScheduledDiscussions($Sender, $Page = '0') {
		// Determine offset from $Page
		list($Page, $Limit) = OffsetLimit($Page, C('Vanilla.Discussions.PerPage', 30));
		$Sender->CanonicalUrl(Url(ConcatSep('/', 'discussions', PageNumber($Page, $Limit, TRUE, FALSE)), TRUE));

		// Validate $Page
		if (!is_numeric($Page) || $Page < 0) {
			$Page = 0;
		}

		// Setup head.
		if (!$Sender->Data('Title')) {
			$Sender->Title(T('Scheduled Posts'));
		}

		// Add modules
		$Sender->AddModule('NewDiscussionModule');
		$Sender->AddModule('CategoriesModule');
		$Sender->AddModule('BookmarkedModule');

		// Set criteria & get discussions data
		$Sender->SetData('Category', FALSE, TRUE);
		$DiscussionModel = new DiscussionModel();
		$DiscussionModel->Watching = TRUE;

		// Get Discussions
		// To get only Scheduled Posts, additional WHERE clauses are required. These
		// are added via an Event Handler, as DiscussionModel doesn't provide a way
		// to do it.
		$Sender->DiscussionData = $DiscussionModel->Get($Page, $Limit, array('Announce' => 'all'));

		// Get Discussion Count
		$CountDiscussions = $Sender->DiscussionData->NumRows();
		$Sender->SetData('CountDiscussions', $CountDiscussions);

//		var_dump($Sender->DiscussionData);
//		die();

		$Sender->SetData('Discussions', $Sender->DiscussionData, TRUE);
		$Sender->SetJson('Loading', $Page . ' to ' . $Limit);

		// Build a pager
		$PagerFactory = new Gdn_PagerFactory();
		$Sender->EventArguments['PagerType'] = 'Pager';
		$Sender->FireEvent('BeforeBuildPager');
		$Sender->Pager = $PagerFactory->GetPager($Sender->EventArguments['PagerType'], $this);
		$Sender->Pager->ClientID = 'Pager';
		$Sender->Pager->Configure(
			$Page,
			$Limit,
			$CountDiscussions,
			'discussions/%1$s'
		);
		$Sender->SetData('_PagerUrl', 'discussions/scheduled/{Page}');
		$Sender->SetData('_Page', $Page);
		$Sender->SetData('_Limit', $Limit);
		$Sender->FireEvent('AfterBuildPager');

		// Deliver JSON data if necessary
		if($Sender->DeliveryType() != DELIVERY_TYPE_ALL) {
			$Sender->SetJson('LessRow', $Sender->Pager->ToString('less'));
			$Sender->SetJson('MoreRow', $Sender->Pager->ToString('more'));
			$Sender->View = 'discussions';
		}

		// Set a definition of the user's current timezone from the db. jQuery
		// will pick this up, compare to the browser, and update the user's
		// timezone if necessary.
		$CurrentUser = Gdn::Session()->User;
		if (is_object($CurrentUser)) {
			$ClientHour = $CurrentUser->HourOffset + date('G', time());
			$Sender->AddDefinition('SetClientHour', $ClientHour);
		}

		// Render default view (discussions/index.php)
		$Sender->View = 'index';
		$Sender->Render();
	}

	/*** Cron Methods ***/

	/**
	 * Implements Cron method, which will be run automaticall by Cron Plugin.
	 */
	public function Cron() {
		// Retrieve and send all scheduled notifications
		$this->SendScheduledNotifications();
	}

	/**
	 * Register plugin for Cron Jobs.
	 */
	public function CronJobsPlugin_CronJobRegister_Handler($Sender){
		$Sender->RegisterCronJob($this);
	}
}
