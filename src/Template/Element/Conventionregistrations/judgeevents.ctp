<?php
use Cake\ORM\TableRegistry;
$this->Eventsubmissions = TableRegistry::getTableLocator()->get('Eventsubmissions');
$this->Judgeevaluations = TableRegistry::getTableLocator()->get('Judgeevaluations');
$this->Crstudentevents = TableRegistry::getTableLocator()->get('Crstudentevents');
$activeJudgeUserIds = isset($relatedJudgeUserIds) && is_array($relatedJudgeUserIds)
	? array_values(array_unique(array_filter(array_map('intval', $relatedJudgeUserIds))))
	: [(int)$this->request->getSession()->read('user_id')];
if (empty($activeJudgeUserIds)) {
	$activeJudgeUserIds = [(int)$this->request->getSession()->read('user_id')];
}


$getUniqueEventSubmissionGroups = function ($conditions, $event) {
	$submissions = $this->Eventsubmissions->find()->where($conditions)->all();
	$rows = $submissions->toList();
	$isGroupEvent = ((int)($event->group_event_yes_no ?? 0) === 1);

	$bestByKey = [];
	foreach ($rows as $row) {
		$eventId = (int)($row->event_id ?? 0);
		$studentId = (int)($row->student_id ?? 0);
		$groupName = strtolower(trim((string)($row->group_name ?? '')));

		$registrationId = (int)($row->conventionregistration_id ?? 0);
		$key = $isGroupEvent
			? 'G|' . $eventId . '|' . $registrationId . '|' . $groupName
			: 'S|' . $eventId . '|' . $studentId;

		if (!isset($bestByKey[$key])) {
			$bestByKey[$key] = ['row' => $row];
			continue;
		}

		$current = $bestByKey[$key];
		$currentId = (int)($current['row']->id ?? 0);
		$rowId = (int)($row->id ?? 0);

		if ($rowId > $currentId) {
			$bestByKey[$key] = ['row' => $row];
		}
	}

	$result = [];
	foreach ($bestByKey as $item) {
		$result[] = $item['row'];
	}
	return $result;
};

$buildViewEntriesLink = function ($datarecord) use ($conventionRegD) {
	if ($datarecord->event_judging_type == 'times') {
		return $this->Html->link('<i class="fa fa-list"></i>', ['controller' => 'eventsubmissions', 'action' => 'timeseventsentries', $conventionRegD->slug, $datarecord->slug], ['escape' => false, 'title' => 'View Times Event Entries Submitted', 'class' => '']);
	}
	if ($datarecord->event_judging_type == 'distances') {
		return $this->Html->link('<i class="fa fa-list"></i>', ['controller' => 'eventsubmissions', 'action' => 'distanceseventsentries', $conventionRegD->slug, $datarecord->slug], ['escape' => false, 'title' => 'View Distances Event Entries Submitted', 'class' => '']);
	}
	if ($datarecord->event_judging_type == 'scores') {
		return $this->Html->link('<i class="fa fa-list"></i>', ['controller' => 'eventsubmissions', 'action' => 'scoreseventsentries', $conventionRegD->slug, $datarecord->slug], ['escape' => false, 'title' => 'View Scores Events Entries Submitted', 'class' => '']);
	}
	if ($datarecord->event_judging_type == 'soccer_kick') {
		return $this->Html->link('<i class="fa fa-list"></i>', ['controller' => 'eventsubmissions', 'action' => 'soccerkickeventsentries', $conventionRegD->slug, $datarecord->slug], ['escape' => false, 'title' => 'View Soccer Kick Events Entries Submitted', 'class' => '']);
	}
	if ($datarecord->event_judging_type == 'spellings') {
		return $this->Html->link('<i class="fa fa-list"></i>', ['controller' => 'eventsubmissions', 'action' => 'spellingseventsentries', $conventionRegD->slug, $datarecord->slug], ['escape' => false, 'title' => 'View Spellings Events Entries Submitted', 'class' => '']);
	}
	return $this->Html->link('<i class="fa fa-list"></i>', ['controller' => 'conventionregistrations', 'action' => 'judgeevententries', $conventionRegD->slug, $datarecord->slug], ['escape' => false, 'title' => 'View Entries Submitted', 'class' => '']);
};

$eventMetrics = [];
foreach ($events as $datarecord) {
	$condEventEntries = [];
	$condEventEntries[] = "(Eventsubmissions.conventionseason_id = '".$conventionRegD->conventionseason_id."' AND Eventsubmissions.convention_id = '".$conventionRegD->convention_id."' AND Eventsubmissions.season_id = '".$conventionRegD->season_id."' AND Eventsubmissions.season_year = '".$conventionRegD->season_year."' AND Eventsubmissions.event_id = '".$datarecord->id."')";

	$listTotalEntries = $getUniqueEventSubmissionGroups($condEventEntries, $datarecord);
	$totalEntriesSubmitted = count($listTotalEntries);
	$totalEntriesM = 0;
	foreach ($listTotalEntries as $eventsub) {
		$condCheckMark = [];
		$condCheckMark[] = "(Judgeevaluations.eventsubmission_id = '".$eventsub->id."')";
		$condCheckMark['Judgeevaluations.uploaded_by_user_id IN'] = $activeJudgeUserIds;
		$checkEntryMark = $this->Judgeevaluations->find()->where($condCheckMark)->first();
		if ($checkEntryMark) {
			$totalEntriesM++;
		}
	}

	$eventMetrics[] = [
		'event' => $datarecord,
		'total' => $totalEntriesSubmitted,
		'marked' => $totalEntriesM,
		'is_completed' => ($totalEntriesSubmitted > 0 && $totalEntriesM == $totalEntriesSubmitted),
	];
}

$hasCompletedEvents = false;
foreach ($eventMetrics as $metric) {
	if (!empty($metric['is_completed'])) {
		$hasCompletedEvents = true;
		break;
	}
}
?>
<div class="admin_loader" id="loaderID"><?php echo $this->Html->image('loader_large_blue.gif');?></div>
<?php if (!$events->isEmpty()) { ?> 
    <div class="panel-body">
        
        <?php echo $this->Form->create(null, ['id'=>'actionFrom', "method" => "Post"]);  ?>
        <section id="no-more-tables" class="lstng-section">
             

            <div class="tbl-resp-listing">
				<table id="group_events_table" class="table table-striped table-bordered judge-events-table" style="width:100%">
					<thead>
						<tr class="judge-accordion-toggle-main">
							<th class="sorting_paging">Event Name <span id="incompleteAccordionIcon" class="judge-accordion-icon">&#9660;</span></th>
							<th class="sorting_paging">Event Number</th>
							<th class="sorting_paging">Group Event</th>
							<th class="sorting_paging">Total Entries</th>
							<th class="sorting_paging">Marked Entries</th>
							<th class="sorting_paging">View Entries</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($eventMetrics as $metric) {
							if (!empty($metric['is_completed'])) {
								continue;
							}
							$datarecord = $metric['event'];
							$totalEntriesSubmitted = (int)$metric['total'];
							$totalEntriesM = (int)$metric['marked'];
						?>
						<tr class="judge-event-row-incomplete">
						<td data-title="Event Name"><span class="judge-event-name"><?php echo h($datarecord->event_name);?></span></td>
						<td data-title="Event Number"><?php echo $datarecord->event_id_number;?></td>
						<td data-title="Group Event"><?php if($datarecord->group_event_yes_no == 1) echo 'Yes'; else echo 'No';?></td>
						<td data-title="Total Entries">
						<?php echo $totalEntriesSubmitted; ?>
						</td>
						
						<td data-title="Marked Entries">
						<?php echo $totalEntriesM; ?>
						</td>
						
						<td data-title="View Entries">
						<?php
						if($totalEntriesSubmitted>0)
						{
							echo $buildViewEntriesLink($datarecord);
						}
						?>
						</td>
					</tr>
						<?php } ?>
					<?php if ($hasCompletedEvents) { ?>
					<tr class="judge-event-section-row judge-accordion-toggle-completed">
						<td><strong>Completed <span id="completedAccordionIcon" class="judge-accordion-icon">&#9660;</span></strong></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
					<?php foreach ($eventMetrics as $metric) {
						if (empty($metric['is_completed'])) {
							continue;
						}
						$datarecord = $metric['event'];
						$totalEntriesSubmitted = (int)$metric['total'];
						$totalEntriesM = (int)$metric['marked'];
					?>
					<tr class="judge-event-row-completed">
						<td data-title="Event Name"><span class="judge-event-name"><?php echo h($datarecord->event_name);?></span></td>
						<td data-title="Event Number"><?php echo $datarecord->event_id_number;?></td>
						<td data-title="Group Event"><?php if($datarecord->group_event_yes_no == 1) echo 'Yes'; else echo 'No';?></td>
						<td data-title="Total Entries"><?php echo $totalEntriesSubmitted; ?></td>
						<td data-title="Marked Entries"><?php echo $totalEntriesM; ?></td>
						<td data-title="View Entries"><?php echo $buildViewEntriesLink($datarecord); ?></td>
					</tr>
					<?php } ?>
					<?php } ?>
					</tbody>
				</table>
            </div>
        </section>

         
        
        <?php echo $this->Form->end(); ?>
    
    </div>
<?php } else { ?>
    <div id="listingJS" style="display: none;" class="alert alert-success alert-block fade in"></div>
    <div class="admin_no_record">No record found.</div>
<?php }
?>

<script>
$(document).ready(function() {
$('#group_events_table').dataTable({
    //"bPaginate": false,
    "bLengthChange": false,
	"pageLength": 50,
	"ordering": false,
    //"bFilter": true,
    //"bInfo": false,
    //"bAutoWidth": false
	});
	$('#searchInput').on('keyup', function() {
        $('#group_events_table').dataTable.search(this.value).draw();
    });

	var incompleteOpen = true;
	var completedOpen = true;

	$('.judge-accordion-toggle-main').on('click', function () {
		incompleteOpen = !incompleteOpen;
		$('#incompleteAccordionIcon').html(incompleteOpen ? '&#9660;' : '&#9658;');
		if (incompleteOpen) {
			$('.judge-event-row-incomplete').each(function () {
				$(this).stop(true, true).css('display', 'table-row').hide().fadeIn('slow');
			});
		} else {
			$('.judge-event-row-incomplete').stop(true, true).fadeOut('slow');
		}
	});

	$('.judge-accordion-toggle-completed').on('click', function () {
		completedOpen = !completedOpen;
		$('#completedAccordionIcon').html(completedOpen ? '&#9660;' : '&#9658;');
		if (completedOpen) {
			$('.judge-event-row-completed').each(function () {
				$(this).stop(true, true).css('display', 'table-row').hide().fadeIn('slow');
			});
		} else {
			$('.judge-event-row-completed').stop(true, true).fadeOut('slow');
		}
	});
});
</script>

<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<style type="text/css">
	.judge-events-table tr.judge-event-section-row td {
		background-color: #1c2452 !important;
		border-color: #273061 !important;
		color: #fff !important;
		font-weight: 700;
	}

	.judge-events-table tr.judge-accordion-toggle-main th,
	.judge-events-table tr.judge-accordion-toggle-completed td {
		cursor: pointer;
		user-select: none;
	}

	.judge-accordion-icon {
		font-size: 12px;
		margin-left: 6px;
		vertical-align: middle;
	}

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
</style>




