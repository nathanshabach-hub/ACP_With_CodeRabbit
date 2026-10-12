<?php
namespace App\Controller\Admin;
use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use App\Utility\LegacyEmail as Email;
use Cake\Datasource\ConnectionManager;
use Cake\Collection\Collection;

#[\AllowDynamicProperties]
class UsersController extends AppController{

    public array $paginate = ['limit' => 50];
   
    public function initialize(): void{
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
                 $this->redirect(['controller'=>'admins', 'action' => 'login']);
            }
        }
        
        $this->loadModel('Emailtemplates');
        $this->loadModel('Admins');
        $this->loadModel('Conventionregistrationteachers');
        $this->loadModel('Conventionregistrationstudents');
    }

    private function getLoggedInAdmin()
    {
        $adminId = (int)$this->request->getSession()->read('admin_id');
        if ($adminId <= 0) {
            return null;
        }

        return $this->Admins->find()
            ->where(['Admins.id' => $adminId])
            ->first();
    }

    private function canUseSuperAdminImpersonation($admin): bool
    {
        if (empty($admin)) {
            return false;
        }

        $email = strtolower(trim((string)$admin->email));
        $username = strtolower(trim((string)$admin->username));

        return ($email === 'events@scee.edu.au' || $username === 'events');
    }

    private function getSchoolSubmissionStats(array $schoolIds, ?int $conventionSeasonId = null): array
    {
        $schoolIds = array_values(array_unique(array_filter(array_map('intval', $schoolIds))));
        if (empty($schoolIds)) {
            return [];
        }

        $this->loadModel('Conventionregistrations');
        $this->loadModel('Conventionregistrationstudents');
        $this->loadModel('Events');
        $this->loadModel('Crstudentevents');
        $this->loadModel('Eventsubmissions');

        $registrationConditions = [
            'Conventionregistrations.user_id IN' => $schoolIds,
        ];
        if (!empty($conventionSeasonId)) {
            $registrationConditions['Conventionregistrations.conventionseason_id'] = (int)$conventionSeasonId;
        }

        $registrationRows = $this->Conventionregistrations->find()
            ->select(['id', 'user_id'])
            ->where($registrationConditions)
            ->all();

        $registrationIdToSchoolId = [];
        $registrationIds = [];
        foreach ($registrationRows as $registrationRow) {
            $registrationId = (int)$registrationRow->id;
            $schoolId = (int)$registrationRow->user_id;
            if ($registrationId <= 0 || $schoolId <= 0) {
                continue;
            }
            $registrationIdToSchoolId[$registrationId] = $schoolId;
            $registrationIds[] = $registrationId;
        }

        $stats = [];
        foreach ($schoolIds as $schoolId) {
            $stats[$schoolId] = ['submitted' => 0, 'total' => 0];
        }

        if (empty($registrationIds)) {
            return $stats;
        }

        $studentRows = $this->Conventionregistrationstudents->find()
            ->select(['conventionregistration_id', 'student_id', 'event_ids'])
            ->where(['Conventionregistrationstudents.conventionregistration_id IN' => $registrationIds])
            ->all();

        $eventIds = [];
        $eventAssignments = [];
        foreach ($studentRows as $studentRow) {
            $registrationId = (int)$studentRow->conventionregistration_id;
            $studentId = (int)$studentRow->student_id;
            if (!isset($registrationIdToSchoolId[$registrationId]) || $studentId <= 0) {
                continue;
            }

            $ids = array_filter(array_map('intval', explode(',', (string)$studentRow->event_ids)));
            if (empty($ids)) {
                continue;
            }

            $schoolId = (int)$registrationIdToSchoolId[$registrationId];
            foreach ($ids as $eventId) {
                if ($eventId <= 0) {
                    continue;
                }
                $eventIds[$eventId] = true;
                $stats[$schoolId]['total']++;
                $eventAssignments[] = [
                    'registration_id' => $registrationId,
                    'student_id' => $studentId,
                    'event_id' => $eventId,
                    'school_id' => $schoolId,
                ];
            }
        }

        if (empty($eventAssignments)) {
            return $stats;
        }

        $eventMetaById = [];
        if (!empty($eventIds)) {
            $eventMetaRows = $this->Events->find()
                ->select([
                    'id',
                    'upload_type',
                    'report',
                    'context_box',
                    'score_sheet',
                    'additional_documents',
                    'auto_submission',
                    'group_event_yes_no',
                ])
                ->where(['Events.id IN' => array_keys($eventIds)])
                ->all();

            foreach ($eventMetaRows as $eventMetaRow) {
                $eventMetaById[(int)$eventMetaRow->id] = [
                    'upload_type' => (string)$eventMetaRow->upload_type,
                    'report' => (int)$eventMetaRow->report,
                    'context_box' => (int)$eventMetaRow->context_box,
                    'score_sheet' => (int)$eventMetaRow->score_sheet,
                    'additional_documents' => (int)$eventMetaRow->additional_documents,
                    'auto_submission' => (int)$eventMetaRow->auto_submission,
                    'group_event_yes_no' => (int)$eventMetaRow->group_event_yes_no,
                ];
            }
        }

        $groupNameByAssignmentKey = [];
        $groupRows = $this->Crstudentevents->find()
            ->select(['conventionregistration_id', 'student_id', 'event_id', 'group_name'])
            ->where(['Crstudentevents.conventionregistration_id IN' => $registrationIds])
            ->all();

        foreach ($groupRows as $groupRow) {
            $groupName = trim((string)$groupRow->group_name);
            $groupNameByAssignmentKey[
                (int)$groupRow->conventionregistration_id . '|' . (int)$groupRow->student_id . '|' . (int)$groupRow->event_id
            ] = $groupName;
        }

        $submissionByStudentKey = [];
        $submissionByGroupKey = [];
        $submissionRows = $this->Eventsubmissions->find()
            ->select(['conventionregistration_id', 'event_id', 'student_id', 'group_name'])
            ->where(['Eventsubmissions.conventionregistration_id IN' => $registrationIds])
            ->all();

        foreach ($submissionRows as $submissionRow) {
            $studentKey = (int)$submissionRow->conventionregistration_id . '|' . (int)$submissionRow->event_id . '|' . (int)$submissionRow->student_id;
            $submissionByStudentKey[$studentKey] = true;

            $groupName = trim((string)$submissionRow->group_name);
            if ($groupName !== '') {
                $groupKey = (int)$submissionRow->conventionregistration_id . '|' . (int)$submissionRow->event_id . '|' . $groupName;
                $submissionByGroupKey[$groupKey] = true;
            }
        }

        foreach ($eventAssignments as $assignment) {
            $registrationId = (int)$assignment['registration_id'];
            $studentId = (int)$assignment['student_id'];
            $eventId = (int)$assignment['event_id'];
            $schoolId = (int)$assignment['school_id'];

            $eventMeta = $eventMetaById[$eventId] ?? null;
            if ($eventMeta === null) {
                continue;
            }

            $requiresUpload = (
                $eventMeta['upload_type'] !== 'Nil' ||
                $eventMeta['report'] === 1 ||
                $eventMeta['context_box'] === 1 ||
                $eventMeta['score_sheet'] === 1 ||
                $eventMeta['additional_documents'] === 1
            );

            $isSubmitted = false;
            if ($requiresUpload) {
                if ($eventMeta['group_event_yes_no'] === 1) {
                    $groupName = $groupNameByAssignmentKey[$registrationId . '|' . $studentId . '|' . $eventId] ?? '';
                    if ($groupName !== '' && !empty($submissionByGroupKey[$registrationId . '|' . $eventId . '|' . $groupName])) {
                        $isSubmitted = true;
                    }
                } else {
                    if (!empty($submissionByStudentKey[$registrationId . '|' . $eventId . '|' . $studentId])) {
                        $isSubmitted = true;
                    }
                }
            } else {
                if ($eventMeta['auto_submission'] === 1 && $eventMeta['group_event_yes_no'] === 1) {
                    $groupName = $groupNameByAssignmentKey[$registrationId . '|' . $studentId . '|' . $eventId] ?? '';
                    $isSubmitted = ($groupName !== '');
                } else {
                    $isSubmitted = true;
                }
            }

            if ($isSubmitted) {
                $stats[$schoolId]['submitted']++;
            }
        }

        return $stats;
    }
    
 
    /*School Module*/
    
    public function index() {
        $this->set('title', ADMIN_TITLE. 'Manage Schools/Homeschools');
        $this->viewBuilder()->setLayout('admin');
        $this->set('manageSchools', '1');
        $this->set('schoolList', '1');
        $this->set('canSuperAdminImpersonate', $this->canUseSuperAdminImpersonation($this->getLoggedInAdmin()));
        
        $separator = array();
        $condition = array();
        
        $condition[] = "(Users.user_type = 'School')";

        $sessAdminHeaderSeasonId = (int)$this->request->getSession()->read('sess_admin_header_season_id');
        if ($sessAdminHeaderSeasonId > 0) {
            $convSeason = $this->Conventionseasons->find()
                ->where(['Conventionseasons.id' => $sessAdminHeaderSeasonId])
                ->first();

            if ($convSeason) {
                $registeredSchoolIds = $this->Conventionregistrations->find()
                    ->select(['Conventionregistrations.user_id'])
                    ->where([
                        'Conventionregistrations.convention_id' => $convSeason->convention_id,
                        'Conventionregistrations.season_id' => $convSeason->season_id,
                        'Conventionregistrations.season_year' => $convSeason->season_year,
                    ])
                    ->distinct(['Conventionregistrations.user_id'])
                    ->extract('user_id')
                    ->map(static function ($userId) {
                        return (int)$userId;
                    })
                    ->toList();

                if (!empty($registeredSchoolIds)) {
                    $condition['Users.id IN'] = $registeredSchoolIds;
                } else {
                    // No registered schools for the selected convention season.
                    $condition['Users.id'] = 0;
                }
            }
        }
        
        if($this->request->is('post')){
            if (isset($this->request->getData()['action'])) {
                $selectedIds = array_values(array_filter(array_map('intval', (array)$this->request->getData('chkRecordId', []))));
                $action = $this->request->getData()['action'];
                if (!empty($selectedIds)) {
                    $pendingJudgeCondition = [
                        'OR' => [
                            [
                                'Users.user_type' => 'Judge',
                                'Users.status' => 0,
                                'Users.activation_status' => 1,
                            ],
                            [
                                'Users.user_type' => 'Teacher_Parent',
                                'Users.is_judge' => 2,
                                'Users.status' => 1,
                                'Users.activation_status' => 1,
                            ],
                        ],
                    ];

                    $targetUsers = $this->Users->find()
                        ->select(['id', 'user_type'])
                        ->where(['Users.id IN' => $selectedIds, $pendingJudgeCondition])
                        ->all();

                    $targetUserIds = [];
                    $targetJudgeIds = [];
                    $targetSupervisorIds = [];

                    foreach ($targetUsers as $targetUser) {
                        $targetUserIds[] = (int)$targetUser->id;
                        if ($targetUser->user_type === 'Judge') {
                            $targetJudgeIds[] = (int)$targetUser->id;
                        }
                        if ($targetUser->user_type === 'Teacher_Parent') {
                            $targetSupervisorIds[] = (int)$targetUser->id;
                        }
                    }

                    if (empty($targetUserIds)) {
                        $this->Flash->error('No valid pending judge accounts selected.');
                        return $this->redirect(['controller' => 'users', 'action' => 'pendingjudges']);
                    }

                    if ($action == "Activate") {
                        $this->Users->updateAll(['status' => '1'], ['Users.id IN' => $targetUserIds]);
                        $this->Flash->success('Records are activated successfully.');
                    } elseif ($action == "Deactivate") {
                        $this->Users->updateAll(['status' => '0'], ['Users.id IN' => $targetUserIds]);
                        $this->Flash->success('Records are deactivated successfully.');
                    } elseif ($action == "Reject") {
                        if (!empty($targetJudgeIds)) {
                            $this->Users->updateAll(
                                ['status' => '3', 'modified' => date('Y-m-d H:i:s')],
                                ['Users.id IN' => $targetJudgeIds]
                            );
                        }
                        if (!empty($targetSupervisorIds)) {
                            $this->Users->updateAll(
                                ['is_judge' => '3', 'modified' => date('Y-m-d H:i:s')],
                                ['Users.id IN' => $targetSupervisorIds]
                            );
                        }
                        $this->Flash->success('Selected pending judge accounts were rejected successfully.');
                    } elseif ($action == "Delete") {
                        $this->Users->deleteAll(['Users.id IN' => $targetUserIds]);
                        $this->Flash->success('Selected pending judge accounts were deleted successfully.');
                    }
                } else {
                    $this->Flash->error('Please select at least one record.');
                }
            }
            
            if(isset($this->request->getData()['Users']['keyword']) && $this->request->getData()['Users']['keyword']!=''){
              $keyword = trim($this->request->getData()['Users']['keyword']); 
            }
        }elseif($this->request->getParam('pass')){
            if(isset($this->request->getParam('pass', [])[0]) && $this->request->getParam('pass', [])[0]!=''){
                $searchArr = $this->request->getParam('pass', []);
                foreach($searchArr as $val){
                if (strpos($val, ":") !== false) {
                   $vars  = explode(":",$val);
                   ${$vars[0]}   = urldecode($vars[1]);
                }
               }
            }
        }
        
        if (isset($keyword) && $keyword != '') {
             $separator[] = 'keyword:' . urlencode($keyword);
             $condition[] = "(Users.customer_code LIKE '%".addslashes($keyword)."%' OR  Users.first_name LIKE '%".addslashes($keyword)."%' OR  Users.email_address LIKE '%".addslashes($keyword)."%')";
             $this->set('keyword', $keyword);
        }
        
        $separator = implode("/", $separator); 
        $this->set('separator',$separator);
        $this->paginate = ['conditions' => $condition, 'limit' => 50, 'order' => ['Users.id' => 'DESC']];
        $users = $this->paginate($this->Users);
        $this->set('users', $users);

        $schoolIdsForPage = [];
        foreach ($users as $user) {
            $schoolIdsForPage[] = (int)$user->id;
        }
        $submittedStatsBySchoolId = $this->getSchoolSubmissionStats($schoolIdsForPage, $sessAdminHeaderSeasonId > 0 ? $sessAdminHeaderSeasonId : null);
        $this->set('submittedStatsBySchoolId', $submittedStatsBySchoolId);
        if($this->request->is("ajax")){
            $this->viewBuilder()->disableAutoLayout();
            $this->viewBuilder()->setTemplatePath('Element' . DS . 'Admin/Users');
            $this->render('index');
        }
    }
    
    public function activateuser($slug=null){
        if ($slug != '') {
            $this->viewBuilder()->disableAutoLayout();
            $this->Users->updateAll(['status' => '1'], ["slug"=>$slug]);
            $this->set('action', '/admin/users/deactivateuser/' . $slug);
            $this->set('status', 1);
            $this->viewBuilder()->setTemplatePath('Element' . DS . 'Admin');
            $this->render('update_status');
        }
    }
    
    public function deactivateuser($slug=null){
        if ($slug != '') {
            $this->viewBuilder()->disableAutoLayout();
            $this->Users->updateAll(['status' => '0'], ["slug"=>$slug]);
            $this->set('action', '/admin/users/activateuser/' . $slug);
            $this->set('status', 0);
            $this->viewBuilder()->setTemplatePath('Element' . DS . 'Admin');
            $this->render('update_status');
        }
    }
    
    public function archiveuser($slug=null) {
        $this->Users->updateAll(['status' => '2'], ["slug"=>$slug]);
        $this->Flash->success('School details archived successfully.');
        $this->redirect(['controller'=>'users', 'action' => 'index']);
    }
    
    public function restoreuser($slug=null) {
        $this->Users->updateAll(['status' => '1'], ["slug"=>$slug]);
        $this->Flash->success('School details restored successfully.');
        $this->redirect(['controller'=>'users', 'action' => 'index']);
    }
    
    public function resetaccount($slug=null) {
        if ($slug != '') {
            $this->Users->updateAll(
                ['status' => '1', 'activation_status' => '1'],
                ['slug' => $slug]
            );
            $this->Flash->success('Account has been reset successfully. The school can now log in.');
        }
        $this->redirect(['controller' => 'users', 'action' => 'index']);
    }
    
    public function add() {
        $this->set('title', ADMIN_TITLE . 'Add School/Homeschool');
        $this->viewBuilder()->setLayout('admin');
        $this->set('manageSchools', '1');
        $this->set('schoolAdd', '1');
        
        $users = $this->Users->newEntity([]);
        if ($this->request->is('post')) {
            
            $data = $this->Users->patchEntity($users, $this->request->getData(), ['validate' => 'add']);
            if (count($data->getErrors()) == 0) {

                $flagCheck = 1;
                
                $checkCCode = $this->Users->find()->where(['Users.customer_code' => $data->customer_code])->first();
                if($checkCCode)
                {
                    $flagCheck = 0;
                    $this->Flash->success('Customer code already exists.');
                }
                
                if($flagCheck == 1)
                {
                    $slug = $this->getSlug($this->request->getData()['Users']['first_name'] . ' ' . time(), 'Users');
                    $data->slug = $slug;
                    
                    $data->user_type = 'School';
                    $data->status = 1;
                    $data->activation_status = 0;
                    $data->created = date('Y-m-d H:i:s');
                    $data->modified = date('Y-m-d H:i:s');
                    if ($this->Users->save($data)) {
                        $this->Flash->success('School details added successfully. School admin need to verify account from front end.');
                        $this->redirect(['controller' => 'users', 'action' => 'index']);
                    }
                }
                
            } else {
                // $this->Flash->error('Please below listed errors.');
            }
        }
        $this->set('users', $users);
    }
    
    public function add_custom_query() {
        $this->set('title', ADMIN_TITLE . 'Add School/Homeschool');
        $this->viewBuilder()->setLayout('admin');
        $this->set('manageSchools', '1');
        $this->set('schoolAdd', '1');
        
        $users = $this->Users->newEntity([]);
        if ($this->request->is('post')) {
            
            $data = $this->Users->patchEntity($users, $this->request->getData(), ['validate' => 'add']);
            if (count($data->getErrors()) == 0) {

                $flagCheck = 1;
                
                $checkCCode = $this->Users->find()->where(['Users.customer_code' => $data->customer_code])->first();
                if($checkCCode)
                {
                    $flagCheck = 0;
                    $this->Flash->error('Customer code already exists.');
                }
                
                if($flagCheck == 1)
                {
                    
                    $conn = ConnectionManager::get('default');
                    
                    $slug = $this->getSlug($this->request->getData()['Users']['first_name'] . ' ' . time(), 'Users');
                    
                    $queryAdd = "INSERT INTO users 
                    (
                    `slug`,`user_type`,`customer_code`,
                    `first_name`,`middle_name`,`phone`,
                    `phone2`,`email_address`,`bill_to_street`,
                    `bill_to_block`,`bill_to_city`,`bill_to_zip`,
                    `bill_to_country`,`status`,`activation_status`,
                    `created`,`modified`
                    )
                    VALUES
                    (
                    '".$slug."','School','".$data->customer_code."',
                    '".$data->first_name."','".$data->middle_name."','".$data->phone."',
                    '".$data->phone2."','".$data->email_address."','".$data->bill_to_street."',
                    '".$data->bill_to_block."','".$data->bill_to_city."','".$data->bill_to_zip."',
                    '".$data->bill_to_country."','1','0',
                    '".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."'
                    )
                    ";
                    
                    $stmt = $conn->execute($queryAdd);
                    $this->Flash->success('School details added successfully. School admin need to verify account from front end.');
                    $this->redirect(['controller' => 'users', 'action' => 'index']);
                }
                
            } else {
                // $this->Flash->error('Please below listed errors.');
            }
        }
        $this->set('users', $users);
    }
    
    public function edit($slug=null){
        $this->set('title', ADMIN_TITLE. 'Edit School');
        $this->viewBuilder()->setLayout('admin');
        
        $this->set('manageSchools', '1');
        $this->set('schoolList', '1');
       
        if($slug){
            $userD = $this->Users->find()->where(['Users.slug' => $slug])->first();
            $uid = $userD->id;
            $this->set('userD', $userD);
        }
        $users = $this->Users->get($uid);
        if ($this->request->is(['post', 'put'])) {
            if(empty($this->request->getData()['Users']['password'])){
                unset($this->request->getData()['Users']['password']);
            }
            $data = $this->Users->patchEntity($users, $this->request->getData());
            
            $flagCheck = 1;
            if($data->email_address_old != $data->email_address)
            {
                $checkUE = $this->Users->find()->where(['Users.email_address' => $data->email_address])->first();
                if($checkUE)
                {
                    $flagCheck = 0;
                    $this->Flash->error('Email address already exists.');
                }
                
                $checkUA = $this->Admins->find()->where(['Admins.email' => $data->email_address])->first();
                if($checkUA)
                {
                    $flagCheck = 0;
                    $this->Flash->error('Email address already exists.');
                }
            }
            
            if(count($data->getErrors()) == 0 && $flagCheck == 1){
               
                if(isset($this->request->getData()['Users']['password']) && $this->request->getData()['Users']['password'] !=''){
                    $new_password = $this->request->getData()['Users']['password'];
                    unset($this->request->getData()['Users']['password']);
                    $salt = uniqid(mt_rand(), true);
                    $password = crypt($new_password, '$2a$07$' . $salt . '$');
                    $data->password = $password;
                }
                
                if ($this->Users->save($data)) {
                    $this->Flash->success('School details updated successfully.');
                    $this->redirect(['controller'=>'users', 'action' => 'index']);
                }
                
            }else{
                if(empty($this->request->getData()['Users']['password'])){
                    $this->request->getData()['Users']['password'] = ''; 
                }
            }
        }else{
             $this->request->getData()['Users']['password'] = '';
        }
        $this->set('users', $users);
    }
    
    
    
    /* Teacher Module */
    
    public function teachers() {
        $this->set('title', ADMIN_TITLE. 'Manage Supervisors');
        $this->viewBuilder()->setLayout('admin');
        $this->set('manageTeachers', '1');
        $this->set('teacherList', '1');
        $this->set('canSuperAdminImpersonate', $this->canUseSuperAdminImpersonation($this->getLoggedInAdmin()));
        
        $separator = array();
        $condition = array();
        
        $condition[] = "(Users.user_type = 'Teacher_Parent')";

        $sessAdminHeaderSeasonId = (int)$this->request->getSession()->read('sess_admin_header_season_id');
        if ($sessAdminHeaderSeasonId > 0) {
            $convSeason = $this->Conventionseasons->find()
                ->where(['Conventionseasons.id' => $sessAdminHeaderSeasonId])
                ->first();

            if ($convSeason) {
                $registeredConventionRegistrationIds = $this->Conventionregistrations->find()
                    ->select(['Conventionregistrations.id'])
                    ->where([
                        'Conventionregistrations.conventionseason_id' => $convSeason->id,
                    ])
                    ->distinct(['Conventionregistrations.id'])
                    ->extract('id')
                    ->map(static function ($registrationId) {
                        return (int)$registrationId;
                    })
                    ->toList();

                if (!empty($registeredConventionRegistrationIds)) {
                    $registeredSupervisorIds = $this->Conventionregistrationteachers->find()
                        ->select(['Conventionregistrationteachers.teacher_id'])
                        ->where([
                            'Conventionregistrationteachers.conventionregistration_id IN' => $registeredConventionRegistrationIds,
                            'Conventionregistrationteachers.teacher_id >' => 0,
                        ])
                        ->distinct(['Conventionregistrationteachers.teacher_id'])
                        ->extract('teacher_id')
                        ->map(static function ($teacherId) {
                            return (int)$teacherId;
                        })
                        ->toList();

                    if (!empty($registeredSupervisorIds)) {
                        // Show only supervisors explicitly linked to the selected convention season.
                        $condition['Users.id IN'] = $registeredSupervisorIds;
                    } else {
                        $condition['Users.id'] = 0;
                    }
                } else {
                    $condition['Users.id'] = 0;
                }
            }
        }
        
        if($this->request->is('post')){
            if (isset($this->request->getData()['action'])) {
                $idList = implode(',', $this->request->getData()['chkRecordId']);
                $action = $this->request->getData()['action'];
                if ($idList) {
                    if ($action == "Activate") {
                        $this->Users->updateAll(['status' => '1'], ["id IN ($idList)"]);
                        $this->Flash->success('Records are activated successfully.');
                    } elseif ($action == "Deactivate") {
                        $this->Users->updateAll(['status' => '0'], ["id IN ($idList)"]);
                        $this->Flash->success('Records are deactivated successfully.');
                    } elseif ($action == "Delete") {
                        $this->Users->deleteAll(["id IN ($idList)"]);
                        $this->Flash->success('Records are deleted successfully.');
                    }
                }
            }
            
            if(isset($this->request->getData()['Users']['keyword']) && $this->request->getData()['Users']['keyword']!=''){
              $keyword = trim($this->request->getData()['Users']['keyword']); 
            }
        }elseif($this->request->getParam('pass')){
            if(isset($this->request->getParam('pass', [])[0]) && $this->request->getParam('pass', [])[0]!=''){
                $searchArr = $this->request->getParam('pass', []);
                foreach($searchArr as $val){
                if (strpos($val, ":") !== false) {
                   $vars  = explode(":",$val);
                   ${$vars[0]}   = urldecode($vars[1]);
                }
               }
            }
        }
        
        if (isset($keyword) && $keyword != '') {
             $separator[] = 'keyword:' . urlencode($keyword);
             $condition[] = "(Users.first_name LIKE '%".addslashes($keyword)."%' OR Users.last_name LIKE '%".addslashes($keyword)."%' OR  Users.email_address LIKE '%".addslashes($keyword)."%')";
             $this->set('keyword', $keyword);
        }
        
        $separator = implode("/", $separator); 
        $this->set('separator',$separator);
        $sortableFields = [
            'Users.first_name',
            'Users.middle_name',
            'Users.last_name',
            'Users.birth_year',
            'Users.gender',
            'Users.created',
            'Users.status',
            'Schools.first_name',
        ];

        $order = ['Users.id' => 'DESC'];
        $queryParams = (array)$this->request->getQueryParams();
        $schoolSortRaw = $queryParams['school_sort'] ?? '';
        $schoolSort = strtoupper((string)$schoolSortRaw);
        if ($schoolSort === 'ASC' || $schoolSort === 'DESC') {
            $order = ['Schools.first_name' => $schoolSort, 'Users.id' => 'DESC'];
        } else {
            $requestedSort = (string)($queryParams['sort'] ?? '');
            if ($requestedSort === 'Schools.first_name') {
                $requestedDirection = strtoupper((string)($queryParams['direction'] ?? ''));
                if ($requestedDirection !== 'ASC' && $requestedDirection !== 'DESC') {
                    $requestedDirection = 'ASC';
                }
                $order = ['Schools.first_name' => $requestedDirection, 'Users.id' => 'DESC'];
            }
        }

        $query = $this->Users->find()
            ->where($condition)
            ->contain(['Schools']);

        if ($schoolSort === 'ASC' || $schoolSort === 'DESC') {
            $schoolNameExpr = $query->newExpr('(SELECT s.first_name FROM users s WHERE s.id = Users.school_id)');
            $query->order([], true);
            if ($schoolSort === 'ASC') {
                $query->orderAsc($schoolNameExpr);
            } else {
                $query->orderDesc($schoolNameExpr);
            }
            $query->orderDesc('Users.id');
        } else {
            $query->order($order);
        }

        $this->paginate = [
            'limit' => 20,
            'sortWhitelist' => $sortableFields,
            'sortableFields' => $sortableFields,
        ];
        $users = $this->paginate($query);
        if ($schoolSort === 'ASC' || $schoolSort === 'DESC') {
            $sortedRows = $users->toList();
            $schoolIds = [];
            foreach ($sortedRows as $row) {
                $schoolId = (int)($row->school_id ?? 0);
                if ($schoolId > 0) {
                    $schoolIds[] = $schoolId;
                }
            }
            $schoolIds = array_values(array_unique($schoolIds));
            $schoolNameById = [];
            if (!empty($schoolIds)) {
                $schoolRows = $this->Users->find()
                    ->select(['id', 'first_name'])
                    ->where(['Users.id IN' => $schoolIds])
                    ->all();
                foreach ($schoolRows as $schoolRow) {
                    $schoolNameById[(int)$schoolRow->id] = strtolower(trim((string)$schoolRow->first_name));
                }
            }

            usort($sortedRows, static function ($a, $b) use ($schoolSort, $schoolNameById) {
                $schoolAId = (int)($a->school_id ?? 0);
                $schoolBId = (int)($b->school_id ?? 0);
                $schoolA = $schoolNameById[$schoolAId] ?? '';
                $schoolB = $schoolNameById[$schoolBId] ?? '';
                $compare = strcmp($schoolA, $schoolB);
                if ($compare === 0) {
                    // Keep newest records first within the same school name.
                    return ((int)$b->id <=> (int)$a->id);
                }
                return ($schoolSort === 'ASC') ? $compare : -$compare;
            });
            $users = new Collection($sortedRows);
        }

        $this->set('users', $users);
        if($this->request->is("ajax")){
            $this->viewBuilder()->disableAutoLayout();
            $this->viewBuilder()->setTemplatePath('Element' . DS . 'Admin/Users');
            $this->render('teachers');
        }
    }
    
    public function activateteacher($slug=null){
        if ($slug != '') {
            $this->viewBuilder()->disableAutoLayout();
            $this->Users->updateAll(['status' => '1'], ["slug"=>$slug]);
            $this->set('action', '/admin/users/deactivateteacher/' . $slug);
            $this->set('status', 1);
            $this->viewBuilder()->setTemplatePath('Element' . DS . 'Admin');
            $this->render('update_status');
        }
    }
    
    public function deactivateteacher($slug=null){
        if ($slug != '') {
            $this->viewBuilder()->disableAutoLayout();
            $this->Users->updateAll(['status' => '0'], ["slug"=>$slug]);
            $this->set('action', '/admin/users/activateteacher/' . $slug);
            $this->set('status', 0);
            $this->viewBuilder()->setTemplatePath('Element' . DS . 'Admin');
            $this->render('update_status');
        }
    }
    
    public function archiveteacher($slug=null) {
        $this->Users->updateAll(['status' => '2'], ["slug"=>$slug]);
        $this->Flash->success('Supervisors details archived successfully.');
        $this->redirect(['controller'=>'users', 'action' => 'teachers']);
    }
    
    public function restoreteacher($slug=null) {
        $this->Users->updateAll(['status' => '1'], ["slug"=>$slug]);
        $this->Flash->success('Supervisors details restored successfully.');
        $this->redirect(['controller'=>'users', 'action' => 'teachers']);
    }
    
    public function addteacher_noneed() {
        $this->set('title', ADMIN_TITLE . 'Add Supervisors');
        $this->viewBuilder()->setLayout('admin');
        $this->set('manageTeachers', '1');
        $this->set('teacherAdd', '1');
        
        $schoolsDD = $this->Users->find()->where(['Users.user_type' => 'School'])->order(['Users.first_name' => 'ASC'])->combine('id', 'first_name')->toArray();
        $this->set('schoolsDD', $schoolsDD);
        
        global $genderDD;
        $this->set('genderDD', $genderDD);
        
        global $yesNoDD;
        $this->set('yesNoDD', $yesNoDD);
        
        $users = $this->Users->newEntity([]);
        if ($this->request->is('post')) {
            
            $data = $this->Users->patchEntity($users, $this->request->getData());
            
            $flagC = 1;
            
            $checkEmailS = $this->Users->find()->where(['Users.email_address' => $data->email_address,'Users.school_id' => $data->school_id])->first();
            
            $checkEmailAdmin = $this->Admins->find()->where(['Admins.email' => $data->email_address])->first();
            if($checkEmailAdmin || $checkEmailS)
            {
                $flagC = 0;
                $this->Flash->error('Email already exists.');
            }
            
            if (count($data->getErrors()) == 0 && $flagC == 1) {

                $slug = $this->getSlug($this->request->getData()['Users']['first_name'] . ' ' . time(), 'Users');
                $data->slug = $slug;
                
                $data->user_type = 'Teacher_Parent';
                $data->status = 1;
                $data->activation_status = 0;
                $data->created = date('Y-m-d H:i:s');
                $data->modified = date('Y-m-d H:i:s');
                if ($this->Users->save($data)) {
                    $this->Flash->success('Supervisors details added successfully.');
                    $this->redirect(['controller' => 'users', 'action' => 'teachers']);
                }
                
            } else {
                // $this->Flash->error('Please below listed errors.');
            }
        }
        $this->set('users', $users);
    }
    
    public function editteacher($slug=null){
        $this->set('title', ADMIN_TITLE. 'Edit Supervisors');
        $this->viewBuilder()->setLayout('admin');
        
        $this->set('manageTeachers', '1');
        $this->set('teacherList', '1');
        
        $schoolsDD = $this->Users->find()->where(['Users.user_type' => 'School'])->order(['Users.first_name' => 'ASC'])->combine('id', 'first_name')->toArray();
        $this->set('schoolsDD', $schoolsDD);
        
        global $genderDD;
        $this->set('genderDD', $genderDD);
        
        global $yesNoDD;
        $this->set('yesNoDD', $yesNoDD);
       
        if($slug){
            $users1 = $this->Users->find()->where(['Users.slug' => $slug])->first();
            $uid = $users1->id;
        }
        $users = $this->Users->get($uid);
        if ($this->request->is(['post', 'put'])) {
            if(empty($this->request->getData()['Users']['password'])){
                unset($this->request->getData()['Users']['password']);
            }
            $data = $this->Users->patchEntity($users, $this->request->getData());

            $flagCheck = 1;
            if($data->email_address_old != $data->email_address)
            {
                $checkUE = $this->Users->find()->where(['Users.email_address' => $data->email_address, 'Users.id !=' => $data->id])->first();
                if($checkUE)
                {
                    $flagCheck = 0;
                    $this->Flash->error('Email address already exists.');
                }

                $checkUA = $this->Admins->find()->where(['Admins.email' => $data->email_address])->first();
                if($checkUA)
                {
                    $flagCheck = 0;
                    $this->Flash->error('Email address already exists.');
                }
            }
            
            if(count($data->getErrors()) == 0 && $flagCheck == 1){
               
                if(isset($this->request->getData()['Users']['password']) && $this->request->getData()['Users']['password'] !=''){
                    $new_password = $this->request->getData()['Users']['password'];
                    unset($this->request->getData()['Users']['password']);
                    $salt = uniqid(mt_rand(), true);
                    $password = crypt($new_password, '$2a$07$' . $salt . '$');
                    $data->password = $password;
                }
                
                if ($this->Users->save($data)) {
                    $this->Flash->success('Supervisors details updated successfully.');
                    $this->redirect(['controller'=>'users', 'action' => 'teachers']);
                }
                
            }else{
                if(empty($this->request->getData()['Users']['password'])){
                    $this->request->getData()['Users']['password'] = ''; 
                }
            }
        }else{
             $this->request->getData()['Users']['password'] = '';
        }
        $this->set('users', $users);
    }
    
    
    /* Student module starts */
    
    public function students() {
        $this->set('title', ADMIN_TITLE. 'Manage Students');
        $this->viewBuilder()->setLayout('admin');
        $this->set('manageStudents', '1');
        $this->set('studentList', '1');
        
        $separator = array();
        $condition = array();
        
        $condition[] = "(Users.user_type = 'Student')";

        $sessAdminHeaderSeasonId = (int)$this->request->getSession()->read('sess_admin_header_season_id');
        if ($sessAdminHeaderSeasonId > 0) {
            $convSeason = $this->Conventionseasons->find()
                ->where(['Conventionseasons.id' => $sessAdminHeaderSeasonId])
                ->first();

            if ($convSeason) {
                $registeredConventionRegistrationIds = $this->Conventionregistrations->find()
                    ->select(['Conventionregistrations.id'])
                    ->where([
                        'Conventionregistrations.conventionseason_id' => $convSeason->id,
                    ])
                    ->distinct(['Conventionregistrations.id'])
                    ->extract('id')
                    ->map(static function ($registrationId) {
                        return (int)$registrationId;
                    })
                    ->toList();

                if (!empty($registeredConventionRegistrationIds)) {
                    $registeredStudentIds = $this->Conventionregistrationstudents->find()
                        ->select(['Conventionregistrationstudents.student_id'])
                        ->where([
                            'Conventionregistrationstudents.conventionregistration_id IN' => $registeredConventionRegistrationIds,
                            'Conventionregistrationstudents.student_id >' => 0,
                        ])
                        ->distinct(['Conventionregistrationstudents.student_id'])
                        ->extract('student_id')
                        ->map(static function ($studentId) {
                            return (int)$studentId;
                        })
                        ->toList();

                    if (!empty($registeredStudentIds)) {
                        // Show only students linked to the selected convention season.
                        $condition['Users.id IN'] = $registeredStudentIds;
                    } else {
                        $condition['Users.id'] = 0;
                    }
                } else {
                    $condition['Users.id'] = 0;
                }
            }
        }
        
        if($this->request->is('post')){
            if (isset($this->request->getData()['action'])) {
                $idList = implode(',', $this->request->getData()['chkRecordId']);
                $action = $this->request->getData()['action'];
                if ($idList) {
                    if ($action == "Activate") {
                        $this->Users->updateAll(['status' => '1'], ["id IN ($idList)"]);
                        $this->Flash->success('Records are activated successfully.');
                    } elseif ($action == "Deactivate") {
                        $this->Users->updateAll(['status' => '0'], ["id IN ($idList)"]);
                        $this->Flash->success('Records are deactivated successfully.');
                    } elseif ($action == "Delete") {
                        $this->Users->deleteAll(["id IN ($idList)"]);
                        $this->Flash->success('Records are deleted successfully.');
                    }
                }
            }
            
            if(isset($this->request->getData()['Users']['keyword']) && $this->request->getData()['Users']['keyword']!=''){
              $keyword = trim($this->request->getData()['Users']['keyword']); 
            }
        }elseif($this->request->getParam('pass')){
            if(isset($this->request->getParam('pass', [])[0]) && $this->request->getParam('pass', [])[0]!=''){
                $searchArr = $this->request->getParam('pass', []);
                foreach($searchArr as $val){
                if (strpos($val, ":") !== false) {
                   $vars  = explode(":",$val);
                   ${$vars[0]}   = urldecode($vars[1]);
                }
               }
            }
        }
        
        if (isset($keyword) && $keyword != '') {
             $separator[] = 'keyword:' . urlencode($keyword);
             $condition[] = "(Users.first_name LIKE '%".addslashes($keyword)."%' OR Users.middle_name LIKE '%".addslashes($keyword)."%' OR  Users.last_name LIKE '%".addslashes($keyword)."%' OR  Users.email_address LIKE '%".addslashes($keyword)."%')";
             $this->set('keyword', $keyword);
        }
        
        $separator = implode("/", $separator); 
        $this->set('separator',$separator);

        $queryParams = (array)$this->request->getQueryParams();
        $schoolSort = strtoupper((string)($queryParams['school_sort'] ?? ''));

        $order = ['Users.id' => 'DESC'];
        if ($schoolSort === 'ASC' || $schoolSort === 'DESC') {
            $order = ['Schools.first_name' => $schoolSort, 'Users.id' => 'DESC'];
        }

        $usersQuery = $this->Users->find()
            ->contain(['Schools'])
            ->where($condition);

        if ($schoolSort === 'ASC' || $schoolSort === 'DESC') {
            $usersQuery
                ->leftJoin(
                    ['SchoolsSort' => 'users'],
                    ['SchoolsSort.id = Users.school_id']
                )
                ->order([
                    'SchoolsSort.first_name' => $schoolSort,
                    'Users.id' => 'DESC',
                ]);
        } else {
            $usersQuery->order($order);
        }

        $this->paginate = [
            'limit' => 20,
        ];

        $users = $this->paginate($usersQuery);
        $this->set('users', $users);
        if($this->request->is("ajax")){
            $this->viewBuilder()->disableAutoLayout();
            $this->viewBuilder()->setTemplatePath('Element' . DS . 'Admin/Users');
            $this->render('students');
        }
    }
    
    public function activatestudent($slug=null){
        if ($slug != '') {
            $this->viewBuilder()->disableAutoLayout();
            $this->Users->updateAll(['status' => '1'], ["slug"=>$slug]);
            $this->set('action', '/admin/users/deactivateparent/' . $slug);
            $this->set('status', 1);
            $this->viewBuilder()->setTemplatePath('Element' . DS . 'Admin');
            $this->render('update_status');
        }
    }
    
    public function deactivatestudent($slug=null){
        if ($slug != '') {
            $this->viewBuilder()->disableAutoLayout();
            $this->Users->updateAll(['status' => '0'], ["slug"=>$slug]);
            $this->set('action', '/admin/users/activateparent/' . $slug);
            $this->set('status', 0);
            $this->viewBuilder()->setTemplatePath('Element' . DS . 'Admin');
            $this->render('update_status');
        }
    }
    
    public function archivestudent($slug=null) {
        $this->Users->updateAll(['status' => '2'], ["slug"=>$slug]);
        $this->Flash->success('Student details archived successfully.');
        $this->redirect(['controller'=>'users', 'action' => 'students']);
    }
    
    public function restorestudent($slug=null) {
        $this->Users->updateAll(['status' => '1'], ["slug"=>$slug]);
        $this->Flash->success('Students details restored successfully.');
        $this->redirect(['controller'=>'users', 'action' => 'students']);
    }
    
    public function editstudent($slug=null){
        $this->set('title', ADMIN_TITLE. 'Edit Student');
        $this->viewBuilder()->setLayout('admin');
        
        $this->set('manageStudents', '1');
        $this->set('studentList', '1');
        
        global $genderDD;
        $this->set('genderDD', $genderDD);
        
        global $birthYearDD;
        $this->set('birthYearDD', $birthYearDD);
        
        $schoolsDD = $this->Users->find()->where(['Users.user_type' => 'School'])->order(['Users.first_name' => 'ASC'])->combine('id', 'first_name')->toArray();
        $this->set('schoolsDD', $schoolsDD);
       
        if($slug){
            $users1 = $this->Users->find()->where(['Users.slug' => $slug])->first();
            $uid = $users1->id;
        }
        $users = $this->Users->get($uid);
        if ($this->request->is(['post', 'put'])) {
            if(empty($this->request->getData()['Users']['password'])){
                unset($this->request->getData()['Users']['password']);
            }
            $data = $this->Users->patchEntity($users, $this->request->getData(), ['validate' => 'edit']);
            
            if(count($data->getErrors()) == 0){
               
                if(isset($this->request->getData()['Users']['password']) && $this->request->getData()['Users']['password'] !=''){
                    $new_password = $this->request->getData()['Users']['password'];
                    unset($this->request->getData()['Users']['password']);
                    $salt = uniqid(mt_rand(), true);
                    $password = crypt($new_password, '$2a$07$' . $salt . '$');
                    $data->password = $password;
                }
                
                if ($this->Users->save($data)) {
                    $this->Flash->success('Student details updated successfully.');
                    $this->redirect(['controller'=>'users', 'action' => 'students']);
                }
                
            }else{
                if(empty($this->request->getData()['Users']['password'])){
                    $this->request->getData()['Users']['password'] = ''; 
                }
            }
        }else{
             $this->request->getData()['Users']['password'] = '';
        }
        $this->set('users', $users);
    }
    
    
    /* CSV Functions */
    public function downloadcsvformat() {
        
        $filename = "schools_standard_csv_format.csv";
        $dataArray = array();
        
        $dataArray[] = array('7732','TEMP001','A B Customer','38815777','','mikaelawaqa@accelerate.edu.au','8-12 Business Drive','','Narangba','4504','AU');
        $dataArray[] = array('7733','TEMP002','X Y Customer','34532456','','abc@accelerate.edu.au','12-13 Business Drive','','Narangba','4504','AU');
        
        $delimiter = ",";
         
        $f = fopen('php://memory', 'w'); 
         
        $fields = array('#','BP Code','BP Name','Telephone 1','Telephone 2','E-Mail','Bill-to Street','Bill-to Block','Bill-to City','Bill-to Zip Code','Bill-to Country');
        fputcsv($f, $fields, $delimiter); 
         
        foreach($dataArray as $datarecord)
        {	
            fputcsv($f, $datarecord, $delimiter);
        } 
         
        fseek($f, 0); 
         
        header('Content-Type: text/csv'); 
        header('Content-Disposition: attachment; filename="' . $filename . '";'); 
         
        fpassthru($f); 
        
        exit;
    }
    
    public function csvimport() {
        $this->set('title', ADMIN_TITLE . 'Import CSV');
        $this->viewBuilder()->setLayout('admin');
        $this->set('manageSchools', '1');
        $this->set('schoolImport', '1');
        
        $users = $this->Users->newEntity([]);
        if ($this->request->is('post')) {
            
            $data = $this->Users->patchEntity($users, $this->request->getData());
            if (count($data->getErrors()) == 0) {

                if(!empty($this->request->getData()['Users']['csv_file']['name'])){
                    $specialCharacters = array('#', '$', '%', '@', '+', '=', '\\', '/', '"', ' ', "'", ':', '~', '`', '!', '^', '*', '(', ')', '|', "'", "&");
                    $toReplace = "-";
                    $this->request->getData()['Users']['csv_file']['name'] = str_replace($specialCharacters, $toReplace, $this->request->getData()['Users']['csv_file']['name']);
                    $imageArray = $this->request->getData()['Users']['csv_file'];
                    $returnedUploadImageArray = $this->PImage->upload($imageArray, UPLOAD_SCHOOLS_CSV_PATH);                     
                     
                    $csv_file_system_name 	=  $returnedUploadImageArray[0];
                    $csv_original_name 		= 	$this->request->getData()['Users']['csv_file']['name'];
                    
                    $filename = UPLOAD_SCHOOLS_CSV_PATH.$csv_file_system_name;
                    
                    $cntrTotalRecords 		= 0;
                    $cntrCustCodeExists 	= 0;
                    $cntrCustEmailExists 	= 0;
                    $cntrRecordsImport 		= 0;
                    
                    $file = fopen($filename, "r");
                    while (($getData = fgetcsv($file, 10000, ",")) !== FALSE)
                    {
                        if($cntrTotalRecords>0)
                        {	
                            $flagCheck = 1;
                            
                            $checkCCode = $this->Users->find()->where(['Users.customer_code' => $getData[1]])->first();
                            if($checkCCode)
                            {
                                $cntrCustCodeExists++;
                                $flagCheck = 0;
                            }
                            
                            if($flagCheck == 1)
                            {
                                $checkEmail = $this->Users->find()->where(['Users.email_address' => $getData[5]])->first();
                                if($checkEmail)
                                {
                                    $cntrCustEmailExists++;
                                    $flagCheck = 0;
                                }
                            }
                            
                            if($flagCheck == 1)
                            {
                                $users = $this->Users->newEntity([]);
                                $dataU = $this->Users->patchEntity($users, array());
                                
                                $dataU->slug 							= $this->getSlug($getData[2] . ' ' . time(), 'Users');
                                $dataU->user_type						= "School";
                                $dataU->status							= 1;
                                $dataU->activation_status				= 0;
                                
                                $dataU->customer_hash_from_csv			= $getData[0];
                                $dataU->customer_code					= $getData[1];
                                $dataU->first_name						= $getData[2];
                                $dataU->phone							= $getData[3];
                                $dataU->phone2							= $getData[4];
                                $dataU->email_address					= $getData[5];
                                $dataU->bill_to_street					= $getData[6];
                                $dataU->bill_to_block					= $getData[7];
                                $dataU->bill_to_city					= $getData[8];
                                $dataU->bill_to_zip						= $getData[9];
                                $dataU->bill_to_country					= $getData[10];
                                
                                $user_password = rand(1000,33455678899000);
                                $salt = uniqid(mt_rand(), true);
                                $dataU->password = crypt($user_password, '$2a$07$' . $salt . '$');
                                $dataU->created 				= date('Y-m-d H:i:s');

                                $resultU = $this->Users->save($dataU);
                                
                                $cntrRecordsImport++;
                            }
                        }
                        
                        $cntrTotalRecords++;
                    }
                    
                    @unlink($filename);
                    
                    $this->Flash->success("Total records in csv file = ".($cntrTotalRecords-1));
                    if($cntrCustCodeExists > 0)
                    {
                        $this->Flash->error("Customer code already exists = ".$cntrCustCodeExists);
                    }
                    
                    if($cntrCustEmailExists > 0)
                    {
                        $this->Flash->error("Email already exists = ".$cntrCustEmailExists);
                    }
                    
                    $this->Flash->success("Total records import = ".$cntrRecordsImport);
                    $this->redirect(['controller'=>'users', 'action' => 'index']);
                     
                }
                else
                {
                    $this->Flash->error('CSV import process failed.');
                    $this->redirect(['controller'=>'users', 'action' => 'index']);
                }
            }
        }
        $this->set('users', $users);
    }
    
    
    /*Judges Module */
    
    public function judges() {
        $this->set('title', ADMIN_TITLE. ' Judges');
        $this->viewBuilder()->setLayout('admin');
        $this->set('manageJudges', '1');
        $this->set('activeJudges', '1');
        $this->set('canSuperAdminImpersonate', $this->canUseSuperAdminImpersonation($this->getLoggedInAdmin()));
        
        $separator = array();
        $condition = array();
        
        $condition[] = "(Users.status = '0' OR Users.status = '1' OR Users.status = '2')";
        $condition[] = "(Users.user_type = 'Judge' OR (Users.user_type = 'Teacher_Parent' AND Users.is_judge = '1'))";

        $sessAdminHeaderSeasonId = (int)$this->request->getSession()->read('sess_admin_header_season_id');
        if ($sessAdminHeaderSeasonId > 0) {
            $convSeason = $this->Conventionseasons->find()
                ->where(['Conventionseasons.id' => $sessAdminHeaderSeasonId])
                ->first();

            if ($convSeason) {
                $registeredJudgeUserIds = $this->Conventionregistrations->find()
                    ->select(['Conventionregistrations.user_id'])
                    ->where([
                        'Conventionregistrations.conventionseason_id' => $convSeason->id,
                        'Conventionregistrations.user_id >' => 0,
                    ])
                    ->distinct(['Conventionregistrations.user_id'])
                    ->extract('user_id')
                    ->map(static function ($userId) {
                        return (int)$userId;
                    })
                    ->toList();

                if (!empty($registeredJudgeUserIds)) {
                    // Show judges registered against this selected convention season.
                    $condition['Users.id IN'] = $registeredJudgeUserIds;
                } else {
                    $condition['Users.id'] = 0;
                }
            }
        }
        
        if($this->request->is('post')){
            if (isset($this->request->getData()['action'])) {
                $idList = implode(',', $this->request->getData()['chkRecordId']);
                $action = $this->request->getData()['action'];
                if ($idList) {
                    if ($action == "Activate") {
                        $this->Users->updateAll(['status' => '1'], ["id IN ($idList)"]);
                        $this->Flash->success('Records are activated successfully.');
                    } elseif ($action == "Deactivate") {
                        $this->Users->updateAll(['status' => '0'], ["id IN ($idList)"]);
                        $this->Flash->success('Records are deactivated successfully.');
                    } elseif ($action == "Delete") {
                        $this->Users->deleteAll(["id IN ($idList)"]);
                        $this->Flash->success('Records are deleted successfully.');
                    }
                }
            }
            
            if(isset($this->request->getData()['Users']['keyword']) && $this->request->getData()['Users']['keyword']!=''){
              $keyword = trim($this->request->getData()['Users']['keyword']); 
            }
        }elseif($this->request->getParam('pass')){
            if(isset($this->request->getParam('pass', [])[0]) && $this->request->getParam('pass', [])[0]!=''){
                $searchArr = $this->request->getParam('pass', []);
                foreach($searchArr as $val){
                if (strpos($val, ":") !== false) {
                   $vars  = explode(":",$val);
                   ${$vars[0]}   = urldecode($vars[1]);
                }
               }
            }
        }
        
        if (isset($keyword) && $keyword != '') {
             $separator[] = 'keyword:' . urlencode($keyword);
             $condition[] = "(Users.first_name LIKE '%".addslashes($keyword)."%' OR Users.last_name LIKE '%".addslashes($keyword)."%' OR  Users.email_address LIKE '%".addslashes($keyword)."%')";
             $this->set('keyword', $keyword);
        }
        
        $separator = implode("/", $separator); 
        $this->set('separator',$separator);
        $this->paginate = ['contain'=>['Schools'],'conditions' => $condition, 'limit' => 50, 'order' => ['Users.id' => 'DESC']];
        $this->set('users', $this->paginate($this->Users));
        if($this->request->is("ajax")){
            $this->viewBuilder()->disableAutoLayout();
            $this->viewBuilder()->setTemplatePath('Element' . DS . 'Admin/Users');
            $this->render('judges');
        }
    }

    public function activatejudgeaccount($slug=null) {
        $judgeD = $this->Users->find()->where([
            'Users.slug' => $slug,
            '(Users.user_type = "Judge" OR (Users.user_type = "Teacher_Parent" AND Users.is_judge = "1"))',
            'Users.status IN' => [0, 1, 2]
        ])->first();

        if($judgeD)
        {
            $this->Users->updateAll([
                'activation_status' => '1',
                'status' => '1',
                'modified' => date('Y-m-d H:i:s', time())
            ], ['slug' => $slug]);

            $this->Flash->success('Judge account activated successfully. The judge can now log in without email verification.');
        }
        else
        {
            $this->Flash->error('Invalid action.');
        }

        return $this->redirect(['controller'=>'users', 'action' => 'judges']);
    }

    public function verifyaccount($slug=null, $returnAction='index') {
        $allowedActions = ['index', 'teachers', 'judges', 'pendingjudges'];
        $user = $this->Users->find()->where([
            'Users.slug' => $slug,
            'Users.user_type IN' => ['School', 'Teacher_Parent', 'Judge']
        ])->first();

        if ($user && in_array($returnAction, $allowedActions, true)) {
            $this->Users->updateAll([
                'activation_status' => '1',
                'modified' => date('Y-m-d H:i:s')
            ], ['id' => $user->id]);
            $this->Flash->success('Account verified successfully.');
        } else {
            $this->Flash->error('Invalid action.');
            $returnAction = 'index';
        }

        return $this->redirect(['controller' => 'users', 'action' => $returnAction]);
    }

    public function unverifyaccount($slug=null, $returnAction='index') {
        $allowedActions = ['index', 'teachers', 'judges', 'pendingjudges'];
        $user = $this->Users->find()->where([
            'Users.slug' => $slug,
            'Users.user_type IN' => ['School', 'Teacher_Parent', 'Judge']
        ])->first();

        if ($user && in_array($returnAction, $allowedActions, true)) {
            $this->Users->updateAll([
                'activation_status' => '0',
                'modified' => date('Y-m-d H:i:s')
            ], ['id' => $user->id]);
            $this->Flash->success('Account marked as unverified.');
        } else {
            $this->Flash->error('Invalid action.');
            $returnAction = 'index';
        }

        return $this->redirect(['controller' => 'users', 'action' => $returnAction]);
    }

    public function activatejudge($slug=null) {
        return $this->updateJudgeStatus($slug, 1);
    }

    public function deactivatejudge($slug=null) {
        return $this->updateJudgeStatus($slug, 0);
    }

    private function updateJudgeStatus($slug, $status) {
        $judge = $this->Users->find()->where([
            'Users.slug' => $slug,
            '(Users.user_type = "Judge" OR (Users.user_type = "Teacher_Parent" AND Users.is_judge = "1"))',
            'Users.status IN' => [0, 1, 2]
        ])->first();

        if ($judge) {
            $this->Users->updateAll([
                'status' => (string)$status,
                'modified' => date('Y-m-d H:i:s')
            ], ['id' => $judge->id]);
            $this->Flash->success($status ? 'Judge account activated successfully.' : 'Judge account deactivated successfully.');
        } else {
            $this->Flash->error('Invalid action.');
        }

        return $this->redirect(['controller' => 'users', 'action' => 'judges']);
    }

    public function editjudge($slug=null){
        $this->set('title', ADMIN_TITLE. 'Edit Judge');
        $this->viewBuilder()->setLayout('admin');

        $this->set('manageJudges', '1');
        $this->set('activeJudges', '1');

        $schoolsDD = $this->Users->find()->where(['Users.user_type' => 'School'])->order(['Users.first_name' => 'ASC'])->combine('id', 'first_name')->toArray();
        $this->set('schoolsDD', $schoolsDD);

        global $genderDD;
        $this->set('genderDD', $genderDD);

        global $yesNoDD;
        $this->set('yesNoDD', $yesNoDD);

        if($slug){
            $users1 = $this->Users->find()->where([
                'Users.slug' => $slug,
                "(Users.user_type = 'Judge' OR (Users.user_type = 'Teacher_Parent' AND Users.is_judge = '1'))"
            ])->first();
            if(!$users1){
                $this->Flash->error('Invalid judge record.');
                return $this->redirect(['controller'=>'users', 'action' => 'judges']);
            }
            $uid = $users1->id;
        }

        $users = $this->Users->get($uid);
        if ($this->request->is(['post', 'put'])) {
            if(empty($this->request->getData()['Users']['password'])){
                unset($this->request->getData()['Users']['password']);
            }
            $data = $this->Users->patchEntity($users, $this->request->getData());

            $flagCheck = 1;
            if($data->email_address_old != $data->email_address)
            {
                $checkUE = $this->Users->find()->where(['Users.email_address' => $data->email_address, 'Users.id !=' => $data->id])->first();
                if($checkUE)
                {
                    $flagCheck = 0;
                    $this->Flash->error('Email address already exists.');
                }

                $checkUA = $this->Admins->find()->where(['Admins.email' => $data->email_address])->first();
                if($checkUA)
                {
                    $flagCheck = 0;
                    $this->Flash->error('Email address already exists.');
                }
            }

            if(count($data->getErrors()) == 0 && $flagCheck == 1){

                if(isset($this->request->getData()['Users']['password']) && $this->request->getData()['Users']['password'] !=''){
                    $new_password = $this->request->getData()['Users']['password'];
                    unset($this->request->getData()['Users']['password']);
                    $salt = uniqid(mt_rand(), true);
                    $password = crypt($new_password, '$2a$07$' . $salt . '$');
                    $data->password = $password;
                }

                if ($this->Users->save($data)) {
                    $this->Flash->success('Judge details updated successfully.');
                    return $this->redirect(['controller'=>'users', 'action' => 'judges']);
                }

            }else{
                if(empty($this->request->getData()['Users']['password'])){
                    $this->request->getData()['Users']['password'] = '';
                }
            }
        }else{
             $this->request->getData()['Users']['password'] = '';
        }
        $this->set('users', $users);
    }
    
    public function pendingjudges() {
        $this->set('title', ADMIN_TITLE. ' Pending Judges');
        $this->viewBuilder()->setLayout('admin');
        $this->set('manageJudges', '1');
        $this->set('pendingJudges', '1');
        
        $separator = array();
        $condition = array();
        
        $condition[] = "( 
            (Users.user_type = 'Judge' AND Users.status = '0' AND Users.activation_status = '1')
            OR 
            (Users.user_type = 'Teacher_Parent' AND Users.is_judge = '2' AND Users.status = '1' AND Users.activation_status = '1') 
            )";
        
        if($this->request->is('post')){
            if (isset($this->request->getData()['action'])) {
                $idList = implode(',', $this->request->getData()['chkRecordId']);
                $action = $this->request->getData()['action'];
                if ($idList) {
                    if ($action == "Activate") {
                        $this->Users->updateAll(['status' => '1'], ["id IN ($idList)"]);
                        $this->Flash->success('Records are activated successfully.');
                    } elseif ($action == "Deactivate") {
                        $this->Users->updateAll(['status' => '0'], ["id IN ($idList)"]);
                        $this->Flash->success('Records are deactivated successfully.');
                    } elseif ($action == "Delete") {
                        $this->Users->deleteAll(["id IN ($idList)"]);
                        $this->Flash->success('Records are deleted successfully.');
                    }
                }
            }
            
            if(isset($this->request->getData()['Users']['keyword']) && $this->request->getData()['Users']['keyword']!=''){
              $keyword = trim($this->request->getData()['Users']['keyword']); 
            }
        }elseif($this->request->getParam('pass')){
            if(isset($this->request->getParam('pass', [])[0]) && $this->request->getParam('pass', [])[0]!=''){
                $searchArr = $this->request->getParam('pass', []);
                foreach($searchArr as $val){
                if (strpos($val, ":") !== false) {
                   $vars  = explode(":",$val);
                   ${$vars[0]}   = urldecode($vars[1]);
                }
               }
            }
        }
        
        if (isset($keyword) && $keyword != '') {
             $separator[] = 'keyword:' . urlencode($keyword);
             $condition[] = "(Users.first_name LIKE '%".addslashes($keyword)."%' OR Users.last_name LIKE '%".addslashes($keyword)."%' OR  Users.email_address LIKE '%".addslashes($keyword)."%')";
             $this->set('keyword', $keyword);
        }
        
        $separator = implode("/", $separator); 
        $this->set('separator',$separator);
        $this->paginate = ['contain'=>['Schools'],'conditions' => $condition, 'limit' => 50, 'order' => ['Users.id' => 'DESC']];
        $this->set('users', $this->paginate($this->Users));
        if($this->request->is("ajax")){
            $this->viewBuilder()->disableAutoLayout();
            $this->viewBuilder()->setTemplatePath('Element' . DS . 'Admin/Users');
            $this->render('pendingjudges');
        }
    }
    
    public function approvejudge($slug=null) {
        
        $judgeD = $this->Users->find()->where(['Users.slug' => $slug,'Users.status' => 0])->first();
        if($judgeD)
        {
            $this->Users->updateAll(['status' => '1','modified' => date('Y-m-d H:i:s', time())], ["slug"=>$slug]);
            
            $emailId = $judgeD->email_address;
                            
            $emailtemplateMessage = $this->Emailtemplates->find()->where(['Emailtemplates.id' => '13'])->first();
            
            $LINK = HTTP_PATH."/users/login/";

            $toRepArray = array('[!SITE_TITLE!]','[!first_name!]','[!LINK!]');
            $fromRepArray = array(SITE_TITLE,$judgeD->first_name,$LINK);

            $subjectToSend = str_replace($toRepArray, $fromRepArray, $emailtemplateMessage['subject']);
            $messageToSend = str_replace($toRepArray, $fromRepArray, $emailtemplateMessage['template']);
            
            $email = new Email();
            $email->setEmailFormat('html')
                ->setTo($emailId)
                ->setCc(ACCOUNTS_TEAM_ANOTHER_EMAIL)
                ->setFrom([HEADERS_FROM_EMAIL => HEADERS_FROM_NAME])
                ->setSubject($subjectToSend)
                ->send($messageToSend);
            
            $this->Flash->success('Judge details approved successfully.');
        
        }
        else
        {
            $this->Flash->error('Invalid action.');
        }
        $this->redirect(['controller'=>'users', 'action' => 'pendingjudges']);
    }
    
    public function rejectjudge($slug=null) {
        
        $judgeD = $this->Users->find()->where(['Users.slug' => $slug,'Users.status' => 0])->first();
        if($judgeD)
        {
            $this->Users->updateAll(['status' => '3','modified' => date('Y-m-d H:i:s', time())], ["slug"=>$slug]);
            
            $emailId = $judgeD->email_address;
                            
            $emailtemplateMessage = $this->Emailtemplates->find()->where(['Emailtemplates.id' => '14'])->first();

            $toRepArray = array('[!SITE_TITLE!]','[!first_name!]');
            $fromRepArray = array(SITE_TITLE,$judgeD->first_name);

            $subjectToSend = str_replace($toRepArray, $fromRepArray, $emailtemplateMessage['subject']);
            $messageToSend = str_replace($toRepArray, $fromRepArray, $emailtemplateMessage['template']);
            
            $email = new Email();
            $email->setEmailFormat('html')
                ->setTo($emailId)
                ->setCc(ACCOUNTS_TEAM_ANOTHER_EMAIL)
                ->setFrom([HEADERS_FROM_EMAIL => HEADERS_FROM_NAME])
                ->setSubject($subjectToSend)
                ->send($messageToSend);
            
            $this->Flash->success('Judge details approved successfully.');
        
        }
        else
        {
            $this->Flash->error('Invalid action.');
        }
        $this->redirect(['controller'=>'users', 'action' => 'pendingjudges']);
    }
    
    public function archivejudge($slug=null) {
        $this->Users->updateAll(['status' => '2'], ["slug"=>$slug]);
        $this->Flash->success('Judge details archived successfully.');
        $this->redirect(['controller'=>'users', 'action' => 'judges']);
    }
    
    public function restorejudge($slug=null) {
        $this->Users->updateAll(['status' => '1'], ["slug"=>$slug]);
        $this->Flash->success('Judge details restored successfully.');
        $this->redirect(['controller'=>'users', 'action' => 'judges']);
    }
    
    public function approvesupervisorasjudge($slug=null) {
        
        $judgeD = $this->Users->find()->where(['Users.slug' => $slug,'Users.is_judge' => 2])->first();
        if($judgeD)
        {
            $this->Users->updateAll(['is_judge' => '1','modified' => date('Y-m-d H:i:s', time())], ["slug"=>$slug]);
            
            $emailId = $judgeD->email_address;
                            
            $emailtemplateMessage = $this->Emailtemplates->find()->where(['Emailtemplates.id' => '16'])->first();
            
            $LINK = HTTP_PATH."/users/login/";

            $toRepArray = array('[!SITE_TITLE!]','[!first_name!]','[!LINK!]');
            $fromRepArray = array(SITE_TITLE,$judgeD->first_name,$LINK);

            $subjectToSend = str_replace($toRepArray, $fromRepArray, $emailtemplateMessage['subject']);
            $messageToSend = str_replace($toRepArray, $fromRepArray, $emailtemplateMessage['template']);
            
            $email = new Email();
            $email->setEmailFormat('html')
                ->setTo($emailId)
                ->setCc(ACCOUNTS_TEAM_ANOTHER_EMAIL)
                ->setFrom([HEADERS_FROM_EMAIL => HEADERS_FROM_NAME])
                ->setSubject($subjectToSend)
                ->send($messageToSend);
            
            $this->Flash->success('Supervisor account successfully approvedas judge.');
        
        }
        else
        {
            $this->Flash->error('Invalid action.');
        }
        $this->redirect(['controller'=>'users', 'action' => 'pendingjudges']);
    }
    
    public function rejectsupervisorasjudge($slug=null) {
        
        $judgeD = $this->Users->find()->where(['Users.slug' => $slug,'Users.is_judge' => 2])->first();
        if($judgeD)
        {
            $this->Users->updateAll(['is_judge' => '3','modified' => date('Y-m-d H:i:s', time())], ["slug"=>$slug]);
            
            $emailId = $judgeD->email_address;
                            
            $emailtemplateMessage = $this->Emailtemplates->find()->where(['Emailtemplates.id' => '17'])->first();

            $toRepArray = array('[!SITE_TITLE!]','[!first_name!]');
            $fromRepArray = array(SITE_TITLE,$judgeD->first_name);

            $subjectToSend = str_replace($toRepArray, $fromRepArray, $emailtemplateMessage['subject']);
            $messageToSend = str_replace($toRepArray, $fromRepArray, $emailtemplateMessage['template']);
            
            $email = new Email();
            $email->setEmailFormat('html')
                ->setTo($emailId)
                ->setCc(ACCOUNTS_TEAM_ANOTHER_EMAIL)
                ->setFrom([HEADERS_FROM_EMAIL => HEADERS_FROM_NAME])
                ->setSubject($subjectToSend)
                ->send($messageToSend);
            
            $this->Flash->success('Judge details approved successfully.');
        
        }
        else
        {
            $this->Flash->error('Invalid action.');
        }
        $this->redirect(['controller'=>'users', 'action' => 'pendingjudges']);
    }

    public function loginasuser($userId = null)
    {
        $admin = $this->getLoggedInAdmin();
        if (!$this->canUseSuperAdminImpersonation($admin)) {
            $this->Flash->error('You do not have access to use Login As.');
            return $this->redirect(['controller' => 'users', 'action' => 'index']);
        }

        $targetUserId = (int)$userId;
        if ($targetUserId <= 0) {
            $this->Flash->error('Invalid user selection.');
            return $this->redirect(['controller' => 'users', 'action' => 'index']);
        }

        $targetUser = $this->Users->find()
            ->where([
                'Users.id' => $targetUserId,
                'Users.user_type IN' => ['School', 'Teacher_Parent', 'Judge'],
                'Users.status IN' => [0, 1]
            ])
            ->first();

        if (!$targetUser) {
            $this->Flash->error('Selected account is not available for Login As.');
            return $this->redirect(['controller' => 'users', 'action' => 'index']);
        }

        $session = $this->request->getSession();
        $session->write('admin_impersonation', [
            'active' => 1,
            'admin_id' => (int)$admin->id,
            'admin_username' => (string)$admin->username,
            'started' => date('Y-m-d H:i:s'),
            'target_user_id' => (int)$targetUser->id,
            'target_user_type' => (string)$targetUser->user_type,
        ]);

        $session->write('user_id', (int)$targetUser->id);
        $session->write('user_type', (string)$targetUser->user_type);

        if ($targetUser->user_type === 'Teacher_Parent') {
            $session->write('current_session_profile_type', 'Supervisor');
        } elseif ($targetUser->user_type === 'Judge') {
            $session->write('current_session_profile_type', 'Judge');
        } else {
            $session->delete('current_session_profile_type');
        }

        $session->delete('sess_selected_convention_registration_id');
        $session->delete('sess_selected_convention_id');

        $this->Flash->success('You are now logged in as '.$targetUser->first_name.'.');
        return $this->redirect(['prefix' => false, 'controller' => 'users', 'action' => 'dashboard']);
    }

    public function stopimpersonation()
    {
        $session = $this->request->getSession();
        $impersonation = (array)$session->read('admin_impersonation');

        if (empty($impersonation) || (int)($impersonation['active'] ?? 0) !== 1) {
            $this->Flash->error('No active Login As session found.');
            return $this->redirect(['controller' => 'admins', 'action' => 'dashboard']);
        }

        $session->delete('user_id');
        $session->delete('user_type');
        $session->delete('current_session_profile_type');
        $session->delete('sess_selected_convention_registration_id');
        $session->delete('sess_selected_convention_id');
        $session->delete('admin_impersonation');

        $this->Flash->success('Returned to admin session.');
        return $this->redirect(['controller' => 'admins', 'action' => 'dashboard']);
    }
    
}