<?php echo $this->Html->script('ajax-pagging.js'); ?>
<div class="content-wrapper admin-esub-page">
    <section class="content-header">
        <?php $conventionName = !empty($convSeasonD->Conventions['name']) ? $convSeasonD->Conventions['name'] : 'Selected Convention'; ?>
        <h1>
            Students Uploads :: <?php echo h($conventionName); ?> (<?php echo h($convSeasonD->season_year); ?>)
        </h1>
        <ol class="breadcrumb">
            <li><?php echo $this->Html->link('<i class="fa fa-dashboard"></i> <span>Dashboard</span> ', ['controller' => 'admins', 'action' => 'dashboard'], ['escape' => false]);?></li>
            <li><?php echo $this->Html->link('<i class="fa fa-database"></i> Event Submissions ', ['controller' => 'eventsubmissions', 'action' => 'index'], ['escape' => false]);?></li>
            <li class="active">Students Uploads</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-info admin-data-box admin-esub-box">
            <div class="ersu_message"> <?php echo $this->Flash->render() ?></div>
            <div class="m_content admin-data-content" id="listID">
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
                                                            $previewUrl = DISPLAY_EVENTS_SUBMISSION_DOCUMENT_PATH.$file;
                                                            echo '<a class="btn btn-info btn-xs js-file-preview-link" title="'.h($title).'" href="#" data-file-url="'.h($previewUrl).'" data-file-name="'.h($file).'" data-file-title="'.h($title).'"><i class="fa fa-cloud-download"></i> '.h($title).'</a> ';
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
            </div>
        </div>
    </section>
</div>

<div id="filePreviewModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:10000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;width:92%;max-width:1100px;height:88vh;box-shadow:0 8px 32px rgba(0,0,0,0.35);display:flex;flex-direction:column;overflow:hidden;">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-bottom:1px solid #e3e6ea;">
            <strong id="filePreviewTitle" style="font-size:14px;line-height:1.2;color:#1c2b3a;">File Preview</strong>
            <div style="display:flex;align-items:center;gap:10px;">
                <a id="filePreviewOpenNew" href="#" target="_blank" rel="noopener" style="font-size:12px;color:#1a5a96;text-decoration:underline;">Open in new tab</a>
                <button type="button" onclick="closeFilePreviewModal()" style="border:none;background:#fff;color:#333;font-size:20px;line-height:1;cursor:pointer;">&times;</button>
            </div>
        </div>
        <div id="filePreviewBody" style="flex:1;min-height:0;background:#f7f9fc;"></div>
    </div>
</div>

<script>
var filePreviewBlobUrl = null;

function closeFilePreviewModal() {
    var modal = document.getElementById('filePreviewModal');
    var body = document.getElementById('filePreviewBody');
    if (body) {
        body.innerHTML = '';
    }
    if (filePreviewBlobUrl) {
        URL.revokeObjectURL(filePreviewBlobUrl);
        filePreviewBlobUrl = null;
    }
    if (modal) {
        modal.style.display = 'none';
    }
}

function openFilePreviewModal(fileUrl, fileTitle, fileName) {
    var modal = document.getElementById('filePreviewModal');
    var titleEl = document.getElementById('filePreviewTitle');
    var body = document.getElementById('filePreviewBody');
    var openNew = document.getElementById('filePreviewOpenNew');
    if (!modal || !titleEl || !body || !openNew) {
        window.open(fileUrl, '_blank');
        return;
    }

    var ext = '';
    if (fileName && fileName.indexOf('.') > -1) {
        ext = fileName.split('.').pop().toLowerCase();
    }
    if (!ext && fileUrl) {
        var cleanUrl = fileUrl.split('#')[0].split('?')[0];
        if (cleanUrl.indexOf('.') > -1) {
            ext = cleanUrl.split('.').pop().toLowerCase();
        }
    }

    titleEl.textContent = fileTitle || 'File Preview';
    openNew.href = fileUrl;
    body.innerHTML = '';

    var officeExts = ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'dox'];
    var imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    var isImage = imageExts.indexOf(ext) !== -1;
    var isPdf = (ext === 'pdf');
    var isOffice = officeExts.indexOf(ext) !== -1;
    var isLocalHost = (window.location.hostname === '127.0.0.1' || window.location.hostname === 'localhost');

    var renderFallbackCard = function (message) {
        var wrap = document.createElement('div');
        wrap.style.cssText = 'height:100%;display:flex;align-items:center;justify-content:center;padding:20px;background:#fff;';

        var card = document.createElement('div');
        card.style.cssText = 'max-width:560px;width:100%;border:1px solid #d8dee7;border-radius:10px;padding:18px;background:#f9fbff;color:#1f2937;';

        var p = document.createElement('p');
        p.textContent = message;
        p.style.cssText = 'margin:0 0 12px 0;font-size:14px;line-height:1.45;';
        card.appendChild(p);

        var actions = document.createElement('div');
        actions.style.cssText = 'display:flex;gap:10px;flex-wrap:wrap;';

        var openBtn = document.createElement('a');
        openBtn.href = fileUrl;
        openBtn.target = '_blank';
        openBtn.rel = 'noopener';
        openBtn.textContent = 'Open in new tab';
        openBtn.style.cssText = 'display:inline-block;padding:8px 12px;border-radius:6px;background:#1f4f8b;color:#fff !important;text-decoration:none;font-size:13px;font-weight:600;';
        actions.appendChild(openBtn);

        var downloadBtn = document.createElement('a');
        downloadBtn.href = fileUrl;
        downloadBtn.setAttribute('download', fileName || 'file');
        downloadBtn.textContent = 'Download file';
        downloadBtn.style.cssText = 'display:inline-block;padding:8px 12px;border-radius:6px;border:1px solid #6b7b8f;color:#1f2937 !important;text-decoration:none;font-size:13px;font-weight:600;background:#fff;';
        actions.appendChild(downloadBtn);

        card.appendChild(actions);
        wrap.appendChild(card);
        body.appendChild(wrap);
    };

    var renderImagePreview = function (overrideSrc) {
        var imgWrap = document.createElement('div');
        imgWrap.style.cssText = 'height:100%;display:flex;align-items:center;justify-content:center;padding:10px;overflow:auto;background:#fff;';
        var img = document.createElement('img');
        img.src = overrideSrc || fileUrl;
        img.alt = fileTitle || 'Preview image';
        img.style.cssText = 'max-width:100%;max-height:100%;box-shadow:0 2px 12px rgba(0,0,0,0.2);background:#fff;';
        imgWrap.appendChild(img);
        body.appendChild(imgWrap);
    };

    var renderPdfPreview = function (overrideSrc) {
        var obj = document.createElement('object');
        obj.data = overrideSrc || fileUrl;
        obj.type = 'application/pdf';
        obj.style.cssText = 'width:100%;height:100%;border:0;background:#fff;';
        obj.onerror = function () {
            body.innerHTML = '';
            renderFallbackCard('PDF preview is not available in this browser view.');
        };
        body.appendChild(obj);
    };

    var renderByType = function (detectedIsPdf) {
        if (detectedIsPdf) {
            renderPdfPreview();
        } else if (isImage) {
            renderImagePreview();
        } else if (isOffice) {
            if (!isLocalHost && /^https?:\/\//i.test(fileUrl)) {
                var officeFrame = document.createElement('iframe');
                officeFrame.src = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(fileUrl);
                officeFrame.style.cssText = 'width:100%;height:100%;border:0;background:#fff;';
                officeFrame.setAttribute('title', fileTitle || 'Office file preview');
                body.appendChild(officeFrame);
            } else {
                renderFallbackCard('Office preview is unavailable on localhost. Open this same file from your live site URL to preview inline.');
            }
        } else {
            var iframe = document.createElement('iframe');
            iframe.src = fileUrl;
            iframe.style.cssText = 'width:100%;height:100%;border:0;background:#fff;';
            iframe.setAttribute('title', fileTitle || 'File preview');
            iframe.onerror = function () {
                body.innerHTML = '';
                renderFallbackCard('Inline preview is not available for this file type.');
            };
            body.appendChild(iframe);
        }
    };

    var detectBinaryType = function (byteArray) {
        if (!byteArray || byteArray.length < 4) {
            return '';
        }
        if (byteArray[0] === 0x25 && byteArray[1] === 0x50 && byteArray[2] === 0x44 && byteArray[3] === 0x46) {
            return 'pdf';
        }
        if (byteArray[0] === 0xFF && byteArray[1] === 0xD8 && byteArray[2] === 0xFF) {
            return 'jpeg';
        }
        if (byteArray[0] === 0x89 && byteArray[1] === 0x50 && byteArray[2] === 0x4E && byteArray[3] === 0x47) {
            return 'png';
        }
        if (byteArray[0] === 0x47 && byteArray[1] === 0x49 && byteArray[2] === 0x46) {
            return 'gif';
        }
        return '';
    };

    modal.style.display = 'flex';
    body.innerHTML = '<div style="height:100%;display:flex;align-items:center;justify-content:center;color:#46566b;font-size:14px;">Loading preview...</div>';

    if (window.fetch && /^https?:\/\//i.test(fileUrl) && isPdf) {
        fetch(fileUrl, { method: 'GET', credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Unable to load file');
                }
                return response.blob();
            })
            .then(function (blob) {
                return blob.arrayBuffer().then(function (buffer) {
                    var detected = detectBinaryType(new Uint8Array(buffer).subarray(0, 12));
                    body.innerHTML = '';
                    if (detected === 'pdf') {
                        renderPdfPreview(fileUrl);
                    } else if (detected === 'jpeg' || detected === 'png' || detected === 'gif') {
                        if (filePreviewBlobUrl) {
                            URL.revokeObjectURL(filePreviewBlobUrl);
                        }
                        filePreviewBlobUrl = URL.createObjectURL(blob);
                        renderImagePreview(filePreviewBlobUrl);
                    } else {
                        renderPdfPreview();
                    }
                });
            })
            .catch(function () {
                body.innerHTML = '';
                renderPdfPreview();
            });
        return;
    }

    if (window.fetch && /^https?:\/\//i.test(fileUrl)) {
        fetch(fileUrl, { method: 'HEAD', credentials: 'same-origin' })
            .then(function (response) {
                var contentType = (response.headers.get('content-type') || '').toLowerCase();
                body.innerHTML = '';
                renderByType(contentType.indexOf('application/pdf') !== -1);
            })
            .catch(function () {
                body.innerHTML = '';
                renderByType(false);
            });
        return;
    }

    body.innerHTML = '';
    renderByType(false);
}

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

    $(document).on('click', '.js-file-preview-link', function(event) {
        event.preventDefault();
        openFilePreviewModal($(this).attr('data-file-url'), $(this).attr('data-file-title'), $(this).attr('data-file-name'));
    });

    $('#filePreviewModal').on('click', function(event) {
        if (event.target.id === 'filePreviewModal') {
            closeFilePreviewModal();
        }
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
