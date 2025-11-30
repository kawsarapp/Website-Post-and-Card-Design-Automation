<script>
    window.applyAdminTemplate = function(imageUrl, layoutName) {
        
        console.log("🚀 Applying Template:", imageUrl, "Layout:", layoutName);

        if (layoutName) {
            if(window.userSettings) {
                window.userSettings.titlePos = null;
                window.userSettings.datePos = null;
            }
        }

        if (typeof window.savePreference === 'function') {
            window.savePreference('frameUrl', imageUrl);
        }

        const objects = canvas.getObjects();
        let titleObj = objects.find(obj => obj.isHeadline);
        let dateObj = objects.find(obj => obj.isDate);
        let mainImgObj = objects.find(obj => obj.isMainImage);


        for (let i = objects.length - 1; i >= 0; i--) {
            let obj = objects[i];
            if (obj.isMainImage || obj.isHeadline || obj.isDate) continue; 
            canvas.remove(obj);
        }

        fabric.Image.fromURL(imageUrl, function(img) {
            if(!img) return;

            img.set({ 
                left: 0, top: 0, 
                scaleX: canvas.width / img.width, 
                scaleY: canvas.height / img.height, 
                selectable: false, evented: false, 
                isFrame: true 
            });

            window.frameObj = img;
            canvas.add(img);


            if(mainImgObj) canvas.sendToBack(mainImgObj);
            canvas.sendToBack(img);
            if(mainImgObj) canvas.bringForward(img); 

            if(titleObj) canvas.bringToFront(titleObj);
            if(dateObj) canvas.bringToFront(dateObj);

            const layouts = {
                
                'ntv': { 
                    title: { top: 820, left: 540, originX: 'center', textAlign: 'center', width: 900 },
                    date:  { top: 100, left: 950, originX: 'right' } 
                },

                // ২. RTV (টাইটেল নিচে মাঝখানে, ডেট উপরে বামে)
                'rtv': { 
                    title: { top: 603, left: 525, originX: 'center', textAlign: 'center', width: 950 },
                    date:  { top: 50, left: 50, originX: 'left' } 
                },

                // ৩. Dhaka Post (টাইটেল নিচে, ডেট তার ঠিক উপরে)
                'dhakapost': { 
                    title: { top: 850, left: 540, originX: 'center', textAlign: 'center', width: 980 },
                    date:  { top: 800, left: 540, originX: 'center' } 
                },

                // ৪. Today Events (টাইটেল একটু উপরে, ডেট ডানে)
                'todayevents': { 
                    title: { top: 750, left: 540, originX: 'center', textAlign: 'center', width: 900 },
                    date:  { top: 50, left: 950, originX: 'right' } 
                },

                // ৫. ডিফল্ট স্টাইল (যদি নাম না মেলে)
                'bottom': { 
                    title: { top: 800, left: 540, originX: 'center', textAlign: 'center', width: 980 },
                    date:  { top: 50, left: 50, originX: 'left' } 
                }
            };

            // 🔥 ৬. লজিক: আপনার পাঠানো নাম অনুযায়ী লেআউট খুঁজে বের করা
            const defaultLayout = layouts['bottom'];
            
            // এখানে layoutName হিসেবে 'ntv', 'rtv' ইত্যাদি আসবে
            const targetLayout = layouts[layoutName] || defaultLayout; 

            // ৭. টাইটেল পজিশন বসানো
            if(titleObj) {
                // যদি আগে ম্যানুয়াল পজিশন সেভ না থাকে, অথবা নতুন কার্ডে ক্লিক করা হয়
                if (!window.userSettings?.titlePos || layoutName) {
                    titleObj.set({
                        top: targetLayout.title.top,
                        left: targetLayout.title.left,
                        width: targetLayout.title.width || 980,
                        textAlign: targetLayout.title.textAlign || 'center',
                        originX: targetLayout.title.originX || 'left'
                    });
                    titleObj.setCoords();
                } else {
                    // সেভ করা ম্যানুয়াল পজিশন
                    titleObj.set(window.userSettings.titlePos);
                    titleObj.setCoords();
                }
            }

            // ৮. ডেট পজিশন বসানো
            if(dateObj) {
                if (!window.userSettings?.datePos || layoutName) {
                    dateObj.set({
                        top: targetLayout.date.top,
                        left: targetLayout.date.left,
                        originX: targetLayout.date.originX || 'left'
                    });
                    dateObj.setCoords();
                } else {
                    dateObj.set(window.userSettings.datePos);
                    dateObj.setCoords();
                }
            }

            canvas.requestRenderAll();
            
            // হিস্ট্রি সেভ
            if (typeof window.saveHistory === 'function') window.saveHistory();

        }, { crossOrigin: 'anonymous' });
    };
</script>