<?php
use Cake\ORM\TableRegistry;
$this->Events = TableRegistry::getTableLocator()->get('Events');
?>
<div class="admin_loader" id="loaderID"><?php echo $this->Html->image('loader_large_blue.gif');?></div>

<div class="panel-body judges-page">
    <div class="ersu_message"> <?php echo $this->Flash->render() ?></div>
    <?php echo $this->Form->create(null, ['id' => 'actionFrom', 'method' => 'post']); ?>
    <section id="no-more-tables" class="lstng-section judges-shell">
        <div class="judges-head">
            <div>
                <h3>Judges Directory</h3>
                <p>Review assigned judges and the events they are evaluating.</p>
            </div>
            <div class="judges-tools">
                <input id="judgeSearchInput" type="text" class="form-control" placeholder="Search judge name, email, or event">
            </div>
        </div>

        <div class="tbl-resp-listing judges-table-wrap">
            <table id="convention_judges" class="table table-bordered table-condensed cf judges-table">
                <thead class="cf ajshort">
                    <tr>
                        <th class="sorting_paging">Judge</th>
                        <th class="sorting_paging">Email</th>
                        <th class="sorting_paging">Assigned</th>
                        <th class="sorting_paging">Event(s)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $visibleJudges = 0;
                    $totalAssignedEvents = 0;
                    foreach ($judgeslist as $datarecord) {
                        if ($datarecord->Users['user_type'] == 'Judge' || ($datarecord->Users['user_type'] == 'Teacher_Parent' && $datarecord->Users['is_judge'] == 1)) {
                            $totalEvents = 0;
                            $eventsList = 'N/A';
                            if (!empty($datarecord->judges_event_ids) && $datarecord->judges_event_ids != null) {
                                $condJudgeEvents = array();
                                $condJudgeEvents[] = "(Events.id IN (".$datarecord->judges_event_ids."))";
                                $judgeEvents = $this->Events->find()->where($condJudgeEvents)->order(["Events.event_name" => 'ASC'])->all();
                                $arrJE = array();
                                foreach ($judgeEvents as $judgeev) {
                                    $arrJE[] = $judgeev->event_name.' ('.$judgeev->event_id_number.')';
                                }
                                if (count($arrJE)) {
                                    $eventsList = implode(', ', $arrJE);
                                    $totalEvents = count($arrJE);
                                }
                            }
                            $visibleJudges++;
                            $totalAssignedEvents += $totalEvents;
                    ?>
                    <tr>
                        <td data-title="Judge Name" class="judge-name-col">
                            <?php
                            $jName = trim($datarecord->Users['first_name'].' '.$datarecord->Users['last_name']);
                            if ($jName === '') {
                                $jName = 'Unnamed Judge';
                            }
                            echo $this->Html->link('<i class="fa fa-user-secret"></i> '.h($jName), ['controller' => 'conventions', 'action' => 'judgesevents', $datarecord->slug], ['escape' => false, 'title' => 'View events for this judge', 'class' => 'btn btn-primary btn-xs judge-link']);
                            ?>
                        </td>
                        <td data-title="Judge Email" class="judge-email-col"><?php echo h($datarecord->Users['email_address']); ?></td>
                        <td data-title="Total Events" class="judge-count-col"><span class="event-pill"><?php echo (int)$totalEvents; ?> event<?php echo (int)$totalEvents === 1 ? '' : 's'; ?></span></td>
                        <td data-title="Event(s)" class="judge-events-col"><?php echo h($eventsList); ?></td>
                    </tr>
                    <?php } } ?>
                </tbody>
            </table>
        </div>
        <div class="judges-summary">
            <span class="sum-item"><strong><?php echo (int)$visibleJudges; ?></strong> judges listed</span>
            <span class="sum-item"><strong><?php echo (int)$totalAssignedEvents; ?></strong> total event assignments</span>
        </div>
    </section>

    <?php echo $this->Form->end(); ?>
</div>

<script>
$(document).ready(function() {
var judgeTable = $('#convention_judges').DataTable({
    "bPaginate": true,
    "bLengthChange": false,
    "pageLength": 100,
    "order": [[0, 'asc']],
    "dom": 'rt<"judges-pagination"p>',
    "language": {
        "paginate": {
            "previous": "Prev",
            "next": "Next"
        },
        "emptyTable": "No judges found for this season."
    }
});

$('#judgeSearchInput').on('keyup', function() {
    judgeTable.search(this.value).draw();
});
});
</script>

<!--
<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
-->
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<style type="text/css">
    .judges-shell {
        background: #ffffff;
        border: 1px solid #e3e9f3;
        border-radius: 10px;
        padding: 14px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }

    .judges-head {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-end;
        margin-bottom: 14px;
    }

    .judges-head h3 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #233a61;
    }

    .judges-head p {
        margin: 3px 0 0 0;
        color: #6f7f97;
        font-size: 13px;
    }

    .judges-tools {
        width: 340px;
        max-width: 100%;
    }

    #judgeSearchInput {
        border: 1px solid #ced9eb;
        border-radius: 8px;
        height: 38px;
        box-shadow: none;
    }

    #judgeSearchInput:focus {
        border-color: #3c7bd6;
        box-shadow: 0 0 0 2px rgba(60, 123, 214, 0.12);
    }

    .judges-table-wrap {
        border: 1px solid #e5ebf6;
        border-radius: 8px;
        overflow: hidden;
    }

    .judges-table {
        margin: 0 !important;
    }

    .judges-table thead th {
        background: #254873;
        color: #fff;
        border-color: #2f598a !important;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .judges-table tbody td {
        vertical-align: top;
        border-color: #ecf1f8 !important;
    }

    .judge-link {
        border-radius: 6px;
        padding: 6px 10px;
        font-size: 12px;
    }

    .judge-link i {
        margin-right: 5px;
    }

    .judge-email-col {
        word-break: break-word;
    }

    .event-pill {
        display: inline-block;
        background: #e9f2ff;
        color: #234a82;
        border: 1px solid #d7e5fb;
        border-radius: 999px;
        padding: 3px 10px;
        font-size: 12px;
        font-weight: 600;
    }

    .judge-events-col {
        color: #44546a;
        line-height: 1.5;
        font-size: 13px;
    }

    .judges-summary {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 12px;
    }

    .sum-item {
        background: #f2f6fd;
        color: #3b4c68;
        border: 1px solid #dce6f7;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .judges-pagination {
        padding: 10px 12px;
        border-top: 1px solid #e5ebf6;
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

    @media (max-width: 767px) {
        .judges-head {
            flex-direction: column;
            align-items: stretch;
        }

        .judges-tools {
            width: 100%;
        }

        .judges-head h3 {
            font-size: 19px;
        }
    }
</style>