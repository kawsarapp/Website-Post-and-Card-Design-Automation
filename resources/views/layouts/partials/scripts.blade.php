@stack('scripts')
<script>
    // 🔥 UPDATED: 3-Dot Toggle Logic (With Smooth Animation)
    const dotBtn = document.getElementById('dotMenuBtn');
    const dotDropdown = document.getElementById('dotDropdown');
    
    if(dotBtn && dotDropdown) {
        dotBtn.addEventListener('click', (e) => { 
            e.stopPropagation(); 
            
            if (dotDropdown.classList.contains('hidden')) {
                // Open Animation
                dotDropdown.classList.remove('hidden');
                // Allow a tiny delay so display block renders before transition
                setTimeout(() => {
                    dotDropdown.classList.remove('opacity-0', 'scale-95');
                    dotDropdown.classList.add('opacity-100', 'scale-100');
                }, 10);
            } else {
                // Close Animation
                dotDropdown.classList.remove('opacity-100', 'scale-100');
                dotDropdown.classList.add('opacity-0', 'scale-95');
                setTimeout(() => {
                    dotDropdown.classList.add('hidden');
                }, 200); // Matches the duration-200 in CSS
            }
        });

        // Click outside to close smoothly
        document.addEventListener('click', (e) => { 
            if (!dotBtn.contains(e.target) && !dotDropdown.contains(e.target) && !dotDropdown.classList.contains('hidden')) {
                dotDropdown.classList.remove('opacity-100', 'scale-100');
                dotDropdown.classList.add('opacity-0', 'scale-95');
                setTimeout(() => {
                    dotDropdown.classList.add('hidden');
                }, 200);
            }
        });
    }

    // Mobile Menu Toggle Logic
    const mobileContainer = document.getElementById('mobileMenuContainer');
    const mobileOverlay = document.getElementById('mobileOverlay');
    const mobileSheet = document.getElementById('mobileMenuSheet');
    const mobileBtn = document.getElementById('mobileMenuBtn');
    let isMenuOpen = false;

    function toggleMobileMenu() {
        isMenuOpen = !isMenuOpen;
        if (isMenuOpen) {
            mobileContainer.classList.remove('hidden');
            setTimeout(() => { 
                mobileOverlay.classList.remove('opacity-0'); 
                mobileSheet.classList.remove('translate-y-full'); 
            }, 10);
            document.body.style.overflow = 'hidden';
        } else {
            mobileOverlay.classList.add('opacity-0');
            mobileSheet.classList.add('translate-y-full');
            setTimeout(() => { mobileContainer.classList.add('hidden'); }, 300);
            document.body.style.overflow = '';
        }
    }
    
    if(mobileBtn) mobileBtn.addEventListener('click', (e) => { 
        e.stopPropagation(); 
        toggleMobileMenu(); 
    });

    // Auto hide flash messages with smooth fade out
    setTimeout(() => {
        const success = document.getElementById('flash-success');
        const error = document.getElementById('flash-error');
        if(success) {
            success.style.transition = "opacity 0.5s ease";
            success.style.opacity = "0";
            setTimeout(() => success.remove(), 500);
        }
        if(error) {
            error.style.transition = "opacity 0.5s ease";
            error.style.opacity = "0";
            setTimeout(() => error.remove(), 500);
        }
    }, 4000);

    function markRead() { fetch('{{ route("notifications.read") }}').catch(e => console.error(e)); }
</script>