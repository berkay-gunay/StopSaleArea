<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
if ($hotel_id <= 0) {
    die("Geçersiz ID");
}
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
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stop Sale</title>
    <style>
        .cancelled {
            text-decoration: line-through;
            opacity: 0.6;
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
                                <a href="edithotels.php?id=<?php echo $hotel_id; ?>"><button class="btn btn-danger"><i class="fa-solid fa-angle-left"></i> Geri</button></a>

                                <a href="stopsale.php?hotel_id=<?php echo $hotel_id ?>"><button class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add/Show Stop Sale</button></a>

                            </div>
                        </div>
                    </div>

                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"></h3>
                        </div>


                        <div class='card-body'>

                            <div class='card-body'>
                                <table class='table table-bordered table-striped'>
                                    <?php
                                    $sql = "SELECT DISTINCT s.id, c.contract_name, h.name, s.from_date, s.to_date,s.monday,s.tuesday,s.wednesday,s.thursday,s.friday,s.saturday,s.sunday,s.contract_id FROM stop_sale s
                                        JOIN hotels h ON s.hotel_id = h.id
                                        JOIN contracts c ON s.contract_id = c.contract_id
                                        WHERE s.hotel_id = ?";
                                    $stmt = $baglanti->prepare($sql);
                                    $stmt->bind_param("i", $hotel_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    if ($result->num_rows > 0) { ?>
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Contract Name</th>
                                                <th>Hotel Name</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Days</th>
                                                <th>Rooms</th>
                                                <th width='12%'></th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            <?php
                                            while ($row = $result->fetch_assoc()) {  ?>
                                                <tr>
                                                    <td><?= $row["id"] ?></td>
                                                    <td><?= htmlspecialchars($row["contract_name"]) ?></td>
                                                    <td><?= htmlspecialchars($row["name"]) ?></td>
                                                    <td><?= date("d-m-Y", strtotime($row["from_date"])) ?></td>
                                                    <td><?= date("d-m-Y", strtotime($row["to_date"])) ?></td>
                                                    <td><?php
                                                        $days = [];
                                                        if ($row['monday']) $days[] = 'Monday';
                                                        if ($row['tuesday']) $days[] = 'Tuesday';
                                                        if ($row['wednesday']) $days[] = 'Wednesday';
                                                        if ($row['thursday']) $days[] = 'Thursday';
                                                        if ($row['friday']) $days[] = 'Friday';
                                                        if ($row['saturday']) $days[] = 'Saturday';
                                                        if ($row['sunday']) $days[] = 'Sunday';
                                                        echo implode(", ", $days);
                                                        ?></td>
                                                    <td><?php
                                                        $room_names = [];
                                                        $sql = "SELECT n.room_type_name
                                                                FROM stop_sale p
                                                                JOIN stop_sale_rooms r ON p.id = r.stop_sale_id 
                                                                JOIN room_type_name n ON r.room_type_id = n.id 
                                                                WHERE p.id =?";
                                                        $stmt = $baglanti->prepare($sql);
                                                        $stmt->bind_param("i", $row["id"]);
                                                        $stmt->execute();
                                                        $result2 = $stmt->get_result();
                                                        if ($result2->num_rows > 0) {
                                                            while ($room_name = $result2->fetch_assoc()) {
                                                                $room_names[] = $room_name["room_type_name"];
                                                            }
                                                            echo implode(", ", $room_names);
                                                        }

                                                        ?></td>
                                                    <td>
                                                        <a class='btn btn-info btn-sm' href="editstopsale.php?hotel_id=<?= $hotel_id ?>&contract_id=<?= $row['contract_id'] ?>">
                                                            <i class='fas fa-pencil-alt'></i> Düzenle
                                                        </a>

                                                        <button class='btn btn-danger btn-sm delete-btn' data-id="<?= $row['id'] ?>">
                                                            <i class='fas fa-trash'></i> Sil
                                                        </button>
                                                    </td>

                                                </tr>


                                            <?php } ?>


                                        <?php } else {
                                        echo "Kayıt bulunamadı";
                                    } ?>



                                        </tbody>
                                </table>
                            </div>

                            <!-- Modal -->
                            <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabel"><span class="ion-alert-circled"></span> UYARI !</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            KAYIT KALICI OLARAK <u>SİLİNECEK</u> !!! İŞLEME DEVAM ETMEK İSTİYORMUSUNUZ ?
                                        </div>
                                        <div class="modal-footer">
                                            <form id="deleteForm" method="POST" action="">
                                                <input type="hidden" name="delete_id" id="deleteId">
                                                <button type="submit" class="btn btn-danger">EVET</button>
                                            </form>
                                            <button type="button" class="btn btn-success" data-dismiss="modal" aria-label="Close">HAYIR</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                $(document).ready(function() {
                                    // Sil butonuna tıklama olayı
                                    $('.delete-btn').click(function() {
                                        var deleteId = $(this).data('id');
                                        $('#deleteId').val(deleteId);
                                        $('#exampleModal').modal('show');
                                    });
                                });
                            </script>

                            <?php
                            // POST ile makale silme işlemi
                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
                                $deleteId = (int)$_POST['delete_id']; // Güvenlik için id'yi tam sayı olarak al
                                $deleteSql = "DELETE FROM stop_sale WHERE id = ?";
                                $stmt = $baglanti->prepare($deleteSql);
                                $stmt->bind_param("i", $deleteId);

                                if ($stmt->execute()) {
                                    header("Location: stopsalelist.php?hotel_id=$hotel_id&success=3");
                                    exit;
                                } else {
                                    echo "<p style='color: red;'>Makaleyi silerken bir hata oluştu.</p>";
                                }

                                $stmt->close();
                            }


                            //Toast kısımları
                            if (isset($_GET['success']) && $_GET['success'] == 1) {
                                echo "<script type='text/javascript'> 
                                        toastr.success('Güncelleme başarılı bir şekilde yapıldı. !');
                                    </script>";
                            }

                            if (isset($_GET['success']) && $_GET['success'] == 2) {
                                echo "<script type='text/javascript'> 
                                    toastr.success('Kayıt başarılı bir şekilde yapıldı. !');
                                </script>";
                            }
                            if (isset($_GET['success']) && $_GET['success'] == 3) {
                                echo "<script type='text/javascript'> 
                                    toastr.success('Kayıt başarılı bir şekilde silindi. !');
                                </script>";
                            }

                            ?>



                        </div>
                    </div>
                </div>
        </section>
    </div>
    <!-- /.content-wrapper -->

    <script>

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