<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// اتصال بقاعدة البيانات
$conn = mysqli_connect("localhost", "root", "", "eef");
if (!$conn) {
    die("فشل الاتصال بقاعدة البيانات: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");

// استعلام للحصول على قوائم التصفية
$sex_query = "SELECT * FROM sex ORDER BY sex_id";
$events_query = "SELECT * FROM events ORDER BY events_id";
$het_query = "SELECT * FROM het ORDER BY het_id";

// التحقق من نجاح الاستعلامات
$sex_result = mysqli_query($conn, $sex_query);
if (!$sex_result) {
    die("خطأ في استعلام الجنس: " . mysqli_error($conn));
}

$events_result = mysqli_query($conn, $events_query);
if (!$events_result) {
    die("خطأ في استعلام المسابقات: " . mysqli_error($conn));
}

$het_result = mysqli_query($conn, $het_query);
if (!$het_result) {
    die("خطأ في استعلام التصفيات: " . mysqli_error($conn));
}

// حفظ القيم المحددة
$selected_sex = isset($_GET['sex']) ? $_GET['sex'] : '';
$selected_event = isset($_GET['event']) ? $_GET['event'] : '';
$selected_het = isset($_GET['het']) ? $_GET['het'] : '';

// طباعة القيم للتحقق من البيانات
echo "<!-- عدد نتائج الجنس: " . mysqli_num_rows($sex_result) . " -->";
echo "<!-- عدد نتائج المسابقات: " . mysqli_num_rows($events_result) . " -->";
echo "<!-- عدد نتائج التصفيات: " . mysqli_num_rows($het_result) . " -->";

?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>نتائج بطولة لجمهورية تحت 18</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Cairo', sans-serif;
        }
        .filter-section {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .results-table {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        th {
            background-color: #0d6efd;
            color: white;
            white-space: nowrap;
        }
        .table > :not(caption) > * > * {
            padding: 12px;
        }
        .no-results {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <h1 class="text-center mb-4">نتائج بطولة الجمهورية تحت 16 - الاسكندرية 2024</h1>
        
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">الجنس</label>
                    <select name="sex" class="form-select">
                        <option value="">الكل</option>
                        <?php 
                        mysqli_data_seek($sex_result, 0);
                        while($sex = mysqli_fetch_assoc($sex_result)): 
                        ?>
                            <option value="<?php echo $sex['sex_id']; ?>" <?php echo ($selected_sex == $sex['sex_id']) ? 'selected' : ''; ?>>
                                <?php echo $sex['sex_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">المسابقة</label>
                    <select name="event" class="form-select">
                        <option value="">الكل</option>
                        <?php 
                        mysqli_data_seek($events_result, 0);
                        while($event = mysqli_fetch_assoc($events_result)): 
                        ?>
                            <option value="<?php echo $event['events_id']; ?>" <?php echo ($selected_event == $event['events_id']) ? 'selected' : ''; ?>>
                                <?php echo $event['events']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">التصفية</label>
                    <select name="het" class="form-select">
                        <option value="">الكل</option>
                        <?php 
                        mysqli_data_seek($het_result, 0);
                        while($het = mysqli_fetch_assoc($het_result)): 
                        ?>
                            <option value="<?php echo $het['het_id']; ?>" <?php echo ($selected_het == $het['het_id']) ? 'selected' : ''; ?>>
                                <?php echo $het['het_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">بحث</button>
                    <a href="results.php" class="btn btn-secondary">إعادة تعيين</a>
                </div>
            </form>
        </div>

        <div class="results-table">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>الترتيب</th>
                        <th>الاسم</th>
                        <th>النادي</th>
                        <th>المحافظة</th>
                        <th>المسابقة</th>
                        <th>النتيجة</th>
                        <th>التصفية</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // بناء استعلام البحث
                    $query = "SELECT e.*, c.club_id, c.club, ev.events_id, ev.events AS event_name, h.het_name, s.sex_name 
                             FROM eef e 
                             INNER JOIN club c ON e.club_id = c.club_id
                             LEFT JOIN events ev ON e.events_id = ev.events_id
                             LEFT JOIN het h ON e.het_id = h.het_id
                             LEFT JOIN sex s ON e.sex_id = s.sex_id
                             WHERE 1=1";

                    if(!empty($selected_sex)) {
                        $query .= " AND e.sex_id = '" . mysqli_real_escape_string($conn, $selected_sex) . "'";
                    }
                    if(!empty($selected_event)) {
                        $query .= " AND e.events_id = '" . mysqli_real_escape_string($conn, $selected_event) . "'";
                    }
                    if(!empty($selected_het)) {
                        $query .= " AND e.het_id = '" . mysqli_real_escape_string($conn, $selected_het) . "'";
                    }

                    $query .= " ORDER BY e.events_id, e.het_id, CAST(NULLIF(REGEXP_REPLACE(e.Rank, '[^0-9]', ''), '') AS UNSIGNED)";
                    
                    $result = mysqli_query($conn, $query);
                    if(!$result) {
                        die("خطأ في استعلام النتائج: " . mysqli_error($conn));
                    }

                    if(mysqli_num_rows($result) > 0):
                        while($row = mysqli_fetch_assoc($result)):
                    ?>
                    <tr>
                        <td><?php echo $row['Rank']; ?></td>
                        <td><?php echo $row['E-name']; ?></td>
                        <td><?php echo $row['club']; ?></td>
                        <td><?php echo $row['gov']; ?></td>
                        <td><?php echo $row['event_name']; ?></td>
                        <td><?php echo $row['results']; ?></td>
                        <td><?php echo $row['het_name']; ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="7" class="no-results">لا توجد نتائج</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 