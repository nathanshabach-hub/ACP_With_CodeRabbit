<?php
use Cake\ORM\TableRegistry;
$this->Conventionregistrationstudents = TableRegistry::getTableLocator()->get('Conventionregistrationstudents');
$this->Eventsubmissions = TableRegistry::getTableLocator()->get('Eventsubmissions');
$this->Books = TableRegistry::getTableLocator()->get('Books');

foreach($conventionregistrationstudents as $convRegStudentD)
{
	// to get event submission details of student so that we will get books
	$bookArr 		= array();
	$checkFlagPrint = 0;
	$bookNames 		= "";
	
	$studentEventSub = $this->Eventsubmissions->find()->where(['Eventsubmissions.conventionregistration_id' => $convRegStudentD->conventionregistration_id,'Eventsubmissions.convention_id' => $convRegStudentD->convention_id,'Eventsubmissions.user_id' => $convRegStudentD->user_id,'Eventsubmissions.season_id' => $convRegStudentD->season_id,'Eventsubmissions.student_id' => $convRegStudentD->student_id,'Eventsubmissions.event_id' => $eventD->id])->order(["Eventsubmissions.id"=> "DESC"])->first();
	
	//echo '<pre>';print_r($studentEventSub);echo '</pre>';exit;
	
	$submission_book_ids = $studentEventSub->book_ids;
	if(!empty($submission_book_ids))
	{	
		// to get name of books
		$condBooks = array();
		$condBooks[] = "(Books.id IN ($submission_book_ids))";
		$booksList = $this->Books->find()->where($condBooks)->order(['Books.book_name' => 'ASC'])->all();
		foreach($booksList as $bookd)
		{
			$bookArr[] = $bookd->book_name;
		}
	}
	
	//echo '<pre>';print_r($bookArr);exit;
	if(count($bookArr))
	{
		$checkFlagPrint = 1;
		
		$bookNames = implode(", ",$bookArr);
	

		// to prepare an arrayto send forpdf generation
		$arrCertData = array();
		
		$arrCertData['convention_name'] = $convRegStudentD['Conventions']['name'];
		
		$arrCertData['student_name'] 	= $convRegStudentD['Students']['first_name'];
		if(!empty($convRegStudentD['Students']['middle_name']))
		{
			$arrCertData['student_name'] .= ' '.$convRegStudentD['Students']['middle_name'];
		}
		if(!empty($convRegStudentD['Students']['last_name']))
		{
			$arrCertData['student_name'] .= ' '.$convRegStudentD['Students']['last_name'];
		}
		
		$arrCertData['school_name'] = $convRegStudentD['Users']['first_name'];
		$arrCertData['book_names'] 	= $bookNames;
		$bookNamesRaw = trim((string)$arrCertData['book_names']);
		$isLongBookList = mb_strlen($bookNamesRaw) > 90;
		$bookNamesRender = htmlspecialchars($bookNamesRaw);
		if ($isLongBookList) {
			$bookNamesRender = nl2br(htmlspecialchars(wordwrap($bookNamesRaw, 68, "\n", false)));
		}
	
		// certificate theme
		$headerImg 		=	HTTP_PATH.'/img/front/certificates/'.$certificateTheme['header_image'];
		$footerImg 		=	HTTP_PATH.'/img/front/certificates/'.$certificateTheme['footer_image'];
		$signatureImg 	=	HTTP_PATH.'/img/front/certificates/signature.png';
		$borderColor 	= $certificateTheme['border_color'];
?>


<!DOCTYPE html>
<html lang="en" class="h-100">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
		<link rel="shortcut icon" type="image/x-icon" href="images/favicon.png">
		<title><?php //echo $arrCertData['student_name']; ?></title>
		<link href="https://fonts.googleapis.com/css2?family=Pinyon+Script&display=swap" rel="stylesheet">
		<style type="text/css">
		* { box-sizing: border-box; }
		@page {
		size: A4 landscape;
		margin:0;
		}
		body { margin: 0; padding: 0; font-family: Arial, sans-serif; font-weight: 500; background: #fff; }
		
		 
	</style>
	</head>
	<body>
		<div class="container" style="max-width:100%;margin:0 auto;background:#fff;padding:0;">
			<div class="maincontainer" style="max-width:100%;background:#fff;margin:0 auto;">
				<div class="header" style="width:100%;display:block;padding:0;">
					<img src="<?php echo $headerImg; ?>" style="width:100%;display:block;">
				</div>
				<div class="contentpart" style="width:100%;text-align:center;padding:0 1%;">
					<div style="position:relative;top:-100px;right:12%;font-size:14px;font-weight:bold;text-align:right;"><?php echo h($arrCertData['convention_name']); ?></div>
					<p class="simpletextt" style="font-family:arial;font-size:14px;margin-top:-20px;padding:0;">
						Accelerate Educational Ministries takes pleasure in presenting this award to
					</p>
					<div><i style="font-size:30px;padding:10px 0 5px;display:block;font-weight:500;font-family:'Pinyon Script',cursive;"><?php echo h($arrCertData['student_name']); ?></i></div>
					<span style="font-size:14px;display:block;width:100%;">from</span>
					<div><i style="font-size:24px;padding:10px 0 5px;display:block;font-family:'Pinyon Script',cursive;"><?php echo h($arrCertData['school_name']); ?></i></div>
					<span style="font-size:12px;width:100%;display:block;padding-top:10px;
						letter-spacing:0.5px;">for memorising the book of</span>
					<div style="margin-bottom:-80px;">
						<h5 style="font-size:<?php echo $isLongBookList ? '22px' : '30px'; ?>;line-height:<?php echo $isLongBookList ? '1.2' : '1.0'; ?>;font-style:italic;letter-spacing:0.6px;max-width:86%;width:100%;display:block;margin:10px auto 30px;font-weight:500;font-family:'Pinyon Script',cursive;white-space:normal;word-break:break-word;overflow-wrap:anywhere;"><?php echo $bookNamesRender; ?></h5>
					</div>
					<div style="width:110px;position:relative;top:160px;left:180px;">
						<img src="<?php echo $signatureImg; ?>" style="width:60px;">
						<b style="position:absolute;top:43px;left:10px;font-size:10px;">Slabbert Pretorius</b>
						<p style="position:absolute;top:57px;left:3px;font-size:9px;padding:0px;margin:0px;">MANAGING DIRECTOR</p>
						<span style="position:absolute;top:69px;left:-30px;font-size:10px;width:200px;">Southern Cross Educational Enterprises Ltd.</span>
					</div>
					<div style="position:relative;width:240px;left:39%;font-size:12px;font-weight:bold;font-style:italic;top:80px;">
						A word fitly spoken is like apples of gold <br>
						in pictures of silver<br>
						Proverbs 25:11
					</div>
				</div>
				<div class="footer" style="width:100%;display:inline-block;padding:0;box-sizing:border-box;margin-top:40px;">
					<img src="<?php echo $footerImg; ?>" style="width:100%;display:block;">&nbsp;
				</div>
			</div>
		</div>
	</body>
	 
	
</html>

<?php
	}
}
?>

<?php
if($checkFlagPrint == 1)
{
?>
<script type="text/javascript">
<!--
window.print();
//-->
</script>
<?php
}
else
{
	echo 'Sorry, no certificate available to print. Might be possible that books are not assigned in event submission.';
}
?>