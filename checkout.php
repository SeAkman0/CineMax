<?php 
// --- PHPMailer Dahil Etme ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Kütüphaneleri Dahil Et
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require_once('tcpdf/tcpdf.php'); // TCPDF Kütüphanesi

include 'config/database.php'; 
include 'includes/header.php'; 

// Güvenlik
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['selected_seats'])) {
    
    $session_id = $_POST['session_id'];
    $user_id = $_SESSION['user_id'];
    $seats = explode(',', $_POST['selected_seats']); 
    
    // Kullanıcı Bilgileri
    $stmtUser = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $user_info = $stmtUser->fetch();
    
    // --- DÜZELTME BURADA YAPILDI ---
    // Değişkenleri tanımlıyoruz ki aşağıda hata vermesin
    $user_name = $user_info['username'];
    $user_email = $user_info['email'];
    // -------------------------------

    // Film ve Salon Bilgileri
    $stmtSession = $pdo->prepare("SELECT s.start_time, f.title, f.poster_url, h.name as hall_name 
                                  FROM sessions s 
                                  JOIN films f ON s.film_id = f.id 
                                  JOIN halls h ON s.hall_id = h.id 
                                  WHERE s.id = ?");
    $stmtSession->execute([$session_id]);
    $session_info = $stmtSession->fetch();

    $success = true;
    $error = "";
    
    $ticket_details = [];

    try {
        $pdo->beginTransaction(); 

        $sql = "INSERT INTO tickets (user_id, session_id, seat_number, verification_code) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        foreach ($seats as $seat) {
            // Kod Üret
            $unique_code = "CNM-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            
            // Kaydet
            $stmt->execute([$user_id, $session_id, $seat, $unique_code]);
            
            // Mail için diziye at
            $ticket_details[] = ['seat' => $seat, 'code' => $unique_code];
        }

        $pdo->commit(); 

        // ==========================================
        //  1. PDF OLUŞTURMA İŞLEMİ
        // ==========================================
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('CinemaMax');
        $pdf->SetTitle('Sinema Biletiniz');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $filmAdi = $session_info['title'];
        $tarih = date("d.m.Y", strtotime($session_info['start_time']));
        $saat = date("H:i", strtotime($session_info['start_time']));
        $salon = $session_info['hall_name'];
        
        $html = '
        <style>
            .ticket-box { border: 2px dashed #333; padding: 20px; margin-bottom: 20px; color:#333; }
            .header { font-size: 24px; font-weight: bold; color: #1e90ff; border-bottom: 2px solid #1e90ff; }
            .info { font-size: 14px; color: #555; }
            .label { font-weight: bold; color: #000; }
            .seat-badge { font-size: 20px; font-weight: bold; color: #e74c3c; }
        </style>';

        foreach ($ticket_details as $item) {
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . $item['code'];

            $html .= '
            <table cellpadding="10" cellspacing="0" border="0" style="border: 2px dashed #ccc;">
                <tr>
                    <td width="60%">
                        <div style="font-size: 22px; font-weight: bold; color:#1e90ff;">CinemaMax</div>
                        <br><br>
                        <div style="font-size: 18px; font-weight: bold;">'.$filmAdi.'</div>
                        <div style="color:#555;">
                            <br><b>Tarih:</b> '.$tarih.'
                            <br><b>Saat:</b> '.$saat.'
                            <br><b>Salon:</b> '.$salon.'
                        </div>
                        <br><br>
                        <div style="font-size: 16px;"><b>Koltuk:</b> <span style="font-size:24px; color:#e74c3c;">'.$item['seat'].'</span></div>
                        <div style="font-size: 10px; color:#999;">Bilet Kodu: '.$item['code'].'</div>
                    </td>
                    <td width="40%" align="center">
                        <br><br>
                        <img src="'.$qrUrl.'" width="120" height="120">
                        <br><span style="font-size:10px; color:#999;">Girişte Okutunuz</span>
                    </td>
                </tr>
            </table>
            <br><br>';
        }

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdfContent = $pdf->Output('bilet.pdf', 'S'); 


        // ==========================================
        //  2. MAİL GÖNDERME (EKLİ)
        // ==========================================
        
        $mail = new PHPMailer(true);

        try {
            // Ayarlar
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'cmax131415@gmail.com'; // <-- GÜNCELLE
            $mail->Password   = 'mawm khmr ehez zxhr';     // <-- GÜNCELLE
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Alıcı
            $mail->setFrom('noreply@cinemamax.com', 'CinemaMax Bilet');
            $mail->addAddress($user_email, $user_name); // ARTIK HATA VERMEZ

            // PDF Dosyasını Ekle
            $mail->addStringAttachment($pdfContent, 'SinemaBiletiniz.pdf');

            // İçerik
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Biletiniz Hazır! 🎬 ' . $session_info['title'];
            $mail->Body    = "
                <h2>İyi Seyirler $user_name!</h2>
                <p>Bilet satın alma işleminiz başarıyla gerçekleşti.</p>
                <p><strong>Biletiniz ekteki PDF dosyasındadır.</strong> Girişte bu dosyadaki QR kodu okutarak geçiş yapabilirsiniz.</p>
                <br>
                <p>SinemaMax Ekibi</p>
            ";

            $mail->send();

        } catch (Exception $e) {
            // Mail hatası sessizce geçilir
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        $success = false;
        $error = $e->getMessage();
    }
} else {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}
?>

<div class="container" style="padding: 100px 20px; text-align: center;">
    <?php if ($success): ?>
        <div style="background: white; padding: 50px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto;">
            <div style="width: 80px; height: 80px; background: #2ecc71; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="fas fa-check" style="font-size: 40px; color: white;"></i>
            </div>
            
            <h1 style="color: #333; margin-bottom: 15px;">Ödeme Başarılı!</h1>
            <p style="color: #666; font-size: 1.1rem; margin-bottom: 30px;">
                Biletiniz oluşturuldu ve <strong><?php echo $user_email; ?></strong> adresine, <strong>PDF formatında</strong> gönderildi.
            </p>
            
            <div style="display: flex; justify-content: center; gap: 15px;">
                <a href="my-tickets.php" class="btn btn-primary">Biletlerimi Gör</a>
                <a href="index.php" class="btn btn-secondary" style="color:#1e90ff; border-color:#1e90ff;">Ana Sayfa</a>
            </div>
        </div>
    <?php else: ?>
        <div style="background: white; padding: 50px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto;">
             <div style="width: 80px; height: 80px; background: #ff4757; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="fas fa-times" style="font-size: 40px; color: white;"></i>
            </div>
            <h1 style="color: #333; margin-bottom: 15px;">Bir Sorun Oluştu</h1>
            <p style="color: #666; margin-bottom: 30px;"><?php echo $error; ?></p>
            <a href="index.php" class="btn btn-primary">Tekrar Dene</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>