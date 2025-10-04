<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: aliceblue;
            font-family: 'Franklin Gothic Medium', sans-serif;
            padding: 0;
            transition: margin-left 0.3s ease;
        }

        h1 {
            font-size: 30px;
            text-align: center;
            margin-top: 80px;
        }

        .content {
            margin: 90px auto 30px auto;
            width: 95%;
            max-width: 1200px;
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        /* Shift content when drawer opens */
        body.drawer-open .content {
            margin-left: 250px; /* drawer width */
            width: calc(95% - 250px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            background-color: #fff;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px 15px;
            font-size: 14px;
        }

        th {
            font-size: 16px;
            background-color: #00509e;
            color: white;
        }

        .blocked {
            color: red;
        }

        .unblocked {
            color: black;
        }

        button {
            background-color: #003366;
            color: white;
            font-size: 14px;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 6px;
        }

        #search {
            margin: 20px 0;
            padding: 8px;
            width: 100%;
            max-width: 300px;
            box-sizing: border-box;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .pagination {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .pagination a {
            padding: 8px 12px;
            border: 1px solid #003366;
            margin: 3px;
            text-decoration: none;
            color: #003366;
            font-size: 14px;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .pagination a.active {
            background-color: #00509e;
            color: white;
        }

        .pagination a:hover {
            background-color: #00509e;
            color: white;
        }
        /* Mobile adjustments */
@media (max-width: 768px) {
    body.drawer-open .content {
        margin-left: 0;
        width: 100%;
    }

    /* Make table responsive */
    .content table {
        display: block;
        width: 100%;
        overflow-x: auto;
        white-space: nowrap;
    }

    table th, table td {
        padding: 6px 8px;
        font-size: 12px;
    }
}


    </style>
</head>
<body>
<?php include 'navbar_admin.php'; ?>

<div class="content">
    
    <input type="text" id="search" placeholder="Search by username..." />

    <table id='t1'>
        <thead>
            <tr>
                <th>SNo</th>
                <th>USERNAME</th>
                <th>EMAIL</th>
                <th>PHONE</th>
                <th>ADDRESS</th>
            </tr>
        </thead>
        <tbody id="userTable">
           <?php
$conn = mysqli_connect("localhost", "root", "", "kcpl");
$limit = 5; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_sql = "SELECT COUNT(*) as total FROM users";
$total_result = mysqli_query($conn, $total_sql);
$total_row = mysqli_fetch_assoc($total_result);
$total_users = $total_row['total'];
$total_pages = ceil($total_users / $limit);

$sql = "
    SELECT u.id, u.name, u.email, u.is_blocked,
           CONCAT(a.address_line1, ' ', IFNULL(a.address_line2, ''), ', ', a.city, ', ', a.state, ' - ', a.postal_code) AS full_address,
           a.phone
    FROM users u
    LEFT JOIN addresses a ON u.id = a.user_id
    ORDER BY u.id
    LIMIT $limit OFFSET $offset
";

$sql_exe = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($sql_exe)) {
    $rowClass = $row['is_blocked'] ? 'blocked' : 'unblocked';
    echo "<tr class='$rowClass'>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td>{$row['email']}</td>";
    echo "<td>{$row['phone']}</td>";
    echo "<td>{$row['full_address']}</td>";
    echo "</tr>";
}

$conn->close();
?>
        </tbody>
    </table>

    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $("#search").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#userTable tr").filter(function() {
            $(this).toggle($(this).find("td:nth-child(2)").text().toLowerCase().indexOf(value) > -1)
        });
    });
});
</script>
</body>
</html>
