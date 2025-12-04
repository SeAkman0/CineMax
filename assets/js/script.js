document.addEventListener("DOMContentLoaded", function() {

    /* --- CANLI FİLM ARAMA --- */
    const searchInput = document.getElementById('movieSearchInput');
    
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            const term = e.target.value.toLowerCase().trim(); // Yazılanı al, küçült
            const movies = document.querySelectorAll('.movie-card'); // Filmleri seç

            movies.forEach(function(movie) {
                // Film başlığını bul
                const title = movie.querySelector('.movie-info h3').textContent.toLowerCase();
                
                // Eşleşiyor mu?
                if (title.includes(term)) {
                    movie.style.display = 'block'; // Göster
                    movie.style.animation = 'fadeIn 0.5s'; // Efekt
                } else {
                    movie.style.display = 'none'; // Gizle
                }
            });
        });
    }

    /* --- SCROLL SPY (Menü Parlatma) --- */
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

// CSS Animasyonu (Script ile ekleme)
const style = document.createElement('style');
style.innerHTML = `
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
`;
document.head.appendChild(style);