<?php
function deliverySearch($dynamoClient,$tableName,$queryArgs,$startDate,$endDate)
{
	$resultSet = array();
	$continue = false;
	$result = "";

	// Convert our start and end dates into epoch time
	// note all the times in the DB are UTC
	$startDT = new DateTime($startDate . '00:00:00',new DateTimeZone('UTC'));
	$startEpoch = $startDT->format('U');

	$endDT = new DateTime($endDate . '23:59:59',new DateTimeZone('UTC'));
	$endEpoch = $endDT->format('U');

		$doScan = false;

		error_log("Looking in DynamoDB for email address: " . $queryArgs);

		if ($queryArgs === '*')
		{
			$doScan = true;
			$queryArgs = '.';
		}

		if (strpos($queryArgs,'*') !== false)
		{
			// remove the wildcards and set flag to do a scan rather than query
			$doScan = true;
			$queryArgs = preg_replace('/\*/','',$queryArgs);
		}

		if ($doScan)
		{
			error_log("Performing dynamoDB SCAN");
			try
			{
				$results = $dynamoClient->getPaginator('Scan',array(
					'TableName' => $tableName,
					'ScanFilter' => array(
						'recipientAddress' => array(
							'ComparisonOperator' => 'CONTAINS',
							'AttributeValueList' => array(
								array('S' => $queryArgs)
							)
						),
						'sesTimestamp' => array(
							'ComparisonOperator' => 'BETWEEN',
							'AttributeValueList' => array(
								array('N' => $startEpoch),
								array('N' => $endEpoch)
							)
						)
					)
				));
			} catch (DynamoDbException $e)
			{
				$messageString = $e->getExceptionCode() . ":" . $e->getMessage();
				error_log("Error scanning dynamoDB for delivery: " . $messageString);
			}
		} else
		{
			error_log("Performing dynamoDB QUERY");
			try
			{
				$results = $dynamoClient->getPaginator('Query',array(
					'TableName' => $tableName,
					'KeyConditions' => array(
						'recipientAddress' => array(
							'ComparisonOperator' => 'EQ',
							'AttributeValueList' => array(
								array('S' => $queryArgs)
							)
						)
					),
					'QueryFilter' => array(
						'sesTimestamp' => array(
						'ComparisonOperator' => 'BETWEEN',
						'AttributeValueList' => array(
							array('N' => $startEpoch),
							array('N' => $endEpoch)
							)
						)
					)
				));
			} catch (DynamoDbException $e)
			{
				$messageString = $e->getExceptionCode() . ":" . $e->getMessage();
				error_log("Error querying dynamoDB for delivery: " . $messageString);
			}
		} // doScan

	if (isset($results))
	{
		$index = 0;
		foreach ($results as $result)
		{
			foreach ($result['Items'] as $item)
			{
				$resultSet[$index]['recipient'] = $item['recipientAddress']['S'];
				$resultSet[$index]['reportingmta'] = $item['reportingMTA']['S'];
				$resultSet[$index]['sender'] = $item['sender']['S'];
				$resultSet[$index]['smtpresponse'] = $item['smtpResponse']['S'];
				$resultSet[$index]['timestamp'] = $item['sesTimestamp']['N'];
				$resultSet[$index]['deliverytimestamp'] = $item['deliveryTimestamp']['N'];
				$index++;
			}
		}
	}
	return $resultSet;
}

function outputResults($results,$panelTitle)
{
	// The results are in a 2D indexed array

	print "<div class='panel panel-primary'>\n";
	print "<div class='panel-heading'>\n";
	print "<h3 class='panel-title'>" . $panelTitle . "</h3>\n";
	print "</div>\n";
	print "<div class='panel-body'>\n";

	print "<table class='table table-condensed'>\n";
	print "<tr>\n";
		print "<th width='1%'>#</th>\n";
		print "<th width='7%'>Original Message Sent</th>\n";
		print "<th width='7%'>AWS Delivery Time</th>\n";
		print "<th width='20%'>Recipient</th>\n";
		print "<th width='21%'>Sender</th>\n";
		print "<th width='6%'>MTA</th>\n";
		print "<th width='45%'>Response</th>\n";
	print "</tr>\n";

	$resultNumber = 1;

	unset($results['error']);

	usort($results,'timestamp_sort');

	foreach ($results as $result)
	{
		// If we have an MTA, clean up the field slightly
		if ($result['reportingmta'] != "UNKNOWN")
		{
			$MTA = substr($result['reportingmta'],(strpos($result['reportingmta'],";")+1));
		} else
		{
			$MTA = "Unknown";
		}

		// We may not have a timestamp
		if ($result['deliverytimestamp'])
		{
			$displayTimestamp = date('r',$result['deliverytimestamp']);
		} else
		{
			$displayTimestamp = "N/A";
		}

		print "<tr>\n";
			print "<td>" . $resultNumber . "</td>\n";
			print "<td class='wrapvals'><small>" . date('r',$result['timestamp']) . "</small></td>\n";
			print "<td class='wrapvals'><small>" . $displayTimestamp . "</small></td>\n";
			print "<td class='wrapvals'><small>" . $result['recipient'] . "</small></td>\n";
			print "<td class='wrapvals'><small>" . $result['sender'] . "</small></td>\n";
			print "<td class='wrapvals'><small>" . $MTA . "</small></td>\n";
			print "<td class='wrapvals'><small>" . $result['smtpresponse'] . "</small></td>\n";
		print "</tr>\n";
		$resultNumber++;
	}
	print "</table>\n";
	print "</div>\n";
}
 ?>
