<?php
$dbHost = 'localhost';
$dbUser = 'root';
$dbPassword = '';
$dbName = 'club';
$conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);
if ($conn->connect_error) {
  die('Connection failed: ' . $conn->connect_error);
}

if (isset($_POST['query']) && !empty($_POST['query'])) {
  $userQuery = $_POST['query'];

  if (isset($_POST['selectedTable'])) {
    $selectedTable = $_POST['selectedTable'];
  } else {
    $selectedTable = 'register'; //default table
  }

  echo '<input type="hidden" id="selectedTableTemp" name="selectedTableTemp" value="' . htmlspecialchars($selectedTable) . '" readonly>';
  $queries = explode(';', $userQuery);

  foreach ($queries as $query) {
    $query = trim($query);

    if (!empty($query)) {
      $result = mysqli_query($conn, $query);
      echo "<div class='box m-0 p-0 mb-3'>";
      echo "<p class='alert alert-primary m-0 p-1' style='font-weight: bold; font-size: 16px;'>Executing Query:</p>";
      echo "<pre class='alert alert-info m-0 p-0' style='margin: 0; padding: 0; white-space: pre-wrap;'>$query;</pre>";

      if (!$result) {
        // Handle and send error messages to JavaScript
        $errorMessage = mysqli_error($conn);
        echo "<script>displayError(" . json_encode($errorMessage) . ");</script>"; // Call the JavaScript function
      } else {
        $output = '';

        if (stripos($query, 'SELECT') === 0) {
          $output .= "<div class='alert alert-success m-0 p-0' role='alert'>Query executed successfully!</div>";
          $output .= "<div class='table-responsive result-table m-0 p-0'>";
          $output .= "<table class='table table-bordered table-striped'>";
          $output .= "<thead class='thead-dark'>";
          $output .= "<tr>";
          $fieldinfo = mysqli_fetch_fields($result);
          foreach ($fieldinfo as $field) {
            $output .= "<th>{$field->name}</th>";
          }
          $output .= "</tr>";
          $output .= "</thead>";
          $output .= "<tbody>";

          while ($row = mysqli_fetch_assoc($result)) {
            $output .= "<tr data-row-id='{$row['id']}'>";
            foreach ($row as $key => $value) {
              $output .= "<td class='editable-cell' data-column-name='{$key}' ondblclick='enableEdit(this)' onblur='saveEdit(this)' contenteditable='false' m-0 p-0>" . $value . "</td>";
            }
            $output .= "</tr>";
          }
          $output .= "</tbody>";
          $output .= "</table>";
          $output .= "</div>";
        } else {
          $affectedRows = mysqli_affected_rows($conn);
          $output .= "<div class='alert alert-success m-0 p-0' role='alert'>Query executed successfully!</div>";
          $output .= "<div class='alert alert-info m-0 p-0' role='alert'>Affected Rows: $affectedRows</div>";
        }
        echo $output;
      }
      echo "</div>";
    }
  }
}

// table dynamic updating
if (isset($_POST['newValue']) && isset($_POST['rowId']) && isset($_POST['columnName'])) {
  $newValue = mysqli_real_escape_string($conn, $_POST['newValue']);
  $rowId = mysqli_real_escape_string($conn, $_POST['rowId']);
  $columnName = mysqli_real_escape_string($conn, $_POST['columnName']);
  $selectedTable = mysqli_real_escape_string($conn, $_POST['selectedTable']);
  $updateQuery = "UPDATE $selectedTable SET $columnName = '$newValue' WHERE id = $rowId";
  if (mysqli_query($conn, $updateQuery)) {
    $response = $updateQuery;
  } else {
    $response = 'Error updating cell value: ' . mysqli_error($conn);
  }
  echo $response;
  $conn->close();
  exit;
}
?>

<!DOCTYPE html>
<html>

<head>
  <link rel="icon" href="img/AIML LOGO WHITE.png" type="png">
  <title>Private Page</title>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
  <meta name="format-detection" content="telephone=no" />
  <meta name="HandheldFriendly" content="true" />
  <meta name="MobileOptimized" content="320" />
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>

<body>
  <div class="container m-0 p-0">
    <div class="card bg-dark mt-2">
      <div class="card-header p-0 ml-1 pt-1">
        <h3 class="card-title p-0 mb-2 text-white">Enter SQL Query:</h3>
      </div>

      <form id="sql-form" method="post" action="">
        <div class="form-row flex-0.5 m-0 p-0">
          <div class="col-12 col-md-6 mb-3">
            <select class="form-control text-white bg-dark" name="selectedTable" id="selectedTable">
              <option value="" disabled selected>Table Name</option>
              <option value="register">Register</option>
              <option value="events">Events</option>
            </select>
          </div>
          <div class="col-12 col-md-6 mb-3">
            <select class="form-control text-white bg-dark" name="tableQuery" id="tableQuery">
              <option value="" selected>Operation</option>
              <option value="select">SELECT</option>
              <option value="update">UPDATE</option>
              <option value="insert">INSERT</option>
              <option value="delete">DELETE</option>
              <option value="credits">CREDITS</option>
              <option value="drop">DROP</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <textarea class="form-control text-white bg-dark" placeholder="Enter Query" name="query" id="query" rows="5"
            style="font-family: monospace;"></textarea>
        </div>
        <div class="form-group">
          <button tabindex="1" type="submit" id="submit" class="btn btn-primary btn-block">Execute
            Query</button>
        </div>
      </form>
    </div>
  </div>

  <div class="output-container mt-4">
    <p class="alert alert-dark m-0 p-0" role="alert">Query Logs:</p>
  </div>
  <div class="error-messages mt-4">
    <p class="alert alert-dark m-0 p-0" role="alert">Error Logs:</p>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    var outputContainer = document.querySelector('.output-container');
    const errorMessagesDiv = document.querySelector('.error-messages');

    selectedTable = document.getElementById("selectedTable").value;
    document.getElementById("selectedTable").addEventListener("change", function () {
      var selectedValue = this.value;
      document.getElementById("query").value = selectedValue;
    });
    document.getElementById("tableQuery").addEventListener("change", function () {
      var selectedValue = this.value;
      var sqlQuery = '';
      selectedTable = document.getElementById("selectedTable").value;

      if (selectedValue === "update") {
        sqlQuery = `UPDATE ${selectedTable}
SET column1 = value1, column2 = value2
WHERE condition = value;`;
      } else if (selectedValue === "delete") {
        sqlQuery = `DELETE FROM ${selectedTable}
WHERE condition = value;`;
      } else if (selectedValue === "drop") {
        sqlQuery = `DROP TABLE ${selectedTable};`;
      } else if (selectedValue === "select") {
        sqlQuery = `SELECT * FROM ${selectedTable};`;
      } else if (selectedValue === "insert") {
        sqlQuery = `INSERT INTO ${selectedTable} (usn, fullname, password, phone, email, year, club, credits, userpost, whatsapp, instagram, linkedin, github, gender, created_at)
VALUES (
'usn_value', 'fullname_value', 'password_value', 'phone_value', 'email_value', 'year_value', 'club_value', 'credits_value', 'userpost_value', 'whatsapp_value', 'instagram_value', 'linkedin_value', 'github_value', 'gender_value', NOW());`;
      }
      else if (selectedValue === "credits") {
        sqlQuery = `UPDATE ${selectedTable} SET credits = 'credits_value' WHERE usn = '4AI21AI006';`;
      }
      document.getElementById("query").value = sqlQuery;
    });

    function createOutput(data) {
      const outputContainer = document.querySelector('.output-container');
      if (outputContainer) {
        const output = document.createElement('pre');
        output.style.whiteSpace = 'pre-wrap';
        output.style.margin = 0;
        output.style.padding = 0;
        output.className = 'output';
        output.textContent = data + ";";
        outputContainer.appendChild(output);
      }
    }

    function displayError(errorMessage) {
      const errorMessagesDiv = document.querySelector('.error-messages');
      const errorDiv = document.createElement("div");
      errorDiv.className = "alert alert-danger m-0 p-0";
      errorDiv.textContent = "Error executing the query: \n" + errorMessage;
      errorMessagesDiv.appendChild(errorDiv);
    }

    document.getElementById("executeDaily").addEventListener("click", function () {
      var query = document.getElementById("query").value;
      if (query) {
        const data = {
          query: query,
          selectedTable: selectedTable
        };
        $.ajax({
          type: 'POST',
          data: data,
          success: function (data) {
            if (data.includes("Warning") || data.includes("Error")) {
              console.error('Error 1');
              displayError(data);
            } else {
              console.log('Query executed successfully!');
              createOutput(data);
            }
          },
          error: function (error) {
            console.error('Error 2');
            displayError(error.responseText);
          }
        });
      }
    });

    function enableEdit(cell) {
      cell.setAttribute('contenteditable', true);
      cell.style.backgroundColor = 'lightyellow';
      cell.style.color = 'black';
      cell.focus();
    }

    function saveEdit(cell) {
      cell.setAttribute('contenteditable', false);
      cell.style.backgroundColor = '#454545';
      cell.style.color = 'white';
      saveCellValue(cell);
    }

    function saveCellValue(cell) {
      const newValue = cell.textContent;
      const rowId = cell.closest('tr').getAttribute('data-row-id');
      const columnName = cell.dataset.columnName;
      const selectedTable = document.getElementById('selectedTableTemp').value;
      const data = {
        newValue: newValue,
        rowId: rowId,
        columnName: columnName,
        selectedTable: selectedTable
      };

      $.ajax({
        type: 'POST',
        data: data,
        success: function (data) {
          if (data.includes("Warning") || data.includes("Error")) {
            console.error('Error 1');
            displayError(data);
          } else {
            console.log('Cell updated successfully!');
            createOutput(data);
          }
        },
        error: function (error) {
          console.error('Error 3');
          displayError(error.responseText);
        }
      });
    }

    $('.editable-cell').on('dblclick', function () {
      enableEdit(this);
    });

    $('.editable-cell').on('blur', function () {
      saveEdit(this);
    });
  </script>


  <style>
    /* MOBILE */
    @media (max-width: 767px) {
      .container {
        max-width: 100%;
      }
    }

    /* DESKTOP */
    @media (min-width: 768px) {
      .container {
        max-width: 50%;
      }
    }

    .table td,
    .table th {
      vertical-align: middle;
    }

    b,
    #text {
      color: white;
      background-color: #3c4b64;
    }

    ::-webkit-scrollbar {
      height: 15px;
    }

    ::-webkit-scrollbar-thumb {
      background: #545454;
    }

    ::-webkit-scrollbar-thumb:horizontal {
      height: auto;
    }

    ::-webkit-scrollbar-thumb:vertical {
      display: none;
    }

    .table-container {
      max-height: 600px;
      overflow-y: scroll;
    }

    body {
      height: 100vh;
    }

    .result-table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid #ddd;
    }

    .result-table th {
      background-color: #545454;
      color: white;
      text-align: left;
      padding: 10px;
    }

    .result-table tr {
      border-bottom: 2px solid #000;
    }

    .result-table td {
      padding: 5px;
      text-align: left;
      border: 1px solid #000;
    }

    .editable-cell {
      background-color: #f7f7f7;
      cursor: pointer;
      transition: background-color 0.5s;
    }

    .editable-cell:hover {
      background-color: #e3e3e3;
    }
  </style>
</body>

</html>