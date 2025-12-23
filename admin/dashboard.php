<?php 
include 'header.php';

// --- VERİ ÇEKME İŞLEMLERİ ---
$stmtTicket = $pdo->query("SELECT COUNT(*) FROM tickets");
$totalTickets = $stmtTicket->fetchColumn();

$stmtRevenue = $pdo->query("SELECT SUM(s.price) FROM tickets t JOIN sessions s ON t.session_id = s.id");
$totalRevenue = $stmtRevenue->fetchColumn();

$stmtFilms = $pdo->query("SELECT COUNT(*) FROM films");
$totalFilms = $stmtFilms->fetchColumn();

$stmtUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
$totalUsers = $stmtUsers->fetchColumn();

// --- GRAFİK İÇİN VERİ HAZIRLIĞI ---
$sqlTopFilms = "SELECT f.title, COUNT(t.id) as ticket_count, SUM(s.price) as total_earnings
                FROM tickets t
                JOIN sessions s ON t.session_id = s.id
                JOIN films f ON s.film_id = f.id
                GROUP BY f.id
                ORDER BY ticket_count DESC
                LIMIT 5";
$topFilms = $pdo->query($sqlTopFilms)->fetchAll();

// --- YAKLAŞAN SEANSLAR ---
$sqlUpcoming = "SELECT s.id, s.start_time, f.title, h.name as hall_name
                FROM sessions s
                JOIN films f ON s.film_id = f.id
                JOIN halls h ON s.hall_id = h.id
                WHERE s.start_time >= NOW()
                ORDER BY s.start_time ASC
                LIMIT 10"; 
$upcomingSessions = $pdo->query($sqlUpcoming)->fetchAll();

// --- PHP DİZİLERİNİ JSON'A ÇEVİRME ---
$chartLabels = [];
$chartTickets = [];
$chartRevenue = [];

foreach($topFilms as $film) {
    $shortName = strlen($film['title']) > 15 ? substr($film['title'], 0, 15).'...' : $film['title'];
    $chartLabels[] = $shortName;
    $chartTickets[] = $film['ticket_count'];
    $chartRevenue[] = $film['total_earnings'];
}

// --- SON SATIŞLAR (LİMİTİ ARTIRDIM ÇÜNKÜ ARTIK SCROLL VAR) ---
$sqlRecentSales = "SELECT t.*, u.username, f.title, s.start_time, s.price, t.verification_code
                   FROM tickets t
                   JOIN users u ON t.user_id = u.id
                   JOIN sessions s ON t.session_id = s.id
                   JOIN films f ON s.film_id = f.id
                   ORDER BY t.id DESC LIMIT 20";
$recentSales = $pdo->query($sqlRecentSales)->fetchAll();
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* GENEL KARTLAR */
    .dashboard-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .card {
        background: #fff; padding: 20px; border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        display: flex; justify-content: space-between; align-items: center;
    }
    .card-info h3 { margin: 0; font-size: 1.8rem; color: #333; }
    .card-info p { margin: 0; color: #777; font-size: 0.9rem; }
    .card-icon { font-size: 2.2rem; opacity: 0.3; }

    /* ORTAK KUTU STİLLERİ (Grafik, Tablo, Liste Hepsi Aynı) */
    .box-container {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        height: 400px; /* HEPSİ EŞİT YÜKSEKLİKTE */
        position: relative;
        display: flex;
        flex-direction: column;
    }

    /* GRAFİK ALANI GRID */
    .charts-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .box-container canvas {
        flex: 1;
        width: 100% !important;
        max-height: 320px !important;
    }

    /* TABLO VE LİSTE GRUPLARI GRID */
    .dashboard-tables-grid {
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); 
        gap: 30px; 
        align-items: start;
        margin-bottom: 30px;
    }

    /* SCROLL (KAYDIRMA) TASARIMI */
    .scroll-container {
        flex: 1; /* Kalan boşluğu doldur */
        overflow-y: auto; /* Dikey kaydırma aç */
        padding-right: 5px;
    }
    .scroll-container::-webkit-scrollbar { width: 6px; }
    .scroll-container::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .scroll-container::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
    .scroll-container::-webkit-scrollbar-thumb:hover { background: #aaa; }

    /* TABLO STİLLERİ */
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
    
    /* YAPIŞKAN TABLO BAŞLIĞI (Sticky Header) */
    th {
        position: sticky;
        top: 0;
        background: #fff; /* Arkası şeffaf olmasın diye */
        z-index: 2; /* İçeriğin üstünde dursun */
        box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1); /* Hafif gölge */
    }

    /* ALT LİNKLER (Tümünü Gör vb.) */
    .box-footer-link {
        text-align: center; 
        margin-top: 15px; 
        padding-top: 10px; 
        border-top: 1px solid #eee;
    }
    .box-footer-link a {
        text-decoration: none; 
        color: #3498db; 
        font-size: 0.9rem; 
        font-weight: bold;
    }

    /* EMOJI BUTONLARI */
    .quick-action-btn {
        text-decoration: none;
        background: #f8f9fa;
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 15px 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .quick-action-btn:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-color: #3498db;
        background: #fff;
    }
    .quick-action-btn .emoji { font-size: 2rem; margin-bottom: 8px; display: block; }
    .quick-action-btn .text { color: #555; font-weight: 600; font-size: 0.9rem; }
</style>

<h1>Yönetim Paneli</h1>

<div class="dashboard-cards">
    <div class="card">
        <div class="card-info">
            <h3><?php echo $totalTickets ?: 0; ?></h3>
            <p>Satılan Bilet</p>
        </div>
        <div class="card-icon" style="color: #3498db;"><i class="fas fa-ticket-alt"></i></div>
    </div>
    <div class="card">
        <div class="card-info">
            <h3 style="color:#2ecc71;"><?php echo number_format($totalRevenue ?: 0, 0); ?> ₺</h3>
            <p>Toplam Hasılat</p>
        </div>
        <div class="card-icon" style="color: #2ecc71;"><i class="fas fa-money-bill-wave"></i></div>
    </div>
    <div class="card">
        <div class="card-info">
            <h3><?php echo $totalFilms; ?></h3>
            <p>Aktif Film</p>
        </div>
        <div class="card-icon" style="color: #e74c3c;"><i class="fas fa-film"></i></div>
    </div>
    <div class="card">
        <div class="card-info">
            <h3><?php echo $totalUsers; ?></h3>
            <p>Kullanıcı</p>
        </div>
        <div class="card-icon" style="color: #f1c40f;"><i class="fas fa-users"></i></div>
    </div>
</div>

<div class="charts-container">
    <div class="box-container"> <h3 style="margin-top:0; color:#555;">🎟️ En Çok Bilet Satan Filmler</h3>
        <canvas id="topFilmsChart"></canvas>
    </div>

    <div class="box-container">
        <h3 style="margin-top:0; color:#555;">💰 Hasılat Dağılımı (Top 5)</h3>
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<div class="dashboard-tables-grid">

    <div class="box-container">
        <h3>⏳ Yaklaşan Seanslar</h3>
        
        <div class="scroll-container">
            <?php if(count($upcomingSessions) > 0): ?>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php foreach($upcomingSessions as $session): ?>
                        <?php 
                            $start = strtotime($session['start_time']);
                            $now = time();
                            $diffMins = round(($start - $now) / 60);
                            
                            $isToday = (date("Y-m-d", $start) == date("Y-m-d", $now));
                            $isTomorrow = (date("Y-m-d", $start) == date("Y-m-d", strtotime("+1 day")));

                            if ($diffMins <= 60 && $diffMins > 0) {
                                $badgeText = $diffMins . ' dk kaldı';
                                $badgeColor = ($diffMins <= 30) ? '#e74c3c' : '#f39c12';
                            } else {
                                if ($isToday) {
                                    $badgeText = "Bugün " . date("H:i", $start);
                                    $badgeColor = '#2ecc71';
                                } elseif ($isTomorrow) {
                                    $badgeText = "Yarın " . date("H:i", $start);
                                    $badgeColor = '#3498db';
                                } else {
                                    $badgeText = date("d.m.Y H:i", $start);
                                    $badgeColor = '#95a5a6';
                                }
                            }
                        ?>
                        <li style="display: flex; align-items: center; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #eee;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="background: #f1f2f6; width: 55px; height: 55px; display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 8px; color: #555; line-height: 1.2;">
                                    <span style="font-size: 0.75rem; color: #888;"><?php echo date("d.m", $start); ?></span>
                                    <strong style="font-size: 1rem; color: #333;"><?php echo date("H:i", $start); ?></strong>
                                </div>
                                <div>
                                    <strong style="display: block; font-size: 1rem; color: #333;"><?php echo $session['title']; ?></strong>
                                    <span style="font-size: 0.85rem; color: #777;">
                                        <i class="fas fa-chair"></i> <?php echo $session['hall_name']; ?>
                                    </span>
                                </div>
                            </div>
                            <span style="background: <?php echo $badgeColor; ?>; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; white-space: nowrap;">
                                <?php echo $badgeText; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="text-align:center; color:#999; padding:20px;">Yakın zamanda planlanmış seans yok.</p>
            <?php endif; ?>
        </div>
        
        <div class="box-footer-link">
            <a href="showtimes.php">Tüm Seansları Gör <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>

    <div class="box-container">
        <h3>🚀 Hızlı İşlemler</h3>
        <div class="scroll-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 15px; align-content: start;">
            <a href="movies.php" class="quick-action-btn"><span class="emoji">🎬</span> <span class="text">Film Yönetimi</span></a>
            <a href="halls.php" class="quick-action-btn"><span class="emoji">💺</span> <span class="text">Salon Yönetimi</span></a>
            <a href="showtimes.php" class="quick-action-btn"><span class="emoji">⏰</span> <span class="text">Seans Ekle</span></a>
            <a href="scan-ticket.php" class="quick-action-btn"><span class="emoji">📱</span> <span class="text">QR Okut</span></a>
            <a href="users.php" class="quick-action-btn"><span class="emoji">👥</span> <span class="text">Üye Yönetimi</span></a>
            <a href="../index.php" target="_blank" class="quick-action-btn" style="background: #fff8e1; border-color: #ffe082;">
                <span class="emoji">🌍</span> <span class="text">Siteyi Aç</span>
            </a>
        </div>
    </div>

</div>

<div style="margin-bottom: 50px;"> <div class="box-container">
        <h3>🕒 Son Satış İşlemleri</h3>
        
        <div class="scroll-container">
            <table>
                <thead>
                    <tr>
                        <th>Kullanıcı</th>
                        <th>Film</th>
                        <th>Koltuk</th>
                        <th>Tutar</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recentSales as $sale): ?>
                    <tr>
                        <td><?php echo $sale['username']; ?></td>
                        <td><?php echo $sale['title']; ?></td>
                        <td><span style="background:#eee; padding:2px 6px; border-radius:4px;"><?php echo $sale['seat_number']; ?></span></td>
                        <td style="color:#2ecc71; font-weight:bold;">+<?php echo $sale['price']; ?> ₺</td>
                        <td style="color:#999; font-size:0.85rem;"><?php echo date("d.m H:i", strtotime($sale['purchase_time'] ?? 'now')); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(count($recentSales) == 0): echo '<tr><td colspan="5">Veri yok.</td></tr>'; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="box-footer-link">
            <a href="tickets.php">Tüm Satış Geçmişi <i class="fas fa-history"></i></a>
        </div>
    </div>
</div>

<script>
    const labels = <?php echo json_encode($chartLabels); ?>;
    const ticketData = <?php echo json_encode($chartTickets); ?>;
    const revenueData = <?php echo json_encode($chartRevenue); ?>;

    const ctx1 = document.getElementById('topFilmsChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Satılan Bilet',
                data: ticketData,
                backgroundColor: '#3498db',
                borderRadius: 5,
                barThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    const ctx2 = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                label: 'Hasılat (TL)',
                data: revenueData,
                backgroundColor: ['#e74c3c', '#2ecc71', '#f1c40f', '#9b59b6', '#34495e'],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: 10 },
            plugins: {
                legend: { position: 'right' }
            }
        }
    });
</script>

<?php 
// include 'footer.php'; 
?>