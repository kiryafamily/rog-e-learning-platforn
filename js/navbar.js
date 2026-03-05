// Enhanced mobile menu with smooth animations
// This script handles the mobile menu toggle functionality, including smooth animations for opening and closing the menu. It also changes the menu icon from a hamburger to a close icon when the menu is active. Additionally, it includes functionality to close the menu when clicking outside of it or when clicking on a navigation link (useful for single-page applications). The script also adds a "scrolled" class to the navbar when the user scrolls down, allowing for styling changes based on scroll position. Finally, it highlights the active navigation link based on the current scroll position, providing visual feedback to users about their location on the page.
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenu = document.querySelector('.mobile-menu');
    const navMenu = document.querySelector('.nav-menu');
    const navbar = document.querySelector('.navbar');
    
    // Toggle mobile menu
    if (mobileMenu) {
        mobileMenu.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
            navMenu.classList.toggle('active');
            
            // Change icon
            const icon = this.querySelector('i');
            if (navMenu.classList.contains('active')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        });
    }
    
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!navMenu.contains(e.target) && !mobileMenu.contains(e.target) && navMenu.classList.contains('active')) {
            navMenu.classList.remove('active');
            mobileMenu.classList.remove('active');
            const icon = mobileMenu.querySelector('i');
            icon.className = 'fas fa-bars';
        }
    });
    
    // Close menu when clicking a link (for single page navigation)
    navMenu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                navMenu.classList.remove('active');
                mobileMenu.classList.remove('active');
                const icon = mobileMenu.querySelector('i');
                icon.className = 'fas fa-bars';
            }
        });
    });
    
    // Add scrolled class to navbar on scroll
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    
    // Highlight active link based on scroll position (for single page)
    const sections = document.querySelectorAll('section[id]');
    window.addEventListener('scroll', function() {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (scrollY >= (sectionTop - 200)) {
                current = section.getAttribute('id');
            }
        });
        
        navMenu.querySelectorAll('a').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    });
});