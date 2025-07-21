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
$contract_id = isset($_GET['contract_id']) ? (int)$_GET['contract_id'] : 0;
if ($contract_id <= 0) {
    die("Geçersiz ID");
}



?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Stop Sale</title>
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
                        <h1><i class="fa-solid fa-person-walking-luggage"></i>Edit Stop Sale</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Rezervasyon Modülü</a></li>
                            <li class="breadcrumb-item active">Edit Stop Sale</li>
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


                            <form action="" method="post">
                                <?php

                                $sql = "SELECT * FROM stop_sale WHERE hotel_id = ? AND contract_id=?";
                                $stmt = $baglanti->prepare($sql);
                                $stmt->bind_param("ii", $hotel_id, $contract_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {

                                        $sql_dates = "SELECT DISTINCT start_date, finish_date FROM contracts WHERE contract_id=? AND hotel_id = ?";
                                        $stmt_dates = $baglanti->prepare($sql_dates);
                                        $stmt_dates->bind_param("ii", $contract_id, $hotel_id);
                                        $stmt_dates->execute();
                                        $result_dates = $stmt_dates->get_result();
                                        $row_dates = $result_dates->fetch_assoc();

                                ?>

                                        <table class="table dotted-rows">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <p>From</p><input type="date" name="date_from" min="<?= $row_dates['start_date']?>" max="<?= $row_dates['finish_date']?>" class="form-control checkout" value="<?= $row["from_date"] ?>" required>
                                                    </td>
                                                    <td>
                                                        <p>To</p><input type="date" name="date_to" min="<?= $row_dates['start_date']?>" max="<?= $row_dates['finish_date']?>" class="form-control checkout" value="<?= $row["to_date"] ?>" required>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <br>
                                        <hr style="border-top: 1px dotted #000;">

                                        <div class="container mt-3">
                                            <div class="row g-2">
                                                <div class="col-auto">
                                                    <div class="day-card">
                                                        <input type="checkbox" name="monday" value="1" <?php if ($row["monday"]) {
                                                                                                            echo "checked";
                                                                                                        } ?>> Monday
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="day-card">
                                                        <input type="checkbox" name="tuesday" value="1" <?php if ($row["tuesday"]) {
                                                                                                            echo "checked";
                                                                                                        } ?>> Tuesday
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="day-card">
                                                        <input type="checkbox" name="wednesday" value="1" <?php if ($row["wednesday"]) {
                                                                                                                echo "checked";
                                                                                                            } ?>> Wednesday
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="day-card">
                                                        <input type="checkbox" name="thursday" value="1" <?php if ($row["thursday"]) {
                                                                                                                echo "checked";
                                                                                                            } ?>> Thursday
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="day-card">
                                                        <input type="checkbox" name="friday" value="1" <?php if ($row["friday"]) {
                                                                                                            echo "checked";
                                                                                                        } ?>> Friday
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="day-card">
                                                        <input type="checkbox" name="saturday" value="1" <?php if ($row["saturday"]) {
                                                                                                                echo "checked";
                                                                                                            } ?>> Saturday
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="day-card">
                                                        <input type="checkbox" name="sunday" value="1" <?php if ($row["sunday"]) {
                                                                                                            echo "checked";
                                                                                                        } ?>> Sunday
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <br>

                                        <table class='table table-bordered table-striped'>
                                            <thead>
                                                <tr>
                                                    <th>Room Type</th>
                                                    <th>Open</th>
                                                    <th>Close</th>

                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td></td>
                                                    <div class="form-group">
                                                        <td>
                                                            <label><input type="radio" name="selectAll" id="selectOpen"> Tümünü Open Seç</label>
                                                        </td>
                                                        <td>
                                                            <label><input type="radio" name="selectAll" id="selectClose"> Tümünü Close Seç</label>
                                                        </td>
                                                    </div>
                                                </tr>
                                                <?php
                                                $sql2 = "SELECT n.room_type_name,s.room_type_id,s.open,s.close
                                                FROM stop_sale_rooms s 
                                                JOIN room_type_name n ON s.room_type_id = n.id
                                                WHERE s.stop_sale_id = ?";
                                                $stmt = $baglanti->prepare($sql2);
                                                $stmt->bind_param("i", $row["id"]);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                if ($result->num_rows > 0) {
                                                    $roomCount = 0;
                                                    while ($rowRoom = $result->fetch_assoc()) { ?>
                                                        <tr>
                                                            <td>
                                                                <label><?= htmlspecialchars($rowRoom['room_type_name']) ?> </label>
                                                                <input type="hidden" name="room_id_<?= $roomCount ?>" value="<?= $rowRoom['room_type_id'] ?>">
                                                            </td>
                                                            <td>
                                                                <p><input type="radio" name="radio_<?= $roomCount ?>" value="1" <?php if ($rowRoom["open"] == 1) {
                                                                                                                                    echo "checked";
                                                                                                                                } ?>> Open</p>
                                                            </td>
                                                            <td>
                                                                <p><input type="radio" name="radio_<?= $roomCount ?>" value="0" <?php if ($rowRoom["close"] == 1) {
                                                                                                                                    echo "checked";
                                                                                                                                } ?>> Close</p>
                                                            </td>
                                                        </tr>

                                                        <?php $roomCount++ ?>

                                                    <?php } ?>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                <?php }
                                } ?>
                                <input type="hidden" name="room_count" value="<?= $roomCount ?>">

                                <button type="submit" id="saveButton" style="width: 100%;" class="btn btn-success" disabled> Save </button>
                            </form>


                            <?php

                            if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                                $room_count = $_POST["room_count"];
                                //$contract_id = intval($_POST['contract_id']);
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
                                header("Location: stopsalelist.php?hotel_id=$hotel_id&success=1"); 
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

        $('#selectOpen').on('change', function() {
            if ($(this).is(':checked')) {
                $('[name^="radio_"][value="1"]').prop('checked', true);
            }
        });
        $('#selectClose').on('change', function() {
            if ($(this).is(':checked')) {
                $('[name^="radio_"][value="0"]').prop('checked', true);
            }
        })
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