<?php 
include 'header.php'; 

// 1. İstatistik Sorguları

// Toplam Bilet Sayısı
$stmtTicket = $pdo->query("SELECT COUNT(*) FROM tickets");
$totalTickets = $stmtTicket->fetchColumn();

// Toplam Hasılat (Ciro) - Bilet fiyatı seans tablosunda olduğu için JOIN yapıyoruz
$stmtRevenue = $pdo->query("SELECT SUM(s.price) FROM tickets t JOIN sessions s ON t.session_id = s.id");
$totalRevenue = $stmtRevenue->fetchColumn();

// Toplam Film Sayısı
$stmtFilms = $pdo->query("SELECT COUNT(*) FROM films");
$totalFilms = $stmtFilms->fetchColumn();

// Toplam Kullanıcı Sayısı
$stmtUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
$totalUsers = $stmtUsers->fetchColumn();

// 2. En Çok İzlenen Filmler (Top 5)
$sqlTopFilms = "SELECT f.title, COUNT(t.id) as ticket_count, SUM(s.price) as total_earnings
                FROM tickets t
                JOIN sessions s ON t.session_id = s.id
                JOIN films f ON s.film_id = f.id
                GROUP BY f.id
                ORDER BY ticket_count DESC
                LIMIT 5";
$topFilms = $pdo->query($sqlTopFilms)->fetchAll();

// 3. Son Satılan Biletler (Son 10)
$sqlRecentSales = "SELECT t.*, u.username, f.title, s.start_time, s.price
                   FROM tickets t
                   JOIN users u ON t.user_id = u.id
                   JOIN sessions s ON t.session_id = s.id
                   JOIN films f ON s.film_id = f.id
                   ORDER BY t.id DESC
                   LIMIT 10";
$recentSales = $pdo->query($sqlRecentSales)->fetchAll();
?>

<style>
    /* Dashboard Kartları */
    .dashboard-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    .card {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .card-info h3 { margin: 0; font-size: 2rem; color: #333; }
    .card-info p { margin: 0; color: #777; font-size: 0.9rem; }
    .card-icon {
        font-size: 2.5rem;
        opacity: 0.3;
    }
    
    /* Tablolar */
    .dashboard-tables {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 30px;
    }
    .table-box {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .table-box h3 { margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 0.9rem; }
    th { color: #555; font-weight: 600; }
    
    /* Renkler */
    .text-green { color: #2ecc71; }
    .bg-green { background-color: #eafaf1; color: #2ecc71; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: bold; }
</style>

<h1>Yönetim Paneli</h1>
<p style="margin-bottom: 30px; color: #666;">Sinema sisteminizin genel durumunu buradan takip edebilirsiniz.</p>

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
            <h3 class="text-green"><?php echo number_format($totalRevenue ?: 0, 2); ?> ₺</h3>
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
            <p>Kayıtlı Kullanıcı</p>
        </div>
        <div class="card-icon" style="color: #f1c40f;"><i class="fas fa-users"></i></div>
    </div>
</div>

<div class="dashboard-tables">
    
    <div class="table-box">
        <h3>🏆 En Çok İzlenen Filmler</h3>
        <table>
            <thead>
                <tr>
                    <th>Film Adı</th>
                    <th>Bilet Adedi</th>
                    <th>Kazanç</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($topFilms as $film): ?>
                <tr>
                    <td><?php echo $film['title']; ?></td>
                    <td><strong><?php echo $film['ticket_count']; ?></strong></td>
                    <td class="text-green"><?php echo number_format($film['total_earnings'], 2); ?> ₺</td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(count($topFilms) == 0): ?>
                    <tr><td colspan="3" style="text-align:center;">Henüz satış yok.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-box">
        <h3>🕒 Son Satılan Biletler</h3>
        <table>
            <thead>
                <tr>
                    <th>Kullanıcı</th>
                    <th>Film</th>
                    <th>Tarih</th>
                    <th>Tutar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recentSales as $sale): ?>
                <tr>
                    <td><?php echo $sale['username']; ?></td>
                    <td><?php echo $sale['title']; ?></td>
                    <td><?php echo date("d.m H:i", strtotime($sale['purchase_time'])); ?></td>
                    <td><span class="bg-green">+<?php echo $sale['price']; ?> ₺</span></td>
                </tr>
                <?php endforeach; ?>

                <?php if(count($recentSales) == 0): ?>
                    <tr><td colspan="4" style="text-align:center;">Henüz işlem yok.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</div> </body>
</html>