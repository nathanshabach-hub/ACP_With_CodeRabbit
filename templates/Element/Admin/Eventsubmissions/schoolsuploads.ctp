<div class="admin_loader" id="loaderID"><?php echo $this->Html->image('loader_large_blue.gif');?></div>
<?php if (!$eventsubmissions->isEmpty()) { ?>
    <div class="panel-body admin-data-panel admin-esub-panel">
        <section id="no-more-tables" class="lstng-section admin-data-section">
            <div class="topn admin-data-topn" style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                <div class="topn_left">All student uploads for selected convention season</div>
            </div>

            <div class="tbl-resp-listing admin-data-table-wrap">
                <table id="schools_uploads" class="table table-bordered table-striped table-condensed cf admin-data-table">
                    <thead class="cf ajshort">
                        <tr>
                            <th class="sorting_paging">#ID</th>
                            <th class="sorting_paging">School</th>
                            <th class="sorting_paging">Student</th>
                            <th class="sorting_paging">Event Number</th>
                            <th class="sorting_paging">Event Name</th>
                            <th class="sorting_paging">Submitted By</th>
                            <th class="sorting_paging">Submitted Date</th>
                            <th class="sorting_paging">File</th>
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
                                <td data-title="ID"><?php echo (int)$datarecord->id; ?></td>
                                <td data-title="School"><?php echo h($schoolName); ?></td>
                                <td data-title="Student">
                                    <?php
                                    if ($datarecord->student_id > 0 && !empty($datarecord->Students)) {
                                        $studentName = trim(
                                            (isset($datarecord->Students['first_name']) ? $datarecord->Students['first_name'] : '') . ' ' .
                                            (isset($datarecord->Students['middle_name']) ? $datarecord->Students['middle_name'] : '') . ' ' .
                                            (isset($datarecord->Students['last_name']) ? $datarecord->Students['last_name'] : '')
                                        );
                                        echo h($studentName !== '' ? $studentName : 'N/A');
                                    } else {
                                        echo 'N/A';
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
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
<?php } else { ?>
    <div class="admin_no_record">No student uploads found for this convention season.</div>
<?php } ?>

<script>
$(document).ready(function() {
    $('#schools_uploads').DataTable({
        "bPaginate": true,
        "bLengthChange": false,
        "pageLength": 100,
        "order": [[1, 'asc']]
    });
});
</script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<style type="text/css">
    .page-link { color: #1c2452 !important; background-color: #fff !important; }
    .active>.page-link, .page-link.active { background-color: #1c2452 !important; border-color: #1c2452 !important; color: #fff !important; }
    .pagination { border-radius: 0rem !important; }
</style>
