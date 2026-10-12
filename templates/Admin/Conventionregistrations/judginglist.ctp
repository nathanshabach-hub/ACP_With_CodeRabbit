<?php echo $this->Html->script('ajax-pagging.js'); ?>
<style>
.jl-metric {
    border-radius: 4px;
    padding: 10px 12px;
    color: #fff;
    margin-bottom: 12px;
}
.jl-metric.clickable { cursor: pointer; }
.jl-metric.clickable:hover { filter: brightness(1.06); }
.jl-metric.is-active {
    box-shadow: 0 0 0 3px rgba(255,255,255,.35) inset, 0 0 0 2px rgba(192,57,43,.55);
}
.jl-metric h3 { margin: 0; font-size: 24px; font-weight: 700; }
.jl-metric p  { margin: 2px 0 0 0; font-size: 13px; }
.jl-metric.blue   { background: #2980b9; }
.jl-metric.green  { background: #27ae60; }
.jl-metric.orange { background: #e67e22; }
.jl-metric.red    { background: #c0392b; }
.jl-note {
    border-left: 4px solid #3498db;
    background: #f3f8fc;
    padding: 10px 12px;
    border-radius: 3px;
    margin-bottom: 14px;
    font-size: 13px;
}
.jl-preferred { font-size: 12px; color: #888; margin-top: 3px; }
.jl-badge {
    display: inline-block;
    font-size: 11px;
    padding: 1px 7px;
    border-radius: 10px;
    font-weight: 600;
    margin-left: 4px;
}
.jl-badge.ok   { background: #d5f5e3; color: #1e8449; }
.jl-badge.warn { background: #fdebd0; color: #b9770e; }
.jl-badge.bad  { background: #fce4e4; color: #c0392b; }
#judging_list_table select { min-width: 160px; }

#judging_list_table tbody tr.jl-row-three > td {
    background: #dff0d8 !important;
}

#judging_list_table tbody tr.jl-row-two > td {
    background: #fdebd0 !important;
}

#judging_list_table tbody tr.jl-row-one > td {
    background: #ececec !important;
}

.jl-workload-wrap {
    border: 1px solid #e6e9ef;
    border-radius: 6px;
    overflow: hidden;
}

#workload_table {
    margin-bottom: 0;
}

#workload_table th,
#workload_table td {
    vertical-align: middle !important;
}

#workload_table .wl-rank {
    width: 50px;
    text-align: center;
    color: #777;
    font-weight: 600;
}

#workload_table .wl-count {
    width: 90px;
    text-align: center;
    font-weight: 700;
}

#workload_table .wl-meter {
    width: 38%;
}

.jl-load-track {
    background: #edf1f5;
    border-radius: 999px;
    height: 10px;
    overflow: hidden;
    width: 100%;
}

.jl-load-fill {
    display: block;
    height: 100%;
    transition: width .2s ease;
}

.jl-load-fill.success { background: #27ae60; }
.jl-load-fill.warning { background: #f39c12; }
.jl-load-fill.danger  { background: #e74c3c; }
.jl-load-fill.default { background: #95a5a6; }

.wl-balance {
    width: 130px;
    text-align: center;
    font-weight: 700;
}

.wl-balance.pos { color: #c0392b; }
.wl-balance.neg { color: #1e8449; }
.wl-balance.neu { color: #7f8c8d; }

.jl-workload-note {
    font-size: 12px;
    color: #666;
    margin-bottom: 8px;
}

.jl-accordion-item {
    border: 1px solid #dfe6ee;
    border-radius: 8px;
    margin-bottom: 12px;
    overflow: hidden;
    background: #fff;
}

.jl-accordion-item > summary {
    padding: 11px 14px;
    font-weight: 700;
    color: #1f3a56;
    background: #f6f9fc;
    cursor: pointer;
    border-bottom: 1px solid #e4ebf3;
    list-style: none;
    transition: background-color .3s ease, color .3s ease;
}

.jl-accordion-item > summary::-webkit-details-marker {
    display: none;
}

.jl-accordion-item > summary::after {
    content: '+';
    float: right;
    font-weight: 700;
    transition: transform .3s ease, color .3s ease;
}

.jl-accordion-item[open] > summary::after {
    content: '-';
}

.jl-accordion-item > summary:hover {
    background: #eaf2fb;
    color: #123458;
}

.jl-accordion-item > summary:hover::after {
    transform: scale(1.1);
    color: #0f2e4a;
}

.jl-accordion-item[open] > summary:hover::after {
    transform: rotate(90deg);
}

.jl-accordion-body {
    padding: 0 12px;
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height .75s cubic-bezier(.22, .61, .36, 1), opacity .5s ease, padding .6s ease;
    will-change: max-height, opacity;
}

.jl-accordion-item[open] .jl-accordion-body {
    padding: 10px 12px 2px;
    opacity: 1;
}

.jl-metrics-row {
    display: flex;
    flex-wrap: wrap;
    margin-left: -6px;
    margin-right: -6px;
}

.jl-metrics-row > [class*="col-"] {
    float: none;
    width: 20%;
    padding-left: 6px;
    padding-right: 6px;
}

@media (max-width: 1199px) {
    .jl-metrics-row > [class*="col-"] {
        width: 33.3333%;
    }
}

@media (max-width: 767px) {
    .jl-metrics-row > [class*="col-"] {
        width: 50%;
    }
}

@media (max-width: 480px) {
    .jl-metrics-row > [class*="col-"] {
        width: 100%;
    }
}
</style>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Judging List
            <small><?php echo h($convSeasonD->slug); ?></small>
        </h1>
        <ol class="breadcrumb">
            <li><?php echo $this->Html->link('<i class="fa fa-dashboard"></i> Dashboard', ['controller'=>'admins', 'action'=>'dashboard'], ['escape'=>false]); ?></li>
            <li class="active">Judging List</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-check-square-o"></i>&nbsp; Judging Panel Assignments</h3>
                <div class="box-tools pull-right">
                    <?php echo $this->Html->link('<i class="fa fa-download"></i> Export CSV', ['controller'=>'conventionregistrations', 'action'=>'judginglistcsv'], ['class'=>'btn btn-sm btn-success', 'escape'=>false]); ?>
                    &nbsp;
                    <?php echo $this->Html->link('<i class="fa fa-user"></i> Manage Judges', ['controller'=>'conventionregistrations', 'action'=>'alljudges'], ['class'=>'btn btn-sm btn-default', 'escape'=>false]); ?>
                </div>
            </div>
            <div class="panel-body" style="padding:14px 14px 0;">
                <div class="ersu_message"> <?php echo $this->Flash->render() ?></div>

                <div class="jl-note">
                    Assign up to 3 judges per event. The <em>Preferred Judges</em> column shows who selected this event. Use the dropdowns to confirm or override the panel, then click <strong>Save Assignments</strong>.
                </div>

                <div class="row jl-metrics-row">
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="jl-metric blue">
                            <h3><?php echo (int)$enteredEventsCount; ?></h3>
                            <p>Events Entered</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="jl-metric green">
                            <h3><?php echo $totalJudgesInPool; ?></h3>
                            <p>Judges In Pool</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="jl-metric red clickable" id="eventsNotAssignedCard" title="Click to filter events not assigned">
                            <h3><?php echo (int)$eventsNotAssigned; ?></h3>
                            <p>Events Not Assigned To A Judge</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="jl-metric orange clickable" id="eventsWithTwoJudgesCard" title="Click to filter events with 2 judges">
                            <h3><?php echo (int)$eventsWithExactlyTwoAssigned; ?></h3>
                            <p>Events With 2 Judges</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="jl-metric red clickable" id="eventsWithThreeJudgesCard" title="Click to filter events with 3 judges">
                            <h3><?php echo (int)$eventsWithExactlyThreeAssigned; ?></h3>
                            <p>Events With 3 Judges</p>
                        </div>
                    </div>
                </div>

                <?php if (!empty($eventJudgeRows)) { ?>
                <?php
                $judgingBuckets = [
                    'pre_judging' => ['label' => 'Pre-Judging', 'rows' => []],
                    'in_person_exhibits' => ['label' => 'In-Person Exhibits', 'rows' => []],
                    'physical_education' => ['label' => 'Physical Education', 'rows' => []],
                    'in_person' => ['label' => 'In-Person', 'rows' => []],
                ];

                $preJudgingDivisionNames = [
                    'photography',
                    'design',
                    'technology',
                    'design and technology',
                ];

                $preJudgingEventNames = [
                    'poetry writing u16',
                    'poetry writing open',
                    'persuasive essay writing open',
                    'short story writing open',
                    'short story writing u16',
                    'music vocal composition u16',
                    'music vocal composition open',
                    'music vocal arranging u16',
                    'music vocal arranging open',
                    'book report u16',
                    'book report open',
                ];

                $inPersonExhibitEventNames = [
                    'science collection u16',
                    'science collection open',
                    'social studies collection u16',
                    'social studies collection open',
                    'social studies research open',
                    'science research open',
                    'science engineering open',
                    'science theoretical open',
                ];

                $physicalEducationEventNames = [
                    'checker u16',
                    'checker open',
                    'silver apple u16',
                    'silver apple open',
                ];

                $inPersonEventNames = [
                    'chess u16',
                    'chess open',
                ];

                $physicalEducationDivisionNames = [
                    'physical education',
                    'athletics',
                    'sports',
                    'sports and athletics',
                ];

                $normalizeJudgingLabel = function ($value) {
                    $value = strtolower((string)$value);
                    $value = str_replace('&', 'and', $value);
                    $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
                    return trim((string)$value);
                };

                $preJudgingDivisionLookup = array_fill_keys(array_map($normalizeJudgingLabel, $preJudgingDivisionNames), true);
                $preJudgingEventLookup = array_fill_keys(array_map($normalizeJudgingLabel, $preJudgingEventNames), true);
                $inPersonExhibitEventLookup = array_fill_keys(array_map($normalizeJudgingLabel, $inPersonExhibitEventNames), true);
                $physicalEducationDivisionLookup = array_fill_keys(array_map($normalizeJudgingLabel, $physicalEducationDivisionNames), true);
                $physicalEducationEventLookup = array_fill_keys(array_map($normalizeJudgingLabel, $physicalEducationEventNames), true);
                $inPersonEventLookup = array_fill_keys(array_map($normalizeJudgingLabel, $inPersonEventNames), true);

                foreach ($eventJudgeRows as $bucketRow) {
                    $bucketKey = 'in_person';
                    $rowEventType = isset($bucketRow['event_type']) ? (int)$bucketRow['event_type'] : null;
                    $rowDivisionId = isset($bucketRow['division_id']) ? (int)$bucketRow['division_id'] : null;
                    $rowDivisionName = $normalizeJudgingLabel($bucketRow['division_name'] ?? '');
                    $rowEventName = $normalizeJudgingLabel($bucketRow['event_name'] ?? '');

                    if (isset($preJudgingDivisionLookup[$rowDivisionName]) || isset($preJudgingEventLookup[$rowEventName])) {
                        $bucketKey = 'pre_judging';
                    } elseif (isset($inPersonExhibitEventLookup[$rowEventName])) {
                        $bucketKey = 'in_person_exhibits';
                    } elseif (isset($inPersonEventLookup[$rowEventName])) {
                        $bucketKey = 'in_person';
                    } elseif (
                        isset($physicalEducationDivisionLookup[$rowDivisionName])
                        || isset($physicalEducationEventLookup[$rowEventName])
                        || strpos($rowEventName, 'physical education') !== false
                        || strpos($rowEventName, 'silver apple') !== false
                        || strpos($rowEventName, 'golden apple award') !== false
                        || strpos($rowEventName, 'golden lamb award') !== false
                        || strpos($rowEventName, 'golden harp award') !== false
                        || strpos($rowEventName, 'christian soldier award') !== false
                        || strpos($rowEventName, 'christian worker award') !== false
                        || strpos($rowEventName, 'athletic') !== false
                        || strpos($rowEventName, 'sport') !== false
                    ) {
                        $bucketKey = 'physical_education';
                    } elseif (in_array($rowDivisionId, [4, 5, 6], true) || strpos($rowEventName, 'exhibit') !== false) {
                        $bucketKey = 'in_person_exhibits';
                    }

                    $judgingBuckets[$bucketKey]['rows'][] = $bucketRow;
                }
                ?>
                <?php echo $this->Form->create(null, ['id'=>'judgingAssignForm', 'method'=>'post', 'url'=>['action'=>'judginglist']]); ?>
                <section id="no-more-tables" class="lstng-section">
                    <?php foreach ($judgingBuckets as $bucketKey => $bucketConfig) { ?>
                    <details class="jl-accordion-item">
                        <summary><?php echo h($bucketConfig['label']); ?> (<?php echo count($bucketConfig['rows']); ?>)</summary>
                        <div class="jl-accordion-body">
                            <?php if (!empty($bucketConfig['rows'])) { ?>
                            <div class="tbl-resp-listing">
                                <table id="judging_list_table_<?php echo h($bucketKey); ?>" class="table table-bordered table-striped table-condensed cf judging-list-table">
                                    <thead class="cf ajshort">
                                        <tr>
                                            <th style="width:40px;">#</th>
                                            <th>Code</th>
                                            <th>Event Name</th>
                                            <th>Preferred Judges</th>
                                            <th>Entries</th>
                                            <th>Judge 1</th>
                                            <th>Judge 2</th>
                                            <th>Judge 3</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                            <?php foreach ($bucketConfig['rows'] as $row) {
                                $eid = (int)$row['event_id'];
                                $saved = isset($savedAssignments[$eid]) ? $savedAssignments[$eid] : ['judge1'=>null,'judge2'=>null,'judge3'=>null];
                                $rowJudgeDD = isset($row['judge_dropdown']) && is_array($row['judge_dropdown']) ? $row['judge_dropdown'] : ['' => '-- None --'];

                                // Auto-suggest from preferred names if nothing is saved yet
                                $allSaved = $saved['judge1'] || $saved['judge2'] || $saved['judge3'];
                                if(!$allSaved)
                                {
                                    $judgeFlip = array_flip($judgeDD);
                                    $panel = $row['panel_three_names'];
                                    $saved['judge1'] = isset($panel[0], $judgeFlip[$panel[0]]) ? $judgeFlip[$panel[0]] : null;
                                    $saved['judge2'] = isset($panel[1], $judgeFlip[$panel[1]]) ? $judgeFlip[$panel[1]] : null;
                                    $saved['judge3'] = isset($panel[2], $judgeFlip[$panel[2]]) ? $judgeFlip[$panel[2]] : null;
                                }

                                // Preserve saved assignments in dropdown even if judge is no longer preferred for this event.
                                foreach (['judge1', 'judge2', 'judge3'] as $slotName) {
                                    $slotUserId = (int)$saved[$slotName];
                                    if($slotUserId > 0 && !isset($rowJudgeDD[$slotUserId])) {
                                        $rowJudgeDD[$slotUserId] = isset($judgeDD[$slotUserId]) ? $judgeDD[$slotUserId] : ('User #'.$slotUserId);
                                    }
                                }

                                $assignedCount = count(array_filter([$saved['judge1'],$saved['judge2'],$saved['judge3']]));
                                if($assignedCount >= 3)     { $badge = '<span class="jl-badge ok">3 judges</span>'; }
                                elseif($assignedCount == 2) { $badge = '<span class="jl-badge warn">2 judges</span>'; }
                                elseif($assignedCount == 1) { $badge = '<span class="jl-badge bad">1 judge</span>'; }
                                else                        { $badge = '<span class="jl-badge bad">unassigned</span>'; }

                                $rowClass = '';
                                if($assignedCount >= 3) {
                                    $rowClass = 'jl-row-three';
                                } elseif($assignedCount == 2) {
                                    $rowClass = 'jl-row-two';
                                } elseif($assignedCount == 1) {
                                    $rowClass = 'jl-row-one';
                                }
                            ?>
                                <tr class="<?php echo $rowClass; ?>" data-assigned-count="<?php echo (int)$assignedCount; ?>" data-preferred-count="<?php echo (int)$row['preferred_count']; ?>" data-entries-count="<?php echo (int)($row['entries_count'] ?? 0); ?>">
                                    <td><?php echo $eid; ?></td>
                                    <td><?php echo h($row['event_id_number']); ?></td>
                                    <td>
                                        <?php echo h($row['event_name']); ?>
                                        <?php echo $badge; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['preferred_names'])) {
                                            echo '<small class="jl-preferred">'.implode(', ', array_map('htmlspecialchars', $row['preferred_names'])).'</small>';
                                        } else {
                                            echo '<small class="jl-preferred" style="color:#ccc;">None selected</small>';
                                        } ?>
                                    </td>
                                    <td><?php echo (int)($row['entries_count'] ?? 0); ?></td>
                                    <td>
                                        <select name="assignments[<?php echo $eid; ?>][judge1]" class="form-control input-sm">
                                            <?php foreach($rowJudgeDD as $uid => $uname) {
                                                $sel = ($uid !== '' && (int)$uid === (int)$saved['judge1']) ? ' selected' : '';
                                                echo '<option value="'.h($uid).'"'.$sel.'>'.h($uname).'</option>';
                                            } ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="assignments[<?php echo $eid; ?>][judge2]" class="form-control input-sm">
                                            <?php foreach($rowJudgeDD as $uid => $uname) {
                                                $sel = ($uid !== '' && (int)$uid === (int)$saved['judge2']) ? ' selected' : '';
                                                echo '<option value="'.h($uid).'"'.$sel.'>'.h($uname).'</option>';
                                            } ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="assignments[<?php echo $eid; ?>][judge3]" class="form-control input-sm">
                                            <?php foreach($rowJudgeDD as $uid => $uname) {
                                                $sel = ($uid !== '' && (int)$uid === (int)$saved['judge3']) ? ' selected' : '';
                                                echo '<option value="'.h($uid).'"'.$sel.'>'.h($uname).'</option>';
                                            } ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php } else { ?>
                            <div class="alert alert-info" style="margin-bottom:10px;">No events in this section.</div>
                            <?php } ?>
                        </div>
                    </details>
                    <?php } ?>
                </section>
                <?php echo $this->Form->end(); ?>
                <?php } else { ?>
                    <div class="alert alert-warning">No season events found. Add events to this season first.</div>
                <?php } ?>
            </div>
            <div class="box-footer">
                <?php echo $this->Html->link('<i class="fa fa-arrow-left"></i> Back to Dashboard', ['controller'=>'admins', 'action'=>'dashboard'], ['class'=>'btn btn-default', 'escape'=>false]); ?>
                <?php if (!empty($eventJudgeRows)) { ?>
                <button type="submit" form="judgingAssignForm" class="btn btn-success">
                    <i class="fa fa-save"></i> Save Assignments
                </button>
                <?php } ?>
            </div>
        </div>

        <?php if (!empty($workloadData)) { ?>
        <div class="box box-default" style="margin-top:18px;">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-bar-chart"></i>&nbsp; Judge Workload Balance <small id="wl-avg-label">(saved avg: <?php echo $avgLoad; ?> events/judge)</small></h3>
                <div class="box-tools pull-right">
                    <small class="text-muted" style="font-size:12px;"><i class="fa fa-refresh"></i> Updates live as you change dropdowns</small>
                </div>
            </div>
            <div class="box-body" style="padding:10px;">
                <div class="jl-workload-note">Tip: closer to the average means better balance. Positive delta means overloaded.</div>
                <?php $maxSavedLoad = 0; foreach($workloadData as $wdx){ $maxSavedLoad = max($maxSavedLoad, (int)$wdx['count']); } ?>
                <div class="jl-workload-wrap">
                    <table id="workload_table" class="table table-condensed table-bordered">
                        <thead>
                            <tr>
                                <th class="wl-rank">#</th>
                                <th>Judge</th>
                                <th class="wl-count">Events</th>
                                <th class="wl-meter">Load Meter</th>
                                <th class="wl-balance">Delta vs Avg</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $rank = 0; foreach($workloadData as $wd) { $rank++; ?>
                            <?php
                            $cnt = (int)$wd['count'];
                            if($avgLoad <= 0) { $cls = 'default'; }
                            elseif($cnt <= ceil($avgLoad)) { $cls = 'success'; }
                            elseif($cnt <= ceil($avgLoad) + 2) { $cls = 'warning'; }
                            else { $cls = 'danger'; }
                            $pct = $maxSavedLoad > 0 ? (int)round(($cnt / $maxSavedLoad) * 100) : 0;
                            $delta = $cnt - $avgLoad;
                            $deltaText = $delta > 0 ? '+' . number_format($delta, 1) : number_format($delta, 1);
                            $deltaCls = abs($delta) < 0.05 ? 'neu' : ($delta > 0 ? 'pos' : 'neg');
                            ?>
                            <tr class="wl-row" data-uid="<?php echo (int)$wd['user_id']; ?>">
                                <td class="wl-rank"><?php echo $rank; ?></td>
                                <td><?php echo h($wd['name']); ?></td>
                                <td class="wl-count"><?php echo $cnt; ?></td>
                                <td class="wl-meter">
                                    <div class="jl-load-track"><span class="jl-load-fill <?php echo $cls; ?>" style="width:<?php echo $pct; ?>%"></span></div>
                                </td>
                                <td class="wl-balance <?php echo $deltaCls; ?>"><?php echo $deltaText; ?></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php } ?>

    </section>
</div>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script>
$(document).ready(function() {
    var activeMetricFilter = '';

    function smoothToggleAccordion(detailsEl) {
        var body = detailsEl.querySelector('.jl-accordion-body');
        if (!body || detailsEl.dataset.animating === '1') {
            return;
        }

        detailsEl.dataset.animating = '1';

        if (!detailsEl.open) {
            detailsEl.open = true;
            body.style.maxHeight = '0px';
            void body.offsetHeight;
            body.style.maxHeight = body.scrollHeight + 'px';

            window.setTimeout(function () {
                if (detailsEl.open) {
                    body.style.maxHeight = 'none';
                }
                detailsEl.dataset.animating = '0';
            }, 800);
            return;
        }

        var currentHeight = body.scrollHeight;
        body.style.maxHeight = currentHeight + 'px';
        void body.offsetHeight;
        body.style.maxHeight = '0px';

        window.setTimeout(function () {
            detailsEl.open = false;
            detailsEl.dataset.animating = '0';
        }, 760);
    }

    $('.jl-accordion-item').each(function() {
        var details = this;
        var summary = details.querySelector('summary');
        var body = details.querySelector('.jl-accordion-body');
        if (!summary || !body) {
            return;
        }

        if (details.open) {
            body.style.maxHeight = 'none';
        } else {
            body.style.maxHeight = '0px';
        }

        summary.addEventListener('click', function (event) {
            event.preventDefault();
            smoothToggleAccordion(details);
        });
    });

    var tables = [];
    $('.judging-list-table').each(function() {
        var dt = $(this).DataTable({
            bPaginate: true,
            bLengthChange: false,
            pageLength: 100,
            order: [[0,'asc']],
            columnDefs: [
                { orderable: false, targets: [3,5,6,7] },
                { type: 'num', targets: [4] }
            ]
        });
        tables.push(dt);
    });

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!$(settings.nTable).hasClass('judging-list-table')) {
            return true;
        }
        if (!activeMetricFilter) {
            return true;
        }

        var rowNode = settings.aoData && settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
        if (!rowNode) {
            return true;
        }

        var $row = $(rowNode);
        var assignedCount = parseInt($row.attr('data-assigned-count') || '0', 10);
        var preferredCount = parseInt($row.attr('data-preferred-count') || '0', 10);
        var entriesCount = parseInt($row.attr('data-entries-count') || '0', 10);

        if (activeMetricFilter === 'notAssigned') {
            return entriesCount > 0 && (assignedCount === 0 || preferredCount === 0);
        }
        if (activeMetricFilter === 'twoJudges') {
            return assignedCount === 2;
        }
        if (activeMetricFilter === 'threeJudges') {
            return assignedCount === 3;
        }
        return true;
    });

    function updateRowHighlights() {
        $('.judging-list-table tbody tr').each(function() {
            var filled = 0;
            $(this).find('select').each(function() {
                var val = $(this).val();
                if(val && val !== '') {
                    filled++;
                }
            });

            $(this).attr('data-assigned-count', filled);

            $(this).removeClass('jl-row-three jl-row-two jl-row-one');
            if(filled >= 3) {
                $(this).addClass('jl-row-three');
            } else if(filled === 2) {
                $(this).addClass('jl-row-two');
            } else if(filled === 1) {
                $(this).addClass('jl-row-one');
            }
        });
    }

    // Live workload recalculation when any judge dropdown changes
    function recalcWorkload() {
        var counts = {};
        $('.judging-list-table select').each(function() {
            var val = $(this).val();
            if(val && val !== '') {
                counts[val] = (counts[val] || 0) + 1;
            }
        });
        var total = 0, numJudges = 0;
        $('#workload_table tbody tr').each(function() {
            var uid = $(this).data('uid').toString();
            var cnt = counts[uid] || 0;
            total += cnt; numJudges++;
            $(this).find('.wl-count').text(cnt);
        });
        var avg = numJudges > 0 ? total / numJudges : 0;
        $('#wl-avg-label').text('(live avg: ' + avg.toFixed(1) + ' events/judge)');

        var max = 0;
        $('#workload_table tbody tr .wl-count').each(function() {
            var v = parseInt($(this).text(), 10) || 0;
            if (v > max) max = v;
        });

        $('#workload_table tbody tr').each(function() {
            var uid = $(this).data('uid').toString();
            var cnt = counts[uid] || 0;
            var cls;
            if(avg <= 0)                        { cls = 'default'; }
            else if(cnt <= Math.ceil(avg))      { cls = 'success'; }
            else if(cnt <= Math.ceil(avg) + 2)  { cls = 'warning'; }
            else                                { cls = 'danger'; }

            var pct = max > 0 ? Math.round((cnt / max) * 100) : 0;
            var delta = cnt - avg;
            var deltaText = (delta > 0 ? '+' : '') + delta.toFixed(1);
            var deltaCls = Math.abs(delta) < 0.05 ? 'neu' : (delta > 0 ? 'pos' : 'neg');

            $(this).find('.jl-load-fill')
                .removeClass('success warning danger default')
                .addClass(cls)
                .css('width', pct + '%');

            $(this).find('.wl-balance')
                .removeClass('pos neg neu')
                .addClass(deltaCls)
                .text(deltaText);
        });

        // Keep highest-load judges at top for quick balancing.
        var rows = $('#workload_table tbody tr').get();
        rows.sort(function(a, b) {
            var av = parseInt($(a).find('.wl-count').text(), 10) || 0;
            var bv = parseInt($(b).find('.wl-count').text(), 10) || 0;
            return bv - av;
        });
        $.each(rows, function(i, row) {
            $('#workload_table tbody').append(row);
            $(row).find('.wl-rank').text(i + 1);
        });
    }
    $('.judging-list-table').on('change', 'select', function() {
        recalcWorkload();
        updateRowHighlights();
        if (activeMetricFilter) {
            tables.forEach(function(dt){ dt.draw(); });
        }
    });

    function setMetricFilter(nextFilter) {
        activeMetricFilter = (activeMetricFilter === nextFilter) ? '' : nextFilter;

        $('#eventsNotAssignedCard, #eventsWithTwoJudgesCard, #eventsWithThreeJudgesCard').removeClass('is-active');
        if (activeMetricFilter === 'notAssigned') {
            $('#eventsNotAssignedCard').addClass('is-active');
        } else if (activeMetricFilter === 'twoJudges') {
            $('#eventsWithTwoJudgesCard').addClass('is-active');
        } else if (activeMetricFilter === 'threeJudges') {
            $('#eventsWithThreeJudgesCard').addClass('is-active');
        }

        tables.forEach(function(dt){ dt.draw(); });
    }

    $('#eventsNotAssignedCard').on('click', function() {
        setMetricFilter('notAssigned');
    });

    $('#eventsWithTwoJudgesCard').on('click', function() {
        setMetricFilter('twoJudges');
    });

    $('#eventsWithThreeJudgesCard').on('click', function() {
        setMetricFilter('threeJudges');
    });

    $('.judging-list-table').on('draw.dt', updateRowHighlights);
    updateRowHighlights();
    recalcWorkload();
});
</script>
