    </main>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Streamify</h3>
                    <p>Create, share, and enjoy YouTube playlists with friends and followers.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="help.php">Help</a></li>
                        <li><a href="playlists.php">Playlists</a></li>
                        <li><a href="search.php">Search</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Legal</h3>
                    <ul>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Streamify. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script src="assets/js/script.js"></script>
    <?php if (basename($_SERVER['PHP_SELF']) === 'index.php' || basename($_SERVER['PHP_SELF']) === 'help.php'): ?>
    <script src="assets/js/accordion.js"></script>
    <?php endif; ?>
</body>
</html>
