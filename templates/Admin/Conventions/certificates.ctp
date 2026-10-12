<script type="text/javascript">
    $(document).ready(function() {
        $("#adminForm").validate();
    });
</script>

<div class="content-wrapper admin-form-page admin-certificates-page">
    <section class="content-header">
      <h1>
         Generate Certificate - <?php echo $conventionD->name; ?> (<?php echo $conventionSD->season_year; ?>)
      </h1>
      <ol class="breadcrumb">
          <li><?php echo $this->Html->link('<i class="fa fa-dashboard"></i> <span>Dashboard</span>', ['controller'=>'admins', 'action'=>'dashboard'], ['escape'=>false]);?></li>
          <li><?php echo $this->Html->link('<i class="fa fa-bars"></i> Conventions', ['controller'=>'conventions', 'action'=>'index'], ['escape'=>false]);?></li>
          <li><?php echo $this->Html->link('<i class="fa fa-bullhorn"></i> Seasons', ['controller'=>'conventions', 'action'=>'seasons',$slug_convention], ['escape'=>false]);?></li>
          <li class="active">Generate Certificate - <?php echo $conventionD->name; ?></li>
      </ol>
    </section>

    <section class="content">
     <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Certificate Details</h3>
            </div>
            <div class="ersu_message"> <?php echo $this->Flash->render() ?> </div>
            <div class="form-horizontal">
                    <div class="box-body">

                        <div class="admin-form-note">Add each certificate to the queue, then click <strong>Print All</strong> to print them all at once.</div>

                        <div class="form-group compact-field">
                            <label class="col-sm-2 control-label">Certificate Type <span class="require">*</span></label>
                            <div class="col-sm-10">
                                <select id="cert_type" class="form-control cert-type-native" style="margin-bottom:2px;" autocomplete="off">
                                    <option value="">Choose Certificate Type</option>
                                    <?php foreach ($certTypes as $k => $v): ?>
                                    <option value="<?php echo h($k); ?>"><?php echo h($v); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group compact-field">
                            <label class="col-sm-2 control-label">Recipient Name <span class="require">*</span></label>
                            <div class="col-sm-10">
                                <input type="text" id="cert_name" class="form-control" placeholder="Enter recipient name" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false">
                                <small class="text-muted">Known names auto-fill school on blur. If not found, enter school manually.</small>
                            </div>
                        </div>

                        <div class="form-group compact-field" id="recipient_type_group">
                            <label class="col-sm-2 control-label">Recipient Type <span class="require">*</span></label>
                            <div class="col-sm-10">
                                <select id="recipient_type" class="form-control" style="margin-bottom:2px;">
                                    <option value="">Choose Recipient Type</option>
                                    <?php foreach ($recipientTypes as $k => $v): ?>
                                    <option value="<?php echo h($k); ?>"><?php echo h($v); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group compact-field">
                            <label class="col-sm-2 control-label">School <span class="require">*</span></label>
                            <div class="col-sm-10">
                                <input type="text" id="cert_school" class="form-control" placeholder="Enter school name" autocomplete="off" autocorrect="off" autocapitalize="words" spellcheck="false">
                            </div>
                        </div>

                        <div class="form-group compact-field">
                            <label class="col-sm-2 control-label">Description / Achievement</label>
                            <div class="col-sm-10">
                                <input type="text" id="cert_description" class="form-control" placeholder="e.g. 1st Place in Bible Reading, Outstanding Performance..." autocomplete="off" autocorrect="off" autocapitalize="sentences" spellcheck="false">
                            </div>
                        </div>

                        <div class="box-footer">
                            <label class="col-sm-2 control-label">&nbsp;</label>
                            <button type="button" id="addToQueueBtn" class="btn btn-success"><i class="fa fa-plus"></i> Add to Queue</button>
                            <?php echo $this->Html->link('Cancel', ['controller' => 'conventions', 'action' => 'seasons', $slug_convention], ['class' => 'btn btn-default canlcel_le']); ?>
                        </div>

                    </div>
                </div>
          </div>

          <!-- Queue Table -->
          <div class="box box-warning" id="queue_box" style="display:none; margin-top:10px;">
            <div class="box-header with-border">
                <h3 class="box-title">Certificate Queue (<span id="queue_count">0</span>)</h3>
                <div class="box-tools pull-right">
                    <button type="button" id="printAllBtn" class="btn btn-info btn-sm"><i class="fa fa-print"></i> Print All</button>
                    <button type="button" id="printWithoutBackgroundBtn" class="btn btn-primary btn-sm"><i class="fa fa-print"></i> Print Without Background</button>
                    <button type="button" id="clearQueueBtn" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Clear Queue</button>
                </div>
            </div>
            <div class="box-body">
                <table class="table table-bordered table-striped" id="queue_table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Certificate Type</th>
                            <th>Recipient Name</th>
                            <th>Recipient Type</th>
                            <th>School</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="queue_tbody"></tbody>
                </table>
            </div>
          </div>

          <!-- Hidden form to batch submit -->
          <form id="batchPrintForm" method="POST" action="<?php echo $this->Url->build(['controller'=>'conventions','action'=>'certificatesbatchpdf',$slug_convention_season,$slug_convention]); ?>" target="_blank" style="display:none;">
            <input type="hidden" name="queue_data" id="queue_data_input">
          </form>

                    <form id="batchPlainPrintForm" method="POST" action="<?php echo $this->Url->build(['controller'=>'conventions','action'=>'certificatesbatchplainprint',$slug_convention_season,$slug_convention]); ?>" target="_blank" style="display:none;">
                        <input type="hidden" name="queue_data" id="queue_data_plain_input">
                    </form>

          <script>
            var certQueue = [];
            var awardCertTypes = ['silver_apple','golden_apple','golden_lamb','golden_harp','christian_worker','christian_soilder'];
            var certTypeLabels = <?php echo json_encode($certTypes); ?>;
            var recipientTypeLabels = <?php echo json_encode($recipientTypes); ?>;
            var recipientDirectory = <?php echo json_encode(array_values((array)($recipientDirectory ?? []))); ?>;
            var awardDescriptionByType = <?php echo json_encode((array)($awardDescriptionByType ?? [])); ?>;

            function normalizeRecipientName(value) {
                return $.trim((value || '').toString()).replace(/\s+/g, ' ').toLowerCase();
            }

            var recipientSchoolByName = {};
            recipientDirectory.forEach(function(item) {
                var rawName = (item && item.name) ? item.name : '';
                var normalized = normalizeRecipientName(rawName);
                if (!normalized) {
                    return;
                }
                if (!recipientSchoolByName[normalized]) {
                    recipientSchoolByName[normalized] = {
                        name: rawName,
                        school_name: (item && item.school_name) ? item.school_name : ''
                    };
                }
            });

            function applyRecipientSchoolAutofill() {
                var nameVal = $('#cert_name').val();
                var normalized = normalizeRecipientName(nameVal);
                var match = normalized ? recipientSchoolByName[normalized] : null;
                var schoolField = $('#cert_school');
                var wasAutofilled = schoolField.data('autofill') === 1;

                if (match && $.trim(match.school_name || '') !== '') {
                    schoolField.val(match.school_name);
                    schoolField.data('autofill', 1);
                } else if (wasAutofilled) {
                    schoolField.val('');
                    schoolField.data('autofill', 0);
                }
            }

            function applyCertTypeDescriptionAutofill(force) {
                var certType = $('#cert_type').val();
                var autoDescription = $.trim((awardDescriptionByType[certType] || '').toString());
                var descField = $('#cert_description');
                var currentVal = $.trim(descField.val());
                var wasAutofilled = descField.data('autofill') === 1;

                if (force || currentVal === '' || wasAutofilled) {
                    if (autoDescription !== '') {
                        descField.val(autoDescription);
                        descField.data('autofill', 1);
                    } else if (wasAutofilled) {
                        descField.val('');
                        descField.data('autofill', 0);
                    }
                }
            }

            function toggleRecipientType() {
                var val = $('#cert_type').val();
                if (awardCertTypes.indexOf(val) !== -1) {
                    $('#recipient_type_group').hide();
                    $('#recipient_type').val('');
                } else {
                    $('#recipient_type_group').show();
                }
            }
            $(document).ready(function() {
                // Defensive: keep Certificate Type as native select even if global scripts auto-attach Select2.
                if ($('#cert_type').hasClass('select2-hidden-accessible')) {
                    $('#cert_type').select2('destroy');
                }
                setTimeout(function() {
                    if ($('#cert_type').hasClass('select2-hidden-accessible')) {
                        $('#cert_type').select2('destroy');
                    }
                }, 0);

                toggleRecipientType();
                $('#cert_type').on('change', function() {
                    toggleRecipientType();
                    applyCertTypeDescriptionAutofill(false);
                });

                $('#cert_name').on('input change blur', applyRecipientSchoolAutofill);
                $('#cert_school').on('input', function() {
                    $(this).data('autofill', 0);
                });
                $('#cert_description').on('input', function() {
                    $(this).data('autofill', 0);
                });

                applyCertTypeDescriptionAutofill(true);

                $('#addToQueueBtn').on('click', function() {
                    var certType     = $('#cert_type').val();
                    var name         = $.trim($('#cert_name').val());
                    var recipientType= $('#recipient_type').val();
                    var school       = $.trim($('#cert_school').val());
                    var description  = $.trim($('#cert_description').val());

                    if (!certType) { alert('Please select a Certificate Type.'); return; }
                    if (!name)     { alert('Please enter a Recipient Name.'); return; }
                    if (!school)   { alert('Please enter a School.'); return; }
                    if (awardCertTypes.indexOf(certType) === -1 && !recipientType) {
                        alert('Please select a Recipient Type.'); return;
                    }

                    certQueue.push({ cert_type: certType, name: name, recipient_type: recipientType, school_name: school, description: description });
                    renderQueue();

                    // Clear fields
                    $('#cert_type').val('').trigger('change');
                    $('#cert_name').val('');
                    $('#recipient_type').val('');
                    $('#cert_school').val('');
                    $('#cert_description').val('');
                    $('#cert_school').data('autofill', 0);
                    $('#cert_description').data('autofill', 0);
                });

                $('#clearQueueBtn').on('click', function() {
                    if (confirm('Clear all queued certificates?')) {
                        certQueue = [];
                        renderQueue();
                    }
                });

                $('#printAllBtn').on('click', function() {
                    if (certQueue.length === 0) { alert('No certificates in the queue.'); return; }
                    $('#queue_data_input').val(JSON.stringify(certQueue));
                    $('#batchPrintForm').submit();
                });

                $('#printWithoutBackgroundBtn').on('click', function() {
                    if (certQueue.length === 0) { alert('No certificates in the queue.'); return; }
                    $('#queue_data_plain_input').val(JSON.stringify(certQueue));
                    $('#batchPlainPrintForm').submit();
                });
            });

            function renderQueue() {
                var tbody = $('#queue_tbody');
                tbody.empty();
                $('#queue_count').text(certQueue.length);
                if (certQueue.length === 0) { $('#queue_box').hide(); return; }
                $('#queue_box').show();
                certQueue.forEach(function(item, idx) {
                    var typeLabel = certTypeLabels[item.cert_type] || item.cert_type;
                    var recipLabel = item.recipient_type ? (recipientTypeLabels[item.recipient_type] || item.recipient_type) : '—';
                    tbody.append(
                        '<tr>' +
                        '<td>' + (idx+1) + '</td>' +
                        '<td>' + typeLabel + '</td>' +
                        '<td>' + item.name + '</td>' +
                        '<td>' + recipLabel + '</td>' +
                        '<td>' + item.school_name + '</td>' +
                        '<td>' + (item.description || '—') + '</td>' +
                        '<td><button type="button" class="btn btn-xs btn-danger remove-item" data-idx="' + idx + '"><i class="fa fa-times"></i></button></td>' +
                        '</tr>'
                    );
                });
                tbody.find('.remove-item').on('click', function() {
                    certQueue.splice($(this).data('idx'), 1);
                    renderQueue();
                });
            }
          </script>
                    <style>
                        .admin-certificates-page #cert_type.cert-type-native {
                                position: relative;
                                z-index: 2;
                                background-image: none;
                        }
                        .admin-certificates-page #cert_name,
                        .admin-certificates-page #cert_school,
                        .admin-certificates-page #cert_description {
                                position: relative;
                                z-index: 1;
                        }
                    </style>
    </section>
  </div>
