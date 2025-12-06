<?php 
// =======================================================
//  1. AYARLAR VE GEREKLİ DOSYALAR
// =======================================================

// Veritabanı bağlantısı ve üst menü (header) kodlarını sayfaya dahil ediyoruz.
include 'config/database.php'; 
include 'includes/header.php';

// --- GÜVENLİK KONTROLÜ ---
// Eğer kullanıcı giriş yapmamışsa (session'da user_id yoksa),
// bu sayfayı görmesine izin verme ve giriş sayfasına yönlendir.
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

// Eğer URL'de ?id=... (seans ID'si) yoksa, kullanıcıyı ana sayfaya at.
if (!isset($_GET['id'])) { echo "<script>window.location.href='index.php';</script>"; exit; }
$session_id = $_GET['id']; // Seçilen seansın ID'si

// =======================================================
//  2. SEANS BİLGİLERİNİ ÇEKME
// =======================================================

// Seans bilgilerini, filmin adını ve salonun kapasitesini (satır/sütun sayısı) çekiyoruz.
// JOIN kullanarak 3 tabloyu (Sessions, Films, Halls) birleştiriyoruz.
$sql = "SELECT s.*, f.title, s.film_id, h.name as hall_name, h.total_rows, h.total_cols 
        FROM sessions s
        JOIN films f ON s.film_id = f.id
        JOIN halls h ON s.hall_id = h.id
        WHERE s.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$session_id]);
$session = $stmt->fetch();

// Eğer böyle bir seans yoksa durdur.
if (!$session) { die("Seans bulunamadı."); }

// --- ZAMAN KONTROLÜ (GÜVENLİK) ---
// Eğer seansın saati geçmişse, kullanıcı URL ile girmeye çalışsa bile engelle.
if (strtotime($session['start_time']) < time()) {
    echo "<script>
        alert('Bu seansın saati geçmiştir. Bilet alamazsınız.');
        window.location.href = 'booking.php?id=" . $session['film_id'] . "';
    </script>";
    exit;
}

// =======================================================
//  3. SATILMIŞ BİLETLERİ ÇEKME
// =======================================================

// Bu seansta daha önce hangi koltuklar satılmış? (Örn: ['A-1', 'B-5'])
// Bu bilgiyi alıp, aşağıda o koltukları "Dolu" (Kırmızı) olarak göstereceğiz.
$stmtTickets = $pdo->prepare("SELECT seat_number FROM tickets WHERE session_id = ?");
$stmtTickets->execute([$session_id]);
// FETCH_COLUMN: Bize sadece koltuk numaralarından oluşan düz bir liste (array) verir.
$sold_seats = $stmtTickets->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container" style="padding: 40px 20px;">
    
    <h2 style="text-align:center; margin-bottom:10px;"><?php echo $session['title']; ?></h2>
    <p style="text-align:center; color:#666; margin-bottom:30px;">
        <?php echo $session['hall_name']; ?> | 
        <?php echo date("d.m.Y H:i", strtotime($session['start_time'])); ?>
    </p>

    <div class="seat-info">
        <div><span class="seat-sample" style="background:#444;"></span> Boş</div>
        <div><span class="seat-sample" style="background:#1e90ff;"></span> Seçili</div>
        <div><span class="seat-sample" style="background:#ff4757;"></span> Dolu</div>
    </div>

    <div style="perspective: 1000px; max-width:600px; margin:0 auto;">
        <div class="screen">PERDE</div>
    </div>

    <div class="seat-grid">
        <?php 
        // --- DÖNGÜ İLE KOLTUKLARI ÇİZME ---
        
        // 1. Dış Döngü: SATIRLAR (Örn: 1'den 10'a kadar)
        for ($i = 1; $i <= $session['total_rows']; $i++) {
            
            // Matematik: Sayıyı Harfe Çevirme (1 -> A, 2 -> B, 3 -> C...)
            // ASCII Kod tablosunda 65 sayısı 'A' harfine karşılık gelir.
            $row_letter = chr(64 + $i); 

            echo "<div class='row'>";
            
            // Satırın başına harfi yaz (A, B, C...)
            echo "<div class='row-label'>$row_letter</div>";

            // 2. İç Döngü: SÜTUNLAR (Koltuklar) (Örn: 1'den 15'e kadar)
            for ($j = 1; $j <= $session['total_cols']; $j++) {
                
                // Koltuk Kodunu Oluştur: Harf-Sayı (Örn: A-1, B-5)
                $seat_code = $row_letter . '-' . $j; 
                
                // Bu koltuk daha önce satılmış mı? (Veritabanından gelen listede var mı?)
                // Varsa 'occupied' sınıfı ekle (Kırmızı olur), yoksa boş bırak.
                $is_occupied = in_array($seat_code, $sold_seats) ? 'occupied' : '';
                
                // Koltuk Kutusunu (Div) Oluştur
                // data-seat niteliği ile koltuk kodunu HTML'e gömüyoruz (JS için).
                echo "<div class='seat $is_occupied' data-seat='$seat_code'></div>";
            }
            echo "</div>"; // Satır Bitişi
        }
        ?>
    </div>

    <div style="text-align:center; margin-top:40px; padding:20px; background:white; border-radius:10px; max-width:400px; margin-left:auto; margin-right:auto; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
        
        <p>Seçilen Koltuklar: <span id="selected-seats-display" style="font-weight:bold; color:#1e90ff;">Yok</span></p>
        
        <p>Toplam Tutar: <span id="total-price" style="font-weight:bold; font-size:1.5rem;">0</span> TL</p>
        
        <form action="checkout.php" method="POST" id="booking-form">
            <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
            <input type="hidden" name="selected_seats" id="selected_seats_input">
            
            <button type="submit" class="btn btn-primary" id="checkout-btn" disabled style="width:100%; margin-top:10px; opacity: 0.5; cursor: not-allowed;">Ödeme Yap</button>
        </form>
    </div>

</div>

<script>
    // HTML elemanlarını seçiyoruz
    const container = document.querySelector('.seat-grid');
    const countDisplay = document.getElementById('selected-seats-display');
    const priceDisplay = document.getElementById('total-price');
    const hiddenInput = document.getElementById('selected_seats_input');
    const checkoutBtn = document.getElementById('checkout-btn');
    
    // PHP'den gelen bilet fiyatını JS değişkenine alıyoruz
    const ticketPrice = <?php echo $session['price']; ?>;

    // --- KOLTUK TIKLAMA OLAYI ---
    container.addEventListener('click', (e) => {
        
        // Sadece 'seat' sınıfına sahip bir şeye tıklandıysa çalış
        if (e.target.classList.contains('seat')) {
            
            // 1. DURUM: Dolu Koltuğa Tıklanırsa
            if (e.target.classList.contains('occupied')) {
                // Titreme (Shake) animasyonunu ekle
                e.target.classList.add('anim-shake');
                // 0.4 saniye sonra animasyon sınıfını sil (Tekrar çalışabilsin diye)
                setTimeout(() => { e.target.classList.remove('anim-shake'); }, 400);
                return; // İşlemi durdur, seçme yapma.
            }

            // 2. DURUM: Boş Koltuğa Tıklanırsa
            // 'selected' sınıfını varsa sil, yoksa ekle (Toggle)
            e.target.classList.toggle('selected');
            
            // Pop (Büyüme) animasyonunu ekle
            e.target.classList.add('anim-pop');
            setTimeout(() => { e.target.classList.remove('anim-pop'); }, 300);

            // Hesaplamaları Güncelle (Aşağıdaki fonksiyonu çağır)
            updateSelectedCount();
        }
    });

    // --- HESAPLAMA FONKSİYONU ---
    function updateSelectedCount() {
        // Sayfadaki tüm 'selected' sınıfına sahip koltukları bul
        const selectedSeats = document.querySelectorAll('.row .seat.selected');
        
        // Bu koltukların 'data-seat' (Örn: A-1) değerlerini alıp bir diziye at
        const seatsIndex = [...selectedSeats].map(seat => seat.getAttribute('data-seat'));

        // Ekrana yaz (Dizi boşsa 'Yok', doluysa virgülle ayırıp yaz)
        countDisplay.innerText = seatsIndex.length > 0 ? seatsIndex.join(', ') : 'Yok';
        
        // Fiyatı hesapla (Seçilen Sayısı * Bilet Fiyatı)
        priceDisplay.innerText = (selectedSeats.length * ticketPrice).toFixed(2);
        
        // Formun gizli inputuna veriyi yaz (PHP'ye böyle gidecek: "A-1,B-5")
        hiddenInput.value = seatsIndex.join(',');

        // Buton Kontrolü: En az 1 koltuk seçildiyse butonu aktif et
        if (selectedSeats.length > 0) {
            checkoutBtn.disabled = false;
            checkoutBtn.style.opacity = "1";
            checkoutBtn.style.cursor = "pointer";
        } else {
            checkoutBtn.disabled = true;
            checkoutBtn.style.opacity = "0.5";
            checkoutBtn.style.cursor = "not-allowed";
        }
    }
</script>

<?php include 'includes/footer.php'; ?>