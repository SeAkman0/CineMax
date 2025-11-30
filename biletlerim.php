<?php 
include 'config/db.php'; 
include 'header.php'; 

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Kullanıcının biletlerini çek
$sql = "SELECT t.*, s.start_time, f.title, f.poster_url, h.name as hall_name 
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
            <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: flex; border: 1px solid #eee; transition:0.3s; position:relative;">
                
                <div style="width: 100px; background: url('<?php echo $ticket['poster_url']; ?>') center center/cover;"></div>
                
                <div style="padding: 20px; flex: 1;">
                    <h3 style="font-size: 1.1rem; margin-bottom: 10px; color: #333; font-weight:700;"><?php echo $ticket['title']; ?></h3>
                    
                    <div style="font-size: 0.9rem; color: #666; line-height: 1.8;">
                        <div><i class="fas fa-calendar" style="width: 20px; color: #1e90ff;"></i> <?php echo date("d.m.Y", strtotime($ticket['start_time'])); ?></div>
                        <div><i class="fas fa-clock" style="width: 20px; color: #1e90ff;"></i> <?php echo date("H:i", strtotime($ticket['start_time'])); ?></div>
                        <div><i class="fas fa-map-marker-alt" style="width: 20px; color: #1e90ff;"></i> <?php echo $ticket['hall_name']; ?></div>
                        
                        <div style="margin-top: 15px; font-weight: bold; color: #333; display:flex; justify-content:space-between; align-items:center;">
                            <span>Koltuk: <span style="background: #1e90ff; color: white; padding: 2px 8px; border-radius: 5px;"><?php echo $ticket['seat_number']; ?></span></span>
                            
                            <form action="bilet-iptal.php" method="POST" onsubmit="return confirm('Bu bileti silmek istediğinize emin misiniz?');" style="margin:0;">
                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                <button type="submit" style="background:none; border:none; color:#ff4757; cursor:pointer; font-size:1.2rem; padding:5px;" title="Bileti İptal Et">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>