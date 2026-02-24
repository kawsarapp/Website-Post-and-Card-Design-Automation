<script>
    // --- Template Modal Script ---
    function openTemplateModal(userId, userName, allowedTemplates, defaultTemplate) {
        document.getElementById('modalUserName').innerText = userName;
        document.getElementById('templateForm').action = `/admin/users/${userId}/templates`;
        
        document.querySelectorAll('input[name="templates[]"]').forEach(el => el.checked = false);
        if (Array.isArray(allowedTemplates)) {
            allowedTemplates.forEach(val => {
                const checkbox = document.querySelector(`input[name="templates[]"][value="${val}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }
        const select = document.querySelector('select[name="default_template"]');
        if(select) select.value = defaultTemplate;

        const modal = document.getElementById('templateModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeTemplateModal() {
        document.getElementById('templateModal').classList.add('hidden');
        document.getElementById('templateModal').classList.remove('flex');
    }
    
    // --- Limit Modal Script ---
    function openLimitModal(userId, userName, currentLimit) {
        document.getElementById('limitModalUserName').innerText = userName;
        document.getElementById('limitInput').value = currentLimit;
        document.getElementById('limitForm').action = `/admin/users/${userId}/limit`;
        
        const modal = document.getElementById('limitModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeLimitModal() {
        document.getElementById('limitModal').classList.add('hidden');
        document.getElementById('limitModal').classList.remove('flex');
    }
    
    // --- Source Modal Script ---
    function openSourceModal(userId, userName, assignedWebsites) {
        document.getElementById('sourceModalUserName').innerText = userName;
        document.getElementById('sourceForm').action = `/admin/users/${userId}/websites`;
        
        const checkboxes = document.querySelectorAll('#sourceForm input[name="websites[]"]');
        checkboxes.forEach(el => el.checked = false);
        if (Array.isArray(assignedWebsites)) {
            assignedWebsites.forEach(id => {
                const checkbox = document.querySelector(`#sourceForm input[value="${id}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }
        const modal = document.getElementById('sourceModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeSourceModal() {
        document.getElementById('sourceModal').classList.add('hidden');
        document.getElementById('sourceModal').classList.remove('flex');
    }

    // --- Scraper Modal Script ---
    function openScraperModal(userId, userName, currentMethod) {
        document.getElementById('scraperUserName').innerText = userName;
        document.getElementById('scraperForm').action = `/admin/users/${userId}/scraper`;
        document.getElementById('scraperInput').value = currentMethod || "";
        
        const modal = document.getElementById('scraperModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeScraperModal() {
        document.getElementById('scraperModal').classList.add('hidden');
        document.getElementById('scraperModal').classList.remove('flex');
    }
    
    // --- Create User Modal ---
    function openCreateUserModal() {
        const modal = document.getElementById('createUserModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeCreateUserModal() {
        document.getElementById('createUserModal').classList.add('hidden');
        document.getElementById('createUserModal').classList.remove('flex');
    }

    // --- Edit User Modal (Updated for Staff Limit) ---
    function openEditUserModal(userId, name, email, staffLimit) {
        document.getElementById('editName').value = name;
        document.getElementById('editEmail').value = email;
        
        // 🔥 Staff Limit এর ভ্যালু সেট করা হচ্ছে (না থাকলে ডিফল্ট 0)
        document.getElementById('editStaffLimit').value = staffLimit || 0; 
        
        document.getElementById('editUserForm').action = `/admin/users/${userId}/update`;
        
        const modal = document.getElementById('editUserModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeEditUserModal() {
        document.getElementById('editUserModal').classList.add('hidden');
        document.getElementById('editUserModal').classList.remove('flex');
    }
    
    // --- Permission Modal ---
    function openPermissionModal(userId, userName, userPerms) {
        document.getElementById('permUserName').innerText = userName;
        document.getElementById('permissionForm').action = `/admin/users/${userId}/permissions`;
        
        const checkboxes = document.querySelectorAll('#permissionForm input[name="permissions[]"]');
        checkboxes.forEach(cb => {
            cb.checked = Array.isArray(userPerms) && userPerms.includes(cb.value);
        });

        const modal = document.getElementById('permissionModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closePermissionModal() {
        document.getElementById('permissionModal').classList.add('hidden');
        document.getElementById('permissionModal').classList.remove('flex');
    }
</script>