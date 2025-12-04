<?php include 'header.php'; ?>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container" style="max-width: 600px; margin: 0 auto; text-align: center;">
    <h1>QR Bilet Kontrol</h1>
    <p style="color:#666;">Bilgisayar kamerasını kullanarak bilet üzerindeki QR kodu okutun.</p>

    <div id="reader" style="width: 100%; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);"></div>
    
    <div id="result-area" style="margin-top: 20px; font-weight: bold; font-size: 1.2rem;"></div>
</div>

<audio id="audio-success" src="https://actions.google.com/sounds/v1/cartoon/pop.ogg"></audio>
<audio id="audio-fail" src="https://actions.google.com/sounds/v1/alarms/beep_short.ogg"></audio>

<script>
    let isScanning = true; 

    function onScanSuccess(decodedText, decodedResult) {
        if (!isScanning) return; 

        isScanning = false; 

        fetch('api/validate-ticket.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: decodedText })
        })
        .then(response => response.json())
        .then(data => {
            
            // --- DÜZELTME BURADA YAPILDI ---
            // 'text:' yerine 'html:' kullanıldı.
            
            if (data.status === 'success') {
                document.getElementById('audio-success').play();
                Swal.fire({
                    icon: 'success',
                    title: data.message,
                    html: data.detail, // <--- HTML OLARAK DEĞİŞTİ
                    timer: 5000,
                    showConfirmButton: false
                }).then(() => { isScanning = true; }); 
            } 
            else if (data.status === 'warning') {
                document.getElementById('audio-fail').play();
                Swal.fire({
                    icon: 'warning',
                    title: data.message,
                    html: data.detail, // <--- HTML OLARAK DEĞİŞTİ
                    confirmButtonColor: '#f39c12'
                }).then(() => { isScanning = true; });
            } 
            else {
                document.getElementById('audio-fail').play();
                Swal.fire({
                    icon: 'error',
                    title: 'HATA',
                    html: data.message + (data.detail ? '<br>' + data.detail : ''), // <--- HTML OLARAK DEĞİŞTİ
                    confirmButtonColor: '#e74c3c'
                }).then(() => { isScanning = true; });
            }
        })
        .catch(err => {
            console.error(err);
            isScanning = true;
        });
    }

    function onScanFailure(error) {
        // Hata ayıklama için gerekirse burayı açabilirsin
    }

    let html5QrcodeScanner = new Html5QrcodeScanner(
        "reader",
        { 
            fps: 10, 
            qrbox: { width: 250, height: 250 } 
        },
        false
    );
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
</script>

