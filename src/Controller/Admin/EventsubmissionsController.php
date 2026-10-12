<?php

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Datasource\ConnectionManager;

#[\AllowDynamicProperties]
class EventsubmissionsController extends AppController {

    public array $paginate = ['limit' => 50, 'order' => ['Conventions.name' => 'asc']];

    //public $helpers = array('Javascript', 'Ajax');

    public function initialize(): void {
        parent::initialize();
		$this->loadComponent('RequestHandler');
		$this->loadComponent('PImage');
		$this->loadComponent('PImageTest');
        $this->loadComponent('Paginator');
        $this->loadComponent('Flash');
        $action = $this->request->getParam('action');/*  */
        $loggedAdminId = $this->request->getSession()->read('admin_id');
        if ($action != 'forgotPassword' && $action != 'logout') {
            if (!$loggedAdminId && $action != "login" && $action != 'captcha') {
                $this->redirect(['controller' => 'admins', 'action' => 'login']);
            }
        }
		
		$this->loadModel('Conventionseasons');
		$this->loadModel('Seasons');
		$this->loadModel('Events');
		$this->loadModel('Conventionseasonevents');
		$this->loadModel('Conventionregistrations');
		$this->loadModel('Judgeevaluations');
		$this->loadModel('Combinerequests');
		$this->loadModel('Crstudentevents');
		$this->loadModel('Users');
    }
	
	public function index($slug=null) {

        $this->set('title', ADMIN_TITLE . 'Event submissions');
        $this->viewBuilder()->setLayout('admin');
        $this->set('manageRegistrations', '1');
        $this->set('registrationsList', '1');
		
		$separator = array();
        $condition = array();
		$this->set('slug', $slug);
		
		if($slug)
		{
			$CRDetails = $this->Conventionregistrations->find()->where(['Conventionregistrations.slug' => $slug])->contain(['Conventions'])->first();
			if (!$CRDetails) {
				$this->Flash->error('Invalid registration.');
				return $this->redirect(['controller' => 'eventsubmissions', 'action' => 'index']);
			}

			$this->set('CRDetails', $CRDetails);
			$condition = $this->getRegistrationSubmissionConditions($CRDetails);
		}
		else
		{
			// Keep the list bounded and scoped when browsing without a specific registration.
			$sess_admin_header_season_id = $this->request->getSession()->read("sess_admin_header_season_id");
			if($sess_admin_header_season_id>0)
			{
				$condition[] = "(Eventsubmissions.conventionseason_id = '".$sess_admin_header_season_id."')";
			}
		}
		
        //$this->prx($condition);exit;
        /* $separator = implode("/", $separator);
        $this->set('separator', $separator);
        $this->paginate = ['contain' => ['Conventions','Students','Events','Uploadeduser'], 'conditions' => $condition, 'limit' => 1000000000, 'order' => ['Eventsubmissions.id' => 'DESC']];
        $this->set('eventsubmissions', $this->paginate($this->Eventsubmissions));
        if ($this->request->is("ajax")) {
            $this->viewBuilder()->setLayout(($this->request->is("ajax")) ? "" : "default");
            $this->viewBuilder()->setTemplatePath('Element' . DS . 'Admin/Eventsubmissions');
            $this->render('index');
        } */
		
		$query = $this->Eventsubmissions->find()
			->where($condition)
			->contain(['Conventions','Students','Events','Uploadeduser'])
			->order(['Eventsubmissions.id' => 'DESC']);

		// Keep broad listing capped, but allow full results when a specific registration is requested.
		if (!$slug) {
			$query->limit(200);
		}

		$eventsubmissions = $query->all();

		$this->set('eventsubmissions', $eventsubmissions);
    }

	public function schoolsuploads() {

		$this->set('title', ADMIN_TITLE . 'Schools Uploads');
		$this->viewBuilder()->setLayout('admin');
		$this->set('manageRegistrations', '1');
		$this->set('registrationsList', '1');

		$sess_admin_header_season_id = (int)$this->request->getSession()->read('sess_admin_header_season_id');
		if ($sess_admin_header_season_id <= 0) {
			$this->Flash->error('Please choose a convention season from the header first.');
			return $this->redirect(['controller' => 'admins', 'action' => 'dashboard']);
		}

		$convSeasonD = $this->Conventionseasons->find()
			->where(['Conventionseasons.id' => $sess_admin_header_season_id])
			->contain(['Conventions'])
			->first();
		if (!$convSeasonD) {
			$this->Flash->error('Invalid convention season selected.');
			return $this->redirect(['controller' => 'admins', 'action' => 'dashboard']);
		}

		$eventsubmissions = $this->Eventsubmissions->find()
			->innerJoinWith('Events')
			->where(['Eventsubmissions.conventionseason_id' => $sess_admin_header_season_id])
			->where($this->getManualSubmissionEventConditions())
			->where($this->getSubmissionHasAnyFileConditions())
			->contain(['Conventions', 'Students', 'Events', 'Uploadeduser', 'Conventionregistrations' => ['Users']])
			->order(['Eventsubmissions.id' => 'DESC'])
			->all();

		$eventsubmissions = $this->dedupeSchoolUploads($eventsubmissions);

		$submissionIds = $eventsubmissions->extract('id')->map(static function ($id) {
			return (int)$id;
		})->toList();

		$judgedSubmissionIds = [];
		if (!empty($submissionIds)) {
			$judgedSubmissionIds = $this->Judgeevaluations->find()
				->select(['eventsubmission_id'])
				->where(['Judgeevaluations.eventsubmission_id IN' => $submissionIds])
				->group('Judgeevaluations.eventsubmission_id')
				->extract('eventsubmission_id')
				->map(static function ($id) {
					return (int)$id;
				})
				->toList();
		}
		$judgedSubmissionIds = array_values(array_unique(array_filter($judgedSubmissionIds)));

		$this->set('convSeasonD', $convSeasonD);
		$this->set('eventsubmissions', $eventsubmissions);
		$this->set('judgedSubmissionIds', $judgedSubmissionIds);
	}

	public function schoolsuploadsbulkdelete() {

		if (!$this->request->is('post')) {
			$this->Flash->error('Submissions can only be deleted using the form.');
			return $this->redirect(['controller' => 'eventsubmissions', 'action' => 'schoolsuploads']);
		}

		$sess_admin_header_season_id = (int)$this->request->getSession()->read('sess_admin_header_season_id');
		if ($sess_admin_header_season_id <= 0) {
			$this->Flash->error('Please choose a convention season from the header first.');
			return $this->redirect(['controller' => 'eventsubmissions', 'action' => 'schoolsuploads']);
		}

		$submittedIds = $this->request->getData('submission_ids');
		if (!is_array($submittedIds)) {
			$submittedIds = [];
		}

		$submissionIds = array_values(array_unique(array_filter(array_map('intval', $submittedIds), static function ($id) {
			return $id > 0;
		})));

		if (empty($submissionIds)) {
			$this->Flash->error('Select at least one upload to delete.');
			return $this->redirect(['controller' => 'eventsubmissions', 'action' => 'schoolsuploads']);
		}

		$submissions = $this->Eventsubmissions->find()
			->innerJoinWith('Events')
			->where([
				'Eventsubmissions.conventionseason_id' => $sess_admin_header_season_id,
				'Eventsubmissions.id IN' => $submissionIds,
			])
			->where($this->getManualSubmissionEventConditions())
			->all();

		$deleted = 0;
		$protected = 0;
		foreach ($submissions as $submission) {
			$hasEvaluation = $this->Judgeevaluations->find()
				->where([
					'Judgeevaluations.eventsubmission_id' => $submission->id,
					'Judgeevaluations.conventionregistration_id' => $submission->conventionregistration_id,
				])
				->first();

			if ($hasEvaluation) {
				$protected++;
				continue;
			}

			if ($this->Eventsubmissions->delete($submission)) {
				$this->removeSubmissionFiles($submission);
				$deleted++;
			}
		}

		if ($deleted > 0) {
			$message = sprintf('%d upload(s) removed.', $deleted);
			if ($protected > 0) {
				$message .= sprintf(' %d skipped because a judge evaluation exists.', $protected);
			}
			$this->Flash->success($message);
		} else {
			$this->Flash->error($protected > 0
				? 'No uploads were removed. Judge-evaluated submissions cannot be deleted.'
				: 'No selected uploads could be deleted from this season.');
		}

		return $this->redirect(['controller' => 'eventsubmissions', 'action' => 'schoolsuploads']);
	}

	public function bulkdelete($registration_slug = null) {
		if ($this->isSquadAdmin()) {
			$this->Flash->error('Squad user can delete submissions only from Schools Uploads page.');
			return $this->redirect(['controller' => 'eventsubmissions', 'action' => 'schoolsuploads']);
		}

		$redirect = ['controller' => 'eventsubmissions', 'action' => 'index', $registration_slug];
		if (!$this->request->is('post')) {
			$this->Flash->error('Submissions can only be deleted using the form.');
			return $this->redirect($redirect);
		}

		$registration = $this->Conventionregistrations->find()
			->where(['Conventionregistrations.slug' => $registration_slug])
			->first();
		if (!$registration) {
			$this->Flash->error('Invalid registration.');
			return $this->redirect(['controller' => 'eventsubmissions', 'action' => 'index']);
		}

		$submittedIds = $this->request->getData('submission_ids');
		if (!is_array($submittedIds)) {
			$submittedIds = [];
		}
		$submissionIds = array_values(array_unique(array_filter(array_map('intval', $submittedIds), static function ($id) {
			return $id > 0;
		})));
		if (empty($submissionIds)) {
			$this->Flash->error('Select at least one event submission.');
			return $this->redirect($redirect);
		}

		$visibleConditions = $this->getRegistrationSubmissionConditions($registration);
		$submissions = $this->Eventsubmissions->find()
			->where([
				'AND' => [
					$visibleConditions,
					['Eventsubmissions.id IN' => $submissionIds],
				],
			])
			->all();

		$deleted = 0;
		$protected = 0;
		foreach ($submissions as $submission) {
			$hasEvaluation = $this->Judgeevaluations->find()
				->where([
					'Judgeevaluations.eventsubmission_id' => $submission->id,
					'Judgeevaluations.conventionregistration_id' => $submission->conventionregistration_id,
				])
				->first();
			if ($hasEvaluation) {
				$protected++;
				continue;
			}

			if ($this->Eventsubmissions->delete($submission)) {
				$this->removeSubmissionFiles($submission);
				$deleted++;
			}
		}

		if ($deleted > 0) {
			$message = sprintf('%d event submission(s) removed.', $deleted);
			if ($protected > 0) {
				$message .= sprintf(' %d skipped because a judge evaluation exists.', $protected);
			}
			$this->Flash->success($message);
		} else {
			$this->Flash->error($protected > 0
				? 'No submissions were removed. Judge-evaluated submissions cannot be deleted.'
				: 'No selected submissions could be deleted from this registration.');
		}

		return $this->redirect($redirect);
	}

	private function getRegistrationSubmissionConditions($registration) {
		$conditions = [
			'Eventsubmissions.conventionregistration_id' => (int)$registration->id,
		];
		$approvedEventIds = [];
		$partnerSchoolIds = [];

		$approvedCombinedRows = $this->Combinerequests->find()
			->select(['event_id', 'user_id', 'combine_with_user_id'])
			->where([
				'Combinerequests.status' => 1,
				'Combinerequests.conventionseason_id' => (int)$registration->conventionseason_id,
				'OR' => [
					['Combinerequests.user_id' => (int)$registration->user_id],
					['Combinerequests.combine_with_user_id' => (int)$registration->user_id],
				],
			])
			->all();

		foreach ($approvedCombinedRows as $combinedRow) {
			$approvedEventIds[] = (int)$combinedRow->event_id;
			foreach ([(int)$combinedRow->user_id, (int)$combinedRow->combine_with_user_id] as $schoolId) {
				if ($schoolId > 0 && $schoolId !== (int)$registration->user_id) {
					$partnerSchoolIds[] = $schoolId;
				}
			}
		}

		$mixedGroupRows = $this->Crstudentevents->find()
			->select(['student_id', 'event_id'])
			->where([
				'Crstudentevents.conventionregistration_id' => (int)$registration->id,
				'Crstudentevents.event_id >' => 0,
				'Crstudentevents.student_id >' => 0,
				'Crstudentevents.group_name IS NOT' => null,
				'Crstudentevents.group_name !=' => '',
			])
			->all();

		$studentIds = [];
		foreach ($mixedGroupRows as $mixedGroupRow) {
			$studentIds[] = (int)$mixedGroupRow->student_id;
			$approvedEventIds[] = (int)$mixedGroupRow->event_id;
		}

		$studentIds = array_values(array_unique(array_filter($studentIds)));
		if (!empty($studentIds)) {
			$studentRows = $this->Users->find()
				->select(['id', 'school_id'])
				->where(['Users.id IN' => $studentIds])
				->all();
			foreach ($studentRows as $studentRow) {
				$studentSchoolId = (int)($studentRow->school_id ?? 0);
				if ($studentSchoolId > 0 && $studentSchoolId !== (int)$registration->user_id) {
					$partnerSchoolIds[] = $studentSchoolId;
				}
			}
		}

		$approvedEventIds = array_values(array_unique(array_filter($approvedEventIds)));
		$partnerSchoolIds = array_values(array_unique(array_filter($partnerSchoolIds)));
		if (!empty($approvedEventIds) && !empty($partnerSchoolIds)) {
			$partnerRegistrationIds = $this->Conventionregistrations->find()
				->select(['id'])
				->where([
					'Conventionregistrations.conventionseason_id' => (int)$registration->conventionseason_id,
					'Conventionregistrations.user_id IN' => $partnerSchoolIds,
				])
				->extract('id')
				->map(static function ($id) {
					return (int)$id;
				})
				->toList();

			if (!empty($partnerRegistrationIds)) {
				$conditions = [
					'OR' => [
						['Eventsubmissions.conventionregistration_id' => (int)$registration->id],
						[
							'Eventsubmissions.conventionregistration_id IN' => $partnerRegistrationIds,
							'Eventsubmissions.event_id IN' => $approvedEventIds,
						],
					],
				];
			}
		}

		return $conditions;
	}

	private function removeSubmissionFiles($submission) {
		foreach (['mediafile_file_system_name', 'report', 'score_sheet', 'additional_documents'] as $field) {
			$file = $submission->{$field};
			$path = UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH . $file;
			if (!empty($file) && is_file($path)) {
				@unlink($path);
			}
		}
	}

	private function getManualSubmissionEventConditions(): array {
		return [
			'OR' => [
				"LOWER(TRIM(COALESCE(Events.upload_type, ''))) <> 'nil'",
				['Events.report' => 1],
				['Events.context_box' => 1],
				['Events.score_sheet' => 1],
				['Events.additional_documents' => 1],
			],
		];
	}

	private function getSubmissionHasAnyFileConditions(): array {
		return [
			'OR' => [
				"COALESCE(Eventsubmissions.mediafile_file_system_name, '') <> ''",
				"COALESCE(Eventsubmissions.report, '') <> ''",
				"COALESCE(Eventsubmissions.score_sheet, '') <> ''",
				"COALESCE(Eventsubmissions.additional_documents, '') <> ''",
			],
		];
	}

	private function dedupeSchoolUploads($eventsubmissions) {
		$unique = [];
		foreach ($eventsubmissions as $submission) {
			$registrationId = (int)($submission->conventionregistration_id ?? 0);
			$eventId = (int)($submission->event_id ?? 0);
			$studentId = (int)($submission->student_id ?? 0);
			$groupName = trim((string)($submission->group_name ?? ''));

			if ($studentId > 0) {
				$key = 'S|' . $registrationId . '|' . $eventId . '|' . $studentId;
			} else {
				$key = 'G|' . $registrationId . '|' . $eventId . '|' . strtolower($groupName);
			}

			if (!isset($unique[$key])) {
				$unique[$key] = $submission;
			}
		}

		return new \Cake\Collection\Collection(array_values($unique));
	}

	private function isSquadAdmin(): bool {
		$username = strtolower((string)$this->request->getSession()->read('admin_username'));
		return $username === 'squad';
	}

	private function isSchoolsUploadsReferrer(): bool {
		$referer = (string)$this->request->referer();
		return strpos($referer, '/admin/eventsubmissions/schoolsuploads') !== false;
	}
	
	public function removesubmission($submission_slug = null, $conv_reg_slug = null) {
		if ($this->isSquadAdmin() && !$this->isSchoolsUploadsReferrer()) {
			$this->Flash->error('Squad user can delete submissions only from Schools Uploads page.');
			return $this->redirect(['controller' => 'eventsubmissions', 'action' => 'schoolsuploads']);
		}

		$redirectToSchoolsUploads = $this->isSchoolsUploadsReferrer();
		
		$convRedG = $this->Conventionregistrations->find()->where(['Conventionregistrations.slug' => $conv_reg_slug])->first();
		if($convRedG)
		{
			// check if submission exists
			$checkExists = $this->Eventsubmissions->find()->where([
				'AND' => [
					$this->getRegistrationSubmissionConditions($convRedG),
					['Eventsubmissions.slug' => $submission_slug],
				],
			])->first();
			
			if($checkExists)
			{	
				// to check if any judge submitted evaluation or not
				$checkJudgeEval = $this->Judgeevaluations->find()->where(['Judgeevaluations.eventsubmission_id' => $checkExists->id,'Judgeevaluations.conventionregistration_id' => $convRedG->id])->first();
				if($checkJudgeEval)
				{
					$this->Flash->error('Event submission cannot delete. Judge submitted evaluation for this submission.');
				}
				else
				{
					if ($this->Eventsubmissions->delete($checkExists)) {
						$this->removeSubmissionFiles($checkExists);
						$this->Flash->success('Events submission removed successfully.');
					}
				}
			}
			else
			{
				$this->Flash->error('Invalid document.');
			}
		}
		else
		{
			$this->Flash->error('Invalid registration.');
		}
		
		if ($redirectToSchoolsUploads) {
			return $this->redirect(['controller' => 'eventsubmissions', 'action' => 'schoolsuploads']);
		}

		return $this->redirect(['controller' => 'eventsubmissions', 'action' => 'index', $conv_reg_slug]);
    }
	
	public function guidelinebreach($slug=null) {

        $this->set('title', ADMIN_TITLE . 'Guideline Breach');
        $this->viewBuilder()->setLayout('admin');
        $this->set('judgeEvaluations', '1');
        $this->set('guidelineBreachList', '1');
		
		$separator = array();
        $condition = array();
		
		$condition = array('Eventsubmissions.guideline_breach !=' => 0);
		
		// to check if conv season selected from header then filter list
		$sess_admin_header_season_id = $this->request->getSession()->read("sess_admin_header_season_id");
		if($sess_admin_header_season_id>0)
		{
			$condition[] = "(Eventsubmissions.conventionseason_id = '".$sess_admin_header_season_id."')";
		}
		
        //$this->prx($condition);exit;
        $separator = implode("/", $separator);
        $this->set('separator', $separator);
        $this->paginate = ['contain' => ['Conventionregistrations','Conventions','Users','Events','Students','Uploadeduser','Judge'], 'conditions' => $condition, 'limit' => 100000, 'order' => ['Eventsubmissions.modified' => 'DESC']];
        $this->set('eventsubmissions', $this->paginate($this->Eventsubmissions));
        if ($this->request->is("ajax")) {
            $this->viewBuilder()->setLayout(($this->request->is("ajax")) ? "" : "default");
            $this->viewBuilder()->setTemplatePath('Element' . DS . 'Admin/Eventsubmissions');
            $this->render('guidelinebreach');
        }
    }
	
	public function approveguidelinebreach($slug=null) {
        
		$eventSubmissionD = $this->Eventsubmissions->find()->where(['Eventsubmissions.slug' => $slug])->first();
		if($eventSubmissionD)
		{
			$this->Eventsubmissions->updateAll(['guideline_breach' => '2','modified' => date('Y-m-d H:i:s')], ["slug"=>$slug]);
			
			// check if judge evaluated, then set marks to 0
			$this->Judgeevaluations->updateAll(['total_marks_obtained' => '0','modified' => date('Y-m-d H:i:s')], ["eventsubmission_id"=>$eventSubmissionD->id]);
			
			$this->Flash->success('Guideline breach approved successfully. Marks has been set to 0.');
		
		}
		else
		{
			$this->Flash->error('Invalid action.');
		}
        $this->redirect(['controller'=>'eventsubmissions', 'action' => 'guidelinebreach']);
    }
	
	public function rejectguidelinebreach($slug=null) {
        
		$eventSubmissionD = $this->Eventsubmissions->find()->where(['Eventsubmissions.slug' => $slug])->first();
		if($eventSubmissionD)
		{
			$this->Eventsubmissions->updateAll(['guideline_breach' => '0','guideline_breach_by_judge_id' => '0','modified' => date('Y-m-d H:i:s')], ["slug"=>$slug]);
			
			$this->Flash->success('Guideline breach declined successfully.');
		}
		else
		{
			$this->Flash->error('Invalid action.');
		}
        $this->redirect(['controller'=>'eventsubmissions', 'action' => 'guidelinebreach']);
    }
	
	public function deleteguidelinebreach($slug=null) {
        
		$eventSubmissionD = $this->Eventsubmissions->find()->where(['Eventsubmissions.slug' => $slug])->first();
		if($eventSubmissionD)
		{
			$this->Eventsubmissions->updateAll(['guideline_breach' => '0','guideline_breach_by_judge_id' => '0','modified' => date('Y-m-d H:i:s')], ["slug"=>$slug]);
			
			$this->Flash->success('Guideline breach deleted successfully.');
		}
		else
		{
			$this->Flash->error('Invalid action.');
		}
        $this->redirect(['controller'=>'eventsubmissions', 'action' => 'guidelinebreach']);
    }
	
	public function commandperformance($slug=null) {

        $this->set('title', ADMIN_TITLE . 'Command Performance');
        $this->viewBuilder()->setLayout('admin');
        $this->set('judgeEvaluations', '1');
        $this->set('commandPerformanceList', '1');
		
		$separator = array();
        $condition = array();
		
		$condition = array('Eventsubmissions.command_performance' => 1);
		
		// to check if conv season selected from header then filter list
		$sess_admin_header_season_id = $this->request->getSession()->read("sess_admin_header_season_id");
		if($sess_admin_header_season_id>0)
		{
			$condition[] = "(Eventsubmissions.conventionseason_id = '".$sess_admin_header_season_id."')";
		}
		
        //$this->prx($condition);exit;
        $separator = implode("/", $separator);
        $this->set('separator', $separator);
        $this->paginate = ['contain' => ['Conventionregistrations','Conventions','Users','Events','Students','Uploadeduser','Judgecommand'], 'conditions' => $condition, 'limit' => 100000, 'order' => ['Eventsubmissions.id' => 'DESC']];
        $this->set('eventsubmissions', $this->paginate($this->Eventsubmissions));
        if ($this->request->is("ajax")) {
            $this->viewBuilder()->setLayout(($this->request->is("ajax")) ? "" : "default");
            $this->viewBuilder()->setTemplatePath('Element' . DS . 'Admin/Eventsubmissions');
            $this->render('commandperformance');
        }
    }
	
	public function removecommandperformance($slug=null) {
        
		$eventSubmissionD = $this->Eventsubmissions->find()->where(['Eventsubmissions.slug' => $slug])->first();
		if($eventSubmissionD)
		{
			$this->Eventsubmissions->updateAll(['command_performance' => '0','mark_command_by_judge_id' => NULL,'modified' => date('Y-m-d H:i:s')], ["slug"=>$slug]);
			
			$this->Flash->success('Command performance removed successfully.');
		
		}
		else
		{
			$this->Flash->error('Invalid action.');
		}
        $this->redirect(['controller'=>'eventsubmissions', 'action' => 'commandperformance']);
    }
	
	

}

?>
