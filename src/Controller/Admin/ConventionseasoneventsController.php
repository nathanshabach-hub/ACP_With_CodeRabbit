<?php

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;

#[\AllowDynamicProperties]
class ConventionseasoneventsController extends AppController {

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
    }
	
	public function allevents() {

        $this->set('title', ADMIN_TITLE . 'Convention Registrations Events');
        $this->viewBuilder()->setLayout('admin');
        $this->set('dashboard', '1');
		
        $condition = array();
		
		$sess_admin_header_season_id = $this->request->getSession()->read("sess_admin_header_season_id");
        if (empty($sess_admin_header_season_id)) {
            $this->Flash->error('Please select a convention season first.');
            return $this->redirect(['controller' => 'admins', 'action' => 'dashboard']);
        }
		$convSeasonD = $this->Conventionseasons->find()->where(['Conventionseasons.id' => $sess_admin_header_season_id])->first();
        if (empty($convSeasonD)) {
            $this->Flash->error('Convention season not found. Please select a valid season.');
            return $this->redirect(['controller' => 'admins', 'action' => 'dashboard']);
        }
		
		$this->set('convSeasonD', $convSeasonD);
		
		$condition[] = "(Conventionseasonevents.conventionseasons_id = '".$convSeasonD->id."')";
		
		$conventionseasonevents = $this->Conventionseasonevents->find()->contain(['Events'])->where($condition)->order(["Conventionseasonevents.id" => "DESC"])->all();
		$this->set('conventionseasonevents', $conventionseasonevents);
    }

    public function alleventscsv() {

        $this->viewBuilder()->disableAutoLayout();
        $this->autoRender = false;

        $sess_admin_header_season_id = $this->request->getSession()->read("sess_admin_header_season_id");
        if (empty($sess_admin_header_season_id)) {
            $this->Flash->error('Please select a convention season first.');
            return $this->redirect(['controller' => 'admins', 'action' => 'dashboard']);
        }

        $convSeasonD = $this->Conventionseasons->find()->where(['Conventionseasons.id' => $sess_admin_header_season_id])->first();
        if (empty($convSeasonD)) {
            $this->Flash->error('Convention season not found.');
            return $this->redirect(['controller' => 'admins', 'action' => 'dashboard']);
        }

        $conventionseasonevents = $this->Conventionseasonevents->find()
            ->contain(['Events'])
            ->where(['Conventionseasonevents.conventionseasons_id' => $convSeasonD->id])
            ->order(['Conventionseasonevents.id' => 'DESC'])
            ->all();

        $eventIds = [];
        foreach ($conventionseasonevents as $row) {
            $eventIds[] = (int)$row->event_id;
        }
        $eventIds = array_values(array_unique(array_filter($eventIds)));

        $entryCountsByEvent = [];
        if (!empty($eventIds)) {
            $entryRows = $this->Eventsubmissions->find();
            $entryRows
                ->select([
                    'event_id' => 'Eventsubmissions.event_id',
                    'entries_count' => $entryRows->func()->count('*'),
                ])
                ->where([
                    'Eventsubmissions.conventionseason_id' => $convSeasonD->id,
                    'Eventsubmissions.convention_id' => $convSeasonD->convention_id,
                    'Eventsubmissions.season_id' => $convSeasonD->season_id,
                    'Eventsubmissions.season_year' => $convSeasonD->season_year,
                    'Eventsubmissions.event_id IN' => $eventIds,
                ])
                ->group(['Eventsubmissions.event_id'])
                ->enableHydration(false);

            foreach ($entryRows as $entryRow) {
                $entryCountsByEvent[(int)$entryRow['event_id']] = (int)$entryRow['entries_count'];
            }
        }

        $csvRows = [];
        $csvRows[] = ['Event ID', 'Event Name', 'Event ID Number', 'Entries'];
        foreach ($conventionseasonevents as $row) {
            $eventId = (int)$row->event_id;
            $csvRows[] = [
                $eventId,
                (string)($row->Events['event_name'] ?? ''),
                (string)($row->Events['event_id_number'] ?? ''),
                (int)($entryCountsByEvent[$eventId] ?? 0),
            ];
        }

        $stream = fopen('php://temp', 'w+');
        foreach ($csvRows as $csvRow) {
            fputcsv($stream, $csvRow);
        }
        rewind($stream);
        $csvContent = stream_get_contents($stream);
        fclose($stream);

        $fileName = 'convention_events_' . (int)$convSeasonD->id . '_' . date('Ymd_His') . '.csv';
        return $this->response
            ->withType('csv')
            ->withDownload($fileName)
            ->withStringBody($csvContent);
    }
}

?>
