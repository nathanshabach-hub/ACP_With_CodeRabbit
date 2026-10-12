<style>
	html, body {
		background: #fff !important;
	}
	@page {
		background: #fff;
	}
	@media print {
		html, body {
			background: #fff !important;
		}
		.page-break-after {
			page-break-after: always;
		}
	}
	.topn { display: none; }
	.content-wrapper { margin: 0 !important; padding: 0 !important; min-height: 0 !important; background: #fff !important; }
	.content { padding: 0 !important; margin: 0 !important; background: #fff !important; }
	.sd-all-wrap { padding: 0; }
	.sd-all-event { background: #fff !important; }
	.sd-all-event + .sd-all-event { margin-top: 0; }
	@media print {
		.sd-all-event { break-after: page; page-break-after: always; }
		.sd-all-event:last-child { break-after: auto; page-break-after: auto; }
	}
</style>
<script type="text/javascript">
window.addEventListener('load', function() {
	window.print();
});
</script>

<?php
$drawPackages = isset($drawPackages) && is_array($drawPackages) ? $drawPackages : [];
?>

<div class="content-wrapper">
	<section class="content">
		<div class="sd-all-wrap" id="listID">
			<?php if (empty($drawPackages)) { ?>
				<div style="padding:16px; color:#666;">No sports or elimination draw data found for this season.</div>
			<?php } else { ?>
				<?php foreach ($drawPackages as $package) { ?>
					<div class="sd-all-event">
						<?php
						$eventD = $package['eventD'];
						$bracketData = $package['bracketData'];
						$schedCat = $package['schedCat'];
						$teamNameMap = $package['teamNameMap'];
						echo $this->element('Admin/Schedulingreports/sportsdraw_bracket', [
							'eventD' => $eventD,
							'conventionSD' => $conventionSD,
							'bracketData' => $bracketData,
							'schedCat' => $schedCat,
							'teamNameMap' => $teamNameMap,
						]);
						?>
					</div>
				<?php } ?>
			<?php } ?>
		</div>
	</section>
</div>
