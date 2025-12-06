<?php 
// ------------------------------------------------------------------
//  1. GEREKLİ KÜTÜPHANELERİ VE DOSYALARI DAHİL ETME
// ------------------------------------------------------------------

// PHPMailer: E-posta göndermek için kullandığımız profesyonel kütüphane.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer'ın çekirdek dosyalarını sayfaya çağırıyoruz.
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// TCPDF: PDF dosyası oluşturmak için kullandığımız kütüphane.
require_once('tcpdf/tcpdf.php');

// Veritabanı bağlantısı ve sayfanın üst kısmı (Header).
include 'config/database.php'; 
include 'includes/header.php'; 


// ------------------------------------------------------------------
//  2. GÜVENLİK KONTROLÜ
// ------------------------------------------------------------------

// Kullanıcı giriş yapmamışsa (session'da ID yoksa) bilet alamaz.
// Onu giriş sayfasına (login.php) yönlendiriyoruz ve kodun çalışmasını durduruyoruz.
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}


// ------------------------------------------------------------------
//  3. POST İŞLEMİ: "ÖDEME YAP" BUTONUNA BASILDI MI?
// ------------------------------------------------------------------

// Sayfa POST metoduyla mı açıldı? Ve koltuk seçilmiş mi?
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['selected_seats'])) {
    
    // Formdan gelen verileri değişkenlere alıyoruz.
    $session_id = $_POST['session_id']; // Hangi seans?
    $user_id = $_SESSION['user_id'];    // Hangi kullanıcı?
    
    // Gelen koltuk verisi "A-1,A-2" şeklindedir. Bunu explode ile parçalayıp dizi (array) yapıyoruz.
    $seats = explode(',', $_POST['selected_seats']); 
    
    
    // --- A. KULLANICI BİLGİLERİNİ ÇEKME ---
    // Mail göndermek için kullanıcının e-posta adresine ve ismine ihtiyacımız var.
    $stmtUser = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $user_info = $stmtUser->fetch();
    
    // Değişkenlere atıyoruz ki aşağıda rahat kullanalım.
    $user_name = $user_info['username'];
    $user_email = $user_info['email'];


    // --- B. FİLM VE SALON BİLGİLERİNİ ÇEKME ---
    // Biletin üzerine yazmak için film adı, saati, salon adı ve afişi lazım.
    // JOIN kullanarak 3 tabloyu (Sessions, Films, Halls) birleştiriyoruz.
    $stmtSession = $pdo->prepare("SELECT s.start_time, f.title, f.poster_url, h.name as hall_name 
                                  FROM sessions s 
                                  JOIN films f ON s.film_id = f.id 
                                  JOIN halls h ON s.hall_id = h.id 
                                  WHERE s.id = ?");
    $stmtSession->execute([$session_id]);
    $session_info = $stmtSession->fetch();


    // ------------------------------------------------------------------
    //  4. VERİTABANI KAYIT İŞLEMİ (TRANSACTION)
    // ------------------------------------------------------------------
    
    $success = true; // Başlangıçta her şey yolunda varsayıyoruz.
    $error = "";     // Hata mesajı için boş değişken.
    $ticket_details = []; // Oluşturulan biletlerin kodlarını burada saklayacağız.

    try {
        // Transaction Başlat: "Ya hepsi kaybolsun, ya hiçbiri" mantığı.
        // Eğer 3 koltuktan 2'si kaydedilir 1'i hata verirse, hepsini geri alır.
        $pdo->beginTransaction(); 

        // SQL Sorgusunu Hazırla
        $sql = "INSERT INTO tickets (user_id, session_id, seat_number, verification_code) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        // Her bir koltuk için döngü (Loop)
        foreach ($seats as $seat) {
            
            // Benzersiz Bilet Kodu Üret (Örn: CNM-A1B2C3D4)
            // md5 ve uniqid kullanarak asla tekrar etmeyecek bir kod oluşturuyoruz.
            $unique_code = "CNM-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            
            // Veritabanına Ekle
            $stmt->execute([$user_id, $session_id, $seat, $unique_code]);
            
            // Bu biletin bilgilerini (Koltuk No ve Kod) diziye ekle.
            // (Birazdan PDF oluştururken kullanacağız)
            $ticket_details[] = ['seat' => $seat, 'code' => $unique_code];
        }

        // Hata olmadıysa işlemi onayla ve kalıcı yap.
        $pdo->commit(); 


        // ------------------------------------------------------------------
        //  5. PDF OLUŞTURMA İŞLEMİ (TCPDF)
        // ------------------------------------------------------------------
        
        // Yeni bir PDF dokümanı oluştur. UTF-8 desteği açık.
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Türkçe karakter sorunu olmasın diye 'dejavusans' fontunu seçiyoruz.
        $pdf->setFontSubsetting(true); 
        $pdf->SetFont('dejavusans', '', 10); 

        // PDF Başlık Ayarları
        $pdf->SetCreator('CinemaMax');
        $pdf->SetTitle('Sinema Biletiniz');
        $pdf->setPrintHeader(false); // Sayfa üstü çizgiyi kapat
        $pdf->setPrintFooter(false); // Sayfa altı çizgiyi kapat
        $pdf->SetMargins(10, 10, 10); // Kenar boşlukları
        $pdf->AddPage(); // Sayfayı ekle

        // PDF içine yazılacak değişkenleri hazırla
        $filmAdi = $session_info['title'];
        $tarih = date("d.m.Y", strtotime($session_info['start_time']));
        $saat = date("H:i", strtotime($session_info['start_time']));
        $salon = $session_info['hall_name'];

        // PDF İçeriği (HTML Başlangıcı)
        $htmlContent = '<h1 style="color:#1e90ff; text-align:center;">İyi Seyirler!</h1>';

        // Her bilet için bir kutu oluştur (Döngü)
        foreach ($ticket_details as $item) {
            // QR Kod API Linki (Google Chart veya QRServer)
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . $item['code'];

            // HTML Tablo Tasarımı (TCPDF tabloları sever)
            $htmlContent .= '
            <table cellpadding="10" border="1" bordercolor="#cccccc" style="background-color:#ffffff;">
                <tr>
                    <td width="65%" style="color:#333;">
                        <span style="font-size: 18px; font-weight: bold; color:#1e90ff;">CinemaMax Bilet</span>
                        <br><br>
                        <span style="font-size: 14px;"><b>Film:</b> '.$filmAdi.'</span><br>
                        <span style="font-size: 12px;"><b>Tarih:</b> '.$tarih.' | <b>Saat:</b> '.$saat.'</span><br>
                        <span style="font-size: 12px;"><b>Salon:</b> '.$salon.'</span>
                        <br><br>
                        <span style="font-size: 12px;">Koltuk:</span> 
                        <span style="font-size: 18px; color:#e74c3c; font-weight:bold;">'.$item['seat'].'</span>
                        <br>
                        <span style="font-size: 8px; color:#555;">Kod: '.$item['code'].'</span>
                    </td>

                    <td width="35%" align="center">
                        <img src="'.$qrUrl.'" width="100" height="100">
                        <br>
                        <span style="font-size: 8px;">Girişte Okutunuz</span>
                    </td>
                </tr>
            </table>
            <br><br>'; // Biletler arası boşluk
        }

        // Hazırladığımız HTML'i PDF'e yazdır.
        $pdf->writeHTML($htmlContent, true, false, true, false, '');
        
        // PDF'i dosya olarak kaydetme, String (Metin) olarak al.
        // Çünkü bunu mail'e ek olarak koyacağız. 'S' = String.
        $pdfContent = $pdf->Output('bilet.pdf', 'S'); 


        // ------------------------------------------------------------------
        //  6. E-POSTA GÖNDERME İŞLEMİ (PHPMailer)
        // ------------------------------------------------------------------
        
        $mail = new PHPMailer(true);

        try {
            // Türkçe karakter ayarları
            $mail->CharSet = 'UTF-8'; 
            $mail->Encoding = 'base64'; 

            // SMTP Sunucu Ayarları (Gmail)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'cmax131415@gmail.com'; // Gönderen Mail
            $mail->Password   = 'mawm khmr ehez zxhr';  // Uygulama Şifresi
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Güvenli Bağlantı (SSL)
            $mail->Port       = 465; // SSL Portu

            // SSL Sertifika Hatasını Geç (Localhost için gerekli)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Gönderen ve Alıcı
            $mail->setFrom('noreply@cinemamax.com', 'CinemaMax Bilet');
            $mail->addAddress($user_email, $user_name);
            
            // Oluşturduğumuz PDF'i maile ekle (Attachment)
            $mail->addStringAttachment($pdfContent, 'SinemaBiletiniz.pdf');

            // Mail İçeriği (HTML)
            $mail->isHTML(true);
            $mail->Subject = 'Biletiniz Hazır! 🎬 ' . $session_info['title'];
            
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;'>
                    <div style='background-color: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #1e90ff;'>Biletiniz Ektedir</h2>
                        <p>Merhaba $user_name, bilet satın alma işleminiz tamamlandı.</p>
                        <p>Bilet detaylarınız ve giriş için gerekli QR kodunuz <strong>ekteki PDF dosyasındadır.</strong></p>
                        <br>
                        <p style='color:#888; font-size:12px;'>CinemaMax Ekibi</p>
                    </div>
                </div>
            ";

            // Maili Gönder
            $mail->send();

        } catch (Exception $e) {
             // Mail hatası olursa işlemi durdurmuyoruz, kullanıcıya biletini aldığını gösteriyoruz.
             // Hata loglanabilir: error_log($mail->ErrorInfo);
        }

    } catch (Exception $e) {
        // Eğer veritabanı işleminde hata olursa (Rollback), her şeyi geri al.
        $pdo->rollBack();
        $success = false;
        $error = $e->getMessage();
    }

} else {
    // Sayfaya doğrudan girilirse ana sayfaya at.
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
             <h1 style="color: #333;">Bir Sorun Oluştu</h1>
             <p style="color: #666;"><?php echo $error; ?></p>
             <a href="index.php" class="btn btn-primary">Tekrar Dene</a>
        </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>