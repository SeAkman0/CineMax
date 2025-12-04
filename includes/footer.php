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
</body>
</html>

<script>
        document.addEventListener("DOMContentLoaded", function() {
            
            window.addEventListener('scroll', function() {
                
                // 1. Gerekli Elemanları Seç
                const section = document.getElementById('filmler');

                // DÜZELTME BURADA: Sadece '.nav-menu' sınıfının içindeki linkleri seçiyoruz.
                // Böylece logoyu değil, menü düğmelerini hedefliyoruz.
                const navLink = document.querySelector('.nav-menu a[href*="#filmler"]'); 
                const homeLink = document.querySelector('.nav-menu a[href="index.php"]');

                // 2. Güvenlik
                if (!section || !navLink || !homeLink) {
                    return;
                }

                // 3. Konum Hesaplamaları
                let sectionTop = section.offsetTop - 150; 
                let sectionHeight = section.offsetHeight;
                let scrollPosition = window.scrollY;

                // 4. Mantık
                if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                    // Filmler'i yak, Ana Sayfa'yı söndür
                    navLink.classList.add('active');
                    homeLink.classList.remove('active');
                } 
                else if (scrollPosition < sectionTop) {
                    // Filmler'i söndür, Ana Sayfa'yı yak
                    navLink.classList.remove('active');
                    homeLink.classList.add('active');
                }
            });
        });
    </script>

    <script src="assets/js/script.js"></script>
</body>
</html>
