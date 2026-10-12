<!DOCTYPE html>
<html>
<head>
    <title>Nametags - Visitors</title>
    <style>
        * {
            box-sizing: border-box;
        }
        <?php
        $webroot = (string)($this->request->getAttribute('webroot') ?? '/');
        ?>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #fff;
        }
        .print-page {
            width: 190mm;
            min-height: 281mm;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(2, 90mm);
            grid-template-rows: repeat(5, 56.2mm);
            column-gap: 0;
            row-gap: 0;
            justify-content: center;
            align-content: start;
        }
        .print-page:last-child {
            min-height: 0;
        }
        .name-card {
            border: 1px solid #ccc;
            text-align: center;
            padding: 4mm 3mm;
            position: relative;
            width: 90mm;
            height: 56.2mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-image: url('<?php echo h($webroot); ?>img/front/nametag_bg.png');
            background-size: 90mm 56.2mm;
            background-repeat: no-repeat;
            background-position: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .name-card h2 {
            font-weight: bold;
            color: #1f4fbf;
            margin: -10mm 0 4mm;
            font-size: 22px;
            line-height: 1.2;
        }
        .name-card p {
            margin: 0 0 2mm;
            color: #1b1464;
            font-size: 14px;
            font-style: italic;
            line-height: 1.25;
        }
        .name-card.blank {
            background: #fff;
        }
        @page {
            size: A4 portrait;
            margin: 8mm 5mm 8mm 5mm;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .print-page {
                page-break-after: always;
                break-after: page;
            }
            .print-page:last-child {
                page-break-after: auto;
                break-after: auto;
            }
        }
    </style>
</head>
<body onload="window.print()">
<?php
$nametagsArray = is_array($nametags) ? $nametags : $nametags->toArray();
$pages = array_chunk($nametagsArray, 10);
if (empty($pages)) {
    $pages = [[]];
}

foreach ($pages as $pageNametags) {
    $filledPage = array_pad($pageNametags, 10, null);
?>
    <div class="print-page">
        <?php foreach ($filledPage as $datarecord) { ?>
            <?php if ($datarecord) { ?>
                <div class="name-card">
                    <?php
                        $visitorName = trim((string)($datarecord->first_name ?? '') . ' ' . (string)($datarecord->last_name ?? ''));
                        $schoolCompany = trim((string)($datarecord->school_company ?? ''));
                    ?>
                    <h2><?php echo h($visitorName); ?></h2>
                    <p><?php echo h($schoolCompany); ?></p>
                    <p><?php echo h($convSeasD->Conventions['name']); ?><br><?php echo h($convSeasD->season_year); ?></p>
                </div>
            <?php } else { ?>
                <div class="name-card blank"></div>
            <?php } ?>
        <?php } ?>
    </div>
<?php } ?>
</body>
</html>
