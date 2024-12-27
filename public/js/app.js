// Navigation menu for mobile
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-button');
    const mobileMenu = document.querySelector('.mobile-menu');

    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }

    // Date picker initialization
    const checkInDate = document.querySelector('input[type="date"]');
    const checkOutDate = document.querySelector('input[type="date"]');

    if (checkInDate && checkOutDate) {
        // Set minimum date as today
        const today = new Date().toISOString().split('T')[0];
        checkInDate.min = today;
        
        // Update checkout minimum date when checkin changes
        checkInDate.addEventListener('change', (e) => {
            checkOutDate.min = e.target.value;
            if (checkOutDate.value && checkOutDate.value < e.target.value) {
                checkOutDate.value = e.target.value;
            }
        });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Form validation
    const searchForm = document.querySelector('form');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Basic validation
            const checkIn = searchForm.querySelector('input[name="check-in"]');
            const checkOut = searchForm.querySelector('input[name="check-out"]');
            
            if (checkIn && checkOut && checkIn.value && checkOut.value) {
                // Add your form submission logic here
                console.log('Form submitted:', {
                    checkIn: checkIn.value,
                    checkOut: checkOut.value
                });
            } else {
                alert('Please fill in all required fields');
            }
        });
    }

    // Lazy loading images
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));
});
