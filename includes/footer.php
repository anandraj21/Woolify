<footer class="footer bg-white border-t border-gray-200 mt-auto">
    <div class="container py-8">
        <div class="row">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <div class="footer-brand mb-4">
                    <div class="d-flex align-items-center space-x-3 mb-3">
                        <img src="assets/images/logo.png" alt="Woolify" class="brand-logo">
                        <span class="brand-text">Woolify</span>
                    </div>
                    <p class="text-muted">Transforming the wool supply chain with transparency and efficiency.</p>
                </div>
                <div class="social-links">
                    <a href="#" class="text-muted me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-muted me-3"><i class="fab fa-linkedin"></i></a>
                    <a href="#" class="text-muted"><i class="fab fa-github"></i></a>
                </div>
            </div>
            <div class="col-6 col-lg-2 mb-4 mb-lg-0">
                <h6 class="text-uppercase mb-3">Product</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Features</a></li>
                    <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Pricing</a></li>
                    <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Documentation</a></li>
                    <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Release Notes</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2 mb-4 mb-lg-0">
                <h6 class="text-uppercase mb-3">Company</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="#" class="text-muted text-decoration-none">About Us</a></li>
                    <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Careers</a></li>
                    <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Press</a></li>
                    <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Contact</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 class="text-uppercase mb-3">Newsletter</h6>
                <p class="text-muted mb-3">Stay updated with our latest features and releases.</p>
                <form class="newsletter-form">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Enter your email">
                        <button class="btn btn-primary" type="submit">Subscribe</button>
                    </div>
                </form>
            </div>
        </div>
        <hr class="my-4">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> Woolify. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <ul class="list-inline mb-0">
                    <li class="list-inline-item">
                        <a href="#" class="text-muted text-decoration-none">Terms</a>
                    </li>
                    <li class="list-inline-item">
                        <span class="text-muted">·</span>
                    </li>
                    <li class="list-inline-item">
                        <a href="#" class="text-muted text-decoration-none">Privacy</a>
                    </li>
                    <li class="list-inline-item">
                        <span class="text-muted">·</span>
                    </li>
                    <li class="list-inline-item">
                        <a href="#" class="text-muted text-decoration-none">Security</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<?php
// includes/footer.php 
// This file primarily contains the closing body/html tags and common JS includes.
?>

    <!-- Bootstrap Bundle JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Common JS like sidebar toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggleButtons = document.querySelectorAll('.sidebar-toggle');
            const dashboardContainer = document.querySelector('.dashboard-container');
            const body = document.body; 

            if (dashboardContainer) { 
                 sidebarToggleButtons.forEach(button => {
                    button.addEventListener('click', (e) => {
                        e.preventDefault(); // Prevent default link behavior if it's an <a> tag
                        // Toggle class on body for global styling changes (like collapsing sidebar)
                        body.classList.toggle('sidebar-collapsed'); 
                        // You might also toggle on dashboardContainer if specific layout adjustments are needed
                        // dashboardContainer.classList.toggle('sidebar-collapsed'); 
                        
                        // Optional: Save preference in localStorage
                        // if (body.classList.contains('sidebar-collapsed')) {
                        //    localStorage.setItem('sidebarState', 'collapsed');
                        // } else {
                        //    localStorage.setItem('sidebarState', 'expanded');
                        // }
                    });
                });
                
                // Optional: Check localStorage on load to restore sidebar state
                // if (localStorage.getItem('sidebarState') === 'collapsed') {
                //    body.classList.add('sidebar-collapsed');
                //    dashboardContainer.classList.add('sidebar-collapsed');
                // }

            } else {
                console.warn('Dashboard container not found for sidebar toggle.');
            }
        });
    </script>

<style>
/* Footer Brand Styling */
.footer-brand .brand-logo {
    height: 40px;
    width: auto;
    border-radius: 1.5rem;
    transition: transform 0.3s ease;
}

.footer-brand:hover .brand-logo {
    transform: scale(1.05);
}

.footer-brand .brand-text {
    font-size: 1.5rem;
    font-weight: 700;
    background: linear-gradient(to right, #0d6efd, #0dcaf0);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-left: 0.75rem;
}

.space-x-3 {
    gap: 0.75rem;
}
</style>

</body>
</html>