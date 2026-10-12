<?php
$conventionSD = $conventionSD ?? null;
$eventD = $eventD ?? null;
$convSeasonEventD = $convSeasonEventD ?? null;
$eventStudentGroups = isset($eventStudentGroups) && is_array($eventStudentGroups) ? $eventStudentGroups : [];

$conventionName = is_object($conventionSD) ? (string)($conventionSD->Conventions['name'] ?? '') : '';
$eventName = is_object($eventD) ? (string)($eventD->event_name ?? '') : '';
$eventCode = is_object($eventD) ? (string)($eventD->event_id_number ?? '') : '';
$divisionId = (int)($eventD->division_id ?? 0);
$judgingType = trim((string)($eventD->event_judging_type ?? ''));

$qualifyingDetails = '';
if (is_object($convSeasonEventD)) {
	$candidateQualFields = ['qualifying_details', 'qualifying_time', 'qualifying_score', 'qualifying_distance', 'convSeasEventD'];
	foreach ($candidateQualFields as $fieldName) {
		if (!empty($convSeasonEventD->{$fieldName})) {
			$qualifyingDetails = trim((string)$convSeasonEventD->{$fieldName});
			break;
		}
	}
}

$recordHolder = '';
$recordSchoolState = '';
$recordValue = '';
$recordYear = '';
if (is_object($eventD)) {
	$holderFields = ['record_holder', 'record_holder_name'];
	foreach ($holderFields as $fieldName) {
		if (!empty($eventD->{$fieldName})) {
			$recordHolder = trim((string)$eventD->{$fieldName});
			break;
		}
	}
	$schoolStateFields = ['record_school_state', 'record_school'];
	foreach ($schoolStateFields as $fieldName) {
		if (!empty($eventD->{$fieldName})) {
			$recordSchoolState = trim((string)$eventD->{$fieldName});
			break;
		}
	}
	$valueFields = ['record_time', 'record_distance', 'record_points', 'record_value'];
	foreach ($valueFields as $fieldName) {
		if (!empty($eventD->{$fieldName})) {
			$recordValue = trim((string)$eventD->{$fieldName});
			break;
		}
	}
	if (!empty($eventD->record_year)) {
		$recordYear = trim((string)$eventD->record_year);
	}
}

$categoryLabel = 'Student Convention Contestant List';
if ($divisionId === 10) {
	$categoryLabel = 'Track Events';
} elseif ($divisionId === 11) {
	$categoryLabel = 'Physical Education - Athletics';
} elseif ($divisionId === 12) {
	$categoryLabel = 'Physical Education - Sports';
}

$isAthleticsStyle = $judgingType === 'times' || $judgingType === 'distances';
$isDistanceStyle = $judgingType === 'distances';
$isSoccerKickStyle = $judgingType === 'soccer_kick';
$isSportsPointsStyle = $judgingType === 'scores';
$isSpellingStyle = $judgingType === 'spellings';
$isContestantStyle = !$isDistanceStyle && !$isSoccerKickStyle && !$isSportsPointsStyle && !$isAthleticsStyle;

$distanceMarks = [10, 15, 20, 25, 30, 35, 40, 45, 50];
?>

<style>
.legacy-sheet { font-family: Arial, sans-serif; color: #111; font-size: 13px; }
.legacy-head-title { font-size: 22px; font-weight: 600; margin-bottom: 4px; }
.legacy-head-sub { font-size: 40px; font-style: italic; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 10px; border-bottom: 2px solid #333; line-height: 1.05; }
.legacy-event-line { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px; }
.legacy-event-name { font-size: 34px; font-weight: 600; }
.legacy-event-code { font-size: 38px; font-weight: 700; }
.legacy-meta { display: flex; justify-content: space-between; margin-bottom: 8px; }
.legacy-meta-left, .legacy-meta-right { font-size: 12px; color: #333; }
.legacy-meta-right { text-align: right; }
.legacy-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
.legacy-table th { text-align: left; border-bottom: 2px solid #222; padding: 4px 6px; font-size: 12px; }
.legacy-table td { padding: 4px 6px; border-bottom: 1px dotted #bbb; vertical-align: top; }
.legacy-table .score-col { text-align: center; width: 38px; color: #777; }
.legacy-table .num-col { text-align: center; width: 50px; }
.legacy-group-head td { border-top: 1px solid #333; border-bottom: none; padding-top: 7px; font-weight: 700; }
.legacy-school-name { color: #1f78a8; font-style: italic; margin-left: 8px; }
.legacy-foot { margin-top: 20px; border-top: 1px solid #333; padding-top: 10px; display: flex; justify-content: space-between; gap: 20px; font-size: 12px; }
.legacy-foot-col { flex: 1; }
.legacy-foot-label { font-weight: 700; }
.legacy-box { border: 1px solid #222; height: 16px; min-width: 30px; }
.legacy-note { font-size: 11px; color: #666; font-style: italic; }

@media print {
	.topn { display: none; }
	.legacy-sheet { padding: 0 6px; }
}
</style>

<div class="legacy-sheet">
	<div class="legacy-head-title"><?php echo h($conventionName); ?></div>
	<div class="legacy-head-sub"><?php echo h($categoryLabel); ?></div>

	<div class="legacy-event-line">
		<div class="legacy-event-name"><?php echo h($eventName); ?></div>
		<div class="legacy-event-code"><?php echo h($eventCode); ?></div>
	</div>

	<div class="legacy-meta">
		<div class="legacy-meta-left">Number of Entries: <?php echo count($eventStudentGroups); ?></div>
		<div class="legacy-meta-right">Qualifying Details: <?php echo h($qualifyingDetails !== '' ? $qualifyingDetails : '-'); ?></div>
	</div>

	<?php if (!$isAthleticsStyle) { ?>
	<div class="legacy-meta" style="margin-top:-4px; margin-bottom:2px;">
		<div class="legacy-meta-left">G = Guidelines breach</div>
		<div class="legacy-meta-right">W = withdrawn from event</div>
	</div>
	<?php } else { ?>
	<div class="legacy-meta" style="margin-top:-4px; margin-bottom:2px;">
		<div class="legacy-meta-left">Year of Birth:</div>
		<div class="legacy-meta-right">W = withdrawn from event</div>
	</div>
	<?php } ?>

	<table class="legacy-table">
		<thead>
			<?php if ($isSoccerKickStyle) { ?>
			<tr>
				<th style="width:120px;">Year of Birth</th>
				<th>Name</th>
				<th style="width:230px;">School</th>
				<?php foreach ($distanceMarks as $distanceMark) { ?>
				<th class="num-col"><?php echo h($distanceMark); ?>m</th>
				<?php } ?>
				<th class="num-col">Furthest</th>
				<th class="num-col">Attempt</th>
				<th class="num-col">Place</th>
			</tr>
			<?php } elseif ($isDistanceStyle) { ?>
			<tr>
				<th style="width:120px;">Year of Birth</th>
				<th>Name</th>
				<th style="width:230px;">School</th>
				<th class="num-col">1st Attempt</th>
				<th class="num-col">2nd Attempt</th>
				<th class="num-col">3rd Attempt</th>
				<th class="num-col">Place</th>
			</tr>
			<?php } elseif ($isAthleticsStyle) { ?>
			<tr>
				<th style="width:120px;">Year of Birth</th>
				<th>Name</th>
				<th style="width:260px;">School</th>
				<th class="num-col">Time</th>
				<th class="num-col">Place</th>
				<th class="num-col">W</th>
			</tr>
			<?php } elseif ($isSportsPointsStyle) { ?>
			<tr>
				<th style="width:120px;">Year of Birth</th>
				<th>Name</th>
				<th style="width:230px;">School</th>
				<th class="num-col">1</th>
				<th class="num-col">2</th>
				<th class="num-col">3</th>
				<th class="num-col">4</th>
				<th class="num-col">5</th>
				<th class="num-col">6</th>
				<th class="num-col">7</th>
				<th class="num-col">8</th>
				<th class="num-col">9</th>
				<th class="num-col">Choice x3</th>
				<th class="num-col">Total</th>
				<th class="num-col">Place</th>
			</tr>
			<?php } else { ?>
			<tr>
				<th style="width:120px;">Year of Birth</th>
				<th>Name</th>
				<th style="width:260px;">School</th>
				<th class="score-col">1st</th>
				<th class="score-col">2nd</th>
				<th class="score-col">3rd</th>
				<th class="score-col">4th</th>
				<?php if ($divisionId !== 12 || $isSpellingStyle) { ?>
				<th class="score-col">5th</th>
				<th class="score-col">6th</th>
				<?php } ?>
				<th class="score-col">W</th>
				<th class="score-col">G</th>
			</tr>
			<?php } ?>
		</thead>
		<tbody>
			<?php foreach ($eventStudentGroups as $groupRow) { ?>
				<?php if (!empty($groupRow['group_label']) || !empty($groupRow['school_name'])) { ?>
				<tr class="legacy-group-head">
					<td colspan="12">
						<?php echo h($groupRow['group_label'] ?? ''); ?>
						<?php if (!empty($groupRow['school_name'])) { ?><span class="legacy-school-name"><?php echo h($groupRow['school_name']); ?></span><?php } ?>
					</td>
				</tr>
				<?php } ?>
				<?php foreach (($groupRow['students'] ?? []) as $studentRow) { ?>
				<tr>
					<td><?php echo h($studentRow['birth_year'] ?? ''); ?></td>
					<td><?php echo h($studentRow['student_name'] ?? ''); ?></td>
					<td><?php echo h($groupRow['school_name'] ?? ''); ?></td>
					<?php if ($isSoccerKickStyle) { ?>
						<?php foreach ($distanceMarks as $distanceMark) { ?><td><div class="legacy-box"></div></td><?php } ?>
						<td><div class="legacy-box"></div></td>
						<td><div class="legacy-box"></div></td>
						<td class="score-col">W</td>
					<?php } elseif ($isDistanceStyle) { ?>
						<td><div class="legacy-box"></div></td>
						<td><div class="legacy-box"></div></td>
						<td><div class="legacy-box"></div></td>
						<td class="score-col">W</td>
					<?php } elseif ($isAthleticsStyle) { ?>
						<td><div class="legacy-box"></div></td>
						<td><div class="legacy-box"></div></td>
						<td class="score-col">W</td>
					<?php } elseif ($isSportsPointsStyle) { ?>
						<?php for ($pointIndex = 1; $pointIndex <= 9; $pointIndex++) { ?><td><div class="legacy-box"></div></td><?php } ?>
						<td><div class="legacy-box"></div></td>
						<td><div class="legacy-box"></div></td>
						<td class="score-col">W</td>
					<?php } else { ?>
						<td class="score-col">1st</td>
						<td class="score-col">2nd</td>
						<td class="score-col">3rd</td>
						<td class="score-col">4th</td>
						<?php if ($divisionId !== 12 || $isSpellingStyle) { ?>
						<td class="score-col">5th</td>
						<td class="score-col">6th</td>
						<?php } ?>
						<td class="score-col">W</td>
						<td class="score-col">G</td>
					<?php } ?>
				</tr>
				<?php } ?>
			<?php } ?>
		</tbody>
	</table>

	<?php if ($isSoccerKickStyle) { ?>
	<p class="legacy-note">Notes: Marking guide: "X" = Missed Attempt, "O" = Successful Attempt, "W" = withdrawn from event.</p>
	<?php } elseif ($isDistanceStyle) { ?>
	<p class="legacy-note">Please circle or highlight the best attempt. W = withdrawn from event.</p>
	<?php } ?>

	<div class="legacy-foot">
		<div class="legacy-foot-col">
			<div><span class="legacy-foot-label">Record Holder:</span> <?php echo h($recordHolder !== '' ? $recordHolder : '-'); ?></div>
			<div><span class="legacy-foot-label">School &amp; State:</span> <?php echo h($recordSchoolState !== '' ? $recordSchoolState : '-'); ?></div>
		</div>
		<div class="legacy-foot-col">
			<div><span class="legacy-foot-label"><?php echo $isAthleticsStyle ? 'Record ' . ($divisionId === 11 ? 'Distance' : 'Time') : 'Record'; ?>:</span> <?php echo h($recordValue !== '' ? $recordValue : '-'); ?></div>
			<div><span class="legacy-foot-label">Record Year:</span> <?php echo h($recordYear !== '' ? $recordYear : '-'); ?></div>
		</div>
	</div>
</div>
