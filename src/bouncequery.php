<?php
	require 'vendor/autoload.php';
	require "lib/common.php";
	require "lib/bounce.php";

	ob_implicit_flush(TRUE);
	date_default_timezone_set('America/New_York');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="../../docs-assets/ico/favicon.png">

    <title>SES Bounced Email Query</title>
    <!-- Bootstrap core CSS -->
    <link href="vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="navbar.css" rel="stylesheet">
  </head>
  <body>
    <div class="container">

<?php include 'menu.php'; ?>

<?php
	$RecipientEmailPlaceholder = "Recipient Email";
	$recipient_email = "";

	$currentDate = date('Y-m-d');
	// $yesterdayDate = date('Y-m-d',(strtotime ( '-1 day' , strtotime ( $currentDate) ) ));

	$start_date = $currentDate;
	$end_date = $currentDate;

	if ( (isset($_POST["search-email"])) )
	{
		// This just fills in the form fields to show what the user is searching for
		if (!empty($_POST['recipient_email']))
		{
			$recipient_email = $_POST['recipient_email'];
			$RecipientEmailPlaceholder = $recipient_email;
		}
		if (!empty($_POST['start_date'])) { $start_date = $_POST['start_date']; }
		if (!empty($_POST['end_date'])) { $end_date = $_POST['end_date']; }
	}
?>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Bounced Email Query Tool</h3>
		</div>
		<form method='post' action=''>
			<div class='panel-body'>
				This tool allows you to query for bounced emails.  Wildcards in the form of an asterix are permitted.  You MUST specify a date filter (minium is 1 day).
				<p class="text-center">&nbsp;</p>
				<div class='input-group input-group-lg'>
					<span class='input-group-addon'>@</span>
					<input type='text' class='form-control' name='recipient_email' value='<?php echo $recipient_email; ?>' placeholder='<?php echo $RecipientEmailPlaceholder; ?>'>
					<span class='input-group-btn'>
						<button class='btn btn-primary' type='submit' name='search-email'>Search</button>
					</span>
				</div>
				&nbsp;
				<div class='input-group input-group-lg'>
					<span class='input-group-addon'>
						<span class='glyphicon glyphicon-calendar'> Start</span>
					</span>
					<input type='date' class='form-control' name='start_date' value='<?php echo $start_date; ?>'>
					<span class='input-group-addon'>
						<span class='glyphicon glyphicon-calendar'> End</span>
					</span>
					<input type='date' class='form-control' name='end_date' value='<?php echo $end_date; ?>'>
				</div>
			</div>
		</form>
	</div> <!-- /panel -->

<?php

$appConfig = readConfig("config.yaml");
$bounceTable = $appConfig['dynamodb']['bouncetable'];
$region = $appConfig['dynamodb']['region'];

// Setup the Sdk
$sharedConfig = [
	'region' => $region,
	'version' => 'latest'
];

$sdk = new Aws\Sdk($sharedConfig);
$dynamoClient = $sdk->createDynamoDb();

if ( (isset($_POST["search-email"])) )
{
	$infoText = "";

	if ( (!empty($_POST['start_date'])) && (!empty($_POST['end_date'])) )
	{
		$startDate = $_POST['start_date'];
		$endDate = $_POST['end_date'];

		if (strtotime($startDate) > strtotime($endDate) )
		{
			$infoText = "Start date must come before end date";
		}
	} else
	{
		$infoText = "No start and/or end date provided";
	}

	if (empty($infoText))
	{
		if ( isset($_POST["search-email"]) )
		{
			$startDate = $_POST['start_date'];

			if (!empty($_POST['recipient_email']))
			{
				$recipientAddressToQuery = trim(strtolower($_POST['recipient_email']));
				$panelTitle = "Bounced emails for recipient: " . $recipientAddressToQuery;

				$resultDataSet = bounceSearch($dynamoClient,$bounceTable,$recipientAddressToQuery,$startDate,$endDate);

				if ( empty($resultDataSet['error']) )
				{
					outputResults($resultDataSet,$panelTitle);
				} else
				{
					$infoText = $resultDataSet['error'];
				}
			} else
			{
				$infoText = "No email recipient provided";
			}
		}
	}

	if (!empty($infoText))
	{
		print "<div class='alert alert-danger'>\n";
		print $infoText;
		print "</div>\n";
	}
}
?>
    </div> <!-- /container -->

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
    <script src="vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
  </body>
</html>
