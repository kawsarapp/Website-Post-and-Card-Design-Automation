<script>
    function loadFonts() {
        WebFont.load({
            google: { families: ['Hind Siliguri:300,400,500,600,700', 'Noto Sans Bengali', 'Baloo Da 2', 'Galada', 'Anek Bangla', 'Tiro Bangla', 'Mina', 'Oswald', 'Roboto', 'Montserrat', 'Lato', 'Open Sans'] }
        });
    }

    function getBanglaDate() {
        const date = new Date();
        const months = ["জানুয়ারি", "ফেব্রুয়ারি", "মার্চ", "এপ্রিল", "মে", "জুন", "জুলাই", "আগস্ট", "সেপ্টেম্বর", "অক্টোবর", "নভেম্বর", "ডিসেম্বর"];
        const banglaDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        const convert = (num) => num.toString().split('').map(d => banglaDigits[d] || d).join('');
        return `${convert(date.getDate())} ${months[date.getMonth()]}, ${convert(date.getFullYear())}`;
    }

    function addDateText() {
        const oldDate = canvas.getObjects().find(o => o.isDate);
        if(oldDate) canvas.remove(oldDate);
        const dateText = new fabric.Text(getBanglaDate(), { left: 50, top: 50, fontSize: 30, fill: '#fff', fontFamily: 'Hind Siliguri', backgroundColor: '#d90429', padding: 10, isDate: true });
        canvas.add(dateText); canvas.bringToFront(dateText);
    }

    function addText(text, size = 50) {
        const t = new fabric.Textbox(text, { left: 100, top: 100, width: 400, fontSize: size, fill: '#fff', fontFamily: 'Hind Siliguri', fontWeight: 'bold', textAlign: 'center', backgroundColor: 'rgba(0,0,0,0.5)' });
        canvas.add(t); canvas.setActiveObject(t); switchTab('text'); saveHistory();
    }

    function changeFont(fontName) {
        const obj = canvas.getActiveObject();
        if (obj) {
            const cleanFont = fontName.replace(/'/g, "").split(',')[0].trim();
            WebFont.load({ google: { families: [cleanFont] }, active: function() { obj.set("fontFamily", cleanFont); canvas.requestRenderAll(); if(obj.isHeadline) savePreference('font', fontName); saveHistory(); } });
        }
    }

    function updateActiveProp(prop, value) {
        const obj = canvas.getActiveObject();
        if (obj) { 
            obj.set(prop, value); canvas.renderAll(); saveHistory(); 
            if(obj.isHeadline) {
                if(prop === 'fill') savePreference('color', value);
                if(prop === 'backgroundColor') savePreference('bgColor', value);
                if(prop === 'fontSize') savePreference('size', value);
            }
        }
        if(prop==='fontSize') document.getElementById('val-size').innerText = value;
    }

    function toggleStyle(style) {
        const obj = canvas.getActiveObject();
        if (!obj) return;
        if (style === 'bold') obj.set('fontWeight', obj.fontWeight === 'bold' ? 'normal' : 'bold');
        if (style === 'italic') obj.set('fontStyle', obj.fontStyle === 'italic' ? 'normal' : 'italic');
        if (style === 'underline') obj.set('underline', !obj.underline);
        canvas.renderAll(); saveHistory();
    }

    function toggleShadow(checked) {
        const obj = canvas.getActiveObject();
        if (obj) { obj.set('shadow', checked ? new fabric.Shadow({ color: 'rgba(0,0,0,0.8)', blur: 15, offsetX: 5, offsetY: 5 }) : null); canvas.renderAll(); saveHistory(); }
    }

    function toggleStroke(checked) {
        const obj = canvas.getActiveObject();
        if (obj) { obj.set('stroke', checked ? '#000' : null); obj.set('strokeWidth', checked ? 2 : 0); canvas.renderAll(); saveHistory(); }
    }

    function applyLastStyles() {
        setTimeout(() => {
            const titleObj = canvas.getObjects().find(obj => obj.isHeadline);
            if(titleObj) {
                const cleanFont = userSettings.defaultFont.replace(/'/g, "").split(',')[0].trim();
                titleObj.set({ fontFamily: cleanFont, fill: userSettings.defaultColor, backgroundColor: userSettings.defaultBg, fontSize: parseInt(userSettings.defaultSize) });
                WebFont.load({ google: { families: [cleanFont] }, active: function() { canvas.requestRenderAll(); } });
                const fs = document.getElementById('font-family'); if(fs) fs.value = userSettings.defaultFont;
            }
        }, 500);
    }
</script>