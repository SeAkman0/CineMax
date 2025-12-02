<?php 
include 'header.php'; 

$error = "";
$success = "";

// --- KAYIT İŞLEMİ (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $session_id = $_POST['session_id'];
    $seat_number = $_POST['seat_number'];

    if (empty($seat_number) || empty($session_id)) {
        $error = "Lütfen tüm seçimleri yapınız!";
    } else {
        // Koltuk dolu mu kontrolü
        $checkStmt = $pdo->prepare("SELECT id FROM tickets WHERE session_id = ? AND seat_number = ?");
        $checkStmt->execute([$session_id, $seat_number]);

        if ($checkStmt->rowCount() > 0) {
            $error = "HATA: Seçilen koltuk ($seat_number) dolu!";
        } else {
            $unique_code = "CNM-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $sql = "INSERT INTO tickets (user_id, session_id, seat_number, verification_code) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$user_id, $session_id, $seat_number, $unique_code])) {
                $success = "Bilet başarıyla kesildi! <br> Kod: <strong>$unique_code</strong>";
            } else {
                $error = "Veritabanı hatası.";
            }
        }
    }
}

// Verileri Çek (Sadece Kullanıcılar ve Filmler)
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

    // 1. Film Seçilince -> Seansları Getir
    filmSelect.addEventListener('change', function() {
        const filmId = this.value;
        
        // Önce temizlik yap
        sessionSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        sessionSelect.disabled = true;
        seatArea.style.display = 'none';

        if (filmId) {
            fetch('ajax-seans-getir.php?film_id=' + filmId)
                .then(response => response.json())
                .then(data => {
                    sessionSelect.innerHTML = '<option value="">Seans Seçiniz...</option>';
                    
                    if(data.length === 0) {
                        sessionSelect.innerHTML = '<option value="">Bu film için aktif seans yok</option>';
                    } else {
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

    // 2. Seans Seçilince -> Koltukları Getir
    sessionSelect.addEventListener('change', function() {
        const sessionId = this.value;
        
        if (sessionId) {
            seatArea.style.display = 'block';
            loadSeats(sessionId);
        } else {
            seatArea.style.display = 'none';
        }
    });

    function loadSeats(sessionId) {
        fetch('ajax-koltuk-getir.php?session_id=' + sessionId)
            .then(response => response.json())
            .then(data => {
                gridContainer.innerHTML = ''; 
                seatInput.value = '';
                selectedDisplay.innerText = 'Yok';

                for (let i = 1; i <= data.rows; i++) {
                    const rowDiv = document.createElement('div');
                    rowDiv.className = 'row';
                    
                    // Harf Etiketi
                    const rowLabel = document.createElement('div');
                    rowLabel.className = 'row-label';
                    rowLabel.innerText = String.fromCharCode(64 + i);
                    rowDiv.appendChild(rowLabel);

                    for (let j = 1; j <= data.cols; j++) {
                        const seatDiv = document.createElement('div');
                        const seatCode = String.fromCharCode(64 + i) + '-' + j;
                        
                        seatDiv.className = 'seat';
                        
                        if (data.sold.includes(seatCode)) {
                            seatDiv.classList.add('occupied');
                            seatDiv.title = "Dolu";
                        } else {
                            seatDiv.onclick = function() { selectSeat(this, seatCode); };
                        }
                        rowDiv.appendChild(seatDiv);
                    }
                    gridContainer.appendChild(rowDiv);
                }
            });
    }

    function selectSeat(element, code) {
        const prev = document.querySelector('.seat.selected');
        if (prev) prev.classList.remove('selected');
        element.classList.add('selected');
        seatInput.value = code;
        selectedDisplay.innerText = code;
    }
</script>

</div>
</body>
</html>