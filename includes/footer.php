<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>SAYURPKY</h3>
                <p>
                    Menyediakan sayuran organik segar langsung dari petani lokal. Komitmen kami adalah kesehatan keluarga Anda dan kelestarian lingkungan.
                </p>
            </div>

            <div class="footer-section">
                <h3>Link Cepat</h3>
                <ul>
                    <li><a href="<?php echo isset($base_url) ? $base_url : '/SAYURPKY/'; ?>index.php">Beranda</a></li>
                    <li><a href="<?php echo isset($base_url) ? $base_url : '/SAYURPKY/'; ?>pages/produk.php">Produk</a></li>
                    <li><a href="<?php echo isset($base_url) ? $base_url : '/SAYURPKY/'; ?>pages/edukasi.php">Edukasi</a></li>
                    <li><a href="<?php echo isset($base_url) ? $base_url : '/SAYURPKY/'; ?>pages/resep.php">Resep</a></li>
                    <li><a href="<?php echo isset($base_url) ? $base_url : '/SAYURPKY/'; ?>pages/about.php">Tentang Kami</a></li>
                </ul>
            </div>

            <!-- Bantuan section removed per request -->

            <div class="footer-section">
                <h3>Kotak Kami</h3>
                <ul>
                    <li><a href="mailto:info@sayursegar.com">✉️ info@sayursegar.com</a></li>
                    <li><a href="tel:+6282357000187">📞 +62 823-5700-0187</a></li>
                    <li>📍Palangka Raya, Kalimantan Tengah</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2025 SAYURPKY. Semua hak cipta dilindungi.</p>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button onclick="scrollToTop()" id="backToTop" class="btn" style="position: fixed; bottom: 24px; right: 24px; border-radius: 50%; width:48px; height:48px; display:none; justify-content:center; align-items:center;">↑</button>

<script>
// Back to Top functionality
window.onscroll = function() {
    const btn = document.getElementById('backToTop');
    if (!btn) return;
    if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
        btn.style.display = 'flex';
    } else {
        btn.style.display = 'none';
    }
};

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Hover effects handled by CSS; provide small JS fallback for accessibility
const backToTopBtn = document.getElementById('backToTop');
if (backToTopBtn) {
    backToTopBtn.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.05)';
    });
    backToTopBtn.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
    });
}
</script>