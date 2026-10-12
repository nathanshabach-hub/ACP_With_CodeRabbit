<?php
use Cake\ORM\TableRegistry;
$this->Conventionregistrations = TableRegistry::getTableLocator()->get('Conventionregistrations');
$this->Eventsubmissions = TableRegistry::getTableLocator()->get('Eventsubmissions');
$this->Judgeevaluations = TableRegistry::getTableLocator()->get('Judgeevaluations');
$this->Conventionseasonroomevents = TableRegistry::getTableLocator()->get('Conventionseasonroomevents');
$this->Conventionseasonevents = TableRegistry::getTableLocator()->get('Conventionseasonevents');
$this->Crstudentevents = TableRegistry::getTableLocator()->get('Crstudentevents');
$this->Combinerequests = TableRegistry::getTableLocator()->get('Combinerequests');

$eventIds = [];
$uploadRequiredByEvent = [];
$fourPlaceSportsEventNumbers = array_flip([
	'001', '002', '051', '052',
	'109', '110', '139', '140', '169', '170', '174', '175', '176', '177', '178',
	'209', '210', '239', '240', '269', '270', '274', '275', '276', '277', '278',
]);
$fourPlaceSportsEventIds = [];
foreach ($conventionseasonevents as $eventRow) {
	if (!empty($eventRow->Events)) {
		$eventId = (int)$eventRow->Events->id;
		$eventIds[] = $eventId;
		if (isset($fourPlaceSportsEventNumbers[(string)$eventRow->Events->event_id_number])) {
			$fourPlaceSportsEventIds[$eventId] = true;
		}
		$uploadType = strtolower(trim((string)($eventRow->Events->upload_type ?? '')));
		$uploadRequiredByEvent[$eventId] = strpos($uploadType, 'upload') !== false;
	}
}
$eventIds = array_values(array_unique($eventIds));
$eventIdSet = array_fill_keys($eventIds, true);
$roomsByEvent = [];
$roomRows = $this->Conventionseasonroomevents->find()
	->contain(['Conventionrooms'])
	->where([
		'Conventionseasonroomevents.conventionseasons_id' => (int)$conventionSD->id,
		'Conventionseasonroomevents.convention_id' => (int)$conventionSD->convention_id,
		'Conventionseasonroomevents.season_id' => (int)$conventionSD->season_id,
		'Conventionseasonroomevents.season_year' => (int)$conventionSD->season_year,
	])
	->all();
foreach ($roomRows as $roomRow) {
	$roomEventIds = preg_split('/\s*,\s*/', trim((string)$roomRow->event_ids), -1, PREG_SPLIT_NO_EMPTY);
	foreach ($roomEventIds as $roomEventId) {
		$roomsByEvent[(int)$roomEventId][] = $roomRow->Conventionrooms->room_name.' ('.$roomRow->room_id.')';
	}
}

$judgesByEvent = [];
$judgeUserIds = [];
$judgeRegistrations = $this->Conventionregistrations->find()
	->contain(['Users'])
	->where([
		'Conventionregistrations.conventionseason_id' => (int)$conventionSD->id,
		'Conventionregistrations.convention_id' => (int)$conventionSD->convention_id,
		'Conventionregistrations.season_id' => (int)$conventionSD->season_id,
		'Conventionregistrations.status' => 1,
	])
	->select([
		'Conventionregistrations.user_id', 'Conventionregistrations.judges_event_ids',
		'Users.id', 'Users.first_name', 'Users.last_name',
	])
	->all();
foreach ($judgeRegistrations as $judgeRegistration) {
	if (empty($judgeRegistration->judges_event_ids) || empty($judgeRegistration->Users)) {
		continue;
	}
	$judgeId = (int)$judgeRegistration->user_id;
	$judgeName = $judgeRegistration->Users->first_name.' '.$judgeRegistration->Users->last_name;
	foreach (explode(',', (string)$judgeRegistration->judges_event_ids) as $assignedEventId) {
		$assignedEventId = (int)trim($assignedEventId);
		if ($assignedEventId <= 0 || !isset($eventIdSet[$assignedEventId])) {
			continue;
		}
		$judgesByEvent[$assignedEventId][$judgeId] = $judgeName;
		$judgeUserIds[$judgeId] = $judgeId;
	}
}

$submissionsByEvent = [];
$submissionStatsByEvent = [];
$submissionRows = [];
if (!empty($eventIds)) {
	$submissionRows = $this->Eventsubmissions->find()
		->select([
			'id', 'conventionregistration_id', 'conventionseason_id',
			'convention_id', 'season_id', 'season_year', 'event_id',
			'group_name', 'student_id', 'guideline_breach',
			'mediafile_file_system_name', 'report', 'score_sheet',
			'additional_documents', 'context_box',
		])
		->where([
			'Eventsubmissions.conventionseason_id' => (int)$conventionSD->id,
			'Eventsubmissions.convention_id' => (int)$conventionSD->convention_id,
			'Eventsubmissions.season_id' => (int)$conventionSD->season_id,
			'Eventsubmissions.event_id IN' => $eventIds,
		])
		->all()
		->toList();

	foreach ($submissionRows as $submissionRow) {
		$eventId = (int)$submissionRow->event_id;
		$requiresUploadContent = (bool)($uploadRequiredByEvent[$eventId] ?? false);
		$hasFile = (
			trim((string)$submissionRow->mediafile_file_system_name) !== '' ||
			trim((string)$submissionRow->report) !== '' ||
			trim((string)$submissionRow->score_sheet) !== '' ||
			trim((string)$submissionRow->additional_documents) !== ''
		);
		$hasContext = trim((string)$submissionRow->context_box) !== '';
		if ($requiresUploadContent && !$hasFile && !$hasContext) {
			continue;
		}

		$breach = (int)$submissionRow->guideline_breach;
		$submissionsByEvent[$eventId][] = $submissionRow;
		if (!isset($submissionStatsByEvent[$eventId])) {
			$submissionStatsByEvent[$eventId] = [
				'total' => 0,
				'effective' => 0,
				'pending_breaches' => 0,
				'approved_breaches' => 0,
			];
		}
		$submissionStatsByEvent[$eventId]['total']++;
		if ($breach !== 1) {
			$submissionStatsByEvent[$eventId]['effective']++;
		}
		if ($breach === 2) {
			$submissionStatsByEvent[$eventId]['approved_breaches']++;
		}
		if ($breach === 1) {
			$submissionStatsByEvent[$eventId]['pending_breaches']++;
		}
	}
}

$approvedCombinedEvents = [];
if (!empty($eventIds)) {
	$approvedCombinedRows = $this->Combinerequests->find()
		->select(['event_id'])
		->where([
			'Combinerequests.conventionseason_id' => (int)$conventionSD->id,
			'Combinerequests.event_id IN' => $eventIds,
			'Combinerequests.status' => 1,
		])
		->enableHydration(false)
		->toArray();
	foreach ($approvedCombinedRows as $approvedCombinedRow) {
		$approvedCombinedEvents[(int)$approvedCombinedRow['event_id']] = true;
	}
}

$membersByGroup = [];
if (!empty($approvedCombinedEvents)) {
	$groupMemberRows = $this->Crstudentevents->find()
		->select(['conventionregistration_id', 'event_id', 'group_name', 'student_id'])
		->where([
			'Crstudentevents.conventionseason_id' => (int)$conventionSD->id,
			'Crstudentevents.convention_id' => (int)$conventionSD->convention_id,
			'Crstudentevents.season_id' => (int)$conventionSD->season_id,
			'Crstudentevents.season_year' => (int)$conventionSD->season_year,
			'Crstudentevents.event_id IN' => array_keys($approvedCombinedEvents),
			'Crstudentevents.student_id >' => 0,
			'Crstudentevents.group_name IS NOT' => null,
			'Crstudentevents.group_name !=' => '',
		])
		->enableHydration(false)
		->toArray();
	foreach ($groupMemberRows as $groupMemberRow) {
		$groupKey = (int)$groupMemberRow['conventionregistration_id'].'|'.(int)$groupMemberRow['event_id'].'|'.trim((string)$groupMemberRow['group_name']);
		$membersByGroup[$groupKey][] = (int)$groupMemberRow['student_id'];
	}
}

$entryCountsByEvent = [];
foreach ($eventIds as $eventId) {
	$eventRows = $submissionsByEvent[$eventId] ?? [];
	if (!isset($approvedCombinedEvents[$eventId])) {
		$uniqueEntries = [];
		foreach ($eventRows as $submissionRow) {
			$registrationId = (int)$submissionRow->conventionregistration_id;
			$studentId = (int)$submissionRow->student_id;
			if ($studentId > 0) {
				$uniqueEntries['S:'.$registrationId.':'.$studentId] = true;
				continue;
			}

			$groupName = trim((string)$submissionRow->group_name);
			if ($groupName !== '') {
				$uniqueEntries['G:'.$registrationId.':'.$groupName] = true;
				continue;
			}

			$uniqueEntries['ID:'.(int)$submissionRow->id] = true;
		}
		$entryCountsByEvent[$eventId] = count($uniqueEntries);
		continue;
	}
	$uniqueGroups = [];
	foreach ($eventRows as $submissionRow) {
		$registrationId = (int)$submissionRow->conventionregistration_id;
		$studentId = (int)$submissionRow->student_id;
		if ($studentId > 0) {
			$uniqueGroups['S:'.$registrationId.':'.$studentId] = true;
			continue;
		}

		$groupName = trim((string)$submissionRow->group_name);
		$groupKey = $registrationId.'|'.$eventId.'|'.$groupName;
		$memberIds = array_values(array_unique($membersByGroup[$groupKey] ?? []));
		sort($memberIds);
		$uniqueGroups[$groupName.'|'.(!empty($memberIds) ? implode('-', $memberIds) : 'submission-'.$submissionRow->id)] = true;
	}
	$entryCountsByEvent[$eventId] = count($uniqueGroups);
}

$submissionEventById = [];
$submittableSubmissionIds = [];
foreach ($submissionRows as $submissionRow) {
	$submissionId = (int)$submissionRow->id;
	$submissionEventById[$submissionId] = (int)$submissionRow->event_id;
	if ((int)$submissionRow->guideline_breach !== 2) {
		$submittableSubmissionIds[] = $submissionId;
	}
}
$evaluationCountsByEvent = [];
$evaluationJudgesBySubmission = [];
$fourPlaceProgressByEventJudge = [];
if (!empty($submittableSubmissionIds) && !empty($judgeUserIds)) {
	$evaluationPairs = $this->Judgeevaluations->find()
		->select(['eventsubmission_id', 'uploaded_by_user_id'])
		->where([
			'Judgeevaluations.eventsubmission_id IN' => $submittableSubmissionIds,
			'Judgeevaluations.uploaded_by_user_id IN' => array_values($judgeUserIds),
		])
		->distinct(['Judgeevaluations.eventsubmission_id', 'Judgeevaluations.uploaded_by_user_id'])
		->enableHydration(false)
		->toArray();
	foreach ($evaluationPairs as $evaluationPair) {
		$submissionId = (int)$evaluationPair['eventsubmission_id'];
		$eventId = $submissionEventById[$submissionId] ?? 0;
		$judgeId = (int)$evaluationPair['uploaded_by_user_id'];
		if (!$eventId) {
			continue;
		}
		$evaluationCountsByEvent[$eventId][$judgeId] = ($evaluationCountsByEvent[$eventId][$judgeId] ?? 0) + 1;
		$evaluationJudgesBySubmission[$eventId][$submissionId][$judgeId] = true;
	}

	if (!empty($fourPlaceSportsEventIds)) {
		$placeRows = $this->Judgeevaluations->find()
			->select(['eventsubmission_id', 'uploaded_by_user_id', 'place'])
			->where([
				'Judgeevaluations.eventsubmission_id IN' => $submittableSubmissionIds,
				'Judgeevaluations.uploaded_by_user_id IN' => array_values($judgeUserIds),
				'Judgeevaluations.place IN' => [1, 2, 3, 4],
				'AND' => [
					['OR' => [
						['Judgeevaluations.withdraw_yes_no IS' => null],
						['Judgeevaluations.withdraw_yes_no !=' => 1],
					]],
					['OR' => [
						['Judgeevaluations.did_not_attend IS' => null],
						['Judgeevaluations.did_not_attend !=' => 1],
					]],
				],
			])
			->enableHydration(false)
			->toArray();
		foreach ($placeRows as $placeRow) {
			$submissionId = (int)$placeRow['eventsubmission_id'];
			$eventId = $submissionEventById[$submissionId] ?? 0;
			$judgeId = (int)$placeRow['uploaded_by_user_id'];
			$place = (int)$placeRow['place'];
			if ($eventId && isset($fourPlaceSportsEventIds[$eventId]) && $place >= 1 && $place <= 4) {
				$fourPlaceProgressByEventJudge[$eventId][$judgeId][$place] = ($fourPlaceProgressByEventJudge[$eventId][$judgeId][$place] ?? 0) + 1;
			}
		}
	}
}

$timeToMilliseconds = static function ($timeValue) {
	if ($timeValue === null || $timeValue === '') {
		return null;
	}
	if (is_object($timeValue) && method_exists($timeValue, 'format')) {
		$timeValue = $timeValue->format('H:i:s.u');
	}
	$timeValue = trim((string)$timeValue);
	if (!preg_match('/^(\d{1,2}):(\d{2}):(\d{2})(?:\.(\d{1,6}))?$/', $timeValue, $matches)) {
		return null;
	}
	$fraction = isset($matches[4]) ? str_pad(substr($matches[4], 0, 3), 3, '0') : '000';
	return ((((int)$matches[1] * 60 + (int)$matches[2]) * 60 + (int)$matches[3]) * 1000) + (int)$fraction;
};

$isSingleJudgeEventType = static function ($eventJudgingType) {
	$eventJudgingType = strtolower(trim((string)$eventJudgingType));
	return in_array($eventJudgingType, ['times', 'distances', 'soccer_kick'], true);
};

$singleJudgeEventNumbers = array_flip([
	'001', '051', '002', '052', '003', '053', '1057', '1056', '054',
	'1050', '1051', '1052', '1053', '1054', '1055',
	'1000', '1001', '1002', '1003', '1004', '1005',
	'109', '110', '111', '112', '113', '139', '140', '141', '142', '143',
	'169', '170', '171', '172', '173', '174', '175', '176', '177', '178',
	'209', '210', '211', '212', '213', '239', '240', '241', '242', '243',
	'269', '270', '271', '272', '273', '274', '275', '276', '277', '278',
]);

$isSoccerKickEvent = static function ($event) {
	$judgingType = strtolower(trim((string)$event->event_judging_type));
	$eventName = (string)$event->event_name;
	return $judgingType === 'soccer_kick'
		|| ($judgingType === 'distances' && stripos($eventName, 'soccer kick') !== false);
};

$brokenRecordEvents = [];
$recordCandidates = $this->Conventionseasonevents->find()
	->contain(['Events'])
	->where([
		'Conventionseasonevents.conventionseasons_id' => (int)$conventionSD->id,
		'Conventionseasonevents.judging_ends' => 1,
		'Conventionseasonevents.current_record IS NOT' => null,
		'Conventionseasonevents.current_record !=' => '',
	])
	->all();
$recordEventIds = [];
foreach ($recordCandidates as $recordCandidate) {
	if (empty($recordCandidate->Events) || (string)$recordCandidate->Events->event_judging_type !== 'times') {
		continue;
	}
	$recordEventIds[(int)$recordCandidate->event_id] = true;
}
$bestTimesByEvent = [];
if (!empty($recordEventIds)) {
	$bestTimeRows = $this->Judgeevaluations->find()
		->select([
			'event_id',
			'best_time' => $this->Judgeevaluations->find()->func()->min('time_score'),
		])
		->where([
			'Judgeevaluations.conventionseason_id' => (int)$conventionSD->id,
			'Judgeevaluations.convention_id' => (int)$conventionSD->convention_id,
			'Judgeevaluations.season_id' => (int)$conventionSD->season_id,
			'Judgeevaluations.season_year' => (int)$conventionSD->season_year,
			'Judgeevaluations.event_id IN' => array_keys($recordEventIds),
			'Judgeevaluations.withdraw_yes_no !=' => 1,
			'Judgeevaluations.time_score IS NOT' => null,
		])
		->group(['Judgeevaluations.event_id'])
		->enableHydration(false)
		->toArray();
	foreach ($bestTimeRows as $bestTimeRow) {
		$bestTimesByEvent[(int)$bestTimeRow['event_id']] = $bestTimeRow['best_time'];
	}
}
foreach ($recordCandidates as $recordCandidate) {
	if (empty($recordCandidate->Events) || (string)$recordCandidate->Events->event_judging_type !== 'times') {
		continue;
	}
	$currentRecordMilliseconds = $timeToMilliseconds($recordCandidate->current_record);
	$bestTimeMilliseconds = $timeToMilliseconds($bestTimesByEvent[(int)$recordCandidate->event_id] ?? null);
	if ($currentRecordMilliseconds !== null && $bestTimeMilliseconds !== null && $bestTimeMilliseconds < $currentRecordMilliseconds) {
		$brokenRecordEvents[] = $recordCandidate;
	}
}
$brokenRecordEventIds = [];
foreach ($brokenRecordEvents as $brokenRecordEvent) {
	$brokenRecordEventIds[(int)$brokenRecordEvent->event_id] = true;
}
$closeOnly = ((string)$this->request->getQuery('close_only') === '1');
$eventsToCloseCount = 0;
foreach ($conventionseasonevents as $eventRow) {
	if (empty($eventRow->Events) || (int)$eventRow->judging_ends === 1) continue;
	$eventId = (int)$eventRow->Events->id;
	if (($submissionStatsByEvent[$eventId]['total'] ?? 0) > 0 && !empty($judgesByEvent[$eventId])) {
		$eventsToCloseCount++;
	}
}
?>
<div class="admin_loader" id="loaderID"><?php echo $this->Html->image('loader_large_blue.gif');?></div>
<?php if (!$conventionseasonevents->isEmpty()) { ?> 
    <div class="panel-body">
        <div class="ersu_message"> <?php echo $this->Flash->render() ?></div>
        <?php echo $this->Form->create(null, ['id'=>'actionFrom', "method" => "Post"]);  ?>
        <section id="no-more-tables" class="lstng-section">
            <div class="events-toolbar">
                <div class="events-toolbar__left">
                    <div class="events-toolbar__count">
                        <span class="events-toolbar__count-num"><?php echo (int)$totalEventsConventions; ?></span>
                        <span class="events-toolbar__count-label">event<?php echo ($totalEventsConventions == 1 ? '' : 's'); ?> selected</span>
                    </div>
                </div>
                <div class="events-toolbar__right">
                    <?php echo $this->Html->link('<i class="fa fa-toggle-left"></i> Back to seasons', ['controller'=>'conventions', 'action'=>'seasons',$slug_convention], ['escape'=>false, 'class'=>'btn btn-default btn-sm']);?>
					<?php
					if ($closeOnly) {
						echo $this->Html->link('<i class="fa fa-list"></i> All Events', ['controller'=>'conventions', 'action'=>'events',$slug_convention_season,$slug_convention], ['escape'=>false, 'class'=>'btn btn-default btn-sm']);
					} else {
						echo $this->Html->link('<i class="fa fa-close"></i> Events to Close ('.number_format($eventsToCloseCount).')', ['controller'=>'conventions', 'action'=>'events',$slug_convention_season,$slug_convention,'?' => ['close_only' => 1]], ['escape'=>false, 'class'=>'btn btn-warning btn-sm']);
					}
					?>
                    <?php echo $this->Html->link('<i class="fa fa-refresh"></i> Reset Event List', ['controller'=>'conventions', 'action' => 'reseteventlist',$slug_convention_season,$slug_convention], ['class'=>'btn btn-success btn-sm', 'escape' => false, 'confirm' => 'Are you sure you want to reset event list for this convention? This will delete all events for this convention & selected season ?']); ?>
                </div>
            </div>   

            <div class="tbl-resp-listing events-table-wrap">
                <table id="convention_events" class="table table-bordered table-striped table-condensed cf events-table">
                    <thead class="cf ajshort">
                        <tr>
                            <th class="sorting_paging col-id">#ID</th>
                            <th class="sorting_paging col-id col-id-secondary">#DB</th>
                            <th class="sorting_paging col-num">Event #</th>
                            <th class="sorting_paging">Event Name</th>
                            <th class="sorting_paging">Event Type</th>
                            <th class="sorting_paging">Rooms Allocated</th>
                            <th class="sorting_paging col-num">Entries</th>
                            <th class="sorting_paging col-judges">Judge(s) &amp; Progress</th>
                            <th class="sorting_paging col-status">Status</th>
                            <th class="sorting_paging col-action">Judging?</th>
                            <th class="sorting_paging col-action">Qualifying Score/Time</th>
                            <th class="sorting_paging col-action">Result</th>
							<th class="sorting_paging col-action">Record Broken</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
						foreach ($conventionseasonevents as $datarecord)
						{
							// Skip rows whose underlying Event has been deleted
							if (empty($datarecord->Events)) {
								continue;
							}

							// In "Events to Close" mode, show only events that are not closed yet.
							if ($closeOnly && (int)$datarecord->judging_ends === 1) {
								continue;
							}
							$eventIDCS = $datarecord->Events['id'];
							$roomArrCSEvent = $roomsByEvent[(int)$eventIDCS] ?? [];
						?> 
                            <tr>
                                <td data-title="#ID" class="col-id"><?php echo $datarecord->id;?></td>
                                <td data-title="#DB" class="col-id col-id-secondary"><?php echo $datarecord->Events['id'];?></td>
                                <td data-title="Event #" class="col-num"><strong><?php echo $datarecord->Events['event_id_number'];?></strong></td>
                                <td data-title="Event Name">
                                    <div class="event-name"><?php echo h($datarecord->Events['event_name']);?></div>
                                    <div class="event-judging-type text-muted"><?php echo h($datarecord->Events['event_judging_type']);?></div>
                                </td>
                                <td data-title="Event Type"><?php echo isset($eventTypeDD[$datarecord->Events['event_type']]) ? h($eventTypeDD[$datarecord->Events['event_type']]) : 'Unknown ('.h($datarecord->Events['event_type']).')';?></td>

                                <td data-title="Rooms Allocated">
                                <?php
                                if(count($roomArrCSEvent))
                                {
                                    echo h(implode(", ",$roomArrCSEvent));
                                }
                                else
                                {
                                    echo '<span class="text-muted">&mdash;</span>';
                                }
                                ?></td>

                                <td data-title="Entries" class="col-num">
								<?php
								$eventId = (int)$datarecord->Events->id;
								$eventSubmissionStats = $submissionStatsByEvent[$eventId] ?? [
									'total' => 0,
									'effective' => 0,
									'pending_breaches' => 0,
									'approved_breaches' => 0,
								];
								$totalEntriesEvent = $entryCountsByEvent[$eventId] ?? 0;
								$rawEffectiveEntriesEvent = (int)$eventSubmissionStats['effective'];
								$effectiveEntriesEvent = min($totalEntriesEvent, $rawEffectiveEntriesEvent);
								$pendingGuidelineBreaches = $eventSubmissionStats['pending_breaches'];
								$approvedGuidelineBreaches = $eventSubmissionStats['approved_breaches'];
								echo $totalEntriesEvent;
								?>
								</td>
								
                                <td data-title="Judges & Progress" class="col-judges">
								<?php
								// to get judges register for this
								$judgeNamesArr = array();
								$judgeUserIdsArr = array();
								$judgeNameById = array();
								
								$eventJudges = $judgesByEvent[(int)$datarecord->event_id] ?? [];
								foreach ($eventJudges as $judgeId => $judgeName) {
									$judgeNamesArr[] = $judgeName;
									$judgeUserIdsArr[] = (int)$judgeId;
									$judgeNameById[(int)$judgeId] = $judgeName;
								}
								$judgeUserIdsArr = array_values(array_unique($judgeUserIdsArr));
								$totalAssignedJudges = count($judgeUserIdsArr);
								$eventNumber = trim((string)($datarecord->Events['event_id_number'] ?? ''));
								$singleJudgeByEventNumber = ($eventNumber !== '' && isset($singleJudgeEventNumbers[$eventNumber]));
								$requiredJudgeCount = ($isSingleJudgeEventType($datarecord->Events['event_judging_type']) || $singleJudgeByEventNumber) ? 1 : 2;
								$isFourPlaceSportsEvent = isset($fourPlaceSportsEventIds[$eventId]);
								$judgeProgressTarget = $isFourPlaceSportsEvent ? min(4, $effectiveEntriesEvent) : $effectiveEntriesEvent;
								$needsAnotherJudge = ($effectiveEntriesEvent > 0 && $totalAssignedJudges > 0 && $totalAssignedJudges < $requiredJudgeCount);

								// Per-judge submitted evaluation counts (distinct submissions evaluated by each judge)
								$perJudgeSubmittedCount = array();
								foreach($judgeUserIdsArr as $jUid)
								{
									$perJudgeSubmittedCount[$jUid] = 0;
								}

								$pendingEntries = 0;
								$completedEntries = 0;
								if($effectiveEntriesEvent > 0 && $totalAssignedJudges > 0)
								{
									if ($isFourPlaceSportsEvent) {
										$completedJudgeCount = 0;
										$bestJudgeProgress = 0;
										foreach ($judgeUserIdsArr as $judgeId) {
											$places = $fourPlaceProgressByEventJudge[$eventId][$judgeId] ?? [];
											$validPlaceCount = 0;
											for ($place = 1; $place <= $judgeProgressTarget; $place++) {
												if (($places[$place] ?? 0) === 1) {
													$validPlaceCount++;
												}
											}
											$perJudgeSubmittedCount[$judgeId] = $validPlaceCount;
											$bestJudgeProgress = max($bestJudgeProgress, $validPlaceCount);
											if ($validPlaceCount >= $judgeProgressTarget) {
												$completedJudgeCount++;
											}
										}
										$pendingEntries = $completedJudgeCount >= $requiredJudgeCount
											? 0
											: max(1, $judgeProgressTarget - $bestJudgeProgress);
									} else {
										foreach ($judgeUserIdsArr as $judgeId) {
											$perJudgeSubmittedCount[$judgeId] = $evaluationCountsByEvent[$eventId][$judgeId] ?? 0;
										}
										foreach ($submissionsByEvent[$eventId] ?? [] as $submissionRow) {
											if ((int)$submissionRow->guideline_breach === 2) {
												continue;
											}
											$submissionId = (int)$submissionRow->id;
											$submittedJudgeIds = array_keys($evaluationJudgesBySubmission[$eventId][$submissionId] ?? []);
											if (count(array_intersect($judgeUserIdsArr, $submittedJudgeIds)) >= $totalAssignedJudges) {
												$completedEntries++;
											}
										}

										$approvedBreachSubmissionCount = $eventSubmissionStats['approved_breaches'];
										if($approvedBreachSubmissionCount > 0)
										{
											$completedEntries += $approvedBreachSubmissionCount;
											if($completedEntries > $effectiveEntriesEvent)
											{
												$completedEntries = $effectiveEntriesEvent;
											}

											foreach($judgeUserIdsArr as $jUid)
											{
												$perJudgeSubmittedCount[$jUid] += $approvedBreachSubmissionCount;
											}
										}

										$pendingEntries = ($effectiveEntriesEvent - $completedEntries);
										if($pendingEntries < 0)
										{
											$pendingEntries = 0;
										}
									}
								}
								
								if(count($judgeUserIdsArr))
								{
									echo '<ul class="judge-progress-list">';
									foreach($judgeUserIdsArr as $jUid)
									{
										$jName = isset($judgeNameById[$jUid]) ? $judgeNameById[$jUid] : ('User #'.$jUid);
										$jSubmitted = isset($perJudgeSubmittedCount[$jUid]) ? (int)$perJudgeSubmittedCount[$jUid] : 0;
										if($jSubmitted > $judgeProgressTarget)
										{
											$jSubmitted = $judgeProgressTarget;
										}

										$pct = $judgeProgressTarget > 0 ? min(100, round(($jSubmitted / $judgeProgressTarget) * 100)) : 0;

										if($judgeProgressTarget > 0 && $jSubmitted >= $judgeProgressTarget)
										{
											$statusClass = 'is-complete';
											$statusLabel = 'Completed';
										}
										else if($judgeProgressTarget == 0)
										{
											$statusClass = 'is-none';
											$statusLabel = 'No Entries';
										}
										else if($jSubmitted == 0)
										{
											$statusClass = 'is-notstarted';
											$statusLabel = 'Not Started';
										}
										else
										{
											$statusClass = 'is-progress';
											$statusLabel = 'In Progress';
										}

										echo '<li class="judge-progress-item '.$statusClass.'">';
										echo '<span class="judge-progress-name">'.h($jName).'</span>';
										echo '<span class="judge-progress-meta">';
										echo '<span class="judge-progress-count">'.$jSubmitted.'/'.$judgeProgressTarget.'</span>';
										echo '<span class="judge-progress-badge">'.$statusLabel.'</span>';
										echo '</span>';
										if($judgeProgressTarget > 0)
										{
											echo '<span class="judge-progress-bar"><span class="judge-progress-bar__fill" style="width:'.$pct.'%;"></span></span>';
										}
										echo '</li>';
									}
									echo '</ul>';
								}
								else
								{
									echo '<span class="text-muted"><em>No judges assigned</em></span>';
								}
								?>
								</td>
								<td data-title="Status" class="col-status">
									<?php
									if($datarecord->judging_ends == 1)
									{
										echo '<span class="status-pill status-pill--closed">Closed</span>';
									}
									else if($effectiveEntriesEvent == 0)
									{
										echo '<span class="status-pill status-pill--neutral">No Entries</span>';
									}
									else if($totalAssignedJudges == 0)
									{
										echo '<span class="status-pill status-pill--neutral">No Judges</span>';
									}
									else if($needsAnotherJudge)
									{
										echo '<span class="status-pill status-pill--pending">Needs Another Judge</span>';
									}
									else if($pendingGuidelineBreaches > 0)
									{
										echo '<span class="status-pill status-pill--pending">Pending Breach</span>';
									}
									else if($pendingEntries > 0)
									{
										$pendingLabel = $isFourPlaceSportsEvent ? ' places remaining' : ' remaining';
										echo '<span class="status-pill status-pill--pending">'.$pendingEntries.$pendingLabel.'</span>';
										if($approvedGuidelineBreaches > 0)
										{
											echo '<br /><small class="text-muted">'.$approvedGuidelineBreaches.' breach approved</small>';
										}
									}
									else
									{
										echo '<span class="status-pill status-pill--ready">Ready to Close</span>';
										if($approvedGuidelineBreaches > 0)
										{
											echo '<br /><small class="text-muted">'.$approvedGuidelineBreaches.' breach approved</small>';
										}
									}
									?>
								</td>
								
								<td data-title="Judging" class="col-action">
									<?php
									if($totalEntriesEvent>0 && count($judgeNamesArr) >0)
									{
										if($datarecord->judging_ends == 1)
										{
											echo '<span class="status-pill status-pill--closed">Closed</span>';
											echo '<br /><br />';
											echo $this->Html->link('<i class="fa fa-unlock"></i> Open', ['controller' => 'results', 'action' => 'openjudging',$slug_convention_season,$slug_convention,$datarecord->Events['slug']], [ 'escape' => false, 'title' => 'Reopen Judging', 'class'=>'btn btn-info btn-xs', 'confirm' => 'Are you sure you want to reopen judging for this event?']);
										}
										else if($needsAnotherJudge)
										{
											echo '<span class="status-pill status-pill--pending">Needs Another Judge</span>';
											echo '<br /><small class="text-muted">Assign at least '.$requiredJudgeCount.' judges for this event.</small>';
										}
										else if($pendingEntries > 0)
										{
											$pendingLabel = $isFourPlaceSportsEvent ? ' places left' : ' left';
											echo '<span class="status-pill status-pill--pending">Pending ('.$pendingEntries.$pendingLabel.')</span>';
											echo '<br /><br />';

											if($isSoccerKickEvent($datarecord->Events))
											{
												$actionClose = 'closejudgingsoccerkick';
											}
											else
											if($datarecord->Events['event_judging_type'] == 'times')
											{
												$actionClose = 'closejudgingtimes';
											}
											else
											if($datarecord->Events['event_judging_type'] == 'distances')
											{
												$actionClose = 'closejudgingdistances';
											}
											else
											if($datarecord->Events['event_judging_type'] == 'scores')
											{
												$actionClose = 'closejudgingscores';
											}
											else
											if($datarecord->Events['event_judging_type'] == 'spellings')
											{
												$actionClose = 'closejudgingspellings';
											}
											else
											{
												$actionClose = 'closejudging';
											}

											$pendingItemLabel = $isFourPlaceSportsEvent ? 'places' : 'entries';
											echo $this->Html->link('<i class="fa fa-close"></i> Close', ['controller' => 'results', 'action' => $actionClose,$slug_convention_season,$slug_convention,$datarecord->Events['slug']], [ 'escape' => false, 'title' => 'Close Judging', 'class'=>'btn btn-danger btn-xs', 'confirm' => 'This event still has '.$pendingEntries.' '.$pendingItemLabel.' pending. Are you sure you want to force close judging for this event?']);
										}
										else
										{
											echo '<span class="status-pill status-pill--ready">Open</span>';
											echo '<br /><br />';
											
											if($isSoccerKickEvent($datarecord->Events))
											{
												$actionClose = 'closejudgingsoccerkick';
											}
											else
											if($datarecord->Events['event_judging_type'] == 'times')
											{
												$actionClose = 'closejudgingtimes';
											}
											else
											if($datarecord->Events['event_judging_type'] == 'distances')
											{
												$actionClose = 'closejudgingdistances';
											}
											else
											if($datarecord->Events['event_judging_type'] == 'scores')
											{
												$actionClose = 'closejudgingscores';
											}
											else
											if($datarecord->Events['event_judging_type'] == 'spellings')
											{
												$actionClose = 'closejudgingspellings';
											}
											else
											{
												$actionClose = 'closejudging';
											}
											
											echo $this->Html->link('<i class="fa fa-close"></i> Close', ['controller' => 'results', 'action' => $actionClose,$slug_convention_season,$slug_convention,$datarecord->Events['slug']], [ 'escape' => false, 'title' => 'Close Judging', 'class'=>'btn btn-warning btn-xs', 'confirm' => 'Are you sure you want to close judging for this event? This action cannot be undone ?']);
											
											
										}
									}
									?>
								</td>
								
								<td data-title="Qualifying Score/Time" class="col-action">
								<?php
								//echo $datarecord->Events['event_judging_type'];
								if($datarecord->Events['event_judging_type'] == 'scores' || $datarecord->Events['event_judging_type'] == 'times' || $datarecord->Events['event_judging_type'] == 'distances')
								{
									// Allow to enter qualifying data
									echo $this->Html->link('<i class="fa fa-arrows"></i> Qualifying Data', ['controller' => 'conventions', 'action' => 'qualifyingdata',$slug_convention_season,$slug_convention,$datarecord->Events['slug']], [ 'escape' => false, 'title' => 'Qualifying data', 'class'=>'btn btn-primary btn-xs']);
								}
								?>
								</td>
								
								<td data-title="Result" class="col-action">
									<?php
									if($datarecord->judging_ends == 1)
									{
										if($totalEntriesEvent>0 && count($judgeNamesArr) >0)
										{
											if($isSoccerKickEvent($datarecord->Events))
											{
												$actionResults = 'resultsoccerkick';
											}
											else
											if($datarecord->Events['event_judging_type'] == 'times')
											{
												$actionResults = 'resulttimes';
											}
											else
											if($datarecord->Events['event_judging_type'] == 'distances')
											{
												$actionResults = 'resultdistances';
											}
											else
											if($datarecord->Events['event_judging_type'] == 'scores')
											{
												$actionResults = 'resultscores';
											}
											else
											if($datarecord->Events['event_judging_type'] == 'spellings')
											{
												$actionResults = 'resultspellings';
											}
											else
											{
												$actionResults = 'index';
											}
											
											
											echo $this->Html->link('<i class="fa fa-pencil"></i> Result', ['controller' => 'results', 'action' => $actionResults,$slug_convention_season,$slug_convention,$datarecord->Events['slug']], [ 'escape' => false, 'title' => 'View Results', 'class'=>'btn btn-primary btn-xs']);
										}
									}
									?>
								</td>

								<td data-title="Record Broken" class="col-action">
									<?php if (isset($brokenRecordEventIds[(int)$datarecord->event_id])) { ?>
										<?php echo $this->Html->link('<i class="fa fa-trophy"></i> Broken', ['controller'=>'conventions', 'action'=>'brokenrecordcertificatepdf',$slug_convention_season,$slug_convention,$datarecord->Events['slug']], ['escape'=>false, 'class'=>'btn btn-success btn-xs', 'title'=>'Generate this event\'s broken-record certificate', 'target'=>'_blank']); ?>
									<?php } else { ?>
										<span class="text-muted">&mdash;</span>
									<?php } ?>
								</td>
                                
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="search_frm" style="display:none;">
            <button type="button" name="chkRecordId" onclick="checkAll(true);"  class="btn btn-info">Select All</button>
            <button type="button" name="chkRecordId" onclick="checkAll(false);" class="btn btn-info">Unselect All</button>
            <?php
            $arr = array(
                "" => "Action for selected record",
                'Activate' => "Activate",
                'Deactivate' => "Deactivate",
                //'Delete' => "Delete",
            );
            ?>
            <div class="list_sel"><?php echo $this->Form->input('action', ['options' => $arr, 'type'=>'select', 'label'=>false, 'class'=>"small form-control",'id'=>'action']);?></div>
            <button type="submit" class="small btn btn-success btn-cons btn-info" onclick="return ajaxActionFunction();" id="submit_action">OK</button>
        </div>
        <?php 
        if (isset($keyword) && $keyword != '') {
            echo $this->Form->input('Divisions.keyword', ['label'=>false, 'type'=>'hidden', 'value'=>$keyword]);
        }?>
        <?php echo $this->Form->end(); ?>
    
    </div>
<?php } else { ?>
    <div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
    <div class="admin_no_record">No record found.</div>
<?php }
?>

<script>
$(document).ready(function() {
	$('#convention_events').dataTable({
		"bPaginate": true,
		//"bInfo": false,
		"bLengthChange": false,
		"pageLength": 100,
		order: [[0, 'asc']],
		//"bFilter": true,
		//"bInfo": false,
		//"bAutoWidth": false
	});
	/* $('#searchInput').on('keyup', function() {
        $('#convention_events').dataTable.search(this.value).draw();
    }); */
});
</script>

<!--
<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
-->
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<style type="text/css">
    .page-link {
        color: #1c2452 !important;
        background-color: #fff !important;
    }

    .active>.page-link,
    .page-link.active {
        background-color: #1c2452 !important;
        border-color: #1c2452 !important;
        color: #fff !important;
    }

    .pagination {
        border-radius: 0rem !important;
    }

    /* ===== Events listing — refreshed layout ===== */
    .events-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        background: #f7f9fc;
        border: 1px solid #e3e8ef;
        border-radius: 6px;
        padding: 12px 16px;
        margin-bottom: 16px;
    }
    .events-toolbar__count {
        display: flex;
        align-items: baseline;
        gap: 8px;
    }
    .events-toolbar__count-num {
        font-size: 22px;
        font-weight: 700;
        color: #1c2452;
        line-height: 1;
    }
    .events-toolbar__count-label {
        color: #5b6b80;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .events-toolbar__right {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .events-table-wrap {
        background: #fff;
        border: 1px solid #e3e8ef;
        border-radius: 6px;
        overflow: hidden;
    }
    table.events-table {
        margin-bottom: 0 !important;
        font-size: 12.5px;
    }
    table.events-table thead th {
        background: #1c2452 !important;
        color: #fff !important;
        font-weight: 600 !important;
        font-size: 11px !important;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-color: #1c2452 !important;
        vertical-align: middle !important;
        padding: 10px 8px !important;
        white-space: nowrap;
    }
    table.events-table tbody td {
        vertical-align: middle !important;
        padding: 10px 8px !important;
    }
    table.events-table tbody tr:hover {
        background-color: #f3f6fb !important;
    }
    table.events-table .col-id { width: 60px; text-align: center; color: #6b7a8f; }
    table.events-table .col-id-secondary { color: #9aa6b8; font-size: 11px; }
    table.events-table .col-num { text-align: center; }
    table.events-table .col-judges { min-width: 280px; }
    table.events-table .col-status { width: 130px; text-align: center; }
    table.events-table .col-action { width: 130px; text-align: center; }

    .event-name { font-weight: 600; color: #1c2452; }
    .event-judging-type {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #8a96a8;
        margin-top: 2px;
    }

    /* Status pills */
    .status-pill {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        color: #fff;
        line-height: 1;
        white-space: nowrap;
    }
    .status-pill--ready    { background-color: #28a745; }
    .status-pill--closed   { background-color: #6c757d; }
    .status-pill--pending  { background-color: #dc3545; }
    .status-pill--neutral  { background-color: #adb5bd; }

    /* Judge progress list */
    .judge-progress-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .judge-progress-item {
        display: grid;
        grid-template-columns: 1fr auto;
        grid-template-areas:
            "name meta"
            "bar  bar";
        align-items: center;
        gap: 4px 10px;
        padding: 6px 8px;
        margin-bottom: 6px;
        border-radius: 4px;
        background: #f7f9fc;
        border-left: 3px solid #adb5bd;
    }
    .judge-progress-item:last-child { margin-bottom: 0; }
    .judge-progress-item.is-complete    { border-left-color: #28a745; background: #eaf7ed; }
    .judge-progress-item.is-progress    { border-left-color: #f0ad4e; background: #fdf6ea; }
    .judge-progress-item.is-notstarted  { border-left-color: #dc3545; background: #fbecec; }
    .judge-progress-item.is-none        { border-left-color: #adb5bd; background: #f1f3f5; }

    .judge-progress-name {
        grid-area: name;
        font-weight: 600;
        color: #1c2452;
        font-size: 12.5px;
    }
    .judge-progress-meta {
        grid-area: meta;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .judge-progress-count {
        font-variant-numeric: tabular-nums;
        font-weight: 700;
        font-size: 11.5px;
        color: #1c2452;
    }
    .judge-progress-badge {
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #5b6b80;
        font-weight: 600;
    }
    .judge-progress-item.is-complete   .judge-progress-badge { color: #1d7a36; }
    .judge-progress-item.is-progress   .judge-progress-badge { color: #b97206; }
    .judge-progress-item.is-notstarted .judge-progress-badge { color: #b3261e; }

    .judge-progress-bar {
        grid-area: bar;
        display: block;
        height: 4px;
        width: 100%;
        background: #e3e8ef;
        border-radius: 999px;
        overflow: hidden;
    }
    .judge-progress-bar__fill {
        display: block;
        height: 100%;
        background: #adb5bd;
        transition: width 0.3s ease;
    }
    .judge-progress-item.is-complete   .judge-progress-bar__fill { background: #28a745; }
    .judge-progress-item.is-progress   .judge-progress-bar__fill { background: #f0ad4e; }
    .judge-progress-item.is-notstarted .judge-progress-bar__fill { background: #dc3545; }
</style>