<?php 
// Üst menüyü ve gerekli ayarları sayfaya dahil et.
include 'header.php'; 
?>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<div class="container" style="max-width: 600px; margin: 0 auto; text-align: center;">
    <h1>QR Bilet Kontrol</h1>
    <p style="color:#666;">Bilgisayar veya telefon kamerasını kullanarak bilet üzerindeki QR kodu okutun.</p>

    <div id="reader" style="width: 100%; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);"></div>
    
    <div id="result-area" style="margin-top: 20px; font-weight: bold; font-size: 1.2rem;"></div>
</div>

<audio id="audio-success" src="https://actions.google.com/sounds/v1/cartoon/pop.ogg"></audio>
<audio id="audio-fail" src="https://actions.google.com/sounds/v1/alarms/beep_short.ogg"></audio>


<script>
    // Tarama durumunu kontrol eden değişken.
    // QR kod okunduğunda işlem bitene kadar yeni okumayı engellemek için.
    let isScanning = true; 

    // --- BAŞARILI OKUMA FONKSİYONU ---
    // Kamera bir QR kod tespit ettiğinde bu fonksiyon çalışır.
    // decodedText: Okunan metin (Örn: CNM-A1B2C3)
    function onScanSuccess(decodedText, decodedResult) {
        
        // Eğer şu an işlem yapılıyorsa (popup açıksa), yeni kodu okuma.
        if (!isScanning) return; 

        // Taramayı geçici olarak durdur (Tekrar tekrar aynı kodu okumasın)
        isScanning = false; 

        // --- SUNUCUYA SOR (AJAX/FETCH) ---
        // Okunan kodu PHP dosyasına (API) gönderiyoruz.
        fetch('api/validate-ticket.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: decodedText }) // JSON formatında gönder
        })
        .then(response => response.json()) // Gelen cevabı JSON olarak al
        .then(data => {
            
            // --- DURUM A: GİRİŞ BAŞARILI (YEŞİL) ---
            if (data.status === 'success') {
                document.getElementById('audio-success').play(); // Ses çal
                
                Swal.fire({
                    icon: 'success',
                    title: data.message, // "GİRİŞ ONAYLANDI"
                    html: data.detail,   // "Hoşgeldin: Ahmet <br> Film..." (HTML desteği açık)
                    timer: 5000,         // 5 saniye sonra otomatik kapan
                    showConfirmButton: false
                }).then(() => { 
                    isScanning = true; // Popup kapanınca taramayı tekrar aç
                }); 
            } 
            
            // --- DURUM B: DAHA ÖNCE KULLANILMIŞ (SARI) ---
            else if (data.status === 'warning') {
                document.getElementById('audio-fail').play(); // Hata sesi çal
                
                Swal.fire({
                    icon: 'warning',
                    title: data.message, // "BU BİLET ZATEN KULLANILDI"
                    html: data.detail,   // Giriş saati bilgisi
                    confirmButtonColor: '#f39c12'
                }).then(() => { isScanning = true; });
            } 
            
            // --- DURUM C: GEÇERSİZ / SÜRESİ DOLMUŞ / HATALI (KIRMIZI) ---
            else {
                document.getElementById('audio-fail').play();
                
                Swal.fire({
                    icon: 'error',
                    title: 'HATA',
                    // Mesaj ve detayı birleştirip HTML olarak göster
                    html: data.message + (data.detail ? '<br>' + data.detail : ''), 
                    confirmButtonColor: '#e74c3c'
                }).then(() => { isScanning = true; });
            }
        })
        .catch(err => {
            console.error('Bağlantı Hatası:', err);
            isScanning = true; // Hata olsa bile taramayı aç ki sistem kilitlenmesin
        });
    }

    // --- OKUMA HATASI FONKSİYONU ---
    // Kamera odaklanamazsa veya QR bulamazsa burası çalışır.
    // Kullanıcıyı rahatsız etmemek için genelde boş bırakılır veya konsola yazılır.
    function onScanFailure(error) {
        // console.warn(`Kod okunamadı = ${error}`);
    }

    // --- KAMERAYI BAŞLAT ---
    // 'reader': HTML'deki div'in ID'si
    let html5QrcodeScanner = new Html5QrcodeScanner(
        "reader",
        { 
            fps: 10, // Saniyede kaç kare tarasın (Performans ayarı)
            qrbox: { width: 250, height: 250 } // Tarama alanının boyutu
        },
        /* verbose= */ false
    );
    
    // Tarayıcıyı çalıştır ve sonuçları fonksiyonlara gönder
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
</script>

