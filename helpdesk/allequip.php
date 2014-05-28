<?php 

		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			require("incl/sqlConnect.inc.php");
			
			// Set variables, assign "none" to userID if deptID is used
			if ($_POST['deptID'] == "none") {
				$userID = mysqli_real_escape_string($dbc, $_POST['userID']);
				$deptID = "none";
			} else {
				$userID = "";
				$deptID = $_POST['deptID'];	
			}
			
			// Sanitize ticket description and escape special chars for mySQL query
			$equipDesc = mysqli_real_escape_string($dbc, $_POST['equipDesc']);
			$equipSerial = mysqli_real_escape_string($dbc, $_POST['equipSerial']);

			// If a linkID is set in userEquip table:
			if ($_POST['linkID'] !== "none") {
				mysqli_query($dbc, "UPDATE equipment SET equipDesc='$equipDesc', equipSerial='$equipSerial', equipType='{$_POST['equipType']}' WHERE equipID='{$_POST['id']}' LIMIT 1");
				if ($userID == "" && $deptID == "none") { // If no user and dept is set, delete userEquip record
					mysqli_query($dbc, "DELETE FROM userEquip WHERE linkID='{$_POST['linkID']}' LIMIT 1");
				} else { // Otherwise update userEquip record
					mysqli_query($dbc, "UPDATE userEquip SET userID='$userID', deptID='$deptID' WHERE linkID='{$_POST['linkID']}' LIMIT 1");
				}
			} else { // Create a new userEquip link if there isn't one
				mysqli_query($dbc, "UPDATE equipment SET equipDesc='$equipDesc', equipSerial='$equipSerial', equipType='{$_POST['equipType']}' WHERE equipID='{$_POST['id']}' LIMIT 1");
				if ($userID == "") { // Set when no userID is set
					mysqli_query($dbc, "INSERT INTO userEquip (equipID, userID, deptID) VALUES ('{$_POST['id']}', NULL, '$deptID')");
				} else { // Set when no deptID is set
					mysqli_query($dbc, "INSERT INTO userEquip (equipID, userID, deptID) VALUES ('{$_POST['id']}', '$userID', NULL)");
				}
				
			}
			
			// Refresh page after recieving edit form post to update page table
			header("Location: allequip.php");

		}
?>

<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>All Equip</title>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script type="text/javascript" src="js/global.js"></script>
	<script type="text/javascript" src="js/jquery.dataTables.js"></script>
	<link rel="stylesheet" href="css/foundation.css">
	<link rel="stylesheet" href="css/foundation.min.css">
	<link rel="stylesheet" href="css/normalize.css">
	<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css">
</head>

<?php 
		session_start();

		include("navigation.php");
		require("incl/sqlConnect.inc.php");

		if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== '1') {
			header("Location: login.php");
		}
?>

<?php 

	function table_user_assigned_equip() {
		global $dbc;
		$result = mysqli_query($dbc, "SELECT equipment.equipID, equipment.equipDesc, equipType.equipType, userEquip.userID, userEquip.linkID, user.username, user.firstName, user.lastName, department.department FROM equipment JOIN equipType ON equipment.equipType=equipType.equipTypeID JOIN userEquip ON userEquip.equipID=userEquip.equipID JOIN user ON userEquip.userID=user.userID JOIN department ON user.department=department.deptID AND userEquip.userID=user.userID AND userEquip.equipID=equipment.equipID");
		$rows = mysqli_num_rows($result);

		echo '<div class="row">';
		echo '<div class="large-12 columns" id="userEquip">';

		echo "<p>Equipment Assigned to Employees:</p>";

		echo "<table id=\"userequip_table\">";
		echo "<thead>";
		echo "<tr>";
		echo "<th>Action</th>";
		echo "<th>Equip ID</th>";
		echo "<th>Type</th>";
		echo "<th>Description</th>";
		echo "<th>User ID</th>";
		echo "<th>Username</th>";
		echo "<th>First</th>";
		echo "<th>Last</th>";
		echo "<th>Department</th>";
		echo "</tr>";
		echo "</thead>";
		echo "<tbody>";
		while ($rows=mysqli_fetch_array($result)) {
			echo "<tr>";
			echo "<td><a class=\"?delete=1&id=" . $rows['0'] . "\" href=\"#\"><img src=\"images/delete.png\"></a><a data-reveal-id=\"editEquip\" class=\"?edit=1&id=" . $rows['0'] . "&user=" . $rows['userID'] . "&link=" . $rows['linkID'] . "\" href=\"#\"><img src=\"images/edit.png\"></a></td>";
			echo "<td>" . $rows['equipID'] . "</td>";
			echo "<td>" . $rows['equipType'] . "</td>";
			echo "<td>" . $rows['equipDesc'] . "</td>";
			echo "<td>" . $rows['userID'] . "</td>";
			echo "<td>" . $rows['username'] . "</td>";
			echo "<td>" . $rows['firstName'] . "</td>";
			echo "<td>" . $rows['lastName'] . "</td>";
			echo "<td>" . $rows['department'] . "</td>";
			echo "</tr>";
		}
		echo "</tbody>";
		echo "</table>";
		echo "</div>";
		echo "</div>";
	}

	function table_dept_assigned_equip() {
		global $dbc;
		$result = mysqli_query($dbc, "SELECT equipment.equipID, equipment.equipDesc, equipType.equipType, userEquip.userID, userEquip.linkID, department.deptID, department.department FROM equipment JOIN equipType ON equipment.equipType=equipType.equipTypeID JOIN userEquip ON userEquip.equipID=userEquip.equipID JOIN department ON userEquip.deptID=department.deptID AND userEquip.equipID=equipment.equipID");
		$rows = mysqli_num_rows($result);

		echo '<div class="row">';
		echo '<div class="large-12 columns" id="deptEquip">';

		echo "<p>Equipment Assigned to Departments:</p>";

		echo "<table id=\"deptequip_table\">";
		echo "<thead>";
		echo "<tr>";
		echo "<th>Action</th>";
		echo "<th>Equip ID</th>";
		echo "<th>Type</th>";
		echo "<th>Description</th>";
		echo "<th>Department</th>";
		echo "</tr>";
		echo "</thead>";
		echo "<tbody>";
		while ($rows=mysqli_fetch_array($result)) {
			echo "<tr>";
			echo "<td><a class=\"?delete=1&id=" . $rows['0'] . "\" href=\"#\"><img src=\"images/delete.png\"></a><a data-reveal-id=\"editEquip\" class=\"?edit=1&id=" . $rows['0'] . "&dept=" . $rows['deptID'] . "&link=" . $rows['linkID'] . "\" href=\"#\"><img src=\"images/edit.png\"></a></td>";
			echo "<td>" . $rows['equipID'] . "</td>";
			echo "<td>" . $rows['equipType'] . "</td>";
			echo "<td>" . $rows['equipDesc'] . "</td>";
			echo "<td>" . $rows['department'] . "</td>";
			echo "</tr>";
		}
		echo "</tbody>";
		echo "</table>";
		echo "</div>";
		echo "</div>";
	}

	function table_unassigned_equip() {
		global $dbc;
		$result = mysqli_query($dbc, "SELECT equipment.equipID, equipment.equipDesc, equipType.equipType FROM equipment JOIN equipType ON equipment.equipType=equipType.equipTypeID WHERE NOT EXISTS (SELECT userEquip.equipID FROM userEquip WHERE equipment.equipID=userEquip.equipID)");
		$rows = mysqli_num_rows($result);

		echo '<div class="row">';
		echo '<div class="large-12 columns" id="unassignedEquip">';

		echo "<p>Unassigned Equipment:</p>";

		echo "<table id=\"unassignedequip_table\">";
		echo "<thead>";
		echo "<tr>";
		echo "<th>Action</th>";
		echo "<th>Equip ID</th>";
		echo "<th>Type</th>";
		echo "<th>Description</th>";
		echo "</tr>";
		echo "</thead>";
		echo "<tbody>";
		while ($rows=mysqli_fetch_array($result)) {
			echo "<tr>";
			echo "<td><a class=\"?delete=1&id=" . $rows['0'] . "\" href=\"#\"><img src=\"images/delete.png\"></a><a data-reveal-id=\"editEquip\" class=\"?edit=1&id=" . $rows['0'] . "\" href=\"#\"><img src=\"images/edit.png\"></a></td>";
			echo "<td>" . $rows['equipID'] . "</td>";
			echo "<td>" . $rows['equipType'] . "</td>";
			echo "<td>" . $rows['equipDesc'] . "</td>";
			echo "</tr>";
		}
		echo "</tbody>";
		echo "</table>";
		echo "</div>";
		echo "</div>";
	}

	//=================================Page content start=================================

	echo "<h2>View All Equipment</h2>";

	?>
		<button type="button" id="showUserEquip">Equipment Assigned to Users</button>
		<button type="button" id="showDeptEquip">Equipment Assigned to Departments</button>
		<button type="button" id="showUnassignedEquip">Unassigned Equipment</button>
	<?php

	table_user_assigned_equip();
	table_dept_assigned_equip();
	table_unassigned_equip();

	/*
	
	// MySQL Notes:

	###Query for equipment assigned to users:

	SELECT 
		equipment.equipID, 
		equipment.equipDesc, 
		equipType.equipType, 
		userEquip.userID, 
		userEquip.linkID, 
		user.username, 
		user.firstName, 
		user.lastName, 
		department.department 
	FROM equipment 
	JOIN equipType ON equipment.equipType=equipType.equipTypeID 
	JOIN userEquip ON userEquip.equipID=userEquip.equipID 
	JOIN user ON userEquip.userID=user.userID 
	JOIN department ON user.department=department.deptID 
	AND userEquip.userID=user.userID 
	AND userEquip.equipID=equipment.equipID

	###Query for equipment assigned to departments:

	SELECT 
		equipment.equipID, 
		equipment.equipDesc, 
		equipType.equipType, 
		userEquip.userID, 
		userEquip.linkID, 
		department.deptID, 
		department.department
	FROM equipment 
	JOIN equipType ON equipment.equipType=equipType.equipTypeID 
	JOIN userEquip ON userEquip.equipID=userEquip.equipID 
	JOIN department ON userEquip.deptID=department.deptID AND userEquip.equipID=equipment.equipID

	###Query for equipment that is unassigned:

	SELECT 
		equipment.equipID, 
		equipment.equipDesc, 
		equipType.equipType 
	FROM equipment 
	WHERE NOT EXISTS
	(SELECT
		userEquip.equipID
	FROM userEquip 
	JOIN equipType ON equipment.equipType=equipType.equipTypeID 
	WHERE
		equipment.equipID=userEquip.equipID
	)

	*/
?>

<div id="editEquip" class="reveal-modal" data-reveal></div>
<script src="js/foundation.min.js"></script>
<script src="js/vendor/fastclick.js"></script>
<script> $(document).foundation(); </script>
</body>
</html>
