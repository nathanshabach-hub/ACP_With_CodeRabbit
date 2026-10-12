<div class="admin_loader" id="loaderID"><?php echo $this->Html->image('loader_large_blue.gif');?></div>
<?php if (!$eventsubmissions->isEmpty()) { ?>
    <?php $judgedIdsLookup = array_flip(array_map('intval', (array)($judgedSubmissionIds ?? []))); ?>
    <div class="panel-body admin-data-panel admin-esub-panel">
        <?php echo $this->Form->create(null, ['id' => 'schoolsUploadsBulkDeleteForm', 'method' => 'post', 'url' => ['controller' => 'eventsubmissions', 'action' => 'schoolsuploadsbulkdelete']]); ?>
        <section id="no-more-tables" class="lstng-section admin-data-section">
            <div class="topn admin-data-topn" style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                <div class="topn_left">All student uploads for selected convention season</div>
                <div class="topn_actions" style="margin-left:auto;display:flex;justify-content:flex-end;">
                    <button type="submit" id="bulkDeleteSchoolUploads" class="btn btn-danger" disabled>
                        <i class="fa fa-trash-o" aria-hidden="true"></i> Delete selected
                    </button>
                    <div id="bulkSchoolsUploadIds"></div>
                </div>
            </div>

            <div class="tbl-resp-listing admin-data-table-wrap">
                <table id="schools_uploads" class="table table-bordered table-striped table-condensed cf admin-data-table">
                    <thead class="cf ajshort">
                        <tr>
                            <th class="sorting_paging">
                                <input type="checkbox" id="selectAllSchoolUploads" aria-label="Select all deletable uploads" title="Select all deletable uploads">
                            </th>
                            <th class="sorting_paging">#ID</th>
                            <th class="sorting_paging">School</th>
                            <th class="sorting_paging">Student/Group</th>
                            <th class="sorting_paging">Event Number</th>
                            <th class="sorting_paging">Event Name</th>
                            <th class="sorting_paging">Submitted By</th>
                            <th class="sorting_paging">Submitted Date</th>
                            <th class="sorting_paging">File</th>
                            <th class="action_dvv"><i class="fa fa-gavel"></i> Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eventsubmissions as $datarecord) { ?>
                            <?php
                            $schoolName = 'N/A';
                            if (!empty($datarecord->Conventionregistrations) && !empty($datarecord->Conventionregistrations->Users)) {
                                $schoolName = trim((string)$datarecord->Conventionregistrations->Users['first_name']);
                                if ($schoolName === '') {
                                    $schoolName = trim((string)$datarecord->Conventionregistrations->Users['email_address']);
                                }
                            }
                            ?>
                            <tr>
                                <td data-title="Select">
                                    <?php $hasJudgeEvaluation = isset($judgedIdsLookup[(int)$datarecord->id]); ?>
                                    <input type="checkbox" class="school-upload-checkbox" value="<?php echo (int)$datarecord->id; ?>" aria-label="Select upload <?php echo (int)$datarecord->id; ?>" <?php echo $hasJudgeEvaluation ? 'disabled title="A judge evaluation exists"' : ''; ?>>
                                </td>
                                <td data-title="ID"><?php echo (int)$datarecord->id; ?></td>
                                <td data-title="School"><?php echo h($schoolName); ?></td>
                                <td data-title="Student">
                                    <?php
                                    $groupLabel = trim((string)($datarecord->group_name ?? ''));
                                    if ($datarecord->student_id > 0 && !empty($datarecord->Students)) {
                                        $studentName = trim(
                                            (isset($datarecord->Students['first_name']) ? $datarecord->Students['first_name'] : '') . ' ' .
                                            (isset($datarecord->Students['middle_name']) ? $datarecord->Students['middle_name'] : '') . ' ' .
                                            (isset($datarecord->Students['last_name']) ? $datarecord->Students['last_name'] : '')
                                        );
                                        $studentText = $studentName !== '' ? $studentName : 'N/A';
                                        if ($groupLabel !== '') {
                                            $studentText .= ' (Group ' . $groupLabel . ')';
                                        }
                                        echo h($studentText);
                                    } else {
                                        echo $groupLabel !== '' ? h('Group ' . $groupLabel) : 'N/A';
                                    }
                                    ?>
                                </td>
                                <td data-title="Event Number"><?php echo h((string)$datarecord->event_id_number); ?></td>
                                <td data-title="Event Name"><?php echo !empty($datarecord->Events['event_name']) ? h($datarecord->Events['event_name']) : 'N/A'; ?></td>
                                <td data-title="Submitted By"><?php echo h(trim((isset($datarecord->Uploadeduser['first_name']) ? $datarecord->Uploadeduser['first_name'] : '') . ' ' . (isset($datarecord->Uploadeduser['last_name']) ? $datarecord->Uploadeduser['last_name'] : ''))); ?></td>
                                <td data-title="Submitted Date"><?php echo !empty($datarecord->created) ? h($datarecord->created->format('M d, Y')) : 'N/A'; ?></td>
                                <td data-title="File">
                                    <?php
                                    $files = [
                                        $datarecord->mediafile_file_system_name => (isset($datarecord->Events['upload_type']) ? $datarecord->Events['upload_type'] : 'Upload'),
                                        $datarecord->report => 'Report',
                                        $datarecord->score_sheet => 'Score Sheet',
                                        $datarecord->additional_documents => 'Additional Documents'
                                    ];
                                    foreach ($files as $file => $title) {
                                        if (file_exists(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$file) && !empty($file)) {
                                            echo '<a class="btn btn-info btn-xs" target="_blank" title="'.$title.'" href="'.DISPLAY_EVENTS_SUBMISSION_DOCUMENT_PATH.$file.'"><i class="fa fa-cloud-download"></i> '.h($title).'</a> ';
                                        }
                                    }
                                    ?>
                                </td>
                                <td data-title="Action">
                                    <?php
                                    if ($hasJudgeEvaluation) {
                                        echo '<i title="Evaluation submitted by judge" class="fa fa-gavel"></i>';
                                    } else {
                                        if (!empty($datarecord->Conventionregistrations) && !empty($datarecord->Conventionregistrations->slug)) {
                                            echo $this->Html->link('<i class="fa fa-trash-o"></i>', ['controller' => 'eventsubmissions', 'action' => 'removesubmission', $datarecord->slug, $datarecord->Conventionregistrations->slug], ['escape' => false, 'title' => 'Remove', 'class' => 'btn btn-danger btn-xs', 'confirm' => 'Are you sure you want to remove this submission?']);
                                        } else {
                                            echo '<span class="btn btn-default btn-xs disabled" title="Remove unavailable"><i class="fa fa-trash-o"></i></span>';
                                        }
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php echo $this->Form->end(); ?>
    </div>
<?php } else { ?>
    <div class="admin_no_record">No student uploads found for this convention season.</div>
<?php } ?>

<script>
$(document).ready(function() {
    var table = $('#schools_uploads').DataTable({
        "bPaginate": true,
        "bLengthChange": false,
        "pageLength": 100,
        "order": [[1, 'asc']]
    });

    function refreshBulkDeleteState() {
        var checked = $('.school-upload-checkbox:checked');
        var button = $('#bulkDeleteSchoolUploads');
        button.prop('disabled', checked.length === 0);

        var container = $('#bulkSchoolsUploadIds');
        container.empty();
        checked.each(function() {
            container.append('<input type="hidden" name="submission_ids[]" value="' + $(this).val() + '">');
        });

        var selectableCount = $('.school-upload-checkbox:not(:disabled)').length;
        var selectedCount = checked.length;
        $('#selectAllSchoolUploads').prop('checked', selectableCount > 0 && selectedCount === selectableCount);
    }

    $(document).on('change', '.school-upload-checkbox', refreshBulkDeleteState);
    $('#selectAllSchoolUploads').on('change', function() {
        $('.school-upload-checkbox:not(:disabled)').prop('checked', this.checked);
        refreshBulkDeleteState();
    });

    $('#schoolsUploadsBulkDeleteForm').on('submit', function(e) {
        if ($('.school-upload-checkbox:checked').length === 0) {
            e.preventDefault();
            return false;
        }
        if (!window.confirm('Are you sure you want to delete the selected uploads?')) {
            e.preventDefault();
            return false;
        }
        return true;
    });

    table.on('draw', refreshBulkDeleteState);
    refreshBulkDeleteState();
});
</script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<style type="text/css">
    .page-link { color: #1c2452 !important; background-color: #fff !important; }
    .active>.page-link, .page-link.active { background-color: #1c2452 !important; border-color: #1c2452 !important; color: #fff !important; }
    .pagination { border-radius: 0rem !important; }

    /* Keep this table readable even when external DataTables theme CSS conflicts */
    #schools_uploads.table > tbody > tr > td {
        color: #1f2937 !important;
        background-color: #ffffff !important;
        vertical-align: middle;
    }
    #schools_uploads.table-striped > tbody > tr:nth-of-type(odd) > td {
        background-color: #f4f7fb !important;
    }
    #schools_uploads.table-striped > tbody > tr:nth-of-type(even) > td {
        background-color: #ffffff !important;
    }
    #schools_uploads.table > tbody > tr:hover > td {
        background-color: #eaf1fb !important;
    }
    #schools_uploads.table > tbody > tr > td a {
        color: inherit;
    }
    #schools_uploads.table > tbody > tr > td a.btn,
    #schools_uploads.table > tbody > tr > td a.btn i {
        color: #fff !important;
    }
    #schools_uploads.table > tbody > tr > td a.btn:hover,
    #schools_uploads.table > tbody > tr > td a.btn:focus,
    #schools_uploads.table > tbody > tr > td a.btn:hover i,
    #schools_uploads.table > tbody > tr > td a.btn:focus i {
        color: #fff !important;
    }
</style>
