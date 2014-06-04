<?php 
	//session_start();
	// Connect to SQL DB
	require("incl/sqlConnect.inc.php");

	if($_SERVER['REQUEST_METHOD'] == 'POST') {
		//print_r($_POST);
		if (isset($_POST['pageNo'])) { // If page number was submitted...
			$urlParams = http_build_query(array_merge($_GET, array("page"=>$_POST['pageNo']))); // Merge page number into the url params
			header("Location: " . $_SERVER['PHP_SELF'] . "?" . $urlParams); // And reload the page so the url params go into effect
		}
		if (isset($_POST['searchFilter'])) {  // If search filter was submitted...
			$urlParams = http_build_query(array_merge($_GET, array("search"=>$_POST['searchFilter']))); // Merge search filter into the url params
			header("Location: " . $_SERVER['PHP_SELF'] . "?" . $urlParams); // And reload the page so the url params go into effect
		}
		if (isset($_POST['submitType'])) {
			switch ($_POST['submitType']) {
				case 'editDynamic':
					// Build MySQL Statement while sanitizing inputted data
					$postCount = 0;
					$sqlEdit = "UPDATE " . $_POST['table'] . " SET ";
					foreach ($_POST as $key => $value) { // Loops through POST (ignore first 2 values of submitType and table)
						if ($postCount > 1) {
							if ($postCount == 2) { // Make sure to ignore leading comma on first field entry
								$sqlEdit .= $key . "=" . "'" . mysqli_real_escape_string($dbc,$value) . "'";
								$idName = $key;
								$idVal = mysqli_real_escape_string($dbc,$value);
							} else {
								// And continue building SQL update statement
								$sqlEdit .= ", " . $key . "=" . "'" . mysqli_real_escape_string($dbc,$value) . "'";	
							}
						}
						$postCount++;
					}
					// Finalize SQL statement with a qualifier and limit
					$sqlEdit .= " WHERE " . $idName . "='" . $idVal . "' LIMIT 1";
					//echo $sqlEdit;
					mysqli_query($dbc,$sqlEdit);
					break;

				case 'edit':
					// Sanitize ticket description and escape special chars for mySQL query
					$desc = mysqli_real_escape_string($dbc, $_POST['desc']);
					$date = mysqli_real_escape_string($dbc, $_POST['date']);
					$category = mysqli_real_escape_string($dbc, $_POST['category']);
					$priority = mysqli_real_escape_string($dbc, $_POST['priority']);
					$status = mysqli_real_escape_string($dbc, $_POST['status']);
					if ($status == "1") { // If setting ticket to OPEN status
						// Then make sure to clear assignedTo field
						mysqli_query($dbc, "UPDATE ticket SET timestamp='$date', categoryID='$category', priorityID='$priority', statusID='$status', issueDesc='$desc', assignedTo=null WHERE ticketID='{$_POST['id']}' LIMIT 1");	
					} else { // Otherwise, simply update ticket details
						mysqli_query($dbc, "UPDATE ticket SET timestamp='$date', categoryID='$category', priorityID='$priority', statusID='$status', issueDesc='$desc' WHERE ticketID='{$_POST['id']}' LIMIT 1");
					}
					break;

				case 'comment':
					// Sanitize ticket description and escape special chars for mySQL query
					$comment = mysqli_real_escape_string($dbc, $_POST['comment']);
					mysqli_query($dbc, "INSERT INTO ticketComment (ticketID, userID, timestamp, comment) VALUES ('{$_POST['ticketID']}','{$_POST['userID']}', NOW(), '$comment')");
					echo "INSERT INTO ticketComment (ticketID, userID, timestamp, comment) VALUES ('{$_POST['ticketID']}','{$_POST['userID']}', NOW(), '$comment')";
					break;

				case 'delete':				
					mysqli_query($dbc, "DELETE FROM ticket WHERE ticketID='{$_POST['id']}' LIMIT 1");
					mysqli_query($dbc, "DELETE FROM ticketComment WHERE ticketID='{$_POST['id']}'");
					break;

				case 'deleteDynamic':	
					// Build MySQL Statement to delete record
					$sqlDelete = "DELETE FROM " . $_POST['table'] . " WHERE " . $_POST['firstColumn'] . "=" . $_POST['id'];
					//echo $sqlDelete;
					mysqli_query($dbc,$sqlDelete);			
					break;
				case 'editEquip':
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
					break;

				case 'assign':
					// Update ticket and change assignedTo field to help desk's userID, then set status to assigned
					$userID = $_SESSION['userID'];
					$assignSQL = "UPDATE ticket SET assignedTo='" . $userID . "', statusID='2' WHERE ticketID='{$_POST['id']}'";
					//echo $assignSQL;
					mysqli_query($dbc,$assignSQL);

				default:
					break;
			}
		}
	}

		
?>

<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>All Users</title>
	<?php 
		require("incl/scripts.inc.html")
	?>	
</head>

<div class="row">

<?php 

	function column_sort($col_name) {
		//global $col_name; // Grab column name for use
		if (isset($_GET['sortBy']) && isset($_GET['sortOrder'])) { // If something is already set to sorting
			if ($_GET['sortBy'] !== $col_name->table . "." . $col_name->orgname) { // If not sorting by the link that was clicked, set it to the new sort
				$urlParams = http_build_query(array_merge($_GET, array("sortBy"=>$col_name->table . "." . $col_name->orgname,"sortOrder"=>"ASC"))); // Merge/add sort options into url params
				echo "<th><a href=\"?" . $urlParams . "\">" . $col_name->name . "</a></th>";
			} else {
				if ($_GET['sortOrder'] == "ASC") { // If it was sorting ascending, swap it to descending
					$urlParams = http_build_query(array_merge($_GET, array("sortBy"=>$col_name->table . "." . $col_name->orgname,"sortOrder"=>"DESC"))); // Merge/add sort options into url params
					echo "<th><a href=\"?" . $urlParams . "\">" . $col_name->name . "</a></th>";
				} else { // Otherwise make it ascending
					$urlParams = http_build_query(array_merge($_GET, array("sortBy"=>$col_name->table . "." . $col_name->orgname,"sortOrder"=>"ASC"))); // Merge/add sort options into url params
					echo "<th><a href=\"?" . $urlParams . "\">" . $col_name->name . "</a></th>";
				}
			}
		} else { // If nothing was previously set, add sorting
			$urlParams = http_build_query(array_merge($_GET, array("sortBy"=>$col_name->table . "." . $col_name->orgname,"sortOrder"=>"ASC"))); // Merge/add sort options into url params
			echo "<th><a href=\"?" . $urlParams . "\">" . $col_name->name . "</a></th>";
		}
	}
	
	function next_page($page) {
		$page++; // Increment page counter upon click
		if ($_SERVER['QUERY_STRING']) { // If there are already url parameters, add page to the end
			$urlParams = http_build_query(array_merge($_GET, array("page"=>$page))); // Build new url params and add/merge page
			echo "<a href=\"?" . $urlParams . "\">Next</a>";
		} else { // Otherwise set the url parameter to page
			echo "<a href=\"?page=" . $page . "\">Next</a>";
		}
	}

	function prev_page($page) {
		$page--; // Increment page counter upon click
		if ($_SERVER['QUERY_STRING']) { // If there are already url parameters, add page to the end
			$urlParams = http_build_query(array_merge($_GET, array("page"=>$page))); // Build new url params and add/merge page
			echo "<a href=\"?" . $urlParams . "\">Previous</a>";
		} else { // Otherwise set the url parameter to page
			echo "<a href=\"?page=" . $page . "\">Previous</a>";
		}
	}

	function pagination_links($numRows,$rowsPerPage,$page) {
		//echo '<div class="large-6 columns">';
		//echo '<div class="large-4 columns">';
		// First determine number of pages needed
		if ($numRows % $rowsPerPage != 0) {
			$numPages = intval($numRows / $rowsPerPage) + 1;
		} else {
			$numPages = intval($numRows / $rowsPerPage);
		}
		// Previous page link
		if (!($page <= 1)) {
			prev_page($page);
		}
		// Echo out current page
		echo "<pre>" . $page . "</pre>";
		// Next page link
		if (!($page >= $numPages)) {
			next_page($page);
		}
		// Form logic for entering a page number manually
		echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . '">';
		echo '<input type="text" name="pageNo" placeholder="' . $page . '">';
		echo '<input class="button" type="submit" value="Go">';
		echo '</form>';
		// List total page number
		echo "<p>Number of pages: $numPages</p>";
	}

	function search_filter() {
		// Form for submitting search filter string
		// Reset page counter back to 1 when submitting form
		echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, array("page"=>"1"))) . '">';
		echo '<input type="text" name="searchFilter" placeholder="' . "Search..." . '">';
		echo '<input class="button" type="submit" value="Search">';
		echo '</form>';
	}

	// ================== Table Page Start ====================

function output_table($sql,$tableName,$view,$edit,$delete) {
	global $dbc;
	
	// Set sorting and page variables
	if (isset($_GET['sortBy'])) {
		$sortBy = mysqli_real_escape_string($dbc,$_GET['sortBy']);
	} else {
		$sortBy = null;
	}
	if (isset($_GET['sortOrder'])) {
		$sortOrder = mysqli_real_escape_string($dbc,$_GET['sortOrder']);
	} else {
		$sortOrder = "ASC";
	}
	if (isset($_GET['page'])) {
		$page = $_GET['page'];
	} else {
		$page = "1";
	}
	if (isset($_GET['search'])) {
		$searchFilter = mysqli_real_escape_string($dbc,$_GET['search']);
	} else {
		$searchFilter = "";
	}
	// Begin search filter paramters with blank
	$searchParams = null;
	if ($searchFilter) {
		// First reset page counter to 1 for search results
		array_merge($_GET, array("page"=>"1"));
		$result = mysqli_query($dbc,$sql);
		while ($col_name = mysqli_fetch_field($result)) { // Get columns and loop through each
			if (!$searchParams) {
				// Begin search string to inject into SQL
				$searchParams = "WHERE ";
			} else {
				// Continue string search building and connect with the next one
				$searchParams .= "OR ";
			}
			// Add in [table name] LIKE [search filter]
			$searchParams .= $col_name->orgname . " LIKE \"%$searchFilter%\" ";
		}
		$rowsPerPage = 10;
		$pageOffset = 0;
		$page = 1;
	} else {
		$rowsPerPage = 10;
		$pageOffset = $page * $rowsPerPage - $rowsPerPage;	
	}
	
	// Set sort parameters for SQL query
	$sortParams = null;
	if ($sortBy) {
		$sortParams = "ORDER BY $sortBy $sortOrder";
	}
	
	$result = mysqli_query($dbc,$sql . " " . $searchParams);
	if ($result) {
		$numRows = mysqli_num_rows($result);
	} else {
		$numRows = "0";
	}
	
	$result = mysqli_query($dbc, $sql . " " . $searchParams . $sortParams . " LIMIT $pageOffset, $rowsPerPage");
	//echo $sql . " " . $searchParams . $sortParams . " LIMIT $pageOffset, $rowsPerPage";
	if ($result) { // If records were found...
		//echo mysqli_error($dbc);
		$numCols = mysqli_num_fields($result);

		// Begin table
		echo "<table id=\"" . "tbl_" . $tableName . "\">";
		
		// *********** I have no idea how the hell to format these things here
		//echo '<div class=large-12 columns>';
		echo "<div class='large-6 columns'>";
		pagination_links($numRows,$rowsPerPage,$page);
		echo '</div>';
		echo "<div class='large-6 columns'>";
		search_filter();
		//echo '</div>';
		echo '</div>';
		echo "</div>";

		echo '<div class="row">';
		// Build table head
		echo "<thead>";
		echo "<tr>";

		// Insert edit/delete column if selected
		if ($view || $edit || $delete) {
			echo "<th>";
			echo "Action";
			echo "</th>";
		}

		// Continue building table head
		$columnList = [];
		while ($col_name = mysqli_fetch_field($result)) { // While more columns exist...
			column_sort($col_name); // Output table column name with sort links
			// Store column names in an arrya for later use
			array_push($columnList, $col_name->orgname);
		}
		//print_r($columnList);
		echo "</tr>";
		echo "</thead>";

		// Build table body
		echo "<tbody>";

		if (!$rows=mysqli_fetch_row($result)) { // If no records are returned...
			echo "<tr><td>No Entries Found.</td></tr>";
		}
		// Reset pointer after above empty check, otherwise we lose first record
		mysqli_data_seek($result,0);
		
		while ($rows=mysqli_fetch_array($result)) { // While more rows exist...			

			echo "<tr>";

			// Insert View/Edit/Delete options if enabled
			if ($view || $edit || $delete) {
			echo "<th>";
			if ($view) {
				echo "<a data-reveal-id=\"viewRecord\" id=\"view" . $rows['0'] . "\" class=\"?viewid=" . $rows['0'] . "&assignedTo=" . $rows['8'] . "\" href=\"#\"><img src=\"images/view.png\"></a>";
			}
			if ($edit) {
				echo "<a data-reveal-id=\"editRecord\" id=\"edit" . $rows['0'] . "\" class=\"?table=" . $tableName . "&id=" . $rows['0'] . "\" href=\"#\" href=\"#\" id=\"edit" . $rows['0'] . "\"><img src=\"images/edit.png\"></a>";
			}
			if ($delete) {
				echo "<a data-reveal-id=\"deleteRecord\" id=\"delete" . $rows['0'] . "\" class=\"?delete=" . $rows['0'] . "&table=" . $tableName . "\" href=\"#\"><img src=\"images/delete.png\"></a>";
			}
			echo "</th>";
		
		}
			for ($col_num=0; $col_num < $numCols; $col_num++) { // Run through $numCols number of columns each row
				echo "<td>";
				
				if ($columnList[$col_num] == "username") { // If it's a username column
					//echo "Username!";
					//echo $rows[$col_num]; // Echo out username
					echo '<a data-reveal-id="newMessage" href="#" id="message' . $rows[3] . '"class="?username=' . $rows[3] . '">' . $rows[$col_num] . '</a>';
				} elseif ($columnList[$col_num] == "assignedTo") { // If it's an AssignedTo column
					//echo "AssignedTo!";
					if ($rows[$col_num]) { // If a help desk person is listed...
						$assignedTo = $rows[$col_num]; // Store assignedTo userID
						// Query SQL for the username of specified help desk person since we cannot join ticket table on username twice, use separate query
						$assignedToSQL = "SELECT user.username FROM user JOIN ticket ON ticket.assignedTo=user.userID WHERE ticket.assignedTo='$assignedTo'";
						$assignedToResult = mysqli_query($dbc,$assignedToSQL);
						while ($assignedToRows=mysqli_fetch_array($assignedToResult)) { // While more rows exist...	
							// Echo out username of help desk person
							echo $assignedToRows[0];
							// Not sure why this loops, so breka out after listing help desk username once
							break;
						}
					} else {
						//echo "UNASSIGNED";
						// Make a link to allow assigning ticket to self
						echo '<span id="assign' . $rows[0] . '"><a data-reveal-id="assign" href="#" class="tiny success button" id="?ticketID=' . $rows[0] . '">Click to Assign</a></span>';
					}
				} else { // For everything else...
					echo $rows[$col_num]; // Spit out data for specified column on this row
				}
				echo "</td>";
			}
			echo "</tr>";
		}
		echo "</tbody>";

		// Close table
		echo "</table>";
		echo "</div>";
	} else { // SQL query returned no records
		pagination_links($numRows,$rowsPerPage,$page);
		search_filter();
		echo "<p>No Results.</p>";
	}

	// Include modal divs for view/edit/delete functions
	echo '<div id="viewRecord" class="reveal-modal" data-reveal></div>';
	echo '<div id="editRecord" class="reveal-modal" data-reveal></div>';
	echo '<div id="deleteRecord" class="reveal-modal" data-reveal></div>';
	echo '<div id="assign" class="reveal-modal" data-reveal>Testing</div>';
	
}


// ====================== Page Content Start ================================

//$sql = "SELECT userID AS 'User ID', username AS Username, email AS Email, firstName AS First, lastName AS Last FROM user";

// This function is all that's needed to call a full table
// Just pass in an SQL statement and the name of the table you want it labeled as

//output_table($sql,"User_Table");

?>

</div>
</body>
</html>
