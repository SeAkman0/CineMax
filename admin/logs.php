<?php 
include 'header.php'; 

// Logları Çek (Kullanıcı adlarıyla birleştirerek - JOIN)
// En son giren en üstte görünsün (DESC)
$sql = "SELECT l.*, u.username, u.email 
        FROM login_logs l 
        JOIN users u ON l.user_id = u.id 
        ORDER BY l.login_time DESC 
        LIMIT 50";
$logs = $pdo->query($sql)->fetchAll();
?>

<h1>Giriş Logları (Son 50)</h1>
<p style="color:#666; margin-bottom:20px;">Sisteme giriş yapan kullanıcıların hareket dökümü.</p>

<div class="table-box" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="background:#f8f9fa; border-bottom:2px solid #eee;">
                <th style="padding:12px; text-align:left;">Kullanıcı</th>
                <th style="padding:12px; text-align:left;">E-posta</th>
                <th style="padding:12px; text-align:left;">IP Adresi</th>
                <th style="padding:12px; text-align:left;">Tarih & Saat</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($logs as $log): ?>
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:12px;">
                    <strong style="color:#333;"><?php echo $log['username']; ?></strong>
                    <?php if($log['username'] == 'admin'): ?>
                        <span style="background:#e74c3c; color:white; padding:2px 6px; border-radius:4px; font-size:0.7rem; margin-left:5px;">Admin</span>
                    <?php endif; ?>
                </td>
                <td style="padding:12px; color:#555;"><?php echo $log['email']; ?></td>
                <td style="padding:12px; color:#666; font-family:monospace;"><?php echo $log['ip_address']; ?></td>
                <td style="padding:12px; color:#1e90ff;">
                    <i class="fas fa-clock"></i> <?php echo date("d.m.Y H:i:s", strtotime($log['login_time'])); ?>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if(count($logs) == 0): ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding:20px;">Henüz kayıt bulunmuyor.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div> </body>
</html>