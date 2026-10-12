<style>
@media print {
	.page-break-after {
		page-break-after: always;
	}
}
.topn { display: none; }
.ath-all-empty { padding: 20px; font-size: 14px; color: #555; }
.ath-all-event { margin-bottom: 8px; }
@media print {
	.ath-all-event {
		break-after: page;
		page-break-after: always;
	}
	.ath-all-event:last-child {
		break-after: auto;
		page-break-after: auto;
	}
}
</style>
<script type="text/javascript">
window.print();
</script>

<div class="m_content" id="listID">
	<?php if (!empty($athleticsPackages)) { ?>
		<?php foreach ($athleticsPackages as $athleticsPackage) { ?>
			<div class="ath-all-event">
				<?php
				echo $this->element('Admin/Schedulingreports/legacy_contestant_sheet', [
					'conventionSD' => $conventionSD,
					'eventD' => $athleticsPackage['eventD'],
					'convSeasonEventD' => $athleticsPackage['convSeasonEventD'],
					'eventStudentGroups' => $athleticsPackage['eventStudentGroups'],
				]);
				?>
			</div>
		<?php } ?>
	<?php } else { ?>
		<div class="ath-all-empty">No athletics or sports events with participants were found for this season.</div>
	<?php } ?>
</div>
