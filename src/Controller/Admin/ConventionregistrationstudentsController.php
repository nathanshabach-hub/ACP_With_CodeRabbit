<?php

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;

#[\AllowDynamicProperties]
class ConventionregistrationstudentsController extends AppController {

    public array $paginate = ['limit' => 50, 'order' => ['Events.name' => 'asc']];

    //public $helpers = array('Javascript', 'Ajax');

    public function initialize(): void {
        parent::initialize();
		$this->loadComponent('RequestHandler');
		$this->loadComponent('PImage');
		$this->loadComponent('PImageTest');
        $this->loadComponent('Paginator');
        $this->loadComponent('Flash');
        $action = $this->request->getParam('action');
        $loggedAdminId = $this->request->getSession()->read('admin_id');
        if ($action != 'forgotPassword' && $action != 'logout') {
            if (!$loggedAdminId && $action != "login" && $action != 'captcha') {
                $this->redirect(['controller' => 'admins', 'action' => 'login']);
            }
        }
		
		$this->loadModel('Divisions');
		$this->loadModel('Books');
		$this->loadModel("Events");
		$this->loadModel("Users");
		$this->loadModel("Conventionregistrations");
		$this->loadModel("Conventionseasons");
		$this->loadModel("Conventionregistrationstudents");
		$this->loadModel("Eventsubmissions");
		$this->loadModel("Crstudentevents");
    }
	
	public function allstudents() {

        $this->set('title', ADMIN_TITLE . 'Convention Registrations Students');
        $this->viewBuilder()->setLayout('admin');
        $this->set('dashboard', '1');
		
        $condition = array();
		
		$sess_admin_header_season_id = $this->request->getSession()->read("sess_admin_header_season_id");
		$convSeasonQuery = $this->Conventionseasons->find();
		if (!empty($sess_admin_header_season_id)) {
			$convSeasonQuery->where(['Conventionseasons.id' => $sess_admin_header_season_id]);
		}
		$convSeasonD = $convSeasonQuery->order(['Conventionseasons.id' => 'DESC'])->first();
		if (!$convSeasonD) {
			$this->Flash->error('No convention season is available.');
			return;
		}
		
		$condition[] = "(Conventionregistrationstudents.convention_id = '".$convSeasonD->convention_id."' AND Conventionregistrationstudents.season_id = '".$convSeasonD->season_id."' AND Conventionregistrationstudents.season_year = '".$convSeasonD->season_year."')";
		
		$conventionregistrationstudents = $this->Conventionregistrationstudents->find()
			->contain(['Users','Students'])
			->where($condition)
			->order(["Conventionregistrationstudents.id" => "DESC"])
			->all();

		// Deduplicate: keep only the latest registration per student (highest id comes first)
		$seen = [];
		$unique = [];
		foreach ($conventionregistrationstudents as $record) {
			if (!in_array($record->student_id, $seen)) {
				$seen[] = $record->student_id;
				$unique[] = $record;
			}
		}
		$this->set('conventionregistrationstudents', new \Cake\Collection\Collection($unique));
    }
	
	public function studentevents($conv_reg_student_slug = NULL) {

        $this->set('title', ADMIN_TITLE . 'Convention Registrations Student Events');
        $this->viewBuilder()->setLayout('admin');
        $this->set('dashboard', '1');
		
        $condition = array();
		$condition[] = "(Conventionregistrationstudents.slug = '".$conv_reg_student_slug."')";
		
		$sess_admin_header_season_id = $this->request->getSession()->read("sess_admin_header_season_id");
		$convSeasonQuery = $this->Conventionseasons->find();
		if (!empty($sess_admin_header_season_id)) {
			$convSeasonQuery->where(['Conventionseasons.id' => $sess_admin_header_season_id]);
		}
		$convSeasonD = $convSeasonQuery->order(['Conventionseasons.id' => 'DESC'])->first();
		if (!$convSeasonD) {
			$this->Flash->error('No convention season is available.');
			return $this->redirect(['controller' => 'conventionregistrationstudents', 'action' => 'allstudents']);
		}
		
		$condition[] = "(Conventionregistrationstudents.convention_id = '".$convSeasonD->convention_id."' AND Conventionregistrationstudents.season_id = '".$convSeasonD->season_id."' AND Conventionregistrationstudents.season_year = '".$convSeasonD->season_year."')";
		
		$convRegStudentD = $this->Conventionregistrationstudents->find()->contain(['Users','Students'])->where($condition)->first();
		if (empty($convRegStudentD)) {
			$this->Flash->error('Student registration not found for this convention season.');
			return $this->redirect(['controller' => 'conventionregistrationstudents', 'action' => 'allstudents']);
		}
		$this->set('convRegStudentD', $convRegStudentD);
		//$this->prx($convRegStudentD);

		if ($this->request->is('post')) {
			$selectedEventSlugs = (array)$this->request->getData('selected_event_slugs');
			$selectedEventSlugs = array_values(array_unique(array_filter(array_map('trim', $selectedEventSlugs))));

			if (empty($selectedEventSlugs)) {
				$this->Flash->error('Please select at least one event to remove.');
				return $this->redirect(['controller' => 'conventionregistrationstudents', 'action' => 'studentevents', $conv_reg_student_slug]);
			}

			$removedCount = 0;
			foreach ($selectedEventSlugs as $eventSlug) {
				$eventD = $this->Events->find()->where(['slug' => $eventSlug])->first();
				if ($eventD && $this->removeStudentEventFromRegistration($convRegStudentD, $eventD, $sess_admin_header_season_id)) {
					$removedCount++;
				}
			}

			if ($removedCount > 0) {
				$this->Flash->success($removedCount . ' event(s) successfully removed for this student.');
			} else {
				$this->Flash->error('No selected events were removed.');
			}

			if (empty($convRegStudentD->event_ids)) {
				return $this->redirect(['controller' => 'conventionregistrationstudents', 'action' => 'allstudents']);
			}

			return $this->redirect(['controller' => 'conventionregistrationstudents', 'action' => 'studentevents', $conv_reg_student_slug]);
		}
		
		if(empty($convRegStudentD->event_ids) || $convRegStudentD->event_ids == NULL)
		{
			$this->Flash->error('No event found for this student.');
			$this->redirect(['controller' => 'conventionregistrationstudents', 'action' => 'allstudents']);
		}
		else
		{
			$event_ids_student 	= $convRegStudentD->event_ids;
			
			$condEvents = array();
			$condEvents[] = "(Events.id IN  ($event_ids_student))";
			
			$eventsList = $this->Events->find()->where($condEvents)->order(["Events.event_name" => "ASC"])->all();
			//$this->prx($eventsList);
			$this->set('eventsList', $eventsList);
		}
    }

    public function removestudentevent($conv_reg_student_slug=NULL, $event_slug = NULL) {
		
		$sess_admin_header_season_id = $this->request->getSession()->read("sess_admin_header_season_id");
		
		$convRegStudentD = $this->Conventionregistrationstudents->find()->where(["slug" => $conv_reg_student_slug])->first();
		
		$eventD = $this->Events->find()->where(["slug" => $event_slug])->first();
		
		if($convRegStudentD && $eventD && $this->removeStudentEventFromRegistration($convRegStudentD, $eventD, $sess_admin_header_season_id))
		{
			$this->Flash->success('Event successfully removed for this student.');
		}

		if (empty($convRegStudentD) || empty($convRegStudentD->event_ids)) {
			return $this->redirect(['controller' => 'conventionregistrationstudents', 'action' => 'allstudents']);
		}
		
		$this->redirect(['controller' => 'conventionregistrationstudents', 'action' => 'studentevents',$conv_reg_student_slug]);
		
	}

	protected function removeStudentEventFromRegistration($convRegStudentD, $eventD, $sessAdminHeaderSeasonId) {
		if (empty($convRegStudentD) || empty($eventD) || empty($convRegStudentD->event_ids)) {
			return false;
		}

		$studentEventIds = array_values(array_filter(array_map('trim', explode(',', (string)$convRegStudentD->event_ids)), 'strlen'));
		$keyE = array_search((string)$eventD->id, $studentEventIds, true);
		if ($keyE === false) {
			return false;
		}

		unset($studentEventIds[$keyE]);
		$eventImplode = count($studentEventIds) ? implode(',', $studentEventIds) : null;

		$condCRSTE = [
			'conventionregistration_id' => $convRegStudentD->conventionregistration_id,
			'user_id' => $convRegStudentD->user_id,
			'student_id' => $convRegStudentD->student_id,
			'event_id' => $eventD->id,
			'event_id_number' => $eventD->event_id_number,
		];
		$this->Crstudentevents->deleteAll($condCRSTE);

		$condES = [
			'convention_id' => $convRegStudentD->convention_id,
			'season_id' => $convRegStudentD->season_id,
			'season_year' => $convRegStudentD->season_year,
			'conventionregistration_id' => $convRegStudentD->conventionregistration_id,
			'user_id' => $convRegStudentD->user_id,
			'student_id' => $convRegStudentD->student_id,
			'event_id' => $eventD->id,
			'event_id_number' => $eventD->event_id_number,
		];

		$eventSubmissionList = $this->Eventsubmissions->find()->where($condES)->all();
		foreach ($eventSubmissionList as $eventSubmissionD) {
			if(!empty($eventSubmissionD->mediafile_file_system_name) && file_exists(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$eventSubmissionD->mediafile_file_system_name))
			{
				@unlink(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$eventSubmissionD->mediafile_file_system_name);
			}

			if(!empty($eventSubmissionD->report) && file_exists(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$eventSubmissionD->report))
			{
				@unlink(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$eventSubmissionD->report);
			}

			if(!empty($eventSubmissionD->score_sheet) && file_exists(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$eventSubmissionD->score_sheet))
			{
				@unlink(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$eventSubmissionD->score_sheet);
			}

			if(!empty($eventSubmissionD->additional_documents) && file_exists(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$eventSubmissionD->additional_documents))
			{
				@unlink(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$eventSubmissionD->additional_documents);
			}
		}

		$this->Eventsubmissions->deleteAll($condES);

		if ($eventImplode === null) {
			$this->Conventionregistrationstudents->deleteAll(['id' => $convRegStudentD->id]);
			$convRegStudentD->event_ids = null;
			return true;
		}

		$this->Conventionregistrationstudents->updateAll(['event_ids' => $eventImplode], ["id" => $convRegStudentD->id]);
		$convRegStudentD->event_ids = $eventImplode;

		return true;
	}
		
		
		
    public function events($conv_reg_slug=NULL) {

        $this->set('title', ADMIN_TITLE . 'Manage Events Registered');
        $this->viewBuilder()->setLayout('admin');
        $this->set('manageRegistrations', '1');
        $this->set('registrationsList', '1');
		
		$separator = array();
        $condition = array();
		
		if($conv_reg_slug)
		{
			$CRDetails = $this->Conventionregistrations->find()->where(['Conventionregistrations.slug' => $conv_reg_slug])->contain(['Conventions','Users'])->first();
			$this->set('CRDetails', $CRDetails);
			
			$this->set('conv_reg_slug', $conv_reg_slug);
			
			$condition = array('Conventionregistrationstudents.conventionregistration_id' => $CRDetails->id);
			
			$crstudents = $this->Conventionregistrationstudents->find()->where($condition)->contain(['Users'])->all();
			$this->set('crstudents', $crstudents);
			
			$allEventsArr = array();
			
			// now extract all events from students list
			foreach($crstudents as $crstudent)
			{
				$event_ids = $crstudent->event_ids;
				if(!empty($event_ids))
				{
					$event_ids_explode = explode(",",$event_ids);
					foreach($event_ids_explode as $event_id)
					{
						if(!in_array($event_id,(array)$allEventsArr))
						{
							$allEventsArr[] = $event_id;
						}
					}
				}
			}
			
			//$this->prx($allEventsArr);
			
			$this->set('allEventsArr', $allEventsArr);
		}
		
		
		
		//$this->prx($crstudents);
		
    }
	
	public function scriptureawardpdf($slug_convention_season = null, $school_slug = null, $event_slug = null) {
		
		//$this->helpers[] = 'Pdf';
		
		$this->viewBuilder()->disableAutoLayout();
		
		// to get convention season details
		if ($slug_convention_season)
		{
            $conventionSD 			= $this->Conventionseasons->find()->where(['Conventionseasons.slug' => $slug_convention_season])->first();
			if (!$conventionSD) {
				return $this->response->withStringBody('Convention season not found.');
			}
			$this->set('conventionSD', $conventionSD);
        }
		else
		{
			return $this->response->withStringBody('Convention season not found.');
		}
		
		// to get school details
		if ($school_slug)
		{
            $schoolD 			= $this->Users->find()->where(['Users.slug' => $school_slug])->first();
			if (!$schoolD) {
				return $this->response->withStringBody('School not found.');
			}
			$this->set('schoolD', $schoolD);
        }
		else
		{
			return $this->response->withStringBody('School not found.');
		}
		
		// to get event details
		if ($event_slug)
		{
            $eventD 			= $this->Events->find()->where(['Events.slug' => $event_slug])->first();
			if (!$eventD) {
				return $this->response->withStringBody('Event not found.');
			}
			$event_id 			= $eventD->id;
			$this->set('eventD', $eventD);
        }
		else
		{
			return $this->response->withStringBody('Event not found.');
		}
		
		// now fetch all students who match with this event
		$condConvRegStudents = array();
		$condConvRegStudents[] = "(Conventionregistrationstudents.convention_id = '".$conventionSD->convention_id."' AND Conventionregistrationstudents.user_id = '".$schoolD->id."' AND Conventionregistrationstudents.season_id = '".$conventionSD->season_id."' AND Conventionregistrationstudents.season_year = '".$conventionSD->season_year."')";
		$condConvRegStudents[] = "(Conventionregistrationstudents.event_ids LIKE '".$event_id."' OR Conventionregistrationstudents.event_ids LIKE '".$event_id.",%' OR Conventionregistrationstudents.event_ids LIKE '%,".$event_id.",%' OR Conventionregistrationstudents.event_ids LIKE '%,".$event_id."')";
		
		$conventionregistrationstudents = $this->Conventionregistrationstudents->find()->where($condConvRegStudents)->contain(['Students','Users','Conventions'])->all();
		$this->set('conventionregistrationstudents', $conventionregistrationstudents);
		
		//$this->prx($conventionregistrationstudents);
		
		
		
		// to see if event is 1005 1055 Silver Apple Award, db id 336 and 342then grey certificate need to generate
		// .. otherwise yellow certificate
		//echo $studentEventSub->event_id;exit;
		
		// prepare an array for theme so that based on event id, theme will select
		$arrEventTheme = array();
		
		// for 1000 1050 Golden Apple Award
		$arrEventTheme['331']	= 	array(
										"header_image" => 'header_golden_apple_award.png',
										"footer_image" => 'footer_yellow.png',
										"border_color" => '#fbf0c2'
										);
		$arrEventTheme['337']	=	$arrEventTheme['331'];
		
		// for 1001 1051 Golden Lamb Award 
		$arrEventTheme['332']	= 	array(
										"header_image" => 'header_golden_lamp_award.png',
										"footer_image" => 'footer_yellow.png',
										"border_color" => '#fbf0c2'
										);
		$arrEventTheme['338']	=	$arrEventTheme['332'];
		
		// for 1002 1052 Golden Harp Award 
		$arrEventTheme['333']	= 	array(
										"header_image" => 'header_golden_harp_award.png',
										"footer_image" => 'footer_yellow.png',
										"border_color" => '#fbf0c2'
										);
		$arrEventTheme['339']	=	$arrEventTheme['333'];
		
		// for 1003 1053 Christian Soldier Award 
		$arrEventTheme['334']	= 	array(
										"header_image" => 'header_christian_soldier_award.png',
										"footer_image" => 'footer_yellow.png',
										"border_color" => '#fbf0c2'
										);
		$arrEventTheme['340']	=	$arrEventTheme['334'];
		
		// for 1004 1054 Christian Worker Award 
		$arrEventTheme['335']	= 	array(
										"header_image" => 'header_christian_worker_award.png',
										"footer_image" => 'footer_yellow.png',
										"border_color" => '#fbf0c2'
										);
		$arrEventTheme['341']	=	$arrEventTheme['335'];
		
		// for 1005 1055 Silver Apple Award
		$arrEventTheme['336']	= 	array(
										"header_image" => 'header_silver_apple_award.png',
										"footer_image" => 'footer_grey.png',
										"border_color" => '#d7d8da'
										);
		$arrEventTheme['342']	=	$arrEventTheme['336'];
		
		
		if (!isset($arrEventTheme[$event_id])) {
			return $this->response->withStringBody('Sorry, this event does not have a scripture award template.');
		}

		$certificateTheme	=	$arrEventTheme[$event_id];
		$this->set('certificateTheme', $certificateTheme);
		
		//$this->prx($certificateTheme);
		
		
		//$this->prx($arrCertData);
		
		ini_set('memory_limit', '512M');
        set_time_limit(0);
		
	}
}

?>
