<?php
use Cake\ORM\TableRegistry;
$this->Users 				= TableRegistry::getTableLocator()->get('Users');
$this->Judgeevaluations 	= TableRegistry::getTableLocator()->get('Judgeevaluations');
$this->Crstudentevents 		= TableRegistry::getTableLocator()->get('Crstudentevents');
$activeJudgeUserIds = isset($relatedJudgeUserIds) && is_array($relatedJudgeUserIds)
	? array_values(array_unique(array_filter(array_map('intval', $relatedJudgeUserIds))))
	: [(int)$this->request->getSession()->read('user_id')];
if (empty($activeJudgeUserIds)) {
	$activeJudgeUserIds = [(int)$this->request->getSession()->read('user_id')];
}
?>

<!-- Mark Command Performance Modal -->
<div id="commandModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:12px;padding:32px;width:360px;max-width:90%;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
      <h5 style="margin:0;font-size:1.1em;font-weight:600;">Mark Command Performance</h5>
      <span onclick="closeCommandModal()" style="cursor:pointer;font-size:1.4em;line-height:1;color:#888;">&times;</span>
    </div>
    <p style="margin-bottom:12px;font-size:0.95em;color:#444;">Please explain why you are marking this entry as command performance:</p>
    <textarea id="commandReason" placeholder="Enter reason..." style="width:100%;height:100px;padding:10px;border:1px solid #ccc;border-radius:6px;font-size:0.9em;resize:vertical;"></textarea>
    <div style="display:flex;justify-content:center;gap:10px;margin-top:16px;">
      <button onclick="closeCommandModal()" style="padding:8px 18px;border-radius:6px;border:1px solid #ccc;background:#333;color:#fff;cursor:pointer;">Cancel</button>
      <button onclick="submitCommand()" style="padding:8px 18px;border-radius:6px;border:none;background:#27ae60;color:#fff;cursor:pointer;">Submit Command</button>
    </div>
  </div>
</div>
<script>
var _commandUrl = '';
function openCommandModal(url) {
  _commandUrl = url;
  document.getElementById('commandReason').value = '';
  document.getElementById('commandModal').style.display = 'flex';
}
function closeCommandModal() {
  document.getElementById('commandModal').style.display = 'none';
}
function submitCommand() {
  var reason = document.getElementById('commandReason').value.trim();
  var form = document.createElement('form');
  form.method = 'POST';
  form.action = _commandUrl;
  var inp = document.createElement('input');
  inp.type = 'hidden'; inp.name = 'command_reason'; inp.value = reason;
  form.appendChild(inp);
  var tok = document.createElement('input');
  tok.type = 'hidden'; tok.name = '_method'; tok.value = 'POST';
  form.appendChild(tok);
  document.body.appendChild(form);
  form.submit();
}
</script>

<!-- Mark Guideline Breach Modal -->
<div id="breachModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:12px;padding:32px;width:360px;max-width:90%;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
      <h5 style="margin:0;font-size:1.1em;font-weight:600;">Mark Guideline Breach</h5>
      <span onclick="closeBreachModal()" style="cursor:pointer;font-size:1.4em;line-height:1;color:#888;">&times;</span>
    </div>
    <p style="margin-bottom:12px;font-size:0.95em;color:#444;">Please explain why you are marking this entry as a guideline breach:</p>
    <textarea id="breachReason" placeholder="Enter reason for breach..." style="width:100%;height:100px;padding:10px;border:1px solid #ccc;border-radius:6px;font-size:0.9em;resize:vertical;"></textarea>
    <div style="display:flex;justify-content:center;gap:10px;margin-top:16px;">
      <button onclick="closeBreachModal()" style="padding:8px 18px;border-radius:6px;border:1px solid #ccc;background:#333;color:#fff;cursor:pointer;">Cancel</button>
      <button onclick="submitBreach()" style="padding:8px 18px;border-radius:6px;border:none;background:#c0392b;color:#fff;cursor:pointer;">Submit Breach</button>
    </div>
  </div>
</div>
<script>
var _breachUrl = '';
function openBreachModal(url) {
  _breachUrl = url;
  document.getElementById('breachReason').value = '';
  document.getElementById('breachModal').style.display = 'flex';
}
function closeBreachModal() {
  document.getElementById('breachModal').style.display = 'none';
}
function submitBreach() {
  var reason = document.getElementById('breachReason').value.trim();
  var form = document.createElement('form');
  form.method = 'POST';
  form.action = _breachUrl;
  var inp = document.createElement('input');
  inp.type = 'hidden'; inp.name = 'breach_reason'; inp.value = reason;
  form.appendChild(inp);
  var tok = document.createElement('input');
  tok.type = 'hidden'; tok.name = '_method'; tok.value = 'POST';
  form.appendChild(tok);
  document.body.appendChild(form);
  form.submit();
}
</script>

<!-- Submitted File Preview Modal -->
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

<!-- Evaluation Preview Modal -->
<div id="evaluationPreviewModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.72);z-index:10010;align-items:center;justify-content:center;">
	<div style="background:#fff;border-radius:12px;width:95%;max-width:1320px;height:92vh;box-shadow:0 8px 32px rgba(0,0,0,0.35);display:flex;flex-direction:column;overflow:hidden;">
		<div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-bottom:1px solid #e3e6ea;">
			<strong id="evaluationPreviewTitle" style="font-size:14px;line-height:1.2;color:#1c2b3a;">Evaluation Form</strong>
			<div style="display:flex;align-items:center;gap:10px;">
				<a id="evaluationPreviewOpenNew" href="#" target="_blank" rel="noopener" style="font-size:12px;color:#1a5a96;text-decoration:underline;">Open in new tab</a>
				<button type="button" onclick="closeEvaluationPreviewModal()" style="border:none;background:#fff;color:#333;font-size:20px;line-height:1;cursor:pointer;">&times;</button>
			</div>
		</div>
		<iframe id="evaluationPreviewFrame" src="about:blank" style="width:100%;height:100%;border:0;background:#fff;" title="Evaluation Form Preview"></iframe>
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

	modal.style.display = 'flex';
	body.innerHTML = '<div style="height:100%;display:flex;align-items:center;justify-content:center;color:#46566b;font-size:14px;">Loading preview...</div>';

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

function closeEvaluationPreviewModal() {
	var modal = document.getElementById('evaluationPreviewModal');
	var frame = document.getElementById('evaluationPreviewFrame');
	if (frame) {
		frame.src = 'about:blank';
	}
	if (modal) {
		modal.style.display = 'none';
	}
}

function openEvaluationPreviewModal(evaluationUrl, evaluationTitle) {
	var modal = document.getElementById('evaluationPreviewModal');
	var frame = document.getElementById('evaluationPreviewFrame');
	var titleEl = document.getElementById('evaluationPreviewTitle');
	var openNew = document.getElementById('evaluationPreviewOpenNew');
	if (!modal || !frame || !titleEl || !openNew) {
		window.open(evaluationUrl, '_blank');
		return;
	}

	titleEl.textContent = evaluationTitle || 'Evaluation Form';
	openNew.href = evaluationUrl;
	frame.onload = function () {
		var framePath = '';
		try {
			framePath = (frame.contentWindow && frame.contentWindow.location && frame.contentWindow.location.pathname) ? frame.contentWindow.location.pathname : '';
		} catch (err) {
			framePath = '';
		}
		if (framePath.indexOf('/conventionregistrations/judgeevententries/') !== -1) {
			closeEvaluationPreviewModal();
			window.location.reload();
		}
	};
	frame.src = evaluationUrl;
	modal.style.display = 'flex';
}

document.addEventListener('click', function (event) {
	var link = event.target.closest('.js-file-preview-link');
	if (!link) {
		if (event.target.id === 'filePreviewModal') {
			closeFilePreviewModal();
		}
		return;
	}
	event.preventDefault();
	openFilePreviewModal(link.getAttribute('data-file-url'), link.getAttribute('data-file-title'), link.getAttribute('data-file-name'));
});

document.addEventListener('click', function (event) {
	var evaluateLink = event.target.closest('.js-evaluate-modal-trigger');
	if (!evaluateLink) {
		if (event.target.id === 'evaluationPreviewModal') {
			closeEvaluationPreviewModal();
		}
		return;
	}

	event.preventDefault();
	var confirmMessage = evaluateLink.getAttribute('data-confirm-message');
	if (confirmMessage && !window.confirm(confirmMessage)) {
		return;
	}
	openEvaluationPreviewModal(
		evaluateLink.getAttribute('data-evaluate-url') || evaluateLink.getAttribute('href'),
		evaluateLink.getAttribute('data-evaluate-title') || 'Evaluation Form'
	);
});
</script>

<div class="admin_loader" id="loaderID"><?php echo $this->Html->image('loader_large_blue.gif');?></div>
<?php
$rowsToRender = isset($eventsubmissionRows) && is_array($eventsubmissionRows)
	? $eventsubmissionRows
	: (isset($eventsubmissions) ? $eventsubmissions->toList() : []);
?>
<?php if (!empty($rowsToRender)) { ?>
    <div style="padding:0;">
		<?php
		$formOptions = ['id' => 'actionFrom', 'method' => 'Post'];
		if (!empty($isAutoAwardEvent) || !empty($isSilverAppleEvent)) {
			$formOptions['url'] = ['controller' => 'conventionregistrations', 'action' => 'saveautoawardevaluations', $conv_reg_slug, $eventD->slug];
		} elseif (!empty($isFourPlaceSportsEvent)) {
			$formOptions['url'] = ['controller' => 'conventionregistrations', 'action' => 'savebibleplaces', $conv_reg_slug, $eventD->slug];
		} elseif (!empty($isBibleBowlOpenScoreEvent)) {
			$formOptions['url'] = ['controller' => 'conventionregistrations', 'action' => 'savebiblebowlscores', $conv_reg_slug, $eventD->slug];
		}
		echo $this->Form->create(null, $formOptions);
		?>
        <section id="no-more-tables" class="lstng-section">
             

            <div class="tbl-resp-listing" style="border:0;border-radius:0;">
                <table id="group_events_table" class="table table-striped table-bordered" style="width:100%">
					<thead>
						<tr>
							<th class="sorting_paging">
							<?php
							if($eventD->group_event_yes_no == 1)
							{
								echo 'Group';
							}
							else
							{
								echo "Student Name";
							}
							?>
							</th>
							<th class="sorting_paging">School</th>
							<th class="sorting_paging">Context</th>
							<th class="sorting_paging">Submitted File</th>
							<th class="sorting_paging">Submission Date</th>
					<th class="sorting_paging">Breach</th>
					<th class="sorting_paging">Command</th>
					<th class="sorting_paging">Done</th>
					<th class="sorting_paging">Total Score</th>
					<?php if(!empty($isBiblePlacingEvent) || !empty($isSilverAppleEvent)) { ?>
					<th class="sorting_paging">Place</th>
					<?php } ?>
					<th class="sorting_paging">Action</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($rowsToRender as $datarecord) { ?>
						<tr>
						<td data-title="Student Name / Group">
						
						<?php
						if($eventD->group_event_yes_no == 1)
						{
							echo $datarecord->group_name.'<br>';
							
							// now fetch all name of students of this group
							$condAllUGroup = array();
							$condAllUGroup[] = "(Crstudentevents.conventionregistration_id = '".$datarecord->conventionregistration_id."' AND Crstudentevents.event_id = '".$datarecord->event_id."' AND Crstudentevents.group_name = '".$datarecord->group_name."')";
							
							$listAllUGroup = $this->Crstudentevents->find()->where($condAllUGroup)->contain(["Students"])->limit(4)->all();
							
							$arrNG = array();
							foreach($listAllUGroup as $datamembgroup)
							{
								$nameV =  $datamembgroup->Students['first_name'].' '.$datamembgroup->Students['middle_name'].' '.$datamembgroup->Students['last_name'];
								$arrNG[] = $nameV;
							}
							
							echo '('.implode(", ",$arrNG).')';
							
						?>
							<a title="View all members of group" href="#" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#exampleModal_<?php echo $datarecord->id; ?>" style="width:45px;">
								  <i class="fa fa-eye"></i>
							</a>
								
							<div class="modal fade" id="exampleModal_<?php echo $datarecord->id; ?>" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
							  <div class="modal-dialog">
								<div class="modal-content">
								
								  <div class="modal-header">
									<h5 class="modal-title" id="exampleModalLabel">Group <?php echo $datarecord->group_name; ?></h5>
									<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
								  </div>
								  
								  <div class="modal-body">
									
									<table class="table">
										<thead>
											<tr>
												<th scope="col">#</th>
												<th scope="col">Name</th>
											</tr>
										</thead>
										<tbody>
											<?php
											// now fetch all students of this group
											$listAllUGroup = $this->Crstudentevents->find()->where($condAllUGroup)->contain(["Students"])->all();
											$cntrMM = 1;
											foreach($listAllUGroup as $datamembgroup)
											{
											?>
											<tr>
												<td scope="row"><?php echo $cntrMM; ?></td>
												<td colspan="2"><?php echo $datamembgroup->Students['first_name'].' '.$datamembgroup->Students['middle_name'].' '.$datamembgroup->Students['last_name']; ?></td>
											</tr>
											<?php
											$cntrMM++;
											}
											?>
										</tbody>
									</table>
									
								  </div>
								
								</div>
							  </div>
							</div>
							
						<?php
						}
						else
						{
							echo $datarecord->Students['first_name'].' '.$datarecord->Students['middle_name'].' '.$datarecord->Students['last_name'];
						}
						?>
						</td>
						<td data-title="School">
						<?php
						$schoolLabel = $combinedSchoolLabels[(int)$datarecord->id] ?? $datarecord->Users['first_name'];
						if ((int)$eventD->group_event_yes_no === 1 && isset($listAllUGroup)) {
							$groupSchoolIds = [];
							foreach ($listAllUGroup as $groupMember) {
								$schoolId = (int)($groupMember->Students['school_id'] ?? 0);
								if ($schoolId > 0) {
									$groupSchoolIds[] = $schoolId;
								}
							}
							$groupSchoolIds = array_values(array_unique($groupSchoolIds));
							if (count($groupSchoolIds) > 1) {
								$groupSchoolNames = $this->Users->find()
									->select(['first_name'])
									->where(['Users.id IN' => $groupSchoolIds])
									->extract('first_name')
									->toList();
								$groupSchoolNames = array_values(array_unique(array_filter(array_map('trim', $groupSchoolNames))));
								if (count($groupSchoolNames) > 1) {
									$schoolLabel = implode(' + ', $groupSchoolNames);
								}
							}
						}
						echo h($schoolLabel);
						?>
						</td>
						<td data-title="Context"><?php echo ($datarecord->context_box) ? $datarecord->context_box : "N/A"; ?></td>
						<td data-title="Submitted File">
						<?php
							$imgToShow = $datarecord->mediafile_file_system_name;
							if(file_exists(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$imgToShow) && !empty($imgToShow))
							{
								$previewUrl = DISPLAY_EVENTS_SUBMISSION_DOCUMENT_PATH.$imgToShow;
								$mediaExtensions = ['mp3', 'm4a', 'aac', 'wav', 'ogg', 'oga', 'opus', 'flac', 'wma', 'mp4', 'm4v', 'mov', 'webm', 'ogv', 'avi', 'wmv', 'mpeg', 'mpg'];
								if (in_array(strtolower(pathinfo($imgToShow, PATHINFO_EXTENSION)), $mediaExtensions, true)) {
									$previewUrl = $this->Url->build('/media_stream.php').'?file='.rawurlencode($imgToShow);
								}
								echo '<a href="#" class="js-file-preview-link" style="color:#0f172a !important;font-weight:700;font-size:12px;text-decoration:none;" data-file-url="'.h($previewUrl).'" data-file-name="'.h($imgToShow).'" data-file-title="'.h((string)$eventD->upload_type).'" title="'.h((string)$eventD->upload_type).'"><i class="fa fa-cloud-download"></i> '.h((string)$eventD->upload_type).'</a>';
							}
						?>
						
						<?php
							$imgToShow = $datarecord->report;
							if(file_exists(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$imgToShow) && !empty($imgToShow))
							{
								$previewUrl = DISPLAY_EVENTS_SUBMISSION_DOCUMENT_PATH.$imgToShow;
								echo '<br /><a style="color:#0f172a !important;font-weight:700;font-size:12px;text-decoration:none;" href="#" class="js-file-preview-link" data-file-url="'.h($previewUrl).'" data-file-name="'.h($imgToShow).'" data-file-title="Report" title="Report"><i class="fa fa-cloud-download"></i> Report</a>';
							}
						?>
						
						<?php
							$imgToShow = $datarecord->score_sheet;
							if(file_exists(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$imgToShow) && !empty($imgToShow))
							{
								$previewUrl = DISPLAY_EVENTS_SUBMISSION_DOCUMENT_PATH.$imgToShow;
								echo '<br /><a style="color:#0f172a !important;font-weight:700;font-size:12px;text-decoration:none;" href="#" class="js-file-preview-link" data-file-url="'.h($previewUrl).'" data-file-name="'.h($imgToShow).'" data-file-title="Score Sheet" title="Score Sheet"><i class="fa fa-cloud-download"></i> Score Sheet</a>';
							}
						?>
						
						<?php
							$imgToShow = $datarecord->additional_documents;
							if(file_exists(UPLOAD_EVENTS_SUBMISSION_DOCUMENT_PATH.$imgToShow) && !empty($imgToShow))
							{
								$previewUrl = DISPLAY_EVENTS_SUBMISSION_DOCUMENT_PATH.$imgToShow;
								echo '<br /><a style="color:#0f172a !important;font-weight:700;font-size:12px;text-decoration:none;" href="#" class="js-file-preview-link" data-file-url="'.h($previewUrl).'" data-file-name="'.h($imgToShow).'" data-file-title="Additional Documents" title="Additional Documents"><i class="fa fa-cloud-download"></i> Additional Documents</a>';
							}
						?>
						</td>
						<td data-title="Submission Date">
							<?php
							$submissionDate = 'N/A';
							$timestamp = false;

							if (!empty($datarecord->created)) {
								$createdValue = $datarecord->created;
								if ($createdValue instanceof \DateTimeInterface) {
									$timestamp = $createdValue->getTimestamp();
								} elseif (is_numeric($createdValue)) {
									$timestamp = (int)$createdValue;
								} else {
									$timestamp = strtotime((string)$createdValue);
								}
							}

							if (($timestamp === false || $timestamp <= 0) && !empty($datarecord->modified)) {
								$modifiedValue = $datarecord->modified;
								if ($modifiedValue instanceof \DateTimeInterface) {
									$timestamp = $modifiedValue->getTimestamp();
								} elseif (is_numeric($modifiedValue)) {
									$timestamp = (int)$modifiedValue;
								} else {
									$timestamp = strtotime((string)$modifiedValue);
								}
							}

							if (($timestamp === false || $timestamp <= 0) && !empty($datarecord->slug)) {
								$slugParts = explode('-', (string)$datarecord->slug);
								if (count($slugParts) >= 4 && ctype_digit($slugParts[3])) {
									$timestamp = (int)$slugParts[3];
								}
							}

							if ($timestamp !== false && $timestamp > 0) {
								$submissionDate = date('M d, Y', $timestamp);
							}

							echo $submissionDate;
							?>
						</td>
						
<td data-title="Breach">
					<?php
					if($datarecord->guideline_breach == 0)
					{
						$breachUrl = $this->Url->build(['controller' => 'judgeevaluations', 'action' => 'markbreach', $datarecord->slug]);
						echo '<button type="button" onclick="openBreachModal(\'' . $breachUrl . '\')" class="btn btn-sm btn-outline-danger" style="font-size:11px;padding:4px 8px;">Mark Breach</button>';
					}
					else if($datarecord->guideline_breach == 1)
					{
						echo '<span style="font-size:11px;color:#9c6a16;">Awaiting admin</span>';
					}
					else if($datarecord->guideline_breach == 2)
					{
						echo '<span style="font-size:11px;color:#1f7a45;">Approved</span>';
					}
					?>
				</td>
				
				<td data-title="Command">
					<?php
					if($datarecord->guideline_breach == 0)
					{
						if($datarecord->command_performance == 1)
						{
							echo '<span style="font-size:11px;color:#1f7a45;font-weight:600;">Yes</span>';
						}
						else
						{
							$commandUrl = $this->Url->build(['controller' => 'judgeevaluations', 'action' => 'markcommand', $datarecord->slug]);
							echo '<button type="button" onclick="openCommandModal(\'' . $commandUrl . '\')" class="btn btn-sm btn-outline-success" style="font-size:11px;padding:4px 8px;">Mark Command</button>';
						}
					}
					?>
				</td>
				
				<td data-title="Done" style="text-align:center;">
					<?php
					$condCheckJudging = array();
					$condCheckJudging[] = "(Judgeevaluations.eventsubmission_id = '".$datarecord->id."')";
					$condCheckJudging['Judgeevaluations.uploaded_by_user_id IN'] = $activeJudgeUserIds;
					$getScores = $this->Judgeevaluations->find()
						->where($condCheckJudging)
						->order(['Judgeevaluations.id' => 'DESC'])
						->first();
					if($getScores)
					{
						echo '<span class="jee-judged-chip" title="Judged by you"><i class="fa fa-check"></i> Done</span>';
					}
					else { echo '<span style="color:#aaa;font-size:12px;">—</span>'; }
					?>
				</td>
				
				<td data-title="Total Score" style="text-align:center;">
				<?php
				$saveScoreUrl = $this->Url->build(['controller' => 'conventionregistrations', 'action' => 'savejudgetotalscore', $conv_reg_slug, $datarecord->slug]);
				$totalScore = '';

				if($getScores)
				{
					if (isset($getScores->total_marks_obtained) && $getScores->total_marks_obtained !== '' && $getScores->total_marks_obtained !== null)
					{
						$totalScore = $getScores->total_marks_obtained;
					}
					else if (isset($getScores->all_pos_score) && $getScores->all_pos_score !== '' && $getScores->all_pos_score !== null)
					{
						$totalScore = $getScores->all_pos_score;
					}
					else if (isset($getScores->spelling_score) && $getScores->spelling_score !== '' && $getScores->spelling_score !== null)
					{
						$totalScore = $getScores->spelling_score;
					}
					else if (isset($getScores->soccer_kick_best_kick) && $getScores->soccer_kick_best_kick !== '' && $getScores->soccer_kick_best_kick !== null)
					{
						$totalScore = $getScores->soccer_kick_best_kick;
					}
				}

				if (!empty($isSilverAppleEvent))
				{
					$award = $silverAppleAwardsBySubmissionId[(int)$datarecord->id] ?? null;
					echo $award !== null ? (int)$award['points'] : '<span style="color:#aaa;">—</span>';
				}
				else if (!empty($isAutoAwardEvent))
				{
					echo '12';
				}
				else if (!empty($isBibleBowlOpenScoreEvent))
				{
					if ($getScores && (int)$getScores->did_not_attend === 1)
					{
						echo '<span style="font-size:11px;color:#999;">Did not attend</span>';
					}
					else
					{
						echo '<input type="number" step="0.01" min="0" name="scores['.(int)$datarecord->id.']" value="'.h((string)$totalScore).'" style="width:88px;padding:4px 6px;border:1px solid #ccc;border-radius:6px;font-size:12px;" />';
					}
				}
				else if (!empty($isFourPlaceSportsEvent))
				{
					echo $totalScore !== '' ? h((string)$totalScore) : '<span style="color:#aaa;">—</span>';
				}
				else if(!$getScores)
				{
					echo '<form method="post" action="'.$saveScoreUrl.'" class="acp-offline-update-form" data-update-type="total_score" data-submission-slug="'.h($datarecord->slug).'" style="display:flex;gap:6px;align-items:center;justify-content:center;margin:0;">';
					echo '<input type="number" step="0.01" min="0" name="total_score" value="" style="width:72px;padding:4px 6px;border:1px solid #ccc;border-radius:6px;font-size:12px;" />';
					echo '<button type="submit" class="btn btn-sm btn-primary" style="font-size:11px;padding:4px 8px;">Save</button>';
					echo '</form>';
				}
				else if($getScores->did_not_attend == 0)
				{
					echo '<form method="post" action="'.$saveScoreUrl.'" class="acp-offline-update-form" data-update-type="total_score" data-submission-slug="'.h($datarecord->slug).'" style="display:flex;gap:6px;align-items:center;justify-content:center;margin:0;">';
					echo '<input type="number" step="0.01" min="0" name="total_score" value="'.h((string)$totalScore).'" style="width:72px;padding:4px 6px;border:1px solid #ccc;border-radius:6px;font-size:12px;" />';
					echo '<button type="submit" class="btn btn-sm btn-primary" style="font-size:11px;padding:4px 8px;">Save</button>';
					echo '</form>';
				}
				else
				{
					echo '<span style="font-size:11px;color:#999;">Did not attend</span>';
						}
						?>
						</td>
						
						<?php if(!empty($isBiblePlacingEvent) || !empty($isSilverAppleEvent)) {
							$currentPlace = isset($existingPlaces[$datarecord->id]) ? $existingPlaces[$datarecord->id] : '';
						?>
						<td data-title="Place">
							<?php if (!empty($isSilverAppleEvent)) {
								$award = $silverAppleAwardsBySubmissionId[(int)$datarecord->id] ?? null;
								echo $award !== null ? (int)$award['place'] : '<span style="color:#aaa;">—</span>';
							} elseif (!empty($isFourPlaceSportsEvent)) { ?>
							<input type="number" name="places[<?php echo (int)$datarecord->id; ?>]" min="1" max="4" value="<?php echo h($currentPlace); ?>" placeholder="Place" style="width:64px;padding:4px 6px;border:1px solid #ccc;border-radius:6px;font-size:12px;" />
							<?php } else {
								$placeUrl = $this->Url->build(['controller' => 'conventionregistrations', 'action' => 'savebibleplace', $conv_reg_slug, $datarecord->slug]);
							?>
							<form method="post" action="<?php echo $placeUrl; ?>" class="acp-offline-update-form" data-update-type="place" data-submission-slug="<?php echo h($datarecord->slug); ?>" style="display:flex;gap:6px;align-items:center;margin:0;">
								<input type="number" name="place" min="1" value="<?php echo h($currentPlace); ?>" placeholder="Place" style="width:64px;padding:4px 6px;border:1px solid #ccc;border-radius:6px;font-size:12px;" />
								<button type="submit" class="btn btn-sm btn-primary" style="font-size:11px;padding:4px 9px;">Save</button>
							</form>
							<?php } ?>
						</td>
						<?php } ?>
						
						<td data-title="Action">
						<div class="jee-action-group" data-submission-slug="<?php echo h($datarecord->slug); ?>">
						<?php
						if(empty($hasEvaluationForm))
						{
							echo '<span style="font-size:11px;color:#aaa;">N/A</span>';
						}
						else if($datarecord->guideline_breach != 2)
						{
							echo $this->Html->link('<i class="fa fa-pencil-square-o"></i> Evaluate', ['controller' => 'judgeevaluations', 'action' => 'addnew',$conv_reg_slug,$datarecord->slug], [ 'escape' => false, 'title' => 'Submit Evaluation', 'class'=>'btn btn-sm btn-primary js-evaluate-modal-trigger', 'style' => 'font-size:11px;padding:4px 9px;', 'data-confirm-message' => 'Are you sure you want to submit evaluation for this entry?', 'data-evaluate-url' => $this->Url->build(['controller' => 'judgeevaluations', 'action' => 'addnew',$conv_reg_slug,$datarecord->slug, '?' => ['popup' => 1]]), 'data-evaluate-title' => 'Evaluation Form']);
						}
						echo $this->Html->link('<i class="fa fa-times-circle-o"></i>', ['controller' => 'judgeevaluations', 'action' => 'markdidnotattend',$conv_reg_slug,$datarecord->slug], [ 'escape' => false, 'title' => 'Mark As Did Not Attend', 'class'=>'btn btn-sm btn-outline-secondary', 'style' => 'font-size:11px;padding:4px 8px;display:inline-flex;align-items:center;justify-content:center;width:auto;background:transparent;color:#6c757d;border:1px solid #6c757d;', 'confirm' => 'Are you sure you want to mark this submission as did not attend?']);
						?>
						</div>
						</td>
					</tr>
						<?php } ?>
					</tbody>
				</table>
            </div>
			<?php if (!empty($isBibleBowlOpenScoreEvent)) { ?>
			<div style="display:flex;justify-content:flex-end;padding:12px 0;">
				<button type="submit" class="btn btn-primary">Save All Scores</button>
			</div>
			<?php } else if (!empty($isFourPlaceSportsEvent)) { ?>
			<div style="display:flex;justify-content:flex-end;padding:12px 0;">
				<button type="submit" class="btn btn-primary">Save All Places</button>
			</div>
			<?php } else if (!empty($isAutoAwardEvent) || !empty($isSilverAppleEvent)) { ?>
			<div style="display:flex;justify-content:flex-end;padding:12px 0;">
				<button type="submit" class="btn btn-primary">Save All Evaluations</button>
			</div>
			<?php } ?>
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
	order: [[0, 'asc']],
    //"bFilter": true,
    //"bInfo": false,
    //"bAutoWidth": false
	});
	/* $('#searchInput').on('keyup', function() {
        $('#group_events_table').dataTable.search(this.value).draw();
    }); */
});
</script>

<script>
/* Phase 3d: Show per-submission pending-sync badge from IndexedDB */
(function () {
  if (!window.indexedDB) return;

  var openReq = indexedDB.open('acp_offline', 1);
  openReq.onsuccess = function (ev) {
    var db = ev.target.result;
    if (!db.objectStoreNames.contains('pending_evaluations')) { db.close(); return; }

    var tx = db.transaction('pending_evaluations', 'readonly');
    var all = [];
    tx.objectStore('pending_evaluations').openCursor().onsuccess = function (e) {
      var cursor = e.target.result;
      if (cursor) { all.push(cursor.value); cursor.continue(); }
      else {
        db.close();
        if (all.length === 0) return;

        all.forEach(function (evalRecord) {
          /* Match the submission slug from the stored URL */
          var urlMatch = (evalRecord.url || '').match(/\/addnew\/[^/]+\/([^/?#]+)/);
          if (!urlMatch) return;
          var slug = urlMatch[1];

          /* Find the action cell with that slug and inject the badge */
          var actionGroups = document.querySelectorAll('.jee-action-group[data-submission-slug="' + slug + '"]');
          actionGroups.forEach(function (group) {
            if (group.querySelector('.acp-pending-badge')) return; /* already added */
            var badge = document.createElement('span');
            badge.className = 'acp-pending-badge';
            badge.title = 'This evaluation is queued and will sync when internet is restored';
            badge.style.cssText = [
              'display:inline-flex', 'align-items:center', 'gap:4px',
              'background:#fff5db', 'color:#8a6400',
              'border:1px solid #e5c847', 'border-radius:999px',
              'padding:3px 9px', 'font-size:11px', 'font-weight:700',
              'margin-left:6px', 'vertical-align:middle', 'cursor:default'
            ].join(';');
            badge.innerHTML = '&#8987; Pending sync';
            group.appendChild(badge);
          });
        });
      }
    };
  };
})();
</script>

<script>
/* Phase 3e: Offline queue for score/place updates on event entries */
(function () {
	if (!window.indexedDB) return;

	var DB_NAME = 'acp_offline';
	var DB_VERSION = 2;
	var STORE_NAME = 'pending_score_updates';

	function openDb(callback) {
		var req = indexedDB.open(DB_NAME, DB_VERSION);
		req.onupgradeneeded = function (ev) {
			var db = ev.target.result;
			if (!db.objectStoreNames.contains('pending_evaluations')) {
				var evalStore = db.createObjectStore('pending_evaluations', { keyPath: 'id', autoIncrement: true });
				evalStore.createIndex('queuedAt', 'queuedAt', { unique: false });
			}
			if (!db.objectStoreNames.contains('judge_cache')) {
				db.createObjectStore('judge_cache', { keyPath: 'key' });
			}
			if (!db.objectStoreNames.contains(STORE_NAME)) {
				var updateStore = db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
				updateStore.createIndex('queuedAt', 'queuedAt', { unique: false });
			}
		};
		req.onsuccess = function (ev) { callback(null, ev.target.result); };
		req.onerror = function () { callback(new Error('Failed to open IndexedDB')); };
	}

	function queueUpdate(form) {
		var formData = new FormData(form);
		var payload = {};
		formData.forEach(function (value, key) {
			payload[key] = value;
		});

		var submitBtn = form.querySelector('button[type="submit"]');
		var originalLabel = submitBtn ? submitBtn.textContent : '';
		if (submitBtn) {
			submitBtn.disabled = true;
			submitBtn.textContent = 'Queued';
		}

		openDb(function (err, db) {
			if (err || !db.objectStoreNames.contains(STORE_NAME)) {
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.textContent = originalLabel;
				}
				alert('Offline queue unavailable. Please reconnect and try again.');
				return;
			}

			var tx = db.transaction(STORE_NAME, 'readwrite');
			tx.objectStore(STORE_NAME).add({
				url: form.getAttribute('action') || window.location.href,
				method: (form.getAttribute('method') || 'POST').toUpperCase(),
				payload: payload,
				updateType: form.getAttribute('data-update-type') || 'unknown',
				submissionSlug: form.getAttribute('data-submission-slug') || '',
				queuedAt: new Date().toISOString()
			});

			tx.oncomplete = function () {
				db.close();
				addPendingBadge(form.getAttribute('data-submission-slug') || '');
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.textContent = originalLabel;
				}
				notify('Saved offline. This update will sync when internet is restored.');
			};

			tx.onerror = function () {
				db.close();
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.textContent = originalLabel;
				}
				alert('Could not queue offline update. Please reconnect and try again.');
			};
		});
	}

	function flushQueuedUpdates() {
		if (!navigator.onLine) return;

		openDb(function (err, db) {
			if (err || !db.objectStoreNames.contains(STORE_NAME)) return;

			var tx = db.transaction(STORE_NAME, 'readonly');
			var getAllReq = tx.objectStore(STORE_NAME).getAll();
			getAllReq.onsuccess = function () {
				var queued = getAllReq.result || [];
				db.close();
				if (queued.length === 0) return;

				var synced = 0;
				var failed = 0;

				var chain = Promise.resolve();
				queued.forEach(function (item) {
					chain = chain.then(function () {
						var body = new URLSearchParams();
						Object.keys(item.payload || {}).forEach(function (key) {
							body.append(key, item.payload[key]);
						});

						return fetch(item.url, {
							method: item.method || 'POST',
							credentials: 'same-origin',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
								'X-Requested-With': 'XMLHttpRequest'
							},
							body: body.toString()
						}).then(function (resp) {
							if (!resp.ok) throw new Error('HTTP ' + resp.status);
							synced++;
							removeQueuedUpdate(item.id, item.submissionSlug);
						}).catch(function () {
							failed++;
						});
					});
				});

				chain.then(function () {
					if (synced > 0) notify(synced + ' queued update' + (synced > 1 ? 's' : '') + ' synced.', true);
					if (failed > 0) notify(failed + ' queued update' + (failed > 1 ? 's' : '') + ' failed and will retry.');
				});
			};
		});
	}

	function removeQueuedUpdate(id, slug) {
		openDb(function (err, db) {
			if (err || !db.objectStoreNames.contains(STORE_NAME)) return;
			var tx = db.transaction(STORE_NAME, 'readwrite');
			tx.objectStore(STORE_NAME).delete(id);
			tx.oncomplete = function () {
				db.close();
				refreshPendingBadge(slug || '');
			};
		});
	}

	function refreshPendingBadge(slug) {
		if (!slug) return;
		openDb(function (err, db) {
			if (err || !db.objectStoreNames.contains(STORE_NAME)) return;
			var tx = db.transaction(STORE_NAME, 'readonly');
			var req = tx.objectStore(STORE_NAME).getAll();
			req.onsuccess = function () {
				var list = req.result || [];
				var hasPending = list.some(function (entry) { return entry.submissionSlug === slug; });
				db.close();
				if (!hasPending) {
					var group = document.querySelector('.jee-action-group[data-submission-slug="' + slug + '"]');
					if (!group) return;
					var badge = group.querySelector('.acp-score-pending-badge');
					if (badge) badge.remove();
				}
			};
		});
	}

	function addPendingBadge(slug) {
		if (!slug) return;
		var group = document.querySelector('.jee-action-group[data-submission-slug="' + slug + '"]');
		if (!group) return;
		if (group.querySelector('.acp-score-pending-badge')) return;

		var badge = document.createElement('span');
		badge.className = 'acp-score-pending-badge';
		badge.title = 'Score/place update queued offline';
		badge.style.cssText = [
			'display:inline-flex', 'align-items:center', 'gap:4px',
			'background:#e7f2ff', 'color:#1a4f8a',
			'border:1px solid #97bdf0', 'border-radius:999px',
			'padding:3px 9px', 'font-size:11px', 'font-weight:700',
			'margin-left:6px', 'vertical-align:middle', 'cursor:default'
		].join(';');
		badge.innerHTML = '&#8987; Score pending';
		group.appendChild(badge);
	}

	function hydratePendingBadges() {
		openDb(function (err, db) {
			if (err || !db.objectStoreNames.contains(STORE_NAME)) return;
			var tx = db.transaction(STORE_NAME, 'readonly');
			var req = tx.objectStore(STORE_NAME).getAll();
			req.onsuccess = function () {
				var list = req.result || [];
				db.close();
				list.forEach(function (entry) {
					if (entry.submissionSlug) addPendingBadge(entry.submissionSlug);
				});
			};
		});
	}

	function notify(msg, success) {
		if (typeof window.jQuery !== 'undefined' && window.jQuery.fn && window.jQuery.fn.toast) {
			// No bootstrap toasts here; fallback to alert-like small hint.
		}
		var el = document.createElement('div');
		el.style.cssText = [
			'position:fixed', 'bottom:18px', 'left:50%', 'transform:translateX(-50%)',
			'background:' + (success ? '#2a7a4f' : '#1c2452'), 'color:#fff',
			'padding:10px 16px', 'border-radius:9px', 'z-index:99999',
			'font-size:13px', 'font-weight:600', 'box-shadow:0 5px 16px rgba(0,0,0,0.2)'
		].join(';');
		el.textContent = msg;
		document.body.appendChild(el);
		setTimeout(function () {
			el.style.opacity = '0';
			el.style.transition = 'opacity .35s ease';
			setTimeout(function () { el.remove(); }, 360);
		}, 2600);
	}

	document.querySelectorAll('form.acp-offline-update-form').forEach(function (form) {
		form.addEventListener('submit', function (e) {
			if (navigator.onLine) return;
			e.preventDefault();
			queueUpdate(form);
		});
	});

	window.addEventListener('online', flushQueuedUpdates);
	hydratePendingBadges();
	if (navigator.onLine) flushQueuedUpdates();
})();
</script>

<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
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
</style>

<style>
.moretext {display: none;}
</style>
