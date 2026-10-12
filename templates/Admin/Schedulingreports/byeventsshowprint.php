<style>
  @media print {
    .page-break-after {
      page-break-after: always;
    }
  }
  .topn {display:none;}
</style>
<script type="text/javascript">
<!--
window.print();
//-->
</script>

<div class="m_content" id="listID">
  <?php if (!empty($eventStudentGroups)) {
    echo $this->element('Admin/Schedulingreports/legacy_contestant_sheet', [
      'conventionSD' => $conventionSD,
      'eventD' => $eventD,
      'convSeasonEventD' => $convSeasonEventD ?? null,
      'eventStudentGroups' => $eventStudentGroups,
    ]);
  } else {
    echo '<h2 style="padding:20px 0px 20px 5px;">'.h($eventD->event_name).' ('.h($eventD->event_id_number).')</h2>';
    echo $this->element("Admin/Schedulingreports/byeventsshow");
  } ?>
</div>