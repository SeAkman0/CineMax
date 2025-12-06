<footer class="footer">
        <div class="container">
            <div class="footer-content">
                
                <div class="footer-section">
                    <h3>CinemaMax</h3>
                    <p>En iyi sinema deneyimi için buradayız. Vizyondaki en yeni filmleri hemen keşfedin.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

                <div class="footer-section">
                    <h4>Hızlı Linkler</h4>
                    <p><a href="index.php">Ana Sayfa</a></p>
                    <p><a href="login.php">Giriş Yap</a></p>
                    <p><a href="register.php">Kayıt Ol</a></p>
                </div>

                <div class="footer-section">
                    <h4>İletişim</h4>
                    <p><i class="fas fa-envelope"></i> info@cinemamax.com</p>
                    <p><i class="fas fa-map-marker-alt"></i> İstanbul, Türkiye</p>
                </div>

            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 CinemaMax. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            
            // --- 1. MOBİL FOOTER ACCORDION (YENİ) ---
            // Sadece mobil boyutta çalışsın diye kontrol edebiliriz ama CSS zaten hallediyor.
            const footerHeaders = document.querySelectorAll('.footer-section h3, .footer-section h4');

            footerHeaders.forEach(header => {
                header.addEventListener('click', () => {
                    // Sadece mobilde (768px altı) çalışsın
                    if (window.innerWidth <= 768) {
                        const parent = header.parentElement;
                        
                        // Diğerlerini kapat (İsteğe bağlı, hepsi açık kalsın istersen bu bloğu sil)
                        document.querySelectorAll('.footer-section').forEach(item => {
                            if (item !== parent) item.classList.remove('active');
                        });

                        // Tıklananı aç/kapat
                        parent.classList.toggle('active');
                    }
                });
            });


            // --- 2. SCROLL SPY (Senin Kodun) ---
            window.addEventListener('scroll', function() {
                const section = document.getElementById('filmler');
                const navLink = document.querySelector('.nav-menu a[href*="#filmler"]'); 
                const homeLink = document.querySelector('.nav-menu a[href="index.php"]');

                if (!section || !navLink || !homeLink) return;

                let sectionTop = section.offsetTop - 150; 
                let sectionHeight = section.offsetHeight;
                let scrollPosition = window.scrollY;

                if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                    navLink.classList.add('active');
                    homeLink.classList.remove('active');
                } 
                else if (scrollPosition < sectionTop) {
                    navLink.classList.remove('active');
                    homeLink.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>