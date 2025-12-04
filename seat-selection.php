<?php 
include 'config/database.php'; 
include 'includes/header.php';

// Güvenlik: Giriş yapmamışsa login'e at
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

if (!isset($_GET['id'])) { echo "<script>window.location.href='index.php';</script>"; exit; }
$session_id = $_GET['id'];

// Seans, Film ve Salon Bilgilerini Çek
$sql = "SELECT s.*, f.title, h.name as hall_name, h.total_rows, h.total_cols 
        FROM sessions s
        JOIN films f ON s.film_id = f.id
        JOIN halls h ON s.hall_id = h.id
        WHERE s.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) { die("Seans bulunamadı."); }

//zaman kontrolü
if (strtotime($session['start_time']) < time()) {
    echo "<script>
        alert('Bu seansın saati geçmiştir. Bilet alamazsınız.');
        window.location.href = 'booking.php?id=" . $session['film_id'] . "';
    </script>";
    exit;
}

// Satılmış Biletleri Çek
$stmtTickets = $pdo->prepare("SELECT seat_number FROM tickets WHERE session_id = ?");
$stmtTickets->execute([$session_id]);
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
        // SATIR DÖNGÜSÜ (1'den Toplam Satıra Kadar)
        for ($i = 1; $i <= $session['total_rows']; $i++) {
            
            // Matematik: Satır numarasını Harfe çevir (1 -> A, 2 -> B)
            // chr(65) = 'A' demektir.
            $row_letter = chr(64 + $i); 

            echo "<div class='row'>";
            
            // Satırın Başına Harfi Yaz (Örn: A)
            echo "<div class='row-label'>$row_letter</div>";

            // SÜTUN DÖNGÜSÜ
            for ($j = 1; $j <= $session['total_cols']; $j++) {
                
                // Koltuk Kodunu Oluştur: Harf-Sayı (Örn: A-1, B-5)
                $seat_code = $row_letter . '-' . $j; 
                
                // Bu koltuk daha önce satılmış mı? (Veritabanında A-1 var mı?)
                $is_occupied = in_array($seat_code, $sold_seats) ? 'occupied' : '';
                
                // Koltuğu Çiz
                echo "<div class='seat $is_occupied' data-seat='$seat_code'></div>";
            }
            echo "</div>";
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
    const container = document.querySelector('.seat-grid');
    const seats = document.querySelectorAll('.row .seat:not(.occupied)');
    const countDisplay = document.getElementById('selected-seats-display');
    const priceDisplay = document.getElementById('total-price');
    const hiddenInput = document.getElementById('selected_seats_input');
    const checkoutBtn = document.getElementById('checkout-btn');
    
    // PHP'den gelen fiyat bilgisi
    const ticketPrice = <?php echo $session['price']; ?>;

    // Koltuklara tıklama olayı
    container.addEventListener('click', (e) => {
        
        // Sadece koltuklara tıklanırsa işlem yap
        if (e.target.classList.contains('seat')) {
            
            // 1. DURUM: Dolu Koltuğa Tıklanırsa (Titret)
            if (e.target.classList.contains('occupied')) {
                e.target.classList.add('anim-shake');
                setTimeout(() => { e.target.classList.remove('anim-shake'); }, 400);
                return;
            }

            // 2. DURUM: Boş Koltuğa Tıklanırsa (Seç/Bırak ve Pop Yap)
            e.target.classList.toggle('selected');
            
            // Pop animasyonunu ekle
            e.target.classList.add('anim-pop');
            setTimeout(() => { e.target.classList.remove('anim-pop'); }, 300);

            // Hesaplamaları Güncelle
            updateSelectedCount();
        }
    });

    function updateSelectedCount() {
        const selectedSeats = document.querySelectorAll('.row .seat.selected');
        
        // Seçilen koltukların kodlarını al (data-seat="A-1")
        const seatsIndex = [...selectedSeats].map(seat => seat.getAttribute('data-seat'));

        // Ekrana yaz
        countDisplay.innerText = seatsIndex.length > 0 ? seatsIndex.join(', ') : 'Yok';
        
        // Fiyat hesapla
        priceDisplay.innerText = (selectedSeats.length * ticketPrice).toFixed(2);
        
        // Form inputuna yaz
        hiddenInput.value = seatsIndex.join(',');

        // Buton aktif/pasif
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