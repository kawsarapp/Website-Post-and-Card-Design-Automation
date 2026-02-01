@extends('layouts.app')

@section('content')
<style>
    /* Workspace Layout */
    .builder-container { height: calc(100vh - 100px); overflow: hidden; background: #333; }
    .sidebar-panel { height: 100%; overflow-y: auto; background: white; border-right: 1px solid #444; }
    
    /* Canvas Area Container */
    .canvas-area { 
        height: 100%; 
        width: 100%;
        display: flex; 
        align-items: center; 
        justify-content: center; 
        background-color: #555;
        overflow: auto; /* স্ক্রলবার আসবে যদি জুম অনেক বেশি হয় */
        position: relative;
    }
    
    /* Canvas Shadow */
    .canvas-wrapper { box-shadow: 0 0 50px rgba(0,0,0,0.5); background: white; }
    
    /* Control Buttons */
    .element-btn { transition: all 0.2s; border: 1px dashed #ccc; text-align: left; background: #f8f9fa; }
    .element-btn:hover { border-color: #4f46e5; background: #eef2ff; color: #4f46e5; }
    
    /* Properties Panel */
    #properties-panel { display: none; background: #f8fafc; padding: 15px; border-radius: 8px; margin-top: 15px; border: 1px solid #e2e8f0; }
</style>

<div class="container-fluid p-0 builder-container">
    <div class="row g-0 h-100">
        
        {{-- বাম পাশ: টুলবক্স --}}
        <div class="col-lg-3 col-md-4 sidebar-panel p-4 shadow-sm z-1">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark m-0">🛠️ Builder</h5>
                <a href="{{ route('admin.templates.index') }}" class="btn btn-sm btn-outline-secondary">Exit</a>
            </div>
            
            {{-- 1. Frame Upload --}}
            <div class="mb-4 p-3 bg-light rounded border">
                <label class="form-label text-uppercase fw-bold small text-muted">1. Upload Frame</label>
                <input type="file" id="frameUpload" class="form-control form-control-sm" accept="image/png">
                <small class="text-xs text-muted d-block mt-1">PNG format required.</small>
            </div>

            {{-- 2. Tools --}}
            <div class="mb-4">
                <label class="form-label text-uppercase fw-bold small text-muted">2. Add Elements</label>
                <div class="d-grid gap-2">
                    <button onclick="addPlaceholder('headline')" class="btn element-btn btn-sm">
                        <i class="fas fa-heading me-2"></i> Headline Area
                    </button>
                    <button onclick="addPlaceholder('main_image')" class="btn element-btn btn-sm">
                        <i class="fas fa-image me-2"></i> News Image Area
                    </button>
                    <button onclick="addPlaceholder('date')" class="btn element-btn btn-sm">
                        <i class="fas fa-calendar-alt me-2"></i> Date Area
                    </button>
                </div>
            </div>

            {{-- 3. Live Properties --}}
            <div id="properties-panel">
                <label class="fw-bold small text-dark mb-2 border-bottom w-100 pb-1">⚙️ Settings</label>
                
                <div class="mb-2">
                    <label class="small text-muted">Font Size / Scale</label>
                    <input type="range" class="form-range" min="10" max="200" id="propSize" oninput="updateActiveProp('size', this.value)">
                </div>

                <div class="mb-2">
                    <label class="small text-muted">Color</label>
                    <input type="color" class="form-control form-control-color w-100" id="propColor" oninput="updateActiveProp('fill', this.value)">
                </div>

                <div class="d-flex justify-content-between mt-3 gap-2">
                    <button onclick="centerActive()" class="btn btn-outline-secondary btn-sm flex-fill" title="Center"><i class="fas fa-align-center"></i></button>
                    <button onclick="bringToFront()" class="btn btn-outline-primary btn-sm flex-fill" title="Up"><i class="fas fa-arrow-up"></i></button>
                    <button onclick="sendToBack()" class="btn btn-outline-primary btn-sm flex-fill" title="Down"><i class="fas fa-arrow-down"></i></button>
                    <button onclick="deleteActive()" class="btn btn-outline-danger btn-sm flex-fill" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
            </div>

            <hr class="my-4">

            {{-- 4. Save --}}
            <div class="mb-3">
                <label class="form-label text-uppercase fw-bold small text-muted">3. Save Template</label>
                <input type="text" id="templateName" class="form-control mb-2" placeholder="Template Name">
                <button onclick="saveTemplate()" id="saveBtn" class="btn btn-success w-100 fw-bold shadow-sm">
                    💾 Save Template
                </button>
            </div>
        </div>

        {{-- ডান পাশ: ক্যানভাস --}}
        <div class="col-lg-9 col-md-8 canvas-area bg-dark">
            
            {{-- Zoom Controls --}}
            <div class="position-absolute bottom-0 end-0 m-4 btn-group shadow z-3">
                <button onclick="setZoom(0.5)" class="btn btn-light btn-sm fw-bold">50%</button>
                <button onclick="setZoom(0.75)" class="btn btn-light btn-sm fw-bold">75%</button>
                <button onclick="setZoom(1)" class="btn btn-primary btn-sm fw-bold">100%</button>
            </div>

            <div class="canvas-wrapper">
                <canvas id="builderCanvas" width="1080" height="1080"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Scripts --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

<script>
    const canvas = new fabric.Canvas('builderCanvas');
    let frameFile = null;

    // 🔥 ১. পারফেক্ট জুম সেটআপ (ডিফল্ট ছোট স্ক্রিনে ফিট হওয়ার জন্য)
    function initZoom() {
        const containerWidth = document.querySelector('.canvas-area').clientWidth;
        // ডিফল্ট জুম এমন হবে যাতে ১০৮০ পিক্সেল ক্যানভাস স্ক্রিনে ধরে
        let initialZoom = (containerWidth - 60) / 1080; 
        if(initialZoom > 1) initialZoom = 1;
        setZoom(initialZoom);
    }

    function setZoom(scale) {
        canvas.setZoom(scale);
        canvas.setWidth(1080 * scale);
        canvas.setHeight(1080 * scale);
        canvas.renderAll();
    }

    // উইন্ডো লোড হলে জুম ঠিক করবে
    setTimeout(initZoom, 200);
    window.addEventListener('resize', initZoom);


    // --- ২. ফ্রেম আপলোড (অটোমেটিক জুম রিসেট সহ) ---
    document.getElementById('frameUpload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if(!file) return;
        frameFile = file;

        const reader = new FileReader();
        reader.onload = function(f) {
            fabric.Image.fromURL(f.target.result, function(img) {
                // ক্যানভাসের আসল সাইজ সবসময় ১০৮০x১০৮০ থাকবে
                // আমরা শুধু দেখার জন্য জুম ইন/আউট করব
                
                canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), {
                    scaleX: 1080 / img.width,
                    scaleY: 1080 / img.height,
                    originX: 'left', originY: 'top'
                });
            });
        };
        reader.readAsDataURL(file);
    });


    // --- ৩. এলিমেন্ট যোগ করা ---
    window.addPlaceholder = function(type) {
        let obj;
        
        // ডিফল্ট সেন্টার পজিশন (১০৮০ এর হিসেবে)
        const centerX = 540;
        const centerY = 540;

        if(type === 'headline') {
            obj = new fabric.Textbox('Title Goes Here', {
                left: centerX, top: 800, width: 900,
                fontSize: 60, fontFamily: 'Arial', fill: '#000000',
                textAlign: 'center', originX: 'center', originY: 'center',
                data: { type: 'headline' }
            });
        } 
        else if(type === 'main_image') {
            obj = new fabric.Rect({
                left: centerX, top: 400, width: 980, height: 550,
                fill: '#cccccc', opacity: 0.5,
                stroke: 'red', strokeWidth: 4, strokeDashArray: [20, 20],
                originX: 'center', originY: 'center',
                data: { type: 'main_image' }
            });
        }
        else if(type === 'date') {
            obj = new fabric.Text('01 Jan 2025', {
                left: 950, top: 50, fontSize: 30, fill: '#666666',
                originX: 'right', originY: 'top',
                data: { type: 'date' }
            });
        }

        if(obj) {
            canvas.add(obj);
            canvas.setActiveObject(obj);
            canvas.renderAll();
        }
    }


    // --- ৪. প্রপার্টি প্যানেল লজিক ---
    canvas.on('selection:created', showProperties);
    canvas.on('selection:updated', showProperties);
    canvas.on('selection:cleared', () => { document.getElementById('properties-panel').style.display = 'none'; });

    function showProperties(e) {
        const obj = e.selected[0];
        document.getElementById('properties-panel').style.display = 'block';
        
        if(obj.fill) document.getElementById('propColor').value = obj.fill;
        
        if(obj.type === 'rect') {
            // ইমেজের ক্ষেত্রে সাইজ মানে স্কেল
            document.getElementById('propSize').value = (obj.width * obj.scaleX) / 10; 
        } else {
            document.getElementById('propSize').value = obj.fontSize;
        }
    }

    window.updateActiveProp = function(key, val) {
        const obj = canvas.getActiveObject();
        if(!obj) return;

        if(key === 'fill') {
            obj.set('fill', val);
        } else if (key === 'size') {
            if(obj.type === 'rect') {
                // ইমেজের সাইজ বাড়ালে width না বাড়িয়ে scale বাড়াচ্ছি
                const newScale = (parseInt(val) * 10) / obj.width;
                obj.scale(newScale);
            } else {
                obj.set('fontSize', parseInt(val));
            }
        }
        canvas.renderAll();
    }

    window.centerActive = function() {
        const obj = canvas.getActiveObject();
        if(obj) { canvas.centerObjectH(obj); canvas.renderAll(); }
    }
    window.deleteActive = function() {
        const obj = canvas.getActiveObject();
        if(obj) { canvas.remove(obj); canvas.discardActiveObject(); canvas.renderAll(); }
    }
    window.bringToFront = function() {
        const obj = canvas.getActiveObject();
        if(obj) { obj.bringToFront(); canvas.renderAll(); }
    }
    window.sendToBack = function() {
        const obj = canvas.getActiveObject();
        if(obj) { obj.sendToBack(); canvas.renderAll(); }
    }


    // --- ৫. সেভ টেমপ্লেট (ERROR FIX) ---
    window.saveTemplate = function() {
        const name = document.getElementById('templateName').value;
        if(!name || !frameFile) {
            alert('❌ নাম লিখুন এবং ফ্রেম আপলোড করুন।');
            return;
        }

        // লেআউট ডাটা তৈরি (জুম ফ্যাক্টর বাদ দিয়ে আসল কোর্ডিনেট নেওয়া)
        const currentZoom = canvas.getZoom();
        const layoutData = [];
        
        canvas.getObjects().forEach(obj => {
            if(obj.data && obj.data.type) {
                layoutData.push({
                    type: obj.data.type,
                    // জুম বাদে অরিজিনাল পজিশন সেভ করা হচ্ছে
                    left: obj.left, 
                    top: obj.top,
                    width: obj.width * obj.scaleX,
                    height: obj.height * obj.scaleY,
                    fill: obj.fill,
                    fontSize: obj.fontSize,
                    textAlign: obj.textAlign,
                    originX: obj.originX,
                    originY: obj.originY
                });
            }
        });

        if(layoutData.length === 0) {
            alert("⚠️ কমপক্ষে একটি এলিমেন্ট (Title/Image) যোগ করুন।");
            return;
        }

        // থাম্বনেইল তৈরি (অরিজিনাল সাইজ থেকে ছোট করে)
        // জুম ১ এ নিয়ে স্ন্যাপশট নেওয়া, তারপর আবার আগের জুমে ফেরত আসা
        canvas.setZoom(1);
        canvas.setWidth(1080);
        canvas.setHeight(1080);
        const thumbBase64 = canvas.toDataURL({ format: 'png', multiplier: 0.2 });
        // জুম রিস্টোর
        setZoom(currentZoom);

        // ডাটা পাঠানো
        const formData = new FormData();
        formData.append('name', name);
        formData.append('frame_image', frameFile);
        formData.append('layout_data', JSON.stringify(layoutData));
        formData.append('thumbnail_base64', thumbBase64);
        
        // 🔥 CSRF টোকেন ফিক্স
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if(csrfToken) {
            formData.append('_token', csrfToken.content);
        }

        const btn = document.getElementById('saveBtn');
        btn.innerHTML = '⏳ Saving...';
        btn.disabled = true;

        fetch("{{ route('admin.templates.store') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken ? csrfToken.content : ''
            },
            body: formData
        })
        .then(async res => {
            const data = await res.json();
            if(!res.ok) {
                throw new Error(data.message || 'Validation Failed');
            }
            return data;
        })
        .then(data => {
            if(data.success) {
                alert('✅ Template Saved!');
                window.location.href = data.redirect;
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('⚠️ Save Failed: ' + err.message);
        })
        .finally(() => {
            btn.innerHTML = '💾 Save Template';
            btn.disabled = false;
        });
    }
</script>
@endsection