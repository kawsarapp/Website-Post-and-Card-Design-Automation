@stack('scripts')
<script>
    // 3-Dot Toggle Logic
    const dotBtn = document.getElementById('dotMenuBtn');
    const dotDropdown = document.getElementById('dotDropdown');
    if(dotBtn) {
        dotBtn.addEventListener('click', (e) => { 
            e.stopPropagation(); 
            dotDropdown.classList.toggle('hidden'); 
        });
        document.addEventListener('click', (e) => { 
            if (!dotBtn.contains(e.target) && !dotDropdown.contains(e.target)) {
                dotDropdown.classList.add('hidden'); 
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
    if(mobileBtn) mobileBtn.addEventListener('click', (e) => { e.stopPropagation(); toggleMobileMenu(); });

    // Auto hide flash messages
    setTimeout(() => {
        const success = document.getElementById('flash-success');
        const error = document.getElementById('flash-error');
        if(success) success.remove();
        if(error) error.remove();
    }, 4000);

    function markRead() { fetch('{{ route("notifications.read") }}').catch(e => console.error(e)); }
</script>