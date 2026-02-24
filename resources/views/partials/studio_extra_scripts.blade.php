{{-- 🔥 EXTRA SCRIPTS FOR FEATURES --}}
<script>
    let studioCategories = [];

    window.applyTextColor = function(color) {
        const activeObj = canvas.getActiveObject();
        
        if (activeObj && (activeObj.type === 'i-text' || activeObj.type === 'textbox')) {
            if (activeObj.isEditing && activeObj.selectionStart !== activeObj.selectionEnd) {
                activeObj.setSelectionStyles({ fill: color });
            } else {
                activeObj.set('fill', color);
            }
            canvas.requestRenderAll();
            if(typeof saveHistory === 'function') saveHistory();
        }
    };

    window.openPublishModal = function() {
        const modal = document.getElementById('studioPublishModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        if(studioCategories.length === 0) {
            refreshStudioCategories();
        } else {
            populateStudioDropdown();
        }
    };

    window.refreshStudioCategories = function() {
        const btn = document.querySelector('button[onclick="refreshStudioCategories()"]');
        const select = document.getElementById('modalCategory');
        
        if(btn) { btn.innerText = "⏳ Loading..."; btn.disabled = true; }
        if(select) select.innerHTML = '<option value="">⏳ Fetching data...</option>';

        fetch("{{ route('settings.fetch-categories') }}")
            .then(res => res.json())
            .then(data => {
                if(!data.error) {
                    studioCategories = data;
                    populateStudioDropdown();
                    if(btn) btn.innerText = "✅ Updated";
                } else {
                    alert("Error: " + data.error);
                }
            })
            .catch(err => console.error("Category Fetch Error:", err))
            .finally(() => {
                if(btn) { 
                    setTimeout(() => { 
                        btn.innerHTML = '🔄 Refresh List'; 
                        btn.disabled = false; 
                    }, 1500); 
                }
            });
    };

    window.populateStudioDropdown = function() {
        const select = document.getElementById('modalCategory');
        if(!select) return;
        
        select.innerHTML = '<option value="">-- Select Category --</option>';
        
        select.innerHTML += '<option value="1">Uncategorized (Default)</option>';

        studioCategories.forEach(cat => {
            let option = document.createElement('option');
            option.value = cat.id;
            option.text = `${cat.name} (ID: ${cat.id})`;
            select.appendChild(option);
        });
    };
    
    window.closePublishModal = function() {
        document.getElementById('studioPublishModal').classList.add('hidden');
        document.getElementById('studioPublishModal').classList.remove('flex');
    };
    
    function showCanvasLoader() {
        document.getElementById('canvas-loader').classList.remove('hidden');
    }
    function hideCanvasLoader() {
        document.getElementById('canvas-loader').classList.add('hidden');
    }
</script>