        </main>
        
        <!-- Footer -->
        <footer class="app-footer mt-5 py-3 text-center">
            <div class="container">
                <p class="mb-0 text-muted">
                    &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($companyName ?? 'Employee Time Tracking System'); ?>
                    <span class="mx-2">|</span>
                    <a href="#" class="text-muted text-decoration-none">Privacy Policy</a>
                    <span class="mx-2">|</span>
                    <a href="#" class="text-muted text-decoration-none">Terms of Service</a>
                </p>
            </div>
        </footer>
    </div>
    
    <!-- Custom Scripts -->
    <script src="js/time-tracking.js"></script>
    <script>
        // Initialize any custom scripts
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scrolling to page
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Add hover effect to cards
            document.querySelectorAll('.app-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>