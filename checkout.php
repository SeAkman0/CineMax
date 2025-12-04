<?php 
// --- PHPMailer Dahil Etme ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

include 'config/database.php'; // Yeni dosya adı
include 'includes/header.php';// Yeni dosya adı

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
    
    // Mail İçeriği İçin Veri Toplama
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
        //  E-POSTA GÖNDERME İŞLEMİ (TASARIMLI)
        // ==========================================
        
        $mail = new PHPMailer(true);

        try {
            // 1. Sunucu Ayarları
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'cmax131415@gmail.com'; 
            $mail->Password   = 'klhs biqc hjtl kuup'; 
    
            $mail->SMTPSecure = 'tls'; // veya 'ssl'
            $mail->Port       = 587;   // ssl için 465

            // 2. Alıcı Ayarları
            $mail->setFrom('noreply@cinemamax.com', 'CinemaMax Bilet');
            $mail->addAddress($user_info['email'], $user_info['username']);

            // 3. İçerik Ayarları
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Biletiniz Hazır! 🎬 ' . $session_info['title'];

            // --- HTML MAİL TASARIMI (Bilet Görünümlü) ---
            $tarih = date("d.m.Y", strtotime($session_info['start_time']));
            $saat = date("H:i", strtotime($session_info['start_time']));
            $poster = $session_info['poster_url']; // Tam URL olmalı (http://...) yoksa mailde görünmez. 
            // Localhost olduğu için poster mailde kırık görünebilir, canlıda düzelir.

            $messageBody = "
            <div style='background-color:#f4f4f4; padding:20px; font-family:Arial, sans-serif;'>
                <div style='max-width:600px; margin:0 auto; background:white; border-radius:10px; overflow:hidden; box-shadow:0 4px 15px rgba(0,0,0,0.1);'>
                    
                    <div style='background:#1e90ff; color:white; padding:20px; text-align:center;'>
                        <h1 style='margin:0; font-size:24px;'>CinemaMax</h1>
                        <p style='margin:5px 0 0 0;'>İyi Seyirler Dileriz!</p>
                    </div>

                    <div style='padding:20px; border-bottom:1px dashed #ddd; display:flex; align-items:center;'>
                        <div style='flex:1;'>
                            <h2 style='margin:0 0 10px 0; color:#333;'>{$session_info['title']}</h2>
                            <p style='margin:5px 0; color:#666;'>📅 <strong>Tarih:</strong> $tarih</p>
                            <p style='margin:5px 0; color:#666;'>⏰ <strong>Saat:</strong> $saat</p>
                            <p style='margin:5px 0; color:#666;'>📍 <strong>Salon:</strong> {$session_info['hall_name']}</p>
                        </div>
                    </div>

                    <div style='padding:20px;'>
                        <h3 style='margin-top:0; color:#555; text-align:center;'>Biletleriniz</h3>
                        <table style='width:100%; border-collapse:collapse;'>";

            foreach ($ticket_details as $item) {
                // QR API Linki
                $qrLink = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . $item['code'];
                
                $messageBody .= "
                <tr>
                    <td style='border:1px solid #eee; padding:15px; background:#f9f9f9; border-radius:5px;'>
                        <div style='font-size:18px; font-weight:bold; color:#1e90ff;'>Koltuk: {$item['seat']}</div>
                        <div style='font-size:12px; color:#999; margin-top:5px;'>Kod: {$item['code']}</div>
                    </td>
                    <td style='border:1px solid #eee; padding:10px; text-align:center; background:white; width:100px;'>
                        <img src='$qrLink' alt='QR' width='80'>
                    </td>
                </tr>
                <tr><td colspan='2' style='height:10px;'></td></tr>"; // Boşluk
            }

            $messageBody .= "
                        </table>
                    </div>

                    <div style='background:#333; color:#999; padding:15px; text-align:center; font-size:12px;'>
                        <p>Lütfen sinema girişinde QR kodlarınızı görevliye okutunuz.</p>
                        <p>&copy; 2025 CinemaMax</p>
                    </div>
                </div>
            </div>";

            $mail->Body = $messageBody;
            $mail->send();

        } catch (Exception $e) {
            // Mail hatası olursa kullanıcıya hissettirme, logla geç
            // error_log("Mail Gönderilemedi: " . $mail->ErrorInfo);
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
                Biletleriniz oluşturuldu ve <strong><?php echo $user_info['email']; ?></strong> adresine gönderildi.
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