<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Gün, Ay ve tarihleri türkçeye çevirmek için kullanacağız
function turkceTarih($format, $dateStr)
{
    $en = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
        'Mon',
        'Tue',
        'Wed',
        'Thu',
        'Fri',
        'Sat',
        'Sun',
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December',
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec'
    ];

    $tr = [
        'Pazartesi',
        'Salı',
        'Çarşamba',
        'Perşembe',
        'Cuma',
        'Cumartesi',
        'Pazar',
        'Pts',
        'Sal',
        'Çar',
        'Per',
        'Cum',
        'Cts',
        'Paz',
        'Ocak',
        'Şubat',
        'Mart',
        'Nisan',
        'Mayıs',
        'Haziran',
        'Temmuz',
        'Ağustos',
        'Eylül',
        'Ekim',
        'Kasım',
        'Aralık',
        'Oca',
        'Şub',
        'Mar',
        'Nis',
        'May',
        'Haz',
        'Tem',
        'Ağu',
        'Eyl',
        'Eki',
        'Kas',
        'Ara'
    ];

    return str_replace($en, $tr, date($format, strtotime($dateStr)));
}


// Girilen tarihler arasındaki ayları veriyor bize
function getMonthsBetween($startDate, $endDate)
{
    $months = [];
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $end->modify('+1 day');

    while ($start <= $end) {
        $key = $start->format('Y-m'); // 2025-04
        $label = $start->format('F Y'); // April 2025
        $months[$key] = $label;
        $start->modify('first day of next month');
    }

    return $months;
}



$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
if ($hotel_id <= 0) {
    die("Geçersiz ID");
}



//Seçilen kontrata göre ay seçilen select i ayarlıyoruz
if (isset($_POST['ajax']) && $_POST["ajax"] === "get_details" && isset($_POST['contract_id'])) {

    $contract_id = intval($_POST['contract_id']);


    $sql = "SELECT start_date, finish_date FROM contracts WHERE contract_id = ? AND hotel_id = ?";
    $stmt = $baglanti->prepare($sql);
    $stmt->bind_param("ii", $contract_id, $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $startDate = $row["start_date"];
        $endDate = $row["finish_date"];
        $months_select = getMonthsBetween($startDate, $endDate);

        echo '<option value="">Ay seçiniz</option>';
        foreach ($months_select as $val => $label) {
            echo "<option value='$val'>" . htmlspecialchars(turkceTarih('M Y', $label)) . "</option>";
        }
    } else {
        echo '<option value="">Tarih bulunamadı</option>';
    }

    exit;
}

//Seçilen aya göre o aya ait kayıtları getiriyoruz
if (isset($_POST['ajax']) && $_POST['ajax'] === 'get_days_of_selected_month' && isset($_POST['month']) && isset($_POST['contract_id'])) {

    $selectedMonth = $_POST['month'];
    $contract_id = $_POST['contract_id'];

    $dates = [];
    $sql = 'SELECT * FROM stop_sale WHERE contract_id=? AND hotel_id=?';
    $stmt = $baglanti->prepare($sql);
    $stmt->bind_param('ii', $contract_id, $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $start_date = new DateTime($row['from_date']);
        $end_date = new DateTime($row['to_date']);
        $end_date->modify('+1 day'); // bitiş gününü dahil etmek
        $recording_days = [];
        if ($row['monday']) $recording_days[] = 'monday';
        if ($row['tuesday']) $recording_days[] = 'tuesday';
        if ($row['wednesday']) $recording_days[] = 'wednesday';
        if ($row['thursday']) $recording_days[] = 'thursday';
        if ($row['friday']) $recording_days[] = 'friday';
        if ($row['saturday']) $recording_days[] = 'saturday';
        if ($row['sunday']) $recording_days[] = 'sunday';


        // Seçilen tarihler arasındaki kayıtlı günleri bir diziye atıyoruz.
        while ($start_date < $end_date) {
            $current_month = $start_date->format('Y-m');
            $dayName = strtolower($start_date->format('l'));

            if ($current_month == $selectedMonth && in_array($dayName, $recording_days)) {
                $dates[] = $start_date->format('Y-m-d');
            }

            $start_date->modify('+1 day');
        }

        $sql = "SELECT n.room_type_name, r.open, r.close, n.id
    FROM stop_sale_rooms r
    JOIN room_type_name n ON r.room_type_id = n.id
    WHERE r.stop_sale_id = ?";
        $stmt = $baglanti->prepare($sql);
        $stmt->bind_param("i", $row["id"]);
        $stmt->execute();
        $result_rooms = $stmt->get_result();

        $room_rows = [];
        while ($row_r = $result_rooms->fetch_assoc()) {
            $room_rows[] = $row_r; // tüm satırları array'e ekle
        }


        $today = date("Y-m-d");
        $disabled = "";
        if (!empty($dates)) {


            echo "<div class='price-container'>";

            foreach ($dates as $date) {
                echo "<h5 id='title' class='alert-info'>" .  htmlspecialchars(turkceTarih('d F Y (l)', $date)) . "</h5>";
                if ($date < $today) {
                    $disabled = " disabled";
                } else {
                    $disabled = ""; // yeni tarihler için disabled kaldırılmalı
                }
                foreach ($room_rows as $row_rooms) {
                    //Oda kartları
                    echo "<div id='price-field'>";
                    echo "<h5>" . $row_rooms['room_type_name'] . "</h5>";
                    if ($row_rooms['open'] === 1 && $row_rooms['close'] === 0) {
                        echo "<p><input type='radio' name='same_radio_{$date}_{$row_rooms['id']}' checked" . $disabled . "> Open</p>";
                        echo "<p><input type='radio' name='same_radio_{$date}_{$row_rooms['id']}'" . $disabled . "> Close</p>";
                    } else if ($row_rooms['open'] === 0 && $row_rooms['close'] === 1) {
                        echo "<p><input type='radio' name='same_radio_{$date}_{$row_rooms['id']}'" . $disabled . "> Open</p>";
                        echo "<p><input type='radio' name='same_radio_{$date}_{$row_rooms['id']}' checked" . $disabled . "> Close</p>";
                    }

                    echo "</div>";
                }
            }

            echo "</div>";
        } else {
            echo "<div class='alert alert-warning'>Bu ay için kayıtlı gün yok.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Kayıt bulunamadı</div>";
    }


    exit;
}

//Kontrat seçildikten sonra odaların gelmesi
if (isset($_POST["ajax"]) && $_POST["ajax"] === "get_contract" && isset($_POST["contract_id"])) {

    $contract_id = $_POST["contract_id"];

    $checkSql = "SELECT contract_id FROM stop_sale WHERE hotel_id = ? AND contract_id = ?";
    $checkStmt = $baglanti->prepare($checkSql);
    $checkStmt->bind_param("ii", $hotel_id, $contract_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows > 0) {
        echo "<div class='alert alert-danger'> Bu kontrata ait kayıt bulunmaktadır. Lütfen başka bir kontrat seçiniz.</div>";
        echo "<input type='hidden' id='disable_check' value ='1'>";
        exit;
    } else {

        $sql_dates = "SELECT DISTINCT start_date, finish_date FROM contracts WHERE contract_id=? AND hotel_id = ?";
        $stmt_dates = $baglanti->prepare($sql_dates);
        $stmt_dates->bind_param("ii", $contract_id, $hotel_id);
        $stmt_dates->execute();
        $result_dates = $stmt_dates->get_result();
        $row_dates = $result_dates->fetch_assoc();

        echo "<table class='table dotted-rows'>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <p>From</p><input type='date' name='date_from' min='" . $row_dates['start_date'] . "' max='" . $row_dates['finish_date'] . "'  class='form-control checkout' required>
                                                </td>
                                                <td>
                                                    <p>To</p><input type='date' name='date_to' min='" . $row_dates['start_date'] . "' max='" . $row_dates['finish_date'] . "' class='form-control checkout' required>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <br>";
        echo "<table class='table table-bordered table-striped'>
                                        <thead>
                                            <tr>
                                                <th>Room Type</th>
                                                <th>Open</th>
                                                <th>Close</th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td> </td>
                                                <div class='form-group'>
                                                    <td>
                                                        <label><input type='radio' name='selectAll' id='selectOpen'> Tümünü Open Seç</label>
                                                    </td>
                                                    <td>
                                                        <label><input type='radio' name='selectAll' id='selectClose'> Tümünü Close Seç</label>
                                                    </td>
                                                </div>
                                            </tr>";

        $sql = "SELECT DISTINCT c.room_id, n.room_type_name
                                            FROM contracts c
                                            JOIN room_type_name n ON c.room_id = n.id
                                            WHERE c.hotel_id=? AND c.contract_id=?";
        $stmt = $baglanti->prepare($sql);
        $stmt->bind_param("ii", $hotel_id, $contract_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $roomCount = 0;
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                    <td>
                      <label>" . $row['room_type_name'] . "</label>
                      <input type='hidden' name='room_id_" . $roomCount . "' value='" . $row['room_id'] . "'>
                      </td>
                      <td>
                      <p><input type='radio' name='radio_" . $roomCount . "' value='1'> Open</p>
                      </td>
                      <td>
                      <p><input type='radio' name='radio_" . $roomCount . "' value='0'> Close</p>
                      </td>
                      </tr>";

                $roomCount++;
            }
        }
        echo "</tbody>
              </table>
          <input type='hidden' name='room_count' value='$roomCount'>";

        exit;
    }
}


?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stop Sale</title>
    <style>
        /*checkbox için*/
        .day-card {
            background-color: #f1f1f1;
            padding: 15px 25px;
            /* artırıldı */
            border-radius: 8px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            /* yazı biraz büyüdü */
            height: 100%;
            min-width: 130px;
            /* kutu genişliği sabitlendi */
        }

        .day-card input {
            margin-right: 10px;
            transform: scale(1.2);
            /* checkbox biraz büyüdü */
        }

        .content-section {
            display: none;
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #ccc;
        }

        .price-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            /* kutular arası boşluk */
        }

        #price-field {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: calc(20% - 10px);
            /* 4 kutu için toplam genişlik (daha küçük yapıldı) */
            min-width: 100px;
            /* Minimum genişlik */
            padding: 8px;
            /* Padding değerini küçülttük */
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
            box-sizing: border-box;
        }

        #title {
            /* background-color: #dc3545; */
            color: white;
            padding: 10px;
            border-radius: 5px;
            display: inline-block;
            width: 100%;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fa-solid fa-person-walking-luggage"></i> Stop Sale</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Rezervasyon Modülü</a></li>
                            <li class="breadcrumb-item active">Stop Sale</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <section class="content">
            <!-- ./row -->
            <div class="row">
                <div class="col-md-12">

                    <div class="callout callout-info">
                        <div class="row no-print">
                            <div class="col-12">
                                <a href="stopsalelist.php?hotel_id=<?php echo $hotel_id; ?>"><button class="btn btn-danger"><i class="fa-solid fa-angle-left"></i> Geri</button></a>

                            </div>
                        </div>
                    </div>

                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"></h3>
                        </div>


                        <div class='card-body'>
                            <!-- <div class="alert alert-warning" role="alert">
                                In this section you can stop sales for the room types according to specific dates or day by day. </div> -->

                            <div class="card p-3" style="max-width: 100% ;">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="secim" id="option2" value="option2">
                                    <label class="form-check-label" for="option2">
                                        Date Range
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="secim" id="option1" value="option1" checked>
                                    <label class="form-check-label" for="option1">
                                        Day by Day
                                    </label>
                                </div>

                            </div>


                            <!-- Day by Day -->
                            <div id="daybyday" class="content-section">

                                <?php
                                $sql = "SELECT DISTINCT s.contract_id, c.contract_name
                                FROM stop_sale s 
                                JOIN contracts c ON c.contract_id = s.contract_id
                                WHERE s.hotel_id=?";
                                $stmt = $baglanti->prepare($sql);
                                $stmt->bind_param("i", $hotel_id);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) { ?>
                                    <div class="form-group border p-3 rounded">
                                        <h4>Contracts</h4>
                                        <select name="contract_id" class="form-control" id="contractSelect">
                                            <option value="0">Kontrat seçiniz</option>
                                            <?php while ($row = $result->fetch_assoc()) { ?>
                                                <option value="<?= $row["contract_id"] ?>" class="form-control"><?= $row["contract_name"] ?></option>

                                            <?php } ?>
                                        </select>
                                    </div>
                                <?php } else {

                                    echo "<div class='alert alert-danger'>Mevcut kontrat bulunamadı</div>";
                                    echo "<script>document.getElementById('daybyday').style.display = 'block';</script>";
                                    //exit;
                                } ?>

                                <br>

                                <div class="form-group border p-3 rounded">
                                    <!-- <h4>Ay Seçiniz</h4> -->
                                    <select id="monthSelect" class="form-control">
                                        <option value="">Önce kontrat seçiniz</option>
                                    </select>
                                </div>

                                <div id="monthResults" class="mt-3"></div>



                            </div>



                            <!-- Date Range  -->
                            <div id="daterange" class="content-section">
                                <form action="" method="post">
                                    
                                    <?php
                                    $sql = "SELECT DISTINCT contract_id, contract_name FROM contracts WHERE hotel_id=?";
                                    $stmt = $baglanti->prepare($sql);
                                    $stmt->bind_param("i", $hotel_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    if ($result->num_rows > 0) { ?>
                                        <div class="form-group border p-3 rounded">
                                            <h4>Contracts</h4>
                                            <select name="contract_id" class="form-control contract-select" id="contract_name">
                                                <option value="0">Kontrat seçiniz</option>
                                                <?php while ($row = $result->fetch_assoc()) { ?>
                                                    <option value="<?= $row["contract_id"] ?>" class="form-control"><?= $row["contract_name"] ?></option>

                                                <?php } ?>
                                            </select>
                                        </div>
                                    <?php } else { ?>
                                        <div class="alert alert-danger"> Bu otele ait kayıtlı kontrat bulunmamaktadır</div>
                                    <?php } ?>
                                    <br>


                                    <div class="container mt-3">
                                        <div class="row g-2">
                                            <div class="col-auto">
                                                <div class="day-card">
                                                    <input type="checkbox" name="monday" value="1" checked> Monday
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="day-card">
                                                    <input type="checkbox" name="tuesday" value="1" checked> Tuesday
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="day-card">
                                                    <input type="checkbox" name="wednesday" value="1" checked> Wednesday
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="day-card">
                                                    <input type="checkbox" name="thursday" value="1" checked> Thursday
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="day-card">
                                                    <input type="checkbox" name="friday" value="1" checked> Friday
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="day-card">
                                                    <input type="checkbox" name="saturday" value="1" checked> Saturday
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="day-card">
                                                    <input type="checkbox" name="sunday" value="1" checked> Sunday
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <br>



                                    <div class="table-rooms"></div>

                                    <button type="submit" id="saveButton" style="width: 100%;" class="btn btn-success" disabled> Save </button>
                                </form>

                            </div>
                            <?php

                            if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                                $room_count = $_POST["room_count"];
                                $contract_id = intval($_POST['contract_id']);
                                $date_from = $_POST['date_from'];
                                $date_to = $_POST['date_to'];
                                $monday = isset($_POST["monday"]) ? 1 : 0;
                                $tuesday = isset($_POST["tuesday"]) ? 1 : 0;
                                $wednesday = isset($_POST["wednesday"]) ? 1 : 0;
                                $thursday = isset($_POST["thursday"]) ? 1 : 0;
                                $friday = isset($_POST["friday"]) ? 1 : 0;
                                $saturday = isset($_POST["saturday"]) ? 1 : 0;
                                $sunday = isset($_POST["sunday"]) ? 1 : 0;



                                // Kayıt işlemlerini yapıyoruz

                                $sql = "SELECT * from stop_sale WHERE contract_id = ? AND hotel_id = ?";
                                $stmt = $baglanti->prepare($sql);
                                $stmt->bind_param("ii", $contract_id, $hotel_id);
                                $stmt->execute();
                                //$stmt->close();
                                $result = $stmt->get_result();
                                if ($result->num_rows > 0) {
                                    $row = $result->fetch_assoc();

                                    $stop_sale_id = $row['id'];
                                    $sqlUpdate = "UPDATE stop_sale 
                                    SET monday=?, tuesday=?, wednesday=?, thursday=?, friday=?, saturday=?, sunday=?, from_date=?, to_date=? 
                                    WHERE id=?";
                                    $stmtUpdate = $baglanti->prepare($sqlUpdate);
                                    $stmtUpdate->bind_param("iiiiiiissi", $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday, $date_from, $date_to, $stop_sale_id);
                                    $stmtUpdate->execute();
                                    $stmtUpdate->close();

                                    //delete rooms
                                    $sqlDelete = "DELETE FROM stop_sale_rooms WHERE stop_sale_id = ?";
                                    $stmtDelete = $baglanti->prepare($sqlDelete);
                                    $stmtDelete->bind_param("i", $stop_sale_id);
                                    $stmtDelete->execute();
                                    $stmtDelete->close();
                                } else {

                                    $sql = "INSERT INTO stop_sale(contract_id, hotel_id, monday, tuesday, wednesday, thursday, friday, saturday, sunday, from_date, to_date) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                    $stmt = $baglanti->prepare($sql);
                                    $stmt->bind_param("iiiiiiiiiss", $contract_id, $hotel_id, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday, $date_from, $date_to);
                                    $stmt->execute();
                                    $stop_sale_id = $stmt->insert_id;
                                    $stmt->close();
                                }

                                for ($i = 0; $i < $room_count; $i++) {
                                    $room_id = intval($_POST["room_id_{$i}"]);
                                    $radio = intval($_POST["radio_{$i}"]);
                                    if ($radio === 1) {
                                        $open = 1;
                                        $close = 0;
                                    } else if ($radio === 0) {
                                        $open = 0;
                                        $close = 1;
                                    }

                                    $sql = "INSERT INTO stop_sale_rooms(stop_sale_id, room_type_id, open, close)VALUES (?, ?, ?, ?)";
                                    $stmt = $baglanti->prepare($sql);
                                    $stmt->bind_param("iiii", $stop_sale_id, $room_id, $open, $close);
                                    $stmt->execute();
                                    $stmt->close();
                                }

                                header("Location: stopsalelist.php?hotel_id=$hotel_id&success=2");
                                exit;
                            }


                            ?>

                            


                        </div>
                    </div>
                </div>
        </section>
    </div>
    <!-- /.content-wrapper -->

    <script>
        function checkRadiosAndContract() {
            let allSelected = true;

            // Acenta (contract) kontrolü
            if ($('#contract_name').val() === "0") {
                allSelected = false;
            }

            // Radio’ları kontrol et
            $('[name^="radio_"]').each(function() {
                let name = $(this).attr('name');

                if (
                    !$('input[name="' + name + '"]:checked').length) {
                    allSelected = false;
                    return false;
                }
            });

            // Butonu aktifleştir / pasifleştir
            $('#saveButton').prop('disabled', !allSelected);
        }

        $(document).ready(function() {
            //Hangi alanın açılacağını belirliyoruz
            $('input[name="secim"][value="option2"]').prop('checked', true).trigger('change');
            $('#daterange').show();
            $('#daybyday').hide();
            $('input[name="secim"]').on('change', function() {
                var secilen = $(this).val();
                if (secilen === 'option1') {
                    $('#daybyday').show();
                    $('#daterange').hide();
                } else if (secilen === 'option2') {
                    $('#daterange').show();
                    $('#daybyday').hide();
                }
            });


            //Kaydet butonunun kontrolü 
            // Sayfa yüklendiğinde kontrol et
            checkRadiosAndContract();

            // Radio tıklanırsa kontrol et
            $(document).on('change', 'input[type="radio"]', function() {
                checkRadiosAndContract();
            });

            // Contract select değişirse kontrol et
            $('#contract_name').on('change', function() {
                checkRadiosAndContract();
            });

        });

        $('#contractSelect').on('change', function() {
            var contractId = $(this).val();

            console.log("contractId:", contractId);
            if (contractId !== 0) {
                $.post('', {
                    ajax: 'get_details',
                    contract_id: contractId
                }, function(data) {
                    $('#monthSelect').html(data);
                })
            } else {
                $('#monthSelect').html('<option value="">Önce kontrat seçiniz</option>');
            }
        });
        // Ay seçilince tarihler gelsin
        $('#monthSelect').on('change', function() {
            const selectedMonth = $(this).val();
            const contractId = $('#contractSelect').val();

            if (selectedMonth !== '' && contractId !== '') {
                $.post('', {
                    ajax: 'get_days_of_selected_month',
                    month: selectedMonth,
                    contract_id: contractId
                }, function(response) {
                    $('#monthResults').html(response);
                });
            }
        });
        $(document).on('change', '.contract-select', function() {
            const contractID = $(this).val();

            $.post('', {

                ajax: "get_contract",
                contract_id: contractID
            }, function(data) {
                $('.table-rooms').html(data);
                $('#selectOpen').on('change', function() {
                    if ($(this).is(':checked') && ($('input[name="secim"]:checked').val() === 'option2')) {
                        $('[name^="radio_"][value="1"]').prop('checked', true);
                        checkRadiosAndContract();
                    }
                });
                $('#selectClose').on('change', function() {
                    if ($(this).is(':checked') && ($('input[name="secim"]:checked').val() === 'option2')) {
                        $('[name^="radio_"][value="0"]').prop('checked', true);
                        checkRadiosAndContract();
                    }
                });
                checkRadiosAndContract();

                //eğer geçersiz kontrat varsa butonu disable yap
                if ($("#disable_check").length > 0) {
                    $('#saveButton').prop('disabled', true);
                }
            });
        });
    </script>
    <footer class="main-footer">
        <strong>Telif hakkı &copy; 2014-2025 <a href="https://mansurbilisim.com" target="_blank">Mansur Bilişim Ltd. Şti.</a></strong>
        Her hakkı saklıdır.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 1.0.1
        </div>
    </footer>
</body>

</html>