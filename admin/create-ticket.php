<?php 
// =======================================================
//  1. KÜTÜPHANELER VE AYARLAR
// =======================================================

// PHPMailer: E-posta göndermek için kullanılan kütüphane.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Kütüphane dosyalarını (bir üst klasörden) çağırıyoruz.
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
// TCPDF: PDF bileti oluşturmak için kullanılan kütüphane.
require_once('../tcpdf/tcpdf.php');

// Veritabanı bağlantısı ve Admin Paneli menüsü (Header).
include 'header.php'; 

// Hata ve Başarı mesajlarını tutacak değişkenler.
$error = "";
$success = "";

// =======================================================
//  2. KAYIT İŞLEMİ (FORM GÖNDERİLDİĞİNDE)
// =======================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Formdan gelen verileri alıyoruz.
    $user_id = $_POST['user_id'];
    $session_id = $_POST['session_id'];
    $seat_number = $_POST['seat_number']; // JavaScript'ten gelen gizli input verisi (Örn: A-5)

    // A. Boş Alan Kontrolü
    if (empty($seat_number) || empty($session_id)) {
        $error = "Lütfen tüm seçimleri yapınız!";
    } else {
        
        // B. Çifte Kontrol: Koltuk gerçekten boş mu?
        // Admin seçerken başka biri o koltuğu almış olabilir.
        $checkStmt = $pdo->prepare("SELECT id FROM tickets WHERE session_id = ? AND seat_number = ?");
        $checkStmt->execute([$session_id, $seat_number]);

        if ($checkStmt->rowCount() > 0) {
            $error = "HATA: Seçilen koltuk ($seat_number) maalesef dolu!";
        } else {
            
            // --- Veritabanından Detaylı Bilgileri Çekme ---
            
            // 1. Kullanıcı Bilgileri (Mail atmak için lazım)
            $stmtUser = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
            $stmtUser->execute([$user_id]);
            $user_info = $stmtUser->fetch();
            $user_name = $user_info['username'];
            $user_email = $user_info['email'];

            // 2. Seans ve Film Bilgileri (Bilet üzerine yazmak için lazım)
            $stmtSession = $pdo->prepare("SELECT s.start_time, f.title, f.poster_url, h.name as hall_name 
                                          FROM sessions s 
                                          JOIN films f ON s.film_id = f.id 
                                          JOIN halls h ON s.hall_id = h.id 
                                          WHERE s.id = ?");
            $stmtSession->execute([$session_id]);
            $session_info = $stmtSession->fetch();

            // 3. Benzersiz Bilet Kodu Üretme (Örn: CNM-A1B2...)
            $unique_code = "CNM-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            
            // --- VERİTABANI KAYDI ---
            $sql = "INSERT INTO tickets (user_id, session_id, seat_number, verification_code) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            // Eğer kayıt başarılıysa PDF ve Mail işlemlerine geç
            if ($stmt->execute([$user_id, $session_id, $seat_number, $unique_code])) {
                
                // ==========================================
                //  3. PDF OLUŞTURMA (TCPDF)
                // ==========================================
                
                // Yeni PDF nesnesi oluştur
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                
                // Türkçe karakter desteği için font ayarı
                $pdf->setFontSubsetting(true); 
                $pdf->SetFont('dejavusans', '', 10); 

                // Sayfa ayarları
                $pdf->SetCreator('CinemaMax');
                $pdf->SetTitle('Sinema Biletiniz');
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
                $pdf->AddPage();

                // QR Kod API Linki
                $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . $unique_code;
                $tarih = date("d.m.Y", strtotime($session_info['start_time']));
                $saat = date("H:i", strtotime($session_info['start_time']));

                // PDF İçeriği (HTML Tablo Tasarımı)
                $html = '
                <table cellpadding="10" cellspacing="0" border="0" style="border: 2px dashed #ccc;">
                    <tr>
                        <td width="60%">
                            <div style="font-size: 22px; font-weight: bold; color:#1e90ff;">CinemaMax</div>
                            <br><br>
                            <div style="font-size: 18px; font-weight: bold;">'.$session_info['title'].'</div>
                            <div style="color:#555;">
                                <br><b>Tarih:</b> '.$tarih.'
                                <br><b>Saat:</b> '.$saat.'
                                <br><b>Salon:</b> '.$session_info['hall_name'].'
                            </div>
                            <br><br>
                            <div style="font-size: 16px;"><b>Koltuk:</b> <span style="font-size:24px; color:#e74c3c;">'.$seat_number.'</span></div>
                            <div style="font-size: 10px; color:#999;">Bilet Kodu: '.$unique_code.'</div>
                        </td>
                        <td width="40%" align="center">
                            <br><br>
                            <img src="'.$qrUrl.'" width="120" height="120">
                            <br><span style="font-size:10px; color:#999;">Girişte Okutunuz</span>
                        </td>
                    </tr>
                </table>';

                // HTML'i PDF'e yazdır
                $pdf->writeHTML($html, true, false, true, false, '');
                
                // PDF'i dosya olarak değil, String (Metin) verisi olarak al (Mail'e eklemek için)
                $pdfContent = $pdf->Output('bilet.pdf', 'S'); 

                // ==========================================
                //  4. MAİL GÖNDERME (PHPMailer)
                // ==========================================
                $mail = new PHPMailer(true);
                try {
                    // Türkçe karakter ayarları
                    $mail->CharSet = 'UTF-8'; 
                    $mail->Encoding = 'base64'; 

                    // SMTP Sunucu Ayarları
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'cmax131415@gmail.com'; // Gönderen Mail
                    $mail->Password   = 'mawm khmr ehez zxhr';    // Uygulama Şifresi
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;
                    
                    // Localhost SSL Hatasını Atlatma Ayarı
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
                    
                    // PDF'i eklenti olarak koy
                    $mail->addStringAttachment($pdfContent, 'SinemaBiletiniz.pdf');

                    // Mail İçeriği
                    $mail->isHTML(true);
                    $mail->Subject = 'Biletiniz Hazır! 🎬 ' . $session_info['title'];
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;'>
                            <div style='background-color: white; padding: 30px; border-radius: 10px; margin: 0 auto;'>
                                <h2 style='color: #1e90ff;'>Biletiniz Ektedir</h2>
                                <p>Merhaba $user_name, bilet satın alma işleminiz tamamlandı.</p>
                                <p>Bilet detaylarınız ve giriş için gerekli QR kodunuz <strong>ekteki PDF dosyasındadır.</strong></p>
                                <br>
                                <p style='color:#888; font-size:12px;'>CinemaMax Ekibi</p>
                            </div>
                        </div>
                    ";

                    $mail->send();
                    
                    // İşlem Başarılı Mesajı
                    $success = "Bilet oluşturuldu ve <strong>{$user_email}</strong> adresine gönderildi! <br> Kod: <strong>$unique_code</strong>";

                } catch (Exception $e) {
                    // Mail gitmese bile bilet oluşturulduğu için başarı mesajı ver ama hatayı da söyle
                    $success = "Bilet oluşturuldu ANCAK mail gönderilemedi. Hata: " . $mail->ErrorInfo;
                }

            } else {
                $error = "Veritabanı hatası.";
            }
        }
    }
}

// =======================================================
//  5. DROPDOWN (SEÇİM) VERİLERİNİ HAZIRLAMA
// =======================================================
$users = $pdo->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll();
$films = $pdo->query("SELECT id, title FROM films WHERE is_active = 1 ORDER BY title ASC")->fetchAll();
?>

<style>
    .screen { background: #ccc; height: 30px; width: 80%; margin: 20px auto; transform: rotateX(-45deg); text-align: center; letter-spacing: 5px; color: #555; }
    .seat-grid { display: flex; flex-direction: column; align-items: center; gap: 8px; margin-top: 20px; }
    .row { display: flex; gap: 8px; align-items: center; }
    .row-label { width: 25px; font-weight: bold; color: #555; text-align:center;}
    .seat { width: 30px; height: 30px; background: #e0e0e0; border-radius: 5px; cursor: pointer; transition: 0.2s; }
    .seat:hover:not(.occupied) { background: #3498db; }
    .seat.selected { background: #3498db; box-shadow: 0 0 5px #3498db; }
    .seat.occupied { background: #e74c3c; cursor: not-allowed; }
</style>

<h1>Manuel Bilet Oluştur</h1>

<div class="table-box" style="background:#fff; padding:30px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1); max-width:800px;">
    
    <?php if($error): ?>
        <div style="background:#ff4757; color:white; padding:15px; border-radius:5px; margin-bottom:20px;"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div style="background:#2ecc71; color:white; padding:15px; border-radius:5px; margin-bottom:20px;"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        
        <div style="display:flex; gap:20px; flex-wrap:wrap;">
            
            <div style="flex:1; min-width:200px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Kullanıcı</label>
                <select name="user_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                    <option value="">Kullanıcı Seçiniz...</option>
                    <?php foreach($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo $u['username']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex:1; min-width:200px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Film</label>
                <select id="filmSelect" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                    <option value="">Film Seçiniz...</option>
                    <?php foreach($films as $f): ?>
                        <option value="<?php echo $f['id']; ?>"><?php echo $f['title']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex:1; min-width:200px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Seans</label>
                <select name="session_id" id="sessionSelect" required disabled style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; background:#f9f9f9;">
                    <option value="">Önce Film Seçiniz</option>
                </select>
            </div>
        </div>

        <div id="seatArea" style="display:none; margin-top:30px; background:#f9f9f9; padding:20px; border-radius:10px; text-align:center;">
            <h3>Koltuk Seçimi</h3>
            <p>Seçilen: <strong id="selectedDisplay" style="color:#3498db;">Yok</strong></p>
            
            <div class="screen">PERDE</div>
            <div id="gridContainer" class="seat-grid"></div>
            <input type="hidden" name="seat_number" id="seatInput">
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:20px; width:100%; padding:12px; background:#2ecc71; border:none; color:white; border-radius:5px; font-size:1rem; cursor:pointer;">
            Bileti Oluştur
        </button>

    </form>
</div>

<script>
    const filmSelect = document.getElementById('filmSelect');
    const sessionSelect = document.getElementById('sessionSelect');
    const seatArea = document.getElementById('seatArea');
    const gridContainer = document.getElementById('gridContainer');
    const seatInput = document.getElementById('seatInput');
    const selectedDisplay = document.getElementById('selectedDisplay');

    // A. Film Seçilince -> İlgili Seansları Getir
    filmSelect.addEventListener('change', function() {
        const filmId = this.value;
        sessionSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        sessionSelect.disabled = true;
        seatArea.style.display = 'none';

        if (filmId) {
            // API'ye istek at
            fetch('api/get-showtimes.php?film_id=' + filmId)
                .then(response => response.json())
                .then(data => {
                    sessionSelect.innerHTML = '<option value="">Seans Seçiniz...</option>';
                    if(data.length === 0) {
                        sessionSelect.innerHTML = '<option value="">Aktif seans yok</option>';
                    } else {
                        // Gelen seansları kutuya doldur
                        data.forEach(sess => {
                            const option = document.createElement('option');
                            option.value = sess.id;
                            option.text = `${sess.hall_name} | ${sess.formatted_time} (${sess.price} ₺)`;
                            sessionSelect.appendChild(option);
                        });
                        sessionSelect.disabled = false;
                        sessionSelect.style.background = "#fff";
                    }
                });
        } else {
            sessionSelect.innerHTML = '<option value="">Önce Film Seçiniz</option>';
        }
    });

    // B. Seans Seçilince -> Koltukları Getir
    sessionSelect.addEventListener('change', function() {
        const sessionId = this.value;
        if (sessionId) {
            seatArea.style.display = 'block';
            loadSeats(sessionId);
        } else {
            seatArea.style.display = 'none';
        }
    });

    // C. Koltukları Çizen Fonksiyon
    function loadSeats(sessionId) {
        fetch('api/get-seats.php?session_id=' + sessionId)
            .then(response => response.json())
            .then(data => {
                gridContainer.innerHTML = ''; 
                seatInput.value = '';
                selectedDisplay.innerText = 'Yok';

                // Satırları oluştur (A, B, C...)
                for (let i = 1; i <= data.rows; i++) {
                    const rowDiv = document.createElement('div');
                    rowDiv.className = 'row';
                    
                    const rowLabel = document.createElement('div');
                    rowLabel.className = 'row-label';
                    rowLabel.innerText = String.fromCharCode(64 + i);
                    rowDiv.appendChild(rowLabel);

                    // Sütunları oluştur (1, 2, 3...)
                    for (let j = 1; j <= data.cols; j++) {
                        const seatDiv = document.createElement('div');
                        const seatCode = String.fromCharCode(64 + i) + '-' + j;
                        
                        seatDiv.className = 'seat';
                        
                        // Koltuk dolu mu?
                        if (data.sold.includes(seatCode)) {
                            seatDiv.classList.add('occupied');
                            seatDiv.title = "Dolu";
                        } else {
                            // Boşsa tıklama özelliği ekle
                            seatDiv.onclick = function() { selectSeat(this, seatCode); };
                        }
                        rowDiv.appendChild(seatDiv);
                    }
                    gridContainer.appendChild(rowDiv);
                }
            });
    }

    // D. Tek Koltuk Seçme Mantığı
    function selectSeat(element, code) {
        // Önceki seçimi kaldır (Sadece 1 koltuk seçilebilsin)
        const prev = document.querySelector('.seat.selected');
        if (prev) prev.classList.remove('selected');
        
        // Yeni seçimi yap
        element.classList.add('selected');
        seatInput.value = code;
        selectedDisplay.innerText = code;
    }
</script>

<?php include 'footer.php'; ?>