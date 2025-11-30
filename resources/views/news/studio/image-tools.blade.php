<script>
    // 1. মেইন ইমেজ সেটআপ
    window.setupMainImage = function(img) {
        if (typeof mainImageObj !== 'undefined' && mainImageObj) canvas.remove(mainImageObj);
        
        canvas.getObjects().forEach(obj => { 
            if (obj.isMainImage) canvas.remove(obj); 
        });

        window.scaleAndCenterImage(img);
        img.set({ selectable: true, evented: true, isMainImage: true });
        window.mainImageObj = img;
        canvas.add(img); 
        canvas.sendToBack(img);
    };

    window.scaleAndCenterImage = function(img) {
        const scale = Math.max(canvas.width / img.width, canvas.height / img.height);
        img.set({ 
            scaleX: scale, scaleY: scale, 
            left: (canvas.width - img.width * scale) / 2, 
            top: (canvas.height - img.height * scale) / 2 
        });
    };

    // 2. কাস্টম ফ্রেম আপলোড (SERVER UPLOAD ADDED)
    window.addCustomFrame = function(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];

            // A. তাৎক্ষণিক প্রিভিউ (Local Reader) - যাতে ইউজার ওয়েট না করে
            const r = new FileReader();
            r.onload = function (e) {
                fabric.Image.fromURL(e.target.result, function(img) {
                    window.setupFrameObj(img);
                });
            };
            r.readAsDataURL(file);

            // B. সার্ভারে আপলোড (Background Process)
            const formData = new FormData();
            formData.append('frame', file);
            
            // CSRF Token
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch("{{ route('settings.upload-frame') }}", {
                method: "POST",
                headers: { "X-CSRF-TOKEN": token },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    console.log("✅ Frame Uploaded to Server:", data.url);
                    
                    // গ্লোবাল ভেরিয়েবল আপডেট (যাতে রিস্টোর করলে পায়)
                    window.userSettings.frameUrl = data.url;
                    
                    // লোকাল স্টোরেজ আপডেট
                    if(typeof window.savePreference === 'function') {
                        window.savePreference('frameUrl', data.url);
                    }
                    alert("✅ ফ্রেম সেভ হয়েছে! পরবর্তী বার এটি অটোমেটিক লোড হবে।");
                } else {
                    alert("⚠️ সার্ভারে সেভ হয়নি: " + data.message);
                }
            })
            .catch(err => console.error("Upload Error:", err));
        }
    };

    // Helper: ফ্রেম ক্যানভাসে বসানো
    window.setupFrameObj = function(img) {
        if (typeof frameObj !== 'undefined' && frameObj) canvas.remove(frameObj);
        
        img.set({ 
            left: 0, top: 0, 
            scaleX: canvas.width / img.width, 
            scaleY: canvas.height / img.height, 
            selectable: false, evented: false, 
            isFrame: true 
        });
        
        window.frameObj = img; 
        canvas.add(img); 
        canvas.bringToFront(img);
        
        // টাইটেল উপরে আনা
        const title = canvas.getObjects().find(o => o.isHeadline);
        if (title) title.bringToFront();
        
        if(typeof window.saveHistory === 'function') window.saveHistory();
    };

    // অন্যান্য টুলস...
    window.addImageOnCanvas = function(input) {
        if (input.files && input.files[0]) {
            const r = new FileReader();
            r.onload = function (e) {
                fabric.Image.fromURL(e.target.result, function(img) { 
                    img.scaleToWidth(300); 
                    canvas.add(img); 
                    canvas.centerObject(img); 
                    canvas.setActiveObject(img); 
                    if(typeof window.saveHistory === 'function') window.saveHistory();
                });
            };
            r.readAsDataURL(input.files[0]);
        }
    };

    window.setBackgroundImage = function(input) {
        if (input.files && input.files[0]) {
            const r = new FileReader();
            r.onload = function (e) {
                fabric.Image.fromURL(e.target.result, function(img) { 
                    window.setupMainImage(img); 
                    canvas.renderAll(); 
                    if(typeof window.saveHistory === 'function') window.saveHistory();
                });
            };
            r.readAsDataURL(input.files[0]);
        }
    };
    
    window.setBackgroundColor = function(color) {
        canvas.backgroundColor = color; 
        canvas.renderAll(); 
        if(typeof window.saveHistory === 'function') window.saveHistory();
    };

    window.addProfileLogo = function(url) {
        fabric.Image.fromURL(url, function(img) {
            img.scaleToWidth(150); 
            img.set({ left: 880, top: 50 }); 
            canvas.add(img); 
            canvas.bringToFront(img);
        }, { crossOrigin: 'anonymous' });
    };
	
	
	window.removeCustomFrame = function() {
    if (typeof frameObj !== 'undefined' && frameObj) {
        canvas.remove(frameObj);
        window.frameObj = null;
    }
    
    // সেটিংস থেকে ফ্রেম রিমুভ করা
    window.userSettings.frameUrl = null;
    if(typeof window.savePreference === 'function') {
        window.savePreference('frameUrl', null);
    }

    // ডাটাবেস আপডেট (অপশনাল, তবে ভালো প্র্যাকটিস)
    // আপনি চাইলে এখানেও একটি fetch request পাঠিয়ে DB থেকে রিমুভ করতে পারেন
    
    alert("ফ্রেম রিমুভ করা হয়েছে!");
    if(typeof window.saveHistory === 'function') window.saveHistory();
};




</script>