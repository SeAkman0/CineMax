<?php 
include 'config/db.php'; 
include 'header.php'; 

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// SQL Sorgusu: Film Durumu (is_active) ve Bilet Durumu (is_used) çekiliyor
$sql = "SELECT t.*, s.start_time, f.title, f.poster_url, f.is_active, h.name as hall_name 
        FROM tickets t
        JOIN sessions s ON t.session_id = s.id
        JOIN films f ON s.film_id = f.id
        JOIN halls h ON s.hall_id = h.id
        WHERE t.user_id = ?
        ORDER BY t.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll();
?>

<div class="container" style="padding: 60px 20px;">
    
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2 class="section-title" style="margin:0;">Biletlerim</h2>
        
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <span style="background:#2ecc71; color:white; padding:5px 15px; border-radius:20px; font-size:0.9rem;">
                <i class="fas fa-check"></i> Bilet iptal edildi.
            </span>
        <?php endif; ?>
    </div>

    <?php if (count($tickets) == 0): ?>
        <div style="text-align: center; padding: 50px; color: #666; background:white; border-radius:15px;">
            <i class="fas fa-ticket-alt" style="font-size: 50px; margin-bottom: 20px; opacity: 0.5;"></i>
            <p>Henüz satın aldığınız bir bilet yok.</p>
            <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">Hemen Bilet Al</a>
        </div>
    
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px;">
            
            <?php foreach($tickets as $ticket): ?>
            
            <?php 
                // --- DURUM MANTIĞI (0-1-2-3) ---
                $simdi = time();
                $seans_zamani = strtotime($ticket['start_time']);
                $durum_kodu = 0; // Varsayılan: Aktif

                if ($ticket['is_active'] == 0) {
                    $durum_kodu = 2; // Vizyondan Kalktı
                } elseif ($ticket['is_used'] == 1) {
                    $durum_kodu = 1; // Kullanıldı
                } elseif ($seans_zamani < $simdi) {
                    $durum_kodu = 3; // Süresi Doldu
                } else {
                    $durum_kodu = 0; // Aktif
                }

                // --- TASARIM AYARLARI ---
                switch ($durum_kodu) {
                    case 2: // Vizyondan Kalktı (KIRMIZI)
                        $badge_bg = "#e74c3c"; 
                        $badge_text = "VİZYONDAN KALKTI";
                        $card_style = "opacity: 0.6; background: #fbeaea; border: 1px solid #e74c3c;";
                        $qr_style = "display:none;"; // QR Yok
                        break;
                    case 3: // Zamanı Geçti (TURUNCU)
                        $badge_bg = "#e67e22";
                        $badge_text = "SÜRESİ DOLDU";
                        $card_style = "opacity: 0.7; background: #fdf2e9; border: 1px solid #d35400;";
                        $qr_style = "filter: grayscale(100%); opacity: 0.2;";
                        break;
                    case 1: // Kullanıldı (GRİ)
                        $badge_bg = "#7f8c8d";
                        $badge_text = "KULLANILDI";
                        $card_style = "opacity: 0.8; background: #f4f6f9; border: 1px solid #ccc;";
                        $qr_style = "filter: grayscale(100%); opacity: 0.3;";
                        break;
                    case 0: // Aktif (YEŞİL)
                    default:
                        $badge_bg = "#2ecc71";
                        $badge_text = "AKTİF";
                        $card_style = "background: white; border: 1px solid #eee;";
                        $qr_style = "";
                        break;
                }
            ?>

            <div style="<?php echo $card_style; ?> border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: flex; transition:0.3s; position:relative; margin-bottom: 20px;">
                
                <div style="position:absolute; top:10px; left:10px; z-index:10;">
                    <span style="background:<?php echo $badge_bg; ?>; color:white; padding:4px 12px; border-radius:15px; font-size:0.75rem; font-weight:bold; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                        <?php echo $badge_text; ?>
                    </span>
                </div>

                <div style="width: 100px; background: url('<?php echo $ticket['poster_url']; ?>') center center/cover; <?php echo ($durum_kodu != 0) ? 'filter: grayscale(100%);' : ''; ?>"></div>
                
                <div style="padding: 20px; flex: 1; padding-top: 40px;">
                    <h3 style="font-size: 1.1rem; margin-bottom: 10px; color: #333; font-weight:700;"><?php echo $ticket['title']; ?></h3>
                    
                    <div style="font-size: 0.9rem; color: #666; line-height: 1.8;">
                        <div><i class="fas fa-calendar" style="width: 20px; color: #1e90ff;"></i> <?php echo date("d.m.Y", strtotime($ticket['start_time'])); ?></div>
                        <div><i class="fas fa-clock" style="width: 20px; color: #1e90ff;"></i> <?php echo date("H:i", strtotime($ticket['start_time'])); ?></div>
                        <div><i class="fas fa-map-marker-alt" style="width: 20px; color: #1e90ff;"></i> <?php echo $ticket['hall_name']; ?></div>
                        
                        <div style="margin-top: 15px; font-weight: bold; color: #333; display:flex; justify-content:space-between; align-items:center;">
                            <span>Koltuk: <span style="background: #1e90ff; color: white; padding: 2px 8px; border-radius: 5px;"><?php echo $ticket['seat_number']; ?></span></span>
                            
                            <?php if($durum_kodu == 0): ?>
                                <form action="bilet-iptal.php" method="POST" onsubmit="return confirm('Silmek istediğinize emin misiniz?');" style="margin:0;">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                    <button type="submit" style="background:none; border:none; color:#ff4757; cursor:pointer; font-size:1.2rem;" title="İptal Et"><i class="fas fa-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:0.75rem; color:#aaa; margin-top:5px;">Kod: <?php echo $ticket['verification_code']; ?></div>
                    </div>
                </div>

                <div style="width: 130px; display:flex; flex-direction:column; align-items:center; justify-content:center; border-left:1px dashed #ddd; background:#f9f9f9; padding:10px; <?php echo $qr_style; ?>">
                    <?php if($durum_kodu == 2 || $durum_kodu == 3): ?>
                        <i class="fas fa-ban" style="font-size:40px; color:#aaa;"></i>
                        <span style="font-size:0.6rem; color:#aaa; margin-top:5px; font-weight:bold;">GEÇERSİZ</span>
                    <?php else: ?>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo $ticket['verification_code']; ?>" alt="QR" style="width:80px; height:80px; mix-blend-mode: multiply;">
                        <span style="font-size:0.7rem; color:#666; margin-top:5px;">
                            <?php echo ($durum_kodu == 1) ? 'KULLANILDI' : 'OKUTUNUZ'; ?>
                        </span>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>