<?php

namespace App\Controller;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use App\Utility\LegacyEmail as Email;
use Cake\I18n\I18n;

#[\AllowDynamicProperties]
class EventsubmissionsController extends AppController {

    public array $paginate = ['limit' => 50];
	
	public function initialize(): void {
        parent::initialize();
		$this->loadComponent('RequestHandler');
		$this->loadComponent('PImage');
		$this->loadComponent('PImageTest');

        // Include the FlashComponent
        $this->loadComponent('Flash');

        $this->loadModel("Users"); 
		$this->loadModel("Emailtemplates");
		$this->loadModel("Users");
		$this->loadModel("Conventionregistrations");
		$this->loadModel("Conventionregistrationstudents");
		$this->loadModel("Conventionseasonevents");
		$this->loadModel("Events");
		$this->loadModel("Books");
		$this->loadModel("Crstudentevents");
		$this->loadModel("Judgeevaluations");
		$this->loadModel("Results");
		$this->loadModel("Resultpositions");
		$this->loadModel("Conventionseasons");
		$this->loadModel("Combinerequests");
    }

	// ── Silver Apple Award helpers ────────────────────────────────────
	private function isSilverAppleEvent($eventD)
	{
		$name = strtolower(preg_replace('/\s+/',' ',trim((string)($eventD->event_name ?? ''))));
		return in_array($name, ['silver apple award u16','silver apple award open'], true);
	}

	private function silverAppleBookTable()
	{
		// [book_id => [chapters, verses]] — matches books table IDs 1-63
		return [
			1=>[50,1533], 2=>[40,1213], 3=>[27,859],  4=>[36,1263], 5=>[34,959],
			6=>[24,658],  7=>[21,618],  8=>[4,85],    9=>[31,810],  10=>[24,672],
			11=>[22,816], 12=>[25,719], 13=>[29,941], 14=>[36,821], 15=>[10,280],
			16=>[13,406], 17=>[10,167], 18=>[42,1049],19=>[12,222], 20=>[8,117],
			21=>[66,1264],22=>[52,1363],23=>[5,154],  24=>[48,1273],25=>[12,357],
			26=>[14,197], 27=>[3,73],   28=>[9,146],  29=>[1,21],   30=>[4,48],
			31=>[7,105],  32=>[3,47],   33=>[3,56],   34=>[3,53],   35=>[2,38],
			36=>[14,211], 37=>[4,55],   38=>[28,1071],39=>[16,678], 40=>[24,1151],
			41=>[28,1007],42=>[16,433], 43=>[16,437], 44=>[13,239], 45=>[6,149],
			46=>[6,155],  47=>[4,104],  48=>[4,95],   49=>[5,89],   50=>[3,47],
			51=>[6,98],   52=>[4,83],   53=>[3,46],   54=>[1,25],   55=>[13,303],
			56=>[5,108],  57=>[5,105],  58=>[3,61],   59=>[5,105],  60=>[1,13],
			61=>[1,14],   62=>[1,25],   63=>[21,404],
		];
	}

	private function silverAppleCalcPlace($bookIdsString)
	{
		$table         = $this->silverAppleBookTable();
		$ids           = array_filter(array_map('intval', explode(',', $bookIdsString)));
		$totalVerses   = 0;
		$totalChapters = 0;

		foreach ($ids as $bid) {
			if (isset($table[$bid])) {
				$totalChapters += $table[$bid][0];
				$totalVerses   += $table[$bid][1];
			}
		}

		if ($totalVerses <= 0)   return null;
		if ($totalVerses >= 300) return 1;
		if ($totalChapters > 1)  return 2;
		return 3;
	}

	private function iniSizeToBytes($value)
	{
		$value = trim((string)$value);
		if ($value === '') {
			return 0;
		}

		$lastChar = strtolower(substr($value, -1));
		$number = (float)$value;

		switch ($lastChar) {
			case 'g':
				$number *= 1024;
				// no break
			case 'm':
				$number *= 1024;
				// no break
			case 'k':
				$number *= 1024;
				break;
		}

		return (int)round($number);
	}

	private function buildPostLimitExceededMessage($contentLength)
	{
		$postMax = (string)ini_get('post_max_size');
		$uploadMax = (string)ini_get('upload_max_filesize');
		$contentMb = number_format(((int)$contentLength) / 1048576, 2);

		return 'Uploaded request is too large for server limits. Request size ~'.$contentMb.'MB, post_max_size='.$postMax.', upload_max_filesize='.$uploadMax.'. Please upload a smaller file or ask admin to increase limits.';
	}

	private function normalizeTimeScoreForMysql($value)
	{
		$raw = trim((string)$value);
		if ($raw === '') {
			return null;
		}

		$raw = str_replace('.', ':', $raw);
		$parts = array_values(array_filter(array_map('trim', explode(':', $raw)), static function ($p) {
			return $p !== '';
		}));

		if (count($parts) === 4) {
			$hours = str_pad((string)max(0, (int)$parts[0]), 2, '0', STR_PAD_LEFT);
			$minutes = str_pad((string)max(0, (int)$parts[1]), 2, '0', STR_PAD_LEFT);
			$seconds = str_pad((string)max(0, (int)$parts[2]), 2, '0', STR_PAD_LEFT);
			$fraction = preg_replace('/[^0-9]/', '', (string)$parts[3]);
			$fraction = substr(str_pad($fraction, 2, '0', STR_PAD_RIGHT), 0, 2);
			return $hours.':'.$minutes.':'.$seconds.'.'.$fraction;
		}

		if (count($parts) === 3) {
			$hours = str_pad((string)max(0, (int)$parts[0]), 2, '0', STR_PAD_LEFT);
			$minutes = str_pad((string)max(0, (int)$parts[1]), 2, '0', STR_PAD_LEFT);
			$seconds = str_pad((string)max(0, (int)$parts[2]), 2, '0', STR_PAD_LEFT);
			return $hours.':'.$minutes.':'.$seconds;
		}

		if (count($parts) === 2) {
			$minutes = str_pad((string)max(0, (int)$parts[0]), 2, '0', STR_PAD_LEFT);
			$seconds = str_pad((string)max(0, (int)$parts[1]), 2, '0', STR_PAD_LEFT);
			return '00:'.$minutes.':'.$seconds;
		}

		if (count($parts) === 1 && preg_match('/^\d+$/', $parts[0])) {
			$seconds = str_pad((string)max(0, (int)$parts[0]), 2, '0', STR_PAD_LEFT);
			return '00:00:'.$seconds;
		}

		return null;
	}
	
	public function viewlist() {

        $this->userLoginCheck();
		$this->multiLoginCheck(array("School","Teacher_Parent"));
		
        $this->set("title_for_layout", "View/Edit Event Submissions" . TITLE_FOR_PAGES);
        $this->viewBuilder()->setLayout('home');
        
		$this->set('active_cr_eventsubmission','active');
		
        $msgString = '';

		$user_id = $this->request->getSession()->read("user_id");
		$user_type 	= $this->request->getSession()->read("user_type");
		if (empty($user_id)) {
			$this->Flash->error('Your session has expired. Please login again.');
			return $this->redirect(['controller' => 'users', 'action' => 'login']);
		}
		$userDetails = null;
		if (!empty($user_id)) {
			$userDetails = $this->Users->find()->where(['Users.id' => $user_id])->first();
		}
        $this->set('userDetails', $userDetails);
		
                if($this->request->getSession()->read("sess_selected_convention_registration_id")>0)
        {
            $selectedRegistrationId = (int)$this->request->getSession()->read("sess_selected_convention_registration_id");
        }
        else
        {
            $this->Flash->error('Please choose convention registration first.');
            return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
        }

        $submissionConditions = [
            'Eventsubmissions.conventionregistration_id' => $selectedRegistrationId,
        ];

        // if teacher is logged in, then only show documents uploaded by teacher
        if($user_type == "Teacher_Parent")
        {
            $submissionConditions['Eventsubmissions.uploaded_by_user_id'] = (int)$user_id;
        }

        $conventionRegD = $this->Conventionregistrations->find()->where(['Conventionregistrations.id' => $selectedRegistrationId])->contain(["Conventionseasons"])->first();
        if ($conventionRegD && !$this->areSubmissionsLocked($conventionRegD->conventionseason_id)) {
            $this->syncAutomaticGroupSubmissions($conventionRegD);
        }

        if ($conventionRegD && $user_type == "School") {
            $mixedGroupRows = $this->Crstudentevents->find()
                ->select(['student_id', 'event_id'])
                ->where([
                    'Crstudentevents.conventionregistration_id' => $selectedRegistrationId,
                    'Crstudentevents.event_id >' => 0,
                    'Crstudentevents.student_id >' => 0,
                    'Crstudentevents.group_name IS NOT' => null,
                    'Crstudentevents.group_name !=' => '',
                ])
                ->all();

            $mixedStudentIds = [];
            $mixedEventIds = [];
            foreach ($mixedGroupRows as $mixedGroupRow) {
                $mixedStudentIds[] = (int)$mixedGroupRow->student_id;
                $mixedEventIds[] = (int)$mixedGroupRow->event_id;
            }

            $studentSchoolMap = [];
            if (!empty($mixedStudentIds)) {
                $studentRows = $this->Users->find()
                    ->select(['id', 'school_id'])
                    ->where(['Users.id IN' => array_values(array_unique($mixedStudentIds))])
                    ->all();
                foreach ($studentRows as $studentRow) {
                    $studentSchoolMap[(int)$studentRow->id] = (int)($studentRow->school_id ?? 0);
                }
            }

            $approvedCombinedRows = $this->Combinerequests->find()
                ->select(['event_id', 'user_id', 'combine_with_user_id'])
                ->where([
                    'Combinerequests.status' => 1,
                    'Combinerequests.conventionseason_id' => (int)$conventionRegD->conventionseason_id,
                    'OR' => [
                        ['Combinerequests.user_id' => (int)$conventionRegD->user_id],
                        ['Combinerequests.combine_with_user_id' => (int)$conventionRegD->user_id],
                    ],
                ])
                ->all();

            $approvedEventIds = [];
            $partnerSchoolIds = [];

            foreach ($mixedGroupRows as $mixedGroupRow) {
                $studentId = (int)$mixedGroupRow->student_id;
                $studentSchoolId = (int)($studentSchoolMap[$studentId] ?? 0);
                if ($studentSchoolId > 0 && $studentSchoolId !== (int)$conventionRegD->user_id) {
                    $partnerSchoolIds[] = $studentSchoolId;
                    $approvedEventIds[] = (int)$mixedGroupRow->event_id;
                }
            }

            foreach ($approvedCombinedRows as $approvedCombinedRow) {
                $approvedEventIds[] = (int)$approvedCombinedRow->event_id;
                $userIdA = (int)$approvedCombinedRow->user_id;
                $userIdB = (int)$approvedCombinedRow->combine_with_user_id;
                if ($userIdA > 0 && $userIdA !== (int)$conventionRegD->user_id) {
                    $partnerSchoolIds[] = $userIdA;
                }
                if ($userIdB > 0 && $userIdB !== (int)$conventionRegD->user_id) {
                    $partnerSchoolIds[] = $userIdB;
                }
            }

            $approvedEventIds = array_values(array_unique(array_filter($approvedEventIds)));
            $mixedEventIds = array_values(array_unique(array_filter($mixedEventIds)));
            $approvedEventIds = array_values(array_unique(array_merge($approvedEventIds, $mixedEventIds)));
            $partnerSchoolIds = array_values(array_unique(array_filter($partnerSchoolIds)));

			// Only group events should pull partner-school submissions into this list.
			if (!empty($approvedEventIds)) {
				$approvedEventIds = $this->Events->find()
					->select(['id'])
					->where([
						'Events.id IN' => $approvedEventIds,
						'Events.group_event_yes_no' => 1,
					])
					->extract('id')
					->map(static function ($id) {
						return (int)$id;
					})
					->toList();
			}

            if (!empty($approvedEventIds) && !empty($partnerSchoolIds)) {
                $partnerRegistrationIds = $this->Conventionregistrations->find()
                    ->select(['id'])
                    ->where([
                        'Conventionregistrations.conventionseason_id' => (int)$conventionRegD->conventionseason_id,
                        'Conventionregistrations.user_id IN' => $partnerSchoolIds,
                    ])
                    ->extract('id')
                    ->map(static function ($id) {
                        return (int)$id;
                    })
                    ->toList();

                if (!empty($partnerRegistrationIds)) {
                    $submissionConditions = [
                        'OR' => [
                            ['Eventsubmissions.conventionregistration_id' => $selectedRegistrationId],
                            [
                                'Eventsubmissions.conventionregistration_id IN' => $partnerRegistrationIds,
                                'Eventsubmissions.event_id IN' => $approvedEventIds,
                            ],
                        ],
                    ];
                }
            }
        }

        $eventsubmissions = $this->Eventsubmissions->find()->where($submissionConditions)->contain(['Events','Students'])->order(['Eventsubmissions.id' => 'DESC'])->all();
		$this->set('eventsubmissions',$eventsubmissions);
		//$this->prx($condition);
		
		// to check if result released or not
		$this->set('results_release', $conventionRegD ? $conventionRegD->Conventionseasons['results_release'] : 0);
		
    }

	// Combine-approved partner schools share one team; return their registration ids so we don't duplicate submissions.
	private function getApprovedCombinePartnerRegistrationIds($conventionRegD): array {
		$approvedCombinedRows = $this->Combinerequests->find()
			->select(['user_id', 'combine_with_user_id'])
			->where([
				'Combinerequests.status' => 1,
				'Combinerequests.conventionseason_id' => (int)$conventionRegD->conventionseason_id,
				'OR' => [
					['Combinerequests.user_id' => (int)$conventionRegD->user_id],
					['Combinerequests.combine_with_user_id' => (int)$conventionRegD->user_id],
				],
			])
			->all();

		$partnerSchoolIds = [];
		foreach ($approvedCombinedRows as $approvedCombinedRow) {
			$userIdA = (int)$approvedCombinedRow->user_id;
			$userIdB = (int)$approvedCombinedRow->combine_with_user_id;
			if ($userIdA > 0 && $userIdA !== (int)$conventionRegD->user_id) {
				$partnerSchoolIds[] = $userIdA;
			}
			if ($userIdB > 0 && $userIdB !== (int)$conventionRegD->user_id) {
				$partnerSchoolIds[] = $userIdB;
			}
		}
		$partnerSchoolIds = array_values(array_unique(array_filter($partnerSchoolIds)));
		if (empty($partnerSchoolIds)) {
			return [];
		}

		return $this->Conventionregistrations->find()
			->select(['id'])
			->where([
				'Conventionregistrations.conventionseason_id' => (int)$conventionRegD->conventionseason_id,
				'Conventionregistrations.user_id IN' => $partnerSchoolIds,
			])
			->extract('id')
			->map(static function ($id) {
				return (int)$id;
			})
			->toList();
	}

	private function syncAutomaticGroupSubmissions($conventionRegD): void {
		$groupedEventRows = $this->Crstudentevents->find()
			->select(['event_id', 'group_name'])
			->where([
				'Crstudentevents.conventionregistration_id' => $conventionRegD->id,
				'Crstudentevents.convention_id' => $conventionRegD->convention_id,
				'Crstudentevents.season_id' => $conventionRegD->season_id,
				'Crstudentevents.season_year' => $conventionRegD->season_year,
				'Crstudentevents.group_name IS NOT' => null,
				'Crstudentevents.group_name !=' => '',
			])
			->group(['Crstudentevents.event_id', 'Crstudentevents.group_name'])
			->all();

		$partnerRegistrationIds = $this->getApprovedCombinePartnerRegistrationIds($conventionRegD);

		foreach ($groupedEventRows as $groupedEventRow) {
			$event = $this->Events->find()->where([
				'Events.id' => $groupedEventRow->event_id,
				'Events.auto_submission' => 1,
				'Events.group_event_yes_no' => 1,
			])->first();
			if (!$event) {
				continue;
			}

			// Only create automatic group submissions for events that do not require user uploads/context.
			$uploadType = strtolower(trim((string)($event->upload_type ?? '')));
			$requiresManualSubmission = (
				$uploadType !== 'nil' ||
				(int)($event->report ?? 0) === 1 ||
				(int)($event->context_box ?? 0) === 1 ||
				(int)($event->score_sheet ?? 0) === 1 ||
				(int)($event->additional_documents ?? 0) === 1
			);
			if ($requiresManualSubmission) {
				$groupName = trim((string)$groupedEventRow->group_name);
				if ($groupName !== '') {
					$this->Eventsubmissions->deleteAll([
						'Eventsubmissions.conventionregistration_id' => $conventionRegD->id,
						'Eventsubmissions.event_id' => $event->id,
						'Eventsubmissions.group_name' => $groupName,
						'Eventsubmissions.student_id' => 0,
						[
							'OR' => [
								['Eventsubmissions.context_box IS' => null],
								['Eventsubmissions.context_box' => ''],
							],
						],
						[
							'OR' => [
								['Eventsubmissions.mediafile_file_system_name IS' => null],
								['Eventsubmissions.mediafile_file_system_name' => ''],
							],
						],
						[
							'OR' => [
								['Eventsubmissions.report IS' => null],
								['Eventsubmissions.report' => ''],
							],
						],
						[
							'OR' => [
								['Eventsubmissions.score_sheet IS' => null],
								['Eventsubmissions.score_sheet' => ''],
							],
						],
						[
							'OR' => [
								['Eventsubmissions.additional_documents IS' => null],
								['Eventsubmissions.additional_documents' => ''],
							],
						],
					]);
				}
				continue;
			}

			$groupName = trim((string)$groupedEventRow->group_name);
			$existingSubmissionConditions = [
				'OR' => [
					['Eventsubmissions.conventionregistration_id' => $conventionRegD->id],
				],
			];
			if (!empty($partnerRegistrationIds)) {
				$existingSubmissionConditions['OR'][] = ['Eventsubmissions.conventionregistration_id IN' => $partnerRegistrationIds];
			}
			$submissionExists = $this->Eventsubmissions->find()->where([
				'Eventsubmissions.event_id' => $event->id,
				'Eventsubmissions.group_name' => $groupName,
			])->where($existingSubmissionConditions)->count() > 0;
			if ($submissionExists) {
				continue;
			}

			$submission = $this->Eventsubmissions->newEntity([]);
			$submission->slug = 'event-submission-'.$conventionRegD->id.'-'.time().'-'.rand(100, 1000000);
			$submission->conventionregistration_id = $conventionRegD->id;
			$submission->conventionseason_id = $conventionRegD->conventionseason_id;
			$submission->convention_id = $conventionRegD->convention_id;
			$submission->user_id = $conventionRegD->user_id;
			$submission->season_id = $conventionRegD->season_id;
			$submission->season_year = $conventionRegD->season_year;
			$submission->event_id = $event->id;
			$submission->event_id_number = $event->event_id_number;
			$submission->student_id = 0;
			$submission->group_name = $groupName;
			$submission->uploaded_by_user_id = $conventionRegD->user_id;
			$submission->created = date('Y-m-d H:i:s');
			$submission->modified = date('Y-m-d H:i:s');
			$this->Eventsubmissions->save($submission);
		}
	}
	
	public function submitnewevent() {
		
		$this->userLoginCheck();
		$this->multiLoginCheck(array("School","Teacher_Parent"));
		
		//echo ' fsdf sdf sdf d';exit;
		$this->viewBuilder()->setLayout("home");
        $this->set("title_for_layout", "Submit New Event " . TITLE_FOR_PAGES);
		
		$this->set('active_cr_eventsubmission','active');
		
        $user_id = $this->request->getSession()->read("user_id");
		if (empty($user_id)) {
			$this->Flash->error('Your session has expired. Please login again.');
			return $this->redirect(['controller' => 'users', 'action' => 'login']);
		}
		$userDetails = $this->Users->find()->where(['Users.id' => $user_id])->first();
        $this->set('userDetails', $userDetails);
		
		if($this->request->getSession()->read("sess_selected_convention_registration_id")>0)
		{
			$sess_selected_convention_registration_id = $this->request->getSession()->read("sess_selected_convention_registration_id");
			
			// to get convention registration details
			$conventionRegD = $this->Conventionregistrations->find()->where(['Conventionregistrations.id' => $sess_selected_convention_registration_id])->first();
			$this->set('conventionRegD', $conventionRegD);
			if ($this->areSubmissionsLocked($conventionRegD->conventionseason_id)) {
				$this->Flash->error('Submissions cannot be uploaded while submissions are locked.');
				return $this->redirect(['controller' => 'eventsubmissions', 'action' => 'viewlist']);
			}
			
			$this->checkRegistrationStillOpen($this->request->getSession()->read("sess_selected_convention_registration_id"));
			
			// to get the list of event ids chosen in this convention for this season
			$arrConvSeasonEvents = array();
			$arrConvSeasonEvents[] = 0;
			$convSeasonEvents = $this->Conventionseasonevents->find()->where(["Conventionseasonevents.conventionseasons_id" => $conventionRegD->conventionseason_id])->order(['Conventionseasonevents.id' => 'ASC'])->all();
			foreach($convSeasonEvents as $convsevent)
			{
				$arrConvSeasonEvents[] = $convsevent->event_id;
			}
			$arrConvSeasonEventsImplode = implode(",",$arrConvSeasonEvents);
			
			// now create event dropdown with event name and number_format
			$eventNameIDDD = array();
			$condEvents = array();
			$condEvents[] = "(Events.id IN ($arrConvSeasonEventsImplode) )";
			$eventsList = $this->Events->find()->where($condEvents)->order(['Events.event_id_number' => 'ASC'])->all();
			foreach($eventsList as $eventrec)
			{
				$eventNameIDDD[$eventrec->id] = $eventrec->event_name.' ('.$eventrec->event_id_number.')';
			}
			$this->set('eventNameIDDD', $eventNameIDDD);
			
			
		}
		else
		{
			$this->Flash->error('Please choose convention registration first.');
			$this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		
		$existingStudentSubmission = $this->Eventsubmissions->find()->where([
			'Eventsubmissions.conventionregistration_id' => $conventionRegD->id,
			'Eventsubmissions.event_id' => $eventD->id,
			'Eventsubmissions.student_id' => $convRegStudentD->student_id,
		])->order(['Eventsubmissions.id' => 'DESC'])->first();

        $eventsubmissions = $existingStudentSubmission ?: $this->Eventsubmissions->newEntity([]);
        if ($this->request->is('post')) {
			$contentLength = (int)($this->request->getEnv('CONTENT_LENGTH') ?: 0);
			$postMaxBytes = $this->iniSizeToBytes((string)ini_get('post_max_size'));
			if ($contentLength > 0 && empty($this->request->getData()) && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
				$this->Flash->error($this->buildPostLimitExceededMessage($contentLength));
				return $this->redirect(['controller' => 'eventsubmissions', 'action' => 'submitnewevent']);
			}

			$requestData = $this->request->getData();
			$eventSubmissionInput = [];
			if (isset($requestData['Eventsubmissions']) && is_array($requestData['Eventsubmissions'])) {
				$eventSubmissionInput = $requestData['Eventsubmissions'];
			} elseif (isset($requestData['eventsubmissions']) && is_array($requestData['eventsubmissions'])) {
				$eventSubmissionInput = $requestData['eventsubmissions'];
			} else {
				foreach (['event_document', 'report', 'score_sheet', 'additional_documents', 'context_box', 'book_ids', 'event_id'] as $fieldKey) {
					if (array_key_exists($fieldKey, $requestData)) {
						$eventSubmissionInput[$fieldKey] = $requestData[$fieldKey];
					}
				}
			}

			$normalizeUpload = function ($fileInput) {
				if (is_object($fileInput) && method_exists($fileInput, 'getClientFilename')) {
					if ($fileInput->getError() !== UPLOAD_ERR_OK || !$fileInput->getClientFilename()) {
						return null;
					}

					$stream = $fileInput->getStream();
					$tmpName = (string)($stream->getMetadata('uri') ?: '');
					if ($tmpName === '' || strpos($tmpName, 'php://') === 0 || !@file_exists($tmpName)) {
						$tmpMaterialized = @tempnam(sys_get_temp_dir(), 'acpup_');
						if ($tmpMaterialized) {
							$stream->rewind();
							@file_put_contents($tmpMaterialized, $stream->getContents());
							$stream->rewind();
							$tmpName = $tmpMaterialized;
						}
					}

					return [
						'name' => $fileInput->getClientFilename(),
						'type' => (string)$fileInput->getClientMediaType(),
						'tmp_name' => $tmpName,
						'error' => $fileInput->getError(),
						'size' => (int)$fileInput->getSize(),
					];
				}

				if (is_array($fileInput) && !empty($fileInput['name'])) {
					return $fileInput;
				}

				return null;
			};

			$eventDocumentUpload = $normalizeUpload($eventSubmissionInput['event_document'] ?? null);
			$reportUpload = $normalizeUpload($eventSubmissionInput['report'] ?? null);
			$scoreSheetUpload = $normalizeUpload($eventSubmissionInput['score_sheet'] ?? null);
			$additionalDocumentsUpload = $normalizeUpload($eventSubmissionInput['additional_documents'] ?? null);

			// Prevent UploadedFile objects from being marshaled into string DB columns.
			$eventSubmissionInput['event_document'] = '';
			$eventSubmissionInput['report'] = '';
			$eventSubmissionInput['score_sheet'] = '';
			$eventSubmissionInput['additional_documents'] = '';
			$data = $this->Eventsubmissions->patchEntity($eventsubmissions, $eventSubmissionInput);
            if (count($data->getErrors()) == 0) {
				
				$book_ids = $eventSubmissionInput['book_ids'] ?? [];
				//$this->prx($book_ids);
				
				if(isset($book_ids) && count((array)$book_ids))
				{
					$data->book_ids = implode(",",(array)$book_ids);
				}
				else
				{
					$data->book_ids = '';
				}

				$data->context_box = isset($eventSubmissionInput['context_box']) ? trim((string)$eventSubmissionInput['context_box']) : '';
				
				$eventD = $this->Events->find()->where(["Events.id" => $data->event_id])->first();
				
				if(!empty($this->request->getData()['Eventsubmissions']['event_document']['name']))
				{
					$data->mediafile_original_file_name =  $this->request->getData()['Eventsubmissions']['event_document']['name'];
					
					$specialCharacters = array('#', '$', '%', '@', '+', '=', '\\', '/', '"', ' ', "'", ':', '~', '`', '!', '^', '*', '(', ')', '|', "'", "&");
					$toReplace = "-";
					$this->request->getData()['Eventsubmissions']['event_document']['name'] = str_replace($specialCharacters, $toReplace, $this->request->getData()['Eventsubmissions']['event_document']['name']);
					$imageArray = $this->request->getData()['Eventsubmissions']['event_document'];
					$returnedUploadImageArray = $this->PImage->upload($imageArray, UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH); 
					 
					$data->mediafile_file_system_name =  $returnedUploadImageArray[0];
				}
				
				if(!empty($this->request->getData()['Eventsubmissions']['report']['name']))
				{
					$data->report =  $this->request->getData()['Eventsubmissions']['report']['name'];
					
					$specialCharacters = array('#', '$', '%', '@', '+', '=', '\\', '/', '"', ' ', "'", ':', '~', '`', '!', '^', '*', '(', ')', '|', "'", "&");
					$toReplace = "-";
					$this->request->getData()['Eventsubmissions']['report']['name'] = str_replace($specialCharacters, $toReplace, $this->request->getData()['Eventsubmissions']['report']['name']);
					$imageArray = $this->request->getData()['Eventsubmissions']['report'];
					$returnedUploadImageArray = $this->PImage->upload($imageArray, UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH); 
					 
					$data->report =  $returnedUploadImageArray[0];
				}
				
				if(!empty($this->request->getData()['Eventsubmissions']['score_sheet']['name']))
				{
					$specialCharacters = array('#', '$', '%', '@', '+', '=', '\\', '/', '"', ' ', "'", ':', '~', '`', '!', '^', '*', '(', ')', '|', "'", "&");
					$toReplace = "-";
					$this->request->getData()['Eventsubmissions']['score_sheet']['name'] = str_replace($specialCharacters, $toReplace, $this->request->getData()['Eventsubmissions']['score_sheet']['name']);
					$imageArray = $this->request->getData()['Eventsubmissions']['score_sheet'];
					$returnedUploadImageArray = $this->PImage->upload($imageArray, UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH); 
					 
					$data->score_sheet =  $returnedUploadImageArray[0];
				}
				
				if(!empty($this->request->getData()['Eventsubmissions']['additional_documents']['name']))
				{
					$specialCharacters = array('#', '$', '%', '@', '+', '=', '\\', '/', '"', ' ', "'", ':', '~', '`', '!', '^', '*', '(', ')', '|', "'", "&");
					$toReplace = "-";
					$this->request->getData()['Eventsubmissions']['additional_documents']['name'] = str_replace($specialCharacters, $toReplace, $this->request->getData()['Eventsubmissions']['additional_documents']['name']);
					$imageArray = $this->request->getData()['Eventsubmissions']['additional_documents'];
					$returnedUploadImageArray = $this->PImage->upload($imageArray, UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH); 
					 
					$data->additional_documents =  $returnedUploadImageArray[0];
				}
				
                $data->slug = 'event-submission-'.$sess_selected_convention_registration_id.'-'.time().'-'.rand(100,1000000);
				
				$data->conventionregistration_id	= $sess_selected_convention_registration_id;
				$data->conventionseason_id			= $conventionRegD->conventionseason_id;
				$data->convention_id				= $conventionRegD->convention_id;
				$data->user_id						= $conventionRegD->user_id;
				$data->season_id 					= $conventionRegD->season_id;
				$data->season_year 					= $conventionRegD->season_year;
				$data->event_id_number 				= $eventD->event_id_number;
				
				
				if($eventD->group_event_yes_no == 1)
				{
					$data->student_id 			= 0;
				}
				else
				{
					$data->group_name 			= NULL;
				}
				
				$data->uploaded_by_user_id 			= $user_id;
				
				if (empty($data->id)) {
					$data->created = date('Y-m-d H:i:s');
				}
                $data->modified = date('Y-m-d H:i:s');
				
				//$this->prx($data);
				
                if ($this->Eventsubmissions->save($data)) {
					
					$this->Flash->success('Events submission completed successfully.');
                    $this->redirect(['controller' => 'eventsubmissions', 'action' => 'viewlist']);
                }
            } else {
                // $this->Flash->error('Please below listed errors.');
            }
        }
        $this->set('eventsubmissions', $eventsubmissions);
    }
	
	public function removesubmission($eventsubmission_slug = null) {
		
		$this->schoolAdminLoginCheck();
		
        $user_id = $this->request->getSession()->read("user_id");
		if (empty($user_id)) {
			$this->Flash->error('Your session has expired. Please login again.');
			return $this->redirect(['controller' => 'users', 'action' => 'login']);
		}
		$userDetails = $this->Users->find()->where(['Users.id' => $user_id])->first();
        $this->set('userDetails', $userDetails);
		
		if($this->request->getSession()->read("sess_selected_convention_registration_id")>0)
		{
			$sess_selected_convention_registration_id = $this->request->getSession()->read("sess_selected_convention_registration_id");
			
			// to check if slug exists
			$checkExists = $this->Eventsubmissions->find()->where(['Eventsubmissions.slug' => $eventsubmission_slug,'Eventsubmissions.conventionregistration_id' => $sess_selected_convention_registration_id])->first();
			if($checkExists)
			{
				// to remove document as well
				if(file_exists(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$checkExists->mediafile_file_system_name) && !empty($checkExists->mediafile_file_system_name))
				{
					@unlink(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$checkExists->mediafile_file_system_name);
				}
				
				if(file_exists(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$checkExists->report) && !empty($checkExists->report))
				{
					@unlink(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$checkExists->report);
				}
				
				if(file_exists(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$checkExists->score_sheet) && !empty($checkExists->score_sheet))
				{
					@unlink(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$checkExists->score_sheet);
				}
				
				if(file_exists(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$checkExists->additional_documents) && !empty($checkExists->additional_documents))
				{
					@unlink(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$checkExists->additional_documents);
				}
				
				$this->Flash->success('Events submission removed successfully.');
				$this->Eventsubmissions->deleteAll(["slug" => $eventsubmission_slug]);
			}
			else
			{
				$this->Flash->error('Invalid supervisor details.');
			}
		}
		else
		{
			$this->Flash->error('Please choose convention registration first.');
			$this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		
		$this->redirect(['controller' => 'eventsubmissions', 'action' => 'viewlist']);
    }
	
	public function submitstudentevent($conv_reg_student_slug=NULL,$event_slug=NULL) {
		
		//$this->schoolAdminLoginCheck();
		
		$this->userLoginCheck();
		$this->multiLoginCheck(array("School","Teacher_Parent","Student"));
		
		// to check if registration is still open
		$this->checkRegistrationStillOpen($this->request->getSession()->read("sess_selected_convention_registration_id"));
		
		//echo ' fsdf sdf sdf d';exit;
		$this->viewBuilder()->setLayout("home");
        $this->set("title_for_layout", "Submit Student Event " . TITLE_FOR_PAGES);
		
		$this->set('active_cr_packageregistration','active');
		
        $user_id = $this->request->getSession()->read("user_id");
		if (empty($user_id)) {
			$this->Flash->error('Your session has expired. Please login again.');
			return $this->redirect(['controller' => 'users', 'action' => 'login']);
		}
		$userDetails = $this->Users->find()->where(['Users.id' => $user_id])->first();
        $this->set('userDetails', $userDetails);
		
		if($conv_reg_student_slug)
		{
			$convRegStudentD = $this->Conventionregistrationstudents->find()->where(['Conventionregistrationstudents.slug' => $conv_reg_student_slug])->contain(['Students'])->first();
			$this->set('convRegStudentD', $convRegStudentD);
		}

		if($userDetails && $userDetails->user_type === 'Student' && (!isset($convRegStudentD->student_id) || (int)$convRegStudentD->student_id !== (int)$user_id))
		{
			$this->Flash->error('You can only upload for your own event registration.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'studentevents']);
		}
		
		if($event_slug)
		{
			$eventD = $this->Events->find()->where(['Events.slug' => $event_slug])->first();
			$this->set('eventD', $eventD);
			
			// to get list of books associated with this event
			if(!empty($eventD->book_ids))
			{
				$evBookIDS = $eventD->book_ids;
				
				// to get list of books from name
				$condEventBooks = array();
				$condEventBooks[] = "(Books.id IN ($evBookIDS) )";
				$booksDD = $this->Books->find()->where($condEventBooks)->combine('id', 'book_name')->toArray();
				$this->set('booksDD', $booksDD);
			}
		}
		
		if($convRegStudentD->id>0 && $eventD->id>0)
		{
			// to get convention registration details
			$conventionRegD = $this->Conventionregistrations->find()->where(['Conventionregistrations.id' => $convRegStudentD->conventionregistration_id])->first();
			$this->set('conventionRegD', $conventionRegD);
		}
		else
		{
			$this->Flash->error('Invaid information.');
			$this->redirect(['controller' => 'conventionregistrations', 'action' => 'packageregistration']);
		}

		if ($this->areSubmissionsLocked($conventionRegD->conventionseason_id)) {
			$this->Flash->error('Submissions cannot be uploaded while submissions are locked.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'studentevents']);
		}
		
		
        $eventsubmissions = $this->Eventsubmissions->newEntity([]);
        if ($this->request->is('post')) {
			$contentLength = (int)($this->request->getEnv('CONTENT_LENGTH') ?: 0);
			$postMaxBytes = $this->iniSizeToBytes((string)ini_get('post_max_size'));
			if ($contentLength > 0 && empty($this->request->getData()) && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
				$this->Flash->error($this->buildPostLimitExceededMessage($contentLength));
				return $this->redirect($this->request->getRequestTarget());
			}

			$requestData = $this->request->getData();
			$eventSubmissionInput = [];
			if (isset($requestData['Eventsubmissions']) && is_array($requestData['Eventsubmissions'])) {
				$eventSubmissionInput = $requestData['Eventsubmissions'];
			} elseif (isset($requestData['eventsubmissions']) && is_array($requestData['eventsubmissions'])) {
				$eventSubmissionInput = $requestData['eventsubmissions'];
			} else {
				foreach (['event_document', 'report', 'score_sheet', 'additional_documents', 'context_box', 'book_ids'] as $fieldKey) {
					if (array_key_exists($fieldKey, $requestData)) {
						$eventSubmissionInput[$fieldKey] = $requestData[$fieldKey];
					}
				}
			}

			$normalizeUpload = function ($fileInput) {
				if (is_object($fileInput) && method_exists($fileInput, 'getClientFilename')) {
					if ($fileInput->getError() !== UPLOAD_ERR_OK || !$fileInput->getClientFilename()) {
						return null;
					}

					$stream = $fileInput->getStream();
					$tmpName = (string)($stream->getMetadata('uri') ?: '');
					if ($tmpName === '' || strpos($tmpName, 'php://') === 0 || !@file_exists($tmpName)) {
						$tmpMaterialized = @tempnam(sys_get_temp_dir(), 'acpup_');
						if ($tmpMaterialized) {
							$stream->rewind();
							@file_put_contents($tmpMaterialized, $stream->getContents());
							$stream->rewind();
							$tmpName = $tmpMaterialized;
						}
					}

					return [
						'name' => $fileInput->getClientFilename(),
						'type' => (string)$fileInput->getClientMediaType(),
						'tmp_name' => $tmpName,
						'error' => $fileInput->getError(),
						'size' => (int)$fileInput->getSize(),
					];
				}

				if (is_array($fileInput) && !empty($fileInput['name'])) {
					return $fileInput;
				}

				return null;
			};

			$eventDocumentUpload = $normalizeUpload($eventSubmissionInput['event_document'] ?? null);
			$reportUpload = $normalizeUpload($eventSubmissionInput['report'] ?? null);
			$scoreSheetUpload = $normalizeUpload($eventSubmissionInput['score_sheet'] ?? null);
			$additionalDocumentsUpload = $normalizeUpload($eventSubmissionInput['additional_documents'] ?? null);

			// Prevent UploadedFile objects from being marshaled into string DB columns.
			$eventSubmissionInput['event_document'] = '';
			$eventSubmissionInput['report'] = '';
			$eventSubmissionInput['score_sheet'] = '';
			$eventSubmissionInput['additional_documents'] = '';
			$data = $this->Eventsubmissions->patchEntity($eventsubmissions, $eventSubmissionInput);
            if (count($data->getErrors()) == 0) {
				
				$book_ids = $eventSubmissionInput['book_ids'] ?? [];
				//$this->prx($book_ids);
				
				if(isset($book_ids) && count((array)$book_ids))
				{
					$data->book_ids = implode(",",(array)$book_ids);
				}
				else
				{
					$data->book_ids = '';
				}

				$data->context_box = isset($eventSubmissionInput['context_box']) ? trim((string)$eventSubmissionInput['context_box']) : '';
				
				if(!empty($eventDocumentUpload['name']))
				{
					$data->mediafile_original_file_name =  $eventDocumentUpload['name'];
					
					$specialCharacters = array('#', '$', '%', '@', '+', '=', '\\', '/', '"', ' ', "'", ':', '~', '`', '!', '^', '*', '(', ')', '|', "'", "&");
					$toReplace = "-";
					$eventDocumentUpload['name'] = str_replace($specialCharacters, $toReplace, $eventDocumentUpload['name']);
					$imageArray = $eventDocumentUpload;
					$returnedUploadImageArray = $this->PImage->upload($imageArray, UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH); 
					 
					$data->mediafile_file_system_name =  $returnedUploadImageArray[0];
				}
				
				if(!empty($reportUpload['name']))
				{
					$data->report =  $reportUpload['name'];
					
					$specialCharacters = array('#', '$', '%', '@', '+', '=', '\\', '/', '"', ' ', "'", ':', '~', '`', '!', '^', '*', '(', ')', '|', "'", "&");
					$toReplace = "-";
					$reportUpload['name'] = str_replace($specialCharacters, $toReplace, $reportUpload['name']);
					$imageArray = $reportUpload;
					$returnedUploadImageArray = $this->PImage->upload($imageArray, UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH); 
					 
					$data->report =  $returnedUploadImageArray[0];
				}
				
				if(!empty($scoreSheetUpload['name']))
				{
					$specialCharacters = array('#', '$', '%', '@', '+', '=', '\\', '/', '"', ' ', "'", ':', '~', '`', '!', '^', '*', '(', ')', '|', "'", "&");
					$toReplace = "-";
					$scoreSheetUpload['name'] = str_replace($specialCharacters, $toReplace, $scoreSheetUpload['name']);
					$imageArray = $scoreSheetUpload;
					$returnedUploadImageArray = $this->PImage->upload($imageArray, UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH); 
					 
					$data->score_sheet =  $returnedUploadImageArray[0];
				}
				
				if(!empty($additionalDocumentsUpload['name']))
				{
					$specialCharacters = array('#', '$', '%', '@', '+', '=', '\\', '/', '"', ' ', "'", ':', '~', '`', '!', '^', '*', '(', ')', '|', "'", "&");
					$toReplace = "-";
					$additionalDocumentsUpload['name'] = str_replace($specialCharacters, $toReplace, $additionalDocumentsUpload['name']);
					$imageArray = $additionalDocumentsUpload;
					$returnedUploadImageArray = $this->PImage->upload($imageArray, UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH); 
					 
					$data->additional_documents =  $returnedUploadImageArray[0];
				}
				
                $data->slug = 'event-submission-'.$conventionRegD->id.'-'.time().'-'.rand(100,1000000);
				
				$data->conventionregistration_id	= $conventionRegD->id;
				$data->conventionseason_id			= $conventionRegD->conventionseason_id;
				$data->convention_id				= $conventionRegD->convention_id;
				$data->user_id						= $conventionRegD->user_id;
				$data->season_id 					= $conventionRegD->season_id;
				$data->season_year 					= $conventionRegD->season_year;
				$data->event_id 					= $eventD->id;
				$data->event_id_number 				= $eventD->event_id_number;
				$data->student_id 					= $convRegStudentD->student_id;
				
				$data->uploaded_by_user_id 			= $user_id;
				
                $data->created = date('Y-m-d H:i:s');
                $data->modified = date('Y-m-d H:i:s');
				
				//$this->prx($data);
				
                if ($this->Eventsubmissions->save($data)) {

					// ── Silver Apple: auto-calculate place and save result position ──
					if ($this->isSilverAppleEvent($eventD)) {
						$bookIdsSubmitted = trim((string)($data->book_ids ?? ''));
						$place = $bookIdsSubmitted ? $this->silverAppleCalcPlace($bookIdsSubmitted) : null;
						$pointsMap = [1 => 12, 2 => 10, 3 => 8];
						$points = ($place && isset($pointsMap[$place])) ? $pointsMap[$place] : null;

						if ($place) {
							// Find or create the Results row for this event
							$conventionSeasonD = $this->Conventionseasons->find()->where(['Conventionseasons.id' => $conventionRegD->conventionseason_id])->first();
							$resultRow = $this->Results->find()->where([
								'Results.conventionseason_id' => $conventionRegD->conventionseason_id,
								'Results.convention_id'      => $conventionRegD->convention_id,
								'Results.event_id'           => $eventD->id,
							])->first();

							if (!$resultRow) {
								$resultRow = $this->Results->newEntity([]);
								$resultRow->slug                 = 'result-sa-'.$conventionRegD->conventionseason_id.'-'.$eventD->id.'-'.time();
								$resultRow->conventionseason_id  = $conventionRegD->conventionseason_id;
								$resultRow->convention_id        = $conventionRegD->convention_id;
								$resultRow->season_id            = $conventionRegD->season_id;
								$resultRow->season_year          = $conventionRegD->season_year;
								$resultRow->event_id             = $eventD->id;
								$resultRow->event_id_number      = $eventD->event_id_number;
								$resultRow->created              = date('Y-m-d H:i:s');
								$resultRow->modified             = date('Y-m-d H:i:s');
								$this->Results->save($resultRow);
							}

							// Remove existing resultposition for this submission if re-submitting
							$this->Resultpositions->deleteAll(['Resultpositions.eventsubmission_id' => $data->id]);

							$rp = $this->Resultpositions->newEntity([]);
							$rp->slug                   = 'result-pos-sa-'.$data->id.'-'.time();
							$rp->result_id              = $resultRow->id;
							$rp->eventsubmission_id     = $data->id;
							$rp->conventionregistration_id = $conventionRegD->id;
							$rp->conventionseason_id    = $conventionRegD->conventionseason_id;
							$rp->convention_id          = $conventionRegD->convention_id;
							$rp->user_id                = $conventionRegD->user_id;
							$rp->season_id              = $conventionRegD->season_id;
							$rp->season_year            = $conventionRegD->season_year;
							$rp->event_id               = $eventD->id;
							$rp->event_id_number        = $eventD->event_id_number;
							$rp->student_id             = $data->student_id;
							$rp->division_id            = $eventD->division_id ?? null;
							$rp->avg_marks              = null;
							$rp->position               = $place;
							$rp->points_obtained        = $points;
							$rp->created                = date('Y-m-d H:i:s');
							$rp->modified               = date('Y-m-d H:i:s');
							$this->Resultpositions->save($rp);
						}
					}
					// ─────────────────────────────────────────────────────────────────

					$this->Flash->success('Events submission completed successfully.');
                    $this->redirect(['controller' => 'conventionregistrations', 'action' => 'packageregistration']);
                }
            } else {
                // $this->Flash->error('Please below listed errors.');
            }
        }
        $this->set('eventsubmissions', $eventsubmissions);
    }
	
	public function submitgroupevent($conv_reg_student_slug=NULL,$event_slug=NULL,$crstudentevents_slug=NULL) {
		
		//$this->schoolAdminLoginCheck();
		
		$this->userLoginCheck();
		$this->multiLoginCheck(array("School","Teacher_Parent","Student"));
		
		//echo ' fsdf sdf sdf d';exit;
		$this->viewBuilder()->setLayout("home");
        $this->set("title_for_layout", "Submit Group Event " . TITLE_FOR_PAGES);
		
		$this->set('active_cr_packageregistration','active');
		
        $user_id = $this->request->getSession()->read("user_id");
		if (empty($user_id)) {
			$this->Flash->error('Your session has expired. Please login again.');
			return $this->redirect(['controller' => 'users', 'action' => 'login']);
		}
		$userDetails = $this->Users->find()->where(['Users.id' => $user_id])->first();
        $this->set('userDetails', $userDetails);

		$convRegStudentD = null;
		$crstudentEventsD = null;
		$eventD = null;
		
		if($conv_reg_student_slug)
		{
			$convRegStudentD = $this->Conventionregistrationstudents->find()->where(['Conventionregistrationstudents.slug' => $conv_reg_student_slug])->contain(['Students'])->first();
			$this->set('convRegStudentD', $convRegStudentD);
		}
		else
		{
			$this->Flash->error('Invalid registration information.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'packageregistration']);
		}

		if(!$convRegStudentD)
		{
			$this->Flash->error('Registration record was not found.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'packageregistration']);
		}

		if($userDetails && $userDetails->user_type === 'Student' && (!isset($convRegStudentD->student_id) || (int)$convRegStudentD->student_id !== (int)$user_id))
		{
			$this->Flash->error('You can only upload for your own event registration.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'studentevents']);
		}
		
		if($crstudentevents_slug)
		{
			$crstudentEventsD = $this->Crstudentevents->find()->where(['Crstudentevents.slug' => $crstudentevents_slug])->first();
			$this->set('crstudentEventsD', $crstudentEventsD);
		}
		else
		{
			$this->Flash->error('Invalid group event information.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'packageregistration']);
		}

		if(!$crstudentEventsD)
		{
			$this->Flash->error('Group event record was not found.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'packageregistration']);
		}
		
		if($event_slug)
		{
			$eventD = $this->Events->find()->where(['Events.slug' => $event_slug])->first();
			$this->set('eventD', $eventD);
			
			// to get list of books associated with this event
			if(!empty($eventD->book_ids))
			{
				$evBookIDS = $eventD->book_ids;
				
				// to get list of books from name
				$condEventBooks = array();
				$condEventBooks[] = "(Books.id IN ($evBookIDS) )";
				$booksDD = $this->Books->find()->where($condEventBooks)->combine('id', 'book_name')->toArray();
				$this->set('booksDD', $booksDD);
			}
		}
		else
		{
			$this->Flash->error('Invalid event information.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'packageregistration']);
		}

		if(!$eventD)
		{
			$this->Flash->error('Event record was not found.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'packageregistration']);
		}
		
		if($crstudentEventsD->id>0 && !empty($crstudentEventsD->group_name) && $crstudentEventsD->group_name != NULL && $eventD->id>0)
		{
			// to get convention registration details
			$conventionRegD = $this->Conventionregistrations->find()->where(['Conventionregistrations.id' => $crstudentEventsD->conventionregistration_id])->first();
			$this->set('conventionRegD', $conventionRegD);
			if(!$conventionRegD)
			{
				$this->Flash->error('Convention registration could not be loaded.');
				return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'packageregistration']);
			}
		}
		else
		{
			$this->Flash->error('Invalid information.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'packageregistration']);
		}

		if ($this->areSubmissionsLocked($conventionRegD->conventionseason_id)) {
			$this->Flash->error('Submissions cannot be uploaded while submissions are locked.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'packageregistration']);
		}
		
		$existingGroupSubmission = $this->Eventsubmissions->find()->where([
			'Eventsubmissions.conventionregistration_id' => $conventionRegD->id,
			'Eventsubmissions.event_id' => $eventD->id,
			'Eventsubmissions.student_id' => 0,
			'Eventsubmissions.group_name' => $crstudentEventsD->group_name,
		])->order(['Eventsubmissions.id' => 'DESC'])->first();
		
		// to get students for this group
		$groupStudentListArr 	= array();
		$this->set('groupStudentList', '');
		$condGrpStudents 		= array();
		$condGrpStudents[] 		= "(Crstudentevents.conventionregistration_id = '".$conventionRegD->id."' AND Crstudentevents.convention_id = '".$conventionRegD->convention_id."' AND Crstudentevents.season_id = '".$conventionRegD->season_id."' AND Crstudentevents.season_year = '".$conventionRegD->season_year."')";
		$condGrpStudents[] = "(Crstudentevents.event_id = '".$eventD->id."')";
		$condGrpStudents[] = "(Crstudentevents.group_name = '".$crstudentEventsD->group_name."')";
		$groupstudentlist = $this->Crstudentevents->find()->where($condGrpStudents)->all();
		foreach($groupstudentlist as $grpstudent)
		{
			// to get student details
			$studentD = $this->Users->find()->where(['Users.id' => $grpstudent->student_id])->first();
			if(!$studentD)
			{
				continue;
			}

			$groupStudentListArr[] = $studentD->first_name.' '.$studentD->middle_name.' '.$studentD->last_name;
			$this->set('groupStudentList', implode(", ",$groupStudentListArr));
		}
		
		
	$eventsubmissions = $existingGroupSubmission ?: $this->Eventsubmissions->newEntity([]);
        if ($this->request->is('post')) {
			$contentLength = (int)($this->request->getEnv('CONTENT_LENGTH') ?: 0);
			$postMaxBytes = $this->iniSizeToBytes((string)ini_get('post_max_size'));
			if ($contentLength > 0 && empty($this->request->getData()) && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
				$this->Flash->error($this->buildPostLimitExceededMessage($contentLength));
				return $this->redirect($this->request->getRequestTarget());
			}

			$requestData = $this->request->getData();
			$eventSubmissionInput = [];
			if (isset($requestData['Eventsubmissions']) && is_array($requestData['Eventsubmissions'])) {
				$eventSubmissionInput = $requestData['Eventsubmissions'];
			} elseif (isset($requestData['eventsubmissions']) && is_array($requestData['eventsubmissions'])) {
				$eventSubmissionInput = $requestData['eventsubmissions'];
			} else {
				foreach (['event_document', 'report', 'score_sheet', 'additional_documents', 'context_box', 'book_ids'] as $fieldKey) {
					if (array_key_exists($fieldKey, $requestData)) {
						$eventSubmissionInput[$fieldKey] = $requestData[$fieldKey];
					}
				}
			}

			$normalizeUpload = function ($fileInput) {
				if (is_object($fileInput) && method_exists($fileInput, 'getClientFilename')) {
					if ($fileInput->getError() !== UPLOAD_ERR_OK || !$fileInput->getClientFilename()) {
						return null;
					}

					$stream = $fileInput->getStream();
					$tmpName = (string)($stream->getMetadata('uri') ?: '');
					if ($tmpName === '' || strpos($tmpName, 'php://') === 0 || !@file_exists($tmpName)) {
						$tmpMaterialized = @tempnam(sys_get_temp_dir(), 'acpup_');
						if ($tmpMaterialized) {
							$stream->rewind();
							@file_put_contents($tmpMaterialized, $stream->getContents());
							$stream->rewind();
							$tmpName = $tmpMaterialized;
						}
					}

					return [
						'name' => $fileInput->getClientFilename(),
						'type' => (string)$fileInput->getClientMediaType(),
						'tmp_name' => $tmpName,
						'error' => $fileInput->getError(),
						'size' => (int)$fileInput->getSize(),
					];
				}

				if (is_array($fileInput) && !empty($fileInput['name'])) {
					return $fileInput;
				}

				return null;
			};

			$eventDocumentUpload = $normalizeUpload($eventSubmissionInput['event_document'] ?? null);
			$reportUpload = $normalizeUpload($eventSubmissionInput['report'] ?? null);
			$scoreSheetUpload = $normalizeUpload($eventSubmissionInput['score_sheet'] ?? null);
			$additionalDocumentsUpload = $normalizeUpload($eventSubmissionInput['additional_documents'] ?? null);

			// Prevent UploadedFile objects from being marshaled into string DB columns.
			$eventSubmissionInput['event_document'] = '';
			$eventSubmissionInput['report'] = '';
			$eventSubmissionInput['score_sheet'] = '';
			$eventSubmissionInput['additional_documents'] = '';
			$data = $this->Eventsubmissions->patchEntity($eventsubmissions, $eventSubmissionInput);
            if (count($data->getErrors()) == 0) {
				
				$book_ids = $eventSubmissionInput['book_ids'] ?? [];
				//$this->prx($this->request->getData());
				
				if(isset($book_ids) && count((array)$book_ids))
				{
					$data->book_ids = implode(",",(array)$book_ids);
				}
				else
				{
					$data->book_ids = '';
				}

				$data->context_box = isset($eventSubmissionInput['context_box']) ? trim((string)$eventSubmissionInput['context_box']) : '';
				
				if($eventDocumentUpload)
				{
					$data->mediafile_original_file_name =  $eventDocumentUpload['name'];
					
					$specialCharacters = array('#', '$', '%', '@', '+', '=', '\\', '/', '"', ' ', "'", ':', '~', '`', '!', '^', '*', '(', ')', '|', "'", "&");
					$toReplace = "-";
					$eventDocumentUpload['name'] = str_replace($specialCharacters, $toReplace, $eventDocumentUpload['name']);
					$imageArray = $eventDocumentUpload;
					$returnedUploadImageArray = $this->PImage->upload($imageArray, UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH); 
					 
					$data->mediafile_file_system_name =  $returnedUploadImageArray[0];
				}
				
				if($reportUpload)
				{
					$data->report =  $reportUpload['name'];
					
					$specialCharacters = array('#', '$', '%', '@', '+', '=', '\\', '/', '"', ' ', "'", ':', '~', '`', '!', '^', '*', '(', ')', '|', "'", "&");
					$toReplace = "-";
					$reportUpload['name'] = str_replace($specialCharacters, $toReplace, $reportUpload['name']);
					$imageArray = $reportUpload;
					$returnedUploadImageArray = $this->PImage->upload($imageArray, UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH); 
					 
					$data->report =  $returnedUploadImageArray[0];
				}
				
				if($scoreSheetUpload)
				{
					$specialCharacters = array('#', '$', '%', '@', '+', '=', '\\', '/', '"', ' ', "'", ':', '~', '`', '!', '^', '*', '(', ')', '|', "'", "&");
					$toReplace = "-";
					$scoreSheetUpload['name'] = str_replace($specialCharacters, $toReplace, $scoreSheetUpload['name']);
					$imageArray = $scoreSheetUpload;
					$returnedUploadImageArray = $this->PImage->upload($imageArray, UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH); 
					 
					$data->score_sheet =  $returnedUploadImageArray[0];
				}
				
				if($additionalDocumentsUpload)
				{
					$specialCharacters = array('#', '$', '%', '@', '+', '=', '\\', '/', '"', ' ', "'", ':', '~', '`', '!', '^', '*', '(', ')', '|', "'", "&");
					$toReplace = "-";
					$additionalDocumentsUpload['name'] = str_replace($specialCharacters, $toReplace, $additionalDocumentsUpload['name']);
					$imageArray = $additionalDocumentsUpload;
					$returnedUploadImageArray = $this->PImage->upload($imageArray, UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH); 
					 
					$data->additional_documents =  $returnedUploadImageArray[0];
				}
				
                $data->slug = 'event-submission-'.$conventionRegD->id.'-'.time().'-'.rand(100,1000000);
				
				$data->conventionregistration_id	= $conventionRegD->id;
				$data->conventionseason_id			= $conventionRegD->conventionseason_id;
				$data->convention_id				= $conventionRegD->convention_id;
				$data->user_id						= $conventionRegD->user_id;
				$data->season_id 					= $conventionRegD->season_id;
				$data->season_year 					= $conventionRegD->season_year;
				$data->event_id 					= $eventD->id;
				$data->event_id_number 				= $eventD->event_id_number;
				
				$data->group_name 					= $crstudentEventsD->group_name;
				$data->student_id 					= 0;
				
				$data->uploaded_by_user_id 			= $user_id;
				
				if (empty($data->id)) {
					$data->created = date('Y-m-d H:i:s');
				}
                $data->modified = date('Y-m-d H:i:s');
				
				//$this->prx($data);
				
                if ($this->Eventsubmissions->save($data)) {
					
					$this->Flash->success('Events submission completed successfully for group.');
                    $this->redirect(['controller' => 'conventionregistrations', 'action' => 'packageregistration']);
                }
            } else {
                // $this->Flash->error('Please below listed errors.');
            }
        }
        $this->set('eventsubmissions', $eventsubmissions);
    }
	
	public function timeseventsentries($conv_reg_slug=null,$event_slug=null) {

        $this->userLoginCheck();
        $this->multiLoginCheck(['Teacher_Parent','Judge']);
		
        $this->set("title_for_layout", "Times Event Entries" . TITLE_FOR_PAGES);
        $this->viewBuilder()->setLayout('home');
		
		$user_id 	= $this->request->getSession()->read("user_id");
		
		if($this->request->getSession()->read("sess_selected_convention_registration_id")>0)
		{
			$this->set('active_cr_judgeevents','active');
		}
		else
		{
			$this->set('active_convention_registrations','active');
		}
		
		$this->set('conv_reg_slug',$conv_reg_slug);
		
        $msgString = '';

		$user_id = $this->request->getSession()->read("user_id");
		$user_type 	= $this->request->getSession()->read("user_type");
		if (empty($user_id)) {
			return $this->redirect(['controller' => 'users', 'action' => 'login']);
		}
		$userDetails = $this->Users->find()->where(['Users.id' => $user_id])->first();
        $this->set('userDetails', $userDetails);
		
		$conventionRegD = $this->Conventionregistrations->find()->where(['Conventionregistrations.slug' => $conv_reg_slug])->contain(['Conventions'])->first();
		//$this->prx($conventionRegD);
		$this->set('conventionRegD', $conventionRegD);
		if (!$conventionRegD) {
			$this->Flash->error('Invalid convention registration.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'judgeevents']);
		}
		if($conventionRegD->status == 2)
		{
			$this->Flash->error('Sorry, admin has not approved these events yet. You will receive an email and entries will be visible once approved.');
			$this->redirect(['controller' => 'conventionregistrations', 'action' => 'judgeevents',$conv_reg_slug]);
		}
		else
		if($conventionRegD->status == 0)
		{
			$this->Flash->error('Sorry, admin declined your registration. Please contact events team.');
			$this->redirect(['controller' => 'conventionregistrations', 'action' => 'judgeevents',$conv_reg_slug]);
		}
		
		
		$eventD = $this->Events->find()->where(['Events.slug' => $event_slug])->first();
		$this->set('eventD', $eventD);
		if (!$eventD) {
			$this->Flash->error('Invalid event.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'judgeevents', $conv_reg_slug]);
		}

        $condition = array();
		$condition[] = "(Eventsubmissions.convention_id = '".$conventionRegD->convention_id."')";
		$condition[] = "(Eventsubmissions.season_id = '".$conventionRegD->season_id."')";
		$condition[] = "(Eventsubmissions.season_year = '".$conventionRegD->season_year."')";
		$condition[] = "(Eventsubmissions.event_id = '".$eventD->id."')";
		
		if($eventD->group_event_yes_no == 1)
		{
			$condition[] = "(Eventsubmissions.student_id = '0')";
		}
		else
		{
			$condition[] = "(Eventsubmissions.student_id >0)";
		}
		
		$eventsubmissions = $this->Eventsubmissions->find()->where($condition)->contain(["Students","Users"])->order(['Eventsubmissions.id' => 'DESC'])->all();
		$this->set('eventsubmissions',$eventsubmissions);

		$eventsubmissionRows = [];
		if ((int)$eventD->group_event_yes_no === 1) {
			$groupedRows = [];
			foreach ($eventsubmissions as $submission) {
				$groupName = trim((string)($submission->group_name ?? ''));
				$memberIds = $this->Crstudentevents->find()
					->select(['student_id'])
					->where([
						'Crstudentevents.conventionregistration_id' => (int)$submission->conventionregistration_id,
						'Crstudentevents.event_id' => (int)$submission->event_id,
						'Crstudentevents.group_name' => $groupName,
						'Crstudentevents.student_id >' => 0,
					])
					->extract('student_id')
					->map(static function ($sid) {
						return (int)$sid;
					})
					->toList();
				$memberIds = array_values(array_unique(array_filter($memberIds)));
				sort($memberIds);
				$memberSignature = !empty($memberIds) ? implode('-', $memberIds) : 'submission-'.$submission->id;
				$groupKey = $groupName.'|'.$memberSignature;

				if (!isset($groupedRows[$groupKey])) {
					$groupedRows[$groupKey] = [
						'primary' => $submission,
						'submission_ids' => [],
						'convention_registration_ids' => [],
						'school_pairs' => [],
						'member_signature' => $memberSignature,
					];
				}

				$groupedRows[$groupKey]['submission_ids'][] = (int)$submission->id;
				$groupedRows[$groupKey]['convention_registration_ids'][] = (int)$submission->conventionregistration_id;
				$schoolName = trim((string)($submission->Users['first_name'] ?? ''));
				if ($schoolName !== '') {
					$groupedRows[$groupKey]['school_pairs'][(int)$submission->user_id] = $schoolName;
				}
			}

			foreach ($groupedRows as $rowData) {
				$primary = $rowData['primary'];
				$submissionIds = array_values(array_unique(array_filter(array_map('intval', (array)$rowData['submission_ids']))));
				$conventionRegistrationIds = array_values(array_unique(array_filter(array_map('intval', (array)$rowData['convention_registration_ids']))));
				$schoolPairs = (array)($rowData['school_pairs'] ?? []);
				$schoolNames = [];
				$currentSchoolUserId = (int)$conventionRegD->user_id;
				if ($currentSchoolUserId > 0 && isset($schoolPairs[$currentSchoolUserId])) {
					$schoolNames[] = trim((string)$schoolPairs[$currentSchoolUserId]);
				}
				foreach ($schoolPairs as $schoolUserId => $schoolName) {
					if ((int)$schoolUserId === $currentSchoolUserId) {
						continue;
					}
					$schoolNames[] = trim((string)$schoolName);
				}
				$schoolNames = array_values(array_unique(array_filter($schoolNames)));

				$displaySchoolName = implode(' + ', $schoolNames);
				if (count($schoolNames) > 1) {
					$displaySchoolName .= ' combined';
				}

				$eventsubmissionRows[] = [
					'record' => $primary,
					'submission_ids_csv' => implode(',', $submissionIds),
					'convention_registration_ids_csv' => implode(',', $conventionRegistrationIds),
					'display_school_name' => $displaySchoolName !== '' ? $displaySchoolName : (string)($primary->Users['first_name'] ?? ''),
					'member_signature' => (string)$rowData['member_signature'],
				];
			}
		} else {
			foreach ($eventsubmissions as $submission) {
				$eventsubmissionRows[] = [
					'record' => $submission,
					'submission_ids_csv' => (string)((int)$submission->id),
					'convention_registration_ids_csv' => (string)((int)$submission->conventionregistration_id),
					'display_school_name' => (string)($submission->Users['first_name'] ?? ''),
					'member_signature' => '',
				];
			}
		}
		$this->set('eventsubmissionRows', $eventsubmissionRows);
		
		
		if ($this->request->is(['post']))
		{
			//$this->prx($this->request->getData());
			
			$total_records = $this->request->getData()['total_records'];
			
			for($cntrE=1;$cntrE<=$total_records;$cntrE++)
			{
				$time_score			= $this->normalizeTimeScoreForMysql($this->request->getData()['time_score_'.$cntrE] ?? '');
				$submissionIdsRaw = trim((string)($this->request->getData()['submission_ids_'.$cntrE] ?? ($this->request->getData()['submission_id_'.$cntrE] ?? '')));
				$submissionIds = array_values(array_unique(array_filter(array_map('intval', explode(',', str_replace(' ', '', $submissionIdsRaw))), static function ($sid) {
					return $sid > 0;
				})));
				if (empty($submissionIds)) {
					continue;
				}
				if(isset($this->request->getData()['withdrawn_'.$cntrE]))
				{
					$withdraw_yes_no = 1;
				}
				else
				{
					$withdraw_yes_no = 0;
				}
				
				if(empty($time_score))
				{
					$time_score = NULL;
				}	
				
				foreach ($submissionIds as $submission_id) {
					$eventsubmissionD = $this->Eventsubmissions->find()->where(['Eventsubmissions.id' => $submission_id])->contain(["Users","Students"])->first();
					if (!$eventsubmissionD) {
						continue;
					}

					$condCheckJS = array();
					$condCheckJS[] = "(Judgeevaluations.eventsubmission_id = '".$submission_id."')";
					$condCheckJS[] = "(Judgeevaluations.uploaded_by_user_id = '".$user_id."')";

					$checkJS = $this->Judgeevaluations->find()
						->select(['id'])
						->where($condCheckJS)
						->enableHydration(false)
						->first();
					if($checkJS)
					{
						$this->Judgeevaluations->updateAll(
						[
							'time_score' 		=> $time_score,
							'withdraw_yes_no' 	=> $withdraw_yes_no,
						],
						[
							"id" => (int)$checkJS['id']]
						);
					}
					else
					{
						$judgeevaluations = $this->Judgeevaluations->newEntity([]);
						$dataJ = $this->Judgeevaluations->patchEntity($judgeevaluations, array());

						$dataJ->slug 							= "judge-times-event-evaluation-".$eventsubmissionD->id.'-'.time();
						$dataJ->eventsubmission_id				= $eventsubmissionD->id;
						$dataJ->conventionregistration_id		= $eventsubmissionD->conventionregistration_id;
						$dataJ->conventionseason_id				= $eventsubmissionD->conventionseason_id;
						$dataJ->convention_id					= $eventsubmissionD->convention_id;
						$dataJ->user_id							= $eventsubmissionD->user_id;
						$dataJ->season_id						= $eventsubmissionD->season_id;
						$dataJ->season_year						= $eventsubmissionD->season_year;
						$dataJ->event_id						= $eventsubmissionD->event_id;
						$dataJ->event_id_number					= $eventsubmissionD->event_id_number;
						$dataJ->group_name						= $eventsubmissionD->group_name;
						$dataJ->student_id						= $eventsubmissionD->student_id;
						$dataJ->uploaded_by_user_id				= $user_id;
						$dataJ->time_score						= $time_score;
						$dataJ->withdraw_yes_no					= $withdraw_yes_no;
						$dataJ->created 						= date('Y-m-d H:i:s');

						$this->Judgeevaluations->save($dataJ);
					}
				}
			}
			
			$this->Flash->success('Times score submitted successfully.');
			$this->redirect(['controller' => 'eventsubmissions', 'action' => 'timeseventsentries',$conv_reg_slug,$eventD->slug]);
			
		}
    }
	
	public function distanceseventsentries($conv_reg_slug=null,$event_slug=null) {

        $this->set("title_for_layout", "Distance Event Entries" . TITLE_FOR_PAGES);
        $this->viewBuilder()->setLayout('home');
		
		$user_id 	= $this->request->getSession()->read("user_id");
		$user_type 	= $this->request->getSession()->read("user_type");
		if (empty($user_id)) {
			$this->Flash->error('Please Login.');
			return $this->redirect(['controller' => 'users', 'action' => 'logout']);
		}
		if (!in_array($user_type, ['Teacher_Parent', 'Judge'], true)) {
			$this->Flash->error('Please Login.');
			return $this->redirect(['controller' => 'users', 'action' => 'logout']);
		}
		
		if($this->request->getSession()->read("sess_selected_convention_registration_id")>0)
		{
			$this->set('active_cr_judgeevents','active');
		}
		else
		{
			$this->set('active_convention_registrations','active');
		}
		
		$this->set('conv_reg_slug',$conv_reg_slug);
		
        $msgString = '';

		$user_id = $this->request->getSession()->read("user_id");
		$userDetails = $this->Users->find()->where(['Users.id' => $user_id])->first();
        $this->set('userDetails', $userDetails);
		
		$conventionRegD = $this->Conventionregistrations->find()->where(['Conventionregistrations.slug' => $conv_reg_slug])->contain(['Conventions'])->first();
		if (!$conventionRegD) {
			$this->Flash->error('Invalid convention registration.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'index']);
		}
		//$this->prx($conventionRegD);
		$this->set('conventionRegD', $conventionRegD);
		if($conventionRegD->status == 2)
		{
			$this->Flash->error('Sorry, admin has not approved these events yet. You will receive an email and entries will be visible once approved.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'judgeevents',$conv_reg_slug]);
		}
		else
		if($conventionRegD->status == 0)
		{
			$this->Flash->error('Sorry, admin declined your registration. Please contact events team.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'judgeevents',$conv_reg_slug]);
		}
		
		
		$eventD = $this->Events->find()->where(['Events.slug' => $event_slug])->first();
		if (!$eventD) {
			$this->Flash->error('Invalid event.');
			return $this->redirect(['controller' => 'conventionregistrations', 'action' => 'judgeevents', $conv_reg_slug]);
		}
		$soccerKickDistanceEventNumbers = ['112', '142', '172', '212', '242', '272'];
		$isSoccerKickDistanceEvent = in_array((string)$eventD->event_id_number, $soccerKickDistanceEventNumbers, true);
		$this->set('eventD', $eventD);

        $condition = array();
		$condition[] = "(Eventsubmissions.convention_id = '".$conventionRegD->convention_id."')";
		$condition[] = "(Eventsubmissions.season_id = '".$conventionRegD->season_id."')";
		$condition[] = "(Eventsubmissions.season_year = '".$conventionRegD->season_year."')";
		$condition[] = "(Eventsubmissions.event_id = '".$eventD->id."')";
		
		if($eventD->group_event_yes_no == 1)
		{
			$condition[] = "(Eventsubmissions.student_id = '0')";
		}
		else
		{
			$condition[] = "(Eventsubmissions.student_id >0)";
		}
		
		$eventsubmissions = $this->Eventsubmissions->find()->where($condition)->contain(["Students","Users"])->order(['Eventsubmissions.id' => 'DESC'])->all();
		$this->set('eventsubmissions',$eventsubmissions);
		
		
		if ($this->request->is(['post']))
		{
			//$this->prx($this->request->getData());
			
			$total_records = $this->request->getData()['total_records'];
			
			for($cntrE=1;$cntrE<=$total_records;$cntrE++)
			{
				$submission_id = $this->request->getData()['submission_id_'.$cntrE];

				if(isset($this->request->getData()['withdrawn_'.$cntrE]))
					$withdraw_yes_no = 1;
				else
					$withdraw_yes_no = 0;

				$place = $this->request->getData()['place_'.$cntrE] ?? null;
				$place = (!empty($place) && is_numeric($place)) ? (int)$place : null;

				$distance_attempt_1 = $distance_attempt_2 = $distance_attempt_3 = null;
				$soccer_kick_best_kick = null;
				$soccer_kick_all_kicks = null;
				$bestScore = null;

				if ($isSoccerKickDistanceEvent) {
					$submittedGrid = json_decode((string)($this->request->getData()['soccer_kick_all_kicks_'.$cntrE] ?? ''), true);
					$allowedDistances = [10, 15, 20, 25, 30, 35, 40, 45, 50];
					$normalizedGrid = ['1' => [], '2' => [], '3' => [], 'outcomes' => []];
					if (is_array($submittedGrid)) {
						$legacySuccessesByAttempt = ['1' => [], '2' => [], '3' => []];
						$submittedOutcomes = isset($submittedGrid['outcomes']) && is_array($submittedGrid['outcomes'])
							? $submittedGrid['outcomes']
							: [];
						foreach ($submittedGrid as $legacyKick) {
							if (is_string($legacyKick) && preg_match('/^(10|15|20|25|30|35|40|45|50)_([123])$/', $legacyKick, $matches)) {
								$legacySuccessesByAttempt[$matches[2]][] = (int)$matches[1];
							}
						}
						foreach ([1, 2, 3] as $att) {
							$attemptKey = (string)$att;
							$legacySuccesses = isset($submittedGrid[$attemptKey]) && is_array($submittedGrid[$attemptKey])
								? array_map('intval', $submittedGrid[$attemptKey])
								: $legacySuccessesByAttempt[$attemptKey];
							foreach ($allowedDistances as $distance) {
								$distanceOutcomes = $submittedOutcomes[(string)$distance] ?? null;
								$outcome = is_array($distanceOutcomes) ? ($distanceOutcomes[$attemptKey] ?? null) : null;
								if (in_array($outcome, ['O', 'X'], true)) {
									$normalizedGrid['outcomes'][(string)$distance][$attemptKey] = $outcome;
								} elseif (in_array($distance, $legacySuccesses, true)) {
									$normalizedGrid['outcomes'][(string)$distance][$attemptKey] = 'O';
								}
								if (($normalizedGrid['outcomes'][(string)$distance][$attemptKey] ?? null) === 'O') {
									$normalizedGrid[$attemptKey][] = $distance;
								}
							}
							$normalizedGrid[$attemptKey] = array_values(array_unique($normalizedGrid[$attemptKey]));
							if (!empty($normalizedGrid[$attemptKey])) {
								$varName = 'distance_attempt_'.$att;
								$$varName = max($normalizedGrid[$attemptKey]);
							}
						}
						$kickoffData = $submittedGrid['kickoff'] ?? null;
						$kickoffDistance = is_array($kickoffData) ? (int)($kickoffData['distance'] ?? 0) : 0;
						if (is_array($kickoffData) && in_array($kickoffDistance, $allowedDistances, true)) {
							$kickoffAttempts = [];
							$submittedKickoffAttempts = isset($kickoffData['attempts']) && is_array($kickoffData['attempts'])
								? $kickoffData['attempts']
								: [];
							foreach ([1, 2, 3] as $kickoffAttempt) {
								$attemptKey = (string)$kickoffAttempt;
								$outcome = $submittedKickoffAttempts[$attemptKey] ?? null;
								if (in_array($outcome, ['O', 'X'], true)) {
									$kickoffAttempts[$attemptKey] = $outcome;
								}
							}
							$normalizedGrid['kickoff'] = [
								'distance' => $kickoffDistance,
								'attempts' => $kickoffAttempts,
							];
						}
					}
					$soccer_kick_all_kicks = json_encode($normalizedGrid);
					$successfulDistances = array_merge($normalizedGrid['1'], $normalizedGrid['2'], $normalizedGrid['3']);
					$soccer_kick_best_kick = !empty($successfulDistances) ? max($successfulDistances) : null;
					$bestScore = $soccer_kick_best_kick;
				} else {
					foreach ([1, 2, 3] as $att) {
						$fieldName = 'distance_attempt_'.$att.'_'.$cntrE;
						$fieldValue = $this->request->getData()[$fieldName] ?? null;
						if ($fieldValue !== null && $fieldValue !== '' && is_numeric($fieldValue)) {
							$varName = 'distance_attempt_'.$att;
							$$varName = (float)$fieldValue;
						}
					}
				}

				if (($bestScore === null || $bestScore === '' || (float)$bestScore <= 0) &&
					($distance_attempt_1 !== null || $distance_attempt_2 !== null || $distance_attempt_3 !== null)) {
					$attemptCandidates = array_filter([
						$distance_attempt_1,
						$distance_attempt_2,
						$distance_attempt_3,
					], function ($v) {
						return $v !== null && $v !== '';
					});
					if (!empty($attemptCandidates)) {
						$bestScore = max(array_map('floatval', $attemptCandidates));
					}
				}
				
				
				$eventsubmissionD 	= $this->Eventsubmissions->find()->where(['Eventsubmissions.id' => $submission_id])->contain(["Users","Students"])->first();
				
				// now check if this judge submitted time score for this event submission
				$condCheckJS = array();
				$condCheckJS[] = "(Judgeevaluations.eventsubmission_id = '".$submission_id."')";
				$condCheckJS[] = "(Judgeevaluations.uploaded_by_user_id = '".$user_id."')";
				
				$checkJS = $this->Judgeevaluations->find()->where($condCheckJS)->first();
				if($checkJS)
				{
					// update record
					$this->Judgeevaluations->updateAll(
					[
						'distance_score' 			=> $bestScore,
						'distance_attempt_1' 		=> $distance_attempt_1,
						'distance_attempt_2' 		=> $distance_attempt_2,
						'distance_attempt_3' 		=> $distance_attempt_3,
						'soccer_kick_best_kick'		=> $soccer_kick_best_kick,
						'soccer_kick_all_kicks'		=> $soccer_kick_all_kicks,
						'place'						=> $place,
						'withdraw_yes_no' 			=> $withdraw_yes_no,
					], 
					[
						"id" => $checkJS->id]
					);
				}
				else
				{
					// insert new record
					$judgeevaluations = $this->Judgeevaluations->newEntity([]);
					$dataJ = $this->Judgeevaluations->patchEntity($judgeevaluations, array());
					
					$dataJ->slug 							= "judge-times-event-evaluation-".$eventsubmissionD->id.'-'.time();
					$dataJ->eventsubmission_id				= $eventsubmissionD->id;
					$dataJ->conventionregistration_id		= $eventsubmissionD->conventionregistration_id;
					$dataJ->conventionseason_id				= $eventsubmissionD->conventionseason_id;
					$dataJ->convention_id					= $eventsubmissionD->convention_id;
					$dataJ->user_id							= $eventsubmissionD->user_id;
					$dataJ->season_id						= $eventsubmissionD->season_id;
					$dataJ->season_year						= $eventsubmissionD->season_year;
					$dataJ->event_id						= $eventsubmissionD->event_id;
					$dataJ->event_id_number					= $eventsubmissionD->event_id_number;
					$dataJ->group_name						= $eventsubmissionD->group_name;
					$dataJ->student_id						= $eventsubmissionD->student_id;
					$dataJ->uploaded_by_user_id				= $user_id;
					
					$dataJ->distance_attempt_1				= $distance_attempt_1;
					$dataJ->distance_attempt_2				= $distance_attempt_2;
					$dataJ->distance_attempt_3				= $distance_attempt_3;
					$dataJ->distance_score					= $bestScore;
					$dataJ->soccer_kick_best_kick			= $soccer_kick_best_kick;
					$dataJ->soccer_kick_all_kicks			= $soccer_kick_all_kicks;
					$dataJ->place							= $place;
					
					$dataJ->withdraw_yes_no					= $withdraw_yes_no;
					$dataJ->created 						= date('Y-m-d H:i:s');

					$resultJ = $this->Judgeevaluations->save($dataJ);
					
				}
			}
			
			$this->Flash->success('Distance score submitted successfully.');
			$this->redirect(['controller' => 'eventsubmissions', 'action' => 'distanceseventsentries',$conv_reg_slug,$eventD->slug]);
			
		}
    }
	
	public function scoreseventsentries($conv_reg_slug=null,$event_slug=null) {

        $this->userLoginCheck();
        $this->multiLoginCheck(['Teacher_Parent','Judge']);
		
        $this->set("title_for_layout", "Scores Event Entries" . TITLE_FOR_PAGES);
        $this->viewBuilder()->setLayout('home');
		
		$user_id 	= $this->request->getSession()->read("user_id");
		
		if($this->request->getSession()->read("sess_selected_convention_registration_id")>0)
		{
			$this->set('active_cr_judgeevents','active');
		}
		else
		{
			$this->set('active_convention_registrations','active');
		}
		
		$this->set('conv_reg_slug',$conv_reg_slug);
		
        $msgString = '';

		$user_id = $this->request->getSession()->read("user_id");
		$user_type 	= $this->request->getSession()->read("user_type");
		if (empty($user_id)) {
			$this->Flash->error('Your session has expired. Please login again.');
			return $this->redirect(['controller' => 'users', 'action' => 'login']);
		}
		$userDetails = $this->Users->find()->where(['Users.id' => $user_id])->first();
		if (!$userDetails) {
			$this->Flash->error('Unable to find your user account. Please login again.');
			return $this->redirect(['controller' => 'users', 'action' => 'login']);
		}
        $this->set('userDetails', $userDetails);
		
		$conventionRegD = $this->Conventionregistrations->find()->where(['Conventionregistrations.slug' => $conv_reg_slug])->contain(['Conventions'])->first();
		//$this->prx($conventionRegD);
		$this->set('conventionRegD', $conventionRegD);
		if($conventionRegD->status == 2)
		{
			$this->Flash->error('Sorry, admin has not approved these events yet. You will receive an email and entries will be visible once approved.');
			$this->redirect(['controller' => 'conventionregistrations', 'action' => 'judgeevents',$conv_reg_slug]);
		}
		else
		if($conventionRegD->status == 0)
		{
			$this->Flash->error('Sorry, admin declined your registration. Please contact events team.');
			$this->redirect(['controller' => 'conventionregistrations', 'action' => 'judgeevents',$conv_reg_slug]);
		}
		
		
		$eventD = $this->Events->find()->where(['Events.slug' => $event_slug])->first();
		$this->set('eventD', $eventD);

        $condition = array();
		$condition[] = "(Eventsubmissions.convention_id = '".$conventionRegD->convention_id."')";
		$condition[] = "(Eventsubmissions.season_id = '".$conventionRegD->season_id."')";
		$condition[] = "(Eventsubmissions.season_year = '".$conventionRegD->season_year."')";
		$condition[] = "(Eventsubmissions.event_id = '".$eventD->id."')";
		
		if($eventD->group_event_yes_no == 1)
		{
			$condition[] = "(Eventsubmissions.student_id = '0')";
		}
		else
		{
			$condition[] = "(Eventsubmissions.student_id >0)";
		}
		
		$eventsubmissions = $this->Eventsubmissions->find()->where($condition)->contain(["Students","Users"])->order(['Eventsubmissions.id' => 'DESC'])->all();
		$this->set('eventsubmissions',$eventsubmissions);
		
		
		if ($this->request->is(['post']))
		{
			//$this->prx($this->request->getData());
			
			$posScoresArr = array(
				'pos_1' => 5,
				'pos_2' => 15,
				'pos_3' => 20,
				'pos_4' => 10,
				'pos_5' => 20,
				'pos_6' => 30,
				'pos_7' => 15,
				'pos_8' => 25,
				'pos_9' => 5
			);
			
			$total_records = $this->request->getData()['total_records'];
			
			for($cntrE=1;$cntrE<=$total_records;$cntrE++)
			{	
				$submission_id			= $this->request->getData()['submission_id_'.$cntrE];
				
				$arrPosYN 		= array();
				$arrPosScore 	= array();
				$overallScore 	= 0;
				
				for($cntPPS=1;$cntPPS<=9;$cntPPS++)
				{
					if(isset($this->request->getData()['pos_'.$cntPPS.'_'.$cntrE]) && !empty($this->request->getData()['pos_'.$cntPPS.'_'.$cntrE]))
					{
						$arrPosYN[$cntPPS] 			= 1;
						$arrPosScore[$cntPPS] 		= $posScoresArr['pos_'.$cntPPS];
						
						$overallScore = $overallScore+$posScoresArr['pos_'.$cntPPS];
					}
					else
					{
						$arrPosYN[$cntPPS] 			= 0;
						$arrPosScore[$cntPPS] 		= NULL;
					}
				}
				
				
				
				$comp_choice_pos_1				= $this->request->getData()['comp_choice_pos_1_'.$cntrE];
				$comp_choice_pos_2				= $this->request->getData()['comp_choice_pos_2_'.$cntrE];
				$comp_choice_pos_3				= $this->request->getData()['comp_choice_pos_3_'.$cntrE];
				
				
				if(isset($comp_choice_pos_1) && !empty($comp_choice_pos_1))
				{
					$comp_choice_pos_1_score 			= $posScoresArr['pos_'.$comp_choice_pos_1];
					$overallScore 						= $overallScore+$comp_choice_pos_1_score;
				}
				else
				{
					$comp_choice_pos_1_score 			= NULL;
				}
				
				if(isset($comp_choice_pos_2) && !empty($comp_choice_pos_2))
				{
					$comp_choice_pos_2_score 			= $posScoresArr['pos_'.$comp_choice_pos_2];
					$overallScore 						= $overallScore+$comp_choice_pos_2_score;
				}
				else
				{
					$comp_choice_pos_2_score 			= NULL;
				}
				
				if(isset($comp_choice_pos_3) && !empty($comp_choice_pos_3))
				{
					$comp_choice_pos_3_score 			= $posScoresArr['pos_'.$comp_choice_pos_3];
					$overallScore 						= $overallScore+$comp_choice_pos_3_score;
				}
				else
				{
					$comp_choice_pos_3_score 			= NULL;
				}
				
				
				
				if(isset($this->request->getData()['withdrawn_'.$cntrE]))
				{
					$withdraw_yes_no = 1;
				}
				else
				{
					$withdraw_yes_no = 0;
				}
				
				$eventsubmissionD 	= $this->Eventsubmissions->find()->where(['Eventsubmissions.id' => $submission_id])->contain(["Users","Students"])->first();
				
				// now check if this judge submitted time score for this event submission
				$condCheckJS = array();
				$condCheckJS[] = "(Judgeevaluations.eventsubmission_id = '".$submission_id."')";
				$condCheckJS[] = "(Judgeevaluations.uploaded_by_user_id = '".$user_id."')";
				
				$checkJS = $this->Judgeevaluations->find()->where($condCheckJS)->first();
				if($checkJS)
				{
					// update record
					$this->Judgeevaluations->updateAll(
					[
						'pos_1_yes_no' 					=> $arrPosYN[1],
						'pos_1_score' 					=> $arrPosScore[1],
						'pos_2_yes_no' 					=> $arrPosYN[2],
						'pos_2_score' 					=> $arrPosScore[2],
						'pos_3_yes_no' 					=> $arrPosYN[3],
						'pos_3_score' 					=> $arrPosScore[3],
						'pos_4_yes_no' 					=> $arrPosYN[4],
						'pos_4_score' 					=> $arrPosScore[4],
						'pos_5_yes_no' 					=> $arrPosYN[5],
						'pos_5_score' 					=> $arrPosScore[5],
						'pos_6_yes_no' 					=> $arrPosYN[6],
						'pos_6_score' 					=> $arrPosScore[6],
						'pos_7_yes_no' 					=> $arrPosYN[7],
						'pos_7_score' 					=> $arrPosScore[7],
						'pos_8_yes_no' 					=> $arrPosYN[8],
						'pos_8_score' 					=> $arrPosScore[8],
						'pos_9_yes_no' 					=> $arrPosYN[9],
						'pos_9_score' 					=> $arrPosScore[9],
						
						'comp_choice_pos_1' 			=> $comp_choice_pos_1,
						'comp_choice_pos_1_score' 		=> $comp_choice_pos_1_score,
						'comp_choice_pos_2' 			=> $comp_choice_pos_2,
						'comp_choice_pos_2_score' 		=> $comp_choice_pos_2_score,
						'comp_choice_pos_3' 			=> $comp_choice_pos_3,
						'comp_choice_pos_3_score' 		=> $comp_choice_pos_3_score,
						
						'all_pos_score' 				=> $overallScore,
						
						'withdraw_yes_no' 			=> $withdraw_yes_no,
					], 
					[
						"id" => $checkJS->id]
					);
				}
				else
				{
					// insert new record
					$judgeevaluations = $this->Judgeevaluations->newEntity([]);
					$dataJ = $this->Judgeevaluations->patchEntity($judgeevaluations, array());
					
					$dataJ->slug 							= "judge-times-event-evaluation-".$eventsubmissionD->id.'-'.time();
					$dataJ->eventsubmission_id				= $eventsubmissionD->id;
					$dataJ->conventionregistration_id		= $eventsubmissionD->conventionregistration_id;
					$dataJ->conventionseason_id				= $eventsubmissionD->conventionseason_id;
					$dataJ->convention_id					= $eventsubmissionD->convention_id;
					$dataJ->user_id							= $eventsubmissionD->user_id;
					$dataJ->season_id						= $eventsubmissionD->season_id;
					$dataJ->season_year						= $eventsubmissionD->season_year;
					$dataJ->event_id						= $eventsubmissionD->event_id;
					$dataJ->event_id_number					= $eventsubmissionD->event_id_number;
					$dataJ->group_name						= $eventsubmissionD->group_name;
					$dataJ->student_id						= $eventsubmissionD->student_id;
					$dataJ->uploaded_by_user_id				= $user_id;
					
					$dataJ->pos_1_yes_no					= $arrPosYN[1];
					$dataJ->pos_1_score						= $arrPosScore[1];
					$dataJ->pos_2_yes_no					= $arrPosYN[2];
					$dataJ->pos_2_score						= $arrPosScore[2];
					$dataJ->pos_3_yes_no					= $arrPosYN[3];
					$dataJ->pos_3_score						= $arrPosScore[3];
					$dataJ->pos_4_yes_no					= $arrPosYN[4];
					$dataJ->pos_4_score						= $arrPosScore[4];
					$dataJ->pos_5_yes_no					= $arrPosYN[5];
					$dataJ->pos_5_score						= $arrPosScore[5];
					$dataJ->pos_6_yes_no					= $arrPosYN[6];
					$dataJ->pos_6_score						= $arrPosScore[6];
					$dataJ->pos_7_yes_no					= $arrPosYN[7];
					$dataJ->pos_8_score						= $arrPosScore[8];
					$dataJ->pos_8_score						= $arrPosScore[8];
					$dataJ->pos_9_yes_no					= $arrPosYN[9];
					$dataJ->pos_2_score						= $arrPosScore[9];
					
					$dataJ->comp_choice_pos_1				= $comp_choice_pos_1;
					$dataJ->comp_choice_pos_1_score			= $comp_choice_pos_1_score;
					$dataJ->comp_choice_pos_2				= $comp_choice_pos_2;
					$dataJ->comp_choice_pos_2_score			= $comp_choice_pos_2_score;
					$dataJ->comp_choice_pos_3				= $comp_choice_pos_3;
					$dataJ->comp_choice_pos_3_score			= $comp_choice_pos_3_score;
					
					$dataJ->all_pos_score					= $overallScore;
					
					$dataJ->withdraw_yes_no					= $withdraw_yes_no;
					$dataJ->created 						= date('Y-m-d H:i:s');

					$resultJ = $this->Judgeevaluations->save($dataJ);
					
				}
			}
			
			$this->Flash->success('Position score submitted successfully.');
			$this->redirect(['controller' => 'eventsubmissions', 'action' => 'scoreseventsentries',$conv_reg_slug,$eventD->slug]);
			
		}
    }
	
	public function soccerkickeventsentries($conv_reg_slug=null,$event_slug=null) {

        $this->userLoginCheck();
        $this->multiLoginCheck(['Teacher_Parent','Judge']);
		return $this->redirect([
			'controller' => 'eventsubmissions',
			'action' => 'distanceseventsentries',
			$conv_reg_slug,
			$event_slug,
		]);
		
        $this->set("title_for_layout", "Soccer Kick Event Entries" . TITLE_FOR_PAGES);
        $this->viewBuilder()->setLayout('home');
		
		$user_id 	= $this->request->getSession()->read("user_id");
		
		if($this->request->getSession()->read("sess_selected_convention_registration_id")>0)
		{
			$this->set('active_cr_judgeevents','active');
		}
		else
		{
			$this->set('active_convention_registrations','active');
		}
		
		$this->set('conv_reg_slug',$conv_reg_slug);
		
        $msgString = '';

		$user_id = $this->request->getSession()->read("user_id");
		$user_type 	= $this->request->getSession()->read("user_type");
		if (empty($user_id)) {
			$this->Flash->error('Your session has expired. Please login again.');
			return $this->redirect(['controller' => 'users', 'action' => 'login']);
		}
		$userDetails = $this->Users->find()->where(['Users.id' => $user_id])->first();
		if (!$userDetails) {
			$this->Flash->error('Unable to find your user account. Please login again.');
			return $this->redirect(['controller' => 'users', 'action' => 'login']);
		}
        $this->set('userDetails', $userDetails);
		
		$conventionRegD = $this->Conventionregistrations->find()->where(['Conventionregistrations.slug' => $conv_reg_slug])->contain(['Conventions'])->first();
		//$this->prx($conventionRegD);
		$this->set('conventionRegD', $conventionRegD);
		if($conventionRegD->status == 2)
		{
			$this->Flash->error('Sorry, admin has not approved these events yet. You will receive an email and entries will be visible once approved.');
			$this->redirect(['controller' => 'conventionregistrations', 'action' => 'judgeevents',$conv_reg_slug]);
		}
		else
		if($conventionRegD->status == 0)
		{
			$this->Flash->error('Sorry, admin declined your registration. Please contact events team.');
			$this->redirect(['controller' => 'conventionregistrations', 'action' => 'judgeevents',$conv_reg_slug]);
		}
		
		
		$eventD = $this->Events->find()->where(['Events.slug' => $event_slug])->first();
		$this->set('eventD', $eventD);

        $condition = array();
		$condition[] = "(Eventsubmissions.convention_id = '".$conventionRegD->convention_id."')";
		$condition[] = "(Eventsubmissions.season_id = '".$conventionRegD->season_id."')";
		$condition[] = "(Eventsubmissions.season_year = '".$conventionRegD->season_year."')";
		$condition[] = "(Eventsubmissions.event_id = '".$eventD->id."')";
		
		if($eventD->group_event_yes_no == 1)
		{
			$condition[] = "(Eventsubmissions.student_id = '0')";
		}
		else
		{
			$condition[] = "(Eventsubmissions.student_id >0)";
		}
		
		$eventsubmissions = $this->Eventsubmissions->find()->where($condition)->contain(["Students","Users"])->order(['Eventsubmissions.id' => 'DESC'])->all();
		$this->set('eventsubmissions',$eventsubmissions);
		
		
		if ($this->request->is(['post']))
		{
			//$this->prx($this->request->getData());
			
			$total_records = $this->request->getData()['total_records'];
			
			for($cntrE=1;$cntrE<=$total_records;$cntrE++)
			{
				$arrBestKick = array();
				$arrAllKicks = array();
				
				$submission_id			= $this->request->getData()['submission_id_'.$cntrE];
				if(isset($this->request->getData()['withdrawn_'.$cntrE]))
				{
					$withdraw_yes_no = 1;
				}
				else
				{
					$withdraw_yes_no = 0;
				}
				
				for($cntrKD=10;$cntrKD<=50;$cntrKD+=5)
				{
					for($cntrAtt=1;$cntrAtt<=3;$cntrAtt++)
					{
						$nameCB = $cntrE.'_'.$cntrKD.'m_'.$cntrAtt;
						
						$valSK 	= $this->request->getData()[$nameCB];
						
						if(isset($valSK) && $valSK>0)
						{
							$arrBestKick[] = $cntrKD;
							$arrAllKicks[] = $cntrKD.'_'.$cntrAtt;
						}
					}
				}
				
				if(count($arrBestKick))
				{
					$bestKick = max($arrBestKick);
				}
				else
				{
					$bestKick = NULL;
				}
				
				$eventsubmissionD 	= $this->Eventsubmissions->find()->where(['Eventsubmissions.id' => $submission_id])->contain(["Users","Students"])->first();
				
				// now check if this judge submitted time score for this event submission
				$condCheckJS = array();
				$condCheckJS[] = "(Judgeevaluations.eventsubmission_id = '".$submission_id."')";
				$condCheckJS[] = "(Judgeevaluations.uploaded_by_user_id = '".$user_id."')";
				
				$checkJS = $this->Judgeevaluations->find()->where($condCheckJS)->first();
				if($checkJS)
				{
					// update record
					$this->Judgeevaluations->updateAll(
					[
						'soccer_kick_best_kick' 	=> $bestKick,
						'soccer_kick_all_kicks' 	=> json_encode($arrAllKicks),
						'withdraw_yes_no' 			=> $withdraw_yes_no,
					], 
					[
						"id" => $checkJS->id]
					);
				}
				else
				{
					// insert new record
					$judgeevaluations = $this->Judgeevaluations->newEntity([]);
					$dataJ = $this->Judgeevaluations->patchEntity($judgeevaluations, array());
					
					$dataJ->slug 							= "judge-times-event-evaluation-".$eventsubmissionD->id.'-'.time();
					$dataJ->eventsubmission_id				= $eventsubmissionD->id;
					$dataJ->conventionregistration_id		= $eventsubmissionD->conventionregistration_id;
					$dataJ->conventionseason_id				= $eventsubmissionD->conventionseason_id;
					$dataJ->convention_id					= $eventsubmissionD->convention_id;
					$dataJ->user_id							= $eventsubmissionD->user_id;
					$dataJ->season_id						= $eventsubmissionD->season_id;
					$dataJ->season_year						= $eventsubmissionD->season_year;
					$dataJ->event_id						= $eventsubmissionD->event_id;
					$dataJ->event_id_number					= $eventsubmissionD->event_id_number;
					$dataJ->group_name						= $eventsubmissionD->group_name;
					$dataJ->student_id						= $eventsubmissionD->student_id;
					$dataJ->uploaded_by_user_id				= $user_id;
					
					$dataJ->soccer_kick_best_kick			= $bestKick;
					$dataJ->soccer_kick_all_kicks			= json_encode($arrAllKicks);
					
					$dataJ->withdraw_yes_no					= $withdraw_yes_no;
					$dataJ->created 						= date('Y-m-d H:i:s');

					$resultJ = $this->Judgeevaluations->save($dataJ);
					
				}
			}
			
			$this->Flash->success('Soccer kick scores submitted successfully.');
			$this->redirect(['controller' => 'eventsubmissions', 'action' => 'soccerkickeventsentries',$conv_reg_slug,$eventD->slug]);
			
		}
    }
	
	// Spellings
	public function spellingseventsentries($conv_reg_slug=null,$event_slug=null) {

        $this->userLoginCheck();
        $this->multiLoginCheck(['Teacher_Parent','Judge']);
		
        $this->set("title_for_layout", "Spelling Event Entries" . TITLE_FOR_PAGES);
        $this->viewBuilder()->setLayout('home');
		
		$user_id 	= $this->request->getSession()->read("user_id");
		
		if($this->request->getSession()->read("sess_selected_convention_registration_id")>0)
		{
			$this->set('active_cr_judgeevents','active');
		}
		else
		{
			$this->set('active_convention_registrations','active');
		}
		
		$this->set('conv_reg_slug',$conv_reg_slug);
		
        $msgString = '';

		$user_id = $this->request->getSession()->read("user_id");
		$user_type 	= $this->request->getSession()->read("user_type");
		if (empty($user_id)) {
			$this->Flash->error('Your session has expired. Please login again.');
			return $this->redirect(['controller' => 'users', 'action' => 'login']);
		}
		$userDetails = $this->Users->find()->where(['Users.id' => $user_id])->first();
		if (!$userDetails) {
			$this->Flash->error('Unable to find your user account. Please login again.');
			return $this->redirect(['controller' => 'users', 'action' => 'login']);
		}
        $this->set('userDetails', $userDetails);
		
		$conventionRegD = $this->Conventionregistrations->find()->where(['Conventionregistrations.slug' => $conv_reg_slug])->contain(['Conventions'])->first();
		//$this->prx($conventionRegD);
		$this->set('conventionRegD', $conventionRegD);
		if($conventionRegD->status == 2)
		{
			$this->Flash->error('Sorry, admin has not approved these events yet. You will receive an email and entries will be visible once approved.');
			$this->redirect(['controller' => 'conventionregistrations', 'action' => 'judgeevents',$conv_reg_slug]);
		}
		else
		if($conventionRegD->status == 0)
		{
			$this->Flash->error('Sorry, admin declined your registration. Please contact events team.');
			$this->redirect(['controller' => 'conventionregistrations', 'action' => 'judgeevents',$conv_reg_slug]);
		}
		
		
		$eventD = $this->Events->find()->where(['Events.slug' => $event_slug])->first();
		$this->set('eventD', $eventD);

        $condition = array();
		$condition[] = "(Eventsubmissions.convention_id = '".$conventionRegD->convention_id."')";
		$condition[] = "(Eventsubmissions.season_id = '".$conventionRegD->season_id."')";
		$condition[] = "(Eventsubmissions.season_year = '".$conventionRegD->season_year."')";
		$condition[] = "(Eventsubmissions.event_id = '".$eventD->id."')";
		
		if($eventD->group_event_yes_no == 1)
		{
			$condition[] = "(Eventsubmissions.student_id = '0')";
		}
		else
		{
			$condition[] = "(Eventsubmissions.student_id >0)";
		}
		
		$eventsubmissions = $this->Eventsubmissions->find()->where($condition)->contain(["Students","Users"])->order(['Eventsubmissions.id' => 'DESC'])->all();
		$this->set('eventsubmissions',$eventsubmissions);
		
		
		if ($this->request->is(['post']))
		{
			//$this->prx($this->request->getData());
			
			$total_records = $this->request->getData()['total_records'];
			
			for($cntrE=1;$cntrE<=$total_records;$cntrE++)
			{	
				$submission_id			= $this->request->getData()['submission_id_'.$cntrE];
				if(isset($this->request->getData()['withdrawn_'.$cntrE]))
				{
					$withdraw_yes_no = 1;
				}
				else
				{
					$withdraw_yes_no = 0;
				}
				
				
				$eventsubmissionD 	= $this->Eventsubmissions->find()->where(['Eventsubmissions.id' => $submission_id])->contain(["Users","Students"])->first();
				
				// now check if this judge submitted time score for this event submission
				$condCheckJS = array();
				$condCheckJS[] = "(Judgeevaluations.eventsubmission_id = '".$submission_id."')";
				$condCheckJS[] = "(Judgeevaluations.uploaded_by_user_id = '".$user_id."')";
				
				$checkJS = $this->Judgeevaluations->find()->where($condCheckJS)->first();
				if($checkJS)
				{
					// update record
					$this->Judgeevaluations->updateAll(
					[
						'spelling_score' 	=> $this->request->getData()['spelling_score_'.$cntrE],
						'withdraw_yes_no' 	=> $withdraw_yes_no,
					], 
					[
						"id" => $checkJS->id]
					);
				}
				else
				{
					// insert new record
					$judgeevaluations = $this->Judgeevaluations->newEntity([]);
					$dataJ = $this->Judgeevaluations->patchEntity($judgeevaluations, array());
					
					$dataJ->slug 							= "judge-times-event-evaluation-".$eventsubmissionD->id.'-'.time();
					$dataJ->eventsubmission_id				= $eventsubmissionD->id;
					$dataJ->conventionregistration_id		= $eventsubmissionD->conventionregistration_id;
					$dataJ->conventionseason_id				= $eventsubmissionD->conventionseason_id;
					$dataJ->convention_id					= $eventsubmissionD->convention_id;
					$dataJ->user_id							= $eventsubmissionD->user_id;
					$dataJ->season_id						= $eventsubmissionD->season_id;
					$dataJ->season_year						= $eventsubmissionD->season_year;
					$dataJ->event_id						= $eventsubmissionD->event_id;
					$dataJ->event_id_number					= $eventsubmissionD->event_id_number;
					$dataJ->group_name						= $eventsubmissionD->group_name;
					$dataJ->student_id						= $eventsubmissionD->student_id;
					$dataJ->uploaded_by_user_id				= $user_id;
					
					$dataJ->spelling_score					= $this->request->getData()['spelling_score_'.$cntrE];
					$dataJ->withdraw_yes_no					= $withdraw_yes_no;
					$dataJ->created 						= date('Y-m-d H:i:s');

					$resultJ = $this->Judgeevaluations->save($dataJ);
					
				}
			}
			
			$this->Flash->success('Spelling scores submitted successfully.');
			$this->redirect(['controller' => 'eventsubmissions', 'action' => 'spellingseventsentries',$conv_reg_slug,$eventD->slug]);
			
		}
    }

}

?>
