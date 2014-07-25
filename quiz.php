<?php
session_start();
include("database.php");
extract($_POST);
extract($_GET);
extract($_SESSION);

echo "---------".$_SESSION["group"];
if(isset($subid) && isset($testid)) {
	$_SESSION["sid"] = $subid;
	$_SESSION["tid"] = $testid;
	
	$query = "SELECT * FROM mst_useranswer WHERE sess_id='".session_id()."' AND test_id=".$testid;
	$result = mysql_query($query, $cn) or die(mysql_error());
	if (mysql_num_rows($result) == 0) {
		
		$questionTable = "mst_question";
		$limitQ = 25;
		if (isset($_SESSION["group"]) and $_SESSION["group"] == 1) {
			$questionTable = "mst_question_basic";
			$limitQ = 20;
		}
		echo "------------".$questionTable;
		echo "------------".$limitQ;
		
		$query1 = "SELECT * FROM ".$questionTable." WHERE test_id=".$testid." ORDER BY RAND() LIMIT ".$limitQ;
		echo "--".$query1."--";
		$result1 = mysql_query($query1, $cn) or die(mysql_error());
		while($row = mysql_fetch_row($result1)) {
			$query2 = "INSERT INTO mst_useranswer(sess_id, test_id, que_id, que_des, ans1, ans2, ans3, ans4, true_ans, your_ans) values ('".session_id()."', ".$testid.",'$row[0]','$row[2]','$row[3]','$row[4]','$row[5]', '$row[6]','$row[7]','')";
			echo "-----------".$query2;
			mysql_query($query2) or die(mysql_error());
		}
	}
	$_SESSION["qn"] = 0;
	$_SESSION["trueans"] = 0;
	echo "Sucessfull".$_SESSION["qn"];
	header("location:quiz.php");
}

if(!isset($_SESSION["sid"]) || !isset($_SESSION["tid"])) {
	//echo "Fiale case";
	header("location: index.php");
}
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Online Quiz</title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<link href="quiz.css" rel="stylesheet" type="text/css">
	</head>

<body>
<?php
include("header.php");
$submit = (isset($_POST["submit"]))?$_POST["submit"]:"";

if(isset($_SESSION["qn"])) {
	$sessionid = session_id();
	$query3 = "SELECT * FROM mst_useranswer WHERE sess_id='".session_id()."' AND test_id=$tid";
	$result3 = mysql_query($query3, $cn) or die(mysql_error());
	$rowCount = mysql_num_rows($result3);
	mysql_data_seek($result3, $_SESSION["qn"]);
	$row = mysql_fetch_row($result3); 
	
	if($_SESSION["qn"] > $rowCount-1) {
		unset($_SESSION["qn"]);
		echo "<h1 class=head1>Some Error  Occured</h1>";
		session_destroy();
		echo "Please <a href=index.php> Start Again</a>";
		exit;
	} else {
		if ($submit=='Next Question' or $submit=='Get Result') {
			if (!isset($ans)) {
				echo "Please Select valid answer";
			} else {
				$query4 = "UPDATE mst_useranswer SET your_ans=$ans WHERE que_id=".$row[2];
				//echo $query4."---";
				mysql_query($query4, $cn) or die(mysql_error());
			
				if($ans == $row[8]) {
					$_SESSION["trueans"] = $_SESSION["trueans"]+1;
				}
				$_SESSION["qn"] = $_SESSION["qn"]+1;
			
				if($submit=='Get Result') {
					echo "<h1 class=head1> Thanks for Participating.</h1>";
					$percentage=($_SESSION["trueans"]/$_SESSION["qn"])*100;
					//echo $percentage."%. ".$_SESSION["trueans"]." out of ".$_SESSION["qn"].".";
					mysql_query("insert into mst_result(login,test_id,sess_id,test_date,correctque,totalque,percentage) values('$login',$tid,'$sessionid',now(),'$trueans','$qn'+1,'$percentage')") or die(mysql_error());
					//echo "<h1 align=center><a href=review.php> Review Question</a> </h1>";
					unset($_SESSION["qn"]);
					unset($_SESSION["sid"]);
					unset($_SESSION["tid"]);
					unset($_SESSION["trueans"]);
					session_destroy();
					echo "Please <a href=index.php> Start Again</a>";
					exit;
				}
	
			}
		}
		
		mysql_data_seek($result3, $_SESSION["qn"]);
		$row = mysql_fetch_row($result3);
	
		renderData($row, $rowCount);
	}
} else {
	unset($_SESSION["qn"]);
	echo "<h1 class=head1>Some Error  Occured</h1>";
	session_destroy();
	echo "Please <a href=index.php> Start Again</a>";
	exit;
}

function renderData($row, $count) {
	echo "<form name=myfm method=post action=quiz.php>";
	echo "<table width=100%>";
	echo "<tr><td width=30>&nbsp</td></tr>";
	echo "<table border=0>";
	$n=$_SESSION["qn"]+1;
	echo "<tr><td><span class=style2>Que ".  $n .": $row[3]</span></td></tr>";
	echo "<tr><td class=style8><input type=radio name=ans value=1>$row[4]</td></tr>";
	echo "<tr><td class=style8> <input type=radio name=ans value=2>$row[5]</td></tr>";
	echo "<tr><td class=style8><input type=radio name=ans value=3>$row[6]</td></tr>";
	echo "<tr><td class=style8><input type=radio name=ans value=4>$row[7]</td></tr>";

	if($_SESSION["qn"] < $count-1)
		echo "<tr><td><input type=submit name=submit value='Next Question'></td></tr>";
	else
		echo "<tr><td><input type=submit name=submit value='Get Result'></td></tr>";
		
	echo "</table></table></form>";
}
?>
</body>
</html>